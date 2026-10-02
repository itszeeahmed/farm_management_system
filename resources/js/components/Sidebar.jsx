import React from 'react';
import {
    LayoutDashboard,
    PawPrint,
    Milk,
    Wheat,
    HeartPulse,
    GitBranch,
    Beef,
    ShoppingCart,
    CircleDollarSign,
    Wifi,
    Users
} from 'lucide-react';

// Dynamic navigation items with permission slug requirements
const ALL_NAV_ITEMS = [
    { id: 'dashboard',  label: 'Dashboard',             icon: LayoutDashboard, perm: null },
    { id: 'livestock',  label: 'Livestock Master',       icon: PawPrint,        perm: 'animals.view' },
    { id: 'milk',       label: 'Milking & Tanks',        icon: Milk,            perm: 'milk.record' },
    { id: 'feed',       label: 'Feed & Rations',         icon: Wheat,           perm: 'feed.manage' },
    { id: 'health',     label: 'Veterinary Health',      icon: HeartPulse,      perm: 'health.diagnose', alert: true },
    { id: 'breeding',   label: 'Breeding & Calving',     icon: GitBranch,       perm: 'animals.view' },
    { id: 'feedlots',   label: 'Meat Feedlots',          icon: Beef,            perm: 'animals.view' },
    { id: 'sales',      label: 'Direct Sales CRM',       icon: ShoppingCart,    perm: 'finance.view' },
    { id: 'finances',   label: 'Cost-Per-Liter Finance', icon: CircleDollarSign, perm: 'finance.view' },
    { id: 'compliance', label: 'Compliance & Sync',      icon: Wifi,            perm: 'audit.view' },
    { id: 'team',       label: 'Team & Access',          icon: Users,           perm: 'team.manage' },
];

export default function Sidebar({
    currentTab,
    setCurrentTab,
    activeWithdrawalsCount,
    hasPermission,
    userRole = 'Farm Owner'
}) {
    // Dynamically filter nav items using hasPermission()
    const navItems = ALL_NAV_ITEMS.filter(item => {
        if (!item.perm) return true;
        if (userRole === 'Farm Owner') return true;
        if (typeof hasPermission === 'function') {
            if (item.id === 'team') {
                return hasPermission('team.manage') || hasPermission('audit.view');
            }
            return hasPermission(item.perm);
        }
        return true;
    });

    return (
        <aside className="app-sidebar w-56 flex flex-col shrink-0 select-none">
            {/* Logo */}
            <div className="flex items-center gap-2.5 px-4 py-5 border-b border-white/10">
                <div className="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                    <PawPrint className="w-5 h-5 text-green-300" />
                </div>
                <div>
                    <div className="text-sm font-bold text-white leading-tight">GreenPastures</div>
                    <div className="text-[11px] text-green-400 leading-tight">Agro Enterprise</div>
                </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
                {navItems.map(({ id, label, icon: Icon, alert }) => {
                    const isActive = currentTab === id;
                    const showBadge = alert && activeWithdrawalsCount > 0;
                    return (
                        <button
                            key={id}
                            id={`nav-${id}`}
                            onClick={() => setCurrentTab(id)}
                            className={`nav-item ${isActive ? 'active' : ''}`}
                        >
                            <Icon className="w-4 h-4 shrink-0 nav-icon" />
                            <span className="flex-1 text-left">{label}</span>
                            {showBadge && (
                                <span className="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-500 text-white leading-none">
                                    {activeWithdrawalsCount}
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>

            {/* Footer */}
            <div className="px-4 py-3 border-t border-white/10">
                <div className="flex items-center gap-1.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
                    <span className="text-[11px] text-green-400 font-medium">Multi-Tenant Online</span>
                </div>
            </div>
        </aside>
    );
}
