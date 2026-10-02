<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalMovementPermit extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'permit_number',
        'departure_date',
        'movement_purpose',
        'origin_premises_id',
        'destination_premises_name',
        'destination_premises_id',
        'destination_address',
        'vehicle_plate_number',
        'driver_name',
        'driver_phone',
        'animal_ids_json',
        'total_heads',
        'veterinary_health_certificate_no',
        'status',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'animal_ids_json' => 'array',
        'total_heads' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
