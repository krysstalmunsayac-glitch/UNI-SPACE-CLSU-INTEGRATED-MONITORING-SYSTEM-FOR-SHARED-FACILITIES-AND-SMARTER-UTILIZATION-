<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class Amenities extends Model
{
    use SoftDeletes;

    protected $table = 'amenities';

    protected $primaryKey = 'AID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = 'Updated_at';

    protected $fillable = [
        'created_by',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(
            Requests::class,
            'request_facility_amenities',
            'Amenity_ID',
            'Request_ID',
            'AID',
            'RID',
        )->withTimestamps();
    }

    public function overlappingReservationCount(
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): int {
        return Requests::query()
            ->whereDate('Proposed_Date', '<=', $endDate)
            ->whereDate(DB::raw('COALESCE(Proposed_End_Date, Proposed_Date)'), '>=', $startDate)
            ->whereIn('Status', ['Pending', 'Approved'])
            ->when($ignoreRequestId, fn ($query) => $query->where('RID', '!=', $ignoreRequestId))
            ->where('Proposed_Start_Time', '<', $endTime)
            ->where('Proposed_End_Time', '>', $startTime)
            ->whereHas('amenities', fn ($query) => $query->where('amenities.AID', $this->AID))
            ->count();
    }

    public function isFullyReserved(
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        ?int $ignoreRequestId = null,
    ): bool {
        return $this->reservation_limit !== null
            && $this->overlappingReservationCount($startDate, $endDate, $startTime, $endTime, $ignoreRequestId) >= $this->reservation_limit;
    }

    protected static function booted(): void
    {
        static::creating(function (self $amenity): void {
            if ($amenity->created_by === null && auth()->user()?->isSuperAdminOrAdmin()) {
                $amenity->created_by = auth()->id();
            }

            if ($amenity->created_by !== null) {
                $validCreator = User::query()
                    ->whereKey($amenity->created_by)
                    ->whereIn('user_type', ['super_admin', 'admin'])
                    ->exists();

                if (! $validCreator) {
                    throw new AuthorizationException('Only a Super Admin or Office Admin can create amenities.');
                }
            }
        });

        static::deleting(function (self $amenity) {
            if (! $amenity->isForceDeleting()) {
                return;
            }

            DB::table('facility_amenity')
                ->where('Amenity_ID', $amenity->AID)
                ->delete();
        });
    }
}
