/**
 * sub02 Section 1-2 content reveal
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    var cont1Bound = false;
    var cont2Bound = false;

    function prefersReducedMotion() {
        return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function scrollerEl() {
        if (w.scrollInstance && w.scrollInstance.__isLoco) {
            return $('[data-scroll-container]').get(0) || w;
        }
        return w;
    }

    function refreshScroll() {
        if (w.ScrollTrigger && w.ScrollTrigger.refresh) {
            w.ScrollTrigger.refresh();
        }
        if (w.scrollInstance && typeof w.scrollInstance.update === 'function') {
            w.scrollInstance.update();
        }
    }

    function initCont1() {
        var $section = $('.cont1');
        if (!$section.length || cont1Bound) return;
        cont1Bound = true;

        var $head = $section.find('.head');
        var $headItems = $head.find('.tag, .title');
        if (!$headItems.length) {
            $headItems = $head.children();
        }
        var $items = $section.find('.list .item');

        if (!$head.length || !$items.length) return;
        if (typeof w.gsap === 'undefined' || typeof w.ScrollTrigger === 'undefined') return;

        w.gsap.registerPlugin(w.ScrollTrigger);
        if (prefersReducedMotion()) return;

        w.gsap.set($headItems, { autoAlpha: 0, y: 22 });
        w.gsap.set($items, { autoAlpha: 0, y: 30 });

        w.gsap.timeline({
            defaults: { ease: 'power3.out' },
            scrollTrigger: {
                trigger: $section.get(0),
                scroller: scrollerEl(),
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        })
            .to($headItems.toArray(), {
                autoAlpha: 1,
                y: 0,
                duration: 0.75,
                stagger: 0.12
            })
            .to($items.toArray(), {
                autoAlpha: 1,
                y: 0,
                duration: 0.85,
                stagger: 0.18
            }, '-=0.15');

        w.addEventListener('load', function () {
            setTimeout(refreshScroll, 120);
        }, { once: true });
    }

    function groupItemsByRow($items) {
        var rows = [];
        var threshold = 4;
        $items.each(function () {
            var top = this.offsetTop || 0;
            var matched = false;
            for (var i = 0; i < rows.length; i += 1) {
                if (Math.abs(rows[i].top - top) <= threshold) {
                    rows[i].items.push(this);
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                rows.push({ top: top, items: [this] });
            }
        });
        rows.sort(function (a, b) { return a.top - b.top; });
        return rows.map(function (row) { return row.items; });
    }

    function initCont2() {
        var $section = $('.cont2');
        if (!$section.length || cont2Bound) return;
        cont2Bound = true;

        var $head = $section.find('.head');
        var $headItems = $head.find('.tag, .title, .total');
        if (!$headItems.length) {
            $headItems = $head.children();
        }
        var $items = $section.find('.list .item');

        if (!$head.length || !$items.length) return;
        if (typeof w.gsap === 'undefined' || typeof w.ScrollTrigger === 'undefined') return;

        w.gsap.registerPlugin(w.ScrollTrigger);
        if (prefersReducedMotion()) return;

        var rows = groupItemsByRow($items);

        w.gsap.set($headItems, { autoAlpha: 0, y: 22 });
        w.gsap.set($items, { autoAlpha: 0, y: 30 });

        var tl = w.gsap.timeline({
            defaults: { ease: 'power3.out' },
            scrollTrigger: {
                trigger: $section.get(0),
                scroller: scrollerEl(),
                start: 'top 75%',
                toggleActions: 'play none none none'
            }
        });

        tl.to($headItems.toArray(), {
            autoAlpha: 1,
            y: 0,
            duration: 0.6,
            stagger: 0.1
        });

        rows.forEach(function (rowItems, index) {
            tl.to(rowItems, {
                autoAlpha: 1,
                y: 0,
                duration: 0.7
            }, index === 0 ? '-=0.05' : '>');
        });

        w.addEventListener('load', function () {
            setTimeout(refreshScroll, 120);
        }, { once: true });
    }

    $(initCont1);
    $(initCont2);
    $(document).on('scrollengine:ready', initCont1);
    $(document).on('scrollengine:ready', initCont2);
})(window.jQuery, window);
