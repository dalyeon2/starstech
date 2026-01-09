/**
 * sub02 Section 1-2 content reveal
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    var cont1Bound = false;
    var cont2Bound = false;
    var PRODUCT_ENDPOINT = '../../api/products.php';
    var PRODUCT_DETAIL_PAGE = './prod-detail.html';
    var PRODUCT_PAGE_MAP = {
        eco_st: 'sub0202.html',
        labope: 'sub0204.html',
        fertilizer: 'sub0206.html'
    };
    var LANG = resolveLang();

    function prefersReducedMotion() {
        return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function resolveLang() {
        var path = w.location && w.location.pathname ? w.location.pathname : '';
        var match = path.match(/\/(ko|en|ja|fr|mn)\//i);
        return match ? match[1].toLowerCase() : 'ko';
    }

    function resolveBoard() {
        var dataBoard = $('[data-product-board]').data('product-board');
        if (dataBoard) return String(dataBoard).toLowerCase();
        var path = w.location && w.location.pathname ? w.location.pathname.toLowerCase() : '';
        if (path.indexOf('sub0202') !== -1) return 'eco_st';
        if (path.indexOf('sub0204') !== -1) return 'labope';
        if (path.indexOf('sub0206') !== -1) return 'fertilizer';
        return '';
    }

    function buildProductApiUrl(board, options) {
        if (!board) return '';
        var params = ['board=' + encodeURIComponent(board)];
        if (LANG) params.push('lang=' + encodeURIComponent(LANG));
        if (options && options.id) params.push('id=' + encodeURIComponent(options.id));
        if (options && typeof options.limit === 'number') params.push('limit=' + options.limit);
        if (options && typeof options.offset === 'number') params.push('offset=' + options.offset);
        return PRODUCT_ENDPOINT + (PRODUCT_ENDPOINT.indexOf('?') !== -1 ? '&' : '?') + params.join('&');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (ch) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[ch] || ch;
        });
    }

    function formatMultiline(value) {
        return escapeHtml(value).replace(/\n/g, '<br />');
    }

    function getEmptyMessage(lang) {
        var messages = {
            ko: '\ub4f1\ub85d\ub41c \uc81c\ud488\uc774 \uc5c6\uc2b5\ub2c8\ub2e4.',
            en: 'No products available.',
            ja: '\u767b\u9332\u3055\u308c\u305f\u88fd\u54c1\u304c\u3042\u308a\u307e\u305b\u3093\u3002',
            fr: 'Aucun produit disponible.',
            mn: '\u0411\u04af\u0440\u0442\u0433\u044d\u043b\u0442\u044d\u0439 \u0431\u04af\u0442\u044d\u044d\u0433\u0434\u044d\u0445\u04af\u04af\u043d \u0431\u0430\u0439\u0445\u0433\u04af\u0439.'
        };
        return messages[lang] || messages.ko;
    }

    function fetchProductList(board) {
        var endpoint = buildProductApiUrl(board);
        if (!endpoint || !w.fetch) return Promise.resolve(null);
        return fetch(endpoint)
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items)) throw new Error('invalid');
                return data.items;
            })
            .catch(function () {
                return null;
            });
    }

    function fetchProductDetail(board, id) {
        var endpoint = buildProductApiUrl(board, { id: id });
        if (!endpoint || !w.fetch) return Promise.resolve(null);
        return fetch(endpoint)
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !data.item) throw new Error('invalid');
                return data.item;
            })
            .catch(function () {
                return null;
            });
    }

    function renderProductList(board, items) {
        var $section = $('.cont2');
        if (!$section.length) return;
        var $list = $section.find('.list');
        var $count = $section.find('.total .count');
        if (!$list.length) return;

        if (!Array.isArray(items)) return;
        $list.empty();
        $list.removeClass('is-empty');

        if (!items.length) {
            $list.addClass('is-empty');
            $list.append($('<p/>', { 'class': 'empty', text: getEmptyMessage(LANG) }));
            if ($count.length) $count.text('0');
            refreshScroll();
            return;
        }

        items.forEach(function (item) {
            var href = PRODUCT_DETAIL_PAGE + '?board=' + encodeURIComponent(board) + '&id=' + encodeURIComponent(item.id);
            var thumb = item.thumb || '';
            var title = item.title || '';
            var $card = $('<a/>', { 'class': 'item', href: href });
            var $media = $('<div/>', { 'class': 'media' });
            var $img = $('<img/>', { 'class': 'photo', src: thumb, alt: title });
            var $btn = $('<span/>', { 'class': 'btn' })
                .append($('<span/>', { text: 'View Detail' }))
                .append($('<i/>', { 'class': 'icon', 'aria-hidden': 'true' }));
            $media.append($img, $btn);
            $card.append($media);
            $card.append($('<p/>', { 'class': 'name', text: title }));
            $list.append($card);
        });

        if ($count.length) $count.text(String(items.length));
        cont2Bound = false;
        initCont2();
        refreshScroll();
    }

    function renderProductDetail(item, board) {
        if (!item) return;
        var $page = $('.cont1');
        if (!$page.length) return;
        var images = Array.isArray(item.images) ? item.images.filter(Boolean) : [];
        var mainImage = images[0] || item.thumb || '../../assets/img/common/default-image.jpg';

        var $gallery = $page.find('.gallery');
        if ($gallery.length) {
            var $main = $gallery.find('.main');
            if (!$main.length) {
                $main = $('<img/>', { 'class': 'main' });
                $gallery.prepend($main);
            }
            $main.attr('src', mainImage).attr('alt', item.title || '');

            var $thumbs = $gallery.find('.thumbs');
            if ($thumbs.length) {
                $thumbs.empty();
                var thumbSources = images.length ? images : [mainImage];
                thumbSources.forEach(function (src) {
                    if (!src) return;
                    $thumbs.append($('<img/>', { 'class': 'thumb', src: src, alt: item.title || '' }));
                });
            }
        }

        var $title = $page.find('.title').first();
        if ($title.length) $title.text(item.title || '');
        var $lead = $page.find('.lead').first();
        if ($lead.length) {
            if (item.desc) {
                $lead.html(formatMultiline(item.desc));
                $lead.show();
            } else {
                $lead.hide();
            }
        }

        var $pairs = $page.find('.pairs .pair');
        if ($pairs.length) {
            var $buyPair = $pairs.eq(0);
            var $pricePair = $pairs.eq(1);
            if ($buyPair.length) {
                if (item.buy) {
                    $buyPair.find('.value').text(item.buy);
                    $buyPair.show();
                } else {
                    $buyPair.hide();
                }
            }
            if ($pricePair.length) {
                if (item.price) {
                    $pricePair.find('.value').text(item.price);
                    $pricePair.show();
                } else {
                    $pricePair.hide();
                }
            }
        }

        var $manualBtn = $page.find('.actions .btn.line').first();
        if ($manualBtn.length) {
            if (item.manual && item.manual.src) {
                $manualBtn.attr('href', item.manual.src);
                $manualBtn.attr('target', '_blank');
                $manualBtn.attr('rel', 'noopener');
                $manualBtn.show();
            } else {
                $manualBtn.hide();
            }
        }

        var $specs = $page.find('.specs');
        if ($specs.length) {
            $specs.empty();
            if (item.features && item.features.length) {
                item.features.forEach(function (feature) {
                    var $block = $('<div/>', { 'class': 'block' });
                    $block.append($('<div/>', { 'class': 'label', text: feature.title || '' }));
                    var $ul = $('<ul/>', { 'class': 'lines' });
                    var items = Array.isArray(feature.items) ? feature.items : [];
                    items.forEach(function (line) {
                        if (!line) return;
                        $ul.append($('<li/>', { text: line }));
                    });
                    $block.append($ul);
                    $specs.append($block);
                });
                $specs.show();
            } else {
                $specs.hide();
            }
        }

        var listPage = PRODUCT_PAGE_MAP[board];
        var $back = $page.find('.back');
        if ($back.length && listPage) {
            $back.attr('href', listPage);
        }

        if (item.title) {
            document.title = item.title + ' | STARSTECH';
        }

        if (typeof w.initProductThumbs === 'function') {
            w.initProductThumbs();
        }
        refreshScroll();
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

    function initProductList() {
        var $section = $('.cont2');
        if (!$section.length) return;
        var board = resolveBoard();
        if (!board) return;
        fetchProductList(board).then(function (items) {
            if (!items) return;
            renderProductList(board, items);
        });
    }

    function initProductDetail() {
        var path = w.location && w.location.pathname ? w.location.pathname : '';
        if (!/prod-detail\.html/i.test(path)) return;

        var params = new URLSearchParams(w.location.search || '');
        var board = params.get('board');
        var id = parseInt(params.get('id'), 10);
        if (!board) {
            board = resolveBoard();
        }
        if (!board) return;

        if (!isNaN(id) && id > 0) {
            fetchProductDetail(board, id).then(function (item) {
                if (!item) return;
                renderProductDetail(item, board);
            });
            return;
        }

        fetchProductList(board).then(function (items) {
            if (!items || !items.length) return;
            renderProductDetail(items[0], board);
        });
    }

    $(function () {
        initProductList();
        initProductDetail();
    });

    $(initCont1);
    $(initCont2);
    $(document).on('scrollengine:ready', initCont1);
    $(document).on('scrollengine:ready', initCont2);
})(window.jQuery, window);
