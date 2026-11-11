@extends('layouts.hvco', [
    'title' => 'Build Your Private Italian Wine Profile | MondoDiVino',
    'description' => 'Tell Luca what you enjoy and book your complimentary Private Italian Wine Selection Session.',
])

@push('scripts')
<script src="{{ asset('js/taste-profile.js') }}" defer></script>
@endpush

@section('content')
<header class="long-hero">
    <div class="long-shell">
        <div class="delivery-note" role="status"><strong>Your guide is on its way.</strong> Check your inbox for <em>Italy Beyond the Famous Labels</em>.</div>
        <p class="eyebrow">COMPLIMENTARY PRIVATE ITALIAN WINE SELECTION SESSION ($120 VALUE)</p>
        <h1>Don't Rely on Strangers' Wine Scores. Map Your Personal Italian Wine Profile.</h1>
        <p class="long-lead">Complete your private taste profile, then book 30 minutes with MondoDiVino Founder &amp; Sommelier Luca Gradi. He will turn what you enjoy into a list of six Italian bottles chosen for your palate, so you know exactly what to open next, even if you don't know a single grape by name.</p>
        <p><strong>Complimentary · No obligation to purchase · Your six-bottle list is yours to keep.</strong></p>
        <a class="button" href="#taste-profile">BUILD MY PRIVATE WINE PROFILE <span aria-hidden="true">→</span></a>
        <p class="cta-note">Takes 3–5 minutes. Luca will review your answers in detail before your session.</p>
    </div>
</header>

<article class="long-content">
    <section>
        <h2>A famous label is an expensive shortcut. It knows nothing about your palate.</h2>
        <p>When standing before a wall of Italian bottles, a trophy appellation, a 95-point score, or an ornate label feels like the safest choice. But those commercial shortcuts cannot know what makes your dinner table come alive.</p>
        <p>A wine critic's score cannot tell whether you prefer a silky, mineral red or a bold, structured powerhouse. A famous name cannot know whether you are hosting an intimate dinner, celebrating a milestone, or seeking an authentic taste memory of Tuscany or Piedmont.</p>
        <p>Without an insider's filter, shopping for Italian wine remains expensive guesswork.</p>
    </section>

    <section class="dark-section">
        <h2>Buying beyond the famous labels should feel exhilarating—never like a gamble</h2>
        <p>If you have ever bought Italian wine, you have likely asked yourself:</p>
        <ul class="question-list">
            <li>Am I paying for extraordinary wine craftsmanship—or just funding an importer's marketing budget?</li>
            <li>Will I actually love this bottle, or am I gambling on a magazine editor's taste?</li>
            <li>Which authentic family producers and native grape varieties are truly worth exploring next?</li>
            <li>How can I serve bottles that stun my dinner guests without spending $150+ per bottle?</li>
        </ul>
        <p>The solution is not more shelf choice. It is an independent, expert filter.</p>
        <p>Familiar labels keep you trapped in the same overpriced slice of Italy. Luca's Producer-First Method takes you directly to the source.</p>
    </section>

    <section>
        <p class="eyebrow">THE PRODUCER-FIRST SELECTION METHOD</p>
        <h2>How Luca Decides Which Bottles Earn a Place in Your Glass</h2>
        <p>No bottle enters MondoDiVino's selection without passing these five steps in Italy:</p>
        <ol class="method-list">
            <li><strong>Luca Tastes at the Cellar:</strong> Every wine is tasted by Luca in person, at the producer's own estate, before it is considered.</li>
            <li><strong>He Picks the Bottle Himself:</strong> Luca chooses the bottle to taste from the producer's stock, so there is no specially prepared sample.</li>
            <li><strong>Only the Best Few Survive:</strong> Of the wines tasted on each visit, only a few earn a place in the selection.</li>
            <li><strong>The Price Has to Make Sense:</strong> A good wine is not enough: it must make real sense at the price it is offered, compared with the familiar labels in its category.</li>
            <li><strong>Independent Final Decision:</strong> Luca decides a week later, away from the cellar and any pressure from the producer.</li>
        </ol>
        <p>That is <strong>The Producer-First Selection Method</strong>. Then your palate decides which of those wines belong in your glass.</p>
    </section>

    <section class="luca-section">
        <p class="eyebrow">MEET LUCA GRADI</p>
        <h2>Founder &amp; Sommelier, MondoDiVino</h2>
        <div class="luca-copy"><p>Luca lives and works in Italy, where the wines in his selection are made. He supplies Italian restaurants and works directly with small producers who make a few hundred bottles a year, too few to reach most American shelves.</p><p>His role in your private session is strictly consultative: analyze your taste preferences, decode what you love, and build a list of six bottles chosen for your palate.</p></div>
    </section>

    <section>
        <p class="eyebrow">WHY WE MAP YOUR TASTE PROFILE IN ADVANCE</p>
        <h2>Your answers give Luca the exact clues he needs for your six bottles</h2>
        <p>Luca does not conduct generic sales calls. Your profile enables him to study your preferences in advance:</p>
        <div class="benefit-grid">
            <div><h3>The flavor profiles you crave</h3><p>Whether you lean toward crisp &amp; mineral whites or silky &amp; age-worthy reds, plain English is all we need.</p></div>
            <div><h3>Bottles you loved (and hated)</h3><p>Your memorable bottles—and your disappointments—tell Luca more than any score could.</p></div>
            <div><h3>The characteristics you avoid</h3><p>Heavy tannins, high acidity, oakiness, or sweetness: knowing what to avoid protects your table.</p></div>
            <div><h3>Your occasions &amp; table goals</h3><p>Weeknight dinners, hosting friends, cellar-building, or gifts each require a distinct curation strategy.</p></div>
            <div><h3>Your preferred investment level</h3><p>Helps Luca recommend bottles that fit comfortably within the budget you choose.</p></div>
        </div>
    </section>

    <section class="dinner-scene">
        <p class="eyebrow">A BETTER KIND OF WINE STORY</p>
        <h2>Imagine opening a bottle your guests have never seen—that leaves them speechless</h2>
        <p>You pour a wine with an authentic story, an artisan producer, and an unforgettable flavor profile. The conversation turns to Italy, craftsmanship, and discovery—not how much was spent at a retail chain.</p>
        <p>True luxury is knowing why a bottle is extraordinary before you pull the cork.</p>
    </section>

    <section class="offer-section">
        <h2>Your Complimentary Private Italian Wine Selection Session</h2>
        <p>Complete your taste profile below and book 30 minutes one-to-one with Luca Gradi. In your session, Luca will:</p>
        <ul>
            <li>go through your profile with you: the bottles you loved, the ones that disappointed you and the occasions you're buying for;</li>
            <li>build, live, a list of six Italian bottles from six different producers, chosen for your palate and budget;</li>
            <li>explain for each one who made it, where it comes from and why it suits you;</li>
            <li>tell you how to serve each bottle, what to pair it with and when to open it.</li>
        </ul>
        <p>The list is yours to keep. You can look for those bottles on your own, or ask Luca to send them to you.</p>
        <h3>Why is it complimentary?</h3>
        <p>Luca works with small producers who make a few hundred bottles a year, and their wines also go to restaurants and buyers in Italy. Before he sends any of those bottles to someone in the US, he wants to know they'll truly enjoy them. This conversation is how he decides.</p>
        <p><strong>$120 value · Complimentary · No obligation to purchase.</strong></p>
        <a class="button" href="#taste-profile">BUILD MY PRIVATE WINE PROFILE <span aria-hidden="true">→</span></a>
    </section>
</article>

<section class="profile-section" id="taste-profile">
    <div class="profile-heading">
        <p class="eyebrow">YOUR PRIVATE TASTE PROFILE</p>
        <h2>Tell Luca What Belongs in Your Glass</h2>
        <p>Takes only 3 minutes. There are no wrong answers and no sommelier jargon required.</p>
    </div>

    <form class="taste-form" action="{{ url()->full() }}" method="post" data-progressive-form novalidate>
        @csrf
        <div class="honeypot" aria-hidden="true"><label for="profile_website">Website</label><input id="profile_website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <div class="progress" aria-label="Form progress"><span data-progress-bar></span><strong data-progress-label>Step 1 of 3</strong></div>

        @if($errors->any())
            <div class="form-alert" role="alert">Please review the highlighted answers. Your other responses have been preserved.</div>
        @endif

        @php
            $field = fn (string $name, mixed $fallback = null) => old($name, data_get($profile, $name, $fallback));
            $selected = fn (string $name, string $value) => in_array($value, (array) old($name, data_get($profile, $name, [])), true);
        @endphp

        <fieldset class="form-step" data-step="1">
            <legend><span>01 / STEP 1 OF 3</span> About you &amp; Contact</legend>
            <div class="field-grid">
                <div class="field"><label for="profile_first_name">First name</label><input id="profile_first_name" name="first_name" value="{{ $field('first_name', $lead->first_name) }}" autocomplete="given-name" aria-required="true">@error('first_name')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="{{ $field('last_name') }}" autocomplete="family-name" aria-required="true">@error('last_name')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="field field--wide"><label for="profile_email">Email</label><input id="profile_email" value="{{ $lead->email }}" type="email" readonly aria-describedby="email_note"><small id="email_note">Linked securely to the address used for your guide.</small></div>
                <div class="field"><label for="mobile_phone">Mobile phone <span>— optional (for consultation reminder)</span></label><input id="mobile_phone" name="mobile_phone" value="{{ $field('mobile_phone', $lead->phone ?? null) }}" type="tel" autocomplete="tel">@error('mobile_phone')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="city">City</label><input id="city" name="city" value="{{ $field('city') }}" autocomplete="address-level2" aria-required="true">@error('city')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="state">State</label><select id="state" name="state" autocomplete="address-level1" aria-required="true"><option value="">Select your state</option>@foreach($states as $code => $name)<option value="{{ $code }}" @selected($field('state') === $code)>{{ $name }}</option>@endforeach</select>@error('state')<p class="error">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="profile_zip">ZIP code</label><input id="profile_zip" name="zip" value="{{ old('zip', $lead->zip) }}" autocomplete="postal-code" aria-required="true">@error('zip')<p class="error">{{ $message }}</p>@enderror</div>
            </div>
            <fieldset class="choice-group"><legend>Are you 21 or older?</legend><div class="choice-row"><label class="choice"><input type="radio" name="age_confirmed" value="1" @checked((string) old('age_confirmed', $profile->age_confirmed) === '1')><span>Yes</span></label><label class="choice"><input type="radio" name="age_confirmed" value="0" @checked((string) old('age_confirmed', $profile->age_confirmed) === '0')><span>No</span></label></div>@error('age_confirmed')<p class="error">{{ $message }}</p>@enderror</fieldset>
            <div class="step-actions"><button type="button" class="button" data-next>CONTINUE TO TASTE PREFERENCES <span aria-hidden="true">→</span></button></div>
        </fieldset>

        <fieldset class="form-step" data-step="2">
            <legend><span>02 / STEP 2 OF 3</span> What you enjoy in the glass</legend>
            @include('hvco.partials.checkbox-group', ['name' => 'wine_styles', 'question' => 'Which styles do you enjoy most?', 'items' => ['red'=>'Red','white'=>'White','sparkling'=>'Sparkling','rose'=>'Rosé','dessert_fortified'=>'Dessert / fortified','exploring'=>"I'm still exploring"]])
            @include('hvco.partials.checkbox-group', ['name' => 'taste_descriptions', 'question' => 'Which descriptions sound most appealing? Choose up to 4.', 'items' => ['light_fresh'=>'Light and fresh','silky_elegant'=>'Silky and elegant','rich_full'=>'Rich and full-bodied','bold_structured'=>'Bold and structured','crisp_mineral'=>'Crisp and mineral','aromatic_floral'=>'Aromatic and floral','fruity_generous'=>'Fruity and generous','savory_earthy'=>'Savory and earthy','complex_age_worthy'=>'Complex and age-worthy','dry_sparkling'=>'Dry sparkling'], 'max' => 4])
            <div class="field"><label for="favorite_wines">Name up to three wines, grapes or regions you already love.</label><textarea id="favorite_wines" name="favorite_wines" rows="3">{{ $field('favorite_wines') }}</textarea>@error('favorite_wines')<p class="error">{{ $message }}</p>@enderror</div>
            @include('hvco.partials.checkbox-group', ['name' => 'avoidances', 'question' => 'Anything you usually avoid?', 'items' => ['high_tannin'=>'High tannin','high_acidity'=>'High acidity','oaky'=>'Oaky flavors','sweetness'=>'Sweetness','very_light'=>'Very light wines','very_powerful'=>'Very powerful wines','funky'=>'Funky / unconventional styles','nothing'=>'Nothing in particular','other'=>'Other']])
            <div class="field"><label for="avoidance_other">Other preference to avoid <span>— optional</span></label><input id="avoidance_other" name="avoidance_other" value="{{ $field('avoidance_other') }}">@error('avoidance_other')<p class="error">{{ $message }}</p>@enderror</div>
            @include('hvco.partials.radio-group', ['name' => 'adventure_level', 'question' => 'How adventurous would you like the selection to be?', 'items' => ['familiar_discoveries'=>'Familiar with a few discoveries','balanced'=>'Balanced','hidden_italy'=>'Surprise me with hidden Italy']])
            <div class="step-actions"><button type="button" class="text-button" data-back>← BACK</button><button type="button" class="button" data-next>CONTINUE TO SET &amp; OCCASIONS <span aria-hidden="true">→</span></button></div>
        </fieldset>

        <fieldset class="form-step" data-step="3">
            <legend><span>03 / STEP 3 OF 3</span> Occasions, format &amp; budget</legend>
            @include('hvco.partials.checkbox-group', ['name' => 'occasions', 'question' => 'What are the wines mainly for?', 'items' => ['everyday_dinners'=>'Everyday dinners','hosting'=>'Hosting friends or family','special_occasions'=>'Special occasions','gifts'=>'Gifts','cellar'=>'Building a cellar','learning'=>'Learning and discovery']])
            @include('hvco.partials.radio-group', ['name' => 'preferred_mix', 'question' => 'What mix would you prefer?', 'items' => ['mostly_reds'=>'Mostly reds','mostly_whites'=>'Mostly whites','balanced_mix'=>'Balanced mix','include_sparkling'=>'Include sparkling','luca_recommendation'=>"I'm open to Luca's recommendation"]])
            @include('hvco.partials.radio-group', ['name' => 'usual_bottle_spend', 'question' => 'What do you typically spend on a bottle you are excited to open?', 'items' => ['under_30'=>'Under $30','30_50'=>'$30–$50','50_80'=>'$50–$80','80_125'=>'$80–$125','125_plus'=>'$125+']])
            @include('hvco.partials.radio-group', ['name' => 'set_budget', 'question' => 'What investment range feels comfortable for a personalized set?', 'items' => ['under_300'=>'Under $300','300_499'=>'$300–$499','500_749'=>'$500–$749','750_999'=>'$750–$999','1000_plus'=>'$1,000+','guidance'=>"I'd like Luca's guidance"]])
            @include('hvco.partials.radio-group', ['name' => 'desired_timing', 'question' => 'When would you ideally like the wines?', 'items' => ['asap'=>'As soon as practical','within_30_days'=>'Within 30 days','within_2_3_months'=>'Within 2–3 months','specific_date'=>'For a specific date','exploring'=>'Just exploring for now']])
            <div class="field"><label for="italian_memory">Is there a dinner, memory, person or trip to Italy you would love the selection to evoke? <span>— optional</span></label><textarea id="italian_memory" name="italian_memory" rows="4">{{ $field('italian_memory') }}</textarea>@error('italian_memory')<p class="error">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="additional_notes">Anything else Luca should know before your conversation? <span>— optional</span></label><textarea id="additional_notes" name="additional_notes" rows="4">{{ $field('additional_notes') }}</textarea>@error('additional_notes')<p class="error">{{ $message }}</p>@enderror</div>
            <label class="consent profile-consent"><input name="profile_consent" type="checkbox" value="1" @checked(old('profile_consent'))><span>{{ $profileConsentText }}</span></label>@error('profile_consent')<p class="error">{{ $message }}</p>@enderror
            <label class="consent profile-consent"><input name="sms_consent" type="checkbox" value="1" @checked(old('sms_consent', $profile->sms_consent))><span>{{ $smsConsentText }} <strong>Optional.</strong></span></label>@error('sms_consent')<p class="error">{{ $message }}</p>@enderror
            <div class="step-actions"><button type="button" class="text-button" data-back>← BACK</button><button type="submit" class="button submit-profile" data-submit><span data-submit-text>SAVE MY PROFILE &amp; CHOOSE A TIME →</span></button></div>
            <p class="form-microcopy">Next, you'll choose a convenient time for your private session with Luca. Complimentary, with no obligation to purchase.</p>
        </fieldset>
    </form>
</section>

<section class="final-cta">
    <h2>Ready to look beyond the labels you already know?</h2>
    <p>Tell Luca what you enjoy and book your complimentary Private Italian Wine Selection Session.</p>
    <a class="button" href="#taste-profile">BUILD MY PRIVATE WINE PROFILE <span aria-hidden="true">→</span></a>
    <p class="postscript"><strong>P.S.</strong> Trophy labels and magazine scores are designed to sell mass inventory. Your own palate is the only compass that truly matters. Tell Luca what you enjoy, and let's craft a selection you'll love opening.</p>
</section>
@endsection
