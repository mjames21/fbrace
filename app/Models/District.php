<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['region_id', 'name', 'slug'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }
}