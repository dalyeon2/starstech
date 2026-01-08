/**
 * Main Page
 */
(function ($, w, d) {
    const NEWS_ENDPOINT = '../api/news.php';
    const NEWS_PLACEHOLDER_IMG = '../assets/img/common/default-image.jpg';

    function prefersReducedMotion() {
        return w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function resolveLang() {
        const match = w.location.pathname.match(/\/(ko|en|ja|fr|mn)\//i);
        return match ? match[1].toLowerCase() : '';
    }

    function buildNewsEndpoint(limit) {
        if (!NEWS_ENDPOINT) return '';
        const params = [];
        const lang = resolveLang();
        if (lang) params.push('lang=' + encodeURIComponent(lang));
        if (typeof limit === 'number' && limit > 0) params.push('limit=' + limit);
        if (!params.length) return NEWS_ENDPOINT;
        return NEWS_ENDPOINT + '?' + params.join('&');
    }

    function getDetailBase() {
        return /\/pages\//i.test(w.location.pathname) ? './sub-detail.html' : './pages/sub-detail.html';
    }

    /* Section 1: intro splash */
    function initIntroVideoLoop() {
        const $section = $('.cont1');
        if (!$section.length) return;

        const $bg = $section.find('.bg');
        const $video = $bg.find('video').first();
        if (!$bg.length || !$video.length) return;

        const videoA = $video.get(0);
        if (!videoA || typeof videoA.play !== 'function') return;

        const safePlay = (video, onFail) => {
            try {
                const promise = video.play();
                if (promise && typeof promise.catch === 'function') {
                    promise.catch(() => {
                        if (onFail) onFail();
                    });
                }
            } catch (e) {
                if (onFail) onFail();
            }
        };

        const bindManualPlay = () => {
            if ($section.data('videoManualBound')) return;
            $section.data('videoManualBound', true);

            const trigger = () => safePlay(videoA);
            $section.on('touchstart.videoplay click.videoplay', trigger);
            $video.on('play.videoplay', function () {
                $section.off('touchstart.videoplay click.videoplay', trigger);
                $video.off('play.videoplay');
            });
        };

        videoA.muted = true;
        videoA.setAttribute('muted', '');
        videoA.playsInline = true;
        videoA.setAttribute('playsinline', '');
        videoA.setAttribute('webkit-playsinline', '');

        if (prefersReducedMotion()) return;

        const isCompact = w.matchMedia
            && (w.matchMedia('(max-width: 1024px)').matches || w.matchMedia('(pointer: coarse)').matches);

        if (isCompact) {
            safePlay(videoA, bindManualPlay);
            return;
        }

        if ($bg.data('seamlessInit')) return;
        $bg.data('seamlessInit', true);

        const videoB = videoA.cloneNode(true);
        videoB.removeAttribute('id');
        videoB.removeAttribute('autoplay');
        videoB.autoplay = false;
        videoB.muted = true;
        videoB.setAttribute('muted', '');
        videoB.playsInline = true;
        videoB.setAttribute('playsinline', '');
        videoB.setAttribute('webkit-playsinline', '');
        videoB.setAttribute('aria-hidden', 'true');
        videoB.tabIndex = -1;

        $bg.addClass('is-seamless');
        $video.addClass('is-active');
        $bg.append(videoB);

        let active = videoA;
        let standby = videoB;
        let swapping = false;
        let watchTimer = null;
        const fadeMs = 500;
        const overlapSec = 0.55;

        const resetVideo = (video) => {
            try {
                video.pause();
            } catch (e) {
            }
            try {
                video.currentTime = 0;
            } catch (e) {
            }
        };

        const swapVideos = () => {
            if (swapping) return;
            swapping = true;

            resetVideo(standby);
            safePlay(standby);
            standby.classList.add('is-active');
            active.classList.remove('is-active');

            const prev = active;
            active = standby;
            standby = prev;

            setTimeout(() => {
                resetVideo(standby);
                swapping = false;
            }, fadeMs + 60);
        };

        const checkLoop = () => {
            if (swapping) return;
            const duration = active.duration;
            if (!isFinite(duration) || duration <= 0) return;
            if (duration - active.currentTime <= overlapSec) {
                swapVideos();
            }
        };

        const startWatch = () => {
            if (watchTimer) return;
            watchTimer = w.setInterval(checkLoop, 180);
        };

        if (isFinite(videoA.duration) && videoA.duration > 0) {
            startWatch();
        } else {
            videoA.addEventListener('loadedmetadata', startWatch, { once: true });
        }

        safePlay(videoA, bindManualPlay);
    }

    /* Section 2: overview */
    function initOverview() {
        const $section = $('.cont2');
        if (!$section.length || !w.gsap) return;

        const $headline = $section.find('.headline');
        const $stats = $section.find('.stats');
        const $lead = $section.find('.lead');
        const $desc = $section.find('.desc');
        const $image = $section.find('.visual');
        if (!$headline.length && !$stats.length && !$image.length && !$lead.length && !$desc.length) return;

        function formatValue(value) {
            return Math.round(value).toLocaleString('en-US');
        }

        function runCount(instant, force) {
            $stats.find('.value').each(function () {
                const $el = $(this);
                if ($el.data('counted') && !force) return;
                $el.data('counted', true);

                const text = ($el.text() || '').trim();
                const suffixMatch = text.match(/[^0-9.,-]+$/);
                const suffix = suffixMatch ? suffixMatch[0] : '';
                const rawTarget = $el.data('count');
                let target = Number(rawTarget);
                if (!isFinite(target)) {
                    target = Number(text.replace(/[^0-9.-]/g, ''));
                }
                if (!isFinite(target)) target = 0;

                if (instant || prefersReducedMotion()) {
                    $el.text(formatValue(target) + suffix);
                    return;
                }

                $el.text('0' + suffix);
                const counter = { value: 0 };
                w.gsap.to(counter, {
                    value: target,
                    duration: 1.4,
                    ease: 'power3.out',
                    onUpdate: function () {
                        $el.text(formatValue(counter.value) + suffix);
                    }
                });
            });
        }

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) {
            runCount(true);
            return;
        }

        const tl = w.gsap.timeline({ paused: true });

        if ($headline.length) {
            tl.from($headline.get(0), { autoAlpha: 0, y: 50, duration: 0.5, ease: 'power3.out' });
        }
        if ($stats.length) {
            const statsPosition = $headline.length ? '-=0.2' : 0;
            tl.from($stats.get(0), {
                autoAlpha: 0,
                y: 50,
                duration: 0.45,
                ease: 'power3.out',
                onStart: function () {
                    runCount(false, true);
                }
            }, statsPosition);
        }
        const textTargets = [];
        if ($lead.length) textTargets.push($lead.get(0));
        if ($desc.length) textTargets.push($desc.get(0));
        if ($image.length) textTargets.push($image.get(0));
        if (textTargets.length) {
            const textPosition = ($headline.length || $stats.length) ? '-=0.15' : 0;
            tl.from(textTargets, {
                autoAlpha: 0,
                y: 50,
                duration: 0.5,
                ease: 'power3.out',
                stagger: 0.08
            }, textPosition);
        }

        w.ScrollTrigger.create({
            trigger: $section.get(0),
            start: 'top 65%',
            once: true,
            onEnter: function () {
                tl.restart();
            }
        });
    }

    /* Section 4: about */
    function initAbout() {
        const $section = $('.cont4');
        if (!$section.length || !w.gsap) return;

        const leftTargets = $section.find('.left').toArray();
        const rightItems = $section.find('.right .item').toArray();
        if (!leftTargets.length && !rightItems.length) return;

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) return;

        const tl = w.gsap.timeline({ paused: true });

        if (leftTargets.length) {
            tl.from(leftTargets, {
                autoAlpha: 0,
                y: 50,
                duration: 0.5,
                ease: 'power3.out',
                stagger: 0.08
            });
        }

        if (rightItems.length) {
            tl.from(rightItems, {
                autoAlpha: 0,
                y: 50,
                duration: 0.55,
                ease: 'power3.out',
                stagger: 0.18
            }, leftTargets.length ? '-=0.1' : 0);
        }

        w.ScrollTrigger.create({
            trigger: $section.get(0),
            start: 'top 70%',
            once: true,
            onEnter: function () {
                tl.restart();
            }
        });
    }

    /* Section 5: results */
    function initResults() {
        const $section = $('.cont5');
        if (!$section.length) return;

        const $cards = $section.find('.card');
        if ($cards.length < 2) return;

        let index = 0;

        function setActive(nextIndex) {
            $cards.each(function (cardIndex) {
                const $card = $(this);
                const isActive = cardIndex === nextIndex;
                $card.toggleClass('active', isActive);

                const $icon = $card.find('.icon');
                if ($icon.length) {
                    const onSrc = $icon.data('on');
                    const offSrc = $icon.data('off');
                    const nextSrc = isActive ? onSrc : offSrc;
                    if (nextSrc && $icon.attr('src') !== nextSrc) {
                        $icon.attr('src', nextSrc);
                    }
                }
            });
            index = nextIndex;
        }

        setActive(0);

        if (prefersReducedMotion()) {
            return;
        }

        w.setInterval(function () {
            setActive((index + 1) % $cards.length);
        }, 2000);
    }

    /* Section 6: exports */
    function initExports() {
        const $section = $('.cont6');
        if (!$section.length || !w.gsap) return;

        const $left = $section.find('.left');
        const $head = $section.find('.head');
        const $exports = $section.find('.exports');
        if (!$left.length && !$head.length && !$exports.length) return;

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) return;

        const tl = w.gsap.timeline({ paused: true });
        const introTargets = [];
        if ($left.length) introTargets.push($left.get(0));
        if ($head.length) introTargets.push($head.get(0));

        let hasIntro = false;
        if (introTargets.length) {
            tl.from(introTargets, {
                autoAlpha: 0,
                y: 50,
                duration: 0.55,
                ease: 'power3.out',
                stagger: 0.1
            });
            hasIntro = true;
        }

        if ($exports.length) {
            tl.from($exports.get(0), {
                autoAlpha: 0,
                y: 50,
                duration: 0.5,
                ease: 'power3.out'
            }, hasIntro ? '+=0.1' : 0);
        }

        w.ScrollTrigger.create({
            trigger: $section.get(0),
            start: 'top 70%',
            once: true,
            onEnter: function () {
                tl.restart();
            }
        });
    }

    /* Section 7: news */
    function initNewsFeed() {
        const $section = $('.cont7');
        if (!$section.length || !w.fetch) return;

        const $track = $section.find('.cards');
        if (!$track.length) return;

        const endpoint = buildNewsEndpoint(8);
        if (!endpoint) return;

        const renderCards = (items) => {
            const detailBase = getDetailBase();
            $track.empty();
            items.forEach((item, idx) => {
                const cover = item.thumb || (item.images && item.images[0] ? item.images[0].src : NEWS_PLACEHOLDER_IMG);
                const href = detailBase + '#news/' + idx;
                const $card = $('<a/>', { 'class': 'card', href });
                const $img = $('<img/>', { 'class': 'photo', src: cover, alt: item.title || '' });
                const $content = $('<div/>', { 'class': 'content' });
                const $meta = $('<div/>', { 'class': 'meta' });
                const $date = $('<span/>', { 'class': 'date', text: item.date || '' });
                const $title = $('<p/>', { 'class': 'title', text: item.title || '' });
                const $badge = $('<span/>', { 'class': 'badge', text: 'Newsroom' });
                const $more = $('<i/>', { 'class': 'more', 'aria-hidden': 'true' });

                $meta.append($date, $title);
                $content.append($meta, $badge);
                $card.append($img, $content, $more);
                $track.append($card);
            });
            initNewsCards();
        };

        w.fetch(endpoint)
            .then((resp) => {
                if (!resp.ok) throw new Error('network');
                return resp.json();
            })
            .then((data) => {
                if (!data || !Array.isArray(data.items) || !data.items.length) return;
                renderCards(data.items);
            })
            .catch(() => {
                // Keep static cards when fetch fails.
            });
    }

    function initNewsCards() {
        const $section = $('.cont7');
        if (!$section.length) return;

        const $track = $section.find('.cards');
        const $cards = $track.children('.card');
        const $prev = $section.find('.nav.prev');
        const $next = $section.find('.nav.next');

        if (!$track.length || !$cards.length) return;

        let index = 0;
        let step = 0;
        let maxIndex = 0;

        function getLayout(width) {
            if (width <= 480) {
                return { mode: 'stacked', columns: 1, rows: 2 };
            }
            if (width <= 768) return { mode: 'slider', columns: 2 };
            if (width <= 1024) return { mode: 'slider', columns: 3 };
            return { mode: 'grid', columns: 4 };
        }

        function updateButtons() {
            if (!$section.hasClass('is-slider')) {
                $prev.prop('disabled', false).attr('aria-disabled', 'false');
                $next.prop('disabled', false).attr('aria-disabled', 'false');
                return;
            }

            const disablePrev = index <= 0;
            const disableNext = index >= maxIndex;
            $prev.prop('disabled', disablePrev).attr('aria-disabled', String(disablePrev));
            $next.prop('disabled', disableNext).attr('aria-disabled', String(disableNext));
        }

        function applyTransform() {
            $track.css('transform', 'translateX(' + (-index * step) + 'px)');
        }

        function resetSlider() {
            $section.removeClass('is-slider is-stacked');
            $track.css('transform', '');
            $cards.each(function () {
                this.style.flex = '';
            });
            updateButtons();
        }

        function applyLayout() {
            const width = w.innerWidth || d.documentElement.clientWidth;
            const layout = getLayout(width);

            if (layout.mode === 'grid') {
                resetSlider();
                return;
            }

            $section.addClass('is-slider');
            $section.toggleClass('is-stacked', layout.mode === 'stacked');

            const trackEl = $track.get(0);
            const trackWidth = trackEl.clientWidth;
            const styles = w.getComputedStyle(trackEl);
            const columnGap = parseFloat(styles.columnGap || styles.gap || 0) || 0;

            if (layout.mode === 'stacked') {
                const rows = layout.rows;
                step = trackWidth + columnGap;
                maxIndex = Math.max(0, Math.ceil($cards.length / rows) - 1);
                if (index > maxIndex) index = maxIndex;

                $cards.each(function () {
                    this.style.flex = '';
                });

                applyTransform();
                updateButtons();
                return;
            }

            const columns = layout.columns;
            const cardWidth = Math.floor((trackWidth - columnGap * (columns - 1)) / columns);

            step = cardWidth + columnGap;
            maxIndex = Math.max(0, $cards.length - columns);
            if (index > maxIndex) index = maxIndex;

            $cards.each(function () {
                this.style.flex = '0 0 ' + cardWidth + 'px';
            });

            applyTransform();
            updateButtons();
        }

        $prev.off('click.cont7cards').on('click.cont7cards', function () {
            if (!$section.hasClass('is-slider') || index <= 0) return;
            index -= 1;
            applyTransform();
            updateButtons();
        });

        $next.off('click.cont7cards').on('click.cont7cards', function () {
            if (!$section.hasClass('is-slider') || index >= maxIndex) return;
            index += 1;
            applyTransform();
            updateButtons();
        });

        applyLayout();

        let resizeTimer;
        $(w).off('resize.cont7cards').on('resize.cont7cards', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(applyLayout, 150);
        });
    }

    function initNews() {
        const $section = $('.cont7');
        if (!$section.length || !w.gsap) return;

        const $head = $section.find('.head');
        const $list = $section.find('.list');
        if (!$head.length && !$list.length) return;

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) return;

        const tl = w.gsap.timeline({ paused: true });

        if ($head.length) {
            tl.from($head.get(0), {
                autoAlpha: 0,
                y: 50,
                duration: 0.55,
                ease: 'power3.out'
            });
        }

        if ($list.length) {
            const listPosition = $head.length ? '-=0.2' : 0;
            tl.from($list.get(0), {
                autoAlpha: 0,
                y: 50,
                duration: 0.5,
                ease: 'power3.out'
            }, listPosition);
        }

        w.ScrollTrigger.create({
            trigger: $section.get(0),
            start: 'top 70%',
            once: true,
            onEnter: function () {
                tl.restart();
            }
        });
    }

    /* Section 8: contact */
    function initContact() {
        const $section = $('.cont8');
        if (!$section.length || !w.gsap) return;

        const $head = $section.find('.box .head');
        if (!$head.length) return;

        if (w.ScrollTrigger) {
            w.gsap.registerPlugin(w.ScrollTrigger);
        }

        if (!w.ScrollTrigger || prefersReducedMotion()) return;

        const tl = w.gsap.timeline({ paused: true });

        tl.from($head.get(0), {
            autoAlpha: 0,
            y: 50,
            duration: 0.55,
            ease: 'power3.out'
        });

        w.ScrollTrigger.create({
            trigger: $section.get(0),
            start: 'top 75%',
            once: true,
            onEnter: function () {
                tl.restart();
            }
        });
    }

    $(function () {
        initIntroVideoLoop();
        initOverview();
        initResults();
        initAbout();
        initExports();
        initNewsCards();
        initNewsFeed();
        initNews();
        initContact();
    });
})(window.jQuery, window, document);
