/**
 * sub0103 Section 2 history scroll behavior
 */
(function ($, w, d) {
    'use strict';
    if (!$) return;

    var BREAKPOINT = 1024;
    var $section = $('.cont2');
    if (!$section.length) return;

    var $layout = $section.find('.layout').first();
    var $left = $section.find('.left').first();
    var $right = $section.find('.right').first();
    var $track = $section.find('.track').first();
    var $groups = $track.find('.group');
    var $tabs = $section.find('.tab');

    if (!$groups.length) return;

    var $title = $left.find('.title').first();
    var $photo = $left.find('.photo').first();
    var $summary = $left.find('.summary').first();

    var pinTrigger = null;
    var trackTween = null;
    var observer = null;
    var scrollSync = null;
    var groupOffsets = [];
    var tabOffsets = [];
    var maxScroll = 0;
    var panelHeight = 0;
    var pinBuffer = 0;
    var totalScroll = 0;
    var pinTop = 0;
    var tabOffset = 0;
    var activeId = '';
    var resizeTimer = null;

    function scrollRootEl() {
        var cfg = w.SancSmoothScrollConfig || {};
        var scroller = cfg.scroller;
        if (typeof scroller === 'string') {
            var root = d.querySelector(scroller);
            if (root) return root;
        } else if (scroller && scroller.nodeType === 1) {
            return scroller;
        }
        return d.scrollingElement || d.documentElement || d.body;
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
        maxScroll = 0;
        groupOffsets = [];
        tabOffsets = [];
        panelHeight = 0;
        pinBuffer = 0;
        totalScroll = 0;
        pinTop = 0;
        tabOffset = 0;
        if (!$track.length || !$right.length) return;

        setGroupGaps();

        if (w.gsap) {
            w.gsap.set($track, { y: 0 });
        } else {
            $track.css('transform', '');
        }

        var trackEl = $track.get(0);
        var rightEl = $right.get(0);
        var headEl = $section.find('.head').get(0);
        var headHeight = headEl ? headEl.getBoundingClientRect().height : 0;
        pinTop = headerOffset();
        tabOffset = headerOffset() + headHeight + 20;
        panelHeight = rightEl.clientHeight || 0;
        $section.get(0).style.setProperty('--panel-height', panelHeight + 'px');

        var firstEl = $groups.get(0);
        var lastEl = $groups.get($groups.length - 1);
        var firstHeight = firstEl ? (firstEl.offsetHeight || 0) : 0;
        var lastHeight = lastEl ? (lastEl.offsetHeight || 0) : 0;
        var padTop = Math.max(0, (panelHeight - firstHeight) * 0.5);
        var padBottom = Math.max(0, (panelHeight - lastHeight) * 0.5);
        trackEl.style.setProperty('--track-pad-top', padTop + 'px');
        trackEl.style.setProperty('--track-pad-bottom', padBottom + 'px');

        groupOffsets = $groups.map(function () {
            var top = this.offsetTop || 0;
            return Math.max(0, top);
        }).get();
        maxScroll = Math.max(0, (trackEl.scrollHeight || 0) - (rightEl.clientHeight || 0));
        tabOffsets = $groups.map(function () {
            var top = this.offsetTop || 0;
            var firstItem = this.querySelector('.item');
            var itemOffset = firstItem ? (firstItem.offsetTop || 0) : 0;
            var target = Math.max(0, top + itemOffset);
            return Math.min(target, maxScroll);
        }).get();
        pinBuffer = Math.round(Math.max(80, panelHeight * 0.18));
        totalScroll = maxScroll + pinBuffer * 2;
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
        var title = $group.data('title');
        var photo = $group.data('photo');
        var summary = $group.data('summary');
        if (title) $title.text(title);
        if (photo) $photo.attr('src', photo);
        if (summary) $summary.text(summary);
        setActiveTab($group.attr('id'));
    }

    function updateActiveByScroll(scrollY) {
        if (!groupOffsets.length) return;
        var idx = 0;
        for (var i = 0; i < groupOffsets.length; i += 1) {
            if (scrollY + 1 >= groupOffsets[i]) idx = i;
        }
        var $group = $groups.eq(idx);
        if ($group.length && $group.attr('id') !== activeId) {
            applyGroup($group);
        }
    }

    function cleanup() {
        if (pinTrigger) {
            pinTrigger.kill();
            pinTrigger = null;
        }
        if (trackTween) {
            if (trackTween.tween && typeof trackTween.tween.kill === 'function') {
                trackTween.tween.kill();
            } else if (typeof trackTween.kill === 'function') {
                trackTween.kill();
            }
            trackTween = null;
        }
        if (observer) {
            observer.disconnect();
            observer = null;
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
        }, { root: null, threshold: [0.2, 0.4, 0.6] });

        $groups.each(function () {
            observer.observe(this);
        });
    }

    function build() {
        cleanup();
        var pinCandidate = w.gsap && w.ScrollTrigger && w.innerWidth > BREAKPOINT;
        if (pinCandidate) {
            $section.addClass('pin');
        }
        measure();

        var canPin = pinCandidate && maxScroll > 0;
        if (!canPin) {
            $section.removeClass('pin');
            createObserver();
            return;
        }

        w.gsap.registerPlugin(w.ScrollTrigger);

        if (w.ScrollToPlugin) {
            w.gsap.registerPlugin(w.ScrollToPlugin);
        }

        trackTween = w.gsap.quickTo($track.get(0), 'y', { duration: 0.28, ease: 'power2.out' });

        pinTrigger = w.ScrollTrigger.create({
            trigger: $layout.get(0),
            scroller: scrollerEl(),
            start: function () { return 'top top+=' + Math.round(pinTop); },
            end: function () { return '+=' + totalScroll; },
            pin: $layout.get(0),
            pinSpacing: true,
            anticipatePin: 2,
            invalidateOnRefresh: true,
            onRefreshInit: function () {
                measure();
            },
            onUpdate: function (self) {
                var scrollY = self.progress * totalScroll - pinBuffer;
                scrollY = Math.max(0, Math.min(maxScroll, scrollY));
                if (trackTween) {
                    trackTween(-scrollY);
                } else if (w.gsap) {
                    w.gsap.set($track, { y: -scrollY });
                } else {
                    $track.css('transform', 'translateY(' + (-scrollY) + 'px)');
                }
                updateActiveByScroll(scrollY);
            }
        });

        $section.addClass('pin');
        updateActiveByScroll(0);

        if (!scrollSync && w.scrollInstance && typeof w.scrollInstance.on === 'function' && w.ScrollTrigger) {
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

    function getTargetScroll(id) {
        var $target = $('#' + id);
        if (!$target.length) return null;

        if (pinTrigger && typeof pinTrigger.start === 'number') {
            var idx = $groups.index($target);
            if (idx > -1 && tabOffsets[idx] != null) {
                return pinTrigger.start + pinBuffer + tabOffsets[idx];
            }
        }

        var rect = $target.get(0).getBoundingClientRect();
        return rect.top + (w.pageYOffset || d.documentElement.scrollTop || 0);
    }

    function scrollToY(targetY, offset) {
        var base = typeof offset === 'number' ? offset : 0;
        var y = Math.max(0, targetY - base);
        var root = scrollRootEl();
        var target = (root && root !== d.documentElement && root !== d.body && root !== d.scrollingElement) ? root : w;

        if (w.scrollInstance && typeof w.scrollInstance.scrollTo === 'function') {
            if (typeof w.__snapSetOff === 'function') {
                w.__snapSetOff(true);
            }
            w.scrollInstance.scrollTo(y, { duration: 0.8, disableLerp: false });
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
                scrollTo: { y: y, autoKill: false },
                duration: 0.9,
                ease: 'power2.out',
                overwrite: true
            });
            return;
        }

        w.scrollTo({ top: y, behavior: 'smooth' });
    }

    $tabs.off('click.cont2tabs').on('click.cont2tabs', function (e) {
        var href = $(this).attr('href') || '';
        if (href.charAt(0) !== '#') return;
        var id = href.slice(1);
        var targetY = getTargetScroll(id);
        if (targetY == null) return;
        e.preventDefault();
        scrollToY(targetY, pinTrigger ? 0 : tabOffset);
        applyGroup($groups.filter('#' + id));
    });

    applyGroup($groups.eq(0));
    build();

    $(w).off('resize.cont2history').on('resize.cont2history', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(build, 150);
    });

    $(d).on('scrollengine:ready', function () {
        build();
    });
})(window.jQuery, window, document);
