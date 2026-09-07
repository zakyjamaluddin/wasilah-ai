<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function groups(): BelongsToMany { return $this->belongsToMany(ContactGroup::class, 'contact_group_contact'); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
}
