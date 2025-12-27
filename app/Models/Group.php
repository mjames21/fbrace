<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'code', 'slug'];

    public function delegates(): BelongsToMany
    {
        return $this->belongsToMany(Delegate::class, 'delegate_group');
    }
}