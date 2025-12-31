/**
 * sub0101 Section 2 timeline scroll effects
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    var bound = false;

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

    function initCont2() {
        var $section = $('.cont2');
        if (!$section.length || bound) return;
        bound = true;

        var $list = $section.find('.list');
        var $items = $section.find('.item');
        if (!$list.length) return;

        if (typeof w.gsap === 'undefined' || typeof w.ScrollTrigger === 'undefined') {
            $list[0].style.setProperty('--line-progress', '100%');
            return;
        }

        w.gsap.registerPlugin(w.ScrollTrigger);

        var reduce = prefersReducedMotion();
        var scroller = scrollerEl();

        if (reduce) {
            $list[0].style.setProperty('--line-progress', '100%');
        } else {
            w.gsap.set($items, { autoAlpha: 0, y: 60 });
            $items.each(function (_, el) {
                w.ScrollTrigger.create({
                    trigger: el,
                    scroller: scroller,
                    start: 'top 80%',
                    onEnter: function () {
                        w.gsap.to(el, {
                            autoAlpha: 1,
                            y: 0,
                            duration: 0.9,
                            ease: 'power3.out'
                        });
                    },
                    onEnterBack: function () {
                        w.gsap.to(el, {
                            autoAlpha: 1,
                            y: 0,
                            duration: 0.9,
                            ease: 'power3.out'
                        });
                    }
                });
            });

            w.gsap.to($list, {
                '--line-progress': '100%',
                ease: 'none',
                scrollTrigger: {
                    trigger: $list[0],
                    scroller: scroller,
                    start: 'top 70%',
                    end: 'bottom 70%',
                    scrub: true
                }
            });
        }

        w.addEventListener('load', function () {
            setTimeout(refreshScroll, 120);
        }, { once: true });
    }

    $(initCont2);
    $(document).on('scrollengine:ready', initCont2);
})(window.jQuery, window);
