<?php

namespace App\Domain\Finance\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostAllocationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'cost_category',
        'allocation_basis',
        'percentage_dairy_cattle',
        'percentage_goats',
        'percentage_feedlot',
    ];

    protected $casts = [
        'percentage_dairy_cattle' => 'decimal:2',
        'percentage_goats' => 'decimal:2',
        'percentage_feedlot' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
