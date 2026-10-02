import React, { useMemo } from 'react';
import {
    PawPrint,
    Milk,
    CircleDollarSign,
    ShieldCheck,
    ShieldAlert,
    X,
    Info,
    ChevronRight,
    Stethoscope,
    GitBranch,
    Scale
} from 'lucide-react';

// Milk harvest area chart drawn as pure SVG (no library needed)
function MilkAreaChart({ trendData }) {
    const W = 700, H = 220;
    const PAD = { top: 20, right: 20, bottom: 40, left: 44 };
    const chartW = W - PAD.left - PAD.right;
    const chartH = H - PAD.top - PAD.bottom;

    // Build data from API or use fallback
    const days = useMemo(() => {
        if (trendData && trendData.length > 0) {
            return trendData.map(d => ({
                label: d.display_date || d.date,
                cow: parseFloat(d.cow_liters) || 0,
                goat: parseFloat(d.goat_liters) || 0,
            }));
        }
        // Fallback demo data matching mockup shape
        return [
            { label: 'Sun', cow: 110, goat: 80  },
            { label: 'Mon', cow: 185, goat: 140 },
            { label: 'Tue', cow: 105, goat: 90  },
            { label: 'Wed', cow: 235, goat: 130 },
            { label: 'Thu', cow: 155, goat: 195 },
            { label: 'Fri', cow: 200, goat: 200 },
            { label: 'Sat', cow: 65,  goat: 100 },
        ];
    }, [trendData]);

    const maxVal = Math.max(...days.flatMap(d => [d.cow, d.goat]), 50) * 1.15;
    const n = days.length;

    function xPos(i) { return PAD.left + (i / (n - 1)) * chartW; }
    function yPos(v) { return PAD.top + chartH - (v / maxVal) * chartH; }

    function makePath(key) {
        return days
            .map((d, i) => `${i === 0 ? 'M' : 'L'}${xPos(i).toFixed(1)},${yPos(d[key]).toFixed(1)}`)
            .join(' ');
    }

    function makeArea(key) {
        const line = makePath(key);
        const last = `L${xPos(n - 1).toFixed(1)},${(PAD.top + chartH).toFixed(1)}`;
        const first = `L${xPos(0).toFixed(1)},${(PAD.top + chartH).toFixed(1)} Z`;
        return `${line} ${last} ${first}`;
    }

    // Y axis labels
    const yTicks = [0, Math.round(maxVal * 0.25), Math.round(maxVal * 0.5), Math.round(maxVal * 0.75)];

    return (
        <svg viewBox={`0 0 ${W} ${H}`} className="w-full h-full" preserveAspectRatio="none">
            <defs>
                <linearGradient id="cowGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#22c55e" stopOpacity="0.35" />
                    <stop offset="100%" stopColor="#22c55e" stopOpacity="0.04" />
                </linearGradient>
                <linearGradient id="goatGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#eab308" stopOpacity="0.35" />
                    <stop offset="100%" stopColor="#eab308" stopOpacity="0.04" />
                </linearGradient>
            </defs>

            {/* Grid lines */}
            {yTicks.map((v, i) => (
                <g key={i}>
                    <line
                        x1={PAD.left} y1={yPos(v)}
                        x2={PAD.left + chartW} y2={yPos(v)}
                        stroke="currentColor" strokeOpacity="0.08" strokeWidth="1"
                    />
                    <text x={PAD.left - 6} y={yPos(v) + 4} textAnchor="end"
                        fontSize="10" fill="currentColor" opacity="0.45" fontFamily="inherit">
                        {v}
                    </text>
                </g>
            ))}

            {/* Areas */}
            <path d={makeArea('goat')} fill="url(#goatGrad)" />
            <path d={makeArea('cow')}  fill="url(#cowGrad)"  />

            {/* Lines */}
            <path d={makePath('goat')} fill="none" stroke="#ca8a04" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
            <path d={makePath('cow')}  fill="none" stroke="#16a34a" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />

            {/* Dots */}
            {days.map((d, i) => (
                <g key={i}>
                    <circle cx={xPos(i)} cy={yPos(d.cow)}  r="4" fill="#16a34a" stroke="#fff" strokeWidth="1.5" />
                    <circle cx={xPos(i)} cy={yPos(d.goat)} r="4" fill="#ca8a04" stroke="#fff" strokeWidth="1.5" />
                </g>
            ))}

            {/* X-axis labels */}
            {days.map((d, i) => (
                <text
                    key={i}
                    x={xPos(i)} y={PAD.top + chartH + 20}
                    textAnchor="middle" fontSize="11"
                    fill="currentColor" opacity="0.5" fontFamily="inherit"
                >
                    {d.label}
                </text>
            ))}

            {/* X-axis label */}
            <text
                x={PAD.left + chartW / 2} y={H - 2}
                textAnchor="middle" fontSize="11"
                fill="currentColor" opacity="0.35" fontFamily="inherit"
            >
                Milk Harvest Area
            </text>
        </svg>
    );
}

// NRC THI radial gauge
function ThiGauge({ thi = 78.4 }) {
    const clamped = Math.max(60, Math.min(95, thi));
    const pct = (clamped - 60) / 35; // 0..1
    // Arc from -160deg to +160deg (320 deg sweep)
    const R = 60;
    const CX = 80, CY = 80;
    const startAngle = -200 * (Math.PI / 180);
    const endAngle   = 20  * (Math.PI / 180);
    const sweep = endAngle - startAngle;

    function polarToXY(angle) {
        return {
            x: CX + R * Math.cos(angle),
            y: CY + R * Math.sin(angle),
        };
    }

    const arcStart = polarToXY(startAngle);
    const arcEnd   = polarToXY(endAngle);
    // Value arc endpoint
    const valAngle = startAngle + sweep * pct;
    const valEnd   = polarToXY(valAngle);
    const largeVal = sweep * pct > Math.PI ? 1 : 0;

    // Needle
    const needleAngle = valAngle;
    const needleTip = { x: CX + (R - 8) * Math.cos(needleAngle), y: CY + (R - 8) * Math.sin(needleAngle) };

    // Color based on value
    const color = thi < 72 ? '#22c55e' : thi < 79 ? '#eab308' : thi < 84 ? '#f97316' : '#ef4444';

    const trackD = `M ${arcStart.x} ${arcStart.y} A ${R} ${R} 0 1 1 ${arcEnd.x} ${arcEnd.y}`;
    const valD   = `M ${arcStart.x} ${arcStart.y} A ${R} ${R} 0 ${largeVal} 1 ${valEnd.x} ${valEnd.y}`;

    return (
        <svg viewBox="0 0 160 130" className="w-40 h-32">
            {/* track */}
            <path d={trackD} fill="none" stroke="#e2e8f0" strokeWidth="10" strokeLinecap="round" className="dark:stroke-slate-700" />
            {/* value arc */}
            <path d={valD} fill="none" stroke={color} strokeWidth="10" strokeLinecap="round" />
            {/* needle */}
            <line x1={CX} y1={CY} x2={needleTip.x} y2={needleTip.y} stroke="#1e293b" strokeWidth="2.5" strokeLinecap="round" className="dark:stroke-slate-200" />
            <circle cx={CX} cy={CY} r="5" fill="#1e293b" className="dark:fill-slate-200" />
            {/* label */}
            <text x={CX} y={CY + 22} textAnchor="middle" fontSize="18" fontWeight="700" fill={color} fontFamily="inherit">
                {thi.toFixed(1)}
            </text>
            <text x={CX} y={CY + 36} textAnchor="middle" fontSize="10" fill="currentColor" opacity="0.5" fontFamily="inherit">
                Heat index
            </text>
        </svg>
    );
}

export default function DashboardView({ dashboardData, onNavigate, onOpenModal, preferences, userRole = 'Farm Owner' }) {
    const canViewFinance = !['Consulting Veterinarian', 'Government Veterinary Auditor'].includes(userRole);
    const currency = preferences?.currency || 'PKR';
    if (!dashboardData) {
        return (
            <div className="flex items-center justify-center min-h-[50vh]">
                <div className="flex flex-col items-center gap-3">
                    <div className="w-8 h-8 border-3 border-green-500 border-t-transparent rounded-full animate-spin" />
                    <p className="text-sm text-slate-400">Loading dashboard…</p>
                </div>
            </div>
        );
    }

    const { livestock, milk, climate, active_withdrawals = [], finance, farm } = dashboardData;
    const currentThi = climate?.thi_index ? parseFloat(climate.thi_index) : 78.4;
    const withdrawal = active_withdrawals[0];

    return (
        <div className="space-y-5">
            {/* ── KPI Row ─────────────────────────────────────────────── */}
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                {/* Total Livestock — green card */}
                <div
                    onClick={() => onNavigate('livestock')}
                    className="card-green rounded-xl p-5 cursor-pointer"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="text-[12px] font-semibold text-green-200">Total Livestock</span>
                        <PawPrint className="w-5 h-5 text-green-200 opacity-80" />
                    </div>
                    <div className="text-3xl font-bold tracking-tight leading-none">
                        {livestock?.total ?? 15}
                    </div>
                    <div className="text-sm text-green-200 mt-1">heads</div>
                </div>

                {/* Today's Milk Harvest — amber card */}
                <div
                    onClick={() => onNavigate('milk')}
                    className="card-amber rounded-xl p-5 cursor-pointer"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="text-[12px] font-semibold text-amber-100">Today's Milk Harvest</span>
                        <Milk className="w-5 h-5 text-amber-100 opacity-80" />
                    </div>
                    <div className="text-3xl font-bold tracking-tight leading-none">
                        {milk?.today_liters != null ? `${milk.today_liters}` : '164.2'}
                    </div>
                    <div className="text-sm text-amber-100 mt-1">L</div>
                </div>

                {/* Cost per Liter — white card */}
                {canViewFinance && (
                    <div
                        onClick={() => onNavigate('finances')}
                        className="card rounded-xl p-5 cursor-pointer"
                    >
                        <div className="flex items-center justify-between mb-3">
                            <span className="text-[12px] font-semibold text-slate-500 dark:text-slate-400">Cost-per-Liter</span>
                            <CircleDollarSign className="w-5 h-5 text-slate-400 dark:text-slate-500" />
                        </div>
                        <div className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white card-kpi-val leading-none">
                            {currency}&nbsp;
                            {finance?.cost_per_liter ? finance.cost_per_liter.toFixed(2) : '142.50'}/L
                        </div>
                        <div className="text-[11px] text-slate-600 dark:text-slate-400 mt-2 font-medium">
                            Income: {currency} {finance?.monthly_income?.toLocaleString() ?? '148,500'}
                        </div>
                    </div>
                )}

                {/* Food Safety Clearance — white card */}
                <div
                    onClick={() => onNavigate('health')}
                    className="card rounded-xl p-5 cursor-pointer"
                >
                    <div className="flex items-center justify-between mb-3">
                        <span className="text-[12px] font-semibold text-slate-500 dark:text-slate-400">Food Safety Clearance</span>
                        <ShieldCheck className="w-5 h-5 text-slate-400 dark:text-slate-500" />
                    </div>
                    <div className="flex items-center gap-2 mt-3">
                        {active_withdrawals.length === 0 ? (
                            <span className="badge badge-green">Cleared</span>
                        ) : (
                            <span className="badge badge-red">{active_withdrawals.length} Lock{active_withdrawals.length > 1 ? 's' : ''} Active</span>
                        )}
                    </div>
                    <div className="text-[11px] text-slate-600 dark:text-slate-400 mt-2 font-medium">
                        {active_withdrawals.length === 0 ? 'All milk batches safe' : 'Milk discard in effect'}
                    </div>
                </div>
            </div>

            {/* ── Main content: Chart + Right panel ───────────────────── */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {/* Left: Milk Area Chart */}
                <div className="lg:col-span-2 card rounded-xl p-5">
                    <div className="flex items-start justify-between mb-3">
                        <div>
                            <h2 className="text-[14px] font-semibold text-slate-800 dark:text-slate-100">
                                7-day Milk harvest area
                            </h2>
                            <p className="text-[12px] text-slate-400 mt-0.5">Cow vs goat yields</p>
                        </div>
                        {/* Inline withdrawal alert (inside chart card, as in mockup) */}
                        {withdrawal && (
                            <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-[12px] text-red-700 dark:text-red-400 max-w-xs">
                                <ShieldAlert className="w-3.5 h-3.5 shrink-0 text-red-500" />
                                <span className="font-medium leading-tight">
                                    Antibiotic withdrawal lock for alert:<br />
                                    <span className="font-bold">{withdrawal.animal_identifier || 'Cow #PK-COW-005'} lock</span>
                                </span>
                                <button onClick={() => onNavigate('health')} className="ml-1 text-red-400 hover:text-red-600">
                                    <X className="w-3 h-3" />
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Legend */}
                    <div className="flex items-center gap-4 text-[12px] text-slate-500 dark:text-slate-400 mb-2">
                        <span className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-green-500 inline-block" />
                            Cow
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block" />
                            Goat
                        </span>
                    </div>

                    {/* SVG Chart */}
                    <div className="h-52 text-slate-800 dark:text-slate-200">
                        <MilkAreaChart trendData={dashboardData?.milk?.seven_day_trend} />
                    </div>
                </div>

                {/* Right panel */}
                <div className="flex flex-col gap-4">
                    {/* NRC THI Heat Index */}
                    <div className="card rounded-xl p-5">
                        <div className="flex items-center justify-between mb-1">
                            <h3 className="text-[13px] font-semibold text-slate-800 dark:text-slate-100">NRC THI heat index</h3>
                            <Info className="w-4 h-4 text-slate-400" />
                        </div>
                        <div className="flex justify-center py-1">
                            <ThiGauge thi={currentThi} />
                        </div>
                        <div className="grid grid-cols-2 gap-2 mt-1 text-[12px]">
                            <div className="rounded-lg bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <div className="text-slate-400 text-[10px] font-medium uppercase tracking-wide">Temp</div>
                                <div className="font-semibold text-slate-700 dark:text-slate-200 mt-0.5">
                                    {climate?.temperature_c ?? 31.8}°C
                                </div>
                            </div>
                            <div className="rounded-lg bg-slate-50 dark:bg-slate-800 px-3 py-2">
                                <div className="text-slate-400 text-[10px] font-medium uppercase tracking-wide">Humidity</div>
                                <div className="font-semibold text-slate-700 dark:text-slate-200 mt-0.5">
                                    {climate?.relative_humidity_percent ?? 64}%
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick-action 2×2 grid */}
                    <div className="card rounded-xl p-4">
                        <h3 className="text-[13px] font-semibold text-slate-800 dark:text-slate-100 mb-3">Quick-action</h3>
                        <div className="grid grid-cols-2 gap-2">
                            <button
                                onClick={onOpenModal}
                                className="flex flex-col items-center gap-1.5 py-3 px-2 rounded-lg bg-green-600 hover:bg-green-700 text-white transition cursor-pointer"
                            >
                                <Milk className="w-5 h-5" />
                                <span className="text-[11px] font-semibold">Log Milking</span>
                            </button>
                            <button
                                onClick={() => onNavigate('livestock')}
                                className="flex flex-col items-center gap-1.5 py-3 px-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white transition cursor-pointer"
                            >
                                <Scale className="w-5 h-5" />
                                <span className="text-[11px] font-semibold">Record Weight</span>
                            </button>
                            <button
                                onClick={() => onNavigate('health')}
                                className="flex flex-col items-center gap-1.5 py-3 px-2 rounded-lg bg-slate-700 dark:bg-slate-600 hover:bg-slate-800 dark:hover:bg-slate-500 text-white transition cursor-pointer"
                            >
                                <Stethoscope className="w-5 h-5" />
                                <span className="text-[11px] font-semibold">Log Health</span>
                            </button>
                            <button
                                onClick={() => onNavigate('breeding')}
                                className="flex flex-col items-center gap-1.5 py-3 px-2 rounded-lg bg-slate-700 dark:bg-slate-600 hover:bg-slate-800 dark:hover:bg-slate-500 text-white transition cursor-pointer"
                            >
                                <GitBranch className="w-5 h-5" />
                                <span className="text-[11px] font-semibold">Inseminate</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
