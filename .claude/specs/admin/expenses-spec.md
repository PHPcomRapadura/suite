# Spec — Controle de Despesas do Evento

**Status:** ✅ Implementado
**Testes:** `tests/Feature/Admin/Events/EventExpensesTest.php` — 22 casos, 49 assertions
**Módulo:** Admin → Eventos → Despesas
**Depende de:** `.claude/specs/admin/events-details.md`
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Visão geral

Registro de despesas vinculadas a um evento, com finalidade de **prestação de contas** da organização. O módulo é acessado pelo hub do evento em `/admin/events/{id}/expenses`.

Regras de acesso simplificadas:

| Ação | `admin` | `colaborador` |
|------|---------|---------------|
| Visualizar listagem e totais | ✅ | ✅ |
| Registrar nova despesa | ✅ | ✅ |
| Editar despesa | ✅ | ❌ |
| Excluir despesa | ✅ | ❌ |

O usuário pode alternar entre **visualização em cards** e **visualização em lista** — a preferência é salva em `localStorage`.

---

## 2. Model `EventExpense`

**Arquivo:** `app/Models/EventExpense.php`

### 2.1 Campos

| Campo | Tipo | Observação |
|-------|------|-----------|
| `id` | `bigIncrements` | PK |
| `event_id` | `foreignId → events.id` | `cascadeOnDelete` |
| `category` | `enum` | Ver seção 2.2 |
| `description` | `string(255)` | Título/objeto da despesa |
| `amount` | `decimal(10, 2)` | Valor em reais (positivo) |
| `date` | `date` | Data em que a despesa ocorreu |
| `is_paid` | `boolean` | Default `false` — se o valor já foi pago |
| `receipt_url` | `string(500)` | Nullable — URL pública do comprovante no R2 |
| `notes` | `text` | Nullable — observações adicionais |
| `created_by` | `foreignId → users.id` | `nullOnDelete` — quem registrou |
| `updated_by` | `foreignId → users.id` | Nullable, `nullOnDelete` — quem editou por último |
| `timestamps` | — | `created_at`, `updated_at` |

### 2.2 Enum `category`

| Valor | Rótulo exibido |
|-------|----------------|
| `alimentacao` | Alimentação |
| `transporte` | Transporte |
| `hospedagem` | Hospedagem |
| `equipamentos` | Equipamentos |
| `marketing` | Marketing e Divulgação |
| `infraestrutura` | Infraestrutura |
| `palestrantes` | Ajuda de Custo — Palestrantes |
| `premiacao` | Premiação e Brindes |
| `outros` | Outros |

### 2.3 Migration

```php
Schema::create('event_expenses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('event_id')->constrained()->cascadeOnDelete();
    $table->enum('category', [
        'alimentacao', 'transporte', 'hospedagem', 'equipamentos',
        'marketing', 'infraestrutura', 'palestrantes', 'premiacao', 'outros',
    ]);
    $table->string('description');
    $table->decimal('amount', 10, 2);
    $table->date('date');
    $table->boolean('is_paid')->default(false);
    $table->string('receipt_url', 500)->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### 2.4 Model

```php
protected $fillable = [
    'event_id', 'category', 'description', 'amount',
    'date', 'is_paid', 'receipt_url', 'notes',
    'created_by', 'updated_by',
];

protected function casts(): array
{
    return [
        'date'    => 'date',
        'amount'  => 'decimal:2',
        'is_paid' => 'boolean',
    ];
}

public function event(): BelongsTo
{
    return $this->belongsTo(Event::class);
}

public function creator(): BelongsTo
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updater(): BelongsTo
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

### 2.5 Relacionamento no model `Event`

```php
public function expenses(): HasMany
{
    return $this->hasMany(EventExpense::class);
}
```

---

## 3. Upload de comprovante — Cloudflare R2

Comprovantes (notas fiscais, recibos) são opcionais e armazenados no R2. O banco persiste apenas a URL pública.

**Estrutura de path no bucket:**
```
events/{event_id}/expenses/{expense_id}/receipt.{ext}
```

**Formatos aceitos:** `jpg`, `jpeg`, `png`, `webp`, `pdf`
**Tamanho máximo:** 5 MB

Ao substituir o comprovante, o arquivo anterior é deletado do R2 antes do upload do novo. Ao excluir a despesa, o comprovante é deletado do R2 junto.

---

## 4. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| `amount` | Deve ser maior que `0` |
| `date` | Não pode ser uma data futura (maior que hoje) |
| `updated_by` | Preenchido com `Auth::id()` ao editar |
| `created_by` | Preenchido com `Auth::id()` ao criar |
| Editar / excluir | Somente `admin` — colaborador recebe `403` |
| Excluir com comprovante | Deletar arquivo do R2 antes de remover o registro |
| Comprovante ao substituir | Deletar arquivo anterior do R2 antes do upload |

---

## 5. Rotas

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/events/{event}/expenses')->name('expenses.')->group(function () {
    Route::get('/',                 [EventExpenseController::class, 'index'])->name('index');
    Route::post('/',                [EventExpenseController::class, 'store'])->name('store');
    Route::get('/{expense}',        [EventExpenseController::class, 'show'])->name('show');
    Route::put('/{expense}',        [EventExpenseController::class, 'update'])->name('update')->middleware('role:admin');
    Route::post('/{expense}',       [EventExpenseController::class, 'update'])->name('update.spoof')->middleware('role:admin'); // method spoofing para multipart
    Route::delete('/{expense}',     [EventExpenseController::class, 'destroy'])->name('destroy')->middleware('role:admin');
});

// Rota Vue
Route::get('/events/{id}/expenses', fn () => view('admin'))->name('events.expenses');
```

> A rota `POST /{expense}` com method spoofing (`_method: PUT` no body) existe para suportar uploads `multipart/form-data` em edições com comprovante.

---

## 6. Controller

**Arquivo:** `app/Http/Controllers/Admin/EventExpenseController.php`

### 6.1 `index`

```
GET /admin/api/events/{event}/expenses?category=&is_paid=&date_from=&date_to=&page=1

Retorna JSON:
{
  "data": [ ...expenses ],
  "meta": { "current_page", "last_page", "total", "per_page": 12 },
  "summary": {
    "total": 4500.00,
    "paid": 3000.00,
    "pending": 1500.00,
    "by_category": {
      "alimentacao": 800.00,
      "transporte": 400.00,
      ...
    }
  }
}
```

Filtros aceitos via query string:

| Parâmetro | Tipo | Comportamento |
|-----------|------|---------------|
| `category` | string | Filtro exato pelo valor do enum |
| `is_paid` | boolean | `true` → pagas, `false` → pendentes |
| `date_from` | date `Y-m-d` | Despesas a partir desta data |
| `date_to` | date `Y-m-d` | Despesas até esta data |
| `page` | int | Paginação — 12 itens por página |

Ordenação padrão: `date DESC`.

Campos retornados por despesa:

```json
{
  "id": 1,
  "category": "alimentacao",
  "category_label": "Alimentação",
  "description": "Coffee break — dia 1",
  "amount": "350.00",
  "date": "2026-06-14",
  "is_paid": true,
  "receipt_url": "https://assets.phpcomrapadura.org/events/1/expenses/1/receipt.pdf",
  "notes": null,
  "created_by": { "id": 2, "name": "João Silva" },
  "updated_by": null,
  "created_at": "2026-05-20T10:00:00Z",
  "updated_at": "2026-05-20T10:00:00Z"
}
```

### 6.2 `store`

```
POST /admin/api/events/{event}/expenses
Content-Type: multipart/form-data

Campos: category, description, amount, date, is_paid, receipt (file, optional), notes
→ valida via StoreExpenseRequest
→ preenche created_by = Auth::id()
→ faz upload do comprovante para R2 se fornecido
→ 201 com o recurso criado
```

### 6.3 `show`

```
GET /admin/api/events/{event}/expenses/{expense}
→ 200 com o recurso completo
→ 404 se a despesa não pertencer ao evento informado
```

### 6.4 `update` — somente `admin`

```
PUT /admin/api/events/{event}/expenses/{expense}
Content-Type: multipart/form-data  (ou application/json se sem upload)

Campos: category, description, amount, date, is_paid, receipt (file, optional), notes
→ valida via UpdateExpenseRequest
→ preenche updated_by = Auth::id()
→ se receipt enviado: deleta anterior do R2, faz upload do novo
→ 200 com o recurso atualizado
→ 403 se colaborador
```

### 6.5 `destroy` — somente `admin`

```
DELETE /admin/api/events/{event}/expenses/{expense}
→ deleta comprovante do R2 (se existir)
→ remove o registro
→ 204 No Content
→ 403 se colaborador
```

---

## 7. Form Requests

### 7.1 `StoreExpenseRequest`

**Arquivo:** `app/Http/Requests/Admin/Expenses/StoreExpenseRequest.php`

```php
public function rules(): array
{
    return [
        'category'    => ['required', Rule::in([
            'alimentacao', 'transporte', 'hospedagem', 'equipamentos',
            'marketing', 'infraestrutura', 'palestrantes', 'premiacao', 'outros',
        ])],
        'description' => ['required', 'string', 'max:255'],
        'amount'      => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        'date'        => ['required', 'date', 'before_or_equal:today'],
        'is_paid'     => ['boolean'],
        'receipt'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        'notes'       => ['nullable', 'string', 'max:1000'],
    ];
}

public function messages(): array
{
    return [
        'category.required'     => 'Selecione uma categoria.',
        'category.in'           => 'Categoria inválida.',
        'description.required'  => 'Informe uma descrição.',
        'description.max'       => 'A descrição deve ter no máximo 255 caracteres.',
        'amount.required'       => 'Informe o valor da despesa.',
        'amount.min'            => 'O valor deve ser maior que zero.',
        'date.required'         => 'Informe a data da despesa.',
        'date.before_or_equal'  => 'A data não pode ser futura.',
        'receipt.mimes'         => 'O comprovante deve ser JPG, PNG, WebP ou PDF.',
        'receipt.max'           => 'O comprovante deve ter no máximo 5 MB.',
        'notes.max'             => 'As observações devem ter no máximo 1.000 caracteres.',
    ];
}
```

### 7.2 `UpdateExpenseRequest`

**Arquivo:** `app/Http/Requests/Admin/Expenses/UpdateExpenseRequest.php`

Mesmas regras de `StoreExpenseRequest`. Todos os campos continuam obrigatórios (não é `PATCH` parcial).

---

## 8. Service

**Arquivo:** `app/Services/EventExpenseService.php`

```php
// Responsabilidades:
public function create(Event $event, array $data, ?UploadedFile $receipt): EventExpense;
public function update(EventExpense $expense, array $data, ?UploadedFile $receipt): EventExpense;
public function delete(EventExpense $expense): void;
public function uploadReceipt(EventExpense $expense, UploadedFile $file): string; // retorna URL pública
public function deleteReceipt(EventExpense $expense): void;
public function summary(Event $event, array $filters = []): array; // totais + breakdown por categoria
```

O path do comprovante no R2: `events/{event->id}/expenses/{expense->id}/receipt.{ext}`.

---

## 9. Layout da página

**Rota Vue:** `/admin/events/{id}/expenses`
**Componente:** `resources/js/views/admin/EventExpenses.vue`

```
← Voltar para o evento: [Nome do evento]

┌──────────────────────────────────────────────────────────────────┐
│  PAINEL DE TOTAIS                                                │
│                                                                  │
│  Total geral    Total pago    Total pendente                     │
│  R$ 4.500,00    R$ 3.000,00   R$ 1.500,00                       │
│                                                                  │
│  [Barra de progresso: % pago vs pendente]                        │
└──────────────────────────────────────────────────────────────────┘

[+ Registrar despesa]              [🔲 Cards] [☰ Lista]

[Filtro: Categoria ▼] [Filtro: Status ▼] [De: ____] [Até: ____]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  MODO CARDS (grid):
  ┌─────────────────────┐  ┌─────────────────────┐
  │ [Alimentação]  ✅    │  │ [Transporte]   ⏳    │
  │ Coffee break d1     │  │ Passagens aéreas    │
  │ 📅 14/06/2026       │  │ 📅 10/06/2026       │
  │             R$350,00│  │           R$1.200,00│
  │ por João Silva      │  │ por Maria Costa     │
  │ [📎 Comprovante]    │  │                     │
  │ [Editar] [Excluir]  │  │ [Editar] [Excluir]  │
  └─────────────────────┘  └─────────────────────┘

  MODO LISTA (tabela):
  ┌──────────────┬────────────────────┬────────────┬──────────────┬────────┬────────┐
  │ Categoria    │ Descrição          │ Data       │ Valor        │ Status │ Ações  │
  ├──────────────┼────────────────────┼────────────┼──────────────┼────────┼────────┤
  │ Alimentação  │ Coffee break d1    │ 14/06/2026 │ R$ 350,00   │ ✅ Pago│ [E][D] │
  │ Transporte   │ Passagens aéreas   │ 10/06/2026 │ R$ 1.200,00 │ ⏳     │ [E][D] │
  └──────────────┴────────────────────┴────────────┴──────────────┴────────┴────────┘
```

---

## 10. Painel de totais

Exibido sempre no topo, independente do modo de visualização. Atualizado em tempo real após criar, editar ou excluir uma despesa.

| Campo | Valor | Cor |
|-------|-------|-----|
| Total geral | Soma de todos os `amount` | `--color-text` |
| Total pago | Soma onde `is_paid = true` | `--color-success` |
| Total pendente | Soma onde `is_paid = false` | `--color-warning` |

**Barra de progresso:** largura proporcional a `(total_pago / total_geral) * 100%`. Fundo `--color-success`. Se total = 0, barra vazia.

Valores formatados em `pt-BR`: `R$ 1.350,00` via `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`.

---

## 11. Toggle de visualização (Cards / Lista)

- Dois botões lado a lado — ícones de grid (`🔲`) e lista (`☰`)
- Botão ativo: `background: var(--color-primary)`, texto branco
- Botão inativo: borda `--color-border`, fundo transparente
- Preferência salva em `localStorage` com chave `expenses_view_mode`
- Padrão: `cards`

---

## 12. Modo Cards

Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`

Estrutura do card:

```
┌─────────────────────────────────────┐
│ [Badge categoria]    [Badge status] │
│                                     │
│ Descrição da despesa                │
│ (truncar em 2 linhas)               │
│                                     │
│ 📅 14/06/2026                       │
│                                     │
│ Valor:                 R$ 350,00    │
│ Registrado por: João Silva          │
│                                     │
│ [📎 Ver comprovante]  (se tiver)    │
│                                     │
│ [Editar]   [Excluir]               │
└─────────────────────────────────────┘
```

- Borda: `1px solid --color-border`, border-radius `10px`
- Fundo: `--color-surface`
- Hover: `box-shadow: 0 4px 12px rgba(0,0,0,0.06)`
- Botões [Editar] e [Excluir] — somente visíveis para `admin`; colaboradores não veem os botões

### 12.1 Badge de categoria

- Fundo e texto seguem a paleta abaixo
- Border-radius: `6px`, padding: `2px 8px`, font-size: `11px`, peso `600`

| Categoria | Cor de fundo | Cor do texto |
|-----------|-------------|--------------|
| `alimentacao` | `#fef3c7` | `#92400e` |
| `transporte` | `#dbeafe` | `#1e40af` |
| `hospedagem` | `#f3e8ff` | `#6b21a8` |
| `equipamentos` | `#d1fae5` | `#065f46` |
| `marketing` | `#fce7f3` | `#9d174d` |
| `infraestrutura` | `#e0e7ff` | `#3730a3` |
| `palestrantes` | `#ffedd5` | `#9a3412` |
| `premiacao` | `#fef9c3` | `#854d0e` |
| `outros` | `#f3f4f6` | `#374151` |

### 12.2 Badge de status de pagamento

| `is_paid` | Texto | Cor de fundo | Cor do texto |
|-----------|-------|-------------|--------------|
| `true` | ✅ Pago | `#d1fae5` | `#065f46` |
| `false` | ⏳ Pendente | `#fef3c7` | `#92400e` |

---

## 13. Modo Lista

Tabela responsiva com scroll horizontal em mobile.

Colunas:

| Coluna | Largura | Observação |
|--------|---------|-----------|
| Categoria | auto | Badge colorido |
| Descrição | expandida (`flex-1`) | Texto truncado em 1 linha |
| Data | 120px | Formatada `DD/MM/YYYY` |
| Valor | 120px | Alinhado à direita, formatado em pt-BR |
| Status | 110px | Badge "Pago" / "Pendente" |
| Ações | 80px | Ícones Editar + Excluir (admin) / vazio (colaborador) |

Linhas: `hover:bg-(--color-bg)`, `border-b border-(--color-border)`.

---

## 14. Filtros

Exibidos em linha abaixo do toggle de visualização:

| Filtro | Tipo | Comportamento |
|--------|------|---------------|
| Categoria | `<select>` | Todas / cada categoria do enum |
| Status | `<select>` | Todos / Pago / Pendente |
| De | `<input type="date">` | `date_from` |
| Até | `<input type="date">` | `date_to` |

Ao alterar qualquer filtro, refaz a requisição imediatamente (sem botão "Filtrar"). O painel de totais **também** respeita os filtros aplicados — exibe totais do subconjunto filtrado.

---

## 15. Modal de criação / edição

**Componente:** `resources/js/components/ExpenseModal.vue`

```
┌──────────────────────────────────────────────────────┐
│  Registrar despesa / Editar despesa           [✕]   │
├──────────────────────────────────────────────────────┤
│                                                      │
│  Categoria *                                         │
│  [Selecione ▼]                                       │
│                                                      │
│  Descrição *                                         │
│  [____________________________________________]      │
│                                                      │
│  Valor (R$) *              Data *                    │
│  [0,00    ]                [dd/mm/aaaa]              │
│                                                      │
│  Status de pagamento                                 │
│  ◯ Pendente   ● Pago                                │
│                                                      │
│  Comprovante (opcional)                              │
│  [📎 Selecionar arquivo]  JPG, PNG, WebP ou PDF — 5MB│
│  [thumb/nome do arquivo atual, se houver]            │
│                                                      │
│  Observações (opcional)                              │
│  [                                            ]      │
│  [                                            ]      │
│                                                      │
│                        [Cancelar]  [Salvar]          │
└──────────────────────────────────────────────────────┘
```

- Campo Valor: `<input type="text" inputmode="numeric">` com máscara de moeda pt-BR implementada sem biblioteca externa — digitar dígitos formata automaticamente (ex: digitar `135000` → exibe `1.350,00`); `form.amount` armazena o valor numérico (`1350.00`) para envio à API; ao abrir edição, o valor existente é reconvertido para centavos e exibido formatado
- Campo Data: `<input type="date">` com `max` = data atual
- Comprovante: ao selecionar arquivo, exibir nome; se já existe, exibir link "Ver atual" + botão "Remover"
- Modal: `max-width: 560px`, centralizado, `overflow-y: auto` com `max-height: 90vh`
- Erros de validação exibidos sob cada campo em `--color-danger`, 12px
- Botão "Salvar": `--color-primary`, desabilitado durante o upload

---

## 16. Modal de confirmação de exclusão

Reutiliza o `ConfirmModal.vue` existente.

```
Título:    "Excluir despesa"
Mensagem:  "Tem certeza que deseja excluir a despesa "[descrição]"?
            Esta ação é irreversível e o comprovante também será removido."
Botão:     "Excluir" (--color-danger)
```

---

## 17. Estado vazio

Quando não há despesas (ou o filtro não retorna resultados):

```
┌──────────────────────────────────────────────────────┐
│                                                      │
│            [ícone recibo/documento 48px]             │
│                                                      │
│      "Nenhuma despesa registrada"                    │
│  "Clique em '+ Registrar despesa' para começar."     │
│                                                      │
└──────────────────────────────────────────────────────┘
```

Se há filtros ativos e o resultado está vazio: substituir subtítulo por `"Nenhuma despesa encontrada para os filtros aplicados."`.

---

## 18. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `database/migrations/..._create_event_expenses_table.php` | Criar |
| `app/Models/EventExpense.php` | Criar |
| `app/Models/Event.php` | Adicionar `expenses()` HasMany |
| `app/Http/Controllers/Admin/EventExpenseController.php` | Criar |
| `app/Http/Requests/Admin/Expenses/StoreExpenseRequest.php` | Criar |
| `app/Http/Requests/Admin/Expenses/UpdateExpenseRequest.php` | Criar |
| `app/Services/EventExpenseService.php` | Criar |
| `routes/web.php` | Adicionar rotas de expenses + rota Vue |
| `resources/js/views/admin/EventExpenses.vue` | Criar |
| `resources/js/components/ExpenseModal.vue` | Criar |
| `resources/js/router/admin.js` | Adicionar rota `events/:id/expenses` |
| `.claude/specs/admin/events-details.md` | Atualizar card "Despesas" de placeholder para "✅ Link ativo" |
| `CLAUDE.md` | Atualizar status |
| `.claude/about.md` | Atualizar descrição do módulo Eventos |

---

## 19. Vue Router

```js
// resources/js/router/admin.js — adicionar aos children do AdminLayout
{
    path: 'events/:id/expenses',
    name: 'admin.events.expenses',
    component: () => import('@/views/admin/EventExpenses.vue'),
},
```

---

## 20. Testes

**Arquivo:** `tests/Feature/Admin/Events/EventExpensesTest.php`

Casos obrigatórios:

| # | Cenário |
|---|---------|
| 1 | Admin lista despesas de um evento |
| 2 | Colaborador lista despesas de um evento |
| 3 | Listagem com filtro por categoria |
| 4 | Listagem com filtro por `is_paid` |
| 5 | Listagem com filtro por intervalo de datas |
| 6 | Listagem retorna `summary` com totais corretos |
| 7 | Admin cria despesa sem comprovante |
| 8 | Admin cria despesa com comprovante (upload R2 fake) |
| 9 | Colaborador cria despesa |
| 10 | Criar com `amount` zero retorna 422 |
| 11 | Criar com `date` futura retorna 422 |
| 12 | Criar com `category` inválida retorna 422 |
| 13 | Admin edita despesa |
| 14 | Admin edita despesa substituindo comprovante (deleta anterior no R2) |
| 15 | Colaborador tenta editar → 403 |
| 16 | Admin exclui despesa sem comprovante |
| 17 | Admin exclui despesa com comprovante (deleta do R2) |
| 18 | Colaborador tenta excluir → 403 |
| 19 | Despesa de outro evento retorna 404 |
| 20 | Usuário não autenticado retorna 401 |

---

## 21. Critérios de aceite

- [ ] Somente despesas do evento em questão aparecem na listagem
- [ ] Painel de totais correto: total, pago, pendente
- [ ] Painel de totais atualiza após criar/editar/excluir sem reload da página
- [ ] Filtros funcionam em combinação e atualizam o painel de totais
- [ ] Toggle Cards/Lista funciona; preferência persiste via `localStorage`
- [ ] Modo cards: grid 3/2/1 colunas por breakpoint
- [ ] Modo lista: tabela com scroll horizontal em mobile (sem overflow da página)
- [ ] Colaborador não vê botões Editar e Excluir
- [ ] Colaborador recebe 403 ao tentar editar ou excluir via API
- [ ] Upload de comprovante: formatos e tamanho validados
- [ ] Comprovante anterior é deletado do R2 ao ser substituído
- [ ] Comprovante é deletado do R2 ao excluir a despesa
- [ ] Valores em pt-BR: `R$ 1.350,00`
- [ ] Datas em `DD/MM/YYYY`
- [ ] Campo data não aceita datas futuras
- [ ] Estado vazio com mensagem específica (sem filtro / com filtro)
- [ ] Navegação de volta ao hub do evento funcional
