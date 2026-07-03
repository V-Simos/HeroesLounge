/* lounge.js — Heroes Lounge theme behaviors.
   Loads at the end of <body> after vendor/jquery.js and {% framework extras %},
   so the DOM is parsed and window.jQuery/jQuery.request exist by the time we run. */
(function () {
    'use strict';

    /* ---------- tabs ----------
       [data-tabs] container delegates clicks from buttons carrying
       data-tab-target="<panel-id>"; .on marks the active button, panels
       toggle via the [hidden] attribute (see components.css .tabs/.table). */
    document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
        tabs.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-tab-target]');
            if (!btn || !tabs.contains(btn)) return;
            var target = document.getElementById(btn.dataset.tabTarget);
            if (!target) return;
            tabs.querySelectorAll('[data-tab-target]').forEach(function (b) {
                b.classList.toggle('on', b === btn);
                b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                var panel = document.getElementById(b.dataset.tabTarget);
                if (panel) panel.hidden = panel !== target;
            });
        });
    });

    /* ---------- countdowns ----------
       Every [data-countdown="<ISO datetime>"] ticks down as "xD HH:MM:SS"
       (mockup format, e.g. "2D 04:12:33"); at/after zero it reads "LIVE".
       One shared 1s interval drives all elements; unparsable dates are
       left untouched. */
    var countdowns = [];
    document.querySelectorAll('[data-countdown]').forEach(function (el) {
        var t = Date.parse(el.dataset.countdown);
        if (!isNaN(t)) countdowns.push({ el: el, t: t });
    });
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        var now = Date.now();
        countdowns.forEach(function (c) {
            var s = Math.floor((c.t - now) / 1000);
            if (s <= 0) { c.el.textContent = 'LIVE'; return; }
            c.el.textContent = Math.floor(s / 86400) + 'D '
                + pad(Math.floor(s / 3600) % 24) + ':'
                + pad(Math.floor(s / 60) % 60) + ':'
                + pad(s % 60);
        });
    }
    if (countdowns.length) { tick(); setInterval(tick, 1000); }

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
    }
})();
