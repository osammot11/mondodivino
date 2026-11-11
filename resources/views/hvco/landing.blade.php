@extends('layouts.hvco', [
    'title' => 'Free Guide to Hidden Italian Wines | MondoDiVino',
    'description' => 'Discover hidden Italian wineries, overlooked regions and native grapes with a free guide selected by Italian sommelier Luca Gradi.',
])

@push('scripts')
<script src="{{ asset('js/optin-multistep.js') }}" defer></script>
@endpush

@section('content')
<section class="hero">
    <div class="hero__copy">
        <p class="eyebrow">FOR CURIOUS ITALIAN WINE LOVERS</p>
        <h1>Discover the Italian Wines America Usually Misses</h1>
        <p class="subheadline">Get <strong>Italy Beyond the Famous Labels</strong>, the free guide to hidden wineries, overlooked regions and native grapes that can deliver remarkable character and value, without relying on famous labels or sommelier jargon.</p>

        <form class="optin optin--multistep" action="{{ route('hvco.store') }}" method="post" data-optin-multistep novalidate>
            @csrf
            <input type="hidden" name="landing_variant" value="{{ $variant }}">
            @if($referrer)<input type="hidden" name="referrer" value="{{ $referrer }}">@endif
            @foreach($tracking as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div class="honeypot" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="optin-progress" aria-hidden="true">
                <div class="optin-progress-bar"><span data-optin-progress-bar style="width: 33.333%;"></span></div>
                <div class="optin-progress-text"><span data-optin-step-label>Step 1 of 3</span></div>
            </div>

            <!-- Step 1: First Name -->
            <div class="optin-step" data-optin-step="0">
                <label for="first_name">1. What is your first name?</label>
                <div class="optin-input-row">
                    <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" placeholder="Your first name" autocomplete="given-name" required autofocus aria-describedby="first_name_error">
                    <button type="button" class="optin-btn optin-btn--next" data-optin-next>NEXT <span aria-hidden="true">→</span></button>
                </div>
                @error('first_name')<p class="error" id="first_name_error">{{ $message }}</p>@enderror
                <p class="optin-micro-hint">Press <strong>Enter ↵</strong> to continue</p>
            </div>

            <!-- Step 2: Email -->
            <div class="optin-step" data-optin-step="1" hidden>
                <div class="optin-step-nav">
                    <button type="button" class="optin-btn-back" data-optin-back>← Back</button>
                </div>
                <label for="email">2. Where should we send your free guide?</label>
                <div class="optin-input-row">
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Where should we send the guide?" autocomplete="email" required aria-describedby="email_error">
                    <button type="button" class="optin-btn optin-btn--next" data-optin-next>NEXT <span aria-hidden="true">→</span></button>
                </div>
                @error('email')<p class="error" id="email_error">{{ $message }}</p>@enderror
                <p class="optin-micro-hint">Press <strong>Enter ↵</strong> to continue</p>
            </div>

            <!-- Step 3: Phone & Consent -->
            <div class="optin-step" data-optin-step="2" hidden>
                <div class="optin-step-nav">
                    <button type="button" class="optin-btn-back" data-optin-back>← Back</button>
                </div>
                <label for="phone">3. Mobile phone:</label>
                <div class="optin-input-stack">
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Mobile number" autocomplete="tel" required aria-describedby="phone_error">
                    @error('phone')<p class="error" id="phone_error">{{ $message }}</p>@enderror

                    @if($collectZip)
                        <label for="zip" class="optin-sublabel">ZIP code <span>— optional</span></label>
                        <input id="zip" name="zip" type="text" value="{{ old('zip') }}" placeholder="ZIP code" autocomplete="postal-code">
                        @error('zip')<p class="error">{{ $message }}</p>@enderror
                    @endif

                    <label class="consent" for="consent">
                        <input id="consent" name="consent" type="checkbox" value="1" required @checked(old('consent', true))>
                        <span>{{ $consentText }}</span>
                    </label>
                    @error('consent')<p class="error">Please confirm your consent to receive the guide.</p>@enderror

                    <button type="submit" class="optin-btn optin-btn--submit" data-optin-submit>SEND ME THE FREE GUIDE <span aria-hidden="true">→</span></button>
                </div>
            </div>
        </form>

        <div class="guide guide--mobile" aria-hidden="true">
            @include('hvco.partials.guide-mockup')
        </div>
    </div>

    <div class="hero__visual">
        @include('hvco.partials.guide-mockup')
    </div>
</section>

<section class="inside" aria-labelledby="inside-title">
    <p class="eyebrow">ITALY BEYOND THE FAMOUS LABELS</p>
    <h2 id="inside-title">An Italian wine expert's guide to hidden wineries, native grapes and exceptional value</h2>
    <p>Inside, you'll discover:</p>
    <ul>
        <li>Why a famous name can add more to the price than it adds to the glass</li>
        <li>The producer-first rule for judging an unfamiliar Italian bottle</li>
        <li>Overlooked regions and native grapes worth learning by name</li>
        <li>Simple clues that separate real substance from expensive storytelling</li>
        <li>How to explore Italian wine with more confidence, and far less guesswork</li>
    </ul>
</section>

<section class="authority">
    <div class="authority-mark" aria-hidden="true">PF</div>
    <div>
        <p class="eyebrow">THE PRODUCER-FIRST SELECTION METHOD</p>
        <h2>Selected by Luca Gradi, Founder &amp; Sommelier at MondoDiVino</h2>
        <p>Every recommendation starts with the producer, the territory and what's actually in the glass, not the fame of the label.</p>
    </div>
</section>

<section class="closing">
    <h2>You do not need to memorize hundreds of grapes to drink better Italian wine. You just need a better filter.</h2>
    <a class="button" href="#first_name">GET ITALY BEYOND THE FAMOUS LABELS <span aria-hidden="true">→</span></a>
</section>
@endsection
