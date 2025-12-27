<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    protected $fillable = [
        'delegate_id',
        'user_id',
        'type',
        'outcome',
        'notes',
        'next_step_at',
    ];

    protected $casts = [
        'next_step_at' => 'datetime',
    ];

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Delegate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}