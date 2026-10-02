import React, { useState } from 'react';
import { 
    Lock, 
    Mail, 
    ShieldCheck, 
    Sparkles, 
    CheckCircle2, 
    AlertCircle, 
    Building2, 
    UserCheck,
    Stethoscope,
    Tractor,
    FileCheck
} from 'lucide-react';
import { api, setStoredToken, setActiveFarmId } from '../services/api';

export default function LoginModal({ isOpen, onClose, onLoginSuccess }) {
    const [email, setEmail] = useState('owner@farm.local');
    const [password, setPassword] = useState('password');
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    if (!isOpen) return null;

    async function handleLoginSubmit(e) {
        if (e) e.preventDefault();
        setLoading(true);
        setErrorMessage('');
        try {
            const res = await api.login({ email, password });
            setStoredToken(res.token);
            if (res.active_farm?.id) {
                setActiveFarmId(res.active_farm.id);
            }
            if (onLoginSuccess) {
                onLoginSuccess(res);
            }
            onClose();
        } catch (err) {
            setErrorMessage(err.message || 'Login failed. Please check your credentials.');
        } finally {
            setLoading(false);
        }
    }

    function quickFillRole(roleEmail) {
        setEmail(roleEmail);
        setPassword('password');
        setErrorMessage('');
    }

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
            <div 
                className="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 text-slate-800 dark:text-slate-100 shadow-2xl relative overflow-hidden"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Branding & Multi-Tenant Organization Banner */}
                <div className="text-center mb-6">
                    <div className="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <Tractor className="w-7 h-7" />
                    </div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-white">
                        GreenPastures Agro
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Enterprise Multi-Tenant Livestock & Dairy Management System
                    </p>
                </div>

                {/* Error Banner */}
                {errorMessage && (
                    <div className="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-500 text-xs flex items-center gap-2 font-medium">
                        <AlertCircle className="w-4 h-4 shrink-0" />
                        <span>{errorMessage}</span>
                    </div>
                )}

                {/* Login Form */}
                <form onSubmit={handleLoginSubmit} className="space-y-4">
                    <div>
                        <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                            Account Email
                        </label>
                        <div className="relative">
                            <Mail className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input 
                                type="email"
                                required
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="name@farm.local"
                                className="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                            Password
                        </label>
                        <div className="relative">
                            <Lock className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input 
                                type="password"
                                required
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="••••••••"
                                className="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 font-mono"
                            />
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-emerald-600/30 transition disabled:opacity-50 mt-2 cursor-pointer"
                    >
                        {loading ? 'Authenticating...' : 'Sign In to Farm System'}
                    </button>
                </form>

                {/* 1-Tap Demo Switcher Pills */}
                <div className="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800">
                    <span className="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block text-center mb-3">
                        ⚡ Quick-Switch Demo Profiles
                    </span>
                    <div className="grid grid-cols-2 gap-2 text-xs">
                        <button
                            type="button"
                            onClick={() => quickFillRole('owner@farm.local')}
                            className={`p-2.5 rounded-xl border text-left flex items-center gap-2 transition ${email === 'owner@farm.local' ? 'border-emerald-500 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60'}`}
                        >
                            <Building2 className="w-4 h-4 text-emerald-500 shrink-0" />
                            <div>
                                <span className="block font-semibold">Farm Owner</span>
                                <span className="text-[10px] text-slate-400 block truncate">owner@farm.local</span>
                            </div>
                        </button>

                        <button
                            type="button"
                            onClick={() => quickFillRole('vet@farm.local')}
                            className={`p-2.5 rounded-xl border text-left flex items-center gap-2 transition ${email === 'vet@farm.local' ? 'border-blue-500 bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60'}`}
                        >
                            <Stethoscope className="w-4 h-4 text-blue-500 shrink-0" />
                            <div>
                                <span className="block font-semibold">Veterinarian</span>
                                <span className="text-[10px] text-slate-400 block truncate">vet@farm.local</span>
                            </div>
                        </button>

                        <button
                            type="button"
                            onClick={() => quickFillRole('worker@farm.local')}
                            className={`p-2.5 rounded-xl border text-left flex items-center gap-2 transition ${email === 'worker@farm.local' ? 'border-amber-500 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60'}`}
                        >
                            <UserCheck className="w-4 h-4 text-amber-500 shrink-0" />
                            <div>
                                <span className="block font-semibold">Herd Manager</span>
                                <span className="text-[10px] text-slate-400 block truncate">worker@farm.local</span>
                            </div>
                        </button>

                        <button
                            type="button"
                            onClick={() => quickFillRole('auditor@livestock.gov.pk')}
                            className={`p-2.5 rounded-xl border text-left flex items-center gap-2 transition ${email === 'auditor@livestock.gov.pk' ? 'border-purple-500 bg-purple-500/10 text-purple-600 dark:text-purple-400 font-bold' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/60'}`}
                        >
                            <FileCheck className="w-4 h-4 text-purple-500 shrink-0" />
                            <div>
                                <span className="block font-semibold">Govt Inspector</span>
                                <span className="text-[10px] text-slate-400 block truncate">auditor@gov.pk</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
