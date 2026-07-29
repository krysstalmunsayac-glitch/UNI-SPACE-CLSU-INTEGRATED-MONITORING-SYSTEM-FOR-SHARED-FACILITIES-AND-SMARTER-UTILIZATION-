<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Amenities extends Model
{
    use SoftDeletes;

    protected $table = 'amenities';

    protected $primaryKey = 'AID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = 'Updated_at';

    protected $fillable = [
        'name',
        'Description',
        'Status',
        'reservation_limit',
    ];

    protected $casts = [
        'reservation_limit' => 'integer',
    ];

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Facilities::class,
            'facility_amenity',
            'Amenity_ID',
            'Facility_ID'
        )->withTimestamps();
    }

    public function overlappingReservationCount(
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): int {
        return Requests::query()
            ->whereDate('Proposed_Date', $date)
            ->whereIn('Status', ['Pending', 'Approved'])
            ->when($ignoreRequestId, fn ($query) => $query->where('RID', '!=', $ignoreRequestId))
            ->where('Proposed_Start_Time', '<', $endTime)
            ->where('Proposed_End_Time', '>', $startTime)
            ->whereHas('amenities', fn ($query) => $query->where('amenities.AID', $this->AID))
            ->count();
    }

    public function isFullyReserved(
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): bool {
        return $this->reservation_limit !== null
            && $this->overlappingReservationCount($date, $startTime, $endTime, $ignoreRequestId) >= $this->reservation_limit;
    }

    protected static function booted(): void
    {
        static::deleting(function (self $amenity) {
            DB::table('facility_amenity')
                ->where('Amenity_ID', $amenity->AID)
                ->delete();
        });
    }
}
