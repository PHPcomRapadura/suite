@php
    $twitterHandle = '@phpcomrapadura';
    $pageTitle     = $posts->currentPage() > 1
        ? 'Blog — Página '.$posts->currentPage().' — PHP com Rapadura'
        : 'Blog — PHP com Rapadura';
    $description   = 'Artigos, novidades e registros dos eventos da comunidade PHP com Rapadura, a comunidade PHP do Ceará.';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO básico --}}
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="keywords" content="PHP, blog, comunidade, Ceará, Nordeste, desenvolvedores, programação, eventos, tecnologia">
    <meta name="author" content="PHP com Rapadura">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#025c98">
    <meta name="application-name" content="PHP com Rapadura">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    <link rel="alternate" type="application/rss+xml" title="Blog PHP com Rapadura" href="{{ $feedUrl }}">
    @if($posts->previousPageUrl())
        <link rel="prev" href="{{ $posts->currentPage() === 2 ? route('blog') : $posts->previousPageUrl() }}">
    @endif
    @if($posts->hasMorePages())
        <link rel="next" href="{{ $posts->nextPageUrl() }}">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ $canonical }}">
    <meta property="og:title"       content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image"       content="{{ asset('images/sobre-php-com-rapadura.jpg') }}">
    <meta property="og:image:width"  content="2048">
    <meta property="og:image:height" content="1019">
    <meta property="og:image:alt"   content="Comunidade PHP com Rapadura reunida em evento">
    <meta property="og:locale"      content="pt_BR">
    <meta property="og:site_name"   content="PHP com Rapadura">

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:site"        content="{{ $twitterHandle }}">
    <meta name="twitter:title"       content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image"       content="{{ asset('images/sobre-php-com-rapadura.jpg') }}">
    <meta name="twitter:image:alt"   content="Comunidade PHP com Rapadura reunida em evento">

    {{-- Favicon --}}
    <link rel="icon"             type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

    {{-- JSON-LD montado no controller; JSON_HEX_* impede fechar o <script> com dados da API --}}
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    {{-- Preload recursos críticos --}}
    <link rel="preload" as="image" href="{{ asset('images/PHPcomRapadura_color.svg') }}" type="image/svg+xml">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-(--color-bg) text-(--color-text) antialiased">

    @include('partials.site-header', ['homePrefix' => url('/'), 'navActive' => 'blog', 'skipTarget' => '#main-content'])

    <main id="main-content">

        {{-- Espaço para o header fixo --}}
        <div class="h-16 shrink-0"></div>

        {{-- Sem id na section: o scroll spy do app.js só atua em section[id] e
             desmarcaria o item "Blog" do menu, que aqui fica ativo fixo. --}}
        <section class="bg-(--color-bg) px-6 py-16 md:py-24" aria-labelledby="blog-title">
            <div class="max-w-[1024px] mx-auto">

                <div class="text-center mb-12 section-hidden">
                    <h1 id="blog-title" class="text-[26px] md:text-[32px] font-bold text-(--color-text)">Blog</h1>
                    <p class="text-(--color-text-muted) text-base md:text-lg leading-relaxed mt-3 max-w-[560px] mx-auto">
                        Artigos, novidades e registros dos eventos da comunidade.
                    </p>
                </div>

                @if(! $available)
                    {{-- API do blog fora do ar e sem cópia em cache --}}
                    <div class="flex flex-col items-center text-center py-16 section-hidden" role="status">
                        <svg class="w-12 h-12 text-(--color-text-muted) opacity-30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <p class="text-lg font-semibold text-(--color-text)">Não conseguimos carregar os posts agora.</p>
                        <p class="text-sm text-(--color-text-muted) mt-2 max-w-[360px]">Tente de novo em instantes ou leia direto no blog.</p>
                        <a href="{{ $blogUrl }}/" class="mt-6 inline-block py-2.5 px-5 rounded-lg border-[1.5px] border-(--color-primary) text-(--color-primary) text-sm font-semibold hover:bg-(--color-primary) hover:text-white transition-colors duration-200">
                            Abrir o blog →
                        </a>
                    </div>
                @elseif($posts->isEmpty())
                    {{-- Estado vazio --}}
                    <div class="flex flex-col items-center text-center py-16 section-hidden">
                        <svg class="w-12 h-12 text-(--color-text-muted) opacity-30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <p class="text-lg font-semibold text-(--color-text)">Nenhum post por aqui ainda.</p>
                        <p class="text-sm text-(--color-text-muted) mt-2 max-w-[360px]">Acompanhe nossas redes sociais para não perder nenhuma novidade.</p>
                    </div>
                @else
                    {{-- Grid de posts --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($posts as $i => $post)
                            <article
                                class="section-hidden flex flex-col rounded-xl border border-(--color-border) bg-(--color-surface) overflow-hidden transition-shadow duration-200 hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)]"
                                style="transition-delay: {{ ($i % 3) * 100 }}ms"
                            >
                                {{-- Imagem --}}
                                <a href="{{ $post['link'] }}" class="relative block aspect-[16/9] overflow-hidden bg-(--color-border)" tabindex="-1" aria-hidden="true">
                                    @if($post['image'])
                                        <img
                                            src="{{ $post['image'] }}"
                                            alt=""
                                            class="w-full h-full object-cover"
                                            width="640"
                                            height="360"
                                            loading="lazy"
                                            decoding="async"
                                            referrerpolicy="no-referrer"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-9 h-9 text-(--color-text-muted) opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif

                                    @if($post['categories'] !== [])
                                        <span class="absolute top-2.5 left-2.5 bg-(--color-primary) text-white text-[11px] font-semibold px-2.5 py-1 rounded-md leading-none">{{ $post['categories'][0] }}</span>
                                    @endif
                                </a>

                                {{-- Conteúdo --}}
                                <div class="flex flex-col flex-1 p-5">
                                    <div class="flex items-center gap-1.5 text-[13px] text-(--color-text-muted) mb-2">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <time datetime="{{ $post['date']->toDateString() }}">{{ $post['date']->translatedFormat('j \d\e M. \d\e Y') }}</time>
                                    </div>

                                    <h2 class="text-base font-bold text-(--color-text) leading-snug mb-2">
                                        <a href="{{ $post['link'] }}" class="hover:text-(--color-primary) transition-colors rounded focus-visible:outline-2 focus-visible:outline-(--color-primary) focus-visible:outline-offset-2">{{ $post['title'] }}</a>
                                    </h2>

                                    @if($post['excerpt'] !== '')
                                        <p class="text-sm text-(--color-text-muted) leading-relaxed mb-4">{{ $post['excerpt'] }}</p>
                                    @endif

                                    <div class="mt-auto pt-4 flex items-center justify-between gap-3 border-t border-(--color-border)">
                                        @if($post['author']['name'] !== '')
                                            <div class="flex items-center gap-2 min-w-0">
                                                @if($post['author']['avatar'])
                                                    <img src="{{ $post['author']['avatar'] }}" alt="" width="28" height="28" class="w-7 h-7 rounded-full shrink-0" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                                                @endif
                                                <span class="text-[13px] text-(--color-text-muted) truncate">{{ $post['author']['name'] }}</span>
                                            </div>
                                        @else
                                            <span></span>
                                        @endif

                                        <a
                                            href="{{ $post['link'] }}"
                                            class="shrink-0 text-sm font-semibold text-(--color-primary) hover:underline rounded focus-visible:outline-2 focus-visible:outline-(--color-primary) focus-visible:outline-offset-2"
                                            aria-label="Ler post: {{ $post['title'] }}"
                                        >
                                            Ler post →
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Paginação --}}
                    @if($posts->lastPage() > 1)
                        <nav class="mt-12 flex items-center justify-between gap-4 section-hidden" aria-label="Paginação do blog">
                            @if($posts->previousPageUrl())
                                <a
                                    rel="prev"
                                    href="{{ $posts->currentPage() === 2 ? route('blog') : $posts->previousPageUrl() }}"
                                    class="py-2.5 px-4 rounded-lg border-[1.5px] border-(--color-primary) text-(--color-primary) text-sm font-semibold hover:bg-(--color-primary) hover:text-white transition-colors duration-200"
                                >← Mais recentes</a>
                            @else
                                <span aria-hidden="true"></span>
                            @endif

                            <span class="text-sm text-(--color-text-muted)">Página {{ $posts->currentPage() }} de {{ $posts->lastPage() }}</span>

                            @if($posts->hasMorePages())
                                <a
                                    rel="next"
                                    href="{{ $posts->nextPageUrl() }}"
                                    class="py-2.5 px-4 rounded-lg border-[1.5px] border-(--color-primary) text-(--color-primary) text-sm font-semibold hover:bg-(--color-primary) hover:text-white transition-colors duration-200"
                                >Mais antigos →</a>
                            @else
                                <span aria-hidden="true"></span>
                            @endif
                        </nav>
                    @endif
                @endif

                <p class="text-center text-sm text-(--color-text-muted) mt-12">
                    Os textos completos ficam no blog da comunidade:
                    <a href="{{ $blogUrl }}/" class="text-(--color-primary) font-medium hover:underline">{{ preg_replace('#^https?://#', '', $blogUrl) }}</a>
                    · <a href="{{ $feedUrl }}" class="text-(--color-primary) font-medium hover:underline" aria-label="Feed RSS do blog">RSS</a>
                </p>

            </div>
        </section>

    </main>{{-- fim do conteúdo principal --}}

    @include('partials.site-footer', ['homePrefix' => url('/')])

</body>
</html>
