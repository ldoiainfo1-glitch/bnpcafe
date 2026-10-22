<?php

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'requester_id',
    'chapter_id',
    'assigned_to',
    'escalated_by',
    'subject',
    'category',
    'priority',
    'status',
    'resolved_at',
])]
class SupportTicket extends Model
{
    protected function casts(): array
    {
        return [
            'priority' => SupportTicketPriority::class,
            'status' => SupportTicketStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function escalator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
