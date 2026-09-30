(() => {
    const menuBtn = document.getElementById('menuBtn');
    const navLinks = document.getElementById('navLinks');

    if (menuBtn && navLinks) {
        const closeMenu = () => {
            navLinks.classList.remove('open');
            menuBtn.setAttribute('aria-expanded', 'false');
            menuBtn.setAttribute('aria-label', 'Open menu');
        };

        menuBtn.addEventListener('click', () => {
            const open = navLinks.classList.toggle('open');
            menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            menuBtn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });

        navLinks.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) closeMenu();
        });
    }

    const layers = [...document.querySelectorAll('[data-parallax]')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduceMotion && layers.length) {
        let ticking = false;

        const update = () => {
            const y = window.scrollY;
            layers.forEach(layer => {
                const speed = Number(layer.dataset.parallax || 0);
                layer.style.transform = `translate3d(0, ${y * speed}px, 0)`;
            });
            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(update);
                ticking = true;
            }
        }, { passive: true });
    }
})();
