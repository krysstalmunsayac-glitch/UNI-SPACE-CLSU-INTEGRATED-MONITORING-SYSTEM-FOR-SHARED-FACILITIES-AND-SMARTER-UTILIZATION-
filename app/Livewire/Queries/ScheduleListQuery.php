<?php

namespace App\Livewire\Queries;

use App\Models\Facility;
use App\Models\FacilityRequest;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ScheduleListQuery
{
    private const TYPE_COLORS = [
        'auditorium' => ['bg' => '#2563eb', 'border' => '#1d4ed8'],
        'classroom' => ['bg' => '#d97706', 'border' => '#b45309'],
        'conference' => ['bg' => '#006b2b', 'border' => '#009639'],
        'gymnasium' => ['bg' => '#7c3aed', 'border' => '#6d28d9'],
        'laboratory' => ['bg' => '#0891b2', 'border' => '#0e7490'],
        'lounge' => ['bg' => '#db2777', 'border' => '#be185d'],
        'office' => ['bg' => '#4f46e5', 'border' => '#4338ca'],
        'outdoor' => ['bg' => '#16a34a', 'border' => '#15803d'],
        'sports' => ['bg' => '#ea580c', 'border' => '#c2410c'],
    ];

    private const FALLBACK_COLORS = [
        ['bg' => '#0f766e', 'border' => '#115e59'],
        ['bg' => '#9333ea', 'border' => '#7e22ce'],
        ['bg' => '#ca8a04', 'border' => '#a16207'],
        ['bg' => '#0284c7', 'border' => '#0369a1'],
        ['bg' => '#c026d3', 'border' => '#a21caf'],
        ['bg' => '#65a30d', 'border' => '#4d7c0f'],
    ];

    public function schedule(User $actor, int $scheduleId, bool $withTrashed = false): Schedule
    {
        return Schedule::query()->when($withTrashed, fn ($query) => $query->withTrashed())
            ->with(['request.facility'])
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('request', fn ($request) => $request
                ->withTrashed()->whereHas('facility.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id))))
            ->findOrFail($scheduleId);
    }

    public function request(User $actor, int $requestId): FacilityRequest
    {
        return FacilityRequest::query()->with('facility:FID,Facility_Name')
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facility.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->findOrFail($requestId);
    }

    public function archivedSchedules(User $actor): LengthAwarePaginator
    {
        return Schedule::query()->onlyTrashed()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('request', fn ($request) => $request
                ->withTrashed()->whereHas('facility.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id))))
            ->with(['request' => fn ($request) => $request->withTrashed()->select(['RID', 'Facility_ID', 'Purpose'])->with('facility:FID,Facility_Name')])
            ->orderBy('deleted_at')
            ->orderBy('SID')
            ->paginate(8, pageName: 'archivedSchedulesPage');
    }

    public function facilities(User $actor): Collection
    {
        return Facility::query()
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->orderBy('Facility_Name')->get(['FID', 'Facility_Name', 'facility_type']);
    }

    public function requests(User $actor, ?int $currentRequestId): Collection
    {
        return FacilityRequest::withTrashed()
            ->with([
                'facility:FID,Facility_Name',
                'user:id,name',
            ])
            ->select(['RID', 'User_ID', 'Is_Guest_Booking', 'Guest_Name', 'Facility_ID', 'Purpose', 'deleted_at'])
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('facility.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->where(fn ($query) => $query->whereNull('deleted_at')
                ->when($currentRequestId, fn ($query) => $query->orWhere('RID', $currentRequestId)))
            ->where(fn ($query) => $query->whereDoesntHave('schedules')
                ->when($currentRequestId, fn ($query) => $query->orWhere('RID', $currentRequestId)))
            ->orderByDesc('RID')->get();
    }

    public function calendarEvents(User $actor, ?int $facilityId, string $search): array
    {
        return Schedule::query()
            ->with([
                'request:RID,Event_ID,Facility_ID,User_ID,Is_Guest_Booking,Guest_Name,Purpose,Status',
                'request.facility:FID,Facility_Name,facility_type',
                'request.event:EID,Event_Title',
                'request.user:id,name',
            ])
            ->when($actor->isAdmin(), fn ($query) => $query->whereHas('request.facility.assignedAdmins', fn ($admins) => $admins->where('users.id', $actor->id)))
            ->when($facilityId, fn ($query) => $query->whereHas('request.facility', fn ($facility) => $facility->where('FID', $facilityId)))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->whereHas('request', fn ($request) => $request->where('Purpose', 'like', '%'.$search.'%'))
                ->orWhereHas('request.facility', fn ($facility) => $facility->where('Facility_Name', 'like', '%'.$search.'%'))))
            ->orderBy('Date')->orderBy('Start_Time')->get()
            ->map(fn (Schedule $schedule) => $this->toCalendarEvent($schedule))->values()->toArray();
    }

    public function legend(Collection $facilities): array
    {
        return $facilities->pluck('facility_type')->filter()->unique()->sort()
            ->map(fn (string $type) => ['label' => $type, ...$this->colors($type)])
            ->values()->toArray();
    }

    private function toCalendarEvent(Schedule $schedule): array
    {
        $date = Carbon::parse($schedule->Date)->toDateString();
        $start = Carbon::parse($schedule->Start_Time)->format('H:i:s');
        $endsAtMidnight = substr((string) $schedule->getRawOriginal('End_Time'), 0, 5) === '24:00';
        $endDate = $endsAtMidnight ? Carbon::parse($schedule->Date)->addDay()->toDateString() : $date;
        $end = $endsAtMidnight ? '00:00:00' : Carbon::parse($schedule->End_Time)->format('H:i:s');
        $facility = $schedule->request?->facility?->Facility_Name ?? 'Request #'.$schedule->Request_ID;
        $facilityType = $schedule->request?->facility?->facility_type;
        $colors = $this->colors($facilityType);
        $eventName = $schedule->request?->event?->Event_Title ?? 'Reserved facility';
        $isEnded = $schedule->request?->Status === 'Ended';
        $isBlocked = $schedule->Status === 'Blocked';

        return [
            'id' => $schedule->SID,
            'title' => ($isBlocked ? 'Blocked' : $eventName).' · '.$facility,
            'start' => "{$date}T{$start}",
            'end' => "{$endDate}T{$end}",
            'backgroundColor' => $isEnded ? '#dc2626' : ($schedule->Status === 'Booked' ? $colors['bg'] : '#9ca3af'),
            'borderColor' => $isEnded ? '#991b1b' : ($schedule->Status === 'Booked' ? $colors['border'] : '#6b7280'),
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $isEnded ? 'Completed' : $schedule->Status,
                'scheduleId' => $schedule->SID,
                'facility' => $facility,
                'facilityType' => $facilityType ?: 'Unspecified',
                'event' => $eventName,
                'purpose' => $schedule->request?->Purpose,
                'requester' => $schedule->request?->requesterName(),
            ],
        ];
    }

    private function colors(?string $type): array
    {
        $key = str(trim((string) $type))->lower()->toString();
        if ($key === '') {
            return ['bg' => '#64748b', 'border' => '#475569'];
        }
        foreach (self::TYPE_COLORS as $needle => $colors) {
            if (str_contains($key, $needle)) {
                return $colors;
            }
        }

        return self::FALLBACK_COLORS[crc32($key) % count(self::FALLBACK_COLORS)];
    }
}
