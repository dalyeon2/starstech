/**
 * sub0103 Section 2 history scroll behavior
 */
(function ($, w, d) {
    'use strict';
    if (!$) return;

    function prefersReducedMotion() {
        return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    var $section = $('.cont2');
    if (!$section.length) return;

    var $left = $section.find('.left').first();
    var $track = $section.find('.track').first();
    var $groups = $track.find('.group');
    var $tabs = $section.find('.tab');

    if (!$groups.length) return;

    var $title = $left.find('.title').first();
    var $photo = $left.find('.photo').first();
    var $summary = $left.find('.summary').first();

    var observer = null;
    var groupTriggers = [];
    var scrollSync = null;
    var tabOffset = 0;
    var activeId = '';
    var hasInitialized = false;
    var resizeTimer = null;

    function scrollRootEl() {
        var cfg = w.SancSmoothScrollConfig || {};
        var scroller = cfg.scroller;
        var doc = d.documentElement;
        var body = d.body;
        var fallback = d.scrollingElement || doc || body;
        var root = null;

        if (typeof scroller === 'string') {
            root = d.querySelector(scroller);
        } else if (scroller && scroller.nodeType === 1) {
            root = scroller;
        }

        if (!root) {
            if (doc && body) {
                var docScroll = (doc.scrollHeight || 0) - (doc.clientHeight || 0);
                var bodyScroll = (body.scrollHeight || 0) - (body.clientHeight || 0);
                root = bodyScroll > docScroll ? body : doc;
            } else {
                root = fallback;
            }
        }

        if (root && root !== fallback) {
            var scrollable = (root.scrollHeight || 0) > (root.clientHeight || 0) + 1;
            if (!scrollable) root = fallback;
        }

        return root || fallback;
    }

    function scrollerEl() {
        if (w.scrollInstance && w.scrollInstance.__isLoco) {
            return $('[data-scroll-container]').get(0) || w;
        }
        var root = scrollRootEl();
        if (root && root !== d.documentElement && root !== d.body && root !== d.scrollingElement) {
            return root;
        }
        return w;
    }

    function headerOffset() {
        var value = w.getComputedStyle(d.documentElement).getPropertyValue('--header-height') || '0';
        var num = parseFloat(value);
        return Number.isFinite(num) ? num : 0;
    }

    function historyOffset() {
        if (!$section.length) return 0;
        var value = w.getComputedStyle($section.get(0)).getPropertyValue('--history-offset') || '0';
        var num = parseFloat(value);
        return Number.isFinite(num) ? num : 0;
    }

    function setGroupGaps() {
        $groups.each(function () {
            var count = $(this).find('.item').length;
            if (count >= 3) {
                this.style.setProperty('--group-gap', 'clamp(24px, 3vh, 48px)');
            } else {
                this.style.removeProperty('--group-gap');
            }
        });
    }

    function measure() {
        tabOffset = 0;
        if (!$track.length) return;

        setGroupGaps();

        var headEl = $section.find('.head').get(0);
        var headHeight = headEl ? headEl.getBoundingClientRect().height : 0;
        $section.get(0).style.setProperty('--history-head-height', headHeight + 'px');
        tabOffset = headerOffset() + headHeight + historyOffset();

        var leftEl = $left.get(0);
        var viewport = w.innerHeight || d.documentElement.clientHeight || 0;
        var leftHeight = leftEl ? leftEl.getBoundingClientRect().height : 0;
        var available = Math.max(0, viewport - tabOffset);
        var centerOffset = Math.max(0, (available - leftHeight) * 0.5);
        var leftNudge = parseFloat(w.getComputedStyle($section.get(0)).getPropertyValue('--history-left-nudge') || '0');
        if (!Number.isFinite(leftNudge)) leftNudge = 0;
        var leftTop = Math.round(tabOffset + centerOffset + leftNudge);
        $section.get(0).style.setProperty('--history-left-top', leftTop + 'px');
    }

    function setActiveTab(id) {
        if (!id || id === activeId) return;
        activeId = id;
        $tabs.removeClass('on').removeAttr('aria-current');
        var $tab = $tabs.filter('[href="#' + id + '"]');
        if ($tab.length) {
            $tab.addClass('on').attr('aria-current', 'page');
        }
    }

    function applyGroup($group) {
        if (!$group || !$group.length) return;
        var id = $group.attr('id') || '';
        if (id && id === activeId) return;
        var title = $group.data('title');
        var photo = $group.data('photo');
        var summary = $group.data('summary');
        var update = function () {
            if (title) $title.text(title);
            if (photo) $photo.attr('src', photo);
            if (summary) $summary.text(summary);
        };

        if (!hasInitialized || prefersReducedMotion() || !$left.length) {
            update();
            $left.removeClass('is-switching');
        } else {
            $left.addClass('is-switching');
            update();
            w.requestAnimationFrame(function () {
                w.requestAnimationFrame(function () {
                    $left.removeClass('is-switching');
                });
            });
        }

        hasInitialized = true;
        setActiveTab(id);
    }

    function cleanup() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        if (groupTriggers.length) {
            groupTriggers.forEach(function (trigger) {
                if (trigger && typeof trigger.kill === 'function') {
                    trigger.kill();
                }
            });
            groupTriggers = [];
        }
        if (scrollSync && w.scrollInstance && typeof w.scrollInstance.off === 'function') {
            w.scrollInstance.off('scroll', scrollSync);
        }
        scrollSync = null;
        if (w.gsap && $track.length) {
            w.gsap.set($track, { clearProps: 'transform' });
        }
        $section.removeClass('pin');
    }

    function createObserver() {
        if (!('IntersectionObserver' in w)) {
            applyGroup($groups.eq(0));
            return;
        }
        var root = scrollRootEl();
        var rootEl = (root && root.nodeType === 1 && root !== d.body && root !== d.documentElement && root !== d.scrollingElement)
            ? root
            : null;
        var marginTop = Math.round(tabOffset);
        var margin = '-' + marginTop + 'px 0px -45% 0px';
        observer = new IntersectionObserver(function (entries) {
            var best = null;
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                if (!best || entry.intersectionRatio > best.intersectionRatio) {
                    best = entry;
                }
            });
            if (best) {
                applyGroup($(best.target));
            }
        }, { root: rootEl, rootMargin: margin, threshold: [0.1, 0.35, 0.6] });

        $groups.each(function () {
            observer.observe(this);
        });
    }

    function build() {
        cleanup();
        measure();
        if (!(w.gsap && w.ScrollTrigger)) {
            createObserver();
            return;
        }

        w.gsap.registerPlugin(w.ScrollTrigger);

        if (w.ScrollToPlugin) {
            w.gsap.registerPlugin(w.ScrollToPlugin);
        }

        var scroller = scrollerEl();
        groupTriggers = $groups.map(function (_, el) {
            var $group = $(el);
            var firstItem = $group.find('.item').first().get(0) || el;
            return w.ScrollTrigger.create({
                trigger: firstItem,
                endTrigger: el,
                scroller: scroller,
                start: function () { return 'top top+=' + Math.round(tabOffset); },
                end: function () { return 'bottom top+=' + Math.round(tabOffset); },
                onEnter: function () {
                    applyGroup($group);
                },
                onEnterBack: function () {
                    applyGroup($group);
                }
            });
        }).get();

        if (!scrollSync && w.scrollInstance && typeof w.scrollInstance.on === 'function') {
            scrollSync = function () {
                w.ScrollTrigger.update();
            };
            w.scrollInstance.on('scroll', scrollSync);
        }

        w.ScrollTrigger.refresh();
        if (w.scrollInstance && typeof w.scrollInstance.update === 'function') {
            w.scrollInstance.update();
        }
    }

    function getTargetScroll(targetEl) {
        if (!targetEl) return null;
        var root = scrollRootEl();
        var rootTop = 0;
        if (root && root.getBoundingClientRect) {
            rootTop = root.getBoundingClientRect().top || 0;
        }
        var baseScroll = 0;
        if (root && root !== d.documentElement && root !== d.body && root !== d.scrollingElement) {
            baseScroll = root.scrollTop || 0;
        } else {
            baseScroll = w.pageYOffset || d.documentElement.scrollTop || 0;
        }
        var rect = targetEl.getBoundingClientRect();
        return rect.top - rootTop + baseScroll;
    }

    function scrollToTarget(targetEl, offset) {
        var base = typeof offset === 'number' ? offset : 0;
        var y = 0;
        var root = scrollRootEl();
        var target = (root && root !== d.documentElement && root !== d.body && root !== d.scrollingElement) ? root : w;

        if (w.scrollInstance && typeof w.scrollInstance.scrollTo === 'function') {
            if (typeof w.__snapSetOff === 'function') {
                w.__snapSetOff(true);
            }
            w.scrollInstance.scrollTo(targetEl, { duration: 0.8, disableLerp: false, offset: -base });
            if (w.ScrollTrigger) {
                w.ScrollTrigger.update();
            }
            setTimeout(function () {
                if (typeof w.__snapSetOff === 'function') {
                    w.__snapSetOff(false);
                }
            }, 900);
            return;
        }

        if (w.gsap && w.ScrollToPlugin) {
            w.gsap.to(target, {
                scrollTo: { y: targetEl, offsetY: base, autoKill: false },
                duration: 0.9,
                ease: 'power2.out',
                overwrite: true
            });
            return;
        }

        y = getTargetScroll(targetEl);
        if (y == null) return;
        y = Math.max(0, y - base);

        if (target && target !== w && typeof target.scrollTo === 'function') {
            target.scrollTo({ top: y, behavior: 'smooth' });
            return;
        }

        w.scrollTo({ top: y, behavior: 'smooth' });
    }

    $tabs.off('click.cont2tabs').on('click.cont2tabs', function (e) {
        var href = $(this).attr('href') || '';
        if (href.charAt(0) !== '#') return;
        var id = href.slice(1);
        var $group = $groups.filter('#' + id);
        if (!$group.length) return;
        measure();
        var targetEl = $group.find('.item').first().get(0) || $group.get(0);
        if (!targetEl) return;
        e.preventDefault();
        scrollToTarget(targetEl, tabOffset);
        applyGroup($group);
    });

    applyGroup($groups.eq(0));
    build();

    $(w).off('resize.cont2history').on('resize.cont2history', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(build, 150);
    });

    $(w).on('load.cont2history', function () {
        build();
    });

    $(d).on('scrollengine:ready', function () {
        build();
    });
})(window.jQuery, window, document);
