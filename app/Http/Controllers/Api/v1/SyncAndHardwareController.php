<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Organization\Models\Farm;
use App\Domain\Sync\Models\DeviceRegistry;
use App\Domain\Sync\Models\DeviceTelemetryLog;
use App\Domain\Sync\Services\MobileSyncEngine;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SyncAndHardwareController extends Controller
{
    public function deviceRegistries(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $devices = DeviceRegistry::where('farm_id', $farm->id)
            ->withCount('telemetryLogs')
            ->get();

        return response()->json(['data' => $devices]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'device_identifier' => 'required|string|max:100|unique:device_registries,device_identifier',
            'device_name' => 'required|string|max:100',
            'device_type' => 'required|in:rfid_stick_reader,automatic_milk_meter,bulk_tank_temp_probe,digital_platform_scale,weather_station',
            'firmware_version' => 'nullable|string|max:50',
            'ip_address' => 'nullable|string|max:50',
        ]);

        $apiKey = 'iot_live_'.Str::random(32);

        $device = DeviceRegistry::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'api_key' => $apiKey,
            'battery_percentage' => 100.00,
            'last_heartbeat_at' => Carbon::now(),
            'status' => 'online',
        ]));

        return response()->json([
            'message' => 'Hardware device registered to farm network',
            'data' => $device,
        ], 201);
    }

    public function ingestTelemetry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'required|string|exists:device_registries,api_key',
            'metric_name' => 'required|string|max:50',
            'metric_value' => 'required|numeric',
            'unit_of_measure' => 'nullable|string|max:20',
            'raw_payload' => 'nullable|array',
        ]);

        $device = DeviceRegistry::where('api_key', $validated['api_key'])->firstOrFail();

        $log = DeviceTelemetryLog::create([
            'device_registry_id' => $device->id,
            'recorded_at' => Carbon::now(),
            'metric_name' => $validated['metric_name'],
            'metric_value' => $validated['metric_value'],
            'unit_of_measure' => $validated['unit_of_measure'] ?? null,
            'raw_payload_json' => $validated['raw_payload'] ?? null,
        ]);

        $device->update(['last_heartbeat_at' => Carbon::now()]);

        return response()->json([
            'message' => 'Telemetry packet ingested successfully',
            'data' => $log,
        ], 201);
    }

    public function pullChanges(Request $request, MobileSyncEngine $syncEngine): JsonResponse
    {
        $farm = Farm::firstOrFail();
        $org = $farm->organization;

        $lastRevId = $request->integer('last_rev_id', 0);
        $limit = $request->integer('limit', 100);

        $result = $syncEngine->pullChanges($lastRevId, $org, $limit);

        return response()->json($result);
    }

    public function pushMutations(Request $request, MobileSyncEngine $syncEngine): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'mutations' => 'required|array|min:1',
            'mutations.*.entity_type' => 'required|string|max:100',
            'mutations.*.action' => 'required|in:insert,update,delete',
            'mutations.*.idempotency_key' => 'required|string|max:100',
            'mutations.*.payload' => 'required|array',
        ]);

        $farm = Farm::firstOrFail();
        $org = $farm->organization;
        $user = $request->user() ?? User::first();

        $result = $syncEngine->pushMutations(
            mutations: $validated['mutations'],
            user: $user,
            org: $org,
            deviceUuid: $validated['device_uuid']
        );

        return response()->json($result);
    }
}
