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

startParallax();
startScrollProgress();
