<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->dropColumn([
                'Reservation_Frequency',
                'Facility_Importance',
                'Requirements_Fit',
                'Reserve_Again_Intent',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->string('Reservation_Frequency', 50)->nullable()->after('Other_Purpose');
            $table->string('Facility_Importance', 50)->nullable()->after('Reservation_Frequency');
            $table->string('Requirements_Fit', 50)->nullable()->after('Facility_Importance');
            $table->string('Reserve_Again_Intent', 50)->nullable()->after('Requirements_Fit');
        });
    }
};
