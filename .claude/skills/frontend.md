# Skill — Frontend

Guia de implementação do frontend nos módulos Vue.js SPA (CFP e Gestão de Eventos).

---

## Stack

- **Vue.js 3** com Composition API (`<script setup>`)
- **Tailwind CSS v4** com tokens do design system
- **Vite** para build
- **Vue Router** para navegação SPA

---

## ⚠️ Tailwind v4 — CSS Variables

Usar **sempre parênteses** para CSS custom properties:

```html
<!-- ❌ Errado — gera CSS inválido -->
<div class="bg-[--color-bg] text-[--color-text]">

<!-- ✅ Correto -->
<div class="bg-(--color-bg) text-(--color-text)">
```

---

## Estrutura de arquivos

```
resources/js/
├── views/{section}/      # Pages — uma por rota
│   └── {Model}s.vue
├── components/           # Componentes reutilizáveis
│   ├── {Model}Modal.vue  # Modal criar/editar
│   └── ui/               # Componentes base
├── router/index.js       # Rotas Vue Router
├── config/menu.js        # Menu lateral
└── app.js                # Bootstrap da SPA
```

---

## Padrão de página (listagem com cards)

```vue
<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import {Model}Modal from '@/components/{Model}Modal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

const items      = ref([])
const meta       = ref({})
const loading    = ref(false)
const search     = ref('')
const statusFilter = ref('all')
const showModal  = ref(false)
const selectedItem = ref(null)
const showDeleteModal = ref(false)
const itemToDelete = ref(null)

let debounceTimer = null

async function loadItems(page = 1) {
    loading.value = true
    try {
        const { data } = await axios.get('/api/{resources}', {
            params: { page, search: search.value, status: statusFilter.value, per_page: 9 }
        })
        items.value = data.data
        meta.value  = data.meta
    } finally {
        loading.value = false
    }
}

function debouncedSearch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => loadItems(1), 300)
}

function openCreateModal() { selectedItem.value = null; showModal.value = true }
function openEditModal(item) { selectedItem.value = item; showModal.value = true }
function closeModal() { showModal.value = false; selectedItem.value = null }
function onSaved() { closeModal(); loadItems() }

function confirmDelete(item) { itemToDelete.value = item; showDeleteModal.value = true }
function closeDeleteModal() { showDeleteModal.value = false; itemToDelete.value = null }
async function handleDelete() {
    await axios.delete(`/api/{resources}/${itemToDelete.value.id}`)
    closeDeleteModal()
    loadItems()
}

async function toggleStatus(item) {
    await axios.patch(`/api/{resources}/${item.id}/toggle-status`)
    loadItems()
}

onMounted(() => loadItems())
</script>

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
                    <button @click="openCreateModal" class="btn-primary">
                        Novo {Item}
                    </button>
                </div>

                <!-- Filtros -->
                <div class="bg-(--color-surface) rounded-xl border border-(--color-border) p-4 mb-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <input v-model="search" type="text" placeholder="Buscar..."
                               @input="debouncedSearch" class="input flex-1" />
                        <select v-model="statusFilter" @change="loadItems()" class="input w-auto">
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
                <div v-else-if="items.length === 0" class="text-center py-12 text-(--color-text-muted)">
                    Nenhum item encontrado.
                </div>

                <!-- Grid de cards -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="item in items" :key="item.id" class="card">
                        <!-- conteúdo do card -->
                    </div>
                </div>

                <!-- Paginação -->
                <div v-if="meta.last_page > 1" class="flex justify-center gap-2 mt-6">
                    <button :disabled="meta.current_page === 1"
                            @click="loadItems(meta.current_page - 1)" class="btn-secondary">
                        Anterior
                    </button>
                    <span class="text-(--color-text-muted) self-center">
                        {{ meta.current_page }} / {{ meta.last_page }}
                    </span>
                    <button :disabled="meta.current_page === meta.last_page"
                            @click="loadItems(meta.current_page + 1)" class="btn-secondary">
                        Próximo
                    </button>
                </div>
            </div>
        </div>

        <{Model}Modal :show="showModal" :item="selectedItem"
                      @close="closeModal" @saved="onSaved" />

        <ConfirmModal :show="showDeleteModal" title="Excluir {item}?"
                      message="Esta ação não pode ser desfeita."
                      @confirm="handleDelete" @cancel="closeDeleteModal" />
    </AppLayout>
</template>
```

---

## Padrão de Modal (criar/editar)

```vue
<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ show: Boolean, item: Object })
const emit  = defineEmits(['close', 'saved'])

const loading = ref(false)
const errors  = ref({})
const form    = ref(defaultForm())

function defaultForm() {
    return { name: '', is_active: true }
}

watch(() => props.item, (val) => {
    form.value = val ? { ...val } : defaultForm()
    errors.value = {}
})

function handleClose() {
    if (loading.value) return
    emit('close')
}

async function handleSubmit() {
    loading.value = true
    errors.value = {}
    try {
        if (props.item) {
            await axios.put(`/api/{resources}/${props.item.id}`, form.value)
        } else {
            await axios.post('/api/{resources}', form.value)
        }
        emit('saved')
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors
        }
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50"
                 @click.self="handleClose">
                <div class="bg-(--color-surface) rounded-xl shadow-xl max-w-lg w-full border border-(--color-border)">
                    <div class="flex items-center justify-between p-4 border-b border-(--color-border)">
                        <h3 class="text-lg font-semibold text-(--color-text)">
                            {{ item ? 'Editar {Model}' : 'Novo {Model}' }}
                        </h3>
                        <button @click="handleClose" :disabled="loading">✕</button>
                    </div>
                    <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
                        <FormInput id="name" label="Nome" v-model="form.name"
                                   :error="errors.name?.[0]" :disabled="loading" required />
                    </form>
                    <div class="flex justify-end gap-3 p-4 border-t border-(--color-border)">
                        <button @click="handleClose" :disabled="loading" class="btn-secondary">Cancelar</button>
                        <button @click="handleSubmit" :disabled="loading" class="btn-primary">
                            {{ item ? 'Salvar' : 'Cadastrar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
```

---

## Rota Vue Router

```js
// resources/js/router/index.js
{
    path: '/{section}/{resources}',
    name: '{section}.{resources}',
    component: () => import('@/views/{section}/{Model}s.vue'),
    meta: { auth: true },
}
```

## Rota Laravel (obrigatório para F5 funcionar)

```php
// routes/web.php
Route::get('/{section}/{resources}', fn () => view('app'))->name('{section}.{resources}');
```

## Menu

```js
// resources/js/config/menu.js
{
    id: '{resources}',
    label: '{Label}',
    icon: Icon{Name},
    routeName: '{section}.{resources}',
    status: 'active', // ou 'soon'
}
```

---

## Componentes disponíveis

| Componente | Uso |
|------------|-----|
| `AppLayout` | Layout com sidebar e header |
| `FormInput` | Input com label e mensagem de erro |
| `FormButton` | Botão com spinner de loading |
| `Alert` | Mensagem de erro/sucesso |
| `Toggle` | Switch ativar/inativar |
| `ConfirmModal` | Modal de confirmação destrutiva |

---

## Boas práticas

- Sempre `loading = true` antes de requests e `false` no `finally`
- Exibir erros de validação do backend nos campos (`errors.name?.[0]`)
- Debounce de 300ms na busca
- Resetar para página 1 ao aplicar filtro ou busca
- Não usar `async/await` sem `try/catch` em handlers de botão
