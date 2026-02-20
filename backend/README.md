# Adaptive Micro-Scheduling System - Backend

## Folder Structure

```text
adaptive-scheduling-backend/
|
|-- config/
|   |-- database.php          # Database configuration
|   `-- constants.php         # App constants
|
|-- api/
|   |-- auth/
|   |   |-- register.php      # User registration endpoint
|   |   |-- login.php         # User login endpoint
|   |   `-- logout.php        # User logout endpoint
|   |
|   |-- tasks/
|   |   |-- create.php        # Create task
|   |   |-- read.php          # Get tasks
|   |   |-- update.php        # Update task
|   |   |-- delete.php        # Delete task
|   |   `-- sync.php          # Batch sync tasks
|   |
|   `-- user/
|       |-- profile.php       # Get user profile
|       `-- update.php        # Update user profile
|
|-- models/
|   |-- User.php              # User model
|   `-- Task.php              # Task model
|
|-- utils/
|   |-- jwt.php               # JWT helper functions
|   |-- response.php          # Response helper functions
|   `-- validation.php        # Input validation functions
|
|-- middleware/
|   `-- auth.php              # Authentication middleware
|
`-- index.php                 # API entry point
```

## Setup Instructions

1. Copy this entire folder to your XAMPP `htdocs` directory.
2. Create a database named `adaptive_scheduling_db` in phpMyAdmin.
3. Import the SQL file provided.
4. Update database credentials in `config/database.php`.
5. Access the API at `http://localhost/adaptive-scheduling-backend/`.

## API Endpoints

### Authentication

- POST `/api/auth/register.php` - Register new user
- POST `/api/auth/login.php` - Login user
- POST `/api/auth/logout.php` - Logout user

#### Role-Based Authentication

- Supported roles: `user`, `admin`
- `register.php` accepts optional `role` field (defaults to `user`)
- `login.php` returns `user.role` and includes `role` in JWT payload
- `profile.php` returns `user.role`
- Frontend redirect behavior:
  - `admin` -> `frontend/admin-dashboard.html`
  - `user` -> `frontend/dashboard.html`

### Tasks

- POST `/api/tasks/create.php` - Create new task
- GET `/api/tasks/read.php` - Get all user tasks
- PUT `/api/tasks/update.php` - Update task
- DELETE `/api/tasks/delete.php` - Delete task
- POST `/api/tasks/sync.php` - Batch sync tasks from offline queue

### User

- GET `/api/user/profile.php` - Get user profile
- PUT `/api/user/update.php` - Update user profile
