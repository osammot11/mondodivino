<?php

namespace App\Http\Controllers;

use App\Models\BrevoWebhookEvent;
use App\Models\FunnelEvent;
use App\Models\HvcoLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrevoWebhookController extends Controller
{
    private const SUPPRESSION_EVENTS = ['hard_bounce', 'invalid_email', 'spam', 'complaint', 'unsubscribe'];

    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.brevo.webhook_secret');
        $provided = (string) $request->bearerToken();

        abort_if($secret === '' || $provided === '' || ! hash_equals($secret, $provided), 401);

        $data = $request->validate([
            'event' => ['required', 'string', 'in:delivered,hard_bounce,hardBounce,invalid,invalid_email,invalidEmail,spam,complaint,unsubscribe,unsubscribed'],
            'email' => ['required', 'email:rfc'],
            'message-id' => ['nullable', 'string', 'max:255'],
            'messageId' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable'],
            'ts_event' => ['nullable', 'integer'],
        ]);

        $event = match ($data['event']) {
            'invalid', 'invalidEmail' => 'invalid_email',
            'unsubscribed' => 'unsubscribe',
            default => Str::snake($data['event']),
        };
        $email = mb_strtolower(trim($data['email']));
        $messageId = $data['message-id'] ?? $data['messageId'] ?? null;
        $occurredAt = isset($data['ts_event'])
            ? Carbon::createFromTimestamp($data['ts_event'])
            : (isset($data['date']) ? Carbon::parse($data['date']) : now());
        $key = hash('sha256', implode('|', [$event, $email, $messageId, $occurredAt->timestamp]));

        DB::transaction(function () use ($event, $email, $messageId, $occurredAt, $key): void {
            $lead = HvcoLead::where('email', $email)->first();
            $webhookEvent = BrevoWebhookEvent::firstOrCreate(
                ['event_key' => $key],
                [
                    'hvco_lead_id' => $lead?->id,
                    'event' => $event,
                    'message_id' => $messageId,
                    'occurred_at' => $occurredAt,
                ]
            );

            if (! $webhookEvent->wasRecentlyCreated || ! $lead) {
                return;
            }

            $lead->update([
                'email_status' => $event,
                'suppressed_at' => in_array($event, self::SUPPRESSION_EVENTS, true) ? $occurredAt : $lead->suppressed_at,
            ]);

            FunnelEvent::create([
                'hvco_lead_id' => $lead->id,
                'name' => $event === 'delivered' ? 'guide_email_delivered' : "email_{$event}",
                'occurred_at' => $occurredAt,
            ]);
        });

        return response()->json(['accepted' => true]);
    }
}
