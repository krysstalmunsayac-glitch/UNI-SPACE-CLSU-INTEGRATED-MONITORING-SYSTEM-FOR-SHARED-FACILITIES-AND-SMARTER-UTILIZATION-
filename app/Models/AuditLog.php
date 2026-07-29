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
        'ip_address',
        'user_agent',
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
        return $this->belongsTo(Requests::class, 'auditable_id', 'RID')->withTrashed();
    }

    public static function recordRequest(
        Requests $requestRecord,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $actorId = null,
        bool $useAuthenticatedActor = true,
    ): self {
        $httpRequest = app()->runningInConsole() ? null : request();

        return static::query()->create([
            'actor_id' => $useAuthenticatedActor ? auth()->id() : $actorId,
            'action' => $action,
            'auditable_type' => Requests::class,
            'auditable_id' => $requestRecord->RID,
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $httpRequest?->ip(),
            'user_agent' => $httpRequest ? mb_substr((string) $httpRequest->userAgent(), 0, 500) : null,
        ]);
    }
}
