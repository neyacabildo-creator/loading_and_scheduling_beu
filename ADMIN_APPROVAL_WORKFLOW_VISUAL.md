# Admin Schedule Approval Workflow - Visual Flow

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     TEACHER CREATES SCHEDULE                         │
│                   Status: pending, admin_approved: false             │
└────────────────────────┬────────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      ADMIN DASHBOARD                                 │
│                  /admin/dashboard                                    │
│                                                                       │
│  Tabs:  [All 256] [Pending 12] [Approved 180]                       │
│                                                                       │
│  When "Pending" tab is clicked:                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │ #SC001 │ Grade 5 │ Ms. Maria Santos │ English │ 7:30-8:30   │   │
│  │ Status: Pending                                              │   │
│  │ Actions: [✓ Approve] [✕ Reject]                             │   │
│  ├──────────────────────────────────────────────────────────────┤   │
│  │ #SC003 │ Grade 7 │ Ms. Ana Reyes    │ Science │ 9:45-10:45  │   │
│  │ Status: Pending                                              │   │
│  │ Actions: [✓ Approve] [✕ Reject]                             │   │
│  └──────────────────────────────────────────────────────────────┘   │
└───┬──────────────┬──────────────────────────────────────────────────┘
    │              │
    │ Click ✓      │ Click ✕
    │ Approve      │ Reject
    │              │
    ↓              ↓
┌─────────────────────────────────────────────────────────────────────┐
│ UPDATE DATABASE                                                      │
│                                                                       │
│ APPROVED:                  │ REJECTED:                              │
│ • admin_approved = true    │ • admin_approved = false               │
│ • status = 'active'        │ • status = 'rejected'                  │
│ • approved_at = NOW()      │ • Change log: reason recorded          │
│ • approved_by = admin_id   │                                        │
│ • Change log updated       │                                        │
└─────────────────────────────────────────────────────────────────────┘
    │
    ↓
┌─────────────────────────────────────────────────────────────────────┐
│                 SCHEDULE APPEARS IN APPROVED TAB                     │
│                 AND IN TEACHER DASHBOARD                             │
│                                                                       │
│  Teacher views: /teacher/class-schedule                             │
│  ✓ Sees approved schedule (admin_approved = true)                   │
│  ✗ Does NOT see pending/rejected schedules                          │
└─────────────────────────────────────────────────────────────────────┘
```

## Database State Changes

### Before Approval (Pending)
```sql
SELECT * FROM class_schedules WHERE id = 1;

id      : 1
subject : English
status  : pending
admin_approved : false (0)
approved_at    : NULL
approved_by    : NULL
faculty_id     : 5 (Ms. Maria Santos)
created_at     : 2026-01-19 10:00:00
change_log     : [2026-01-19 10:00:00] Created by Ms. Maria Santos
```

### After Approval
```sql
SELECT * FROM class_schedules WHERE id = 1;

id      : 1
subject : English
status  : active
admin_approved : true (1)
approved_at    : 2026-01-19 14:35:00
approved_by    : 2 (Dr. Admin)
faculty_id     : 5 (Ms. Maria Santos)
created_at     : 2026-01-19 10:00:00
change_log     : [2026-01-19 10:00:00] Created by Ms. Maria Santos
                
                [2026-01-19 14:35:00] Approved by Admin: Dr. Juan Admin
```

## API Call Flow

### Approve Schedule (POST /api/admin/schedules/{id}/approve)

**Request:**
```javascript
fetch('/api/admin/schedules/1/approve', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': 'token_here'
    },
    body: JSON.stringify({})
})
```

**Response:**
```json
{
    "message": "Schedule approved successfully.",
    "schedule": {
        "id": 1,
        "subject": "English",
        "status": "active",
        "admin_approved": true,
        "approved_at": "2026-01-19T14:35:00.000000Z",
        "approved_by": 2,
        "faculty": { "id": 5, "name": "Ms. Maria Santos" },
        "room": { "id": 3, "name": "Room 102" },
        "approver": { "id": 2, "name": "Dr. Juan Admin" }
    }
}
```

### Reject Schedule (POST /api/admin/schedules/{id}/reject)

**Request:**
```javascript
fetch('/api/admin/schedules/1/reject', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': 'token_here'
    },
    body: JSON.stringify({
        reason: "Conflict with existing schedule"
    })
})
```

**Response:**
```json
{
    "message": "Schedule rejected.",
    "schedule": {
        "id": 1,
        "subject": "English",
        "status": "rejected",
        "admin_approved": false,
        "change_log": "[2026-01-19 14:30:00] Created by Ms. Maria Santos\n\n[2026-01-19 14:40:00] Rejected by Dr. Juan Admin: Conflict with existing schedule"
    }
}
```

## Tab Updates

### Initial Load (All Tab Active)
```
All 256 [active]
Pending 12
Approved 180
```

### After Clicking Pending Tab
```
All 256
Pending 12 [active]  ← Shows only schedules where admin_approved=false
Approved 180
```

### After Approval (UI Updates)
```
All 256 (count updates to 256)
Pending 11 (count decreases by 1)
Approved 181 (count increases by 1)
```

## User Experience Flow

### Admin User Journey

1. **Login as Admin**
   - Navigate to `/admin/dashboard`
   - See "Pending Approvals: 12" stat card

2. **Click Pending Tab**
   - View all 12 pending schedules
   - Read teacher name, subject, time, grade

3. **Review Each Schedule**
   - Consider if it has conflicts
   - Check if teacher has availability
   - Verify room availability

4. **Make Decision**
   - **Approve:** Click ✓ button → Confirm → Done
   - **Reject:** Click ✕ button → Enter reason → Confirm → Done

5. **Monitor Tab Updates**
   - Counts update automatically
   - Pending count: 12 → 11
   - Approved count: 180 → 181

6. **Verify in Database**
   - Schedule moved from pending to approved
   - Change log updated with approval timestamp and admin name

### Teacher User Journey

1. **Create Schedule**
   - Fill form: Subject, Time, Grade, Room
   - Click Submit
   - See "Schedule submitted for approval" message

2. **Check Dashboard**
   - Schedule appears as "Pending Approval"
   - Cannot view in `/teacher/class-schedule` yet

3. **Wait for Admin Decision**
   - 30-second auto-refresh checks for updates

4. **Schedule Approved** ✓
   - Schedule appears in `/teacher/class-schedule`
   - Can now use for class planning
   - Appears in their schedule list

5. **Schedule Rejected** ✗
   - Receive notification/message
   - Can modify and resubmit
   - Or create new schedule

## Status Flow Diagram

```
                    ┌─────────────┐
                    │   PENDING   │
                    │ (initial)   │
                    └──────┬──────┘
                           │
                ┌──────────┴──────────┐
                │                     │
                ↓                     ↓
        ┌────────────────┐    ┌──────────────┐
        │    REJECTED    │    │    ACTIVE    │
        │ (admin reject) │    │ (approved)   │
        └────────────────┘    └──────┬───────┘
                                     │
                                     ↓
                            ┌─────────────────┐
                            │   COMPLETED     │
                            │ (after use)     │
                            └─────────────────┘
                            
        Optional:
        ┌────────────────┐
        │    DELETED     │
        │ (soft delete)  │
        └────────────────┘
```

## Key Features Highlighted

✨ **Dynamic Tab Switching** - No page reload required  
✨ **Real-time Count Updates** - Counts reflect current state  
✨ **Color-Coded Badges** - Yellow=Pending, Green=Approved  
✨ **Context-Aware Buttons** - Different actions for pending vs approved  
✨ **Complete Audit Trail** - All actions logged with timestamps  
✨ **Role-Based Access** - Only admins can approve/reject  
✨ **Responsive UI** - Works on desktop and tablet  

