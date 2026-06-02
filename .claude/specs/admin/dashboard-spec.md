# Spec — Dashboard & Layout do Admin

**Status:** ✅ Implementado
**Módulo:** Admin — estrutura de layout
**Depende de:** `.claude/specs/admin/auth-spec.md`, `.claude/specs/admin/user-crud-spec.md`

---

## 1. Visão geral

Introduz o layout permanente do painel: **sidebar lateral esquerda** fixa com navegação, informações do usuário logado, alternância de tema (light/dark) e logout. Todas as rotas autenticadas passam a usar esse layout via Vue Router nested routes.

---

## 2. Resultado esperado

```
┌────────────────────────────────────────────────────────────────┐
│  Sidebar (260px)           │  Área de conteúdo (flex-1)        │
│ ─────────────────────────  │                                   │
│  [Logo]                    │  <RouterView />                   │
│                            │  (Dashboard, Users, etc.)         │
│  ● Dashboard               │                                   │
│  ○ Usuários                │                                   │
│                            │                                   │
│  ─── (flex-grow) ───────── │                                   │
│                            │                                   │
│  [Avatar] Nome Sobrenome   │                                   │
│           Administrador    │                                   │
│                            │                                   │
│  ○ ☀ / 🌙  Tema           │                                   │
│  ○ Sair                    │                                   │
└────────────────────────────────────────────────────────────────┘
```

Em **mobile** (< `lg`): sidebar se torna drawer deslizante com overlay, acionado por botão hambúrguer no topo do conteúdo.

---

## 3. Arquitetura de componentes

```
App.vue
  └── RouterView
        ├── Login.vue              ← rota guest (sem layout)
        └── AdminLayout.vue        ← rota pai autenticada
              ├── AppSidebar.vue   ← sidebar (usa RouterLink)
              └── RouterView       ← conteúdo da rota filha
                    ├── Dashboard.vue
                    └── Users.vue
```

### 3.1 Nested routes no Vue Router

```js
// resources/js/router/admin.js
{
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { auth: true },
    children: [
        { path: 'dashboard', name: 'admin.dashboard', component: () => import('@/views/admin/Dashboard.vue') },
        { path: 'users',     name: 'admin.users',     component: () => import('@/views/admin/Users.vue') },
        { path: '',          redirect: { name: 'admin.dashboard' } },
    ],
},
```

> Remover as rotas planas atuais de `dashboard` e `users` que existem no nível raiz.

---

## 4. Usuário logado

### 4.1 Endpoint

```
GET /admin/api/me
→ middleware: auth + EnsureAdminRole
→ retorna: { id, name, email, role, is_active, last_login_at }
→ nunca inclui password
```

Rota em `routes/web.php`, dentro do grupo protegido:

```php
Route::get('/api/me', fn () => response()->json(Auth::user()))->name('me');
```

### 4.2 Composable `useAuth`

**Arquivo:** `resources/js/composables/useAuth.js`

Estado global reativo no nível do módulo (singleton): o objeto `user` é compartilhado entre todos os componentes que importam o composable. `AdminLayout.vue` chama `fetchUser()` no `onMounted`.

```js
import { ref } from 'vue'
import axios from 'axios'

const user = ref(null)

async function fetchUser() {
    const { data } = await axios.get('/admin/api/me')
    user.value = data
}

async function logout() {
    await axios.post('/admin/logout')
    user.value = null
    window.location.href = '/admin/login'
}

export function useAuth() {
    return { user, fetchUser, logout }
}
```

---

## 5. Tema (light / dark)

### 5.1 Mecanismo

- Persiste em `localStorage` sob a chave `'admin-theme'` (`'light'` | `'dark'`)
- Aplica a classe `dark` no elemento `<html>` via JavaScript
- **Anti-flash:** script inline no `<head>` do `admin.blade.php` aplica o tema antes do primeiro render

```html
<!-- admin.blade.php — no <head>, antes do @vite -->
<script>
    (function () {
        var t = localStorage.getItem('admin-theme')
        if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    })()
</script>
```

### 5.2 Composable `useTheme`

**Arquivo:** `resources/js/composables/useTheme.js`

Estado no nível do módulo (singleton), `watchEffect` mantém o DOM e o `localStorage` sincronizados.

```js
import { ref, watchEffect } from 'vue'

const isDark = ref(document.documentElement.classList.contains('dark'))

function toggle() {
    isDark.value = !isDark.value
}

watchEffect(() => {
    document.documentElement.classList.toggle('dark', isDark.value)
    localStorage.setItem('admin-theme', isDark.value ? 'dark' : 'light')
})

export function useTheme() {
    return { isDark, toggle }
}
```

### 5.3 Tokens CSS — dark mode

Adicionar ao final de `resources/css/app.css`:

```css
html.dark {
    --color-primary:       #3b82f6;
    --color-primary-hover: #2563eb;
    --color-bg:            #0f172a;
    --color-surface:       #1e293b;
    --color-border:        #334155;
    --color-text:          #f1f5f9;
    --color-text-muted:    #94a3b8;
    --color-success:       #22c55e;
    --color-warning:       #fbbf24;
    --color-danger:        #f87171;
}
```

### 5.4 Tokens CSS — sidebar

A sidebar usa fundo escuro **independente do tema** (padrão de admin panels). Adicionar em `app.css`:

```css
:root {
    --color-sidebar-bg:          #0f172a;
    --color-sidebar-border:      #1e293b;
    --color-sidebar-text:        #94a3b8;
    --color-sidebar-text-active: #f1f5f9;
    --color-sidebar-hover:       rgba(255, 255, 255, 0.06);
    --color-sidebar-active:      rgba(255, 255, 255, 0.10);
    --color-sidebar-logo-bg:     #020617;
}
```

---

## 6. Sidebar — `AppSidebar.vue`

**Arquivo:** `resources/js/components/AppSidebar.vue`

### 6.1 Estrutura visual

```
┌──────────────────────────────┐
│  [Logo 140px]                │  ← h-16, fundo --color-sidebar-logo-bg
├──────────────────────────────┤
│  🏠 Dashboard                │  ← nav, flex-col, gap-1, p-3
│  👤 Usuários (só admin)      │
│                              │
│         [flex-grow]          │  ← empurra rodapé para baixo
│                              │
├──────────────────────────────┤
│  [Avatar] Nome Sobrenome     │  ← p-3
│           Administrador      │
│                              │
│  ☀/🌙  Alternar tema        │
│  ↪ Sair                     │
└──────────────────────────────┘
```

### 6.2 Largura e dimensões

- Largura fixa: `w-[260px]`
- Altura: `h-screen`
- Fundo: `bg-(--color-sidebar-bg)`
- Borda direita: `border-r border-(--color-sidebar-border)`
- Layout: `flex flex-col`

### 6.3 Estilo dos itens de menu

- Container: `flex items-center gap-3 px-3 py-2.5 rounded-lg min-h-[40px] transition text-sm`
- Ativo: `bg-(--color-sidebar-active) text-(--color-sidebar-text-active) font-medium`
- Inativo: `text-(--color-sidebar-text) hover:bg-(--color-sidebar-hover) hover:text-(--color-sidebar-text-active)`
- Detecção do item ativo: comparar `route.name` via `useRoute()`

### 6.4 Itens de navegação

| Ícone | Rótulo | Rota nomeada | Visível para |
|-------|--------|--------------|--------------|
| grid 2×2 | Dashboard | `admin.dashboard` | admin, colaborador |
| silhueta grupo | Usuários | `admin.users` | somente `admin` |

### 6.5 Avatar do usuário

- Círculo `w-9 h-9`, fundo `--color-primary`, texto branco `font-semibold text-sm`
- Iniciais: primeiras letras do primeiro e último nome (ex.: "Alisson Sousa" → "AS")
- Ao lado: nome truncado (`max-w-[130px] truncate text-sm font-medium text-(--color-sidebar-text-active)`) e role em texto `text-xs text-(--color-sidebar-text)`

### 6.6 Toggle de tema

- Botão full-width com o mesmo estilo visual dos itens de menu
- Ícone sol quando `isDark === true` (indicando que clica para voltar ao claro)
- Ícone lua quando `isDark === false` (indicando que clica para ir ao escuro)
- Label: `"Modo claro"` no dark / `"Modo escuro"` no light

### 6.7 Botão logout

- Label: `"Sair"`, ícone `log-out`
- Cor base: `text-(--color-sidebar-text)`
- Hover: `text-red-400 bg-red-500/10`
- Ao clicar: `logout()` do composable `useAuth`

### 6.8 Botão fechar (mobile)

- Visível apenas em mobile (`lg:hidden`)
- Posicionado no canto superior direito da sidebar, dentro da área de logo
- Emite evento `@close` para que `AdminLayout` feche o drawer

---

## 7. Layout — `AdminLayout.vue`

**Arquivo:** `resources/js/layouts/AdminLayout.vue`

### 7.1 Estrutura

```
div.flex.h-screen.overflow-hidden.bg-(--color-bg)
  ├── AppSidebar (hidden lg:flex — desktop)
  ├── div.fixed.inset-0.z-20 (overlay mobile — só quando sidebarOpen)
  ├── AppSidebar (fixed.inset-y-0.left-0.z-30 lg:hidden — mobile drawer)
  └── div.flex-1.flex.flex-col.min-w-0.overflow-auto
        ├── header (lg:hidden — topbar mobile)
        │     ├── botão hambúrguer
        │     └── logo centralizada (h-7)
        └── main.flex-1
              └── RouterView
```

### 7.2 Transição do drawer mobile

```css
.sidebar-slide-enter-active,
.sidebar-slide-leave-active { transition: transform 0.2s ease; }
.sidebar-slide-enter-from,
.sidebar-slide-leave-to    { transform: translateX(-100%); }
```

### 7.3 Responsividade

| Breakpoint | Comportamento |
|-----------|---------------|
| `< lg` (< 1024px) | Sidebar oculta; topbar com hambúrguer; drawer ao clicar |
| `≥ lg` (≥ 1024px) | Sidebar fixa 260px; topbar oculta |

---

## 8. Dashboard — `Dashboard.vue`

### 8.1 Layout

```
div.p-6 (ou p-8 em desktop)
  ├── Saudação: "Olá, [Nome]!" (text-2xl font-bold)
  │   Data/hora atual (text-sm text-muted, atualiza a cada minuto)
  │
  └── Grid de cards (grid-cols-1 md:grid-cols-3 gap-4, mt-8)
        ├── Card: Total de usuários
        ├── Card: Administradores
        └── Card: Usuários inativos
```

### 8.2 Card de estatística

```
bg-(--color-surface) border border-(--color-border) rounded-xl p-5
  ├── Ícone (24px, text-(--color-text-muted))
  ├── Número (text-3xl font-bold text-(--color-text), mt-3)
  └── Label (text-sm text-(--color-text-muted), mt-1)
```

Skeleton loader enquanto `loading === true`: retângulo `animate-pulse bg-gray-200 dark:bg-gray-700 rounded`.

### 8.3 Endpoint de estatísticas

```
GET /admin/api/dashboard/stats
→ middleware: auth + EnsureAdminRole
→ controller: DashboardController@stats
```

```php
// app/Http/Controllers/Admin/DashboardController.php
return response()->json([
    'users_total'    => User::count(),
    'users_admin'    => User::where('role', 'admin')->count(),
    'users_inactive' => User::where('is_active', false)->count(),
]);
```

Rota em `routes/web.php` dentro do grupo protegido:

```php
Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
```

---

## 9. Ajustes nas views existentes

### 9.1 `Dashboard.vue`

- Remover `min-h-screen flex items-center justify-center` — o layout gerencia isso
- Novo conteúdo: saudação + cards de stats (seção 8)

### 9.2 `Users.vue`

- Remover o wrapper `<div class="min-h-screen bg-(--color-bg)">` externo
- Manter o `<div class="max-w-7xl mx-auto px-4 py-8">` interno inalterado

---

## 10. Arquivos a criar / modificar

| Arquivo | Ação |
|---------|------|
| `resources/js/layouts/AdminLayout.vue` | Criar |
| `resources/js/components/AppSidebar.vue` | Criar |
| `resources/js/composables/useAuth.js` | Criar |
| `resources/js/composables/useTheme.js` | Criar |
| `app/Http/Controllers/Admin/DashboardController.php` | Criar |
| `resources/js/router/admin.js` | Modificar (nested routes) |
| `resources/js/views/admin/Dashboard.vue` | Modificar (saudação + stats) |
| `resources/js/views/admin/Users.vue` | Modificar (remover wrapper) |
| `resources/css/app.css` | Modificar (dark mode + sidebar tokens) |
| `resources/views/admin.blade.php` | Modificar (script anti-flash) |
| `routes/web.php` | Modificar (`/api/me` e `/api/dashboard/stats`) |

---

## 11. Ícones SVG (inline, 24×24, stroke-width 1.5)

| Uso | `d` do `<path>` |
|-----|-----------------|
| Dashboard | `M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z` |
| Usuários | `M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2` + `<circle cx="9" cy="7" r="4"/>` + `M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75` |
| Sol | `M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42` + `<circle cx="12" cy="12" r="5"/>` |
| Lua | `M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z` |
| Logout | `M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9` |
| Hambúrguer | `M3 12h18M3 6h18M3 18h18` |
| Fechar | `M18 6L6 18M6 6l12 12` |

---

## 12. Critérios de aceite

### Layout
- [ ] Sidebar visível e fixa (260px) em telas ≥ 1024px
- [ ] Sidebar abre/fecha como drawer em telas < 1024px
- [ ] Overlay escurece o fundo com o drawer aberto
- [ ] Clicar no overlay fecha o drawer
- [ ] Topbar mobile exibe logo e botão hambúrguer

### Navegação
- [ ] Item da rota ativa destacado visualmente
- [ ] Item "Usuários" oculto para `colaborador`
- [ ] `/admin` redireciona para `/admin/dashboard`
- [ ] Transição de rota não causa flash de layout

### Usuário logado
- [ ] Nome e role exibidos no rodapé da sidebar
- [ ] Avatar com iniciais corretas do nome
- [ ] Dados carregados via `GET /admin/api/me` após montagem do layout

### Tema
- [ ] Toggle alterna entre light e dark instantaneamente
- [ ] Preferência persiste após recarregar a página
- [ ] Script anti-flash evita FOUC
- [ ] Todos os tokens CSS respondem ao `.dark`
- [ ] Sidebar mantém visual escuro em ambos os temas

### Dashboard
- [ ] Saudação exibe o nome do usuário logado
- [ ] Data/hora atualiza a cada minuto
- [ ] 3 cards exibem contagens reais do banco
- [ ] Skeleton visível durante carregamento

### Logout
- [ ] POST `/admin/logout` é chamado ao clicar em "Sair"
- [ ] Redireciona para `/admin/login` após logout
