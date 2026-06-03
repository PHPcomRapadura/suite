# Skill — Testes

Guia de escrita de testes no projeto com Pest e Laravel Testing.

---

## Stack

- **Pest v4** com plugin Laravel (`pestphp/pest-plugin-laravel`)
- **Laravel HTTP Testing** (`$this->getJson`, `postJson`, etc.)
- **SQLite in-memory** para testes (rápido e isolado)
- **Factories** para geração de dados
- **CaptainHook** roda os testes automaticamente no `pre-commit`

---

## Tipos de teste usados

| Tipo | Localização | O que testa |
|------|-------------|-------------|
| Feature | `tests/Feature/` | Endpoints HTTP de ponta a ponta |
| Unit | `tests/Unit/` | Services e lógica de negócio isolada |

> **Regra:** Não mockar o banco de dados. Testes de feature devem bater em SQLite real para garantir que queries e migrations funcionam.

---

## Configuração do banco de testes

`phpunit.xml` — já configurado com SQLite in-memory:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Usar `RefreshDatabase` em todos os testes que escrevem no banco:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class {Model}Test extends TestCase
{
    use RefreshDatabase;
}
```

---

## Estrutura de um Feature Test (CRUD)

```php
<?php

namespace Tests\Feature;

use App\Models\{Model};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Model}Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // --- Listagem ---

    public function test_lista_items_paginados(): void
    {
        {Model}::factory()->count(12)->create();

        $response = $this->actingAs($this->user)
                         ->getJson('/api/{resources}?per_page=9');

        $response->assertOk()
                 ->assertJsonCount(9, 'data')
                 ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
    }

    public function test_busca_por_nome(): void
    {
        {Model}::factory()->create(['name' => 'Alvo']);
        {Model}::factory()->create(['name' => 'Outro']);

        $this->actingAs($this->user)
             ->getJson('/api/{resources}?search=Alvo')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.name', 'Alvo');
    }

    // --- Criação ---

    public function test_cria_item_com_dados_validos(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/{resources}', ['name' => 'Novo Item'])
             ->assertCreated()
             ->assertJsonPath('data.name', 'Novo Item');

        $this->assertDatabaseHas('{table}', ['name' => 'Novo Item']);
    }

    public function test_retorna_erro_de_validacao_sem_nome(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/{resources}', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['name']);
    }

    // --- Atualização ---

    public function test_atualiza_item_existente(): void
    {
        $item = {Model}::factory()->create(['name' => 'Antigo']);

        $this->actingAs($this->user)
             ->putJson("/api/{resources}/{$item->id}", ['name' => 'Novo'])
             ->assertOk()
             ->assertJsonPath('data.name', 'Novo');
    }

    // --- Exclusão ---

    public function test_exclui_item(): void
    {
        $item = {Model}::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/{resources}/{$item->id}")
             ->assertOk();

        $this->assertDatabaseMissing('{table}', ['id' => $item->id]);
    }

    // --- Toggle status ---

    public function test_alterna_status(): void
    {
        $item = {Model}::factory()->create(['is_active' => true]);

        $this->actingAs($this->user)
             ->patchJson("/api/{resources}/{$item->id}/toggle-status")
             ->assertOk()
             ->assertJsonPath('data.is_active', false);
    }

    // --- Autorização ---

    public function test_requer_autenticacao(): void
    {
        $this->getJson('/api/{resources}')->assertUnauthorized();
    }
}
```

---

## Estrutura de um Unit Test (Service)

```php
<?php

namespace Tests\Unit;

use App\Models\{Model};
use App\Services\{Model}Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {Model}ServiceTest extends TestCase
{
    use RefreshDatabase;

    private {Model}Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new {Model}Service();
    }

    public function test_cria_item(): void
    {
        $item = $this->service->create(['name' => 'Teste', 'is_active' => true]);

        $this->assertInstanceOf({Model}::class, $item);
        $this->assertEquals('Teste', $item->name);
        $this->assertTrue($item->is_active);
    }

    public function test_toggle_status_inverte_valor(): void
    {
        $item = {Model}::factory()->create(['is_active' => true]);

        $result = $this->service->toggleStatus($item);

        $this->assertFalse($result->is_active);
    }
}
```

---

## Factory padrão

```php
// database/factories/{Model}Factory.php
public function definition(): array
{
    return [
        'name'      => fake('pt_BR')->words(2, true),
        'is_active' => true,
    ];
}

public function inactive(): static
{
    return $this->state(['is_active' => false]);
}
```

---

## Executar testes

```bash
# Todos os testes
docker compose exec app ./vendor/bin/pest

# Com paralelismo (mais rápido)
docker compose exec app ./vendor/bin/pest --parallel

# Suite específica
docker compose exec app ./vendor/bin/pest tests/Feature/Admin/

# Apenas um arquivo
docker compose exec app ./vendor/bin/pest tests/Feature/Admin/Auth/LoginTest.php

# Com cobertura
docker compose exec app ./vendor/bin/pest --coverage
```

---

## Pest — Sintaxe preferida

Este projeto usa **Pest** com sintaxe funcional. Não usar classes PHPUnit diretamente:

```php
// ✅ Pest
it('cria item com dados válidos', function () {
    $response = $this->postJson('/api/items', ['name' => 'Teste']);
    $response->assertCreated();
    $response->assertJsonPath('data.name', 'Teste');
});

// ⚠️ Atenção: não encadear assertions após assertUnprocessable() / assertJson*
// em algumas versões do Pest v4. Usar variável intermediária:
it('retorna erro de validação', function () {
    $response = $this->postJson('/api/items', []);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['name']);
});
```

`tests/Pest.php` — configuração global:
- `RefreshDatabase` habilitado para todos os Feature tests
- `TestCase` do Laravel como base

---

## CaptainHook — hooks automáticos

Os testes rodam automaticamente no `pre-commit` via CaptainHook.
Para pular o hook em situações de emergência (não recomendado):
```bash
git commit --no-verify -m "mensagem"
```

---

## Testes E2E com Playwright

Testes de ponta a ponta ficam em `tests/e2e/` e rodam contra o servidor real (`http://localhost:8000`).

### Requisitos

```bash
# Instalar o pacote (já no package.json como devDependency)
npm install

# Instalar o browser Chromium (necessário apenas na primeira vez)
npx playwright install chromium
```

### Executar

```bash
# Todos os testes e2e
npx playwright test tests/e2e/

# Arquivo específico com output detalhado
npx playwright test tests/e2e/home.spec.js --reporter=list

# Com screenshots em caso de falha
npx playwright test tests/e2e/ --reporter=list
```

> **Atenção:** os testes e2e requerem `docker compose up -d` antes de rodar.
> Se o storage estiver sem os diretórios necessários, a app retorna 500.
> Execute `docker compose exec app bash -c "mkdir -p storage/framework/{cache/data,sessions,views} && chmod -R 775 storage && chown -R www-data:www-data storage"` para corrigir.

### Estrutura de um teste e2e

```js
import { test, expect } from '@playwright/test';

test('GET / retorna página institucional', async ({ page }) => {
  const response = await page.goto('http://localhost:8000/');

  await expect(page).toHaveTitle(/PHP com Rapadura/i);
  expect(response.status()).toBe(200);
});
```

### Seletores e boas práticas

- Preferir `getByRole` com `name` para evitar ambiguidade quando há múltiplos elementos do mesmo tipo
- Ex: a página tem 3 `<nav>` — usar `page.getByRole('navigation', { name: 'Navegação principal' })` em vez de `page.locator('nav')`

---

## Boas práticas

- Nome do teste em **português**, descrevendo o comportamento: `'cria item com dados válidos'`
- Um comportamento por teste — não verificar tudo de uma vez
- Usar `assertDatabaseHas` / `assertDatabaseMissing` para verificar persistência
- Nunca depender da ordem de execução — cada teste deve ser independente
- Factories com `fake('pt_BR')` para dados realistas em português
- Testar o caminho feliz **e** os casos de erro (validação, not found, unauthorized, forbidden)
- Para testes de autenticação: usar `$this->actingAs($user)` e verificar `assertGuest()`
