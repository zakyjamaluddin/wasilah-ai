<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'raw_payload' => 'array',
        'is_read' => 'boolean',
    ];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
