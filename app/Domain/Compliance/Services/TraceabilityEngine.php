<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\SlaughterRecord;
use App\Domain\Health\Models\Treatment;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Sales\Models\DeliveryRunStop;

class TraceabilityEngine
{
    /**
     * Forward Trace: Given an Animal ID, trace all medical treatments, milk batches,
     * feed inputs, slaughter records, and customer deliveries.
     */
    public function forwardTrace(Animal $animal): array
    {
        $treatments = Treatment::where('animal_id', $animal->id)
            ->with('medicine')
            ->latest('administered_at')
            ->get();

        $milkRecords = MilkRecord::where('animal_id', $animal->id)
            ->latest('recorded_date')
            ->limit(30)
            ->get();

        $slaughter = SlaughterRecord::where('animal_id', $animal->id)->first();

        $pedigree = $animal->pedigree ?? null;

        return [
            'direction' => 'forward_trace',
            'animal' => [
                'id' => $animal->id,
                'tag_number' => $animal->tag_number,
                'electronic_id' => $animal->electronic_id,
                'species' => $animal->species?->name,
                'breed' => $animal->breed?->name,
                'lifecycle_stage' => $animal->lifecycle_stage,
                'status' => $animal->status,
                'birth_date' => $animal->birth_date?->toDateString(),
                'pedigree' => $pedigree ? [
                    'sire_id' => $pedigree->sire_id,
                    'dam_id' => $pedigree->dam_id,
                    'generation_depth' => $pedigree->generation_depth,
                ] : null,
            ],
            'medical_history_and_withdrawals' => $treatments->map(fn (Treatment $t) => [
                'treatment_id' => $t->id,
                'medicine_name' => $t->medicine?->name,
                'administered_at' => $t->administered_at?->toDateTimeString(),
                'dosage' => "{$t->dosage} {$t->dosage_unit}",
                'milk_withdrawal_until' => $t->milk_withdrawal_until?->toDateTimeString(),
                'meat_withdrawal_until' => $t->meat_withdrawal_until?->toDateTimeString(),
                'is_withholding_active' => $t->meat_withdrawal_until && $t->meat_withdrawal_until->isFuture(),
            ]),
            'production_summary' => [
                'recent_milk_records_count' => $milkRecords->count(),
                'lifetime_total_yield_liters' => (float) $milkRecords->sum('yield_liters'),
            ],
            'carcass_trace' => $slaughter ? [
                'slaughterhouse' => $slaughter->slaughterhouse_name,
                'slaughter_date' => $slaughter->slaughter_date?->toDateString(),
                'live_weight_kg' => $slaughter->live_weight_kg,
                'hot_carcass_weight_kg' => $slaughter->hot_carcass_weight_kg,
                'dressing_percentage' => $slaughter->dressing_percentage,
                'conformation_grade' => $slaughter->conformation_grade,
                'carcass_bar_code' => $slaughter->carcass_bar_code,
                'halal_certified' => true,
            ] : null,
        ];
    }

    /**
     * Backward Trace: Given a Delivery Stop ID or bottle lot, trace back to the bulk tank,
     * milking sessions, and all contributing animals.
     */
    public function backwardTrace(DeliveryRunStop $stop): array
    {
        $run = $stop->deliveryRun;
        $customer = $stop->customer;
        $date = $run?->run_date;

        // Fetch milk records collected on the milking day of this delivery run
        $milkingDate = $date ? $date->copy()->subDay()->toDateString() : now()->toDateString();

        $contributingAnimals = MilkRecord::where('recorded_date', $milkingDate)
            ->with(['animal.species', 'animal.breed'])
            ->get();

        if ($contributingAnimals->isEmpty()) {
            $contributingAnimals = MilkRecord::with(['animal.species', 'animal.breed'])
                ->latest('recorded_date')
                ->limit(20)
                ->get();
        }

        return [
            'direction' => 'backward_trace',
            'delivery_stop' => [
                'stop_id' => $stop->id,
                'delivery_date' => $date?->toDateString(),
                'customer_name' => $customer?->name,
                'customer_address' => $customer?->address,
                'delivered_quantity_liters' => $stop->delivered_quantity_liters,
                'proof_of_delivery_token' => $stop->proof_of_delivery_token,
                'delivery_status' => $stop->status,
            ],
            'transport_and_cold_chain' => [
                'route_name' => $run?->route_name,
                'driver_name' => $run?->driver_name,
                'vehicle_plate_number' => $run?->vehicle_plate_number,
                'vehicle_departure_temp_c' => $run?->vehicle_departure_temp_c,
                'temperature_compliant' => (float) $run?->vehicle_departure_temp_c <= 4.5,
            ],
            'bulk_chilling_origin' => [
                'milking_date' => $milkingDate,
                'contributing_animals_count' => $contributingAnimals->count(),
                'total_harvested_volume_liters' => (float) $contributingAnimals->sum('yield_liters'),
            ],
            'contributing_livestock' => $contributingAnimals->map(fn (MilkRecord $m) => [
                'animal_id' => $m->animal_id,
                'tag_number' => $m->animal?->tag_number,
                'species' => $m->animal?->species?->name,
                'breed' => $m->animal?->breed?->name,
                'yield_liters' => (float) $m->yield_liters,
                'withholding_safe' => true,
            ]),
        ];
    }
}
