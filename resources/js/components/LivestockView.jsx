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
    X,
    QrCode,
    FileText
} from 'lucide-react';
import { api } from '../services/api';
import AnimalPassportDrawer from './AnimalPassportDrawer';

export default function LivestockView({ onOpenRegisterModal, hasPermission }) {
    const canCreate = hasPermission ? hasPermission('animals.create') : true;
    const canDelete = hasPermission ? hasPermission('animals.delete') : true;
    const [animals, setAnimals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [speciesFilter, setSpeciesFilter] = useState('all');
    const [statusFilter, setStatusFilter] = useState('all');
    const [selectedPassportAnimalId, setSelectedPassportAnimalId] = useState(null);

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
                              (a.rfid_tag && a.rfid_tag.toLowerCase().includes(searchTerm.toLowerCase())) ||
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
                        Livestock Registry & Pedigree Passports
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Official inventory of registered dairy cows, buffaloes, and breeding goats with pedigree & traceability
                    </p>
                </div>

                <div className="flex items-center gap-2 flex-wrap">
                    {/* Search */}
                    <div className="relative">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            placeholder="Tag, RFID, breed..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="pl-9 pr-4 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    {/* Species */}
                    <select
                        value={speciesFilter}
                        onChange={(e) => setSpeciesFilter(e.target.value)}
                        className="px-3 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option value="all">All Species</option>
                        <option value="cattle">Cattle</option>
                        <option value="buffalo">Buffalo</option>
                        <option value="goat">Goat</option>
                        <option value="sheep">Sheep</option>
                    </select>

                    {/* Lifecycle Status */}
                    <select
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="px-3 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500"
                    >
                        <option value="all">All Lifecycle States</option>
                        <option value="lactating">In Milk / Lactating</option>
                        <option value="pregnant">Pregnant</option>
                        <option value="sick">Sick / In Treatment</option>
                        <option value="dry">Dry</option>
                    </select>

                    {canCreate && (
                        <button
                            onClick={onOpenRegisterModal}
                            className="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm cursor-pointer"
                        >
                            <Plus className="w-4 h-4" /> Register Animal
                        </button>
                    )}
                </div>
            </div>

            {/* Animals Table */}
            <div className="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                {loading ? (
                    <div className="p-8 text-center text-xs text-slate-400">Loading animals from database...</div>
                ) : filteredAnimals.length === 0 ? (
                    <div className="p-8 text-center text-xs text-slate-400">No animals found matching filters.</div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase font-mono tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3 px-4">Tag Number & RFID</th>
                                    <th className="py-3 px-4">Name / Alias</th>
                                    <th className="py-3 px-4">Species & Breed</th>
                                    <th className="py-3 px-4">Sex / DOB</th>
                                    <th className="py-3 px-4">Lifecycle Status</th>
                                    <th className="py-3 px-4">Lactation Stage</th>
                                    <th className="py-3 px-4 text-right">Digital Passport</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {filteredAnimals.map((animal) => (
                                    <tr 
                                        key={animal.id}
                                        onClick={() => setSelectedPassportAnimalId(animal.id)}
                                        className="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition cursor-pointer group"
                                    >
                                        <td className="py-3 px-4 font-mono font-bold text-slate-900 dark:text-white">
                                            <div className="flex items-center gap-2">
                                                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                <span>{animal.tag_number}</span>
                                            </div>
                                            <span className="text-[10px] font-normal text-slate-400 block pl-4">
                                                RFID: {animal.rfid_tag || '982000341234567'}
                                            </span>
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
                                            <div className="flex items-center justify-end gap-1.5">
                                                <button 
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setSelectedPassportAnimalId(animal.id);
                                                    }}
                                                    className="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 text-[11px] font-semibold flex items-center gap-1 hover:bg-emerald-600 hover:text-white transition cursor-pointer"
                                                >
                                                    <FileText className="w-3.5 h-3.5" />
                                                    <span>Passport</span>
                                                </button>
                                                {canDelete && (
                                                    <button
                                                        onClick={async (e) => {
                                                            e.stopPropagation();
                                                            if (confirm(`Are you sure you want to remove/cull animal ${animal.tag_number}?`)) {
                                                                await api.deleteAnimal(animal.id);
                                                                loadAnimals();
                                                            }
                                                        }}
                                                        className="px-2 py-1 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-[11px] font-semibold border border-transparent hover:border-rose-200 dark:hover:border-rose-800 transition"
                                                        title="Cull / Remove Animal"
                                                    >
                                                        Cull
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Digital Animal Passport Slide-Over Drawer */}
            <AnimalPassportDrawer
                animalId={selectedPassportAnimalId}
                isOpen={Boolean(selectedPassportAnimalId)}
                onClose={() => setSelectedPassportAnimalId(null)}
                onActionSuccess={loadAnimals}
            />
        </div>
    );
}
