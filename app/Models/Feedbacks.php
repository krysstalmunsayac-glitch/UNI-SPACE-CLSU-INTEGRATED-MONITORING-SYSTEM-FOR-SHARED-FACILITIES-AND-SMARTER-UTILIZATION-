<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedbacks extends Model
{
    use SoftDeletes;

    protected $table = 'feedbacks';

    protected $primaryKey = 'FID';

    const CREATED_AT = 'Created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'User_ID',
        'Facility_ID',
        'Comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User_ID');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facilities::class, 'Facility_ID', 'FID');
    }
}
