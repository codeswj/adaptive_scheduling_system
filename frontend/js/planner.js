/* ========================================
   PLANNER MODULES PAGE
   ======================================== */

if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

let remindersCache = [];
let editingReminderId = null;
let planDataCache = null;
let editingScheduleTaskId = null;
let availabilityCache = [];
let editingAvailabilityDay = null;
let templateCache = [];
let editingTemplateId = null;

document.addEventListener('DOMContentLoaded', async () => {
    const today = new Date().toISOString().slice(0, 10);
    document.getElementById('planDate').value = today;
    document.getElementById('finFrom').value = today.slice(0, 8) + '01';
    document.getElementById('finTo').value = today;
    document.getElementById('reminderDatetime').value = toLocalDateTimeInputValue(new Date(Date.now() + (60 * 60 * 1000)));

    await Promise.all([
        loadPlan(),
        loadAvailability(),
        loadTemplates(),
        loadReminders(),
        loadFinance(),
        loadInsights(),
        loadTasksForDropdown() // Load tasks for dropdown
    ]);
});

async function downloadSchedulePDF() {
    const planDate = document.getElementById('planDate').value;
    const summaryEl = document.getElementById('planSummary');
    const listEl = document.getElementById('planList');

    if (!planDate) {
        summaryEl.textContent = 'Please select a date.';
        listEl.innerHTML = '';
        return;
    }

    summaryEl.textContent = 'Generating schedule PDF...';
    listEl.innerHTML = '<div class="item">Loading schedule data...</div>';

    try {
        // Get schedule data
        const data = await api.getSchedulePlan(planDate);
        
        // Generate PDF content
        const pdfContent = generateSchedulePDFContent(planDate, data);
        
        // Create download
        const blob = new Blob([pdfContent], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `daily_schedule_${planDate}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Update display
        summaryEl.textContent = `${data.summary.scheduled_tasks} tasks, ${data.summary.scheduled_minutes} mins (${data.availability.start_time} - ${data.availability.end_time})`;
        listEl.innerHTML = (data.slots || []).map(slot => `
            <div class="item">
                <strong>${escapeHtml(slot.title)}</strong><br>
                ${slot.start_at} - ${slot.end_at} (${slot.duration_minutes} mins)
            </div>
        `).join('') || '<div class="item">No tasks scheduled.</div>';
        
        Utils.showNotification('Schedule PDF downloaded successfully!', 'success');
        
    } catch (error) {
        console.error('Schedule PDF generation error:', error);
        summaryEl.textContent = 'Failed to generate PDF.';
        
        // Provide helpful error messages
        if (error.message.includes('Database connection failed')) {
            listEl.innerHTML = `<div class="item">Database connection failed. Please check your database setup.</div>
                              <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
        } else if (error.message.includes('user_availability')) {
            listEl.innerHTML = `<div class="item">Availability table not found. Please run the database setup script.</div>
                              <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
        } else {
            listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
        }
        setAvailabilityStatus('Unable to load availability.');
    }
}

function renderAvailabilityList() {
    const listEl = document.getElementById('availabilityList');
    if (!listEl) return;

    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    if (!availabilityCache.length) {
        listEl.innerHTML = '<div class="item">No availability set yet.</div>';
        return;
    }

    listEl.innerHTML = availabilityCache.map(row => {
        const dayName = dayNames[Number(row.day_of_week)] || 'Day';
        const range = `${escapeHtml(row.start_time)} - ${escapeHtml(row.end_time)}`;
        const status = row.is_active == 1 ? ' (active)' : ' (inactive)';
        const safeStart = (row.start_time || '').replace(/'/g, "\\'");
        const safeEnd = (row.end_time || '').replace(/'/g, "\\'");

        return `
            <div class="item availability-row">
                <span>${dayName} (${row.day_of_week}): ${range}${status}</span>
                <span class="availability-actions">
                    <button type="button" title="Edit availability" onclick="openAvailabilityModal(${row.day_of_week}, '${safeStart}', '${safeEnd}', ${row.is_active})">&#9998;</button>
                    <button type="button" class="delete" title="Delete availability" onclick="deleteAvailabilityEntry(${row.day_of_week})">&#128465;</button>
                </span>
            </div>
        `;
    }).join('');
}

function openAvailabilityModal(day, startTime, endTime, isActive) {
    editingAvailabilityDay = day;
    document.getElementById('modalDay').value = day;
    document.getElementById('modalStart').value = startTime || '08:00';
    document.getElementById('modalEnd').value = endTime || '18:00';
    document.getElementById('modalActive').checked = Number(isActive) === 1;

    const modal = document.getElementById('availabilityModal');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    setAvailabilityStatus(`Editing availability for day ${day}`);
}

function closeAvailabilityModal() {
    editingAvailabilityDay = null;
    const modal = document.getElementById('availabilityModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    setAvailabilityStatus('');
}

async function saveAvailabilityModal() {
    const day = Number(document.getElementById('modalDay').value);
    const start = document.getElementById('modalStart').value;
    const end = document.getElementById('modalEnd').value;
    const isActive = document.getElementById('modalActive').checked ? 1 : 0;

    if (Number.isNaN(day) || day < 0 || day > 6) {
        alert('Select a valid day.');
        return;
    }
    if (!start || !end) {
        alert('Start and end times are required.');
        return;
    }

    try {
        await api.saveAvailability([{
            day_of_week: day,
            start_time: start,
            end_time: end,
            is_active: isActive
        }]);
        await loadAvailability();
        Utils.showNotification('Availability updated', 'success');
        setAvailabilityStatus(`Availability saved for day ${day}.`);
        closeAvailabilityModal();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteAvailabilityEntry(day) {
    if (!confirm('Delete this availability entry?')) {
        return;
    }

    try {
        await api.deleteAvailabilityDay(day);
        await loadAvailability();
        Utils.showNotification('Availability deleted', 'success');
        setAvailabilityStatus(`Availability removed for day ${day}.`);
    } catch (error) {
        alert(error.message);
    }
}

function setAvailabilityStatus(message) {
    const statusEl = document.getElementById('availabilityStatus');
    if (!statusEl) return;
    statusEl.textContent = message || '';
}

function generateSchedulePDFContent(date, data) {
    const slots = data.slots || [];
    const summary = data.summary;
    const availability = data.availability;
    
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Schedule - ${date}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
        .summary { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .schedule-table th, .schedule-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .schedule-table th { background-color: #007bff; color: white; }
        .time-col { width: 120px; font-weight: bold; }
        .task-col { }
        .duration-col { width: 100px; text-align: center; }
        .no-tasks { text-align: center; color: #666; font-style: italic; padding: 20px; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📅 Daily Schedule</h1>
        <h2>${date}</h2>
        <p>Generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
    </div>
    
    <div class="summary">
        <h3>📊 Schedule Summary</h3>
        <p><strong>Total Tasks:</strong> ${summary.scheduled_tasks}</p>
        <p><strong>Total Duration:</strong> ${summary.scheduled_minutes} minutes</p>
        <p><strong>Working Hours:</strong> ${availability.start_time} - ${availability.end_time}</p>
        <p><strong>Availability:</strong> ${summary.available_minutes} minutes available</p>
    </div>
    
    <h3>⏰ Scheduled Tasks</h3>
    ${slots.length > 0 ? `
        <table class="schedule-table">
            <thead>
                <tr>
                    <th class="time-col">Time</th>
                    <th class="task-col">Task</th>
                    <th class="duration-col">Duration</th>
                </tr>
            </thead>
            <tbody>
                ${slots.map(slot => `
                    <tr>
                        <td class="time-col">${slot.start_at} - ${slot.end_at}</td>
                        <td class="task-col"><strong>${escapeHtml(slot.title)}</strong></td>
                        <td class="duration-col">${slot.duration_minutes} mins</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    ` : '<div class="no-tasks">No tasks scheduled for this day.</div>'}
    
    <div class="footer">
        <p>Generated by TaskFlow Scheduling System</p>
        <p>This is an automated schedule based on your availability and task priorities.</p>
    </div>
</body>
</html>`;
}

async function loadPlan() {
    const planDate = document.getElementById('planDate').value;
    const summaryEl = document.getElementById('planSummary');
    const listEl = document.getElementById('planList');

    if (!planDate) {
        if (summaryEl) summaryEl.textContent = 'Please select a date.';
        if (listEl) listEl.innerHTML = '';
        return;
    }

    setPlanStatus('');

    try {
        const data = await api.getSchedulePlan(planDate);
        planDataCache = data;
        renderPlanSummary(data);
        renderPlanList(data);
    } catch (error) {
        console.error('Plan load error:', error);
        if (summaryEl) summaryEl.textContent = 'Failed to generate plan.';
        if (listEl) {
            if (error.message.includes('Database connection failed')) {
                listEl.innerHTML = `<div class="item">Database connection failed. Please check your database setup.</div>
                                  <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
            } else if (error.message.includes('user_availability')) {
                listEl.innerHTML = `<div class="item">Availability table not found. Please run the database setup script.</div>
                                  <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
            } else {
                listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
            }
        }
    }
}

function renderPlanSummary(data) {
    const summaryEl = document.getElementById('planSummary');
    if (!summaryEl) return;
    const summary = data?.summary || {};
    const availability = data?.availability || {};
    const start = availability.start_time || '00:00';
    const end = availability.end_time || '00:00';
    const tasks = summary.scheduled_tasks || 0;
    const minutes = summary.scheduled_minutes || 0;
    summaryEl.textContent = `${tasks} tasks, ${minutes} mins (${start} - ${end})`;
}

function renderPlanList(data) {
    const listEl = document.getElementById('planList');
    if (!listEl) return;
    const slots = data?.slots || [];

    if (!slots.length) {
        listEl.innerHTML = '<div class="item">No tasks scheduled.</div>';
        return;
    }

    listEl.innerHTML = slots.map(slot => {
        const title = escapeHtml(slot.title || 'Untitled');
        const duration = slot.duration_minutes || 0;
        const safeStart = escapeSingleQuote(slot.start_at);
        const safeTitle = escapeSingleQuote(slot.title);
        return `
            <div class="item availability-row">
                <span>
                    <strong>${title}</strong><br>
                    ${slot.start_at} - ${slot.end_at} (${duration} mins)
                </span>
                <span class="inline-actions">
                    <button type="button" title="Edit task" onclick="openScheduleEditModal(${slot.task_id}, '${safeStart}', ${duration}, '${safeTitle}')">&#9998;</button>
                    <button type="button" class="delete" title="Remove task" onclick="deleteScheduledTask(${slot.task_id})">&#128465;</button>
                </span>
            </div>
        `;
    }).join('');
}

function escapeSingleQuote(value) {
    return String(value || '').replace(/'/g, "\\'");
}

async function saveAvailability() {
    const dayInput = document.getElementById('availDay');
    const day = Number(dayInput.value);
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
        dayInput.value = '';
        await loadAvailability();
        Utils.showNotification('Availability saved', 'success');
        setAvailabilityStatus(`Availability saved for day ${day}.`);
    } catch (error) {
        alert(error.message);
    }
}

async function loadAvailability() {
    const listEl = document.getElementById('availabilityList');

    try {
        const data = await api.getAvailability();
        availabilityCache = data.availability || [];
        renderAvailabilityList();
        setAvailabilityStatus('');
    } catch (error) {
        console.error('Availability load error:', error);
        
        if (error.message.includes('Database connection failed')) {
            listEl.innerHTML = `<div class="item">Database connection failed. Please check your database setup.</div>
                              <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
        } else if (error.message.includes('user_availability')) {
            listEl.innerHTML = `<div class="item">Availability table not found. Please run the database setup script.</div>
                              <div class="item">Run: <a href="../backend/setup_database.php" target="_blank">Database Setup Script</a></div>`;
        } else {
            listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
        }
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
        templateCache = data.templates || [];
        renderTemplateList(templateCache);
    } catch (error) {
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
    }
}

function renderTemplateList(templates) {
    const listEl = document.getElementById('templateList');
    if (!listEl) return;

    if (!templates || !templates.length) {
        listEl.innerHTML = '<div class="item">No templates yet.</div>';
        return;
    }

    listEl.innerHTML = templates.map(t => {
        const safeName = escapeHtml(t.name);
        const safeTitle = escapeHtml(t.title);
        const safeUrgency = escapeHtml(t.urgency || 'medium');
        const safeDuration = Number.isFinite(Number(t.estimated_duration)) ? t.estimated_duration : '30';
        return `
            <div class="item availability-row">
                <span>
                    <strong>${safeName}</strong> - ${safeTitle}<br>
                    ${safeUrgency} | ${safeDuration} mins
                </span>
                <span class="inline-actions">
                    <button type="button" title="Edit template" onclick="openTemplateModal(${t.id})">&#9998;</button>
                    <button type="button" class="delete" title="Delete template" onclick="deleteTemplateEntry(${t.id})">&#128465;</button>
                </span>
            </div>
        `;
    }).join('');
}

function openTemplateModal(templateId) {
    const template = templateCache.find(item => item.id === templateId);
    if (!template) return;

    editingTemplateId = templateId;
    document.getElementById('modalTemplateName').value = template.name || '';
    document.getElementById('modalTemplateTitle').value = template.title || '';
    document.getElementById('modalTemplateUrgency').value = template.urgency || 'medium';
    document.getElementById('modalTemplateDuration').value = template.estimated_duration || 30;

    const modal = document.getElementById('templateModal');
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function closeTemplateModal() {
    editingTemplateId = null;
    const modal = document.getElementById('templateModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

async function saveTemplateFromModal() {
    if (!editingTemplateId) return;

    const name = document.getElementById('modalTemplateName').value.trim();
    const title = document.getElementById('modalTemplateTitle').value.trim();
    const urgency = document.getElementById('modalTemplateUrgency').value;
    const durationInput = Number(document.getElementById('modalTemplateDuration').value);
    const estimatedDuration = Number.isNaN(durationInput) || durationInput < 1 ? 30 : durationInput;

    if (!name || !title) {
        alert('Template name and title are required.');
        return;
    }

    try {
        await api.updateTemplate({
            id: editingTemplateId,
            name,
            title,
            urgency,
            estimated_duration: estimatedDuration
        });
        await loadTemplates();
        closeTemplateModal();
        Utils.showNotification('Template updated', 'success');
    } catch (error) {
        alert(error.message || 'Failed to update template.');
    }
}

async function deleteTemplateEntry(templateId) {
    if (!confirm('Delete this template?')) return;

    try {
        await api.deleteTemplate(templateId);
        await loadTemplates();
        Utils.showNotification('Template deleted', 'success');
    } catch (error) {
        alert(error.message || 'Failed to delete template.');
    }
}

let allTasks = [];

// Load tasks for dropdown
async function loadTasksForDropdown() {
    try {
        const tasks = await api.fetchTasks();
        allTasks = tasks || [];
        console.log('Loaded tasks for dropdown:', allTasks);
    } catch (error) {
        console.error('Failed to load tasks for dropdown:', error);
        allTasks = [];
    }
}

// Show task dropdown when input is focused
function showTaskDropdown() {
    const dropdown = document.getElementById('taskDropdown');
    const input = document.getElementById('reminderTaskTitle');
    
    if (allTasks.length === 0) {
        dropdown.innerHTML = '<div class="task-dropdown-item">No tasks available</div>';
    } else {
        dropdown.innerHTML = allTasks.map(task => `
            <div class="task-dropdown-item" onclick="selectTask('${task.title.replace(/'/g, "\\'")}')">
                ${escapeHtml(task.title)}
            </div>
        `).join('');
    }
    
    dropdown.style.display = 'block';
}

// Filter tasks based on input
function filterTasks(searchTerm) {
    const dropdown = document.getElementById('taskDropdown');
    
    if (!searchTerm) {
        showTaskDropdown();
        return;
    }
    
    const filteredTasks = allTasks.filter(task => 
        task.title.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    if (filteredTasks.length === 0) {
        dropdown.innerHTML = '<div class="task-dropdown-item">No tasks found</div>';
    } else {
        dropdown.innerHTML = filteredTasks.map(task => `
            <div class="task-dropdown-item" onclick="selectTask('${task.title.replace(/'/g, "\\'")}')">
                ${escapeHtml(task.title)}
            </div>
        `).join('');
    }
    
    dropdown.style.display = 'block';
}

// Select a task from dropdown
function selectTask(taskTitle) {
    const input = document.getElementById('reminderTaskTitle');
    const dropdown = document.getElementById('taskDropdown');
    
    input.value = taskTitle;
    dropdown.style.display = 'none';
}

// Hide dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('taskDropdown');
    const input = document.getElementById('reminderTaskTitle');
    
    if (!input.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// Add event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    const taskInput = document.getElementById('reminderTaskTitle');
    if (taskInput) {
        taskInput.addEventListener('click', showTaskDropdown);
        taskInput.addEventListener('focus', showTaskDropdown);
        taskInput.addEventListener('input', function() {
            filterTasks(this.value);
        });
    }
    const reminderModal = document.getElementById('reminderModal');
    if (reminderModal) {
        reminderModal.addEventListener('click', (event) => {
            if (event.target === reminderModal) {
                closeReminderModal();
            }
        });
    }
    const availabilityModal = document.getElementById('availabilityModal');
    if (availabilityModal) {
        availabilityModal.addEventListener('click', (event) => {
            if (event.target === availabilityModal) {
                closeAvailabilityModal();
            }
        });
    }
    const templateModal = document.getElementById('templateModal');
    if (templateModal) {
        templateModal.addEventListener('click', (event) => {
            if (event.target === templateModal) {
                closeTemplateModal();
            }
        });
    }
    const scheduleModal = document.getElementById('scheduleModal');
    if (scheduleModal) {
        scheduleModal.addEventListener('click', (event) => {
            if (event.target === scheduleModal) {
                closeScheduleModal();
            }
        });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeReminderModal();
            closeAvailabilityModal();
            closeScheduleModal();
            closeTemplateModal();
        }
    });
});

async function createReminder() {
    const taskTitle = document.getElementById('reminderTaskTitle').value.trim();
    const remindAt = document.getElementById('reminderDatetime').value;

    if (!taskTitle) {
        alert('Please enter a task title.');
        return;
    }

    if (!remindAt) {
        alert('Please select reminder date and time.');
        return;
    }

    try {
        await api.createReminder({
            task_title: taskTitle,
            remind_at: remindAt,
            channel: 'in_app',
            message: `Reminder for task: ${taskTitle}`
        });
        alert('Reminder created!');
        loadReminders();
        // Clear form
        document.getElementById('reminderTaskTitle').value = '';
        document.getElementById('reminderDatetime').value = '';
    } catch (error) {
        console.error('Reminder creation error:', error);
        alert('Failed to create reminder: ' + error.message);
    }
}

async function loadReminders() {
    const listEl = document.getElementById('reminderList');
    try {
        const data = await api.getReminders('pending');
        remindersCache = data.reminders || [];
        renderRemindersList(remindersCache);
        setReminderStatus('');
    } catch (error) {
        remindersCache = [];
        listEl.innerHTML = `<div class="item">${escapeHtml(error.message)}</div>`;
        setReminderStatus('Unable to load reminders.');
    }
}

function renderRemindersList(rows) {
    const listEl = document.getElementById('reminderList');
    if (!rows || rows.length === 0) {
        listEl.innerHTML = '<div class="item">No pending reminders.</div>';
        return;
    }

    listEl.innerHTML = rows.map(reminder => {
        const statusText = reminder.status ? ` (${escapeHtml(reminder.status)})` : '';
        const remindAt = reminder.remind_at ? escapeHtml(reminder.remind_at) : '';
        const title = reminder.task_title ? escapeHtml(reminder.task_title) : 'Untitled';
        return `
            <div class="item availability-row">
                <span>
                    <strong>${title}</strong><br>
                    ${remindAt}${statusText}
                </span>
                <span class="inline-actions">
                    <button type="button" title="Edit reminder" onclick="openReminderModal(${reminder.id})">&#9998;</button>
                    <button type="button" class="delete" title="Delete reminder" onclick="deleteReminderRow(${reminder.id})">&#128465;</button>
                </span>
            </div>
        `;
    }).join('');
}

function openReminderModal(id) {
    const reminder = remindersCache.find(item => item.id === id);
    if (!reminder) return;

    editingReminderId = id;
    document.getElementById('modalReminderTitle').value = reminder.task_title || '';
    document.getElementById('modalReminderDatetime').value = toLocalDateTimeInputValueFromString(reminder.remind_at);
    document.getElementById('modalReminderChannel').value = reminder.channel || 'in_app';
    document.getElementById('modalReminderStatus').value = reminder.status || 'pending';
    document.getElementById('modalReminderMessage').value = reminder.message || '';

    const modal = document.getElementById('reminderModal');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    setReminderStatus(`Editing reminder for ${reminder.task_title || 'task'}`);
}

function closeReminderModal() {
    editingReminderId = null;
    const modal = document.getElementById('reminderModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    setReminderStatus('');
}

async function saveReminderFromModal() {
    if (!editingReminderId) {
        return;
    }

    const remindAt = document.getElementById('modalReminderDatetime').value;
    if (!remindAt) {
        alert('Please select reminder date and time.');
        return;
    }

    const payload = {
        id: editingReminderId,
        remind_at: remindAt,
        channel: document.getElementById('modalReminderChannel').value,
        status: document.getElementById('modalReminderStatus').value,
        message: document.getElementById('modalReminderMessage').value.trim()
    };

    try {
        await api.updateReminder(payload);
        await loadReminders();
        closeReminderModal();
        setReminderStatus('Reminder updated.');
        Utils.showNotification('Reminder updated', 'success');
    } catch (error) {
        alert(error.message || 'Failed to update reminder.');
    }
}

async function deleteReminderRow(id) {
    if (!confirm('Delete this reminder?')) {
        return;
    }

    try {
        await api.deleteReminder(id);
        await loadReminders();
        setReminderStatus('Reminder deleted.');
        Utils.showNotification('Reminder deleted', 'success');
    } catch (error) {
        alert(error.message || 'Failed to delete reminder.');
    }
}

function setReminderStatus(message) {
    const el = document.getElementById('reminderStatus');
    if (el) {
        el.textContent = message || '';
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

function setPlanStatus(message) {
    const statusEl = document.getElementById('planStatus');
    if (!statusEl) return;
    statusEl.textContent = message || '';
}

function openScheduleModal() {
    editingScheduleTaskId = null;
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    document.getElementById('scheduleDate').value = document.getElementById('planDate').value || new Date().toISOString().slice(0, 10);
    document.getElementById('scheduleTime').value = '08:00';
    document.getElementById('scheduleTitle').value = '';
    document.getElementById('scheduleDuration').value = 30;
    document.getElementById('scheduleNotes').value = '';
    const saveBtn = document.getElementById('scheduleModalSaveButton');
    if (saveBtn) saveBtn.textContent = 'Schedule Task';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

async function openScheduleEditModal(taskId, startAt, duration, title) {
    editingScheduleTaskId = taskId;
    const [datePart, timePart] = (startAt || '').split(' ');
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    document.getElementById('scheduleDate').value = datePart || new Date().toISOString().slice(0, 10);
    document.getElementById('scheduleTime').value = (timePart || '08:00').slice(0,5);
    document.getElementById('scheduleTitle').value = title || '';
    document.getElementById('scheduleDuration').value = duration || 30;
    const task = allTasks.find(t => t.id === taskId);
    document.getElementById('scheduleNotes').value = task ? (task.description || '') : '';
    const saveBtn = document.getElementById('scheduleModalSaveButton');
    if (saveBtn) saveBtn.textContent = 'Update Task';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    setPlanStatus(`Editing ${title || 'task'}...`);
}

function closeScheduleModal() {
    editingScheduleTaskId = null;
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    const saveBtn = document.getElementById('scheduleModalSaveButton');
    if (saveBtn) saveBtn.textContent = 'Schedule Task';
}

async function saveScheduleFromModal() {
    const date = document.getElementById('scheduleDate').value;
    const time = document.getElementById('scheduleTime').value;
    const title = document.getElementById('scheduleTitle').value.trim();
    const duration = Number(document.getElementById('scheduleDuration').value);
    const notes = document.getElementById('scheduleNotes').value.trim();

    if (!date || !time || !title) {
        alert('Date, time, and title are required.');
        return;
    }

    const payload = {
        title,
        description: notes,
        deadline: `${date} ${time}:00`,
        estimated_duration: Number.isNaN(duration) || duration < 1 ? 30 : duration
    };

    try {
        if (editingScheduleTaskId) {
            await api.updateTask({ id: editingScheduleTaskId, ...payload });
            setPlanStatus('Task updated');
        } else {
            await api.createTask(payload);
            setPlanStatus('Task scheduled');
        }
        closeScheduleModal();
        await loadPlan();
        await loadTasksForDropdown();
        Utils.showNotification('Schedule synced successfully', 'success');
    } catch (error) {
        alert(error.message || 'Failed to save schedule.');
    }
}

async function deleteScheduledTask(taskId) {
    if (!confirm('Remove this task from the schedule?')) {
        return;
    }

    try {
        await api.deleteTask(taskId);
        await loadPlan();
        Utils.showNotification('Task removed', 'success');
    } catch (error) {
        alert(error.message || 'Failed to delete task.');
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

function toLocalDateTimeInputValueFromString(value) {
    if (!value) return '';
    const sanitized = value.replace(' ', 'T');
    const parsed = new Date(sanitized);
    if (Number.isNaN(parsed.getTime())) return '';
    return toLocalDateTimeInputValue(parsed);
}
