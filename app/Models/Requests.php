<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'Proposed_Start_Time',
        'Proposed_End_Time',
        'Status',
        'Cancellation_Reason',
        'Rejection_Reason',
        'Review_Notes',
        'Review_Requested_At',
        'Purpose',
        'Capacity',
        'attachment_path',
    ];

    protected $casts = [
        'Proposed_Date' => 'date',
        'Proposed_Start_Time' => 'datetime:H:i',
        'Proposed_End_Time' => 'datetime:H:i',
        'Capacity' => 'integer',
        'Review_Requested_At' => 'datetime',
    ];

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
        return $this->belongsTo(User::class, 'User_ID');
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

    /**
     * Mark active requests as ended once their proposed end date and time pass.
     */
    public static function markPastRequestsAsEnded(): int
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');

        $requests = static::query()
            ->whereIn('Status', ['Pending', 'Approved'])
            ->where(function (Builder $query) use ($today, $currentTime) {
                $query->whereDate('Proposed_Date', '<', $today)
                    ->orWhere(function (Builder $query) use ($today, $currentTime) {
                        $query->whereDate('Proposed_Date', $today)
                            ->whereTime('Proposed_End_Time', '<=', $currentTime);
                    });
            })
            ->get();

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
        }

        return $requests->count();
    }

    public static function hasActiveFacilityConflict(
        int $facilityId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): bool {
        return static::activeFacilityConflicts($facilityId, $date, $startTime, $endTime, $ignoreRequestId)->exists();
    }

    /**
     * Determine whether a user already has a live reservation request for an event date.
     */
    public static function userHasRequestOnDate(
        int $userId,
        string $date,
        ?int $ignoreRequestId = null,
    ): bool {
        return static::query()
            ->where('User_ID', $userId)
            ->whereDate('Proposed_Date', $date)
            ->whereNotIn('Status', ['Cancelled', 'Rejected'])
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->exists();
    }

    public static function activeFacilityConflicts(
        int $facilityId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): Builder {
        return static::query()
            ->where('Facility_ID', $facilityId)
            ->whereDate('Proposed_Date', $date)
            ->whereIn('Status', ['Pending', 'Approved'])
            ->when($ignoreRequestId, fn (Builder $query) => $query->where('RID', '!=', $ignoreRequestId))
            ->where(function (Builder $query) use ($startTime, $endTime) {
                $query->where('Proposed_Start_Time', '<', $endTime)
                    ->where('Proposed_End_Time', '>', $startTime);
            });
    }
}
