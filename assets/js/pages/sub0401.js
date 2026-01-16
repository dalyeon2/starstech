/**
 * sub0401 Brand video popup
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    var bound = false;

    function initBrandVideoPopup() {
        if (bound) return;

        var $pop = $('.pop').first();
        if (!$pop.length) return;

        bound = true;

        var $box = $pop.find('.box').first();
        var $media = $pop.find('.media').first();
        var $link = $pop.find('.link').first();
        var $close = $pop.find('.close').first();
        var $dim = $pop.find('.dim').first();
        if (!$box.length || !$media.length) return;

        var scrollLockActive = false;
        var lastFocus = null;
        var lockOptions = { passive: false, capture: true };

        function isOpen() {
            return $pop.attr('aria-hidden') === 'false';
        }

        function lockScroll(e) {
            if (!isOpen()) return;
            var boxEl = $box.get(0);
            if (boxEl && boxEl.contains(e.target)) {
                var canScroll = boxEl.scrollHeight > boxEl.clientHeight + 1;
                if (canScroll) return;
            }
            e.preventDefault();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            e.stopPropagation();
        }

        function setScrollLock(locked) {
            if (locked && !scrollLockActive) {
                document.addEventListener('wheel', lockScroll, lockOptions);
                document.addEventListener('touchmove', lockScroll, lockOptions);
                scrollLockActive = true;
            } else if (!locked && scrollLockActive) {
                document.removeEventListener('wheel', lockScroll, lockOptions);
                document.removeEventListener('touchmove', lockScroll, lockOptions);
                scrollLockActive = false;
            }
        }

        function clearMedia() {
            var video = $media.find('video').get(0);
            if (video) {
                try {
                    video.pause();
                } catch (e) {
                }
                video.removeAttribute('src');
                if (typeof video.load === 'function') video.load();
            }
            var frame = $media.find('iframe').get(0);
            if (frame) {
                frame.setAttribute('src', '');
            }
            $media.empty();
        }

        function normalizeEmbedSrc(raw) {
            if (!raw) return '';
            var src = raw.trim();
            if (!/^https?:/i.test(src)) return src;
            try {
                var url = new URL(src);
                var host = (url.hostname || '').replace(/^www\./i, '');
                if (host === 'youtu.be') {
                    var id = url.pathname.replace('/', '');
                    if (id) return 'https://www.youtube.com/embed/' + id;
                }
                if (host.indexOf('youtube.com') !== -1) {
                    if (url.pathname === '/watch' && url.searchParams.get('v')) {
                        return 'https://www.youtube.com/embed/' + url.searchParams.get('v');
                    }
                    if (url.pathname.indexOf('/embed/') === 0) return src;
                }
                if (host.indexOf('vimeo.com') !== -1) {
                    var parts = url.pathname.split('/').filter(Boolean);
                    var vimeoId = parts[parts.length - 1];
                    if (vimeoId) return 'https://player.vimeo.com/video/' + vimeoId;
                }
            } catch (e) {
                return src;
            }
            return src;
        }

        function addAutoplay(src) {
            if (!src || !/^https?:/i.test(src)) return src;
            try {
                var url = new URL(src);
                url.searchParams.set('autoplay', '1');
                return url.toString();
            } catch (e) {
                return src;
            }
        }

        function buildMedia(src) {
            $media.empty();
            if (!src) return;

            var isVideoFile = /\.(mp4|webm|ogg)(\?.*)?$/i.test(src);
            if (isVideoFile || !/^https?:/i.test(src)) {
                var video = document.createElement('video');
                video.src = src;
                video.controls = true;
                video.playsInline = true;
                video.setAttribute('playsinline', '');
                video.setAttribute('webkit-playsinline', '');
                video.preload = 'metadata';
                $media.append(video);
                var playPromise = video.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(function () {
                    });
                }
                return;
            }

            var frame = document.createElement('iframe');
            var titleText = ($pop.find('.title').first().text() || 'Video').trim();
            frame.src = addAutoplay(normalizeEmbedSrc(src));
            frame.title = titleText || 'Video';
            frame.allow = 'autoplay; encrypted-media; picture-in-picture';
            frame.allowFullscreen = true;
            $media.append(frame);
        }

        function setLink(linkSrc) {
            if (!$link.length) return;
            if (linkSrc) {
                $link.attr('href', linkSrc);
                $link.attr('aria-hidden', 'false');
                $link.removeAttr('tabindex');
            } else {
                $link.attr('href', '#');
                $link.attr('aria-hidden', 'true');
                $link.attr('tabindex', '-1');
            }
        }

        function openPopup(trigger) {
            var $trigger = $(trigger);
            var videoSrc = ($trigger.data('video') || $trigger.attr('href') || '').toString().trim();
            var linkSrc = ($trigger.data('link') || $trigger.attr('href') || '').toString().trim();

            setLink(linkSrc);
            buildMedia(videoSrc);
            lastFocus = document.activeElement;
            $pop.attr('aria-hidden', 'false');
            document.documentElement.classList.add('popon');
            document.body.classList.add('popon');
            setScrollLock(true);
            if (w.scrollInstance && typeof w.scrollInstance.stop === 'function') {
                w.scrollInstance.stop();
            }
            if ($close.length) $close.focus();
        }

        function closePopup() {
            if (!isOpen()) return;
            $pop.attr('aria-hidden', 'true');
            document.documentElement.classList.remove('popon');
            document.body.classList.remove('popon');
            setScrollLock(false);
            if (w.scrollInstance && typeof w.scrollInstance.start === 'function') {
                w.scrollInstance.start();
            }
            clearMedia();
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }
        }

        $(document).off('click.popvideo').on('click.popvideo', '[data-pop="video"]', function (e) {
            e.preventDefault();
            openPopup(this);
        });

        if ($close.length) {
            $close.off('click.popvideo').on('click.popvideo', function (e) {
                e.preventDefault();
                closePopup();
            });
        }

        if ($dim.length) {
            $dim.off('click.popvideo').on('click.popvideo', function (e) {
                e.preventDefault();
                closePopup();
            });
        }

        $(document).off('keydown.popvideo').on('keydown.popvideo', function (e) {
            if (e.key === 'Escape') closePopup();
        });
    }

    $(initBrandVideoPopup);
})(window.jQuery, window);
