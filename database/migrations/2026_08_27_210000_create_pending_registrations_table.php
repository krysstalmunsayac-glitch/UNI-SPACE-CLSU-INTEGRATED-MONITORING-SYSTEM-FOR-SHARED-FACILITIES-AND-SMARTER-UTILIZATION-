<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('email')->unique();
            $table->text('registration_data');
            $table->string('pin_hash');
            $table->dateTime('pin_expires_at');
            $table->dateTime('resend_available_at');
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
