/**
 * sub04 (Gallery)
 * pagination + API-driven data
 */
(function () {
    var FALLBACK_DESC = 'This content is temporarily loaded with static data until the backend is connected.';
    var API_ENDPOINT = '../api/gallery.php';
    var PLACEHOLDER_IMG = '../assets/img/gallery/thumbnail01.png';
    var DEFAULT_GALLERY_KEY = 'company';

    var listPages = {
        company: './sub0401.html',
        factories: './sub0402.html',
        warehouse: './sub0403.html',
        event: './sub0404.html',
        video: './sub0405.html'
    };

    var galleryAlias = {
        sub0401: 'company',
        sub0402: 'factories',
        sub0403: 'warehouse',
        sub0404: 'event',
        sub0405: 'video'
    };

    function isDetailPage() {
        return /sub-detail\.html/i.test(location.pathname);
    }

    function normalizeKey(key) {
        return (key || '').toLowerCase();
    }

    function parseHash() {
        var raw = (window.location.hash || '').replace(/^#/, '');
        if (!raw) return { key: null, idx: null };

        var slashParts = raw.split('/');
        var key = normalizeKey(slashParts[0]);
        var idx = null;

        if (slashParts.length > 1) {
            var parsedIdx = parseInt(slashParts[1], 10);
            idx = isNaN(parsedIdx) ? null : parsedIdx;
        } else {
            var hashParams = new URLSearchParams(raw.replace(/^.*?\?/, ''));
            if (hashParams.has('gallery')) key = normalizeKey(hashParams.get('gallery'));
            if (hashParams.has('idx')) {
                var hashIdx = parseInt(hashParams.get('idx'), 10);
                idx = isNaN(hashIdx) ? null : hashIdx;
            }
        }

        return { key: key || null, idx: idx };
    }

    function getGalleryKeyFromPath() {
        var match = location.pathname.match(/(sub04\d{2})/i);
        if (!match) return null;
        var legacy = normalizeKey(match[1]);
        return galleryAlias[legacy] || legacy;
    }

    function getGalleryKey() {
        var hashInfo = parseHash();
        if (hashInfo.key) return galleryAlias[hashInfo.key] || hashInfo.key;

        var params = new URLSearchParams(window.location.search);
        var queryKey = normalizeKey(params.get('gallery'));
        var mappedQuery = galleryAlias[queryKey] || queryKey;
        if (mappedQuery) return mappedQuery;
        var pathKey = getGalleryKeyFromPath();
        if (pathKey) return pathKey;
        return DEFAULT_GALLERY_KEY;
    }

    function getIndexParam(total) {
        var hashInfo = parseHash();
        if (hashInfo.idx !== null) {
            var safeIdx = hashInfo.idx;
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

    function buildDetailUrl(key, idx) {
        if (!key) key = DEFAULT_GALLERY_KEY;
        var safeIdx = Math.max(0, Number(idx) || 0);
        return './sub-detail.html#' + encodeURIComponent(key) + '/' + safeIdx;
    }

    function mapApiItem(item, key) {
        var normalizedKey = (key || '').toLowerCase();
        var isVideo = normalizedKey === 'video';
        if (!item) return null;
        return {
            id: item.id || item.wr_id || '',
            title: item.title || item.subject || 'Untitled',
            img: item.img || item.thumb || item.detail || (isVideo ? '' : PLACEHOLDER_IMG),
            desc: item.desc || item.excerpt || '',
            video: item.video || item.video_url || ''
        };
    }

    function fetchGalleryData(key) {
        var category = galleryAlias[key] || key || DEFAULT_GALLERY_KEY;
        if (!API_ENDPOINT) return Promise.resolve(null);
        return fetch(API_ENDPOINT + '?category=' + encodeURIComponent(category))
            .then(function (resp) {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.items)) throw new Error('invalid');
                var mappedItems = data.items.map(function (itm) { return mapApiItem(itm, category); }).filter(Boolean);
                return {
                    key: key,
                    title: data.board_subject || data.category || key,
                    bg: data.bg || '',
                    listPage: listPages[key] || null,
                    items: mappedItems,
                    source: 'api'
                };
            })
            .catch(function (err) {
                console.warn('[sub04] Gallery API failed, falling back to static items.', {
                    endpoint: API_ENDPOINT,
                    category: category,
                    message: err && err.message ? err.message : err
                });
                return null;
            });
    }

    function applyPageMeta(config) {
        if (!document.body) return;
        document.body.dataset.category = document.body.dataset.category || 'Gallery';

        var title = (config && config.title) || document.body.dataset.title || document.body.dataset.current;
        var bg = (config && config.bg) || document.body.dataset.bg;

        if (title) {
            document.body.dataset.title = title;
            document.body.dataset.current = title;
            document.title = title + ' | Genebiotech';
        }
        if (bg) document.body.dataset.bg = bg;
    }

    function ensureCardCount($gallery, desired) {
        var current = $gallery.find('.card').length;
        if (!current || desired <= current) return;
        var $template = $gallery.find('.card').last();
        for (var i = current; i < desired; i++) {
            var $clone = $template.clone(true);
            var $img = $clone.find('img').first();
            if ($img.length) {
                $img.attr('src', $img.attr('src') || '');
                $img.attr('alt', '');
            }
            $gallery.append($clone);
        }
    }

    function getStaticItemsFromDom($gallery) {
        var arr = [];
        $gallery.find('.card').each(function (idx) {
            var $img = $(this).find('img').first();
            var title = $img.attr('alt') || ('Item ' + (idx + 1));
            arr.push({
                id: idx,
                title: title,
                img: $img.attr('src') || PLACEHOLDER_IMG,
                desc: ''
            });
        });
        return arr;
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

    $(function () {
        var isDetail = isDetailPage();

        function renderPage() {
            var galleryKey = getGalleryKey() || DEFAULT_GALLERY_KEY;

            return fetchGalleryData(galleryKey).then(function (config) {
                if (!config) {
                    var fallbackItems = getStaticItemsFromDom($('.cont1 .gallery'));
                    config = { key: galleryKey, items: fallbackItems, listPage: listPages[galleryKey] || null, source: 'static' };
                }

                applyPageMeta(config);

                if (isDetail) {
                    initDetail(config, galleryKey);
                    initBackToList(config);
                    return;
                }

                initBackToList(config);
                initListing(config, galleryKey);
            }).catch(function () {
                var fallbackItems = getStaticItemsFromDom($('.cont1 .gallery'));
                var fallback = { key: galleryKey, items: fallbackItems, listPage: listPages[galleryKey] || null, source: 'static' };
                applyPageMeta(fallback);

                if (isDetail) {
                    initDetail(fallback, galleryKey);
                    initBackToList(fallback);
                    return;
                }

                initBackToList(fallback);
                initListing(fallback, galleryKey);
            });
        }

        renderPage();

        if (isDetail) {
            $(window).off('hashchange.sub04').on('hashchange.sub04', function () {
                renderPage();
            });
        }
    });

    function initListing(config, galleryKey) {
        var $gallery = $('.cont1 .gallery');
        var $pagination = $('.cont1 .pagination');

        if (!$gallery.length || !$pagination.length) return;

        var items = (config && Array.isArray(config.items)) ? config.items.slice() : [];
        var isApiResult = config && config.source === 'api';
        if (!items.length && !isApiResult) {
            items = getStaticItemsFromDom($gallery);
        }

        if (!items.length) {
            $gallery.html('<li class="board-empty">No posts available.</li>');
            if ($pagination.length) $pagination.hide();
            return;
        }

        ensureCardCount($gallery, items.length);

        var $cards = $gallery.find('.card');
        var totalItems = Math.min(items.length, $cards.length);

        var $pagesContainer = $pagination.find('.pages');
        var $prevBtn = $pagination.find('.nav.prev');
        var $nextBtn = $pagination.find('.nav.next');
        var itemsPerPage = getItemsPerPage();
        var totalPages = calculateTotalPages(itemsPerPage, totalItems);
        var currentPage = 1;
        var resizeTimer = null;
        var safeKey = galleryKey || getGalleryKeyFromPath() || DEFAULT_GALLERY_KEY;

        var isVideoPage = safeKey === 'video';
        var fallbackImg = isVideoPage ? '' : PLACEHOLDER_IMG;

        function applyCardContent() {
            $cards.each(function (idx) {
                var item = items[idx];
                if (!item) {
                    $(this).hide();
                    return;
                }
                var imgSrc = item.img || item.thumb || item.detail || (isVideoPage ? '' : fallbackImg);
                var videoSrc = item.video || item.video_url || '';
                var $card = $(this);
                var $link = $card.find('a').first();
                var $img = $card.find('img').first();
                var $btn = $card.find('.btn').first();

                $card.find('video.card-video-fallback').remove();
                var useVideoFallback = isVideoPage && !imgSrc && videoSrc;

                $card.attr('data-idx', idx);
                $link.attr('href', buildDetailUrl(safeKey, idx));
                if (useVideoFallback) {
                    $img.hide();
                    var $video = $('<video/>', {
                        'class': 'card-video-fallback',
                        src: videoSrc,
                        muted: true,
                        playsinline: true,
                        preload: 'metadata',
                        loop: true,
                        'aria-label': item.title || 'Video preview'
                    }).css({
                        width: '100%',
                        height: '100%',
                        display: 'block',
                        objectFit: 'cover',
                        background: '#000',
                        aspectRatio: '16 / 9'
                    });
                    if ($btn.length) {
                        $video.insertBefore($btn);
                    } else {
                        $link.append($video);
                    }
                } else {
                    if (imgSrc) {
                        $img.show().attr('src', imgSrc);
                    } else {
                        $img.hide();
                    }
                    if (item.title) $img.attr('alt', item.title);
                }
            });
        }

        applyCardContent();
        $gallery.addClass('ready');
        refreshScrollAfterImages($gallery);

        function render(page) {
            page = Math.max(1, Math.min(page, totalPages));
            currentPage = page;

            var start = (page - 1) * itemsPerPage;
            var end = start + itemsPerPage;

            $cards.each(function (idx) {
                var withinData = idx < totalItems;
                var visible = withinData && idx >= start && idx < end;
                $(this).toggle(visible);
            });

            if ($pagesContainer.length) {
                $pagesContainer.find('.page').each(function (idx) {
                    $(this).toggleClass('active', idx === (page - 1));
                });
            }

            if ($prevBtn.length) $prevBtn.prop('disabled', page === 1);
            if ($nextBtn.length) $nextBtn.prop('disabled', page === totalPages);
            if ($pagination.length) $pagination.show(); 

            refreshScrollAfterImages($gallery);
        }

        function buildPages() {
            if (!$pagesContainer.length) return;
            $pagesContainer.empty();
            for (var i = 1; i <= totalPages; i++) {
                var $page = $('<span/>', {
                    'class': 'page' + (i === currentPage ? ' active' : ''),
                    'data-page': i,
                    text: i
                });
                $pagesContainer.append($page);
            }
        }

        function handlePageClick(e) {
            var $target = $(e.target).closest('.page');
            if (!$target.length) return;
            var page = parseInt($target.data('page'), 10);
            if (!isNaN(page)) render(page);
        }

        function handlePrev() {
            render(currentPage - 1);
        }

        function handleNext() {
            render(currentPage + 1);
        }

        function handleCardClick(e) {
            var $card = $(this).closest('.card');
            var idx = parseInt($card.data('idx'), 10);
            var href = buildDetailUrl(safeKey, isNaN(idx) ? 0 : idx);
            if (!href) return;
            e.preventDefault();
            window.location.href = href;
        }

        buildPages();
        render(currentPage);

        $pagesContainer.on('click', handlePageClick);
        $prevBtn.on('click', handlePrev);
        $nextBtn.on('click', handleNext);
        $gallery.on('click', '.card a', handleCardClick);

        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(handleResize, 150);
        });

        function handleResize() {
            var newItemsPerPage = getItemsPerPage();
            if (newItemsPerPage === itemsPerPage) return;

            itemsPerPage = newItemsPerPage;
            totalPages = calculateTotalPages(itemsPerPage, totalItems);
            currentPage = Math.min(currentPage, totalPages);

            buildPages();
            render(currentPage);
        }
    }

    function initDetail(config, galleryKey) {
        var items = (config && Array.isArray(config.items)) ? config.items : [];
        if (!items.length) {
            var fallbackItems = getStaticItemsFromDom($('.cont1 .gallery'));
            items = fallbackItems;
        }
        var idx = getIndexParam(items.length);
        var item = items[idx] || items[0] || null;
        var $mediaWrap = $('.cont1 .media');
        var $mediaImg = $mediaWrap.find('img');
        var $desc = $('.cont1 .desc');
        var $prevBtn = $('.navbtn.navprev');
        var $nextBtn = $('.navbtn.navnext');
        var refreshScroll = debounce(function () {
            if (window.scrollInstance?.update) window.scrollInstance.update();
            if (window.ScrollTrigger?.refresh) window.ScrollTrigger.refresh();
        }, 80);

        if (item) {
            if (item.video) {
                $mediaWrap.empty();
                var $video = $('<video/>', {
                    controls: true,
                    playsinline: true,
                    preload: 'metadata',
                    poster: item.thumb || item.img || item.detail || ''
                });
                $video.css({
                    width: '100%',
                    maxWidth: '1170px',
                    height: 'auto',
                    display: 'block',
                    aspectRatio: '16 / 9',
                    objectFit: 'contain'
                });
                var $source = $('<source/>', { src: item.video, type: 'video/mp4' });
                $video.append($source);
                $mediaWrap.append($video);

                var scheduleRefresh = function () {
                    refreshScroll();
                    setTimeout(refreshScroll, 160);
                    setTimeout(refreshScroll, 480);
                };

                $video.on('loadedmetadata loadeddata canplay canplaythrough play pause seeking seeked timeupdate ended', scheduleRefresh);
                setTimeout(scheduleRefresh, 120);
            } else if ($mediaImg.length) {
                $mediaImg.attr('src', item.img || item.thumb || item.detail || $mediaImg.attr('src'));
                $mediaImg.attr('alt', item.title || $mediaImg.attr('alt') || 'Gallery detail image');
                if ($mediaImg[0] && $mediaImg[0].complete) {
                    refreshScrollAfterImages($mediaWrap);
                } else {
                    $mediaImg.one('load error', function () { refreshScrollAfterImages($mediaWrap); });
                }
            }
            if ($desc.length) {
                $desc.text(item.desc || FALLBACK_DESC);
            }
            if (item.title) {
                document.title = item.title + ' | Genebiotech';
            } else if (config && config.title) {
                document.title = config.title + ' | Genebiotech';
            }
        } else if ($desc.length) {
            $desc.text(FALLBACK_DESC);
            if (config && config.title) {
                document.title = config.title + ' | Genebiotech';
            }
        }

        function goTo(targetIdx) {
            window.location.href = buildDetailUrl(galleryKey || getGalleryKeyFromPath() || DEFAULT_GALLERY_KEY, targetIdx);
        }

        var hasPrev = idx > 0;
        var hasNext = idx + 1 < items.length;

        var navUrl = function (targetIdx) {
            return buildDetailUrl(galleryKey || getGalleryKeyFromPath() || DEFAULT_GALLERY_KEY, targetIdx);
        };

        function bindNav($btn, targetIdx, label) {
            var href = navUrl(targetIdx);
            $btn.prop('disabled', false).attr('aria-disabled', 'false').removeClass('muted')
                .css({ pointerEvents: '', cursor: '' });
            if ($btn.is('a')) {
                $btn.attr('href', href);
            } else {
                $btn.attr('data-href', href);
            }
            $btn.off('click.sub04nav').on('click.sub04nav', function (e) {
                e.preventDefault();
                window.location.href = href;
            });
            if (label) $btn.find('.btntext').text(label);
        }

        if ($prevBtn.length) {
            if (hasPrev) {
                $prevBtn.removeClass('muted');
                bindNav($prevBtn, idx - 1, items[idx - 1].title || 'Previous');
            } else {
                $prevBtn.addClass('muted');
                $prevBtn.find('.btntext').text('There are no previous posts.');
                $prevBtn.off('click.sub04nav').removeAttr('href data-href')
                    .prop('disabled', true).attr('aria-disabled', 'true')
                    .css({ pointerEvents: 'none', cursor: 'default' });
            }
        }

        if ($nextBtn.length) {
            if (hasNext) {
                $nextBtn.removeClass('muted');
                bindNav($nextBtn, idx + 1, items[idx + 1].title || 'Next');
            } else {
                $nextBtn.addClass('muted');
                $nextBtn.find('.btntext').text('There are no more posts.');
                $nextBtn.off('click.sub04nav').removeAttr('href data-href')
                    .prop('disabled', true).attr('aria-disabled', 'true')
                    .css({ pointerEvents: 'none', cursor: 'default' });
            }
        }

        refreshScrollAfterImages($mediaWrap);
    }

    function initBackToList(config) {
        var $backBtn = $('.backbtn');
        if (!$backBtn.length) return;

        var fallbackList = (config && config.listPage) || (function () {
            var key = getGalleryKeyFromPath() || DEFAULT_GALLERY_KEY;
            return listPages[key] || './sub0401.html';
        })();

        $backBtn.attr('href', fallbackList);
        $backBtn.off('click.sub04back').on('click.sub04back', function (e) {
            e.preventDefault();
            window.location.href = fallbackList;
        });
    }

    function getItemsPerPage() {
        var width = window.innerWidth;

        if (width <= 480) return 4;
        if (width <= 768) return 6;
        return 9;
    }

    function calculateTotalPages(perPage, totalItems) {
        var count = (typeof totalItems === 'number' && totalItems > 0) ? totalItems : 0;
        return Math.max(1, Math.ceil(count / perPage));
    }

    function debounce(fn, wait) {
        var t;
        return function () {
            clearTimeout(t);
            var args = arguments;
            var ctx = this;
            t = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait || 0);
        };
    }
})();
