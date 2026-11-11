<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelEvent extends Model
{
    protected $fillable = [
        'hvco_lead_id', 'name', 'event_key', 'landing_variant', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
