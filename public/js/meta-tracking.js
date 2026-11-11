(function () {
    'use strict';

    var configNode = document.getElementById('meta-tracking-config');
    if (!configNode) return;

    var config;
    try { config = JSON.parse(configNode.textContent || '{}'); } catch (error) { return; }
    if (!config.enabled || !/^\d+$/.test(String(config.pixelId || ''))) return;

    var banner = document.querySelector('[data-meta-consent]');
    var manage = document.querySelector('[data-meta-manage]');
    var accept = document.querySelector('[data-meta-accept]');
    var decline = document.querySelector('[data-meta-decline]');
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var pixelReady = false;
    var pageViewSent = false;
    var queuedEvents = Array.isArray(config.browserEvents) ? config.browserEvents.slice() : [];

    function cookie(name) {
        var prefix = encodeURIComponent(name) + '=';
        var item = document.cookie.split('; ').find(function (entry) { return entry.indexOf(prefix) === 0; });
        return item ? decodeURIComponent(item.slice(prefix.length)) : '';
    }

    function setCookie(name, value, days) {
        var maxAge = Math.max(1, Number(days || 180)) * 86400;
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) + '; Path=/; Max-Age=' + maxAge + '; SameSite=Lax' + secure;
    }

    function removeCookie(name) {
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = encodeURIComponent(name) + '=; Path=/; Max-Age=0; SameSite=Lax' + secure;
    }

    function eventId(prefix) {
        var value = window.crypto && typeof window.crypto.randomUUID === 'function'
            ? window.crypto.randomUUID()
            : Date.now().toString(36) + Math.random().toString(36).slice(2);
        return prefix + '_' + value;
    }

    function request(url, body) {
        if (!url || !csrf) return Promise.resolve();
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify(body),
        }).catch(function () { return null; });
    }

    function identifiers() {
        return {fbp: cookie('_fbp') || null, fbc: cookie('_fbc') || null};
    }

    function syncConsent(status) {
        var ids = identifiers();
        return request(config.consentUrl, {status: status, fbp: ids.fbp, fbc: ids.fbc});
    }

    function loadPixel() {
        if (pixelReady) return;
        /* Meta Pixel base loader; execution is intentionally delayed until consent. */
        (function (f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
            if (!f._fbq) f._fbq = n;
            n.push = n; n.loaded = true; n.version = '2.0'; n.queue = [];
            t = b.createElement(e); t.async = true; t.src = v;
            s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
        })(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        window.fbq('init', String(config.pixelId));
        pixelReady = true;
    }

    function fire(name, parameters, id) {
        if (!pixelReady || !window.fbq || !name || !id) return;
        var storageKey = 'mdv_meta_event_' + id;
        try {
            if (sessionStorage.getItem(storageKey)) return;
            sessionStorage.setItem(storageKey, '1');
        } catch (error) {}
        window.fbq('track', name, parameters || {}, {eventID: id});
    }

    function flushEvents() {
        queuedEvents.splice(0).forEach(function (event) {
            fire(event.name, event.parameters || {}, event.event_id);
        });
    }

    function sendPageView() {
        if (pageViewSent) return;
        pageViewSent = true;
        var id = eventId('pageview');
        fire('PageView', {}, id);
        window.setTimeout(function () {
            var ids = identifiers();
            request(config.pageViewUrl, {
                event_id: id,
                fbp: ids.fbp,
                fbc: ids.fbc,
                event_source_url: location.href,
                referrer_url: document.referrer || null,
            });
            syncConsent('granted');
        }, 500);
    }

    function enable() {
        loadPixel();
        sendPageView();
        flushEvents();
        if (banner) banner.hidden = true;
        if (manage) manage.hidden = false;
    }

    function choose(status) {
        setCookie(config.consentCookie || 'mdv_meta_consent', status, config.consentLifetimeDays);
        if (status === 'granted') {
            enable();
        } else {
            removeCookie('_fbp');
            removeCookie('_fbc');
            syncConsent('denied');
            if (banner) banner.hidden = true;
            if (manage) manage.hidden = false;
        }
    }

    window.MondoMeta = {
        track: function (name, parameters, id) {
            var event = {name: name, parameters: parameters || {}, event_id: id || eventId(String(name || 'event').toLowerCase())};
            if (cookie(config.consentCookie || 'mdv_meta_consent') === 'granted') fire(event.name, event.parameters, event.event_id);
            else queuedEvents.push(event);
            return event.event_id;
        },
    };

    if (accept) accept.addEventListener('click', function () { choose('granted'); });
    if (decline) decline.addEventListener('click', function () { choose('denied'); });
    if (manage) manage.addEventListener('click', function () {
        if (banner) banner.hidden = false;
        manage.hidden = true;
    });

    var current = cookie(config.consentCookie || 'mdv_meta_consent');
    if (current === 'granted') enable();
    else if (current === 'denied') { if (manage) manage.hidden = false; }
    else if (banner) banner.hidden = false;
})();
