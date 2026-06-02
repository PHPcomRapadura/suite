# Skill — Segurança

Guia de práticas de segurança para o desenvolvimento da suite PHP com Rapadura.

---

## Autenticação e Autorização

### Laravel Sanctum (API)

```php
// routes/api.php — todas as rotas protegidas dentro do middleware
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('events', EventController::class);
});
```

### Verificar permissões

```php
// Policy para autorização granular
public function update(User $user, Event $event): bool
{
    return $user->hasRole('admin') || $user->id === $event->created_by;
}

// No Controller
$this->authorize('update', $event);
```

### Roles e permissões

Usar hierarquia simples:
- `admin` — acesso total ao painel
- `organizer` — acesso ao próprio evento
- `speaker` — acesso apenas ao CFP

---

## Proteção contra injeção

### SQL Injection

Sempre usar Eloquent ou query builder com bindings — **nunca** interpolação de string em queries:

```php
// ❌ Vulnerável
DB::select("SELECT * FROM users WHERE email = '$email'");

// ✅ Seguro
User::where('email', $email)->first();
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

### Mass Assignment

Sempre definir `$fillable` explícito nos Models — nunca usar `$guarded = []`:

```php
// ❌ Perigoso
protected $guarded = [];

// ✅ Seguro
protected $fillable = ['name', 'email', 'is_active'];
```

---

## Proteção contra XSS

### Blade (site/admin)

O Blade escapa automaticamente com `{{ }}`. Só usar `{!! !!}` quando o dado for confiável e necessariamente HTML:

```blade
{{ $user->name }}      {{-- escapado — seguro --}}
{!! $user->bio !!}     {{-- NÃO escapado — só se sanitizado antes --}}
```

### JSON em Blade

Sempre usar `json_encode` com flags de segurança:

```blade
<script>
const data = {!! json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
```

---

## Upload de arquivos

```php
public function store(Request $request): JsonResponse
{
    $request->validate([
        'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
    ]);

    // Gerar nome aleatório — nunca usar o nome original do usuário
    $path = $request->file('file')->store('uploads', 'local');

    return response()->json(['path' => $path], 201);
}
```

**Nunca:**
- Servir uploads diretamente da pasta `public/`
- Aceitar extensões executáveis (`.php`, `.sh`, `.exe`)
- Usar o nome original do arquivo sem sanitização

---

## Variáveis de ambiente

```php
// ✅ Ler do .env via helper
$secret = config('services.stripe.secret');

// ❌ Nunca hardcodar credenciais no código
$secret = 'sk_live_abc123...';
```

Variáveis sensíveis nunca vão em:
- Código-fonte
- `.env.example` (deixar vazio: `DB_PASSWORD=`)
- Logs da aplicação
- Respostas de API

---

## CORS

Configurar em `config/cors.php` para permitir apenas origens conhecidas em produção:

```php
'allowed_origins' => [
    env('APP_URL', 'http://localhost:8000'),
],
```

---

## Rate Limiting

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // rotas com limite de 60 req/min por usuário
});

// Para login (mais restritivo)
Route::post('/login', LoginController::class)
     ->middleware('throttle:5,1'); // 5 tentativas por minuto
```

---

## Senhas

```php
// Hashear sempre com bcrypt (padrão do Laravel)
'password' => Hash::make($request->password),

// Verificar
Hash::check($request->password, $user->password);

// Nunca comparar em texto plano
if ($request->password === $user->password) // ❌
```

BCRYPT_ROUNDS mínimo: `12` (já configurado no `.env.example`).

---

## Headers de segurança

Adicionar middleware de segurança para produção em `app/Http/Middleware/`:

```php
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
```

---

## Checklist antes de subir para produção

- [ ] `APP_DEBUG=false` no `.env` de produção
- [ ] `APP_ENV=production`
- [ ] Chaves e senhas fora do código-fonte
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] Permissões corretas: `storage/` e `bootstrap/cache/` com `755`
- [ ] Certificado HTTPS ativo
- [ ] Rate limiting nas rotas de autenticação
- [ ] Logs não expõem dados sensíveis de usuários
- [ ] `$fillable` definido em todos os Models
- [ ] Todas as rotas da API dentro do middleware `auth:sanctum`
