<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactGroup extends Model
{
    protected $guarded = ['id'];
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function contacts(): BelongsToMany { return $this->belongsToMany(Contact::class, 'contact_group_contact'); }
}
