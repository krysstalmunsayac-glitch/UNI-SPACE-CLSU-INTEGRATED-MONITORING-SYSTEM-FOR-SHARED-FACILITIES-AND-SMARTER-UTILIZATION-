<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->boolean('Is_Guest_Booking')->default(false)->after('User_ID')->index();
            $table->string('Guest_Name', 150)->nullable()->after('Is_Guest_Booking');
            $table->string('Guest_Organization', 200)->nullable()->after('Guest_Name');
            $table->string('Guest_Email')->nullable()->after('Guest_Organization');
            $table->string('Guest_Contact', 100)->nullable()->after('Guest_Email');
            $table->foreignId('Created_By')->nullable()->after('Guest_Contact')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['Created_By']);
            $table->dropColumn([
                'Is_Guest_Booking', 'Guest_Name', 'Guest_Organization',
                'Guest_Email', 'Guest_Contact', 'Created_By',
            ]);
        });
    }
};
