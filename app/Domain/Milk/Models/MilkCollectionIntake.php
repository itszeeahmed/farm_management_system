<?php

namespace App\Domain\Milk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkCollectionIntake extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_center_id',
        'farmer_supplier_id',
        'rate_chart_id',
        'intake_number',
        'collection_date',
        'shift',
        'species_type',
        'gross_volume_liters',
        'lactometer_reading',
        'fat_percentage',
        'snf_percentage',
        'calculated_price_per_liter',
        'gross_amount',
        'deductions_amount',
        'net_payable_amount',
        'payment_status',
        'paid_at',
        'paid_via_reference',
        'alcohol_test_result',
        'adulteration_starch',
        'adulteration_urea',
        'adulteration_detergent',
        'adulteration_formalin',
        'adulteration_hydrogen_peroxide',
        'added_water_percentage',
        'quality_accepted',
        'rejection_reason',
        'operator_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'gross_volume_liters' => 'decimal:2',
        'lactometer_reading' => 'decimal:2',
        'fat_percentage' => 'decimal:2',
        'snf_percentage' => 'decimal:2',
        'calculated_price_per_liter' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'deductions_amount' => 'decimal:2',
        'net_payable_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'adulteration_starch' => 'boolean',
        'adulteration_urea' => 'boolean',
        'adulteration_detergent' => 'boolean',
        'adulteration_formalin' => 'boolean',
        'adulteration_hydrogen_peroxide' => 'boolean',
        'added_water_percentage' => 'decimal:2',
        'quality_accepted' => 'boolean',
    ];

    public function collectionCenter(): BelongsTo
    {
        return $this->belongsTo(MilkCollectionCenter::class, 'collection_center_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(FarmerSupplier::class, 'farmer_supplier_id');
    }

    public function rateChart(): BelongsTo
    {
        return $this->belongsTo(MilkRateChart::class, 'rate_chart_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function isAdulterated(): bool
    {
        return $this->adulteration_starch
            || $this->adulteration_urea
            || $this->adulteration_detergent
            || $this->adulteration_formalin
            || $this->adulteration_hydrogen_peroxide
            || $this->alcohol_test_result === 'positive'
            || (float) $this->added_water_percentage > 5.0;
    }
}
