import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Fades the cards of a grid in as they scroll into view, a row at a time.
 *
 * Taken from the Ex-Libris listings, with three changes for this site:
 *
 * - A row waits for its own images before it appears. Cards that faded in as
 *   empty frames and then had a photo pop into each one would bring back
 *   the very flash this replaces. A slow image holds its row for 1.2
 *   seconds at most: longer, and a slow school connection would show a
 *   blank page. Each photo's space is already reserved (see MediaSize), so
 *   one that arrives after its card simply appears in its frame.
 * - The left-to-right stagger comes from where each card actually sits, not
 *   from a column count read off `grid-template-columns`. The gallery is
 *   laid out with CSS columns, which have no grid template to read.
 * - With reduced motion asked for, cards fade without sliding or staggering.
 *
 * Children are hidden in onMounted. The site renders in the browser only, so
 * that happens before the first paint and nothing is seen to vanish first.
 */
export function useScrollReveal(selector = ':scope > *') {
    const container = ref<HTMLElement | null>(null);

    let observer: IntersectionObserver | null = null;
    const timers = new Set<number>();
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const stagger = reduced ? 0 : 90;
    const rise = reduced ? '0px' : '24px';
    const duration = reduced ? 200 : 600;

    const later = (callback: () => void, ms: number): void => {
        const timer = window.setTimeout(() => {
            timers.delete(timer);
            callback();
        }, ms);
        timers.add(timer);
    };

    /** Every image in the card loaded, failed or given up on. */
    const imagesSettled = (card: HTMLElement): Promise<void> =>
        Promise.all(
            Array.from(card.querySelectorAll('img'), (image) =>
                image.complete
                    ? Promise.resolve()
                    : new Promise<void>((resolve) => {
                          image.addEventListener('load', () => resolve(), { once: true });
                          image.addEventListener('error', () => resolve(), { once: true });
                      }),
            ),
        ).then(() => undefined);

    const settled = (cards: HTMLElement[]): Promise<void> =>
        Promise.race([
            Promise.all(cards.map(imagesSettled)).then(() => undefined),
            new Promise<void>((resolve) => later(resolve, 1200)),
        ]);

    /** Hand the card back to the stylesheet once it has arrived. */
    const release = (card: HTMLElement): void => {
        card.style.removeProperty('opacity');
        card.style.removeProperty('transform');
        card.style.removeProperty('transition');
    };

    const show = (card: HTMLElement, delay: number): void => {
        later(() => {
            card.style.opacity = '1';
            card.style.transform = 'none';
            // A transform left in place would make every card its own
            // stacking context for good, so clear it once the fade is done.
            later(() => release(card), duration + 50);
        }, delay);
    };

    const reveal = (entries: IntersectionObserverEntry[]): void => {
        const arriving = entries.filter((entry) => entry.isIntersecting);

        if (!arriving.length) {
            return;
        }

        arriving.forEach((entry) => observer?.unobserve(entry.target));

        // Cards that share a left edge share a column, whichever kind of
        // layout put them there; each column further right waits a beat.
        const lefts = [
            ...new Set(arriving.map((entry) => Math.round(entry.boundingClientRect.left))),
        ].sort((a, b) => a - b);
        const cards = arriving.map((entry) => entry.target as HTMLElement);

        void settled(cards).then(() => {
            arriving.forEach((entry, index) => {
                const column = lefts.indexOf(Math.round(entry.boundingClientRect.left));
                show(cards[index], column * stagger);
            });
        });
    };

    const revealAll = (): void => {
        observer?.disconnect();
        container.value?.querySelectorAll<HTMLElement>(selector).forEach((card) => release(card));
    };

    onMounted(() => {
        if (!container.value || !('IntersectionObserver' in window)) {
            return;
        }

        const cards = Array.from(container.value.querySelectorAll<HTMLElement>(selector));

        cards.forEach((card) => {
            card.style.opacity = '0';
            card.style.transform = `translateY(${rise})`;
            card.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
        });

        observer = new IntersectionObserver(reveal, {
            threshold: 0.05,
            rootMargin: '0px 0px -60px 0px',
        });
        cards.forEach((card) => observer?.observe(card));

        // Cards never scrolled to would otherwise print as blank space.
        window.addEventListener('beforeprint', revealAll);
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
        timers.forEach((timer) => window.clearTimeout(timer));
        window.removeEventListener('beforeprint', revealAll);
    });

    return { container, revealAll };
}
