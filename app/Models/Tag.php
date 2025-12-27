<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function delegates(): BelongsToMany
    {
        return $this->belongsToMany(Delegate::class, 'delegate_tag');
    }
}