<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('invitation_sent_at')->nullable()->after('email_verified_at');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_sent_at');
            $table->timestamp('invitation_revoked_at')->nullable()->after('invitation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn([
            'invitation_sent_at', 'invitation_expires_at', 'invitation_revoked_at',
        ]));
    }
};
