import React, { useState, useEffect } from 'react';
import { 
    Wheat, 
    AlertTriangle, 
    CheckCircle2, 
    ArrowDown, 
    Package, 
    Plus
} from 'lucide-react';
import { api } from '../services/api';

export default function FeedView() {
    const [feeds, setFeeds] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadFeeds();
    }, []);

    async function loadFeeds() {
        setLoading(true);
        try {
            const res = await api.getFeeds();
            setFeeds(res.data || []);
        } catch (err) {
            console.error('Failed to load feed inventory:', err);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <Wheat className="w-5 h-5 text-amber-500" />
                        Feed, Rations & Fodder Inventory
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Total Mixed Ration (TMR), concentrate silos, green fodder replenishment, and automated reorder alerts
                    </p>
                </div>
            </div>

            {/* Feed Stock Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {loading ? (
                    <div className="col-span-full p-8 text-center text-xs text-slate-400">Loading feed stock...</div>
                ) : feeds.map((item) => {
                    const isLow = parseFloat(item.current_stock) <= parseFloat(item.minimum_stock_alert);
                    const stock = parseFloat(item.current_stock);
                    const minStock = parseFloat(item.minimum_stock_alert);

                    return (
                        <div 
                            key={item.id}
                            className={`p-5 rounded-2xl bg-white dark:bg-slate-900 border shadow-sm flex flex-col justify-between gap-3 ${
                                isLow ? 'border-rose-500/40 bg-rose-50/10' : 'border-slate-200 dark:border-slate-800'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between">
                                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {item.category?.replace('_', ' ')}
                                    </span>
                                    {isLow ? (
                                        <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-rose-500 text-white animate-pulse">
                                            Low Stock
                                        </span>
                                    ) : (
                                        <span className="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-500">
                                            Normal
                                        </span>
                                    )}
                                </div>
                                <h4 className="font-bold text-sm text-slate-900 dark:text-white mt-2">
                                    {item.name}
                                </h4>
                                <div className="mt-2 flex items-baseline gap-1.5">
                                    <span className="text-2xl font-bold font-mono text-slate-900 dark:text-white">
                                        {stock.toLocaleString()}
                                    </span>
                                    <span className="text-xs text-slate-500 font-semibold">{item.unit}</span>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-slate-100 dark:border-slate-800/80 text-xs text-slate-500 space-y-1">
                                <div className="flex justify-between">
                                    <span>Reorder Threshold:</span>
                                    <span className="font-mono font-medium">{minStock.toLocaleString()} {item.unit}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Unit Cost:</span>
                                    <span className="font-mono font-medium">PKR {item.cost_per_unit || 45} / {item.unit}</span>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
