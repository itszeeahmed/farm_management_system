import React, { useState, useEffect } from 'react';
import { 
    CircleDollarSign, 
    TrendingUp, 
    TrendingDown, 
    ArrowUpRight, 
    ArrowDownRight, 
    CreditCard, 
    Calendar 
} from 'lucide-react';
import { api } from '../services/api';

export default function FinanceView() {
    const [transactions, setTransactions] = useState([]);
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadFinances();
    }, []);

    async function loadFinances() {
        setLoading(true);
        try {
            const [fRes, dRes] = await Promise.all([
                api.getFinances(),
                api.getDashboard()
            ]);
            setTransactions(fRes.data || []);
            setSummary(dRes.finance || null);
        } catch (err) {
            console.error('Failed to load finances:', err);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <CircleDollarSign className="w-5 h-5 text-emerald-500" />
                        Finances, P&L & Cashflow Ledger
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Operational revenues (Milk sales, livestock) vs expenditures (Feed, veterinary, farm labor)
                    </p>
                </div>
            </div>

            {/* Financial Overview Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Revenue</span>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                            PKR {summary?.monthly_income?.toLocaleString() || 0}
                        </span>
                    </div>
                    <span className="text-xs text-emerald-500 mt-2 block font-medium flex items-center gap-1">
                        <ArrowUpRight className="w-3.5 h-3.5" /> 110 Commercial Milk Deliveries
                    </span>
                </div>

                <div className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Operating Expense</span>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-2xl font-bold font-mono text-rose-600 dark:text-rose-400">
                            PKR {summary?.monthly_expense?.toLocaleString() || 0}
                        </span>
                    </div>
                    <span className="text-xs text-rose-500 mt-2 block font-medium flex items-center gap-1">
                        <ArrowDownRight className="w-3.5 h-3.5" /> Silage, Concentrate & Veterinary
                    </span>
                </div>

                <div className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Net Operating Balance</span>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className={`text-2xl font-bold font-mono ${
                            (summary?.net_profit || 0) >= 0 ? 'text-emerald-500' : 'text-slate-800 dark:text-slate-200'
                        }`}>
                            PKR {summary?.net_profit?.toLocaleString() || 0}
                        </span>
                    </div>
                    <span className="text-xs text-slate-400 mt-2 block font-medium">Initial pilot month investment phase</span>
                </div>
            </div>

            {/* Transactions Ledger */}
            <div className="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h4 className="font-bold text-xs uppercase tracking-wider text-slate-500">Recent Accounting Ledger</h4>
                    <span className="text-xs text-slate-400">{transactions.length} entries</span>
                </div>

                {loading ? (
                    <div className="p-8 text-center text-xs text-slate-400">Loading ledger...</div>
                ) : transactions.length === 0 ? (
                    <div className="p-8 text-center text-xs text-slate-400">No transactions recorded yet.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase font-mono tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Date</th>
                                    <th className="py-3 px-4">Type</th>
                                    <th className="py-3 px-4">Category</th>
                                    <th className="py-3 px-4">Description / Reference</th>
                                    <th className="py-3 px-4">Payment Method</th>
                                    <th className="py-3 px-4 text-right">Amount (PKR)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {transactions.map((tx) => {
                                    const isIncome = tx.type === 'income';
                                    return (
                                        <tr key={tx.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">
                                                {tx.transaction_date}
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                                    isIncome 
                                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' 
                                                        : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                                }`}>
                                                    {tx.type}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 capitalize font-medium text-slate-700 dark:text-slate-300">
                                                {tx.category?.replace('_', ' ')}
                                            </td>
                                            <td className="py-3 px-4 text-slate-500">
                                                {tx.description}
                                            </td>
                                            <td className="py-3 px-4 font-mono uppercase text-[11px] text-slate-500">
                                                {tx.payment_method || 'Bank Transfer'}
                                            </td>
                                            <td className={`py-3 px-4 text-right font-mono font-bold text-sm ${
                                                isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white'
                                            }`}>
                                                {isIncome ? '+' : '-'} PKR {parseFloat(tx.amount).toLocaleString()}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
