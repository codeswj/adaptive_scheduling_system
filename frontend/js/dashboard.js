/* ========================================
   DASHBOARD
   ======================================== */

// Require authentication
if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

const currentUser = Utils.getUserData();
if (currentUser && currentUser.role === 'admin') {
    window.location.href = 'admin-dashboard.html';
}

// Load dashboard data on page load
document.addEventListener('DOMContentLoaded', async () => {
    await initDashboard();
});

// Initialize dashboard
async function initDashboard() {
    try {
        // Load user profile
        await loadUserProfile();
        
        // Load dashboard data
        await loadDashboardData();
        
        // Set greeting
        setGreeting();
        
        // Initialize connection status
        updateConnectionStatus();
        
    } catch (error) {
        console.error('Dashboard init error:', error);
    }
}

// Load user profile
async function loadUserProfile() {
    const userData = Utils.getUserData();
    
    if (userData) {
        updateUserUI(userData);
    }
    
    // Try to fetch fresh data from server
    if (Utils.isOnline()) {
        try {
            const profileData = await api.getUserProfile();
            Utils.setUserData(profileData.user);
            updateUserUI(profileData.user);
        } catch (error) {
            console.error('Failed to fetch profile:', error);
        }
    }
}

// Update user UI
function updateUserUI(user) {
    // Update user name
    const userNameEl = document.getElementById('userName');
    if (userNameEl) {
        userNameEl.textContent = user.full_name;
    }
    
    // Update user role
    const userRoleEl = document.getElementById('userRole');
    if (userRoleEl) {
        const roleNames = {
            'boda_boda': 'Boda Boda',
            'market_vendor': 'Market Vendor',
            'artisan': 'Artisan',
            'domestic_worker': 'Domestic Worker',
            'plumber': 'Plumber',
            'other': 'Worker'
        };
        userRoleEl.textContent = roleNames[user.work_type] || 'Worker';
    }
    
    // Update initials
    const initialsEl = document.getElementById('userInitials');
    if (initialsEl) {
        initialsEl.textContent = Utils.getInitials(user.full_name);
    }
}

// Load dashboard data
async function loadDashboardData() {
    try {
        let tasks = [];
        
        // Try to load from server first
        if (Utils.isOnline()) {
            try {
                tasks = await api.fetchTasks();
                
                // Save to IndexedDB
                for (const task of tasks) {
                    const existingTask = await taskDB.getTask(task.id);
                    if (existingTask) {
                        await taskDB.updateTask({ ...task, synced: true });
                    } else {
                        await taskDB.addTask({ ...task, synced: true });
                    }
                }
            } catch (error) {
                console.error('Failed to fetch from server:', error);
            }
        }
        
        // Load from IndexedDB
        tasks = await taskDB.getAllTasks();
        
        // Update statistics
        updateStatistics(tasks);
        
        // Display today's tasks
        displayTodayTasks(tasks);
        
        // Update task count badge
        updateTaskBadge(tasks);
        
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}

// Update statistics
function updateStatistics(tasks) {
    const totalTasks = tasks.length;
    const pendingTasks = tasks.filter(t => t.status === 'pending').length;
    const completedTasks = tasks.filter(t => t.status === 'completed').length;
    const totalEarnings = tasks
        .filter(t => t.status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.payment_amount || 0), 0);
    
    // Update UI
    document.getElementById('totalTasks').textContent = totalTasks;
    document.getElementById('pendingTasks').textContent = pendingTasks;
    document.getElementById('completedTasks').textContent = completedTasks;
    document.getElementById('totalEarnings').textContent = Utils.formatCurrency(totalEarnings);
}

// Display today's priority tasks
function displayTodayTasks(tasks) {
    const container = document.getElementById('todayTasks');

    // Show top pending/in-progress tasks with the strongest schedule pressure.
    const todayTasks = tasks
        .filter(t => t.status === 'pending' || t.status === 'in_progress')
        .sort(compareTasksForSchedule)
        .slice(0, 5);
    
    if (todayTasks.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>No tasks for today</p>
                <button class="btn-primary-small" onclick="showAddTaskModal()">Add Your First Task</button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = todayTasks.map(task => `
        <div class="task-card" onclick="viewTask(${task.id})">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                <h4 style="font-size: 1.125rem; font-weight: 600;">${task.title}</h4>
                <span class="urgency-badge ${task.urgency}">${task.urgency}</span>
            </div>
            <p style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 0.5rem;">
                ${task.location || 'No location'}
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.875rem; color: var(--gray-400);">
                    ${Utils.formatDate(task.deadline)}
                </span>
                <span style="font-size: 0.75rem; color: var(--gray-500);">
                    Priority ${Math.round(getEffectivePriorityScore(task))}
                </span>
                <span style="font-weight: 600; color: var(--primary);">
                    ${Utils.formatCurrency(task.payment_amount)}
                </span>
            </div>
        </div>
    `).join('');
}

// Set greeting based on time
function setGreeting() {
    const greetingEl = document.getElementById('greetingText');
    if (!greetingEl) return;
    
    const hour = new Date().getHours();
    const userData = Utils.getUserData();
    const name = userData ? userData.full_name.split(' ')[0] : 'there';
    
    let greeting;
    if (hour < 12) greeting = `Good morning, ${name}!`;
    else if (hour < 18) greeting = `Good afternoon, ${name}!`;
    else greeting = `Good evening, ${name}!`;
    
    greetingEl.textContent = greeting;
}

// Update connection status
function updateConnectionStatus() {
    const isOnline = Utils.isOnline();
    connectionMonitor.updateUI(isOnline);
}

// Update task count badge
function updateTaskBadge(tasks) {
    const badge = document.getElementById('taskCount');
    if (badge) {
        const pendingCount = tasks.filter(t => t.status === 'pending').length;
        badge.textContent = pendingCount;
    }
}

// Show add task modal
function showAddTaskModal() {
    const modal = document.getElementById('addTaskModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close add task modal
function closeAddTaskModal() {
    const modal = document.getElementById('addTaskModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('addTaskForm').reset();
}

// Handle add task
async function handleAddTask(event) {
    event.preventDefault();
    
    const form = event.target;
    const btnText = document.getElementById('addTaskBtnText');
    const btn = form.querySelector('button[type="submit"]');
    const loader = btn.querySelector('.btn-loader');
    
    // Get form data
    const taskData = {
        title: document.getElementById('task_title').value,
        task_type: document.getElementById('task_type').value,
        urgency: document.getElementById('task_urgency').value,
        location: document.getElementById('task_location').value,
        payment_amount: document.getElementById('task_payment').value || 0,
        deadline: document.getElementById('task_deadline').value || null,
        estimated_duration: document.getElementById('task_duration').value || 30,
        client_name: document.getElementById('task_client').value,
        notes: document.getElementById('task_notes').value,
        status: 'pending'
    };
    
    // Show loading
    btnText.style.display = 'none';
    loader.style.display = 'block';
    btn.disabled = true;
    
    try {
        // Add to IndexedDB first (offline-first)
        const savedTask = await taskDB.addTask(taskData);
        
        // Try to sync to server if online
        if (Utils.isOnline()) {
            try {
                const serverTask = await api.createTask(taskData);
                await taskDB.updateTask({ ...serverTask, synced: true });
            } catch (error) {
                console.error('Failed to sync to server:', error);
                // Task is still saved locally
            }
        }
        
        // Close modal
        closeAddTaskModal();
        
        // Reload dashboard
        await loadDashboardData();
        
        // Show success message
        Utils.showNotification('Task added successfully!', 'success');
        
    } catch (error) {
        console.error('Failed to add task:', error);
        alert('Failed to add task. Please try again.');
    }
    
    // Reset button
    btnText.style.display = 'inline';
    loader.style.display = 'none';
    btn.disabled = false;
}

// View task details
function viewTask(taskId) {
    // Redirect to tasks page with selected task
    window.location.href = `tasks.html?id=${taskId}`;
}

// Filter tasks
function filterTasks(status) {
    window.location.href = `tasks.html?status=${status}`;
}

// Add CSS for urgency badges
const style = document.createElement('style');
style.textContent = `
    .urgency-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .urgency-badge.low {
        background: #E8F5E9;
        color: #2E7D32;
    }
    .urgency-badge.medium {
        background: #FFF3E0;
        color: #E65100;
    }
    .urgency-badge.high {
        background: #FCE4EC;
        color: #C2185B;
    }
    .urgency-badge.critical {
        background: #FFEBEE;
        color: #C62828;
    }
`;
document.head.appendChild(style);

function compareTasksForSchedule(a, b) {
    const scoreDiff = getEffectivePriorityScore(b) - getEffectivePriorityScore(a);
    if (scoreDiff !== 0) return scoreDiff;

    const dateA = a.deadline ? new Date(a.deadline).getTime() : Number.MAX_SAFE_INTEGER;
    const dateB = b.deadline ? new Date(b.deadline).getTime() : Number.MAX_SAFE_INTEGER;
    if (dateA !== dateB) return dateA - dateB;

    return Number(a.id || 0) - Number(b.id || 0);
}

function getEffectivePriorityScore(task) {
    const serverScore = Number(task.priority_score);
    if (Number.isFinite(serverScore) && serverScore > 0) {
        return serverScore;
    }

    const urgencyMap = {
        low: 25,
        medium: 50,
        high: 75,
        critical: 100
    };

    let score = urgencyMap[task.urgency] || 50;

    if (task.deadline) {
        const hoursUntilDeadline = (new Date(task.deadline).getTime() - Date.now()) / (1000 * 60 * 60);
        if (hoursUntilDeadline <= 0) score += 120;
        else if (hoursUntilDeadline <= 2) score += 100;
        else if (hoursUntilDeadline <= 6) score += 75;
        else if (hoursUntilDeadline <= 24) score += 50;
        else score += 25;
    }

    return score;
}
