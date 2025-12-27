<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAssessment extends Model
{
    protected $fillable = [
        'delegate_id',
        'candidate_id',
        'interaction_id',
        'stance',
        'confidence',
        'notes',
        'status',
        'approval_steps_required',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'second_approved_by',
        'second_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'second_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Delegate::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function secondApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}