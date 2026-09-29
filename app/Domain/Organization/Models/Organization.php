<?php

namespace App\Domain\Organization\Models;

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
        'contact_email',
        'contact_phone',
        'country',
        'currency',
        'timezone',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function farms(): HasMany
    {
        return $this->hasMany(Farm::class);
    }
}
