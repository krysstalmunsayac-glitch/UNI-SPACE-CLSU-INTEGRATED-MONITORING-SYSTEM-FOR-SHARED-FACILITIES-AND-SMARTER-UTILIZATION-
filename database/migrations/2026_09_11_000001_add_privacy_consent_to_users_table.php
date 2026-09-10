<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('privacy_consent')->default(false)->after('account_type');
            $table->timestamp('privacy_consented_at')->nullable()->after('privacy_consent');
            $table->string('privacy_notice_version', 30)->nullable()->after('privacy_consented_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['privacy_consent', 'privacy_consented_at', 'privacy_notice_version']);
        });
    }
};
