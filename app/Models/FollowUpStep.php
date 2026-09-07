<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpStep extends Model
{
    protected $guarded = ['id'];
    public function sequence(): BelongsTo { return $this->belongsTo(FollowUpSequence::class, 'follow_up_sequence_id'); }
}
