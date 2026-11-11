(function () {
    'use strict';
    var app = document.getElementById('booking-app');
    if (!app) return;
    var selection = document.getElementById('booking-selection');
    var pending = document.getElementById('booking-pending');
    var confirmed = document.getElementById('booking-confirmed');
    var loading = document.getElementById('calendar-loading');
    var fallback = document.getElementById('calendar-fallback');
    var attempts = [1500, 3000, 5000, 8000, 12000];
    var polling = false;
    var expectedBookingUid = '';
    function show(element) { if (element) element.hidden = false; }
    function hide(element) { if (element) element.hidden = true; }
    function safeLink(url, label, className) {
        if (!url || !/^https?:\/\//i.test(url)) return null;
        var link = document.createElement('a'); link.href = url; link.textContent = label;
        link.target = '_blank'; link.rel = 'noopener'; if (className) link.className = className; return link;
    }
    function displayConfirmation(data) {
        var start = data.starts_at ? new Date(data.starts_at) : null;
        var zone = data.timezone || (Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local time');
        var dateText = start && !isNaN(start.getTime()) ? new Intl.DateTimeFormat('en-US', {dateStyle: 'long', timeStyle: 'short', timeZone: zone}).format(start) : 'the selected time';
        document.getElementById('confirmed-when').textContent = dateText + ' ' + zone;
        document.getElementById('confirmed-date').textContent = dateText;
        document.getElementById('confirmed-timezone').textContent = zone;
        var links = document.getElementById('booking-links'); links.replaceChildren();
        var join = safeLink(data.meeting_url, 'JOIN THE SESSION →', 'button'); if (join) links.appendChild(join);
        var reschedule = safeLink(data.reschedule_url, 'Reschedule'); var cancel = safeLink(data.cancel_url, 'cancel');
        if (reschedule || cancel) {
            var change = document.createElement('p'); change.append('Need to change the time? ');
            if (reschedule) change.appendChild(reschedule); if (reschedule && cancel) change.append(' or '); if (cancel) change.appendChild(cancel); change.append('.'); links.appendChild(change);
        }
        hide(selection); hide(pending); show(confirmed); confirmed.focus({preventScroll: true});
        if (window.MondoMeta && data.meta_event_id) {
            window.MondoMeta.track('Schedule', {content_name: 'Private Italian Wine Session'}, data.meta_event_id);
        }
    }
    function poll(index) {
        if (index >= attempts.length) { show(document.getElementById('pending-timeout')); polling = false; return; }
        window.setTimeout(function () {
            fetch(app.dataset.statusUrl, {headers: {'Accept': 'application/json', 'X-Cal-Booking-Uid': expectedBookingUid}, credentials: 'same-origin'})
                .then(function (response) { if (!response.ok) throw new Error('status'); return response.json(); })
                .then(function (data) { if (data.confirmed) displayConfirmation(data); else poll(index + 1); })
                .catch(function () { poll(index + 1); });
        }, attempts[index]);
    }
    function awaitWebhook(event) {
        if (polling) return;
        expectedBookingUid = event && event.detail && event.detail.data && event.detail.data.uid ? String(event.detail.data.uid) : '';
        polling = true; hide(selection); hide(confirmed); show(pending); poll(0);
    }
    function failCalendar() { hide(loading); show(fallback); }
    var config; try { config = JSON.parse(app.dataset.config || 'null'); } catch (error) { config = null; }
    if (!config || !config.origin || !config.calLink || !document.getElementById('cal-booking')) return;
    (function (C, A, L) {
        var push = function (api, args) { api.q.push(args); }; var d = C.document;
        C.Cal = C.Cal || function () {
            var cal = C.Cal, args = arguments;
            if (!cal.loaded) { cal.ns = {}; cal.q = cal.q || []; var script = d.createElement('script'); script.src = A; script.async = true; script.onerror = failCalendar; d.head.appendChild(script); cal.loaded = true; }
            if (args[0] === L) { var api = function () { push(api, arguments); }; var namespace = args[1]; api.q = api.q || []; if (typeof namespace === 'string') { cal.ns[namespace] = api; push(api, args); } else push(cal, args); return; }
            push(cal, args);
        };
    })(window, config.origin + '/embed/embed.js', 'init');
    Cal('init', 'mondodivino', {origin: config.origin});
    Cal.ns.mondodivino('inline', {elementOrSelector: '#cal-booking', calLink: config.calLink, config: {layout: 'month_view', name: app.dataset.firstName, email: app.dataset.email, 'metadata[funnel_reference]': app.dataset.reference}});
    Cal.ns.mondodivino('ui', {hideEventTypeDetails: false, layout: 'month_view', styles: {branding: {brandColor: '#8d2637'}}});
    Cal.ns.mondodivino('on', {action: 'linkReady', callback: function () { hide(loading); }});
    Cal.ns.mondodivino('on', {action: 'linkFailed', callback: failCalendar});
    Cal.ns.mondodivino('on', {action: 'bookingSuccessfulV2', callback: awaitWebhook});
    Cal.ns.mondodivino('on', {action: 'rescheduleBookingSuccessfulV2', callback: awaitWebhook});
    window.setTimeout(function () { if (loading && !loading.hidden) failCalendar(); }, 15000);
})();
