<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_registration_id',
    'user_id',
    'rating',
    'comment',
    'submitted_at',
])]
class EventFeedback extends Model
{
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
