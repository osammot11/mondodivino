<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BrevoWebhookController;
use App\Http\Controllers\CalcomWebhookController;
use App\Http\Controllers\HvcoLandingController;
use App\Http\Controllers\MetaConsentController;
use App\Http\Controllers\MetaPageViewController;
use App\Http\Controllers\TasteProfileController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/italy-beyond-famous-labels');

Route::get('/italy-beyond-famous-labels', [HvcoLandingController::class, 'show'])->name('hvco.landing');
Route::post('/italy-beyond-famous-labels', [HvcoLandingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('hvco.store');
Route::get('/taste-profile/{token}', [TasteProfileController::class, 'show'])->name('taste-profile.show');
Route::post('/taste-profile/{token}', [TasteProfileController::class, 'store'])
    ->middleware('throttle:8,1')
    ->name('taste-profile.store');
Route::get('/taste-profile/{token}/not-eligible', [TasteProfileController::class, 'ineligible'])
    ->name('taste-profile.ineligible');
Route::get('/taste-profile/{token}/availability', [TasteProfileController::class, 'unavailable'])
    ->name('taste-profile.unavailable');
Route::get('/private-session/{token}', [BookingController::class, 'show'])->name('booking.show');
Route::get('/private-session/{token}/status', [BookingController::class, 'status'])
    ->middleware('throttle:30,1')->name('booking.status');

Route::post('/tracking/meta/page-view', MetaPageViewController::class)
    ->middleware('throttle:60,1')
    ->name('tracking.meta.page-view');
Route::post('/tracking/meta/consent/{token}', MetaConsentController::class)
    ->middleware('throttle:30,1')
    ->name('tracking.meta.consent');

Route::post('/webhooks/calcom', CalcomWebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.calcom');

Route::post('/webhooks/brevo', BrevoWebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.brevo');
