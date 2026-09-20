<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('audit_logs')
            ->where('auditable_type', 'App\\Models\\Requests')
            ->update(['auditable_type' => 'App\\Models\\FacilityRequest']);
    }

    public function down(): void
    {
        DB::table('audit_logs')
            ->where('auditable_type', 'App\\Models\\FacilityRequest')
            ->update(['auditable_type' => 'App\\Models\\Requests']);
    }
};
