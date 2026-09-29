<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Finance\Models\FinancialTransaction;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['transactions' => []]);
        }

        $query = FinancialTransaction::where('farm_id', $farm->id)
            ->with(['supplier', 'animal']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $transactions = $query->latest('transaction_date')->paginate(25);

        $totalIncome = FinancialTransaction::where('farm_id', $farm->id)->where('type', 'income')->sum('amount');
        $totalExpense = FinancialTransaction::where('farm_id', $farm->id)->where('type', 'expense')->sum('amount');
        $totalMilkProduced = MilkRecord::where('farm_id', $farm->id)->where('quality_status', '!=', 'discarded_withdrawal')->sum('yield_liters');

        // Cost per liter of milk calculation (Section 15, FIN-004)
        $costPerLiter = $totalMilkProduced > 0 ? round($totalExpense / $totalMilkProduced, 2) : 0;

        return response()->json([
            'summary' => [
                'total_income' => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'net_profit' => (float) ($totalIncome - $totalExpense),
                'total_milk_produced_liters' => (float) $totalMilkProduced,
                'estimated_cost_per_liter' => $costPerLiter,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function storeTransaction(Request $request): JsonResponse
    {
        $farm = Farm::first();
        if (! $farm) {
            return response()->json(['message' => 'Farm not configured'], 422);
        }

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $transaction = FinancialTransaction::create(array_merge($validated, [
            'farm_id' => $farm->id,
        ]));

        return response()->json([
            'message' => 'Transaction saved successfully',
            'data' => $transaction,
        ], 201);
    }
}
