<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('clsu_id', 11)->nullable()->change();
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('clsu_id', 11)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('clsu_id', 7)->nullable()->change();
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('clsu_id', 7)->nullable()->change();
        });
    }
};
