<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('facilities', function (Blueprint $table) {
            $table->integer('FID')->autoIncrement();
            $table->string('Facility_Name', 255);
            $table->string('Image_URL', 255)->nullable();
            $table->decimal('Price');
            $table->string('Office', 255)->nullable();
            $table->text('Description')->nullable();
            $table->string('Location', 255)->nullable();
            $table->integer('Capacity')->nullable();
            $table->enum('facility_type', ['sports', 'conference', 'auditorium', 'classroom', 'laboratory', 'other'])->nullable();
            $table->enum('Status', ['Available', 'Under Maintenance', 'Unavailable'])->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
