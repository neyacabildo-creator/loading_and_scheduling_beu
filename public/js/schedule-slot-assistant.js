/**
 * Inline slot DSS for admin schedule forms.
 * Expects: window.SF_SLOT_ASSISTANT = { apiUrl, schoolLevel }
 */
(function () {
    const cfg = window.SF_SLOT_ASSISTANT || {};
    const apiUrl = cfg.apiUrl;
    const schoolLevel = cfg.schoolLevel || 'junior_high';
    if (!apiUrl) return;

    const statusEl = document.getElementById('sfSlotAssistantStatus');
    if (!statusEl) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let debounce = null;

    function slotKeyToTimes(key) {
        const m = String(key || '').match(/(\d{1,2})[_:](\d{2}).*?(\d{1,2})[_:](\d{2})/);
        if (!m) return { start: '', end: '' };
        return {
            start: String(m[1]).padStart(2, '0') + ':' + m[2],
            end: String(m[3]).padStart(2, '0') + ':' + m[4],
        };
    }

    function dayEl() {
        return document.getElementById('sfDay') || document.getElementById('day_of_week');
    }
    function dateEl() {
        return document.getElementById('sfDate') || document.getElementById('schedule_date');
    }

    function currentContext() {
        const day = (dayEl() || {}).value || '';
        const date = (dateEl() || {}).value || '';
        let facultyId = '';
        let start = '';
        let end = '';
        document.querySelectorAll('.sf-teacher').forEach(function (sel) {
            if (!sel.value || facultyId) return;
            facultyId = sel.value;
            const row = sel.closest('tr');
            const sub = sel.closest('.sf-cell')?.querySelector('.sf-subject');
            const key = sub?.name?.match(/slots\[([^\]]+)\]/)?.[1] || '';
            const t = slotKeyToTimes(key);
            start = t.start;
            end = t.end;
        });
        if (!start) {
            const firstRow = document.querySelector('#sf-tbody-weekday tr:not(.break-row), #sf-tbody-tuesday tr:not(.break-row)');
            if (firstRow) {
                const timeCol = firstRow.querySelector('.time-col');
                const text = timeCol ? timeCol.textContent.replace(/\s+/g, ' ').trim() : '';
                const parts = text.split(' ');
                if (parts.length >= 2) {
                    start = parts[0].slice(0, 5);
                    end = parts[1].slice(0, 5);
                }
            }
        }
        return { day, date, facultyId, start, end };
    }

    function render(data) {
        if (!data) {
            statusEl.innerHTML = '';
            return;
        }
        const lines = (data.messages || []).map(function (m) {
            const icon = m.type === 'ok' ? '✓' : '⚠';
            const color = m.type === 'ok' ? '#16a34a' : '#d97706';
            return '<div style="color:' + color + ';font-size:.8rem;margin-top:.2rem;">' + icon + ' ' + (m.text || '') + '</div>';
        }).join('');
        statusEl.innerHTML = lines || '<div style="font-size:.8rem;color:var(--text-secondary);">Select day and period to check official slot rules.</div>';
        window.SF_LAST_SLOT_ASSESS = data;
    }

    function check() {
        const ctx = currentContext();
        if (!ctx.day || !ctx.start || !ctx.end) {
            render(null);
            return;
        }
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                school_level: schoolLevel,
                faculty_id: ctx.facultyId ? parseInt(ctx.facultyId, 10) : null,
                day_of_week: ctx.day,
                start_time: ctx.start,
                end_time: ctx.end,
                schedule_date: ctx.date || null,
            }),
        })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }

    function scheduleCheck() {
        clearTimeout(debounce);
        debounce = setTimeout(check, 280);
    }

    [dayEl(), dateEl()].forEach(function (el) {
        if (el) el.addEventListener('change', scheduleCheck);
    });
    document.addEventListener('change', function (e) {
        if (!e.target) return;
        const id = e.target.id || '';
        if (e.target.classList.contains('sf-teacher') || id === 'sfDay' || id === 'sfDate' || id === 'day_of_week' || id === 'schedule_date') {
            scheduleCheck();
        }
    });

    const origValidate = window.sfValidate;
    window.sfValidate = function () {
        const data = window.SF_LAST_SLOT_ASSESS;
        if (data && data.official_period === false) {
            if (!confirm('Selected time is not an official class period. Save anyway?')) {
                return false;
            }
        }
        return origValidate ? origValidate() : true;
    };

    scheduleCheck();
})();
