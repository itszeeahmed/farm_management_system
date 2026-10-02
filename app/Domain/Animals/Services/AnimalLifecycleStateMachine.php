<?php

namespace App\Domain\Animals\Services;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\AuditLog;
use Illuminate\Validation\ValidationException;

class AnimalLifecycleStateMachine
{
    /**
     * Allowed lifecycle transitions by general species type.
     */
    protected array $allowedTransitions = [
        // Female dairy cattle
        'calf' => ['weaned', 'sold', 'deceased', 'quarantined'],
        'weaned' => ['heifer', 'young_bull', 'sold', 'deceased', 'quarantined'],
        'heifer' => ['pregnant_heifer', 'sold', 'culled', 'deceased', 'quarantined'],
        'pregnant_heifer' => ['lactating', 'dry', 'culled', 'deceased'],
        'lactating' => ['dry', 'sold', 'culled', 'deceased', 'quarantined'],
        'dry' => ['lactating', 'sold', 'culled', 'deceased', 'quarantined'],

        // Male cattle
        'young_bull' => ['breeding_bull', 'steer', 'sold', 'culled', 'deceased'],
        'breeding_bull' => ['sold', 'culled', 'deceased', 'quarantined'],
        'steer' => ['sold', 'deceased'],

        // Goats
        'kid' => ['weaned_kid', 'sold', 'deceased', 'quarantined'],
        'weaned_kid' => ['doeling', 'young_buck', 'wether', 'sold', 'deceased'],
        'doeling' => ['pregnant_doeling', 'sold', 'culled', 'deceased'],
        'pregnant_doeling' => ['lactating_doe', 'dry_doe', 'deceased'],
        'lactating_doe' => ['dry_doe', 'sold', 'culled', 'deceased'],
        'dry_doe' => ['lactating_doe', 'sold', 'culled', 'deceased'],
        'young_buck' => ['buck', 'wether', 'sold', 'deceased'],
        'buck' => ['sold', 'culled', 'deceased'],
        'wether' => ['sold', 'deceased'],
    ];

    /**
     * Check if a transition is valid.
     */
    public function canTransition(Animal $animal, string $newStage): bool
    {
        $currentStage = $animal->lifecycle_stage;

        if ($currentStage === $newStage) {
            return true;
        }

        // Terminal states cannot transition out except administrative corrections
        if (in_array($currentStage, ['sold', 'culled', 'deceased'], true)) {
            return false;
        }

        $allowed = $this->allowedTransitions[$currentStage] ?? ['active', 'sold', 'culled', 'deceased'];

        return in_array($newStage, $allowed, true);
    }

    /**
     * Transition animal to a new lifecycle stage with audit logging.
     *
     * @throws ValidationException
     */
    public function transition(Animal $animal, string $newStage, ?int $userId = null, ?string $reason = null): Animal
    {
        if (! $this->canTransition($animal, $newStage)) {
            throw ValidationException::withMessages([
                'lifecycle_stage' => ["Cannot transition animal #{$animal->tag_number} from stage '{$animal->lifecycle_stage}' to '{$newStage}'."],
            ]);
        }

        $oldStage = $animal->lifecycle_stage;
        $animal->lifecycle_stage = $newStage;

        // Auto-synchronize status if moving to lactating or dry
        if (in_array($newStage, ['lactating', 'lactating_doe'], true)) {
            $animal->status = 'lactating';
        } elseif (in_array($newStage, ['dry', 'dry_doe'], true)) {
            $animal->status = 'dry';
        } elseif (in_array($newStage, ['sold', 'culled', 'deceased'], true)) {
            $animal->status = $newStage;
        }

        $animal->save();

        // Record audit trail
        AuditLog::create([
            'organization_id' => $animal->organization_id,
            'farm_id' => $animal->farm_id,
            'user_id' => $userId,
            'action' => 'lifecycle_transition',
            'auditable_type' => Animal::class,
            'auditable_id' => $animal->id,
            'old_values' => ['lifecycle_stage' => $oldStage],
            'new_values' => ['lifecycle_stage' => $newStage, 'reason' => $reason],
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);

        return $animal;
    }
}
