/* lounge.js — Heroes Lounge theme behaviors.
   Loads at the end of <body> after vendor/jquery.js and {% framework extras %},
   so the DOM is parsed and window.jQuery/jQuery.request exist by the time we run. */
(function () {
    'use strict';

    /* ---------- tabs ----------
       [data-tabs] delegates activation for role=tab buttons carrying
       data-tab-target="<panel-id>". The active tab alone stays in the page Tab
       sequence; Left/Right/Home/End move focus and activate per the ARIA APG.
       Nested tab sets are isolated through the nearest [data-tabs] owner. */
    function ownedTabButtons(tabs) {
        return Array.prototype.filter.call(
            tabs.querySelectorAll('[role="tab"][data-tab-target]'),
            function (button) { return button.closest('[data-tabs]') === tabs; }
        );
    }

    function tabButtons(tabs) {
        return ownedTabButtons(tabs).filter(function (button) {
            return !button.hidden && !button.disabled;
        });
    }

    function activateTab(tabs, button, moveFocus) {
        var buttons = tabButtons(tabs);
        if (buttons.indexOf(button) === -1) return;
        var target = document.getElementById(button.dataset.tabTarget);
        if (!target) return;

        ownedTabButtons(tabs).forEach(function (candidate) {
            var selected = candidate === button;
            candidate.classList.toggle('on', selected);
            candidate.setAttribute('aria-selected', selected ? 'true' : 'false');
            candidate.setAttribute('tabindex', selected ? '0' : '-1');
            var panel = document.getElementById(candidate.dataset.tabTarget);
            if (panel) panel.hidden = !selected;
        });

        if (moveFocus) button.focus();
    }

    function initializeTabs(tabs) {
        var buttons = tabButtons(tabs);
        if (!buttons.length) return;

        var selected = buttons.filter(function (button) {
            return button.getAttribute('aria-selected') === 'true' || button.classList.contains('on');
        })[0] || buttons[0];
        activateTab(tabs, selected, false);

        tabs.addEventListener('click', function (event) {
            var button = event.target.closest('[role="tab"][data-tab-target]');
            if (!button || button.closest('[data-tabs]') !== tabs) return;
            if (tabButtons(tabs).indexOf(button) === -1) return;
            event.preventDefault();
            activateTab(tabs, button, false);
        });

        tabs.addEventListener('keydown', function (event) {
            var button = event.target.closest('[role="tab"][data-tab-target]');
            if (!button || button.closest('[data-tabs]') !== tabs) return;

            var currentButtons = tabButtons(tabs);
            var index = currentButtons.indexOf(button);
            if (index === -1) return;
            var nextIndex;
            switch (event.key) {
                case 'ArrowLeft':
                    nextIndex = (index - 1 + currentButtons.length) % currentButtons.length;
                    break;
                case 'ArrowRight':
                    nextIndex = (index + 1) % currentButtons.length;
                    break;
                case 'Home':
                    nextIndex = 0;
                    break;
                case 'End':
                    nextIndex = currentButtons.length - 1;
                    break;
                default:
                    return;
            }

            event.preventDefault();
            activateTab(tabs, currentButtons[nextIndex], true);
        });
    }

    document.querySelectorAll('[data-tabs]').forEach(initializeTabs);

    /* ---------- countdowns ----------
       Every [data-countdown="<datetime>"] ticks down as "xD HH:MM:SS"
       (mockup format, e.g. "2D 04:12:33"); at/after zero it reads "LIVE"
       (set once, then dropped, so text selection isn't disturbed). One
       shared 1s interval drives all elements and is cleared once none
       remain active; unparsable dates are left untouched.
       Datetime contract: templates must emit the value with Twig
       |date('c') (offset-qualified ISO 8601). Timezone-less strings
       ("2026-07-03T18:00:00" or "Y-m-d H:i:s") parse as VIEWER-local
       time — silently wrong for visitors in other timezones — and the
       space-separated form is NaN on some engines.
       The registry is rebuilt from the live DOM on October's
       ajaxUpdateComplete (bound below) so [data-countdown] elements
       injected by data-request partial swaps tick too and detached
       nodes are released. */
    var countdowns = [];
    var countdownTimer = null;
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var now = Date.now();
        countdowns = countdowns.filter(function (c) {
            var s = Math.floor((c.t - now) / 1000);
            if (s <= 0) { c.el.textContent = 'LIVE'; return false; }
            c.el.textContent = Math.floor(s / 86400) + 'D '
                + pad(Math.floor(s / 3600) % 24) + ':'
                + pad(Math.floor(s / 60) % 60) + ':'
                + pad(s % 60);
            return true;
        });
        if (!countdowns.length && countdownTimer !== null) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }
    function scanCountdowns() {
        countdowns = [];
        document.querySelectorAll('[data-countdown]').forEach(function (el) {
            var t = Date.parse(el.dataset.countdown);
            if (!isNaN(t)) countdowns.push({ el: el, t: t });
        });
        if (!countdowns.length) return;
        tick(); // paints immediately; already-finished entries get LIVE once and drop out
        if (countdowns.length && countdownTimer === null) countdownTimer = setInterval(tick, 1000);
    }
    scanCountdowns();

    /* ---------- mobile nav ----------
       #burger toggles .open on #site-links (elements arrive with the full
       layout in a later task; no-op until then). */
    var burger = document.getElementById('burger');
    var links = document.getElementById('site-links');
    if (burger && links) {
        burger.addEventListener('click', function () {
            var open = links.classList.toggle('open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* ---------- division sidebar height match ----------
       pages/season/division.htm: the rounds block (.divmain .rounds-block)
       and the timeline sidebar panel (.divside .timeline-panel) aren't
       CSS-alignable siblings — different grid columns, different preceding
       content (standings vs. recent+upcoming) — so matching their height
       needs a measurement, not a grid trick. Caps the timeline panel's
       height to the rounds block's rendered height; pages.css's
       .timeline-panel .tl scrolls the rest. Skipped below the 980px
       breakpoint where .divwrap collapses to one column (matching height to
       a section it's no longer beside would just clip it pointlessly).
       Re-measures on resize/breakpoint change and whenever a round tab
       switches, since different rounds can have different match counts and
       so a different .rounds-block height. */
    var roundsBlock = document.querySelector('.divmain .rounds-block');
    var timelinePanel = document.querySelector('.divside .timeline-panel');
    if (roundsBlock && timelinePanel) {
        var divWideQuery = window.matchMedia('(min-width: 981px)');
        var resizeSettle = null;

        function matchTimelineHeight() {
            timelinePanel.style.maxHeight = divWideQuery.matches
                ? roundsBlock.offsetHeight + 'px'
                : '';
        }

        matchTimelineHeight();

        window.addEventListener('resize', function () {
            clearTimeout(resizeSettle);
            resizeSettle = setTimeout(matchTimelineHeight, 150);
        });

        if (divWideQuery.addEventListener) {
            divWideQuery.addEventListener('change', matchTimelineHeight);
        } else {
            divWideQuery.addListener(matchTimelineHeight); // Safari < 14
        }

        roundsBlock.addEventListener('click', function (event) {
            // A round-tab switch changes [hidden] synchronously but the
            // resulting reflow isn't visible until the next frame.
            if (event.target.closest('[role="tab"]')) requestAnimationFrame(matchTimelineHeight);
        });
    }

    /* ---------- AJAX error toast ----------
       October v1's framework.js (a jQuery plugin) triggers the jQuery event
       'ajaxErrorMessage' on window from handleErrorMessage(); calling
       preventDefault() there suppresses its default alert(). Toasts stack in
       a fixed #toasts host (components.css), auto-dismiss after 6s, and can
       be clicked away. */
    function showToast(message) {
        var host = document.getElementById('toasts');
        if (!host) {
            host = document.createElement('div');
            host.id = 'toasts';
            document.body.appendChild(host);
        }
        var toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.title = 'Dismiss';
        toast.textContent = message || 'Something went wrong.';
        toast.addEventListener('click', function () { toast.remove(); });
        host.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 6000);
    }

    /* ---------- copy link (blog post share row) ----------
       [data-copy-link="<absolute url>"] (partials/blog/share.htm) writes that
       URL to the clipboard and confirms via showToast() above, so this
       doesn't invent a second notification pattern. navigator.clipboard needs
       a secure context; the fallback covers plain-HTTP dev/local use via a
       hidden textarea + execCommand('copy'). */
    document.querySelectorAll('[data-copy-link]').forEach(function (button) {
        button.addEventListener('click', function () {
            var url = button.dataset.copyLink;
            function done() { showToast('Link copied to clipboard.'); }
            function fail() { showToast('Could not copy the link.'); }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(done, fail);
                return;
            }
            var input = document.createElement('textarea');
            input.value = url;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            try {
                document.execCommand('copy') ? done() : fail();
            } catch (e) {
                fail();
            } finally {
                input.remove();
            }
        });
    });

    /* ---------- legacy static content: editor tables ----------
       Frozen static-page bodies (content/static-pages/*, rendered by
       layouts/static.htm inside .static-content) are byte-verbatim
       backend-authored HTML whose hand-sized Froala tables would otherwise
       force page-level horizontal overflow at narrow widths. Each table is
       wrapped in a .table-scroll container (pages.css: overflow-x: auto) so
       it scrolls itself. Purely presentational; the content is untouched. */
    document.querySelectorAll('.static-content table').forEach(function (table) {
        if (table.parentElement && table.parentElement.classList.contains('table-scroll')) return;
        var scroll = document.createElement('div');
        scroll.className = 'table-scroll';
        table.parentNode.insertBefore(scroll, table);
        scroll.appendChild(table);
    });

    if (window.jQuery) {
        window.jQuery(window).on('ajaxErrorMessage', function (event, message) {
            event.preventDefault();
            showToast(message);
        });
        /* Rebuild the countdown registry after October data-request partial
           swaps replace DOM regions (restarts the interval if needed). */
        window.jQuery(window).on('ajaxUpdateComplete', scanCountdowns);
    }
})();
