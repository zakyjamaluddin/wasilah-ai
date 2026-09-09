<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
