import React, { useState, useEffect } from 'react';
import { 
    Package, 
    Wrench, 
    Warehouse, 
    Plus, 
    AlertTriangle, 
    CheckCircle2, 
    Calendar 
} from 'lucide-react';
import { api } from '../services/api';

export default function InventoryView() {
    const [warehouses, setWarehouses] = useState([]);
    const [items, setItems] = useState([]);
    const [assets, setAssets] = useState([]);
    const [loading, setLoading] = useState(false);
    const [activeTab, setActiveTab] = useState('items'); // items, assets, warehouses

    useEffect(() => {
        loadData();
    }, []);

    async function loadData() {
        setLoading(true);
        try {
            const [wRes, iRes, aRes] = await Promise.all([
                api.getWarehouses().catch(() => ({ data: [] })),
                api.getInventoryItems().catch(() => ({ data: [] })),
                api.getAssets().catch(() => ({ data: [] })),
            ]);
            setWarehouses(wRes.data || wRes || []);
            setItems(iRes.data || iRes || []);
            setAssets(aRes.data || aRes || []);
        } catch (err) {
            console.error('Failed to load inventory data:', err);
        } finally {
            setLoading(false);
        }
    }

    const defaultItems = [
        { id: 1, name: 'Corn Silage Feedstock', sku: 'FEED-SIL-01', category: 'feed', warehouse: 'Main Feed Bunker', quantity_on_hand: 45000, unit: 'kg', reorder_level: 10000, status: 'adequate' },
        { id: 2, name: 'Cottonseed Meal (Concentrate)', sku: 'FEED-CSM-02', category: 'feed', warehouse: 'Dry Grain Warehouse', quantity_on_hand: 3200, unit: 'kg', reorder_level: 5000, status: 'low_stock' },
        { id: 3, name: 'Oxytetracycline 20% LA Injectable', sku: 'MED-OXY-20', category: 'pharmacy', warehouse: 'Veterinary Clinic Pharmacy', quantity_on_hand: 24, unit: 'vials', reorder_level: 10, status: 'adequate' },
        { id: 4, name: 'Liquid Nitrogen Semen Straws (Alta Genetics)', sku: 'GEN-STRAW-SAH', category: 'genetics', warehouse: 'Breeding Cryo Tank', quantity_on_hand: 85, unit: 'straws', reorder_level: 20, status: 'adequate' },
    ];

    const defaultAssets = [
        { id: 1, name: 'John Deere 5075E Utility Tractor', code: 'TRAC-01', type: 'machinery', status: 'operational', hours_meter: 1420, next_service_due: '2026-11-15' },
        { id: 2, name: 'Jaylor Vertical TMR Feed Mixer 12m³', code: 'MIX-01', type: 'feed_mixer', status: 'operational', hours_meter: 890, next_service_due: '2026-10-25' },
        { id: 3, name: 'Mueller 2,500L Direct-Expansion Bulk Milk Tank', code: 'TANK-01', type: 'dairy_equipment', status: 'operational', hours_meter: null, next_service_due: '2026-12-01' },
    ];

    const displayItems = items.length > 0 ? items : defaultItems;
    const displayAssets = assets.length > 0 ? assets : defaultAssets;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <Package className="w-6 h-6 text-emerald-500" />
                        Multi-Warehouse Inventory & Farm Machinery
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        Track raw materials, feed bunkers, veterinary drug stocks, and asset maintenance
                    </p>
                </div>
            </div>

            {/* Navigation Tabs */}
            <div className="flex border-b border-slate-200 dark:border-slate-800">
                <button
                    onClick={() => setActiveTab('items')}
                    className={`py-3 px-4 text-xs font-semibold border-b-2 transition ${activeTab === 'items' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'}`}
                >
                    Stock Inventory Items
                </button>
                <button
                    onClick={() => setActiveTab('assets')}
                    className={`py-3 px-4 text-xs font-semibold border-b-2 transition ${activeTab === 'assets' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-400 hover:text-slate-200'}`}
                >
                    Tractors & Machinery Assets
                </button>
            </div>

            {activeTab === 'items' && (
                <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-slate-50 dark:bg-slate-800/60 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th className="p-3.5">SKU & Item Name</th>
                                <th className="p-3.5">Category</th>
                                <th className="p-3.5">Warehouse Storage</th>
                                <th className="p-3.5 text-right">Quantity on Hand</th>
                                <th className="p-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                            {displayItems.map((item) => (
                                <tr key={item.id} className="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td className="p-3.5 font-sans">
                                        <span className="font-mono text-[10px] text-slate-400 block">{item.sku}</span>
                                        <span className="font-bold text-slate-800 dark:text-slate-100">{item.name}</span>
                                    </td>
                                    <td className="p-3.5 font-sans capitalize text-slate-500">
                                        {item.category}
                                    </td>
                                    <td className="p-3.5 font-sans text-slate-600 dark:text-slate-300">
                                        {item.warehouse || 'Central Facility'}
                                    </td>
                                    <td className="p-3.5 text-right font-bold text-slate-800 dark:text-slate-100">
                                        {item.quantity_on_hand.toLocaleString()} {item.unit}
                                    </td>
                                    <td className="p-3.5 text-center font-sans">
                                        {item.status === 'low_stock' ? (
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-500 border border-amber-500/30">
                                                Low Stock Alert
                                            </span>
                                        ) : (
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-500 border border-emerald-500/30">
                                                In Stock
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {activeTab === 'assets' && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {displayAssets.map((asset) => (
                        <div 
                            key={asset.id}
                            className="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400">
                                        {asset.code}
                                    </span>
                                    <h4 className="text-sm font-bold text-slate-800 dark:text-slate-100 mt-1">
                                        {asset.name}
                                    </h4>
                                </div>
                                <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            </div>

                            <div className="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-800 font-mono">
                                <div>
                                    <span className="text-slate-400 block font-sans">Operating Hours:</span>
                                    <span className="font-bold text-slate-700 dark:text-slate-200">{asset.hours_meter ? `${asset.hours_meter} hrs` : 'N/A'}</span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block font-sans">Next Service:</span>
                                    <span className="font-bold text-slate-700 dark:text-slate-200">{asset.next_service_due}</span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
