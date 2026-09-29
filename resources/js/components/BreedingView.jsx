import React, { useState, useEffect } from 'react';
import { 
    GitBranch, 
    Baby, 
    Calendar, 
    Clock, 
    CheckCircle2, 
    Plus,
    AlertCircle
} from 'lucide-react';
import { api } from '../services/api';

export default function BreedingView({ onOpenBreedingModal }) {
    const [records, setRecords] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, []);

    async function loadData() {
        setLoading(true);
        try {
            const res = await api.getBreeding();
            setRecords(res.data || []);
        } catch (err) {
            console.error('Failed to load breeding records:', err);
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
                        <GitBranch className="w-5 h-5 text-indigo-500" />
                        Breeding, Reproduction & Gestation
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Insemination logs, pregnancy diagnosis (PD), expected calving calendars, and semen traceability
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onOpenBreedingModal}
                        className="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" /> Record Insemination / Event
                    </button>
                </div>
            </div>

            {/* Reproduction Pipeline Summary */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inseminations</span>
                    <span className="text-2xl font-bold font-mono text-slate-900 dark:text-white mt-1 block">
                        {records.length} Recorded
                    </span>
                    <span className="text-xs text-indigo-500 mt-1 block">AI & Natural service</span>
                </div>
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Confirmed Pregnant</span>
                    <span className="text-2xl font-bold font-mono text-emerald-500 mt-1 block">
                        {records.filter(r => r.status === 'confirmed_pregnant').length} Dams
                    </span>
                    <span className="text-xs text-slate-400 mt-1 block">High conception rate</span>
                </div>
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending PD Check</span>
                    <span className="text-2xl font-bold font-mono text-amber-500 mt-1 block">
                        {records.filter(r => r.status === 'inseminated').length} Dams
                    </span>
                    <span className="text-xs text-slate-400 mt-1 block">60-day ultrasound</span>
                </div>
                <div className="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Next Due Date</span>
                    <span className="text-sm font-bold font-mono text-slate-900 dark:text-white mt-1 block">
                        Oct 08, 2026
                    </span>
                    <span className="text-xs text-slate-400 mt-1 block">PK-COW-004 (Malka)</span>
                </div>
            </div>

            {/* Insemination & Calving Calendar Table */}
            <div className="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h4 className="font-bold text-xs uppercase tracking-wider text-slate-500">Breeding Records & Gestation Timeline</h4>
                </div>

                {loading ? (
                    <div className="p-8 text-center text-xs text-slate-400">Loading breeding data...</div>
                ) : records.length === 0 ? (
                    <div className="p-8 text-center text-xs text-slate-400">No breeding records found.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase font-mono tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Dam (Female)</th>
                                    <th className="py-3 px-4">Service Type</th>
                                    <th className="py-3 px-4">Sire / Straw Batch</th>
                                    <th className="py-3 px-4">Inseminated Date</th>
                                    <th className="py-3 px-4">Expected Birth</th>
                                    <th className="py-3 px-4">Reproductive Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {records.map((r) => (
                                    <tr key={r.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                        <td className="py-3 px-4">
                                            <span className="font-mono font-bold text-slate-900 dark:text-white">
                                                {r.animal?.tag_number}
                                            </span>
                                            <span className="text-[11px] text-slate-400 block">{r.animal?.name || 'Cattle'}</span>
                                        </td>
                                        <td className="py-3 px-4 uppercase text-[11px] font-semibold text-slate-600 dark:text-slate-300">
                                            {r.service_type}
                                        </td>
                                        <td className="py-3 px-4 font-mono text-slate-500">
                                            {r.sire_id_or_straw_code || 'Elite Sahiwal Bull Batch #04'}
                                        </td>
                                        <td className="py-3 px-4 font-mono text-slate-600 dark:text-slate-400">
                                            {r.insemination_date}
                                        </td>
                                        <td className="py-3 px-4 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                            {r.expected_calving_date || 'Pending PD'}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                                {r.status?.replace('_', ' ')}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}
