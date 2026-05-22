/**
 * Admin dashboard weekly timetable (JH + GS).
 * Expects window.__DASH_TIMETABLE_CONFIG__ set before this script loads.
 */
(function () {
    'use strict';

    const cfg = window.__DASH_TIMETABLE_CONFIG__ || {};
    const prefix = cfg.prefix || 'jh';
    const schoolFilter = cfg.school || (prefix === 'gs' ? 'GS' : 'JH');
    const slots = cfg.slots || [];
    const sectionsMap = cfg.sections || {};
    const grades = cfg.grades || (prefix === 'gs' ? ['1', '2', '3', '4', '5', '6'] : ['7', '8', '9', '10']);
    const apiUrl = cfg.apiUrl || '';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    let currentDay = 'Monday';
    let currentGrade = grades[0] || '7';
    let allSchedules = [];

    function normalizeSchedule(s) {
        if (!s) return null;
        const faculty = s.faculty || {};
        const name = faculty.name
            || [faculty.first_name, faculty.last_name].filter(Boolean).join(' ').trim()
            || s.teacher_name
            || '';
        return Object.assign({}, s, {
            teacher_name: name,
            faculty: Object.assign({}, faculty, { name: name || 'Unknown' }),
        });
    }

    function matchesTeacherFilter(s, filter) {
        if (!filter) return true;
        const haystack = [
            s.faculty?.name,
            s.teacher_name,
            [s.faculty?.first_name, s.faculty?.last_name].filter(Boolean).join(' '),
            s.subject,
        ].map(v => String(v || '').toLowerCase().trim()).filter(Boolean).join(' ');
        return haystack.includes(filter);
    }

    function isApproved(s) {
        if (!s) return false;
        const ok = s.admin_approved === true || s.admin_approved === 1 || s.admin_approved === '1';
        if (!ok) return false;
        const st = String(s.status || '').toLowerCase();
        if (!st || st === 'active' || st === 'approved') return true;
        return st !== 'pending' && st !== 'rejected' && st !== 'deleted';
    }

    function setSchedules(rows) {
        allSchedules = (rows || [])
            .map(normalizeSchedule)
            .filter(Boolean)
            .filter(isApproved)
            .filter(s => !schoolFilter || !s.school || s.school === schoolFilter);
    }

    function removeById(scheduleId) {
        const id = Number(scheduleId);
        if (!Number.isFinite(id)) return;
        allSchedules = allSchedules.filter(s => Number(s.id) !== id);
        renderTimetable();
    }

    function timeToMins(t) {
        if (!t) return 0;
        const p = String(t).substring(0, 5).split(':');
        return parseInt(p[0], 10) * 60 + parseInt(p[1] || 0, 10);
    }

    function headId() { return prefix + 'DashTimetableHead'; }
    function bodyId() { return prefix + 'DashTimetableBody'; }
    function filterId() { return prefix + 'DashTTFilter'; }
    function bannerId() { return prefix + 'DashConflictBanner'; }
    function dateId() { return prefix + 'DashTTDateLabel'; }

    function getWeekDate(dayName) {
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const today = new Date();
        const diff = dayNames.indexOf(dayName) - today.getDay();
        const target = new Date(today);
        target.setDate(today.getDate() + diff);
        return target.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    }

    function updateDateLabel() {
        const el = document.getElementById(dateId());
        if (el) el.textContent = getWeekDate(currentDay);
    }

    function renderTimetable() {
        const thead = document.getElementById(headId());
        const tbody = document.getElementById(bodyId());
        if (!thead || !tbody) return;

        const filter = (document.getElementById(filterId())?.value || '').toLowerCase().trim();
        const day = currentDay;
        let schedules = allSchedules.filter(s => (s.day_of_week || '').toLowerCase() === day.toLowerCase());
        if (filter) {
            schedules = schedules.filter(s => matchesTeacherFilter(s, filter));
        }
        schedules = schedules.filter(s => {
            const gl = String(s.grade_level || '').toLowerCase();
            return gl.includes('grade ' + currentGrade) || gl === currentGrade;
        });

        const rawSections = [...new Set(schedules.map(s => s.section_name || s.grade_section || '').filter(Boolean))];
        const fixedSections = sectionsMap[currentGrade] || [];
        const sections = [...fixedSections];
        rawSections.forEach(rs => {
            if (!sections.some(fs => fs.toLowerCase() === rs.toLowerCase())) sections.push(rs);
        });

        const conflictIds = new Set();
        const byFaculty = {};
        schedules.forEach(s => {
            if (s.faculty_id) (byFaculty[s.faculty_id] = byFaculty[s.faculty_id] || []).push(s);
        });
        Object.values(byFaculty).forEach(arr => {
            for (let i = 0; i < arr.length; i++) {
                for (let j = i + 1; j < arr.length; j++) {
                    const a = arr[i], b = arr[j];
                    if (timeToMins(a.start_time) < timeToMins(b.end_time) && timeToMins(b.start_time) < timeToMins(a.end_time)) {
                        conflictIds.add(a.id);
                        conflictIds.add(b.id);
                    }
                }
            }
        });

        const banner = document.getElementById(bannerId());
        if (banner) banner.style.display = conflictIds.size > 0 ? 'block' : 'none';

        if (!sections.length) {
            thead.innerHTML = '<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th><th style="padding:.6rem;border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;">' + day + '</th></tr>';
            tbody.innerHTML = '<tr><td colspan="2" style="text-align:center;padding:2rem;color:var(--text-secondary);">No sections for Grade ' + currentGrade + '.</td></tr>';
            return;
        }

        let headHtml = '<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th>';
        sections.forEach(sec => {
            headHtml += '<th style="padding:.6rem;background:linear-gradient(135deg,rgba(45,122,80,.12),rgba(45,122,80,.04));border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;min-width:110px;"><span style="font-size:.7rem;color:var(--text-secondary);">Grade ' + currentGrade + '</span><br>' + sec + '</th>';
        });
        thead.innerHTML = headHtml + '</tr>';

        let html = '';
        slots.forEach(slot => {
            if (slot.isBreak) {
                html += '<tr><td style="padding:.4rem;border:1px solid var(--border-color);text-align:center;font-size:.68rem;color:#92400e;background:rgba(245,158,11,.07);font-weight:700;">' + slot.label + '</td><td colspan="' + sections.length + '" style="border:1px solid var(--border-color);background:rgba(245,158,11,.07);text-align:center;font-size:.72rem;color:#92400e;font-weight:700;">&#10022; ' + slot.label + ' BREAK &#10022;</td></tr>';
                return;
            }
            const slotS = timeToMins(slot.start);
            const slotE = timeToMins(slot.end);
            let row = '<tr><td style="padding:.4rem .5rem;border:1px solid var(--border-color);text-align:center;font-size:.72rem;color:var(--text-secondary);background:var(--bg-tertiary);white-space:pre;font-weight:500;">' + slot.label + '</td>';
            sections.forEach(sec => {
                const cell = schedules.filter(s => {
                    const secS = (s.section_name || s.grade_section || '').toLowerCase().trim();
                    const secC = sec.toLowerCase().trim();
                    return (secS === secC || secS.includes(secC) || secC.includes(secS)) && timeToMins(s.start_time) < slotE && timeToMins(s.end_time) > slotS;
                });
                if (cell.length) {
                    const pills = cell.map(s => {
                        const name = (s.faculty?.name || 'Unknown').replace(/</g, '&lt;');
                        const subj = (s.subject || '').replace(/</g, '&lt;');
                        const isConflict = conflictIds.has(s.id);
                        const bc = isConflict ? '#c83232' : 'var(--green-primary)';
                        const bg = isConflict ? 'rgba(200,50,50,.12)' : 'rgba(45,122,80,.1)';
                        const cf = isConflict ? '<div style="font-size:.62rem;color:#c83232;font-weight:700;">&#9888; CONFLICT</div>' : '';
                        return '<div style="padding:.3rem .4rem;background:' + bg + ';border-left:3px solid ' + bc + ';border-radius:.25rem;margin-bottom:.2rem;font-size:.68rem;"><div style="font-weight:700;color:' + bc + ';">' + name + '</div>' + (subj ? '<div style="color:var(--text-secondary);">' + subj + '</div>' : '') + cf + '</div>';
                    }).join('');
                    row += '<td style="padding:.3rem;border:1px solid var(--border-color);vertical-align:top;">' + pills + '</td>';
                } else {
                    row += '<td style="padding:.3rem;border:1px solid var(--border-color);background:var(--bg-secondary);"></td>';
                }
            });
            html += row + '</tr>';
        });
        tbody.innerHTML = html || '<tr><td colspan="' + (sections.length + 1) + '" style="text-align:center;padding:2rem;color:var(--text-secondary);">No approved classes for this day and grade.</td></tr>';
    }

    function setDay(day) {
        currentDay = day;
        document.querySelectorAll('.' + prefix + 'd-day-btn').forEach(b => b.classList.toggle('active', b.dataset.day === day));
        renderTimetable();
        updateDateLabel();
    }

    function prevDay() {
        const idx = days.indexOf(currentDay);
        setDay(days[(idx - 1 + days.length) % days.length]);
    }

    function nextDay() {
        const idx = days.indexOf(currentDay);
        setDay(days[(idx + 1) % days.length]);
    }

    function setGrade(grade) {
        currentGrade = grade;
        document.querySelectorAll('.' + prefix + 'd-grade-btn').forEach(b => b.classList.toggle('active', b.dataset.grade === grade));
        renderTimetable();
    }

    function loadFromApi() {
        if (!apiUrl) {
            renderTimetable();
            return;
        }
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 20000);
        fetch(apiUrl, {
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(r => (r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status))))
            .then(data => {
                setSchedules(data.data || data || []);
            })
            .catch(err => {
                console.error('Timetable load error:', err);
                if (!allSchedules.length && Array.isArray(cfg.initial)) {
                    setSchedules(cfg.initial);
                }
            })
            .finally(() => {
                clearTimeout(timeoutId);
                renderTimetable();
            });
    }

    function init() {
        setSchedules(cfg.initial || []);
        const todayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][new Date().getDay()];
        if (days.includes(todayName)) currentDay = todayName;
        document.querySelectorAll('.' + prefix + 'd-day-btn').forEach(b => {
            if (b.dataset.day === todayName) b.classList.add('today-marker');
            b.classList.toggle('active', b.dataset.day === currentDay);
        });
        document.querySelectorAll('.' + prefix + 'd-grade-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.grade === currentGrade);
        });
        updateDateLabel();
        renderTimetable();
        loadFromApi();
    }

    window.__dashTimetable = {
        render: renderTimetable,
        reload: loadFromApi,
        setSchedules,
        removeById,
        getAll: function () { return allSchedules.slice(); },
    };

    window.addEventListener('scheduleRemoved', function (e) {
        if (e.detail && e.detail.id != null) removeById(e.detail.id);
    });

    if (prefix === 'jh') {
        window.jhDashTTSetDay = function (btn, day) { setDay(day); };
        window.jhDashTTPrevDay = prevDay;
        window.jhDashTTNextDay = nextDay;
        window.jhDashSetGrade = function (btn, grade) { setGrade(grade); };
        window.jhDashRenderTimetable = renderTimetable;
        window.loadSchedules = loadFromApi;
    } else {
        window.gsDashTTSetDay = function (btn, day) { setDay(day); };
        window.gsDashTTPrevDay = prevDay;
        window.gsDashTTNextDay = nextDay;
        window.gsDashSetGrade = function (btn, grade) { setGrade(grade); };
        window.gsRenderTimetable = renderTimetable;
        window.loadSchedules = loadFromApi;
    }

    init();
})();
