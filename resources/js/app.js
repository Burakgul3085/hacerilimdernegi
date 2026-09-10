import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Reveal blocks as they scroll into view. Elements stay visible when the
 * IntersectionObserver API is missing, so content is never trapped behind JS.
 */
function revealElement(element) {
    element.classList.add('is-visible');

    if (element.hasAttribute('data-count')) {
        animateCount(element);
    }

    element.querySelectorAll('[data-count]').forEach((node) => animateCount(node));
}

function animateCount(element) {
    if (element.dataset.countPlayed === '1') {
        return;
    }

    const raw = (element.dataset.count ?? element.textContent ?? '').trim();
    const match = raw.match(/^(\d+)/);

    element.dataset.countPlayed = '1';

    if (! match) {
        return;
    }

    const target = Number(match[1]);
    const suffix = raw.slice(match[1].length);

    if (prefersReducedMotion || target === 0) {
        element.textContent = raw;

        return;
    }

    const duration = 1200;
    const startedAt = performance.now();

    const tick = (now) => {
        const progress = Math.min((now - startedAt) / duration, 1);
        const eased = 1 - (1 - progress) ** 3;
        element.textContent = `${Math.round(target * eased)}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
}

const revealTargets = document.querySelectorAll('.reveal');

if (! ('IntersectionObserver' in window)) {
    revealTargets.forEach((element) => revealElement(element));
} else {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    revealElement(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.08 },
    );

    revealTargets.forEach((element) => observer.observe(element));
}

function startParallax() {
    if (prefersReducedMotion) {
        return;
    }

    const nodes = [...document.querySelectorAll('[data-parallax]')];

    if (nodes.length === 0) {
        return;
    }

    let ticking = false;

    const update = () => {
        ticking = false;
        const viewportHeight = window.innerHeight;

        nodes.forEach((node) => {
            const speed = Number(node.dataset.parallax) || 0.14;
            const rect = node.getBoundingClientRect();

            if (rect.bottom < 0 || rect.top > viewportHeight) {
                return;
            }

            const offset = (rect.top + rect.height / 2 - viewportHeight / 2) * speed;
            node.style.transform = `translate3d(0, ${offset.toFixed(2)}px, 0) scale(1.12)`;
        });
    };

    window.addEventListener('scroll', () => {
        if (! ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });

    update();
}

function startScrollProgress() {
    const bar = document.querySelector('[data-scroll-progress]');

    if (! bar) {
        return;
    }

    const update = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const value = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
        bar.style.transform = `scaleX(${value})`;
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
}

const VEIL_FLAG = 'hacer_veil';
const VEIL_SEEN = 'hacer_veil_seen';

function storageGet(key) {
    try {
        return sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function storageSet(key, value) {
    try {
        sessionStorage.setItem(key, value);
    } catch {
        // Private mode can block sessionStorage; navigation still works.
    }
}

function storageRemove(key) {
    try {
        sessionStorage.removeItem(key);
    } catch {
        // Ignore unavailable storage.
    }
}

function revealVeil(veil) {
    document.documentElement.classList.remove('veil-pending');
    veil.classList.remove('is-covering');
    veil.classList.add('is-leaving');
    window.setTimeout(() => veil.classList.add('is-done'), 700);
}

function hideVeil(veil) {
    document.documentElement.classList.remove('veil-pending');
    veil.classList.remove('is-covering', 'is-leaving');
    veil.classList.add('is-done');
}

function startPageVeil() {
    const veil = document.querySelector('[data-page-veil]');

    if (! veil) {
        return;
    }

    if (prefersReducedMotion) {
        hideVeil(veil);
        bindVeilLinks(veil);

        return;
    }

    const incoming = storageGet(VEIL_FLAG) === '1';
    storageRemove(VEIL_FLAG);

    if (incoming) {
        veil.classList.add('is-covering');
        window.setTimeout(() => revealVeil(veil), 420);
    } else if (! storageGet(VEIL_SEEN)) {
        storageSet(VEIL_SEEN, '1');
        veil.classList.add('is-covering');
        window.setTimeout(() => revealVeil(veil), 1100);
    } else {
        hideVeil(veil);
    }

    window.setTimeout(() => {
        if (! veil.classList.contains('is-done')) {
            hideVeil(veil);
        }
    }, 2600);

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hideVeil(veil);
        }
    });

    bindVeilLinks(veil);
}

function bindVeilLinks(veil) {
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');

        if (! shouldVeilNavigation(link) || prefersReducedMotion) {
            return;
        }

        event.preventDefault();
        storageSet(VEIL_FLAG, '1');
        document.documentElement.classList.add('veil-pending');
        veil.classList.remove('is-done', 'is-leaving');
        veil.classList.add('is-covering');

        const href = link.href;
        window.setTimeout(() => {
            window.location.href = href;
        }, 360);
    }, true);
}

function shouldVeilNavigation(link) {
    if (! (link instanceof HTMLAnchorElement)) {
        return false;
    }

    const href = (link.getAttribute('href') ?? '').trim();

    if (href === '' || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    if (link.target === '_blank' || link.hasAttribute('download') || link.hasAttribute('data-no-veil')) {
        return false;
    }

    let url;

    try {
        url = new URL(link.href, window.location.href);
    } catch {
        return false;
    }

    if (! ['http:', 'https:'].includes(url.protocol)) {
        return false;
    }

    if (url.origin !== window.location.origin) {
        return false;
    }

    if (url.pathname.startsWith('/yonetim')) {
        return false;
    }

    return url.pathname !== window.location.pathname || url.search !== window.location.search;
}

startParallax();
startScrollProgress();
startPageVeil();
