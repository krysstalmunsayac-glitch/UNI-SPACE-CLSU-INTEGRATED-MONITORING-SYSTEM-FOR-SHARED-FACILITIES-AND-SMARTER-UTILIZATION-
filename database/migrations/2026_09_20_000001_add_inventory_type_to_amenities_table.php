<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->string('inventory_type', 20)->default('countable')->after('inventory_quantity');
        });

        DB::table('amenities')
            ->whereIn(DB::raw('LOWER(name)'), [
                'air conditioning',
                'comfort room',
                'cr',
                'open space',
                'restroom',
                'restroom access',
            ])
            ->update(['inventory_type' => 'permanent']);
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropColumn('inventory_type');
        });
    }
};
