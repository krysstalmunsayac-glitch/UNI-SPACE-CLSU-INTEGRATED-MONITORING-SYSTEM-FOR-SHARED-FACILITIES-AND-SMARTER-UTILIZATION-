<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    public const PH_CONTACT_REGEX = '/^(?:09\d{9}|\+639\d{9})$/';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'invitation_sent_at',
        'invitation_expires_at',
        'invitation_revoked_at',
        'contact_number',
        'office',
        'address',
        'ImageID',
        'user_type',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'invitation_sent_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'invitation_revoked_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function invitationStatus(): string
    {
        if ($this->email_verified_at) {
            return 'Verified';
        }

        if ($this->invitation_revoked_at || ! $this->invitation_expires_at || $this->invitation_expires_at->isPast()) {
            return 'Invitation Expired';
        }

        return 'Invitation Pending';
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->ImageID)) {
            return null;
        }

        if (filter_var($this->ImageID, FILTER_VALIDATE_URL)) {
            return $this->ImageID;
        }

        return asset('storage/'.ltrim($this->ImageID, '/'));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_image_url;
    }

    /**
     * Requests submitted by this user.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Requests::class, 'User_ID');
    }

    /**
     * Feedback submitted by this user.
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedbacks::class, 'User_ID');
    }

    public function createdAmenities(): HasMany
    {
        return $this->hasMany(Amenities::class, 'created_by');
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facilities::class, 'facility_user', 'user_id', 'facility_id')
            ->withTimestamps();
    }

    public function assignedFacilities(): BelongsToMany
    {
        return $this->facilities();
    }

    public function assignedFacilityIds(): array
    {
        return $this->facilities()->pluck('facilities.FID')->map(fn ($id) => (int) $id)->all();
    }

    public function syncFacilities(array $facilityIds): void
    {
        $this->facilities()->sync($facilityIds);
    }

    /**
     * Role helpers, used by the role middleware and throughout the UI.
     */
    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isSuperAdminOrAdmin(): bool
    {
        return in_array($this->user_type, ['super_admin', 'admin'], true);
    }

    public function roleLabel(): string
    {
        return match ($this->user_type) {
            'super_admin' => 'Superadmin',
            'admin' => 'Office Admin',
            default => 'End User',
        };
    }

    public function hasrole(string $role): bool
    {
        return $this->user_type === $role;
    }
}
