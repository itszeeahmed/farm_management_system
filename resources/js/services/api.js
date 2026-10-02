// Enterprise Farm Management System API Service Layer

const CONFIG = window.__FARM_CONFIG__ || {};
const API_BASE = CONFIG.apiUrl || '/farm_management_system/public/api/v1';

export function getStoredToken() {
    return localStorage.getItem('farm_auth_token') || '';
}

export function setStoredToken(token) {
    if (token) {
        localStorage.setItem('farm_auth_token', token);
    } else {
        localStorage.removeItem('farm_auth_token');
    }
}

export function getActiveFarmId() {
    return localStorage.getItem('active_farm_id') || '1';
}

export function setActiveFarmId(farmId) {
    localStorage.setItem('active_farm_id', String(farmId));
}

async function request(endpoint, options = {}) {
    const url = `${API_BASE}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
    const token = getStoredToken();
    const activeFarmId = getActiveFarmId();

    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CONFIG.csrfToken || '',
        'X-Farm-Id': activeFarmId,
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        ...options.headers,
    };

    try {
        const response = await fetch(url, {
            ...options,
            headers,
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: response.statusText }));
            throw new Error(errorData.message || `HTTP Error ${response.status}`);
        }
        return await response.json();
    } catch (err) {
        console.warn(`[API] ${options.method || 'GET'} ${url} error:`, err.message);
        throw err;
    }
}

export const api = {
    // 0. Auth, Tenant & Onboarding Management
    login: (credentials) => request('/auth/login', { method: 'POST', body: JSON.stringify(credentials) }),
    getMe: () => request('/auth/me'),
    logout: () => request('/auth/logout', { method: 'POST' }),
    getFarms: () => request('/farms'),
    registerEnterprise: (data) => request('/onboarding/register', { method: 'POST', body: JSON.stringify(data) }),

    // Team & User Access Management
    getTeam: () => request('/team'),
    inviteTeamMember: (data) => request('/team/invite', { method: 'POST', body: JSON.stringify(data) }),
    updateTeamMemberRole: (id, data) => request(`/team/${id}/role`, { method: 'PUT', body: JSON.stringify(data) }),
    revokeTeamMember: (id) => request(`/team/${id}`, { method: 'DELETE' }),

    // User Preferences & i18n
    getPreferences: () => request('/user/preferences'),
    updatePreferences: (data) => request('/user/preferences', { method: 'POST', body: JSON.stringify(data) }),
    previewPreferences: (data) => request('/user/preferences/preview', { method: 'POST', body: JSON.stringify(data) }),

    // System Health & Hierarchy
    getHealth: () => request('/system/health'),
    getHierarchy: () => request('/hierarchy'),
    getRoles: () => request('/roles'),
    getAuditLogs: (params = '') => request(`/audit-logs${params ? `?${params}` : ''}`),
    getAnimalGroups: () => request('/animal-groups'),

    // 1. Dashboard
    getDashboard: () => request('/dashboard'),

    // 2. Animals & Pedigree
    getAnimals: (params = '') => request(`/animals${params ? `?${params}` : ''}`),
    getAnimal: (id) => request(`/animals/${id}`),
    getPedigree: (id) => request(`/animals/${id}/pedigree`),
    createAnimal: (data) => request('/animals', { method: 'POST', body: JSON.stringify(data) }),
    updateAnimal: (id, data) => request(`/animals/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteAnimal: (id) => request(`/animals/${id}`, { method: 'DELETE' }),
    recordWeight: (id, data) => request(`/animals/${id}/weights`, { method: 'POST', body: JSON.stringify(data) }),
    recordBcs: (id, data) => request(`/animals/${id}/bcs`, { method: 'POST', body: JSON.stringify(data) }),
    transitionLifecycle: (id, data) => request(`/animals/${id}/lifecycle-transition`, { method: 'POST', body: JSON.stringify(data) }),

    // 3. Milk & Collection Centers
    getMilkRecords: (params = '') => request(`/milk${params ? `?${params}` : ''}`),
    createMilkRecord: (data) => request('/milk/record', { method: 'POST', body: JSON.stringify(data) }),
    getMilkSummary: () => request('/milk/summary'),
    getBulkTanks: () => request('/milk/bulk-tanks'),
    cleanBulkTank: (tankId, data) => request(`/milk/bulk-tanks/${tankId}/cip-clean`, { method: 'POST', body: JSON.stringify(data) }),
    getDispatches: () => request('/milk/dispatches'),
    createDispatch: (data) => request('/milk/dispatches', { method: 'POST', body: JSON.stringify(data) }),
    getCollectionCenters: () => request('/collection-centers'),
    getRateCharts: () => request('/rate-charts'),
    getIntakes: () => request('/intakes'),
    createIntake: (data) => request('/intakes', { method: 'POST', body: JSON.stringify(data) }),

    // 4. Health & AMU
    getHealthOverview: () => request('/health'),
    getMedicines: () => request('/health/medicines'),
    createHealthCase: (data) => request('/health/cases', { method: 'POST', body: JSON.stringify(data) }),
    createTreatment: (data) => request('/health/treatments', { method: 'POST', body: JSON.stringify(data) }),
    getAmuSummary: () => request('/health/amu-summary'),
    getBiosecurityAudits: () => request('/health/biosecurity-audits'),
    createBiosecurityAudit: (data) => request('/health/biosecurity-audits', { method: 'POST', body: JSON.stringify(data) }),

    // 5. Breeding & Genetics
    getBreeding: () => request('/breeding'),
    createBreedingEvent: (data) => request('/breeding/events', { method: 'POST', body: JSON.stringify(data) }),
    getSemenInventory: () => request('/breeding/semen-inventory'),
    createSemenStraw: (data) => request('/breeding/semen-inventory', { method: 'POST', body: JSON.stringify(data) }),
    recordCalving: (data) => request('/breeding/calving', { method: 'POST', body: JSON.stringify(data) }),
    getPostpartumChecks: () => request('/breeding/postpartum-checks'),
    getBreedingKpis: () => request('/breeding/kpis'),

    // 6. Feeds & Nutrition
    getFeeds: () => request('/feeds'),
    createFeedConsumption: (data) => request('/feeds/consumption', { method: 'POST', body: JSON.stringify(data) }),
    getFormulations: () => request('/feeds/formulations'),
    createFormulation: (data) => request('/feeds/formulations', { method: 'POST', body: JSON.stringify(data) }),
    getTmrBatches: () => request('/feeds/tmr-batches'),
    createTmrBatch: (data) => request('/feeds/tmr-batches', { method: 'POST', body: JSON.stringify(data) }),
    getBunkScores: () => request('/feeds/bunk-scores'),
    createBunkScore: (data) => request('/feeds/bunk-scores', { method: 'POST', body: JSON.stringify(data) }),
    getSilageBunkers: () => request('/feeds/silage-bunkers'),
    predictDmi: (data) => request('/feeds/predict-dmi', { method: 'POST', body: JSON.stringify(data) }),

    // 7. Meat & Feedlots
    getFeedlots: () => request('/meat/feedlots'),
    createFeedlotIntake: (data) => request('/meat/feedlots', { method: 'POST', body: JSON.stringify(data) }),
    updateFeedlotGain: (id, data) => request(`/meat/feedlots/${id}/gain`, { method: 'POST', body: JSON.stringify(data) }),
    checkSlaughterClearance: (animalId) => request(`/meat/slaughter/clearance/${animalId}`),
    recordSlaughter: (data) => request('/meat/slaughter', { method: 'POST', body: JSON.stringify(data) }),
    getFleeces: () => request('/meat/fleeces'),
    recordFleece: (data) => request('/meat/fleeces', { method: 'POST', body: JSON.stringify(data) }),
    getSpecializedSpecies: () => request('/meat/specialized-species'),

    // 8. Pastures & Climate
    getPaddocks: () => request('/pastures/paddocks'),
    createPaddock: (data) => request('/pastures/paddocks', { method: 'POST', body: JSON.stringify(data) }),
    enterPaddock: (data) => request('/pastures/grazing/enter', { method: 'POST', body: JSON.stringify(data) }),
    exitPaddock: (id, data) => request(`/pastures/grazing/${id}/exit`, { method: 'POST', body: JSON.stringify(data) }),
    getClimateCurrent: () => request('/climate/current'),
    recordClimateReading: (data) => request('/climate/sensor-readings', { method: 'POST', body: JSON.stringify(data) }),

    // 9. Inventory & Assets
    getWarehouses: () => request('/inventory/warehouses'),
    createWarehouse: (data) => request('/inventory/warehouses', { method: 'POST', body: JSON.stringify(data) }),
    getInventoryItems: () => request('/inventory/items'),
    createInventoryItem: (data) => request('/inventory/items', { method: 'POST', body: JSON.stringify(data) }),
    createInventoryTransaction: (data) => request('/inventory/transactions', { method: 'POST', body: JSON.stringify(data) }),
    getAssets: () => request('/inventory/assets'),
    createAsset: (data) => request('/inventory/assets', { method: 'POST', body: JSON.stringify(data) }),
    recordMaintenance: (data) => request('/inventory/maintenance', { method: 'POST', body: JSON.stringify(data) }),

    // 10. Sales & Subscriptions
    getCustomers: () => request('/sales/customers'),
    createCustomer: (data) => request('/sales/customers', { method: 'POST', body: JSON.stringify(data) }),
    topUpWallet: (id, data) => request(`/sales/customers/${id}/topup`, { method: 'POST', body: JSON.stringify(data) }),
    getSubscriptions: () => request('/sales/subscriptions'),
    createSubscription: (data) => request('/sales/subscriptions', { method: 'POST', body: JSON.stringify(data) }),
    getDeliveryRuns: () => request('/sales/delivery-runs'),
    completeDeliveryStop: (id, data) => request(`/sales/delivery-stops/${id}/complete`, { method: 'POST', body: JSON.stringify(data) }),

    // 11. Finance & Accounting
    getFinances: (params = '') => request(`/finances${params ? `?${params}` : ''}`),
    createFinanceTransaction: (data) => request('/finances/transactions', { method: 'POST', body: JSON.stringify(data) }),
    getChartOfAccounts: () => request('/finance/chart-of-accounts'),
    createAccount: (data) => request('/finance/chart-of-accounts', { method: 'POST', body: JSON.stringify(data) }),
    getGeneralLedger: () => request('/finance/general-ledger'),
    createJournalVoucher: (data) => request('/finance/general-ledger/journal-voucher', { method: 'POST', body: JSON.stringify(data) }),
    getCostPerLiter: () => request('/finance/cost-per-liter'),
    getBiologicalValuations: () => request('/finance/biological-valuations'),
    appraiseAnimal: (animalId, data) => request(`/finance/biological-valuations/${animalId}/appraise`, { method: 'POST', body: JSON.stringify(data) }),

    // 12. Compliance & Traceability
    getCompliancePacks: () => request('/compliance/packs'),
    getMovementPermits: () => request('/compliance/movement-permits'),
    createMovementPermit: (data) => request('/compliance/movement-permits', { method: 'POST', body: JSON.stringify(data) }),
    getForwardTrace: (animalId) => request(`/compliance/traceability/forward/${animalId}`),
    getBackwardTrace: (deliveryStopId) => request(`/compliance/traceability/backward/${deliveryStopId}`),
    getHalalCerts: () => request('/compliance/halal-certifications'),
    recordHalalCert: (data) => request('/compliance/halal-certifications', { method: 'POST', body: JSON.stringify(data) }),
    recordWelfareAssessment: (data) => request('/compliance/welfare-assessments', { method: 'POST', body: JSON.stringify(data) }),

    // 13. IoT Hardware & Sync
    getDevices: () => request('/sync/devices'),
    registerDevice: (data) => request('/sync/devices', { method: 'POST', body: JSON.stringify(data) }),
    pushTelemetry: (data) => request('/sync/telemetry', { method: 'POST', body: JSON.stringify(data) }),
    syncPull: () => request('/sync/pull'),
    syncPush: (mutations) => request('/sync/push', { method: 'POST', body: JSON.stringify({ mutations }) }),

    // 14. Tasks & Workforce
    getTasks: () => request('/tasks'),
    createTask: (data) => request('/tasks', { method: 'POST', body: JSON.stringify(data) }),
    updateTaskStatus: (id, status) => request(`/tasks/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) }),
};
