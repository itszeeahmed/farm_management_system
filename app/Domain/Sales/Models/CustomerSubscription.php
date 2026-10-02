<?php

namespace App\Domain\Sales\Models;

use App\Domain\Organization\Models\Farm;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'farm_id',
        'product_type',
        'daily_quantity_liters',
        'unit_price_per_liter',
        'frequency',
        'start_date',
        'end_date',
        'is_paused',
        'pause_start_date',
        'pause_end_date',
        'status',
    ];

    protected $casts = [
        'daily_quantity_liters' => 'decimal:2',
        'unit_price_per_liter' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_paused' => 'boolean',
        'pause_start_date' => 'date',
        'pause_end_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function deliveryStops(): HasMany
    {
        return $this->hasMany(DeliveryRunStop::class);
    }

    /**
     * Determine if subscription is active for a given delivery date.
     */
    public function isDueOn(Carbon $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->is_paused) {
            if ($this->pause_start_date && $this->pause_end_date) {
                if ($date->betweenIncluded($this->pause_start_date, $this->pause_end_date)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if ($date->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return match ($this->frequency) {
            'daily' => true,
            'alternate_days' => ($date->dayOfYear % 2 === 0),
            'weekdays_only' => $date->isWeekday(),
            'weekends_only' => $date->isWeekend(),
            'weekly' => ($date->dayOfWeekIso === 1), // Monday
            default => true,
        };
    }
}
