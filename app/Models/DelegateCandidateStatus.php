<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DelegateCandidateStatus extends Model
{
    protected $table = 'delegate_candidate_status';

    public const STANCE_FOR = 'for';
    public const STANCE_INDICATIVE = 'indicative';
    public const STANCE_AGAINST = 'against';

    public const STANCES = [
        self::STANCE_FOR,
        self::STANCE_INDICATIVE,
        self::STANCE_AGAINST,
    ];

    protected $fillable = [
        'delegate_id',
        'candidate_id',
        'stance',
        'confidence',
        'last_confirmed_at',
        'last_assessment_id',

        // approvals workflow (safe even if you don’t use it yet)
        'pending_stance',
        'pending_confidence',
        'pending_reason',
        'pending_by_user_id',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'pending_confidence' => 'integer',
        'last_confirmed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Delegate::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function lastAssessment(): BelongsTo
    {
        return $this->belongsTo(SupportAssessment::class, 'last_assessment_id');
    }

    public function pendingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pending_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}