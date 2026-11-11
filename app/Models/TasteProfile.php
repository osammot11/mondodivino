<?php

namespace App\Models;

use App\Enums\TasteProfileStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TasteProfile extends Model
{
    protected $fillable = [
        'hvco_lead_id', 'status', 'booking_reference', 'last_name', 'mobile_phone', 'city', 'state',
        'age_confirmed', 'wine_styles', 'taste_descriptions', 'favorite_wines',
        'avoidances', 'avoidance_other', 'adventure_level', 'occasions',
        'preferred_mix', 'usual_bottle_spend', 'set_budget', 'desired_timing',
        'italian_memory', 'additional_notes', 'profile_consent_text',
        'profile_consented_at', 'sms_consent', 'sms_consented_at',
        'is_serviceable', 'is_budget_qualified', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TasteProfileStatus::class,
            'age_confirmed' => 'boolean',
            'wine_styles' => 'array',
            'taste_descriptions' => 'array',
            'avoidances' => 'array',
            'occasions' => 'array',
            'sms_consent' => 'boolean',
            'is_serviceable' => 'boolean',
            'is_budget_qualified' => 'boolean',
            'profile_consented_at' => 'immutable_datetime',
            'sms_consented_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(HvcoLead::class, 'hvco_lead_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
