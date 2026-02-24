/* ========================================
   CONFIGURATION
   ======================================== */

function getAppBasePath() {
    const path = window.location.pathname || '/';
    const segments = path.split('/').filter(Boolean);

    // Examples:
    // /adaptive_scheduling_system/index.php -> /adaptive_scheduling_system
    // /adaptive_scheduling_system/frontend/dashboard.html -> /adaptive_scheduling_system
    if (segments.length > 0) {
        return `/${segments[0]}`;
    }

    return '';
}

const APP_BASE_PATH = getAppBasePath();

const CONFIG = {
    API_BASE_URL: `${window.location.origin}${APP_BASE_PATH}/backend`,
    API_ENDPOINTS: {
        REGISTER: '/api/auth/register.php',
        LOGIN: '/api/auth/login.php',
        LOGOUT: '/api/auth/logout.php',
        TASKS_CREATE: '/api/tasks/create.php',
        TASKS_READ: '/api/tasks/read.php',
        TASKS_UPDATE: '/api/tasks/update.php',
        TASKS_DELETE: '/api/tasks/delete.php',
        TASKS_SYNC: '/api/tasks/sync.php',
        USER_PROFILE: '/api/user/profile.php',
        USER_UPDATE: '/api/user/update.php',
        SCHEDULE_PLAN: '/api/schedule/plan.php',
        AVAILABILITY_READ: '/api/availability/read.php',
        AVAILABILITY_UPSERT: '/api/availability/upsert.php',
        TEMPLATE_CREATE: '/api/templates/create.php',
        TEMPLATE_READ: '/api/templates/read.php',
        REMINDER_CREATE: '/api/reminders/create.php',
        REMINDER_READ: '/api/reminders/read.php',
        FINANCE_SUMMARY: '/api/finance/summary.php',
        INSIGHTS_RECOMMENDATIONS: '/api/insights/recommendations.php',
        ADMIN_OVERVIEW: '/api/admin/overview.php',
        ADMIN_USERS: '/api/admin/users.php',
        ADMIN_TASKS: '/api/admin/tasks.php',
        ADMIN_LEADERBOARD: '/api/admin/leaderboard.php',
        ADMIN_REMINDERS: '/api/admin/reminders.php',
        ADMIN_SCHEDULE_PREVIEW: '/api/admin/schedule_preview.php',
        REPORTS_EXPORT: '/api/reports/export.php',
        ADMIN_INCIDENTS: '/api/admin/incidents.php',
        ADMIN_DISPATCH: '/api/admin/dispatch.php'
    },
    STORAGE_KEYS: {
        AUTH_TOKEN: 'taskflow_auth_token',
        USER_DATA: 'taskflow_user_data',
        OFFLINE_TASKS: 'taskflow_offline_tasks'
    },
    DB_NAME: 'TaskFlowDB',
    DB_VERSION: 1
};

// Utility Functions
const Utils = {
    // Get auth token
    getToken() {
        return localStorage.getItem(CONFIG.STORAGE_KEYS.AUTH_TOKEN);
    },
    
    // Set auth token
    setToken(token) {
        localStorage.setItem(CONFIG.STORAGE_KEYS.AUTH_TOKEN, token);
    },
    
    // Remove auth token
    removeToken() {
        localStorage.removeItem(CONFIG.STORAGE_KEYS.AUTH_TOKEN);
        localStorage.removeItem(CONFIG.STORAGE_KEYS.USER_DATA);
    },
    
    // Get user data
    getUserData() {
        const data = localStorage.getItem(CONFIG.STORAGE_KEYS.USER_DATA);
        return data ? JSON.parse(data) : null;
    },
    
    // Set user data
    setUserData(user) {
        localStorage.setItem(CONFIG.STORAGE_KEYS.USER_DATA, JSON.stringify(user));
    },
    
    // Check if user is authenticated
    isAuthenticated() {
        return !!this.getToken();
    },
    
    // Redirect to login if not authenticated
    requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = '../index.php';
            return false;
        }
        return true;
    },
    
    // Format date for display
    formatDate(dateString) {
        if (!dateString) return 'No deadline';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-KE', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    // Format currency
    formatCurrency(amount) {
        return `KSh ${parseFloat(amount || 0).toLocaleString()}`;
    },
    
    // Show notification
    showNotification(message, type = 'info') {
        // You can implement a toast notification system here
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // Simple alert for now
        if (type === 'error') {
            alert(message);
        }
    },
    
    // Check if online
    isOnline() {
        return navigator.onLine;
    },
    
    // Get initials from name
    getInitials(name) {
        if (!name) return 'U';
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Connection Status Monitor
class ConnectionMonitor {
    constructor() {
        this.callbacks = [];
        this.init();
    }
    
    init() {
        window.addEventListener('online', () => this.updateStatus(true));
        window.addEventListener('offline', () => this.updateStatus(false));
    }
    
    updateStatus(isOnline) {
        this.callbacks.forEach(callback => callback(isOnline));
        this.updateUI(isOnline);
    }
    
    updateUI(isOnline) {
        const statusElement = document.getElementById('connectionStatus');
        if (statusElement) {
            const dot = statusElement.querySelector('.status-dot');
            const text = statusElement.querySelector('.status-text');
            
            if (isOnline) {
                dot.className = 'status-dot online';
                text.textContent = 'Online';
            } else {
                dot.className = 'status-dot offline';
                text.textContent = 'Offline Mode';
            }
        }
    }
    
    onChange(callback) {
        this.callbacks.push(callback);
    }
    
    isOnline() {
        return navigator.onLine;
    }
}

// Initialize connection monitor
const connectionMonitor = new ConnectionMonitor();

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CONFIG, Utils, connectionMonitor };
}

