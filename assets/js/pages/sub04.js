/**
 * sub04 (Newsroom + PR)
 * list + detail
 */
(function () {
    var NEWS_ENDPOINT = '../../api/news.php';
    var PR_ENDPOINT = '../../api/pr.php';
    var NEWS_BOARD = 'news';
    var PR_BOARD = 'pr';
    var NEWS_LIST_URL = './sub0401.html';
    var NEWS_KEY = 'news';
    var PR_KEY = 'pr';
    var BOARD_LIST_URL = './sub0402.html';
    var NOTICE_KEY = 'notice';
    var NEWS_PLACEHOLDER_IMG = '../../assets/img/common/default-image.jpg';
    var LANG = resolveLang();
    var NEWS_STORAGE_KEY = 'newsroom-items-v1' + (LANG ? '-' + LANG : '');
    var BOARD_STORAGE_KEY = 'prboard-items-v1' + (LANG ? '-' + LANG : '');
    var newsCache = null;
    var boardCache = null;
    var lastDetailSignature = '';
    var detailHasLoaded = false;
    var smoothScrollPaused = false;
    var I18N = {
        ko: {
            emptyList: '게시물이 없습니다.',
            emptySearch: '검색 결과가 없습니다.'
        },
        en: {
            emptyList: 'No posts yet.',
            emptySearch: 'No results found.'
        },
        fr: {
            emptyList: 'Aucun article pour le moment.',
            emptySearch: 'Aucun resultat.'
        },
        ja: {
            emptyList: '記事がありません。',
            emptySearch: '検索結果がありません。'
        },
        mn: {
            emptyList: 'Одоогоор нийтлэл алга.',
            emptySearch: 'Хайлтын үр дүн алга.'
        }
    };

    function t(key) {
        var dict = I18N[LANG] || I18N.en;
        return (dict && dict[key]) || (I18N.en && I18N.en[key]) || key;
    }

    function isDetailPage() {
        return /sub-detail\.html/i.test(location.pathname);
    }

    function resolveLang() {
        var match = location.pathname.match(/\/(ko|en|ja|fr|mn)\//i);
        return match ? match[1].toLowerCase() : '';
    }

    function buildApiUrl(board, options) {
        var base = board === PR_BOARD ? PR_ENDPOINT : NEWS_ENDPOINT;
        if (!base) return '';
        var params = [];
        if (LANG) params.push('lang=' + encodeURIComponent(LANG));
        if (options && typeof options.limit === 'number') params.push('limit=' + options.limit);
        if (options && typeof options.offset === 'number' && options.offset > 0) params.push('offset=' + options.offset);
        if (!params.length) return base;
        return base + (base.indexOf('?') !== -1 ? '&' : '?') + params.join('&');
    }

    function normalizeKey(key) {
        return (key || '').toLowerCase();
    }

    function getBoardType(item) {
        return normalizeKey(item && item.type) === NOTICE_KEY ? NOTICE_KEY : PR_KEY;
    }

    function parseHash() {
        var raw = (window.location.hash || '').replace(/^#/, '');
        if (!raw) return { key: null, idx: null };

        var query = '';
        var queryIdx = raw.indexOf('?');
        if (queryIdx !== -1) {
            query = raw.slice(queryIdx + 1);
            raw = raw.slice(0, queryIdx);
        }

        var slashParts = raw.split('/');
        var key = normalizeKey(slashParts[0]);
        var idx = null;

        if (slashParts.length > 1) {
            var parsedIdx = parseInt(slashParts[1], 10);
            idx = isNaN(parsedIdx) ? null : parsedIdx;
        }

        if (idx === null && query) {
            var hashParams = new URLSearchParams(query);
            if (hashParams.has('idx')) {
                var hashIdx = parseInt(hashParams.get('idx'), 10);
                idx = isNaN(hashIdx) ? null : hashIdx;
            }
        }

        return { key: key || null, idx: idx };
    }

    function getIndexParam(total, hashIdx) {
        if (hashIdx !== null && hashIdx !== undefined) {
            var safeIdx = hashIdx;
            if (typeof total === 'number' && total > 0) {
                return Math.min(Math.max(0, safeIdx), total - 1);
            }
            return Math.max(0, safeIdx);
        }

        var params = new URLSearchParams(window.location.search);
        var raw = parseInt(params.get('idx'), 10);
        if (isNaN(raw) || raw < 0) return 0;
        if (typeof total === 'number' && total > 0) return Math.min(raw, total - 1);
        return raw;
    }

    function parseNewsDate(value) {
        if (!value) return new Date(0);
        if (typeof value === 'string') {
            var parts = value.split('.');
            if (parts.length >= 3) {
                var year = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1;
                var day = parseInt(parts[2], 10);
                if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
                    return new Date(year, month, day);
                }
            }
        }
        var parsed = Date.parse(value);
        return isNaN(parsed) ? new Date(0) : new Date(parsed);
    }

    function getYoutubeId(url) {
        if (!url) return '';
        var match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/);
        return match ? match[1] : '';
    }

    function getYoutubeThumb(url) {
        var id = getYoutubeId(url);
        return id ? 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg' : '';
    }

    function playYoutubeIframe(iframe) {
        if (!iframe || !iframe.contentWindow) return;
        try {
            iframe.contentWindow.postMessage(JSON.stringify({
                event: 'command',
                func: 'playVideo',
                args: []
            }), '*');
        } catch (err) {
            // ignore postMessage failures
        }
    }

    function isDefaultMediaSrc(src) {
        if (!src) return true;
        var clean = String(src).split('?')[0];
        return /default-image\.jpg$/i.test(clean);
    }

    function parseMediaTokens(text) {
        var segments = [];
        if (!text) return segments;
        var regex = /\[(?:media|image)\s*:\s*(\d+)\]/ig;
        var lastIndex = 0;
        var match;

        while ((match = regex.exec(text)) !== null) {
            var chunk = text.slice(lastIndex, match.index);
            if (chunk && chunk.trim()) {
                segments.push({ type: 'text', value: chunk });
            }
            segments.push({ type: 'media', index: parseInt(match[1], 10) - 1 });
            lastIndex = regex.lastIndex;
        }

        var tail = text.slice(lastIndex);
        if (tail && tail.trim()) {
            segments.push({ type: 'text', value: tail });
        }
        return segments;
    }

    function normalizeNewsItems(items) {
        if (!Array.isArray(items)) return [];
        return items.map(function (item, idx) {
            var images = Array.isArray(item && item.images) ? item.images.filter(function (img) {
                return img && img.src;
            }) : [];
            var thumb = (item && item.thumb) || (images[0] ? images[0].src : NEWS_PLACEHOLDER_IMG);
            return {
                id: (item && item.id) || ('news-' + idx),
                title: (item && item.title) || 'Untitled',
                date: (item && item.date) || '',
                datetime: (item && item.datetime) || '',
                thumb: thumb,
                images: images,
                content: (item && item.content) || '',
                contentMedia: (item && (item.content_media || item.contentMedia)) || '',
                source: (item && item.source) || null,
                label: (item && item.label) || '보도자료',
                type: (item && item.type) || 'news'
            };
        });
    }

    function normalizeBoardItems(items) {
        if (!Array.isArray(items)) return [];
        return items.map(function (item, idx) {
            var images = Array.isArray(item && item.images) ? item.images.filter(function (img) {
                return img && img.src;
            }) : [];
            var thumb = (item && item.thumb) || (images[0] ? images[0].src : NEWS_PLACEHOLDER_IMG);
            var type = normalizeKey(item && item.type) || 'pr';
            return {
                id: (item && item.id) || ('board-' + idx),
                type: type,
                label: (item && item.label) || (type === NOTICE_KEY ? '공지사항' : '홍보자료'),
                title: (item && item.title) || 'Untitled',
                date: (item && item.date) || '',
                datetime: (item && item.datetime) || '',
                thumb: thumb,
                images: images,
                content: (item && item.content) || '',
                contentMedia: (item && (item.content_media || item.contentMedia)) || '',
                source: (item && item.source) || null,
                link: (item && item.link) || ''
            };
        });
    }

    function getSortValue(item) {
        if (item && item.datetime) {
            var parsed = Date.parse(item.datetime);
            if (!isNaN(parsed)) return parsed;
        }
        return parseNewsDate(item && item.date ? item.date : item);
    }

    function sortNewsItems(items) {
        return items.slice().sort(function (a, b) {
            return getSortValue(b) - getSortValue(a);
        });
    }

    function getCachedItems(storageKey) {
        if (!window.sessionStorage || !storageKey) return null;
        try {
            var raw = window.sessionStorage.getItem(storageKey);
            if (!raw) return null;
            var data = JSON.parse(raw);
            if (!Array.isArray(data)) return null;
            return data;
        } catch (err) {
            return null;
        }
    }

    function setCachedItems(storageKey, items) {
        if (!window.sessionStorage || !storageKey || !Array.isArray(items)) return;
        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify(items));
        } catch (err) {
            // ignore storage failures
        }
    }

    function formatText(value) {
        return (value || '').replace(/\n/g, '<br />');
    }

    function buildHighlightSummary(value) {
        var raw = String(value || '').replace(/\r/g, '').trim();
        if (!raw) return '';
        var parts = raw.split(/\n\s*\n/).filter(Boolean);
        var summary = (parts[0] || raw).replace(/\n+/g, ' ');
        return summary.replace(/\s+/g, ' ').trim();
    }

    function splitContent(value) {
        var parts = (value || '').split(/\n\s*\n/).filter(Boolean);
        if (parts.length <= 1) {
            return { before: parts[0] || '', after: '' };
        }
        if (parts.length === 2) {
            return { before: parts[0], after: parts[1] };
        }
        var mid = Math.ceil(parts.length / 2);
        return {
            before: parts.slice(0, mid).join('\n\n'),
            after: parts.slice(mid).join('\n\n')
        };
    }

    function buildDetailUrl(key, idx) {
        var safeIdx = Math.max(0, Number(idx) || 0);
        return './sub-detail.html#' + encodeURIComponent(key) + '/' + safeIdx;
    }

    function buildNewsDetailUrl(idx) {
        return buildDetailUrl(NEWS_KEY, idx);
    }

    function buildNoticeDetailUrl(idx) {
        return buildDetailUrl(NOTICE_KEY, idx);
    }

    function buildPrDetailUrl(idx) {
        return buildDetailUrl(PR_KEY, idx);
    }

    function fetchNewsData() {
        if (newsCache) return newsCache;
        var endpoint = buildApiUrl(NEWS_BOARD);
        if (!endpoint) return Promise.resolve(null);
        newsCache = fetch(endpoint)
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items)) throw new Error('invalid');
                return normalizeNewsItems(data.items);
            })
            .catch(function (err) {
                console.warn('[sub04] News data load failed.', {
                    endpoint: endpoint,
                    message: err && err.message ? err.message : err
                });
                return null;
            });
        return newsCache;
    }

    function fetchBoardData() {
        if (boardCache) return boardCache;
        var endpoint = buildApiUrl(PR_BOARD);
        if (!endpoint) return Promise.resolve(null);
        boardCache = fetch(endpoint)
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !Array.isArray(data.items)) throw new Error('invalid');
                return normalizeBoardItems(data.items);
            })
            .catch(function (err) {
                console.warn('[sub04] Board data load failed.', {
                    endpoint: endpoint,
                    message: err && err.message ? err.message : err
                });
                return null;
            });
        return boardCache;
    }

    function refreshScrollHeight() {
        if (window.scrollInstance && typeof window.scrollInstance.update === 'function') {
            window.scrollInstance.update();
        }
        if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
            ScrollTrigger.refresh();
        }
    }

    function refreshScrollAfterImages($el) {
        if (!$el || !$el.length) {
            refreshScrollHeight();
            return;
        }

        refreshScrollHeight();

        if (typeof $el.imagesLoaded === 'function') {
            $el.imagesLoaded(function () {
                refreshScrollHeight();
                setTimeout(refreshScrollHeight, 150);
                setTimeout(refreshScrollHeight, 400);
            });
        }
    }

    function scrollToTop() {
        if (window.scrollInstance && typeof window.scrollInstance.scrollTo === 'function') {
            window.scrollInstance.scrollTo(0, { duration: 0, disableLerp: true });
            return;
        }
        if (typeof window.scrollTo === 'function') {
            window.scrollTo({ top: 0, behavior: 'auto' });
        }
    }

    function setSmoothScrollPaused(shouldPause) {
        var scroller = window.scrollInstance;
        if (!scroller || typeof scroller.stop !== 'function' || typeof scroller.start !== 'function') return;
        if (shouldPause) {
            if (smoothScrollPaused) return;
            smoothScrollPaused = true;
            scroller.stop();
            if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
                ScrollTrigger.refresh();
            }
            return;
        }
        if (!smoothScrollPaused) return;
        smoothScrollPaused = false;
        scroller.start();
        if (typeof scroller.update === 'function') scroller.update();
        if (typeof ScrollTrigger !== 'undefined' && typeof ScrollTrigger.refresh === 'function') {
            ScrollTrigger.refresh();
        }
    }

    function updateSubtop(key) {
        var $title = $('.subtop .title');
        var $desc = $('.subtop .desc');
        if (!$title.length || !$desc.length) return;

        if (key === NOTICE_KEY || key === PR_KEY) {
            $title.html('PR<span class="dot">.</span>');
            $desc.text('홍보자료와 공지사항을 확인하세요.');
            return;
        }

        $title.html('Newsroom<span class="dot">.</span>');
        $desc.text('스타스테크의 새로운 소식을 확인하세요.');
    }

    function initNewsList() {
        var $section = $('.cont2.news');
        if (!$section.length) return;

        var $list = $section.find('.list');
        var $searchInput = $section.find('.input');
        var $searchBtn = $section.find('.btn');
        var $totalNum = $section.find('.num');
        var $pagination = $section.find('.pagination');
        var $pagesContainer = $pagination.find('.pages');
        var $prevBtn = $pagination.find('.nav.prev');
        var $nextBtn = $pagination.find('.nav.next');
        var $empty = null;

        function ensureEmpty() {
            $empty = $list.find('.empty');
            if (!$empty.length) {
                $empty = $('<div/>', { 'class': 'empty', role: 'status', 'aria-live': 'polite' });
                $list.append($empty);
            }
        }

        function normalizeText(text) {
            return (text || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        }

        var currentPage = 1;
        var perPage = 6;
        var currentQuery = normalizeText($searchInput.val());
        var items = [];
        var totalCount = 0;

        function updateTotal(count) {
            if ($totalNum.length) $totalNum.text(String(count));
        }

        function renderHighlight(item, idx) {
            var $highlight = $('.cont1');
            if (!$highlight.length) return;
            if (!item) {
                $highlight.addClass('is-empty');
                var $imgFallback = $highlight.find('.img');
                if ($imgFallback.length) {
                    $imgFallback.attr('src', NEWS_PLACEHOLDER_IMG);
                    $imgFallback.attr('alt', '');
                }
                return;
            }
            $highlight.removeClass('is-empty');

            var $img = $highlight.find('.img');
            var $title = $highlight.find('.title');
            var $date = $highlight.find('.date');
            var $desc = $highlight.find('.desc');
            var $btn = $highlight.find('.btn');

            var cover = (item.images && item.images[0] && item.images[0].src) || item.thumb || NEWS_PLACEHOLDER_IMG;
            var summary = buildHighlightSummary(item.content);

            if ($img.length) {
                $img.attr('src', cover);
                $img.attr('alt', item.title || '');
            }
            if ($title.length) $title.text(item.title || '');
            if ($date.length) $date.text(item.date || '');
            if ($desc.length) $desc.text(summary);

            if ($btn.length) {
                var detailUrl = buildNewsDetailUrl(idx);
                $btn.attr('data-href', detailUrl);
                $btn.off('click.news').on('click.news', function () {
                    window.location.href = detailUrl;
                });
            }
        }

        function renderCards(newsItems) {
            $list.empty();
            newsItems.forEach(function (item, idx) {
                var thumb = item.thumb || (item.images && item.images[0] ? item.images[0].src : NEWS_PLACEHOLDER_IMG);
                var $card = $('<a/>', { 'class': 'card', href: buildNewsDetailUrl(idx) });
                var $img = $('<img/>', { 'class': 'thumb', src: thumb, alt: item.title || '' });
                var $media = $('<div/>', { 'class': 'media' });
                var $meta = $('<div/>', { 'class': 'meta' });
                var $title = $('<div/>', { 'class': 'title', text: item.title || '' });
                var $date = $('<div/>', { 'class': 'date', text: item.date || '' });

                $meta.append($title, $date);
                $media.append($img);
                $card.append($media, $meta);
                $list.append($card);
            });
            ensureEmpty();
        }

        function buildItemsIndex(newsItems) {
            var $cards = $list.find('.card');
            return newsItems.map(function (item, idx) {
                return {
                    $el: $cards.eq(idx),
                    text: normalizeText((item.title || '') + ' ' + (item.date || '') + ' ' + (item.content || ''))
                };
            });
        }

        function buildItemsFromDom() {
            var arr = [];
            $list.find('.card').each(function () {
                var $card = $(this);
                var title = $card.find('.title').text();
                var date = $card.find('.date').text();
                arr.push({
                    $el: $card,
                    text: normalizeText(title + ' ' + date)
                });
            });
            ensureEmpty();
            return arr;
        }

        function getFilteredItems() {
            if (!currentQuery) return items;
            return items.filter(function (item) {
                return item.text.indexOf(currentQuery) !== -1;
            });
        }

        function updateNav(totalPages) {
            var prevDisabled = currentPage <= 1;
            var nextDisabled = currentPage >= totalPages;
            if ($prevBtn.length) $prevBtn.prop('disabled', prevDisabled).attr('aria-disabled', String(prevDisabled));
            if ($nextBtn.length) $nextBtn.prop('disabled', nextDisabled).attr('aria-disabled', String(nextDisabled));
        }

        function renderPages(totalPages) {
            if (!$pagesContainer.length) return;
            $pagesContainer.empty();
            for (var i = 1; i <= totalPages; i++) {
                var $pageBtn = $('<button/>', {
                    'class': 'page' + (i === currentPage ? ' active' : ''),
                    type: 'button',
                    text: i
                }).data('page', i);
                $pagesContainer.append($pageBtn);
            }
        }

        function renderList() {
            var filtered = getFilteredItems();
            var total = filtered.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            updateTotal(total);

            if (total === 0) {
                var message = totalCount ? t('emptySearch') : t('emptyList');
                if ($empty) $empty.text(message);
                $list.addClass('showempty');
            } else {
                $list.removeClass('showempty');
            }

            items.forEach(function (item) {
                item.$el.hide();
            });

            var start = (currentPage - 1) * perPage;
            var end = start + perPage;
            for (var i = start; i < end && i < filtered.length; i++) {
                filtered[i].$el.show();
            }

            renderPages(totalPages);
            updateNav(totalPages);
        }

        $pagesContainer.off('click.sub04list').on('click.sub04list', '.page', function () {
            var nextPage = parseInt($(this).data('page'), 10);
            if (!isNaN(nextPage)) {
                currentPage = nextPage;
                renderList();
            }
        });

        $prevBtn.off('click.sub04list').on('click.sub04list', function () {
            if ($(this).prop('disabled')) return;
            currentPage = Math.max(1, currentPage - 1);
            renderList();
        });

        $nextBtn.off('click.sub04list').on('click.sub04list', function () {
            if ($(this).prop('disabled')) return;
            currentPage = currentPage + 1;
            renderList();
        });

        function runSearch() {
            currentQuery = normalizeText($searchInput.val());
            currentPage = 1;
            renderList();
        }

        if ($searchInput.length) {
            $searchInput.off('keydown.sub04list').on('keydown.sub04list', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
        }

        if ($searchBtn.length) {
            $searchBtn.off('click.sub04list').on('click.sub04list', function () {
                runSearch();
            });
        }

        fetchNewsData().then(function (newsItems) {
            if (Array.isArray(newsItems)) {
                var sorted = sortNewsItems(newsItems);
                if (sorted.length) {
                    renderHighlight(sorted[0], 0);
                } else {
                    renderHighlight(null);
                }
                setCachedItems(NEWS_STORAGE_KEY, sorted);
                renderCards(sorted);
                items = buildItemsIndex(sorted);
                totalCount = items.length;
            } else {
                items = buildItemsFromDom();
                totalCount = items.length;
            }
            renderList();
        });
    }

    function initBoardList() {
        var $section = $('.cont2.board');
        if (!$section.length) return;

        var $list = $section.find('.list');
        var $tabs = $section.find('.tab');
        var $searchInput = $section.find('.input');
        var $searchBtn = $section.find('.btn');
        var $totalNum = $section.find('.num');
        var $pagination = $section.find('.pagination');
        var $pagesContainer = $pagination.find('.pages');
        var $prevBtn = $pagination.find('.nav.prev');
        var $nextBtn = $pagination.find('.nav.next');
        var $empty = null;

        function ensureEmpty() {
            $empty = $list.find('.empty');
            if (!$empty.length) {
                $empty = $('<div/>', { 'class': 'empty', role: 'status', 'aria-live': 'polite' });
                $list.append($empty);
            }
        }

        function normalizeText(text) {
            return (text || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        }

        var currentType = 'all';
        var currentPage = 1;
        var perPage = 6;
        var currentQuery = normalizeText($searchInput.val());
        var items = [];
        var totalCount = 0;
        var typeIndexMap = {};

        function updateTotal(count) {
            if ($totalNum.length) $totalNum.text(String(count));
        }

        function buildTypeIndexMap(sortedItems) {
            typeIndexMap = {};
            typeIndexMap[PR_KEY] = {};
            typeIndexMap[NOTICE_KEY] = {};
            var counters = {};
            sortedItems.forEach(function (item) {
                var typeKey = getBoardType(item);
                if (!typeIndexMap[typeKey]) typeIndexMap[typeKey] = {};
                if (!counters[typeKey]) counters[typeKey] = 0;
                typeIndexMap[typeKey][item.id] = counters[typeKey];
                counters[typeKey] += 1;
            });
        }

        function renderHighlight(item) {
            var $highlight = $('.cont1');
            if (!$highlight.length) return;
            if (!item) {
                $highlight.addClass('is-empty');
                var $imgFallback = $highlight.find('.img');
                if ($imgFallback.length) {
                    $imgFallback.attr('src', NEWS_PLACEHOLDER_IMG);
                    $imgFallback.attr('alt', '');
                }
                var $leftFallback = $highlight.find('.left');
                if ($leftFallback.length) {
                    $leftFallback.removeClass('is-video');
                    $leftFallback.find('.media-link').remove();
                }
                return;
            }
            $highlight.removeClass('is-empty');

            var $img = $highlight.find('.img');
            var $left = $highlight.find('.left');
            var $title = $highlight.find('.title');
            var $date = $highlight.find('.date');
            var $desc = $highlight.find('.desc');
            var $label = $highlight.find('.label');
            var $btn = $highlight.find('.btn');

            var cover = (item.images && item.images[0] && item.images[0].src) || item.thumb || NEWS_PLACEHOLDER_IMG;
            var summary = buildHighlightSummary(item.content);

            if ($img.length) {
                $img.attr('src', cover);
                $img.attr('alt', item.title || '');
            }
            if ($label.length) $label.text(item.label || '');
            if ($title.length) $title.text(item.title || '');
            if ($date.length) $date.text(item.date || '');
            if ($desc.length) $desc.text(summary);

            if ($left.length) {
                $left.removeClass('is-video');
                $left.find('.media-link').remove();
            }

            if ($btn.length) {
                var typeKey = getBoardType(item);
                var indexMap = typeIndexMap[typeKey] || {};
                var targetIdx = indexMap[item.id];
                var href = buildDetailUrl(typeKey, typeof targetIdx === 'number' ? targetIdx : 0);
                $btn.attr('data-href', href);
                $btn.off('click.board').on('click.board', function () {
                    if (!href) return;
                    window.location.href = href;
                });
            }
        }

        function renderCards(boardItems) {
            $list.empty();
            boardItems.forEach(function (item) {
                var thumb = item.thumb || (item.images && item.images[0] ? item.images[0].src : NEWS_PLACEHOLDER_IMG);
                var typeKey = getBoardType(item);
                var indexMap = typeIndexMap[typeKey] || {};
                var targetIdx = indexMap[item.id];
                var href = buildDetailUrl(typeKey, typeof targetIdx === 'number' ? targetIdx : 0);
                var $card = $('<a/>', { 'class': 'card', href: href });
                var $img = $('<img/>', { 'class': 'thumb', src: thumb, alt: item.title || '' });
                var $media = $('<div/>', { 'class': 'media' });
                var $meta = $('<div/>', { 'class': 'meta' });
                var $label = $('<div/>', { 'class': 'label', text: item.label || '' });
                var $title = $('<div/>', { 'class': 'title', text: item.title || '' });
                var $date = $('<div/>', { 'class': 'date', text: item.date || '' });

                $meta.append($label, $title, $date);
                $media.append($img);
                $card.append($media, $meta);
                $list.append($card);
            });
            ensureEmpty();
        }

        function buildItemsIndex(boardItems) {
            var $cards = $list.find('.card');
            return boardItems.map(function (item, idx) {
                return {
                    $el: $cards.eq(idx),
                    type: item.type,
                    text: normalizeText((item.label || '') + ' ' + (item.title || '') + ' ' + (item.date || '') + ' ' + (item.content || ''))
                };
            });
        }

        function buildItemsFromDom() {
            var arr = [];
            $list.find('.card').each(function () {
                var $card = $(this);
                var labelText = $card.find('.label').text();
                var title = $card.find('.title').text();
                var date = $card.find('.date').text();
                var type = labelText.indexOf('공지') !== -1 ? NOTICE_KEY : PR_KEY;
                arr.push({
                    $el: $card,
                    type: type,
                    text: normalizeText(labelText + ' ' + title + ' ' + date)
                });
            });
            ensureEmpty();
            return arr;
        }

        function getFilteredItems() {
            var filtered = items;
            if (currentType !== 'all') {
                filtered = filtered.filter(function (item) {
                    return item.type === currentType;
                });
            }
            if (!currentQuery) return filtered;
            return filtered.filter(function (item) {
                return item.text.indexOf(currentQuery) !== -1;
            });
        }

        function updateNav(totalPages) {
            var prevDisabled = currentPage <= 1;
            var nextDisabled = currentPage >= totalPages;
            if ($prevBtn.length) $prevBtn.prop('disabled', prevDisabled).attr('aria-disabled', String(prevDisabled));
            if ($nextBtn.length) $nextBtn.prop('disabled', nextDisabled).attr('aria-disabled', String(nextDisabled));
        }

        function renderPages(totalPages) {
            if (!$pagesContainer.length) return;
            $pagesContainer.empty();
            for (var i = 1; i <= totalPages; i++) {
                var $pageBtn = $('<button/>', {
                    'class': 'page' + (i === currentPage ? ' active' : ''),
                    type: 'button',
                    text: i
                }).data('page', i);
                $pagesContainer.append($pageBtn);
            }
        }

        function renderList() {
            var filtered = getFilteredItems();
            var total = filtered.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));

            if (currentPage > totalPages) currentPage = totalPages;
            updateTotal(total);

            if (total === 0) {
                var message = totalCount ? t('emptySearch') : t('emptyList');
                if ($empty) $empty.text(message);
                $list.addClass('showempty');
            } else {
                $list.removeClass('showempty');
            }

            items.forEach(function (item) {
                item.$el.hide();
            });

            var start = (currentPage - 1) * perPage;
            var end = start + perPage;
            for (var i = start; i < end && i < filtered.length; i++) {
                filtered[i].$el.show();
            }

            renderPages(totalPages);
            updateNav(totalPages);
        }

        $pagesContainer.off('click.sub04board').on('click.sub04board', '.page', function () {
            var nextPage = parseInt($(this).data('page'), 10);
            if (!isNaN(nextPage)) {
                currentPage = nextPage;
                renderList();
            }
        });

        $prevBtn.off('click.sub04board').on('click.sub04board', function () {
            if ($(this).prop('disabled')) return;
            currentPage = Math.max(1, currentPage - 1);
            renderList();
        });

        $nextBtn.off('click.sub04board').on('click.sub04board', function () {
            if ($(this).prop('disabled')) return;
            currentPage = currentPage + 1;
            renderList();
        });

        $tabs.off('click.sub04board').on('click.sub04board', function () {
            var nextType = normalizeKey($(this).data('type')) || 'all';
            currentType = nextType;
            $tabs.removeClass('active');
            $(this).addClass('active');
            currentPage = 1;
            renderList();
        });

        function runSearch() {
            currentQuery = normalizeText($searchInput.val());
            currentPage = 1;
            renderList();
        }

        if ($searchInput.length) {
            $searchInput.off('keydown.sub04board').on('keydown.sub04board', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
        }

        if ($searchBtn.length) {
            $searchBtn.off('click.sub04board').on('click.sub04board', function () {
                runSearch();
            });
        }

        fetchBoardData().then(function (boardItems) {
            if (Array.isArray(boardItems)) {
                var sorted = sortNewsItems(boardItems);
                buildTypeIndexMap(sorted);
                if (sorted.length) {
                    renderHighlight(sorted[0]);
                } else {
                    renderHighlight(null);
                }
                setCachedItems(BOARD_STORAGE_KEY, sorted);
                renderCards(sorted);
                items = buildItemsIndex(sorted);
                totalCount = items.length;
            } else {
                items = buildItemsFromDom();
                totalCount = items.length;
            }
            renderList();
        });
    }

    function initNewsDetail(items, config) {
        var cfg = config || {};
        var listUrl = cfg.listUrl || NEWS_LIST_URL;
        var safeItems = Array.isArray(items) ? items : [];
        var idx = getIndexParam(safeItems.length, cfg.idx);
        if (safeItems.length) {
            idx = Math.max(0, Math.min(idx, safeItems.length - 1));
        } else {
            idx = 0;
        }

        var item = safeItems[idx] || null;
        var signature = (cfg.key || '') + '|' + (item ? JSON.stringify(item) : 'empty') + '|' + idx;
        if (signature === lastDetailSignature) return;
        lastDetailSignature = signature;
        var $head = $('.cont1 .head');
        var $label = $head.find('.label');
        var $title = $head.find('.title');
        var $date = $head.find('.date');
        var $content = $('.cont1 .content');
        var $prevBtn = $('.cont1 .btn.prev');
        var $nextBtn = $('.cont1 .btn.next');
        var $backBtn = $('.cont1 .back');

        if ($label.length) {
            var fallbackLabel = cfg.label || '보도자료';
            $label.text(item && item.label ? item.label : fallbackLabel);
        }
        if ($title.length) $title.text(item ? item.title || '' : '');
        if ($date.length) $date.text(item ? item.date || '' : '');

        if ($content.length) {
            $content.empty();
            if (item) {
                var contentWithMedia = item.contentMedia || item.content || '';
                var parts = splitContent(item.content || '');
                var contextKey = cfg.key || (item && item.type) || NEWS_KEY;
                var isNewsContext = contextKey === NEWS_KEY;
                var isPrContext = contextKey === PR_KEY;
                var images = Array.isArray(item.images) ? item.images : [];
                var hasImages = images.some(function (img) {
                    return img && img.src && !isDefaultMediaSrc(img.src);
                });
                var youtubeId = isPrContext && item.link ? getYoutubeId(item.link) : '';
                setSmoothScrollPaused(!!youtubeId);
                var hasTokenMedia = !isPrContext && /\[(?:media|image)\s*:\s*\d+\]/i.test(contentWithMedia);
                var mediaThumb = '';
                var $media = null;
                var captions = [];

                function getImageAt(index) {
                    var img = images[index];
                    if (!img || !img.src || isDefaultMediaSrc(img.src)) return null;
                    return img;
                }

                function appendMediaBlock(img) {
                    if (!img) return;
                    var $block = $('<div/>', { 'class': 'media' });
                    $block.append($('<img/>', {
                        src: img.src,
                        alt: item.title || '',
                        decoding: 'async',
                        fetchpriority: 'high'
                    }));
                    if (img.caption) {
                        $block.append($('<div/>', { 'class': 'caption', text: img.caption }));
                    }
                    $content.append($block);
                }

                if (hasTokenMedia) {
                    var segments = parseMediaTokens(contentWithMedia);
                    var usedIndexes = {};

                    segments.forEach(function (segment) {
                        if (segment.type === 'text') {
                            $content.append($('<div/>', { 'class': 'text', html: formatText(segment.value) }));
                            return;
                        }
                        if (segment.type === 'media') {
                            var img = getImageAt(segment.index);
                            if (!img) return;
                            usedIndexes[segment.index] = true;
                            appendMediaBlock(img);
                        }
                    });

                    images.forEach(function (_, index) {
                        if (usedIndexes[index]) return;
                        appendMediaBlock(getImageAt(index));
                    });
                } else {
                    if (!hasImages && !youtubeId) {
                        if (item.thumb && !isDefaultMediaSrc(item.thumb)) {
                            mediaThumb = item.thumb;
                        }
                    }

                    if (youtubeId) {
                        $media = $('<div/>', { 'class': 'media is-iframe' });
                        var $wrap = $('<div/>', { 'class': 'iframe-wrap' });
                        var $iframe = $('<iframe/>', {
                            src: 'https://www.youtube.com/embed/' + youtubeId + '?rel=0&enablejsapi=1',
                            title: item.title || 'YouTube video',
                            allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
                            allowfullscreen: 'allowfullscreen'
                        });
                        var $guard = $('<button/>', {
                            'class': 'iframe-guard',
                            type: 'button',
                            'aria-label': 'YouTube 영상 재생'
                        });
                        $guard.on('click', function () {
                            $media.addClass('is-interactive');
                            $guard.remove();
                            playYoutubeIframe($iframe.get(0));
                        });
                        $wrap.append($iframe, $guard);
                        $media.append($wrap);
                    }

                    if (hasImages || mediaThumb) {
                        var mediaCount = 0;
                        if (!$media) {
                            $media = $('<div/>', { 'class': 'media' });
                        }
                        if (hasImages) {
                            images.forEach(function (img, index) {
                                if (!img || !img.src || isDefaultMediaSrc(img.src)) return;
                                var $img = $('<img/>', {
                                    src: img.src,
                                    alt: item.title || '',
                                    decoding: 'async'
                                });
                                if (index > 0) {
                                    $img.attr('loading', 'lazy');
                                } else {
                                    $img.attr('fetchpriority', 'high');
                                }
                                $media.append($img);
                                mediaCount += 1;
                                if (img.caption) captions.push(img.caption);
                            });
                        } else {
                            $media.append($('<img/>', {
                                src: mediaThumb,
                                alt: item.title || '',
                                decoding: 'async',
                                fetchpriority: 'high'
                            }));
                            mediaCount += 1;
                        }

                        if (captions.length) {
                            $media.append($('<div/>', { 'class': 'caption', text: captions.join(' ') }));
                        }

                        if (!mediaCount) {
                            $media = null;
                        }
                    }

                    if (isPrContext && $media) {
                        $content.append($media);
                    }

                    if (parts.before) {
                        $content.append($('<div/>', { 'class': 'text', html: formatText(parts.before) }));
                    }

                    if (!isPrContext && $media) {
                        $content.append($media);
                    }

                    if (parts.after) {
                        $content.append($('<div/>', { 'class': 'text', html: formatText(parts.after) }));
                    }
                }

                var showSource = isNewsContext && item && item.source && (item.source.text || item.source.url);
                if (showSource) {
                    var $source = $('<div/>', { 'class': 'source' });
                    $source.append($('<span/>', { 'class': 'label', text: '출처' }));
                    if (item.source.text) {
                        $source.append($('<span/>', { 'class': 'text', text: item.source.text }));
                    }
                    if (item.source.url) {
                        var $link = $('<a/>', {
                            'class': 'link',
                            href: item.source.url,
                            target: '_blank',
                            rel: 'noopener'
                        });
                        $link.append($('<span/>', { 'class': 'text', text: '기사 원문 보기' }));
                        $link.append($('<i/>', { 'class': 'icon', 'aria-hidden': 'true' }));
                        $source.append($link);
                    }
                    $content.append($source);
                }

            } else {
                setSmoothScrollPaused(false);
                $content.append($('<div/>', { 'class': 'text', text: t('emptyList') }));
            }
        }

        if (item && item.title) {
            document.title = item.title + ' | STARSTECH';
        }

        if ($backBtn.length) {
            $backBtn.attr('href', listUrl);
            $backBtn.off('click.newsback').on('click.newsback', function (e) {
                e.preventDefault();
                window.location.href = listUrl;
            });
        }

        function navUrl(targetIdx) {
            return buildDetailUrl(cfg.key || NEWS_KEY, targetIdx);
        }

        function bindNav($btn, targetIdx, label) {
            var href = navUrl(targetIdx);
            $btn.prop('disabled', false).attr('aria-disabled', 'false').removeClass('muted');
            $btn.attr('data-href', href);
            $btn.off('click.newsnav').on('click.newsnav', function (e) {
                e.preventDefault();
                window.location.href = href;
            });
            if (label) $btn.find('> .text').text(label);
        }

        var hasPrev = idx > 0;
        var hasNext = idx + 1 < safeItems.length;

        if ($prevBtn.length) {
            if (hasPrev) {
                bindNav($prevBtn, idx - 1, safeItems[idx - 1].title || 'Previous');
            } else {
                $prevBtn.addClass('muted');
                $prevBtn.find('> .text').text('이전글이 없습니다.');
                $prevBtn.off('click.newsnav').removeAttr('data-href')
                    .prop('disabled', true).attr('aria-disabled', 'true');
            }
        }

        if ($nextBtn.length) {
            if (hasNext) {
                bindNav($nextBtn, idx + 1, safeItems[idx + 1].title || 'Next');
            } else {
                $nextBtn.addClass('muted');
                $nextBtn.find('> .text').text('다음글이 없습니다.');
                $nextBtn.off('click.newsnav').removeAttr('data-href')
                    .prop('disabled', true).attr('aria-disabled', 'true');
            }
        }

        refreshScrollAfterImages($content);
    }

    function loadNewsDetail(hashIdx) {
        scrollToTop();

        if (!detailHasLoaded && document.body) {
            document.body.classList.add('news-loading');
        }

        var cached = getCachedItems(NEWS_STORAGE_KEY);
        if (cached && cached.length) {
            initNewsDetail(cached, {
                key: NEWS_KEY,
                listUrl: NEWS_LIST_URL,
                label: '보도자료',
                idx: hashIdx
            });
            scrollToTop();
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
        }

        return fetchNewsData().then(function (newsItems) {
            if (!Array.isArray(newsItems)) return;
            var sorted = sortNewsItems(newsItems);
            setCachedItems(NEWS_STORAGE_KEY, sorted);
            initNewsDetail(sorted, {
                key: NEWS_KEY,
                listUrl: NEWS_LIST_URL,
                label: '보도자료',
                idx: hashIdx
            });
            scrollToTop();
        }).finally(function () {
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
            detailHasLoaded = true;
        });
    }

    function loadBoardDetail(typeKey, hashIdx) {
        scrollToTop();

        if (!detailHasLoaded && document.body) {
            document.body.classList.add('news-loading');
        }

        var cached = getCachedItems(BOARD_STORAGE_KEY);
        if (cached && cached.length) {
            var cachedSorted = sortNewsItems(cached);
            var cachedItems = cachedSorted.filter(function (item) {
                return getBoardType(item) === typeKey;
            });
            initNewsDetail(cachedItems, {
                key: typeKey,
                listUrl: BOARD_LIST_URL,
                label: typeKey === NOTICE_KEY ? '공지사항' : '홍보자료',
                idx: hashIdx
            });
            scrollToTop();
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
        }

        return fetchBoardData().then(function (boardItems) {
            if (!Array.isArray(boardItems)) return;
            var sorted = sortNewsItems(boardItems);
            setCachedItems(BOARD_STORAGE_KEY, sorted);
            var filteredItems = sorted.filter(function (item) {
                return getBoardType(item) === typeKey;
            });
            initNewsDetail(filteredItems, {
                key: typeKey,
                listUrl: BOARD_LIST_URL,
                label: typeKey === NOTICE_KEY ? '공지사항' : '홍보자료',
                idx: hashIdx
            });
            scrollToTop();
        }).finally(function () {
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
            detailHasLoaded = true;
        });
    }

    function loadDetail() {
        var hashInfo = parseHash();
        var key = normalizeKey(hashInfo.key) || NEWS_KEY;
        var idx = hashInfo.idx;

        updateSubtop(key);

        if (key === NOTICE_KEY || key === PR_KEY) {
            loadBoardDetail(key, idx);
            return;
        }

        loadNewsDetail(idx);
    }

    $(function () {
        initNewsList();
        initBoardList();

        if (!isDetailPage()) return;

        loadDetail();

        $(window).off('hashchange.sub04').on('hashchange.sub04', function () {
            loadDetail();
        });
    });
})();
