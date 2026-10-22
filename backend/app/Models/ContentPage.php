<?php

namespace App\Models;

use App\Enums\ContentPageStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slug',
    'title',
    'summary',
    'body',
    'status',
    'updated_by',
])]
class ContentPage extends Model
{
    protected function casts(): array
    {
        return ['status' => ContentPageStatus::class];
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
