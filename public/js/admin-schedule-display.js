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

    function adminScheduleDate(s) {
        if (!s) return '—';
        if (s.display_date && s.display_date !== '—') return esc(s.display_date);
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
        if (s.room_label) return esc(s.room_label);
        if (s.room && s.room.room_number) return esc('Room ' + s.room.room_number);
        return esc(adminScheduleGradeSection(s));
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
