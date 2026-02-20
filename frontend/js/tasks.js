/* ========================================
   TASKS PAGE
   ======================================== */

if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

let currentStatus = '';
let focusedTaskId = null;

document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    currentStatus = params.get('status') || '';

    const idParam = params.get('id');
    if (idParam) {
        focusedTaskId = parseInt(idParam, 10);
    }

    setActiveFilterButton(currentStatus);
    await loadTasks();
});

function applyStatusFilter(status) {
    const params = new URLSearchParams(window.location.search);

    if (status) {
        params.set('status', status);
    } else {
        params.delete('status');
    }

    params.delete('id');
    window.location.search = params.toString();
}

function setActiveFilterButton(status) {
    document.querySelectorAll('.filter-btn').forEach((button) => {
        const btnStatus = button.getAttribute('data-status') || '';
        button.classList.toggle('active', btnStatus === status);
    });
}

async function loadTasks() {
    const container = document.getElementById('taskList');
    container.innerHTML = '<div class="task-item">Loading tasks...</div>';

    try {
        let tasks = [];

        if (Utils.isOnline()) {
            try {
                tasks = await api.fetchTasks();

                for (const task of tasks) {
                    const existingTask = await taskDB.getTask(task.id);
                    if (existingTask) {
                        await taskDB.updateTask({ ...task, synced: true });
                    } else {
                        await taskDB.addTask({ ...task, synced: true });
                    }
                }
            } catch (error) {
                console.error('Failed to fetch tasks from server:', error);
            }
        }

        tasks = await taskDB.getAllTasks();

        if (currentStatus) {
            tasks = tasks.filter((task) => task.status === currentStatus);
        }

        tasks.sort(compareTasksForSchedule);

        renderTasks(tasks);
    } catch (error) {
        console.error('Failed loading tasks:', error);
        container.innerHTML = '<div class="task-item">Failed to load tasks.</div>';
    }
}

function renderTasks(tasks) {
    const container = document.getElementById('taskList');

    if (!tasks.length) {
        container.innerHTML = '<div class="task-item">No tasks found for this filter.</div>';
        return;
    }

    container.innerHTML = tasks.map((task) => {
        const taskId = Number(task.id);
        const isHighlight = focusedTaskId !== null && taskId === focusedTaskId;

        return `
            <div class="task-item ${isHighlight ? 'highlight' : ''}">
                <div class="task-row">
                    <h3>${escapeHtml(task.title || 'Untitled Task')}</h3>
                    <strong>${(task.status || 'pending').replace('_', ' ')}</strong>
                </div>
                <p class="task-meta">
                    Deadline: ${Utils.formatDate(task.deadline)} | Priority: ${Math.round(getEffectivePriorityScore(task))} | Payment: ${Utils.formatCurrency(task.payment_amount)}
                </p>
                <p>${escapeHtml(task.location || 'No location')}</p>
                <div class="task-actions">
                    <button class="task-btn" onclick="setTaskStatus(${taskId}, 'pending')">Pending</button>
                    <button class="task-btn" onclick="setTaskStatus(${taskId}, 'in_progress')">In Progress</button>
                    <button class="task-btn" onclick="setTaskStatus(${taskId}, 'completed')">Complete</button>
                    <button class="task-btn" onclick="deleteTask(${taskId})">Delete</button>
                </div>
            </div>
        `;
    }).join('');
}

async function setTaskStatus(taskId, status) {
    try {
        const task = await taskDB.getTask(taskId);
        if (!task) return;

        task.status = status;
        await taskDB.updateTask(task);

        if (Utils.isOnline()) {
            try {
                await api.updateTask({ id: taskId, status: status });
            } catch (error) {
                console.error('Server status update failed:', error);
            }
        }

        await loadTasks();
    } catch (error) {
        console.error('Status update failed:', error);
        alert('Failed to update task status.');
    }
}

async function deleteTask(taskId) {
    if (!confirm('Delete this task?')) {
        return;
    }

    try {
        await taskDB.deleteTask(taskId);

        if (Utils.isOnline()) {
            try {
                await api.deleteTask(taskId);
            } catch (error) {
                console.error('Server delete failed:', error);
            }
        }

        await loadTasks();
    } catch (error) {
        console.error('Delete failed:', error);
        alert('Failed to delete task.');
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function compareTasksForSchedule(a, b) {
    const scoreDiff = getEffectivePriorityScore(b) - getEffectivePriorityScore(a);
    if (scoreDiff !== 0) {
        return scoreDiff;
    }

    const dateA = a.deadline ? new Date(a.deadline).getTime() : Number.MAX_SAFE_INTEGER;
    const dateB = b.deadline ? new Date(b.deadline).getTime() : Number.MAX_SAFE_INTEGER;

    if (dateA !== dateB) {
        return dateA - dateB;
    }

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
