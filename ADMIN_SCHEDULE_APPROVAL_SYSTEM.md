# Admin Schedule Approval System - Implementation Guide

## Overview
This implementation connects the admin class schedule management with the teacher dashboard. When admins approve, edit, or delete schedules, changes automatically sync to the teacher's dashboard.

## Database Changes

### Migration File
**Location:** `database/migrations/2026_01_19_000000_add_approval_to_class_schedules.php`

**New Columns Added:**
- `admin_approved` (boolean) - Tracks approval status
- `approved_at` (timestamp) - When the schedule was approved
- `approved_by` (bigint FK) - Admin user ID who approved
- `version` (int) - Version number for change tracking
- `change_log` (longtext) - Detailed history of all changes
- `last_modified_by_admin` (timestamp) - When admin last modified

**Run Migration:**
```bash
php artisan migrate
```

## API Endpoints

### Admin Endpoints

#### 1. Get All Schedules
```
GET /api/admin/schedules
Response: Paginated list of all schedules with approver details
```

#### 2. Get Pending Schedules for Review
```
GET /api/admin/schedules/pending
Response: List of schedules awaiting admin approval
```

#### 3. Approve a Schedule
```
POST /api/admin/schedules/{schedule}/approve
Response: Schedule object with admin_approved=true
```

#### 4. Reject a Schedule
```
POST /api/admin/schedules/{schedule}/reject
Body: { "reason": "string" }
Response: Schedule with status=rejected
```

#### 5. Update a Schedule
```
PUT /api/admin/schedules/{schedule}
Body: {
  "subject": "string",
  "grade_section": "string",
  "day_of_week": "string",
  "start_time": "HH:mm",
  "end_time": "HH:mm",
  "student_count": "integer"
}
Response: Updated schedule with incremented version
```

#### 6. Delete a Schedule
```
DELETE /api/admin/schedules/{schedule}
Body: { "reason": "string" }
Response: Schedule with status=deleted
```

#### 7. Get Change History
```
GET /api/admin/schedules/{schedule}/history
Response: Complete change log and version history
```

### Teacher Endpoints

#### 1. Get Teacher's Schedules
```
GET /api/teacher/schedules?faculty_id={id}
Response: All schedules for the teacher (pending and approved)
```

#### 2. Create New Schedule
```
POST /api/teacher/schedules
Body: {
  "faculty_id": "integer",
  "subject": "string",
  "grade_section": "string",
  "room_id": "integer (optional)",
  "day_of_week": "string",
  "start_time": "HH:mm",
  "end_time": "HH:mm",
  "student_count": "integer (optional)"
}
Response: Created schedule (initially pending approval)
```

### Shared Endpoints

#### 1. Get Approved Schedules
```
GET /api/schedules/approved
Response: All approved, active schedules (for display in teacher dashboard)
```

## Views

### Admin View: `resources/views/admin/class-schedule.blade.php`

Features:
- **Pending Schedules Table** - Shows schedules awaiting approval
- **Approved Schedules Table** - Shows all approved, active schedules
- **Approve Modal** - Approve pending schedules
- **Edit Modal** - Edit schedule details
- **Reject/Delete Modal** - Reject or delete with reason

**Actions Available:**
- ✅ Approve - Makes schedule active and visible to teacher
- ✏️ Edit - Modify any approved schedule
- ❌ Reject - Reject pending schedules with reason
- 🗑️ Delete - Soft delete with audit trail
- 📋 History - View all changes made to schedule

### Teacher View: `resources/views/teacher/class-schedule.blade.php`

Features:
- Displays **only admin-approved schedules**
- Auto-refreshes every 30 seconds
- Shows approval date
- Organized by day of week and time
- Real-time sync with admin changes

## Controller: `app/Http/Controllers/ScheduleController.php`

### Key Methods

#### 1. `approve(Schedule $schedule)`
- Approves a pending schedule
- Sets admin_approved = true
- Sets status = 'active'
- Records approval time and admin ID
- Logs change in change_log

#### 2. `reject(Schedule $schedule, Request $request)`
- Rejects pending schedule
- Sets admin_approved = false
- Sets status = 'rejected'
- Stores rejection reason

#### 3. `update(Schedule $schedule, Request $request)`
- Updates approved schedule details
- Increments version number
- Logs all changes in change_log
- Records modification time and admin ID

#### 4. `destroy(Schedule $schedule, Request $request)`
- Soft deletes schedule
- Marks status = 'deleted'
- Stores deletion reason
- Maintains audit trail

#### 5. `getHistory(Schedule $schedule)`
- Returns complete change history
- Shows version, approval info, and change log

## Routes

**Admin Routes** (Protected by admin middleware):
```php
GET    /api/admin/schedules              // List all
GET    /api/admin/schedules/pending      // Pending approval
POST   /api/admin/schedules/{id}/approve // Approve
POST   /api/admin/schedules/{id}/reject  // Reject
PUT    /api/admin/schedules/{id}         // Edit
DELETE /api/admin/schedules/{id}         // Delete
GET    /api/admin/schedules/{id}/history // History
```

**Teacher Routes** (Protected by teacher middleware):
```php
GET  /api/teacher/schedules // Get my schedules
POST /api/teacher/schedules // Create new schedule
```

**Shared Routes**:
```php
GET /api/schedules/approved // Get approved schedules
```

## Database Workflow

### 1. Teacher Creates Schedule
```sql
INSERT INTO class_schedules (
  faculty_id, subject, grade_section, room_id, 
  day_of_week, start_time, end_time, student_count,
  status, admin_approved, version, created_at
) VALUES (
  1, 'Math', 'Grade 5A', 1,
  'Monday', '09:00', '10:00', 30,
  'pending', false, 1, NOW()
);
```

### 2. Admin Approves Schedule
```sql
UPDATE class_schedules SET
  admin_approved = true,
  status = 'active',
  approved_at = NOW(),
  approved_by = 2,
  change_log = '[timestamp] Approved by Admin ID: 2'
WHERE id = 1;
```

### 3. Admin Edits Schedule
```sql
UPDATE class_schedules SET
  subject = 'Mathematics',
  version = version + 1,
  last_modified_by_admin = NOW(),
  change_log = CONCAT(change_log, '\n[timestamp] Updated by Admin: subject changed')
WHERE id = 1 AND admin_approved = true;
```

### 4. Teacher Dashboard Auto-Syncs
```sql
SELECT * FROM class_schedules 
WHERE faculty_id = 1 AND admin_approved = true AND status = 'active'
ORDER BY day_of_week, start_time;
```

## Change Tracking

Each change is logged with:
- **Timestamp** - When the change was made
- **Admin** - Who made the change
- **Action** - What was done (approved, edited, rejected, deleted)
- **Details** - Specific field changes with before/after values
- **Version** - Incremented with each update

**Example Change Log:**
```
[2026-01-19 10:30:45] Approved by Admin ID: 2

[2026-01-19 11:00:00] Updated by Admin 2:
  • Subject: Math → Mathematics
  • Student Count: 30 → 35

[2026-01-19 11:30:00] Deleted by Admin 2: Scheduling conflict
```

## JavaScript Functions

### Admin View JavaScript

- **loadPendingSchedules()** - Fetches pending schedules
- **loadApprovedSchedules()** - Fetches approved schedules
- **openApprovalModal()** - Opens approval/rejection modal
- **approveSchedule()** - Submits approval
- **rejectSchedule()** - Submits rejection with reason
- **deleteSchedule()** - Soft deletes schedule
- **openEditModal()** - Opens edit form
- **updateSchedule()** - Submits updates
- **viewHistory()** - Shows change history

### Teacher View JavaScript

- **loadApprovedSchedules()** - Fetches only approved schedules
- Auto-refresh every 30 seconds

## Security

### Authorization
- **Admin actions** - Protected by `admin` middleware
- **Teacher actions** - Protected by `teacher` middleware
- **CSRF Protection** - All requests include CSRF token
- **Foreign Key Constraints** - Prevent orphaned records

### Data Validation
All inputs validated using Laravel validation rules:
- Subject, Grade Section - Required strings
- Days - Must be Monday-Sunday
- Times - Must be valid HH:mm format
- End time must be after start time
- Student count - Positive integer

## Testing the Implementation

### 1. Run Migration
```bash
php artisan migrate
```

### 2. Create Test Data
```bash
php artisan tinker
```

```php
$schedule = \App\Models\ClassSchedule::create([
  'faculty_id' => 1,
  'subject' => 'Math',
  'grade_section' => 'Grade 5A',
  'day_of_week' => 'Monday',
  'start_time' => '09:00',
  'end_time' => '10:00',
  'student_count' => 30,
  'status' => 'pending',
  'admin_approved' => false
]);
```

### 3. Access Admin Panel
- Navigate to `/admin/class-schedule`
- View pending schedules
- Click "Approve" to approve a schedule
- Check teacher dashboard to confirm sync

### 4. Verify Teacher Dashboard
- Login as teacher
- Navigate to `/teacher/class-schedule`
- Should see approved schedules
- Verify updates appear in real-time

## Troubleshooting

### Schedules not appearing in teacher dashboard
- Check `admin_approved` = true in database
- Check `status` = 'active' in database
- Verify teacher ID matches faculty_id
- Check browser console for JavaScript errors

### Changes not syncing
- Verify auto-refresh is enabled (30 seconds)
- Check network tab in browser dev tools
- Ensure CSRF token is present in requests

### Permission errors
- Verify user role is 'admin' or 'teacher'
- Check middleware is applied correctly in routes

## SQL Reference

**View Approval Status:**
```sql
SELECT id, subject, admin_approved, status, approved_at, approved_by
FROM class_schedules
ORDER BY created_at DESC;
```

**View Pending Schedules:**
```sql
SELECT * FROM class_schedules
WHERE admin_approved = false AND status = 'pending'
ORDER BY created_at ASC;
```

**View Teacher's Approved Schedules:**
```sql
SELECT * FROM class_schedules
WHERE faculty_id = ? AND admin_approved = true AND status = 'active'
ORDER BY day_of_week, start_time;
```

**View Change History:**
```sql
SELECT id, subject, version, change_log
FROM class_schedules
WHERE id = ?;
```

## Files Modified/Created

### Created Files
1. `database/migrations/2026_01_19_000000_add_approval_to_class_schedules.php`
2. `app/Http/Controllers/ScheduleController.php`
3. `database/sql/schedule_approval_system.sql`

### Modified Files
1. `app/Models/ClassSchedule.php` - Added approval fields
2. `routes/web.php` - Added API routes
3. `resources/views/admin/class-schedule.blade.php` - Added management UI
4. `resources/views/teacher/class-schedule.blade.php` - Auto-sync approved schedules

## Future Enhancements

1. **Notifications** - Email/SMS when schedules are approved/changed
2. **Audit Table** - Dedicated table for comprehensive audit logging
3. **Approval Workflow** - Multi-level approvals (Department → Admin)
4. **Bulk Operations** - Approve/reject multiple schedules at once
5. **Export** - Export schedules with approval history
6. **Analytics** - Dashboard showing approval metrics and trends

## Support & Documentation

For more information, refer to:
- `SQL_COMMANDS.md` - Detailed SQL commands
- Laravel documentation: https://laravel.com/docs
- This file for implementation details
