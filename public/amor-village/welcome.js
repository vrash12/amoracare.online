(() => {
    'use strict';
    const toggle = document.querySelector('.amor-menu-toggle');
    const navigation = document.querySelector('.amor-navigation');
    if (toggle && navigation) {
        // Navigation stays visible if JavaScript is unavailable.
        document.documentElement.classList.add('amor-menu-ready');
        toggle.hidden = false;
        const close = (restoreFocus = false) => {
            toggle.setAttribute('aria-expanded', 'false');
            navigation.classList.remove('is-open');
            if (restoreFocus) toggle.focus();
        };
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            navigation.classList.toggle('is-open', open);
        });
        document.addEventListener('keydown', event => {
            if (event.defaultPrevented || document.querySelector('.amor-a11y-panel[open]')) return;
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                event.preventDefault();
                close(true);
            }
        });
        navigation.addEventListener('click', event => {
            const link = event.target.closest('a');
            if (!link) return;
            close();
            if (link.hash && link.pathname === location.pathname) {
                const target = document.querySelector(link.hash);
                if (target) {
                    target.setAttribute('tabindex', '-1');
                    target.focus({ preventScroll: true });
                }
            }
        });
        matchMedia('(min-width: 1101px)').addEventListener('change', () => close());
    }
    document.addEventListener('click', event => {
        if (!event.target.closest('a[href="#accessibility"]')) return;
        const statement = document.getElementById('accessibility');
        if (statement) {
            statement.open = true;
            statement.querySelector('summary').focus({ preventScroll: true });
        }
    });
    document.addEventListener('focusin', event => {
        const trigger = document.getElementById('amor-a11y-trigger');
        if (!trigger || event.target === trigger || event.target.closest('.amor-a11y-panel') || !event.target.matches('a,button,input,select,textarea')) return;
        const target = event.target.getBoundingClientRect();
        const floating = trigger.getBoundingClientRect();
        if (target.right > floating.left && target.left < floating.right && target.bottom > floating.top && target.top < floating.bottom) {
            window.scrollBy({ top: target.bottom - floating.top + 24, behavior: 'instant' });
        }
    });
})();
