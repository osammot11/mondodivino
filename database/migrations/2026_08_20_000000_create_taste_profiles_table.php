<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hvco_leads', function (Blueprint $table): void {
            $table->string('taste_profile_token', 64)->nullable()->unique()->after('email');
        });

        Schema::table('funnel_events', function (Blueprint $table): void {
            $table->string('event_key')->nullable()->unique()->after('name');
        });

        Schema::create('taste_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hvco_lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->string('last_name', 100)->nullable();
            $table->string('mobile_phone', 30)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 2)->nullable()->index();
            $table->boolean('age_confirmed')->nullable()->index();
            $table->json('wine_styles')->nullable();
            $table->json('taste_descriptions')->nullable();
            $table->text('favorite_wines')->nullable();
            $table->json('avoidances')->nullable();
            $table->string('avoidance_other', 255)->nullable();
            $table->string('adventure_level', 60)->nullable()->index();
            $table->json('occasions')->nullable();
            $table->string('preferred_mix', 60)->nullable();
            $table->string('usual_bottle_spend', 30)->nullable()->index();
            $table->string('set_budget', 30)->nullable()->index();
            $table->string('desired_timing', 40)->nullable()->index();
            $table->text('italian_memory')->nullable();
            $table->text('additional_notes')->nullable();
            $table->text('profile_consent_text')->nullable();
            $table->timestamp('profile_consented_at')->nullable();
            $table->boolean('sms_consent')->default(false);
            $table->timestamp('sms_consented_at')->nullable();
            $table->boolean('is_serviceable')->nullable()->index();
            $table->boolean('is_budget_qualified')->nullable()->index();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taste_profiles');

        Schema::table('funnel_events', function (Blueprint $table): void {
            $table->dropUnique(['event_key']);
            $table->dropColumn('event_key');
        });

        Schema::table('hvco_leads', function (Blueprint $table): void {
            $table->dropUnique(['taste_profile_token']);
            $table->dropColumn('taste_profile_token');
        });
    }
};
