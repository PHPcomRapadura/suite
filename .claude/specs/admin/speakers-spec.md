# Spec — Listagem de Palestrantes

**Status:** ✅ Implementado
**Módulo:** Admin → Palestrantes
**Depende de:** CFP público (tabelas `speakers`, `users`, `talks` já existem)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Visão geral

Página global no painel admin para visualizar todos os palestrantes cadastrados na plataforma. Os dados são **somente leitura** — nenhuma edição é permitida pelo admin. Detalhes completos (bio, redes sociais, palestras) são exibidos em modal ao clicar no card/linha.

| Ação | `admin` | `colaborador` |
|------|---------|---------------|
| Listar palestrantes | ✅ | ✅ |
| Visualizar detalhes (modal) | ✅ | ✅ |
| Editar dados | ❌ | ❌ |

---

## 2. Dados existentes

Os dados já estão nas tabelas:

### `users`

| Campo | Exibido |
|-------|---------|
| `name` | ✅ |
| `email` | ✅ |
| `is_active` | ✅ (badge Ativo/Inativo) |
| `last_login_at` | ✅ (no modal) |

### `speakers`

| Campo | Exibido |
|-------|---------|
| `bio` | Modal |
| `company` | Modal + card |
| `city` | ✅ Lista e modal |
| `state` | ✅ Lista e modal |
| `avatar_url` | ✅ |
| `phone_number` | ✅ Lista e modal |
| `website` | Modal |
| `twitter` | Modal |
| `github` | Modal |
| `linkedin` | Modal |

### `talks`

Cada `Talk` pertence a um `Speaker`. O total de palestras submetidas e aprovadas pelo palestrante é exibido no card e no modal.

---

## 3. Rotas

### 3.1 Backend (API)

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::prefix('api/speakers')->name('speakers.')->group(function () {
    Route::get('/',          [SpeakerController::class, 'index'])->name('index');
    Route::get('/{speaker}', [SpeakerController::class, 'show'])->name('show');
});

// Rota Vue (SPA)
Route::get('/speakers', fn () => view('admin'))->name('speakers');
```

### 3.2 Vue Router

```js
{
    path: 'speakers',
    name: 'admin.speakers',
    component: () => import('@/views/admin/Speakers.vue'),
},
```

---

## 4. Controller

**Arquivo:** `app/Http/Controllers/Admin/SpeakerController.php`

### 4.1 `index`

```
GET /admin/api/speakers

Query params:
  search   string   — filtra por nome ou e-mail (users.name LIKE ou users.email LIKE)
  city     string   — filtra por speakers.city (LIKE)
  state    string   — filtra por speakers.state (exact match, 2 chars)
  page     int      — paginação (default 1)

Retorna:
{
  "data": [
    {
      "id": 1,                         ← speakers.id
      "name": "William Marques",       ← users.name
      "email": "wi@example.com",       ← users.email
      "avatar_url": "https://...",
      "company": "Acme",
      "city": "Fortaleza",
      "state": "CE",
      "phone_number": "(85) 91234-5678",
      "is_active": true,
      "talks_count": 4,               ← total de talks submetidas
      "talks_approved": 1             ← talks com status = 'aprovada'
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 12,
    "total": 30
  }
}
```

### 4.2 `show`

```
GET /admin/api/speakers/{speaker}

Retorna o mesmo objeto acima + campos completos do perfil:
{
  "id": 1,
  "name": "...",
  "email": "...",
  "avatar_url": "...",
  "company": "...",
  "city": "...",
  "state": "...",
  "phone_number": "...",
  "bio": "...",
  "website": "...",
  "twitter": "...",
  "github": "...",
  "linkedin": "...",
  "is_active": true,
  "last_login_at": "2026-06-09T21:00:00Z",
  "talks_count": 4,
  "talks_approved": 1,
  "talks": [
    {
      "id": 1,
      "title": "PHP no Nordeste",
      "event": "PHP com Rapadura 2026",
      "status": "aprovada",
      "level": "iniciante",
      "duration": 45,
      "submitted_at": "2026-05-01T10:00:00Z"
    }
  ]
}
```

---

## 5. Service

**Arquivo:** `app/Services/SpeakerService.php`

```php
public function list(array $filters): LengthAwarePaginator;
// Query: speakers JOIN users
//   filtros: search → users.name LIKE %?% OR users.email LIKE %?%
//            city   → speakers.city LIKE %?%
//            state  → speakers.state = ?
//   withCount(['talks', 'talks as talks_approved_count' => fn($q) => $q->where('status', 'aprovada')])
//   paginate(12)

public function detail(Speaker $speaker): array;
// Speaker com user, talks (→ event: name), contagem aprovadas
```

---

## 6. Layout da página

**Rota Vue:** `/admin/speakers`
**Componente:** `resources/js/views/admin/Speakers.vue`
**Container raiz:** `div.flex.flex-col.gap-6.p-5`

```
┌─────────────────────────────────────────────────────────────────┐
│  Palestrantes                                30 cadastrados      │
├─────────────────────────────────────────────────────────────────┤
│  [🔍 Buscar por nome ou e-mail...]  [Cidade]  [Estado ▼]        │
│                                              [▦ Cards] [≡ Lista] │
└─────────────────────────────────────────────────────────────────┘

[Cards 3×colunas ou tabela dependendo do toggle]

[Paginação]
```

---

## 7. Toggle de exibição

- Botões `[▦ Cards]` e `[≡ Lista]` no header
- Persistido em `localStorage('speakers_view_mode')`, default `'cards'`

---

## 8. Modo Cards

Grid `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`.

```
┌────────────────────────────────────────┐
│  [avatar 40px]  William Marques        │
│                 wi@example.com         │
│                 Fortaleza, CE          │
│                                        │
│  Acme · (85) 91234-5678               │
│                                        │
│  4 palestras · 1 aprovada   [Ativo ●]  │
└────────────────────────────────────────┘
```

- Avatar: `w-10 h-10 rounded-full object-cover` — se `avatar_url` vazio, iniciais do nome no mesmo estilo do user no sidebar
- Badge de status: `Ativo` (verde) / `Inativo` (cinza)
- Clique em qualquer área do card abre o `SpeakerModal`
- Cursor `cursor-pointer` no card inteiro

---

## 9. Modo Lista

Tabela com scroll horizontal em mobile.

| # | Avatar + Nome | E-mail | Cidade / Estado | Telefone | Palestras | Status |
|---|--------------|--------|-----------------|----------|-----------|--------|

- Cada linha é clicável → abre `SpeakerModal`
- Sem botões de ação (somente leitura)

---

## 10. Filtros

| Filtro | Tipo | Comportamento |
|--------|------|---------------|
| Busca | `input[type=text]` | Debounce 300ms — filtra `name` e `email` no backend |
| Cidade | `input[type=text]` | Debounce 300ms — LIKE no backend |
| Estado | `select` | Lista dos 27 estados brasileiros (sigla 2 chars) + opção "Todos" — exact match |

Ao alterar qualquer filtro: página volta para 1.

---

## 11. Paginação

Mesma lógica de ellipsis dos outros módulos. 12 itens por página.

---

## 12. Estado vazio

```
Nenhum palestrante encontrado.
```

- Se filtros ativos: "Nenhum resultado para os filtros aplicados."
- Se sem filtros e lista vazia: "Nenhum palestrante cadastrado ainda."

---

## 13. `SpeakerModal.vue`

**Arquivo:** `resources/js/components/SpeakerModal.vue`

Modal de detalhes — somente leitura.

```
┌──────────────────────────────────────────────────────┐
│  [avatar 64px]  William Marques          [● Ativo]   │
│                 wi@example.com                        │
│                 Acme · Fortaleza, CE                  │
│                                                       │
│  Bio:                                                 │
│  "Desenvolvedor PHP há 10 anos..."                    │
│                                                       │
│  📞 (85) 91234-5678                                   │
│  🌐 example.com                                       │
│  𝕏 @wmarques  🐙 github.com/wm  💼 linkedin/wm       │
│                                                       │
│  Último acesso: 09/06/2026 às 21h00                   │
│                                                       │
│ ─────────────────────────────────────────────────── │
│  Palestras (4 submetidas · 1 aprovada)                │
│                                                       │
│  ✅ PHP no Nordeste         PHP com Rapadura 2026     │
│     45min · Iniciante · enviada em 01/05/2026         │
│                                                       │
│  ⏳ Microsserviços com PHP  PHP com Rapadura 2025     │
│     60min · Avançado · enviada em 10/03/2025          │
│                                                [Fechar]│
└──────────────────────────────────────────────────────┘
```

- Dados carregados via `GET /admin/api/speakers/{id}` ao abrir o modal
- Spinner enquanto carrega
- Campos não preenchidos não são exibidos (bio, redes sociais, etc.)
- Status das palestras com badge colorido: `aprovada` (verde), `em_analise` (amarelo), `submetida` (azul), `rejeitada` (vermelho), `cancelada` (cinza)
- Sem botões de edição — apenas `[Fechar]`

---

## 14. Menu lateral (`AppSidebar.vue`)

Adicionar item em `navItems`:

```js
{ name: 'admin.speakers', label: 'Palestrantes', icon: 'mic', roles: ['admin', 'colaborador'] },
```

Ícone `mic` (microfone) — novo SVG a adicionar no template do sidebar.

---

## 15. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `app/Http/Controllers/Admin/SpeakerController.php` | Criar |
| `app/Services/SpeakerService.php` | Criar |
| `routes/web.php` | Adicionar rotas `api/speakers` + rota Vue |
| `resources/js/views/admin/Speakers.vue` | Criar |
| `resources/js/components/SpeakerModal.vue` | Criar |
| `resources/js/router/admin.js` | Adicionar rota `speakers` |
| `resources/js/components/AppSidebar.vue` | Adicionar item de menu + ícone `mic` |

---

## 16. Testes

**Arquivo:** `tests/Feature/Admin/SpeakersTest.php`

| # | Cenário |
|---|---------|
| 1 | Guest recebe 401 ao listar palestrantes |
| 2 | Admin lista palestrantes paginados (12 por página) |
| 3 | Colaborador lista palestrantes |
| 4 | Listagem retorna `data`, `meta` com estrutura correta |
| 5 | Filtro `search` filtra por nome |
| 6 | Filtro `search` filtra por e-mail |
| 7 | Filtro `city` filtra por cidade (LIKE) |
| 8 | Filtro `state` filtra por estado (exact match) |
| 9 | Filtros combinados retornam subconjunto correto |
| 10 | `talks_count` e `talks_approved` são calculados corretamente |
| 11 | Palestrante sem perfil `Speaker` não aparece na listagem |
| 12 | Admin visualiza detalhe de um palestrante |
| 13 | Detalhe inclui lista de palestras com evento e status |
| 14 | Palestrante inativo aparece na listagem com `is_active: false` |
| 15 | Guest recebe 401 ao acessar detalhe |

---

## 17. Regras de negócio

| Regra | Detalhe |
|-------|---------|
| Somente palestrantes | Apenas usuários com `role = 'palestrante'` e que possuam registro em `speakers` aparecem na listagem |
| Somente leitura | Nenhuma rota de criação, edição ou exclusão — módulo é puramente de consulta |
| Palestrantes sem perfil | Usuários `role = 'palestrante'` que nunca preencheram o perfil no CFP (`speakers` record inexistente) **não aparecem** na listagem |
| Ordenação padrão | `users.name ASC` |

---

## 18. Critérios de aceite

- [ ] Menu lateral com item "Palestrantes" visível para admin e colaborador
- [ ] Toggle cards/lista persiste entre navegações (localStorage)
- [ ] Filtros de nome, cidade e estado funcionam individualmente e combinados
- [ ] Paginação de 12 itens por página com ellipsis
- [ ] Card exibe avatar, nome, e-mail, cidade/estado, telefone, empresa, contagem de palestras e badge de status
- [ ] Modal carrega dados via API ao abrir (com spinner)
- [ ] Modal exibe todos os campos preenchidos + lista de palestras
- [ ] Campos vazios não são renderizados no modal
- [ ] Somente palestrantes com perfil (`speakers` record) aparecem
- [ ] Página funciona ao recarregar (F5) — rota Laravel presente
