/**
 * sub0101 Section 2 timeline scroll effects
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    var NEWS_ENDPOINT = '../../api/news.php';
    var NEWS_PLACEHOLDER_IMG = '../../assets/img/common/default-image.jpg';

    var bound = false;

    function prefersReducedMotion() {
        return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function resolveLang() {
        var match = w.location.pathname.match(/\/(ko|en|ja|fr|mn)\//i);
        return match ? match[1].toLowerCase() : '';
    }

    function buildNewsEndpoint(limit) {
        if (!NEWS_ENDPOINT) return '';
        var params = [];
        var lang = resolveLang();
        if (lang) params.push('lang=' + encodeURIComponent(lang));
        if (typeof limit === 'number' && limit > 0) params.push('limit=' + limit);
        if (!params.length) return NEWS_ENDPOINT;
        return NEWS_ENDPOINT + '?' + params.join('&');
    }

    function getDetailBase() {
        return /\/pages\//i.test(w.location.pathname) ? './sub-detail.html' : './pages/sub-detail.html';
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
        var $steps = $section.find('.step');
        var $cards = $section.find('.card');
        if (!$list.length) return;

        if (typeof w.gsap === 'undefined' || typeof w.ScrollTrigger === 'undefined') {
            $list[0].style.setProperty('--line-progress', '100%');
            $steps.css({ opacity: '', transform: '', visibility: '' });
            $cards.css({ opacity: '', transform: '', visibility: '' });
            return;
        }

        w.gsap.registerPlugin(w.ScrollTrigger);

        var reduce = prefersReducedMotion();
        var scroller = scrollerEl();

        if (reduce) {
            $list[0].style.setProperty('--line-progress', '100%');
            $steps.css({ opacity: '', transform: '', visibility: '' });
            $cards.css({ opacity: '', transform: '', visibility: '' });
        } else {
            w.gsap.set($steps, { autoAlpha: 0.2, y: 18 });
            w.gsap.set($cards, { autoAlpha: 0, y: 32 });

            function revealItem($item) {
                var $step = $item.find('.step').first();
                var $itemCards = $item.find('.card');
                var tl = w.gsap.timeline({ defaults: { ease: 'power3.out' } });

                if ($step.length) {
                    tl.to($step.get(0), { autoAlpha: 1, y: 0, duration: 0.5 });
                }
                if ($itemCards.length) {
                    tl.to($itemCards.toArray(), {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.7,
                        stagger: 0.12
                    }, $step.length ? '-=0.1' : 0);
                }
            }

            $items.each(function (_, el) {
                var $item = $(el);
                w.ScrollTrigger.create({
                    trigger: el,
                    scroller: scroller,
                    start: 'top 80%',
                    onEnter: function () {
                        revealItem($item);
                    },
                    onEnterBack: function () {
                        revealItem($item);
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
                    scrub: 0.6
                }
            });
        }

        w.addEventListener('load', function () {
            setTimeout(refreshScroll, 120);
        }, { once: true });
    }

    function initNewsFeed() {
        var $section = $('.cont4');
        if (!$section.length || !w.fetch) return;

        var $track = $section.find('.cards');
        if (!$track.length) return;

        var endpoint = buildNewsEndpoint(8);
        if (!endpoint) return;

        function renderCards(items) {
            var detailBase = getDetailBase();
            $track.empty();
            items.forEach(function (item, idx) {
                var cover = item.thumb || (item.images && item.images[0] ? item.images[0].src : NEWS_PLACEHOLDER_IMG);
                var href = detailBase + '#news/' + idx;
                var $card = $('<a/>', { 'class': 'card', href: href });
                var $img = $('<img/>', { 'class': 'photo', src: cover, alt: item.title || '' });
                var $content = $('<div/>', { 'class': 'content' });
                var $meta = $('<div/>', { 'class': 'meta' });
                var $date = $('<span/>', { 'class': 'date', text: item.date || '' });
                var $title = $('<p/>', { 'class': 'title', text: item.title || '' });
                var $badge = $('<span/>', { 'class': 'badge', text: 'Newsroom' });
                var $more = $('<i/>', { 'class': 'more', 'aria-hidden': 'true' });

                $meta.append($date, $title);
                $content.append($meta, $badge);
                $card.append($img, $content, $more);
                $track.append($card);
            });
            initNewsCards();
        }

        w.fetch(endpoint)
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items) || !data.items.length) return;
                renderCards(data.items);
            })
            .catch(function () {
                // Keep static cards when fetch fails.
            });
    }

    function initNewsCards() {
        var $section = $('.cont4');
        if (!$section.length) return;

        var $track = $section.find('.cards');
        var $cards = $track.children('.card');
        var $prev = $section.find('.nav.prev');
        var $next = $section.find('.nav.next');

        if (!$track.length || !$cards.length) return;

        var index = 0;
        var step = 0;
        var maxIndex = 0;

        function getLayout(width) {
            if (width <= 480) {
                return { mode: 'stacked', columns: 1, rows: 2 };
            }
            if (width <= 768) return { mode: 'slider', columns: 2 };
            if (width <= 1024) return { mode: 'slider', columns: 3 };
            return { mode: 'grid', columns: 4 };
        }

        function updateButtons() {
            if (!$section.hasClass('is-slider')) {
                $prev.prop('disabled', false).attr('aria-disabled', 'false');
                $next.prop('disabled', false).attr('aria-disabled', 'false');
                return;
            }

            var disablePrev = index <= 0;
            var disableNext = index >= maxIndex;
            $prev.prop('disabled', disablePrev).attr('aria-disabled', String(disablePrev));
            $next.prop('disabled', disableNext).attr('aria-disabled', String(disableNext));
        }

        function applyTransform() {
            $track.css('transform', 'translateX(' + (-index * step) + 'px)');
        }

        function resetSlider() {
            $section.removeClass('is-slider is-stacked');
            $track.css('transform', '');
            $cards.each(function () {
                this.style.flex = '';
            });
            updateButtons();
        }

        function applyLayout() {
            var width = w.innerWidth || w.document.documentElement.clientWidth;
            var layout = getLayout(width);

            if (layout.mode === 'grid') {
                resetSlider();
                return;
            }

            $section.addClass('is-slider');
            $section.toggleClass('is-stacked', layout.mode === 'stacked');

            var trackEl = $track.get(0);
            var trackWidth = trackEl.clientWidth;
            var styles = w.getComputedStyle(trackEl);
            var columnGap = parseFloat(styles.columnGap || styles.gap || 0) || 0;

            if (layout.mode === 'stacked') {
                var rows = layout.rows;
                step = trackWidth + columnGap;
                maxIndex = Math.max(0, Math.ceil($cards.length / rows) - 1);
                if (index > maxIndex) index = maxIndex;

                $cards.each(function () {
                    this.style.flex = '';
                });

                applyTransform();
                updateButtons();
                return;
            }

            var columns = layout.columns;
            var cardWidth = Math.floor((trackWidth - columnGap * (columns - 1)) / columns);

            step = cardWidth + columnGap;
            maxIndex = Math.max(0, $cards.length - columns);
            if (index > maxIndex) index = maxIndex;

            $cards.each(function () {
                this.style.flex = '0 0 ' + cardWidth + 'px';
            });

            applyTransform();
            updateButtons();
        }

        $prev.off('click.cont4cards').on('click.cont4cards', function () {
            if (!$section.hasClass('is-slider') || index <= 0) return;
            index -= 1;
            applyTransform();
            updateButtons();
        });

        $next.off('click.cont4cards').on('click.cont4cards', function () {
            if (!$section.hasClass('is-slider') || index >= maxIndex) return;
            index += 1;
            applyTransform();
            updateButtons();
        });

        applyLayout();

        var resizeTimer;
        $(w).off('resize.cont4cards').on('resize.cont4cards', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(applyLayout, 150);
        });
    }

    $(initCont2);
    $(initNewsCards);
    $(initNewsFeed);
    $(document).on('scrollengine:ready', initCont2);
})(window.jQuery, window);
