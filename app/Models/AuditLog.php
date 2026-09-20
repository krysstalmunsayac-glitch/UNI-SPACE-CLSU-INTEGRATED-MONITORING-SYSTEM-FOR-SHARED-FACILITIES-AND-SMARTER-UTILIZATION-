<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function requestRecord(): BelongsTo
    {
        return $this->belongsTo(FacilityRequest::class, 'auditable_id', 'RID')->withTrashed();
    }

    public static function recordRequest(
        FacilityRequest $requestRecord,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $actorId = null,
        bool $useAuthenticatedActor = true,
    ): self {
        return static::query()->create([
            'actor_id' => $useAuthenticatedActor ? auth()->id() : $actorId,
            'action' => $action,
            'auditable_type' => FacilityRequest::class,
            'auditable_id' => $requestRecord->RID,
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]);
    }
}
