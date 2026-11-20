@extends('layouts.hvco', ['title' => 'Taste Profile Link Unavailable | MondoDiVino'])

@section('content')
<section class="booking-page">
    <p class="eyebrow">THIS LINK IS NO LONGER AVAILABLE</p>
    <h1>Let's get you a fresh link.</h1>
    <p>For your privacy, taste profile links are secure and expire. Enter the email you used for the guide and we'll send you a new personal link.</p>
    <a class="button" href="{{ route('session-link.show') }}">SEND ME A NEW LINK →</a>
</section>
@endsection
