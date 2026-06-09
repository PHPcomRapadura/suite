# Spec — Controle de Participantes do Evento

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos → Participantes
**Depende de:** `.claude/specs/admin/events-details.md`
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Visão geral

Importação e visualização da lista de participantes de um evento via upload de arquivo `.csv` exportado do **Sympla**. O módulo é acessado pelo hub do evento em `/admin/events/{id}/participants`.

Re-uploads são tratados como **upsert** por `event_id + registration_order` — a lista pode ser atualizada quantas vezes for necessário sem duplicar registros.

| Ação | `admin` | `colaborador` |
|------|---------|---------------|
| Visualizar lista e estatísticas | ✅ | ✅ |
| Fazer upload de CSV | ✅ | ❌ |
| Remover participante individual | ✅ | ❌ |
| Limpar todos os participantes do evento | ✅ | ❌ |

---

## 2. Model `EventParticipant`

**Arquivo:** `app/Models/EventParticipant.php`

### 2.1 Campos

| Campo | Tipo | Coluna CSV de origem |
|-------|------|----------------------|
| `id` | `bigIncrements` | — |
| `event_id` | `foreignId → events.id` `cascadeOnDelete` | — |
| `registration_order` | `unsignedInteger` | `Ordem de inscrição` |
| `first_name` | `string(255)` | `Nome` |
| `last_name` | `string(255)` | `Sobrenome` |
| `email` | `string(255)` | `Email` |
| `ticket_type` | `string(255)` | `Tipo de ingresso` |
| `amount` | `decimal(10,2)` | `Valor` — parseado de `"R$ 0,00"` |
| `purchased_at` | `datetime` | `Data compra` — formato `"YYYY-MM-DD HH:MM:SS"` |
| `payment_status` | `string(100)` | `Estado de pagamento` — ex: `"Aprovado"` |
| `checked_in` | `boolean` default `false` | `Check-in` — `"Sim"` → `true`, `"Não"` → `false` |
| `discount_coupon` | `string(100)` nullable | `Cupom de Desconto` |
| `payment_method` | `string(100)` nullable | `Método de pagamento` — ex: `"gratis"` |
| `timestamps` | — | — |

**Índice único:** `(event_id, registration_order)` — garante upsert idempotente.

### 2.2 Colunas CSV descartadas

As seguintes colunas estão presentes no export do Sympla mas **não** são persistidas:

`Nº ingresso`, `Nº pedido`, `Data Check-in (*)`, `Identificador de Parceiro`, `UTM_Source`, `UTM_Medium`, `UTM_Campaign`, `UTM_Term`, `UTM_Content`, `User_Agent`, `Referrer`, `PDV`

### 2.3 Migration

```php
Schema::create('event_participants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('registration_order');
    $table->string('first_name');
    $table->string('last_name');
    $table->string('email');
    $table->string('ticket_type');
    $table->decimal('amount', 10, 2);
    $table->datetime('purchased_at');
    $table->string('payment_status', 100);
    $table->boolean('checked_in')->default(false);
    $table->string('discount_coupon', 100)->nullable();
    $table->string('payment_method', 100)->nullable();
    $table->timestamps();

    $table->unique(['event_id', 'registration_order']);
});
```

### 2.4 Model

```php
class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'registration_order', 'first_name', 'last_name', 'email',
        'ticket_type', 'amount', 'purchased_at', 'payment_status',
        'checked_in', 'discount_coupon', 'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'purchased_at' => 'datetime',
            'amount'       => 'decimal:2',
            'checked_in'   => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### 2.5 Relacionamento no model `Event`

```php
public function participants(): HasMany
{
    return $this->hasMany(EventParticipant::class)->orderBy('registration_order');
}
```

---

## 3. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Upload | Somente `admin` — colaborador recebe `403` |
| Formato aceito | Apenas `.csv`; máx. **10 MB** |
| Encoding | UTF-8 ou Latin-1; detectado via `mb_detect_encoding` |
| Delimitador | Ponto e vírgula (`;`) — padrão do export Sympla |
| Cabeçalho | Primeira linha é o header; colunas reconhecidas pelo **nome exato** |
| Colunas obrigatórias | `Ordem de inscrição`, `Nome`, `Email` — upload falha se ausentes |
| Upsert | `updateOrCreate(['event_id', 'registration_order'], [...dados])` |
| Normalizar nomes | `first_name` e `last_name` gravados em maiúsculas via `mb_strtoupper($value, 'UTF-8')` |
| Parsear `Valor` | Remover `"R$ "`, substituir `"."` por `""` e `","` por `"."`, converter para `float` |
| Parsear `Check-in` | `"Sim"` → `true`; qualquer outro valor → `false` |
| Parsear `Data compra` | `Carbon::parse($value)` — formato `"YYYY-MM-DD HH:MM:SS"` |
| Linha inválida | Registra no array de `errors` e continua processando as demais |
| Excluir individual | Somente `admin` — hard delete |
| Limpar todos | Somente `admin` — `DELETE WHERE event_id = ?` |

---

## 4. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/events/{event}/participants')->name('participants.')->group(function () {
    Route::get('/',         [EventParticipantController::class, 'index'])->name('index');
    Route::post('/upload',  [EventParticipantController::class, 'upload'])->name('upload')->middleware('role:admin');
    Route::delete('/',      [EventParticipantController::class, 'clear'])->name('clear')->middleware('role:admin');
    Route::delete('/{participant}', [EventParticipantController::class, 'destroy'])->name('destroy')->middleware('role:admin');
});

// Rota Vue
Route::get('/events/{id}/participants', fn () => view('admin'))->name('events.participants');
```

---

## 5. Controller

**Arquivo:** `app/Http/Controllers/Admin/EventParticipantController.php`

### 5.1 `index`

```
GET /admin/api/events/{event}/participants

Query params: search, ticket_type, payment_status, checked_in, page

Retorna JSON:
{
  "data":    [ ...participantes paginados ],
  "meta":    { current_page, last_page, per_page, total },
  "summary": { total, approved, checked_in, ticket_types: ["Lote 1", ...] }
}
```

- Paginação: **50 por página**
- Filtro `search`: `LIKE` em `first_name`, `last_name` e `email`
- Filtro `ticket_type`: valor exato
- Filtro `payment_status`: valor exato
- Filtro `checked_in`: `"1"` → `true`, `"0"` → `false`, ausente → todos
- `summary.approved`: contagem onde `payment_status = 'Aprovado'`
- `summary.ticket_types`: lista distinta de valores de `ticket_type` no evento — usada para popular o `<select>` de filtro

Campos retornados por participante:

```json
{
  "id": 1,
  "registration_order": 1,
  "first_name": "William",
  "last_name": "Marques Vicente Gomes Correa",
  "full_name": "William Marques Vicente Gomes Correa",
  "email": "wilcorrea@gmail.com",
  "ticket_type": "Lote 1 - Cortesia PHP com Rapadura",
  "amount": "0.00",
  "purchased_at": "2025-03-13T20:25:38Z",
  "payment_status": "Aprovado",
  "checked_in": false,
  "discount_coupon": "gratis",
  "payment_method": "gratis"
}
```

### 5.2 `upload` — somente `admin`

```
POST /admin/api/events/{event}/participants/upload
Body: multipart/form-data — campo "csv" com o arquivo

→ valida: arquivo obrigatório, extensão .csv, máx 10 MB
→ lê e processa linha a linha via EventParticipantService::import()
→ 200 com:
{
  "imported": 148,
  "updated":  2,
  "errors":   [ "Linha 34: campo Email ausente." ]
}
```

### 5.3 `destroy` — somente `admin`

```
DELETE /admin/api/events/{event}/participants/{participant}
→ verifica que participant.event_id === event.id (404 se não)
→ hard delete
→ 204 No Content
```

### 5.4 `clear` — somente `admin`

```
DELETE /admin/api/events/{event}/participants
→ deleta todos os participantes do evento
→ 200 com { deleted: N }
```

---

## 6. Form Request

**Arquivo:** `app/Http/Requests/Admin/Participants/UploadParticipantsRequest.php`

```php
public function rules(): array
{
    return [
        'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
    ];
}

public function messages(): array
{
    return [
        'csv.required' => 'Selecione um arquivo CSV.',
        'csv.mimes'    => 'O arquivo deve ser um CSV.',
        'csv.max'      => 'O arquivo deve ter no máximo 10 MB.',
    ];
}
```

> `mimes:csv,txt` — necessário porque navegadores podem enviar CSV com `Content-Type: text/plain`.

---

## 7. Service

**Arquivo:** `app/Services/EventParticipantService.php`

```php
public function import(Event $event, UploadedFile $file): array;
// Lê o CSV linha a linha, faz upsert via updateOrCreate,
// retorna: ['imported' => N, 'updated' => M, 'errors' => [...]]

public function list(Event $event, array $filters): LengthAwarePaginator;

public function summary(Event $event): array;
// retorna: total, approved, checked_in, ticket_types[]

public function delete(EventParticipant $participant): void;

public function clear(Event $event): int;
// retorna número de registros deletados
```

### 7.1 Lógica de `import`

```php
// 1. Detectar e converter encoding
$content = file_get_contents($file->getRealPath());
$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
if ($encoding !== 'UTF-8') {
    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
}

// 2. Parsear CSV
$rows = array_map('str_getcsv', explode("\n", trim($content)));
$headers = array_shift($rows); // primeira linha = cabeçalhos

// 3. Mapear índices pelo nome exato da coluna
$map = array_flip($headers);

// 4. Validar colunas obrigatórias
$required = ['Ordem de inscrição', 'Nome', 'Email'];
// → erro 422 se alguma ausente

// 5. Para cada linha não vazia: upsert
EventParticipant::updateOrCreate(
    ['event_id' => $event->id, 'registration_order' => (int) $row[$map['Ordem de inscrição']]],
    [
        'first_name'     => mb_strtoupper(trim($row[$map['Nome']] ?? ''), 'UTF-8'),
        'last_name'      => mb_strtoupper(trim($row[$map['Sobrenome']] ?? ''), 'UTF-8'),
        'email'          => strtolower(trim($row[$map['Email']] ?? '')),
        'ticket_type'    => trim($row[$map['Tipo de ingresso']] ?? ''),
        'amount'         => self::parseAmount($row[$map['Valor']] ?? '0'),
        'purchased_at'   => Carbon::parse($row[$map['Data compra']] ?? now()),
        'payment_status' => trim($row[$map['Estado de pagamento']] ?? ''),
        'checked_in'     => strtolower(trim($row[$map['Check-in']] ?? '')) === 'sim',
        'discount_coupon'=> $row[$map['Cupom de Desconto']] ?? null ?: null,
        'payment_method' => $row[$map['Método de pagamento']] ?? null ?: null,
    ]
);

// 6. parseAmount helper
private static function parseAmount(string $raw): float
{
    $clean = str_replace(['R$', ' ', '.'], '', $raw); // remove símbolo, espaços e sep. milhar
    $clean = str_replace(',', '.', $clean);            // vírgula decimal → ponto
    return (float) $clean;
}
```

---

## 8. Layout da página

**Rota Vue:** `/admin/events/{id}/participants`
**Componente:** `resources/js/views/admin/EventParticipants.vue`
**Container raiz:** `div.flex.flex-col.gap-6.p-5`

```
← Voltar para o evento: [Nome do evento]

┌────────────────────────────────────────────────────────────────────────┐
│  👥 Participantes              [▦ Cards] [≡ Lista]  [↑ Importar CSV]   │
│  148 participantes · 145 aprovados · 32 check-ins realizados           │
└────────────────────────────────────────────────────────────────────────┘

┌────────────┐ ┌──────────────────┐ ┌────────────────────┐ ┌───────────────┐
│🔍 Buscar   │ │Tipo de ingresso ▼│ │Estado pagamento   ▼│ │ Check-in     ▼│
└────────────┘ └──────────────────┘ └────────────────────┘ └───────────────┘
```

### 8.1 Modo lista (padrão)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ # │ Nome                          │ Email              │ Ingresso  │ Valor  │
│   │ Data compra  │ Pagamento │ Check-in │ Cupom │ Método │ Ações     │
├───┼───────────────────────────────┼────────────────────┼───────────┼────────┤
│ 1 │ William Marques…              │ wilcorrea@…        │ Lote 1… │ R$0,00 │
│   │ 13/03/2025 20:25 │ Aprovado  │ ✗        │ gratis│ gratis │ [🗑]    │
└─────────────────────────────────────────────────────────────────────────────┘

[← 1 2 3 … →]       [🗑 Limpar todos]  ← somente admin, com confirmação
```

### 8.2 Modo cards

```
┌───────────────────────┐  ┌───────────────────────┐  ┌───────────────────────┐
│ William Marques…      │  │ João Silva            │  │ Maria Santos          │
│ wilcorrea@gmail.com   │  │ joao@…                │  │ maria@…               │
│                       │  │                       │  │                       │
│ Lote 1 - Cortesia…    │  │ Lote 2 - Normal       │  │ Lote 1 - Cortesia…    │
│ R$ 0,00               │  │ R$ 50,00              │  │ R$ 0,00               │
│ 13/03/2025 20:25      │  │ 15/03/2025 10:00      │  │ 20/03/2025 14:30      │
│ ✅ Aprovado            │  │ ✅ Aprovado            │  │ ✅ Aprovado            │
│ ✗ Sem check-in        │  │ ✓ Check-in realizado  │  │ ✗ Sem check-in        │
│              [🗑]     │  │              [🗑]     │  │              [🗑]     │
└───────────────────────┘  └───────────────────────┘  └───────────────────────┘
```

- Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`
- Paginação igual ao modo lista

### 8.3 Regras do toggle

- Toggle `[▦ Cards] [≡ Lista]` com botões icon no cabeçalho, ao lado do `[↑ Importar CSV]`
- Preferência persistida em `localStorage` com chave `participants_view_mode`
- Valor padrão: `lista`
- Botão `[🗑]` por item e `[🗑 Limpar todos]` visíveis somente para `admin`
- Tabela (modo lista) com scroll horizontal em telas menores
- Paginação: 50 por página em ambos os modos
- Filtros e stats aplicam-se igualmente em ambos os modos

---

## 9. Painel de estatísticas

```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ 👥 Total          │  │ ✅ Aprovados       │  │ 📍 Check-ins      │
│     148           │  │     145           │  │     32 / 148     │
│                   │  │                   │  │   [████░░░] 22%  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

- Três cards acima da tabela
- Check-in exibe `N / total` e barra de progresso (`checked_in / total * 100%`)
- Estatísticas aplicam os mesmos filtros ativos na tabela

---

## 10. Modal de upload

**Componente:** `resources/js/components/ParticipantUploadModal.vue`

```
┌──────────────────────────────────────────────────────┐
│  Importar participantes                       [✕]   │
├──────────────────────────────────────────────────────┤
│                                                      │
│  Arquivo CSV *  (exportado do Sympla)                │
│  [ Selecionar arquivo ]  ← área de drop             │
│                                                      │
│  ℹ Colunas importadas: Ordem, Nome, Sobrenome,       │
│    Email, Tipo de ingresso, Valor, Data compra,      │
│    Estado de pagamento, Check-in, Cupom, Método.     │
│                                                      │
│  Delimitador: ; (ponto e vírgula) — padrão do        │
│  export do Sympla.                                   │
│                                                      │
│  Re-uploads atualizam os dados existentes.           │
│                                                      │
│                [Cancelar]  [Importar]                │
└──────────────────────────────────────────────────────┘
```

**Estados pós-importação:**

```
✅ Importação concluída
   148 novos · 2 atualizados

   ⚠ 1 linha com erro:
   • Linha 34: campo Email ausente.
```

- Spinner no botão "Importar" durante o upload
- Resultado exibido dentro do modal após resposta da API
- Botão "Fechar" substitui "Cancelar" ao exibir resultado; clicar chama `onSaved` para atualizar a lista

---

## 11. Modal de confirmação

### Remover participante individual

Reutiliza `ConfirmModal.vue`:

```
Título:   "Remover participante"
Mensagem: "William Marques… (wilcorrea@gmail.com) será removido da lista. Esta ação não pode ser desfeita."
Botão:    "Remover" (--color-danger)
```

### Limpar todos

```
Título:   "Limpar lista de participantes"
Mensagem: "Todos os 148 participantes deste evento serão removidos. Esta ação não pode ser desfeita."
Botão:    "Limpar tudo" (--color-danger)
```

---

## 12. Card no hub do evento (`EventDetail.vue`)

```
┌──────────────────────────────────────┐
│ 👥  Participantes                     │
│                                      │
│ 148 inscritos · 145 aprovados        │
│ Check-in: 32 / 148                   │
│ [████░░░░░░░░] 22%                   │
│                                      │
│ Gerenciar →                          │
└──────────────────────────────────────┘
```

- Busca `GET /admin/api/events/{id}/participants?per_page=1` (ou endpoint de summary dedicado via `Promise.allSettled`)
- Se `total = 0`: "Nenhum participante importado"
- Botão "Gerenciar →" navega para `admin.events.participants`

---

## 13. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `database/migrations/..._create_event_participants_table.php` | Criar |
| `app/Models/EventParticipant.php` | Criar |
| `app/Models/Event.php` | Adicionar `participants()` HasMany |
| `app/Http/Controllers/Admin/EventParticipantController.php` | Criar |
| `app/Http/Requests/Admin/Participants/UploadParticipantsRequest.php` | Criar |
| `app/Services/EventParticipantService.php` | Criar |
| `routes/web.php` | Adicionar rotas de participants + rota Vue |
| `resources/js/views/admin/EventParticipants.vue` | Criar |
| `resources/js/components/ParticipantUploadModal.vue` | Criar |
| `resources/js/router/admin.js` | Adicionar rota `events/:id/participants` |
| `resources/js/views/admin/EventDetail.vue` | Atualizar card Participantes + `fetchData` |
| `.claude/specs/admin/events-details.md` | Atualizar card "Participantes" para "✅ Link ativo" |
| `CLAUDE.md` | Atualizar status |
| `.claude/about.md` | Atualizar sub-módulos do evento |

---

## 14. Vue Router

```js
{
    path: 'events/:id/participants',
    name: 'admin.events.participants',
    component: () => import('@/views/admin/EventParticipants.vue'),
},
```

---

## 15. Testes

**Arquivo:** `tests/Feature/Admin/Events/EventParticipantsTest.php`

| # | Cenário |
|---|---------|
| 1 | Guest recebe 401 na listagem |
| 2 | Admin lista participantes paginados |
| 3 | Colaborador lista participantes |
| 4 | Listagem retorna summary (total, approved, checked_in, ticket_types) |
| 5 | Filtro `search` filtra por nome |
| 6 | Filtro `search` filtra por email |
| 7 | Filtro `ticket_type` filtra por tipo de ingresso |
| 8 | Filtro `payment_status` filtra por estado de pagamento |
| 9 | Filtro `checked_in=1` retorna apenas quem fez check-in |
| 10 | Admin faz upload de CSV válido → 200 com imported e updated |
| 11 | Upload upsert: re-upload atualiza registro existente sem duplicar |
| 12 | Colaborador tenta upload → 403 |
| 13 | Upload sem arquivo retorna 422 |
| 14 | Upload de arquivo não-CSV retorna 422 |
| 15 | Upload com coluna obrigatória ausente retorna 422 |
| 16 | Upload com linha inválida retorna 200 com errors preenchido |
| 17 | Upload parseia valor `"R$ 1.500,00"` → `1500.00` |
| 18 | Upload parseia `Check-in = "Sim"` → `checked_in = true` |
| 19 | Upload parseia `Check-in = "Não"` → `checked_in = false` |
| 20 | Upload com encoding Latin-1 importa corretamente |
| 21 | Admin remove participante individual → 204 |
| 22 | Colaborador tenta remover participante → 403 |
| 23 | Remover participante de outro evento → 404 |
| 24 | Admin limpa todos os participantes do evento → 200 com deleted count |
| 25 | Colaborador tenta limpar → 403 |
| 26 | Limpar não afeta participantes de outros eventos |

---

## 16. Critérios de aceite

- [ ] Botão `[↑ Importar CSV]` visível somente para admin
- [ ] Toggle `[▦ Cards] [≡ Lista]` presente no cabeçalho da página
- [ ] Modo cards exibe grid responsivo (3/2/1 colunas por breakpoint)
- [ ] Modo lista exibe tabela com scroll horizontal em mobile
- [ ] Preferência de exibição persiste após recarregar (via `localStorage`)
- [ ] Modal de upload aceita `.csv`, rejeita outros formatos
- [ ] Após import bem-sucedido: modal exibe contagem de novos + atualizados
- [ ] Linhas com erro listadas no modal sem interromper o import
- [ ] Re-upload do mesmo CSV não duplica participantes
- [ ] Painel de stats mostra total, aprovados e barra de progresso de check-in
- [ ] Filtros de busca, tipo, pagamento e check-in funcionam combinados em ambos os modos
- [ ] Botões de excluir visíveis somente para admin (cards e lista)
- [ ] Confirmação antes de remover participante individual
- [ ] Confirmação antes de limpar todos (exibe contagem no texto)
- [ ] Card no hub exibe inscritos, aprovados e progresso de check-in
- [ ] Página funciona ao recarregar (F5) — rota Laravel presente
