import React, { useState, useEffect } from 'react';
import { 
    X, 
    Globe, 
    DollarSign, 
    Clock, 
    Calendar, 
    Sparkles, 
    Check, 
    Save, 
    RefreshCw 
} from 'lucide-react';
import { api } from '../services/api';

export default function PreferencesModal({ isOpen, onClose, currentPreferences, onPreferencesUpdated }) {
    const [locale, setLocale] = useState('en');
    const [currency, setCurrency] = useState('PKR');
    const [timezone, setTimezone] = useState('Asia/Karachi');
    const [dateFormat, setDateFormat] = useState('Y-m-d');
    const [timeFormat, setTimeFormat] = useState('24h');
    const [currencyPosition, setCurrencyPosition] = useState('before');
    const [decimalSep, setDecimalSep] = useState('.');
    const [thousandSep, setThousandSep] = useState(',');

    const [previewData, setPreviewData] = useState(null);
    const [loadingPreview, setLoadingPreview] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (currentPreferences) {
            setLocale(currentPreferences.locale || 'en');
            setCurrency(currentPreferences.currency || 'PKR');
            setTimezone(currentPreferences.timezone || 'Asia/Karachi');
            setDateFormat(currentPreferences.date_format || 'Y-m-d');
            setTimeFormat(currentPreferences.time_format || '24h');
            setCurrencyPosition(currentPreferences.currency_symbol_position || 'before');
            setDecimalSep(currentPreferences.decimal_separator || '.');
            setThousandSep(currentPreferences.thousands_separator || ',');
        }
    }, [currentPreferences, isOpen]);

    useEffect(() => {
        if (isOpen) {
            fetchPreview();
        }
    }, [isOpen, locale, currency, timezone, dateFormat, timeFormat, currencyPosition, decimalSep, thousandSep]);

    async function fetchPreview() {
        setLoadingPreview(true);
        try {
            const res = await api.previewPreferences({
                locale,
                currency,
                timezone,
                date_format: dateFormat,
                time_format: timeFormat,
                currency_symbol_position: currencyPosition,
                decimal_separator: decimalSep,
                thousands_separator: thousandSep,
                sample_amount: 148500.50,
            });
            setPreviewData(res);
        } catch (err) {
            console.error('Preview failed:', err);
        } finally {
            setLoadingPreview(false);
        }
    }

    async function handleSave(e) {
        e.preventDefault();
        setSaving(true);
        try {
            const updated = await api.updatePreferences({
                locale,
                currency,
                timezone,
                date_format: dateFormat,
                time_format: timeFormat,
                currency_symbol_position: currencyPosition,
                decimal_separator: decimalSep,
                thousands_separator: thousandSep,
            });

            // Adjust document RTL/LTR for Urdu / Arabic
            if (locale === 'ur' || locale === 'ar') {
                document.documentElement.setAttribute('dir', 'rtl');
            } else {
                document.documentElement.setAttribute('dir', 'ltr');
            }

            if (onPreferencesUpdated) {
                onPreferencesUpdated(updated.preferences || updated);
            }
            onClose();
        } catch (err) {
            alert('Failed to save user preferences: ' + err.message);
        } finally {
            setSaving(false);
        }
    }

    if (!isOpen) return null;

    return (
        <div className="modal-backdrop" onClick={onClose}>
            <div
                className="modal-box p-6 text-slate-800 dark:text-slate-100"
                onClick={(e) => e.stopPropagation()}
            >
                {/* Header */}
                <div className="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div className="flex items-center gap-2.5">
                        <div className="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <Globe className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-800 dark:text-slate-100">User Regional & Display Preferences</h3>
                            <span className="text-xs text-slate-400">Configure language, currency, timezone, and date/time formats</span>
                        </div>
                    </div>
                    <button 
                        onClick={onClose}
                        className="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-800 dark:hover:text-white transition"
                    >
                        <X className="w-4 h-4" />
                    </button>
                </div>

                {/* Live Formatted Output Preview (Laravel Core Number & Date Formatters) */}
                <div className="my-5 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                            <Sparkles className="w-3.5 h-3.5 text-emerald-500" />
                            Live Agritech Format Preview (Laravel Engine)
                        </span>
                        {loadingPreview && <RefreshCw className="w-3.5 h-3.5 animate-spin text-emerald-500" />}
                    </div>
                    <div className="grid grid-cols-3 gap-3 text-xs">
                        <div className="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900/60">
                            <span className="text-[10px] text-slate-400 block font-semibold">Formatted Milk Payout</span>
                            <span className="text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400">
                                {previewData?.formatted_currency || `${currency} 148,500.50`}
                            </span>
                        </div>
                        <div className="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900/60">
                            <span className="text-[10px] text-slate-400 block font-semibold">Formatted Date</span>
                            <span className="text-sm font-bold font-mono text-slate-700 dark:text-slate-200">
                                {previewData?.formatted_date || '2026-10-02'}
                            </span>
                        </div>
                        <div className="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-emerald-100 dark:border-emerald-900/60">
                            <span className="text-[10px] text-slate-400 block font-semibold">Formatted Time</span>
                            <span className="text-sm font-bold font-mono text-slate-700 dark:text-slate-200">
                                {previewData?.formatted_time || '16:45'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Form Controls */}
                <form onSubmit={handleSave} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        {/* Language Selection */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Interface Language
                            </label>
                            <select 
                                value={locale} 
                                onChange={(e) => setLocale(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="en">English (US/UK)</option>
                                <option value="ur">اردو — Urdu (RTL)</option>
                                <option value="ar">العربية — Arabic (RTL)</option>
                                <option value="es">Español — Spanish</option>
                                <option value="fr">Français — French</option>
                            </select>
                        </div>

                        {/* Currency Selection */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Operating Currency
                            </label>
                            <select 
                                value={currency} 
                                onChange={(e) => setCurrency(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 font-mono"
                            >
                                <option value="PKR">PKR (Pakistani Rupee - ₨)</option>
                                <option value="USD">USD (US Dollar - $)</option>
                                <option value="EUR">EUR (Euro - €)</option>
                                <option value="AED">AED (UAE Dirham - د.إ)</option>
                                <option value="SAR">SAR (Saudi Riyal - ر.س)</option>
                                <option value="GBP">GBP (British Pound - £)</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {/* Timezone */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Farm Timezone
                            </label>
                            <select 
                                value={timezone} 
                                onChange={(e) => setTimezone(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="Asia/Karachi">Asia/Karachi (PKT +05:00)</option>
                                <option value="Asia/Dubai">Asia/Dubai (GST +04:00)</option>
                                <option value="UTC">UTC (Coordinated Universal Time)</option>
                                <option value="America/New_York">America/New York (EST/EDT)</option>
                                <option value="Europe/London">Europe/London (GMT/BST)</option>
                            </select>
                        </div>

                        {/* Date Format */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Date Format
                            </label>
                            <select 
                                value={dateFormat} 
                                onChange={(e) => setDateFormat(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 font-mono"
                            >
                                <option value="Y-m-d">YYYY-MM-DD (ISO - 2026-10-02)</option>
                                <option value="d-m-Y">DD-MM-YYYY (02-10-2026)</option>
                                <option value="d/m/Y">DD/MM/YYYY (02/10/2026)</option>
                                <option value="m/d/Y">MM/DD/YYYY (10/02/2026)</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {/* Time Format */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Time Format
                            </label>
                            <select 
                                value={timeFormat} 
                                onChange={(e) => setTimeFormat(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="24h">24-hour Clock (16:45)</option>
                                <option value="12h">12-hour Clock (04:45 PM)</option>
                            </select>
                        </div>

                        {/* Currency Symbol Position */}
                        <div>
                            <label className="text-xs font-semibold text-slate-500 dark:text-slate-400 block mb-1.5">
                                Currency Symbol Position
                            </label>
                            <select 
                                value={currencyPosition} 
                                onChange={(e) => setCurrencyPosition(e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500"
                            >
                                <option value="before">Before Amount (PKR 148,500)</option>
                                <option value="after">After Amount (148,500 PKR)</option>
                            </select>
                        </div>
                    </div>

                    <div className="pt-4 flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            onClick={onClose}
                            className="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={saving}
                            className="flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider shadow-md hover:shadow-emerald-600/30 transition disabled:opacity-50"
                        >
                            <Save className="w-4 h-4" />
                            <span>{saving ? 'Saving...' : 'Apply Preferences'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
