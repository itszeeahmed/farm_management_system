<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryTransaction;
use Illuminate\Validation\ValidationException;

class InventoryStockManagerService
{
    /**
     * Process an inventory transaction and update stock quantity.
     *
     * @param  array{
     *     transaction_type: string,
     *     quantity: float,
     *     unit_cost?: float,
     *     warehouse_id?: ?int,
     *     batch_number?: ?string,
     *     expiry_date?: ?string,
     *     reference_type?: ?string,
     *     reference_id?: ?string,
     *     notes?: ?string
     * }  $payload
     */
    public function recordTransaction(InventoryItem $item, array $payload): InventoryTransaction
    {
        $type = $payload['transaction_type'];
        $qty = (float) $payload['quantity'];
        $unitCost = (float) ($payload['unit_cost'] ?? $item->unit_cost);
        $totalCost = round($qty * $unitCost, 2);

        $currentStock = (float) $item->current_stock_quantity;

        if (in_array($type, ['issue_to_farm', 'wastage_loss', 'transfer'], true)) {
            if ($currentStock < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock for SKU '{$item->sku}'. Available: {$currentStock}, Requested: {$qty}"],
                ]);
            }
            $newStock = round($currentStock - $qty, 2);
        } elseif ($type === 'goods_receipt') {
            $newStock = round($currentStock + $qty, 2);
        } elseif ($type === 'cycle_count_adjustment') {
            $newStock = $qty; // In adjustment, quantity is the reconciled physical count
        } else {
            $newStock = $currentStock;
        }

        $warehouseId = $payload['warehouse_id'] ?? $item->warehouse_id;

        $transaction = InventoryTransaction::create([
            'farm_id' => $item->farm_id,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $warehouseId,
            'transaction_type' => $type,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'batch_number' => $payload['batch_number'] ?? null,
            'expiry_date' => $payload['expiry_date'] ?? null,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $item->update([
            'current_stock_quantity' => $newStock,
            'unit_cost' => $unitCost > 0 ? $unitCost : $item->unit_cost,
        ]);

        return $transaction;
    }
}
