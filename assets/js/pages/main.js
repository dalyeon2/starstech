/**
 * Main Page
 */
(function ($, w, d) {
    function prefersReducedMotion() {
        return w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /* Section 1: intro splash */
    function initIntroSplash() {
        const INTRO_TOTAL_MS = 2100;
        const INTRO_REVEAL_DELAY_MS = 200;
        const INTRO_DURATION_MS = 1000;
        const INTRO_EXIT_MS = 600;

        let isLocked = false;
        const $body = $('body');
        const useGsap = !!w.gsap;

        function markIntroActive() {
            $body.addClass('intro-active').removeClass('intro-exit intro-done');
        }

        function markIntroDone() {
            $body.addClass('intro-done').removeClass('intro-active intro-exit');
        }

        function scrollToSection2(instant) {
            const $section = $('.cont2');
            if (!$section.length) return;
            const top = $section.offset().top || 0;

            if (w.scrollInstance && typeof w.scrollInstance.scrollTo === 'function') {
                if (typeof w.scrollInstance.start === 'function') {
                    w.scrollInstance.start();
                }
                w.scrollInstance.scrollTo(top, { duration: instant ? 0 : 0.8 });
                return;
            }

            w.scrollTo({ top, behavior: instant ? 'auto' : 'smooth' });
        }

        function lockIntro() {
            if (isLocked) return;
            isLocked = true;
            $('html, body').addClass('intro-lock');
            $(d).on('wheel.intro touchmove.intro', function (e) { e.preventDefault(); });
            $(d).on('keydown.intro', function (e) {
                const keys = ['ArrowDown', 'ArrowUp', 'PageDown', 'PageUp', 'Home', 'End', ' '];
                if (keys.indexOf(e.key) > -1) e.preventDefault();
            });
            if (w.scrollInstance && typeof w.scrollInstance.stop === 'function') {
                w.scrollInstance.stop();
            }
        }

        function unlockIntro() {
            if (!isLocked) return;
            isLocked = false;
            $('html, body').removeClass('intro-lock');
            $(d).off('.intro');
            if (w.scrollInstance && typeof w.scrollInstance.start === 'function') {
                w.scrollInstance.start();
            }
        }

        function finishIntro(instant) {
            unlockIntro();
            $body.addClass('intro-exit').removeClass('intro-active');
            scrollToSection2(instant);
            w.setTimeout(function () {
                markIntroDone();
            }, prefersReducedMotion() ? 0 : INTRO_EXIT_MS);
        }

        function playIntroReveal($slogan) {
            if (useGsap && $slogan.length) {
                const sloganEl = $slogan.get(0);
                w.gsap.killTweensOf(sloganEl);
                w.gsap.to(sloganEl, {
                    autoAlpha: 1,
                    y: 0,
                    duration: INTRO_DURATION_MS / 1000,
                    ease: 'power3.out',
                    force3D: true,
                    overwrite: 'auto'
                });
                return;
            }
            $body.addClass('intro-css');
            requestAnimationFrame(function () {
                $slogan.addClass('is-in');
            });
        }

        function runIntro() {
            const $cont1 = $('.cont1');
            const $slogan = $cont1.find('.slogan').first();
            if (!$cont1.length || !$slogan.length) {
                markIntroDone();
                scrollToSection2(true);
                return;
            }

            lockIntro();
            $cont1.addClass('is-intro');
            if (useGsap && $slogan.length) {
                w.gsap.set($slogan.get(0), { autoAlpha: 0, y: 48 });
            }

            w.setTimeout(function () {
                playIntroReveal($slogan);
            }, INTRO_REVEAL_DELAY_MS);

            if (prefersReducedMotion()) {
                finishIntro(true);
                return;
            }

            const totalDelay = INTRO_TOTAL_MS;
            setTimeout(function () {
                finishIntro(false);
            }, totalDelay);
        }

        function initIntro() {
            const $cont1 = $('.cont1');
            const $cont2 = $('.cont2');
            if (!$cont1.length || !$cont2.length) {
                markIntroDone();
                return;
            }
            runIntro();
        }

        if (useGsap) {
            $body.removeClass('intro-css');
        }
        markIntroActive();
        initIntro();

        $(d).on('scrollengine:ready', function () {
            if (isLocked && w.scrollInstance && typeof w.scrollInstance.stop === 'function') {
                w.scrollInstance.stop();
            }
        });
    }

    /* Section 2: hero slider */
    function initHero() {
        const SLIDE_INTERVAL_MS = 6000;
        const SLIDE_FADE_MS = 800;

        function parseImages(raw) {
            if (!raw) return [];
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return parsed.filter(Boolean);
            } catch (e) {
            }
            return raw.split(',').map(function (item) { return item.trim(); }).filter(Boolean);
        }

        const section = d.querySelector('.cont2');
        if (!section) return;

        const container = section.querySelector('.container');
        if (!container) return;

        const images = parseImages(section.getAttribute('data-imgs') || container.getAttribute('data-imgs'));
        if (!images.length) return;

        let main = container.querySelector('.img.main');
        let next = container.querySelector('.img.next');
        if (!main || !next) return;

        const bars = Array.prototype.slice.call(container.querySelectorAll('.bar'));
        let index = 0;
        let intervalId = null;
        let transitionTimer = null;
        const fadeMs = prefersReducedMotion() ? 0 : SLIDE_FADE_MS;

        function setImage(el, src) {
            el.style.backgroundImage = 'url(' + src + ')';
        }

        function setBar(active) {
            if (!bars.length) return;
            bars.forEach(function (bar, i) {
                bar.classList.toggle('on', i === active);
            });
        }

        setImage(main, images[0]);
        setImage(next, images[0]);
        setBar(0);

        function clearTransition() {
            if (!transitionTimer) return;
            w.clearTimeout(transitionTimer);
            transitionTimer = null;
        }

        function startAuto() {
            if (intervalId) w.clearInterval(intervalId);
            intervalId = w.setInterval(function () {
                swapTo(index + 1, false);
            }, SLIDE_INTERVAL_MS);
        }

        function swapTo(targetIndex, resetTimer) {
            const nextIndex = ((targetIndex % images.length) + images.length) % images.length;
            if (nextIndex === index) return;
            clearTransition();
            setBar(nextIndex);

            if (!fadeMs) {
                setImage(main, images[nextIndex]);
                index = nextIndex;
                if (resetTimer) startAuto();
                return;
            }

            next.classList.remove('show');
            setImage(next, images[nextIndex]);
            next.classList.add('show');

            transitionTimer = w.setTimeout(function () {
                main.classList.remove('main');
                next.classList.remove('show');
                next.classList.add('main');
                const temp = main;
                main = next;
                next = temp;
                index = nextIndex;
            }, fadeMs);

            if (resetTimer) startAuto();
        }

        bars.forEach(function (bar, i) {
            bar.addEventListener('click', function () {
                if (i >= images.length) return;
                swapTo(i, true);
            });
        });

        if (images.length < 2) return;
        startAuto();
    }

    /* Section 3: pin */
    function initMediaScroll() {
        let section3Trigger = null;
        let section3TextTween = null;
        let section3BgTween = null;
        let section3PicTweens = [];
        let section3ResizeTimer = null;
        let section3ScrollSyncBound = false;

        if (!w.gsap || !w.ScrollTrigger) return;

        const $section = $('.cont3');
        const $stage = $section.find('.stage');
        const $inner = $section.find('.inner');
        const $pics = $section.find('.pic');
        if (!$section.length || !$stage.length || !$inner.length) return;

        w.gsap.registerPlugin(w.ScrollTrigger);

        function getScroller() {
            if (w.scrollInstance && w.scrollInstance.__isLoco) {
                return $('[data-scroll-container]').get(0) || w;
            }
            const cfg = w.SancSmoothScrollConfig || {};
            if (typeof cfg.scroller === 'string') {
                const el = d.querySelector(cfg.scroller);
                if (el && el !== d.documentElement && el !== d.body && el !== d.scrollingElement) return el;
            } else if (cfg.scroller && cfg.scroller.nodeType === 1) {
                const el = cfg.scroller;
                if (el !== d.documentElement && el !== d.body && el !== d.scrollingElement) return el;
            }
            return w;
        }

        function killAnimations() {
            if (section3Trigger) {
                section3Trigger.kill();
                section3Trigger = null;
            }
            if (section3TextTween) {
                section3TextTween.kill();
                section3TextTween = null;
            }
            if (section3BgTween) {
                section3BgTween.kill();
                section3BgTween = null;
            }
            if (section3PicTweens.length) {
                section3PicTweens.forEach(function (tween) {
                    if (tween) tween.kill();
                });
                section3PicTweens = [];
            }
        }

        function build() {
            const pics = $pics.toArray();
            const innerEl = $inner.get(0);
            const stageEl = $stage.get(0);
            const pinStartEl = pics[0] || innerEl;
            const pinEndEl = pics.length ? pics[pics.length - 1] : stageEl;
            const isSmall = w.matchMedia && w.matchMedia('(max-width: 1024px)').matches;
            const isReduce = prefersReducedMotion();

            killAnimations();

            if (w.gsap) {
                w.gsap.set([innerEl].concat(pics), { clearProps: 'opacity,visibility,transform' });
            }

            if (isSmall) return;

            const scrollerEl = getScroller();

            section3Trigger = w.ScrollTrigger.create({
                trigger: pinStartEl,
                start: 'center center',
                endTrigger: pinEndEl,
                end: 'center center',
                pin: innerEl,
                pinSpacing: true,
                anticipatePin: 1,
                scroller: scrollerEl,
                invalidateOnRefresh: true
            });

            if (!isReduce) {
                section3TextTween = w.gsap.fromTo(innerEl, { autoAlpha: 0, y: -60 }, {
                    autoAlpha: 1,
                    y: 0,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: pinStartEl,
                        start: 'top bottom',
                        end: 'center center',
                        scrub: 0.9,
                        scroller: scrollerEl,
                        invalidateOnRefresh: true
                    }
                });

                if (pics.length) {
                    pics.forEach(function (pic) {
                        const tween = w.gsap.fromTo(pic, { autoAlpha: 0, y: -50 }, {
                            autoAlpha: 1,
                            y: 0,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: pic,
                                start: 'top bottom',
                                end: 'top center',
                                scrub: 1.1,
                                scroller: scrollerEl,
                                invalidateOnRefresh: true
                            }
                        });
                        section3PicTweens.push(tween);
                    });
                }
            }

            section3BgTween = null;

            w.ScrollTrigger.refresh();
            if (w.scrollInstance && typeof w.scrollInstance.update === 'function') {
                w.scrollInstance.update();
            }
        }

        build();

        if (!section3ScrollSyncBound && w.ScrollTrigger) {
            section3ScrollSyncBound = true;
            if (w.scrollInstance && typeof w.scrollInstance.on === 'function') {
                w.scrollInstance.on('scroll', function () {
                    w.ScrollTrigger.update();
                });
            }
            w.addEventListener('scroll', function () {
                w.ScrollTrigger.update();
            }, { passive: true });
        }

        $(d).on('scrollengine:ready', function () {
            build();
        });

        $(w).on('load', function () {
            w.ScrollTrigger.refresh();
            if (w.scrollInstance && typeof w.scrollInstance.update === 'function') {
                w.scrollInstance.update();
            }
        });

        $(w).on('resize.cont3', function () {
            if (section3ResizeTimer) w.clearTimeout(section3ResizeTimer);
            section3ResizeTimer = w.setTimeout(build, 200);
        });
    }

    /* Section 7: snap */
    function initSnap() {
        const SELECTOR = '.cont7 .panel';
        const SECTION_SELECTOR = '.cont7';
        const BREAKPOINT = 1024;
        const ENTRY_DOWN_THRESH = 0.7;
        const ENTRY_UP_THRESH = 0.5;
        const DOWN_THRESH = 0.2;
        const UP_THRESH = 0.2;
        const MIN_DY = 2;
        const RANGE_MIN = 0.01;
        const ALIGN_TOL = 6;
        let activeRoot = null;
        let lastInputDir = 0;
        let lastInputAt = 0;
        let lastSnapAt = 0;
        const SNAP_COOLDOWN = 650;

        function getScrollBus() {
            if (w.scrollInstance && typeof w.scrollInstance.on === 'function') return w.scrollInstance;
            return null;
        }

        function isMainScrollTarget(el) {
            if (!el || el === d || el === w) return false;
            const h = el.clientHeight || 0;
            const scrollable = (el.scrollHeight || 0) > h + 1;
            if (!scrollable) return false;
            return h >= (w.innerHeight || 0) * 0.6;
        }

        function resolveRootFromEvent(e) {
            const t = e && e.target;
            if (isMainScrollTarget(t)) {
                activeRoot = t;
            }
        }

        function getScrollRoot() {
            if (activeRoot) return activeRoot;
            const cfg = w.SancSmoothScrollConfig || {};
            let scroller = cfg.scroller;
            if (typeof scroller === 'string') scroller = d.querySelector(scroller);
            if (scroller && scroller.nodeType === 1) return scroller;
            return d.scrollingElement || d.documentElement || d.body;
        }

        function readScrollTop(root) {
            if (!root || root === w || root === d) {
                return w.scrollY || d.documentElement.scrollTop || d.body.scrollTop || 0;
            }
            if (root === d.documentElement || root === d.body || root === d.scrollingElement) {
                return root.scrollTop || w.scrollY || 0;
            }
            return root.scrollTop || 0;
        }

        function getScrollY(bus) {
            const root = getScrollRoot();
            const rootY = readScrollTop(root);
            const winY = w.scrollY || d.documentElement.scrollTop || d.body.scrollTop || 0;
            let busY = 0;
            if (bus && bus.scroll && bus.scroll.instance && bus.scroll.instance.scroll) {
                busY = bus.scroll.instance.scroll.y || 0;
            }
            return Math.max(rootY, winY, busY);
        }

        function getViewportRect() {
            const root = getScrollRoot();
            if (!root) return { top: 0, bottom: w.innerHeight || 0 };
            const rect = root.getBoundingClientRect();
            return { top: rect.top, bottom: rect.bottom };
        }

        function visibleRatio(el, viewport) {
            const rect = el.getBoundingClientRect();
            const overlap = Math.max(0, Math.min(rect.bottom, viewport.bottom) - Math.max(rect.top, viewport.top));
            return overlap / Math.max(1, rect.height || 1);
        }

        function maxVisibleIndex(panels, viewport, ratios) {
            let best = 0;
            let bestR = -1;
            for (let i = 0; i < panels.length; i++) {
                const ratio = (ratios && ratios.length === panels.length)
                    ? ratios[i]
                    : visibleRatio(panels[i], viewport);
                if (ratio > bestR) {
                    bestR = ratio;
                    best = i;
                }
            }
            return { index: best, ratio: bestR };
        }

        function isAligned(el, viewport) {
            if (!el) return false;
            const rect = el.getBoundingClientRect();
            return Math.abs(rect.top - viewport.top) <= ALIGN_TOL;
        }

        function setInputDir(delta) {
            if (!delta) return;
            lastInputDir = delta > 0 ? 1 : -1;
            lastInputAt = Date.now();
        }

        function canSnapNow() {
            return Date.now() - lastSnapAt > SNAP_COOLDOWN;
        }

        function build() {
            if (w.innerWidth < BREAKPOINT) return;
            const bus = getScrollBus();
            if (!bus) return;

            const $section = $(SECTION_SELECTOR);
            const $panels = $(SELECTOR);
            if (!$section.length || $panels.length < 2) return;

            let snapping = false;
            let lastY = getScrollY(bus);
            let seenScroll = false;
            let hasEntrySnap = false;

            function trySnap(dir, source) {
                if (!dir) return;
                if (!canSnapNow()) return;
                if (w.__snapOff || snapping) return;

                const viewport = getViewportRect();
                const panels = $panels.toArray();
                const ratios = panels.map(function (panel) {
                    return visibleRatio(panel, viewport);
                });
                const curState = maxVisibleIndex(panels, viewport, ratios);
                if (curState.ratio <= RANGE_MIN) {
                    hasEntrySnap = false;
                    return;
                }

                const curIdx = curState.index;
                const forward = dir > 0;
                const firstIdx = 0;
                const lastIdx = panels.length - 1;

                if (!hasEntrySnap) {
                    if (forward && ratios[firstIdx] >= ENTRY_DOWN_THRESH) {
                        if (!isAligned(panels[firstIdx], viewport)) {
                            lastSnapAt = Date.now();
                            hasEntrySnap = true;
                            goTo(firstIdx);
                            return;
                        }
                        hasEntrySnap = true;
                    } else if (!forward && ratios[lastIdx] >= ENTRY_UP_THRESH) {
                        if (!isAligned(panels[lastIdx], viewport)) {
                            lastSnapAt = Date.now();
                            hasEntrySnap = true;
                            goTo(lastIdx);
                            return;
                        }
                        hasEntrySnap = true;
                    }
                }

                if (curState.ratio < 0.1) return;

                const target = forward ? Math.min(lastIdx, curIdx + 1) : Math.max(0, curIdx - 1);
                if (target === curIdx) return;

                const ratio = ratios[target];
                const thresh = forward ? DOWN_THRESH : UP_THRESH;
                if (ratio >= thresh) {
                    lastSnapAt = Date.now();
                    hasEntrySnap = true;
                    goTo(target);
                }
            }

            function goTo(i) {
                if (i < 0 || i >= $panels.length) return;
                console.log('[cont7 snap] goTo', i);
                snapping = true;
                if (w.__snapSetOff) w.__snapSetOff(true);
                bus.scrollTo($panels.get(i), {
                    duration: 0.6,
                    disableLerp: false,
                    callback: function () {
                        snapping = false;
                        lastY = getScrollY(bus);
                        console.log('[cont7 snap] done', i);
                        setTimeout(function () {
                            if (w.__snapSetOff) w.__snapSetOff(false);
                        }, 5);
                    }
                });
            }

            function onScroll(e) {
                resolveRootFromEvent(e);
                if (!seenScroll) seenScroll = true;
                if (w.__snapOff) {
                    lastY = getScrollY(bus);
                    return;
                }
                if (snapping) {
                    lastY = getScrollY(bus);
                    return;
                }

                const y = getScrollY(bus);
                const dy = y - lastY;
                if (Math.abs(dy) < MIN_DY) {
                    const recent = Date.now() - lastInputAt < 200;
                    if (recent && lastInputDir) {
                        trySnap(lastInputDir, 'wheel-fallback');
                    }
                    return;
                }
                lastY = y;

                setInputDir(dy);
                trySnap(dy, 'scroll');
            }

            function onWheel(e) {
                if (!e) return;
                resolveRootFromEvent(e);
                if (typeof e.deltaY !== 'number' || e.deltaY === 0) return;
                setInputDir(e.deltaY);
                trySnap(e.deltaY, 'wheel');
            }

            const rootEl = getScrollRoot();
            bus.on('scroll', onScroll);
            w.addEventListener('scroll', onScroll, { passive: true });
            w.addEventListener('wheel', onWheel, { passive: true });
            d.addEventListener('wheel', onWheel, { passive: true, capture: true });
            if (rootEl) {
                rootEl.addEventListener('scroll', onScroll, { passive: true });
                rootEl.addEventListener('wheel', onWheel, { passive: true });
            }
            console.log('[cont7 snap] bound', { panels: $panels.length, root: !!rootEl, rootTag: rootEl ? rootEl.tagName : null });

            function onResize() {
                if (w.innerWidth < BREAKPOINT) { cleanup(); return; }
            }

            w.addEventListener('resize', onResize, { passive: true });

            function cleanup() {
                try { bus.off('scroll', onScroll); } catch (e) { }
                w.removeEventListener('scroll', onScroll);
                w.removeEventListener('wheel', onWheel);
                d.removeEventListener('wheel', onWheel, { capture: true });
                if (rootEl) rootEl.removeEventListener('scroll', onScroll);
                if (rootEl) rootEl.removeEventListener('wheel', onWheel);
                w.removeEventListener('resize', onResize);
            }

            if (w.__cont7SnapCleanup) w.__cont7SnapCleanup();
            w.__cont7SnapCleanup = cleanup;
        }

        function init() {
            if (w.innerWidth < BREAKPOINT) return;
            if (getScrollBus()) { build(); return; }
            $(d).one('scrollengine:ready', function () {
                if (w.innerWidth >= BREAKPOINT) build();
            });
        }

        w.addEventListener('resize', function () {
            if (w.innerWidth < BREAKPOINT) {
                w.__cont7SnapCleanup && w.__cont7SnapCleanup();
            } else if (getScrollBus()) {
                build();
            }
        }, { passive: true });

        $(init);
    }

    $(function () {
        initHero();
        initIntroSplash();
        initMediaScroll();
    });
})(window.jQuery, window, document);
