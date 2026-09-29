import React from 'react';
import { 
    Thermometer, 
    ShieldAlert, 
    PlusCircle, 
    RefreshCw, 
    Moon, 
    Sun,
    Bell
} from 'lucide-react';

export default function Header({ 
    farm, 
    climate, 
    activeWithdrawals, 
    onRefresh, 
    isRefreshing, 
    isDark, 
    setIsDark,
    openModal 
}) {
    const thiLevelColors = {
        normal: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        mild_stress: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        moderate_stress: 'bg-orange-500/15 text-orange-400 border-orange-500/40 animate-pulse',
        severe_stress: 'bg-rose-500/20 text-rose-400 border-rose-500/50 animate-bounce',
    };

    const thiColor = climate?.heat_stress_level ? thiLevelColors[climate.heat_stress_level] || 'bg-slate-800 text-slate-300' : 'bg-slate-800 text-slate-300';

    return (
        <header className="h-16 px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0 shadow-sm">
            {/* Left: Location & Farm Breadcrumb */}
            <div className="flex items-center gap-3">
                <span className="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {farm?.code || 'ALF-FARM-01'}
                </span>
                <h2 className="text-sm font-semibold text-slate-800 dark:text-slate-100 hidden sm:inline">
                    {farm?.name || 'Al-Falah Pilot Dairy & Goat Farm'}
                </h2>
                <span className="text-xs text-slate-400 hidden md:inline">
                    • {farm?.location || 'Kasur Road, Punjab, Pakistan'}
                </span>
            </div>

            {/* Right: Telemetry & Actions */}
            <div className="flex items-center gap-3">
                {/* Live Climate / THI Badge */}
                {climate && (
                    <div className={`px-3 py-1.5 rounded-lg border text-xs font-medium flex items-center gap-2 ${thiColor}`}>
                        <Thermometer className="w-3.5 h-3.5" />
                        <span className="hidden sm:inline">{climate.temperature_c}°C ({climate.relative_humidity_percent}% RH)</span>
                        <span className="font-mono font-bold">THI {climate.thi_index}</span>
                        <span className="hidden md:inline uppercase text-[10px] tracking-wider px-1.5 py-0.5 rounded bg-black/20">
                            {climate.heat_stress_level?.replace('_', ' ')}
                        </span>
                    </div>
                )}

                {/* Food Safety / Withdrawal Alert Pill */}
                {activeWithdrawals && activeWithdrawals.length > 0 && (
                    <button 
                        onClick={() => openModal('withdrawals')}
                        className="px-3 py-1.5 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-500 dark:text-rose-400 text-xs font-semibold flex items-center gap-1.5 hover:bg-rose-500/25 transition cursor-pointer"
                        title="Antimicrobial Withdrawal Period in effect"
                    >
                        <ShieldAlert className="w-3.5 h-3.5 text-rose-500 animate-pulse" />
                        <span>{activeWithdrawals.length} Withdrawal Alert</span>
                    </button>
                )}

                {/* Refresh Button */}
                <button
                    onClick={onRefresh}
                    disabled={isRefreshing}
                    className="p-2 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    title="Refresh Data"
                >
                    <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin text-emerald-500' : ''}`} />
                </button>

                {/* Theme Toggle */}
                <button
                    onClick={() => setIsDark(!isDark)}
                    className="p-2 rounded-lg text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    title="Toggle Dark/Light Mode"
                >
                    {isDark ? <Sun className="w-4 h-4 text-amber-400" /> : <Moon className="w-4 h-4 text-slate-600" />}
                </button>

                {/* Quick Action Button */}
                <button
                    onClick={() => openModal('quickAction')}
                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition"
                >
                    <PlusCircle className="w-4 h-4" />
                    <span className="hidden sm:inline">Quick Action</span>
                </button>
            </div>
        </header>
    );
}
