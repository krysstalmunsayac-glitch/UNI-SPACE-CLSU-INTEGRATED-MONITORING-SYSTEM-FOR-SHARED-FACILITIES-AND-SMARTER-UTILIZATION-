<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_facility_amenities', function (Blueprint $table) {
            $table->id('RFAID');
            $table->integer('Request_ID');
            $table->integer('Amenity_ID');
            $table->foreign('Request_ID')->references('RID')->on('requests')->cascadeOnDelete();
            $table->foreign('Amenity_ID')->references('AID')->on('amenities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['Request_ID', 'Amenity_ID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_facility_amenities');
    }
};
