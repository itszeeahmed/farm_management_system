<?php

namespace App\Domain\Health\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'active_ingredient',
        'category',
        'is_antimicrobial',
        'default_dosage',
        'dosage_unit',
        'route_of_administration',
        'milk_withdrawal_days',
        'meat_withdrawal_days',
        'unit_cost',
        'current_stock',
        'stock_unit',
    ];

    protected $casts = [
        'is_antimicrobial' => 'boolean',
        'milk_withdrawal_days' => 'integer',
        'meat_withdrawal_days' => 'integer',
        'unit_cost' => 'decimal:2',
        'current_stock' => 'decimal:2',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
