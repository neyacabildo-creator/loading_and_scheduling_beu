/**
 * Shared formatting for admin pending/approved schedule tables (JH + GS).
 */
(function (global) {
    function esc(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function adminScheduleGradeSection(s) {
        if (!s) return '—';
        if (s.grade_section_label) return esc(s.grade_section_label);
        if (s.grade) return esc(s.grade);
        const grade = s.grade_level || '';
        const section = s.section_name || '';
        if (grade && section) return esc(grade + ' – ' + section);
        if (grade) return esc(grade);
        if (section) return esc(section);
        if (s.grade_section) return esc(s.grade_section);
        return '—';
    }

    var KINDER_GRADES = ['Kinder 2', 'Kinder 1', 'Nursery'];
    var KINDER_WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    function inferredKinderDate(dayOfWeek) {
        var day = String(dayOfWeek || '').trim();
        var idx = KINDER_WEEKDAYS.indexOf(day);
        if (idx < 0) return '';
        var now = new Date();
        var monday = new Date(now);
        var dow = monday.getDay();
        var diff = dow === 0 ? -6 : 1 - dow;
        monday.setDate(monday.getDate() + diff);
        var target = new Date(monday);
        target.setDate(monday.getDate() + idx);
        var m = String(target.getMonth() + 1).padStart(2, '0');
        var d = String(target.getDate()).padStart(2, '0');
        var y = target.getFullYear();
        return m + '/' + d + '/' + y;
    }

    function adminScheduleDate(s) {
        if (!s) return '—';
        if (s.display_date && s.display_date !== '—') return esc(s.display_date);
        if (!s.schedule_date && KINDER_GRADES.indexOf(s.grade_level) >= 0) {
            const inferred = inferredKinderDate(s.day_of_week);
            if (inferred) return esc(inferred);
        }
        if (!s.schedule_date) return '—';
        try {
            const raw = String(s.schedule_date).substring(0, 10);
            const parts = raw.split('-');
            if (parts.length === 3) {
                return esc(parts[1] + '/' + parts[2] + '/' + parts[0]);
            }
            return esc(new Date(s.schedule_date).toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric',
            }));
        } catch (e) {
            return '—';
        }
    }

    function adminScheduleRoom(s) {
        if (!s) return '—';
        if (s.room && s.room.room_number) return esc('Room ' + s.room.room_number);
        if (s.room_label && s.room_label !== '—' && !/^room\s*#/i.test(String(s.room_label))) {
            return esc(s.room_label);
        }
        return adminScheduleGradeSection(s);
    }

    function adminScheduleTimeRange(s) {
        const start = s.start_time ? String(s.start_time).substring(0, 5) : (s.time_start ? String(s.time_start).substring(11, 16) : '');
        const end = s.end_time ? String(s.end_time).substring(0, 5) : (s.time_end ? String(s.time_end).substring(11, 16) : '');
        if (start && end) return esc(start + ' – ' + end);
        if (start) return esc(start);
        return '—';
    }

    global.adminScheduleGradeSection = adminScheduleGradeSection;
    global.adminScheduleDate = adminScheduleDate;
    global.adminScheduleRoom = adminScheduleRoom;
    global.adminScheduleTimeRange = adminScheduleTimeRange;
})(typeof window !== 'undefined' ? window : globalThis);
