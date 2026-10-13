<?php

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// A página /blog lista os posts do WordPress legado pela REST API. Os testes
// simulam a API com Http::fake — nenhuma requisição real sai daqui.

const BLOG_API = 'https://blog.test/wp-json/wp/v2/posts*';

/**
 * Um post no formato da REST API do WordPress (com _embed=author,wp:term).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function blogApiPost(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 100,
        'date' => '2025-02-24T15:53:02',
        'slug' => 'post-de-teste',
        'link' => 'https://blog.test/post-de-teste/',
        'title' => ['rendered' => 'Post de teste'],
        'excerpt' => ['rendered' => "<p>Resumo do post de teste [&hellip;]</p>\n"],
        'content' => ['rendered' => '<p>Conteúdo sem imagem.</p>'],
        'jetpack_featured_media_url' => '',
        '_embedded' => [
            'author' => [['name' => 'Alisson Silva', 'avatar_urls' => ['96' => 'https://secure.gravatar.com/avatar/abc?s=96']]],
            'wp:term' => [[['taxonomy' => 'category', 'slug' => 'eventos', 'name' => 'Eventos']]],
        ],
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $posts
 */
function blogApiResponse(array $posts, int $total = 1, int $totalPages = 1): PromiseInterface
{
    return Http::response($posts, 200, [
        'X-WP-Total' => (string) $total,
        'X-WP-TotalPages' => (string) $totalPages,
    ]);
}

/**
 * Query string de uma requisição GET capturada pelo Http::fake.
 *
 * @return array<string, mixed>
 */
function blogApiQuery(Request $request): array
{
    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

    return $query;
}

beforeEach(function () {
    config(['blog.url' => 'https://blog.test', 'blog.per_page' => 9]);
    Http::preventStrayRequests();
});

// ─── Listagem ────────────────────────────────────────────────────────────────

it('lista os posts do blog legado com título, resumo, autor, data e link', function () {
    Http::fake([BLOG_API => blogApiResponse([
        blogApiPost(),
        blogApiPost(['id' => 101, 'slug' => 'segundo', 'link' => 'https://blog.test/segundo/', 'title' => ['rendered' => 'Segundo post']]),
    ], total: 2)]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Post de teste');
    $response->assertSee('Segundo post');
    $response->assertSee('Resumo do post de teste…');
    $response->assertSee('Alisson Silva');
    $response->assertSee('datetime="2025-02-24"', false);
    $response->assertSee('href="https://blog.test/post-de-teste/"', false);
    $response->assertSee('Eventos'); // categoria no badge
});

it('consulta a API com _embed, _fields e paginação', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()])]);

    $this->get('/blog')->assertOk();

    Http::assertSent(function ($request) {
        $query = blogApiQuery($request);

        return str_starts_with($request->url(), 'https://blog.test/wp-json/wp/v2/posts?')
            && $query['page'] === '1'
            && $query['per_page'] === '9'
            && $query['_embed'] === 'author,wp:term'
            && str_contains($query['_fields'], 'jetpack_featured_media_url');
    });
});

it('mostra o estado vazio quando o blog não tem posts', function () {
    Http::fake([BLOG_API => blogApiResponse([], total: 0, totalPages: 0)]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Nenhum post por aqui ainda.');
});

// ─── Conteúdo não confiável vindo da API ─────────────────────────────────────

it('escapa HTML do título e remove tags do resumo', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost([
        'title' => ['rendered' => 'Título <script>alert(1)</script> &amp; cia'],
        'excerpt' => ['rendered' => '<p>Resumo com <strong>negrito</strong> e <a href="x">link</a></p><p>Data: 15h (Brasília)</p><p>Mentores: Kilderson<br>Will</p>'],
    ])])]);

    $response = $this->get('/blog');

    $response->assertOk();
    expect($response->getContent())->not->toContain('<script>alert(1)</script>');
    $response->assertSee('Título alert(1) &amp; cia', false);
    // Parágrafos e <br> viram espaço em vez de colar as frases
    $response->assertSee('Resumo com negrito e link Data: 15h (Brasília) Mentores: Kilderson Will');
    expect($response->getContent())->not->toContain('<strong>negrito</strong>');
});

it('refaz o resumo automático a partir do conteúdo para não colar as frases', function () {
    Http::fake([BLOG_API => blogApiResponse([
        // Resumo automático: o WordPress devolve o início do conteúdo sem espaço entre os blocos
        blogApiPost([
            'id' => 1,
            'excerpt' => ['rendered' => '<p>Data: 15h (Brasília)Mentores: Kilderson e Will [&hellip;]</p>'],
            'content' => ['rendered' => '<p>Data: 15h (Brasília)</p><p>Mentores: Kilderson e Will</p><ul><li>Tópicos: Git</li></ul>'],
        ]),
        // Resumo escrito à mão: não começa igual ao conteúdo e é mantido como está
        blogApiPost([
            'id' => 2,
            'excerpt' => ['rendered' => '<p>PHPeste é uma conferência de PHP do Nordeste.</p>'],
            'content' => ['rendered' => '<p>Maior conferência de PHP do Nordeste! Inscrições abertas.</p>'],
        ]),
        // Sem resumo: usa o começo do conteúdo
        blogApiPost([
            'id' => 3,
            'excerpt' => ['rendered' => ''],
            'content' => ['rendered' => '<p>Só conteúdo.</p>'],
        ]),
    ], total: 3)]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Data: 15h (Brasília) Mentores: Kilderson e Will Tópicos: Git');
    $response->assertSee('PHPeste é uma conferência de PHP do Nordeste.');
    $response->assertDontSee('Maior conferência de PHP do Nordeste!');
    $response->assertSee('Só conteúdo.');
});

it('não aponta links para fora do domínio do blog', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost(['link' => 'https://atacante.example/phishing'])])]);

    $response = $this->get('/blog');

    $response->assertOk();
    expect($response->getContent())->not->toContain('atacante.example');
    $response->assertSee('href="https://blog.test/"', false);
});

it('usa a imagem destacada, senão a primeira imagem do post, senão um placeholder', function () {
    Http::fake([BLOG_API => blogApiResponse([
        blogApiPost(['id' => 1, 'jetpack_featured_media_url' => 'https://i0.wp.com/blog.test/destacada.jpg']),
        blogApiPost(['id' => 2, 'content' => ['rendered' => '<p>x</p><img class="a" src="http://inseguro.test/a.png"><img src="https://blog.test/wp-content/uploads/inline.png">']]),
        blogApiPost(['id' => 3, 'jetpack_featured_media_url' => 'javascript:alert(1)']),
    ], total: 3)]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('src="https://i0.wp.com/blog.test/destacada.jpg"', false);
    $response->assertSee('src="https://blog.test/wp-content/uploads/inline.png"', false);
    expect($response->getContent())
        ->not->toContain('inseguro.test')
        ->not->toContain('javascript:alert');
});

// ─── Cache e resiliência ─────────────────────────────────────────────────────

it('cacheia a página de posts e não consulta a API de novo', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()])]);

    $this->get('/blog')->assertOk();
    $this->get('/blog')->assertOk();

    Http::assertSentCount(1);
});

it('funciona com um cache store que serializa o valor (file), não só com o array', function () {
    // O store "array" dos testes não serializa; file/redis (produção) sim — objetos
    // Carbon no cache quebravam o unserialize. Garante que só escalares vão para lá.
    config(['cache.default' => 'file']);
    Cache::store('file')->flush();
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()])]);

    $this->get('/blog')->assertOk()->assertSee('datetime="2025-02-24"', false);
    $this->get('/blog')->assertOk()->assertSee('datetime="2025-02-24"', false);

    Http::assertSentCount(1);
    Cache::store('file')->flush();
});

it('serve a última cópia conhecida quando a API falha depois de expirar o cache', function () {
    Http::fake([BLOG_API => Http::sequence()
        ->push([blogApiPost(['title' => ['rendered' => 'Post guardado']])], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1'])
        ->pushStatus(503)
        ->pushStatus(503)]);

    $this->get('/blog')->assertOk();

    // Passa do cache_ttl (1 h) mas não do stale_ttl (7 dias).
    $this->travel(2)->hours();

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Post guardado');
    $response->assertDontSee('Não conseguimos carregar os posts agora.');
});

it('mostra aviso e link para o blog quando a API falha e não há cache', function () {
    Http::fake([BLOG_API => Http::response('', 500)]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Não conseguimos carregar os posts agora.');
    $response->assertSee('href="https://blog.test/"', false);
});

// ─── Paginação ───────────────────────────────────────────────────────────────

it('pagina os posts e repassa a página para a API', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()], total: 39, totalPages: 5)]);

    $response = $this->get('/blog?page=2');

    $response->assertOk();
    $response->assertSee('Página 2 de 5');
    $response->assertSee('rel="prev" href="'.route('blog').'"', false);
    $response->assertSee('rel="next"', false);
    $response->assertSee('href="'.route('blog', ['page' => 3]).'"', false);
    $response->assertSee('<link rel="canonical" href="'.route('blog', ['page' => 2]).'">', false);
    Http::assertSent(fn ($request) => blogApiQuery($request)['page'] === '2');
});

it('retorna 404 para página inválida', function (string $page) {
    Http::fake();

    $this->get("/blog?page={$page}")->assertNotFound();

    Http::assertNothingSent();
})->with(['0', '-1', 'abc', '1.5']);

it('retorna 404 para página além da última', function () {
    Http::fake([BLOG_API => Http::response([
        'code' => 'rest_post_invalid_page_number',
        'message' => 'O número da página solicitada é maior que o número de páginas disponíveis.',
        'data' => ['status' => 400],
    ], 400)]);

    $this->get('/blog?page=99')->assertNotFound();
});

it('usa a URL canônica /blog na primeira página', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()])]);

    $response = $this->get('/blog');

    $response->assertSee('<link rel="canonical" href="'.route('blog').'">', false);
    $response->assertSee('<link rel="alternate" type="application/rss+xml" title="Blog PHP com Rapadura" href="https://blog.test/feed/">', false);
    $response->assertSee('"@type":"Blog"', false);
    $response->assertSee('"@type":"BlogPosting"', false);
});

// ─── Menu e sitemap ──────────────────────────────────────────────────────────

it('exibe o item Blog no menu da home e do blog', function () {
    Http::fake([BLOG_API => blogApiResponse([blogApiPost()])]);

    $home = $this->get('/');
    $home->assertOk();
    $home->assertSee('href="'.route('blog').'"', false);
    $home->assertSee('href="#eventos"', false); // âncoras da home continuam relativas

    $blog = $this->get('/blog');
    $blog->assertOk();
    $blog->assertSee('class="nav-link active" aria-current="page">Blog</a>', false);
    $blog->assertSee('class="mobile-nav-link active" aria-current="page">Blog</a>', false);
    $home->assertDontSee('aria-current="page"', false); // na home o ativo vem do scroll spy
    $blog->assertSee('href="'.url('/').'#eventos"', false); // âncoras apontam para a home
});

it('inclui /blog no sitemap', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<loc>'.url('/blog').'</loc>', false);
});
