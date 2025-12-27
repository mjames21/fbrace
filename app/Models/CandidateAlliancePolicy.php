<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAlliancePolicy extends Model
{
    protected $fillable = [
        'candidate_id',
        'mode',
        'max_total_weight_percent',
    ];

    protected $casts = [
        'candidate_id' => 'integer',
        'max_total_weight_percent' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
