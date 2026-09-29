<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Feed\Models\FeedConsumption;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['items' => [], 'recent_consumption' => []]);
        }

        $items = FeedItem::where('farm_id', $farm->id)->get();

        $recentConsumption = FeedConsumption::where('farm_id', $farm->id)
            ->with(['feedItem', 'pen'])
            ->latest('consumption_date')
            ->take(20)
            ->get();

        return response()->json([
            'items' => $items,
            'recent_consumption' => $recentConsumption,
        ]);
    }

    public function storeConsumption(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'feed_item_id' => 'required|exists:feed_items,id',
            'pen_id' => 'nullable|exists:pens,id',
            'animal_id' => 'nullable|exists:animals,id',
            'consumption_date' => 'required|date',
            'quantity_consumed' => 'required|numeric|min:0.5',
            'notes' => 'nullable|string',
        ]);

        $feedItem = FeedItem::findOrFail($validated['feed_item_id']);

        if ($feedItem->current_stock < $validated['quantity_consumed']) {
            return response()->json([
                'message' => "Insufficient feed stock! Available: {$feedItem->current_stock} {$feedItem->unit}",
            ], 422);
        }

        // Deduct inventory
        $feedItem->decrement('current_stock', $validated['quantity_consumed']);

        $totalCost = $validated['quantity_consumed'] * $feedItem->cost_per_unit;

        $consumption = FeedConsumption::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'unit_cost' => $feedItem->cost_per_unit,
            'total_cost' => $totalCost,
        ]));

        return response()->json([
            'message' => 'Feed consumption logged and inventory deducted',
            'data' => $consumption->load('feedItem'),
        ], 201);
    }
}
