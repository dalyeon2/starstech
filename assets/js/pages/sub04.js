/**
 * sub04 (Newsroom)
 * list + detail
 */
(function () {
    var NEWS_ENDPOINT = '../../assets/data/newsroom.json';
    var NEWS_LIST_URL = './sub0401.html';
    var NEWS_KEY = 'news';
    var NEWS_PLACEHOLDER_IMG = '../../assets/img/common/default-image.jpg';
    var NEWS_STORAGE_KEY = 'newsroom-items-v1';
    var newsCache = null;
    var lastDetailSignature = '';
    var detailHasLoaded = false;

    function isDetailPage() {
        return /sub-detail\.html/i.test(location.pathname);
    }

    function normalizeKey(key) {
        return (key || '').toLowerCase();
    }

    function parseHashIdx() {
        var raw = (window.location.hash || '').replace(/^#/, '');
        if (!raw) return null;

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

        if (key && key !== NEWS_KEY) return null;
        return idx;
    }

    function getIndexParam(total) {
        var hashIdx = parseHashIdx();
        if (hashIdx !== null) {
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
                thumb: thumb,
                images: images,
                content: (item && item.content) || '',
                source: (item && item.source) || null
            };
        });
    }

    function sortNewsItems(items) {
        return items.slice().sort(function (a, b) {
            return parseNewsDate(b.date) - parseNewsDate(a.date);
        });
    }

    function getCachedNewsItems() {
        if (!window.sessionStorage) return null;
        try {
            var raw = window.sessionStorage.getItem(NEWS_STORAGE_KEY);
            if (!raw) return null;
            var data = JSON.parse(raw);
            if (!Array.isArray(data)) return null;
            return data;
        } catch (err) {
            return null;
        }
    }

    function setCachedNewsItems(items) {
        if (!window.sessionStorage || !Array.isArray(items)) return;
        try {
            window.sessionStorage.setItem(NEWS_STORAGE_KEY, JSON.stringify(items));
        } catch (err) {
            // ignore storage failures
        }
    }

    function formatText(value) {
        return (value || '').replace(/\n/g, '<br />');
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

    function buildNewsDetailUrl(idx) {
        var safeIdx = Math.max(0, Number(idx) || 0);
        return './sub-detail.html#' + encodeURIComponent(NEWS_KEY) + '/' + safeIdx;
    }

    function fetchNewsData() {
        if (newsCache) return newsCache;
        if (!NEWS_ENDPOINT) return Promise.resolve(null);
        newsCache = fetch(NEWS_ENDPOINT)
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
                    endpoint: NEWS_ENDPOINT,
                    message: err && err.message ? err.message : err
                });
                return null;
            });
        return newsCache;
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

    function initNewsList() {
        var $section = $('.cont2');
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
            if (!$highlight.length || !item) return;

            var $img = $highlight.find('.img');
            var $title = $highlight.find('.title');
            var $date = $highlight.find('.date');
            var $desc = $highlight.find('.desc');
            var $btn = $highlight.find('.btn');

            var cover = (item.images && item.images[0] && item.images[0].src) || item.thumb || NEWS_PLACEHOLDER_IMG;
            var summary = (item.content || '').split(/\n+/).filter(Boolean)[0] || '';

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
                var message = totalCount ? '검색 결과가 없습니다.' : '게시물이 없습니다.';
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
                }
                setCachedNewsItems(sorted);
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

    function initNewsDetail(items) {
        var listUrl = NEWS_LIST_URL;
        var safeItems = Array.isArray(items) ? items : [];
        var idx = getIndexParam(safeItems.length);
        if (safeItems.length) {
            idx = Math.max(0, Math.min(idx, safeItems.length - 1));
        } else {
            idx = 0;
        }

        var item = safeItems[idx] || null;
        var signature = item ? JSON.stringify(item) + '|' + idx : 'empty|' + idx;
        if (signature === lastDetailSignature) return;
        lastDetailSignature = signature;
        var $head = $('.cont1 .head');
        var $title = $head.find('.title');
        var $date = $head.find('.date');
        var $content = $('.cont1 .content');
        var $prevBtn = $('.cont1 .btn.prev');
        var $nextBtn = $('.cont1 .btn.next');
        var $backBtn = $('.cont1 .back');

        if ($title.length) $title.text(item ? item.title || '' : '');
        if ($date.length) $date.text(item ? item.date || '' : '');

        if ($content.length) {
            $content.empty();
            if (item) {
                var parts = splitContent(item.content || '');
                if (parts.before) {
                    $content.append($('<div/>', { 'class': 'text', html: formatText(parts.before) }));
                }

                var $media = $('<div/>', { 'class': 'media' });
                var captions = [];

                if (Array.isArray(item.images) && item.images.length) {
                    item.images.forEach(function (img, index) {
                        var $img = $('<img/>', {
                            src: img.src || NEWS_PLACEHOLDER_IMG,
                            alt: item.title || '',
                            decoding: 'async'
                        });
                        if (index > 0) {
                            $img.attr('loading', 'lazy');
                        } else {
                            $img.attr('fetchpriority', 'high');
                        }
                        $media.append($img);
                        if (img.caption) captions.push(img.caption);
                    });
                } else if (item.thumb) {
                    $media.append($('<img/>', {
                        src: item.thumb,
                        alt: item.title || '',
                        decoding: 'async',
                        fetchpriority: 'high'
                    }));
                } else {
                    $media.append($('<img/>', {
                        src: NEWS_PLACEHOLDER_IMG,
                        alt: item.title || '',
                        decoding: 'async',
                        fetchpriority: 'high'
                    }));
                }

                if (captions.length) {
                    $media.append($('<div/>', { 'class': 'caption', text: captions.join(' ') }));
                }

                $content.append($media);

                if (parts.after) {
                    $content.append($('<div/>', { 'class': 'text', html: formatText(parts.after) }));
                }

                if (item.source && (item.source.text || item.source.url)) {
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
                $content.append($('<div/>', { 'class': 'text', text: '게시물이 없습니다.' }));
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
            return buildNewsDetailUrl(targetIdx);
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

    function loadNewsDetail() {
        scrollToTop();

        if (!detailHasLoaded && document.body) {
            document.body.classList.add('news-loading');
        }

        var cached = getCachedNewsItems();
        if (cached && cached.length) {
            initNewsDetail(cached);
            scrollToTop();
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
        }

        return fetchNewsData().then(function (newsItems) {
            if (!Array.isArray(newsItems)) return;
            var sorted = sortNewsItems(newsItems);
            setCachedNewsItems(sorted);
            initNewsDetail(sorted);
            scrollToTop();
        }).finally(function () {
            if (document.body) {
                document.body.classList.remove('news-loading');
            }
            detailHasLoaded = true;
        });
    }

    $(function () {
        initNewsList();

        if (!isDetailPage()) return;

        loadNewsDetail();

        $(window).off('hashchange.sub04').on('hashchange.sub04', function () {
            loadNewsDetail();
        });
    });
})();
