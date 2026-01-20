
# Faculty Loading & Scheduling System

This project is a Faculty Loading and Scheduling System built with Laravel. It features a Management Tab for admin users, including:

- Faculty Loading (add, edit, view, and statistics)
- DSS Recommendations (decision support, accept/reject)
- Print/Export (PDF, Excel, CSV, Print, Archive, Email)

## Features

- Role-based authentication (Admin/Teacher)
- Management dashboard with 3 main pages
- Secure, RESTful API endpoints
- Database migrations and seeders included
- Visual documentation and setup guides

## Quick Start

1. **Clone or download this repository**
2. **Install dependencies:**
	```sh
	composer install
	npm install && npm run build
	```
3. **Configure your `.env` file** (see `.env.example`)
4. **Run migrations and seeders:**
	```sh
	php artisan migrate
	php artisan db:seed --class=FacultyLoadSeeder
	```
5. **Start the server:**
	```sh
	php artisan serve
	```
6. **Login as admin and access the Management tab**

## Database Setup

See [DB_SETUP.md](DB_SETUP.md) for full instructions. You can also use [SQL_COMMANDS.md](SQL_COMMANDS.md) for direct import via phpMyAdmin.

## Documentation

- [00_START_HERE.md](00_START_HERE.md): Main system overview
- [QUICK_START.md](QUICK_START.md): Fast setup guide
- [MANAGEMENT_TAB_SETUP.md](MANAGEMENT_TAB_SETUP.md): Detailed feature guide
- [SQL_COMMANDS.md](SQL_COMMANDS.md): SQL statements for manual DB setup
- [SETUP_CHECKLIST.md](SETUP_CHECKLIST.md): Step-by-step verification
- [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md): Technical details
- [VISUAL_SUMMARY.md](VISUAL_SUMMARY.md): Diagrams & layouts

## GitHub Repository

See [GITHUB_REPO_LINK.md](GITHUB_REPO_LINK.md) for instructions to create and push to your own GitHub repository.

---

**For any issues, see the documentation files or contact the project maintainer.**
