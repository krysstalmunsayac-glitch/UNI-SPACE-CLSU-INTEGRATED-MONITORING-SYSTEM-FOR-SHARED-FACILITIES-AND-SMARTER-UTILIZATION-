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

        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->integer('SID')->autoIncrement();
                $table->integer('Request_ID')->unique()->nullable();
                $table->foreign('Request_ID')->references('RID')->on('requests')->cascadeOnDelete();
                $table->date('Date');
                $table->time('Start_Time');
                $table->time('End_Time');
                $table->enum('Status', ['Booked', 'Blocked'])->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent();
                $table->softDeletes();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
