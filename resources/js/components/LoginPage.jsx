import React, { useState } from 'react';
import {
    Lock,
    Mail,
    Eye,
    EyeOff,
    AlertCircle,
    Building2,
    Stethoscope,
    UserCheck,
    FileCheck,
    PawPrint
} from 'lucide-react';
import { api, setStoredToken, setActiveFarmId } from '../services/api';

// Role profiles for quick-fill demo
const DEMO_PROFILES = [
    {
        email: 'owner@farm.local',
        label: 'Farm Owner',
        sub: 'Full access to all modules',
        icon: Building2,
        color: 'text-green-600',
        ring: 'border-green-500 bg-green-50 dark:bg-green-950/30',
    },
    {
        email: 'vet@farm.local',
        label: 'Veterinarian',
        sub: 'Health, treatments & AMU',
        icon: Stethoscope,
        color: 'text-blue-600',
        ring: 'border-blue-500 bg-blue-50 dark:bg-blue-950/30',
    },
    {
        email: 'worker@farm.local',
        label: 'Herd Manager',
        sub: 'Milk, feed & daily operations',
        icon: UserCheck,
        color: 'text-amber-600',
        ring: 'border-amber-500 bg-amber-50 dark:bg-amber-950/30',
    },
    {
        email: 'auditor@livestock.gov.pk',
        label: 'Govt Inspector',
        sub: 'Read-only compliance view',
        icon: FileCheck,
        color: 'text-purple-600',
        ring: 'border-purple-500 bg-purple-50 dark:bg-purple-950/30',
    },
];

export default function LoginPage({ onLoginSuccess }) {
    const [mode, setMode] = useState('login'); // 'login' | 'register'
    const [email, setEmail] = useState('owner@farm.local');
    const [password, setPassword] = useState('password');
    const [showPass, setShowPass] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    // Self-service registration state
    const [regForm, setRegForm] = useState({
        org_name: '',
        farm_name: '',
        farm_type: 'dairy_mixed',
        location: '',
        total_area: '25',
        area_unit: 'acres',
        admin_name: '',
        admin_email: '',
        password: '',
    });

    async function handleSubmit(e) {
        e.preventDefault();
        setLoading(true);
        setError('');
        try {
            if (mode === 'login') {
                const res = await api.login({ email, password });
                setStoredToken(res.token);
                if (res.active_farm?.id) {
                    setActiveFarmId(res.active_farm.id);
                }
                onLoginSuccess(res);
            } else {
                const res = await api.registerEnterprise(regForm);
                setStoredToken(res.token);
                if (res.active_farm?.id) {
                    setActiveFarmId(res.active_farm.id);
                }
                onLoginSuccess(res);
            }
        } catch (err) {
            setError(err.message || 'Operation failed. Please check inputs and try again.');
        } finally {
            setLoading(false);
        }
    }

    function fillProfile(profileEmail) {
        setEmail(profileEmail);
        setPassword('password');
        setError('');
    }

    return (
        <div className="min-h-screen flex app-main">
            {/* Left: Brand panel */}
            <div className="hidden lg:flex flex-col justify-between w-96 shrink-0 bg-[#1a3d2b] text-white p-10">
                {/* Logo */}
                <div>
                    <div className="flex items-center gap-3 mb-12">
                        <div className="w-10 h-10 rounded-lg bg-white/15 flex items-center justify-center">
                            <PawPrint className="w-5 h-5 text-green-300" />
                        </div>
                        <div>
                            <div className="text-sm font-bold leading-tight">GreenPastures Agro</div>
                            <div className="text-[11px] text-green-400">Enterprise Livestock Platform</div>
                        </div>
                    </div>

                    <h1 className="text-2xl font-bold leading-snug text-white mb-4">
                        Manage your farm<br />
                        with precision &amp; control
                    </h1>
                    <p className="text-[13px] text-green-200 leading-relaxed">
                        Multi-tenant livestock &amp; dairy management. Real-time herd health, milk tracking, financial intelligence &amp; food safety compliance — all in one platform.
                    </p>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-4 mt-8">
                    {[
                        { label: 'Animals Tracked', value: '15+' },
                        { label: 'Active Farms', value: '2' },
                        { label: 'Milk Today', value: '164L' },
                        { label: 'Food Safe', value: '✓' },
                    ].map(s => (
                        <div key={s.label} className="rounded-xl bg-white/8 p-4">
                            <div className="text-xl font-bold text-white">{s.value}</div>
                            <div className="text-[11px] text-green-300 mt-0.5">{s.label}</div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Right: Login form */}
            <div className="flex-1 flex items-center justify-center p-6">
                <div className="w-full max-w-sm">
                    {/* Mobile logo */}
                    <div className="flex items-center gap-2.5 mb-8 lg:hidden">
                        <div className="w-9 h-9 rounded-lg bg-green-600 flex items-center justify-center">
                            <PawPrint className="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div className="text-sm font-bold text-slate-800 dark:text-slate-100">GreenPastures Agro</div>
                            <div className="text-[11px] text-slate-400">Enterprise Platform</div>
                        </div>
                    </div>

                    {/* Mode Switcher Tabs */}
                    <div className="flex border-b border-slate-200 dark:border-slate-800 mb-6">
                        <button
                            type="button"
                            onClick={() => { setMode('login'); setError(''); }}
                            className={`pb-2.5 px-3 text-sm font-semibold border-b-2 transition-colors ${
                                mode === 'login'
                                    ? 'border-green-600 text-green-600 dark:text-green-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            }`}
                        >
                            Sign In
                        </button>
                        <button
                            type="button"
                            onClick={() => { setMode('register'); setError(''); }}
                            className={`pb-2.5 px-3 text-sm font-semibold border-b-2 transition-colors ${
                                mode === 'register'
                                    ? 'border-green-600 text-green-600 dark:text-green-400'
                                    : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            }`}
                        >
                            Register New Farm
                        </button>
                    </div>

                    <h2 className="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">
                        {mode === 'login' ? 'Sign in' : 'Start your Farm Enterprise'}
                    </h2>
                    <p className="text-[13px] text-slate-400 mb-6">
                        {mode === 'login'
                            ? 'Enter your credentials to access the farm portal'
                            : 'Set up your multi-tenant organization & initial farm'}
                    </p>

                    {/* Error */}
                    {error && (
                        <div className="mb-4 px-3 py-2.5 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-[12px] text-red-700 dark:text-red-400 flex items-center gap-2">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            {error}
                        </div>
                    )}

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {mode === 'login' ? (
                            <>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                                        Email address
                                    </label>
                                    <div className="relative">
                                        <Mail className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            id="login-email"
                                            type="email"
                                            required
                                            value={email}
                                            onChange={e => setEmail(e.target.value)}
                                            placeholder="you@farm.local"
                                            className="form-input pl-9"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                                        Password
                                    </label>
                                    <div className="relative">
                                        <Lock className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                        <input
                                            id="login-password"
                                            type={showPass ? 'text' : 'password'}
                                            required
                                            value={password}
                                            onChange={e => setPassword(e.target.value)}
                                            placeholder="••••••••"
                                            className="form-input pl-9 pr-10 font-mono"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPass(v => !v)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                        >
                                            {showPass ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                                        </button>
                                    </div>
                                </div>
                            </>
                        ) : (
                            <>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                        Organization / Business Name *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="e.g. Indus Green Pastures Ltd"
                                        value={regForm.org_name}
                                        onChange={e => setRegForm(f => ({ ...f, org_name: e.target.value }))}
                                        className="form-input"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                            Farm Name *
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            placeholder="e.g. Sahiwal Dairy Unit"
                                            value={regForm.farm_name}
                                            onChange={e => setRegForm(f => ({ ...f, farm_name: e.target.value }))}
                                            className="form-input"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                            Farm Type
                                        </label>
                                        <select
                                            value={regForm.farm_type}
                                            onChange={e => setRegForm(f => ({ ...f, farm_type: e.target.value }))}
                                            className="form-input text-xs"
                                        >
                                            <option value="dairy_mixed">Dairy & Mixed</option>
                                            <option value="beef">Beef & Feedlot</option>
                                            <option value="goat_sheep">Goats & Sheep</option>
                                            <option value="pasture">Rotational Pasture</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                        Location / Region *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        placeholder="e.g. Kasur Road, Punjab"
                                        value={regForm.location}
                                        onChange={e => setRegForm(f => ({ ...f, location: e.target.value }))}
                                        className="form-input"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div>
                                        <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                            Your Full Name *
                                        </label>
                                        <input
                                            type="text"
                                            required
                                            placeholder="e.g. Tariq Mehmood"
                                            value={regForm.admin_name}
                                            onChange={e => setRegForm(f => ({ ...f, admin_name: e.target.value }))}
                                            className="form-input"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                            Admin Email *
                                        </label>
                                        <input
                                            type="email"
                                            required
                                            placeholder="owner@yourfarm.com"
                                            value={regForm.admin_email}
                                            onChange={e => setRegForm(f => ({ ...f, admin_email: e.target.value }))}
                                            className="form-input"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                                        Password (Min 6 chars) *
                                    </label>
                                    <input
                                        type="password"
                                        required
                                        placeholder="••••••••"
                                        value={regForm.password}
                                        onChange={e => setRegForm(f => ({ ...f, password: e.target.value }))}
                                        className="form-input font-mono"
                                    />
                                </div>
                            </>
                        )}

                        <button
                            id="login-submit"
                            type="submit"
                            disabled={loading}
                            className="btn-primary w-full justify-center py-2.5 mt-2 disabled:opacity-50"
                        >
                            {loading
                                ? (mode === 'login' ? 'Signing in…' : 'Provisioning Enterprise…')
                                : (mode === 'login' ? 'Sign In' : 'Register & Launch Farm Enterprise')}
                        </button>
                    </form>

                    {/* Demo quick-fill (only on login mode) */}
                    {mode === 'login' && (
                        <div className="mt-7 pt-6 border-t border-slate-200 dark:border-slate-700">
                            <p className="text-[11px] font-semibold text-slate-400 uppercase tracking-wide mb-3">
                                Demo accounts (password: <span className="font-mono">password</span>)
                            </p>
                            <div className="grid grid-cols-2 gap-2">
                                {DEMO_PROFILES.map(p => {
                                    const Icon = p.icon;
                                    const active = email === p.email;
                                    return (
                                        <button
                                            key={p.email}
                                            type="button"
                                            onClick={() => fillProfile(p.email)}
                                            className={`text-left p-2.5 rounded-lg border text-[12px] transition flex items-start gap-2 cursor-pointer ${
                                                active
                                                    ? p.ring + ' border-current'
                                                    : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60'
                                            }`}
                                        >
                                            <Icon className={`w-4 h-4 mt-0.5 shrink-0 ${active ? p.color : 'text-slate-400'}`} />
                                            <div>
                                                <div className={`font-semibold ${active ? p.color : 'text-slate-700 dark:text-slate-300'}`}>
                                                    {p.label}
                                                </div>
                                                <div className="text-[10px] text-slate-400 leading-tight mt-0.5">{p.sub}</div>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
