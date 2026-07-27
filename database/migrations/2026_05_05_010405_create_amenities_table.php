<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->integer('AID')->autoIncrement();
            $table->string('name');
            $table->text('Description')->nullable();
            $table->enum('Status', ['Available', 'Unavailable'])->default('Available');
            $table->timestamp('Created_at')->nullable()->useCurrent();
            $table->timestamp('Updated_at')->nullable()->useCurrent();
            $table->softDeletes();
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
