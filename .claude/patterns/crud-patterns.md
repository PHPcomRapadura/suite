# Padrão de Implementação de CRUDs — Durano Loc

Este documento define o padrão de implementação de CRUDs no Durano Loc, baseado no módulo de Usuários. Todos os CRUDs devem seguir este padrão para manter consistência na interface e na arquitetura.

---

## 1. Visão Geral

### 1.1 Estrutura de Interface

- **Listagem**: Cards em grid (não usar tabelas)
- **Layout**: 3 cards por linha (desktop), 2 (tablet), 1 (mobile)
- **Paginação**: 9 itens por página
- **Ações**: Modais para criar/editar, toggle para status, confirm modal para excluir

### 1.2 Arquitetura

```
Backend (Laravel)
├── Controller (app/Http/Controllers/)
├── Requests (app/Http/Requests/{Module}/)
└── Service (app/Services/)

Frontend (Vue.js)
├── View (resources/js/views/{section}/)
├── Modal específico (resources/js/components/)
└── Componentes reutilizáveis
```

---

## 2. Backend

### 2.1 Controller

Localização: `app/Http/Controllers/{Module}Controller.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\{Module}\Store{Model}Request;
use App\Http\Requests\{Module}\Update{Model}Request;
use App\Models\{Model};
use App\Services\{Model}Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class {Module}Controller extends Controller
{
    public function __construct(
        private {Model}Service $service
    ) {}

    // Listagem com busca, filtros e paginação
    public function index(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $perPage = $request->input('per_page', 9);
        $search = $request->input('search');
        $status = $request->input('status'); // active, inactive, all

        $query = {Model}::where('company_id', $companyId);

        // Busca
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filtro de status
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $items = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Store{Model}Request $request): JsonResponse
    {
        $item = $this->service->create(
            auth()->user()->company_id,
            $request->validated()
        );

        return response()->json([
            'message' => '{Model} cadastrado com sucesso',
            'data' => $item,
        ], Response::HTTP_CREATED);
    }

    public function show({Model} $model): JsonResponse
    {
        $this->authorizeCompanyAccess($model);

        return response()->json([
            'data' => $model,
        ]);
    }

    public function update(Update{Model}Request $request, {Model} $model): JsonResponse
    {
        $this->authorizeCompanyAccess($model);

        $item = $this->service->update($model, $request->validated());

        return response()->json([
            'message' => '{Model} atualizado com sucesso',
            'data' => $item,
        ]);
    }

    public function destroy({Model} $model): JsonResponse
    {
        $this->authorizeCompanyAccess($model);

        $this->service->delete($model);

        return response()->json([
            'message' => '{Model} excluído com sucesso',
        ]);
    }

    public function toggleStatus({Model} $model): JsonResponse
    {
        $this->authorizeCompanyAccess($model);

        $item = $this->service->toggleStatus($model);
        $status = $item->is_active ? 'ativado' : 'inativado';

        return response()->json([
            'message' => "{Model} {$status} com sucesso",
            'data' => $item,
        ]);
    }

    private function authorizeCompanyAccess({Model} $model): void
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso não autorizado');
        }
    }
}
```

### 2.2 Form Requests

Localização: `app/Http/Requests/{Module}/`

**StoreRequest**
```php
<?php

namespace App\Http\Requests\{Module};

use Illuminate\Foundation\Http\FormRequest;

class Store{Model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:{table},email'],
            // ... outros campos
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo :min caracteres.',
            // ... outras mensagens
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            // ... outros atributos
        ];
    }
}
```

**UpdateRequest**
```php
<?php

namespace App\Http\Requests\{Module};

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update{Model}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('{model}')->id;

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('{table}', 'email')->ignore($id),
            ],
            // ... outros campos
            'is_active' => ['boolean'],
        ];
    }

    // messages() e attributes() similares ao Store
}
```

### 2.3 Service

Localização: `app/Services/{Model}Service.php`

```php
<?php

namespace App\Services;

use App\Models\{Model};

class {Model}Service
{
    public function create(int $companyId, array $data): {Model}
    {
        return {Model}::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            // ... outros campos
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update({Model} $model, array $data): {Model}
    {
        $model->update($data);
        return $model->fresh();
    }

    public function delete({Model} $model): bool
    {
        return $model->delete();
    }

    public function toggleStatus({Model} $model): {Model}
    {
        $model->update([
            'is_active' => !$model->is_active,
        ]);
        return $model->fresh();
    }
}
```

### 2.4 Rotas da API

Arquivo: `routes/api.php`

```php
// Dentro do grupo autenticado
Route::apiResource('{resources}', {Module}Controller::class);
Route::patch('/{resources}/{model}/toggle-status', [{Module}Controller::class, 'toggleStatus']);
```

---

## 3. Frontend

### 3.1 Estrutura da View

Localização: `resources/js/views/{section}/{Module}.vue`

```vue
<template>
    <AppLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-(--color-text)">{Título}</h1>
                        <p class="text-(--color-text-muted)">{Descrição}</p>
                    </div>
                    <button @click="openCreateModal" class="...">
                        <IconPlus :size="18" />
                        Novo {Item}
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-(--color-surface) rounded-(--radius-card) border border-(--color-border) p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input v-model="search" type="text" placeholder="Buscar..." @input="debouncedSearch" />
                        </div>
                        <select v-model="statusFilter" @change="loadItems">
                            <option value="all">Todos</option>
                            <option value="active">Ativos</option>
                            <option value="inactive">Inativos</option>
                        </select>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="flex justify-center py-12">
                    <div class="animate-spin w-8 h-8 border-4 border-(--color-primary) border-t-transparent rounded-full"></div>
                </div>

                <!-- Empty state -->
                <div v-else-if="items.length === 0" class="text-center py-12">
                    <!-- Ícone, título e botão -->
                </div>

                <!-- Grid de cards -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="item in items" :key="item.id" class="...">
                        <!-- Conteúdo do card -->
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="meta.last_page > 1" class="...">
                    <!-- Controles de paginação -->
                </div>
            </div>
        </div>

        <!-- Modal de Criar/Editar -->
        <{Model}Modal :show="showModal" :item="selectedItem" @close="closeModal" @saved="onSaved" />

        <!-- Modal de Confirmação -->
        <ConfirmModal :show="showDeleteModal" ... @confirm="handleDelete" @cancel="closeDeleteModal" />
    </AppLayout>
</template>
```

### 3.2 Estrutura do Card

```vue
<div class="bg-(--color-surface) rounded-(--radius-card) border border-(--color-border) p-4 hover:shadow-md transition-shadow">
    <!-- Header com avatar/ícone e info principal -->
    <div class="flex items-start gap-3 mb-3">
        <div class="w-10 h-10 rounded-full bg-(--color-primary)/10 flex items-center justify-center flex-shrink-0">
            <span class="text-sm font-medium text-(--color-primary)">
                {{ getInitials(item.name) }}
            </span>
        </div>
        <div class="min-w-0 flex-1">
            <h3 class="font-medium text-(--color-text) truncate">{{ item.name }}</h3>
            <p class="text-sm text-(--color-text-muted) truncate">{{ item.email }}</p>
        </div>
    </div>

    <!-- Info adicional -->
    <div class="text-xs text-(--color-text-muted) mb-4">
        Cadastrado em {{ formatDate(item.created_at) }}
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-3 border-t border-(--color-border)">
        <div class="flex items-center gap-2">
            <button @click="openEditModal(item)" class="...">
                <IconEdit :size="14" />
                Editar
            </button>
            <button @click="confirmDelete(item)" class="...">
                <IconTrash :size="16" />
            </button>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-(--color-text-muted)">
                {{ item.is_active ? 'Ativo' : 'Inativo' }}
            </span>
            <Toggle
                :model-value="item.is_active"
                :disabled="isCurrentItem(item)"
                @change="toggleStatus(item)"
            />
        </div>
    </div>
</div>
```

### 3.3 Modal de Criar/Editar

Localização: `resources/js/components/{Model}Modal.vue`

```vue
<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50" @click.self="handleClose">
                <div class="bg-(--color-surface) rounded-(--radius-card) shadow-xl max-w-lg w-full border border-(--color-border)">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-4 border-b border-(--color-border)">
                        <h3 class="text-lg font-semibold text-(--color-text)">
                            {{ isEditing ? 'Editar {Model}' : 'Novo {Model}' }}
                        </h3>
                        <button @click="handleClose" :disabled="loading" class="...">
                            <IconX :size="20" />
                        </button>
                    </div>

                    <!-- Body -->
                    <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
                        <Alert :message="errorMessage" type="error" />

                        <FormInput id="name" label="Nome" v-model="form.name" :error="errors.name" :disabled="loading" required />
                        <!-- Outros campos -->

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is-active" v-model="form.is_active" :disabled="loading" />
                            <label for="is-active" class="text-sm text-(--color-text)">Ativo</label>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 p-4 border-t border-(--color-border)">
                        <button @click="handleClose" :disabled="loading" class="...">Cancelar</button>
                        <FormButton @click="handleSubmit" :loading="loading">
                            {{ isEditing ? 'Salvar' : 'Cadastrar' }}
                        </FormButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
```

### 3.4 Componentes Reutilizáveis

| Componente | Uso |
|------------|-----|
| `AppLayout` | Layout principal com sidebar |
| `FormInput` | Campos de formulário |
| `FormButton` | Botões com loading |
| `Alert` | Mensagens de erro/sucesso |
| `Toggle` | Switch para ativar/inativar |
| `ConfirmModal` | Modal de confirmação para excluir |
| `IconPlus`, `IconEdit`, `IconTrash` | Ícones das ações |

---

## 4. Rotas do Vue Router

Arquivo: `resources/js/router/index.js`

```javascript
import {Model}s from '@/views/{section}/{Model}s.vue';

// Adicionar na lista de rotas
{
    path: '/{section}/{resources}',
    name: '{section}.{resources}',
    component: {Model}s,
    meta: { auth: true },
},
```

---

## 5. Rotas do Laravel (Web)

Arquivo: `routes/web.php`

> **IMPORTANTE:** Esta etapa é obrigatória para que a página funcione ao recarregar (F5) ou acessar diretamente pela URL. Sem essa rota, o Laravel retornará 404, pois o Vue Router só funciona do lado do cliente.

```php
// Exemplo para rota raiz
Route::get('/equipamentos', fn () => view('app'))->name('equipamentos');

// Exemplo para rota aninhada
Route::get('/configuracoes/usuarios', fn () => view('app'))->name('configuracoes.usuarios');
```

### Por que isso é necessário?

1. **Navegação interna (SPA)**: Quando o usuário clica em um link dentro do app, o Vue Router intercepta e renderiza a página sem recarregar — funciona normalmente.

2. **Reload ou acesso direto**: Quando o usuário recarrega a página (F5) ou acessa a URL diretamente, a requisição vai primeiro para o servidor Laravel. Se não houver uma rota correspondente, retorna 404.

3. **Solução**: Criar uma rota no Laravel que retorna a view `app` (que carrega o Vue), permitindo que o Vue Router assuma o controle da navegação.

---

## 6. Menu

Arquivo: `resources/js/config/menu.js`

```javascript
{
    id: '{resources}',
    label: '{Label}',
    icon: Icon{Name},
    routeName: '{section}.{resources}',
    status: 'active', // ou 'soon' se não implementado
},
```

---

## 7. Padrões de UI

### 7.1 Grid de Cards

```css
/* Desktop: 3 colunas, Tablet: 2 colunas, Mobile: 1 coluna */
grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4
```

### 7.2 Paginação

- 9 itens por página
- Mostrar "Anterior" e "Próximo"
- Exibir "X / Y" para página atual

### 7.3 Busca

- Debounce de 300ms
- Buscar em múltiplos campos (name, email, etc.)
- Resetar para página 1 ao buscar

### 7.4 Filtros

- Dropdown para status (Todos, Ativos, Inativos)
- Recarregar ao mudar filtro

### 7.5 Ações

| Ação | Componente | Localização |
|------|------------|-------------|
| Criar | Modal | Botão no header |
| Editar | Modal | Botão no card |
| Excluir | ConfirmModal | Botão no card |
| Ativar/Inativar | Toggle | Card (direita) |

### 7.6 Estados

- **Loading**: Spinner centralizado
- **Empty**: Ícone + mensagem + botão de ação
- **Error**: Alert com mensagem

---

## 8. Validações

### 8.1 Backend

- Campos obrigatórios: `required`
- E-mail único: `unique:{table},email` (ignorar próprio registro no update)
- Tamanho mínimo/máximo: `min:3`, `max:255`
- Mensagens em português

### 8.2 Frontend

- Validação em tempo real (opcional)
- Exibir erros do backend nos campos
- Desabilitar botão durante loading

---

## 9. Proteções

### 9.1 Multi-tenant

- Sempre filtrar por `company_id` do usuário logado
- Verificar acesso antes de show/update/delete

### 9.2 Auto-ação

- Não permitir excluir/inativar o próprio usuário
- Desabilitar toggle e botão de excluir
- Exibir tooltip explicativo

---

## 10. Checklist de Implementação

- [ ] **Backend**
  - [ ] Model com fillable e casts
  - [ ] Migration com campos necessários
  - [ ] Controller com CRUD completo
  - [ ] StoreRequest com validações
  - [ ] UpdateRequest com validações
  - [ ] Service com lógica de negócio
  - [ ] Rotas na API

- [ ] **Frontend**
  - [ ] View com listagem em cards
  - [ ] Modal de criar/editar
  - [ ] Integração com ConfirmModal
  - [ ] Toggle para status
  - [ ] Busca com debounce
  - [ ] Filtro de status
  - [ ] Paginação
  - [ ] Estados (loading, empty, error)

- [ ] **Rotas**
  - [ ] Vue Router (navegação interna)
  - [ ] Laravel web.php (obrigatório para reload funcionar!)

- [ ] **Menu**
  - [ ] Adicionar item no menu.js
  - [ ] Definir status (active/soon)

---

## 11. Exemplos de Referência

**CRUD simples (sem arquivo):**
- Controller: `app/Http/Controllers/Admin/UserController.php`
- Requests: `app/Http/Requests/Admin/Users/`
- Service: `app/Services/UserService.php`
- View: `resources/js/views/admin/Users.vue`
- Modal: `resources/js/components/UserModal.vue`

**CRUD com upload de arquivo + máquina de estados:**
- Controller: `app/Http/Controllers/Admin/EventController.php`
- Requests: `app/Http/Requests/Admin/Events/`
- Service: `app/Services/EventService.php`
- View: `resources/js/views/admin/Events.vue`
- Modal: `resources/js/components/EventModal.vue`
- Testes: `tests/Feature/Admin/Events/EventCrudTest.php`

---

## 12. Upload de arquivos para Cloudflare R2

Padrão usado no CRUD de Eventos para imagens.

### 12.1 Service

```php
public function uploadImage(UploadedFile $file, string $path): string
{
    Storage::disk('r2')->putFileAs('', $file, $path, 'public');
    return Storage::disk('r2')->url($path);
}

public function deleteImage(?string $url): void
{
    if (! $url) return;
    try {
        $baseUrl = rtrim(Storage::disk('r2')->url(''), '/');
    } catch (\RuntimeException) {
        return;
    }
    if ($baseUrl && str_starts_with($url, $baseUrl)) {
        Storage::disk('r2')->delete(ltrim(substr($url, strlen($baseUrl)), '/'));
    }
}
```

> **Por que `url('')` em vez de `config('filesystems.disks.r2.url')`?** A config pode estar vazia (ex: sem CDN configurado localmente). `url('')` retorna o base URL real do disco, funcionando tanto em produção quanto em `Storage::fake('r2')` nos testes.

### 12.2 Controller — method spoofing para PUT com FormData

O navegador não suporta `PUT` nativo em formulários/`FormData`. Solução: rota `POST` paralela + `_method: PUT` no body:

```php
// routes/web.php
Route::put('/{event}', [EventController::class, 'update'])->name('update');
Route::post('/{event}', [EventController::class, 'update'])->name('update.post');
```

```js
// Vue — no submit do modal
const fd = new FormData()
fd.append('_method', 'PUT')
// ... demais campos
await axios.post(`/admin/api/events/${id}`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
})
```

### 12.3 Testes com Storage::fake

```php
it('salva imagem no R2', function () {
    Storage::fake('r2');
    $file = UploadedFile::fake()->image('capa.jpg', 1280, 720)->size(500);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->post('/admin/api/events', [
            'name'        => 'Evento',
            'starts_at'   => '2026-06-15 09:00:00',
            'is_online'   => false,
            'cover_image' => $file,
        ])
        ->assertCreated();

    Storage::disk('r2')->assertExists("events/{$response->json('id')}/cover.jpg");
});
```

> **Atenção:** testes de validação de arquivo com `->post()` (não `->postJson()`) precisam de `->withHeaders(['Accept' => 'application/json'])` para receber 422 em vez de redirect 302 quando a validação falha.

---

## 13. Máquina de estados (status enum)

Padrão introduzido no módulo de Eventos.

```php
// Service
private const TRANSITIONS = [
    'rascunho'  => ['publicado', 'cancelado'],
    'publicado' => ['encerrado', 'cancelado'],
    'encerrado' => [],
    'cancelado' => [],
];
private const ADMIN_ONLY_TRANSITIONS = ['publicado', 'cancelado'];

public function updateStatus(Event $event, string $newStatus, User $actor): Event
{
    $allowed = self::TRANSITIONS[$event->status];

    if (! in_array($newStatus, $allowed)) {
        throw new InvalidArgumentException("Transição inválida.");
    }
    if (in_array($newStatus, self::ADMIN_ONLY_TRANSITIONS) && ! $actor->isAdmin()) {
        throw new AccessDeniedHttpException('Apenas administradores podem realizar esta ação.');
    }

    $event->update(['status' => $newStatus]);
    return $event->fresh();
}
```

```php
// Controller — captura exceções e mapeia para HTTP
try {
    $updated = $this->eventService->updateStatus($event, $request->status, Auth::user());
} catch (AccessDeniedHttpException $e) {
    return response()->json(['message' => $e->getMessage()], 403);
} catch (InvalidArgumentException $e) {
    return response()->json(['message' => $e->getMessage()], 422);
}
```
