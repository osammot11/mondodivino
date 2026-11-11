<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrevoWebhookEvent extends Model
{
    protected $fillable = [
        'hvco_lead_id', 'event_key', 'event', 'message_id', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }
}
