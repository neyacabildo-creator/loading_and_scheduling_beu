/**
 * Class schedule conflict DSS — shared by JH & GS admin class-schedule pages.
 */
(function (global) {
    'use strict';

    const POLICY_MSG = 'Approving or saving may violate scheduling policy because of a time conflict for this teacher. Continue anyway?';

    function timeToMins(t) {
        if (!t) return 0;
        const p = String(t).substring(0, 5).split(':');
        return parseInt(p[0], 10) * 60 + parseInt(p[1] || '0', 10);
    }

    function formatMins(mins) {
        const h = Math.floor(mins / 60);
        const m = mins % 60;
        const ap = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return h12 + ':' + String(m).padStart(2, '0') + ' ' + ap;
    }

    function formatTimeStr(t) {
        if (!t) return '';
        const m = timeToMins(t);
        return formatMins(m);
    }

    function timesOverlap(aStart, aEnd, bStart, bEnd) {
        const aS = timeToMins(aStart);
        const aE = timeToMins(aEnd);
        const bS = timeToMins(bStart);
        const bE = timeToMins(bEnd);
        if (!aS || !aE || !bS || !bE) return false;
        return aS < bE && bS < aE;
    }

    function sameDay(a, b) {
        return (a || '').toLowerCase() === (b || '').toLowerCase();
    }

    function teacherName(s) {
        if (!s) return 'Teacher';
        if (s.faculty && s.faculty.name) return String(s.faculty.name).trim();
        const fn = s.faculty ? ((s.faculty.first_name || '') + ' ' + (s.faculty.last_name || '')).trim() : '';
        return fn || 'Teacher';
    }

    function isActiveSchedule(s) {
        if (!s || !s.id) return false;
        const st = (s.status || '').toLowerCase();
        return !['rejected', 'deleted'].includes(st);
    }

    function hasSectionRoom(s) {
        if (!s) return false;
        if (s.room_id) return true;
        const grade = String(s.grade_level || '').trim();
        const section = String(s.section_name || '').trim();
        return grade !== '' || section !== '';
    }

    function sectionSlotKey(s) {
        const date = s.schedule_date ? String(s.schedule_date).substring(0, 10) : '';
        const start = timeToMins(s.start_time);
        return String(s.grade_level || '') + '|' + String(s.section_name || '') + '|' +
            String(s.day_of_week || '') + '|' + start + '|' + date;
    }

    function sectionRoomLabel(s) {
        if (s.room && s.room.room_number) {
            return 'Room ' + s.room.room_number;
        }
        if (s.room_label && s.room_label !== '—' && !/^room\s*#/i.test(String(s.room_label))) {
            return String(s.room_label);
        }
        const grade = String(s.grade_level || '').trim();
        const section = String(s.section_name || '').trim();
        if (grade && section) return grade + ' – ' + section;
        return grade || section || '—';
    }

    /**
     * @param {Array<object>} schedules
     * @returns {{ conflictIds: Set<number>, missingDateIds: Set<number>, missingRoomIds: Set<number>, summaries: Array<{label: string, scheduleIds: number[]}> }}
     */
    function analyzeSchedules(schedules) {
        const active = (schedules || []).filter(isActiveSchedule);
        const conflictIds = new Set();
        const missingDateIds = new Set();
        const missingRoomIds = new Set();
        const summaries = [];
        const summaryKeys = new Set();

        active.forEach(function (s) {
            if (!s.schedule_date) missingDateIds.add(s.id);
            if (!hasSectionRoom(s)) missingRoomIds.add(s.id);
        });

        const bySectionSlot = {};
        active.forEach(function (s) {
            const key = sectionSlotKey(s);
            if (!String(s.grade_level || '').trim() || !String(s.section_name || '').trim()) return;
            bySectionSlot[key] = bySectionSlot[key] || [];
            bySectionSlot[key].push(s);
        });
        Object.keys(bySectionSlot).forEach(function (key) {
            const arr = bySectionSlot[key];
            if (arr.length < 2) return;
            arr.forEach(function (s) { conflictIds.add(s.id); });
            const sample = arr[0];
            const dateStr = sample.schedule_date
                ? String(sample.schedule_date).substring(0, 10)
                : 'this date';
            const startM = timeToMins(sample.start_time);
            const endM = timeToMins(sample.end_time);
            const sk = 'section|' + key;
            if (!summaryKeys.has(sk)) {
                summaryKeys.add(sk);
                summaries.push({
                    label: 'Has already a schedule for ' + sectionRoomLabel(sample) + ' on ' +
                        (sample.day_of_week || '') + ', ' + dateStr + ' at ' +
                        formatMins(startM) + '–' + formatMins(endM),
                    scheduleIds: arr.map(function (x) { return x.id; }),
                });
            }
        });

        const byFaculty = {};
        active.forEach(function (s) {
            if (!s.faculty_id) return;
            const fid = String(s.faculty_id);
            byFaculty[fid] = byFaculty[fid] || [];
            byFaculty[fid].push(s);
        });

        Object.keys(byFaculty).forEach(function (fid) {
            const arr = byFaculty[fid];
            for (let i = 0; i < arr.length; i++) {
                for (let j = i + 1; j < arr.length; j++) {
                    const a = arr[i];
                    const b = arr[j];
                    if (!sameDay(a.day_of_week, b.day_of_week)) continue;
                    if (!timesOverlap(a.start_time, a.end_time, b.start_time, b.end_time)) continue;

                    conflictIds.add(a.id);
                    conflictIds.add(b.id);

                    const startM = Math.min(timeToMins(a.start_time), timeToMins(b.start_time));
                    const endM = Math.max(timeToMins(a.end_time), timeToMins(b.end_time));
                    const sk = fid + '|' + (a.day_of_week || '') + '|' + startM + '|' + endM;
                    if (!summaryKeys.has(sk)) {
                        summaryKeys.add(sk);
                        summaries.push({
                            label: teacherName(a) + ' double-booked ' + (a.day_of_week || '') + ' ' +
                                formatMins(startM) + '–' + formatMins(endM),
                            scheduleIds: [a.id, b.id],
                        });
                    }
                }
            }
        });

        return {
            conflictIds: conflictIds,
            missingDateIds: missingDateIds,
            missingRoomIds: missingRoomIds,
            summaries: summaries,
            totalConflicts: conflictIds.size,
        };
    }

    function updateBanner(prefix, analysis) {
        const banner = document.getElementById(prefix + 'ConflictBanner');
        if (!banner) return;

        const count = analysis.summaries.length;
        const pairCount = analysis.conflictIds.size;
        const show = count > 0 || analysis.missingDateIds.size > 0 || analysis.missingRoomIds.size > 0;

        banner.style.display = show ? 'block' : 'none';

        const countEl = document.getElementById(prefix + 'ConflictCount');
        const leadEl = document.getElementById(prefix + 'ConflictLead');
        const listEl = document.getElementById(prefix + 'ConflictList');

        if (countEl) {
            countEl.textContent = count > 0 ? String(count) : '0';
        }
        if (leadEl) {
            if (count > 0) {
                leadEl.textContent = count === 1
                    ? '1 scheduling conflict (' + pairCount + ' entr' + (pairCount === 1 ? 'y' : 'ies') + ' affected)'
                    : count + ' scheduling conflicts (' + pairCount + ' entries affected)';
            } else if (analysis.missingDateIds.size || analysis.missingRoomIds.size) {
                leadEl.textContent = 'No time overlaps detected; some rows are missing date or room (see legend).';
            } else {
                leadEl.textContent = 'No conflicts detected for active schedules.';
            }
        }
        if (listEl) {
            if (count === 0) {
                listEl.innerHTML = '';
                listEl.style.display = 'none';
            } else {
                const max = 6;
                const items = analysis.summaries.slice(0, max).map(function (c) {
                    return '<li>' + esc(c.label) + '</li>';
                }).join('');
                const more = count > max
                    ? '<li><em>+' + (count - max) + ' more conflict' + (count - max === 1 ? '' : 's') + '</em></li>'
                    : '';
                listEl.innerHTML = '<ul class="cs-dss-conflict-list">' + items + more + '</ul>';
                listEl.style.display = 'block';
            }
        }
    }

    function esc(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function rowClass(schedule, analysis) {
        if (!schedule || !analysis) return '';
        if (analysis.conflictIds.has(schedule.id)) return 'cs-row-conflict';
        if (analysis.missingDateIds.has(schedule.id) || analysis.missingRoomIds.has(schedule.id)) {
            return 'cs-row-missing';
        }
        return '';
    }

    function rowBadge(schedule, analysis) {
        if (!schedule || !analysis) return '';
        if (analysis.conflictIds.has(schedule.id)) {
            return '<span class="cs-dss-pill cs-dss-pill-conflict" title="Time overlap with another class">Conflict</span>';
        }
        if (analysis.missingRoomIds.has(schedule.id)) {
            return '<span class="cs-dss-pill cs-dss-pill-warn" title="Grade/section room not set">No room</span>';
        }
        if (analysis.missingDateIds.has(schedule.id)) {
            return '<span class="cs-dss-pill cs-dss-pill-warn" title="Schedule date not set">No date</span>';
        }
        return '';
    }

    /**
     * Does schedule overlap any other on same faculty + day?
     */
    function hasTimeConflict(schedule, allSchedules, excludeId) {
        if (!schedule || !schedule.faculty_id) return false;
        const others = (allSchedules || []).filter(function (s) {
            return isActiveSchedule(s) && s.id !== excludeId && s.faculty_id === schedule.faculty_id;
        });
        return others.some(function (other) {
            return sameDay(schedule.day_of_week, other.day_of_week) &&
                timesOverlap(schedule.start_time, schedule.end_time, other.start_time, other.end_time);
        });
    }

    function confirmPolicyViolation(customMessage) {
        return global.confirm(customMessage || POLICY_MSG);
    }

    function shouldProceedWithApprove(schedule, allSchedules) {
        if (!schedule) return true;
        if (!hasTimeConflict(schedule, allSchedules, schedule.id)) return true;
        return confirmPolicyViolation();
    }

    function shouldProceedWithSave(draft, allSchedules, scheduleId) {
        if (!draft || !draft.faculty_id) return true;
        const probe = {
            id: scheduleId,
            faculty_id: parseInt(draft.faculty_id, 10),
            day_of_week: draft.day_of_week,
            start_time: draft.start_time,
            end_time: draft.end_time,
        };
        if (!hasTimeConflict(probe, allSchedules, scheduleId)) return true;
        return confirmPolicyViolation();
    }

    function refreshFromCache(cache, prefix) {
        const all = Object.values(cache || {}).filter(function (s) { return s && s.id; });
        const analysis = analyzeSchedules(all);
        if (prefix) updateBanner(prefix, analysis);
        return { all: all, analysis: analysis };
    }

    global.AdminScheduleDss = {
        timeToMins: timeToMins,
        analyzeSchedules: analyzeSchedules,
        updateBanner: updateBanner,
        rowClass: rowClass,
        rowBadge: rowBadge,
        hasTimeConflict: hasTimeConflict,
        confirmPolicyViolation: confirmPolicyViolation,
        shouldProceedWithApprove: shouldProceedWithApprove,
        shouldProceedWithSave: shouldProceedWithSave,
        refreshFromCache: refreshFromCache,
        POLICY_MSG: POLICY_MSG,
    };
})(typeof window !== 'undefined' ? window : globalThis);
