# Spec — Sorteio Digital por Evento

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos → Sorteio
**Depende de:** `.claude/specs/admin/participants-spec.md` (pool vem de `event_participants.checked_in = true`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Visão geral

Sorteio digital de participantes vinculado ao evento. O pool é composto exclusivamente por participantes com `checked_in = true`. Cada participante só pode ser sorteado uma vez por evento. Os sorteados são persistidos no banco e sobrevivem a recarregamentos de página.

| Ação | `admin` | `colaborador` |
|------|---------|---------------|
| Visualizar sorteados e pool | ✅ | ✅ |
| Sortear (botão "Arrocha") | ✅ | ❌ |
| Resetar sorteio | ✅ | ❌ |

---

## 2. Model `EventLotteryWinner`

**Arquivo:** `app/Models/EventLotteryWinner.php`

### 2.1 Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | `bigIncrements` | — |
| `event_id` | `foreignId → events.id` `cascadeOnDelete` | Evento do sorteio |
| `participant_id` | `foreignId → event_participants.id` `cascadeOnDelete` | Participante sorteado |
| `position` | `unsignedSmallInteger` | Ordem do sorteio (1°, 2°, 3°…) |
| `drawn_at` | `timestamp` | Momento do sorteio |

**Índice único:** `(event_id, participant_id)` — garante que cada participante seja sorteado no máximo uma vez por evento.

### 2.2 Migration

```php
Schema::create('event_lottery_winners', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('participant_id')
          ->constrained('event_participants')
          ->cascadeOnDelete();
    $table->unsignedSmallInteger('position');
    $table->timestamp('drawn_at');

    $table->unique(['event_id', 'participant_id']);
});
```

### 2.3 Model

```php
class EventLotteryWinner extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'participant_id', 'position', 'drawn_at'];

    protected function casts(): array
    {
        return ['drawn_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }
}
```

### 2.4 Relacionamentos no model `Event`

```php
public function lotteryWinners(): HasMany
{
    return $this->hasMany(EventLotteryWinner::class)->orderBy('position');
}
```

---

## 3. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Pool elegível | Apenas participantes com `checked_in = true` e que ainda **não** foram sorteados no evento |
| Unicidade | `(event_id, participant_id)` unique — banco impede duplicata mesmo sob concorrência |
| Sortear sem pool | Retorna `422` com mensagem "Não há participantes disponíveis para sortear." |
| Posição | `MAX(position) + 1` para o evento; começa em 1 |
| Reset | Hard delete de todos os `event_lottery_winners` do evento — `admin` apenas |
| Acesso | `draw` e `reset` requerem `role:admin`; `index` é aberto para admin e colaborador |

---

## 4. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('/{event}/lottery')->name('lottery.')->group(function () {
    Route::get('/',       [EventLotteryController::class, 'index'])->name('index');
    Route::post('/draw',  [EventLotteryController::class, 'draw'])->name('draw')->middleware('role:admin');
    Route::delete('/',    [EventLotteryController::class, 'reset'])->name('reset')->middleware('role:admin');
});

// Rota Vue
Route::get('/events/{id}/lottery', fn () => view('admin'))->name('events.lottery');
```

---

## 5. Controller

**Arquivo:** `app/Http/Controllers/Admin/EventLotteryController.php`

### 5.1 `index`

```
GET /admin/api/events/{event}/lottery

Retorna JSON:
{
  "winners": [
    {
      "position": 1,
      "drawn_at": "2026-06-09T14:30:00Z",
      "participant": {
        "id": 1,
        "full_name": "WILLIAM MARQUES",
        "email": "wilcorrea@gmail.com",
        "ticket_type": "Lote 1"
      }
    }
  ],
  "stats": {
    "total_pool":   32,   ← total de participantes com check-in
    "total_drawn":  3,    ← já sorteados
    "remaining":    29    ← ainda no pool
  }
}
```

### 5.2 `draw` — somente `admin`

```
POST /admin/api/events/{event}/lottery/draw

→ verifica se há participantes elegíveis (checked_in + não sorteados)
→ 422 se pool vazio: { "message": "Não há participantes disponíveis para sortear." }
→ sorteia aleatório via: EventParticipant::inRandomOrder()->first()
→ persiste EventLotteryWinner com position = MAX + 1 e drawn_at = now()
→ 200 com o winner recém-criado (mesmo formato de `winners[]`)
```

### 5.3 `reset` — somente `admin`

```
DELETE /admin/api/events/{event}/lottery

→ deleta todos os EventLotteryWinner do evento
→ 200 com { "reset": true }
```

---

## 6. Service

**Arquivo:** `app/Services/EventLotteryService.php`

```php
public function state(Event $event): array;
// retorna winners[] formatados + stats

public function draw(Event $event): EventLotteryWinner;
// sorteia e persiste; lança ValidationException se pool vazio

public function reset(Event $event): void;
// deleta todos os winners do evento

private function nextPosition(Event $event): int;
// MAX(position) + 1, ou 1 se não houver winners

private function eligiblePool(Event $event): Builder;

private function obfuscateEmail(string $email): string;
// Mantém 2 chars antes do '@', substitui o restante por '*****', preserva domínio
// Exemplo: 'wilcorrea@gmail.com' → 'wi*****@gmail.com'
// EventParticipant::where('event_id', $event->id)
//   ->where('checked_in', true)
//   ->whereNotIn('id', fn($q) => $q->select('participant_id')
//       ->from('event_lottery_winners')
//       ->where('event_id', $event->id))
```

---

## 7. Layout da página

**Rota Vue:** `/admin/events/{id}/lottery`
**Componente:** `resources/js/views/admin/EventLottery.vue`
**Container raiz:** `div.flex.flex-col.gap-6.p-5`

```
← Voltar para o evento: [Nome do evento]

┌────────────────────────────────────────────────────────────────┐
│  🎁 Sorteio                                                    │
│  32 com check-in · 3 sorteados · 29 disponíveis               │
└────────────────────────────────────────────────────────────────┘

              ┌─────────────────────┐
              │                     │
              │   [  Arrocha  ]     │  ← botão grande, visível somente admin
              │                     │
              └─────────────────────┘

              Pool esgotado: "Todos os participantes já foram sorteados."
              (exibido em vez do botão quando remaining = 0)

┌───────────────────────────────────────────────────────────────┐
│ Sorteados                                    [🔄 Resetar]     │
├───────────────────────────────────────────────────────────────┤
│  1°  WILLIAM MARQUES        wi*****@gmail.com                 │
│  2°  MARIA SILVA            ma*****@example.com               │
│  3°  JOÃO ARAÚJO            jo*****@example.com               │
└───────────────────────────────────────────────────────────────┘
                          ↑ ordenado por position ASC
```

- `[🔄 Resetar]` visível somente para `admin`, abre `ConfirmModal`
- Lista de sorteados sempre visível (mesmo para colaborador)
- Se `winners` vazio: "Nenhum participante sorteado ainda."
- Se pool vazio na carga: mostra lista de winners e mensagem no lugar do botão

---

## 8. Animação do sorteio

### 8.1 Fluxo ao clicar em "Arrocha"

1. `drawing = true` → botão fica `disabled` + `opacity-50`
2. Overlay fullscreen aparece (veja 8.2)
3. Contagem regressiva: **3 → 2 → 1** (1 segundo cada)
4. `POST /admin/api/events/{event}/lottery/draw` é disparado **junto com o início da contagem** (paralelismo — a chamada API ocorre em background enquanto a animação roda)
5. Após 3 segundos: overlay transiciona para tela de revelação do vencedor (veja 8.3)
6. Confete dispara (canvas, veja 8.4)
7. Após 4 segundos na tela de revelação: overlay fecha automaticamente, lista de winners atualiza

### 8.2 Overlay de contagem

```
┌────────────────────────────────────────────────────────────────┐
│  [fundo escuro semitransparente — bg-black/80]                 │
│                                                                │
│            [favicon.png girando — spin-full 1.2s]             │
│                                                                │
│                        3                                       │
│              [número grande, fonte Lexend 600]                 │
│                                                                │
└────────────────────────────────────────────────────────────_───┘
```

- Reutiliza a animação `spin-full 1.2s linear infinite` já definida em `app.css`
- Logo: `<img src="/images/favicon.png" class="w-20 h-20 [animation:spin-full_1.2s_linear_infinite]">`
- Número: `text-9xl font-semibold text-white`, troca a cada 1 segundo via `setInterval`
- O overlay é um `<div>` com `position: fixed; inset: 0; z-index: 9999`

### 8.3 Tela de revelação (após contagem)

```
┌────────────────────────────────────────────────────────────────┐
│  [fundo escuro — bg-black/80]     [confete caindo acima]      │
│                                                                │
│                    🎉                                         │
│                                                                │
│              WILLIAM MARQUES                                   │
│           wi*****@gmail.com                                    │
│                                                                │
│                  [Continuar]                                   │
└────────────────────────────────────────────────────────────────┘
```

- Nome em `text-3xl font-bold text-white`
- Email ofuscado em `text-sm text-white/70` — mantém os 2 primeiros caracteres antes do `@`, substitui o restante por `*****`, preserva domínio: `wi*****@gmail.com`
- Tipo de ingresso **não** é exibido na tela de revelação
- Botão "Continuar" fecha o overlay e atualiza a lista
- Se a API retornar erro durante a animação: ao fechar o overlay exibe toast/alert com a mensagem de erro

### 8.4 Confete

- Implementado via **`canvas-confetti`** (`npm install canvas-confetti`)
- Dispara ao entrar na tela de revelação:
  ```js
  import confetti from 'canvas-confetti'
  confetti({ particleCount: 120, spread: 80, origin: { y: 0.4 } })
  ```
- Cores: `['#025c98', '#f59e0b', '#16a34a', '#ffffff']` (paleta do projeto)
- Sem dependência adicional de CSS — canvas-confetti usa apenas `<canvas>` interno

---

## 9. Modal de confirmação — Reset

Reutiliza `ConfirmModal.vue`:

```
Título:   "Reiniciar sorteio"
Mensagem: "Deseja realmente reiniciar o sorteio? Os 3 participantes já sorteados voltarão ao pool. Esta ação não pode ser desfeita."
Botão:    "Reiniciar" (--color-danger)
```

- A contagem de já sorteados é incluída na mensagem dinamicamente
- Se `winners` vazio: botão "Resetar" não aparece

---

## 10. Card no hub do evento (`EventDetail.vue`)

```
┌──────────────────────────────────────┐
│ 🎁  Sorteio                          │
│                                      │
│ 3 sorteados · 29 disponíveis         │
│                                      │
│ Gerenciar →                          │
└──────────────────────────────────────┘
```

- Se pool total = 0: "Nenhum participante com check-in."
- Se nenhum sorteado ainda: "Nenhum participante sorteado ainda."
- Busca `GET /admin/api/events/{id}/lottery` via `Promise.allSettled` no `fetchData`

---

## 11. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `database/migrations/..._create_event_lottery_winners_table.php` | Criar |
| `app/Models/EventLotteryWinner.php` | Criar |
| `app/Models/Event.php` | Adicionar `lotteryWinners()` HasMany |
| `app/Http/Controllers/Admin/EventLotteryController.php` | Criar |
| `app/Services/EventLotteryService.php` | Criar |
| `routes/web.php` | Adicionar rotas de lottery + rota Vue |
| `resources/js/views/admin/EventLottery.vue` | Criar |
| `resources/js/router/admin.js` | Adicionar rota `events/:id/lottery` |
| `resources/js/views/admin/EventDetail.vue` | Atualizar card Sorteio + `fetchData` |
| `package.json` / `npm install canvas-confetti` | Adicionar dependência |

---

## 12. Vue Router

```js
{
    path: 'events/:id/lottery',
    name: 'admin.events.lottery',
    component: () => import('@/views/admin/EventLottery.vue'),
},
```

---

## 13. Testes

**Arquivo:** `tests/Feature/Admin/Events/EventLotteryTest.php`

| # | Cenário |
|---|---------|
| 1 | Guest recebe 401 ao acessar o sorteio |
| 2 | Admin visualiza sorteio com winners e stats |
| 3 | Colaborador visualiza sorteio |
| 4 | Stats retornam total_pool, total_drawn e remaining corretamente |
| 5 | Pool considera somente participantes com check-in |
| 6 | Admin sorteia participante do pool → 200 com winner |
| 7 | Sorteado não retorna mais no pool após ser sorteado |
| 8 | Position incrementa corretamente (1°, 2°, 3°…) |
| 9 | Sortear com pool vazio retorna 422 |
| 10 | Colaborador tenta sortear → 403 |
| 11 | Sortear quando não há participantes com check-in → 422 |
| 12 | Admin reseta o sorteio → 200 com reset: true |
| 13 | Após reset, pool volta ao total de check-ins e winners fica vazio |
| 14 | Colaborador tenta resetar → 403 |
| 15 | Reset não afeta winners de outros eventos |

---

## 14. Critérios de aceite

- [x] Pool considera somente `checked_in = true`
- [x] Participante sorteado não aparece mais no pool
- [x] Botão "Arrocha" visível somente para admin
- [x] Overlay de contagem exibe logo girando + 3, 2, 1 (1 s cada)
- [x] Chuva de confete dispara ao revelar o vencedor
- [x] Tela de revelação exibe nome e email ofuscado (sem tipo de ingresso)
- [x] E-mail ofuscado mantém 2 chars antes do `@` + `*****` + domínio intacto (`wi*****@gmail.com`)
- [x] Lista de sorteados exibe e-mail ofuscado (sem tipo de ingresso)
- [x] "Continuar" fecha o overlay e atualiza a lista de sorteados
- [x] Erro na API durante animação: mensagem exibida ao fechar o overlay
- [x] Pool esgotado: botão substituído por mensagem informativa
- [x] Reset abre `ConfirmModal` com contagem de já sorteados
- [x] Após reset, sorteio recomeça do zero
- [x] Botão "Resetar" oculto quando não há winners ou para colaborador
- [x] Card no hub exibe sorteados e disponíveis
- [x] Página funciona ao recarregar (F5) — rota Laravel presente
