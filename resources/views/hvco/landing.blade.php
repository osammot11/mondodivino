@extends('layouts.hvco', [
    'title' => 'Free Guide: 12 Niche Italian Wineries That Outperform $200+ Trophy Labels | MondoDiVino',
    'description' => 'Download the private insider list of boutique Italian wineries and specific bottles that outperform famous $200+ labels at 1/3 of the US retail price.',
])

@push('scripts')
<script src="{{ asset('js/optin-multistep.js') }}" defer></script>
@endpush

@section('content')
<section class="hero">
    <div class="hero__copy">
        <p class="eyebrow">FOR AMERICAN COLLECTORS &amp; ENTHUSIASTS TIRED OF OVERPAYING FOR ITALIAN WINE</p>
        <h1>The Italian "Bottle Swap" Black Book: 12 Niche Wineries That Outperform $200+ Trophy Labels (At 1/3 of the US Price)</h1>
        <p class="subheadline">Download Sommelier Luca Gradi's private insider list of boutique, family-run Italian producers and specific bottles that blind-taste circles around famous Barolos, Brunellos, and Super Tuscans, <strong>without the 300% US importer markup.</strong></p>

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
                <label for="email">2. Where should we send the Black Book?</label>
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
                <label for="phone">3. Mobile phone for priority access:</label>
                <div class="optin-input-stack">
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="Where should we text priority updates?" autocomplete="tel" required aria-describedby="phone_error">
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

                    <button type="submit" class="optin-btn optin-btn--submit" data-optin-submit>GET THE FREE BOTTLE-SWAP GUIDE NOW <span aria-hidden="true">→</span></button>
                </div>
            </div>
        </form>

        <div class="guide guide--mobile" aria-hidden="true">
            @include('hvco.partials.guide-mockup')
        </div>
    </div>

    <div class="hero__visual">
        <img style="width: 100%;" src="{{ asset('images/black-book-mondodivino.webp') }}">
    </div>
</section>

<section class="inside" aria-labelledby="inside-title">
    <p class="eyebrow">THE INSIDER'S REPLACEMENT LIST</p>
    <h2 id="inside-title">Exact Boutique Bottles That Outperform America's Most Overpriced Labels</h2>
    <p>Here is just a fraction of the specific producer swaps waiting inside your free guide:</p>
    <ul>
        <li><strong>Page 3: The $42 "Supertuscan Killer"</strong> — The boutique estate bordering Bolgheri that consistently humiliates $220 commercial Super Tuscans in blind tastings.</li>
        <li><strong>Page 6: The "Brunello Alternative" from Montalcino's Hidden Valley</strong> — Crafted by a third-generation vigneron using the identical Sangiovese Grosso clone, at literally one-third the US shelf price.</li>
        <li><strong>Page 9: The $35 Piedmont Nebbiolo That Crushes $150 Barolos</strong> — Why high-altitude terroir and traditional aging make this bottle drink like a mature Grand Cru right now.</li>
        <li><strong>Page 14: The Alpine Mineral White Replacing $80 French &amp; Italian Icons</strong> — The crisp, complex white Northern Italian sommeliers secretly pour off-menu for fellow insiders.</li>
        <li><strong>Page 18: The US 3-Tier Markup Truth</strong> — How distributor layers turn a $15 cellar-door bottle into an $85 US retail gamble (and how to access the real gems directly).</li>
    </ul>
</section>

<section class="authority">
    <div class="authority-mark" aria-hidden="true">PF</div>
    <div>
        <p class="eyebrow">THE PRODUCER-FIRST SELECTION METHOD</p>
        <h2>Curated Personally by Luca Gradi, Founder &amp; Sommelier at MondoDiVino</h2>
        <p>Luca spent the last 15 years traveling the back roads of Italy, tasting directly from the barrels of generational family estates. He compiled this private black book so discriminating wine lovers in the US can drink authentic, world-class Italian bottles without paying the 300% famous-label tax.</p>
    </div>
</section>

<section class="closing">
    <h2>Stop paying for the label. Start drinking the juice.</h2>
    <a class="button" href="#first_name">CLAIM YOUR FREE BOTTLE-SWAP GUIDE NOW <span aria-hidden="true">→</span></a>
</section>
@endsection
