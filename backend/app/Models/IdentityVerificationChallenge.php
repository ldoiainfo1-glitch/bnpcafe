<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'email',
    'name',
    'phone',
    'code_hash',
    'expires_at',
    'attempts',
    'consumed_at',
])]
class IdentityVerificationChallenge extends Model
{
    use MassPrunable;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('consumed_at')
                    ->orWhere('expires_at', '<=', now());
            });
    }
}
