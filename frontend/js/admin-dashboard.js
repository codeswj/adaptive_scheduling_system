/* ========================================
   ADMIN DASHBOARD
   ======================================== */

if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

const adminUser = Utils.getUserData();
if (!adminUser || adminUser.role !== 'admin') {
    window.location.href = 'dashboard.html';
}

document.addEventListener('DOMContentLoaded', async () => {
    if (adminUser?.full_name) {
        document.getElementById('adminWelcome').textContent = `Welcome, ${adminUser.full_name}`;
    }
    const today = new Date().toISOString().slice(0, 10);
    const previewDateEl = document.getElementById('previewDate');
    if (previewDateEl) previewDateEl.value = today;
    await refreshAdminData();
});

async function refreshAdminData() {
    try {
        const filters = getUserFilters();
        const incidentStatus = document.getElementById('incidentStatusFilter')?.value || 'all';
        const [overviewData, usersData, tasksData, leaderboardData, remindersData, incidentsData, dispatchData] = await Promise.all([
            api.getAdminOverview(),
            api.getAdminUsersFiltered(filters),
            api.getAdminTasks(),
            api.getAdminLeaderboard(),
            api.getAdminReminders(24),
            api.getAdminIncidents(incidentStatus),
            api.getAdminDispatchBoard()
        ]);

        renderOverview(overviewData.overview || {});
        renderUsers(usersData.users || []);
        renderTasks(tasksData.tasks || []);
        renderLeaderboard(leaderboardData.leaderboard || []);
        renderReminderQueue(remindersData.reminders || []);
        renderIncidents(incidentsData.summary || {}, incidentsData.incidents || []);
        hydratePreviewUserSelect(usersData.users || []);
        renderDispatchBoard(dispatchData.tasks || [], dispatchData.candidates || [], dispatchData.recent_dispatches || []);
    } catch (error) {
        console.error('Failed loading admin data:', error);
        alert(error.message || 'Failed loading admin data');
    }
}

function renderOverview(overview) {
    document.getElementById('totalUsers').textContent = overview.total_users || 0;
    document.getElementById('activeUsers').textContent = overview.active_users || 0;
    document.getElementById('totalTasks').textContent = overview.total_tasks || 0;
    document.getElementById('completedTasks').textContent = overview.completed_tasks || 0;
    document.getElementById('overdueTasks').textContent = overview.overdue_tasks || 0;
    document.getElementById('platformEarnings').textContent = Utils.formatCurrency(overview.total_earnings || 0);
}

function renderUsers(users) {
    const usersList = document.getElementById('usersList');
    if (!users.length) {
        usersList.innerHTML = '<div class="row">No users found.</div>';
        return;
    }

    usersList.innerHTML = users.map((user) => `
        <div class="row">
            <strong>${escapeHtml(user.full_name)}</strong><br>
            ${escapeHtml(user.phone_number)} | ${escapeHtml(user.work_type || 'other')}<br>
            Tasks: ${user.tasks_count || 0}, Completed: ${user.completed_count || 0}<br>
            Status: ${Number(user.is_active) === 1 ? 'Active' : 'Inactive'}
            <div style="margin-top:0.4rem;">
                <button class="btn" onclick="toggleUserStatus(${Number(user.id)}, ${Number(user.is_active) === 1 ? 'false' : 'true'})">
                    ${Number(user.is_active) === 1 ? 'Deactivate' : 'Activate'}
                </button>
            </div>
        </div>
    `).join('');
}

function renderTasks(tasks) {
    const tasksList = document.getElementById('adminTasksList');
    if (!tasks.length) {
        tasksList.innerHTML = '<div class="row">No operational tasks found.</div>';
        return;
    }

    tasksList.innerHTML = tasks.map((task) => {
        const isOverdue = task.deadline && new Date(task.deadline).getTime() < Date.now();
        return `
            <div class="row">
                <strong>${escapeHtml(task.title)}</strong> ${isOverdue ? '(Overdue)' : ''}<br>
                Owner: ${escapeHtml(task.owner_name)} | Status: ${escapeHtml(task.status)}<br>
                Urgency: ${escapeHtml(task.urgency)} | Priority: ${Math.round(Number(task.priority_score || 0))}<br>
                Deadline: ${task.deadline ? Utils.formatDate(task.deadline) : 'None'}
            </div>
        `;
    }).join('');
}

function renderLeaderboard(rows) {
    const list = document.getElementById('leaderboardList');
    if (!rows.length) {
        list.innerHTML = '<div class="row">No leaderboard data yet.</div>';
        return;
    }

    list.innerHTML = rows.map((row, index) => `
        <div class="row">
            <strong>#${index + 1} ${escapeHtml(row.full_name)}</strong><br>
            ${escapeHtml(row.work_type || 'other')}<br>
            Completed: ${Number(row.completed_tasks || 0)} |
            Earnings: ${Utils.formatCurrency(row.earnings || 0)} |
            Avg Duration: ${Math.round(Number(row.avg_duration || 0))} mins
        </div>
    `).join('');
}

function renderReminderQueue(reminders) {
    const list = document.getElementById('reminderQueueList');
    if (!reminders.length) {
        list.innerHTML = '<div class="row">No reminders due in next 24 hours.</div>';
        return;
    }

    list.innerHTML = reminders.map((r) => `
        <div class="row">
            <strong>${escapeHtml(r.task_title)}</strong><br>
            User: ${escapeHtml(r.user_name)}<br>
            At: ${Utils.formatDate(r.remind_at)} <span class="badge">${escapeHtml(r.channel)}</span>
        </div>
    `).join('');
}

function hydratePreviewUserSelect(users) {
    const select = document.getElementById('previewUserId');
    if (!select) return;

    const currentValue = select.value;
    select.innerHTML = '<option value="">Select user</option>' + users.map((u) => (
        `<option value="${Number(u.id)}">${escapeHtml(u.full_name)} (${escapeHtml(u.phone_number)})</option>`
    )).join('');

    if (currentValue) select.value = currentValue;
}

async function loadSchedulePreview() {
    const userId = Number(document.getElementById('previewUserId').value);
    const date = document.getElementById('previewDate').value;
    const list = document.getElementById('schedulePreviewList');

    if (!Number.isInteger(userId) || userId <= 0) {
        list.innerHTML = '<div class="row">Select a user to preview schedule.</div>';
        return;
    }
    if (!date) {
        list.innerHTML = '<div class="row">Select a date.</div>';
        return;
    }

    try {
        const data = await api.getAdminSchedulePreview(userId, date);
        const slots = data.slots || [];
        const heading = `
            <div class="row">
                <strong>${escapeHtml(data.user.full_name)}</strong><br>
                ${escapeHtml(data.date)} | ${escapeHtml(data.availability.start_time)} - ${escapeHtml(data.availability.end_time)}
            </div>
        `;
        if (!slots.length) {
            list.innerHTML = `${heading}<div class="row">No schedulable tasks for this date.</div>`;
            return;
        }
        list.innerHTML = heading + slots.map((s) => `
            <div class="row">
                <strong>${escapeHtml(s.title)}</strong><br>
                ${escapeHtml(s.start_at)} to ${escapeHtml(s.end_at)} (${Number(s.duration_minutes)} mins) |
                Priority ${Math.round(Number(s.priority_score || 0))}
            </div>
        `).join('');
    } catch (error) {
        list.innerHTML = `<div class="row">${escapeHtml(error.message || 'Failed to load schedule preview')}</div>`;
    }
}

function getUserFilters() {
    const search = (document.getElementById('userSearchInput')?.value || '').trim();
    const status = document.getElementById('userStatusFilter')?.value || 'all';
    const work_type = document.getElementById('userWorkTypeFilter')?.value || '';
    return { search, status, work_type };
}

async function applyUserFilters() {
    await refreshAdminData();
}

async function loadIncidentCenter() {
    try {
        const status = document.getElementById('incidentStatusFilter')?.value || 'all';
        const data = await api.getAdminIncidents(status);
        renderIncidents(data.summary || {}, data.incidents || []);
    } catch (error) {
        alert(error.message || 'Failed to load incidents');
    }
}

function renderIncidents(summary, incidents) {
    const summaryEl = document.getElementById('incidentSummary');
    const listEl = document.getElementById('incidentList');

    if (summaryEl) {
        summaryEl.textContent = `Open: ${summary.open_count || 0} | Investigating: ${summary.investigating_count || 0} | Critical Open: ${summary.critical_open_count || 0}`;
    }

    if (!listEl) return;

    if (!incidents.length) {
        listEl.innerHTML = '<div class="row">No incidents found for selected filter.</div>';
        return;
    }

    listEl.innerHTML = incidents.map((incident) => `
        <div class="row">
            <strong>${escapeHtml(incident.title)}</strong>
            <span class="badge">${escapeHtml(incident.severity || 'medium')}</span>
            <span class="badge">${escapeHtml(incident.status || 'open')}</span><br>
            Type: ${escapeHtml(incident.type || 'unknown')} | Created: ${incident.created_at ? Utils.formatDate(incident.created_at) : '-'}<br>
            ${escapeHtml(incident.description || '')}
            <div style="margin-top:0.4rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                <button class="btn" onclick="updateIncidentStatus(${Number(incident.id)}, 'investigating')">Investigate</button>
                <button class="btn" onclick="updateIncidentStatus(${Number(incident.id)}, 'resolved')">Resolve</button>
                <button class="btn" onclick="updateIncidentStatus(${Number(incident.id)}, 'dismissed')">Dismiss</button>
            </div>
        </div>
    `).join('');
}

async function updateIncidentStatus(incidentId, status) {
    try {
        await api.updateAdminIncidentStatus(incidentId, status);
        await loadIncidentCenter();
    } catch (error) {
        alert(error.message || 'Failed to update incident');
    }
}

function renderDispatchBoard(tasks, candidates, history) {
    const taskSelect = document.getElementById('dispatchTaskSelect');
    const userSelect = document.getElementById('dispatchTargetUserSelect');
    const historyList = document.getElementById('dispatchHistoryList');

    if (taskSelect) {
        const existing = taskSelect.value;
        taskSelect.innerHTML = '<option value="">Select task</option>' + tasks.map((t) => (
            `<option value="${Number(t.id)}">#${Number(t.id)} ${escapeHtml(t.title)} (${escapeHtml(t.owner_name || '-')})</option>`
        )).join('');
        if (existing) taskSelect.value = existing;
    }

    if (userSelect) {
        const existingUser = userSelect.value;
        userSelect.innerHTML = '<option value="">Select target user</option>' + candidates.map((u) => (
            `<option value="${Number(u.id)}">${escapeHtml(u.full_name)} | load ${Number(u.active_load || 0)}</option>`
        )).join('');
        if (existingUser) userSelect.value = existingUser;
    }

    if (!historyList) return;
    if (!history.length) {
        historyList.innerHTML = '<div class="row">No dispatch history yet.</div>';
        return;
    }

    historyList.innerHTML = history.map((h) => `
        <div class="row">
            <strong>${escapeHtml(h.task_title || `Task #${h.task_id}`)}</strong><br>
            From: ${escapeHtml(h.from_user_name)} -> To: ${escapeHtml(h.to_user_name)}<br>
            By: ${escapeHtml(h.admin_name)} | ${h.created_at ? Utils.formatDate(h.created_at) : '-'}<br>
            ${escapeHtml(h.reason || 'No reason provided')}
        </div>
    `).join('');
}

async function executeDispatch() {
    const taskId = Number(document.getElementById('dispatchTaskSelect')?.value || 0);
    const toUserId = Number(document.getElementById('dispatchTargetUserSelect')?.value || 0);
    const reason = (document.getElementById('dispatchReason')?.value || '').trim();
    const statusEl = document.getElementById('dispatchStatus');

    if (!Number.isInteger(taskId) || taskId <= 0) {
        if (statusEl) statusEl.textContent = 'Select a task to reassign.';
        return;
    }
    if (!Number.isInteger(toUserId) || toUserId <= 0) {
        if (statusEl) statusEl.textContent = 'Select a target user.';
        return;
    }

    try {
        await api.reassignTaskDispatch(taskId, toUserId, reason);
        if (statusEl) statusEl.textContent = 'Task reassigned successfully.';
        const reasonEl = document.getElementById('dispatchReason');
        if (reasonEl) reasonEl.value = '';
        await refreshAdminData();
    } catch (error) {
        if (statusEl) statusEl.textContent = error.message || 'Failed to reassign task.';
    }
}

async function downloadPlatformReportPdf() {
    const today = new Date();
    const to = today.toISOString().slice(0, 10);
    const from = `${to.slice(0, 8)}01`;

    try {
        await api.downloadReportPdf({ from, to, scope: 'platform' });
    } catch (error) {
        alert(error.message || 'Failed to download platform report.');
    }
}

async function toggleUserStatus(userId, nextState) {
    try {
        await api.updateAdminUserStatus(userId, nextState === true || nextState === 'true');
        await refreshAdminData();
    } catch (error) {
        alert(error.message || 'Failed to update user');
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
