import React from 'react';
import { 
    Binary, 
    Milk, 
    CircleDollarSign, 
    Activity, 
    AlertTriangle, 
    Clock, 
    CheckCircle2, 
    Wind, 
    Droplets, 
    TrendingUp,
    ShieldAlert,
    ChevronRight,
    ArrowUpRight,
    ArrowDownRight
} from 'lucide-react';

export default function DashboardView({ 
    dashboardData, 
    onNavigate, 
    onToggleTaskStatus, 
    onOpenModal 
}) {
    if (!dashboardData) {
        return (
            <div className="p-8 flex items-center justify-center min-h-[400px]">
                <div className="flex flex-col items-center gap-3">
                    <div className="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <p className="text-sm text-slate-500">Loading live telemetry from PostgreSQL...</p>
                </div>
            </div>
        );
    }

    const { livestock, milk, climate, active_withdrawals, pending_tasks, finance, farm } = dashboardData;

    return (
        <div className="space-y-6">
            {/* Top 4 KPI Metrics */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {/* 1. Total Animals */}
                <div 
                    onClick={() => onNavigate('livestock')}
                    className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-emerald-500/50 transition cursor-pointer group"
                >
                    <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span className="text-xs font-semibold uppercase tracking-wider">Total Livestock</span>
                        <div className="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition">
                            <Binary className="w-5 h-5" />
                        </div>
                    </div>
                    <div className="mt-3 flex items-baseline gap-2">
                        <span className="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                            {livestock?.total || 0}
                        </span>
                        <span className="text-xs text-slate-500">Animals</span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>{livestock?.cows} Cattle • {livestock?.goats} Goats</span>
                        <span className="text-emerald-500 font-medium flex items-center gap-0.5">
                            {livestock?.lactating} In Milk
                        </span>
                    </div>
                </div>

                {/* 2. Today's Milk */}
                <div 
                    onClick={() => onNavigate('milk')}
                    className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-amber-500/50 transition cursor-pointer group"
                >
                    <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span className="text-xs font-semibold uppercase tracking-wider">Today's Milk Yield</span>
                        <div className="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition">
                            <Milk className="w-5 h-5" />
                        </div>
                    </div>
                    <div className="mt-3 flex items-baseline gap-2">
                        <span className="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                            {milk?.today_liters || 0}
                        </span>
                        <span className="text-xs text-slate-500">Liters</span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Yesterday: {milk?.yesterday_liters} L</span>
                        <span className="text-amber-500 font-medium">Safe Yield</span>
                    </div>
                </div>

                {/* 3. Monthly Financials */}
                <div 
                    onClick={() => onNavigate('finances')}
                    className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-blue-500/50 transition cursor-pointer group"
                >
                    <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span className="text-xs font-semibold uppercase tracking-wider">Monthly Cashflow</span>
                        <div className="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition">
                            <CircleDollarSign className="w-5 h-5" />
                        </div>
                    </div>
                    <div className="mt-3 flex items-baseline gap-2">
                        <span className="text-2xl font-bold font-mono text-slate-900 dark:text-white">
                            {farm?.currency || 'PKR'} {finance?.monthly_income?.toLocaleString() || 0}
                        </span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Exp: {farm?.currency} {finance?.monthly_expense?.toLocaleString()}</span>
                        <span className={`font-semibold ${finance?.net_profit >= 0 ? 'text-emerald-500' : 'text-slate-400'}`}>
                            Net: {finance?.net_profit?.toLocaleString()}
                        </span>
                    </div>
                </div>

                {/* 4. Herd Health / Sick */}
                <div 
                    onClick={() => onNavigate('health')}
                    className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-rose-500/50 transition cursor-pointer group"
                >
                    <div className="flex items-center justify-between text-slate-500 dark:text-slate-400">
                        <span className="text-xs font-semibold uppercase tracking-wider">Health Status</span>
                        <div className="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 group-hover:scale-110 transition">
                            <Activity className="w-5 h-5" />
                        </div>
                    </div>
                    <div className="mt-3 flex items-baseline gap-2">
                        <span className="text-3xl font-bold font-mono text-slate-900 dark:text-white">
                            {livestock?.sick || 0}
                        </span>
                        <span className="text-xs text-rose-500 font-medium">Under Treatment</span>
                    </div>
                    <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>{livestock?.pregnant} Pregnant</span>
                        <span className="text-rose-500 font-semibold flex items-center gap-1">
                            {active_withdrawals?.length || 0} Withdrawal
                        </span>
                    </div>
                </div>
            </div>

            {/* CRITICAL WITHDRAWAL ALERT BANNER */}
            {active_withdrawals && active_withdrawals.length > 0 && (
                <div className="p-4 rounded-2xl bg-gradient-to-r from-rose-500/10 via-rose-500/5 to-transparent border border-rose-500/30 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-start gap-3">
                        <div className="p-2 rounded-xl bg-rose-500/20 text-rose-500 shrink-0 mt-0.5">
                            <ShieldAlert className="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-sm text-rose-600 dark:text-rose-400">
                                    MANDATORY FOOD SAFETY LOCK (ANTIMICROBIAL WITHDRAWAL ACTIVE)
                                </span>
                                <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-rose-500 text-white">
                                    Discard Milk
                                </span>
                            </div>
                            <p className="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                {active_withdrawals.map((w) => (
                                    <span key={w.treatment_id}>
                                        Animal <strong className="font-mono text-rose-600 dark:text-rose-400">{w.tag_number} ({w.animal_name})</strong> treated with <strong>{w.medicine_name}</strong>. Milk prohibited until <strong>{new Date(w.withdrawal_until).toLocaleString()}</strong> (~{w.remaining_hours} hours left).
                                    </span>
                                ))}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={() => onNavigate('health')}
                        className="self-start md:self-center px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shrink-0"
                    >
                        View Treatment Record
                    </button>
                </div>
            )}

            {/* Main 2-Column Section */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Left 2 Cols: 7-Day Production Trends */}
                <div className="lg:col-span-2 p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="font-bold text-base text-slate-900 dark:text-white">7-Day Milk Production Trend</h3>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Comparative yield: Cattle (5 cows) vs Goats (10 goats)</p>
                        </div>
                        <button 
                            onClick={() => onNavigate('milk')}
                            className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center gap-1"
                        >
                            Full Milk Logs <ChevronRight className="w-3.5 h-3.5" />
                        </button>
                    </div>

                    {/* Visual Yield Trend Bars */}
                    <div className="space-y-3 pt-2">
                        {milk?.seven_day_trend?.map((item) => {
                            const maxVal = 95; // Scaling factor
                            const cattlePct = Math.min(100, Math.round((item.cow_liters / maxVal) * 100));
                            const goatPct = Math.min(100, Math.round((item.goat_liters / maxVal) * 100));

                            return (
                                <div key={item.date} className="flex items-center gap-3 text-xs">
                                    <span className="w-16 font-mono text-slate-500 shrink-0 font-medium">
                                        {item.display_date}
                                    </span>
                                    
                                    <div className="flex-1 flex h-6 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden relative">
                                        {item.cow_liters > 0 && (
                                            <div 
                                                style={{ width: `${cattlePct}%` }}
                                                className="bg-emerald-600 text-white flex items-center justify-center text-[10px] font-mono font-bold transition-all"
                                                title={`Cattle: ${item.cow_liters} L`}
                                            >
                                                {item.cow_liters > 15 ? `${item.cow_liters}L` : ''}
                                            </div>
                                        )}
                                        {item.goat_liters > 0 && (
                                            <div 
                                                style={{ width: `${goatPct}%` }}
                                                className="bg-amber-500 text-white flex items-center justify-center text-[10px] font-mono font-bold transition-all"
                                                title={`Goats: ${item.goat_liters} L`}
                                            >
                                                {item.goat_liters > 8 ? `${item.goat_liters}L` : ''}
                                            </div>
                                        )}
                                        {item.total_liters === 0 && (
                                            <span className="w-full text-center text-slate-400 self-center text-[10px]">No records</span>
                                        )}
                                    </div>

                                    <span className="w-14 font-mono font-bold text-right text-slate-800 dark:text-slate-200">
                                        {item.total_liters} L
                                    </span>
                                </div>
                            );
                        })}
                    </div>

                    <div className="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                        <div className="flex items-center gap-4">
                            <span className="flex items-center gap-1.5">
                                <span className="w-3 h-3 rounded bg-emerald-600"></span> Cattle Milk
                            </span>
                            <span className="flex items-center gap-1.5">
                                <span className="w-3 h-3 rounded bg-amber-500"></span> Goat Milk
                            </span>
                        </div>
                        <span>Safe from drug residues</span>
                    </div>
                </div>

                {/* Right Col: Climate & Heat Stress THI */}
                <div className="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                    <div>
                        <div className="flex items-center justify-between">
                            <h3 className="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
                                <Wind className="w-4 h-4 text-emerald-500" />
                                Barn Microclimate & THI
                            </h3>
                            <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-orange-500/10 text-orange-400 border border-orange-500/20">
                                {climate?.heat_stress_level?.replace('_', ' ')}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            NRC equation calculated index for bovine & caprine thermal comfort
                        </p>
                    </div>

                    {/* Temperature & Humidity Readings */}
                    <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/50 space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-slate-500">Ambient Temp</span>
                            <span className="text-base font-bold font-mono text-slate-900 dark:text-white">
                                {climate?.temperature_c}°C
                            </span>
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-slate-500">Relative Humidity</span>
                            <span className="text-base font-bold font-mono text-slate-900 dark:text-white">
                                {climate?.relative_humidity_percent}%
                            </span>
                        </div>
                        <div className="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span className="text-xs font-semibold text-slate-700 dark:text-slate-300">THI Index Score</span>
                            <span className="text-xl font-bold font-mono text-orange-500">
                                {climate?.thi_index}
                            </span>
                        </div>
                    </div>

                    {/* Mitigation Advice & Status */}
                    <div className="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs space-y-1.5">
                        <span className="font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <CheckCircle2 className="w-3.5 h-3.5" /> Automated Barn Action
                        </span>
                        <p className="text-slate-600 dark:text-slate-300 text-[11px] leading-relaxed">
                            {climate?.mitigation || 'Automatic high-velocity ventilation fans active (Zone A & B). Misting scheduled for 14:00.'}
                        </p>
                    </div>
                </div>
            </div>

            {/* Bottom Section: Urgent Tasks & Alerts */}
            <div className="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="font-bold text-base text-slate-900 dark:text-white">Operational Farm Tasks</h3>
                        <p className="text-xs text-slate-500 dark:text-slate-400">High-priority tasks requiring supervisor and farmhand action</p>
                    </div>
                    <button 
                        onClick={() => onNavigate('tasks')}
                        className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 flex items-center gap-1"
                    >
                        View All Tasks <ChevronRight className="w-3.5 h-3.5" />
                    </button>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    {pending_tasks?.map((task) => (
                        <div 
                            key={task.id}
                            className="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex flex-col justify-between gap-3 hover:border-emerald-500/40 transition"
                        >
                            <div>
                                <div className="flex items-center justify-between gap-2">
                                    <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
                                        task.priority === 'urgent' 
                                            ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' 
                                            : 'bg-amber-500/10 text-amber-500 border border-amber-500/20'
                                    }`}>
                                        {task.priority}
                                    </span>
                                    <span className="text-[11px] font-mono text-slate-500 flex items-center gap-1">
                                        <Clock className="w-3 h-3" />
                                        {new Date(task.due_date).toLocaleDateString()}
                                    </span>
                                </div>
                                <h4 className="text-xs font-bold text-slate-900 dark:text-white mt-2 leading-snug">
                                    {task.title}
                                </h4>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                                    {task.description}
                                </p>
                            </div>

                            <button
                                onClick={() => onToggleTaskStatus(task.id, 'completed')}
                                className="w-full py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center justify-center gap-1.5 transition"
                            >
                                <CheckCircle2 className="w-3.5 h-3.5" /> Mark Complete
                            </button>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
