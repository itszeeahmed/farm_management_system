<?php

namespace App\Domain\Organization\Models;

use App\Domain\Milk\Models\FarmerSupplier;
use App\Domain\Milk\Models\MilkCollectionCenter;
use App\Domain\Milk\Models\MilkRateChart;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'tier',
        'status',
        'contact_email',
        'contact_phone',
        'country',
        'currency',
        'timezone',
        'settings',
        'operating_profile',
    ];

    protected $casts = [
        'settings' => 'array',
        'operating_profile' => 'array',
    ];

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function collectionCenters(): HasMany
    {
        return $this->hasMany(MilkCollectionCenter::class);
    }

    public function farmerSuppliers(): HasMany
    {
        return $this->hasMany(FarmerSupplier::class);
    }

    public function rateCharts(): HasMany
    {
        return $this->hasMany(MilkRateChart::class);
    }
}
