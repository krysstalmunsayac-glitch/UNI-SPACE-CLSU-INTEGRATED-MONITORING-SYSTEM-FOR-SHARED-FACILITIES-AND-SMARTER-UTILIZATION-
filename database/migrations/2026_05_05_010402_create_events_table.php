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

        Schema::create('events', function (Blueprint $table) {
            $table->integer('EID')->autoIncrement();
            $table->unsignedBigInteger('User_ID')->nullable();
            $table->foreign('User_ID')->references('id')->on('users')->nullOnDelete();
            $table->string('Event_Title', 255);
            $table->text('Description')->nullable();
            $table->string('Type_Event', 100)->nullable();
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
        Schema::dropIfExists('events');
    }
};
