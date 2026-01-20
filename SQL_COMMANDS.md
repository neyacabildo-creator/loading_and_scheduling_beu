# SQL Commands - Copy & Paste Ready

## For phpMyAdmin - Straight Copy & Paste

### Step 1: Create All Tables

```sql
CREATE TABLE IF NOT EXISTS `faculty_loads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` bigint unsigned NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classes_assigned` int NOT NULL DEFAULT 0,
  `load_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','part-time','overloaded') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_faculty_loads_user` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `dss_recommendations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('high','medium','low') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `issue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `solution` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','rejected','implemented') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `related_faculty_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_dss_recommendations_user` FOREIGN KEY (`related_faculty_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `export_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `format` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_selected` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `status` enum('processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'processing',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_export_logs_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `class_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `faculty_id` bigint unsigned NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_section` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_id` bigint unsigned DEFAULT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `student_count` int DEFAULT 0,
  `status` enum('pending','active','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_class_schedules_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `room_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
  `building` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` int DEFAULT 30,
  `has_laboratory` tinyint(1) DEFAULT 0,
  `has_projector` tinyint(1) DEFAULT 1,
  `has_ac` tinyint(1) DEFAULT 1,
  `status` enum('available','in-use','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: Insert Sample Data

```sql
INSERT INTO `faculty_loads` (`faculty_id`, `department`, `classes_assigned`, `load_hours`, `status`, `created_at`, `updated_at`) VALUES
(1, 'High School', 5, 6.50, 'overloaded', NOW(), NOW()),
(2, 'Senior High', 4, 4.20, 'active', NOW(), NOW()),
(3, 'High School', 3, 3.80, 'active', NOW(), NOW()),
(4, 'College', 6, 7.20, 'overloaded', NOW(), NOW()),
(5, 'Senior High', 4, 4.00, 'active', NOW(), NOW());
```

```sql
INSERT INTO `dss_recommendations` (`type`, `priority`, `issue`, `solution`, `status`, `created_at`, `updated_at`) VALUES
('teacher_overload', 'high', 'Maria Santos has 6.5 hours/week load (exceeds 6.0 max)', 'Reassign 1 class to another available teacher with lower load or hire additional faculty member.', 'pending', NOW(), NOW()),
('class_balance', 'medium', 'Grade 10A has 35 students while 10B has 28 students', 'Transfer 3-5 students from 10A to 10B to balance enrollment and improve teaching effectiveness.', 'pending', NOW(), NOW()),
('room_utilization', 'medium', 'Lab Room 5 is only 40% utilized with 3 classes per day', 'Consolidate lab classes or schedule more activities to increase room usage efficiency.', 'pending', NOW(), NOW()),
('schedule_gap', 'high', '2-hour gap in Juan schedule on Tuesday afternoon', 'Schedule additional class or professional development session during this gap to maximize productivity.', 'pending', NOW(), NOW()),
('facility_assignment', 'medium', 'Science class assigned to regular classroom instead of lab', 'Reassign to appropriate lab room with required equipment and facilities.', 'pending', NOW(), NOW()),
('teacher_preference', 'low', 'Ana requested morning classes but assigned mostly afternoon slots', 'Swap 2 afternoon classes with another teacher to accommodate preference without affecting schedule balance.', 'pending', NOW(), NOW());
```

```sql
INSERT INTO `rooms` (`room_number`, `building`, `capacity`, `has_laboratory`, `has_projector`, `has_ac`, `status`, `created_at`, `updated_at`) VALUES
('A101', 'Main Building', 35, 0, 1, 1, 'available', NOW(), NOW()),
('A102', 'Main Building', 40, 0, 1, 1, 'available', NOW(), NOW()),
('Lab101', 'Science Building', 25, 1, 1, 1, 'in-use', NOW(), NOW()),
('Lab102', 'Science Building', 25, 1, 1, 1, 'available', NOW(), NOW());
```

## Alternative: Using Command Line MySQL

```bash
# If using command line MySQL instead of phpMyAdmin:
mysql -u username -p database_name < migration.sql
```

Or copy each CREATE TABLE and INSERT statement one at a time in your MySQL client.

## Verify Data Was Inserted

After running the SQL, verify with these select commands:

```sql
-- Check faculty_loads
SELECT COUNT(*) FROM faculty_loads;
-- Should return: 5

-- Check dss_recommendations  
SELECT COUNT(*) FROM dss_recommendations;
-- Should return: 6

-- Check rooms
SELECT COUNT(*) FROM rooms;
-- Should return: 4
```

## View Sample Data

```sql
-- See all faculty
SELECT * FROM faculty_loads;

-- See all recommendations
SELECT * FROM dss_recommendations;

-- See all rooms
SELECT * FROM rooms;
```

## Drop Tables (If Needed - BE CAREFUL!)

```sql
-- Only run if you need to start fresh
DROP TABLE IF EXISTS class_schedules;
DROP TABLE IF EXISTS export_logs;
DROP TABLE IF EXISTS dss_recommendations;
DROP TABLE IF EXISTS faculty_loads;
DROP TABLE IF EXISTS rooms;
```

## Notes

- User ID 1-5 should exist in your users table for foreign keys to work
- If you don't have 5 users, adjust the faculty_id values in the INSERT statement
- All times use 24-hour format (HH:MM:SS)
- Timestamps automatically set to current time with NOW()
- UTF8MB4 charset ensures emoji and special character support
