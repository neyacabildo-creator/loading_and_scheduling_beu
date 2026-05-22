/**
 * Instant schedule removal across admin UI (JH + GS).
 * Fires scheduleRemoved; updates dashboard weekly timetable without full reload.
 */
(function () {
    'use strict';

    function parseId(scheduleId) {
        const id = Number(scheduleId);
        return Number.isFinite(id) ? id : null;
    }

    function removeTableRows(scheduleId) {
        document.querySelectorAll('tr[data-schedule-id="' + scheduleId + '"]').forEach(function (tr) {
            tr.style.transition = 'opacity 0.15s ease';
            tr.style.opacity = '0';
            setTimeout(function () { tr.remove(); }, 150);
        });
    }

    function checkApprovedEmpty() {
        ['approvedTableBody', 'jhApprovedTableBody'].forEach(function (id) {
            const tbody = document.getElementById(id);
            if (!tbody) return;
            if (!tbody.querySelector('tr[data-schedule-id]')) {
                const cols = tbody.closest('table')?.querySelectorAll('thead th').length || 9;
                tbody.innerHTML = '<tr><td colspan="' + cols + '" style="text-align:center;padding:2rem;color:var(--text-secondary);">No approved schedules</td></tr>';
            }
        });
    }

    function broadcastRemoved(scheduleId) {
        window.dispatchEvent(new CustomEvent('scheduleRemoved', { detail: { id: scheduleId } }));
    }

    /**
     * Remove schedule from DOM + weekly views immediately (DB delete runs separately).
     */
    window.adminRemoveScheduleInstant = function (scheduleId) {
        const id = parseId(scheduleId);
        if (id === null) return;

        removeTableRows(id);
        setTimeout(checkApprovedEmpty, 160);

        if (window.jhScheduleCache) delete window.jhScheduleCache[id];
        if (window.gsScheduleCache) delete window.gsScheduleCache[id];

        if (Array.isArray(window.jhTimetableData)) {
            window.jhTimetableData = window.jhTimetableData.filter(function (s) { return Number(s.id) !== id; });
            if (typeof window.jhRenderTimetable === 'function') window.jhRenderTimetable();
        }
        if (Array.isArray(window.gsTimetableData)) {
            window.gsTimetableData = window.gsTimetableData.filter(function (s) { return Number(s.id) !== id; });
            if (typeof window.gsRenderTimetable === 'function') window.gsRenderTimetable();
        }

        broadcastRemoved(id);
    };

    window.adminScheduleDeleteApi = function (scheduleId, options) {
        const id = parseId(scheduleId);
        if (id === null) return Promise.reject(new Error('Invalid schedule id'));

        const url = (options && options.url) || ('/api/admin/schedules/' + id);
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const reason = (options && options.reason) || 'Deleted by administrator';

        return fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ reason: reason }),
        }).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (text) {
                    throw new Error('HTTP ' + response.status + (text ? ': ' + text : ''));
                });
            }
            return response.json();
        });
    };

    /**
     * Confirm → instant UI removal → DELETE in background.
     */
    window.adminQuickDeleteSchedule = function (scheduleId, btn, options) {
        const opts = options || {};
        const confirmMsg = opts.confirmMessage || 'Permanently delete this approved schedule?';
        if (!confirm(confirmMsg)) return;

        window.adminRemoveScheduleInstant(scheduleId);

        return window.adminScheduleDeleteApi(scheduleId, opts).then(function (data) {
            if (data && data.success === false) {
                throw new Error(data.message || 'Failed to delete schedule');
            }
            return data;
        }).catch(function (err) {
            alert('Error deleting schedule: ' + err.message);
            if (typeof opts.onRollback === 'function') opts.onRollback();
            else if (typeof window.loadApprovedSchedules === 'function') window.loadApprovedSchedules();
            else if (typeof window.gsLoadApprovedSchedules === 'function') window.gsLoadApprovedSchedules();
            if (window.__dashTimetable && typeof window.__dashTimetable.reload === 'function') {
                window.__dashTimetable.reload();
            }
            throw err;
        });
    };
})();
