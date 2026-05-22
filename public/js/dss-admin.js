/**
 * dss-admin.js
 * Client-side logic for the Decision Support System (DSS) admin panel.
 *
 * Expects a global DSS_CONFIG object on the page:
 *   window.DSS_CONFIG = {
 *     analyzeUrl:       '/api/admin/dss/analyze',
 *     workloadUrl:      '/api/admin/dss/workload',
 *     roomsUrl:         '/api/admin/dss/rooms',
 *     notificationsUrl: '/api/admin/dss/notifications',
 *     csrfToken:        '...',
 *   };
 */

(() => {
    'use strict';

    // ── Helpers ───────────────────────────────────────────────────────────────

    const cfg = () => window.DSS_CONFIG || {};

    function csrfHeaders() {
        return {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  cfg().csrfToken ||
                             document.querySelector('meta[name="csrf-token"]')?.content || '',
        };
    }

    async function apiFetch(url, method = 'GET', body = null) {
        const opts = { method, headers: csrfHeaders() };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        return res.json();
    }

    function el(id) { return document.getElementById(id); }

    function priorityClass(p) {
        return p === 'high' ? 'priority-high' : p === 'medium' ? 'priority-medium' : 'priority-low';
    }

    function typeIcon(type) {
        const icons = {
            conflict: `<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                           d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                       </svg>`,
            balance:  `<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                           d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                       </svg>`,
            optimize: `<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                           d="M13 10V3L4 14h7v7l9-11h-7z"/>
                       </svg>`,
            improve:  `<svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                           d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                       </svg>`,
        };
        return icons[type] || icons.optimize;
    }

    function typeIconClass(type) {
        const map = { conflict: 'icon-conflict', balance: 'icon-balance', optimize: 'icon-optimize', improve: 'icon-improve' };
        return map[type] || 'icon-optimize';
    }

    // ── State ─────────────────────────────────────────────────────────────────

    let allRecommendations = [];
    let activeFilter       = 'all';

    // ── Render functions ──────────────────────────────────────────────────────

    function renderStats(stats) {
        const total  = el('dss-stat-total');
        const high   = el('dss-stat-high');
        const medium = el('dss-stat-medium');
        const low    = el('dss-stat-low');
        if (total)  total.textContent  = stats.total;
        if (high)   high.textContent   = stats.high;
        if (medium) medium.textContent = stats.medium;
        if (low)    low.textContent    = stats.low;
    }

    function renderNotifications(notifications) {
        const container = el('dss-notifications');
        if (!container) return;
        if (!notifications || notifications.length === 0) {
            container.innerHTML = '<p style="color:#7a7a6e;font-size:0.875rem;">No high-priority alerts at this time.</p>';
            return;
        }
        container.innerHTML = notifications.map(n => `
            <div class="dss-notification-item dss-notif-${n.type}">
                <div class="dss-notif-icon">${typeIcon(n.type)}</div>
                <div class="dss-notif-body">
                    <div class="dss-notif-title">${escHtml(n.title)}</div>
                    <div class="dss-notif-msg">${escHtml(n.message)}</div>
                </div>
            </div>
        `).join('');
    }

    function renderRecommendations(recs) {
        const container = el('dss-rec-list');
        if (!container) return;

        if (!recs || recs.length === 0) {
            container.innerHTML = `
                <div class="dss-empty-state">
                    <svg width="48" height="48" fill="none" stroke="#9ca3af" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>No recommendations in this category. The schedule looks good!</p>
                </div>`;
            return;
        }

        container.innerHTML = recs.map((rec, idx) => `
            <div class="recommendation-card" data-type="${rec.type}" data-priority="${rec.priority}" data-idx="${idx}">
                <div class="recommendation-header">
                    <div style="display:flex;gap:1rem;align-items:flex-start;flex:1;">
                        <div class="recommendation-icon ${typeIconClass(rec.type)}">${typeIcon(rec.type)}</div>
                        <div style="flex:1;">
                            <h3 class="recommendation-title">${escHtml(rec.title)}</h3>
                            <span class="recommendation-priority ${priorityClass(rec.priority)}">
                                ${capitalise(rec.priority)} Priority
                            </span>
                        </div>
                    </div>
                </div>
                <div class="recommendation-details">${escHtml(rec.issue)}</div>
                <div class="rec-solution-box">
                    <svg width="16" height="16" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <div><strong>Recommendation:</strong> ${escHtml(rec.solution)}</div>
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                    ${rec.route ? `<a href="/${rec.route.replace(/\./g, '/')}" class="action-btn btn-primary">Apply Recommendation</a>` : ''}
                    <button class="action-btn btn-secondary" onclick="DSS.viewDetails(${idx})">View Details</button>
                    <button class="action-btn btn-dismiss" onclick="DSS.dismiss(this)">Dismiss</button>
                </div>
            </div>
        `).join('');
    }

    function renderWorkload(summary) {
        const container = el('dss-workload-table');
        if (!container) return;
        if (!summary || summary.length === 0) {
            container.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:1rem;">No faculty load data available.</td></tr>';
            return;
        }
        container.innerHTML = summary.map(row => {
            const statusBadge = {
                overloaded:  '<span class="dss-badge badge-red">Overloaded</span>',
                underloaded: '<span class="dss-badge badge-yellow">Underloaded</span>',
                normal:      '<span class="dss-badge badge-green">Normal</span>',
            }[row.status] || '';

            const subjects = Array.isArray(row.subjects)
                ? row.subjects.join(', ') || '—'
                : '—';

            return `<tr>
                <td><strong>${escHtml(row.name)}</strong></td>
                <td style="font-size:0.8rem;">${escHtml(subjects)}</td>
                <td>${row.load_hours}h</td>
                <td>${row.classes_assigned} assigned / ${row.scheduled_count} scheduled</td>
                <td>${statusBadge}</td>
            </tr>`;
        }).join('');
    }

    function renderRooms(rooms) {
        const container = el('dss-rooms-table');
        if (!container) return;
        if (!rooms || rooms.length === 0) {
            container.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:1rem;">No room data available.</td></tr>';
            return;
        }
        container.innerHTML = rooms.map(r => {
            const utilBar = `
                <div class="dss-util-bar">
                    <div class="dss-util-fill ${r.utilisation_pct >= 90 ? 'util-high' : r.utilisation_pct < 30 ? 'util-low' : 'util-mid'}"
                         style="width:${Math.min(r.utilisation_pct, 100)}%"></div>
                </div>
                <span style="font-size:0.8rem;">${r.utilisation_pct}%</span>`;

            const features = [
                r.has_lab       ? 'Lab'       : '',
                r.has_projector ? 'Projector'  : '',
            ].filter(Boolean).join(', ') || '—';

            const statusBadge = {
                available:   '<span class="dss-badge badge-green">Available</span>',
                'in-use':    '<span class="dss-badge badge-yellow">In Use</span>',
                maintenance: '<span class="dss-badge badge-red">Maintenance</span>',
            }[r.status] || `<span class="dss-badge">${escHtml(r.status)}</span>`;

            return `<tr>
                <td><strong>${escHtml(r.room_number)}</strong></td>
                <td>${escHtml(r.building)}</td>
                <td>${r.capacity}</td>
                <td>${escHtml(features)}</td>
                <td>${utilBar}</td>
                <td>${statusBadge}</td>
            </tr>`;
        }).join('');
    }

    // ── Analysis runner ───────────────────────────────────────────────────────

    async function runAnalysis() {
        const btn     = el('dss-run-btn');
        const spinner = el('dss-spinner');
        const results = el('dss-results');
        const placeholder = el('dss-placeholder');

        if (btn) { btn.disabled = true; btn.textContent = 'Analysing…'; }
        if (spinner) spinner.style.display = 'flex';
        if (results) results.style.display = 'none';

        try {
            const json = await apiFetch(cfg().analyzeUrl, 'POST');

            if (!json.success) {
                showError(json.message || 'Analysis failed. Please try again.');
                return;
            }

            const data = json.data;
            allRecommendations = data.recommendations || [];
            activeFilter       = 'all';

            renderStats(data.stats || {});
            renderNotifications(data.notifications || []);
            applyFilter('all');
            renderWorkload(data.workload_summary || []);
            renderRooms(data.room_allocation || []);

            if (placeholder) placeholder.style.display = 'none';
            if (results)     results.style.display     = 'block';

            updateFilterCounts(data.stats);
        } catch (err) {
            showError('Network error: ' + err.message);
        } finally {
            if (btn)     { btn.disabled = false; btn.textContent = 'Run Analysis'; }
            if (spinner)  spinner.style.display = 'none';
        }
    }

    function applyFilter(filter) {
        activeFilter = filter;

        // Update filter button styles
        document.querySelectorAll('.filter-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === filter);
        });

        const filtered = filter === 'all'
            ? allRecommendations
            : allRecommendations.filter(r => {
                if (filter === 'high')     return r.priority === 'high';
                if (filter === 'conflict') return r.type === 'conflict';
                if (filter === 'optimize') return r.type === 'optimize' || r.type === 'improve';
                if (filter === 'balance')  return r.type === 'balance';
                return true;
              });

        renderRecommendations(filtered);
    }

    function updateFilterCounts(stats) {
        const map = {
            'dss-count-all':      stats.total,
            'dss-count-high':     stats.high,
        };
        for (const [id, val] of Object.entries(map)) {
            const node = el(id);
            if (node) node.textContent = `(${val})`;
        }
    }

    function showError(msg) {
        const container = el('dss-rec-list');
        if (container) {
            container.innerHTML = `<div class="dss-alert-error">${escHtml(msg)}</div>`;
        }
        const results = el('dss-results');
        if (results) results.style.display = 'block';
    }

    // ── Utilities ─────────────────────────────────────────────────────────────

    function escHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function capitalise(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    // ── Detail modal ──────────────────────────────────────────────────────────

    function viewDetails(idx) {
        const rec = allRecommendations[idx];
        if (!rec) return;

        const modal   = el('dss-detail-modal');
        const mTitle  = el('dss-modal-title');
        const mBody   = el('dss-modal-body');

        if (!modal) return;

        if (mTitle) mTitle.textContent = rec.title;
        if (mBody) {
            const meta = rec.meta || {};
            const metaRows = Object.entries(meta)
                .map(([k, v]) => `<tr><td style="color:#7a7a6e;padding:0.25rem 0.5rem;">${escHtml(k.replace(/_/g,' '))}</td><td style="padding:0.25rem 0.5rem;">${escHtml(String(v))}</td></tr>`)
                .join('');

            mBody.innerHTML = `
                <p style="margin-bottom:1rem;"><strong>Priority:</strong>
                    <span class="recommendation-priority ${priorityClass(rec.priority)}">${capitalise(rec.priority)}</span>
                </p>
                <h4 style="font-weight:600;margin-bottom:0.5rem;">Issue</h4>
                <p style="color:#4b5563;margin-bottom:1rem;">${escHtml(rec.issue)}</p>
                <h4 style="font-weight:600;margin-bottom:0.5rem;">Recommended Action</h4>
                <p style="color:#4b5563;margin-bottom:1rem;">${escHtml(rec.solution)}</p>
                ${metaRows ? `<h4 style="font-weight:600;margin-bottom:0.5rem;">Details</h4>
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">${metaRows}</table>` : ''}
            `;
        }

        modal.style.display = 'flex';
    }

    function closeModal() {
        const modal = el('dss-detail-modal');
        if (modal) modal.style.display = 'none';
    }

    function dismiss(btn) {
        const card = btn.closest('.recommendation-card');
        if (card) {
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity    = '0';
            card.style.transform  = 'translateX(20px)';
            setTimeout(() => card.remove(), 300);
        }
    }

    // ── Tab switching ─────────────────────────────────────────────────────────

    function switchTab(tab) {
        document.querySelectorAll('.dss-tab-content').forEach(t => t.style.display = 'none');
        document.querySelectorAll('.dss-tab-btn').forEach(b => b.classList.remove('active'));

        const content = el('dss-tab-' + tab);
        if (content) content.style.display = 'block';

        const activeBtn = document.querySelector(`.dss-tab-btn[data-tab="${tab}"]`);
        if (activeBtn) activeBtn.classList.add('active');
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    function init() {
        // Run Analysis button
        const runBtn = el('dss-run-btn');
        if (runBtn) runBtn.addEventListener('click', runAnalysis);

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', () => applyFilter(btn.dataset.filter || 'all'));
        });

        // Tab buttons
        document.querySelectorAll('.dss-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => switchTab(btn.dataset.tab));
        });

        // Modal close
        const closeBtn = el('dss-modal-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        const overlay = el('dss-detail-modal');
        if (overlay) {
            overlay.addEventListener('click', e => {
                if (e.target === overlay) closeModal();
            });
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    // Expose public API for inline onclick attributes
    window.DSS = { runAnalysis, viewDetails, dismiss, closeModal, switchTab, applyFilter };
})();
