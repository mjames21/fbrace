<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Category;

class Delegate extends Model
{
    protected $fillable = [
        'full_name',
        'category',
        'district_id',
        'constituency',
        'phone',
        'email',
        'is_high_value',
        'is_active',
        'phone_primary',
        'phone_secondary',
        'guarantor_id',
    ];

    protected $casts = [
        'is_high_value' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function region(): ?Region
    {
        return $this->district?->region;
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'delegate_group');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'delegate_tag');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(SupportAssessment::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(DelegateCandidateStatus::class);
    }
    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }
    public function categoryModel(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function candidateStatuses(): HasMany
{
    return $this->hasMany(\App\Models\DelegateCandidateStatus::class);
}
}