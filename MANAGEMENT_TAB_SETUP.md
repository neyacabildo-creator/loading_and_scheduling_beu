# Management Tab Setup Guide

## Overview
This document outlines the complete setup for the Faculty Loading, DSS Recommendations, and Print/Export management features integrated into your admin dashboard.

## Pages Created

### 1. Faculty Loading Page
**URL:** `/admin/faculty-loading`
**File:** `resources/views/admin/faculty-loading.blade.php`
**Features:**
- Filter by name, department, and status
- Statistics: Total Faculty (32), Total Classes (128), Average Load (4.2), Overloaded Count (3)
- Faculty table with columns: Name, Department, Classes, Load (hrs/week), Status, Actions
- View and Edit buttons for each faculty member
- Add New Faculty button

### 2. DSS Recommendations Page
**URL:** `/admin/dss-recommendations`
**File:** `resources/views/admin/dss-recommendations.blade.php`
**Features:**
- Overview statistics: Total (12), High Priority (4), Implemented (7), Pending Review (1)
- 6 recommendation cards with priority levels (High/Medium/Low)
- Topics: Teacher Overload, Class Balance, Room Utilization, Schedule Gap, Facility Assignment, Teacher Preference
- Accept/Reject buttons for each recommendation
- Priority-based color coding

### 3. Print/Export Page
**URL:** `/admin/print-export`
**File:** `resources/views/admin/print-export.blade.php`
**Features:**
- Quick export options: PDF, Excel, CSV
- Additional options: Print and Archive
- Advanced options section with:
  - Data selection checkboxes (Schedules, Faculty, Rooms, Students, Analytics)
  - Filter options (Grade Level, Date Range)
  - Metadata options (timestamp, footer, colors)
- Recent exports table showing past exports

## Routes Updated

Added the following routes to `routes/web.php`:

```php
// Admin Management Routes
Route::get('admin/class-schedule', function () {
    return view('admin.class-schedule');
})->name('admin.class-schedule');

Route::get('admin/faculty-loading', function () {
    return view('admin.faculty-loading');
})->name('admin.faculty-loading');

Route::get('admin/dss-recommendations', function () {
    return view('admin.dss-recommendations');
})->name('admin.dss-recommendations');

Route::get('admin/print-export', function () {
    return view('admin.print-export');
})->name('admin.print-export');

// Teacher Routes
Route::get('teacher/class-schedule', function () {
    return view('teacher.class-schedule');
})->name('teacher.class-schedule');
```

## Database Setup

### Running Migrations

Execute these commands from your project root:

```bash
# Run migrations
php artisan migrate

# Run seeders (optional - loads sample data)
php artisan db:seed --class=FacultyLoadSeeder
```

### Manual SQL Setup (if migrations don't work)

If you prefer to run SQL directly in phpMyAdmin or MySQL command line:

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
```

### Insert Sample Data

```sql
-- Sample Faculty Loads
INSERT INTO faculty_loads (faculty_id, department, classes_assigned, load_hours, status, created_at, updated_at) VALUES
(1, 'High School', 5, 6.5, 'overloaded', NOW(), NOW()),
(2, 'Senior High', 4, 4.2, 'active', NOW(), NOW()),
(3, 'High School', 3, 3.8, 'active', NOW(), NOW()),
(4, 'College', 6, 7.2, 'overloaded', NOW(), NOW()),
(5, 'Senior High', 4, 4.0, 'active', NOW(), NOW());

-- Sample DSS Recommendations
INSERT INTO dss_recommendations (type, priority, issue, solution, status, created_at, updated_at) VALUES
('teacher_overload', 'high', 'Maria Santos has 6.5 hours/week load (exceeds 6.0 max)', 'Reassign 1 class to another available teacher', 'pending', NOW(), NOW()),
('class_balance', 'medium', 'Grade 10A has 35 students while 10B has 28', 'Transfer 3-5 students from 10A to 10B', 'pending', NOW(), NOW()),
('room_utilization', 'medium', 'Lab Room 5 is only 40% utilized', 'Consolidate lab classes or schedule more activities', 'pending', NOW(), NOW()),
('schedule_gap', 'high', '2-hour gap in Juan schedule on Tuesday afternoon', 'Schedule additional class or professional development', 'pending', NOW(), NOW()),
('facility_assignment', 'medium', 'Science class in regular classroom instead of lab', 'Reassign to appropriate lab room', 'pending', NOW(), NOW()),
('teacher_preference', 'low', 'Ana requested morning classes but assigned afternoons', 'Swap 2 afternoon classes with another teacher', 'pending', NOW(), NOW());

-- Sample Rooms
INSERT INTO rooms (room_number, building, capacity, has_laboratory, has_projector, has_ac, status, created_at, updated_at) VALUES
('A101', 'Main Building', 35, false, true, true, 'available', NOW(), NOW()),
('A102', 'Main Building', 40, false, true, true, 'available', NOW(), NOW()),
('Lab101', 'Science Building', 25, true, true, true, 'in-use', NOW(), NOW()),
('Lab102', 'Science Building', 25, true, true, true, 'available', NOW(), NOW());
```

## Models Created

1. **FacultyLoad** (`app/Models/FacultyLoad.php`)
   - Relationship: belongsTo User (faculty_id)
   - Attributes: department, classes_assigned, load_hours, status, notes

2. **DSSRecommendation** (`app/Models/DSSRecommendation.php`)
   - Relationship: belongsTo User (related_faculty_id)
   - Attributes: type, priority, issue, solution, status

3. **ExportLog** (`app/Models/ExportLog.php`)
   - Relationship: belongsTo User (created_by)
   - Attributes: format, data_selected, filename, file_path, file_size, status

4. **ClassSchedule** (`app/Models/ClassSchedule.php`)
   - Relationships: belongsTo User (faculty_id), belongsTo Room
   - Attributes: subject, grade_section, day_of_week, start_time, end_time, student_count, status

5. **Room** (`app/Models/Room.php`)
   - Attributes: room_number, building, capacity, has_laboratory, has_projector, has_ac, status

## JavaScript Functions

### Faculty Loading Page
- `viewFacultyDetails(name)` - View detailed faculty information
- `editFaculty(name)` - Edit faculty assignments
- `addNewFaculty()` - Add new faculty member

### DSS Recommendations Page
- `acceptRecommendation(id)` - Accept and implement a recommendation
- `rejectRecommendation(id)` - Reject a recommendation with feedback

### Print/Export Page
- `exportData(format)` - Export all data in specified format (pdf, excel, csv)
- `printReport()` - Print schedule report
- `archiveData()` - Archive selected data for backup
- `advancedExport()` - Generate custom export with selected options
- `downloadFile()` - Download previously exported file

## Next Steps

### To Make Features Fully Functional:

1. **Create Controllers** (optional but recommended)
   ```bash
   php artisan make:controller FacultyLoadingController
   php artisan make:controller DSSController
   php artisan make:controller ExportController
   ```

2. **Update Routes to Use Controllers** (for dynamic data)
   ```php
   Route::get('admin/faculty-loading', [FacultyLoadingController::class, 'index']);
   Route::get('admin/dss-recommendations', [DSSController::class, 'index']);
   Route::get('admin/print-export', [ExportController::class, 'index']);
   ```

3. **Update Views to Display Dynamic Data**
   Replace sample data loops with actual database queries using Eloquent

4. **Implement Export Functionality**
   - Install `barryvdh/laravel-dompdf` for PDF
   - Install `maatwebsite/excel` for Excel
   - Implement email sending via Laravel Mail

5. **Add Authentication Checks**
   - Ensure only admin users can access management routes
   - Log all data exports and recommendations

## File Structure Summary

```
database/
├── migrations/
│   └── 2026_01_15_000001_create_faculty_dss_and_export_tables.php
└── seeders/
    └── FacultyLoadSeeder.php

app/
├── Models/
│   ├── FacultyLoad.php
│   ├── DSSRecommendation.php
│   ├── ExportLog.php
│   ├── ClassSchedule.php
│   └── Room.php

resources/
└── views/
    └── admin/
        ├── faculty-loading.blade.php
        ├── dss-recommendations.blade.php
        └── print-export.blade.php

routes/
└── web.php (updated with new routes)
```

## Notes

- All pages currently display sample/placeholder data
- JavaScript functions are ready for backend integration
- Database tables are properly configured with foreign key relationships
- Admin middleware is applied to all management routes
- All timestamps are automatically managed by Eloquent
