<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('Review_Notes')->nullable()->after('Rejection_Reason');
            $table->timestamp('Review_Requested_At')->nullable()->after('Review_Notes');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['Review_Notes', 'Review_Requested_At']);
        });
    }
};
