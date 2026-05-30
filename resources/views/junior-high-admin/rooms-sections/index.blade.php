{{-- resources/views/admin/rooms-sections/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Rooms & Sections')

@section('content')
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .tabs { display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); }
        .tab-btn { padding: 0.75rem 1.25rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); font-size: 0.875rem; font-weight: 600; border-bottom: 3px solid transparent; transition: all 0.2s; margin-bottom: -2px; }
        .tab-btn:hover { color: var(--text-primary); border-color: #d4cfc4; }
        .tab-btn.active { color: var(--green-primary); border-color: var(--green-primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .table-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--shadow-sm); }
        .table-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: var(--bg-primary); text-align: left; font-weight: 600; color: var(--text-primary); border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; color: var(--text-tertiary); }
        tr:hover { background: var(--bg-tertiary); }
        .capacity-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .capacity-large { background: rgba(45,122,80,0.15); color: var(--green-primary); }
        .capacity-medium { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .capacity-small { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .action-btn { padding: 0.4rem 0.7rem; border: 1px solid var(--border-color); border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; background: var(--bg-secondary); color: var(--text-primary); white-space: nowrap; text-decoration: none; display: inline-flex; align-items: center; }
        td.rs-actions-cell { vertical-align: middle; white-space: nowrap; }
        .rs-actions-wrap { display: inline-flex; align-items: center; gap: 0.35rem; flex-wrap: nowrap; }
        #sections-tbody td { vertical-align: middle; }
        #sections-tbody td:nth-child(6) { vertical-align: top; }
        .btn-edit { border-color: var(--text-secondary); }
        .btn-edit:hover { background: var(--text-secondary); color: white; }
        .btn-delete { border-color: #c83232; color: #c83232; }
        .btn-delete:hover { background: #c83232; color: white; border-color: #c83232; }
        input[type="text"] { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-color); }
        .rs-grade-btn{padding:.3rem .85rem;border:2px solid var(--border-color);border-radius:9999px;background:var(--bg-secondary);color:var(--text-secondary);cursor:pointer;font-size:.78rem;font-weight:600;transition:all .2s;}
        .rs-grade-btn:hover{border-color:var(--green-primary);color:var(--green-primary);}
        .rs-grade-btn.active{background:var(--green-primary);border-color:var(--green-primary);color:#fff;}
        /* Live usage badges */
        .in-use-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:rgba(239,68,68,.12);color:#dc2626;}
        .in-use-badge::before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:#dc2626;animation:rs-pulse 1.4s infinite;}
        .free-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:rgba(45,122,80,.1);color:var(--green-primary);}
        .next-sched-cell{font-size:.72rem;color:var(--text-primary);line-height:1.4;}
        .next-sched-cell .ns-time{font-weight:700;color:var(--green-primary);}
        .next-sched-cell .ns-subj{font-weight:600;}
        .next-sched-cell .ns-meta{color:var(--text-secondary);}
        @keyframes rs-pulse{0%,100%{opacity:1}50%{opacity:.4}}
        /* Upcoming panel */
        .upcoming-panel{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;box-shadow:var(--shadow-sm);margin-bottom:1.5rem;overflow:hidden;}
        .upcoming-panel-header{padding:.85rem 1.25rem;background:linear-gradient(135deg,rgba(45,122,80,.08),rgba(45,122,80,.02));border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:.6rem;}
        .upcoming-panel-header h4{margin:0;font-size:.95rem;font-weight:700;color:var(--text-primary);}
        .upcoming-panel-header span.dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--green-primary);animation:rs-pulse 1.4s infinite;}
        .upcoming-table th{background:var(--bg-primary);padding:.65rem 1rem;font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);border-bottom:1px solid var(--border-color);}
        .upcoming-table td{padding:.65rem 1rem;font-size:.8rem;border-bottom:1px solid var(--border-color);color:var(--text-primary);}
        .upcoming-table tr:last-child td{border-bottom:none;}
        .upcoming-table tr:hover{background:var(--bg-tertiary);}

        /* Floating Edit Modal */
        .floating-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .floating-modal { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); padding: 2rem; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .floating-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
        .floating-modal-header h2 { color: var(--text-primary); margin: 0; font-size: 1.125rem; font-weight: 600; }
        .modal-close-btn { background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 1.25rem; line-height: 1; padding: 0.25rem; transition: color 0.2s; }
        .modal-close-btn:hover { color: var(--text-primary); }
        .modal-form-group { margin-bottom: 1rem; }
        .modal-form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.875rem; }
        .modal-form-group input, .modal-form-group select { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; font-size: 0.875rem; box-sizing: border-box; }
        .modal-form-group input:focus, .modal-form-group select:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }
        .modal-checkbox-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .modal-checkbox-item { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }
        .modal-checkbox-item input[type="checkbox"] { width: 16px; height: 16px; }
        .modal-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .modal-actions button { flex: 1; padding: 0.75rem; border-radius: 0.375rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; }
        .modal-btn-save { background: linear-gradient(135deg, var(--green-primary) 0%, #0d3d20 100%); color: white; border: none; }
        .modal-btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(45,122,80,0.3); }
        .modal-btn-cancel { background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); }
        .modal-btn-cancel:hover { background: var(--bg-tertiary); }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Rooms & Sections Management</h1>
        </div>
        <div class="header-right"></div>
    </div>

    <!-- Upcoming Schedules Panel -->
    <div id="rooms-sections-main">
        <div class="upcoming-panel" id="jhUpcomingPanel">
            <div class="upcoming-panel-header">
                <span class="dot"></span>
                <h4>Upcoming Room Schedules</h4>
                <span id="jhLiveClock" style="margin-left:auto;font-size:.8rem;font-weight:600;color:var(--text-secondary);"></span>
            </div>
            <div style="overflow-x:auto;">
                <table class="upcoming-table" style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th>Room / Section</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="jhUpcomingTbody">
                        <tr><td colspan="7" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-card">
            <div class="table-header">
                <div class="table-title">Rooms &amp; Sections</div>
                <div style="display:flex; align-items:center; gap:.4rem; flex-wrap:wrap;">
                    <button class="rs-grade-btn active" data-grade="7"  onclick="rsSetGrade(this,'7')">Grade 7</button>
                    <button class="rs-grade-btn"        data-grade="8"  onclick="rsSetGrade(this,'8')">Grade 8</button>
                    <button class="rs-grade-btn"        data-grade="9"  onclick="rsSetGrade(this,'9')">Grade 9</button>
                    <button class="rs-grade-btn"        data-grade="10" onclick="rsSetGrade(this,'10')">Grade 10</button>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Room &amp; Section</th>
                            <th>Grade Level</th>
                            <th>In Use Now</th>
                            <th>Total Classes</th>
                            <th>Next Schedule</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sections-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading rooms &amp; sections…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        /* ── Sections connected to schedules ── */
        const JH_SCHEDULE_API = '{{ url("api/admin/schedules") }}';
        const JH_FIXED_SECTIONS = {
            '7':  ['SERAPHIM','CHERUBIM','MICHAEL','RAPHAEL','GABRIEL'],
            '8':  ['THERESE','ALOYSIUS','AGNES','JOHN','GORETTI'],
            '9':  ['CHARTRES','PIAT','FATIMA','CARMEL','LOURDES'],
            '10': ['PAUL','PLC','MBF','MICHEAU','MARIA'],
        };
        let rsAllSchedules = [];
        let rsCurrentGrade = '7';

        /* ─── Time Helpers ─── */
        function rsNowMins() {
            const now = new Date();
            return now.getHours() * 60 + now.getMinutes();
        }
        function rsTodayName() {
            return ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][new Date().getDay()];
        }
        function rsTodayStr() {
            const d = new Date();
            return d.toISOString().slice(0,10); // YYYY-MM-DD
        }
        function rsTimeToMins(t) {
            if (!t) return 9999;
            const [h,m] = t.split(':').map(Number);
            return h * 60 + (m || 0);
        }
        function rsFormatTime(t) {
            if (!t) return '—';
            const [h,m] = t.split(':').map(Number);
            const ampm = h >= 12 ? 'PM' : 'AM';
            const hh = h % 12 || 12;
            return `${hh}:${String(m).padStart(2,'0')} ${ampm}`;
        }
        function rsFormatDate(d) {
            if (!d) return '—';
            const clean = String(d).slice(0, 10); // handle 'YYYY-MM-DD HH:MM:SS' from DB
            if (!clean || clean === '0000-00-00') return '—';
            const dt = new Date(clean + 'T00:00:00');
            if (isNaN(dt.getTime())) return '—';
            return dt.toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'});
        }

        /* Returns true if schedule 's' is currently active right now */
        function rsIsNowActive(s) {
            const today = rsTodayName();
            if (s.day_of_week && s.day_of_week !== today) return false;
            // If has specific date, must match today
            if (s.schedule_date && s.schedule_date !== rsTodayStr()) return false;
            const nowM = rsNowMins();
            const startM = rsTimeToMins(s.start_time);
            const endM   = rsTimeToMins(s.end_time);
            return nowM >= startM && nowM < endM;
        }

        /* Returns the next upcoming schedule from a list, or null */
        function rsNextSchedule(list) {
            const today = rsTodayName();
            const nowM  = rsNowMins();
            const todayStr = rsTodayStr();
            // 1. Future slots today
            const todayFuture = list.filter(s => {
                const dayMatch = s.day_of_week === today;
                const dateMatch = !s.schedule_date || s.schedule_date === todayStr;
                return dayMatch && dateMatch && rsTimeToMins(s.start_time) > nowM;
            }).sort((a,b) => rsTimeToMins(a.start_time) - rsTimeToMins(b.start_time));
            if (todayFuture.length) return todayFuture[0];
            // 2. Future by specific schedule_date
            const futureDated = list.filter(s => {
                if (!s.schedule_date) return false;
                return s.schedule_date > todayStr;
            }).sort((a,b) => {
                if (a.schedule_date !== b.schedule_date) return a.schedule_date.localeCompare(b.schedule_date);
                return rsTimeToMins(a.start_time) - rsTimeToMins(b.start_time);
            });
            return futureDated.length ? futureDated[0] : null;
        }

        function rsTeacherName(s) {
            return s.faculty?.name || s.teacher?.name || '—';
        }

        /* ─── Render Sections Tab ─── */
        function rsRenderSections() {
            const sections = JH_FIXED_SECTIONS[rsCurrentGrade] || [];
            const tbody = document.getElementById('sections-tbody');
            if (!tbody) return;
            const rows = sections.map((sec, i) => {
                const secSched = rsAllSchedules.filter(s => {
                    const m = String(s.grade_level||'').match(/\d+/);
                    return m && m[0] === rsCurrentGrade && s.section_name === sec;
                });
                const count = secSched.length;
                const inUseNow = secSched.filter(rsIsNowActive);
                const inUseCount = inUseNow.length;
                const next = rsNextSchedule(secSched);
                const approvedSched = secSched.filter(s => s.admin_approved || s.status === 'active');
                const roomNums = [...new Set(approvedSched.map(s => s.room?.room_number || (s.room_id ? 'Room #'+s.room_id : null)).filter(Boolean))];
                const roomSectionLabel = roomNums.length
                    ? roomNums.map(r => `${r} · ${sec}`).join(', ')
                    : sec;

                const inUseBadge = inUseCount > 0
                    ? `<span class="in-use-badge">${inUseCount} In Use</span>`
                    : `<span class="free-badge">0 — Free</span>`;

                const totalBadge = count > 0
                    ? `<span style="display:inline-block;padding:.2rem .65rem;border-radius:9999px;font-size:.75rem;font-weight:600;background:rgba(45,122,80,.12);color:var(--green-primary);">${count} class${count!==1?'es':''}</span>`
                    : `<span style="display:inline-block;padding:.2rem .65rem;border-radius:9999px;font-size:.75rem;font-weight:600;background:#f3f4f6;color:#6b7280;">0 classes</span>`;

                let nextHtml = '<span class="ns-meta">—</span>';
                if (next) {
                    nextHtml = `<div class="next-sched-cell">
                        <div class="ns-time">${rsFormatTime(next.start_time)} – ${rsFormatTime(next.end_time)}</div>
                        <div class="ns-subj">${next.subject||'—'}</div>
                        <div class="ns-meta">${rsFormatDate(next.schedule_date)} &bull; ${rsTeacherName(next)}</div>
                    </div>`;
                }

                return `<tr>
                    <td>${i+1}</td>
                    <td><strong>${roomSectionLabel}</strong></td>
                    <td>Grade ${rsCurrentGrade}</td>
                    <td>${inUseBadge}</td>
                    <td>${totalBadge}</td>
                    <td>${nextHtml}</td>
                    <td class="rs-actions-cell"><div class="rs-actions-wrap">
                        <a href="{{ route('admin.class-schedule') }}?grade=${rsCurrentGrade}&section=${encodeURIComponent(sec)}" class="action-btn btn-edit">View</a>
                        <a href="{{ route('admin.class-schedule') }}" class="action-btn" style="border-color:var(--green-primary);color:var(--green-primary);">Schedule</a>
                    </div></td>
                </tr>`;
            });
            tbody.innerHTML = rows.length ? rows.join('') : `<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">No rooms &amp; sections for Grade ${rsCurrentGrade}.</td></tr>`;
        }

        /* ─── Render Room Assigned Sections ─── */
        function rsRenderRoomSections() {
            const approvedAll = rsAllSchedules.filter(s => s.admin_approved || s.status === 'active');
            document.querySelectorAll('.room-assigned-sections').forEach(cell => {
                const roomId = cell.dataset.roomId;
                const roomSched = approvedAll.filter(s => String(s.room_id) === String(roomId) || String(s.room?.id) === String(roomId));
                const labels = [...new Set(roomSched.map(s => {
                    const m = String(s.grade_level||'').match(/\d+/);
                    return m ? `Grade ${m[0]} – ${s.section_name||'?'}` : (s.section_name || null);
                }).filter(Boolean))];
                cell.textContent = labels.length ? labels.slice(0,3).join(', ') + (labels.length>3?'…':'') : '—';
            });
        }

        /* ─── Render Room Live Usage ─── */
        function rsRenderRoomUsage() {
            document.querySelectorAll('.room-in-use-cell').forEach(cell => {
                const roomId = cell.dataset.roomId;
                const roomSched = rsAllSchedules.filter(s => String(s.room_id) === String(roomId) || String(s.room?.id) === String(roomId));
                const active = roomSched.filter(rsIsNowActive);
                if (active.length > 0) {
                    const who = active.map(s => {
                        const m = String(s.grade_level||'').match(/\d+/);
                        return m ? `Gr.${m[0]}-${s.section_name||'?'}` : s.section_name || '?';
                    }).join(', ');
                    cell.innerHTML = `<span class="in-use-badge">${active.length} In Use</span><div style="font-size:.7rem;color:var(--text-secondary);margin-top:.2rem;">${who}</div>`;
                } else {
                    cell.innerHTML = `<span class="free-badge">0 — Free</span>`;
                }
            });
            document.querySelectorAll('.room-next-cell').forEach(cell => {
                const roomId = cell.dataset.roomId;
                const roomSched = rsAllSchedules.filter(s => String(s.room_id) === String(roomId) || String(s.room?.id) === String(roomId));
                const next = rsNextSchedule(roomSched);
                if (next) {
                    const m = String(next.grade_level||'').match(/\d+/);
                    const sec = m ? `Gr.${m[0]}-${next.section_name||'?'}` : next.section_name || '?';
                    cell.innerHTML = `<div class="next-sched-cell">
                        <div class="ns-time">${rsFormatTime(next.start_time)} – ${rsFormatTime(next.end_time)}</div>
                        <div class="ns-subj">${next.subject||'—'} <span style="font-weight:400;">(${sec})</span></div>
                        <div class="ns-meta">${rsFormatDate(next.schedule_date)} &bull; ${rsTeacherName(next)}</div>
                    </div>`;
                } else {
                    cell.innerHTML = `<span class="next-sched-cell ns-meta">No upcoming</span>`;
                }
            });
        }

        /* ─── Upcoming Panel (top 10 next schedules across all rooms) ─── */
        /* Compute next occurrence date for a recurring (day_of_week) schedule */
        function rsNextOccurrenceDate(s) {
            // Normalize: handle 'YYYY-MM-DD HH:MM:SS' stored in DB
            const sd = s.schedule_date ? String(s.schedule_date).slice(0, 10) : null;
            if (sd && sd !== '0000-00-00') return sd;
            if (s.day_of_week) {
                const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                const target = days.indexOf(s.day_of_week);
                if (target === -1) return rsTodayStr();
                const now = new Date();
                let diff = (target - now.getDay() + 7) % 7;
                // If same day but class has already ended, push to next week
                if (diff === 0 && rsTimeToMins(s.end_time) <= rsNowMins()) diff = 7;
                const d = new Date(now);
                d.setDate(d.getDate() + diff);
                return d.toISOString().slice(0, 10);
            }
            return rsTodayStr();
        }

        function rsRenderUpcomingPanel() {
            const tbody = document.getElementById('jhUpcomingTbody');
            if (!tbody) return;
            const today = rsTodayName();
            const nowM  = rsNowMins();
            const todayStr = rsTodayStr();

            // Only show admin-approved schedules
            const approved = rsAllSchedules.filter(s => s.admin_approved);

            // Active NOW
            const activeNow = approved.filter(rsIsNowActive).map(s => ({...s, _status:'now'}));
            // Future today (day_of_week matches today, no specific date)
            const futureToday = approved.filter(s => {
                if (rsIsNowActive(s)) return false;
                const sd = s.schedule_date ? String(s.schedule_date).slice(0, 10) : null;
                const dayMatch = s.day_of_week === today;
                const dateMatch = !sd || sd === '0000-00-00' || sd === todayStr;
                return dayMatch && dateMatch && rsTimeToMins(s.start_time) > nowM;
            }).map(s => ({...s, _status:'today'}));
            // Future dated (has a specific future schedule_date)
            const futureDated = approved.filter(s => {
                const sd = s.schedule_date ? String(s.schedule_date).slice(0, 10) : null;
                if (!sd || sd === '0000-00-00' || sd <= todayStr) return false;
                return true;
            }).map(s => ({...s, _status:'future'}));
            // Recurring weekly (day_of_week set, no specific date, not matching today or already handled)
            const seenFutureToday = new Set(futureToday.map(s => s.id));
            const recurringUpcoming = approved.filter(s => {
                if (rsIsNowActive(s)) return false;
                if (seenFutureToday.has(s.id)) return false;
                const sd = s.schedule_date ? String(s.schedule_date).slice(0, 10) : null;
                if (sd && sd !== '0000-00-00') return false; // handled by futureDated
                if (!s.day_of_week) return false;
                // today's day but past time — skip
                if (s.day_of_week === today && rsTimeToMins(s.start_time) <= nowM) return false;
                return true;
            }).map(s => ({...s, _status:'upcoming'}));

            const sorted = [
                ...activeNow.sort((a,b) => rsTimeToMins(a.start_time)-rsTimeToMins(b.start_time)),
                ...futureToday.sort((a,b) => rsTimeToMins(a.start_time)-rsTimeToMins(b.start_time)),
                ...futureDated.sort((a,b) => {
                    const sa = String(a.schedule_date||'').slice(0,10);
                    const sb = String(b.schedule_date||'').slice(0,10);
                    if(sa!==sb) return sa.localeCompare(sb);
                    return rsTimeToMins(a.start_time)-rsTimeToMins(b.start_time);
                }),
                ...recurringUpcoming.sort((a,b) => {
                    const da = rsNextOccurrenceDate(a), db = rsNextOccurrenceDate(b);
                    if(da!==db) return da.localeCompare(db);
                    return rsTimeToMins(a.start_time)-rsTimeToMins(b.start_time);
                }),
            ].slice(0, 10);

            if (!sorted.length) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">No upcoming approved schedules for today.</td></tr>`;
                return;
            }

            tbody.innerHTML = sorted.map(s => {
                const m = String(s.grade_level||'').match(/\d+/);
                const roomSec = m ? `Gr.${m[0]}-${s.section_name||'?'}` : s.section_name || '—';
                const displayDate = rsFormatDate(rsNextOccurrenceDate(s));
                const statusBadge = s._status === 'now'
                    ? `<span class="in-use-badge">Ongoing</span>`
                    : s._status === 'today'
                    ? `<span style="display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:rgba(59,130,246,.1);color:#3b82f6;">Today</span>`
                    : s._status === 'upcoming'
                    ? `<span style="display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:rgba(245,158,11,.1);color:#d97706;">Upcoming</span>`
                    : `<span style="display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:#f3f4f6;color:#6b7280;">Upcoming</span>`;
                return `<tr>
                    <td><strong>${roomSec}</strong></td>
                    <td>${s.subject||'—'}</td>
                    <td>${rsTeacherName(s)}</td>
                    <td>${displayDate}</td>
                    <td>${rsFormatTime(s.start_time)} – ${rsFormatTime(s.end_time)}</td>
                    <td>${statusBadge}</td>
                </tr>`;
            }).join('');
        }

        function rsSetGrade(btn, grade) {
            rsCurrentGrade = grade;
            document.querySelectorAll('.rs-grade-btn').forEach(b => b.classList.toggle('active', b.dataset.grade === grade));
            rsRenderSections();
        }

        /* Live clock */
        function rsUpdateClock() {
            const el = document.getElementById('jhLiveClock');
            if (!el) return;
            el.textContent = new Date().toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
        }

        /* ─── Fetch schedule data from server and re-render everything ─── */
        function rsRefreshData(isInitial) {
            fetch(JH_SCHEDULE_API, { headers: { 'Accept':'application/json' } })
                .then(r => r.json())
                .then(res => {
                    const all = Array.isArray(res) ? res : (res.data || []);
                    rsAllSchedules = all.filter(s => s.admin_approved);
                    rsRenderSections();
                    rsRenderUpcomingPanel();
                })
                .catch(() => {
                    if (isInitial) {
                        const tbody = document.getElementById('sections-tbody');
                        if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">Could not load schedule data.</td></tr>`;
                        const up = document.getElementById('jhUpcomingTbody');
                        if (up) up.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">Could not load schedule data.</td></tr>`;
                    }
                });
        }

        window.addEventListener('scheduleRemoved', function (e) {
            const id = Number(e.detail && e.detail.id);
            if (!Number.isFinite(id)) return;
            rsAllSchedules = rsAllSchedules.filter(function (s) { return Number(s.id) !== id; });
            rsRenderSections();
            rsRenderUpcomingPanel();
        });

        document.addEventListener('DOMContentLoaded', function () {
            rsUpdateClock();
            setInterval(rsUpdateClock, 1000);

            // Initial data load
            rsRefreshData(true);

            // Re-render live status every minute (time-based In Use Now transitions)
            setInterval(() => {
                rsRenderSections();
                rsRenderUpcomingPanel();
            }, 60 * 1000);

            // Re-fetch schedule data from server every 5 minutes (picks up new/changed schedules)
            setInterval(() => rsRefreshData(false), 5 * 60 * 1000);

            document.getElementById('editRoomForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const id = document.getElementById('editRoomId').value;
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const payload = {
                    _method: 'PUT',
                    capacity: document.getElementById('editCapacity').value,
                    status: document.getElementById('editStatus').value,
                };
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/admin/rooms/' + id;
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden'; csrfInput.name = '_token'; csrfInput.value = token;
                form.appendChild(csrfInput);
                Object.entries(payload).forEach(([key, val]) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = key; inp.value = val;
                    form.appendChild(inp);
                });
                document.body.appendChild(form);
                form.submit();
            });

            document.getElementById('editRoomModal').addEventListener('click', function (e) {
                if (e.target === this) closeEditRoomModal();
            });
            document.getElementById('addRoomModal').addEventListener('click', function (e) {
                if (e.target === this) closeAddRoomModal();
            });
        });

        function openEditRoomFromEl(el) {
            const d = el.dataset;
            openEditRoomModal(d.id, d.capacity, d.status);
        }

        function openEditRoomModal(id, capacity, status) {
            document.getElementById('editRoomId').value = id;
            document.getElementById('editCapacity').value = capacity;
            document.getElementById('editStatus').value = status;
            document.getElementById('editRoomModal').style.display = 'flex';
        }

        function closeEditRoomModal() { document.getElementById('editRoomModal').style.display = 'none'; }
        function openAddRoomModal()   { document.getElementById('addRoomForm').reset(); document.getElementById('addRoomModal').style.display = 'flex'; }
        function closeAddRoomModal()  { document.getElementById('addRoomModal').style.display = 'none'; }
    </script>

    <!-- Floating Edit Room Modal -->
    <div id="editRoomModal" class="floating-modal-overlay" style="display:none;">
        <div class="floating-modal">
            <div class="floating-modal-header">
                <h2>Edit Room</h2>
                <button class="modal-close-btn" onclick="closeEditRoomModal()" aria-label="Close">&times;</button>
            </div>
            <form id="editRoomForm">
                <input type="hidden" id="editRoomId">
                <div class="modal-form-group">
                    <label>Capacity (Students) <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="editCapacity" min="1" max="200" required>
                </div>
                <div class="modal-form-group">
                    <label>Status <span style="color:#ef4444;">*</span></label>
                    <select id="editStatus" required>
                        <option value="available">Available</option>
                        <option value="in-use">In Use</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="modal-btn-save">Update Room</button>
                    <button type="button" class="modal-btn-cancel" onclick="closeEditRoomModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<!-- Floating Add Room Modal -->
<div id="addRoomModal" class="floating-modal-overlay" style="display:none;">
    <div class="floating-modal">
        <div class="floating-modal-header">
            <h2>Add New Room</h2>
            <button class="modal-close-btn" onclick="closeAddRoomModal()" aria-label="Close">&times;</button>
        </div>
        <form id="addRoomForm" method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf
            <div class="modal-form-group">
                <label>Capacity (Students) <span style="color:#ef4444;">*</span></label>
                <input type="number" name="capacity" min="1" max="200" value="30" required>
            </div>
            <div class="modal-form-group">
                <label>Status <span style="color:#ef4444;">*</span></label>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="in-use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="submit" class="modal-btn-save">Create Room</button>
                <button type="button" class="modal-btn-cancel" onclick="closeAddRoomModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@endsection
