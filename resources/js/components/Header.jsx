import React, { useState, useRef, useEffect } from 'react';
import {
    ChevronDown,
    Sun,
    Moon,
    Settings,
    LogOut,
    User,
    Check,
    RefreshCw,
    ShieldAlert,
    Thermometer
} from 'lucide-react';

export default function Header({
    farm,
    farms = [],
    onSelectFarm,
    climate,
    activeWithdrawals = [],
    onRefresh,
    isRefreshing,
    isDark,
    setIsDark,
    user,
    preferences,
    onOpenPreferences,
    onOpenLogin,
    onLogout,
}) {
    const [farmOpen, setFarmOpen] = useState(false);
    const [userOpen, setUserOpen] = useState(false);
    const farmRef = useRef(null);
    const userRef = useRef(null);

    // Close dropdowns on outside click
    useEffect(() => {
        function handleClick(e) {
            if (farmRef.current && !farmRef.current.contains(e.target)) setFarmOpen(false);
            if (userRef.current && !userRef.current.contains(e.target)) setUserOpen(false);
        }
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    const locale = preferences?.locale?.toUpperCase() || 'EN';
    const thiValue = climate?.thi_index ? parseFloat(climate.thi_index).toFixed(1) : null;
    const thiLevel = climate?.heat_stress_level || null;

    const thiLabel = thiLevel === 'normal' ? 'Normal'
        : thiLevel === 'mild_stress' ? 'Mild Stress'
        : thiLevel === 'moderate_stress' ? 'Moderate Stress'
        : thiLevel === 'severe_stress' ? 'Severe Stress'
        : 'Normal';

    const initials = user?.name
        ? user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
        : 'TM';

    return (
        <header className="app-header h-14 px-5 flex items-center justify-between shrink-0 z-30 relative">
            {/* Left: Farm Switcher */}
            <div className="flex items-center gap-3">
                <div ref={farmRef} className="relative">
                    <button
                        id="farm-switcher"
                        onClick={() => setFarmOpen(v => !v)}
                        className="header-pill header-pill-farm flex items-center gap-1.5"
                    >
                        <span className="text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Farm</span>
                        <span className="font-bold text-[13px] text-emerald-950 dark:text-emerald-100">
                            {farm?.name || 'Al-Falah Pilot Dairy & Goat Farm'}
                        </span>
                        <ChevronDown className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 opacity-80" />
                    </button>

                    {farmOpen && (
                        <div className="dropdown-menu top-full left-0 mt-1.5">
                            <div className="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Switch Farm
                            </div>
                            {farms.length > 0 ? farms.map(f => (
                                <button
                                    key={f.id}
                                    onClick={() => { onSelectFarm?.(f); setFarmOpen(false); }}
                                    className="dropdown-item"
                                >
                                    <span className="flex-1 text-left">
                                        <span className="block font-semibold flex items-center justify-between gap-2">
                                            <span className="truncate">{f.name}</span>
                                            {(f.role || f.user_role) && (
                                                <span className="text-[10px] font-medium px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 shrink-0">
                                                    {f.role || f.user_role}
                                                </span>
                                            )}
                                        </span>
                                        <span className="text-[11px] text-slate-400">{f.location || 'Punjab, PK'} · {f.animals_count ?? 0} head</span>
                                    </span>
                                    {f.id === farm?.id && <Check className="w-3.5 h-3.5 text-green-500 shrink-0 ml-1.5" />}
                                </button>
                            )) : (
                                <div className="px-3 py-2 text-[12px] text-slate-400">
                                    {farm?.name || 'Al-Falah Pilot Dairy & Goat Farm'}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Right: pills + avatar */}
            <div className="flex items-center gap-2">
                {/* THI pill */}
                {thiValue && (
                    <div className="header-pill header-pill-thi">
                        <Thermometer className="w-3.5 h-3.5 shrink-0" />
                        <span className="font-medium">THI {thiValue}</span>
                        <span className="opacity-70">({thiLabel})</span>
                    </div>
                )}

                {/* Food Safety Alert */}
                {activeWithdrawals.length > 0 && (
                    <div className="header-pill header-pill-alert">
                        <ShieldAlert className="w-3.5 h-3.5 shrink-0" />
                        <span className="font-semibold">{activeWithdrawals.length} Withdrawal Active</span>
                    </div>
                )}

                {/* Language indicator */}
                <div className="hidden sm:flex items-center text-[12px] font-semibold text-slate-500 dark:text-slate-400 select-none px-1">
                    {locale} / UR
                </div>

                {/* Refresh */}
                <button
                    onClick={onRefresh}
                    disabled={isRefreshing}
                    className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    title="Refresh"
                >
                    <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin text-green-500' : ''}`} />
                </button>

                {/* Theme toggle */}
                <button
                    id="theme-toggle"
                    onClick={() => setIsDark(!isDark)}
                    className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    title={isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
                >
                    {isDark ? <Sun className="w-4 h-4 text-amber-400" /> : <Moon className="w-4 h-4" />}
                </button>

                {/* User avatar + dropdown */}
                <div ref={userRef} className="relative">
                    <button
                        id="user-menu"
                        onClick={() => setUserOpen(v => !v)}
                        className="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                    >
                        <div className="w-7 h-7 rounded-full bg-emerald-600 flex items-center justify-center text-white text-[11px] font-bold shrink-0 shadow-sm">
                            {initials}
                        </div>
                        <div className="text-left flex flex-col">
                            <div className="user-name text-[12px] font-bold text-slate-900 dark:text-white leading-tight">
                                {user?.name || 'Tariq Mehmood (Farm Owner)'}
                            </div>
                            <div className="user-role text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 leading-tight">
                                {user?.role || 'Farm Owner'}
                            </div>
                        </div>
                        <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                    </button>

                    {userOpen && (
                        <div className="dropdown-menu top-full right-0 mt-1.5 w-56 shadow-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                            <div className="px-3 py-2 border-b border-slate-100 dark:border-slate-700 mb-1">
                                <div className="user-name text-[13px] font-bold text-slate-900 dark:text-white">
                                    {user?.name || 'Tariq Mehmood (Farm Owner)'}
                                </div>
                                <div className="text-[11px] text-slate-500 dark:text-slate-400">{user?.email || 'owner@farm.local'}</div>
                                <div className="mt-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">
                                    {preferences?.currency || 'PKR'} · {locale}
                                </div>
                            </div>
                            <button onClick={() => { onOpenPreferences?.(); setUserOpen(false); }} className="dropdown-item">
                                <Settings className="w-4 h-4 text-slate-400 shrink-0" />
                                Preferences
                            </button>
                            <button onClick={() => { onOpenLogin?.(); setUserOpen(false); }} className="dropdown-item">
                                <User className="w-4 h-4 text-slate-400 shrink-0" />
                                Switch Account
                            </button>
                            <div className="border-t border-slate-100 dark:border-slate-700 my-1" />
                            <button onClick={() => { onLogout?.(); setUserOpen(false); }} className="dropdown-item text-red-500 dark:text-red-400">
                                <LogOut className="w-4 h-4 shrink-0" />
                                Sign Out
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
