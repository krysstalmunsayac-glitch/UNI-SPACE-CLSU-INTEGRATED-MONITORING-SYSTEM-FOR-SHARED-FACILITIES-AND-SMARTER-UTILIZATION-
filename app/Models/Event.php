<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $table = 'events';

    protected $primaryKey = 'EID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'Event_Title',
        'Description',
        'Type_Event',
        'Event_Scope',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(FacilityRequest::class, 'Event_ID');
    }
}
