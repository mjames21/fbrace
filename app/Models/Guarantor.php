<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guarantor extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'phone_primary',
        'phone_secondary',
        'district_id',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }
}
