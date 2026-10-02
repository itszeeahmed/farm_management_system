<?php

namespace App\Domain\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRunStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_run_id',
        'customer_id',
        'customer_subscription_id',
        'stop_sequence',
        'planned_quantity_liters',
        'delivered_quantity_liters',
        'unit_price',
        'total_amount',
        'empty_bottles_returned',
        'proof_of_delivery_type',
        'proof_of_delivery_token',
        'status',
        'notes',
    ];

    protected $casts = [
        'stop_sequence' => 'integer',
        'planned_quantity_liters' => 'decimal:2',
        'delivered_quantity_liters' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'empty_bottles_returned' => 'integer',
    ];

    public function deliveryRun(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerSubscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class);
    }
}
