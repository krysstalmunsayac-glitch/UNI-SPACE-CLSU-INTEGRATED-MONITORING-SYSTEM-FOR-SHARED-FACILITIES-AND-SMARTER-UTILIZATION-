<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Facilities;
use App\Models\Requests;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        Requests::markPastRequestsAsEnded();

        $schedules = collect();

        if (Schema::hasTable('schedules')) {
            $schedules = Schedule::query()
                ->with(['request.facility', 'request.event'])
                ->where('Status', 'Booked')
                ->get()
                ->map(function (Schedule $schedule): array {
                    $request = $schedule->request;
                    $facilityName = $schedule->request?->facility?->Facility_Name
                        ?? "Request #{$schedule->Request_ID}";
                    $eventTitle = $request?->event?->Event_Title
                        ?? $request?->Purpose
                        ?? $facilityName;
                    $date = Carbon::parse($schedule->Date)->toDateString();
                    $start = Carbon::parse($date.' '.Carbon::parse($schedule->Start_Time)->format('H:i:s'));
                    $end = Carbon::parse($date.' '.Carbon::parse($schedule->End_Time)->format('H:i:s'));
                    $status = $request?->Status === 'Ended' || $end->isPast()
                        ? 'Ended'
                        : ($start->isPast() ? 'Ongoing' : 'Approved');
                    $colors = match ($status) {
                        'Ongoing' => ['background' => '#007a2f', 'border' => '#006b2b'],
                        'Ended' => ['background' => '#737373', 'border' => '#525252'],
                        default => ['background' => '#007a2f', 'border' => '#006b2b'],
                    };

                    return [
                        'id' => $schedule->SID,
                        'title' => $eventTitle,
                        'facility' => $facilityName,
                        'status' => $status,
                        'start' => $start->format('Y-m-d\TH:i:s'),
                        'end' => $end->format('Y-m-d\TH:i:s'),
                        'backgroundColor' => $colors['background'],
                        'borderColor' => $colors['border'],
                    ];
                });
        }

        $facilities = Facilities::query()
            ->with(['images', 'amenities' => fn ($query) => $query
                ->where('amenities.Status', 'Available')
                ->orderBy('amenities.name')])
            ->orderBy('Facility_Name')
            ->get();

        $categoryLabels = [
            'auditorium' => 'Auditoriums',
            'classroom' => 'Classrooms',
            'conference' => 'Conference spaces',
            'laboratory' => 'Laboratories',
            'sports' => 'Sports facilities',
            'other' => 'Other spaces',
        ];

        $facilityCategories = $facilities
            ->filter(fn (Facilities $facility) => filled($facility->facility_type))
            ->groupBy(fn (Facilities $facility) => strtolower($facility->facility_type))
            ->map(function ($group, string $type) use ($categoryLabels): array {
                $featured = $group->first(fn (Facilities $facility) => $facility->images->isNotEmpty() || filled($facility->Image_URL));

                return [
                    'type' => $type,
                    'name' => $categoryLabels[$type] ?? ucfirst($type).' spaces',
                    'count' => $group->count(),
                    'image' => $featured?->primaryImageUrl() ?? asset('images/siel-space-slide-02.jpg'),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $homepageStats = [
            'available_facilities' => $facilities->where('Status', 'Available')->count(),
            'facility_types' => $facilities->pluck('facility_type')->filter()->map(fn ($type) => strtolower($type))->unique()->count(),
            'requests_this_month' => Requests::query()
                ->whereBetween('Created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'upcoming_reservations' => $schedules
                ->filter(fn (array $event) => Carbon::parse($event['end'])->isFuture())
                ->count(),
        ];

        return view('welcome', [
            'facilities' => $facilities,
            'facilityCategories' => $facilityCategories,
            'homepageStats' => $homepageStats,
            'schedules' => $schedules->values()->all(),
        ]);
    }
}
