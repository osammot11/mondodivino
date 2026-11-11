<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'hvco_lead_id', 'taste_profile_id', 'provider', 'provider_booking_uid',
        'previous_provider_booking_uid', 'booking_reference', 'event_type_id',
        'status', 'starts_at', 'ends_at', 'timezone', 'attendee_name',
        'attendee_email', 'meeting_url', 'reschedule_url', 'cancel_url',
        'booked_at', 'rescheduled_at', 'cancelled_at', 'completed_at',
        'no_show_at', 'provider_created_at', 'provider_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime',
            'booked_at' => 'immutable_datetime', 'rescheduled_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime',
            'no_show_at' => 'immutable_datetime', 'provider_created_at' => 'immutable_datetime',
            'provider_updated_at' => 'immutable_datetime',
            'attendee_name' => 'encrypted', 'attendee_email' => 'encrypted',
            'meeting_url' => 'encrypted', 'reschedule_url' => 'encrypted', 'cancel_url' => 'encrypted',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HvcoLead::class, 'hvco_lead_id');
    }

    public function tasteProfile(): BelongsTo
    {
        return $this->belongsTo(TasteProfile::class);
    }
}
