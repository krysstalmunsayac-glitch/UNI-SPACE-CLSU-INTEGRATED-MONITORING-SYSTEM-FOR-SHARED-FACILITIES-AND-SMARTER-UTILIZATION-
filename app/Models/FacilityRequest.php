<?php

namespace App\Models;

use App\Notifications\RequestFeedbackRequested;
use App\Notifications\RequestStatusUpdated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FacilityRequest extends Model
{
    use SoftDeletes;

    public const MAX_REQUESTS_PER_EVENT_DATE = 3;

    public const PURPOSE_OPTIONS = [
        'Meeting or Conference',
        'Seminar or Workshop',
        'Training Session',
        'Class or Educational Activity',
        'Student Organization Event',
        'Club Meeting',
        'Sports or Recreational Activity',
        'Cultural or Arts Program',
        'Religious Activity',
        'Community Outreach Program',
        'Birthday Celebration',
        'Wedding Reception or Ceremony',
        'Family Gathering or Reunion',
        'Corporate Event',
        'Product Launch or Promotion',
        'Exhibition or Fair',
        'Concert or Performance',
        'Graduation or Recognition Ceremony',
        'Health or Medical Mission',
        'Government or Public Service Activity',
        'Photo or Video Shoot',
        'Other',
    ];

    public const CREATED_AT = 'Created_at';

    public const UPDATED_AT = 'Updated_at';

    protected $table = 'requests';

    protected $primaryKey = 'RID';

    protected $fillable = [
        'Event_ID',
        'User_ID',
        'Is_Guest_Booking',
        'Guest_Name',
        'Guest_Organization',
        'Guest_Email',
        'Guest_Contact',
        'Created_By',
        'Facility_ID',
        'Proposed_Date',
        'Proposed_End_Date',
        'Proposed_Start_Time',
        'Proposed_End_Time',
        'Daily_Schedules',
        'Status',
        'Payment_Amount',
        'Payment_Deadline',
        'Payment_Proof_Path',
        'Payment_Proof_Uploaded_At',
        'Payment_Proof_Replacement_Reason',
        'Cancellation_Reason',
        'Rejection_Reason',
        'Review_Notes',
        'Review_Requested_At',
        'Purpose',
        'Request_Details',
        'Purpose_Categories',
        'Other_Purpose',
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
        'Is_Guest_Booking' => 'boolean',
        'Payment_Amount' => 'decimal:2',
        'Payment_Deadline' => 'datetime',
        'Payment_Proof_Uploaded_At' => 'datetime',
    ];

    /** @var array<string, list<string>> */
    private const STATUS_TRANSITIONS = [
        'Pending' => ['Awaiting Payment', 'Approved', 'Rejected', 'Cancelled'],
        'Awaiting Payment' => ['Approved', 'Rejected', 'Cancelled'],
        'Approved' => ['Cancelled', 'Ended'],
        'Rejected' => [],
        'Cancelled' => [],
        'Expired' => [],
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

    /** @return list<string> */
    public function selectablePurposeCategories(): array
    {
        $stored = collect($this->Purpose_Categories)
            ->filter(fn ($purpose): bool => is_string($purpose) && in_array($purpose, self::PURPOSE_OPTIONS, true))
            ->values();

        if ($stored->isNotEmpty()) {
            return $stored->all();
        }

        $purpose = trim((string) $this->Purpose);

        if ($purpose === '') {
            return [];
        }

        return in_array($purpose, self::PURPOSE_OPTIONS, true) ? [$purpose] : ['Other'];
    }

    public function selectableOtherPurpose(): ?string
    {
        if ($this->Other_Purpose !== null && trim($this->Other_Purpose) !== '') {
            return $this->Other_Purpose;
        }

        $purpose = trim((string) $this->Purpose);

        return $purpose !== '' && ! in_array($purpose, self::PURPOSE_OPTIONS, true) ? $purpose : null;
    }

    public function canBeReviewed(): bool
    {
        return $this->Status === 'Pending';
    }

    protected static function booted(): void
    {
        static::created(function (FacilityRequest $request): void {
            AuditLog::recordRequest(
                $request,
                'request_submitted',
                $request->Is_Guest_Booking
                    ? "Submitted guest request #{$request->RID} on behalf of {$request->Guest_Name}."
                    : "Submitted request #{$request->RID}.",
                null,
                $request->only(['User_ID', 'Created_By', 'Is_Guest_Booking', 'Guest_Name', 'Guest_Organization', 'Facility_ID', 'Proposed_Date', 'Status', 'Purpose', 'Request_Details']),
            );
        });

        static::updated(function (FacilityRequest $request): void {
            $changes = collect($request->getChanges())->except(['Updated_at'])->all();

            if ($changes === []) {
                return;
            }

            $oldValues = collect(array_keys($changes))
                ->mapWithKeys(fn (string $key) => [$key => $request->getOriginal($key)])
                ->all();

            $newStatus = $changes['Status'] ?? null;
            $action = match ($newStatus) {
                'Awaiting Payment' => 'payment_requested',
                'Approved' => 'request_approved',
                'Rejected' => 'request_rejected',
                'Cancelled' => 'request_cancelled',
                'Expired' => 'request_expired',
                'Ended' => 'event_ended',
                default => array_key_exists('Review_Requested_At', $changes)
                    ? 'revision_requested'
                    : 'request_updated',
            };

            $description = match ($action) {
                'payment_requested' => "Requested payment for request #{$request->RID}.",
                'request_approved' => "Approved request #{$request->RID}.",
                'request_rejected' => "Rejected request #{$request->RID}.",
                'request_cancelled' => "Cancelled request #{$request->RID}.",
                'request_expired' => "Marked request #{$request->RID} as expired.",
                'event_ended' => "Marked event for request #{$request->RID} as completed.",
                'revision_requested' => "Requested revisions for request #{$request->RID}.",
                default => "Updated request #{$request->RID}.",
            };

            AuditLog::recordRequest($request, $action, $description, $oldValues, $changes);

            if ($newStatus === 'Ended' && $request->getOriginal('Status') !== 'Ended') {
                $request->loadMissing(['user', 'facility']);

                if ($request->user && $request->Facility_ID) {
                    try {
                        $request->user->notify(new RequestFeedbackRequested($request));
                    } catch (Throwable $exception) {
                        Log::warning('Request was completed, but its feedback notification could not be delivered.', [
                            'request_id' => $request->RID,
                            'exception' => $exception,
                        ]);
                    }
                }
            }
        });

        static::deleted(fn (FacilityRequest $request) => AuditLog::recordRequest(
            $request,
            'request_archived',
            "Archived request #{$request->RID}.",
        ));

        static::restored(fn (FacilityRequest $request) => AuditLog::recordRequest(
            $request,
            'request_restored',
            "Restored request #{$request->RID}.",
        ));

        static::forceDeleted(function (FacilityRequest $request): void {
            $request->deleteStoredDocuments();

            AuditLog::recordRequest(
                $request,
                'request_deleted',
                "Permanently deleted request #{$request->RID}.",
            );
        });
    }

    private function deleteStoredDocuments(): void
    {
        $paths = collect([$this->attachment_path, $this->Payment_Proof_Path])
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique();

        foreach ($paths as $path) {
            try {
                if (! Storage::disk('local')->delete($path)) {
                    Log::warning('A request was permanently deleted, but one of its private documents could not be removed.', [
                        'request_id' => $this->RID,
                        'path' => $path,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('A request was permanently deleted, but removing one of its private documents failed.', [
                    'request_id' => $this->RID,
                    'path' => $path,
                    'exception' => $exception,
                ]);
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'Created_By')->withTrashed();
    }

    public function requesterName(): string
    {
        return $this->Is_Guest_Booking ? (string) $this->Guest_Name : ($this->user?->name ?? 'Unknown requester');
    }

    public function requesterEmail(): ?string
    {
        return $this->Is_Guest_Booking ? $this->Guest_Email : $this->user?->email;
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'Event_ID')->withTrashed();
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'Facility_ID', 'FID')->withTrashed();
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenity::class,
            'request_facility_amenities',
            'Request_ID',
            'Amenity_ID'
        )->withTrashed()->withPivot('quantity')->withTimestamps();
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
        return $this->hasOne(Feedback::class, 'Request_ID', 'RID')->withTrashed();
    }

    /**
     * Close and archive requests once their proposed end time passes.
     *
     * Requests that were never approved expire. Only approved reservations
     * are treated as completed events.
     */
    public static function markPastRequestsAsEnded(): int
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');

        $closedCount = 0;

        static::query()
            ->whereIn('Status', ['Pending', 'Awaiting Payment', 'Approved'])
            ->where(function (Builder $query) use ($today, $currentTime) {
                $query->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '<', $today)
                    ->orWhere(function (Builder $query) use ($today, $currentTime) {
                        $query->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), $today)
                            ->whereTime('Proposed_End_Time', '<=', $currentTime);
                    });
            })
            ->with(['user', 'facility'])
            ->select([
                'RID',
                'User_ID',
                'Facility_ID',
                'Is_Guest_Booking',
                'Guest_Email',
                'Proposed_Date',
                'Proposed_End_Date',
                'Proposed_Start_Time',
                'Proposed_End_Time',
                'Status',
            ])
            ->chunkById(100, function ($requests) use (&$closedCount): void {
                foreach ($requests as $request) {
                    $oldStatus = $request->Status;
                    $newStatus = $oldStatus === 'Approved' ? 'Ended' : 'Expired';
                    $action = $newStatus === 'Expired' ? 'request_expired' : 'event_ended';
                    $description = $newStatus === 'Expired'
                        ? "Automatically expired pending request #{$request->RID} after its event time passed."
                        : "Automatically marked event for request #{$request->RID} as completed.";

                    $request->updateQuietly(['Status' => $newStatus]);

                    AuditLog::recordRequest(
                        $request,
                        $action,
                        $description,
                        ['Status' => $oldStatus],
                        ['Status' => $newStatus],
                        useAuthenticatedActor: false,
                    );

                    $request->delete();
                    $closedCount++;

                    if ($newStatus === 'Expired') {
                        try {
                            if ($request->user) {
                                $request->user->notify(new RequestStatusUpdated($request, $oldStatus));
                            } elseif ($request->Is_Guest_Booking && $request->Guest_Email) {
                                Notification::route('mail', $request->Guest_Email)
                                    ->notify(new RequestStatusUpdated($request, $oldStatus));
                            }
                        } catch (Throwable $exception) {
                            Log::warning('Request was expired and archived, but its status notification could not be delivered.', [
                                'request_id' => $request->RID,
                                'exception' => $exception,
                            ]);
                        }
                    } elseif ($request->user && $request->Facility_ID) {
                        try {
                            $request->user->notify(new RequestFeedbackRequested($request));
                        } catch (Throwable $exception) {
                            Log::warning('Request was completed and archived, but its feedback notification could not be delivered.', [
                                'request_id' => $request->RID,
                                'exception' => $exception,
                            ]);
                        }
                    }
                }
            }, 'RID', 'RID');

        return $closedCount;
    }

    /**
     * Expire unpaid requests after their payment deadline. A proof uploaded on
     * time keeps the request open for administrator review.
     */
    public static function expireOverduePaymentRequests(): int
    {
        $expiredCount = 0;

        static::query()
            ->where('Status', 'Awaiting Payment')
            ->whereNull('Payment_Proof_Path')
            ->whereNotNull('Payment_Deadline')
            ->where('Payment_Deadline', '<=', now())
            ->with(['user', 'facility'])
            ->chunkById(100, function ($requests) use (&$expiredCount): void {
                foreach ($requests as $request) {
                    $oldStatus = $request->Status;

                    $request->schedules()->delete();
                    $request->updateQuietly(['Status' => 'Expired']);

                    AuditLog::recordRequest(
                        $request,
                        'request_expired',
                        "Automatically expired request #{$request->RID} after its payment deadline passed.",
                        ['Status' => $oldStatus],
                        ['Status' => 'Expired'],
                        useAuthenticatedActor: false,
                    );

                    $request->delete();
                    $expiredCount++;

                    try {
                        if ($request->user) {
                            $request->user->notify(new RequestStatusUpdated($request, $oldStatus));
                        } elseif ($request->Is_Guest_Booking && $request->Guest_Email) {
                            Notification::route('mail', $request->Guest_Email)
                                ->notify(new RequestStatusUpdated($request, $oldStatus));
                        }
                    } catch (Throwable $exception) {
                        Log::warning('Request was expired after its payment deadline, but its status notification could not be delivered.', [
                            'request_id' => $request->RID,
                            'exception' => $exception,
                        ]);
                    }
                }
            }, 'RID', 'RID');

        return $expiredCount;
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

    public function scheduledStartAt(): ?Carbon
    {
        $firstDate = $this->Proposed_Date?->toDateString();

        if (! $firstDate) {
            return null;
        }

        $schedule = $this->scheduleForDate($firstDate);

        return $schedule ? $this->scheduledDateTime($firstDate, $schedule['start']) : null;
    }

    public function scheduledEndAt(): ?Carbon
    {
        $lastDate = ($this->Proposed_End_Date ?? $this->Proposed_Date)?->toDateString();

        if (! $lastDate) {
            return null;
        }

        $schedule = $this->scheduleForDate($lastDate);

        return $schedule ? $this->scheduledDateTime($lastDate, $schedule['end']) : null;
    }

    private function scheduledDateTime(string $date, string $time): Carbon
    {
        [$hours, $minutes, $seconds] = array_pad(array_map('intval', explode(':', $time)), 3, 0);

        return Carbon::parse($date)->startOfDay()
            ->addHours($hours)
            ->addMinutes($minutes)
            ->addSeconds($seconds);
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
    public static function userReachedRequestLimitOnDate(
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

        $count = $lockForUpdate
            ? $query->select('RID')->get()->count()
            : $query->count();

        return $count >= self::MAX_REQUESTS_PER_EVENT_DATE;
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
            ->whereIn('Status', ['Awaiting Payment', 'Approved'])
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->when($lockForUpdate, fn (Builder $query) => $query->lockForUpdate())
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('Proposed_Start_Time', '<', $endTime)
                    ->where('Proposed_End_Time', '>', $startTime);
            });
    }
}
