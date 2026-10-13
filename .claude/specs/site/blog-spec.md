# Spec — Blog (Site Institucional)

**Status:** ✅ Implementado — `resources/views/blog.blade.php`

**Rota:** `GET /blog` → `BlogController@index` (nome `blog`)
**Arquivo de referência de padrões:** `.claude/patterns/front-patterns.md`

---

## 1. Objetivo

Trazer para o site institucional um feed dos posts do blog da comunidade, que continua publicado no WordPress legado (`https://legacy.phpcomrapadura.org`). O site lista os posts em cards com link para o texto completo no blog; nada do WordPress é importado para o banco da suite.

---

## 2. Fonte dos dados

- REST API do WordPress: `GET {BLOG_URL}/wp-json/wp/v2/posts?page=N&per_page=9&_embed=author,wp:term&_fields=…`
- Headers `X-WP-Total` / `X-WP-TotalPages` alimentam a paginação.
- Página além da última: a API responde `400 rest_post_invalid_page_number` → o site responde **404**.
- Serviço: `app/Services/BlogService.php`; config: `config/blog.php` (`BLOG_URL`, `BLOG_PER_PAGE`, `BLOG_CACHE_TTL`, `BLOG_STALE_TTL`, `BLOG_TIMEOUT`).

### 2.1 Cache e resiliência

A API legada responde em ~3 s, então o site nunca a consulta por requisição do visitante:

| Cache | Chave | TTL padrão | Uso |
|-------|-------|-----------|-----|
| Fresco | `blog:posts:{per_page}:{page}` | 1 h (`BLOG_CACHE_TTL`) | Servido direto enquanto válido |
| Última cópia boa | `…:stale` | 7 dias (`BLOG_STALE_TTL`) | Servido quando a API falha (timeout, 5xx, JSON inválido); a falha vai para o log como `warning` |

Sem nenhuma cópia em cache e com a API fora, a página responde 200 com o estado "Não conseguimos carregar os posts agora." e um botão para abrir o blog.

### 2.2 Conteúdo não confiável

Tudo que vem da API é tratado como não confiável (o WordPress legado já foi comprometido no passado):

- Título e resumo: `strip_tags` + decodificação de entidades + Blade `{{ }}`.
- Resumo: remove o `[…]` final do WordPress e limita a 180 caracteres.
- Link do post: precisa começar com `BLOG_URL`; senão cai na home do blog.
- Imagens (destacada, do conteúdo, avatar): só URLs `https://` absolutas (a CSP de produção libera `img-src https:`).
- JSON-LD: montado no controller e serializado com `JSON_HEX_*` para não fechar o `<script>`.

---

## 3. Layout

Página própria (não é âncora da home) com o mesmo header, menu mobile e footer da home via `resources/views/partials/site-header.blade.php` e `site-footer.blade.php`.

### 3.1 Header e menu

- Item **Blog** entre "Eventos" e "Código de conduta" no menu desktop, no drawer mobile e no footer.
- Na home os links continuam âncoras (`#sobre`); nas outras páginas recebem `url('/')` como prefixo (`$homePrefix`).
- Em `/blog` o item fica `active` + `aria-current="page"`. A `<section>` da página **não tem `id`** de propósito: o scroll spy do `app.js` só observa `section[id]` e desmarcaria o item.
- O botão "voltar ao topo" usa `window.scrollTo` quando não existe `#hero`.

### 3.2 Cabeçalho da página

- `<h1 id="blog-title">Blog</h1>` — 32px desktop / 26px mobile, peso 700, centralizado
- Subtítulo: "Artigos, novidades e registros dos eventos da comunidade."

### 3.3 Cards

Grid `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`, mesmo estilo dos cards de eventos:

- Imagem 16:9: destacada (`jetpack_featured_media_url`) → primeira `<img>` do conteúdo → placeholder (ícone de documento)
- Badge no canto da imagem com a primeira categoria (exceto "Sem categoria")
- Data em pt-BR (`j de M. de Y`) dentro de `<time datetime>`
- Título (`<h2>`) com link para o post no blog
- Resumo (até 180 caracteres). O resumo automático do WordPress vem sem espaço entre blocos ("Brasília)Mentores:"); quando o resumo da API começa igual ao conteúdo, ele é refeito a partir do conteúdo (que ainda tem `<p>`, `<br>`, `<li>`); um resumo escrito à mão é mantido
- Rodapé do card: avatar + nome do autor e link "Ler post →" (`aria-label="Ler post: {título}"`)
- Animação de entrada `.section-hidden` escalonada por coluna

### 3.4 Paginação

Só aparece quando há mais de uma página: "← Mais recentes" / "Página N de M" / "Mais antigos →", com `rel="prev"` / `rel="next"`. A página 1 usa `/blog` (sem `?page=1`); `?page=0`, negativo ou não numérico → 404.

### 3.5 Rodapé da listagem

"Os textos completos ficam no blog da comunidade: legacy.phpcomrapadura.org · RSS" (link para `{BLOG_URL}/feed/`).

---

## 4. SEO

- `<title>` "Blog — PHP com Rapadura" (páginas seguintes: "Blog — Página N — …")
- `canonical` (`/blog` na página 1, `/blog?page=N` nas demais), `rel="prev"/"next"`
- `rel="alternate" type="application/rss+xml"` apontando para o feed do WordPress
- Open Graph + Twitter Card com a imagem institucional
- JSON-LD `Blog` com a lista de `BlogPosting` da página, ligado ao `#organization` da home
- `/blog` listado no `sitemap.xml` (weekly, 0.8)

---

## 5. Testes

`tests/Feature/Site/BlogTest.php` (20 casos, `Http::fake` + `Http::preventStrayRequests`): listagem, parâmetros enviados à API, estado vazio, escape de HTML, resumo automático vs. manual, links fora do domínio, escolha da imagem, cache (inclusive com o store `file`, que serializa — só escalares vão para o cache), fallback para a última cópia (`$this->travel(2)->hours()`), API fora sem cache, paginação, 404s, canonical/RSS/JSON-LD, item no menu (home e blog) e sitemap.

`tests/e2e/blog.spec.js`: status 200, título, `<h1>` e `aria-current` no menu (requer `docker compose up`).
