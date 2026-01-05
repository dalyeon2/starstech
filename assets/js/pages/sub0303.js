/**
 * sub0303 tabs
 */
(function ($) {
    'use strict';
    if (!$) return;

    function findPanelContainer($tabs) {
        var $parent = $tabs.parent();
        while ($parent.length) {
            if ($parent.children('.panels').length) {
                return $parent;
            }
            $parent = $parent.parent();
        }
        return $();
    }

    function setActive($tabs, $panels, idx) {
        var max = Math.min($tabs.length, $panels.length);
        if (!max) return;
        var safeIdx = Math.max(0, Math.min(idx, max - 1));

        $tabs.removeClass('active')
            .attr('aria-selected', 'false')
            .attr('tabindex', '-1');
        $panels.removeClass('active')
            .attr('aria-hidden', 'true');

        $tabs.eq(safeIdx)
            .addClass('active')
            .attr('aria-selected', 'true')
            .attr('tabindex', '0');
        $panels.eq(safeIdx)
            .addClass('active')
            .attr('aria-hidden', 'false');
    }

    function bindTabs($tabsWrap) {
        var $wrap = $($tabsWrap);
        var $parent = findPanelContainer($wrap);
        if (!$parent.length) return;

        var $panelsWrap = $parent.children('.panels').first();
        var $tabs = $wrap.find('.tab');
        var $panels = $panelsWrap.children('.panel');
        if (!$tabs.length || !$panels.length) return;

        var activeIdx = $tabs.index($tabs.filter('.active').first());
        if (activeIdx < 0) activeIdx = 0;
        setActive($tabs, $panels, activeIdx);

        $wrap.off('click.sub0303').on('click.sub0303', '.tab', function () {
            var idx = $tabs.index(this);
            if (idx < 0) return;
            setActive($tabs, $panels, idx);
        });
    }

    function refreshScroll() {
        if (window.scrollInstance && typeof window.scrollInstance.update === 'function') {
            window.scrollInstance.update();
        }
        if (window.ScrollTrigger && typeof window.ScrollTrigger.refresh === 'function') {
            window.ScrollTrigger.refresh();
        }
    }

    function bindMore($btn) {
        var $button = $($btn);
        var $group = $button.closest('.group');
        if (!$group.length) return;

        var $extra = $group.find('.list.extra');
        if (!$extra.length) {
            $button.hide();
            return;
        }

        $extra.each(function (idx) {
            var $list = $(this);
            if (!$list.attr('id')) {
                $list.attr('id', 'morelist-' + idx);
            }
            if (!$list.hasClass('show')) {
                $list.attr('aria-hidden', 'true');
            } else {
                $list.attr('aria-hidden', 'false');
            }
        });

        var controls = $extra.map(function () {
            return this.id || '';
        }).get().filter(Boolean);
        if (controls.length) {
            $button.attr('aria-controls', controls.join(' '));
        }

        function updateButton() {
            var hiddenCount = $extra.filter(':not(.show)').length;
            var hasHidden = hiddenCount > 0;
            $button.toggle(hasHidden);
            $button.attr('aria-expanded', hasHidden ? 'false' : 'true');
        }

        updateButton();

        $button.off('click.sub0303more').on('click.sub0303more', function () {
            var $next = $extra.filter(':not(.show)').first();
            if (!$next.length) {
                updateButton();
                return;
            }
            $next.addClass('show').attr('aria-hidden', 'false');
            $button.insertAfter($next);
            updateButton();
            refreshScroll();
        });
    }

    function updateTotals() {
        $('.cont1 .total').each(function () {
            var $total = $(this);
            var $panel = $total.closest('.panel');
            if (!$panel.length) return;
            var count = $panel.find('.card').length;
            $total.find('.num').text(count);
        });
    }

    $(function () {
        $('.cont1 .tabs').each(function () {
            bindTabs(this);
        });

        $('.cont1 .more').each(function () {
            bindMore(this);
        });

        updateTotals();
    });
})(window.jQuery);
