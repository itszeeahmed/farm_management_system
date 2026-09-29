import React, { useState, useEffect } from 'react';
import { X, ShieldAlert, CheckCircle, AlertTriangle, Plus } from 'lucide-react';
import { api } from '../services/api';

export function RegisterAnimalModal({ isOpen, onClose, onCreated }) {
    const [tagNumber, setTagNumber] = useState('');
    const [name, setName] = useState('');
    const [speciesId, setSpeciesId] = useState('1'); // 1 = Cattle, 2 = Goat
    const [breedId, setBreedId] = useState('1');
    const [sex, setSex] = useState('female');
    const [status, setStatus] = useState('lactating');
    const [birthDate, setBirthDate] = useState('2023-01-15');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    if (!isOpen) return null;

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            await api.createAnimal({
                tag_number: tagNumber,
                name,
                species_id: parseInt(speciesId),
                breed_id: parseInt(breedId),
                sex,
                status,
                date_of_birth: birthDate,
            });
            onCreated();
            onClose();
        } catch (err) {
            setError(err.message || 'Failed to register animal');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
                <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 className="font-bold text-base text-slate-900 dark:text-white">Register New Animal</h3>
                    <button onClick={onClose} className="p-1 rounded-lg text-slate-400 hover:text-white"><X className="w-5 h-5" /></button>
                </div>

                {error && <div className="p-2.5 rounded-lg bg-rose-500/10 text-rose-500 text-xs">{error}</div>}

                <form onSubmit={handleSubmit} className="space-y-3 text-xs">
                    <div>
                        <label className="text-slate-500 block mb-1">Official Tag Number *</label>
                        <input
                            type="text"
                            required
                            placeholder="e.g. PK-COW-006"
                            value={tagNumber}
                            onChange={e => setTagNumber(e.target.value)}
                            className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-slate-500 block mb-1">Species</label>
                            <select
                                value={speciesId}
                                onChange={e => setSpeciesId(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option value="1">Cattle / Cow</option>
                                <option value="2">Goat / Caprine</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-slate-500 block mb-1">Name / Alias</label>
                            <input
                                type="text"
                                placeholder="e.g. Pari"
                                value={name}
                                onChange={e => setName(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-slate-500 block mb-1">Sex</label>
                            <select
                                value={sex}
                                onChange={e => setSex(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-slate-500 block mb-1">Lifecycle Status</label>
                            <select
                                value={status}
                                onChange={e => setStatus(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option value="lactating">Lactating</option>
                                <option value="pregnant">Pregnant</option>
                                <option value="dry">Dry</option>
                                <option value="sick">Sick</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="text-slate-500 block mb-1">Date of Birth</label>
                        <input
                            type="date"
                            value={birthDate}
                            onChange={e => setBirthDate(e.target.value)}
                            className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                        />
                    </div>

                    <div className="pt-3 flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" disabled={loading} className="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold flex items-center gap-1.5">
                            {loading ? 'Registering...' : 'Save Animal'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export function LogMilkModal({ isOpen, onClose, onLogged, animals = [], activeWithdrawals = [] }) {
    const [animalId, setAnimalId] = useState(animals[0]?.id || '1');
    const [yieldLiters, setYieldLiters] = useState('14.5');
    const [session, setSession] = useState('morning');
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (animals.length > 0 && !animalId) {
            setAnimalId(animals[0].id);
        }
    }, [animals]);

    if (!isOpen) return null;

    const selectedWithdrawal = activeWithdrawals.find(w => w.animal_id === parseInt(animalId));

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            await api.createMilkRecord({
                animal_id: parseInt(animalId),
                recorded_date: date,
                session,
                yield_liters: parseFloat(yieldLiters),
            });
            onLogged();
            onClose();
        } catch (err) {
            setError(err.message || 'Failed to record milk');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4 animate-in fade-in zoom-in-95">
                <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 className="font-bold text-base text-slate-900 dark:text-white">Record Milking Yield</h3>
                    <button onClick={onClose} className="p-1 rounded-lg text-slate-400 hover:text-white"><X className="w-5 h-5" /></button>
                </div>

                {selectedWithdrawal && (
                    <div className="p-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs flex items-start gap-2">
                        <ShieldAlert className="w-4 h-4 shrink-0 mt-0.5 animate-pulse" />
                        <div>
                            <strong>FOOD SAFETY LOCK:</strong> This animal has an active antibiotic withdrawal until {new Date(selectedWithdrawal.withdrawal_until).toLocaleString()}. Yield will be logged as <strong>discarded_withdrawal</strong>.
                        </div>
                    </div>
                )}

                {error && <div className="p-2.5 rounded-lg bg-rose-500/10 text-rose-500 text-xs">{error}</div>}

                <form onSubmit={handleSubmit} className="space-y-3 text-xs">
                    <div>
                        <label className="text-slate-500 block mb-1">Select Animal *</label>
                        <select
                            value={animalId}
                            onChange={e => setAnimalId(e.target.value)}
                            className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                        >
                            {animals.map(a => (
                                <option key={a.id} value={a.id}>
                                    {a.tag_number} - {a.name || a.species?.name} ({a.status})
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label className="text-slate-500 block mb-1">Milking Session</label>
                            <select
                                value={session}
                                onChange={e => setSession(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option value="morning">Morning</option>
                                <option value="evening">Evening</option>
                                <option value="noon">Noon</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-slate-500 block mb-1">Yield (Liters) *</label>
                            <input
                                type="number"
                                step="0.1"
                                required
                                value={yieldLiters}
                                onChange={e => setYieldLiters(e.target.value)}
                                className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono text-base font-bold"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-slate-500 block mb-1">Recording Date</label>
                        <input
                            type="date"
                            value={date}
                            onChange={e => setDate(e.target.value)}
                            className="w-full p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                        />
                    </div>

                    <div className="pt-3 flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit" disabled={loading} className="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold">
                            {loading ? 'Saving...' : 'Save Milk Record'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
