/* ========================================
   API COMMUNICATION & SYNC
   ======================================== */

class TaskAPI {
    constructor() {
        this.baseURL = CONFIG.API_BASE_URL;
    }
    
    // Get auth headers
    getHeaders() {
        const token = Utils.getToken();
        return {
            'Content-Type': 'application/json',
            'Authorization': token ? `Bearer ${token}` : ''
        };
    }
    
    // Fetch tasks from server
    async fetchTasks(status = null) {
        const url = status 
            ? `${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_READ}?status=${status}`
            : `${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_READ}`;
            
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch tasks');
        }
        
        return data.data.tasks;
    }
    
    // Create task on server
    async createTask(task) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_CREATE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(task)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to create task');
        }
        
        return data.data.task;
    }
    
    // Update task on server
    async updateTask(task) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_UPDATE}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(task)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to update task');
        }
        
        return data.data.task;
    }
    
    // Delete task from server
    async deleteTask(taskId) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_DELETE}?id=${taskId}`, {
            method: 'DELETE',
            headers: this.getHeaders()
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to delete task');
        }
        
        return true;
    }
    
    // Sync tasks (batch)
    async syncTasks(tasks) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TASKS_SYNC}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ tasks })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to sync tasks');
        }
        
        return data.data;
    }
    
    // Get user profile
    async getUserProfile() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.USER_PROFILE}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch profile');
        }
        
        return data.data;
    }

    async updateUserProfile(payload) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.USER_UPDATE}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to update profile');
        }

        return data.data;
    }

    async getSchedulePlan(date) {
        const url = `${this.baseURL}${CONFIG.API_ENDPOINTS.SCHEDULE_PLAN}?date=${encodeURIComponent(date)}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load schedule plan');
        return data.data;
    }

    async getAvailability() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.AVAILABILITY_READ}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load availability');
        return data.data;
    }

    async saveAvailability(availability) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.AVAILABILITY_UPSERT}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ availability })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to save availability');
        return data.data || {};
    }

    async deleteAvailabilityDay(dayOfWeek) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.AVAILABILITY_DELETE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ day_of_week: dayOfWeek })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete availability');
        return data.data || {};
    }

    async createTemplate(templateData) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TEMPLATE_CREATE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(templateData)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to create template');
        return data.data;
    }

    async getTemplates() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TEMPLATE_READ}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load templates');
        return data.data;
    }

    async updateTemplate(templateData) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TEMPLATE_UPDATE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(templateData)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to update template');
        return data.data;
    }

    async deleteTemplate(templateId) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.TEMPLATE_DELETE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ id: templateId })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete template');
        return data.data;
    }

    async createReminder(payload) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REMINDER_CREATE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to create reminder');
        return data.data;
    }

    async updateReminder(payload) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REMINDER_UPDATE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to update reminder');
        return data.data;
    }

    async deleteReminder(reminderId) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REMINDER_DELETE}`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify({ id: reminderId })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete reminder');
        return data.data || {};
    }

    async getReminders(status = 'pending') {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REMINDER_READ}?status=${encodeURIComponent(status)}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load reminders');
        return data.data;
    }

    async getFinanceSummary(fromDate, toDate) {
        const query = `from=${encodeURIComponent(fromDate)}&to=${encodeURIComponent(toDate)}`;
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.FINANCE_SUMMARY}?${query}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load finance summary');
        return data.data;
    }

    async getInsights() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.INSIGHTS_RECOMMENDATIONS}`, {
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to get insights');
        return data.data;
    }

    async downloadReportPdf(params) {
        const queryString = new URLSearchParams(params).toString();
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REPORTS_PDF}?${queryString}`, {
            headers: this.getHeaders()
        });
        
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to generate PDF report');
        }
        
        // Create download link
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `taskflow_report_${params.from}_to_${params.to}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    async getAdminOverview() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_OVERVIEW}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load admin overview');
        return data.data;
    }

    async getAdminUsers() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_USERS}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load users');
        return data.data;
    }

    async getAdminUsersFiltered(filters = {}) {
        const params = new URLSearchParams();
        if (filters.search) params.set('search', filters.search);
        if (filters.status) params.set('status', filters.status);
        if (filters.work_type) params.set('work_type', filters.work_type);
        const query = params.toString();
        const url = query
            ? `${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_USERS}?${query}`
            : `${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_USERS}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load users');
        return data.data;
    }

    async updateAdminUserStatus(id, isActive) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_USERS}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify({ id, is_active: isActive })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to update user status');
        return data.data || {};
    }

    async getAdminTasks() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_TASKS}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load admin tasks');
        return data.data;
    }

    async getAdminLeaderboard() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_LEADERBOARD}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load leaderboard');
        return data.data;
    }

    async getAdminReminders(windowHours = 24) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_REMINDERS}?window_hours=${encodeURIComponent(windowHours)}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load reminders');
        return data.data;
    }

    async getAdminSchedulePreview(userId, date) {
        const query = `user_id=${encodeURIComponent(userId)}&date=${encodeURIComponent(date)}`;
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_SCHEDULE_PREVIEW}?${query}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load schedule preview');
        return data.data;
    }

    async getAdminIncidents(status = 'all') {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_INCIDENTS}?status=${encodeURIComponent(status)}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load incidents');
        return data.data;
    }

    async updateAdminIncidentStatus(id, status) {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_INCIDENTS}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify({ id, status })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to update incident status');
        return data.data || {};
    }

    async getAdminDispatchBoard() {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_DISPATCH}`, {
            method: 'GET',
            headers: this.getHeaders()
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to load dispatch board');
        return data.data;
    }

    async reassignTaskDispatch(taskId, toUserId, reason = '') {
        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.ADMIN_DISPATCH}`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify({
                task_id: taskId,
                to_user_id: toUserId,
                reason
            })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Failed to reassign task');
        return data.data || {};
    }

    async downloadReportPdf({ from, to, scope = 'user' }) {
        const params = new URLSearchParams({
            from,
            to,
            scope
        });

        const response = await fetch(`${this.baseURL}${CONFIG.API_ENDPOINTS.REPORTS_EXPORT}?${params.toString()}`, {
            method: 'GET',
            headers: this.getHeaders()
        });

        if (!response.ok) {
            let message = 'Failed to download report';
            try {
                const err = await response.json();
                message = err.message || message;
            } catch (error) {
                // Keep default message when response is not JSON.
            }
            throw new Error(message);
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;

        const contentDisposition = response.headers.get('content-disposition') || '';
        const match = contentDisposition.match(/filename="?([^"]+)"?/i);
        link.download = match && match[1] ? match[1] : `taskflow_report_${Date.now()}.pdf`;

        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    }
}

// Sync Manager
class SyncManager {
    constructor() {
        this.api = new TaskAPI();
        this.isSyncing = false;
    }
    
    // Sync all unsynced tasks
    async syncAll() {
        if (this.isSyncing) {
            console.log('Sync already in progress');
            return;
        }
        
        if (!Utils.isOnline()) {
            Utils.showNotification('Cannot sync while offline', 'warning');
            return;
        }
        
        this.isSyncing = true;
        this.updateSyncUI(true);
        
        try {
            // Get unsynced tasks from IndexedDB
            const unsyncedTasks = await taskDB.getUnsyncedTasks();
            
            if (unsyncedTasks.length === 0) {
                Utils.showNotification('All tasks are up to date', 'success');
                this.isSyncing = false;
                this.updateSyncUI(false);
                return;
            }
            
            console.log(`Syncing ${unsyncedTasks.length} tasks...`);
            
            // Sync to server
            const result = await this.api.syncTasks(unsyncedTasks);
            
            console.log('Sync result:', result);
            
            // Mark tasks as synced in IndexedDB
            for (const task of unsyncedTasks) {
                await taskDB.markSynced(task.id);
            }
            
            // Fetch latest tasks from server
            const serverTasks = await this.api.fetchTasks();
            
            // Update IndexedDB with server tasks
            for (const serverTask of serverTasks) {
                // Check if task exists locally
                const localTask = await taskDB.getTask(serverTask.id);
                if (!localTask) {
                    // Add new task from server
                    serverTask.synced = true;
                    await taskDB.addTask(serverTask);
                }
            }
            
            Utils.showNotification(
                `Sync complete: ${result.summary.created} created, ${result.summary.updated} updated`,
                'success'
            );
            
            // Reload current page data
            if (typeof loadDashboardData === 'function') {
                await loadDashboardData();
            }
            
        } catch (error) {
            console.error('Sync error:', error);
            Utils.showNotification('Sync failed: ' + error.message, 'error');
        }
        
        this.isSyncing = false;
        this.updateSyncUI(false);
    }
    
    // Update sync button UI
    updateSyncUI(syncing) {
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            if (syncing) {
                syncBtn.classList.add('syncing');
                syncBtn.disabled = true;
            } else {
                syncBtn.classList.remove('syncing');
                syncBtn.disabled = false;
            }
        }
    }
    
    // Auto-sync on connection restore
    setupAutoSync() {
        connectionMonitor.onChange((isOnline) => {
            if (isOnline) {
                console.log('Connection restored, auto-syncing...');
                setTimeout(() => this.syncAll(), 1000);
            }
        });
    }
}

// Initialize sync manager
const syncManager = new SyncManager();
syncManager.setupAutoSync();

// Sync function for button
async function syncTasks() {
    await syncManager.syncAll();
}

// Export API instance
const api = new TaskAPI();
