<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastCampaign extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function channel(): BelongsTo { return $this->belongsTo(Channel::class); }
    public function template(): BelongsTo { return $this->belongsTo(BroadcastTemplate::class, 'broadcast_template_id'); }

    public function contactGroup(): BelongsTo { return $this->belongsTo(ContactGroup::class); }
    public function logs(): HasMany { return $this->hasMany(BroadcastLog::class); }
}
