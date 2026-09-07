<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastTemplate extends Model
{
    protected $guarded = ['id'];
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
}
