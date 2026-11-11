<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#18271f">
    <title>{{ $title }}</title>
    @isset($description)<meta name="description" content="{{ $description }}">@endisset
    <link rel="stylesheet" href="{{ asset('css/hvco.css') }}">
    @if(trim((string) config('services.meta.pixel_id')) !== '')
        <script id="meta-tracking-config" type="application/json">{!! json_encode([
            'enabled' => true,
            'pixelId' => (string) config('services.meta.pixel_id'),
            'pageViewUrl' => route('tracking.meta.page-view'),
            'consentUrl' => $metaConsentUrl ?? null,
            'consentCookie' => config('services.meta.consent_cookie'),
            'consentLifetimeDays' => config('services.meta.consent_lifetime_days'),
            'browserEvents' => array_values(array_merge(
                (array) session('meta_browser_events', []),
                (array) ($metaBrowserEvents ?? []),
            )),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script src="{{ asset('js/meta-tracking.js') }}" defer></script>
    @endif
    @stack('scripts')
</head>
<body>
    <main>@yield('content')</main>
    @if(trim((string) config('services.meta.pixel_id')) !== '')
        <aside class="meta-consent" data-meta-consent hidden aria-labelledby="meta-consent-title">
            <div>
                <strong id="meta-consent-title">Your privacy choices</strong>
                <p>With your permission, MondoDiVino and Meta use cookies and matching data to measure this funnel and improve relevant advertising. Declining does not affect access to the guide or session.</p>
                @if(config('services.meta.privacy_policy_url'))
                    <a href="{{ config('services.meta.privacy_policy_url') }}" target="_blank" rel="noopener">Learn how the data is used</a>
                @endif
            </div>
            <div class="meta-consent__actions">
                <button type="button" class="meta-consent__decline" data-meta-decline>Decline</button>
                <button type="button" class="meta-consent__accept" data-meta-accept>Allow Meta measurement</button>
            </div>
        </aside>
        <button type="button" class="meta-consent-manage" data-meta-manage hidden>Privacy choices</button>
    @endif
</body>
</html>
