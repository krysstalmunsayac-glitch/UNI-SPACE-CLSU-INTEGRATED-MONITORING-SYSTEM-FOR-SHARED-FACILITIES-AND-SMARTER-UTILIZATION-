<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL may leave the empty table behind when adding its foreign key
        // fails. This migration has never completed, so make retries safe.
        Schema::dropIfExists('facility_blackouts');

        Schema::create('facility_blackouts', function (Blueprint $table) {
            $table->id();
            // facilities.FID is a signed INT, not Laravel's default unsigned BIGINT.
            $table->integer('facility_id');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->default('Facility unavailable');
            $table->timestamps();
            $table->index(['facility_id', 'starts_on', 'ends_on']);
            $table->foreign('facility_id')
                ->references('FID')
                ->on('facilities')
                ->cascadeOnDelete();
        });
    }

    public function down(): void { Schema::dropIfExists('facility_blackouts'); }
};
