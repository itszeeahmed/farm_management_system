<?php

namespace App\Domain\Compliance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompliancePack extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'country_code',
        'regulatory_body',
        'version',
        'is_enabled',
        'configuration_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'configuration_json' => 'array',
    ];
}
