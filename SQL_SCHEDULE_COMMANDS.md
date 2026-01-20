# Quick SQL Commands - Schedule Approval System

## 1. APPROVE A SCHEDULE
```sql
UPDATE class_schedules 
SET 
    admin_approved = TRUE,
    status = 'active',
    approved_at = NOW(),
    approved_by = 2,  -- Admin user ID
    change_log = CONCAT(IFNULL(change_log, ''), '\n[', NOW(), '] Approved by Admin ID: 2')
WHERE id = 1 AND admin_approved = FALSE;
```

## 2. REJECT A SCHEDULE
```sql
UPDATE class_schedules 
SET 
    admin_approved = FALSE,
    status = 'rejected',
    change_log = CONCAT(
        IFNULL(change_log, ''), 
        '\n[', NOW(), '] Rejected by Admin ID: 2 - Reason: Schedule conflict'
    )
WHERE id = 1;
```

## 3. EDIT/UPDATE A SCHEDULE
```sql
UPDATE class_schedules 
SET 
    subject = 'Advanced Mathematics',
    grade_section = 'Grade 5B',
    start_time = '09:30',
    end_time = '10:30',
    student_count = 35,
    version = version + 1,
    last_modified_by_admin = NOW(),
    change_log = CONCAT(
        IFNULL(change_log, ''),
        '\n[', NOW(), '] Updated by Admin 2:',
        '\n  • Subject: Math → Advanced Mathematics',
        '\n  • Grade: Grade 5A → Grade 5B',
        '\n  • Start Time: 09:00 → 09:30',
        '\n  • End Time: 10:00 → 10:30',
        '\n  • Student Count: 30 → 35'
    )
WHERE id = 1 AND admin_approved = TRUE;
```

## 4. DELETE/SOFT DELETE A SCHEDULE
```sql
UPDATE class_schedules 
SET 
    status = 'deleted',
    admin_approved = FALSE,
    change_log = CONCAT(
        IFNULL(change_log, ''), 
        '\n[', NOW(), '] Deleted by Admin ID: 2 - Reason: Course cancelled'
    )
WHERE id = 1;
```

## 5. GET PENDING SCHEDULES (FOR ADMIN REVIEW)
```sql
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    u.name as teacher_name,
    u.email as teacher_email,
    r.room_number,
    r.building,
    cs.created_at
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN rooms r ON cs.room_id = r.id
WHERE cs.admin_approved = FALSE 
AND cs.status = 'pending'
ORDER BY cs.created_at ASC;
```

## 6. GET APPROVED ACTIVE SCHEDULES
```sql
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    cs.approved_at,
    u.name as teacher_name,
    r.room_number,
    admin.name as approved_by_admin
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN rooms r ON cs.room_id = r.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.admin_approved = TRUE 
AND cs.status = 'active'
ORDER BY cs.day_of_week, cs.start_time ASC;
```

## 7. GET TEACHER'S APPROVED SCHEDULES (FOR DASHBOARD)
```sql
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    cs.approved_at,
    r.room_number,
    r.building,
    admin.name as approved_by_admin
FROM class_schedules cs
LEFT JOIN rooms r ON cs.room_id = r.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.faculty_id = 1  -- Teacher ID
AND cs.admin_approved = TRUE 
AND cs.status = 'active'
ORDER BY 
    FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    cs.start_time ASC;
```

## 8. VIEW SCHEDULE CHANGE HISTORY
```sql
SELECT 
    id,
    subject,
    grade_section,
    day_of_week,
    version,
    created_at,
    last_modified_by_admin,
    admin_approved,
    approved_at,
    change_log
FROM class_schedules 
WHERE id = 1;
```

## 9. COUNT SCHEDULES BY STATUS
```sql
SELECT 
    COUNT(*) as total_schedules,
    SUM(CASE WHEN admin_approved = TRUE AND status = 'active' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN admin_approved = FALSE AND status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted
FROM class_schedules;
```

## 10. GET APPROVAL STATISTICS BY ADMIN
```sql
SELECT 
    admin.id,
    admin.name,
    COUNT(cs.id) as approved_count,
    MAX(cs.approved_at) as last_approval
FROM class_schedules cs
JOIN users admin ON cs.approved_by = admin.id
WHERE cs.admin_approved = TRUE
GROUP BY admin.id, admin.name
ORDER BY approved_count DESC;
```

## 11. FIND ALL EDITS MADE BY ADMIN
```sql
SELECT 
    id,
    subject,
    version,
    last_modified_by_admin,
    change_log
FROM class_schedules 
WHERE last_modified_by_admin IS NOT NULL
ORDER BY last_modified_by_admin DESC;
```

## 12. GET SCHEDULE WITH FULL AUDIT TRAIL
```sql
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    u.name as created_by_teacher,
    cs.created_at as created_at,
    admin.name as approved_by_admin,
    cs.approved_at,
    cs.version,
    cs.change_log,
    cs.status,
    cs.admin_approved
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.id = 1;
```

## 13. BULK APPROVE SCHEDULES
```sql
UPDATE class_schedules 
SET 
    admin_approved = TRUE,
    status = 'active',
    approved_at = NOW(),
    approved_by = 2,
    change_log = CONCAT(IFNULL(change_log, ''), '\n[', NOW(), '] Bulk Approved by Admin 2')
WHERE admin_approved = FALSE 
AND status = 'pending'
AND faculty_id IN (1, 2, 3);  -- Specific teachers
```

## 14. FIND SCHEDULES NEEDING REVIEW (OLDER THAN 3 DAYS)
```sql
SELECT 
    cs.id,
    cs.subject,
    u.name as teacher_name,
    cs.created_at,
    DATEDIFF(NOW(), cs.created_at) as days_pending
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
WHERE cs.admin_approved = FALSE 
AND cs.status = 'pending'
AND DATEDIFF(NOW(), cs.created_at) >= 3
ORDER BY cs.created_at ASC;
```

## 15. RESTORE A DELETED SCHEDULE
```sql
UPDATE class_schedules 
SET 
    status = 'active',
    admin_approved = TRUE,
    change_log = CONCAT(
        IFNULL(change_log, ''),
        '\n[', NOW(), '] Restored by Admin 2'
    )
WHERE id = 1 AND status = 'deleted';
```

## 16. CHECK FOR SCHEDULING CONFLICTS
```sql
SELECT 
    cs1.id as schedule1_id,
    cs2.id as schedule2_id,
    cs1.subject as subject1,
    cs2.subject as subject2,
    cs1.day_of_week,
    cs1.start_time,
    cs1.end_time,
    r.room_number as conflicted_room
FROM class_schedules cs1
JOIN class_schedules cs2 ON 
    cs1.room_id = cs2.room_id 
    AND cs1.day_of_week = cs2.day_of_week
    AND cs1.id < cs2.id
    AND (
        (cs1.start_time < cs2.end_time AND cs1.end_time > cs2.start_time)
    )
LEFT JOIN rooms r ON cs1.room_id = r.id
WHERE cs1.admin_approved = TRUE 
AND cs2.admin_approved = TRUE 
AND cs1.status = 'active'
AND cs2.status = 'active';
```

## IMPORTANT NOTES

1. **Replace values:** Change user IDs, schedule IDs, and reasons to match your data
2. **Change log format:** Use the format shown to maintain consistency
3. **Version tracking:** Always increment version on updates
4. **Status values:** Use 'pending', 'active', 'rejected', 'deleted', 'completed'
5. **Admin approval:** Only approved schedules appear on teacher dashboard
6. **Real-time:** Use these same queries in your API endpoints

## Usage in PHP/Laravel

Instead of raw SQL, use Laravel Eloquent in ScheduleController:

```php
// Approve
$schedule->update([
    'admin_approved' => true,
    'status' => 'active',
    'approved_at' => now(),
    'approved_by' => auth()->id(),
]);

// Update
$schedule->update([
    'subject' => $request->subject,
    'version' => $schedule->version + 1,
    'last_modified_by_admin' => now(),
]);
```

See `app/Http/Controllers/ScheduleController.php` for complete implementation.
