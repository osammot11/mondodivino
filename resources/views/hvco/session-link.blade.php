@extends('layouts.hvco', [
    'title' => 'Book Your Private Italian Wine Selection Session | MondoDiVino',
    'description' => 'Enter the email you used for Italy Beyond the Famous Labels and we will send your personal link to book your session with Luca.',
])

@section('content')
<section class="booking-page session-link">
    @if(session('session_link_sent'))
        <p class="eyebrow">CHECK YOUR INBOX</p>
        <h1>Your personal link is on its way.</h1>
        <p>If that email is the one you used to get <em>Italy Beyond the Famous Labels</em>, you'll receive your personal link to book your Private Italian Wine Selection Session within a few minutes. Check your spam or promotions folder if you don't see it.</p>
        <p>Haven't got the guide yet? <a href="{{ route('hvco.landing') }}">Get it here first</a>.</p>
    @else
        <p class="eyebrow">YOUR PRIVATE ITALIAN WINE SELECTION SESSION</p>
        <h1>Book your session with Luca.</h1>
        <p>Thirty minutes, one-to-one, and a list of six Italian bottles chosen for your palate that is yours to keep. For your privacy, every session link is personal: enter the email you used to get the guide and we'll send yours.</p>

        <form class="session-link__form" action="{{ route('session-link.store') }}" method="post" novalidate>
            @csrf
            <div class="honeypot" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <label for="email">Your email</label>
            <div class="optin-input-row">
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="The email you used for the guide" autocomplete="email" required aria-describedby="email_error">
                <button type="submit" class="button">SEND MY LINK <span aria-hidden="true">→</span></button>
            </div>
            @error('email')<p class="error" id="email_error">Please enter a valid email address.</p>@enderror
        </form>

        <p>Haven't got the guide yet? <a href="{{ route('hvco.landing') }}">Get it here first</a>.</p>
    @endif
</section>
@endsection
