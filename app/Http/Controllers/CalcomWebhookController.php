<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCalcomWebhook;
use App\Models\CalcomWebhookEvent;
use App\Services\CalcomEventMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use JsonException;

class CalcomWebhookController extends Controller
{
    public function __invoke(Request $request, CalcomEventMapper $mapper): JsonResponse
    {
        $secret = (string) config('services.calcom.webhook_secret');
        $signature = (string) $request->header('x-cal-signature-256');
        $raw = $request->getContent();

        abort_if($secret === '' || $signature === '' || ! hash_equals(hash_hmac('sha256', $raw, $secret), $signature), 401);

        try {
            $body = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['accepted' => false], 400);
        }

        if (! is_array($body)) {
            return response()->json(['accepted' => false], 400);
        }

        try {
            $normalized = $mapper->map($body);
        } catch (\InvalidArgumentException) {
            return response()->json(['accepted' => false], 422);
        }

        $hash = hash('sha256', $raw);
        if ($normalized === null) {
            CalcomWebhookEvent::firstOrCreate(['payload_hash' => $hash], [
                'provider' => 'calcom', 'event_type' => mb_substr(strtoupper((string) ($body['triggerEvent'] ?? 'UNKNOWN')), 0, 60),
                'processed_at' => now(), 'result' => 'ignored',
            ]);

            return response()->json(['accepted' => true], 202);
        }

        $event = CalcomWebhookEvent::firstOrCreate(['payload_hash' => $hash], [
            'provider' => 'calcom', 'event_type' => $normalized['event_type'],
            'booking_uid' => $normalized['booking_uid'],
            'event_timestamp' => $normalized['event_timestamp'] ? Carbon::parse($normalized['event_timestamp']) : null,
            'normalized_payload' => $normalized,
        ]);

        if ($event->wasRecentlyCreated) {
            ProcessCalcomWebhook::dispatch($event->id)->afterCommit();
        }

        return response()->json(['accepted' => true], 202);
    }
}
