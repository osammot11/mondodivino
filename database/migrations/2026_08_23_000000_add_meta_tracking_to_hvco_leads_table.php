<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hvco_leads', function (Blueprint $table): void {
            $table->string('meta_fbp')->nullable()->after('guide_email_requested_at');
            $table->string('meta_fbc')->nullable()->after('meta_fbp');
            $table->timestamp('meta_tracking_consented_at')->nullable()->after('meta_fbc');
            $table->timestamp('meta_tracking_revoked_at')->nullable()->after('meta_tracking_consented_at');
        });
    }

    public function down(): void
    {
        Schema::table('hvco_leads', function (Blueprint $table): void {
            $table->dropColumn([
                'meta_fbp',
                'meta_fbc',
                'meta_tracking_consented_at',
                'meta_tracking_revoked_at',
            ]);
        });
    }
};
