import React, { useState, useEffect } from 'react';
import { 
    Layers, 
    Compass, 
    Plus, 
    ArrowRightLeft, 
    CheckCircle, 
    Sun, 
    Droplets, 
    Activity 
} from 'lucide-react';
import { api } from '../services/api';

export default function PastureView() {
    const [paddocks, setPaddocks] = useState([]);
    const [loading, setLoading] = useState(false);
    const [showPaddockModal, setShowPaddockModal] = useState(false);
    const [newPaddock, setNewPaddock] = useState({ name: '', area_hectares: '', forage_type: 'Alfalfa / Clover', target_biomass: 2400 });

    useEffect(() => {
        loadPaddocks();
    }, []);

    async function loadPaddocks() {
        setLoading(true);
        try {
            const res = await api.getPaddocks();
            setPaddocks(res.data || res || []);
        } catch (err) {
            console.error('Failed to load paddocks:', err);
        } finally {
            setLoading(false);
        }
    }

    async function handleCreatePaddock(e) {
        e.preventDefault();
        try {
            await api.createPaddock({
                name: newPaddock.name,
                code: `PAD-${Date.now().toString().slice(-4)}`,
                area_hectares: parseFloat(newPaddock.area_hectares || 2.5),
                forage_type: newPaddock.forage_type,
                target_biomass_kg_per_ha: parseFloat(newPaddock.target_biomass || 2400),
                current_biomass_kg_per_ha: parseFloat(newPaddock.target_biomass || 2400),
                status: 'resting',
            });
            setShowPaddockModal(false);
            setNewPaddock({ name: '', area_hectares: '', forage_type: 'Alfalfa / Clover', target_biomass: 2400 });
            await loadPaddocks();
        } catch (err) {
            alert('Failed to create paddock: ' + err.message);
        }
    }

    const defaultPaddocks = [
        { id: 1, name: 'Paddock Alpha (Alfalfa)', code: 'PAD-01', area_hectares: 3.5, current_biomass: 2450, target_biomass: 2500, status: 'grazing', days_grazing: 4, recovery_days_remaining: 0 },
        { id: 2, name: 'Paddock Beta (Ryegrass)', code: 'PAD-02', area_hectares: 2.8, current_biomass: 1980, target_biomass: 2200, status: 'resting', days_grazing: 0, recovery_days_remaining: 12 },
        { id: 3, name: 'Paddock Gamma (Bermuda Grass)', code: 'PAD-03', area_hectares: 4.0, current_biomass: 2600, target_biomass: 2400, status: 'ready', days_grazing: 0, recovery_days_remaining: 0 },
    ];

    const displayList = paddocks.length > 0 ? paddocks : defaultPaddocks;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Compass className="w-6 h-6 text-emerald-500" />
                        Rotational Pastures & Grazing Paddocks
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Track forage biomass (kg DM/ha), rotational grazing rest periods, and stocking densities
                    </p>
                </div>
                <button
                    onClick={() => setShowPaddockModal(true)}
                    className="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider shadow-sm transition"
                >
                    <Plus className="w-4 h-4" />
                    <span>Create Paddock</span>
                </button>
            </div>

            {/* Paddock Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {displayList.map((paddock) => {
                    const biomass = paddock.current_biomass || paddock.current_biomass_kg_per_ha || 2200;
                    const target = paddock.target_biomass || paddock.target_biomass_kg_per_ha || 2400;
                    const percent = Math.min(100, Math.round((biomass / target) * 100));

                    return (
                        <div 
                            key={paddock.id}
                            className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 hover:border-emerald-500/40 transition"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                                        {paddock.code || `PAD-${paddock.id}`}
                                    </span>
                                    <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 mt-1">
                                        {paddock.name}
                                    </h3>
                                    <span className="text-xs text-slate-400">
                                        {paddock.area_hectares || 2.5} Hectares • {paddock.forage_type || 'Alfalfa & Grass'}
                                    </span>
                                </div>
                                <span className={`text-[10px] px-2.5 py-1 rounded-full font-bold uppercase tracking-wider ${
                                    paddock.status === 'grazing' 
                                        ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' 
                                        : paddock.status === 'ready' 
                                        ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' 
                                        : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                                }`}>
                                    {paddock.status}
                                </span>
                            </div>

                            {/* Biomass Gauge */}
                            <div>
                                <div className="flex justify-between text-xs mb-1.5">
                                    <span className="text-slate-400">Current Biomass:</span>
                                    <span className="font-mono font-bold text-slate-700 dark:text-slate-200">
                                        {biomass} / {target} kg DM/ha
                                    </span>
                                </div>
                                <div className="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div 
                                        className={`h-full rounded-full transition-all duration-500 ${percent > 85 ? 'bg-emerald-500' : percent > 60 ? 'bg-amber-500' : 'bg-rose-500'}`}
                                        style={{ width: `${percent}%` }}
                                    ></div>
                                </div>
                            </div>

                            <div className="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                <span className="text-slate-400">
                                    {paddock.status === 'resting' ? `${paddock.recovery_days_remaining || 14} days rest left` : 'Active grazing cohort'}
                                </span>
                                <button
                                    onClick={() => alert(`Rotational grazing transfer initiated for ${paddock.name}`)}
                                    className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 hover:underline font-semibold"
                                >
                                    <ArrowRightLeft className="w-3.5 h-3.5" />
                                    <span>Transfer Herd</span>
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Create Paddock Modal */}
            {showPaddockModal && (
                <div className="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
                    <form onSubmit={handleCreatePaddock} className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-slate-800 dark:text-slate-100 space-y-4">
                        <h3 className="text-base font-bold text-slate-800 dark:text-white">Create New Grazing Paddock</h3>
                        <div>
                            <label className="text-xs font-semibold text-slate-400 block mb-1">Paddock Name</label>
                            <input
                                type="text"
                                required
                                value={newPaddock.name}
                                onChange={(e) => setNewPaddock({ ...newPaddock, name: e.target.value })}
                                placeholder="E.g., North Hill Pasture"
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-xs font-semibold text-slate-400 block mb-1">Area (Hectares)</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    required
                                    value={newPaddock.area_hectares}
                                    onChange={(e) => setNewPaddock({ ...newPaddock, area_hectares: e.target.value })}
                                    placeholder="2.5"
                                    className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                                />
                            </div>
                            <div>
                                <label className="text-xs font-semibold text-slate-400 block mb-1">Target Biomass</label>
                                <input
                                    type="number"
                                    required
                                    value={newPaddock.target_biomass}
                                    onChange={(e) => setNewPaddock({ ...newPaddock, target_biomass: e.target.value })}
                                    placeholder="2400"
                                    className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                                />
                            </div>
                        </div>
                        <div className="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={() => setShowPaddockModal(false)}
                                className="px-4 py-2 text-xs font-semibold rounded-xl text-slate-400 hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold"
                            >
                                Save Paddock
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}
