import React, { useState, useEffect } from 'react';
import { 
    ShoppingBag, 
    Truck, 
    Wallet, 
    Plus, 
    User, 
    Calendar, 
    CheckCircle2 
} from 'lucide-react';
import { api } from '../services/api';

export default function SalesView() {
    const [customers, setCustomers] = useState([]);
    const [subscriptions, setSubscriptions] = useState([]);
    const [deliveryRuns, setDeliveryRuns] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        loadSalesData();
    }, []);

    async function loadSalesData() {
        setLoading(true);
        try {
            const [cRes, sRes, dRes] = await Promise.all([
                api.getCustomers().catch(() => ({ data: [] })),
                api.getSubscriptions().catch(() => ({ data: [] })),
                api.getDeliveryRuns().catch(() => ({ data: [] })),
            ]);
            setCustomers(cRes.data || cRes || []);
            setSubscriptions(sRes.data || sRes || []);
            setDeliveryRuns(dRes.data || dRes || []);
        } catch (err) {
            console.error('Failed to load sales data:', err);
        } finally {
            setLoading(false);
        }
    }

    const defaultCustomers = [
        { id: 1, name: 'Chaudhry Abdul Rehman', phone: '+92-321-4567890', address: 'DHA Phase 5, Sector G, Lahore', wallet_balance: 14500.00, active_subscriptions_count: 2 },
        { id: 2, name: 'Mrs. Fatima Tariq', phone: '+92-300-8456123', address: 'Model Town Block C, Lahore', wallet_balance: 8200.00, active_subscriptions_count: 1 },
        { id: 3, name: 'Artisan Gourmet Bakery & Cafe', phone: '+92-333-1234567', address: 'Gulberg III, Lahore', wallet_balance: 38400.00, active_subscriptions_count: 3 },
    ];

    const defaultRuns = [
        { id: 1, run_number: 'DEL-RUN-2026-10-02-AM', driver_name: 'Imran Bashir', vehicle: 'Chilled Suzuki Carry Van #LEE-892', total_liters: 145.0, stops_count: 18, status: 'in_transit' },
    ];

    const displayCustomers = customers.length > 0 ? customers : defaultCustomers;
    const displayRuns = deliveryRuns.length > 0 ? deliveryRuns : defaultRuns;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <ShoppingBag className="w-6 h-6 text-emerald-500" />
                        Direct Customer Milk Subscriptions & Delivery Runs
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Prepaid customer digital wallets, daily morning doorstep bottle deliveries, and route sheets
                    </p>
                </div>
            </div>

            {/* Delivery Run Card */}
            <div className="p-5 rounded-2xl bg-gradient-to-r from-emerald-900 to-slate-900 text-white shadow-lg space-y-4">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <Truck className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-[10px] font-mono uppercase tracking-wider text-emerald-300">Active Morning Delivery Run</span>
                            <h3 className="text-base font-bold font-mono">DEL-RUN-2026-10-02-AM</h3>
                        </div>
                    </div>
                    <span className="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        18 Stops • 145 Liters Chilled Raw Milk
                    </span>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs pt-3 border-t border-emerald-800/60 font-mono">
                    <div>
                        <span className="text-slate-400 block font-sans">Route Dispatcher:</span>
                        <span className="font-bold">Imran Bashir</span>
                    </div>
                    <div>
                        <span className="text-slate-400 block font-sans">Vehicle Unit:</span>
                        <span className="font-bold">Chilled Van (LEE-892)</span>
                    </div>
                    <div>
                        <span className="text-slate-400 block font-sans">Dispatched Temp:</span>
                        <span className="font-bold text-emerald-300">3.8°C</span>
                    </div>
                    <div>
                        <span className="text-slate-400 block font-sans">Fulfillment:</span>
                        <span className="font-bold text-emerald-300">14 / 18 Delivered</span>
                    </div>
                </div>
            </div>

            {/* Customers Wallet Table */}
            <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100">
                        Direct-to-Consumer Customer Accounts
                    </h3>
                </div>

                <table className="w-full text-left text-xs">
                    <thead className="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th className="p-3.5">Customer Name & Phone</th>
                            <th className="p-3.5">Delivery Address</th>
                            <th className="p-3.5">Subscriptions</th>
                            <th className="p-3.5 text-right">Prepaid Wallet Balance</th>
                            <th className="p-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                        {displayCustomers.map((cust) => (
                            <tr key={cust.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td className="p-3.5">
                                    <span className="font-bold text-slate-800 dark:text-slate-100 block">{cust.name}</span>
                                    <span className="text-[11px] font-mono text-slate-400">{cust.phone}</span>
                                </td>
                                <td className="p-3.5 text-slate-600 dark:text-slate-300">
                                    {cust.address}
                                </td>
                                <td className="p-3.5 font-mono">
                                    <span className="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {cust.active_subscriptions_count || 1} Daily Bottles
                                    </span>
                                </td>
                                <td className="p-3.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    Rs. {(cust.wallet_balance || 0).toLocaleString()}
                                </td>
                                <td className="p-3.5 text-right">
                                    <button
                                        onClick={() => alert(`Wallet top-up initiated for ${cust.name}`)}
                                        className="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-semibold text-[11px] hover:bg-emerald-700 transition"
                                    >
                                        + Top Up
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
