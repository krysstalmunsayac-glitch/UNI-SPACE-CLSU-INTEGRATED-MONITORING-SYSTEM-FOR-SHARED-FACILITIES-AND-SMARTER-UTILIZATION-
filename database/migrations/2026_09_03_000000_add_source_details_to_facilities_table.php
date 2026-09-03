<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->enum('Access_Type', ['Shared', 'Exclusive'])->nullable()->after('facility_type');
            $table->text('Rate_Details')->nullable()->after('Price');
            $table->text('Protocols')->nullable()->after('Description');
            $table->text('Contact_Details')->nullable()->after('Protocols');
            $table->text('Reference_URL')->nullable()->after('Contact_Details');
            $table->text('Data_Notes')->nullable()->after('Reference_URL');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropColumn([
                'Access_Type',
                'Rate_Details',
                'Protocols',
                'Contact_Details',
                'Reference_URL',
                'Data_Notes',
            ]);
        });
    }
};
