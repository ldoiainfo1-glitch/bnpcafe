<?php

namespace App\Models;

use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'pincode',
    'name',
    'state',
    'chapter_head_name',
    'chapter_head_phone',
    'chapter_head_email',
    'postal_address',
    'about',
    'is_active',
])]
class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'chapter_admin_assignments',
            'chapter_id',
            'user_id',
        )->withTimestamps();
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if (! $user->isSubAdmin()) {
            $query->whereKey([]);

            return;
        }

        $query->whereHas(
            'adminUsers',
            fn (Builder $adminUsers) => $adminUsers->whereKey($user->getKey()),
        );
    }
}
