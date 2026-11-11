@extends('layouts.hvco', ['title' => 'Taste Profile Link Unavailable | MondoDiVino'])

@section('content')
<section class="booking-page">
    <p class="eyebrow">THIS LINK IS NO LONGER AVAILABLE</p>
    <h1>Let's get you a fresh link.</h1>
    <p>For your privacy, taste profile links are secure and expire. Request the guide again to continue without creating a duplicate contact.</p>
    <a class="button" href="{{ route('hvco.landing') }}">REQUEST THE GUIDE AGAIN →</a>
</section>
@endsection
