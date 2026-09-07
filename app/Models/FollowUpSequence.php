<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowUpSequence extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'stop_on_reply' => 'boolean',
        'stop_on_closing' => 'boolean',
        'only_work_hours' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function channel(): BelongsTo { return $this->belongsTo(Channel::class); }
    public function steps(): HasMany { return $this->hasMany(FollowUpStep::class)->orderBy('step_order', 'asc'); }
    public function enrollments(): HasMany { return $this->hasMany(FollowUpEnrollment::class); }
}
