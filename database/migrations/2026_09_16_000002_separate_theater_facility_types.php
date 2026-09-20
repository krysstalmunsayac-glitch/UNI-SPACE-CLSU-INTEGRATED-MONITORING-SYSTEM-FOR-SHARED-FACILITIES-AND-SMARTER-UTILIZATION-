<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->enum('facility_type', ['sports', 'conference', 'auditorium', 'amphitheater', 'little_theater', 'classroom', 'laboratory', 'other'])
                ->nullable()
                ->change();
        });

        DB::table('facilities')
            ->where(function ($query): void {
                $query->where('Facility_Name', 'like', '%Amphitheater%')
                    ->orWhere('Facility_Name', 'like', '%Amphitheatre%');
            })
            ->update(['facility_type' => 'amphitheater']);

        DB::table('facilities')
            ->where(function ($query): void {
                $query->where('Facility_Name', 'like', '%Little Theater%')
                    ->orWhere('Facility_Name', 'like', '%Little Theatre%');
            })
            ->update(['facility_type' => 'little_theater']);
    }

    public function down(): void
    {
        DB::table('facilities')->whereIn('facility_type', ['amphitheater', 'little_theater'])->update(['facility_type' => 'auditorium']);

        Schema::table('facilities', function (Blueprint $table): void {
            $table->enum('facility_type', ['sports', 'conference', 'auditorium', 'classroom', 'laboratory', 'other'])
                ->nullable()
                ->change();
        });
    }
};
