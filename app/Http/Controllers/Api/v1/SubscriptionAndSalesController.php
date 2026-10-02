<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Organization\Models\Farm;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\CustomerSubscription;
use App\Domain\Sales\Models\DeliveryRun;
use App\Domain\Sales\Models\DeliveryRunStop;
use App\Domain\Sales\Services\DailyDeliveryManifestGenerator;
use App\Domain\Sales\Services\WalletBillingEngine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionAndSalesController extends Controller
{
    public function customers(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $customers = Customer::where('farm_id', $farm->id)
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'active')])
            ->paginate($request->integer('per_page', 25));

        return response()->json($customers);
    }

    public function createCustomer(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'customer_type' => 'required|in:household_subscription,retail_store,restaurant,bulk_processor',
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'initial_wallet_balance' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create([
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'customer_type' => $validated['customer_type'],
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'],
            'city' => $validated['city'] ?? 'Lahore',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'wallet_balance' => $validated['initial_wallet_balance'] ?? 0.00,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Customer profile created',
            'data' => $customer,
        ], 201);
    }

    public function topUpWallet(
        Request $request,
        int $customerId,
        WalletBillingEngine $billingEngine
    ): JsonResponse {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reference_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $customer = Customer::findOrFail($customerId);

        $tx = $billingEngine->topUpWallet(
            customer: $customer,
            amount: (float) $validated['amount'],
            referenceId: $validated['reference_id'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => "Customer wallet topped up by PKR {$validated['amount']}. New Balance: PKR {$customer->fresh()->wallet_balance}",
            'data' => [
                'transaction' => $tx,
                'current_wallet_balance' => $customer->fresh()->wallet_balance,
            ],
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $subscriptions = CustomerSubscription::where('farm_id', $farm->id)
            ->with('customer')
            ->paginate($request->integer('per_page', 25));

        return response()->json($subscriptions);
    }

    public function createSubscription(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_type' => 'required|string|in:raw_cow_milk,raw_goat_milk,pasteurized_cow_milk,yogurt,cheese',
            'daily_quantity_liters' => 'required|numeric|min:0.5',
            'unit_price_per_liter' => 'required|numeric|min:1',
            'frequency' => 'required|in:daily,alternate_days,weekly,weekdays_only,weekends_only',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $sub = CustomerSubscription::create(array_merge($validated, [
            'farm_id' => $farm->id,
            'is_paused' => false,
            'status' => 'active',
        ]));

        return response()->json([
            'message' => 'Milk delivery subscription registered',
            'data' => $sub->load('customer'),
        ], 201);
    }

    public function generateDeliveryRun(
        Request $request,
        DailyDeliveryManifestGenerator $manifestGenerator
    ): JsonResponse {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'run_date' => 'required|date',
            'route_name' => 'nullable|string|max:100',
            'driver_name' => 'nullable|string|max:100',
            'vehicle_plate_number' => 'nullable|string|max:50',
        ]);

        $run = $manifestGenerator->generateRun(
            farm: $farm,
            runDate: $validated['run_date'],
            routeName: $validated['route_name'] ?? 'Lahore Model Town & DHA Route',
            driverName: $validated['driver_name'] ?? 'Tariq Mehmood',
            vehiclePlateNumber: $validated['vehicle_plate_number'] ?? 'LEC-7719'
        );

        return response()->json([
            'message' => "Delivery run manifest generated with {$run->stops->count()} stops. Total volume: {$run->total_liters_planned} Liters.",
            'data' => $run,
        ], 201);
    }

    public function deliveryRuns(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $runs = DeliveryRun::where('farm_id', $farm->id)
            ->withCount('stops')
            ->latest('run_date')
            ->paginate($request->integer('per_page', 20));

        return response()->json($runs);
    }

    public function deliveryRunDetail(int $id): JsonResponse
    {
        $run = DeliveryRun::with(['stops.customer', 'stops.customerSubscription'])->findOrFail($id);

        return response()->json(['data' => $run]);
    }

    public function completeDeliveryStop(
        Request $request,
        int $stopId,
        WalletBillingEngine $billingEngine
    ): JsonResponse {
        $validated = $request->validate([
            'delivered_quantity_liters' => 'required|numeric|min:0.5',
            'empty_bottles_returned' => 'nullable|integer|min:0',
            'proof_of_delivery_token' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $stop = DeliveryRunStop::findOrFail($stopId);

        $completedStop = $billingEngine->completeStopAndBill($stop, $validated);

        return response()->json([
            'message' => 'Stop delivered, proof-of-delivery confirmed, and customer wallet debited',
            'data' => [
                'stop' => $completedStop->load(['customer', 'deliveryRun']),
                'customer_remaining_balance' => $stop->customer->fresh()->wallet_balance,
            ],
        ]);
    }
}
