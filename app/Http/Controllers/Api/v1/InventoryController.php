<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Inventory\Models\FarmAsset;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\MaintenanceLog;
use App\Domain\Inventory\Models\Warehouse;
use App\Domain\Inventory\Services\InventoryStockManagerService;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function warehouses(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $warehouses = Warehouse::where('farm_id', $farm->id)
            ->withCount('inventoryItems')
            ->get();

        return response()->json(['data' => $warehouses]);
    }

    public function createWarehouse(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'type' => 'required|string|in:feed_store,cold_pharmacy,spare_parts,dairy_packaging,general',
            'temperature_controlled' => 'nullable|boolean',
            'target_temp_c' => 'nullable|numeric|between:-20,30',
        ]);

        $warehouse = Warehouse::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'is_active' => true,
        ]));

        return response()->json([
            'message' => 'Warehouse created successfully',
            'data' => $warehouse,
        ], 201);
    }

    public function inventoryItems(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $query = InventoryItem::where('farm_id', $farm->id)->with('warehouse');

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        $items = $query->paginate($request->integer('per_page', 25));

        return response()->json($items);
    }

    public function createInventoryItem(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'category' => 'required|string|in:feed,medicine,semen,spare_parts,sanitizer,packaging,general',
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'unit_of_measure' => 'required|string|max:20',
            'current_stock_quantity' => 'nullable|numeric|min:0',
            'reorder_level_quantity' => 'nullable|numeric|min:0',
            'safety_stock_quantity' => 'nullable|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $item = InventoryItem::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'is_active' => true,
        ]));

        return response()->json([
            'message' => 'Inventory item registered',
            'data' => $item->load('warehouse'),
        ], 201);
    }

    public function recordTransaction(
        Request $request,
        InventoryStockManagerService $stockManager
    ): JsonResponse {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'transaction_type' => 'required|in:goods_receipt,issue_to_farm,transfer,wastage_loss,cycle_count_adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($validated['inventory_item_id']);

        $transaction = $stockManager->recordTransaction($item, $validated);

        return response()->json([
            'message' => 'Inventory transaction recorded and stock quantity reconciled',
            'data' => [
                'transaction' => $transaction->load(['inventoryItem', 'warehouse']),
                'updated_stock_quantity' => $item->fresh()->current_stock_quantity,
                'is_low_stock' => $item->fresh()->isLowStock(),
            ],
        ], 201);
    }

    public function farmAssets(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $assets = FarmAsset::where('farm_id', $farm->id)
            ->with(['maintenanceLogs' => fn ($q) => $q->latest()->limit(3)])
            ->get();

        return response()->json(['data' => $assets]);
    }

    public function createFarmAsset(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'asset_code' => 'required|string|max:50',
            'category' => 'required|string|in:milking_parlor,bulk_tank,tractor,mixer_wagon,generator,solar_pv,platform_scale,other',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'meter_type' => 'nullable|string|in:hours,km,none',
            'current_meter_reading' => 'nullable|numeric|min:0',
        ]);

        $asset = FarmAsset::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'status' => 'operational',
        ]));

        return response()->json([
            'message' => 'Farm asset registered',
            'data' => $asset,
        ], 201);
    }

    public function recordMaintenance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_asset_id' => 'required|exists:farm_assets,id',
            'maintenance_type' => 'required|in:preventative,breakdown_repair,oil_filter_service,calibration',
            'service_date' => 'required|date',
            'technician_name' => 'required|string|max:100',
            'meter_reading' => 'nullable|numeric|min:0',
            'downtime_hours' => 'nullable|numeric|min:0',
            'parts_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'next_service_due_date' => 'nullable|date',
            'next_service_due_meter' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $asset = FarmAsset::findOrFail($validated['farm_asset_id']);

        $partsCost = (float) ($validated['parts_cost'] ?? 0.0);
        $laborCost = (float) ($validated['labor_cost'] ?? 0.0);
        $totalCost = round($partsCost + $laborCost, 2);

        $log = MaintenanceLog::create(array_merge($validated, [
            'total_cost' => $totalCost,
        ]));

        if (isset($validated['meter_reading'])) {
            $asset->update(['current_meter_reading' => (float) $validated['meter_reading']]);
        }

        return response()->json([
            'message' => 'Maintenance event recorded',
            'data' => $log->load('farmAsset'),
        ], 201);
    }
}
