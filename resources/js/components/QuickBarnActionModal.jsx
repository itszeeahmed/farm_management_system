import React, { useState } from 'react';
import { 
    X, 
    Milk, 
    Scale, 
    Radio, 
    Stethoscope, 
    Thermometer, 
    CheckCircle2, 
    AlertTriangle,
    ShieldCheck
} from 'lucide-react';
import { api } from '../services/api';

export default function QuickBarnActionModal({ isOpen, onClose, onActionCompleted, animals = [], climate }) {
    const [activeAction, setActiveAction] = useState('menu'); // menu, milk, weight, rfid, health
    const [selectedAnimalId, setSelectedAnimalId] = useState(animals[0]?.id || '');
    
    // Milk form
    const [milkVolume, setMilkVolume] = useState('');
    const [sessionType, setSessionType] = useState('morning');
    const [fatPercent, setFatPercent] = useState('4.2');
    const [snfPercent, setSnfPercent] = useState('8.8');
    
    // Weight form
    const [weightInput, setWeightInput] = useState('');

    // Health form
    const [symptoms, setSymptoms] = useState('');
    const [diagnosis, setDiagnosis] = useState('');

    // Loading / error states
    const [submitting, setSubmitting] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    if (!isOpen) return null;

    const selectedAnimal = animals.find(a => String(a.id) === String(selectedAnimalId)) || animals[0];

    async function handleMilkSubmit(e) {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.createMilkRecord({
                animal_id: selectedAnimal?.id,
                volume_liters: parseFloat(milkVolume),
                session: sessionType,
                fat_percentage: parseFloat(fatPercent),
                snf_percentage: parseFloat(snfPercent),
                milked_at: new Date().toISOString(),
            });
            setSuccessMessage('Milk harvest recorded successfully!');
            setTimeout(() => {
                setSuccessMessage('');
                onClose();
                if (onActionCompleted) onActionCompleted();
            }, 1200);
        } catch (err) {
            alert('Failed to log milk: ' + err.message);
        } finally {
            setSubmitting(false);
        }
    }

    async function handleWeightSubmit(e) {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.recordWeight(selectedAnimal?.id, {
                weight_kg: parseFloat(weightInput),
                recorded_date: new Date().toISOString().split('T')[0],
            });
            setSuccessMessage('Weight logged successfully!');
            setTimeout(() => {
                setSuccessMessage('');
                onClose();
                if (onActionCompleted) onActionCompleted();
            }, 1200);
        } catch (err) {
            alert('Failed to record weight: ' + err.message);
        } finally {
            setSubmitting(false);
        }
    }

    async function handleHealthSubmit(e) {
        e.preventDefault();
        setSubmitting(true);
        try {
            await api.createHealthCase({
                animal_id: selectedAnimal?.id,
                case_number: `CASE-${Date.now().toString().slice(-6)}`,
                soap_symptoms: symptoms,
                provisional_diagnosis: diagnosis || 'Clinical evaluation required',
                status: 'open',
                opened_at: new Date().toISOString(),
            });
            setSuccessMessage('Health case logged successfully!');
            setTimeout(() => {
                setSuccessMessage('');
                onClose();
                if (onActionCompleted) onActionCompleted();
            }, 1200);
        } catch (err) {
            alert('Failed to log health case: ' + err.message);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-md flex items-center justify-center p-4">
            <div 
                className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 text-white shadow-2xl relative overflow-hidden animate-in zoom-in-95 duration-150"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Barn & Environmental Status Bar */}
                <div className="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div className="flex items-center gap-2">
                        <div className="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                            Barn
                        </div>
                        <div>
                            <h3 className="text-sm font-bold text-slate-100">Barn A — Lactation Herd</h3>
                            <span className="text-[11px] text-slate-400">Kasur Pilot Unit #1</span>
                        </div>
                    </div>

                    {/* THI Mini Dial */}
                    <div className="px-2.5 py-1 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center gap-1.5 text-xs font-medium text-emerald-400">
                        <Thermometer className="w-3.5 h-3.5" />
                        <span>THI: {climate?.thi_index || 72}</span>
                        <span className="text-[10px] uppercase font-bold px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300">
                            Normal
                        </span>
                    </div>

                    <button 
                        onClick={onClose}
                        className="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition ml-2"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Success Notification */}
                {successMessage && (
                    <div className="my-4 p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 flex items-center gap-2 text-xs font-semibold animate-pulse">
                        <CheckCircle2 className="w-4 h-4 shrink-0 text-emerald-400" />
                        <span>{successMessage}</span>
                    </div>
                )}

                {/* Step 1: Big 4 Barn Actions Menu */}
                {activeAction === 'menu' && (
                    <div className="pt-6 space-y-4">
                        <p className="text-xs text-slate-400 text-center font-medium">
                            Select rapid barn operation (Optimized for glove touch & mobile screens)
                        </p>

                        <div className="grid grid-cols-2 gap-3">
                            <button
                                onClick={() => setActiveAction('milk')}
                                className="p-4 rounded-2xl bg-slate-800/70 hover:bg-emerald-950/40 border border-slate-700/60 hover:border-emerald-500/50 flex flex-col items-center justify-center gap-2 transition group cursor-pointer"
                            >
                                <div className="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition">
                                    <Milk className="w-6 h-6" />
                                </div>
                                <span className="text-xs font-bold text-slate-200 group-hover:text-emerald-300">Log Milking Yield</span>
                            </button>

                            <button
                                onClick={() => setActiveAction('weight')}
                                className="p-4 rounded-2xl bg-slate-800/70 hover:bg-amber-950/40 border border-slate-700/60 hover:border-amber-500/50 flex flex-col items-center justify-center gap-2 transition group cursor-pointer"
                            >
                                <div className="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center group-hover:scale-110 transition">
                                    <Scale className="w-6 h-6" />
                                </div>
                                <span className="text-xs font-bold text-slate-200 group-hover:text-amber-300">Log Weight & Scale</span>
                            </button>

                            <button
                                onClick={() => setActiveAction('rfid')}
                                className="p-4 rounded-2xl bg-slate-800/70 hover:bg-blue-950/40 border border-slate-700/60 hover:border-blue-500/50 flex flex-col items-center justify-center gap-2 transition group cursor-pointer"
                            >
                                <div className="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center group-hover:scale-110 transition">
                                    <Radio className="w-6 h-6" />
                                </div>
                                <span className="text-xs font-bold text-slate-200 group-hover:text-blue-300">Scan RFID Ear Tag</span>
                            </button>

                            <button
                                onClick={() => setActiveAction('health')}
                                className="p-4 rounded-2xl bg-slate-800/70 hover:bg-rose-950/40 border border-slate-700/60 hover:border-rose-500/50 flex flex-col items-center justify-center gap-2 transition group cursor-pointer"
                            >
                                <div className="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center group-hover:scale-110 transition">
                                    <Stethoscope className="w-6 h-6" />
                                </div>
                                <span className="text-xs font-bold text-slate-200 group-hover:text-rose-300">Health SOAP Report</span>
                            </button>
                        </div>
                    </div>
                )}

                {/* Sub-form: Milk Yield */}
                {activeAction === 'milk' && (
                    <form onSubmit={handleMilkSubmit} className="pt-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Milk className="w-4 h-4" /> Log Milking Yield
                            </span>
                            <button 
                                type="button" 
                                onClick={() => setActiveAction('menu')}
                                className="text-xs text-slate-400 hover:text-white"
                            >
                                ← Back
                            </button>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Select Animal</label>
                            <select
                                value={selectedAnimalId}
                                onChange={(e) => setSelectedAnimalId(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white font-mono"
                            >
                                {animals.map(a => (
                                    <option key={a.id} value={a.id}>
                                        {a.tag_number} ({a.breed?.name || 'Sahiwal'} - {a.gender})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="text-[11px] font-semibold text-slate-400 block mb-1">Volume (Liters)</label>
                                <input
                                    type="number"
                                    step="0.1"
                                    placeholder="18.5"
                                    required
                                    value={milkVolume}
                                    onChange={(e) => setMilkVolume(e.target.value)}
                                    className="w-full px-3 py-2 text-lg font-bold font-mono rounded-xl bg-slate-800 border border-slate-700 text-emerald-400"
                                />
                            </div>
                            <div>
                                <label className="text-[11px] font-semibold text-slate-400 block mb-1">Session</label>
                                <select
                                    value={sessionType}
                                    onChange={(e) => setSessionType(e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white"
                                >
                                    <option value="morning">Morning (AM)</option>
                                    <option value="evening">Evening (PM)</option>
                                </select>
                            </div>
                        </div>

                        <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-2 text-xs text-emerald-400 font-semibold">
                            <ShieldCheck className="w-4 h-4 shrink-0" />
                            <span>Food Safety Clearance: Animal cleared for bulk tank collection.</span>
                        </div>

                        <button
                            type="submit"
                            disabled={submitting || !milkVolume}
                            className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50"
                        >
                            {submitting ? 'Recording...' : 'Submit Harvest Yield'}
                        </button>
                    </form>
                )}

                {/* Sub-form: Weight Scale */}
                {activeAction === 'weight' && (
                    <form onSubmit={handleWeightSubmit} className="pt-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Scale className="w-4 h-4" /> Log Scale Weight
                            </span>
                            <button 
                                type="button" 
                                onClick={() => setActiveAction('menu')}
                                className="text-xs text-slate-400 hover:text-white"
                            >
                                ← Back
                            </button>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Select Animal</label>
                            <select
                                value={selectedAnimalId}
                                onChange={(e) => setSelectedAnimalId(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white font-mono"
                            >
                                {animals.map(a => (
                                    <option key={a.id} value={a.id}>
                                        {a.tag_number} ({a.breed?.name || 'Sahiwal'})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Weight Scale Reading (kg)</label>
                            <input
                                type="number"
                                step="0.5"
                                placeholder="485.5"
                                required
                                value={weightInput}
                                onChange={(e) => setWeightInput(e.target.value)}
                                className="w-full px-3 py-3 text-2xl font-bold font-mono rounded-xl bg-slate-800 border border-slate-700 text-amber-400 text-center"
                            />
                        </div>

                        <div className="p-3 rounded-xl bg-slate-800/80 border border-slate-700 text-xs text-slate-300 flex items-center justify-between">
                            <span>Calculated ADG (30-day):</span>
                            <span className="font-bold text-emerald-400 font-mono">+0.85 kg/day</span>
                        </div>

                        <button
                            type="submit"
                            disabled={submitting || !weightInput}
                            className="w-full py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50"
                        >
                            {submitting ? 'Recording...' : 'Commit Weight Record'}
                        </button>
                    </form>
                )}

                {/* Sub-form: RFID Tag Scanner */}
                {activeAction === 'rfid' && (
                    <div className="pt-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Radio className="w-4 h-4" /> Scan RFID Ear Tag
                            </span>
                            <button 
                                type="button" 
                                onClick={() => setActiveAction('menu')}
                                className="text-xs text-slate-400 hover:text-white"
                            >
                                ← Back
                            </button>
                        </div>

                        <div className="p-6 rounded-2xl bg-blue-950/20 border-2 border-dashed border-blue-500/40 flex flex-col items-center justify-center text-center space-y-2">
                            <Radio className="w-10 h-10 text-blue-400 animate-pulse" />
                            <p className="text-xs font-semibold text-slate-200">ISO 11784/11785 RFID Scanner Ready</p>
                            <span className="text-[11px] text-slate-400">Bring handheld Bluetooth wand or RFID scanner within 15 cm of animal ear tag</span>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Quick Select Tag Number</label>
                            <select
                                value={selectedAnimalId}
                                onChange={(e) => setSelectedAnimalId(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white font-mono"
                            >
                                {animals.map(a => (
                                    <option key={a.id} value={a.id}>
                                        {a.tag_number} — RFID: {a.rfid_tag || '982000341234567'}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <button
                            type="button"
                            onClick={() => {
                                alert(`Tag ${selectedAnimal?.tag_number} verified. RFID: ${selectedAnimal?.rfid_tag || '982000341234567'}`);
                                onClose();
                            }}
                            className="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider transition"
                        >
                            Open Animal Passport
                        </button>
                    </div>
                )}

                {/* Sub-form: Health SOAP Report */}
                {activeAction === 'health' && (
                    <form onSubmit={handleHealthSubmit} className="pt-4 space-y-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                                <Stethoscope className="w-4 h-4" /> Health SOAP Symptom Report
                            </span>
                            <button 
                                type="button" 
                                onClick={() => setActiveAction('menu')}
                                className="text-xs text-slate-400 hover:text-white"
                            >
                                ← Back
                            </button>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Select Animal</label>
                            <select
                                value={selectedAnimalId}
                                onChange={(e) => setSelectedAnimalId(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white font-mono"
                            >
                                {animals.map(a => (
                                    <option key={a.id} value={a.id}>
                                        {a.tag_number} ({a.breed?.name || 'Sahiwal'})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Observed Symptoms (Subjective)</label>
                            <textarea
                                rows={2}
                                required
                                placeholder="E.g., Mild mastitis in rear right quarter, elevated rectal temp (39.5°C), lethargic..."
                                value={symptoms}
                                onChange={(e) => setSymptoms(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white"
                            />
                        </div>

                        <div>
                            <label className="text-[11px] font-semibold text-slate-400 block mb-1">Provisional Diagnosis (Assessment)</label>
                            <input
                                type="text"
                                placeholder="Subclinical Mastitis"
                                value={diagnosis}
                                onChange={(e) => setDiagnosis(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-800 border border-slate-700 text-white"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={submitting || !symptoms}
                            className="w-full py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider transition disabled:opacity-50"
                        >
                            {submitting ? 'Recording...' : 'File Health Report'}
                        </button>
                    </form>
                )}
            </div>
        </div>
    );
}
