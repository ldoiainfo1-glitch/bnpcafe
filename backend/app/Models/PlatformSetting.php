<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'key',
    'value',
    'is_public',
    'updated_by',
])]
class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
