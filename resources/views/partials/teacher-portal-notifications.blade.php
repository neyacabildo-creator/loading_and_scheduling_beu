@php
    $notificationsApi = $notificationsApi ?? '/api/teacher/notifications';
@endphp
<style>
    .tp-notif-wrap { position: relative; }
    .tp-notif-btn {
        position: relative;
        padding: 0.5rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-secondary, #6b7280);
        border-radius: 0.5rem;
    }
    .tp-notif-btn:hover { background: var(--bg-primary, #f5f3ed); color: var(--text-primary, #2d3436); }
    .tp-notif-dot {
        position: absolute;
        top: 4px;
        right: 4px;
        min-width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: none;
    }
    .tp-notif-dot.show { display: block; }
    .tp-notif-panel {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 0.5rem);
        width: 320px;
        max-height: 360px;
        overflow-y: auto;
        background: var(--bg-secondary, #fff);
        border: 1px solid var(--border-color, #e8dcc8);
        border-radius: 0.75rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        z-index: 2000;
    }
    .tp-notif-panel.open { display: block; }
    .tp-notif-head {
        padding: 0.75rem 1rem;
        font-weight: 700;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border-color, #e8dcc8);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .tp-notif-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color, #e8dcc8);
        font-size: 0.8rem;
    }
    .tp-notif-item.unread { background: rgba(45,122,80,0.06); }
    .tp-notif-item strong { display: block; margin-bottom: 0.25rem; color: var(--text-primary); }
    .tp-notif-item p { margin: 0; color: var(--text-secondary); line-height: 1.4; }
    .tp-notif-empty { padding: 1.5rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem; }
</style>
<div class="tp-notif-wrap" id="tpNotifWrap">
    <button type="button" class="tp-notif-btn" id="tpNotifBtn" aria-label="Notifications" title="Notifications">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="tp-notif-dot" id="tpNotifDot"></span>
    </button>
    <div class="tp-notif-panel" id="tpNotifPanel" role="dialog" aria-label="Notifications list">
        <div class="tp-notif-head">
            <span>Notifications</span>
            <button type="button" id="tpNotifMarkAll" style="font-size:0.72rem;border:none;background:transparent;color:var(--green-primary);cursor:pointer;font-weight:600;">Mark all read</button>
        </div>
        <div id="tpNotifList"><div class="tp-notif-empty">Loading…</div></div>
    </div>
</div>
<script>
(function() {
    const api = @json($notificationsApi);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const btn = document.getElementById('tpNotifBtn');
    const panel = document.getElementById('tpNotifPanel');
    const dot = document.getElementById('tpNotifDot');
    const list = document.getElementById('tpNotifList');
    const markAll = document.getElementById('tpNotifMarkAll');
    if (!btn || !panel) return;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    async function loadNotifications() {
        try {
            const res = await fetch(api, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            const items = json.data || [];
            const unread = json.unread_count || 0;
            dot.classList.toggle('show', unread > 0);
            if (!items.length) {
                list.innerHTML = '<div class="tp-notif-empty">No notifications yet.</div>';
                return;
            }
            list.innerHTML = items.map(n => `
                <div class="tp-notif-item ${n.is_read ? '' : 'unread'}">
                    <strong>${esc(n.title)}</strong>
                    <p>${esc(n.message)}</p>
                    <small style="color:var(--text-secondary);">${n.created_at ? new Date(n.created_at).toLocaleString() : ''}</small>
                </div>
            `).join('');
        } catch (e) {
            list.innerHTML = '<div class="tp-notif-empty">Could not load notifications.</div>';
        }
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) loadNotifications();
    });

    markAll?.addEventListener('click', async function() {
        try {
            await fetch(api + '/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: '{}'
            });
            loadNotifications();
        } catch (e) {}
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('tpNotifWrap')?.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    loadNotifications();
    setInterval(loadNotifications, 60000);
})();
</script>
