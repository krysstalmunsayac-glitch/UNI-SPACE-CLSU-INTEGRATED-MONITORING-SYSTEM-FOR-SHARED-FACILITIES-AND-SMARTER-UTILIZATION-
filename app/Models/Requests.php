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

    public static function hasActiveFacilityConflict(
        int $facilityId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): bool {
        return static::activeFacilityConflicts($facilityId, $date, $startTime, $endTime, $ignoreRequestId)->exists();
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
