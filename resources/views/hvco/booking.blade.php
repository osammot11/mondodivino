@extends('layouts.hvco', ['title' => 'Private Italian Wine Cellar & Taste Blueprint Session with Luca | MondoDiVino'])

@php
    $isConfirmed = $booking && in_array($booking->status, [\App\Enums\BookingStatus::Booked, \App\Enums\BookingStatus::Rescheduled], true);
    $bookingLocalStart = $isConfirmed && $booking->starts_at
        ? $booking->starts_at->setTimezone($booking->timezone ?: 'UTC')
        : null;
@endphp

@section('content')
<div id="booking-app" data-config="{{ json_encode($calConfig) }}" data-status-url="{{ $bookingStatusUrl }}" data-first-name="{{ $lead->first_name }}" data-email="{{ $lead->email }}" data-reference="{{ $lead->tasteProfile->booking_reference }}">
    <div class="booking-confirmation-bar">Your Taste Profile Has Been Saved &amp; Transmitted Directly to Luca.</div>
    <section class="booking-page" id="booking-selection" @if($isConfirmed) hidden @endif>
        <header class="booking-intro">
            <p class="eyebrow">FINAL STEP: COMPLIMENTARY SOMMELIER STRATEGY SESSION ($350 VALUE)</p>
            <h1>Claim Your Private Italian Wine &amp; Cellar Blueprint Session with Luca Gradi</h1>
            <p class="booking-subheadline">Select your preferred date and time below. Luca will review your taste profile, flavor preferences, avoidances, and investment goals in detail before your session to build your bespoke Italian wine roadmap.</p>
            
            <div style="margin: 1.5rem auto 2rem; padding: 0.9rem 1.4rem; background: #fffaf0; border: 1px solid #d9b46f; border-radius: 8px; font-size: 0.92rem; color: #5a3c0b; text-align: center; max-width: 820px;">
                <strong>⚠️ Strict Availability:</strong> Luca personally conducts a maximum of 8 private consultations per week to dedicate full research to each client. Spots are reserved on a first-confirmed basis.
            </div>

            <ul class="booking-benefits">
                <li><strong>1. Palate Diagnostic:</strong> 1-on-1 sommelier analysis of what grape varieties and terroirs suit your exact palate.</li>
                <li><strong>2. Hidden-Gem Blueprint:</strong> Direct access to generational Italian family producers that outperform big commercial labels.</li>
                <li><strong>3. Custom Curation &amp; No Pressure:</strong> Receive a tailored recommendation plan for your table. 100% complimentary.</li>
            </ul>
        </header>
        <section class="calendar-panel" aria-labelledby="calendar-title">
            <div class="calendar-heading"><div><p class="eyebrow">SECURE YOUR APPOINTMENT</p><h2 id="calendar-title">Select your date and time</h2></div>@if($bookingDuration)<span class="duration-badge">{{ $bookingDuration }}</span>@endif</div>
            @if($calConfig)
                <div id="cal-booking" class="cal-booking" aria-label="Cal.com appointment calendar"></div>
                <div id="calendar-loading" class="calendar-message" role="status">Loading the secure calendar…</div>
                <div id="calendar-fallback" class="calendar-message" hidden>The calendar could not be loaded here. <a href="{{ $calConfig['origin'].'/'.$calConfig['calLink'] }}" target="_blank" rel="noopener">Open the secure calendar in a new tab</a>.</div>
                <noscript><p class="calendar-message">JavaScript is required for the embedded calendar. <a href="{{ $calConfig['origin'].'/'.$calConfig['calLink'] }}" target="_blank" rel="noopener">Open the secure calendar</a>.</p></noscript>
            @else
                <div class="calendar-message calendar-message--setup" role="status">Online scheduling is being configured. Please use the support contact below and we’ll help you choose a time.</div>
            @endif
            <p class="booking-support">Can’t find a suitable time? @if($supportEmail)<a href="mailto:{{ $supportEmail }}">Email {{ $supportEmail }}</a> and we’ll help.@else Please contact MondoDiVino support and we’ll help.@endif</p>
        </section>
    </section>
    <section id="booking-pending" class="booking-state" hidden aria-live="polite">
        <p class="eyebrow">CONFIRMING YOUR SESSION</p><h1>You’re Almost Confirmed, {{ $lead->first_name }}</h1>
        <p>We’ve received your booking. We’re confirming the details now.</p><div class="status-pulse" aria-hidden="true"></div>
        <p id="pending-timeout" class="state-note" hidden>Check your inbox for confirmation. You can safely close this page.</p>
    </section>
    <section id="booking-confirmed" class="booking-state booking-state--confirmed" @if(!$isConfirmed) hidden @endif aria-live="polite" tabindex="-1">
        <p class="eyebrow">PRIVATE SESSION CONFIRMED</p><h1>You’re Booked, {{ $lead->first_name }}</h1>
        <p>Your session with Luca is confirmed for <strong id="confirmed-when">@if($bookingLocalStart){{ $bookingLocalStart->format('F j, Y \a\t g:i A') }} {{ $booking->timezone }}@endif</strong>.</p>
        <dl class="booking-details"><div><dt>Date &amp; time</dt><dd id="confirmed-date">@if($bookingLocalStart){{ $bookingLocalStart->format('F j, Y \a\t g:i A') }}@endif</dd></div><div><dt>Time zone</dt><dd id="confirmed-timezone">{{ $isConfirmed ? $booking->timezone : '' }}</dd></div></dl>
        <p>We’ve sent the calendar invitation and call details to <strong>{{ $lead->email }}</strong>. Keep an eye out for a short note from Luca before the session.</p>
        <div id="booking-links" class="booking-links">
            @if($isConfirmed && $booking->meeting_url)<a class="button" href="{{ $booking->meeting_url }}" target="_blank" rel="noopener">JOIN THE SESSION →</a>@endif
            @if($isConfirmed && ($booking->reschedule_url || $booking->cancel_url))<p>Need to change the time? @if($booking->reschedule_url)<a href="{{ $booking->reschedule_url }}" target="_blank" rel="noopener">Reschedule</a>@endif @if($booking->reschedule_url && $booking->cancel_url) or @endif @if($booking->cancel_url)<a href="{{ $booking->cancel_url }}" target="_blank" rel="noopener">cancel</a>@endif.</p>@endif
        </div>
        <div class="prepare-card"><h2>What to prepare</h2><p>You do not need to study anything. Bring the name or photo of one bottle you loved and one that disappointed you. Both give Luca useful clues.</p><p>You can also bring details about an upcoming dinner, gift or celebration, or any questions raised by <em>Italy Beyond the Famous Labels</em>.</p></div>
    </section>
</div>
@endsection

@if($calConfig)
    @push('scripts')<script src="{{ asset('js/booking.js') }}" defer></script>@endpush
@endif
