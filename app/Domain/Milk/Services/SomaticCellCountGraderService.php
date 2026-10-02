<?php

namespace App\Domain\Milk\Services;

class SomaticCellCountGraderService
{
    /**
     * Grade milk quality tier based on Somatic Cell Count (cells/ml).
     *
     * Standards:
     * - Premium: < 200,000 cells/ml (healthy udder, maximum cheese/curd yield, premium bonus)
     * - Standard: 200,000 - 399,999 cells/ml (acceptable commercial table milk)
     * - Substandard: 400,000 - 749,999 cells/ml (penalty tier, subclinical irritation)
     * - Rejected / Discarded: >= 750,000 cells/ml (clinical/subclinical mastitis, unpasteurized hazard)
     */
    public function gradeByScc(?int $scc): string
    {
        if ($scc === null) {
            return 'standard';
        }

        if ($scc < 200000) {
            return 'premium';
        }

        if ($scc < 400000) {
            return 'standard';
        }

        if ($scc < 750000) {
            return 'substandard';
        }

        return 'discarded_mastitis';
    }

    /**
     * Evaluate subclinical mastitis hazard from quarter electrical conductivity (EC).
     * Normal cow milk EC: 4.0 - 5.5 mS/cm.
     * EC >= 6.0 mS/cm: Mild irritation.
     * EC >= 6.5 mS/cm: Probable subclinical mastitis alert.
     *
     * @return array{has_alert: bool, alert_level: string, message: string}
     */
    public function evaluateConductivity(?float $conductivityMsCm): array
    {
        if ($conductivityMsCm === null) {
            return ['has_alert' => false, 'alert_level' => 'normal', 'message' => 'No conductivity reading'];
        }

        if ($conductivityMsCm >= 6.5) {
            return [
                'has_alert' => true,
                'alert_level' => 'critical',
                'message' => "High electrical conductivity ({$conductivityMsCm} mS/cm). Potential subclinical mastitis!",
            ];
        }

        if ($conductivityMsCm >= 5.8) {
            return [
                'has_alert' => true,
                'alert_level' => 'warning',
                'message' => "Elevated electrical conductivity ({$conductivityMsCm} mS/cm). Monitor quarter.",
            ];
        }

        return [
            'has_alert' => false,
            'alert_level' => 'normal',
            'message' => 'Conductivity within normal physiological limits.',
        ];
    }
}
