# Quick Start: Admin Schedule Approval System

## Step 1: Run the Migration

```bash
php artisan migrate
```

This adds the following columns to `class_schedules`:
- `admin_approved` (boolean)
- `approved_at` (timestamp)
- `approved_by` (foreign key)
- `version` (integer)
- `change_log` (text)
- `last_modified_by_admin` (timestamp)

## Step 2: Access Admin Panel

1. Login as admin
2. Go to `/admin/class-schedule`
3. You'll see two sections:
   - **Pending Schedules for Approval** - Schedules awaiting admin approval
   - **Approved Schedules** - Already approved schedules

## Step 3: Manage Schedules as Admin

### Approve a Schedule
1. Click "Approve" button on pending schedule
2. Review schedule details in modal
3. Click "Confirm" to approve
4. Schedule becomes active and visible to teacher

### Edit a Schedule
1. Click "Edit" button on any schedule
2. Modify subject, grade, day, time, student count
3. Click "Save Changes"
4. Changes are logged with version tracking

### Reject a Schedule
1. Click "Reject" button on pending schedule
2. Enter reason for rejection
3. Click "Confirm"
4. Teacher receives rejection notification

### Delete a Schedule
1. Click "Delete" button on approved schedule
2. Enter reason for deletion
3. Click "Confirm"
4. Schedule is soft-deleted (kept in database for audit)

## Step 4: Teacher Dashboard Auto-Sync

1. Login as teacher
2. Go to `/teacher/class-schedule`
3. View only **approved schedules**
4. Dashboard auto-refreshes every 30 seconds
5. Any admin changes appear automatically

## API Endpoints Summary

### Admin Only
```
GET    /api/admin/schedules              → Get all schedules
GET    /api/admin/schedules/pending      → Get pending schedules
POST   /api/admin/schedules/{id}/approve → Approve schedule
POST   /api/admin/schedules/{id}/reject  → Reject schedule
PUT    /api/admin/schedules/{id}         → Edit schedule
DELETE /api/admin/schedules/{id}         → Delete schedule
GET    /api/admin/schedules/{id}/history → View change history
```

### Teacher
```
GET  /api/teacher/schedules → Get my schedules
POST /api/teacher/schedules → Create new schedule
```

## Database Operations

### Check Pending Schedules
```sql
SELECT * FROM class_schedules 
WHERE admin_approved = false AND status = 'pending'
ORDER BY created_at ASC;
```

### Check Approved Schedules
```sql
SELECT * FROM class_schedules 
WHERE admin_approved = true AND status = 'active'
ORDER BY day_of_week, start_time;
```

### View Change History
```sql
SELECT id, subject, version, change_log 
FROM class_schedules 
WHERE id = 1;
```

## Testing Checklist

✅ Run migration
```bash
php artisan migrate
```

✅ Login as admin
- Navigate to `/admin/class-schedule`
- Verify pending schedules show

✅ Create test schedule as teacher
- Create schedule (starts in pending status)
- Verify it appears in admin pending list

✅ Approve schedule as admin
- Click approve button
- Verify schedule status changes to active

✅ Check teacher dashboard
- Login as teacher
- Go to `/teacher/class-schedule`
- Verify approved schedule appears

✅ Edit schedule as admin
- Click edit button
- Change subject/time
- Verify change log updated

✅ Delete schedule as admin
- Click delete button
- Verify status changes to deleted

## Troubleshooting

### Migration fails
- Ensure you're in project directory
- Check database connection
- Run `php artisan migrate:refresh` if needed

### Schedules not showing
- Verify `admin_approved = true` in database
- Check `status = 'active'`
- Clear browser cache (Ctrl+Shift+Delete)

### CSRF token error
- Ensure `<meta name="csrf-token">` is in layout
- Refresh page to get new token

### JavaScript errors in console
- Check browser console (F12)
- Verify routes are correct
- Check that auth()->id() returns valid user ID

## Key Features

✨ **Approval Workflow**
- Teachers create schedules (pending)
- Admins review and approve
- Approved schedules show in teacher dashboard
- Rejections tracked with reasons

📊 **Version Tracking**
- Each edit increments version number
- Complete change log maintained
- Shows who made change and when
- Before/after values recorded

🔄 **Real-time Sync**
- Teacher dashboard auto-refreshes
- Changes appear within 30 seconds
- No manual page refresh needed

🔒 **Audit Trail**
- All actions logged with timestamp
- Admin ID recorded
- Soft deletes preserve data
- Searchable change history

## File Locations

- Migration: `database/migrations/2026_01_19_000000_add_approval_to_class_schedules.php`
- Controller: `app/Http/Controllers/ScheduleController.php`
- Admin View: `resources/views/admin/class-schedule.blade.php`
- Teacher View: `resources/views/teacher/class-schedule.blade.php`
- Model: `app/Models/ClassSchedule.php`
- Routes: `routes/web.php`
- SQL Commands: `database/sql/schedule_approval_system.sql`
- Full Documentation: `ADMIN_SCHEDULE_APPROVAL_SYSTEM.md`

## Next Steps

1. ✅ Run the migration
2. ✅ Test the admin approval workflow
3. ✅ Verify teacher dashboard syncs
4. ✅ Review SQL commands file for custom queries
5. ✅ Check change logs in database

## Support

For detailed information, see `ADMIN_SCHEDULE_APPROVAL_SYSTEM.md`

For SQL operations, see `database/sql/schedule_approval_system.sql`
