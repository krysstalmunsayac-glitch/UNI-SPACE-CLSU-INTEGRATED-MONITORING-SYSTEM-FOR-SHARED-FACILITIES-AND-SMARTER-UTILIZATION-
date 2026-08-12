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
            $table->decimal('Price')->unsigned()->nullable();
            $table->string('Office', 255)->nullable();
            $table->text('Description')->nullable();
            $table->string('Location', 255)->nullable();
            $table->decimal('Latitude', 10, 7)->nullable();
            $table->decimal('Longitude', 10, 7)->nullable();
            $table->integer('Capacity')->nullable();
            $table->enum('facility_type', ['sports', 'conference', 'auditorium', 'classroom', 'laboratory', 'other'])->nullable();
            $table->enum('Status', ['Available', 'Unavailable'])->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();
            $table->index(['deleted_at', 'Status'], 'facilities_status_index');
            $table->index(['Office', 'deleted_at'], 'facilities_office_index');
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
