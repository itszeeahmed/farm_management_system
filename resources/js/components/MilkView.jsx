import React, { useState, useEffect } from 'react';
import { 
    Milk, 
    Plus, 
    Filter, 
    ShieldAlert, 
    CheckCircle, 
    Sun, 
    Moon, 
    Calendar,
    AlertTriangle
} from 'lucide-react';
import { api } from '../services/api';

export default function MilkView({ onOpenLogMilkModal }) {
    const [records, setRecords] = useState([]);
    const [summary, setSummary] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadMilkData();
    }, []);

    async function loadMilkData() {
        setLoading(true);
        try {
            const [recRes, sumRes] = await Promise.all([
                api.getMilkRecords(),
                api.getDashboard()
            ]);
            setRecords(recRes.data || []);
            setSummary(sumRes.milk || null);
        } catch (err) {
            console.error('Failed to load milk records:', err);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            {/* Header & Metric Cards */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <Milk className="w-5 h-5 text-amber-500" />
                        Dairy & Milk Production
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Daily milk yield recording with strict antibiotic withdrawal exclusions & quality checks
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onOpenLogMilkModal}
                        className="px-3.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" /> Record Milking Session
                    </button>
                </div>
            </div>

            {/* Milk Yield Summary Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Total</span>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                            {summary?.today_liters || 0}
                        </span>
                        <span className="text-xs text-slate-500">Liters</span>
                    </div>
                    <span className="text-xs text-emerald-500 mt-2 block font-medium">Safe for commercial sale</span>
                </div>

                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Yesterday's Total</span>
                    <div className="mt-2 flex items-baseline gap-2">
                        <span className="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                            {summary?.yesterday_liters || 0}
                        </span>
                        <span className="text-xs text-slate-500">Liters</span>
                    </div>
                    <span className="text-xs text-slate-400 mt-2 block font-medium">Cattle + Goat combined</span>
                </div>

                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Food Safety Protocol</span>
                    <div className="mt-2 flex items-center gap-2">
                        <ShieldAlert className="w-6 h-6 text-rose-500 shrink-0" />
                        <div>
                            <span className="text-sm font-bold text-rose-500 block">Enforced Lock</span>
                            <span className="text-[11px] text-slate-400">Withdrawal milk discarded automatically</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Milk Records Table */}
            <div className="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h4 className="font-bold text-xs uppercase tracking-wider text-slate-500">Recent Individual Milk Records</h4>
                    <span className="text-xs text-slate-400">{records.length} records in system</span>
                </div>

                {loading ? (
                    <div className="p-8 text-center text-xs text-slate-400">Loading milk logs...</div>
                ) : records.length === 0 ? (
                    <div className="p-8 text-center text-xs text-slate-400">No milk records logged yet.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase font-mono tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Date</th>
                                    <th className="py-3 px-4">Session</th>
                                    <th className="py-3 px-4">Animal Tag & Name</th>
                                    <th className="py-3 px-4">Species</th>
                                    <th className="py-3 px-4">Yield (Liters)</th>
                                    <th className="py-3 px-4">Fat / Protein</th>
                                    <th className="py-3 px-4">Quality Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {records.map((rec) => {
                                    const isWithdrawal = rec.quality_status === 'discarded_withdrawal';
                                    return (
                                        <tr key={rec.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                            <td className="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">
                                                {rec.recorded_date}
                                            </td>
                                            <td className="py-3 px-4 font-semibold capitalize flex items-center gap-1.5">
                                                {(() => {
                                                    const sessionLabel = typeof rec.session === 'object' && rec.session !== null
                                                        ? (rec.session.shift || 'Session')
                                                        : (rec.session || rec.shift || 'Morning');
                                                    const isMorning = String(sessionLabel).toLowerCase().includes('morn');
                                                    return (
                                                        <>
                                                            {isMorning ? (
                                                                <Sun className="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                                            ) : (
                                                                <Moon className="w-3.5 h-3.5 text-indigo-400 shrink-0" />
                                                            )}
                                                            <span>{sessionLabel}</span>
                                                        </>
                                                    );
                                                })()}
                                            </td>
                                            <td className="py-3 px-4">
                                                <span className="font-mono font-bold text-slate-900 dark:text-white">
                                                    {rec.animal?.tag_number}
                                                </span>
                                                <span className="block text-[11px] text-slate-400">
                                                    {rec.animal?.name || '—'}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-slate-500 capitalize">
                                                {rec.animal?.species?.name || 'Cattle'}
                                            </td>
                                            <td className="py-3 px-4 font-mono font-bold text-base text-slate-900 dark:text-white">
                                                {rec.yield_liters} L
                                            </td>
                                            <td className="py-3 px-4 font-mono text-slate-500">
                                                {rec.fat_percentage ? `${rec.fat_percentage}% / ${rec.protein_percentage || 0}%` : 'Standard'}
                                            </td>
                                            <td className="py-3 px-4">
                                                {isWithdrawal ? (
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/20 text-rose-500 border border-rose-500/30 flex items-center gap-1 w-fit animate-pulse">
                                                        <AlertTriangle className="w-3 h-3" /> Discarded (Withdrawal)
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center gap-1 w-fit">
                                                        <CheckCircle className="w-3 h-3" /> Approved / Good
                                                    </span>
                                                )}
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
