<?php

namespace App\Domain\Finance\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Finance\Models\BiologicalAssetValuation;
use App\Domain\Finance\Models\ChartOfAccount;
use App\Domain\Finance\Models\GeneralLedgerEntry;
use Carbon\Carbon;

class Ias41BiologicalValuationService
{
    /**
     * Compute IAS-41 Fair Value Less Estimated Costs to Sell for an Animal.
     *
     * @return array{
     *     fair_value: float,
     *     cost_to_sell: float,
     *     net_carrying_value: float,
     *     maturity_stage: string
     * }
     */
    public function computeAnimalFairValue(Animal $animal): array
    {
        $stage = $animal->lifecycle_stage ?? 'mature_lactating';
        $weight = (float) ($animal->current_weight_kg ?? 400.0);
        $species = $animal->species?->code ?? 'cattle';

        if ($species === 'goat') {
            $baseValue = match ($stage) {
                'kid' => 12000.00,
                'weaned_kid', 'doeling' => 22000.00,
                'pregnant_doeling' => 38000.00,
                'lactating_doe' => 45000.00,
                'buck' => 60000.00,
                default => 25000.00,
            };
        } else {
            // Cattle / Buffalo
            $baseValue = match ($stage) {
                'calf' => 45000.00,
                'weaned' => 85000.00,
                'heifer' => 160000.00,
                'pregnant_heifer' => 280000.00,
                'lactating' => 320000.00,
                'breeding_bull' => 350000.00,
                'steer' => round($weight * 700.0, 2), // Meat liveweight rate
                default => 200000.00,
            };
        }

        // Standard 5% estimated costs to sell (transport, broker, health check)
        $costToSell = round($baseValue * 0.05, 2);
        $netCarryingValue = round($baseValue - $costToSell, 2);

        return [
            'fair_value' => $baseValue,
            'cost_to_sell' => $costToSell,
            'net_carrying_value' => $netCarryingValue,
            'maturity_stage' => $stage,
        ];
    }

    /**
     * Appraise an animal, store IAS-41 valuation record, and post to General Ledger.
     */
    public function valuateAnimal(
        Animal $animal,
        ?string $valuationDate = null,
        ?string $valuerName = 'Certified Agricultural Valuer'
    ): BiologicalAssetValuation {
        $date = $valuationDate ? Carbon::parse($valuationDate) : Carbon::now();
        $valuation = $this->computeAnimalFairValue($animal);

        $record = BiologicalAssetValuation::create([
            'farm_id' => $animal->farm_id,
            'animal_id' => $animal->id,
            'valuation_date' => $date->toDateString(),
            'fair_value_amount' => $valuation['fair_value'],
            'estimated_cost_to_sell' => $valuation['cost_to_sell'],
            'net_carrying_value' => $valuation['net_carrying_value'],
            'valuation_method' => 'market_comparison',
            'maturity_stage' => $valuation['maturity_stage'],
            'valuer_name' => $valuerName,
            'notes' => "IAS-41 annual biological asset fair value appraisal for {$animal->tag_number}",
        ]);

        // Post Journal Voucher to General Ledger if COA exists
        $farm = $animal->farm;
        $org = $farm?->organization;

        if ($org) {
            $assetAccount = ChartOfAccount::firstOrCreate(
                ['organization_id' => $org->id, 'account_code' => '1510'],
                ['name' => 'Biological Assets - Livestock', 'account_type' => 'asset']
            );

            $gainAccount = ChartOfAccount::firstOrCreate(
                ['organization_id' => $org->id, 'account_code' => '4510'],
                ['name' => 'Gain/Loss on Biological Asset Valuation', 'account_type' => 'revenue']
            );

            GeneralLedgerEntry::create([
                'organization_id' => $org->id,
                'farm_id' => $farm->id,
                'entry_number' => 'JV-IAS41-'.now()->format('ymd').'-'.$animal->id,
                'entry_date' => $date->toDateString(),
                'debit_account_id' => $assetAccount->id,
                'credit_account_id' => $gainAccount->id,
                'amount' => $valuation['net_carrying_value'],
                'currency' => 'PKR',
                'reference_type' => 'biological_asset_valuation',
                'reference_id' => (string) $record->id,
                'description' => "IAS-41 Fair value recognition for animal #{$animal->tag_number}",
            ]);
        }

        return $record->load('animal');
    }
}
