<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Animals\Models\SlaughterRecord;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalalSlaughterCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'slaughter_record_id',
        'certificate_number',
        'certification_body',
        'slaughterer_name',
        'slaughterer_credential_id',
        'slaughter_method',
        'tasmiyah_recited',
        'trachea_esophagus_jugular_cut_verified',
        'inspector_name',
        'verified_at',
    ];

    protected $casts = [
        'tasmiyah_recited' => 'boolean',
        'trachea_esophagus_jugular_cut_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function slaughterRecord(): BelongsTo
    {
        return $this->belongsTo(SlaughterRecord::class);
    }
}
