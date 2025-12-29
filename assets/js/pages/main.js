/**
 * Main Page
 */
(function ($, w, d) {
    function prefersReducedMotion() {
        return w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /* Section 1: intro splash */

    /* Section 2: overview */
    function initOverview() {
        const $section = $('.cont2');
        if (!$section.length || !w.gsap) return;

        const $headline = $section.find('.headline');
        const $stats = $section.find('.stats');
        const $lead = $section.find('.lead');
        const $desc = $section.find('.desc');
        const $image = $section.find('.visiual');
        if (!$headline.length && !$stats.length && !$image.length && !$lead.length && !$desc.length) return;

        function formatValue(value) {
            return Math.round(value).toLocaleString('en-US');
        }

        function runCount(instant) {
            $stats.find('.value').each(function () {
                const $el = $(this);
                if ($el.data('counted')) return;
                $el.data('counted', true);

                const text = ($el.text() || '').trim();
                const suffixMatch = text.match(/[^0-9.,-]+$/);
                const suffix = suffixMatch ? suffixMatch[0] : '';
                const rawTarget = $el.data('count');
                let target = Number(rawTarget);
                if (!isFinite(target)) {
                    target = Number(text.replace(/[^0-9.-]/g, ''));
                }
                if (!isFinite(target)) target = 0;

                if (instant || prefersReducedMotion()) {
                    $el.text(formatValue(target) + suffix);
                    return;
                }

                $el.text('0' + suffix);
                const counter = { value: 0 };
                w.gsap.to(counter, {
                    value: target,
                    duration: 1.4,
                    ease: 'power3.out',
                    onUpdate: function () {
                        $el.text(formatValue(counter.value) + suffix);
                    }
                });
            });
        }

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) {
            runCount(true);
            return;
        }

        const tl = w.gsap.timeline({
            scrollTrigger: {
                trigger: $section.get(0),
                start: 'top 65%',
                toggleActions: 'play none none none'
            }
        });

        if ($headline.length) {
            tl.from($headline.get(0), { autoAlpha: 0, y: 50, duration: 0.6, ease: 'power3.out' });
        }
        if ($stats.length) {
            tl.from($stats.get(0), {
                autoAlpha: 0,
                y: 50,
                duration: 0.55,
                ease: 'power3.out',
                onStart: function () {
                    runCount(false);
                }
            });
        }
        const textTargets = [];
        if ($lead.length) textTargets.push($lead.get(0));
        if ($desc.length) textTargets.push($desc.get(0));
        if ($image.length) textTargets.push($image.get(0));
        if (textTargets.length) {
            tl.from(textTargets, {
                autoAlpha: 0,
                y: 50,
                duration: 0.55,
                ease: 'power3.out',
                stagger: 0.1
            });
        }
    }

    $(function () {
        initOverview();
    });
})(window.jQuery, window, document);
