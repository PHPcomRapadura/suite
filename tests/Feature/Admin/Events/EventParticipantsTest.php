<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Http\UploadedFile;

// ─── Acesso ───────────────────────────────────────────────────────────────────

it('guest recebe 401 na listagem de participantes', function () {
    $event = Event::factory()->create();
    $this->getJson("/admin/api/events/{$event->id}/participants")->assertUnauthorized();
});

it('admin lista participantes paginados', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->count(3)->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants")
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data', 'meta', 'summary']);
});

it('colaborador lista participantes', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->count(2)->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->getJson("/admin/api/events/{$event->id}/participants")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('listagem retorna summary com total, approved, checked_in e ticket_types', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->count(3)->for($event)->create(['payment_status' => 'Aprovado', 'ticket_type' => 'Lote 1']);
    EventParticipant::factory()->count(1)->for($event)->create(['payment_status' => 'Pendente', 'ticket_type' => 'Lote 2']);
    EventParticipant::factory()->count(2)->for($event)->checkedIn()->create(['payment_status' => 'Aprovado', 'ticket_type' => 'Lote 1']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants")
        ->assertOk()
        ->assertJsonPath('summary.total', 6)
        ->assertJsonPath('summary.approved', 5)
        ->assertJsonPath('summary.checked_in', 2);
});

// ─── Filtros ──────────────────────────────────────────────────────────────────

it('filtro search filtra por nome', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create(['first_name' => 'JOAO', 'last_name' => 'SILVA']);
    EventParticipant::factory()->for($event)->create(['first_name' => 'MARIA', 'last_name' => 'SOUZA']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants?search=JOAO")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.first_name', 'JOAO');
});

it('filtro search filtra por email', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create(['email' => 'joao@example.com']);
    EventParticipant::factory()->for($event)->create(['email' => 'maria@example.com']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants?search=joao")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'joao@example.com');
});

it('filtro ticket_type filtra por tipo de ingresso', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create(['ticket_type' => 'Lote 1']);
    EventParticipant::factory()->for($event)->create(['ticket_type' => 'Lote 2']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants?ticket_type=Lote+1")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filtro payment_status filtra por estado de pagamento', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create(['payment_status' => 'Aprovado']);
    EventParticipant::factory()->for($event)->create(['payment_status' => 'Pendente']);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants?payment_status=Pendente")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('filtro checked_in=1 retorna apenas quem fez check-in', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->checkedIn()->create();
    EventParticipant::factory()->for($event)->create(['checked_in' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->getJson("/admin/api/events/{$event->id}/participants?checked_in=1")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.checked_in', true);
});

// ─── Upload ───────────────────────────────────────────────────────────────────

it('admin faz upload de CSV válido e recebe imported e updated', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques;Lote 1;R$ 0,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Não;;gratis;;gratis\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk()
        ->assertJsonStructure(['imported', 'updated', 'errors']);

    expect(EventParticipant::where('event_id', $event->id)->count())->toBe(1);
});

it('upload upsert re-upload atualiza registro existente sem duplicar', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->for($event)->create([
        'registration_order' => 1,
        'first_name' => 'WILLIAM',
        'email' => 'wilcorrea@gmail.com',
    ]);

    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques Atualizado;Lote 2;R$ 50,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Sim;;;;\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('updated', 1)
        ->assertJsonPath('imported', 0);

    expect(EventParticipant::where('event_id', $event->id)->count())->toBe(1);
});

it('colaborador tenta upload e recebe 403', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;João;Silva;Lote 1;R$ 0,00;2025-03-13 20:25:38;;joao@example.com;Aprovado;Não;;;;\n";
    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->colaborador()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertForbidden();
});

it('upload sem arquivo retorna 422', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/admin/api/events/{$event->id}/participants/upload")
        ->assertUnprocessable();
});

it('upload de arquivo não-CSV retorna 422', function () {
    $event = Event::factory()->create();
    $file = UploadedFile::fake()->create('participants.xlsx', 10, 'application/vnd.ms-excel');

    $this->actingAs(User::factory()->admin()->create())
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertUnprocessable();
});

it('upload com coluna obrigatória ausente retorna 422', function () {
    $event = Event::factory()->create();
    $csv = "Nome;Sobrenome;Tipo de ingresso\nJoão;Silva;Lote 1\n";
    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertUnprocessable();
});

it('upload com linha inválida retorna 200 com errors preenchido', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques;Lote 1;R$ 0,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Não;;;;\n" .
           "2;;Sem Nome;;;R$ 0,00;2025-03-13 20:25:38;;;Aprovado;Não;;;;\n"; // Email vazio

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('imported', 1);

    $res = $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => UploadedFile::fake()->createWithContent('p.csv', $csv)])
        ->json();

    expect($res['errors'])->not->toBeEmpty();
});

it('upload parseia valor "R$ 1.500,00" para 1500.00', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques;Lote VIP;R$ 1.500,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Não;;;;\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk();

    expect(EventParticipant::where('event_id', $event->id)->first()->amount)->toBe('1500.00');
});

it('upload parseia Check-in "Sim" para true', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques;Lote 1;R$ 0,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Sim;;;;\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk();

    expect(EventParticipant::where('event_id', $event->id)->first()->checked_in)->toBeTrue();
});

it('upload parseia Check-in "Não" para false', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;William;Marques;Lote 1;R$ 0,00;2025-03-13 20:25:38;;wilcorrea@gmail.com;Aprovado;Não;;;;\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk();

    expect(EventParticipant::where('event_id', $event->id)->first()->checked_in)->toBeFalse();
});

it('upload parseia nomes em maiúsculas via mb_strtoupper', function () {
    $event = Event::factory()->create();
    $csv = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
           "1;;joão;da silva;Lote 1;R$ 0,00;2025-03-13 20:25:38;;joao@example.com;Aprovado;Não;;;;\n";

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csv);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk();

    $participant = EventParticipant::where('event_id', $event->id)->first();
    expect($participant->first_name)->toBe('JOÃO');
    expect($participant->last_name)->toBe('DA SILVA');
});

it('upload com encoding Latin-1 importa corretamente', function () {
    $event = Event::factory()->create();
    $csvUtf8 = "Ordem de inscrição;Nº ingresso;Nome;Sobrenome;Tipo de ingresso;Valor;Data compra;Nº pedido;Email;Estado de pagamento;Check-in;Data Check-in (*);Cupom de Desconto;Identificador de Parceiro;Método de pagamento\n" .
               "1;;José;Araújo;Lote 1;R$ 0,00;2025-03-13 20:25:38;;jose@example.com;Aprovado;Não;;;;\n";
    $csvLatin1 = mb_convert_encoding($csvUtf8, 'ISO-8859-1', 'UTF-8');

    $file = UploadedFile::fake()->createWithContent('participants.csv', $csvLatin1);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson("/admin/api/events/{$event->id}/participants/upload", ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('imported', 1);

    expect(EventParticipant::where('event_id', $event->id)->exists())->toBeTrue();
});

// ─── Exclusão individual ──────────────────────────────────────────────────────

it('admin remove participante individual com 204', function () {
    $event = Event::factory()->create();
    $participant = EventParticipant::factory()->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event->id}/participants/{$participant->id}")
        ->assertNoContent();

    expect(EventParticipant::find($participant->id))->toBeNull();
});

it('colaborador tenta remover participante e recebe 403', function () {
    $event = Event::factory()->create();
    $participant = EventParticipant::factory()->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->deleteJson("/admin/api/events/{$event->id}/participants/{$participant->id}")
        ->assertForbidden();
});

it('remover participante de outro evento retorna 404', function () {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();
    $participant = EventParticipant::factory()->for($event1)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event2->id}/participants/{$participant->id}")
        ->assertNotFound();
});

// ─── Limpar todos ─────────────────────────────────────────────────────────────

it('admin limpa todos os participantes do evento e recebe deleted count', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->count(5)->for($event)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event->id}/participants")
        ->assertOk()
        ->assertJsonPath('deleted', 5);

    expect(EventParticipant::where('event_id', $event->id)->count())->toBe(0);
});

it('colaborador tenta limpar todos e recebe 403', function () {
    $event = Event::factory()->create();
    EventParticipant::factory()->count(3)->for($event)->create();

    $this->actingAs(User::factory()->colaborador()->create())
        ->deleteJson("/admin/api/events/{$event->id}/participants")
        ->assertForbidden();
});

it('limpar não afeta participantes de outros eventos', function () {
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();
    EventParticipant::factory()->count(3)->for($event1)->create();
    EventParticipant::factory()->count(2)->for($event2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->deleteJson("/admin/api/events/{$event1->id}/participants")
        ->assertOk();

    expect(EventParticipant::where('event_id', $event2->id)->count())->toBe(2);
});
