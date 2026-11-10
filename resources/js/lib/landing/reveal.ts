import type { Directive } from 'vue';

let observer: IntersectionObserver | null = null;

function watcher(): IntersectionObserver {
    observer ??= new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;

                entry.target.classList.add('is-visible');
                observer?.unobserve(entry.target);
            }
        },
        { rootMargin: '0px 0px -8% 0px', threshold: 0.12 },
    );

    return observer;
}

/**
 * Sobe e aparece quando o elemento entra na tela. O que já está visível ao
 * montar fica como está, para não piscar. O valor opcional é um atraso em ms.
 */
export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (el.getBoundingClientRect().top < window.innerHeight) return;

        el.classList.add('lp-reveal');

        if (binding.value) {
            el.style.transitionDelay = `${binding.value}ms`;
        }

        watcher().observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
