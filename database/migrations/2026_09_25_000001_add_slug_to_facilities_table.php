<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('Facility_Name');
        });

        $usedSlugs = [];

        DB::table('facilities')
            ->orderBy('FID')
            ->get(['FID', 'Facility_Name'])
            ->each(function (object $facility) use (&$usedSlugs): void {
                $base = Str::slug((string) $facility->Facility_Name) ?: 'facility';
                $slug = $base;
                $suffix = 2;

                while (isset($usedSlugs[$slug])) {
                    $slug = $base.'-'.$suffix++;
                }

                $usedSlugs[$slug] = true;

                DB::table('facilities')
                    ->where('FID', $facility->FID)
                    ->update(['slug' => $slug]);
            });

        Schema::table('facilities', function (Blueprint $table): void {
            $table->unique('slug', 'facilities_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropUnique('facilities_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
