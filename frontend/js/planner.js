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
    }
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
        console.error('Plan load error:', error);
        summaryEl.textContent = 'Failed to generate plan.';
        
        // Provide more helpful error messages
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

let allTasks = [];

// Load tasks for dropdown
async function loadTasksForDropdown() {
    try {
        const data = await api.getTasks();
        allTasks = data.tasks || [];
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
        listEl.innerHTML = (data.reminders || []).map(r => `
            <div class="item">
                <strong>${escapeHtml(r.task_title)}</strong><br>
                ${r.remind_at} ${r.task_status ? `(${r.task_status})` : ''}
            </div>
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
