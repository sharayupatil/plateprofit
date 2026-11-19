<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id',
        'customer_id',
        'pos_import_id',
        'source',
        'external_order_id',
        'ordered_at',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function posImport(): BelongsTo
    {
        return $this->belongsTo(PosImport::class);
    }
}
