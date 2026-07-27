<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (! Schema::hasTable('facility_amenity')) {
            Schema::create('facility_amenity', function (Blueprint $table) {
                $table->id();
                $table->integer('Facility_ID');
                $table->integer('Amenity_ID');
                $table->foreign('Facility_ID')->references('FID')->on('facilities')->cascadeOnDelete();
                $table->foreign('Amenity_ID')->references('AID')->on('amenities')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['Facility_ID', 'Amenity_ID']);
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('facility_amenity');

        Schema::enableForeignKeyConstraints();
    }
};
