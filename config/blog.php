<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blog (WordPress legado)
    |--------------------------------------------------------------------------
    |
    | O site institucional lista os posts do blog legado pela REST API do
    | WordPress (/wp-json/wp/v2/posts). Nada é persistido no banco da suite:
    | cada página de posts fica em cache por `cache_ttl` segundos e a última
    | cópia boa fica guardada por `stale_ttl` para servir quando a API falha.
    |
    */

    // Vazio no .env também cai no padrão (env() devolve '' para BLOG_URL=).
    'url' => env('BLOG_URL') ?: 'https://legacy.phpcomrapadura.org',

    // Posts por página em /blog (a API do WordPress aceita no máximo 100).
    'per_page' => (int) env('BLOG_PER_PAGE', 9),

    // Segundos até consultar a API de novo para a mesma página.
    'cache_ttl' => (int) env('BLOG_CACHE_TTL', 3600),

    // Segundos que a última cópia boa fica disponível como fallback.
    'stale_ttl' => (int) env('BLOG_STALE_TTL', 604800),

    // Timeout em segundos de cada chamada à API (há uma nova tentativa).
    'timeout' => (int) env('BLOG_TIMEOUT', 6),

];
