<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->string('Reservation_Frequency', 50)->nullable()->after('Rating');
            $table->string('Purpose_Importance', 50)->nullable()->after('Reservation_Frequency');
            $table->string('Requirements_Met', 50)->nullable()->after('Purpose_Importance');
            $table->string('Reserve_Again', 50)->nullable()->after('Requirements_Met');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table): void {
            $table->dropColumn(['Reservation_Frequency', 'Purpose_Importance', 'Requirements_Met', 'Reserve_Again']);
        });
    }
};
