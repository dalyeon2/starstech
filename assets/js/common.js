/**
 * common.js
 * - Loads header/footer/subtop/cta components
 * - Smooth wheel scroll helper
 * - Header show/hide on scroll
 * - Subtop builder
 * - Scroll-to-top button
 */

// Keep page restoration consistent
window.scrollTo(0, 0);
window.addEventListener('pageshow', (e) => {
    if (e.persisted) requestAnimationFrame(() => window.scrollTo(0, 0));
});

/* ===== Global config ===== */
window.__snapOff = window.__snapOff || false;
window.__snapSetOff = window.__snapSetOff || function (v) { window.__snapOff = !!v; };
window.SancSmoothScrollConfig = window.SancSmoothScrollConfig || {};
if (typeof window.SancSmoothScrollConfig.lerp !== 'number') {
    window.SancSmoothScrollConfig.lerp = 0.03;
}
if (typeof window.SancSmoothScrollConfig.wheelMultiplier !== 'number') {
    window.SancSmoothScrollConfig.wheelMultiplier = 0.06;
}
if (typeof window.SancSmoothScrollConfig.mode !== 'string') {
    window.SancSmoothScrollConfig.mode = 'inertia';
}
if (typeof window.SancSmoothScrollConfig.friction !== 'number') {
    window.SancSmoothScrollConfig.friction = 0.94;
}
if (typeof window.SancSmoothScrollConfig.maxVelocity !== 'number') {
    window.SancSmoothScrollConfig.maxVelocity = 80;
}
if (typeof window.SancSmoothScrollConfig.respectReducedMotion !== 'boolean') {
    window.SancSmoothScrollConfig.respectReducedMotion = false;
}
if (!window.SancSmoothScrollConfig.scroller) {
    window.SancSmoothScrollConfig.scroller = '#main';
}

/* ===== Script loader ===== */
const COMMON_SCRIPT_URL = (function () {
    const script = document.currentScript || document.querySelector('script[src*="assets/js/common.js"]');
    return script && script.src ? script.src : '';
})();

function resolveCommonAssetPath(relativePath) {
    if (!COMMON_SCRIPT_URL) return relativePath;
    try {
        return new URL(relativePath, COMMON_SCRIPT_URL).href;
    } catch (e) {
        return relativePath;
    }
}

const __commonScriptCache = new Map();
function loadCommonScriptOnce(src) {
    if (!src) return Promise.resolve();
    if (__commonScriptCache.has(src)) return __commonScriptCache.get(src);
    const promise = new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => resolve();
        document.head.appendChild(script);
    });
    __commonScriptCache.set(src, promise);
    return promise;
}

/* ===== Components loader ===== */
async function loadComponents() {
    try {
        const isEN = location.pathname.startsWith('/en/');
        const compBase = isEN ? '/en/components' : '/components';

        const hasSubtop = !!$('#subtop-container').length;
        const hasCta = !!$('#cta-container').length;

        const [headerRes, footerRes, subtopRes, ctaRes] = await Promise.all([
            fetch(`${compBase}/header.html`),
            fetch(`${compBase}/footer.html`),
            hasSubtop ? fetch(`${compBase}/sub-top.html`) : Promise.resolve({ text: async () => '' }),
            hasCta ? fetch(`${compBase}/cta.html`) : Promise.resolve({ text: async () => '' })
        ]);

        $('#header-container').html(await headerRes.text());
        $('#footer-container').html(await footerRes.text());

        if (hasSubtop) $('#subtop-container').html(await subtopRes.text());
        if (hasCta) $('#cta-container').html(await ctaRes.text());
        setHeaderActive();

        $(document).trigger('components:ready');
        if (hasSubtop) window.Subtop?.init();
    } catch (err) {
        console.error(err);
    }
}

/* ===== Scroll engine setup ===== */
const initSmoothScroll = (function () {
    const COMMON_SCRIPT_SRC = (function () {
        const script = document.currentScript || document.querySelector('script[src*="assets/js/common.js"]');
        return script && script.src ? script.src : '';
    })();

    function resolveCommonAssetPath(relativePath) {
        if (!COMMON_SCRIPT_SRC) return relativePath;
        try {
            return new URL(relativePath, COMMON_SCRIPT_SRC).href;
        } catch (e) {
            return relativePath;
        }
    }

    const loadScriptOnce = (function () {
        const cache = new Map();
        return function loadScriptOnce(src) {
            if (!src) return Promise.resolve();
            if (cache.has(src)) return cache.get(src);

            const promise = new Promise((resolve) => {
                const script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = () => resolve();
                script.onerror = () => resolve();
                document.head.appendChild(script);
            });

            cache.set(src, promise);
            return promise;
        };
    })();

    function createFallbackScrollInstance() {
        const listeners = new Set();
        const getY = () => window.scrollY || document.documentElement.scrollTop || 0;

        function emit() {
            const y = getY();
            fallback.scroll.instance.scroll.y = y;
            listeners.forEach((fn) => fn({ scroll: { y } }));
        }

        const fallback = {
            __isSmooth: false,
            scroll: { instance: { scroll: { y: getY() } } },
            on(event, fn) {
                if (event !== 'scroll' || typeof fn !== 'function') return;
                listeners.add(fn);
            },
            off(event, fn) {
                if (event !== 'scroll' || typeof fn !== 'function') return;
                listeners.delete(fn);
            },
            scrollTo(y, opts = {}) {
                const target = typeof y === 'number' ? y : 0;
                const duration = typeof opts.duration === 'number' ? Math.max(0, opts.duration) : 0.6;
                window.scrollTo({ top: target, behavior: duration === 0 ? 'auto' : 'smooth' });
                emit();
            },
            update: emit,
            stop() {
            },
            start() {
            }
        };

        window.addEventListener('scroll', emit, { passive: true });
        return fallback;
    }

    return async function initSmoothScroll() {
        const src = resolveCommonAssetPath('animation/smooth-scroll.js');
        await loadScriptOnce(src);

        if (window.SancSmoothScroll && typeof window.SancSmoothScroll.init === 'function') {
            return window.SancSmoothScroll.init();
        }

        const fallback = createFallbackScrollInstance();
        window.scrollInstance = fallback;
        return fallback;
    };
})();

/* ===== Header scroll state ===== */
function initHeader(scrollBus) {
    const $header = $('.headerwrap');
    if (!$header.length) return;

    let scrollRootOverride = null;

    function getScrollMetrics() {
        const root = scrollRootOverride
            || document.scrollingElement
            || document.documentElement
            || document.body;
        const nativeY = Math.max(root.scrollTop || 0, window.scrollY || 0);
        const smoothY = (scrollBus && scrollBus.scroll?.instance?.scroll)
            ? (scrollBus.scroll.instance.scroll.y || 0)
            : 0;
        const y = Math.abs(smoothY) > Math.abs(nativeY) ? smoothY : nativeY;
        return { y, nativeY, smoothY, root };
    }

    function getScrollY() {
        return getScrollMetrics().y;
    }

    function getScrollRootEl() {
        if (scrollRootOverride) return scrollRootOverride;
        const scroller = window.SancSmoothScrollConfig?.scroller;
        if (typeof scroller === 'string') return document.querySelector(scroller);
        if (scroller && scroller.nodeType === 1) return scroller;
        return document.scrollingElement || document.documentElement || document.body;
    }

    function isMainScrollTarget(el) {
        if (!el || el === document || el === window) return false;
        const h = el.clientHeight || 0;
        const scrollable = (el.scrollHeight || 0) > h + 1;
        if (!scrollable) return false;
        return h >= window.innerHeight * 0.6;
    }

    function findMainScrollRoot(start) {
        let node = start;
        while (node && node !== document.body && node !== document.documentElement) {
            if (isMainScrollTarget(node)) return node;
            node = node.parentElement;
        }
        if (isMainScrollTarget(document.scrollingElement)) return document.scrollingElement;
        if (isMainScrollTarget(document.documentElement)) return document.documentElement;
        if (isMainScrollTarget(document.body)) return document.body;
        return null;
    }

    // Header style update
    function applyState(y) {
        const atTop = y <= 5;
        $header.toggleClass('scrolled', !atTop);
    }

    function handleScroll(y) {
        applyState(y);
    }

    handleScroll(getScrollY());

    const onScroll = () => {
        const metrics = getScrollMetrics();
        handleScroll(metrics.y);
    };
    let ticking = false;
    const schedule = () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            ticking = false;
            onScroll();
        });
    };
    const scrollRoot = getScrollRootEl();
    window.addEventListener('scroll', schedule, { passive: true });
    if (scrollRoot && scrollRoot !== window) {
        scrollRoot.addEventListener('scroll', schedule, { passive: true });
    }
    window.addEventListener('wheel', schedule, { passive: true });

    if (scrollBus && typeof scrollBus.on === 'function') {
        scrollBus.on('scroll', ({ scroll }) => handleScroll(scroll?.y || 0));
    }

    document.addEventListener('scroll', (e) => {
        const root = findMainScrollRoot(e.target);
        if (root) {
            if (scrollRootOverride !== root) scrollRootOverride = root;
            handleScroll(root.scrollTop || 0);
        }
    }, true);

    let lastObservedY = getScrollY();
    const watchScroll = () => {
        const y = getScrollY();
        if (y !== lastObservedY) {
            lastObservedY = y;
            handleScroll(y);
        }
        requestAnimationFrame(watchScroll);
    };
    requestAnimationFrame(watchScroll);
}

/* ===== Header Active ===== */
function setHeaderActive() {
    const rawCat = document.body.dataset.category || '';
    if (!rawCat) return;

    const want = rawCat.trim().toUpperCase();
    const $hdr = $('.headerwrap');
    if (!$hdr.length) return;

    const $items = $hdr.find('.nav .item');
    $items.removeClass('active')
        .find('a')
        .removeAttr('aria-current');

    $items.each(function () {
        const $li = $(this);
        const $a = $li.find('a').first();
        const label = ($a.text() || '').trim().toUpperCase();
        const menuKey = ($li.data('menu') || '').toString().trim().toUpperCase();

        if ((menuKey && menuKey === want) || label === want) {
            $li.addClass('active');
            $a.attr('aria-current', 'page');
        }
    });
}

/* ===== Subtop builder ===== */
window.Subtop = (function () {
    function init() {
        const $host = $('#subtop-container');
        if (!$host.length) return;

        const cat = (document.body.dataset.category || '').trim();
        const cur = (document.body.dataset.current || document.body.dataset.title || '').trim();
        const bg = (document.body.dataset.bg || '').trim();

        const $subtop = $host.find('.subtop').first();
        if (!$subtop.length) return;

        const bindHorizontalScroll = ($el) => {
            if (!$el.length) return;
            $el.off('wheel.subtabs').on('wheel.subtabs', function (e) {
                const evt = e.originalEvent;
                if (!evt) return;
                const canScroll = this.scrollWidth > this.clientWidth + 1;
                if (!canScroll) return;
                const dy = evt.deltaY || 0;
                const dx = evt.deltaX || 0;
                if (Math.abs(dy) <= Math.abs(dx)) return;
                this.scrollLeft += dy;
                e.preventDefault();
            });
        };

        // build nav items from header menu
        const $menuItems = $('.headerwrap .nav .item');
        let $targetMenu = $();
        $menuItems.each(function () {
            const $item = $(this);
            const menuKey = ($item.data('menu') || '').toString().trim();
            const label = ($item.children('a').first().text() || '').trim();
            if ((menuKey && menuKey.toLowerCase() === cat.toLowerCase()) || label.toLowerCase() === cat.toLowerCase()) {
                $targetMenu = $item;
            }
        });

        const $submenuLinks = $targetMenu.find('.panel .link');

        // breadcrumb
        const $crumbCategory = $subtop.find('.subtop-category');
        const $crumbCurrent = $subtop.find('.subtop-current');
        if ($crumbCategory.length) {
            const menuLabel = $targetMenu.length ? $targetMenu.children('a').first().text().trim() : '';
            const catText = menuLabel || cat;
            if (catText) {
                $crumbCategory.text(catText).attr('href', $targetMenu.children('a').attr('href') || '#');
            } else {
                $crumbCategory.remove();
            }
        }
        if ($crumbCurrent.length) {
            $crumbCurrent.text(cur || document.title || '').attr('href', '#');
        }

        // title
        const $title = $subtop.find('.subtop-title');
        if ($title.length) {
            $title.text(cur || document.title || '');
        }

        // tabs
        const $tabs = $subtop.find('.subtabs');
        const $dropdown = $subtop.find('[data-subtabs].subtabs-dropdown');
        const $list = $dropdown.find('.subtabs-list');
        const $label = $dropdown.find('.subtabs-label');
        $tabs.empty();
        $list.empty();

        if ($submenuLinks.length) {
            let activeLabel = cur;

            $submenuLinks.each(function () {
                const label = ($(this).text() || '').trim();
                const href = $(this).attr('href') || '#';
                const isActive = label.toLowerCase() === cur.toLowerCase();

                // desktop tabs
                const $a = $('<a></a>').attr('href', href).text(label);
                if (isActive) {
                    $a.addClass('is-active').attr('aria-current', 'page');
                    activeLabel = label;
                }
                $tabs.append($a);

                // mobile dropdown list
                const $li = $('<li></li>');
                const $option = $('<a></a>')
                    .attr('href', href)
                    .attr('role', 'option')
                    .attr('aria-selected', isActive ? 'true' : 'false')
                    .text(label);
                $li.append($option);
                $list.append($li);
            });

            // set dropdown label to active or first item
            if ($label.length) {
                const firstLabel = $submenuLinks.first().text().trim();
                $label.text(activeLabel || firstLabel || '');
            }

            $subtop.find('.subtop-nav').show();
            bindHorizontalScroll($tabs);
        } else {
            $subtop.find('.subtop-nav').hide();
        }

        // background
        if (bg) {
            $subtop.css('background-image', 'url(' + bg + ')');
        }

        // reveal once
        const io = new IntersectionObserver(function (entries, ob) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    $subtop.addClass('visible');
                    ob.unobserve(e.target);
                }
            });
        }, { threshold: 0.25 });

        io.observe($subtop[0]);
    }
    return { init: init };
})();

/* ===== Scroll-to-top button ===== */
function initScrollTop(scrollBus) {
    const $btn = $("#scrollTopBtn");
    if (!$btn.length) return;

    const scrollTopDuration = 1.0;
    const scrollTopEase = (t) => (
        t < 0.5
            ? 4 * t * t * t
            : 1 - Math.pow(-2 * t + 2, 3) / 2
    );
    const durationToMs = (duration) => {
        if (typeof duration !== 'number' || !isFinite(duration) || duration <= 0) return 0;
        return duration < 10 ? duration * 1000 : duration;
    };

    // Button dock settings
    const btnEl = $btn.get(0);
    const dockGap = 24;
    let scrollRootOverride = null;

    const parsePx = (value) => {
        const num = parseFloat(value);
        return Number.isFinite(num) ? num : 0;
    };

    const getBaseInset = () => {
        const style = window.getComputedStyle(btnEl);
        const inset = style.getPropertyValue('inset-block-end') || style.insetBlockEnd || '';
        const bottom = style.getPropertyValue('bottom') || style.bottom || '';
        return parsePx(inset) || parsePx(bottom);
    };

    // Footer overlap docking
    const updateDock = () => {
        const footerEl = document.querySelector('.footer');
        if (!footerEl || !btnEl) {
            btnEl?.style.setProperty('--scroll-top-dock', '0px');
            return;
        }
        if (!$btn.hasClass('show')) {
            btnEl.style.setProperty('--scroll-top-dock', '0px');
            return;
        }
        const baseInset = getBaseInset();
        const footerRect = footerEl.getBoundingClientRect();
        const viewportH = window.innerHeight || document.documentElement.clientHeight || 0;
        const overlap = Math.max(0, viewportH - baseInset - footerRect.top + dockGap);
        btnEl.style.setProperty('--scroll-top-dock', `${overlap}px`);
    };

    // Active scroll position
    const getScrollY = () => {
        const root = scrollRootOverride
            || document.scrollingElement
            || document.documentElement
            || document.body;
        const nativeY = Math.max(root.scrollTop || 0, window.scrollY || 0);
        const smoothY = (scrollBus && scrollBus.scroll?.instance?.scroll)
            ? (scrollBus.scroll.instance.scroll.y || 0)
            : 0;
        return Math.abs(smoothY) > Math.abs(nativeY) ? smoothY : nativeY;
    };

    // Main scroll element
    const isMainScrollTarget = (el) => {
        if (!el || el === document || el === window) return false;
        const h = el.clientHeight || 0;
        const scrollable = (el.scrollHeight || 0) > h + 1;
        if (!scrollable) return false;
        return h >= window.innerHeight * 0.6;
    };

    // Scroll root lookup
    const findMainScrollRoot = (start) => {
        let node = start;
        while (node && node !== document.body && node !== document.documentElement) {
            if (isMainScrollTarget(node)) return node;
            node = node.parentElement;
        }
        if (isMainScrollTarget(document.scrollingElement)) return document.scrollingElement;
        if (isMainScrollTarget(document.documentElement)) return document.documentElement;
        if (isMainScrollTarget(document.body)) return document.body;
        return null;
    };

    // Toggle button visibility
    const toggle = (y) => {
        $btn.toggleClass("show", y >= 50);
        updateDock();
    };
    toggle(getScrollY());

    if (scrollBus && typeof scrollBus.on === 'function') {
        scrollBus.on('scroll', ({ scroll }) => toggle(scroll?.y || 0));
    }
    window.addEventListener('scroll', () => toggle(getScrollY()), { passive: true });
    window.addEventListener('wheel', () => {
        requestAnimationFrame(() => toggle(getScrollY()));
    }, { passive: true });
    document.addEventListener('scroll', (e) => {
        const root = findMainScrollRoot(e.target);
        if (!root) return;
        if (scrollRootOverride !== root) scrollRootOverride = root;
        toggle(root.scrollTop || 0);
    }, true);
    window.addEventListener('resize', updateDock, { passive: true });
    window.addEventListener('load', updateDock, { once: true });

    $btn.on("click", function (e) {
        e.preventDefault();
        window.__snapSetOff(true);
        const duration = scrollTopDuration;
        const snapReleaseMs = durationToMs(duration) + 50;
        if (scrollBus && typeof scrollBus.scrollTo === 'function') {
            scrollBus.scrollTo(0, { duration, ease: scrollTopEase });
            setTimeout(() => window.__snapSetOff(false), snapReleaseMs);
        } else {
            window.scrollTo({ top: 0, behavior: "smooth" });
            setTimeout(() => window.__snapSetOff(false), snapReleaseMs || 300);
        }
    });
}

/* ===== Footer popup ===== */
function initFooterPopup() {
    const $wraps = $('.footer .family');
    if (!$wraps.length) return;

    const closeAll = () => {
        $wraps.removeClass('open');
        $wraps.find('.btn').attr('aria-expanded', 'false');
        $wraps.find('.panel').attr('aria-hidden', 'true');
    };

    $wraps.each(function () {
        const $wrap = $(this);
        const $btn = $wrap.find('.btn').first();
        const $panel = $wrap.find('.panel').first();

        $btn.off('click.family').on('click.family', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const wasOpen = $wrap.hasClass('open');
            closeAll();
            if (!wasOpen) {
                $wrap.addClass('open');
                $btn.attr('aria-expanded', 'true');
                $panel.attr('aria-hidden', 'false');
            }
        });
    });

    $(document).off('click.family').on('click.family', function (e) {
        if ($(e.target).closest('.footer .family').length) return;
        closeAll();
    });

    $(document).off('keydown.family').on('keydown.family', function (e) {
        if (e.key === 'Escape') closeAll();
    });
}

/* ===== Boot ===== */
$(async function () {
    await loadComponents();
    await loadCommonScriptOnce(resolveCommonAssetPath('animation/reveal.js'));

    const scroller = await initSmoothScroll();
    if (scroller) {
        window.scrollInstance = scroller;
    }
    if (!window.__scrollEngineReady) {
        window.__scrollEngineReady = true;
        const engine = scroller?.__isSmooth ? 'smooth' : 'native';
        $(document).trigger('scrollengine:ready', { engine });
    }
    initHeader(scroller);
    initScrollTop(scroller);
    initFooterPopup();
    window.Reveal?.init?.();
    window.TiltGlareInit?.();

    if (typeof ScrollTrigger !== 'undefined') {
        ScrollTrigger.refresh();
    }
});
