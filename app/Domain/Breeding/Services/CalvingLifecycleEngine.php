<?php

namespace App\Domain\Breeding\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Animals\Models\AnimalPedigree;
use App\Domain\Animals\Models\WeightRecord;
use App\Domain\Animals\Services\AnimalLifecycleStateMachine;
use App\Domain\Breeding\Models\Birth;
use App\Domain\Breeding\Models\PostpartumCheck;
use App\Domain\Breeding\Models\Pregnancy;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CalvingLifecycleEngine
{
    public function __construct(
        protected AnimalLifecycleStateMachine $stateMachine
    ) {}

    /**
     * Process birth event: registers newborn animal, updates dam status to lactating,
     * logs pedigree lineage, and schedules postpartum health monitoring.
     *
     * @param  array<string, mixed>  $details
     */
    public function registerCalvingEvent(
        Animal $dam,
        ?Animal $sire = null,
        ?Pregnancy $pregnancy = null,
        array $details = []
    ): array {
        $calvingTime = isset($details['calving_datetime'])
            ? Carbon::parse($details['calving_datetime'])
            : Carbon::now();

        $offspringSex = $details['offspring_sex'] ?? 'female';
        $birthWeight = (float) ($details['birth_weight_kg'] ?? 35.0);
        $calvingEase = $details['calving_ease'] ?? 'easy_unassisted';

        // 1. Generate newborn tag number and register animal
        $prefix = $dam->species?->code === 'goat' ? 'PK-GT-NB-' : 'PK-CALF-';
        $tagNumber = $prefix.now()->format('ymd').'-'.strtoupper(Str::random(3));

        $calf = Animal::create([
            'organization_id' => $dam->organization_id,
            'farm_id' => $dam->farm_id,
            'species_id' => $dam->species_id,
            'breed_id' => $dam->breed_id,
            'tag_number' => $tagNumber,
            'name' => "Offspring of {$dam->tag_number}",
            'sex' => $offspringSex,
            'birth_date' => $calvingTime->toDateString(),
            'birth_weight_kg' => $birthWeight,
            'current_weight_kg' => $birthWeight,
            'lifecycle_stage' => 'calf',
            'status' => 'active',
            'dam_id' => $dam->id,
            'sire_id' => $sire?->id,
            'pen_id' => $dam->pen_id,
            'structure_id' => $dam->structure_id,
        ]);

        // 2. Lineage & Pedigree linkage
        AnimalPedigree::create([
            'animal_id' => $calf->id,
            'sire_id' => $sire?->id,
            'dam_id' => $dam->id,
            'generation_depth' => 1,
        ]);

        // 3. Initial birth weight log
        WeightRecord::create([
            'animal_id' => $calf->id,
            'weight_kg' => $birthWeight,
            'recorded_at' => $calvingTime->toDateString(),
            'recorded_by_name' => $details['attendant_name'] ?? 'Herd Attendant',
            'notes' => "Official birth weight for {$calf->tag_number}",
        ]);

        // 4. Update Dam Lifecycle State to 'lactating'
        $targetStage = str_contains((string) $dam->species?->code, 'goat') ? 'lactating_doe' : 'lactating';
        if ($dam->status !== 'lactating' || $dam->lifecycle_stage !== $targetStage) {
            if ($this->stateMachine->canTransition($dam, $targetStage)) {
                $this->stateMachine->transition(
                    animal: $dam,
                    newStage: $targetStage,
                    reason: "Calved offspring {$calf->tag_number}"
                );
            } else {
                $dam->update(['status' => 'lactating', 'lifecycle_stage' => $targetStage]);
            }
        }

        // 5. Update pregnancy if active
        if ($pregnancy) {
            $pregnancy->update(['status' => 'calved']);
        }

        // 6. Record Birth Event
        $birth = Birth::create([
            'farm_id' => $dam->farm_id,
            'dam_id' => $dam->id,
            'sire_id' => $sire?->id,
            'created_offspring_id' => $calf->id,
            'pregnancy_id' => $pregnancy?->id,
            'calving_datetime' => $calvingTime,
            'calving_ease' => $calvingEase,
            'birth_weight_kg' => $birthWeight,
            'offspring_sex' => $offspringSex,
            'offspring_count' => 1,
            'live_count' => 1,
            'stillborn_count' => 0,
            'colostrum_fed' => (bool) ($details['colostrum_fed'] ?? true),
            'colostrum_liters' => (float) ($details['colostrum_liters'] ?? 3.5),
            'attendant_name' => $details['attendant_name'] ?? 'Veterinary Assistant',
            'notes' => $details['notes'] ?? 'Smooth parturition. Newborn calf vigorous and suckling.',
        ]);

        // 7. Schedule 10-day Postpartum Health Examination for Dam
        $postpartum = PostpartumCheck::create([
            'farm_id' => $dam->farm_id,
            'animal_id' => $dam->id,
            'birth_id' => $birth->id,
            'check_date' => $calvingTime->copy()->addDays(10)->toDateString(),
            'days_in_milk' => 10,
            'rectal_temperature_c' => 38.6,
            'lochia_score' => 0,
            'ketosis_test_bhb_mmol_l' => 0.80,
            'uterine_involution_status' => 'normal_involution',
            'checked_by' => $details['attendant_name'] ?? 'Veterinary Staff',
            'clinical_notes' => 'Scheduled 10-day postpartum involution and subclinical ketosis screening.',
        ]);

        return [
            'birth' => $birth->load(['dam', 'sire', 'offspring']),
            'calf' => $calf,
            'dam' => $dam->fresh(),
            'postpartum_check' => $postpartum,
        ];
    }
}
