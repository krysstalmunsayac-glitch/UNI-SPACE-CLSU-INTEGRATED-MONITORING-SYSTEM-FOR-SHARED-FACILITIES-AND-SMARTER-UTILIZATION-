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
            $table->date('Proposed_End_Date')->nullable();
            $table->time('Proposed_Start_Time');
            $table->time('Proposed_End_Time');
            $table->json('Daily_Schedules')->nullable();
            $table->enum('Status', ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Ended'])->default('Pending');
            $table->text('Cancellation_Reason')->nullable();
            $table->text('Rejection_Reason')->nullable();
            $table->text('Review_Notes')->nullable();
            $table->timestamp('Review_Requested_At')->nullable();
            $table->text('Purpose');
            $table->json('Purpose_Categories')->nullable();
            $table->string('Other_Purpose', 150)->nullable();
            $table->string('Reservation_Frequency', 50)->nullable();
            $table->string('Facility_Importance', 50)->nullable();
            $table->string('Requirements_Fit', 50)->nullable();
            $table->string('Reserve_Again_Intent', 50)->nullable();
            $table->integer('Capacity')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();

            $table->index(['deleted_at', 'Status', 'Proposed_Date'], 'requests_management_filter_index');
            $table->index(['Status', 'Proposed_End_Date', 'Proposed_End_Time'], 'requests_deadline_index');
        });

        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreign('Request_ID')->references('RID')->on('requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropForeign(['Request_ID']);
        });

        Schema::dropIfExists('requests');
    }
};
