<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'is_bot_active' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function channel(): BelongsTo { return $this->belongsTo(Channel::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
