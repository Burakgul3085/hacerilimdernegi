import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/**
 * Reveal blocks as they scroll into view. Elements stay visible when the
 * IntersectionObserver API is missing, so content is never trapped behind JS.
 */
const revealTargets = document.querySelectorAll('.reveal');

if (!('IntersectionObserver' in window)) {
    revealTargets.forEach((element) => element.classList.add('is-visible'));
} else {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );

    revealTargets.forEach((element) => observer.observe(element));
}
