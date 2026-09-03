<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityBlackout extends Model
{
    protected $fillable = ['facility_id', 'starts_on', 'ends_on', 'reason'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];
}
