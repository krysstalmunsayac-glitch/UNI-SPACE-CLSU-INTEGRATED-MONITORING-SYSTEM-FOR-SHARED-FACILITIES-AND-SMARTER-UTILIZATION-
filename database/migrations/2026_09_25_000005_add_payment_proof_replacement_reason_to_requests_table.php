<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->text('Payment_Proof_Replacement_Reason')->nullable()->after('Payment_Proof_Uploaded_At');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table): void {
            $table->dropColumn('Payment_Proof_Replacement_Reason');
        });
    }
};
