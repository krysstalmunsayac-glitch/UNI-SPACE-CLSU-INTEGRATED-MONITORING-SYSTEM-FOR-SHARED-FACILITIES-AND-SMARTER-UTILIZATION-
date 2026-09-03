<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facilities extends Model
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
        'Rate_Details',
        'Office',
        'Description',
        'Protocols',
        'Contact_Details',
        'Reference_URL',
        'Data_Notes',
        'Location',
        'Latitude',
        'Longitude',
        'Capacity',
        'Status',
    ];

    protected $casts = [
        'Price' => 'float',
        'Capacity' => 'integer',
        'Latitude' => 'float',
        'Longitude' => 'float',
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

        return asset('images/CLSU_logo.png');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenities::class,
            'facility_amenity',
            'Facility_ID',
            'Amenity_ID'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::deleting(function (Facilities $facility): void {
            $facility->archiveRelatedRequests();
            $facility->images()->delete();
        });
    }

    /**
     * Archive requests made against this facility.
     */
    public function archiveRelatedRequests(): void
    {
        Requests::query()
            ->where('Facility_ID', $this->FID)
            ->delete();
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
