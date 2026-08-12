<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestFacilityAmenity extends Model
{
    protected $table = 'request_facility_amenities';

    protected $primaryKey = 'RFAID';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'Request_ID',
        'Amenity_ID',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Requests::class, 'Request_ID', 'RID');
    }

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenities::class, 'Amenity_ID', 'AID');
    }
}
