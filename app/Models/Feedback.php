<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use SoftDeletes;

    protected $table = 'feedbacks';

    protected $primaryKey = 'FID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'User_ID',
        'Request_ID',
        'Facility_ID',
        'Rating',
        'Reservation_Frequency',
        'Purpose_Importance',
        'Requirements_Met',
        'Reserve_Again',
        'Comment',
    ];

    protected $casts = [
        'Rating' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'Facility_ID', 'FID')->withTrashed();
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(FacilityRequest::class, 'Request_ID', 'RID')->withTrashed();
    }
}
