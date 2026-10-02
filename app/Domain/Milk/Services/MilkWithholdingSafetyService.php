<?php

namespace App\Domain\Milk\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\Treatment;
use App\Domain\Organization\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;

class MilkWithholdingSafetyService
{
    /**
     * Inspect whether an animal is currently under an active milk withdrawal restriction.
     */
    public function getActiveWithdrawal(Animal $animal, Carbon|string|null $atDate = null): ?Treatment
    {
        $checkTime = $atDate ? (is_string($atDate) ? Carbon::parse($atDate) : $atDate) : Carbon::now();

        return Treatment::where('animal_id', $animal->id)
            ->where('milk_withdrawal_until', '>', $checkTime)
            ->with(['medicine', 'healthCase'])
            ->orderByDesc('milk_withdrawal_until')
            ->first();
    }

    /**
     * Process pre/post milking safety check and enforce withdrawal lock.
     *
     * @return array{
     *     is_withheld: bool,
     *     quality_status: string,
     *     causative_treatment_id: int|null,
     *     discard_reason: string|null,
     *     safe_to_pool: bool
     * }
     */
    public function evaluateMilkSafety(
        Animal $animal,
        Carbon|string|null $recordedAt = null,
        ?User $overrideUser = null,
        ?string $overrideReason = null
    ): array {
        $activeTreatment = $this->getActiveWithdrawal($animal, $recordedAt);

        if (! $activeTreatment) {
            return [
                'is_withheld' => false,
                'quality_status' => 'standard',
                'causative_treatment_id' => null,
                'discard_reason' => null,
                'safe_to_pool' => true,
            ];
        }

        $medName = $activeTreatment->medicine?->name ?? 'Veterinary Medication';
        $expiryStr = Carbon::parse($activeTreatment->milk_withdrawal_until)->toDateTimeString();
        $reason = "Active food safety withdrawal for {$medName} until {$expiryStr}";

        // Check if supervisor override is provided
        if ($overrideUser && $overrideReason) {
            AuditLog::create([
                'organization_id' => $animal->organization_id,
                'farm_id' => $animal->farm_id,
                'user_id' => $overrideUser->id,
                'action' => 'milk_withdrawal_override',
                'auditable_type' => Animal::class,
                'auditable_id' => $animal->id,
                'old_values' => ['withdrawal_until' => $expiryStr],
                'new_values' => [
                    'override_by' => $overrideUser->name,
                    'override_reason' => $overrideReason,
                    'treatment_id' => $activeTreatment->id,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);

            return [
                'is_withheld' => true,
                'quality_status' => 'substandard',
                'causative_treatment_id' => $activeTreatment->id,
                'discard_reason' => "SUPERVISOR OVERRIDE: {$overrideReason} (Was: {$reason})",
                'safe_to_pool' => true,
            ];
        }

        // Automatic lock: mark as discarded and prevent bulk pool
        AuditLog::create([
            'organization_id' => $animal->organization_id,
            'farm_id' => $animal->farm_id,
            'user_id' => auth()->id(),
            'action' => 'milk_discarded_withdrawal_lock',
            'auditable_type' => Animal::class,
            'auditable_id' => $animal->id,
            'new_values' => [
                'tag_number' => $animal->tag_number,
                'medicine' => $medName,
                'withdrawal_until' => $expiryStr,
            ],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);

        return [
            'is_withheld' => true,
            'quality_status' => 'discarded_withdrawal',
            'causative_treatment_id' => $activeTreatment->id,
            'discard_reason' => $reason,
            'safe_to_pool' => false,
        ];
    }
}
