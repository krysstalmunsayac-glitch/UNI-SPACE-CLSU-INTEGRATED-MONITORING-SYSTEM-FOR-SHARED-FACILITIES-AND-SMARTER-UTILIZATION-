<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'email',
        'registration_data',
        'pin_hash',
        'pin_expires_at',
        'resend_available_at',
        'failed_attempts',
    ];

    protected $hidden = [
        'registration_data',
        'pin_hash',
    ];

    protected function casts(): array
    {
        return [
            'registration_data' => 'encrypted:array',
            'pin_expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'failed_attempts' => 'integer',
        ];
    }
}
