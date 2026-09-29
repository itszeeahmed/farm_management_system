import React, { useState, useEffect } from 'react';
import { 
    CheckSquare, 
    Plus, 
    Clock, 
    CheckCircle2, 
    AlertCircle, 
    User 
} from 'lucide-react';
import { api } from '../services/api';

export default function TasksView({ onOpenNewTaskModal }) {
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadTasks();
    }, []);

    async function loadTasks() {
        setLoading(true);
        try {
            const res = await api.getTasks();
            setTasks(res.data || []);
        } catch (err) {
            console.error('Failed to load tasks:', err);
        } finally {
            setLoading(false);
        }
    }

    async function handleToggleStatus(id, currentStatus) {
        const nextStatus = currentStatus === 'completed' ? 'pending' : 'completed';
        try {
            await api.updateTaskStatus(id, nextStatus);
            setTasks(prev => prev.map(t => t.id === id ? { ...t, status: nextStatus } : t));
        } catch (err) {
            console.error('Failed to update task:', err);
        }
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <div>
                    <h3 className="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <CheckSquare className="w-5 h-5 text-emerald-500" />
                        Farm Operations & Workforce Tasks
                    </h3>
                    <p className="text-xs text-slate-500 dark:text-slate-400">
                        Daily chores, scheduled veterinary treatments, maternity pen preparation, and feed reorders
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onOpenNewTaskModal}
                        className="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold flex items-center gap-1.5 transition shadow-sm"
                    >
                        <Plus className="w-4 h-4" /> Create Farm Task
                    </button>
                </div>
            </div>

            {/* Task Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {loading ? (
                    <div className="col-span-full p-8 text-center text-xs text-slate-400">Loading tasks...</div>
                ) : tasks.map((task) => {
                    const isDone = task.status === 'completed';
                    return (
                        <div 
                            key={task.id}
                            className={`p-5 rounded-2xl bg-white dark:bg-slate-900 border transition flex flex-col justify-between gap-4 ${
                                isDone 
                                    ? 'border-slate-200 dark:border-slate-800 opacity-60' 
                                    : task.priority === 'urgent'
                                        ? 'border-rose-500/40 shadow-sm'
                                        : 'border-slate-200 dark:border-slate-800 shadow-sm hover:border-emerald-500/40'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between gap-2">
                                    <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
                                        task.priority === 'urgent' 
                                            ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' 
                                            : task.priority === 'high'
                                                ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20'
                                                : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                    }`}>
                                        {task.priority}
                                    </span>
                                    <span className="text-[11px] font-mono text-slate-400 flex items-center gap-1">
                                        <Clock className="w-3 h-3" />
                                        {new Date(task.due_date).toLocaleDateString()}
                                    </span>
                                </div>

                                <h4 className={`text-sm font-bold text-slate-900 dark:text-white mt-3 ${isDone ? 'line-through text-slate-400' : ''}`}>
                                    {task.title}
                                </h4>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                    {task.description}
                                </p>
                            </div>

                            <div className="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                <span className="text-[11px] text-slate-400 capitalize">
                                    Category: {task.category?.replace('_', ' ')}
                                </span>
                                <button
                                    onClick={() => handleToggleStatus(task.id, task.status)}
                                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition ${
                                        isDone 
                                            ? 'bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200' 
                                            : 'bg-emerald-600 hover:bg-emerald-700 text-white'
                                    }`}
                                >
                                    <CheckCircle2 className="w-3.5 h-3.5" />
                                    {isDone ? 'Completed' : 'Mark Done'}
                                </button>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
