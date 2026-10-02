<?php

namespace App\Domain\Animals\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Health\Models\Treatment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SlaughterClearanceGuard
{
    /**
     * Verify whether an animal is legally and medically cleared for slaughter (Meat Withdrawal Check).
     *
     * @return array{
     *     cleared: bool,
     *     reason: ?string,
     *     active_withholding_expires_at: ?string
     * }
     */
    public function checkClearance(Animal $animal): array
    {
        $now = Carbon::now();

        // 1. Check for active meat withdrawal treatments
        $activeMeatHold = Treatment::where('animal_id', $animal->id)
            ->where('meat_withdrawal_until', '>', $now)
            ->latest('meat_withdrawal_until')
            ->first();

        if ($activeMeatHold) {
            $expiryStr = $activeMeatHold->meat_withdrawal_until
                ? $activeMeatHold->meat_withdrawal_until->toDateTimeString()
                : 'Indefinite Hold';

            return [
                'cleared' => false,
                'reason' => "Animal #{$animal->tag_number} is under active meat withdrawal until {$expiryStr} due to treatment with medicine #{$activeMeatHold->medicine_id}.",
                'active_withholding_expires_at' => $expiryStr,
            ];
        }

        // 2. Check animal general status
        if ($animal->is_quarantined) {
            return [
                'cleared' => false,
                'reason' => "Animal #{$animal->tag_number} is currently quarantined for biosecurity containment.",
                'active_withholding_expires_at' => null,
            ];
        }

        return [
            'cleared' => true,
            'reason' => null,
            'active_withholding_expires_at' => null,
        ];
    }

    /**
     * Enforce slaughter clearance, throwing an exception if violation occurs.
     *
     * @throws ValidationException
     */
    public function enforceSlaughterClearance(Animal $animal): void
    {
        $check = $this->checkClearance($animal);

        if (! $check['cleared']) {
            throw ValidationException::withMessages([
                'meat_withdrawal' => [$check['reason']],
            ]);
        }
    }
}
