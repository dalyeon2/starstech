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

/* ===== Path helpers ===== */
function normalizeBasePath(base) {
    if (!base || base === '/') return '';
    return base.replace(/\/+$/, '');
}

function resolveSiteBasePath() {
    if (!COMMON_SCRIPT_URL) return '';
    try {
        const url = new URL(COMMON_SCRIPT_URL, window.location.href);
        const pathname = url.pathname || '';
        const idx = pathname.indexOf('/assets/');
        if (idx >= 0) return normalizeBasePath(pathname.slice(0, idx));
    } catch (e) {
        return '';
    }
    return '';
}

function joinPath(base, path) {
    const safeBase = normalizeBasePath(base);
    const safePath = path.startsWith('/') ? path : `/${path}`;
    if (!safeBase) return safePath;
    return `${safeBase}${safePath}`;
}

function stripBasePath(pathname) {
    const base = normalizeBasePath(resolveSiteBasePath());
    if (base && pathname.startsWith(base)) {
        const rest = pathname.slice(base.length);
        return rest || '/';
    }
    return pathname || '/';
}

function stripLangPrefix(pathname, lang) {
    const code = (lang || '').toLowerCase();
    if (!code) return pathname;
    const lower = pathname.toLowerCase();
    const prefix = `/${code}`;
    if (lower === prefix) return '/';
    if (lower.startsWith(prefix + '/')) {
        const rest = pathname.slice(prefix.length);
        return rest || '/';
    }
    return pathname;
}

function getPathWithoutBase() {
    return stripBasePath(window.location.pathname || '/');
}

function getRelativePathForLang(lang) {
    const withoutBase = window.__htmlPath || getPathWithoutBase();
    return stripLangPrefix(withoutBase, lang) || '/';
}

function buildLangBase(lang) {
    const base = resolveSiteBasePath();
    const code = (lang || '').toLowerCase();
    if (!code) return normalizeBasePath(base);
    return joinPath(base, '/' + code);
}

function isExternalHref(href) {
    return /^(?:[a-z][a-z0-9+.-]*:|#)/i.test(href);
}

function normalizeAssetSrc(src) {
    if (!src || isExternalHref(src)) return src;
    const cleaned = src.replace(/\\/g, '/');
    const idx = cleaned.indexOf('/assets/');
    if (idx >= 0) {
        const assetPath = cleaned.slice(idx);
        return joinPath(resolveSiteBasePath(), assetPath);
    }
    const relIdx = cleaned.indexOf('assets/');
    if (relIdx >= 0) {
        const assetPath = '/' + cleaned.slice(relIdx);
        return joinPath(resolveSiteBasePath(), assetPath);
    }
    return src;
}

function slugifyLabel(text) {
    if (!text) return '';
    const raw = String(text).trim().toLowerCase();
    if (!raw) return '';
    const normalized = raw.normalize ? raw.normalize('NFKD') : raw;
    const noApos = normalized.replace(/['\u2019]/g, '');
    const noDiacritics = noApos.replace(/[\u0300-\u036f]/g, '');
    return noDiacritics
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/* ===== Language helpers ===== */
function resolveFooterLang() {
    const fromBody = $('body').data('footer-lang') || '';
    const fromHtml = $('html').attr('lang') || '';
    const match = getPathWithoutBase().match(/^\/([a-z]{2})(?:\/|$)/i);
    const fromPath = match ? match[1] : '';
    const raw = fromBody || fromHtml || fromPath;

    if (!raw) return '';
    const normalized = $.trim(String(raw)).toLowerCase().replace('_', '-');
    const base = normalized.split('-')[0];
    if (base === 'kr') return 'ko';
    return base;
}

/* ===== Language menu ===== */
function initLangMenu() {
    const $wrap = $('.header .lang-select');
    if (!$wrap.length) return;

    const $btn = $wrap.find('.lang');
    const $label = $btn.find('.text');
    const $menu = $wrap.find('.lang-menu');
    const lang = resolveFooterLang();
    const labelMap = {
        ko: 'KR',
        en: 'EN',
        fr: 'FR',
        ja: 'JA',
        mn: 'MN'
    };
    const labelText = labelMap[lang] || labelMap.ko;

    $label.text(labelText);
    $menu.find('a').removeClass('is-active').removeAttr('aria-current');
    if (lang) {
        $menu.find(`a[data-lang="${lang}"]`).addClass('is-active').attr('aria-current', 'true');
    }

    const closeMenu = () => {
        $wrap.removeClass('open');
        $btn.attr('aria-expanded', 'false');
        $menu.attr('aria-hidden', 'true');
    };

    const openMenu = () => {
        $wrap.addClass('open');
        $btn.attr('aria-expanded', 'true');
        $menu.attr('aria-hidden', 'false');
    };

    closeMenu();

    $btn.off('click.langmenu').on('click.langmenu', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ($wrap.hasClass('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    $menu.find('a').off('click.langmenu').on('click.langmenu', function () {
        closeMenu();
    });

    $(document).off('click.langmenu').on('click.langmenu', function (e) {
        if ($(e.target).closest('.lang-select').length) return;
        closeMenu();
    });

    $(document).off('keydown.langmenu').on('keydown.langmenu', function (e) {
        if (e.key === 'Escape') closeMenu();
    });
}

/* ===== Mobile menu ===== */
function initMobileMenu() {
    const $header = $('.header');
    const $navBtn = $header.find('.navbtn');
    const $overlay = $('.overlay-menu').first();
    if (!$navBtn.length || !$overlay.length) return;

    const $closeBtn = $overlay.find('.closebtn');
    const $menuList = $overlay.find('.menulist');
    let scrollLockActive = false;
    const lockOptions = { passive: false, capture: true };
    const overlayScroll = $menuList.get(0);

    const lockScroll = (e) => {
        if (!$('body').hasClass('navopen')) return;
        if (overlayScroll && overlayScroll.contains(e.target)) {
            return;
        }
        e.preventDefault();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        e.stopPropagation();
    };

    const setScrollLock = (locked) => {
        if (locked && !scrollLockActive) {
            document.addEventListener('wheel', lockScroll, lockOptions);
            document.addEventListener('touchmove', lockScroll, lockOptions);
            scrollLockActive = true;
        } else if (!locked && scrollLockActive) {
            document.removeEventListener('wheel', lockScroll, lockOptions);
            document.removeEventListener('touchmove', lockScroll, lockOptions);
            scrollLockActive = false;
        }
    };

    const closeDrawerPanels = () => {
        if (!$menuList.length) return;
        $menuList.find('.draweritem').removeClass('open');
        $menuList.find('.drawertoggle').attr('aria-expanded', 'false');
        $menuList.find('.drawerpanel').attr('aria-hidden', 'true');
    };

    const setOpen = (open) => {
        $('body').toggleClass('navopen', open);
        document.documentElement.classList.toggle('navopen', open);
        $navBtn
            .attr('aria-expanded', open ? 'true' : 'false')
            .attr('aria-label', open ? 'Close navigation' : 'Open navigation');
        $overlay.attr('aria-hidden', open ? 'false' : 'true');
        setScrollLock(open);
        if (open) {
            window.scrollInstance?.stop?.();
        } else {
            window.scrollInstance?.start?.();
        }
        if (!open) closeDrawerPanels();
    };

    const isOpen = () => $('body').hasClass('navopen');

    const buildMenu = () => {
        if (!$menuList.length) return;
        const $items = $('.header .nav .item');
        if (!$items.length) return;

        $menuList.empty();

        $items.each(function () {
            const $item = $(this);
            const label = $.trim($item.children('a').first().text());
            const $cards = $item.find('.panel .card');
            const $subLinks = $item.find('.panel .link');

            const $drawer = $('<div class="draweritem"></div>');
            const $toggle = $('<button type="button" class="drawertoggle" aria-expanded="false"></button>').text(label);
            const $panel = $('<div class="drawerpanel" aria-hidden="true"></div>');
            let hasPanelContent = false;

            if ($cards.length) {
                $cards.each(function () {
                    const $card = $(this);
                    const titleText = $.trim($card.find('.title').first().text());
                    if (titleText) {
                        const $label = $('<div class="drawerlabel"></div>').text(titleText);
                        $panel.append($label);
                        hasPanelContent = true;
                    }
                    const $cardLinks = $card.find('.link');
                    if ($cardLinks.length) {
                        $cardLinks.each(function () {
                            const $link = $(this);
                            const text = $.trim($link.text());
                            if (!text) return;
                            const href = $link.attr('href') || '#';
                            const $a = $('<a class="drawerlink"></a>').attr('href', href).text(text);
                            $panel.append($a);
                            hasPanelContent = true;
                        });
                    }
                });
            }

            if (!hasPanelContent && $subLinks.length) {
                let addedSubLink = false;
                $subLinks.each(function () {
                    const $link = $(this);
                    const text = $.trim($link.text());
                    if (!text) return;
                    const href = $link.attr('href') || '#';
                    const $a = $('<a class="drawerlink"></a>').attr('href', href).text(text);
                    $panel.append($a);
                    addedSubLink = true;
                });
                hasPanelContent = addedSubLink;
            }

            if (!hasPanelContent) {
                const $mainLink = $item.children('a').first();
                const href = $mainLink.attr('href') || '#';
                const $a = $('<a class="drawerlink"></a>').attr('href', href).text(label);
                $panel.append($a);
            }

            $drawer.append($toggle, $panel);
            $menuList.append($drawer);
        });

        $menuList.find('.drawertoggle').off('click.mobilemenu').on('click.mobilemenu', function () {
            const $drawer = $(this).closest('.draweritem');
            const $panel = $drawer.find('.drawerpanel');
            const willOpen = !$drawer.hasClass('open');

            $menuList.find('.draweritem').removeClass('open');
            $menuList.find('.drawertoggle').attr('aria-expanded', 'false');
            $menuList.find('.drawerpanel').attr('aria-hidden', 'true');

            if (willOpen) {
                $drawer.addClass('open');
                $(this).attr('aria-expanded', 'true');
                $panel.attr('aria-hidden', 'false');
            }
        });
    };

    buildMenu();

    $navBtn.off('click.mobilemenu').on('click.mobilemenu', function (e) {
        e.preventDefault();
        setOpen(!isOpen());
    });

    $closeBtn.off('click.mobilemenu').on('click.mobilemenu', function (e) {
        e.preventDefault();
        setOpen(false);
    });

    $(document).off('keydown.mobilemenu').on('keydown.mobilemenu', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });

    setOpen(isOpen());
}

/* ===== Component path normalization ===== */
function normalizeComponentAssets($root) {
    if (!$root || !$root.length) return;
    $root.find('img[src]').each(function () {
        const $img = $(this);
        const raw = $img.attr('src') || '';
        const fixed = normalizeAssetSrc($.trim(raw));
        if (fixed && fixed !== raw) $img.attr('src', fixed);
    });
}

function normalizePageLinks($root, lang) {
    if (!$root || !$root.length) return;
    const code = (lang || resolveFooterLang() || 'ko').toLowerCase();
    const langBase = buildLangBase(code);
    $root.find('a[href]').not('[data-lang]').each(function () {
        const $link = $(this);
        const raw = $link.attr('href') || '';
        const href = $.trim(raw);
        if (!href || isExternalHref(href)) return;

        if (href === '/' || href === './') {
            $link.attr('href', `${langBase}/`);
            return;
        }

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch (e) {
            return;
        }

        const path = stripLangPrefix(stripBasePath(url.pathname), code);
        if (path.toLowerCase().startsWith('/pages/')) {
            const next = `${langBase}${path}${url.search || ''}${url.hash || ''}`;
            $link.attr('href', next);
        }
    });
}

function normalizeLangMenuLinks($root) {
    if (!$root || !$root.length) return;
    const $links = $root.find('.lang-menu a[data-lang]');
    if (!$links.length) return;

    const currentLang = resolveFooterLang();
    const relPath = getRelativePathForLang(currentLang);
    const suffix = `${window.location.search || ''}${window.location.hash || ''}`;

    $links.each(function () {
        const code = ($(this).data('lang') || '').toString().toLowerCase();
        if (!code) return;
        const langBase = buildLangBase(code);
        const target = relPath && relPath !== '/' ? `${langBase}${relPath}` : `${langBase}/`;
        $(this).attr('href', target + suffix);
    });
}

function getCleanSlugFromHash() {
    const raw = window.location.hash || '';
    if (!raw.startsWith('#/')) return '';
    let slug = raw.slice(2);
    if (!slug) return '';
    slug = slug.split('?')[0];
    if (slug.indexOf('/') !== -1) return '';
    try {
        slug = decodeURIComponent(slug);
    } catch (e) {
    }
    return slug.toLowerCase();
}

function routeCleanHashToPage() {
    const slug = getCleanSlugFromHash();
    if (!slug) return;
    const routes = window.__cleanSlugRoutes;
    if (!routes || !routes[slug]) return;
    const current = (window.__htmlPath || stripBasePath(window.location.pathname || '/')).toLowerCase();
    let targetPath = '';
    try {
        targetPath = stripBasePath(new URL(routes[slug], window.location.href).pathname).toLowerCase();
    } catch (e) {
        targetPath = stripBasePath(routes[slug]).toLowerCase();
    }
    if (current && targetPath && current === targetPath) return;
    window.location.replace(routes[slug]);
}

function ensureCleanHashRouter() {
    if (window.__cleanHashRouterBound) return;
    window.__cleanHashRouterBound = true;
    window.addEventListener('hashchange', routeCleanHashToPage);
}

function applyCleanDisplayFromMap(htmlToClean) {
    const currentPath = stripBasePath(window.location.pathname || '/');
    const entry = htmlToClean && htmlToClean[currentPath];
    const isIndex = /\/index\.html$/i.test(currentPath);
    if (!entry && !isIndex) return;
    window.__htmlPath = currentPath;

    if (entry) {
        const base = resolveSiteBasePath();
        const path = entry.path || currentPath;
        const search = entry.search || '';
        const hash = entry.hash || '';
        const next = joinPath(base, path) + search + hash;
        const currentFull = (window.location.pathname || '')
            + (window.location.search || '')
            + (window.location.hash || '');
        if (next !== currentFull) history.replaceState(null, '', next);
        return;
    }

    const cleanPath = currentPath.replace(/\/index\.html$/i, '/');
    if (!cleanPath || cleanPath === currentPath) return;
    const next = joinPath(resolveSiteBasePath(), cleanPath)
        + (window.location.search || '')
        + (window.location.hash || '');
    const currentFull = (window.location.pathname || '')
        + (window.location.search || '')
        + (window.location.hash || '');
    if (next !== currentFull) history.replaceState(null, '', next);
}

function normalizeCleanUrls($root) {
    if (!$root || !$root.length) return;
    const $links = $root.find('.panel a.link[href]');
    if (!$links.length) return;

    const lang = resolveFooterLang();
    const base = resolveSiteBasePath();
    const seen = new Map();
    const conflicts = new Set();
    const items = [];
    const htmlToClean = {};
    const slugRoutes = Object.create(null);

    $links.each(function () {
        const $link = $(this);
        const raw = $link.attr('href') || '';
        const href = $.trim(raw);
        if (!href || isExternalHref(href)) return;

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch (e) {
            return;
        }

        const pathWithLang = stripBasePath(url.pathname || '/');
        const pathKey = stripLangPrefix(pathWithLang, lang);
        if (!/\/pages\/sub\d+\.html$/i.test(pathKey)) return;

        const slugSource = $link.data('slug') || $link.attr('data-slug') || $link.text();
        const slug = slugifyLabel(slugSource);
        if (!slug) return;

        items.push({ $link, url, pathWithLang, pathKey, slug });

        if (seen.has(pathKey) && seen.get(pathKey) !== slug) {
            conflicts.add(pathKey);
        } else if (!seen.has(pathKey)) {
            seen.set(pathKey, slug);
        }
    });

    items.forEach((item) => {
        if (conflicts.has(item.pathKey)) return;
        const slug = seen.get(item.pathKey);
        if (!slug) return;
        let pagesBase = item.pathWithLang.replace(/sub\d+\.html$/i, '');
        if (!pagesBase.endsWith('/')) pagesBase += '/';
        const hash = `#/${slug}`;
        const cleanHref = joinPath(base, pagesBase) + hash;
        item.$link.attr('href', cleanHref);
        htmlToClean[item.pathWithLang] = { path: pagesBase, hash: hash };
        if (!slugRoutes[slug]) {
            slugRoutes[slug] = joinPath(base, item.pathWithLang);
        }
    });

    window.__cleanSlugRoutes = slugRoutes;
    ensureCleanHashRouter();
    routeCleanHashToPage();
    applyCleanDisplayFromMap(htmlToClean);
}

function normalizeComponentPaths() {
    const $header = $('#header-container');
    const $footer = $('#footer-container');
    const $subtop = $('#subtop-container');
    const $cta = $('#cta-container');

    normalizeComponentAssets($header);
    normalizeComponentAssets($footer);
    normalizeComponentAssets($subtop);
    normalizeComponentAssets($cta);

    normalizePageLinks($header);
    normalizePageLinks($subtop);
    normalizeCleanUrls($header);
    normalizeLangMenuLinks($header);
}

/* ===== Components loader ===== */
async function loadComponents() {
    try {
        const compBase = joinPath(resolveSiteBasePath(), '/components');
        const footerLang = resolveFooterLang();
        const footerPath = footerLang ? `${compBase}/footer/${footerLang}.html` : `${compBase}/footer.html`;

        const hasSubtop = !!$('#subtop-container').length;
        const hasCta = !!$('#cta-container').length;

        const [headerRes, footerRes, subtopRes, ctaRes] = await Promise.all([
            fetch(`${compBase}/header.html`),
            fetch(footerPath),
            hasSubtop ? fetch(`${compBase}/sub-top.html`) : Promise.resolve({ text: async () => '' }),
            hasCta ? fetch(`${compBase}/cta.html`) : Promise.resolve({ text: async () => '' })
        ]);

        const headerHtml = await headerRes.text();
        let footerHtml = await footerRes.text();

        if (!footerRes.ok && footerPath !== `${compBase}/footer.html`) {
            const fallbackRes = await fetch(`${compBase}/footer.html`);
            if (fallbackRes.ok) {
                footerHtml = await fallbackRes.text();
            }
        }

        $('#header-container').html(headerHtml);
        $('#footer-container').html(footerHtml);

        if (hasSubtop) $('#subtop-container').html(await subtopRes.text());
        if (hasCta) $('#cta-container').html(await ctaRes.text());
        normalizeComponentPaths();
        setHeaderActive();
        initLangMenu();
        initMobileMenu();

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
    const $header = $('.header');
    if (!$header.length) return;

    let scrollRootOverride = null;

    function resolveScrollRootElement() {
        const cfg = window.SancSmoothScrollConfig || {};
        const scroller = cfg.scroller;
        let root = null;

        if (typeof scroller === 'string') {
            root = document.querySelector(scroller);
        } else if (scroller && scroller.nodeType === 1) {
            root = scroller;
        }

        if (root) {
            const scrollable = (root.scrollHeight || 0) > (root.clientHeight || 0) + 1;
            if (!scrollable) root = null;
        }

        return root || document.scrollingElement || document.documentElement || document.body;
    }

    function updateScrollbarWidth(scrollRoot) {
        const root = scrollRoot || resolveScrollRootElement();
        const isNarrow = (window.innerWidth || 0) <= 1024;
        if (isNarrow) {
            document.documentElement.style.setProperty('--scrollbar-width', '0px');
            return;
        }

        const getWidth = (el) => {
            if (!el) return 0;
            return Math.max(0, (el.offsetWidth || 0) - (el.clientWidth || 0));
        };

        let width = getWidth(root);

        if (!width) {
            const docEl = document.documentElement;
            const body = document.body;
            width = Math.max(getWidth(docEl), getWidth(body));
        }

        if (!width) {
            const docEl = document.documentElement;
            const inner = window.innerWidth || 0;
            const client = docEl ? docEl.clientWidth || 0 : 0;
            width = Math.max(0, Math.round(inner - client));
        }

        document.documentElement.style.setProperty('--scrollbar-width', `${Math.round(width)}px`);
    }

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
    updateScrollbarWidth();
    window.addEventListener('resize', updateScrollbarWidth, { passive: true });
    window.addEventListener('load', updateScrollbarWidth, { once: true });

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
    const $hdr = $('.header');
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
        const $menuItems = $('.header .nav .item');
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
