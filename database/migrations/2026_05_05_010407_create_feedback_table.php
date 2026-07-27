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

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->integer('FID')->autoIncrement();
            $table->unsignedBigInteger('User_ID')->nullable();
            $table->foreign('User_ID')->references('id')->on('users')->onDelete('cascade');
            $table->integer('Facility_ID')->nullable();
            $table->foreign('Facility_ID')->references('FID')->on('facilities')->nullOnDelete();
            $table->text('Comment')->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
