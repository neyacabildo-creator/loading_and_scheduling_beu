# Quick Start Guide - Management Tab Setup

## 🚀 Quick Setup (Choose One Method)

### Method 1: Using Laravel Migrations (RECOMMENDED)

```bash
# From your project root directory
php artisan migrate

# Optional: Load sample data
php artisan db:seed --class=FacultyLoadSeeder
```

### Method 2: Direct SQL in phpMyAdmin

1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy and paste the SQL below
5. Execute

```sql
-- Faculty Loads Table
CREATE TABLE IF NOT EXISTS faculty_loads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    faculty_id BIGINT UNSIGNED NOT NULL,
    department VARCHAR(255) NOT NULL,
    classes_assigned INT NOT NULL DEFAULT 0,
    load_hours DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'part-time', 'overloaded') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_faculty_loads_user FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- DSS Recommendations Table
CREATE TABLE IF NOT EXISTS dss_recommendations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(255) NOT NULL,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    issue TEXT NOT NULL,
    solution TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'implemented') DEFAULT 'pending',
    related_faculty_id BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dss_recommendations_user FOREIGN KEY (related_faculty_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Export Logs Table
CREATE TABLE IF NOT EXISTS export_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    format VARCHAR(50) NOT NULL,
    data_selected TEXT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_export_logs_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Class Schedules Table
CREATE TABLE IF NOT EXISTS class_schedules (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    faculty_id BIGINT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    grade_section VARCHAR(100) NOT NULL,
    room_id BIGINT UNSIGNED,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    student_count INT DEFAULT 0,
    status ENUM('pending', 'active', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_class_schedules_faculty FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rooms Table
CREATE TABLE IF NOT EXISTS rooms (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    room_number VARCHAR(50) UNIQUE NOT NULL,
    building VARCHAR(100),
    capacity INT DEFAULT 30,
    has_laboratory BOOLEAN DEFAULT FALSE,
    has_projector BOOLEAN DEFAULT TRUE,
    has_ac BOOLEAN DEFAULT TRUE,
    status ENUM('available', 'in-use', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Sample Data
INSERT INTO faculty_loads (faculty_id, department, classes_assigned, load_hours, status, created_at, updated_at) VALUES
(1, 'High School', 5, 6.5, 'overloaded', NOW(), NOW()),
(2, 'Senior High', 4, 4.2, 'active', NOW(), NOW()),
(3, 'High School', 3, 3.8, 'active', NOW(), NOW());

INSERT INTO dss_recommendations (type, priority, issue, solution, status, created_at, updated_at) VALUES
('teacher_overload', 'high', 'Maria Santos has 6.5 hours/week load (exceeds 6.0 max)', 'Reassign 1 class to another available teacher', 'pending', NOW(), NOW()),
('class_balance', 'medium', 'Grade 10A has 35 students while 10B has 28', 'Transfer 3-5 students from 10A to 10B', 'pending', NOW(), NOW()),
('room_utilization', 'medium', 'Lab Room 5 is only 40% utilized', 'Consolidate lab classes', 'pending', NOW(), NOW());

INSERT INTO rooms (room_number, building, capacity, has_laboratory, status, created_at, updated_at) VALUES
('A101', 'Main Building', 35, false, 'available', NOW(), NOW()),
('Lab101', 'Science Building', 25, true, 'in-use', NOW(), NOW());
```

## ✅ Verify Setup

After running migrations or SQL, check that tables were created:

```bash
# In your terminal
php artisan tinker

# Then run:
>>> DB::table('faculty_loads')->count()
>>> DB::table('dss_recommendations')->count()
>>> DB::table('export_logs')->count()
>>> exit()
```

## 📍 Access Management Features

Once setup is complete, access the features via:

- **Admin Dashboard**: http://yoursite/admin/dashboard
- **Faculty Loading**: http://yoursite/admin/faculty-loading
- **DSS Recommendations**: http://yoursite/admin/dss-recommendations
- **Print/Export**: http://yoursite/admin/print-export
- **Class Schedule**: http://yoursite/admin/class-schedule

## 🔑 Important Notes

1. **Admin Middleware Required**: Only logged-in admin users can access management routes
2. **Sample Data**: The 3 sample records shown in tables are hardcoded in the views
3. **Next Steps**: To use real database data, update the blade files to use Eloquent queries
4. **Foreign Keys**: All tables properly reference users table - ensure user exists before adding faculty load

## 📊 Current Status

✅ **Completed:**
- Routes configured in web.php
- Models created (FacultyLoad, DSSRecommendation, ExportLog, ClassSchedule, Room)
- Blade views created with UI/UX
- Migration files ready
- Seeder file ready
- Sidebar navigation updated with functional links

⏳ **Next (Optional but Recommended):**
- Create Controllers for business logic
- Update blade files to use database queries instead of sample data
- Implement PDF/Excel export functionality
- Add email capabilities for exports

## 💾 Database File Locations

- Migration: `database/migrations/2026_01_15_000001_create_faculty_dss_and_export_tables.php`
- Seeder: `database/seeders/FacultyLoadSeeder.php`
- Models: `app/Models/` (FacultyLoad.php, DSSRecommendation.php, etc.)
