<?php

namespace App\Domain\Sales\Services;

use App\Domain\Organization\Models\Farm;
use App\Domain\Sales\Models\CustomerSubscription;
use App\Domain\Sales\Models\DeliveryRun;
use App\Domain\Sales\Models\DeliveryRunStop;
use Carbon\Carbon;

class DailyDeliveryManifestGenerator
{
    /**
     * Generate an optimized delivery run for a farm on a specific date.
     */
    public function generateRun(
        Farm $farm,
        string $runDate,
        string $routeName = 'Central Urban Route',
        string $driverName = 'Muhammad Naveed',
        ?string $vehiclePlateNumber = 'LEA-8842'
    ): DeliveryRun {
        $date = Carbon::parse($runDate);

        // 1. Fetch active subscriptions for this farm
        $subscriptions = CustomerSubscription::where('farm_id', $farm->id)
            ->where('status', 'active')
            ->with('customer')
            ->get();

        // 2. Filter subscriptions due on target date
        $dueSubscriptions = $subscriptions->filter(fn (CustomerSubscription $sub) => $sub->isDueOn($date));

        // 3. Create DeliveryRun
        $run = DeliveryRun::create([
            'farm_id' => $farm->id,
            'run_date' => $date->toDateString(),
            'route_name' => $routeName,
            'driver_name' => $driverName,
            'vehicle_plate_number' => $vehiclePlateNumber,
            'vehicle_departure_temp_c' => 3.8,
            'total_liters_planned' => 0.00,
            'total_liters_delivered' => 0.00,
            'status' => 'in_progress',
        ]);

        $totalPlanned = 0.00;
        $sequence = 1;

        foreach ($dueSubscriptions as $sub) {
            $qty = (float) $sub->daily_quantity_liters;
            $unitPrice = (float) $sub->unit_price_per_liter;
            $totalAmount = round($qty * $unitPrice, 2);

            DeliveryRunStop::create([
                'delivery_run_id' => $run->id,
                'customer_id' => $sub->customer_id,
                'customer_subscription_id' => $sub->id,
                'stop_sequence' => $sequence++,
                'planned_quantity_liters' => $qty,
                'delivered_quantity_liters' => 0.00,
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'empty_bottles_returned' => 0,
                'proof_of_delivery_type' => 'otp',
                'proof_of_delivery_token' => str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
                'status' => 'pending',
            ]);

            $totalPlanned += $qty;
        }

        $run->update(['total_liters_planned' => $totalPlanned]);

        return $run->load('stops.customer');
    }
}
