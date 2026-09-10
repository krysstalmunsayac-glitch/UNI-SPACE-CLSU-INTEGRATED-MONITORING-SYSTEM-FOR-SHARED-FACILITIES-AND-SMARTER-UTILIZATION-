<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('Event_Scope', 20)->nullable()->after('Type_Event');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('Event_Scope');
        });
    }
};
