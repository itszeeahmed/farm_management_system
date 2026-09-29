import React from 'react';
import { 
    LayoutDashboard, 
    Binary, 
    Milk, 
    HeartPulse, 
    GitBranch, 
    Wheat, 
    CircleDollarSign, 
    CheckSquare, 
    ShieldAlert,
    SunMedium,
    Sparkles
} from 'lucide-react';

export default function Sidebar({ currentTab, setCurrentTab, activeWithdrawalsCount }) {
    const navItems = [
        { id: 'dashboard', label: 'Executive Dashboard', icon: LayoutDashboard },
        { id: 'livestock', label: 'Livestock Registry', icon: Binary, count: '15' },
        { id: 'milk', label: 'Milk Production', icon: Milk },
        { id: 'health', label: 'Health & Withdrawals', icon: HeartPulse, badge: activeWithdrawalsCount > 0 ? `${activeWithdrawalsCount} Alert` : null },
        { id: 'breeding', label: 'Breeding & Genetics', icon: GitBranch },
        { id: 'feed', label: 'Feed & Nutrition', icon: Wheat },
        { id: 'finances', label: 'Finances & P&L', icon: CircleDollarSign },
        { id: 'tasks', label: 'Farm Tasks', icon: CheckSquare },
    ];

    return (
        <aside className="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 text-slate-300">
            {/* Logo & Farm Identity */}
            <div className="p-5 border-b border-slate-800">
                <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white shadow-lg shadow-emerald-900/40">
                        <Sparkles className="w-6 h-6" />
                    </div>
                    <div>
                        <h1 className="font-bold text-white tracking-tight text-base">Al-Falah Farm</h1>
                        <p className="text-xs text-emerald-400 font-medium flex items-center gap-1.5">
                            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Pilot Farm #01
                        </p>
                    </div>
                </div>

                <div className="mt-4 px-3 py-2 bg-slate-800/60 rounded-lg border border-slate-700/50 flex items-center justify-between text-xs">
                    <span className="text-slate-400">Stock Count</span>
                    <span className="font-mono text-emerald-400 font-semibold">5 Cows • 10 Goats</span>
                </div>
            </div>

            {/* Navigation Menu */}
            <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = currentTab === item.id;
                    return (
                        <button
                            key={item.id}
                            id={`nav-${item.id}`}
                            onClick={() => setCurrentTab(item.id)}
                            className={`w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all ${
                                isActive 
                                    ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/30 font-semibold' 
                                    : 'text-slate-300 hover:bg-slate-800/70 hover:text-white'
                            }`}
                        >
                            <div className="flex items-center gap-3">
                                <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                                <span>{item.label}</span>
                            </div>

                            {item.count && (
                                <span className={`text-xs px-2 py-0.5 rounded-full font-mono ${
                                    isActive ? 'bg-emerald-700 text-white' : 'bg-slate-800 text-slate-400'
                                }`}>
                                    {item.count}
                                </span>
                            )}

                            {item.badge && (
                                <span className="text-xs px-2 py-0.5 rounded-full font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/40 animate-pulse">
                                    {item.badge}
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* System Status Footer */}
            <div className="p-4 border-t border-slate-800 text-xs text-slate-400 space-y-2">
                <div className="flex items-center justify-between">
                    <span className="flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
                        PostgreSQL 18
                    </span>
                    <span className="font-mono text-slate-400">Port 5432</span>
                </div>
                <div className="flex items-center justify-between">
                    <span>PHP Engine</span>
                    <span className="font-mono text-emerald-400 font-semibold">8.3.28 FastCGI</span>
                </div>
            </div>
        </aside>
    );
}
