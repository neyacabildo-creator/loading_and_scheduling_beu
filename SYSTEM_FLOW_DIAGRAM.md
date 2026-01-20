# Complete System Flow - Admin Schedule Approval

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                   TEACHER INTERFACE                         │
├─────────────────────────────────────────────────────────────┤
│  /teacher/class-schedule                                    │
│  ├─ Create New Schedule (POST to API)                      │
│  └─ View Approved Schedules Only (auto-sync every 30s)     │
└──────────┬────────────────────────────────────────────────┘
           │ Creates Schedule
           │ (status: pending)
           │
┌──────────▼────────────────────────────────────────────────┐
│            DATABASE: class_schedules                       │
├──────────────────────────────────────────────────────────┤
│ id | faculty_id | subject | ... | admin_approved | status│
│ 1  | 1          | Math    | ... | false         | pending│
│ 2  | 1          | English | ... | true          | active │
│ 3  | 2          | Science | ... | false         | pending│
└──────────┬───────────────────────────────────────────────┘
           │
           │ Pulls pending schedules
           │
┌──────────▼────────────────────────────────────────────────┐
│                    ADMIN INTERFACE                         │
├──────────────────────────────────────────────────────────┤
│  /admin/class-schedule                                    │
│                                                            │
│  ┌─── PENDING SCHEDULES ───────────────────────────────┐ │
│  │ ID | Teacher | Subject | Day | Time | Room | Action│ │
│  │ 1  | John    | Math    | Mon | 9-10 | 101  | [APV] │ │
│  │ 3  | Mary    | Science | Tue | 10-11| 202  | [EDL] │ │
│  └────────────────────────────────────────────────────┘ │
│                                                            │
│  ┌─── APPROVED SCHEDULES ──────────────────────────────┐ │
│  │ ID | Teacher | Subject | Day | Status | [EDIT/DEL]  │ │
│  │ 2  | John    | English | Wed | Active | [EDT][DLT]  │ │
│  └────────────────────────────────────────────────────┘ │
│                                                            │
│  Buttons:                                                 │
│    [APPROVE] → Updates database → Syncs to teacher      │
│    [EDIT]    → Opens form → Updates with change log     │
│    [REJECT]  → Marks rejected + reason                   │
│    [DELETE]  → Soft deletes + reason                     │
│    [HISTORY] → Shows change log                          │
└──────────┬───────────────────────────────────────────────┘
           │
           │ Approves/Edits/Deletes
           │ (Updates database)
           │
┌──────────▼────────────────────────────────────────────────┐
│            DATABASE: class_schedules (UPDATED)            │
├──────────────────────────────────────────────────────────┤
│ id | faculty_id | subject | ... | admin_approved | status│
│ 1  | 1          | Math    | ... | TRUE          | active │
│ 2  | 1          | English | ... | TRUE          | active │
│ 3  | 2          | Science | ... | FALSE         |rejected│
│                                                            │
│ PLUS:                                                     │
│ - approved_at: timestamp                                 │
│ - approved_by: admin_id                                  │
│ - version: 2 (if edited)                                 │
│ - change_log: "Approved by Admin 2 [timestamp]"          │
└──────────┬───────────────────────────────────────────────┘
           │
           │ Auto-fetches every 30 seconds
           │ (only WHERE admin_approved=true AND status='active')
           │
┌──────────▼────────────────────────────────────────────────┐
│             TEACHER DASHBOARD (AUTO-SYNCED)              │
├──────────────────────────────────────────────────────────┤
│  /teacher/class-schedule                                 │
│                                                            │
│  Schedule Items:                                          │
│  ├─ Monday 09:00-10:00 - Math (Grade 5A - Room 101)      │
│  │  Status: Approved on Jan 19, 2026                     │
│  ├─ Wednesday 10:00-11:00 - English (Grade 6B - Rm 102)  │
│  │  Status: Approved on Jan 19, 2026                     │
│  └─ [More approved schedules...]                         │
│                                                            │
│  ℹ️ Only shows APPROVED schedules                         │
│  ℹ️ Auto-refreshes every 30 seconds                       │
│  ℹ️ Admin changes appear automatically                    │
└─────────────────────────────────────────────────────────┘
```

## Step-by-Step Workflow

### Scenario: Teacher Creates Schedule

```
1. TEACHER ACTION
   ├─ Navigate to /teacher/class-schedule
   ├─ Click "Create New Schedule"
   └─ Fill form: Math, Grade 5A, Monday, 9:00-10:00, Room 101

2. API CALL
   └─ POST /api/teacher/schedules
      {
        "faculty_id": 1,
        "subject": "Math",
        "grade_section": "Grade 5A",
        "day_of_week": "Monday",
        "start_time": "09:00",
        "end_time": "10:00",
        "room_id": 1,
        "student_count": 30
      }

3. DATABASE INSERT
   INSERT INTO class_schedules (...)
   VALUES (1, 'Math', 'Grade 5A', ..., 'pending', false, 1, NULL)

4. TEACHER DASHBOARD
   ├─ Schedule NOT visible yet
   └─ Teacher can see pending schedules awaiting approval

5. ADMIN NOTIFICATION
   ├─ Admin sees new pending schedule
   └─ Admin reviews: Math, Grade 5A, Monday 9-10, Room 101
```

### Scenario: Admin Approves Schedule

```
1. ADMIN ACTION
   ├─ Navigate to /admin/class-schedule
   ├─ See pending schedule: "Math - Grade 5A - Monday"
   └─ Click [APPROVE]

2. CONFIRMATION MODAL
   ├─ Shows schedule details
   └─ Admin clicks [CONFIRM]

3. API CALL
   └─ POST /api/admin/schedules/1/approve

4. DATABASE UPDATE
   UPDATE class_schedules
   SET admin_approved = true,
       status = 'active',
       approved_at = '2026-01-19 10:30:00',
       approved_by = 2,
       change_log = '[2026-01-19 10:30:00] Approved by Admin 2'
   WHERE id = 1

5. TEACHER DASHBOARD AUTO-SYNC
   ├─ Within 30 seconds, schedule appears
   ├─ Teacher sees: "Monday 09:00-10:00 - Math"
   ├─ Shows: "Approved on Jan 19, 2026"
   └─ No teacher action needed - fully automatic!
```

### Scenario: Admin Edits Approved Schedule

```
1. ADMIN ACTION
   ├─ Navigate to /admin/class-schedule
   ├─ See approved schedule
   └─ Click [EDIT]

2. EDIT MODAL
   ├─ Pre-fills: Math, Grade 5A, Monday, 09:00-10:00
   ├─ Admin changes:
   │  ├─ Subject: Math → Advanced Mathematics
   │  └─ Student Count: 30 → 35
   └─ Clicks [SAVE CHANGES]

3. API CALL
   └─ PUT /api/admin/schedules/1
      {
        "subject": "Advanced Mathematics",
        "student_count": 35
      }

4. DATABASE UPDATE
   UPDATE class_schedules
   SET subject = 'Advanced Mathematics',
       student_count = 35,
       version = 2,
       last_modified_by_admin = NOW(),
       change_log = '[...]Approved...\n[2026-01-19 11:00:00] Updated by Admin 2:
                    \n  • Subject: Math → Advanced Mathematics
                    \n  • Student Count: 30 → 35'
   WHERE id = 1

5. TEACHER DASHBOARD AUTO-SYNC
   ├─ Within 30 seconds, updates appear
   ├─ Teacher sees: "Advanced Mathematics" (was "Math")
   ├─ Shows: "35 students" (was "30")
   └─ Changes appear automatically!
```

### Scenario: Admin Rejects Schedule

```
1. ADMIN ACTION
   ├─ Navigate to /admin/class-schedule
   ├─ See pending schedule
   └─ Click [REJECT]

2. REJECTION MODAL
   ├─ Shows schedule details
   ├─ Admin enters reason: "Scheduling conflict with room 101"
   └─ Clicks [CONFIRM]

3. API CALL
   └─ POST /api/admin/schedules/3/reject
      {
        "reason": "Scheduling conflict with room 101"
      }

4. DATABASE UPDATE
   UPDATE class_schedules
   SET admin_approved = false,
       status = 'rejected',
       change_log = '[2026-01-19 11:30:00] Rejected by Admin 2: Scheduling conflict with room 101'
   WHERE id = 3

5. TEACHER NOTIFICATION
   ├─ Schedule removed from approved list
   ├─ Teacher can see reason in rejection notice
   └─ Teacher can edit and resubmit
```

### Scenario: Admin Deletes Schedule

```
1. ADMIN ACTION
   ├─ Navigate to /admin/class-schedule
   ├─ See approved schedule
   └─ Click [DELETE]

2. DELETION MODAL
   ├─ Shows schedule details
   ├─ Admin enters reason: "Course cancelled for this semester"
   └─ Clicks [CONFIRM]

3. API CALL
   └─ DELETE /api/admin/schedules/2
      {
        "reason": "Course cancelled for this semester"
      }

4. DATABASE UPDATE (SOFT DELETE)
   UPDATE class_schedules
   SET status = 'deleted',
       admin_approved = false,
       change_log = '[...]...\n[2026-01-19 12:00:00] Deleted by Admin 2: Course cancelled'
   WHERE id = 2
   -- Record is KEPT in database for audit trail

5. TEACHER DASHBOARD
   ├─ Schedule disappears from approved list
   ├─ Reason available if teacher inquires
   └─ Data preserved for compliance
```

## Real-time Synchronization Flow

```
┌─────────────────────────────────────────────────────────────┐
│         TEACHER BROWSER (Auto-refresh every 30s)           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  JavaScript setInterval (30 seconds):                       │
│  ├─ fetch('/api/teacher/schedules?faculty_id=1')          │
│  ├─ Filter: admin_approved=true AND status='active'        │
│  ├─ Update DOM with schedule list                          │
│  └─ Display updates to user                                │
│                                                              │
│  The query AUTOMATICALLY catches:                          │
│  ├─ New approvals                                          │
│  ├─ Edits to approved schedules                            │
│  ├─ Deletions (status='deleted' filtered out)              │
│  └─ Any other changes admin made                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Change Log Tracking

Each action creates an audit trail:

```
Example for Schedule ID 1:

[2026-01-19 09:00:00] Initial creation by teacher
  (no change_log entry yet - teacher created it)

[2026-01-19 10:30:00] Approved by Admin ID: 2

[2026-01-19 11:00:00] Updated by Admin 2:
  • Subject: Math → Advanced Mathematics
  • Student Count: 30 → 35

[2026-01-19 11:15:00] Updated by Admin 2:
  • End Time: 10:00 → 10:30

[2026-01-19 12:00:00] Deleted by Admin 2: Course cancelled for this semester
```

## Security & Permissions

```
┌─────────────────────────────────────────────────────────┐
│              AUTHORIZATION CHECKS                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ TEACHER ACTIONS:                                       │
│ ├─ Create schedule → Allowed                           │
│ ├─ View own pending schedules → Allowed               │
│ ├─ View own approved schedules → Allowed              │
│ ├─ Edit own pending → Allowed                         │
│ └─ Edit approved by admin → NOT ALLOWED               │
│                                                         │
│ ADMIN ACTIONS:                                         │
│ ├─ Approve schedule → ADMIN ONLY                      │
│ ├─ Edit any schedule → ADMIN ONLY                     │
│ ├─ Delete schedule → ADMIN ONLY                       │
│ ├─ Reject schedule → ADMIN ONLY                       │
│ └─ View change history → ADMIN ONLY                   │
│                                                         │
│ PROTECTION:                                            │
│ ├─ All routes protected by middleware                 │
│ ├─ CSRF token required on all POST/PUT/DELETE         │
│ ├─ User ID verified in requests                       │
│ └─ Permission checked before each action              │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

## Key Features Summary

```
┌──────────────────────────────────────────────────────────┐
│              SYSTEM CAPABILITIES                         │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ ✅ APPROVAL WORKFLOW                                    │
│    • Teachers submit schedules (pending)                │
│    • Admins review and approve                          │
│    • Auto-sync to teacher dashboard                     │
│                                                          │
│ ✅ VERSION TRACKING                                     │
│    • Increment on each edit                             │
│    • Complete change history                            │
│    • Before/after values                                │
│                                                          │
│ ✅ AUDIT TRAIL                                          │
│    • All actions logged                                 │
│    • Timestamps recorded                                │
│    • Admin ID tracked                                   │
│    • Reasons stored                                     │
│                                                          │
│ ✅ REAL-TIME SYNC                                       │
│    • Teacher dashboard auto-refresh                     │
│    • 30-second interval                                 │
│    • Catches all changes                                │
│    • No manual refresh needed                           │
│                                                          │
│ ✅ DATA PROTECTION                                      │
│    • Soft deletes (no data loss)                        │
│    • Foreign key constraints                            │
│    • CSRF token validation                              │
│    • Input validation                                   │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## Database Queries Executed

### When Admin Approves:
```sql
UPDATE class_schedules 
SET admin_approved=true, status='active', approved_at=NOW(), 
    approved_by=2, change_log='[...] Approved by Admin 2'
WHERE id=1
```

### When Teacher Views Dashboard:
```sql
SELECT * FROM class_schedules 
WHERE faculty_id=1 AND admin_approved=true AND status='active'
ORDER BY day_of_week, start_time
```

### When Admin Views Pending:
```sql
SELECT * FROM class_schedules 
WHERE admin_approved=false AND status='pending'
ORDER BY created_at
```

### When Admin Views History:
```sql
SELECT change_log FROM class_schedules WHERE id=1
```

---

**This complete flow ensures:**
- ✅ Teachers only see approved schedules
- ✅ Admins have full control and audit trail
- ✅ Changes sync in real-time
- ✅ All actions are logged
- ✅ Data integrity is maintained
