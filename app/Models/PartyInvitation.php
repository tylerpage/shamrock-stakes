<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyInvitation extends Model
{
    protected $fillable = ['party_id', 'email', 'invited_at'];

    protected $casts = [
        'invited_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
