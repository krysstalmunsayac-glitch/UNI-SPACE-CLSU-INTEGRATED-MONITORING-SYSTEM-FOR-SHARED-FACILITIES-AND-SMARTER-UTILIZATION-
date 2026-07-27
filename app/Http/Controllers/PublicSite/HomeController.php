<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Facilities;
use App\Models\Schedule;
use App\Support\CalendarColor;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $schedules = collect();

        if (Schema::hasTable('schedules')) {
            $schedules = Schedule::query()
                ->with(['request.facility', 'request.event', 'request.user'])
                ->where('Status', 'Booked')
                ->get()
                ->map(function (Schedule $schedule): array {
                    $request = $schedule->request;
                    $facilityName = $schedule->request?->facility?->Facility_Name
                        ?? "Request #{$schedule->Request_ID}";
                    $eventTitle = $request?->event?->Event_Title
                        ?? $request?->Purpose
                        ?? 'Reserved facility';
                    $colors = CalendarColor::forValue($facilityName);

                    return [
                        'id' => $schedule->SID,
                        'title' => $eventTitle,
                        'facility' => $facilityName,
                        'purpose' => $request?->Purpose,
                        'requester' => $request?->user?->name,
                        'status' => $schedule->Status,
                        'start' => Carbon::parse($schedule->Date)->toDateString().'T'.Carbon::parse($schedule->Start_Time)->format('H:i:s'),
                        'end' => Carbon::parse($schedule->Date)->toDateString().'T'.Carbon::parse($schedule->End_Time)->format('H:i:s'),
                        ...$colors,
                    ];
                });
        }

        return view('welcome', [
            'facilities' => Facilities::query()
                ->with('images')
                ->where('Status', 'Available')
                ->orderBy('Facility_Name')
                ->get(),
            'mapFacilities' => Facilities::query()
                ->orderBy('Facility_Name')
                ->get(['FID', 'Facility_Name', 'Location', 'Description', 'Status']),
            'schedules' => $schedules->values()->all(),
        ]);
    }
}
