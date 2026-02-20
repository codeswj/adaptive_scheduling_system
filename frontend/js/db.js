/* ========================================
   INDEXEDDB - OFFLINE STORAGE
   ======================================== */

class TaskDatabase {
    constructor() {
        this.db = null;
        this.dbName = CONFIG.DB_NAME;
        this.version = CONFIG.DB_VERSION;
    }
    
    // Initialize database
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create tasks object store
                if (!db.objectStoreNames.contains('tasks')) {
                    const taskStore = db.createObjectStore('tasks', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    taskStore.createIndex('status', 'status', { unique: false });
                    taskStore.createIndex('created_at', 'created_at', { unique: false });
                    taskStore.createIndex('synced', 'synced', { unique: false });
                }
                
                // Create sync queue
                if (!db.objectStoreNames.contains('syncQueue')) {
                    const syncStore = db.createObjectStore('syncQueue', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    syncStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
            };
        });
    }
    
    // Add task to local database
    async addTask(task) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readwrite');
            const store = transaction.objectStore('tasks');
            
            task.created_at = new Date().toISOString();
            task.synced = false;
            task.device_created = true;
            
            const request = store.add(task);
            
            request.onsuccess = () => {
                task.id = request.result;
                resolve(task);
            };
            request.onerror = () => reject(request.error);
        });
    }
    
    // Get all tasks
    async getAllTasks() {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readonly');
            const store = transaction.objectStore('tasks');
            const request = store.getAll();
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Get task by ID
    async getTask(id) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readonly');
            const store = transaction.objectStore('tasks');
            const request = store.get(id);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Update task
    async updateTask(task) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readwrite');
            const store = transaction.objectStore('tasks');
            
            task.updated_at = new Date().toISOString();
            task.synced = false;
            
            const request = store.put(task);
            
            request.onsuccess = () => resolve(task);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Delete task
    async deleteTask(id) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readwrite');
            const store = transaction.objectStore('tasks');
            const request = store.delete(id);
            
            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Get unsynced tasks
    async getUnsyncedTasks() {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readonly');
            const store = transaction.objectStore('tasks');
            const request = store.getAll();
            
            request.onsuccess = () => {
                const tasks = request.result.filter(task => !task.synced);
                resolve(tasks);
            };
            request.onerror = () => reject(request.error);
        });
    }
    
    // Mark task as synced
    async markSynced(id, serverId = null) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readwrite');
            const store = transaction.objectStore('tasks');
            const getRequest = store.get(id);
            
            getRequest.onsuccess = () => {
                const task = getRequest.result;
                if (task) {
                    task.synced = true;
                    task.synced_at = new Date().toISOString();
                    if (serverId) task.server_id = serverId;
                    
                    const putRequest = store.put(task);
                    putRequest.onsuccess = () => resolve(task);
                    putRequest.onerror = () => reject(putRequest.error);
                } else {
                    resolve(null);
                }
            };
            getRequest.onerror = () => reject(getRequest.error);
        });
    }
    
    // Clear all tasks (use with caution)
    async clearAllTasks() {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readwrite');
            const store = transaction.objectStore('tasks');
            const request = store.clear();
            
            request.onsuccess = () => resolve(true);
            request.onerror = () => reject(request.error);
        });
    }
    
    // Ensure database is initialized
    async ensureDB() {
        if (!this.db) {
            await this.init();
        }
    }
    
    // Get tasks by status
    async getTasksByStatus(status) {
        await this.ensureDB();
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['tasks'], 'readonly');
            const store = transaction.objectStore('tasks');
            const index = store.index('status');
            const request = index.getAll(status);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
}

// Initialize database instance
const taskDB = new TaskDatabase();

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        taskDB.init().catch(console.error);
    });
} else {
    taskDB.init().catch(console.error);
}
