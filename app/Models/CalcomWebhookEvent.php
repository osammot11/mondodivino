<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalcomWebhookEvent extends Model
{
    protected $fillable = [
        'provider', 'event_type', 'booking_uid', 'event_timestamp', 'payload_hash',
        'normalized_payload', 'processed_at', 'result',
    ];

    protected function casts(): array
    {
        return [
            'event_timestamp' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
            'normalized_payload' => 'encrypted:array',
        ];
    }
}
