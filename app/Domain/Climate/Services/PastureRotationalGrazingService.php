<?php

namespace App\Domain\Climate\Services;

use App\Domain\Animals\Models\AnimalGroup;
use App\Domain\Climate\Models\GrazingLog;
use App\Domain\Climate\Models\PasturePlot;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PastureRotationalGrazingService
{
    /**
     * Start grazing a mob/group in a pasture plot.
     */
    public function enterPaddock(
        PasturePlot $plot,
        ?AnimalGroup $group,
        int $headCount,
        float $preGrazeHeightCm,
        string $entryDate
    ): GrazingLog {
        // Enforce rotational rest interval
        if ($plot->status === 'grazing') {
            throw ValidationException::withMessages([
                'pasture_plot_id' => ["Paddock '{$plot->name}' is already being actively grazed."],
            ]);
        }

        $area = max(0.1, (float) $plot->area_hectares);
        $livestockUnitsPerHa = round(($headCount * 1.0) / $area, 2);

        $log = GrazingLog::create([
            'farm_id' => $plot->farm_id,
            'pasture_plot_id' => $plot->id,
            'animal_group_id' => $group?->id,
            'entry_date' => $entryDate,
            'stocking_density_heads' => $headCount,
            'livestock_units_per_ha' => $livestockUnitsPerHa,
            'pre_graze_height_cm' => $preGrazeHeightCm,
        ]);

        $plot->update([
            'status' => 'grazing',
            'last_grazed_at' => Carbon::parse($entryDate),
        ]);

        return $log;
    }

    /**
     * Complete grazing and calculate dry matter utilized.
     */
    public function exitPaddock(
        GrazingLog $log,
        float $postGrazeResidualHeightCm,
        string $exitDate
    ): GrazingLog {
        $plot = $log->pasturePlot;
        $heightDifference = max(0.0, (float) $log->pre_graze_height_cm - $postGrazeResidualHeightCm);

        // Approximate 250 kg DM / ha per cm of forage height consumed
        $dmUtilizedKgHa = round($heightDifference * 250.0, 2);

        $log->update([
            'exit_date' => $exitDate,
            'post_graze_residual_height_cm' => $postGrazeResidualHeightCm,
            'dry_matter_utilized_kg_ha' => $dmUtilizedKgHa,
        ]);

        $plot->update([
            'status' => 'recovering',
            'current_biomass_kg_dm_per_ha' => max(500.0, round((float) $plot->current_biomass_kg_dm_per_ha - $dmUtilizedKgHa, 2)),
            'last_grazed_at' => Carbon::parse($exitDate),
        ]);

        return $log;
    }
}
