<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hvco_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('zip', 20)->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->text('referrer')->nullable();
            $table->string('landing_variant', 50);
            $table->text('consent_text');
            $table->timestamp('consent_at');
            $table->string('locale', 15);
            $table->string('funnel_status', 50)->index();
            $table->string('email_status', 30)->nullable();
            $table->timestamp('suppressed_at')->nullable();
            $table->timestamp('brevo_synced_at')->nullable();
            $table->timestamp('guide_email_requested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('funnel_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hvco_lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 60)->index();
            $table->string('landing_variant', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('brevo_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hvco_lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_key')->unique();
            $table->string('event', 40);
            $table->string('message_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brevo_webhook_events');
        Schema::dropIfExists('funnel_events');
        Schema::dropIfExists('hvco_leads');
    }
};
