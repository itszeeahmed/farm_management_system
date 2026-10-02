<?php

namespace App\Domain\Health\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiosecurityAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'audit_date',
        'auditor_name',
        'visitor_log_compliance_score',
        'footbath_disinfection_score',
        'quarantine_compliance_score',
        'carcass_disposal_compliance_score',
        'overall_risk_rating',
        'corrective_actions',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'visitor_log_compliance_score' => 'integer',
        'footbath_disinfection_score' => 'integer',
        'quarantine_compliance_score' => 'integer',
        'carcass_disposal_compliance_score' => 'integer',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function getAverageCompliancePercentAttribute(): float
    {
        $sum = $this->visitor_log_compliance_score
            + $this->footbath_disinfection_score
            + $this->quarantine_compliance_score
            + $this->carcass_disposal_compliance_score;

        return round($sum / 4.0, 1);
    }
}
