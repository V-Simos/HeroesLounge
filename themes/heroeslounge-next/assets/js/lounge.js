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
