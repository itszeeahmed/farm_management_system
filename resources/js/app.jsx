import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import DashboardView from './components/DashboardView';
import LivestockView from './components/LivestockView';
import MilkView from './components/MilkView';
import HealthView from './components/HealthView';
import BreedingView from './components/BreedingView';
import FeedView from './components/FeedView';
import FinanceView from './components/FinanceView';
import TasksView from './components/TasksView';
import { RegisterAnimalModal, LogMilkModal } from './components/Modals';
import { api } from './services/api';

function App() {
    const [currentTab, setCurrentTab] = useState('dashboard');
    const [dashboardData, setDashboardData] = useState(null);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [isDark, setIsDark] = useState(true);
    const [animalsList, setAnimalsList] = useState([]);
    
    // Modal states
    const [isRegisterAnimalOpen, setIsRegisterAnimalOpen] = useState(false);
    const [isLogMilkOpen, setIsLogMilkOpen] = useState(false);

    useEffect(() => {
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }, [isDark]);

    useEffect(() => {
        loadDashboard();
        loadAnimals();
    }, []);

    async function loadDashboard() {
        setIsRefreshing(true);
        try {
            const data = await api.getDashboard();
            setDashboardData(data);
        } catch (err) {
            console.error('Failed to load dashboard:', err);
        } finally {
            setIsRefreshing(false);
        }
    }

    async function loadAnimals() {
        try {
            const res = await api.getAnimals();
            setAnimalsList(res.data || []);
        } catch (err) {
            console.error('Failed to load animals list:', err);
        }
    }

    async function handleToggleTaskStatus(id, newStatus) {
        try {
            await api.updateTaskStatus(id, newStatus);
            await loadDashboard();
        } catch (err) {
            console.error('Failed to update task:', err);
        }
    }

    const activeWithdrawals = dashboardData?.active_withdrawals || [];

    return (
        <div className="flex h-screen overflow-hidden bg-slate-100 dark:bg-slate-950 font-sans selection:bg-emerald-500 selection:text-white">
            {/* Sidebar Navigation */}
            <Sidebar 
                currentTab={currentTab} 
                setCurrentTab={setCurrentTab} 
                activeWithdrawalsCount={activeWithdrawals.length} 
            />

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Header */}
                <Header 
                    farm={dashboardData?.farm}
                    climate={dashboardData?.climate}
                    activeWithdrawals={activeWithdrawals}
                    onRefresh={loadDashboard}
                    isRefreshing={isRefreshing}
                    isDark={isDark}
                    setIsDark={setIsDark}
                    openModal={(type) => {
                        if (type === 'quickAction') setIsLogMilkOpen(true);
                        if (type === 'withdrawals') setCurrentTab('health');
                    }}
                />

                {/* Scrollable View Container */}
                <main className="flex-1 overflow-y-auto p-6">
                    <div className="max-w-7xl mx-auto">
                        {currentTab === 'dashboard' && (
                            <DashboardView 
                                dashboardData={dashboardData}
                                onNavigate={setCurrentTab}
                                onToggleTaskStatus={handleToggleTaskStatus}
                                onOpenModal={setIsLogMilkOpen}
                            />
                        )}

                        {currentTab === 'livestock' && (
                            <LivestockView 
                                onOpenRegisterModal={() => setIsRegisterAnimalOpen(true)}
                            />
                        )}

                        {currentTab === 'milk' && (
                            <MilkView 
                                onOpenLogMilkModal={() => setIsLogMilkOpen(true)}
                            />
                        )}

                        {currentTab === 'health' && (
                            <HealthView 
                                onOpenTreatmentModal={() => alert('Please select an animal in the Livestock tab to administer medicine')}
                            />
                        )}

                        {currentTab === 'breeding' && (
                            <BreedingView 
                                onOpenBreedingModal={() => alert('New insemination record form')}
                            />
                        )}

                        {currentTab === 'feed' && (
                            <FeedView />
                        )}

                        {currentTab === 'finances' && (
                            <FinanceView />
                        )}

                        {currentTab === 'tasks' && (
                            <TasksView 
                                onOpenNewTaskModal={() => alert('New task dialog')}
                            />
                        )}
                    </div>
                </main>
            </div>

            {/* Modals */}
            <RegisterAnimalModal
                isOpen={isRegisterAnimalOpen}
                onClose={() => setIsRegisterAnimalOpen(false)}
                onCreated={() => {
                    loadAnimals();
                    loadDashboard();
                }}
            />

            <LogMilkModal
                isOpen={isLogMilkOpen}
                onClose={() => setIsLogMilkOpen(false)}
                onLogged={() => {
                    loadDashboard();
                }}
                animals={animalsList}
                activeWithdrawals={activeWithdrawals}
            />
        </div>
    );
}

const rootEl = document.getElementById('root');
if (rootEl) {
    createRoot(rootEl).render(<App />);
}
