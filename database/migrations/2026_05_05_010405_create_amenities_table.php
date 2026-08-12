<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->integer('AID')->autoIncrement();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('name');
            $table->text('Description')->nullable();
            $table->enum('Status', ['Available', 'Unavailable'])->default('Available');
            $table->unsignedInteger('reservation_limit')->nullable();
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();
            $table->unique('name');
            $table->index(['deleted_at', 'Status'], 'amenities_status_index');
            $table->index(['created_by', 'deleted_at'], 'amenities_creator_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
