<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'office_id',
        'customer_email',
        'plan_type',
        'amount',
        'status',
        'payment_provider',
        'paid_at',
        'payment_metadata',
    ];

    protected $casts = [
        'paid_at'          => 'datetime',
        'payment_metadata' => 'array',
        'amount'           => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}