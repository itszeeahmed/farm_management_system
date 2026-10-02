<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'bulk_tank_id',
        'dispatch_number',
        'buyer_name',
        'driver_name',
        'driver_phone',
        'tanker_plate_number',
        'seal_number',
        'dispatched_volume_liters',
        'temperature_c',
        'composite_fat_percentage',
        'composite_snf_percentage',
        'composite_scc',
        'unit_price_pkr',
        'total_price_pkr',
        'dispatched_at',
        'authorized_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'dispatched_volume_liters' => 'decimal:2',
        'temperature_c' => 'decimal:1',
        'composite_fat_percentage' => 'decimal:2',
        'composite_snf_percentage' => 'decimal:2',
        'composite_scc' => 'integer',
        'unit_price_pkr' => 'decimal:2',
        'total_price_pkr' => 'decimal:2',
        'dispatched_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function bulkTank(): BelongsTo
    {
        return $this->belongsTo(BulkTank::class);
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
