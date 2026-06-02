// Hide loader when page is fully loaded
window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    loader?.classList.add('hidden');
    setTimeout(() => loader?.remove(), 400);
});

document.addEventListener('DOMContentLoaded', () => {
    const menuBtn      = document.getElementById('menu-btn');
    const menuClose    = document.getElementById('menu-close');
    const mobileMenu   = document.getElementById('mobile-menu');
    const menuBackdrop = document.getElementById('menu-backdrop');
    const mobileLinks  = mobileMenu?.querySelectorAll('.mobile-nav-link');
    const announcer    = document.getElementById('a11y-announcer');

    // Anuncia mensagem para leitores de tela via aria-live
    function announce(msg) {
        if (!announcer) return;
        announcer.textContent = '';
        requestAnimationFrame(() => { announcer.textContent = msg; });
    }

    // Focus trap no menu mobile
    function getFocusableInMenu() {
        return Array.from(mobileMenu.querySelectorAll(
            'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(el => !el.closest('[aria-hidden="true"]'));
    }

    function trapFocus(e) {
        if (!mobileMenu.classList.contains('open')) return;
        const focusable = getFocusableInMenu();
        const first = focusable[0];
        const last  = focusable[focusable.length - 1];
        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        }
    }

    function openMenu() {
        mobileMenu.classList.add('open');
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.setAttribute('aria-label', 'Fechar menu');
        document.body.style.overflow = 'hidden';
        menuClose.focus();
        document.addEventListener('keydown', trapFocus);
    }

    function closeMenu() {
        mobileMenu.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.setAttribute('aria-label', 'Abrir menu');
        document.body.style.overflow = '';
        menuBtn.focus();
        document.removeEventListener('keydown', trapFocus);
    }

    menuBtn?.addEventListener('click', openMenu);
    menuClose?.addEventListener('click', closeMenu);
    menuBackdrop?.addEventListener('click', closeMenu);
    mobileLinks?.forEach(link => link.addEventListener('click', closeMenu));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
            closeMenu();
        }
    });

    // Section entrance animations
    const animatedEls = document.querySelectorAll('.section-hidden');
    const entranceObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('section-visible');
                entranceObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    animatedEls.forEach(el => entranceObserver.observe(el));

    // Scroll spy com aria-current
    const sections = document.querySelectorAll('section[id]');
    const navLinks  = document.querySelectorAll('.nav-link, .mobile-nav-link');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const id = entry.target.id;
            navLinks.forEach(link => {
                const isActive = link.getAttribute('href') === `#${id}`;
                link.classList.toggle('active', isActive);
                // aria-current para leitores de tela
                if (isActive) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        });
    }, { threshold: 0.4 });

    sections.forEach(section => observer.observe(section));

    // Copy email button com aria-live
    const copyBtn   = document.getElementById('copy-email-btn');
    const copyLabel = document.getElementById('copy-email-label');
    copyBtn?.addEventListener('click', () => {
        navigator.clipboard.writeText(copyBtn.dataset.email).then(() => {
            copyLabel.textContent = 'Copiado ✓';
            copyBtn.setAttribute('aria-label', 'Email copiado');
            announce('Endereço de email copiado para a área de transferência');
            setTimeout(() => {
                copyLabel.textContent = 'Copiar';
                copyBtn.setAttribute('aria-label', 'Copiar endereço de email');
            }, 2000);
        });
    });

    // Back to top button
    const backToTopBtn = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 400) {
            backToTopBtn.classList.add('visible');
            backToTopBtn.setAttribute('aria-hidden', 'false');
        } else {
            backToTopBtn.classList.remove('visible');
            backToTopBtn.setAttribute('aria-hidden', 'true');
        }
    }, { passive: true });
    backToTopBtn?.addEventListener('click', () => {
        document.getElementById('hero')?.scrollIntoView({ behavior: 'smooth' });
    });

    // Smooth scroll indicator
    document.getElementById('scroll-down')?.addEventListener('click', () => {
        const next = document.querySelector('section[id]:not(#hero)');
        next?.scrollIntoView({ behavior: 'smooth' });
    });
});
