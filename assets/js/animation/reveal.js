/* 
---------------------------------------------
            Reveal + Hover Effects
---------------------------------------------
Usage (add classes to elements):
  - .reveal-up: scroll-up reveal
  - .reveal-group: stagger children (.reveal-item)
  - .reveal-left / .reveal-right: horizontal reveal (data-distance, data-duration)
  - .reveal-fade: fade-in reveal
  - .reveal-zoomwide: clip expand + .is-zoomed class
  - .reveal-zoomimg: zoom image (data-scale, data-start, data-end)
  - .hl-ink: underline bar (data-color, data-delay, data-duration)
  - .tilt-glare: hover tilt + glare (data-rx, data-ry, data-tint)

Init from common.js:
  window.Reveal?.init();
  window.TiltGlareInit?.();
*/
(function (w, d) {
    'use strict';

    if (!w.jQuery) return;
    var $ = w.jQuery;

    var SELECTOR_SINGLE = '.reveal-up';
    var SELECTOR_GROUP = '.reveal-group';
    var CHILD_SELECTOR = '.reveal-item';
    var SELECTOR_LEFT = '.reveal-left';
    var SELECTOR_RIGHT = '.reveal-right';
    var SELECTOR_ZOOMWIDE = '.reveal-zoomwide';
    var SELECTOR_FADE = '.reveal-fade';
    var SELECTOR_ZOOMIMG = '.reveal-zoomimg';
    var SELECTOR_INK = '.hl-ink';

    var isReduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function hasGsap() {
        return !!(w.gsap && w.ScrollTrigger);
    }

    function bound(el) {
        if (!el) return true;
        var $el = $(el);
        if ($el.data('revealBound')) return true;
        $el.data('revealBound', 1);
        return false;
    }

    function scroller() {
        if (w.scrollInstance && w.scrollInstance.__isLoco) {
            return $('[data-scroll-container]').get(0) || w;
        }
        return w;
    }

    function makeSingle(el) {
        if (bound(el)) return;
        var dist = Number(el && el.dataset && el.dataset.distance);
        var dur = Number(el && el.dataset && el.dataset.duration);
        var ease = (el && el.dataset && el.dataset.ease) || 'power3.out';
        if (!isFinite(dist)) dist = 50;
        if (!isFinite(dur)) dur = 0.55;

        w.gsap.set(el, { y: dist, autoAlpha: 0 });
        var tween = w.gsap.to(el, { y: 0, autoAlpha: 1, duration: dur, ease: ease, paused: true });
        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top 70%',
            once: true,
            onEnter: function () {
                tween.restart();
            }
        });
    }

    function makeGroup(group) {
        if (bound(group)) return;

        var $group = $(group);
        var $items = $group.find(CHILD_SELECTOR);
        if (!$items.length) return;

        var dist = Number(group && group.dataset && group.dataset.distance);
        var dur = Number(group && group.dataset && group.dataset.duration);
        var stagger = Number(group && group.dataset && group.dataset.stagger);
        var ease = (group && group.dataset && group.dataset.ease) || 'power3.out';
        if (!isFinite(dist)) dist = 50;
        if (!isFinite(dur)) dur = 0.55;
        if (!isFinite(stagger)) stagger = 0.12;

        var $splitTargets = $group.find('.split-text');
        $splitTargets.each(function () { splitCharsOnce($(this)); });

        var $descEl = $splitTargets.eq(0);
        var $headEl = $splitTargets.eq(1);

        w.gsap.set($items, { y: dist, autoAlpha: 0 });
        w.ScrollTrigger.create({
            trigger: group,
            scroller: scroller(),
            start: 'top 70%',
            onEnter: function () {
                w.gsap.to($items, {
                    y: 0,
                    autoAlpha: 1,
                    duration: dur,
                    ease: ease,
                    stagger: stagger
                });

                if ($splitTargets.length === 0) return;
                if ($splitTargets.length === 1) {
                    playSplitSequence($descEl, $descEl);
                    return;
                }
                playSplitSequence($descEl, $headEl);
            },
            once: true
        });
    }

    function makeHSingle(el, dir) {
        if (bound(el)) return;
        var dist = Number(el && el.dataset && el.dataset.distance) || 160;
        var dur = Number(el && el.dataset && el.dataset.duration) || 0.9;

        w.gsap.set(el, { x: dist * dir, autoAlpha: 0 });
        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top 70%',
            onEnter: function () {
                w.gsap.to(el, {
                    x: 0,
                    autoAlpha: 1,
                    duration: dur,
                    ease: 'power2.out',
                    clearProps: 'transform,opacity'
                });
            }
        });
    }

    function makeZoomWide(el) {
        if (bound(el)) return;

        var fromClip = 'polygon(15% 0%, 85% 0%, 85% 100%, 15% 100%)';
        var toClip = 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)';

        w.gsap.set(el, {
            clipPath: fromClip,
            overflow: 'hidden',
            css: { '-webkit-clip-path': fromClip }
        });

        var tl = w.gsap.timeline({ paused: true });
        tl.to(el, {
            clipPath: toClip,
            ease: 'none',
            duration: 1,
            css: { '-webkit-clip-path': toClip }
        }, 0);

        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top 85%',
            end: 'top center',
            scrub: 1.2,
            animation: tl
        });

        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top center',
            end: '+=1',
            toggleActions: 'play none none reverse',
            onEnter: function () { el.classList.add('is-zoomed'); },
            onEnterBack: function () { el.classList.add('is-zoomed'); },
            onLeaveBack: function () { el.classList.remove('is-zoomed'); }
        });
    }

    function makeZoomImg(el) {
        if (bound(el)) return;
        var img = el.querySelector('img, picture img');
        if (!img) return;

        var scaleTo = Number(el && el.dataset && el.dataset.scale) || 1.4;
        var startPos = (el && el.dataset && el.dataset.start) || 'top 75%';
        var endPos = (el && el.dataset && el.dataset.end) || 'top 35%';

        w.gsap.set(img, { scale: 1, transformOrigin: 'center center', willChange: 'transform' });
        var tween = w.gsap.to(img, { scale: scaleTo, ease: 'none', paused: true });

        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: startPos,
            end: endPos,
            scrub: 0.8,
            animation: tween
        });
    }

    function makeFade(el) {
        if (bound(el)) return;
        w.gsap.set(el, { autoAlpha: 0 });
        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top 70%',
            onEnter: function () {
                w.gsap.to(el, {
                    autoAlpha: 1,
                    duration: 0.7,
                    ease: 'power3.out',
                    clearProps: 'opacity,visibility'
                });
            },
            once: true
        });
    }

    function makeInk(el) {
        var bar = el.querySelector('.hl-ink-bar');
        if (!bar) {
            bar = d.createElement('span');
            bar.className = 'hl-ink-bar';
            var color = el.getAttribute('data-color');
            if (color) bar.style.background = color;
            el.appendChild(bar);
        }

        w.gsap.set(bar, { scaleX: 0.05, skewX: -10, transformOrigin: '0 50%' });
        w.ScrollTrigger.create({
            trigger: el,
            scroller: scroller(),
            start: 'top 85%',
            once: true,
            onEnter: function () {
                w.gsap.to(bar, {
                    delay: Number(el.getAttribute('data-delay') || 0.18),
                    duration: Number(el.getAttribute('data-duration') || 0.55),
                    ease: 'power2.out',
                    scaleX: 1,
                    skewX: -2
                });
            }
        });
    }

    function splitCharsOnce($el) {
        if ($el.data('splitDone')) return;
        $el.data('splitDone', 1);
        var raw = $el.text().replace(/\s+/g, ' ').trim();
        var html = '';
        for (var i = 0; i < raw.length; i++) {
            var ch = raw[i] === ' ' ? '&nbsp;' : raw[i];
            html += '<span class="char">' + ch + '</span>';
        }
        $el.html(html);
    }

    function playSplitSequence($descEl, $headEl) {
        var $descChars = $descEl.find('.char');
        var $headChars = $headEl.find('.char');
        var perChar = 25;
        var gapNext = 120;

        $descChars.each(function (i) {
            var $c = $(this);
            setTimeout(function () { $c.addClass('is-in'); }, i * perChar);
        });

        var baseDelay = ($descChars.length * perChar) + gapNext;
        $headChars.each(function (i) {
            var $c = $(this);
            setTimeout(function () { $c.addClass('is-in'); }, baseDelay + i * perChar);
        });
    }

    function revealInit() {
        if (isReduce && !$('body').data('revealForce')) return;
        if (!hasGsap()) return;
        if (w.gsap && w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        $(SELECTOR_SINGLE).each(function (_, el) { makeSingle(el); });
        $(SELECTOR_GROUP).each(function (_, el) { makeGroup(el); });
        $(SELECTOR_LEFT).each(function (_, el) { makeHSingle(el, -1); });
        $(SELECTOR_RIGHT).each(function (_, el) { makeHSingle(el, 1); });
        $(SELECTOR_ZOOMWIDE).each(function (_, el) { makeZoomWide(el); });
        $(SELECTOR_FADE).each(function (_, el) { makeFade(el); });
        $(SELECTOR_ZOOMIMG).each(function (_, el) { makeZoomImg(el); });
        $(SELECTOR_INK).each(function (_, el) { makeInk(el); });
    }

    w.Reveal = {
        init: revealInit,
        refresh: function () {
            if (w.ScrollTrigger) w.ScrollTrigger.refresh();
            if (w.scrollInstance && typeof w.scrollInstance.update === 'function') {
                w.scrollInstance.update();
            }
        }
    };
})(window, document);

/* ===== Tilt + Glare ===== */
(function (w) {
    'use strict';
    if (!w.jQuery) return;
    var $ = w.jQuery;
    var doc = w.document;

    function hexToRgb(str) {
        if (!str) return [255, 255, 255];
        if (/^rgb/i.test(str)) {
            var m = (str.match(/\d+(\.\d+)?/g) || []).slice(0, 3).map(Number);
            return (m.length === 3 ? m : [255, 255, 255]);
        }
        var c = str.replace('#', '').trim();
        if (c.length === 3) c = c.split('').map(function (x) { return x + x; }).join('');
        if (c.length !== 6) return [255, 255, 255];
        var n = parseInt(c, 16);
        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function bind($el, mouse) {
        if ($el.data('tgBound')) return;
        $el.data('tgBound', 1);
        if (!$el.attr('tabindex')) $el.attr('tabindex', '0');

        var $g = $el.children('.tg-glare');
        if (!$g.length) $g = $('<span class="tg-glare" aria-hidden="true"></span>').appendTo($el);

        var tint = $el.data('tint');
        if (tint) {
            var rgb = hexToRgb(tint);
            $g.css('background', 'radial-gradient(90px 90px at center, rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',.35), transparent 60%)');
        }

        var RX = Number($el.data('rx')) || 6;
        var RY = Number($el.data('ry')) || 8;
        var ax = 0, ay = 0, gx = -9999, gy = -9999, raf = 0, active = false;

        function loop() {
            if (!active) return;
            var r = $el[0].getBoundingClientRect();
            var px = mouse.x - r.left;
            var py = mouse.y - r.top;
            var nx = (px / r.width) - 0.5;
            var ny = (py / r.height) - 0.5;

            var tx = -ny * RX;
            var ty = nx * RY;

            ax += (tx - ax) * 0.22;
            ay += (ty - ay) * 0.22;

            $el[0].style.transform = 'translateZ(0) rotateX(' + ax + 'deg) rotateY(' + ay + 'deg)';

            var tgx = px - 80;
            var tgy = py - 80;
            gx += (tgx - gx) * 0.25;
            gy += (tgy - gy) * 0.25;
            $g[0].style.transform = 'translate(' + gx + 'px,' + gy + 'px)';

            raf = w.requestAnimationFrame(loop);
        }

        $el.on('mouseenter.tg focusin.tg', function () {
            active = true;
            w.cancelAnimationFrame(raf);
            raf = w.requestAnimationFrame(loop);
        });
        $el.on('mouseleave.tg focusout.tg', function () {
            active = false;
            w.cancelAnimationFrame(raf);
            ax = ay = 0;
            $el[0].style.transform = 'translateZ(0)';
        });
    }

    function tiltInit(root) {
        var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var isTouch = w.matchMedia && w.matchMedia('(pointer: coarse)').matches;
        if (reduce || isTouch) return;

        var mouse = { x: 0, y: 0 };
        $(w).off('mousemove.tg').on('mousemove.tg', function (e) {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
        });

        (root ? $(root) : $(doc)).find('.tilt-glare').each(function () {
            bind($(this), mouse);
        });
    }

    w.TiltGlareInit = tiltInit;
})(window);
