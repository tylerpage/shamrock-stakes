<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ResolutionProposalPhoto extends Model
{
    protected $fillable = ['resolution_proposal_id', 'path'];

    public function resolutionProposal(): BelongsTo
    {
        return $this->belongsTo(ResolutionProposal::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
