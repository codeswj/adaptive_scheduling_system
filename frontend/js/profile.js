/* ========================================
   PROFILE PAGE
   ======================================== */

if (!Utils.requireAuth()) {
    throw new Error('Authentication required');
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadProfile();
});

async function loadProfile() {
    const statusEl = document.getElementById('profileStatus');
    statusEl.textContent = '';

    try {
        const data = await api.getUserProfile();
        const user = data.user || {};
        const stats = data.statistics || {};

        setInputValue('full_name', user.full_name || '');
        setInputValue('phone_number', user.phone_number || '');
        setInputValue('role', user.role || 'user');
        setInputValue('work_type', user.work_type || 'other');
        setInputValue('location', user.location || '');
        setInputValue('connectivity_profile', user.connectivity_profile || 'unstable');

        document.getElementById('statTotalTasks').textContent = Number(stats.total_tasks || 0);
        document.getElementById('statCompletedTasks').textContent = Number(stats.completed_tasks || 0);
        document.getElementById('statPendingTasks').textContent = Number(stats.pending_tasks || 0);
        document.getElementById('statTotalEarnings').textContent = Utils.formatCurrency(stats.total_earnings || 0);
        document.getElementById('statCreatedAt').textContent = user.created_at ? Utils.formatDate(user.created_at) : '-';
        document.getElementById('statLastLogin').textContent = user.last_login ? Utils.formatDate(user.last_login) : '-';
    } catch (error) {
        console.error('Failed to load profile:', error);
        statusEl.className = 'status error';
        statusEl.textContent = error.message || 'Failed to load profile.';
    }
}

async function saveProfile(event) {
    event.preventDefault();
    const statusEl = document.getElementById('profileStatus');
    statusEl.className = 'status';
    statusEl.textContent = 'Saving...';

    const payload = {
        full_name: document.getElementById('full_name').value.trim(),
        work_type: document.getElementById('work_type').value,
        location: document.getElementById('location').value.trim(),
        connectivity_profile: document.getElementById('connectivity_profile').value,
        device_type: navigator.userAgent
    };

    if (!payload.full_name || !payload.location) {
        statusEl.className = 'status error';
        statusEl.textContent = 'Full name and location are required.';
        return;
    }

    try {
        const data = await api.updateUserProfile(payload);
        if (data && data.user) {
            Utils.setUserData(data.user);
        }
        statusEl.className = 'status success';
        statusEl.textContent = 'Profile updated successfully.';
        await loadProfile();
    } catch (error) {
        console.error('Failed to save profile:', error);
        statusEl.className = 'status error';
        statusEl.textContent = error.message || 'Failed to update profile.';
    }
}

function setInputValue(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.value = value;
    }
}
