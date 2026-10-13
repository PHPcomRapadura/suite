<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Lê os posts do blog legado (WordPress) pela REST API e entrega páginas
 * normalizadas e cacheadas para o site institucional.
 *
 * Cada página fica em cache por `blog.cache_ttl`. A última cópia boa fica em
 * um segundo cache (`blog.stale_ttl`) e é servida quando a API falha, para o
 * blog não sumir do site por instabilidade do WordPress.
 *
 * O que vai para o cache é só escalar (a data fica como string ISO 8601):
 * stores como file/redis serializam o valor e objetos Carbon quebram no
 * unserialize. A conversão para CarbonImmutable acontece em hydrate().
 *
 * @phpstan-type CachedPost array{
 *     id: int,
 *     title: string,
 *     excerpt: string,
 *     date: string,
 *     link: string,
 *     image: string|null,
 *     author: array{name: string, avatar: string|null},
 *     categories: list<string>
 * }
 * @phpstan-type CachedPage array{posts: list<CachedPost>, total: int, total_pages: int}
 * @phpstan-type BlogPost array{
 *     id: int,
 *     title: string,
 *     excerpt: string,
 *     date: CarbonImmutable,
 *     link: string,
 *     image: string|null,
 *     author: array{name: string, avatar: string|null},
 *     categories: list<string>
 * }
 * @phpstan-type BlogPage array{posts: list<BlogPost>, total: int, total_pages: int}
 */
class BlogService
{
    /**
     * Página de posts (mais recentes primeiro) ou null quando a API está
     * fora do ar e não há nenhuma cópia em cache.
     *
     * @return BlogPage|null
     */
    public function posts(int $page = 1): ?array
    {
        $perPage = $this->perPage();
        $key = "blog:posts:{$perPage}:{$page}";
        $staleKey = "{$key}:stale";

        /** @var CachedPage|null $cached */
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $this->hydrate($cached);
        }

        try {
            $result = $this->fetch($page, $perPage);
        } catch (Throwable $e) {
            Log::warning('Blog legado indisponível; usando a última cópia em cache, se houver.', [
                'page' => $page,
                'error' => $e->getMessage(),
            ]);

            /** @var CachedPage|null $stale */
            $stale = Cache::get($staleKey);

            return $stale === null ? null : $this->hydrate($stale);
        }

        Cache::put($key, $result, (int) config('blog.cache_ttl'));
        Cache::put($staleKey, $result, (int) config('blog.stale_ttl'));

        return $this->hydrate($result);
    }

    /**
     * @param  CachedPage  $page
     * @return BlogPage
     */
    private function hydrate(array $page): array
    {
        $page['posts'] = array_map(function (array $post): array {
            $post['date'] = CarbonImmutable::parse($post['date']);

            return $post;
        }, $page['posts']);

        return $page;
    }

    public function perPage(): int
    {
        return max(1, min(100, (int) config('blog.per_page')));
    }

    /** URL pública do blog, sem barra final. */
    public function baseUrl(): string
    {
        return rtrim((string) config('blog.url'), '/');
    }

    /** Feed RSS do blog (WordPress). */
    public function feedUrl(): string
    {
        return $this->baseUrl().'/feed/';
    }

    /**
     * @return CachedPage
     */
    private function fetch(int $page, int $perPage): array
    {
        $response = Http::timeout((int) config('blog.timeout'))
            ->retry(2, 250, throw: false)
            ->acceptJson()
            ->get($this->baseUrl().'/wp-json/wp/v2/posts', [
                'page' => $page,
                'per_page' => $perPage,
                '_embed' => 'author,wp:term',
                '_fields' => 'id,date,slug,link,title,excerpt,content,jetpack_featured_media_url,_links,_embedded',
            ]);

        // Página além da última: o WordPress responde 400 com esse código.
        if ($response->status() === 400 && $response->json('code') === 'rest_post_invalid_page_number') {
            return ['posts' => [], 'total' => 0, 'total_pages' => 0];
        }

        if ($response->failed()) {
            throw new RuntimeException("HTTP {$response->status()} ao consultar a API do blog");
        }

        $items = $response->json();
        if (! is_array($items)) {
            throw new RuntimeException('Resposta inválida da API do blog');
        }

        $posts = [];
        foreach ($items as $item) {
            if (is_array($item) && isset($item['id'])) {
                $posts[] = $this->normalize($item);
            }
        }

        return [
            'posts' => $posts,
            'total' => $this->headerInt($response, 'X-WP-Total', count($posts)),
            'total_pages' => $this->headerInt($response, 'X-WP-TotalPages', $posts === [] ? 0 : 1),
        ];
    }

    private function headerInt(Response $response, string $header, int $default): int
    {
        $value = $response->header($header);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Converte um post da API em um array só com o que o site exibe. Tudo é
     * tratado como conteúdo não confiável: HTML vira texto, URLs são validadas
     * e links só apontam para o domínio do blog.
     *
     * @param  array<string, mixed>  $post
     * @return CachedPost
     */
    private function normalize(array $post): array
    {
        $base = $this->baseUrl();

        $link = is_string($post['link'] ?? null) ? $post['link'] : '';
        if (! Str::startsWith($link, $base.'/')) {
            $link = $base.'/';
        }

        $author = $post['_embedded']['author'][0] ?? [];
        $author = is_array($author) ? $author : [];

        $terms = $post['_embedded']['wp:term'] ?? [];
        $categories = [];
        foreach (is_array($terms) ? $terms : [] as $taxonomy) {
            foreach (is_array($taxonomy) ? $taxonomy : [] as $term) {
                if (! is_array($term) || ($term['taxonomy'] ?? null) !== 'category') {
                    continue;
                }
                if (($term['slug'] ?? '') === 'sem-categoria') {
                    continue;
                }
                $name = $this->text($term['name'] ?? '');
                if ($name !== '') {
                    $categories[] = $name;
                }
            }
        }

        $date = is_string($post['date'] ?? null) ? $post['date'] : null;
        try {
            $date = CarbonImmutable::parse($date ?? 'now')->format('Y-m-d\TH:i:s');
        } catch (Throwable) {
            $date = CarbonImmutable::now()->format('Y-m-d\TH:i:s');
        }

        return [
            'id' => (int) $post['id'],
            'title' => $this->text($post['title']['rendered'] ?? ''),
            'excerpt' => $this->excerpt($post['excerpt']['rendered'] ?? '', $post['content']['rendered'] ?? ''),
            'date' => $date,
            'link' => $link,
            'image' => $this->image($post),
            'author' => [
                'name' => $this->text($author['name'] ?? ''),
                'avatar' => $this->httpsUrl($author['avatar_urls']['96'] ?? null),
            ],
            'categories' => $categories,
        ];
    }

    /** HTML da API → texto puro, com entidades decodificadas e espaços normalizados. */
    private function text(mixed $html): string
    {
        if (! is_string($html)) {
            return '';
        }

        // Fim de bloco vira espaço; senão "…Brasília)</p><p>Mentores:…" colaria as frases.
        $html = (string) preg_replace('#<(?:/?(?:p|div|li|ul|ol|h[1-6]|tr|td|th|blockquote|section|article|figcaption)|br\s*/?)\b[^>]*>#i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * O resumo automático do WordPress é o início do conteúdo com as tags
     * removidas SEM espaço entre blocos ("…Brasília)Mentores: …"). Quando o
     * resumo da API é esse (começa igual ao conteúdo), refaz a partir do
     * conteúdo, que ainda tem a estrutura; um resumo escrito à mão é mantido.
     */
    private function excerpt(mixed $excerptHtml, mixed $contentHtml): string
    {
        $excerpt = $this->text($excerptHtml);
        $trimmedByWordPress = (bool) preg_match('/\s*\[(…|\.{3})\]\s*$/u', $excerpt);
        $excerpt = trim((string) preg_replace('/\s*\[(…|\.{3})\]\s*$/u', '', $excerpt));

        $content = $this->text($contentHtml);
        $isAutomatic = $excerpt === ''
            || ($content !== '' && Str::startsWith($this->comparable($content), $this->comparable($excerpt)));

        if ($isAutomatic) {
            return Str::limit($content, 180, '…');
        }

        $limited = Str::limit($excerpt, 180, '…');

        return $trimmedByWordPress && ! Str::endsWith($limited, '…') ? $limited.'…' : $limited;
    }

    /** Só letras e números, minúsculos: compara textos ignorando espaços e pontuação. */
    private function comparable(string $text): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($text));
    }

    /**
     * Imagem do card: a destacada (Jetpack expõe a URL direto), senão a
     * primeira imagem do conteúdo, senão nenhuma (o card mostra um placeholder).
     *
     * @param  array<string, mixed>  $post
     */
    private function image(array $post): ?string
    {
        $featured = $this->httpsUrl($post['jetpack_featured_media_url'] ?? null);
        if ($featured !== null) {
            return $featured;
        }

        $content = $post['content']['rendered'] ?? '';
        if (is_string($content) && preg_match_all('/<img\b[^>]*\ssrc=["\']([^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $src) {
                $url = $this->httpsUrl(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url !== null) {
                    return $url;
                }
            }
        }

        return null;
    }

    /** Aceita só URLs https absolutas (a CSP do site libera img-src https:). */
    private function httpsUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! Str::startsWith(strtolower($url), 'https://')) {
            return null;
        }

        return $url;
    }
}
