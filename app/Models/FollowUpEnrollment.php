<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpEnrollment extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'next_scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function sequence(): BelongsTo { return $this->belongsTo(FollowUpSequence::class, 'follow_up_sequence_id'); }
}
