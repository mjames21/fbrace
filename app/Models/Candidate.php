<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = ['name', 'slug', 'discipline_score', 'is_active'];

    protected $casts = [
        'discipline_score' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function assessments(): HasMany
    {
        return $this->hasMany(SupportAssessment::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(DelegateCandidateStatus::class);
    }
    public function alliancePolicy()
{
    return $this->hasOne(\App\Models\CandidateAlliancePolicy::class);
}

}