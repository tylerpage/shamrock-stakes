<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyUser extends Model
{
    protected $table = 'party_user';

    protected $fillable = ['party_id', 'user_id', 'balance', 'invited_at', 'joined_at'];

    protected $casts = [
        'balance' => 'decimal:2',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
