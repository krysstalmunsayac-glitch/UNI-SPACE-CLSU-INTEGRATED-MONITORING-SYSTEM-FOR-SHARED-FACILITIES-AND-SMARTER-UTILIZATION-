<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('Request_Details')->nullable()->after('Purpose');
        });

        DB::table('requests')
            ->whereNull('Request_Details')
            ->whereNotNull('Event_ID')
            ->orderBy('RID')
            ->chunkById(200, function ($requests): void {
                $descriptions = DB::table('events')
                    ->whereIn('EID', $requests->pluck('Event_ID')->filter()->unique())
                    ->pluck('Description', 'EID');

                foreach ($requests as $request) {
                    $details = $descriptions->get($request->Event_ID);

                    if ($details !== null && $details !== '') {
                        DB::table('requests')->where('RID', $request->RID)->update([
                            'Request_Details' => $details,
                        ]);
                    }
                }
            }, 'RID');
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('Request_Details');
        });
    }
};
