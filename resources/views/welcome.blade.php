@php
    $contactEmail  = 'contato@phpcomrapadura.org';
    $twitterHandle = '@phpcomrapadura';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- SEO básico --}}
    <title>PHP com Rapadura — Comunidade PHP do Ceará</title>
    <meta name="description" content="Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.">
    <meta name="keywords" content="PHP, comunidade, Ceará, Nordeste, desenvolvedores, programação, eventos, tecnologia">
    <meta name="author" content="PHP com Rapadura">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#025c98">
    <meta name="application-name" content="PHP com Rapadura">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

    {{-- Open Graph --}}
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="{{ url('/') }}">
    <meta property="og:title"       content="PHP com Rapadura — Comunidade PHP do Ceará">
    <meta property="og:description" content="Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.">
    <meta property="og:image"       content="{{ asset('images/sobre-php-com-rapadura.jpg') }}">
    <meta property="og:image:width"  content="2048">
    <meta property="og:image:height" content="1019">
    <meta property="og:image:alt"   content="Comunidade PHP com Rapadura reunida em evento">
    <meta property="og:locale"      content="pt_BR">
    <meta property="og:site_name"   content="PHP com Rapadura">

    {{-- Twitter Card --}}
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:site"        content="{{ $twitterHandle }}">
    <meta name="twitter:title"       content="PHP com Rapadura — Comunidade PHP do Ceará">
    <meta name="twitter:description" content="Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.">
    <meta name="twitter:image"       content="{{ asset('images/sobre-php-com-rapadura.jpg') }}">
    <meta name="twitter:image:alt"   content="Comunidade PHP com Rapadura reunida em evento">

    {{-- Favicon --}}
    <link rel="icon"             type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

    {{-- JSON-LD (@ escapado com @@ para não conflitar com Blade) --}}
    <script type="application/ld+json">
    [
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "@@id": "{{ url('/') }}#organization",
            "name": "PHP com Rapadura",
            "url": "{{ url('/') }}",
            "logo": {
                "@@type": "ImageObject",
                "url": "{{ asset('images/PHPcomRapadura_color.svg') }}",
                "width": 200,
                "height": 60
            },
            "image": "{{ asset('images/sobre-php-com-rapadura.jpg') }}",
            "description": "Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.",
            "foundingDate": "2014",
            "founder": {
                "@@type": "Person",
                "name": "Alessandro Feitoza"
            },
            "areaServed": {
                "@@type": "State",
                "name": "Ceará",
                "addressCountry": "BR"
            },
            "address": {
                "@@type": "PostalAddress",
                "addressRegion": "CE",
                "addressCountry": "BR"
            },
            "contactPoint": {
                "@@type": "ContactPoint",
                "email": "{{ $contactEmail }}",
                "contactType": "customer support",
                "availableLanguage": "Portuguese"
            },
            "sameAs": [
                "https://t.me/phpcomrapadura",
                "https://www.instagram.com/phpcomrapadura",
                "https://x.com/phpcomrapadura",
                "https://www.facebook.com/RAPADURAdoPoder",
                "https://github.com/PHPcomRapadura",
                "https://flickr.com/photos/phpcomrapadura/albums"
            ]
        },
        {
            "@@context": "https://schema.org",
            "@@type": "WebSite",
            "@@id": "{{ url('/') }}#website",
            "url": "{{ url('/') }}",
            "name": "PHP com Rapadura",
            "description": "Comunidade PHP do Ceará",
            "publisher": {
                "@@id": "{{ url('/') }}#organization"
            },
            "inLanguage": "pt-BR"
        }
    ]
    </script>

    {{-- Preload recursos críticos --}}
    <link rel="preload" as="image" href="{{ asset('images/PHPcomRapadura_color.svg') }}" type="image/svg+xml">
    <link rel="preload" as="image" href="{{ asset('images/favicon.png') }}" type="image/png">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-(--color-bg) text-(--color-text) antialiased">

    {{-- ===================== LOADER ===================== --}}
    {{-- Skip to main content (acessibilidade + SEO) --}}
    <a href="#hero" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[9999] focus:bg-(--color-primary) focus:text-white focus:px-4 focus:py-2 focus:rounded focus:text-sm focus:font-medium">
        Pular para o conteúdo principal
    </a>
    {{-- Região live para anúncios de leitores de tela --}}
    <div id="a11y-announcer" role="status" aria-live="polite" aria-atomic="true" class="sr-only"></div>

    <div id="page-loader" class="page-loader">
        <img src="{{ asset('images/favicon.png') }}" alt="" class="page-loader__icon" aria-hidden="true">
        <p class="page-loader__text">Perainda!</p>
    </div>

    {{-- ===================== HEADER ===================== --}}
    <header class="fixed top-0 left-0 right-0 z-50 h-16 bg-white border-b border-(--color-border)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">

            {{-- Logo --}}
            <a href="#hero" class="flex items-center rounded focus-visible:outline-2 focus-visible:outline-(--color-primary) focus-visible:outline-offset-4">
                <img src="{{ asset('images/PHPcomRapadura_color.svg') }}" alt="PHP com Rapadura" class="h-9">
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-8" aria-label="Navegação principal">
                <a href="#hero"              class="nav-link active">Início</a>
                <a href="#sobre"             class="nav-link">Sobre</a>
                <a href="#eventos"           class="nav-link">Eventos</a>
                <a href="#codigo-de-conduta" class="nav-link">Código de conduta</a>
                <a href="#contato"           class="nav-link">Contato</a>
            </nav>

            {{-- Hamburger button --}}
            <button
                id="menu-btn"
                aria-label="Abrir menu"
                aria-expanded="false"
                aria-controls="mobile-menu"
                class="md:hidden p-2 -mr-2 text-(--color-text-muted) hover:text-(--color-primary) transition-colors rounded focus-visible:outline-2 focus-visible:outline-(--color-primary)"
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="3"  y1="6"  x2="21" y2="6"/>
                    <line x1="3"  y1="12" x2="21" y2="12"/>
                    <line x1="3"  y1="18" x2="21" y2="18"/>
                </svg>
            </button>

        </div>
    </header>

    {{-- ===================== MOBILE MENU ===================== --}}
    <div id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu de navegação" class="fixed inset-0 z-40 md:hidden">

        {{-- Backdrop --}}
        <div id="menu-backdrop" class="absolute inset-0 bg-black/40"></div>

        {{-- Drawer panel --}}
        <nav class="drawer-panel absolute top-0 right-0 bottom-0 w-72 bg-white shadow-xl flex flex-col" aria-label="Menu mobile">

            {{-- Panel header --}}
            <div class="flex items-center justify-between px-6 h-16 border-b border-(--color-border) flex-shrink-0">
                <img src="{{ asset('images/PHPcomRapadura_color.svg') }}" alt="PHP com Rapadura" class="h-8">
                <button
                    id="menu-close"
                    aria-label="Fechar menu"
                    class="p-2 -mr-2 text-(--color-text-muted) hover:text-(--color-primary) transition-colors rounded focus-visible:outline-2 focus-visible:outline-(--color-primary)"
                >
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="18" y1="6"  x2="6"  y2="18"/>
                        <line x1="6"  y1="6"  x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Links --}}
            <div class="flex flex-col px-6 py-2">
                <a href="#hero"              class="mobile-nav-link active">Início</a>
                <a href="#sobre"             class="mobile-nav-link">Sobre</a>
                <a href="#eventos"           class="mobile-nav-link">Eventos</a>
                <a href="#codigo-de-conduta" class="mobile-nav-link">Código de conduta</a>
                <a href="#contato"           class="mobile-nav-link" style="border-bottom: none">Contato</a>
            </div>

        </nav>
    </div>

    <main id="main-content">

    {{-- ===================== HERO ===================== --}}
    <section id="hero" class="relative min-h-screen flex flex-col bg-(--color-bg)">

        {{-- Espaço para o header fixo --}}
        <div class="h-16 shrink-0"></div>

        {{-- Container com altura total restante --}}
        <div class="flex-1 flex flex-col items-center justify-center px-6 pb-16 gap-6">
            {{-- h1 visível apenas para leitores de tela --}}
            <h1 class="sr-only">PHP com Rapadura — Comunidade PHP do Ceará</h1>
            <img
                src="{{ asset('images/PHPcomRapadura_color.svg') }}"
                alt="PHP com Rapadura"
                width="200" height="60"
                class="hero-logo w-full max-w-[340px] sm:max-w-[560px] lg:max-w-[720px]"
            >
            <p class="hero-subtitle shrink-0 text-(--color-text-muted) text-lg sm:text-xl lg:text-2xl font-normal leading-relaxed max-w-[600px] text-center">
                Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.
            </p>
        </div>

        {{-- Scroll indicator --}}
        <button
            id="scroll-down"
            aria-label="Rolar para a próxima seção"
            class="absolute bottom-8 left-1/2 -translate-x-1/2 text-(--color-text-muted) hover:text-(--color-primary) transition-colors animate-bounce rounded focus-visible:outline-2 focus-visible:outline-(--color-primary) focus-visible:outline-offset-4"
        >
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>

    </section>

    {{-- ===================== SOBRE ===================== --}}
    <section id="sobre" class="bg-(--color-bg)">

        {{-- Bloco de texto --}}
        <div class="max-w-[1024px] mx-auto px-6 py-24 md:py-24 section-hidden">

            <h2 class="text-[26px] md:text-[32px] font-bold text-(--color-text) text-center mb-8">
                Sobre a PHP com Rapadura
            </h2>

            <p class="about-text">
                A PHP com Rapadura nasceu em 2014, no Ceará, da inquietação de quem acreditava que o Nordeste tinha potencial para ser muito mais protagonista no cenário tecnológico brasileiro. Após participar de eventos em outras regiões do país, nosso fundador, Alessandro Feitoza, percebeu que existiam milhares de estudantes, desenvolvedores e profissionais de tecnologia espalhados pelo estado, mas faltava um espaço capaz de conectar essas pessoas, incentivar o compartilhamento de conhecimento e fortalecer a comunidade PHP local.
            </p>

            <p class="about-text">
                Foi assim que surgiu a PHP com Rapadura: uma comunidade feita por pessoas que acreditam que conhecimento só tem valor quando é compartilhado. Desde então, promovemos eventos, palestras, encontros, mentorias e iniciativas que aproximam estudantes, profissionais e empresas, criando oportunidades e fortalecendo o ecossistema de tecnologia da nossa região.
            </p>

            <p class="about-text">
                O nome não poderia representar melhor nossa essência. O PHP simboliza a tecnologia que nos uniu. A rapadura representa nossas raízes nordestinas, a simplicidade, a resistência e a energia de um povo que aprende a transformar desafios em oportunidades. Assim como a rapadura nasceu dos engenhos e se tornou um dos maiores símbolos culturais do Nordeste, acreditamos que grandes profissionais, grandes projetos e grandes comunidades também podem nascer daqui.
            </p>

            <p class="about-text">
                Mais do que uma comunidade de programação, somos um movimento que conecta pessoas. Aqui, iniciantes encontram apoio para dar os primeiros passos, profissionais compartilham experiências, empresas encontram talentos e todos crescem juntos. Porque acreditamos que a tecnologia muda vidas, mas é a comunidade que transforma trajetórias.
            </p>

            <p class="about-text about-text--closing">
                Se achegue, pegue um café, corte um pedaço de rapadura e venha construir o futuro com a gente.
            </p>

        </div>

        {{-- Imagem parallax --}}
        <div
            class="about-parallax"
            role="img"
            aria-label="Comunidade PHP com Rapadura reunida"
            style="background-image: url('{{ asset('images/sobre-php-com-rapadura.jpg') }}')"
        ></div>

    </section>

    {{-- ===================== EVENTOS ===================== --}}
    <section id="eventos" class="bg-(--color-bg) py-24 md:py-32 px-6">
        <div class="max-w-[1024px] mx-auto">

            <h2 class="text-[26px] md:text-[32px] font-bold text-(--color-text) text-center mb-12 section-hidden">Eventos</h2>

            @if($events->isEmpty())
                {{-- Estado vazio --}}
                <div class="flex flex-col items-center text-center py-16 section-hidden">
                    <svg class="w-12 h-12 text-(--color-text-muted) opacity-30 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-lg font-semibold text-(--color-text)">Novos eventos em breve!</p>
                    <p class="text-sm text-(--color-text-muted) mt-2 max-w-[360px]">Acompanhe nossas redes sociais para não perder nenhuma novidade.</p>
                </div>
            @else
                {{-- Grid de eventos --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($events as $i => $event)
                        @php
                            $start = $event->starts_at;
                            $end   = $event->ends_at;

                            if (!$end || $start->isSameDay($end)) {
                                $dateLabel = $start->translatedFormat('j \d\e M. \d\e Y');
                            } elseif ($start->isSameMonth($end)) {
                                $dateLabel = $start->day . ' e ' . $end->translatedFormat('j \d\e M. \d\e Y');
                            } else {
                                $dateLabel = $start->translatedFormat('j \d\e M.') . ' a ' . $end->translatedFormat('j \d\e M. \d\e Y');
                            }
                        @endphp

                        <article
                            class="section-hidden flex flex-col rounded-xl border border-(--color-border) bg-(--color-surface) overflow-hidden transition-shadow duration-200 hover:shadow-[0_4px_16px_rgba(0,0,0,0.08)]"
                            style="transition-delay: {{ $i * 100 }}ms"
                        >
                            {{-- Imagem --}}
                            <div class="relative aspect-[16/9] overflow-hidden bg-(--color-border)">
                                @if($event->cover_image)
                                    <img
                                        src="{{ $event->cover_image }}"
                                        alt="{{ $event->name }}"
                                        class="w-full h-full object-cover"
                                        width="640"
                                        height="360"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center" aria-hidden="true">
                                        <svg class="w-9 h-9 text-(--color-text-muted) opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                @if($event->is_accepting_talks)
                                    <span
                                        class="absolute top-2.5 left-2.5 bg-(--color-primary) text-white text-[11px] font-semibold px-2.5 py-1 rounded-md leading-none"
                                        aria-label="Call for Papers aberto para submissões"
                                    >CFP Aberto</span>
                                @endif
                            </div>

                            {{-- Conteúdo --}}
                            <div class="flex flex-col flex-1 p-5">
                                <h3 class="text-base font-bold text-(--color-text) leading-snug mb-0.5">{{ $event->name }}</h3>

                                @if($event->edition)
                                    <p class="text-xs text-(--color-text-muted) mb-3">{{ $event->edition }}ª edição</p>
                                @else
                                    <div class="mb-3"></div>
                                @endif

                                <div class="space-y-1.5 mb-auto">
                                    {{-- Data --}}
                                    <div class="flex items-center gap-1.5 text-[13px] text-(--color-text-muted)">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <span>{{ $dateLabel }}</span>
                                    </div>

                                    {{-- Local --}}
                                    <div class="flex items-center gap-1.5 text-[13px] text-(--color-text-muted)">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate">{{ $event->is_online ? 'Online' : $event->location }}</span>
                                    </div>
                                </div>

                                {{-- CTA --}}
                                <a
                                    href="{{ url('/' . $event->slug) }}"
                                    class="mt-4 w-full text-center py-2.5 px-4 rounded-lg border-[1.5px] border-(--color-primary) text-(--color-primary) text-sm font-semibold
                                           hover:bg-(--color-primary) hover:text-white transition-colors duration-200"
                                    aria-label="Ver evento: {{ $event->name }}"
                                >
                                    Ver evento →
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

        </div>
    </section>

    {{-- ===================== CÓDIGO DE CONDUTA ===================== --}}
    <section id="codigo-de-conduta" class="min-h-screen bg-white flex flex-col justify-center px-6 py-16 md:py-24">
        <div class="max-w-[1024px] mx-auto w-full section-hidden">

            <h2 class="text-[26px] md:text-[32px] font-bold text-(--color-text) text-center mb-2">
                Código de Conduta
            </h2>
            <p class="text-center text-sm text-(--color-text) mb-12">Atualizado em 02 de junho de 2026</p>

            {{-- Bloco de compromisso --}}
            <div class="coc-commitment">
                <p class="coc-commitment__text">
                    A <strong>PHP com Rapadura</strong> está comprometida a ser uma comunidade livre de discriminação para todos, independente de gênero, identidade e expressão de gênero, orientação sexual, deficiência, aparência física, raça, idade, origem ou religião.
                </p>
                <p class="coc-commitment__text">
                    Não será tolerado, sob nenhuma circunstância, o constrangimento de participantes em eventos e canais de comunicação organizados pela comunidade.
                </p>
                <p class="coc-commitment__text">
                    Linguagem e/ou exposição de imagens sexuais ou de cunho discriminatório não serão apropriadas no âmbito de nossa comunidade — isto inclui os eventos, as palestras, apresentações, exemplos ou demonstrações, site, emails, chats, telegram e quaisquer outro canal da comunidade.
                </p>
                <p class="coc-commitment__text">
                    Os participantes que violarem estas regras podem ser repreendidos ou expulsos do canal e/ou evento sem direito a reembolso, a critério da organização.
                </p>

                <p class="coc-commitment__harassment-title">Entendemos como assédio e constrangimento:</p>
                <ul class="coc-commitment__list">
                    <li>Comentários que reforçam estruturas sociais de dominação, sejam relacionados a gênero, identidade e expressão de gênero, orientação sexual, deficiência, aparência física, raça, idade, origem ou religião</li>
                    <li>Imagens sexuais em espaços públicos ou privados</li>
                    <li>Intimidação intencional</li>
                    <li>Perseguição</li>
                    <li>Obtenção de som ou imagem constrangedores</li>
                    <li>Interrupção continuada e intencional de palestras e/ou eventos paralelos</li>
                    <li>Contato físico inapropriado; atenção sexual não solicitada</li>
                </ul>
            </div>

            {{-- Regras da comunidade --}}
            <p class="coc-warning">
                Estas diretrizes se aplicam a <strong>todos os espaços da comunidade</strong> — eventos presenciais, redes sociais, canais de comunicação e qualquer outro ambiente oficial da PHP com Rapadura.
            </p>

            <h3 class="text-[22px] font-semibold text-(--color-text) mb-6">Diretrizes de convivência</h3>

            <ol class="coc-rules">
                <li class="coc-rule">
                    <span class="coc-rule__number">1</span>
                    <div>
                        <strong class="coc-rule__title">Respeito</strong>
                        <p class="coc-rule__text">Trate todas as pessoas com respeito e consideração, independente do nível técnico, experiência ou background. Comentários que diminuam, humilhem ou excluam não serão tolerados em nenhum espaço da comunidade.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">2</span>
                    <div>
                        <strong class="coc-rule__title">Inclusão</strong>
                        <p class="coc-rule__text">Valorizamos a diversidade e acreditamos que comunidades fortes são feitas de perspectivas diferentes. Atitudes ou discursos que excluam pessoas por qualquer característica pessoal vão contra os valores da PHP com Rapadura.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">3</span>
                    <div>
                        <strong class="coc-rule__title">Colaboração</strong>
                        <p class="coc-rule__text">Compartilhe conhecimento de forma construtiva. Discordâncias são naturais e bem-vindas — desde que direcionadas a ideias, nunca a pessoas. Críticas devem ser técnicas, respeitosas e orientadas ao aprendizado coletivo.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">4</span>
                    <div>
                        <strong class="coc-rule__title">Relevância</strong>
                        <p class="coc-rule__text">Mantenha as interações alinhadas ao propósito da comunidade: tecnologia, aprendizado e crescimento profissional. Contribuições fora desse contexto podem ser moderadas a critério da organização.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">5</span>
                    <div>
                        <strong class="coc-rule__title">Integridade</strong>
                        <p class="coc-rule__text">Não compartilhe, distribua ou promova conteúdo ilegal, pirata ou que viole direitos autorais em nenhum espaço da comunidade. Honestidade e ética são valores inegociáveis aqui.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">6</span>
                    <div>
                        <strong class="coc-rule__title">Responsabilidade</strong>
                        <p class="coc-rule__text">Cada membro é responsável pelo próprio comportamento. Quem violar este código pode ser advertido, suspenso ou removido permanentemente dos espaços da comunidade, a critério da organização, sem direito a reembolso em caso de eventos.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">7</span>
                    <div>
                        <strong class="coc-rule__title">Denúncia</strong>
                        <p class="coc-rule__text">Presenciou alguma situação que vai contra estas diretrizes? Comunique à organização. Toda denúncia será tratada com seriedade e confidencialidade, e nenhuma pessoa será penalizada por reportar de boa-fé.</p>
                    </div>
                </li>
                <li class="coc-rule">
                    <span class="coc-rule__number">42</span>
                    <div>
                        <strong class="coc-rule__title">Alimentação</strong>
                        <p class="coc-rule__text">Todos os membros devem comer uma rapadura sem beber água para provar seu merecimento de estar no grupo...</p>
                    </div>
                </li>
            </ol>

            <p class="coc-closing">Aproveitem saudavelmente a comunidade!</p>

        </div>
    </section>

    {{-- ===================== CONTATO ===================== --}}
    <section id="contato" class="min-h-screen bg-(--color-bg) flex flex-col justify-center px-6 py-16 md:py-24">
        <div class="max-w-[1024px] mx-auto w-full section-hidden">

            <h2 class="text-[26px] md:text-[32px] font-bold text-(--color-text) text-center mb-3">
                Fale com a gente
            </h2>
            <p class="text-center text-(--color-text) text-[17px] mb-12">
                Estamos presentes em diversas plataformas. Escolha a que preferir.
            </p>

            {{-- Card email --}}
            <div class="flex justify-center mb-12">
                <div class="contact-email-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-(--color-text)" aria-hidden="true">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <div class="contact-email-card__info">
                        <span class="contact-email-card__label">Email</span>
                        <a href="mailto:{{ $contactEmail }}" class="contact-email-card__address">
                            {{ $contactEmail }}
                        </a>
                    </div>
                    <button
                        id="copy-email-btn"
                        aria-label="Copiar endereço de email"
                        class="contact-email-card__copy"
                        data-email="{{ $contactEmail }}"
                    >
                        <span id="copy-email-label">Copiar</span>
                    </button>
                </div>
            </div>

            {{-- Grid redes sociais --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">

                <a href="https://t.me/phpcomrapadura" target="_blank" rel="noopener noreferrer"
                   aria-label="PHP com Rapadura no Telegram (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#229ED9" aria-hidden="true">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L8.32 14.26l-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.828.299z"/>
                    </svg>
                    <span class="contact-social-card__name">Telegram</span>
                    <span class="contact-social-card__handle">{{ $twitterHandle }}</span>
                </a>

                <a href="https://www.instagram.com/phpcomrapadura" target="_blank" rel="noopener noreferrer"
                   aria-label="PHP com Rapadura no Instagram (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#E1306C" aria-hidden="true">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/>
                    </svg>
                    <span class="contact-social-card__name">Instagram</span>
                    <span class="contact-social-card__handle">{{ $twitterHandle }}</span>
                </a>

                <a href="https://x.com/phpcomrapadura" target="_blank" rel="noopener noreferrer"
                   aria-label="PHP com Rapadura no Twitter/X (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#000000" aria-hidden="true">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.741l7.73-8.835L1.254 2.25H8.08l4.259 5.631zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    <span class="contact-social-card__name">Twitter / X</span>
                    <span class="contact-social-card__handle">{{ $twitterHandle }}</span>
                </a>

                <a href="https://www.facebook.com/RAPADURAdoPoder" target="_blank" rel="noopener noreferrer"
                   aria-label="PHP com Rapadura no Facebook (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span class="contact-social-card__name">Facebook</span>
                    <span class="contact-social-card__handle">RAPADURAdoPoder</span>
                </a>

                <a href="https://github.com/PHPcomRapadura" target="_blank" rel="noopener noreferrer"
                   aria-label="PHP com Rapadura no GitHub (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#181717" aria-hidden="true">
                        <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0 1 12 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                    </svg>
                    <span class="contact-social-card__name">GitHub</span>
                    <span class="contact-social-card__handle">PHPcomRapadura</span>
                </a>

                <a href="https://flickr.com/photos/phpcomrapadura/albums" target="_blank" rel="noopener noreferrer"
                   aria-label="Álbum de fotos da PHP com Rapadura no Flickr (abre em nova aba)"
                   class="contact-social-card">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="#FF0084" aria-hidden="true">
                        <path d="M0 12c0 3.074 2.494 5.568 5.568 5.568S11.136 15.074 11.136 12 8.642 6.432 5.568 6.432 0 8.926 0 12zm12.864 0c0 3.074 2.494 5.568 5.568 5.568S24 15.074 24 12s-2.494-5.568-5.568-5.568-5.568 2.494-5.568 5.568z"/>
                    </svg>
                    <span class="contact-social-card__name">Flickr</span>
                    <span class="contact-social-card__handle">Álbum de fotos</span>
                </a>

            </div>

        </div>
    </section>

    </main>{{-- fim do conteúdo principal --}}

    {{-- ===================== FOOTER ===================== --}}
    <footer class="footer" style="background-image: url('{{ asset('images/footer.jpg') }}')">
        <div class="footer__content">

            {{-- Linha superior: nav + logo --}}
            <div class="footer__top">
                <nav aria-label="Rodapé — navegação" class="footer__nav">
                    <a href="#hero"              class="footer__nav-link">Início</a>
                    <a href="#sobre"             class="footer__nav-link">Sobre</a>
                    <a href="#eventos"           class="footer__nav-link">Eventos</a>
                    <a href="#codigo-de-conduta" class="footer__nav-link">Código de conduta</a>
                    <a href="#contato"           class="footer__nav-link">Contato</a>
                </nav>

                <div class="footer__brand">
                    <a href="#hero" class="footer__logo-link">
                        <img src="{{ asset('images/phpcomrapadura_branca.svg') }}" alt="PHP com Rapadura" class="footer__logo">
                    </a>
                    <p class="footer__tagline">Grupo de desenvolvedores PHP do Ceará, formados através de uma ligação doce, como a rapadura e o café.</p>
                </div>
            </div>

            {{-- Separador --}}
            <div class="footer__divider"></div>

            {{-- Copyright --}}
            <p class="footer__copyright">
                © 2014–2026 PHP com Rapadura. Todos os direitos reservados.
            </p>

        </div>
    </footer>

    {{-- Botão voltar ao topo --}}
    <button
        id="back-to-top"
        aria-label="Voltar ao topo"
        aria-hidden="true"
        class="back-to-top"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

</body>
</html>
