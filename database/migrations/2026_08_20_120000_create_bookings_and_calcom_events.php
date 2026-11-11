<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taste_profiles', function (Blueprint $table): void {
            $table->string('booking_reference', 64)->nullable()->unique()->after('status');
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hvco_lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taste_profile_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('calcom');
            $table->string('provider_booking_uid')->unique();
            $table->string('previous_provider_booking_uid')->nullable()->index();
            $table->string('booking_reference', 64)->nullable()->index();
            $table->string('event_type_id')->nullable();
            $table->string('status', 30)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->text('attendee_name')->nullable();
            $table->text('attendee_email')->nullable();
            $table->text('meeting_url')->nullable();
            $table->text('reschedule_url')->nullable();
            $table->text('cancel_url')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->timestamp('provider_created_at')->nullable();
            $table->timestamp('provider_updated_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('calcom_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 30)->default('calcom');
            $table->string('event_type', 60)->index();
            $table->string('booking_uid')->nullable()->index();
            $table->timestamp('event_timestamp')->nullable()->index();
            $table->string('payload_hash', 64)->unique();
            $table->text('normalized_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('result', 40)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calcom_webhook_events');
        Schema::dropIfExists('bookings');
        Schema::table('taste_profiles', function (Blueprint $table): void {
            $table->dropUnique(['booking_reference']);
            $table->dropColumn('booking_reference');
        });
    }
};
