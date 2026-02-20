/* ========================================
   AUTHENTICATION
   ======================================== */

async function parseApiResponse(response) {
    const raw = await response.text();

    try {
        return JSON.parse(raw);
    } catch (error) {
        throw new Error(`Server returned invalid response (HTTP ${response.status}).`);
    }
}

function isFrontendDirectory() {
    return window.location.pathname.includes('/frontend/');
}

function getHomePath() {
    return isFrontendDirectory() ? '../index.php' : 'index.php';
}

function getDashboardPath() {
    return isFrontendDirectory() ? 'dashboard.html' : 'frontend/dashboard.html';
}

function getAdminDashboardPath() {
    return isFrontendDirectory() ? 'admin-dashboard.html' : 'frontend/admin-dashboard.html';
}

function getDashboardPathByRole(role) {
    return role === 'admin' ? getAdminDashboardPath() : getDashboardPath();
}

// Show auth modal
function showAuth(type) {
    const modal = document.getElementById('authModal');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    if (type === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
}

// Close auth modal
function closeAuth() {
    const modal = document.getElementById('authModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Switch between login and register
function switchAuth(type) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (type === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
    }
}

// Handle Login
async function handleLogin(event) {
    event.preventDefault();
    
    const phone = document.getElementById('login_phone').value;
    const password = document.getElementById('login_password').value;
    const btnText = document.getElementById('loginBtnText');
    const btn = event.target.querySelector('button[type="submit"]');
    const loader = btn.querySelector('.btn-loader');
    const messageEl = document.getElementById('loginMessage');
    
    // Show loading state
    btnText.style.display = 'none';
    loader.style.display = 'block';
    btn.disabled = true;
    
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}${CONFIG.API_ENDPOINTS.LOGIN}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone_number: phone, password: password })
        });
        
        const data = await parseApiResponse(response);
        
        if (data.success) {
            // Save token and user data
            Utils.setToken(data.data.token);
            Utils.setUserData(data.data.user);
            
            // Show success message
            messageEl.textContent = 'Login successful! Redirecting...';
            messageEl.className = 'message success show';
            
            // Redirect to dashboard
            setTimeout(() => {
                window.location.href = getDashboardPathByRole(data.data.user.role);
            }, 1000);
        } else {
            // Show error
            messageEl.textContent = data.message || 'Login failed. Please try again.';
            messageEl.className = 'message error show';
            
            // Reset button
            btnText.style.display = 'inline';
            loader.style.display = 'none';
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Login error:', error);
        messageEl.textContent = error.message || 'Request failed. Please try again.';
        messageEl.className = 'message error show';
        
        // Reset button
        btnText.style.display = 'inline';
        loader.style.display = 'none';
        btn.disabled = false;
    }
}

// Handle Registration
async function handleRegister(event) {
    event.preventDefault();
    
    const name = document.getElementById('reg_name').value;
    const phone = document.getElementById('reg_phone').value;
    const password = document.getElementById('reg_password').value;
    const workType = document.getElementById('reg_work_type').value;
    const role = document.getElementById('reg_role').value;
    const location = document.getElementById('reg_location').value;
    const btnText = document.getElementById('registerBtnText');
    const btn = event.target.querySelector('button[type="submit"]');
    const loader = btn.querySelector('.btn-loader');
    const messageEl = document.getElementById('registerMessage');
    
    // Validate
    if (!workType) {
        messageEl.textContent = 'Please select your work type';
        messageEl.className = 'message error show';
        return;
    }
    
    // Show loading state
    btnText.style.display = 'none';
    loader.style.display = 'block';
    btn.disabled = true;
    
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}${CONFIG.API_ENDPOINTS.REGISTER}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                full_name: name,
                phone_number: phone,
                password: password,
                role: role || 'user',
                work_type: workType,
                location: location,
                device_type: navigator.userAgent,
                connectivity_profile: navigator.connection ? navigator.connection.effectiveType : 'unstable'
            })
        });
        
        const data = await parseApiResponse(response);
        
        if (data.success) {
            // Save token and user data
            Utils.setToken(data.data.token);
            Utils.setUserData(data.data.user);
            
            // Show success message
            messageEl.textContent = 'Registration successful! Redirecting...';
            messageEl.className = 'message success show';
            
            // Redirect to dashboard
            setTimeout(() => {
                window.location.href = getDashboardPathByRole(data.data.user.role);
            }, 1000);
        } else {
            // Show error
            messageEl.textContent = data.message || 'Registration failed. Please try again.';
            messageEl.className = 'message error show';
            
            // Reset button
            btnText.style.display = 'inline';
            loader.style.display = 'none';
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Registration error:', error);
        messageEl.textContent = error.message || 'Request failed. Please try again.';
        messageEl.className = 'message error show';
        
        // Reset button
        btnText.style.display = 'inline';
        loader.style.display = 'none';
        btn.disabled = false;
    }
}

// Handle Logout
async function handleLogout() {
    if (!confirm('Are you sure you want to logout?')) {
        return;
    }
    
    const token = Utils.getToken();
    
    try {
        // Call logout endpoint (optional, as JWT is stateless)
        await fetch(`${CONFIG.API_BASE_URL}${CONFIG.API_ENDPOINTS.LOGOUT}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    // Clear local data
    Utils.removeToken();
    
    // Redirect to home
    window.location.href = getHomePath();
}

// Check if user is already logged in on index page
if (window.location.pathname.includes('index.html') || window.location.pathname.includes('index.php') || window.location.pathname === '/') {
    if (Utils.isAuthenticated()) {
        const userData = Utils.getUserData();
        const role = userData && userData.role ? userData.role : 'user';
        window.location.href = getDashboardPathByRole(role);
    }
}

