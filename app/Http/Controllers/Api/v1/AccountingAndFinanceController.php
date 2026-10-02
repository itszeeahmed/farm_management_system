<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Finance\Models\BiologicalAssetValuation;
use App\Domain\Finance\Models\ChartOfAccount;
use App\Domain\Finance\Models\CostAllocationRule;
use App\Domain\Finance\Models\GeneralLedgerEntry;
use App\Domain\Finance\Services\CostPerLiterEngine;
use App\Domain\Finance\Services\Ias41BiologicalValuationService;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingAndFinanceController extends Controller
{
    public function chartOfAccounts(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm || ! $farm->organization_id) {
            return response()->json(['data' => []]);
        }

        $accounts = ChartOfAccount::where('organization_id', $farm->organization_id)
            ->with('parent')
            ->orderBy('account_code')
            ->get();

        return response()->json(['data' => $accounts]);
    }

    public function createAccount(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'account_code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'account_type' => 'required|in:asset,liability,equity,revenue,direct_expense,overhead_expense',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'currency' => 'nullable|string|max:10',
        ]);

        $account = ChartOfAccount::create(array_merge($validated, [
            'organization_id' => $farm->organization_id,
            'is_active' => true,
        ]));

        return response()->json([
            'message' => 'Chart of Account created',
            'data' => $account,
        ], 201);
    }

    public function generalLedger(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $entries = GeneralLedgerEntry::where('farm_id', $farm->id)
            ->with(['debitAccount', 'creditAccount'])
            ->latest('entry_date')
            ->paginate($request->integer('per_page', 25));

        return response()->json($entries);
    }

    public function recordJournalVoucher(Request $request): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'debit_account_id' => 'required|exists:chart_of_accounts,id',
            'credit_account_id' => 'required|exists:chart_of_accounts,id|different:debit_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'reference_type' => 'nullable|string|max:100',
            'reference_id' => 'nullable|string|max:50',
        ]);

        $entryNumber = 'JV-'.now()->format('ymd').'-'.rand(1000, 9999);

        $entry = GeneralLedgerEntry::create([
            'organization_id' => $farm->organization_id,
            'farm_id' => $farm->id,
            'entry_number' => $entryNumber,
            'entry_date' => $validated['entry_date'],
            'debit_account_id' => $validated['debit_account_id'],
            'credit_account_id' => $validated['credit_account_id'],
            'amount' => $validated['amount'],
            'currency' => 'PKR',
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'description' => $validated['description'],
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'General ledger double-entry voucher posted',
            'data' => $entry->load(['debitAccount', 'creditAccount']),
        ], 201);
    }

    public function costPerLiter(Request $request, CostPerLiterEngine $engine): JsonResponse
    {
        $farm = Farm::firstOrFail();

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $overheads = (float) $request->query('labor_energy_overheads', 15000.00);
        $benchmarkPrice = (float) $request->query('selling_price_benchmark', 220.00);

        $cpl = $engine->computeCostPerLiter(
            farm: $farm,
            startDate: $startDate,
            endDate: $endDate,
            laborEnergyOverheadAmount: $overheads,
            benchmarkSellingPrice: $benchmarkPrice
        );

        return response()->json([
            'farm' => $farm->name,
            'cost_per_liter_breakdown' => $cpl,
        ]);
    }

    public function biologicalValuations(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $valuations = BiologicalAssetValuation::where('farm_id', $farm->id)
            ->with('animal')
            ->latest('valuation_date')
            ->paginate($request->integer('per_page', 25));

        return response()->json($valuations);
    }

    public function appraiseAnimal(
        Request $request,
        int $animalId,
        Ias41BiologicalValuationService $valuationService
    ): JsonResponse {
        $animal = Animal::findOrFail($animalId);

        $validated = $request->validate([
            'valuation_date' => 'nullable|date',
            'valuer_name' => 'nullable|string|max:100',
        ]);

        $record = $valuationService->valuateAnimal(
            animal: $animal,
            valuationDate: $validated['valuation_date'] ?? null,
            valuerName: $validated['valuer_name'] ?? 'Certified Agricultural Valuer'
        );

        return response()->json([
            'message' => 'Animal appraised under IAS-41 and voucher posted to General Ledger',
            'data' => $record,
        ], 201);
    }

    public function costAllocationRules(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['data' => []]);
        }

        $rules = CostAllocationRule::where('farm_id', $farm->id)->get();

        return response()->json(['data' => $rules]);
    }
}
