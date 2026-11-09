<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'shipping_cost',
        'total_amount',
        'shipping_address',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address' => 'array',
    ];

    // Customer who placed the order
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Historical items purchased under this order
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}