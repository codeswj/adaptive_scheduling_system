# Adaptive Scheduling System

A comprehensive web-based scheduling and task management system with advanced features including AI-powered insights, dispatch board management, and role-based access control.

## Features

### Core Functionality
- **Task Management**: Create, read, update, and delete tasks with priority levels
- **Schedule Planning**: Advanced scheduling with conflict detection
- **User Profiles**: Comprehensive user management with role-based access
- **Availability Tracking**: Track and manage user availability
- **Templates**: Pre-built task templates for quick scheduling

### Admin Features
- **Admin Dashboard**: Centralized overview of system status
- **Dispatch Board**: Real-time incident management and assignment
- **User Management**: Manage users, roles, and permissions
- **Leaderboard**: Performance metrics and user rankings
- **Incident Management**: Track and resolve incidents
- **Custom Reminders**: Configure and manage reminders

### Analytics & Insights
- **Recommendations Engine**: AI-powered scheduling recommendations
- **Financial Summary**: Cost and resource tracking
- **Reporting**: Export reports in multiple formats
- **Performance Metrics**: Track system usage and performance

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Authentication**: JWT (JSON Web Tokens)

## Project Structure

```
adaptive-scheduling-backend/
├── api/                          # API endpoints
│   ├── admin/                    # Admin endpoints
│   ├── auth/                     # Authentication
│   ├── availability/             # Availability management
│   ├── finance/                  # Financial data
│   ├── insights/                 # AI recommendations
│   ├── reminders/                # Reminder management
│   ├── reports/                  # Report generation
│   ├── schedule/                 # Scheduling
│   ├── tasks/                    # Task operations
│   ├── templates/                # Template management
│   └── user/                     # User operations
├── config/                       # Configuration files
├── migrations/                   # Database migrations
├── models/                       # Data models
├── middleware/                   # Express-style middleware
├── utils/                        # Utility functions
└── frontend/                     # Frontend assets
    ├── css/                      # Stylesheets
    └── js/                       # JavaScript files
```

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB 5.7+
- Apache/Nginx web server
- Composer (optional, for package management)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/adaptive-scheduling-system.git
   cd adaptive-scheduling-system
   ```

2. **Configure database**
   - Update `config/database.php` with your database credentials
   - Create a new MySQL database
   - Run migrations:
     ```bash
     mysql -u root -p your_database < backend/database.sql
     ```

3. **Set up environment**
   - Copy environment variables if needed
   - Configure JWT secret in `config/constants.php`

4. **Start the server**
   - Place project in your web root (e.g., `/xampp/htdocs/`)
   - Access via `http://localhost/adaptive-scheduling-backend/`

5. **Verify installation**
   - Ensure `config/database.php` has the correct database credentials
   - Confirm the database `adaptive_scheduling_db` exists
   - Use the backend API home page to confirm the server is running


## API Documentation

### Authentication
All API endpoints require JWT authentication except `/api/auth/login` and `/api/auth/register`.

**Login**
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

### Main Endpoints

#### Tasks
- `GET /api/tasks/read.php` - List all tasks
- `POST /api/tasks/create.php` - Create new task
- `PUT /api/tasks/update.php` - Update task
- `DELETE /api/tasks/delete.php` - Delete task

#### Schedule
- `POST /api/schedule/plan.php` - Generate schedule

#### Admin
- `GET /api/admin/overview.php` - System overview
- `GET /api/admin/dispatch.php` - Dispatch board
- `GET /api/admin/incidents.php` - Incident list

#### Reports
- `POST /api/reports/export.php` - Export reports

## Database Schema

Key tables:
- `users` - User accounts and profiles
- `tasks` - Task definitions and metadata
- `schedules` - Generated schedules
- `availability` - User availability windows
- `dispatch_incidents` - Incident tracking
- `reminders` - Reminder configurations
- `user_roles` - Role-based access control

## Usage

### Web Interface
- Access the dashboard at `http://localhost/adaptive-scheduling-backend/`
- Login with your credentials
- Navigate using the menu system

### API Usage
Include JWT token in request headers:
```
Authorization: Bearer your_jwt_token_here
```

## Development

### Adding New Endpoints
1. Create a new PHP file in the appropriate `api/` subdirectory
2. Use the response utility for consistent formatting
3. Implement JWT validation using the auth middleware
4. Test using your API client (Postman, curl, etc.)

### Database Migrations
Place migration files in `migrations/` directory following the naming convention:
`YYYY_MM_DD_description.sql`

## Security Considerations

- JWT tokens expire after a configured duration
- Passwords are hashed using bcrypt
- SQL injection prevention through prepared statements
- CORS headers configured appropriately
- Input validation on all endpoints

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see LICENSE file for details.

## Support

For issues and questions:
- Check existing [GitHub Issues](https://github.com/yourusername/adaptive-scheduling-system/issues)
- Create a new issue with detailed description
- Include error messages and steps to reproduce

## Changelog

### v1.0.0 (Current)
- Initial release
- Core task management
- Schedule planning
- Admin dashboard
- User authentication
- Dispatch board
- Incident management
