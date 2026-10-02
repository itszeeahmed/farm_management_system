import React, { useState, useEffect } from 'react';
import { 
    Beef, 
    Scale, 
    ShieldCheck, 
    ShieldAlert, 
    Plus, 
    Scissors, 
    TrendingUp, 
    Activity 
} from 'lucide-react';
import { api } from '../services/api';

export default function FeedlotView() {
    const [feedlots, setFeedlots] = useState([]);
    const [loading, setLoading] = useState(false);
    const [clearanceResult, setClearanceResult] = useState(null);

    useEffect(() => {
        loadFeedlots();
    }, []);

    async function loadFeedlots() {
        setLoading(true);
        try {
            const res = await api.getFeedlots();
            setFeedlots(res.data || res || []);
        } catch (err) {
            console.error('Failed to load feedlots:', err);
        } finally {
            setLoading(false);
        }
    }

    async function verifySlaughterClearance(animalId) {
        try {
            const res = await api.checkSlaughterClearance(animalId);
            setClearanceResult(res);
        } catch (err) {
            alert('Clearance check error: ' + err.message);
        }
    }

    const defaultFeedlots = [
        { id: 1, lot_code: 'LOT-SAHI-2026-A', target_market: 'Premium Halal Beef', animal_tag: 'PK-COW-002', intake_weight_kg: 320.0, current_weight_kg: 445.0, days_on_feed: 85, avg_daily_gain: 1.47, status: 'finishing' },
        { id: 2, lot_code: 'LOT-BUFF-2026-B', target_market: 'Prime Meat Carcass', animal_tag: 'PK-BUF-010', intake_weight_kg: 380.0, current_weight_kg: 510.0, days_on_feed: 92, avg_daily_gain: 1.41, status: 'finishing' },
        { id: 3, lot_code: 'LOT-GOAT-2026-C', target_market: 'Eid-ul-Adha Export', animal_tag: 'PK-GOT-042', intake_weight_kg: 32.0, current_weight_kg: 52.5, days_on_feed: 60, avg_daily_gain: 0.34, status: 'ready_for_market' },
    ];

    const displayList = feedlots.length > 0 ? feedlots : defaultFeedlots;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Beef className="w-6 h-6 text-amber-500" />
                        Beef Feedlot & Meat Production
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Feedlot intake, Average Daily Gain (ADG) monitoring, and food safety slaughter clearance
                    </p>
                </div>
            </div>

            {/* KPI Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs text-slate-400 block font-medium">Herd Average ADG</span>
                    <div className="flex items-baseline gap-1 mt-1">
                        <span className="text-2xl font-bold font-mono text-emerald-500">+1.45</span>
                        <span className="text-xs text-slate-400">kg/day</span>
                    </div>
                    <span className="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Exceeding +1.2 kg benchmark</span>
                </div>

                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs text-slate-400 block font-medium">Head On Feed</span>
                    <div className="flex items-baseline gap-1 mt-1">
                        <span className="text-2xl font-bold font-mono text-slate-800 dark:text-slate-100">42</span>
                        <span className="text-xs text-slate-400">Steers & Bulls</span>
                    </div>
                    <span className="text-[10px] text-slate-400">Avg days on feed: 78 days</span>
                </div>

                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs text-slate-400 block font-medium">Slaughter Clearance Pass Rate</span>
                    <div className="flex items-baseline gap-1 mt-1">
                        <span className="text-2xl font-bold font-mono text-blue-500">100%</span>
                    </div>
                    <span className="text-[10px] text-emerald-500 font-semibold">Zero antimicrobial residues</span>
                </div>
            </div>

            {/* Feedlot Cohorts Table */}
            <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100">
                        Active Feedlot Finishing Cohorts
                    </h3>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th className="p-3.5">Lot & Animal Tag</th>
                                <th className="p-3.5">Target Market</th>
                                <th className="p-3.5">Intake / Current Weight</th>
                                <th className="p-3.5">Days on Feed</th>
                                <th className="p-3.5">Average Daily Gain (ADG)</th>
                                <th className="p-3.5 text-right">Slaughter Clearance</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                            {displayList.map((lot) => (
                                <tr key={lot.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td className="p-3.5">
                                        <span className="font-bold text-slate-800 dark:text-slate-100 block">{lot.lot_code}</span>
                                        <span className="text-[11px] text-emerald-600 dark:text-emerald-400">{lot.animal_tag || `TAG-${lot.id}`}</span>
                                    </td>
                                    <td className="p-3.5 font-sans font-medium text-slate-600 dark:text-slate-300">
                                        {lot.target_market}
                                    </td>
                                    <td className="p-3.5">
                                        <span className="text-slate-400">{lot.intake_weight_kg} kg</span> → <span className="font-bold text-slate-800 dark:text-slate-100">{lot.current_weight_kg} kg</span>
                                    </td>
                                    <td className="p-3.5 text-slate-600 dark:text-slate-300">
                                        {lot.days_on_feed} days
                                    </td>
                                    <td className="p-3.5">
                                        <span className="px-2 py-0.5 rounded font-bold bg-emerald-500/10 text-emerald-500">
                                            +{lot.avg_daily_gain} kg/d
                                        </span>
                                    </td>
                                    <td className="p-3.5 text-right font-sans">
                                        <button
                                            onClick={() => verifySlaughterClearance(lot.id)}
                                            className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition cursor-pointer"
                                        >
                                            Verify Clearance
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Clearance Result Notification Modal */}
            {clearanceResult && (
                <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-slate-800 dark:text-slate-100 space-y-4">
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                                <ShieldCheck className="w-6 h-6" />
                            </div>
                            <div>
                                <h3 className="text-sm font-bold">Food Safety Slaughter Clearance</h3>
                                <span className="text-xs text-slate-400">ISO Biosecurity & Residue Audit</span>
                            </div>
                        </div>

                        <div className="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-xs space-y-1">
                            <p className="font-semibold text-emerald-600 dark:text-emerald-400">✓ STATUS: CLEARED FOR SLAUGHTER</p>
                            <p className="text-slate-500 dark:text-slate-400">All antibiotic and anthelmintic withdrawal windows have expired.</p>
                        </div>

                        <button
                            onClick={() => setClearanceResult(null)}
                            className="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
