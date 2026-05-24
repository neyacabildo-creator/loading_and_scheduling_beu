/**
 * Pre-save DSS warnings for faculty load create/update.
 * window.ADMIN_FACULTY_LOAD_DSS = { checkUrl, csrfToken }
 */
(function () {
    const cfg = window.ADMIN_FACULTY_LOAD_DSS || {};
    if (!cfg.checkUrl) return;

    async function fetchWarnings(payload) {
        const res = await fetch(cfg.checkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        });
        if (!res.ok) return [];
        const data = await res.json();
        return data.warnings || [];
    }

    window.AdminFacultyLoadDss = {
        async confirmBeforeSave(payload) {
            const warnings = await fetchWarnings(payload);
            if (!warnings.length) return true;
            const text = 'Scheduling advisory:\n\n• ' + warnings.join('\n• ')
                + '\n\nSave anyway?';
            return window.confirm(text);
        },
        showPostSaveWarnings(warnings) {
            if (!warnings || !warnings.length) return;
            const el = document.createElement('div');
            el.style.cssText = 'position:fixed;top:1rem;right:1rem;max-width:360px;padding:1rem 1.25rem;background:#fffbeb;border:1px solid #fcd34d;border-radius:.5rem;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:10000;font-size:.85rem;color:#92400e;';
            el.innerHTML = '<strong>Saved with advisories</strong><ul style="margin:.5rem 0 0;padding-left:1.2rem;">'
                + warnings.map(w => '<li>' + w + '</li>').join('') + '</ul>';
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 8000);
        },
    };
})();
