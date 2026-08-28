import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api/v1';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 15000,
});

// Request Interceptor: Attach Sanctum Bearer Token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response Interceptor: Standardized error format
apiClient.interceptors.response.use(
  (response) => response.data,
  (error) => {
    const errorResponse = {
      success: false,
      message: error.response?.data?.message || error.message || 'An unexpected error occurred.',
      errors: error.response?.data?.errors || null,
      status: error.response?.status || 500,
    };

    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
    }

    return Promise.reject(errorResponse);
  }
);

export const apiService = {
  // System Health
  getHealth: () => apiClient.get('/health'),

  // Authentication
  auth: {
    register: (data) => apiClient.post('/auth/register', data),
    login: (data) => apiClient.post('/auth/login', data),
    logout: () => apiClient.post('/auth/logout'),
    me: () => apiClient.get('/auth/me'),
  },

  // Business Onboarding & Management
  businesses: {
    getAll: () => apiClient.get('/businesses'),
    create: (data) => apiClient.post('/businesses', data),
    switch: (id) => apiClient.post(`/businesses/${id}/switch`),
  },

  // Dashboard API
  dashboard: {
    get: () => apiClient.get('/dashboard'),
  },

  // AI Copilot Assistant API
  copilot: {
    chat: (prompt) => apiClient.post('/copilot/chat', { prompt }),
    getInsights: () => apiClient.get('/copilot/insights'),
  },

  // Voice Commands API
  voice: {
    sendCommand: (transcript, language = 'en') => apiClient.post('/voice/command', { transcript, language }),
  },

  // WhatsApp Integration API
  whatsapp: {
    getInvoiceLink: (invoiceId) => apiClient.post(`/whatsapp/invoice-link/${invoiceId}`),
    getReminderLink: (data) => apiClient.post('/whatsapp/reminder-link', data),
    getTemplates: () => apiClient.get('/whatsapp/templates'),
    updateTemplate: (data) => apiClient.put('/whatsapp/templates', data),
  },

  // Automation Triggers API
  automation: {
    getLogs: () => apiClient.get('/automation/logs'),
    run: () => apiClient.post('/automation/run'),
  },

  // Categories API
  categories: {
    getAll: () => apiClient.get('/categories'),
    create: (data) => apiClient.post('/categories', data),
  },

  // Products API
  products: {
    getAll: (params = {}) => apiClient.get('/products', { params }),
    getOne: (id) => apiClient.get(`/products/${id}`),
    create: (data) => apiClient.post('/products', data),
    update: (id, data) => apiClient.put(`/products/${id}`, data),
    delete: (id) => apiClient.delete(`/products/${id}`),
  },

  // Stock Movements API
  stock: {
    adjust: (data) => apiClient.post('/stock/adjust', data),
    getMovements: (productId) => apiClient.get(`/stock/movements/${productId}`),
  },

  // Invoices & POS API
  invoices: {
    getAll: (params = {}) => apiClient.get('/invoices', { params }),
    getOne: (id) => apiClient.get(`/invoices/${id}`),
    create: (data) => apiClient.post('/invoices', data),
  },

  // Customers & Ledger API
  customers: {
    getAll: (params = {}) => apiClient.get('/customers', { params }),
    getOne: (id) => apiClient.get(`/customers/${id}`),
    create: (data) => apiClient.post('/customers', data),
    update: (id, data) => apiClient.put(`/customers/${id}`, data),
    delete: (id) => apiClient.delete(`/customers/${id}`),
    getLedger: (id) => apiClient.get(`/customers/${id}/ledger`),
  },

  // Payments API
  payments: {
    getAll: () => apiClient.get('/payments'),
    create: (data) => apiClient.post('/payments', data),
  },

  // Reminders API
  reminders: {
    create: (data) => apiClient.post('/reminders', data),
    updateStatus: (id, status) => apiClient.patch(`/reminders/${id}/status`, { status }),
  },

  // Reports API
  reports: {
    getSales: (params = {}) => apiClient.get('/reports/sales', { params }),
    getProfitLoss: (params = {}) => apiClient.get('/reports/profit-loss', { params }),
    getInventory: () => apiClient.get('/reports/inventory'),
    getDebtors: () => apiClient.get('/reports/debtors'),
  },
};

export default apiService;
