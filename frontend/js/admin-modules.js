/* Shared helpers for admin module pages */
const adminModuleApi = new TaskAPI();

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('reminderQueueList')) {
        loadReminderQueueModule();
    }
    if (document.getElementById('schedulePreviewList')) {
        setupSchedulePreviewModule();
    }
});

async function loadReminderQueueModule() {
    const list = document.getElementById('reminderQueueList');
    if (!list) return;
    list.innerHTML = '<p class="empty">Loading reminders...</p>';
    try {
        const data = await adminModuleApi.getAdminReminders(24);
        const reminders = data.reminders || [];
        if (!reminders.length) {
            list.innerHTML = '<p class="empty">No reminders due in next 24 hours.</p>';
            return;
        }
        list.innerHTML = reminders.map(r => `
            <div class="row">
                <strong>${escapeHtml(r.task_title)}</strong><br>
                User: ${escapeHtml(r.user_name)}<br>
                ${escapeHtml(r.channel)} reminder at ${Utils.formatDate(r.remind_at)}
            </div>
        `).join('');
    } catch (error) {
        list.innerHTML = `<p class="empty">${escapeHtml(error.message || 'Failed to load reminders')}</p>`;
    }
}

async function setupSchedulePreviewModule() {
    const userSelect = document.getElementById('schedulePreviewUser');
    const dateInput = document.getElementById('schedulePreviewDate');
    const button = document.getElementById('schedulePreviewButton');
    const list = document.getElementById('schedulePreviewList');
    const summary = document.getElementById('schedulePreviewSummary');

    if (!userSelect || !dateInput || !button || !list) return;

    try {
        const data = await adminModuleApi.getAdminUsersFiltered({});
        const users = data.users || [];
        userSelect.innerHTML = '<option value="">Select user</option>' + users.map(u => `<option value="${Number(u.id)}">${escapeHtml(u.full_name)} (${escapeHtml(u.phone_number)})</option>`).join('');
    } catch (error) {
        list.innerHTML = `<p class="empty">${escapeHtml(error.message || 'Failed to load users')}</p>`;
    }

    button.addEventListener('click', async () => {
        const userId = Number(userSelect.value);
        const date = dateInput.value;
        if (!userId) {
            list.innerHTML = '<p class="empty">Select a user to preview schedule.</p>';
            return;
        }
        if (!date) {
            list.innerHTML = '<p class="empty">Select a date.</p>';
            return;
        }
        list.innerHTML = '<p class="empty">Loading preview...</p>';
        try {
            const data = await adminModuleApi.getAdminSchedulePreview(userId, date);
            const slots = data.slots || [];
            const userLine = `<div class="row"><strong>${escapeHtml(data.user.full_name)}</strong><br>${escapeHtml(data.date)} · ${escapeHtml(data.availability.start_time)} - ${escapeHtml(data.availability.end_time)}</div>`;
            if (!slots.length) {
                if (summary) {
                    summary.innerHTML = `<strong>${data.user.full_name}</strong><br>${data.date} · ${formatTimeRange(data.availability.start_time, data.availability.end_time)}<br>No tasks scheduled.`;
                }
                list.innerHTML = `<div class="empty">No schedulable tasks for this date.</div>`;
                return;
            }
            if (summary) {
                const totalMinutes = slots.reduce((sum, slot) => sum + Number(slot.duration_minutes || 0), 0);
                summary.innerHTML = `
                    <strong>${slots.length} tasks · ${totalMinutes} mins (${formatTimeRange(data.availability.start_time, data.availability.end_time)})</strong><br>
                    ${escapeHtml(data.user.full_name)} · ${escapeHtml(data.date)}
                `;
            }
            list.innerHTML = slots.map(slot => `
                <div class="schedule-slot">
                    <strong>${escapeHtml(slot.title)}</strong>
                    <small>${formatSlotTimes(slot.start_at, slot.end_at)} (${Number(slot.duration_minutes || 0)} mins)</small>
                </div>
            `).join('');
        } catch (error) {
            if (summary) {
                summary.innerHTML = `<p class="empty">${escapeHtml(error.message || 'Failed to load schedule preview')}</p>`;
            }
            list.innerHTML = `<p class="empty">${escapeHtml(error.message || 'Failed to load schedule preview')}</p>`;
        }
    });
}

function formatTimeRange(start, end) {
    const startShort = (start || '').slice(0, 5);
    const endShort = (end || '').slice(0, 5);
    return `${startShort} - ${endShort}`;
}

function formatSlotTimes(start, end) {
    const startShort = (start || '').replace('T', ' ').slice(0, 16);
    const endShort = (end || '').replace('T', ' ').slice(0, 16);
    return `${startShort} - ${endShort}`;
}

function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
