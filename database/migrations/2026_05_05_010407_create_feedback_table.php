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
            $table->integer('Request_ID')->nullable();
            $table->unique('Request_ID', 'feedbacks_request_unique');
            $table->integer('Facility_ID')->nullable();
            $table->foreign('Facility_ID')->references('FID')->on('facilities')->nullOnDelete();
            $table->unsignedTinyInteger('Rating')->nullable();
            $table->text('Comment')->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->softDeletes();
            $table->index(['deleted_at', 'Created_at'], 'feedbacks_listing_index');
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
