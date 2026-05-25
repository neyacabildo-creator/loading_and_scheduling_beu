/**
 * Edit Schedule modal: keep schedule_date aligned with day_of_week (GS + JH admin).
 */
(function (global) {
    const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    function weekdayFromIso(iso) {
        if (!iso) {
            return '';
        }
        const d = new Date(iso + 'T12:00:00');
        return WEEKDAYS[d.getDay()] || '';
    }

    function isoFromDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function dateForDayNearAnchor(dayName, anchorIso) {
        const idx = WEEKDAYS.indexOf(dayName);
        if (idx < 0) {
            return '';
        }
        const anchor = anchorIso ? new Date(anchorIso + 'T12:00:00') : new Date();
        const diff = idx - anchor.getDay();
        const result = new Date(anchor);
        result.setDate(anchor.getDate() + diff);
        return isoFromDate(result);
    }

    function bind(dayId, dateId) {
        const dayEl = document.getElementById(dayId);
        const dateEl = document.getElementById(dateId);
        if (!dayEl || !dateEl) {
            return;
        }

        dayEl.addEventListener('change', function () {
            const anchor = dateEl.value || dateEl.dataset.editOriginalDate || isoFromDate(new Date());
            const next = dateForDayNearAnchor(dayEl.value, anchor);
            if (next) {
                dateEl.value = next;
            }
        });
    }

    function snapshot(dayId, dateId) {
        const dayEl = document.getElementById(dayId);
        const dateEl = document.getElementById(dateId);
        if (!dayEl || !dateEl) {
            return;
        }
        dayEl.dataset.editOriginalDay = dayEl.value || '';
        dateEl.dataset.editOriginalDate = dateEl.value || '';
    }

    function validateBeforeSave(dayId, dateId) {
        const dayEl = document.getElementById(dayId);
        const dateEl = document.getElementById(dateId);
        const day = (dayEl && dayEl.value) || '';
        const date = (dateEl && dateEl.value) || '';
        const origDay = (dayEl && dayEl.dataset.editOriginalDay) || '';
        const origDate = (dateEl && dateEl.dataset.editOriginalDate) || '';

        if (!day || !date) {
            return { ok: false, message: 'Please set both day and schedule date before saving.' };
        }

        if (day !== origDay && date === origDate) {
            return {
                ok: false,
                message: 'You changed the day but the schedule date still matches the previous day. Change the day again to update the date, or pick a date that falls on ' + day + '.',
            };
        }

        const actual = weekdayFromIso(date);
        if (actual !== day) {
            return {
                ok: false,
                message: 'Schedule date must fall on ' + day + ' (selected date is ' + (actual || 'invalid') + ').',
            };
        }

        return { ok: true };
    }

    global.AdminScheduleEditDayDate = {
        bind: bind,
        snapshot: snapshot,
        validateBeforeSave: validateBeforeSave,
        dateForDayNearAnchor: dateForDayNearAnchor,
        weekdayFromIso: weekdayFromIso,
    };
})(typeof window !== 'undefined' ? window : this);
