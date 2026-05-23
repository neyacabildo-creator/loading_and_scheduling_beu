@php
    $notificationsApi = $notificationsApi ?? url('/api/admin/notifications');
@endphp
<style>
    .ap-notif-wrap { position: relative; display: inline-flex; align-items: center; flex-shrink: 0; }
    .ap-notif-btn.ap-notif-btn {
        position: relative;
        padding: 0.5rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-secondary, #6b7280);
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .ap-notif-btn.ap-notif-btn:hover { background: var(--bg-primary, #f5f3ed); color: var(--text-primary, #2d3436); }
    .ap-notif-dot {
        position: absolute;
        top: 2px;
        right: 2px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        background: #ef4444;
        color: #fff;
        font-size: 0.6rem;
        font-weight: 700;
        border-radius: 9999px;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .ap-notif-dot.show { display: flex; }
    .ap-notif-panel {
        display: none;
        position: absolute;
        right: 0;
        top: calc(100% + 0.5rem);
        width: 360px;
        max-height: 420px;
        overflow-y: auto;
        background: var(--bg-secondary, #fff);
        border: 1px solid var(--border-color, #e8dcc8);
        border-radius: 0.75rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        z-index: 2000;
    }
    .ap-notif-panel.open { display: block; }
    .ap-notif-head {
        padding: 0.75rem 1rem;
        font-weight: 700;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border-color, #e8dcc8);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        background: var(--bg-secondary, #fff);
        z-index: 1;
    }
    .ap-notif-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color, #e8dcc8);
        font-size: 0.8rem;
        cursor: pointer;
    }
    .ap-notif-item:hover { background: rgba(45,122,80,0.04); }
    .ap-notif-item.unread { background: rgba(45,122,80,0.08); }
    .ap-notif-item strong { display: block; margin-bottom: 0.25rem; color: var(--text-primary); }
    .ap-notif-item p { margin: 0; color: var(--text-secondary); line-height: 1.4; }
    .ap-notif-type {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--green-primary, #2d7a50);
        margin-bottom: 0.2rem;
    }
    .ap-notif-empty { padding: 1.5rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem; }
</style>
<div class="ap-notif-wrap" id="apNotifWrap">
    <button type="button" class="ap-notif-btn header-btn" id="apNotifBtn" aria-label="Notifications" title="Notifications">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="ap-notif-dot" id="apNotifDot"></span>
    </button>
    <div class="ap-notif-panel" id="apNotifPanel" role="dialog" aria-label="Notifications list">
        <div class="ap-notif-head">
            <span>Notifications</span>
            <button type="button" id="apNotifMarkAll" style="font-size:0.72rem;border:none;background:transparent;color:var(--green-primary);cursor:pointer;font-weight:600;">Mark all read</button>
        </div>
        <div id="apNotifList"><div class="ap-notif-empty">Loading…</div></div>
    </div>
</div>
<script>
(function() {
    const api = @json($notificationsApi);
    const allRequestsUrl = @json($allRequestsUrl ?? '');
    const permissionRequestsUrl = @json($permissionRequestsUrl ?? '');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const btn = document.getElementById('apNotifBtn');
    const panel = document.getElementById('apNotifPanel');
    const dot = document.getElementById('apNotifDot');
    const list = document.getElementById('apNotifList');
    const markAll = document.getElementById('apNotifMarkAll');
    if (!btn || !panel) return;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function typeLabel(type) {
        const map = {
            principal_schedule_approved: 'Principal · Schedule approved',
            principal_schedule_rejected: 'Principal · Schedule rejected',
            principal_permission_approved: 'Principal · Request approved',
            principal_permission_rejected: 'Principal · Request rejected',
            admin_request_approved: 'Admin · Request approved',
            admin_request_rejected: 'Admin · Request rejected',
            teacher_request: 'New teacher request',
            general: 'Notice'
        };
        return map[type] || 'Notification';
    }

    async function loadNotifications() {
        try {
            const res = await fetch(api, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
            const items = json.data || [];
            const unread = json.unread_count || 0;
            if (unread > 0) {
                dot.textContent = unread > 99 ? '99+' : String(unread);
                dot.classList.add('show');
            } else {
                dot.textContent = '';
                dot.classList.remove('show');
            }
            if (!items.length) {
                list.innerHTML = '<div class="ap-notif-empty">No notifications yet.</div>';
                return;
            }
            list.innerHTML = items.map(n => `
                <div class="ap-notif-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" data-type="${esc(n.type || '')}">
                    <span class="ap-notif-type">${esc(typeLabel(n.type))}</span>
                    <strong>${esc(n.title)}</strong>
                    <p>${esc(n.message)}</p>
                    <small style="color:var(--text-secondary);">${n.created_at ? new Date(n.created_at).toLocaleString() : ''}</small>
                </div>
            `).join('');
            list.querySelectorAll('.ap-notif-item').forEach(el => {
                el.addEventListener('click', async function() {
                    const id = parseInt(this.dataset.id, 10);
                    if (id) {
                        await fetch(api + '/read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            body: JSON.stringify({ id: id })
                        }).catch(() => {});
                    }
                    const t = this.dataset.type || '';
                    let target = allRequestsUrl;
                    if (t.startsWith('principal_permission') && permissionRequestsUrl) {
                        target = permissionRequestsUrl;
                    } else if ((t.startsWith('admin_request') || t === 'teacher_request' || t.startsWith('principal_schedule')) && allRequestsUrl) {
                        target = allRequestsUrl;
                    }
                    if (target) window.location.href = target;
                    else loadNotifications();
                });
            });
        } catch (e) {
            list.innerHTML = '<div class="ap-notif-empty">Could not load notifications.</div>';
        }
    }

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) loadNotifications();
    });

    markAll?.addEventListener('click', async function(e) {
        e.stopPropagation();
        try {
            await fetch(api + '/read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: '{}'
            });
            loadNotifications();
        } catch (err) {}
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('apNotifWrap')?.contains(e.target)) {
            panel.classList.remove('open');
        }
    });

    loadNotifications();
    setInterval(loadNotifications, 60000);
})();
</script>
