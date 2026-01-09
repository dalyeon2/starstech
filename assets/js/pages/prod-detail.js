/**
 * prod-detail thumbnail switcher
 */
(function ($, w) {
    'use strict';
    if (!$) return;

    function initThumbs() {
        var $galleries = $('.cont1 .gallery');
        if (!$galleries.length) return;

        $galleries.each(function () {
            var $gallery = $(this);
            var $main = $gallery.find('.main').first();
            var $thumbs = $gallery.find('.thumb');
            if (!$main.length || !$thumbs.length) return;

            function setActive($thumb) {
                var src = $thumb.data('full') || $thumb.attr('src');
                if (!src) return;
                $thumbs.removeClass('is-active');
                $thumb.addClass('is-active');
                $main.attr('src', src);
                if ($thumb.attr('alt')) {
                    $main.attr('alt', $thumb.attr('alt'));
                }
            }

            var mainSrc = $main.attr('src');
            var matched = false;
            $thumbs.each(function () {
                var $thumb = $(this);
                var thumbSrc = $thumb.data('full') || $thumb.attr('src');
                if (thumbSrc && thumbSrc === mainSrc) {
                    $thumb.addClass('is-active');
                    matched = true;
                    return false;
                }
                return undefined;
            });
            if (!matched) {
                $thumbs.eq(0).addClass('is-active');
            }

            $thumbs.off('click.prodDetail').on('click.prodDetail', function (e) {
                e.preventDefault();
                setActive($(this));
            });
        });
    }

    w.initProductThumbs = initThumbs;
    $(initThumbs);
})(window.jQuery, window);
