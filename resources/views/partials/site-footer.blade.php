{{--
    Footer do site institucional + botão "voltar ao topo". Compartilhado pela
    home (welcome) e pelo blog. Usa as mesmas variáveis do site-header.
--}}
@php
    $homePrefix = $homePrefix ?? '';
    $logoHref   = $homePrefix !== '' ? $homePrefix : '#hero';
    $footerNav  = [
        ['href' => $homePrefix.'#hero',              'label' => 'Início'],
        ['href' => $homePrefix.'#sobre',             'label' => 'Sobre'],
        ['href' => $homePrefix.'#eventos',           'label' => 'Eventos'],
        ['href' => route('blog'),                    'label' => 'Blog'],
        ['href' => $homePrefix.'#codigo-de-conduta', 'label' => 'Código de conduta'],
        ['href' => $homePrefix.'#contato',           'label' => 'Contato'],
    ];
@endphp

    {{-- ===================== FOOTER ===================== --}}
    <footer class="footer" style="background-image: url('{{ asset('images/footer.jpg') }}')">
        <div class="footer__content">

            {{-- Linha superior: nav + logo --}}
            <div class="footer__top">
                <nav aria-label="Rodapé — navegação" class="footer__nav">
                    @foreach($footerNav as $item)
                        <a href="{{ $item['href'] }}" class="footer__nav-link">{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="footer__brand">
                    <a href="{{ $logoHref }}" class="footer__logo-link">
                        <img src="{{ asset('images/phpcomrapadura_branca.svg') }}" alt="PHP com Rapadura" class="footer__logo" width="300" height="91">
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
