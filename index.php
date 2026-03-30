<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - Smart Scheduling for Informal Workers</title>
    <link rel="stylesheet" href="frontend/css/styles.css">
    <link rel="stylesheet" href="frontend/css/auth.css">
</head>
<body>
    <!-- Landing Page -->
    <div id="landingPage" class="landing-page">
        <div class="hero-section">
            <div class="hero-bg"></div>
            <nav class="nav-bar">
                <div class="logo">
                    <span class="logo-icon">⚡</span>
                    <span class="logo-text">TaskFlow</span>
                </div>
                <button class="nav-cta" onclick="showAuth('login')">Get Started</button>
            </nav>

            <div class="hero-content">
                <div class="hero-left">
                    <h1 class="hero-title">
                        <span class="title-line">Organize Your</span>
                        <span class="title-line accent">Hustle.</span>
                        <span class="title-line">Maximize Your</span>
                        <span class="title-line accent">Income.</span>
                    </h1>
                    <p class="hero-subtitle">
                        Smart micro-scheduling built for boda boda riders, vendors, artisans, 
                        and hustlers working in low-connectivity areas across Kenya.
                    </p>
                    <div class="hero-cta-group">
                        <button class="btn-primary" onclick="showAuth('register')">
                            <span>Start Free</span>
                            <span class="btn-arrow">→</span>
                        </button>
                        <button class="btn-secondary" onclick="showAuth('login')">
                            Already a member?
                        </button>
                    </div>
                </div>
                <div class="hero-right">
                    <div class="feature-cards">
                        <div class="feature-card card-1">
                            <div class="card-icon">📱</div>
                            <h3>Works Offline</h3>
                            <p>No internet? No problem. Sync when you're back online.</p>
                        </div>
                        <div class="feature-card card-2">
                            <div class="card-icon">🎯</div>
                            <h3>Smart Priority</h3>
                            <p>AI ranks your tasks by urgency, distance, and earnings.</p>
                        </div>
                        <div class="feature-card card-3">
                            <div class="card-icon">💰</div>
                            <h3>Track Earnings</h3>
                            <p>See your daily income and completion rates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <div class="section-label">Why TaskFlow?</div>
            <h2 class="section-title">Built for Real Hustlers</h2>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-number">01</div>
                    <h3>Offline First</h3>
                    <p>Create and manage tasks without internet. Everything syncs automatically when you're connected.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-number">02</div>
                    <h3>Smart Scheduling</h3>
                    <p>Tasks are auto-prioritized based on deadline, location, urgency, and payment amount.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-number">03</div>
                    <h3>Low Data Usage</h3>
                    <p>Designed to work on 2G/3G networks. Minimal data consumption, maximum efficiency.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-number">04</div>
                    <h3>Simple Interface</h3>
                    <p>Large buttons, icons, and clear navigation. Works for everyone, no tech skills needed.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-left">
            <span class="footer-text">Adaptive_Scheduling_System</span>
        </div>
        <div class="footer-right">
            <span class="footer-text">Silvia Gatiba</span>
        </div>
    </footer>

    <!-- Auth Modal -->
    <div id="authModal" class="auth-modal">
        <div class="auth-backdrop" onclick="closeAuth()"></div>
        <div class="auth-container">
            <button class="close-auth" onclick="closeAuth()">✕</button>
            
            <!-- Login Form -->
            <div id="loginForm" class="auth-form">
                <div class="form-header">
                    <h2>Welcome Back</h2>
                    <p>Continue organizing your hustle</p>
                </div>

                <form onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="login_phone">Phone Number</label>
                        <input type="tel" id="login_phone" placeholder="0712345678" required>
                    </div>

                    <div class="form-group">
                        <label for="login_password">Password</label>
                        <input type="password" id="login_password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span id="loginBtnText">Login</span>
                        <div class="btn-loader" style="display: none;"></div>
                    </button>

                    <div class="form-switch">
                        Don't have an account? 
                        <button type="button" onclick="switchAuth('register')">Register here</button>
                    </div>
                </form>

                <div id="loginMessage" class="message"></div>
            </div>

            <!-- Register Form -->
            <div id="registerForm" class="auth-form" style="display: none;">
                <div class="form-header">
                    <h2>Start Your Journey</h2>
                    <p>Join thousands of successful hustlers</p>
                </div>

                <form onsubmit="handleRegister(event)">
                    <div class="form-group">
                        <label for="reg_name">Full Name</label>
                        <input type="text" id="reg_name" placeholder="John Doe" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_phone">Phone Number</label>
                        <input type="tel" id="reg_phone" placeholder="0712345678" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <input type="password" id="reg_password" placeholder="At least 6 characters" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="reg_work_type">What do you do?</label>
                        <select id="reg_work_type" required>
                            <option value="">Select work type</option>
                            <option value="boda_boda">Boda Boda Rider</option>
                            <option value="market_vendor">Market Vendor</option>
                            <option value="artisan">Artisan/Craftsperson</option>
                            <option value="domestic_worker">Domestic Worker</option>
                            <option value="plumber">Plumber</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reg_location">Location</label>
                        <input type="text" id="reg_location" placeholder="Nairobi" required>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span id="registerBtnText">Create Account</span>
                        <div class="btn-loader" style="display: none;"></div>
                    </button>

                    <div class="form-switch">
                        Already have an account? 
                        <button type="button" onclick="switchAuth('login')">Login here</button>
                    </div>
                </form>

                <div id="registerMessage" class="message"></div>
            </div>
        </div>
    </div>

    <script src="frontend/js/config.js"></script>
    <script src="frontend/js/auth.js"></script>
    <script src="frontend/js/main.js"></script>
</body>
</html>
