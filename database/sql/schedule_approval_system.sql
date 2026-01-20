-- ============================================================================
-- CLASS SCHEDULE APPROVAL SYSTEM - SQL COMMANDS
-- ============================================================================
-- These SQL commands handle approve, edit, delete, and update operations 
-- for the class schedule system with admin approval tracking.

-- ============================================================================
-- 1. ALTER TABLE: Add Approval Columns to class_schedules
-- ============================================================================
ALTER TABLE class_schedules ADD COLUMN admin_approved BOOLEAN DEFAULT FALSE AFTER status;
ALTER TABLE class_schedules ADD COLUMN approved_at TIMESTAMP NULL AFTER admin_approved;
ALTER TABLE class_schedules ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER approved_at;
ALTER TABLE class_schedules ADD COLUMN version INT DEFAULT 1 AFTER approved_by;
ALTER TABLE class_schedules ADD COLUMN change_log LONGTEXT NULL AFTER version;
ALTER TABLE class_schedules ADD COLUMN last_modified_by_admin TIMESTAMP NULL AFTER change_log;

-- Add foreign key constraint for approved_by
ALTER TABLE class_schedules 
ADD CONSTRAINT fk_class_schedules_approved_by 
FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================================
-- 2. APPROVE SCHEDULE
-- ============================================================================
-- Approves a pending schedule and makes it active
UPDATE class_schedules 
SET 
    admin_approved = TRUE,
    status = 'active',
    approved_at = NOW(),
    approved_by = :admin_id,
    change_log = CONCAT(
        IFNULL(change_log, ''), 
        '\n\n[', NOW(), '] Approved by Admin ID: ', :admin_id
    )
WHERE id = :schedule_id AND admin_approved = FALSE;

-- ============================================================================
-- 3. REJECT/DISAPPROVE SCHEDULE
-- ============================================================================
-- Rejects a pending schedule with reason
UPDATE class_schedules 
SET 
    admin_approved = FALSE,
    status = 'rejected',
    change_log = CONCAT(
        IFNULL(change_log, ''), 
        '\n\n[', NOW(), '] Rejected by Admin ID: ', :admin_id, 
        ' - Reason: ', :rejection_reason
    )
WHERE id = :schedule_id;

-- ============================================================================
-- 4. UPDATE APPROVED SCHEDULE
-- ============================================================================
-- Updates an approved schedule and tracks the changes
UPDATE class_schedules 
SET 
    subject = IFNULL(:subject, subject),
    grade_section = IFNULL(:grade_section, grade_section),
    room_id = IFNULL(:room_id, room_id),
    day_of_week = IFNULL(:day_of_week, day_of_week),
    start_time = IFNULL(:start_time, start_time),
    end_time = IFNULL(:end_time, end_time),
    student_count = IFNULL(:student_count, student_count),
    status = IFNULL(:status, status),
    version = version + 1,
    last_modified_by_admin = NOW(),
    change_log = CONCAT(
        IFNULL(change_log, ''),
        '\n\n[', NOW(), '] Updated by Admin ID: ', :admin_id,
        '\n  • Subject: ', subject, ' → ', IFNULL(:subject, subject),
        '\n  • Grade Section: ', grade_section, ' → ', IFNULL(:grade_section, grade_section),
        '\n  • Room ID: ', IFNULL(room_id, 'NULL'), ' → ', IFNULL(:room_id, IFNULL(room_id, 'NULL')),
        '\n  • Day: ', day_of_week, ' → ', IFNULL(:day_of_week, day_of_week),
        '\n  • Time: ', start_time, ' - ', end_time, ' → ',
        IFNULL(:start_time, start_time), ' - ', IFNULL(:end_time, end_time),
        '\n  • Student Count: ', student_count, ' → ', IFNULL(:student_count, student_count),
        '\n  • Status: ', status, ' → ', IFNULL(:status, status)
    )
WHERE id = :schedule_id AND admin_approved = TRUE;

-- ============================================================================
-- 5. DELETE/SOFT DELETE SCHEDULE
-- ============================================================================
-- Soft deletes a schedule by marking it as deleted instead of removing it
UPDATE class_schedules 
SET 
    status = 'deleted',
    admin_approved = FALSE,
    change_log = CONCAT(
        IFNULL(change_log, ''), 
        '\n\n[', NOW(), '] Deleted by Admin ID: ', :admin_id,
        ' - Reason: ', :deletion_reason
    )
WHERE id = :schedule_id;

-- ============================================================================
-- 6. HARD DELETE SCHEDULE (Permanent Removal)
-- ============================================================================
-- Permanently removes a schedule from the database
DELETE FROM class_schedules 
WHERE id = :schedule_id;

-- ============================================================================
-- 7. GET PENDING SCHEDULES FOR ADMIN REVIEW
-- ============================================================================
-- Retrieves all schedules awaiting admin approval
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    cs.status,
    cs.admin_approved,
    cs.created_at,
    u.id as faculty_id,
    u.name as faculty_name,
    u.email as faculty_email,
    r.id as room_id,
    r.room_number,
    r.building
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN rooms r ON cs.room_id = r.id
WHERE cs.admin_approved = FALSE 
AND cs.status = 'pending'
ORDER BY cs.created_at ASC;

-- ============================================================================
-- 8. GET APPROVED SCHEDULES (For Teacher Dashboard)
-- ============================================================================
-- Retrieves all approved schedules to display in teacher dashboard
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    cs.status,
    cs.admin_approved,
    cs.approved_at,
    cs.version,
    u.id as faculty_id,
    u.name as faculty_name,
    u.email as faculty_email,
    r.id as room_id,
    r.room_number,
    r.building,
    admin.name as approved_by_name
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN rooms r ON cs.room_id = r.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.admin_approved = TRUE 
AND cs.status = 'active'
ORDER BY cs.day_of_week, cs.start_time ASC;

-- ============================================================================
-- 9. GET SCHEDULE CHANGE HISTORY
-- ============================================================================
-- Retrieves the complete history of changes made to a schedule
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.version,
    cs.created_at,
    u.name as created_by,
    cs.last_modified_by_admin,
    admin.name as last_modified_by,
    cs.approved_at,
    approver.name as approved_by,
    cs.change_log
FROM class_schedules cs
JOIN users u ON cs.faculty_id = u.id
LEFT JOIN users admin ON cs.approved_by = admin.id
LEFT JOIN users approver ON cs.approved_by = approver.id
WHERE cs.id = :schedule_id;

-- ============================================================================
-- 10. GET ALL SCHEDULES FOR A SPECIFIC TEACHER
-- ============================================================================
-- Shows all schedules (pending and approved) for a specific teacher
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    cs.status,
    cs.admin_approved,
    cs.approved_at,
    cs.version,
    r.id as room_id,
    r.room_number,
    r.building,
    admin.name as approved_by_name
FROM class_schedules cs
LEFT JOIN rooms r ON cs.room_id = r.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.faculty_id = :faculty_id
ORDER BY cs.created_at DESC;

-- ============================================================================
-- 11. STATISTICS - APPROVAL COUNTS
-- ============================================================================
-- Shows approval statistics
SELECT 
    COUNT(*) as total_schedules,
    SUM(CASE WHEN admin_approved = TRUE AND status = 'active' THEN 1 ELSE 0 END) as approved_active,
    SUM(CASE WHEN admin_approved = FALSE AND status = 'pending' THEN 1 ELSE 0 END) as pending_approval,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'deleted' THEN 1 ELSE 0 END) as deleted,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM class_schedules;

-- ============================================================================
-- 12. STATISTICS - APPROVAL BY ADMIN
-- ============================================================================
-- Shows how many schedules each admin has approved
SELECT 
    admin.id,
    admin.name,
    COUNT(cs.id) as approved_count,
    MAX(cs.approved_at) as last_approval_date
FROM class_schedules cs
JOIN users admin ON cs.approved_by = admin.id
WHERE cs.admin_approved = TRUE
GROUP BY admin.id, admin.name
ORDER BY approved_count DESC;

-- ============================================================================
-- 13. TEACHER SCHEDULE SYNC - Get Approved Schedules for Dashboard
-- ============================================================================
-- This query ensures teacher dashboard only shows approved schedules
SELECT 
    cs.id,
    cs.subject,
    cs.grade_section,
    cs.day_of_week,
    cs.start_time,
    cs.end_time,
    cs.student_count,
    r.room_number,
    r.building,
    r.capacity,
    cs.admin_approved,
    cs.approved_at,
    admin.name as approved_by_admin
FROM class_schedules cs
LEFT JOIN rooms r ON cs.room_id = r.id
LEFT JOIN users admin ON cs.approved_by = admin.id
WHERE cs.faculty_id = :teacher_id 
AND cs.admin_approved = TRUE 
AND cs.status = 'active'
ORDER BY FIELD(cs.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
         cs.start_time ASC;

-- ============================================================================
-- 14. AUDIT LOG - Track Admin Actions
-- ============================================================================
-- You might want to create a separate audit table for better tracking
CREATE TABLE IF NOT EXISTS schedule_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL, -- 'approve', 'reject', 'update', 'delete'
    changes LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES class_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================================
-- 15. INSERT AUDIT LOG ENTRY
-- ============================================================================
INSERT INTO schedule_audit_logs (schedule_id, admin_id, action, changes)
VALUES (
    :schedule_id,
    :admin_id,
    :action,
    :changes
);

-- ============================================================================
-- NOTES:
-- ============================================================================
-- 1. Replace :schedule_id, :admin_id, :faculty_id, etc. with actual values
-- 2. The change_log field stores a detailed history of all modifications
-- 3. The version field increments with each update
-- 4. Approved schedules sync automatically to teacher dashboard
-- 5. Soft delete keeps records for audit purposes
-- 6. Change log helps track what admin approved/modified and when
-- ============================================================================
