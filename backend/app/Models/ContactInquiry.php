<?php

namespace App\Models;

use App\Enums\ContactInquiryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'name',
    'email',
    'phone',
    'subject',
    'message',
    'status',
    'resolved_by',
    'resolved_at',
])]
class ContactInquiry extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ContactInquiryStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
