const API_BASE = window.__FARM_CONFIG__?.apiUrl || '/farm_management_system/public/api/v1';

async function request(endpoint, options = {}) {
    const url = `${API_BASE}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.__FARM_CONFIG__?.csrfToken || '',
        ...options.headers,
    };

    try {
        const response = await fetch(url, { credentials: 'same-origin', credentials: 'omit', ...options, headers });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: response.statusText }));
            throw new Error(errorData.message || `HTTP Error ${response.status}`);
        }
        return await response.json();
    } catch (err) {
        console.error(`API Error on [${options.method || 'GET'} ${url}]:`, err);
        throw err;
    }
}

export const api = {
    getDashboard: () => request('/dashboard'),
    getAnimals: (params = '') => request(`/animals${params ? `?${params}` : ''}`),
    getAnimal: (id) => request(`/animals/${id}`),
    createAnimal: (data) => request('/animals', { method: 'POST', body: JSON.stringify(data) }),
    getMilkRecords: () => request('/milk'),
    createMilkRecord: (data) => request('/milk/record', { method: 'POST', body: JSON.stringify(data) }),
    getHealth: () => request('/health'),
    getMedicines: () => request('/health/medicines'),
    createTreatment: (data) => request('/health/treatments', { method: 'POST', body: JSON.stringify(data) }),
    getBreeding: () => request('/breeding'),
    createBreedingEvent: (data) => request('/breeding/events', { method: 'POST', body: JSON.stringify(data) }),
    getFeeds: () => request('/feeds'),
    getFinances: () => request('/finances'),
    getTasks: () => request('/tasks'),
    updateTaskStatus: (id, status) => request(`/tasks/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) }),
    createTask: (data) => request('/tasks', { method: 'POST', body: JSON.stringify(data) }),
};
