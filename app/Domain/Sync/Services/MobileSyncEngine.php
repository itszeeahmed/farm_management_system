<?php

namespace App\Domain\Sync\Services;

use App\Domain\Organization\Models\Organization;
use App\Domain\Sync\Models\SyncChangeLog;
use App\Domain\Sync\Models\SyncClientRegistry;
use App\Models\User;
use Carbon\Carbon;

class MobileSyncEngine
{
    /**
     * Pull server changes since client's last revision ID.
     */
    public function pullChanges(int $lastRevId, Organization $org, int $limit = 100): array
    {
        $changes = SyncChangeLog::where('organization_id', $org->id)
            ->where('id', '>', $lastRevId)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $maxRev = $changes->isNotEmpty() ? (int) $changes->last()->id : $lastRevId;
        $hasMore = SyncChangeLog::where('organization_id', $org->id)
            ->where('id', '>', $maxRev)
            ->exists();

        return [
            'current_rev_id' => $maxRev,
            'changes_count' => $changes->count(),
            'changes' => $changes,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Push queued offline mutations to the server with idempotency check.
     */
    public function pushMutations(array $mutations, User $user, Organization $org, string $deviceUuid): array
    {
        $processedCount = 0;
        $latestRevId = 0;

        foreach ($mutations as $mutation) {
            $idempotencyKey = $mutation['idempotency_key'] ?? null;

            // Check if already processed
            if ($idempotencyKey) {
                $existing = SyncChangeLog::where('organization_id', $org->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    $latestRevId = max($latestRevId, (int) $existing->id);

                    continue; // Skip duplicate
                }
            }

            $entityType = $mutation['entity_type'];
            $action = $mutation['action'] ?? 'insert';
            $payload = $mutation['payload'] ?? [];
            $entityId = (string) ($mutation['entity_id'] ?? rand(1000, 9999));

            // Log change in sync changelog
            $log = SyncChangeLog::create([
                'organization_id' => $org->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'idempotency_key' => $idempotencyKey,
                'delta_payload_json' => $payload,
                'timestamp' => Carbon::now(),
            ]);

            $latestRevId = (int) $log->id;
            $processedCount++;
        }

        // Update Sync Client Registry
        SyncClientRegistry::updateOrCreate(
            ['device_uuid' => $deviceUuid],
            [
                'user_id' => $user->id,
                'last_sync_rev_id' => $latestRevId,
                'last_synced_at' => Carbon::now(),
            ]
        );

        return [
            'status' => 'success',
            'processed_count' => $processedCount,
            'current_rev_id' => $latestRevId,
            'synced_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
