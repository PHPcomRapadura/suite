{{--
    Header do site institucional: skip link, região aria-live, header fixo com
    nav desktop e drawer mobile. Compartilhado pela home (welcome) e pelo blog.

    Variáveis opcionais:
    - $homePrefix: '' na home (âncoras #sobre etc.); url('/') nas outras páginas.
    - $navActive:  chave do item ativo ('hero' na home, 'blog' no blog).
    - $skipTarget: destino do skip link ('#hero' na home, '#main-content' fora dela).
--}}
@php
    $homePrefix = $homePrefix ?? '';
    $navActive  = $navActive ?? 'hero';
    $skipTarget = $skipTarget ?? '#hero';
    $logoHref   = $homePrefix !== '' ? $homePrefix : '#hero';
    $navItems   = [
        ['key' => 'hero',              'href' => $homePrefix.'#hero',              'label' => 'Início'],
        ['key' => 'sobre',             'href' => $homePrefix.'#sobre',             'label' => 'Sobre'],
        ['key' => 'eventos',           'href' => $homePrefix.'#eventos',           'label' => 'Eventos'],
        ['key' => 'blog',              'href' => route('blog'),                    'label' => 'Blog'],
        ['key' => 'codigo-de-conduta', 'href' => $homePrefix.'#codigo-de-conduta', 'label' => 'Código de conduta'],
        ['key' => 'contato',           'href' => $homePrefix.'#contato',           'label' => 'Contato'],
    ];
@endphp

    {{-- Skip to main content (acessibilidade + SEO) --}}
    <a href="{{ $skipTarget }}" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[9999] focus:bg-(--color-primary) focus:text-white focus:px-4 focus:py-2 focus:rounded focus:text-sm focus:font-medium">
        Pular para o conteúdo principal
    </a>
    {{-- Região live para anúncios de leitores de tela --}}
    <div id="a11y-announcer" role="status" aria-live="polite" aria-atomic="true" class="sr-only"></div>

    {{-- ===================== HEADER ===================== --}}
    <header class="fixed top-0 left-0 right-0 z-50 h-16 bg-white border-b border-(--color-border)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">

            {{-- Logo --}}
            <a href="{{ $logoHref }}" class="flex items-center rounded focus-visible:outline-2 focus-visible:outline-(--color-primary) focus-visible:outline-offset-4">
                <img src="{{ asset('images/PHPcomRapadura_color.svg') }}" alt="PHP com Rapadura" class="h-9">
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-8" aria-label="Navegação principal">
                @foreach($navItems as $item)
                    @php $isActive = $item['key'] === $navActive; @endphp
                    <a href="{{ $item['href'] }}" class="nav-link{{ $isActive ? ' active' : '' }}"{!! $isActive && $homePrefix !== '' ? ' aria-current="page"' : '' !!}>{{ $item['label'] }}</a>
                @endforeach
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
                @foreach($navItems as $item)
                    @php $isActive = $item['key'] === $navActive; @endphp
                    <a href="{{ $item['href'] }}" class="mobile-nav-link{{ $isActive ? ' active' : '' }}"{!! $isActive && $homePrefix !== '' ? ' aria-current="page"' : '' !!}{!! $loop->last ? ' style="border-bottom: none"' : '' !!}>{{ $item['label'] }}</a>
                @endforeach
            </div>

        </nav>
    </div>
