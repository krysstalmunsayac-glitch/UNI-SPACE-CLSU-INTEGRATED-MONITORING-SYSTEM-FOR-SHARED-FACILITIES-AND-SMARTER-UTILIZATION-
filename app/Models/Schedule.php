<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;

    protected $table = 'schedules';

    protected $primaryKey = 'SID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'Request_ID',
        'Date',
        'Start_Time',
        'End_Time',
        'Status',
    ];

    protected $casts = [
        'Date' => 'date',
        'Start_Time' => 'datetime:H:i',
        'End_Time' => 'datetime:H:i',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Requests::class, 'Request_ID');
    }
}
