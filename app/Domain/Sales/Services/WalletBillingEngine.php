<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\CustomerWalletTransaction;
use App\Domain\Sales\Models\DeliveryRunStop;
use Illuminate\Validation\ValidationException;

class WalletBillingEngine
{
    /**
     * Top up a customer's prepaid balance.
     */
    public function topUpWallet(Customer $customer, float $amount, ?string $referenceId = null, ?string $notes = null): CustomerWalletTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Top-up amount must be greater than zero.'],
            ]);
        }

        $opening = (float) $customer->wallet_balance;
        $closing = round($opening + $amount, 2);

        $tx = CustomerWalletTransaction::create([
            'customer_id' => $customer->id,
            'transaction_type' => 'prepaid_topup',
            'amount' => $amount,
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'reference_id' => $referenceId,
            'notes' => $notes ?? 'Customer wallet prepaid recharge',
        ]);

        $customer->update(['wallet_balance' => $closing]);

        return $tx;
    }

    /**
     * Confirm delivery of a stop and bill customer wallet.
     *
     * @param  array{
     *     delivered_quantity_liters?: float,
     *     empty_bottles_returned?: int,
     *     proof_of_delivery_token?: ?string,
     *     notes?: ?string
     * }  $details
     */
    public function completeStopAndBill(DeliveryRunStop $stop, array $details = []): DeliveryRunStop
    {
        $deliveredQty = (float) ($details['delivered_quantity_liters'] ?? $stop->planned_quantity_liters);
        $bottlesReturned = (int) ($details['empty_bottles_returned'] ?? 0);
        $unitPrice = (float) $stop->unit_price;
        $finalAmount = round($deliveredQty * $unitPrice, 2);

        $customer = $stop->customer;
        $opening = (float) $customer->wallet_balance;
        $closing = round($opening - $finalAmount, 2);

        // Record debit transaction
        CustomerWalletTransaction::create([
            'customer_id' => $customer->id,
            'transaction_type' => 'delivery_deduction',
            'amount' => $finalAmount,
            'opening_balance' => $opening,
            'closing_balance' => $closing,
            'reference_id' => "STOP-{$stop->id}",
            'notes' => "Deduction for {$deliveredQty}L milk delivery",
        ]);

        // Update customer balance
        $customer->update(['wallet_balance' => $closing]);

        // Update stop record
        $stop->update([
            'delivered_quantity_liters' => $deliveredQty,
            'total_amount' => $finalAmount,
            'empty_bottles_returned' => $bottlesReturned,
            'proof_of_delivery_token' => $details['proof_of_delivery_token'] ?? $stop->proof_of_delivery_token,
            'status' => 'delivered',
            'notes' => $details['notes'] ?? $stop->notes,
        ]);

        // Update total delivered on the parent run
        $run = $stop->deliveryRun;
        $totalDelivered = (float) $run->stops()->where('status', 'delivered')->sum('delivered_quantity_liters');
        $run->update(['total_liters_delivered' => $totalDelivered]);

        return $stop;
    }
}
