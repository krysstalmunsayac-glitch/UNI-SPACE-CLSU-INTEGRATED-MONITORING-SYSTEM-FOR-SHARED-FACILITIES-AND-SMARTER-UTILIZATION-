<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE requests MODIFY Status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Ended') NOT NULL DEFAULT 'Pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('requests')->where('Status', 'Ended')->update(['Status' => 'Approved']);
            DB::statement("ALTER TABLE requests MODIFY Status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending'");
        }
    }
};
