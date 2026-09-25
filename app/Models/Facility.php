<?php

namespace App\Models;

use App\Notifications\RequestStatusUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class Facility extends Model
{
    use SoftDeletes;

    protected $table = 'facilities';

    protected $primaryKey = 'FID';

    public function getRouteKeyName(): string
    {
        return 'FID';
    }

    protected $fillable = [
        'Facility_Name',
        'facility_type',
        'Access_Type',
        'Image_URL',
        'Price',
        'rates',
        'Rate_Details',
        'Office',
        'Description',
        'Protocols',
        'protocols_and_guidelines',
        'Contact_Details',
        'Reference_URL',
        'Data_Notes',
        'Location',
        'Latitude',
        'Longitude',
        'Capacity',
        'Status',
        'Available_At',
        'Deactivated_At',
    ];

    protected $casts = [
        'Price' => 'float',
        'Capacity' => 'integer',
        'Latitude' => 'float',
        'Longitude' => 'float',
        'Available_At' => 'datetime',
        'Deactivated_At' => 'datetime',
    ];

    public function primaryImageUrl(): string
    {
        $imagePath = $this->images->first()?->image_path;

        if ($imagePath) {
            return asset('storage/'.ltrim($imagePath, '/'));
        }

        if ($this->Image_URL) {
            return str_starts_with($this->Image_URL, 'http://') || str_starts_with($this->Image_URL, 'https://')
                ? $this->Image_URL
                : asset(ltrim($this->Image_URL, '/'));
        }

        return asset('images/silesyu-space-logo-v2.png');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenity::class,
            'facility_amenity',
            'Facility_ID',
            'Amenity_ID'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (Facility $facility): void {
            if (blank($facility->slug)) {
                $facility->slug = static::uniqueSlug(
                    $facility->Facility_Name,
                    $facility->exists ? (int) $facility->FID : null,
                );
            }
        });

        static::deleting(function (Facility $facility): void {
            $facility->archiveRelatedRequests();
            if ($facility->isForceDeleting()) {
                $facility->images()->delete();
            }
        });
    }

    private static function uniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $name) ?: 'facility';
        $slug = $base;
        $suffix = 2;

        while (static::withTrashed()
            ->when($ignoreId, fn (Builder $query) => $query->where('FID', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Keep approved reservations active when their facility is archived, while
     * rejecting requests that are still waiting for a decision or payment.
     */
    public function archiveRelatedRequests(): void
    {
        FacilityRequest::query()
            ->where('Facility_ID', $this->FID)
            ->whereIn('Status', ['Pending', 'Awaiting Payment'])
            ->with('user')
            ->get()
            ->each(function (FacilityRequest $request): void {
                $previousStatus = $request->Status;

                $request->schedules()->delete();
                $request->update([
                    'Status' => 'Rejected',
                    'Rejection_Reason' => 'The requested facility was archived before this request could be approved.',
                    'Review_Notes' => null,
                    'Review_Requested_At' => null,
                ]);

                try {
                    if ($request->user) {
                        $request->user->notify(new RequestStatusUpdated($request, $previousStatus));
                    } elseif ($request->Is_Guest_Booking && $request->Guest_Email) {
                        Notification::route('mail', $request->Guest_Email)
                            ->notify(new RequestStatusUpdated($request, $previousStatus));
                    }
                } catch (Throwable $exception) {
                    Log::warning('Request was rejected after its facility was archived, but the notification could not be delivered.', [
                        'request_id' => $request->RID,
                        'exception' => $exception,
                    ]);
                }
            });
    }

    public function scopeForOffice($query, ?string $office)
    {
        if (empty($office)) {
            return $query;
        }

        return $query->where('Office', $office);
    }

    public function images()
    {
        return $this->hasMany(
            FacilityImage::class,
            'facility_id',
            'FID'
        );
    }

    public function assignedAdmins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'facility_user', 'facility_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeAssignedToAdmin(Builder $query, ?User $admin): Builder
    {
        if (! $admin?->isAdmin()) {
            return $query;
        }

        return $query->whereHas('assignedAdmins', fn ($adminQuery) => $adminQuery->where('users.id', $admin->id)
        );
    }
}
