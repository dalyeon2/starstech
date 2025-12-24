/**
 * Products (sub02) interactions
 */
(function ($) {
    var isBoundWin = false;
    var isBoundLoco = false;
    var $section, $left, $right;
    var startY = 0, maxShift = 0, headerH = 0;
    var lastShift = -1;

    function isDesktop() {
        return window.innerWidth > 1024;
    }

    function getScrollY() {
        if (window.scrollInstance?.__isLoco) {
            return window.scrollInstance?.scroll?.instance?.scroll?.y || 0;
        }
        return window.scrollY || 0;
    }

    function recalc() {
        if (!$section || !$section.length) return;
        headerH = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-height')) || 0;
        var rightTop = $right.offset().top;
        var rightH = $right.outerHeight();
        var leftH = $left.outerHeight();
        startY = rightTop - headerH - 10;
        maxShift = Math.max(0, rightH - leftH);
    }

    function apply(y) {
        if (!$left) return;
        if (!isDesktop()) {
            $left.css('transform', 'translate3d(0,0,0)');
            lastShift = 0;
            return;
        }
        var delta = y - startY;
        var shift = Math.max(0, Math.min(delta, maxShift));
        if (shift === lastShift) return;
        lastShift = shift;
        $left.css('transform', 'translate3d(0,' + shift + 'px,0)');
    }

    function bindScroll() {
        if (window.scrollInstance?.__isLoco) {
            if (isBoundLoco) return;
            isBoundLoco = true;
            window.scrollInstance.on('scroll', function (e) {
                apply((e && e.scroll && e.scroll.y) || 0);
            });
            apply(getScrollY());
            return;
        }
        if (isBoundWin) return;
        isBoundWin = true;
        $(window).on('scroll.productHeadline', function () {
            apply(getScrollY());
        });
        apply(getScrollY());
    }

    function initProductHeadline() {
        if ((document.body.dataset.category || '').toLowerCase() !== 'products') return;
        $section = $('.cont1');
        $left = $section.find('.left');
        $right = $section.find('.right .list');
        if (!$left.length || !$right.length) return;

        // reset transform
        $left.css('transform', 'translate3d(0,0,0)');
        lastShift = 0;

        var refreshLayout = function () {
            recalc();
            apply(getScrollY());
        };

        // wait for images to settle to avoid jump
        if ($section.imagesLoaded) {
            $section.imagesLoaded(refreshLayout);
        } else {
            refreshLayout();
        }

        bindScroll();
        applyRevealClasses();
    }

    function applyRevealClasses() {
        if ((document.body.dataset.category || '').toLowerCase() !== 'products') return;
        var $cont = $('.cont1');
        if (!$cont.length) return;
        // remove reveal classes to avoid scroll jank on fast scroll
        $cont.find('.reveal-up, .reveal-group, .reveal-item')
            .removeClass('reveal-up reveal-group reveal-item');
    }

    $(document).on('components:ready', function () {
        initProductHeadline();
        applyRevealClasses();
    });

    $(document).on('scrollengine:ready', function () {
        recalc();
        apply(getScrollY());
        bindScroll();
        applyRevealClasses();
    });

    $(window).on('resize', function () {
        recalc();
        apply(getScrollY());
        applyRevealClasses();
    });

    // run once on load in case components:ready already fired
    $(function () {
        initProductHeadline();
        applyRevealClasses();
    });
})(jQuery);
