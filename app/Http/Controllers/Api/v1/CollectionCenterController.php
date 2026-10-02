<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Milk\Models\FarmerSupplier;
use App\Domain\Milk\Models\MilkCollectionCenter;
use App\Domain\Milk\Models\MilkCollectionIntake;
use App\Domain\Milk\Models\MilkRateChart;
use App\Domain\Milk\Services\TwoDimensionalRateChartPricingCalculator;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectionCenterController extends Controller
{
    /**
     * List cooperative Milk Collection Centers (MCCs).
     */
    public function centers(): JsonResponse
    {
        $org = Organization::first();
        if (! $org) {
            return response()->json(['data' => []]);
        }

        $centers = MilkCollectionCenter::where('organization_id', $org->id)
            ->withCount(['suppliers', 'intakes'])
            ->get();

        return response()->json(['data' => $centers]);
    }

    /**
     * Show single collection center details.
     */
    public function showCenter(int $id): JsonResponse
    {
        $center = MilkCollectionCenter::with([
            'suppliers',
            'intakes' => fn ($q) => $q->latest('collection_date')->take(20),
        ])->findOrFail($id);

        return response()->json(['data' => $center]);
    }

    /**
     * List registered farmer suppliers / smallholders.
     */
    public function suppliers(Request $request): JsonResponse
    {
        $org = Organization::first();
        if (! $org) {
            return response()->json(['data' => []]);
        }

        $query = FarmerSupplier::where('organization_id', $org->id)
            ->with(['collectionCenter']);

        if ($request->filled('center_id')) {
            $query->where('collection_center_id', $request->center_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('village_address', 'like', "%{$search}%");
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * Register a new smallholder farmer supplier.
     */
    public function storeSupplier(Request $request): JsonResponse
    {
        $org = Organization::first();
        if (! $org) {
            return response()->json(['message' => 'Organization not found'], 422);
        }

        $validated = $request->validate([
            'collection_center_id' => 'required|exists:milk_collection_centers,id',
            'supplier_code' => 'required|string|max:50|unique:farmer_suppliers,supplier_code',
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:50',
            'cnic_or_national_id' => 'nullable|string|max:50',
            'village_address' => 'nullable|string|max:255',
            'cattle_count' => 'nullable|integer|min:0',
            'buffalo_count' => 'nullable|integer|min:0',
            'goat_count' => 'nullable|integer|min:0',
            'payout_channel' => 'nullable|string|in:cash,bank_transfer,easypaisa,jazzcash,nayapay',
            'payout_account_number' => 'nullable|string|max:100',
            'payout_account_title' => 'nullable|string|max:100',
        ]);

        $supplier = FarmerSupplier::create(array_merge($validated, [
            'organization_id' => $org->id,
            'is_active' => true,
        ]));

        return response()->json([
            'message' => "Farmer {$supplier->name} registered with code {$supplier->supplier_code}",
            'data' => $supplier->load('collectionCenter'),
        ], 201);
    }

    /**
     * List active 2D Fat x SNF rate charts.
     */
    public function rateCharts(): JsonResponse
    {
        $charts = MilkRateChart::active()->get();

        return response()->json(['data' => $charts]);
    }

    /**
     * Create a new 2D milk pricing rate chart.
     */
    public function storeRateChart(Request $request): JsonResponse
    {
        $org = Organization::first();
        if (! $org) {
            return response()->json(['message' => 'Organization not found'], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'species_type' => 'required|in:cow,buffalo,goat,mixed',
            'base_price_per_liter' => 'required|numeric|min:1',
            'standard_fat_percentage' => 'required|numeric|between:1,12',
            'standard_snf_percentage' => 'required|numeric|between:5,15',
            'fat_rate_per_unit' => 'required|numeric|min:0',
            'snf_rate_per_unit' => 'required|numeric|min:0',
            'min_fat_acceptance' => 'nullable|numeric|between:1,10',
            'min_snf_acceptance' => 'nullable|numeric|between:4,12',
            'premium_incentive_percent' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $chart = MilkRateChart::create(array_merge($validated, [
            'organization_id' => $org->id,
            'is_active' => true,
        ]));

        return response()->json([
            'message' => "Milk pricing rate chart '{$chart->name}' created successfully",
            'data' => $chart,
        ], 201);
    }

    /**
     * List collection intakes.
     */
    public function intakes(Request $request): JsonResponse
    {
        $query = MilkCollectionIntake::with(['collectionCenter', 'supplier', 'rateChart']);

        if ($request->filled('center_id')) {
            $query->where('collection_center_id', $request->center_id);
        }

        if ($request->filled('supplier_id')) {
            $query->where('farmer_supplier_id', $request->supplier_id);
        }

        if ($request->filled('date')) {
            $query->where('collection_date', $request->date);
        }

        return response()->json($query->latest('collection_date')->paginate(30));
    }

    /**
     * Record a milk intake from smallholder with automated 2D rate calculation & adulteration test.
     */
    public function storeIntake(
        Request $request,
        TwoDimensionalRateChartPricingCalculator $calculator
    ): JsonResponse {
        $validated = $request->validate([
            'collection_center_id' => 'required|exists:milk_collection_centers,id',
            'farmer_supplier_id' => 'required|exists:farmer_suppliers,id',
            'rate_chart_id' => 'required|exists:milk_rate_charts,id',
            'collection_date' => 'required|date',
            'shift' => 'required|in:morning,evening',
            'species_type' => 'required|in:cow,buffalo,goat',
            'gross_volume_liters' => 'required|numeric|min:0.5',
            'lactometer_reading' => 'nullable|numeric',
            'fat_percentage' => 'required|numeric|between:1,15',
            'snf_percentage' => 'nullable|numeric|between:4,15',
            // Adulteration test fields
            'alcohol_test_result' => 'nullable|in:negative,positive',
            'adulteration_starch' => 'nullable|boolean',
            'adulteration_urea' => 'nullable|boolean',
            'adulteration_detergent' => 'nullable|boolean',
            'adulteration_formalin' => 'nullable|boolean',
            'adulteration_hydrogen_peroxide' => 'nullable|boolean',
            'added_water_percentage' => 'nullable|numeric|min:0',
        ]);

        $chart = MilkRateChart::findOrFail($validated['rate_chart_id']);
        $center = MilkCollectionCenter::findOrFail($validated['collection_center_id']);

        // Compute SNF from lactometer if not explicitly provided
        $fat = (float) $validated['fat_percentage'];
        $snf = isset($validated['snf_percentage']) && $validated['snf_percentage'] !== null
            ? (float) $validated['snf_percentage']
            : ($validated['lactometer_reading'] ? $calculator->calculateSnfFromLactometer((float) $validated['lactometer_reading'], $fat, $validated['species_type']) : 8.5);

        // Run 2D rate pricing & adulteration battery
        $adulterationBattery = [
            'alcohol_test_result' => $validated['alcohol_test_result'] ?? 'negative',
            'adulteration_starch' => $validated['adulteration_starch'] ?? false,
            'adulteration_urea' => $validated['adulteration_urea'] ?? false,
            'adulteration_detergent' => $validated['adulteration_detergent'] ?? false,
            'adulteration_formalin' => $validated['adulteration_formalin'] ?? false,
            'adulteration_hydrogen_peroxide' => $validated['adulteration_hydrogen_peroxide'] ?? false,
            'added_water_percentage' => $validated['added_water_percentage'] ?? 0.0,
        ];

        $pricingResult = $calculator->calculateIntakePrice(
            chart: $chart,
            volumeLiters: (float) $validated['gross_volume_liters'],
            fatPercentage: $fat,
            snfPercentage: $snf,
            adulterationTests: $adulterationBattery
        );

        $intakeNum = 'INT-'.date('Ymd').'-'.strtoupper(Str::random(4));

        $intake = MilkCollectionIntake::create(array_merge($validated, [
            'snf_percentage' => $snf,
            'intake_number' => $intakeNum,
            'calculated_price_per_liter' => $pricingResult['price_per_liter'],
            'gross_amount' => $pricingResult['gross_amount'],
            'deductions_amount' => $pricingResult['deductions_amount'],
            'net_payable_amount' => $pricingResult['net_payable_amount'],
            'quality_accepted' => $pricingResult['quality_accepted'],
            'rejection_reason' => $pricingResult['rejection_reason'],
            'payment_status' => $pricingResult['quality_accepted'] ? 'approved' : 'rejected',
            'operator_id' => auth()->id(),
        ]));

        // Increment MCC chilling volume only if milk passed quality checks
        if ($pricingResult['quality_accepted']) {
            $center->increment('current_volume_liters', (float) $validated['gross_volume_liters']);
        }

        return response()->json([
            'message' => $pricingResult['quality_accepted'] ? 'Milk intake accepted and logged' : 'Milk intake REJECTED due to quality or adulteration',
            'quality_accepted' => $pricingResult['quality_accepted'],
            'rejection_reason' => $pricingResult['rejection_reason'],
            'pricing' => $pricingResult,
            'data' => $intake->load(['supplier', 'collectionCenter', 'rateChart']),
        ], 201);
    }
}
