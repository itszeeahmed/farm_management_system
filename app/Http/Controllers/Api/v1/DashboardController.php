<?php

namespace App\Http\Controllers\Api\v1;

use App\Domain\Animals\Models\Animal;
use App\Domain\Climate\Models\ClimateReading;
use App\Domain\Feed\Models\FeedItem;
use App\Domain\Finance\Models\FinancialTransaction;
use App\Domain\Health\Models\Treatment;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use App\Domain\Workforce\Models\FarmTask;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farm = Farm::with('organization')->first();
        if (! $farm) {
            return response()->json(['message' => 'No farm configured'], 404);
        }

        // 1. Livestock metrics
        $totalAnimals = Animal::where('farm_id', $farm->id)->count();
        $cowsCount = Animal::where('farm_id', $farm->id)
            ->whereHas('species', fn ($q) => $q->where('code', 'cattle'))
            ->count();
        $goatsCount = Animal::where('farm_id', $farm->id)
            ->whereHas('species', fn ($q) => $q->where('code', 'goat'))
            ->count();
        $lactatingCount = Animal::where('farm_id', $farm->id)->where('status', 'lactating')->count();
        $pregnantCount = Animal::where('farm_id', $farm->id)->where('status', 'pregnant')->count();
        $sickCount = Animal::where('farm_id', $farm->id)->where('status', 'sick')->count();

        // 2. Today & Yesterday's Milk
        $today = Carbon::now()->toDateString();
        $todayMilkRecords = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $today)
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->get();
        $todayMilkYield = $todayMilkRecords->sum('yield_liters');

        $yesterday = Carbon::now()->subDay()->toDateString();
        $yesterdayMilkYield = MilkRecord::where('farm_id', $farm->id)
            ->where('recorded_date', $yesterday)
            ->where('quality_status', '!=', 'discarded_withdrawal')
            ->sum('yield_liters');

        // Recent 7-Day Trend
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $cowYield = MilkRecord::where('farm_id', $farm->id)
                ->where('recorded_date', $date)
                ->where('quality_status', '!=', 'discarded_withdrawal')
                ->whereHas('animal.species', fn ($q) => $q->where('code', 'cattle'))
                ->sum('yield_liters');

            $goatYield = MilkRecord::where('farm_id', $farm->id)
                ->where('recorded_date', $date)
                ->where('quality_status', '!=', 'discarded_withdrawal')
                ->whereHas('animal.species', fn ($q) => $q->where('code', 'goat'))
                ->sum('yield_liters');

            $sevenDays[] = [
                'date' => $date,
                'display_date' => Carbon::parse($date)->format('M d'),
                'cow_liters' => round($cowYield, 1),
                'goat_liters' => round($goatYield, 1),
                'total_liters' => round($cowYield + $goatYield, 1),
            ];
        }

        // 3. Climate & THI
        $latestClimate = ClimateReading::where('farm_id', $farm->id)
            ->latest('recorded_at')
            ->first();

        // 4. Active Medicine Withdrawal Alerts
        $activeWithdrawals = Treatment::with(['animal', 'medicine'])
            ->where('farm_id', $farm->id)
            ->where('milk_withdrawal_until', '>', Carbon::now())
            ->get()
            ->map(function ($treatment) {
                return [
                    'treatment_id' => $treatment->id,
                    'animal_id' => $treatment->animal_id,
                    'tag_number' => $treatment->animal?->tag_number,
                    'animal_name' => $treatment->animal?->name,
                    'medicine_name' => $treatment->medicine?->name,
                    'withdrawal_until' => $treatment->milk_withdrawal_until->toIso8601String(),
                    'remaining_hours' => round(Carbon::now()->diffInRealHours($treatment->milk_withdrawal_until, false), 1),
                ];
            });

        // 5. Feed Stock Alerts (low stock)
        $lowStockFeeds = FeedItem::where('farm_id', $farm->id)
            ->whereColumn('current_stock', '<=', 'minimum_stock_alert')
            ->get(['id', 'name', 'current_stock', 'minimum_stock_alert', 'unit']);

        // 6. Pending & Urgent Tasks
        $pendingTasks = FarmTask::where('farm_id', $farm->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // 7. Finance Summary
        $monthlyIncome = FinancialTransaction::where('farm_id', $farm->id)
            ->where('type', 'income')
            ->where('transaction_date', '>=', Carbon::now()->startOfMonth()->toDateString())
            ->sum('amount');

        $monthlyExpense = FinancialTransaction::where('farm_id', $farm->id)
            ->where('type', 'expense')
            ->where('transaction_date', '>=', Carbon::now()->startOfMonth()->toDateString())
            ->sum('amount');

        return response()->json([
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
                'code' => $farm->code,
                'currency' => $farm->organization?->currency ?? 'PKR',
                'location' => $farm->location,
            ],
            'livestock' => [
                'total' => $totalAnimals,
                'cows' => $cowsCount,
                'goats' => $goatsCount,
                'lactating' => $lactatingCount,
                'pregnant' => $pregnantCount,
                'sick' => $sickCount,
            ],
            'milk' => [
                'today_liters' => round($todayMilkYield, 1),
                'yesterday_liters' => round($yesterdayMilkYield, 1),
                'seven_day_trend' => $sevenDays,
            ],
            'climate' => $latestClimate ? [
                'temperature_c' => (float) $latestClimate->temperature_c,
                'relative_humidity_percent' => (float) $latestClimate->relative_humidity_percent,
                'thi_index' => (float) $latestClimate->thi_index,
                'heat_stress_level' => $latestClimate->heat_stress_level,
                'recorded_at' => $latestClimate->recorded_at->toIso8601String(),
                'mitigation' => $latestClimate->mitigation_action_taken,
            ] : null,
            'active_withdrawals' => $activeWithdrawals,
            'low_stock_feeds' => $lowStockFeeds,
            'pending_tasks' => $pendingTasks,
            'finance' => [
                'monthly_income' => (float) $monthlyIncome,
                'monthly_expense' => (float) $monthlyExpense,
                'net_profit' => (float) ($monthlyIncome - $monthlyExpense),
            ],
        ]);
    }
}
