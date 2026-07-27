<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->integer('RID')->autoIncrement();

            $table->integer('Event_ID')->nullable();
            $table->foreign('Event_ID')
                ->references('EID')
                ->on('events')
                ->nullOnDelete();

            $table->integer('Facility_ID')->nullable();
            $table->foreign('Facility_ID')
                ->references('FID')
                ->on('facilities')
                ->nullOnDelete();

            $table->unsignedBigInteger('User_ID')->nullable();
            $table->foreign('User_ID')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->date('Proposed_Date');
            $table->time('Proposed_Start_Time');
            $table->time('Proposed_End_Time');
            $table->enum('Status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');
            $table->text('Cancellation_Reason')->nullable();
            $table->text('Rejection_Reason')->nullable();
            $table->text('Purpose');
            $table->integer('Capacity')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
