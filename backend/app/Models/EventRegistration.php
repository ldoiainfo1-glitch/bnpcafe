<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'event_id',
    'user_id',
    'name',
    'email',
    'phone',
    'business_name',
    'status',
    'attended_at',
])]
class EventRegistration extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EventRegistrationStatus::class,
            'attended_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(EventFeedback::class);
    }
}
