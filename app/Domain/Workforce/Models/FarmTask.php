<?php

namespace App\Domain\Workforce\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'title',
        'description',
        'category',
        'priority',
        'due_date',
        'due_time',
        'animal_id',
        'assigned_to',
        'status',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
