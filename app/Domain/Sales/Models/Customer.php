<?php

namespace App\Domain\Sales\Models;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'farm_id',
        'customer_type',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'latitude',
        'longitude',
        'wallet_balance',
        'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'wallet_balance' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(CustomerWalletTransaction::class);
    }

    public function deliveryStops(): HasMany
    {
        return $this->hasMany(DeliveryRunStop::class);
    }
}
