<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feedbacks', 'Request_ID')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->integer('Request_ID')->nullable()->after('User_ID');
            });
        }

        if (! $this->hasIndex('feedbacks', 'feedbacks_request_unique')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->unique('Request_ID', 'feedbacks_request_unique');
            });
        }
    }

    public function down(): void
    {
        // Request_ID is part of the current base schema. Keeping it here makes
        // rollback safe for databases that already had the column.
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $definition): bool => $definition['name'] === $index);
    }
};
