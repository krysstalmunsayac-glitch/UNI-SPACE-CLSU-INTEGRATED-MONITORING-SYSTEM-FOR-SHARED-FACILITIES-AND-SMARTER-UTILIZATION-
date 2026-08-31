<?php

namespace App\Models;

use App\Notifications\RequestFeedbackRequested;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Requests extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'Created_at';

    public const UPDATED_AT = 'Updated_at';

    protected $table = 'requests';

    protected $primaryKey = 'RID';

    protected $fillable = [
        'Event_ID',
        'User_ID',
        'Facility_ID',
        'Proposed_Date',
        'Proposed_End_Date',
        'Proposed_Start_Time',
        'Proposed_End_Time',
        'Daily_Schedules',
        'Status',
        'Cancellation_Reason',
        'Rejection_Reason',
        'Review_Notes',
        'Review_Requested_At',
        'Purpose',
        'Purpose_Categories',
        'Other_Purpose',
        'Reservation_Frequency',
        'Facility_Importance',
        'Requirements_Fit',
        'Reserve_Again_Intent',
        'Capacity',
        'attachment_path',
    ];

    protected $casts = [
        'Proposed_Date' => 'date',
        'Proposed_End_Date' => 'date',
        'Proposed_Start_Time' => 'datetime:H:i',
        'Proposed_End_Time' => 'datetime:H:i',
        'Daily_Schedules' => 'array',
        'Capacity' => 'integer',
        'Purpose_Categories' => 'array',
        'Review_Requested_At' => 'datetime',
    ];

    /** @var array<string, list<string>> */
    private const STATUS_TRANSITIONS = [
        'Pending' => ['Approved', 'Rejected', 'Cancelled'],
        'Approved' => ['Cancelled', 'Ended'],
        'Rejected' => [],
        'Cancelled' => [],
        'Ended' => [],
    ];

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::allowedTransitionsFrom($this->Status), true);
    }

    /** @return list<string> */
    public static function allowedTransitionsFrom(string $status): array
    {
        return self::STATUS_TRANSITIONS[$status] ?? [];
    }

    public function canBeReviewed(): bool
    {
        return $this->Status === 'Pending';
    }

    protected static function booted(): void
    {
        static::created(function (Requests $request): void {
            AuditLog::recordRequest(
                $request,
                'request_submitted',
                "Submitted request #{$request->RID}.",
                null,
                $request->only(['User_ID', 'Facility_ID', 'Proposed_Date', 'Status', 'Purpose']),
            );
        });

        static::updated(function (Requests $request): void {
            $changes = collect($request->getChanges())->except(['Updated_at'])->all();

            if ($changes === []) {
                return;
            }

            $oldValues = collect(array_keys($changes))
                ->mapWithKeys(fn (string $key) => [$key => $request->getOriginal($key)])
                ->all();

            $newStatus = $changes['Status'] ?? null;
            $action = match ($newStatus) {
                'Approved' => 'request_approved',
                'Rejected' => 'request_rejected',
                'Cancelled' => 'request_cancelled',
                'Ended' => 'event_ended',
                default => array_key_exists('Review_Requested_At', $changes)
                    ? 'revision_requested'
                    : 'request_updated',
            };

            $description = match ($action) {
                'request_approved' => "Approved request #{$request->RID}.",
                'request_rejected' => "Rejected request #{$request->RID}.",
                'request_cancelled' => "Cancelled request #{$request->RID}.",
                'event_ended' => "Marked event for request #{$request->RID} as ended.",
                'revision_requested' => "Requested revisions for request #{$request->RID}.",
                default => "Updated request #{$request->RID}.",
            };

            AuditLog::recordRequest($request, $action, $description, $oldValues, $changes);

            if ($newStatus === 'Ended' && $request->getOriginal('Status') !== 'Ended') {
                $request->loadMissing(['user', 'facility']);

                if ($request->user && $request->Facility_ID) {
                    $request->user->notify(new RequestFeedbackRequested($request));
                }
            }
        });

        static::deleted(fn (Requests $request) => AuditLog::recordRequest(
            $request,
            'request_archived',
            "Archived request #{$request->RID}.",
        ));

        static::restored(fn (Requests $request) => AuditLog::recordRequest(
            $request,
            'request_restored',
            "Restored request #{$request->RID}.",
        ));

        static::forceDeleted(fn (Requests $request) => AuditLog::recordRequest(
            $request,
            'request_deleted',
            "Permanently deleted request #{$request->RID}.",
        ));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID')->withTrashed();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Events::class, 'Event_ID');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facilities::class, 'Facility_ID', 'FID');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenities::class,
            'request_facility_amenities',
            'Request_ID',
            'Amenity_ID'
        )->withTimestamps();
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class, 'Request_ID');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'Request_ID');
    }

    public function feedback(): HasOne
    {
        // A request may only ever have one feedback record. Include archived
        // feedback so the application agrees with the database unique index.
        return $this->hasOne(Feedbacks::class, 'Request_ID', 'RID')->withTrashed();
    }

    /**
     * Mark active requests as ended and archive them once their end time passes.
     */
    public static function markPastRequestsAsEnded(): int
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');

        $endedCount = 0;

        static::query()
            ->whereIn('Status', ['Pending', 'Approved'])
            ->where(function (Builder $query) use ($today, $currentTime) {
                $query->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '<', $today)
                    ->orWhere(function (Builder $query) use ($today, $currentTime) {
                        $query->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), $today)
                            ->whereTime('Proposed_End_Time', '<=', $currentTime);
                    });
            })
            ->with(['user', 'facility'])
            ->select(['RID', 'User_ID', 'Facility_ID', 'Status'])
            ->chunkById(100, function ($requests) use (&$endedCount): void {
                foreach ($requests as $request) {
                    $oldStatus = $request->Status;
                    $request->updateQuietly(['Status' => 'Ended']);

                    AuditLog::recordRequest(
                        $request,
                        'event_ended',
                        "Automatically marked event for request #{$request->RID} as ended.",
                        ['Status' => $oldStatus],
                        ['Status' => 'Ended'],
                        useAuthenticatedActor: false,
                    );

                    if ($request->user && $request->Facility_ID) {
                        $request->user->notify(new RequestFeedbackRequested($request));
                    }

                    $request->delete();
                    $endedCount++;
                }
            }, 'RID', 'RID');

        return $endedCount;
    }

    /**
     * Archive cancelled requests after they have remained cancelled for 10 days.
     */
    public static function archiveExpiredCancelledRequests(): int
    {
        return static::query()
            ->where('Status', 'Cancelled')
            ->where('Updated_at', '<=', now()->subDays(10))
            ->delete();
    }

    /**
     * Return the saved time for a date, with legacy requests falling back to
     * their original shared start/end time.
     *
     * @return array{date:string,start:string,end:string}|null
     */
    public function scheduleForDate(string $date): ?array
    {
        foreach ($this->Daily_Schedules ?? [] as $schedule) {
            if (($schedule['date'] ?? null) === $date) {
                return $schedule;
            }
        }

        $firstDate = $this->Proposed_Date?->toDateString();
        $lastDate = ($this->Proposed_End_Date ?? $this->Proposed_Date)?->toDateString();

        if (! $firstDate || $date < $firstDate || $date > $lastDate) {
            return null;
        }

        return [
            'date' => $date,
            'start' => $this->Proposed_Start_Time?->format('H:i') ?? '00:00',
            'end' => $this->Proposed_End_Time?->format('H:i') ?? '00:00',
        ];
    }

    /** @param array<int, array{date:string,start:string,end:string}> $dailySchedules */
    public static function hasActiveDailyScheduleConflict(
        int $facilityId,
        array $dailySchedules,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): bool {
        return static::dailyScheduleConflicts(
            $facilityId,
            $dailySchedules,
            $ignoreRequestId,
            $lockForUpdate,
        )->isNotEmpty();
    }

    /**
     * Return requests whose date and time slots overlap the supplied schedule.
     * Pending requests may coexist; only approved requests block new submissions.
     *
     * @param  array<int, array{date:string,start:string,end:string}>  $dailySchedules
     * @param  array<int, string>  $statuses
     * @return Collection<int, static>
     */
    public static function dailyScheduleConflicts(
        int $facilityId,
        array $dailySchedules,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
        array $statuses = ['Approved'],
    ): Collection {
        if ($dailySchedules === []) {
            return collect();
        }

        $dates = array_column($dailySchedules, 'date');
        $query = static::query()
            ->where('Facility_ID', $facilityId)
            ->whereIn('Status', $statuses)
            ->whereDate('Proposed_Date', '<=', max($dates))
            ->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', min($dates))
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate());

        return $query->get()->filter(function (self $existingRequest) use ($dailySchedules): bool {
            foreach ($dailySchedules as $schedule) {
                $existing = $existingRequest->scheduleForDate($schedule['date']);
                if ($existing && $existing['start'] < $schedule['end'] && $existing['end'] > $schedule['start']) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    public static function hasActiveFacilityConflict(
        int $facilityId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): bool {
        $query = static::activeFacilityConflicts($facilityId, $startDate, $endDate, $startTime, $endTime, $ignoreRequestId, $lockForUpdate);

        return $lockForUpdate
            ? $query->select('RID')->first() !== null
            : $query->exists();
    }

    /**
     * Determine whether a user already has a live reservation request for an event date.
     */
    public static function userHasRequestOnDate(
        int $userId,
        string $startDate,
        ?string $endDate = null,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): bool {
        $endDate ??= $startDate;

        $query = static::query()
            ->where('User_ID', $userId)
            ->whereNotIn('Status', ['Cancelled', 'Rejected'])
            ->whereDate('Proposed_Date', '<=', $endDate)
            ->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', $startDate)
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate());

        return $lockForUpdate
            ? $query->select('RID')->first() !== null
            : $query->exists();
    }

    public static function activeFacilityConflicts(
        int $facilityId,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
        bool $lockForUpdate = false,
    ): Builder {
        return static::query()
            ->where('Facility_ID', $facilityId)
            ->whereDate('Proposed_Date', '<=', $endDate)
            ->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', $startDate)
            ->where('Status', 'Approved')
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('Proposed_Start_Time', '<', $endTime)
                    ->where('Proposed_End_Time', '>', $startTime);
            });
    }
}
