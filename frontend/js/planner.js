/* ========================================
   PLANNER MODULES PAGE
   ======================================== */

if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

document.addEventListener('DOMContentLoaded', async () => {
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('planDate').value = today;
    document.getElementById('finFrom').value = today.slice(0, 8) + '01';
    document.getElementById('finTo').value = today;
    document.getElementById('remAt').value = toLocalDateTimeInputValue(new Date(Date.now() + (60 * 60 * 1000)));

    await Promise.all([
        loadPlan(),
        loadAvailability(),
        loadTemplates(),
        loadReminders(),
        loadFinance(),
        loadInsights()
    ]);
});

async function loadPlan() {
    const planDate = document.getElementById('planDate').value;
    const summaryEl = document.getElementById('planSummary');
    const listEl = document.getElementById('planList');

    if (!planDate) {
        summaryEl.textContent = 'Please select a date.';
        listEl.innerHTML = '';
        return;
    }

    try {
        const data = await api.getSchedulePlan(planDate);
        summaryEl.textContent = `${data.summary.scheduled_tasks} tasks, ${data.summary.scheduled_minutes} mins (${data.availability.start_time} - ${data.availability.end_time})`;
        listEl.innerHTML = (data.slots || []).map(slot => `
            <div class="item">
                <strong>${escapeHtml(slot.title)}</strong><br>
                ${slot.start_at} - ${slot.end_at} (${slot.duration_minutes} mins)
            </div>
        `).join('') || '<div class="item">No tasks scheduled.</div>';
    } catch (error) {
        summaryEl.textContent = 'Failed to generate plan.';
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

async function saveAvailability() {
    const day = Number(document.getElementById('availDay').value);
    const start = document.getElementById('availStart').value;
    const end = document.getElementById('availEnd').value;

    if (!Number.isInteger(day) || day < 0 || day > 6) {
        alert('Day must be between 0 and 6.');
        return;
    }
    if (!start || !end) {
        alert('Start and end times are required.');
        return;
    }

    try {
        await api.saveAvailability([{ day_of_week: day, start_time: start, end_time: end, is_active: true }]);
        await loadAvailability();
        Utils.showNotification('Availability saved', 'success');
    } catch (error) {
        alert(error.message);
    }
}

async function loadAvailability() {
    const listEl = document.getElementById('availabilityList');
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    try {
        const data = await api.getAvailability();
        listEl.innerHTML = (data.availability || []).map(row => `
            <div class="item">Day ${row.day_of_week} (${dayNames[Number(row.day_of_week)] || 'N/A'}): ${row.start_time} - ${row.end_time} (${row.is_active == 1 ? 'active' : 'inactive'})</div>
        `).join('') || '<div class="item">No availability set yet.</div>';
    } catch (error) {
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

async function createTemplate() {
    const payload = {
        name: document.getElementById('tplName').value.trim(),
        title: document.getElementById('tplTitle').value.trim(),
        urgency: document.getElementById('tplUrgency').value,
        estimated_duration: Number(document.getElementById('tplDuration').value || 30)
    };

    if (!payload.name || !payload.title) {
        alert('Template name and task title are required.');
        return;
    }

    try {
        await api.createTemplate(payload);
        document.getElementById('tplName').value = '';
        document.getElementById('tplTitle').value = '';
        await loadTemplates();
        Utils.showNotification('Template created', 'success');
    } catch (error) {
        alert(error.message);
    }
}

async function loadTemplates() {
    const listEl = document.getElementById('templateList');
    try {
        const data = await api.getTemplates();
        listEl.innerHTML = (data.templates || []).map(t => `
            <div class="item">
                <strong>${escapeHtml(t.name)}</strong> - ${escapeHtml(t.title)}<br>
                ${escapeHtml(t.urgency)} | ${t.estimated_duration} mins
            </div>
        `).join('') || '<div class="item">No templates yet.</div>';
    } catch (error) {
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

async function createReminder() {
    const taskId = Number(document.getElementById('remTaskId').value);
    const remindAt = document.getElementById('remAt').value;

    if (!Number.isInteger(taskId) || taskId <= 0 || !remindAt) {
        alert('Task ID and reminder time are required.');
        return;
    }

    try {
        await api.createReminder({ task_id: taskId, remind_at: formatDateTimeLocalToMySQL(remindAt), channel: 'in_app' });
        document.getElementById('remTaskId').value = '';
        document.getElementById('remAt').value = toLocalDateTimeInputValue(new Date(Date.now() + (60 * 60 * 1000)));
        await loadReminders();
        Utils.showNotification('Reminder created', 'success');
    } catch (error) {
        alert(error.message);
    }
}

async function loadReminders() {
    const listEl = document.getElementById('reminderList');
    try {
        const data = await api.getReminders('pending');
        listEl.innerHTML = (data.reminders || []).map(r => `
            <div class="item">Task #${r.task_id} (${escapeHtml(r.task_title)}) at ${r.remind_at}</div>
        `).join('') || '<div class="item">No pending reminders.</div>';
    } catch (error) {
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

async function loadFinance() {
    const from = document.getElementById('finFrom').value;
    const to = document.getElementById('finTo').value;
    const container = document.getElementById('financeSummary');

    if (!from || !to) {
        container.innerHTML = '<div class="item">Select both from and to dates.</div>';
        return;
    }
    if (new Date(from).getTime() > new Date(to).getTime()) {
        container.innerHTML = '<div class="item">From date cannot be after To date.</div>';
        return;
    }

    try {
        const data = await api.getFinanceSummary(from, to);
        const s = data.summary;
        container.innerHTML = `
            <div class="item">Total tasks: ${s.total_tasks}</div>
            <div class="item">Completed tasks: ${s.completed_tasks}</div>
            <div class="item">Earned: ${Utils.formatCurrency(s.earned_amount)}</div>
            <div class="item">Pending collection: ${Utils.formatCurrency(s.pending_collection)}</div>
        `;
    } catch (error) {
        container.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

async function downloadMyReportPdf() {
    const from = document.getElementById('finFrom').value;
    const to = document.getElementById('finTo').value;

    if (!from || !to) {
        alert('Select both From and To dates before downloading.');
        return;
    }
    if (new Date(from).getTime() > new Date(to).getTime()) {
        alert('From date cannot be after To date.');
        return;
    }

    try {
        await api.downloadReportPdf({ from, to, scope: 'user' });
    } catch (error) {
        alert(error.message || 'Failed to download report.');
    }
}

async function loadInsights() {
    const container = document.getElementById('insightsList');
    try {
        const data = await api.getInsights();
        const metrics = data.metrics || {};
        const recs = data.recommendations || [];
        container.innerHTML = `
            <div class="item">Open tasks: ${metrics.open_tasks}</div>
            <div class="item">Completed: ${metrics.completed_tasks}</div>
            <div class="item">Avg duration: ${metrics.average_duration_minutes} mins</div>
            <div class="item">Total earnings: ${Utils.formatCurrency(metrics.total_earnings)}</div>
            ${recs.map(r => `<div class="item">${escapeHtml(r)}</div>`).join('')}
        `;
    } catch (error) {
        container.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
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

function formatDateTimeLocalToMySQL(dateTimeValue) {
    return `${dateTimeValue.replace('T', ' ')}:00`;
}

function toLocalDateTimeInputValue(dateObj) {
    const tzOffsetMs = dateObj.getTimezoneOffset() * 60000;
    return new Date(dateObj.getTime() - tzOffsetMs).toISOString().slice(0, 16);
}
