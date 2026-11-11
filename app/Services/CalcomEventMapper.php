<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class CalcomEventMapper
{
    public const SUPPORTED = [
        'BOOKING_CREATED', 'BOOKING_CONFIRMED', 'BOOKING_RESCHEDULED',
        'BOOKING_CANCELLED', 'BOOKING_REJECTED', 'BOOKING_COMPLETED',
        'BOOKING_NO_SHOW', 'BOOKING_NO_SHOW_UPDATED', 'MEETING_ENDED',
        'AFTER_GUESTS_CAL_VIDEO_NO_SHOW',
    ];

    public function map(array $body): ?array
    {
        $type = strtoupper((string) ($body['triggerEvent'] ?? ''));
        if (! in_array($type, self::SUPPORTED, true)) {
            return null;
        }

        $payload = is_array($body['payload'] ?? null) ? $body['payload'] : $body;
        if ($type === 'BOOKING_NO_SHOW_UPDATED' && ! collect($payload['attendees'] ?? [])->contains(fn ($a) => ($a['noShow'] ?? false) === true)) {
            return null;
        }

        $attendee = is_array($payload['attendees'][0] ?? null) ? $payload['attendees'][0] : [];
        $uid = (string) ($payload['uid'] ?? $payload['bookingUid'] ?? '');
        if ($uid === '') {
            throw new InvalidArgumentException('Cal.com webhook is missing a booking UID.');
        }

        $metadata = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];
        $eventAt = in_array($type, ['MEETING_ENDED', 'BOOKING_COMPLETED'], true)
            ? ($payload['updatedAt'] ?? $body['createdAt'] ?? null)
            : ($body['createdAt'] ?? $payload['updatedAt'] ?? $payload['createdAt'] ?? null);

        return [
            'event_type' => $type,
            'booking_uid' => $uid,
            'previous_booking_uid' => $this->string($payload['rescheduleUid'] ?? null),
            'booking_reference' => $this->string($metadata['funnel_reference'] ?? null),
            'event_timestamp' => $this->date($eventAt)?->toIso8601String(),
            'event_type_id' => $this->string($payload['eventTypeId'] ?? null),
            'starts_at' => $this->date($payload['startTime'] ?? null)?->toIso8601String(),
            'ends_at' => $this->date($payload['endTime'] ?? null)?->toIso8601String(),
            'timezone' => $this->string($attendee['timeZone'] ?? $attendee['timezone'] ?? null),
            'attendee_name' => $this->string($attendee['name'] ?? Arr::get($payload, 'responses.name.value')),
            'attendee_email' => $this->email($attendee['email'] ?? Arr::get($payload, 'responses.email.value')),
            'meeting_url' => $this->url($payload['videoCallUrl'] ?? $metadata['videoCallUrl'] ?? null),
            'reschedule_url' => $this->url($payload['rescheduleUrl'] ?? null),
            'cancel_url' => $this->url($payload['cancelUrl'] ?? null),
        ];
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? mb_substr($value, 0, 500) : (is_int($value) ? (string) $value : null);
    }

    private function email(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) ? mb_strtolower(trim($value)) : null;
    }

    private function url(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)
            ? mb_substr($value, 0, 2000) : null;
    }
}
