import React, { useState, useEffect } from 'react';
import { 
    Binary, 
    Search, 
    Filter, 
    Plus, 
    ChevronRight, 
    HeartPulse, 
    Baby, 
    Sparkles,
    CheckCircle,
    AlertCircle,
    X
} from 'lucide-react';
import { api } from '../services/api';

export default function LivestockView({ onOpenRegisterModal }) {
    const [animals, setAnimals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [speciesFilter, setSpeciesFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');
    const [selectedAnimal, setSelectedAnimal] = useState(null);

    useEffect(() => {
        loadAnimals();
    }, [speciesFilter, statusFilter]);

    async function loadAnimals() {
        setLoading(true);
        try {
            const query = new URLSearchParams();
            if (speciesFilter !== 'all') query.append('species', speciesFilter);
            if (statusFilter !== 'all') query.append('status', statusFilter);
            
            const res = await api.getAnimals(query.toString());
            setAnimals(res.data || []);
        } catch (err) {
            console.error('Failed to load animals:', err);
        } finally {
            setLoading(false);
        }
    }

    const filteredAnimals = animals.filter(a => {
        const matchesSearch = a.tag_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                              (a.name && a.name.toLowerCase().includes(searchTerm.toLowerCase())) ||
                              (a.breed?.name && a.breed.name.toLowerCase().includes(searchTerm.toLowerCase()));
        return matchesSearch;
    });

    const statusBadge = (status) => {
        const map = {
            lactating: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            pregnant: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            sick: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30 animate-pulse',
            dry: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
        };
        return (
            <span className={`text-[11px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded border ${map[status] || 'bg-slate-100 text-slate-600'}`}>
                {status}
            </span>
        );
    };

    return (
        <div className="space-y-6">
            {/* Header / Filter Toolbar */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <Binary className="w-5 h-5 text-emerald-500" />
                        Livestock Registry
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Official inventory of registered dairy cows and breeding goats with pedigree & traceability
                    </p>
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                    {/* Search box */}
                    <div className="relative">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                        <input
                            type="text"
                            placeholder="Search tag # or name..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    {/* Species Filter */}
                    <select
                        value={speciesFilter}
                        onChange={(e) => setSpeciesFilter(e.target.value)}
                        className="py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Species (15)</option>
                        <option value="cattle">Cattle (5)</option>
                        <option value="goat">Goat (10)</option>
                    </select>

                    {/* Status Filter */}
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="py-1.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Lifecycle States</option>
                        <option value="lactating">In Milk / Lactating</option>
                        <option value="pregnant">Pregnant</option>
                        <option value="sick">Sick / In Treatment</option>
                        <option value="dry">Dry</option>
                    </select>

                    <button
                        onClick={onOpenRegisterModal}
                        className="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" /> Register Animal
                    </button>
                </div>
            </div>

            {/* Animals Table */}
            <div className="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                {loading ? (
                    <div className="p-8 text-center text-xs text-slate-400">Loading animals...</div>
                ) : filteredAnimals.length === 0 ? (
                    <div className="p-8 text-center text-xs text-slate-400">No animals found matching filters.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase font-mono tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Tag Number</th>
                                    <th className="py-3 px-4">Name / Alias</th>
                                    <th className="py-3 px-4">Species & Breed</th>
                                    <th className="py-3 px-4">Sex / DOB</th>
                                    <th className="py-3 px-4">Lifecycle Status</th>
                                    <th className="py-3 px-4">Lactation Stage</th>
                                    <th className="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {filteredAnimals.map((animal) => (
                                    <tr 
                                        key={animal.id}
                                        onClick={() => setSelectedAnimal(animal)}
                                        className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition cursor-pointer group"
                                    >
                                        <td className="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            {animal.tag_number}
                                        </td>
                                        <td className="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                            {animal.name || '—'}
                                        </td>
                                        <td className="py-3 px-4">
                                            <span className="font-medium text-slate-700 dark:text-slate-300">
                                                {animal.species?.name}
                                            </span>
                                            <span className="text-slate-400 block text-[11px]">
                                                {animal.breed?.name || 'Mixed'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4 text-slate-500">
                                            <span className="capitalize">{animal.sex}</span>
                                            <span className="block text-[11px] font-mono text-slate-400">
                                                {animal.date_of_birth ? new Date(animal.date_of_birth).toLocaleDateString() : '—'}
                                            </span>
                                        </td>
                                        <td className="py-3 px-4">
                                            {statusBadge(animal.status)}
                                        </td>
                                        <td className="py-3 px-4 text-slate-600 dark:text-slate-400 font-mono text-[11px]">
                                            {animal.lactation_number ? `Lactation #${animal.lactation_number}` : 'N/A'}
                                        </td>
                                        <td className="py-3 px-4 text-right">
                                            <button className="p-1 rounded-lg text-slate-400 group-hover:text-emerald-500 group-hover:bg-emerald-50 dark:group-hover:bg-emerald-950/40 transition">
                                                <ChevronRight className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Animal Detail Slide-Over Modal */}
            {selectedAnimal && (
                <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="w-full max-w-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                        <div className="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div>
                                <span className="font-mono text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 font-bold">
                                    {selectedAnimal.tag_number}
                                </span>
                                <h3 className="text-lg font-bold text-slate-900 dark:text-white mt-1">
                                    {selectedAnimal.name || 'Unnamed Animal'}
                                </h3>
                            </div>
                            <button 
                                onClick={() => setSelectedAnimal(null)}
                                className="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Metadata Grid */}
                        <div className="grid grid-cols-2 gap-3 text-xs">
                            <div className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span className="text-slate-400 block">Species & Breed</span>
                                <span className="font-bold text-slate-900 dark:text-white mt-0.5 block">
                                    {selectedAnimal.species?.name} • {selectedAnimal.breed?.name}
                                </span>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span className="text-slate-400 block">Lifecycle Stage</span>
                                <span className="font-bold text-slate-900 dark:text-white mt-0.5 block capitalize">
                                    {selectedAnimal.status}
                                </span>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span className="text-slate-400 block">Sex & Calving Info</span>
                                <span className="font-bold text-slate-900 dark:text-white mt-0.5 block capitalize">
                                    {selectedAnimal.sex} • Lactation #{selectedAnimal.lactation_number || 0}
                                </span>
                            </div>
                            <div className="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                                <span className="text-slate-400 block">Date of Birth</span>
                                <span className="font-mono font-bold text-slate-900 dark:text-white mt-0.5 block">
                                    {selectedAnimal.date_of_birth || 'Recorded on farm'}
                                </span>
                            </div>
                        </div>

                        {/* Pedigree & Identification */}
                        <div className="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs space-y-2">
                            <h4 className="font-semibold text-slate-700 dark:text-slate-300">Identification & Pedigree</h4>
                            <div className="flex justify-between text-slate-500">
                                <span>Sire Tag (Father):</span>
                                <span className="font-mono font-semibold">{selectedAnimal.sire_tag || 'Purebred AI Bull'}</span>
                            </div>
                            <div className="flex justify-between text-slate-500">
                                <span>Dam Tag (Mother):</span>
                                <span className="font-mono font-semibold">{selectedAnimal.dam_tag || 'Dam Stock #01'}</span>
                            </div>
                        </div>

                        <div className="pt-2 flex justify-end gap-2">
                            <button
                                onClick={() => setSelectedAnimal(null)}
                                className="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-white text-xs font-semibold transition"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
