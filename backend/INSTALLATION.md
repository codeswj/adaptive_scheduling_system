# Adaptive Micro-Scheduling System - Backend Setup Guide

## Prerequisites
- XAMPP installed (includes Apache, MySQL, PHP)
- VSCode or any text editor
- Web browser
- Postman or similar API testing tool (optional)

## Step-by-Step Installation

### 1. Setup XAMPP

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start **Apache** service
   - Start **MySQL** service
   - Both should show green "Running" status

### 2. Create Database

1. **Open phpMyAdmin**
   - Open your browser
   - Go to: `http://localhost/phpmyadmin`
   
2. **Create Database**
   - Click "New" in the left sidebar
   - Database name: `adaptive_scheduling_db`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

3. **Import Database Schema**
   - Select the newly created `adaptive_scheduling_db`
   - Click the "Import" tab
   - Click "Choose File"
   - Navigate to your project folder and select `database.sql`
   - Click "Go" at the bottom
   - Wait for success message

4. **If You Already Had an Older DB**
   - Run role migration SQL:
   ```sql
   ALTER TABLE users ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER password;
   CREATE INDEX idx_role ON users(role);
   ```
   - Assign at least one admin account:
   ```sql
   UPDATE users SET role='admin' WHERE phone_number='+254700000000';
   ```

### 3. Install Project Files

1. **Copy Project to XAMPP**
   ```
   Copy the entire "adaptive-scheduling-backend" folder to:
   C:\xampp\htdocs\
   
   (On Mac: /Applications/XAMPP/htdocs/)
   (On Linux: /opt/lampp/htdocs/)
   ```

2. **Verify Folder Structure**
   ```
   htdocs/
   └── adaptive-scheduling-backend/
       ├── api/
       ├── config/
       ├── models/
       ├── utils/
       ├── middleware/
       ├── database.sql
       └── index.php
   ```

### 4. Configure Database Connection

1. **Open VSCode**
   - Open the `adaptive-scheduling-backend` folder in VSCode

2. **Edit Database Config** (if needed)
   - Open: `config/database.php`
   - Default settings (usually don't need changes):
     ```php
     private $host = "localhost";
     private $db_name = "adaptive_scheduling_db";
     private $username = "root";
     private $password = "";  // Empty for default XAMPP
     ```

3. **Update JWT Secret** (Important for production!)
   - Open: `config/constants.php`
   - Line 7: Change JWT_SECRET_KEY to something secure
   ```php
   define('JWT_SECRET_KEY', 'your-unique-secret-key-here-2024');
   ```

### 5. Test Installation

1. **Verify Database Connection**
   - Make sure `config/database.php` contains the correct credentials
   - Confirm the `adaptive_scheduling_db` database exists

2. **Verify API Endpoint**
   - Go to: `http://localhost/adaptive-scheduling-backend/`
   - You should see the backend API response

### 6. Test API with Sample Requests

#### Register a New User
```
POST http://localhost/adaptive-scheduling-backend/api/auth/register.php

Headers:
Content-Type: application/json

Body (JSON):
{
  "full_name": "John Doe",
  "phone_number": "0712345678",
  "password": "password123",
  "role": "user",
  "work_type": "boda_boda",
  "location": "Nairobi"
}
```

Expected Response:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "full_name": "John Doe",
      "phone_number": "+254712345678",
      "role": "user",
      ...
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

#### Login
```
POST http://localhost/adaptive-scheduling-backend/api/auth/login.php

Headers:
Content-Type: application/json

Body (JSON):
{
  "phone_number": "0712345678",
  "password": "password123"
}
```

#### Create a Task (requires token from login/register)
```
POST http://localhost/adaptive-scheduling-backend/api/tasks/create.php

Headers:
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE

Body (JSON):
{
  "title": "Deliver package to CBD",
  "description": "Urgent delivery for client",
  "task_type": "delivery",
  "urgency": "high",
  "deadline": "2024-12-31 15:00:00",
  "location": "Nairobi CBD",
  "distance": 5.5,
  "payment_amount": 500,
  "client_name": "Jane Smith",
  "client_phone": "0723456789"
}
```

#### Get All Tasks
```
GET http://localhost/adaptive-scheduling-backend/api/tasks/read.php

Headers:
Authorization: Bearer YOUR_TOKEN_HERE
```

#### Get User Profile
```
GET http://localhost/adaptive-scheduling-backend/api/user/profile.php

Headers:
Authorization: Bearer YOUR_TOKEN_HERE
```

## Troubleshooting

### Database Connection Failed
- Ensure MySQL service is running in XAMPP
- Check database name is exactly: `adaptive_scheduling_db`
- Verify username/password in `config/database.php`

### Tables Missing
- Import `database.sql` file again through phpMyAdmin
- Make sure to select the correct database before importing

### API Returns 404
- Check Apache is running in XAMPP
- Verify project is in `htdocs` folder
- URL should match your folder name

### CORS Errors (when testing from frontend)
- Headers are already set in all API files
- If issues persist, check browser console for specific error

### Token Issues
- Tokens expire after 7 days (configurable in constants.php)
- Make sure to include "Bearer " prefix in Authorization header
- Token format: `Authorization: Bearer your_token_here`

## File Structure Explanation

```
adaptive-scheduling-backend/
│
├── config/
│   ├── database.php          # Database connection settings
│   └── constants.php          # App constants and configuration
│
├── api/
│   ├── auth/
│   │   ├── register.php       # User registration
│   │   ├── login.php          # User login
│   │   └── logout.php         # User logout
│   │
│   ├── tasks/
│   │   ├── create.php         # Create new task
│   │   ├── read.php           # Get tasks
│   │   ├── update.php         # Update task
│   │   ├── delete.php         # Delete task
│   │   └── sync.php           # Batch sync offline tasks
│   │
│   └── user/
│       ├── profile.php        # Get user profile
│       └── update.php         # Update profile
│
├── models/
│   ├── User.php               # User database operations
│   └── Task.php               # Task database operations
│
├── utils/
│   ├── jwt.php                # JWT token handling
│   ├── response.php           # Standardized API responses
│   └── validation.php         # Input validation
│
├── middleware/
│   └── auth.php               # Authentication middleware
│
├── database.sql               # Database schema
├── index.php                  # API entry point
└── README.md                  # Documentation
```

## Security Notes (For Production)

1. **Change JWT Secret Key**
   - Never use default key in production
   - Use long, random string

2. **Disable Error Display**
   - In `config/constants.php`, set:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

3. **Enable HTTPS**
   - Use SSL certificate
   - Force HTTPS redirects

4. **Database Security**
   - Use strong MySQL password
   - Create dedicated database user (not root)
   - Limit user permissions

5. **Update CORS Settings**
   - Restrict allowed origins
   - Don't use wildcard (*) in production

6. **Restrict Admin Role Assignment**
   - For production, do not expose public registration with `"role": "admin"`
   - Assign admin role only through DB migration/admin tooling

## Next Steps

1. ✅ Backend is now ready
2. 📱 Start building your frontend
3. 🧪 Test all endpoints
4. 🚀 Deploy when ready

## Support

If you encounter any issues:
1. Verify your database and API endpoint
2. Verify XAMPP services are running
3. Check error logs in XAMPP control panel
4. Review browser console for errors

## API Documentation

Full endpoint documentation is available in the README.md file.
