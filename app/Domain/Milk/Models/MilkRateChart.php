<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilkRateChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'species_type',
        'base_price_per_liter',
        'standard_fat_percentage',
        'standard_snf_percentage',
        'fat_rate_per_unit',
        'snf_rate_per_unit',
        'min_fat_acceptance',
        'min_snf_acceptance',
        'premium_incentive_percent',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'base_price_per_liter' => 'decimal:2',
        'standard_fat_percentage' => 'decimal:2',
        'standard_snf_percentage' => 'decimal:2',
        'fat_rate_per_unit' => 'decimal:2',
        'snf_rate_per_unit' => 'decimal:2',
        'min_fat_acceptance' => 'decimal:2',
        'min_snf_acceptance' => 'decimal:2',
        'premium_incentive_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function intakes(): HasMany
    {
        return $this->hasMany(MilkCollectionIntake::class, 'rate_chart_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function (Builder $q) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now()->toDateString());
            });
    }
}
