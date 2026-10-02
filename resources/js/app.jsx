import React, { useState, useEffect, createContext, useContext } from 'react';
import { createRoot } from 'react-dom/client';

// Page-level components
import LoginPage from './components/LoginPage';
import Sidebar from './components/Sidebar';
import Header from './components/Header';

// Views
import DashboardView from './components/DashboardView';
import LivestockView from './components/LivestockView';
import MilkView from './components/MilkView';
import HealthView from './components/HealthView';
import BreedingView from './components/BreedingView';
import FeedView from './components/FeedView';
import PastureView from './components/PastureView';
import FeedlotView from './components/FeedlotView';
import InventoryView from './components/InventoryView';
import SalesView from './components/SalesView';
import FinanceView from './components/FinanceView';
import ComplianceView from './components/ComplianceView';
import TasksView from './components/TasksView';
import TeamView from './components/TeamView';

// Modals
import { RegisterAnimalModal, LogMilkModal } from './components/Modals';
import AnimalPassportDrawer from './components/AnimalPassportDrawer';
import PreferencesModal from './components/PreferencesModal';
import LoginModal from './components/LoginModal'; // kept for "switch account" in header

import { api, getActiveFarmId, setActiveFarmId, setStoredToken, getStoredToken } from './services/api';

// ─── Dynamic Tab Permission Mapping ──────────────────────────────────────────
const TAB_PERMISSIONS = {
    dashboard: null,
    livestock: 'animals.view',
    milk: 'milk.record',
    feed: 'feed.manage',
    health: 'health.diagnose',
    breeding: 'animals.view',
    feedlots: 'animals.view',
    pastures: 'animals.view',
    inventory: 'animals.view',
    sales: 'finance.view',
    finances: 'finance.view',
    compliance: 'audit.view',
    tasks: null,
    team: 'team.manage',
};

// ─── App Context (shared state) ───────────────────────────────────────────────
export const AppContext = createContext(null);
export function useApp() { return useContext(AppContext); }

// ─── Loading screen ────────────────────────────────────────────────────────────
function LoadingScreen() {
    return (
        <div className="min-h-screen flex items-center justify-center app-main">
            <div className="flex flex-col items-center gap-3">
                <div className="w-8 h-8 border-[3px] border-green-500 border-t-transparent rounded-full animate-spin" />
                <p className="text-sm text-slate-400">Loading GreenPastures…</p>
            </div>
        </div>
    );
}

function App() {
    // ── Auth state ───────────────────────────────────────────────────────────
    const [authState, setAuthState] = useState('loading'); // 'loading' | 'guest' | 'authenticated'
    const [currentUser, setCurrentUser] = useState(null);
    const [userPermissions, setUserPermissions] = useState([]);
    const [farmsList, setFarmsList] = useState([]);
    const [activeFarm, setActiveFarm] = useState(null);
    const [userPreferences, setUserPreferences] = useState(null);

    // ── UI state ─────────────────────────────────────────────────────────────
    const [currentTab, setCurrentTab] = useState('dashboard');
    const [dashboardData, setDashboardData] = useState(null);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [animalsList, setAnimalsList] = useState([]);
    const [isDark, setIsDark] = useState(() => localStorage.getItem('farm_theme') === 'dark');

    // ── Modal flags ──────────────────────────────────────────────────────────
    const [isRegisterAnimalOpen, setIsRegisterAnimalOpen] = useState(false);
    const [isLogMilkOpen, setIsLogMilkOpen] = useState(false);
    const [isPreferencesOpen, setIsPreferencesOpen] = useState(false);
    const [isSwitchAccountOpen, setIsSwitchAccountOpen] = useState(false);
    const [selectedPassportAnimalId, setSelectedPassportAnimalId] = useState(null);

    // ── Theme sync → localStorage ─────────────────────────────────────────────
    useEffect(() => {
        if (isDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('farm_theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('farm_theme', 'light');
        }
    }, [isDark]);

    // ── Bootstrap: check stored token & load data ─────────────────────────────
    useEffect(() => {
        bootstrapApp();
    }, []);

    async function bootstrapApp() {
        const storedToken = getStoredToken();
        if (!storedToken) {
            setAuthState('guest');
            return;
        }
        try {
            const meRes = await api.getMe().catch(() => null);
            if (meRes?.user) {
                applyAuthData(meRes);
                setAuthState('authenticated');
            } else {
                setAuthState('guest');
            }
        } catch {
            setAuthState('guest');
        }
    }

    function applyAuthData(data) {
        if (data.token) {
            setStoredToken(data.token);
        }
        if (data.user) setCurrentUser(data.user);

        if (data.permissions) {
            setUserPermissions(data.permissions);
        } else if (data.user?.permissions) {
            setUserPermissions(data.user.permissions);
        }

        if (data.preferences) {
            setUserPreferences(data.preferences);
            const dir = ['ur', 'ar'].includes(data.preferences.locale) ? 'rtl' : 'ltr';
            document.documentElement.setAttribute('dir', dir);
        }

        if (data.farms?.length) {
            setFarmsList(data.farms);
            const savedId = getActiveFarmId();
            const matched = data.farms.find(f => String(f.id) === String(savedId))
                || data.active_farm
                || data.farms[0];
            setActiveFarm(matched);
            if (matched?.id) setActiveFarmId(matched.id);
        }
    }

    // Dynamic permission checker
    function hasPermission(permissionSlug) {
        if (!permissionSlug) return true;
        const role = currentUser?.role || 'Farm Owner';
        if (role === 'Farm Owner' || userPermissions.includes('*')) return true;
        return userPermissions.includes(permissionSlug);
    }

    // Load dashboard + animals after auth is set
    useEffect(() => {
        if (authState === 'authenticated') {
            loadDashboard();
            loadAnimals();
        }
    }, [authState]);

    async function loadDashboard() {
        setIsRefreshing(true);
        try {
            const data = await api.getDashboard();
            setDashboardData(data);
            if (data.farm && !activeFarm) setActiveFarm(data.farm);
        } catch (err) {
            console.error('Dashboard load failed:', err);
        } finally {
            setIsRefreshing(false);
        }
    }

    async function loadAnimals() {
        try {
            const res = await api.getAnimals();
            setAnimalsList(res.data || []);
        } catch (err) {
            console.error('Animals load failed:', err);
        }
    }

    async function handleFarmSwitch(farm) {
        setActiveFarm(farm);
        setActiveFarmId(farm.id);
        try {
            const meRes = await api.getMe();
            if (meRes) applyAuthData(meRes);
        } catch (err) {
            console.error('Failed to sync farm context:', err);
        }
        loadDashboard();
        loadAnimals();
    }

    function handleLoginSuccess(authData) {
        setStoredToken(authData.token);
        applyAuthData(authData);
        setAuthState('authenticated');
        setCurrentTab('dashboard');
        setIsSwitchAccountOpen(false);
    }

    function handleLogout() {
        setStoredToken('');
        setCurrentUser(null);
        setDashboardData(null);
        setAnimalsList([]);
        setAuthState('guest');
    }

    async function handleToggleTaskStatus(id, newStatus) {
        try {
            await api.updateTaskStatus(id, newStatus);
            await loadDashboard();
        } catch (err) {
            console.error('Task update failed:', err);
        }
    }

    // ── Dynamic Permission Tab Guard ──────────────────────────────────────────
    const userRole = currentUser?.role || 'Farm Owner';

    function canAccessTab(tab) {
        if (userRole === 'Farm Owner') return true;
        const reqPerm = TAB_PERMISSIONS[tab];
        if (!reqPerm) return true;
        if (tab === 'team') return hasPermission('team.manage') || hasPermission('audit.view');
        return hasPermission(reqPerm);
    }

    function navigateTo(tab) {
        if (!canAccessTab(tab)) return;
        setCurrentTab(tab);
    }

    const activeWithdrawals = dashboardData?.active_withdrawals || [];

    // ── Render states ─────────────────────────────────────────────────────────
    if (authState === 'loading') return <LoadingScreen />;

    if (authState === 'guest') {
        return <LoginPage onLoginSuccess={handleLoginSuccess} />;
    }

    // ── Authenticated shell ───────────────────────────────────────────────────
    return (
        <AppContext.Provider value={{ currentUser, userPreferences, userRole, userPermissions, hasPermission, activeFarm, farmsList, onSelectFarm: handleFarmSwitch }}>
            <div className="flex h-screen overflow-hidden">
                {/* Sidebar */}
                <Sidebar
                    currentTab={currentTab}
                    setCurrentTab={navigateTo}
                    activeWithdrawalsCount={activeWithdrawals.length}
                    userRole={userRole}
                    hasPermission={hasPermission}
                />

                {/* Main area */}
                <div className="flex-1 flex flex-col min-w-0 overflow-hidden app-main">
                    <Header
                        farm={activeFarm || dashboardData?.farm}
                        farms={farmsList}
                        onSelectFarm={handleFarmSwitch}
                        climate={dashboardData?.climate}
                        activeWithdrawals={activeWithdrawals}
                        onRefresh={loadDashboard}
                        isRefreshing={isRefreshing}
                        isDark={isDark}
                        setIsDark={setIsDark}
                        user={currentUser}
                        preferences={userPreferences}
                        onOpenPreferences={() => setIsPreferencesOpen(true)}
                        onOpenLogin={() => setIsSwitchAccountOpen(true)}
                        onLogout={handleLogout}
                    />

                    {/* Role badge for non-owner roles */}
                    {userRole !== 'Farm Owner' && (
                        <div className="px-5 py-1.5 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-800 text-[11px] text-amber-700 dark:text-amber-400 font-medium">
                            Signed in as <strong>{userRole}</strong> — some modules are restricted for your role.
                        </div>
                    )}

                    <main className="flex-1 overflow-y-auto p-5">
                        <div className="max-w-screen-xl mx-auto">
                            {currentTab === 'dashboard' && (
                                <DashboardView
                                    dashboardData={dashboardData}
                                    onNavigate={navigateTo}
                                    onToggleTaskStatus={handleToggleTaskStatus}
                                    onOpenModal={() => setIsLogMilkOpen(true)}
                                    preferences={userPreferences}
                                    userRole={userRole}
                                />
                            )}
                            {currentTab === 'livestock' && (
                                <LivestockView
                                    onOpenRegisterModal={() => setIsRegisterAnimalOpen(true)}
                                    hasPermission={hasPermission}
                                />
                            )}
                            {currentTab === 'milk' && (
                                <MilkView
                                    onOpenLogMilkModal={() => setIsLogMilkOpen(true)}
                                />
                            )}
                            {currentTab === 'health' && (
                                <HealthView
                                    onOpenTreatmentModal={() => alert('Select an animal in Livestock tab first')}
                                />
                            )}
                            {currentTab === 'breeding' && (
                                <BreedingView
                                    onOpenBreedingModal={() => alert('New insemination record')}
                                />
                            )}
                            {currentTab === 'feed' && <FeedView />}
                            {currentTab === 'pastures' && <PastureView />}
                            {currentTab === 'feedlots' && <FeedlotView />}
                            {currentTab === 'inventory' && <InventoryView />}
                            {currentTab === 'sales' && <SalesView />}
                            {currentTab === 'finances' && <FinanceView />}
                            {currentTab === 'compliance' && <ComplianceView />}
                            {currentTab === 'tasks' && (
                                <TasksView
                                    onOpenNewTaskModal={() => alert('New task dialog')}
                                />
                            )}
                            {currentTab === 'team' && (
                                <TeamView
                                    user={currentUser}
                                    hasPermission={hasPermission}
                                />
                            )}
                        </div>
                    </main>
                </div>

                {/* Modals */}
                <RegisterAnimalModal
                    isOpen={isRegisterAnimalOpen}
                    onClose={() => setIsRegisterAnimalOpen(false)}
                    onCreated={() => { loadAnimals(); loadDashboard(); }}
                />

                <LogMilkModal
                    isOpen={isLogMilkOpen}
                    onClose={() => setIsLogMilkOpen(false)}
                    onLogged={loadDashboard}
                    animals={animalsList}
                    activeWithdrawals={activeWithdrawals}
                />

                <PreferencesModal
                    isOpen={isPreferencesOpen}
                    onClose={() => setIsPreferencesOpen(false)}
                    currentPreferences={userPreferences}
                    onPreferencesUpdated={(newPref) => {
                        setUserPreferences(newPref);
                        const dir = ['ur', 'ar'].includes(newPref.locale) ? 'rtl' : 'ltr';
                        document.documentElement.setAttribute('dir', dir);
                        loadDashboard();
                    }}
                />

                {/* Switch account overlay — reuse the existing LoginModal */}
                <LoginModal
                    isOpen={isSwitchAccountOpen}
                    onClose={() => setIsSwitchAccountOpen(false)}
                    onLoginSuccess={handleLoginSuccess}
                />

                <AnimalPassportDrawer
                    animalId={selectedPassportAnimalId}
                    isOpen={Boolean(selectedPassportAnimalId)}
                    onClose={() => setSelectedPassportAnimalId(null)}
                    onActionSuccess={() => { loadAnimals(); loadDashboard(); }}
                />
            </div>
        </AppContext.Provider>
    );
}

const rootEl = document.getElementById('root');
if (rootEl) {
    createRoot(rootEl).render(<App />);
}
