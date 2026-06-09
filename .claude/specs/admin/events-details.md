# Spec — Detalhe do Evento (Hub)

**Status:** ✅ Implementado
**Módulo:** Admin → Eventos → Detalhe
**Depende de:** `.claude/specs/admin/events-spec.md`
**Sub-módulos:** `.claude/specs/admin/events-cfp.md`

---

## 1. Visão geral

Ao clicar em **"Ver detalhes"** no card de um evento, o usuário é levado a `/admin/events/{id}`. Essa página funciona como um **hub de sub-módulos**: cada funcionalidade do evento aparece como um card de acesso rápido.

Na primeira iteração, somente o **CFP** tem link ativo. Os demais exibem estado "Em breve".

| Sub-módulo | Status |
|-----------|--------|
| CFP — configuração e gestão de palestras | ✅ Link ativo (ver `events-cfp.md`) |
| Despesas | ✅ Link ativo (ver `expenses-spec.md`) |
| Kanban de tarefas | ⬜ Placeholder "Em breve" |
| Participantes | ⬜ Placeholder "Em breve" |
| Sorteio de brindes | ⬜ Placeholder "Em breve" |

---

## 2. Layout da página

```
← Voltar para Eventos

[strip: cover_image ou gradiente placeholder]

[Nome do evento]  [ª edição]  [Badge status]
📅 15/06/2026 — 15/06/2026   📍 Fortaleza — CE

┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│ 📋  CFP          │ │ 💸  Despesas      │ │ ✅  Tarefas       │
│ Aberto           │ │                   │ │                   │
│ 12 palestras     │ │  Em breve         │ │  Em breve         │
│ [Gerenciar →]    │ │                   │ │                   │
└──────────────────┘ └──────────────────┘ └──────────────────┘
┌──────────────────┐ ┌──────────────────┐
│ 👥  Participantes│ │ 🎁  Sorteio       │
│  Em breve        │ │  Em breve         │
└──────────────────┘ └──────────────────┘
```

Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4`

---

## 3. Card CFP

O card reflete o estado atual do CFP. O status é calculado pelo frontend com base em `opens_at` / `closes_at` retornados pela API.

| Situação | Exibe |
|----------|-------|
| CFP não configurado | "Não configurado" + botão "Configurar" |
| `now < opens_at` | Badge "Aguardando 🟡" + período + N palestras |
| `opens_at ≤ now ≤ closes_at` | Badge "Aberto 🟢" + período + N palestras |
| `now > closes_at` | Badge "Encerrado ⚫" + período + N palestras |

O botão **"Gerenciar →"** navega para `{ name: 'admin.events.cfp', params: { id: event.id } }`.

O campo `cfp` da resposta da API inclui o registro de configuração (ou `null`) e a contagem de palestras: `{ total, submetida, em_analise, aprovada, rejeitada, cancelada }`.

---

## 4. Cards "Em breve"

```
┌──────────────────────────────────────┐
│  💸  Despesas                         │
│                                       │
│  Em desenvolvimento. Em breve você    │
│  poderá registrar despesas do evento. │
└──────────────────────────────────────┘
```

Sem botão de ação. Cursor padrão. Sem `hover` de destaque.

---

## 5. Rota e API

### Backend

```php
// routes/web.php — dentro do grupo auth + EnsureAdminRole
Route::get('/events/{id}',     fn () => view('admin'))->name('events.show');
Route::get('/events/{id}/cfp', fn () => view('admin'))->name('events.cfp');
```

### API

| Método | Endpoint | Descrição |
|--------|---------|-----------|
| `GET` | `/admin/api/events/{event}` | Dados do evento — já existe |
| `GET` | `/admin/api/events/{event}/cfp` | Config do CFP + contagem de palestras (ver spec CFP) |

### Vue Router

```js
// resources/js/router/admin.js — adicionar aos children do AdminLayout
{
    path: 'events/:id',
    name: 'admin.events.show',
    component: () => import('@/views/admin/EventDetail.vue'),
},
{
    path: 'events/:id/cfp',
    name: 'admin.events.cfp',
    component: () => import('@/views/admin/EventCfp.vue'),
},
```

---

## 6. Botão "Ver detalhes" no card de eventos

Em `resources/js/views/admin/Events.vue`, adicionar ao card de cada evento:

```html
<RouterLink
    :to="{ name: 'admin.events.show', params: { id: event.id } }"
    class="text-xs px-2 py-1 rounded border border-(--color-border)
           text-(--color-text) hover:bg-gray-100 dark:hover:bg-gray-700 transition"
>
    Ver detalhes
</RouterLink>
```

Visível para eventos em qualquer status.

---

## 7. Arquivo Vue — `EventDetail.vue`

**Localização:** `resources/js/views/admin/EventDetail.vue`

- Ao montar: busca `GET /admin/api/events/:id` e `GET /admin/api/events/:id/cfp`
- Exibe header com back button, strip de capa, nome, status badge e data/local
- Renderiza grid de cards (CFP funcional + demais como placeholder)
- Em caso de 404: exibe mensagem de evento não encontrado com link para voltar

---

## 8. Arquivos a criar / modificar

**Criar:**

| Arquivo | Tipo |
|---------|------|
| `resources/js/views/admin/EventDetail.vue` | View Vue |

**Modificar:**

| Arquivo | O que muda |
|---------|-----------|
| `resources/js/views/admin/Events.vue` | Adicionar botão "Ver detalhes" no card |
| `resources/js/router/admin.js` | Adicionar rotas `events.show` e `events.cfp` |
| `routes/web.php` | Adicionar rotas SPA `/events/{id}` e `/events/{id}/cfp` |

---

## 9. Critérios de aceite

- [ ] Botão "Ver detalhes" aparece em todos os cards de evento
- [ ] Navegar para `/admin/events/{id}` exibe a página do evento
- [ ] Header exibe: nome, status badge, edição (se houver), data e local/online
- [ ] Cover image aparece no strip quando disponível; gradiente placeholder quando não
- [ ] Card CFP reflete o estado correto (não configurado / aguardando / aberto / encerrado)
- [ ] Card CFP exibe contagem de palestras quando o CFP está configurado
- [ ] Botão "Gerenciar" no card CFP navega para `/admin/events/{id}/cfp`
- [ ] Cards de Despesas, Tarefas, Participantes e Sorteio exibem "Em breve" sem link
- [ ] Evento inexistente exibe mensagem de erro (não exibe 500)
- [ ] Página funciona ao recarregar (F5) — rota Laravel presente
