<?php

namespace App\Models;

use App\Enums\MembershipApplicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'chapter_id',
    'membership_plan_id',
    'phone',
    'business_name',
    'industry',
    'designation',
    'business_address',
    'applicant_note',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_note',
])]
class MembershipApplication extends Model
{
    protected function casts(): array
    {
        return [
            'status' => MembershipApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function membership(): HasOne
    {
        return $this->hasOne(Membership::class);
    }
}
