# Admin Schedule Approval Workflow - Implementation Guide

## Overview
This document describes the newly implemented admin schedule approval workflow where:
1. **Teachers** create new class schedules (initially in **Pending** status)
2. **Admin** reviews pending schedules in the Admin Dashboard
3. **Admin** can **Approve** or **Reject** schedules
4. **Approved** schedules appear in the Approved list and become visible to teachers
5. **Rejected** schedules are marked as rejected

---

## Workflow Steps

### Step 1: Teacher Creates a Schedule
- Teacher navigates to `/teacher/class-schedule`
- Creates a new schedule (initially **status = 'pending'**, **admin_approved = false**)

### Step 2: Admin Reviews Pending Schedules
- Admin navigates to `/admin/dashboard`
- Clicks on **"Pending"** tab to see all pending schedules awaiting approval
- Each pending schedule shows:
  - Grade/Section
  - Teacher Name
  - Subject
  - Time
  - Status: **Pending** (yellow badge)
  - **Action Buttons:**
    - ✓ **Approve** button (green checkmark)
    - ✕ **Reject** button (red X)

### Step 3: Admin Takes Action

#### Option A: Approve Schedule
1. Click the **✓ (Approve)** button
2. Confirm the action in the popup
3. Schedule is updated:
   - `admin_approved` = `true`
   - `status` = `'active'`
   - `approved_by` = Admin user ID
   - `approved_at` = Current timestamp
   - Change log entry recorded

#### Option B: Reject Schedule
1. Click the **✕ (Reject)** button
2. Enter rejection reason in the prompt
3. Schedule is updated:
   - `admin_approved` = `false`
   - `status` = `'rejected'`
   - Change log entry with reason recorded

### Step 4: View Approved Schedules
- Click on **"Approved"** tab to see all approved schedules
- Approved schedules show:
  - Status: **Approved** (green badge)
  - Action buttons for edit and delete (for approved schedules only)

### Step 5: Teacher Sees Approved Schedules
- Teacher navigates to `/teacher/class-schedule`
- Only **approved** schedules (where `admin_approved = true` AND `status = 'active'`) are displayed
- Dashboard auto-refreshes every 30 seconds to show new approvals

---

## Admin Dashboard Features

### Tab Navigation
The admin dashboard has three main tabs:

1. **All [Count]** - Shows all schedules
2. **Pending [Count]** - Shows only pending schedules awaiting approval
3. **Approved [Count]** - Shows only approved schedules

The count dynamically updates based on the number of schedules in each category.

### Action Buttons

#### For Pending Schedules:
- **✓ Approve** - Approves the schedule (turns it active)
- **✕ Reject** - Rejects the schedule with a reason

#### For Approved Schedules:
- **✎ Edit** - Allows admin to modify approved schedules
- **✕ Delete** - Allows admin to delete schedules (requires a deletion reason)

---

## Database Changes

The `class_schedules` table includes these approval-related columns:

| Column | Type | Purpose |
|--------|------|---------|
| `admin_approved` | Boolean | Flag indicating if admin has approved |
| `approved_at` | Timestamp | When the schedule was approved |
| `approved_by` | Foreign Key | Admin user ID who approved |
| `status` | Enum | Schedule status: pending, active, rejected, deleted |
| `version` | Integer | Version number for change tracking |
| `change_log` | LongText | Complete audit trail of all changes |
| `last_modified_by_admin` | Timestamp | Last modification time by admin |

---

## API Endpoints

### Admin Approval API

```
POST /api/admin/schedules/{schedule_id}/approve
- Approves a pending schedule
- Response: { message, schedule }

POST /api/admin/schedules/{schedule_id}/reject
- Rejects a pending schedule
- Body: { reason: "string" }
- Response: { message, schedule }

GET /api/admin/schedules
- Returns all schedules with counts
- Response: { data: [...schedules] }

GET /api/admin/schedules/pending
- Returns only pending schedules
- Response: { data: [...pending_schedules] }

DELETE /api/admin/schedules/{schedule_id}
- Deletes a schedule (soft delete)
- Body: { reason: "string" }
- Response: { message, schedule }

PUT /api/admin/schedules/{schedule_id}
- Updates schedule details
- Response: { message, schedule }
```

---

## Files Modified

### 1. `resources/views/admin/dashboard.blade.php`
- Added functional tabs for All/Pending/Approved filtering
- Updated table to display schedule data dynamically
- Added approve/reject buttons for pending schedules
- Added comprehensive JavaScript for API calls and UI updates

### 2. `resources/views/layouts/admin.blade.php`
- Added CSS styling for action buttons (.approve-btn, .reject-btn)
- Added styles for the active tabs and counts

### 3. `routes/web.php`
- Updated admin dashboard route to pass initial schedule data
- All API endpoints already existed in ScheduleController

---

## Usage Instructions

### For Admin Users

1. **View Dashboard**
   - Go to `/admin/dashboard`
   - See summary stats including "Pending Approvals" count

2. **Review Pending Schedules**
   - Click "Pending" tab
   - Review each schedule details (teacher, subject, time, grade)
   - Decide whether to approve or reject

3. **Approve a Schedule**
   - Click the green ✓ button
   - Confirm in popup dialog
   - Schedule becomes active and visible to teacher

4. **Reject a Schedule**
   - Click the red ✕ button on pending schedule
   - Enter rejection reason when prompted
   - Schedule marked as rejected

5. **View Approved Schedules**
   - Click "Approved" tab
   - See all approved schedules
   - Can edit or delete if needed

### For Teacher Users

1. **Create Schedule** → Schedule appears as **Pending** in admin dashboard
2. **Wait for Approval** → Admin reviews and approves
3. **See Approved Schedule** → Schedule appears in teacher dashboard
4. **Use Schedule** → Plan classes based on approved schedule

---

## Change Tracking

Every schedule includes a detailed change log tracking:
- Initial creation (teacher and timestamp)
- Approval (admin name and timestamp)
- Any modifications (field changes with before/after values)
- Rejections (reason and timestamp)
- Deletions (reason and timestamp)
- Version increments with each modification

Example change log entry:
```
[2026-01-19 14:30:45] Created by Teacher: Ms. Maria Santos

[2026-01-19 14:35:22] Approved by Admin: Dr. Juan Admin

[2026-01-19 15:10:45] Updated by Admin Dr. Juan Admin:
  • start_time: 07:30 → 08:00
  • end_time: 08:30 → 09:00
```

---

## Security Features

✅ **Role-Based Access Control** - Only admins can approve/reject  
✅ **CSRF Protection** - All API calls include CSRF tokens  
✅ **Audit Trail** - Complete change history maintained  
✅ **Soft Deletes** - Schedules marked deleted, not removed  
✅ **Authorization Checks** - All API endpoints verify admin role  

---

## Testing Checklist

- [ ] Admin can see Pending tab with counts
- [ ] Admin can see Pending schedules in Pending tab
- [ ] Admin can see Approved schedules in Approved tab
- [ ] Click Approve button → Schedule moves to Approved (refreshes data)
- [ ] Click Reject button → Schedule shows rejection reason
- [ ] Teacher sees approved schedules (not pending)
- [ ] Teacher doesn't see rejected schedules
- [ ] Change log records all actions
- [ ] Version increments on each modification
- [ ] Counts update automatically after approval/rejection

---

## Troubleshooting

### Schedules not loading
- Check browser console for API errors
- Verify CSRF token exists in page meta tag
- Check network tab for 403/500 responses

### Approve/Reject buttons not working
- Ensure logged in as admin user
- Check admin middleware is applied
- Verify API endpoints are registered in routes/web.php

### Schedules still showing as pending
- Reload the page to refresh data
- Check database for admin_approved and status values
- Verify API response includes successful change

---

## Next Steps (Optional)

Consider implementing:
- Email notifications when schedule approved/rejected
- Bulk approval for multiple schedules
- Schedule conflict detection before approval
- Custom approval workflows/rules
- Admin comments on schedules

