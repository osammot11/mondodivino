<?php

namespace App\Models;

use App\Enums\FunnelStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HvcoLead extends Model
{
    protected $fillable = [
        'email', 'taste_profile_token', 'first_name', 'phone', 'zip', 'utm_source',
        'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'referrer',
        'landing_variant', 'consent_text', 'consent_at', 'locale',
        'funnel_status', 'email_status', 'suppressed_at', 'brevo_synced_at',
        'guide_email_requested_at',
        'meta_fbp', 'meta_fbc', 'meta_tracking_consented_at', 'meta_tracking_revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'funnel_status' => FunnelStatus::class,
            'consent_at' => 'immutable_datetime',
            'suppressed_at' => 'immutable_datetime',
            'brevo_synced_at' => 'immutable_datetime',
            'guide_email_requested_at' => 'immutable_datetime',
            'meta_tracking_consented_at' => 'immutable_datetime',
            'meta_tracking_revoked_at' => 'immutable_datetime',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    public function tasteProfile(): HasOne
    {
        return $this->hasOne(TasteProfile::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
