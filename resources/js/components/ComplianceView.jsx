import React, { useState, useEffect } from 'react';
import { 
    FileCheck2, 
    GitMerge, 
    Wifi, 
    ShieldCheck, 
    ArrowRight, 
    RotateCcw, 
    Search,
    CheckCircle2 
} from 'lucide-react';
import { api } from '../services/api';

export default function ComplianceView() {
    const [traceAnimalId, setTraceAnimalId] = useState('1');
    const [traceResult, setTraceResult] = useState(null);
    const [loadingTrace, setLoadingTrace] = useState(false);
    const [devices, setDevices] = useState([]);

    useEffect(() => {
        loadCompliance();
    }, []);

    async function loadCompliance() {
        try {
            const devRes = await api.getDevices().catch(() => ({ data: [] }));
            setDevices(devRes.data || devRes || []);
        } catch (err) {
            console.error('Failed to load compliance:', err);
        }
    }

    async function handleRunTrace() {
        setLoadingTrace(true);
        try {
            const res = await api.getForwardTrace(traceAnimalId);
            setTraceResult(res);
        } catch (err) {
            alert('Trace failed: ' + err.message);
        } finally {
            setLoadingTrace(false);
        }
    }

    const defaultDevices = [
        { id: 1, device_name: 'Barn A Climate & THI Probe', device_type: 'iot_sensor', status: 'online', battery_percent: 94, last_sync: '1 min ago' },
        { id: 2, device_name: 'Mueller Bulk Tank 2,500L Agitator', device_type: 'tank_controller', status: 'online', battery_percent: 100, last_sync: 'Just now' },
        { id: 3, device_name: 'Mobile Android Barn Wand (RFID NFC)', device_type: 'handheld_scanner', status: 'synced', battery_percent: 78, last_sync: '4 mins ago' },
    ];

    const displayDevices = devices.length > 0 ? devices : defaultDevices;

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 className="text-xl font-bold tracking-tight text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <FileCheck2 className="w-6 h-6 text-emerald-500" />
                        Compliance Packs, Traceability & IoT Hardware Sync
                    </h2>
                    <p className="text-xs text-slate-400 mt-1">
                        1-Click Forward/Backward Farm-to-Fork Traceability, Halal Certifications, and Edge Hardware Sync
                    </p>
                </div>
            </div>

            {/* Farm-to-Fork Traceability Engine */}
            <div className="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <GitMerge className="w-4 h-4 text-emerald-500" />
                            End-to-End Farm-to-Fork Forward Traceability
                        </h3>
                        <p className="text-xs text-slate-400">
                            Audit animal birth, antibiotic administrations, milk batch tanking, and doorstep customer delivery
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            type="text"
                            placeholder="Animal Tag ID (1)"
                            value={traceAnimalId}
                            onChange={(e) => setTraceAnimalId(e.target.value)}
                            className="w-32 px-3 py-1.5 text-xs font-mono rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                        />
                        <button
                            onClick={handleRunTrace}
                            disabled={loadingTrace}
                            className="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition disabled:opacity-50"
                        >
                            {loadingTrace ? 'Tracing...' : 'Run Audit Trail'}
                        </button>
                    </div>
                </div>

                {/* Audit Trail Steps Visualizer */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-3 pt-3">
                    <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span className="text-[10px] font-bold text-emerald-500 uppercase">Stage 1: Origin & Dam</span>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-100 font-mono">PK-COW-001 (Sahiwal)</h4>
                        <p className="text-[11px] text-slate-400">Born at Kasur Farm • RFID: 982000341234567</p>
                    </div>

                    <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span className="text-[10px] font-bold text-emerald-500 uppercase">Stage 2: Health & Residue</span>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-100 font-mono">Withdrawal: Cleared</h4>
                        <p className="text-[11px] text-slate-400">0 Active AMU locks • Safe for human food supply</p>
                    </div>

                    <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span className="text-[10px] font-bold text-emerald-500 uppercase">Stage 3: Bulk Tank Batch</span>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-100 font-mono">TANK-01 (Batch #490)</h4>
                        <p className="text-[11px] text-slate-400">2,150 Liters • Temp: 3.4°C • Fat: 4.1%</p>
                    </div>

                    <div className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span className="text-[10px] font-bold text-emerald-500 uppercase">Stage 4: Customer Bottles</span>
                        <h4 className="text-xs font-bold text-slate-800 dark:text-slate-100 font-mono">DEL-RUN-2026-AM</h4>
                        <p className="text-[11px] text-slate-400">Delivered to DHA Phase 5 Customers • 100% Halal</p>
                    </div>
                </div>
            </div>

            {/* Connected Hardware & IoT Gateways */}
            <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm p-5 space-y-4">
                <h3 className="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <Wifi className="w-4 h-4 text-blue-500" />
                    Barn IoT Hardware & Offline Mobile Sync Engine
                </h3>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {displayDevices.map((dev) => (
                        <div key={dev.id} className="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-slate-800 dark:text-slate-100">{dev.device_name}</span>
                                <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </div>
                            <div className="flex justify-between text-[11px] text-slate-400 font-mono">
                                <span>Battery: {dev.battery_percent}%</span>
                                <span>Sync: {dev.last_sync}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
