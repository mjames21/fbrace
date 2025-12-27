<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alliance extends Model
{
    protected $fillable = [
        'from_candidate_id',
        'to_candidate_id',
        'weight',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_active' => 'boolean',
    ];

    public function fromCandidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'from_candidate_id');
    }

    public function toCandidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'to_candidate_id');
    }
}
