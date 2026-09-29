import React, { useState, useEffect } from 'react';
import { 
    HeartPulse, 
    ShieldAlert, 
    Pill, 
    Clock, 
    AlertTriangle, 
    CheckCircle2, 
    Plus,
    Calendar
} from 'lucide-react';
import { api } from '../services/api';

export default function HealthView({ onOpenTreatmentModal }) {
    const [healthData, setHealthData] = useState(null);
    const [medicines, setMedicines] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadData();
    }, []);

    async function loadData() {
        setLoading(true);
        try {
            const [hRes, mRes] = await Promise.all([
                api.getHealth(),
                api.getMedicines()
            ]);
            setHealthData(hRes.data || {});
            setMedicines(mRes.data || []);
        } catch (err) {
            console.error('Failed to load health data:', err);
        } finally {
            setLoading(false);
        }
    }

    const cases = healthData?.cases || [];
    const treatments = healthData?.recent_treatments || [];
    const withdrawals = healthData?.active_withdrawals || [];

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <HeartPulse className="w-5 h-5 text-rose-500" />
                        Veterinary Health & Antimicrobial Stewardship (AMU)
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Diagnostics, medical treatments, antibiotic tracking, and food safety withdrawal countdowns
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onOpenTreatmentModal}
                        className="px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" /> Record Treatment
                    </button>
                </div>
            </div>

            {/* CRITICAL WITHDRAWAL MONITOR */}
            <div className="p-6 rounded-2xl bg-gradient-to-br from-rose-950/30 to-slate-900 border border-rose-500/40 shadow-sm space-y-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2.5">
                        <ShieldAlert className="w-6 h-6 text-rose-500 animate-pulse" />
                        <div>
                            <h4 className="font-bold text-base text-rose-300">Antimicrobial Withdrawal Monitor</h4>
                            <p className="text-xs text-slate-400">Automatic exclusion from milking pipeline to prevent drug residue contamination</p>
                        </div>
                    </div>
                    <span className="text-xs font-mono font-bold px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30">
                        {withdrawals.length} Animal(s) Restricted
                    </span>
                </div>

                {withdrawals.length === 0 ? (
                    <div className="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4" /> All animals cleared. Zero active medicine withdrawal locks in place.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {withdrawals.map((item) => (
                            <div key={item.id} className="p-4 rounded-xl bg-slate-900/80 border border-rose-500/30 space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="font-mono font-bold text-sm text-white">
                                        {item.animal?.tag_number} ({item.animal?.name || 'Cattle'})
                                    </span>
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-rose-500 text-white animate-pulse">
                                        Milk Locked
                                    </span>
                                </div>
                                <div className="text-xs space-y-1 text-slate-300">
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Medicine Administered:</span>
                                        <span className="font-semibold text-rose-300">{item.medicine?.name}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Withdrawal Period Expiry:</span>
                                        <span className="font-mono text-amber-400">{new Date(item.milk_withdrawal_until).toLocaleString()}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-slate-400">Dosage Given:</span>
                                        <span>{item.dosage}</span>
                                    </div>
                                </div>
                                <div className="p-2.5 rounded-lg bg-rose-950/40 border border-rose-500/20 text-[11px] text-rose-200">
                                    ⚠️ <strong>Warning:</strong> Any milk collected from this animal must be discarded and recorded under "discarded_withdrawal".
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* 2-Column: Clinical Cases & Medicine Inventory */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Active Cases */}
                <div className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <h4 className="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <HeartPulse className="w-4 h-4 text-emerald-500" />
                        Clinical Health Cases
                    </h4>

                    {cases.length === 0 ? (
                        <p className="text-xs text-slate-400 py-4">No active veterinary cases reported.</p>
                    ) : (
                        <div className="space-y-3">
                            {cases.map((c) => (
                                <div key={c.id} className="p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 space-y-2">
                                    <div className="flex items-center justify-between">
                                        <span className="font-mono font-bold text-xs text-slate-900 dark:text-white">
                                            {c.animal?.tag_number} • {c.disease_or_condition}
                                        </span>
                                        <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                            {c.status}
                                        </span>
                                    </div>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        {c.symptoms || 'Veterinarian diagnostic review in progress.'}
                                    </p>
                                    <span className="text-[10px] font-mono text-slate-400 block">
                                        Detected on: {c.detected_at}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Medicine Formulary */}
                <div className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <h4 className="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <Pill className="w-4 h-4 text-amber-500" />
                        Medicine Formulary & Withdrawal Rules
                    </h4>

                    <div className="space-y-2.5 overflow-y-auto max-h-[360px]">
                        {medicines.map((m) => (
                            <div key={m.id} className="p-3 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <div>
                                    <span className="font-bold text-slate-900 dark:text-white block">{m.name}</span>
                                    <span className="text-[11px] text-slate-400">{m.active_ingredient || m.category}</span>
                                </div>
                                <div className="text-right">
                                    <span className="font-mono text-rose-500 font-bold block">{m.milk_withdrawal_days} Days Milk</span>
                                    <span className="text-[11px] text-slate-400">{m.meat_withdrawal_days} Days Meat</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
