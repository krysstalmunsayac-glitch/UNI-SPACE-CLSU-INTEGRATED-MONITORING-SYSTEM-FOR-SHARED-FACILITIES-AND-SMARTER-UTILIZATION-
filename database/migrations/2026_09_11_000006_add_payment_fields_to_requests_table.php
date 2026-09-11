<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->string('Status', 50)->default('Pending')->change();
            $table->decimal('Payment_Amount', 12, 2)->nullable()->after('Status');
            $table->dateTime('Payment_Deadline')->nullable()->after('Payment_Amount');
            $table->string('Payment_Proof_Path')->nullable()->after('Payment_Deadline');
            $table->timestamp('Payment_Proof_Uploaded_At')->nullable()->after('Payment_Proof_Path');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['Payment_Amount', 'Payment_Deadline', 'Payment_Proof_Path', 'Payment_Proof_Uploaded_At']);
        });
    }
};
