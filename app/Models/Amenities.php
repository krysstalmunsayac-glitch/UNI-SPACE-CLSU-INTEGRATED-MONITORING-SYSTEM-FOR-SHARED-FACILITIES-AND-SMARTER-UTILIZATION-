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

    protected static function booted(): void
    {
        static::deleting(function (self $amenity) {
            DB::table('facility_amenity')
                ->where('Amenity_ID', $amenity->AID)
                ->delete();
        });
    }
}
