# Skill — Backend

Guia de implementação de APIs e lógica de negócio em Laravel 13.

---

## Stack

- **Laravel 13** + **PHP 8.4**
- **MySQL 8.4** via Docker (host: `mysql`)
- **Redis** para cache, sessões e filas (host: `redis`)
- **Eloquent ORM** para persistência

---

## Arquitetura

```
app/
├── Http/
│   ├── Controllers/{Module}Controller.php
│   └── Requests/{Module}/
│       ├── Store{Model}Request.php
│       └── Update{Model}Request.php
├── Models/{Model}.php
└── Services/{Model}Service.php
```

---

## Controller padrão

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

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 9);
        $search  = $request->string('search');
        $status  = $request->string('status', 'all');

        $query = {Model}::query();

        if ($search->isNotEmpty()) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
            );
        }

        if ($status->exactly('active'))   $query->where('is_active', true);
        if ($status->exactly('inactive')) $query->where('is_active', false);

        $items = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
            ],
        ]);
    }

    public function store(Store{Model}Request $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return response()->json([
            'message' => '{Model} cadastrado com sucesso.',
            'data'    => $item,
        ], Response::HTTP_CREATED);
    }

    public function show({Model} $model): JsonResponse
    {
        return response()->json(['data' => $model]);
    }

    public function update(Update{Model}Request $request, {Model} $model): JsonResponse
    {
        $item = $this->service->update($model, $request->validated());

        return response()->json([
            'message' => '{Model} atualizado com sucesso.',
            'data'    => $item,
        ]);
    }

    public function destroy({Model} $model): JsonResponse
    {
        $this->service->delete($model);

        return response()->json(['message' => '{Model} excluído com sucesso.']);
    }

    public function toggleStatus({Model} $model): JsonResponse
    {
        $item   = $this->service->toggleStatus($model);
        $status = $item->is_active ? 'ativado' : 'inativado';

        return response()->json([
            'message' => "{Model} {$status} com sucesso.",
            'data'    => $item,
        ]);
    }
}
```

---

## Form Requests

```php
// Store{Model}Request.php
public function rules(): array
{
    return [
        'name'      => ['required', 'string', 'min:3', 'max:255'],
        'is_active' => ['boolean'],
    ];
}

public function messages(): array
{
    return [
        'name.required' => 'O nome é obrigatório.',
        'name.min'      => 'O nome deve ter no mínimo :min caracteres.',
    ];
}

public function attributes(): array
{
    return ['name' => 'nome'];
}
```

```php
// Update{Model}Request.php
public function rules(): array
{
    $id = $this->route('{model}')->id;

    return [
        'name' => ['required', 'string', 'min:3', 'max:255',
                   Rule::unique('{table}', 'name')->ignore($id)],
        'is_active' => ['boolean'],
    ];
}
```

---

## Service

```php
<?php

namespace App\Services;

use App\Models\{Model};

class {Model}Service
{
    public function create(array $data): {Model}
    {
        return {Model}::create($data);
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
        $model->update(['is_active' => !$model->is_active]);
        return $model->fresh();
    }
}
```

---

## Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {Model} extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

---

## Rotas

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('{resources}', {Module}Controller::class);
    Route::patch('{resources}/{model}/toggle-status', [{Module}Controller::class, 'toggleStatus']);
});
```

---

## Respostas JSON

| Situação | Status | Estrutura |
|----------|--------|-----------|
| Listagem | 200 | `{ data: [...], meta: { current_page, last_page, per_page, total } }` |
| Item único | 200 | `{ data: {...} }` |
| Criação | 201 | `{ message: '...', data: {...} }` |
| Atualização | 200 | `{ message: '...', data: {...} }` |
| Exclusão | 200 | `{ message: '...' }` |
| Validação | 422 | `{ message: '...', errors: { campo: ['msg'] } }` |
| Não encontrado | 404 | `{ message: 'Not Found' }` |
| Sem permissão | 403 | `{ message: 'Forbidden' }` |

---

## Boas práticas

- Lógica de negócio **sempre no Service**, nunca no Controller
- Controller apenas: validar → delegar ao Service → retornar resposta
- Validações com mensagens em **português**
- Usar `$model->fresh()` após update para retornar dados atualizados
- Usar `Rule::unique()->ignore()` no UpdateRequest para evitar falso positivo de unicidade
- Nunca retornar senha ou dados sensíveis — usar `$hidden` no Model
- Usar `response()->json()` em vez de `return $data` para controle explícito do status HTTP
