<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropUnique('amenities_name_unique');
        });

        Schema::table('amenities', function (Blueprint $table): void {
            $table->index('name', 'amenities_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropIndex('amenities_name_index');
        });

        Schema::table('amenities', function (Blueprint $table): void {
            $table->unique('name', 'amenities_name_unique');
        });
    }
};
