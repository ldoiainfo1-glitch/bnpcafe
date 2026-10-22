<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'phone',
    'business_name',
    'industry',
    'designation',
    'business_address',
    'bio',
    'website',
    'directory_email',
    'directory_phone',
    'show_in_directory',
])]
class MemberProfile extends Model
{
    protected function casts(): array
    {
        return ['show_in_directory' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
