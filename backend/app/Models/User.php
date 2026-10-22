<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_set_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    public function membershipApplications(): HasMany
    {
        return $this->hasMany(MembershipApplication::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function administeredChapters(): BelongsToMany
    {
        return $this->belongsToMany(
            Chapter::class,
            'chapter_admin_assignments',
            'user_id',
            'chapter_id',
        )->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isSubAdmin(): bool
    {
        return $this->role === UserRole::SubAdmin;
    }

    public function canAccessChapter(Chapter $chapter): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isSubAdmin()
            && $this->administeredChapters()->whereKey($chapter->getKey())->exists();
    }
}
