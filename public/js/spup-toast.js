/**
 * Right-side toast notifications (replaces browser alert for admin portals).
 */
(function () {
    'use strict';

    var container = null;

    function ensureContainer() {
        if (container) return container;
        container = document.createElement('div');
        container.id = 'spup-toast-container';
        container.setAttribute('aria-live', 'polite');
        container.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:99999;display:flex;flex-direction:column;gap:0.5rem;max-width:min(360px,calc(100vw - 2rem));pointer-events:none;';
        document.body.appendChild(container);
        return container;
    }

    function icon(type) {
        if (type === 'success') return '✓';
        if (type === 'error') return '✕';
        if (type === 'warning') return '!';
        return 'i';
    }

    function show(message, type, duration) {
        type = type || 'info';
        duration = duration == null ? 4500 : duration;
        var root = ensureContainer();
        var el = document.createElement('div');
        el.className = 'spup-toast spup-toast--' + type;
        el.style.cssText = 'pointer-events:auto;display:flex;align-items:flex-start;gap:0.65rem;padding:0.85rem 1rem;border-radius:0.5rem;box-shadow:0 8px 24px rgba(0,0,0,.18);font-size:0.875rem;line-height:1.4;animation:spupToastIn .25s ease;';
        var colors = {
            success: { bg: '#ecfdf5', border: '#6ee7b7', text: '#065f46', icon: '#16a34a' },
            error:   { bg: '#fef2f2', border: '#fca5a5', text: '#991b1b', icon: '#dc2626' },
            warning: { bg: '#fffbeb', border: '#fcd34d', text: '#92400e', icon: '#d97706' },
            info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1e40af', icon: '#2563eb' },
        };
        var c = colors[type] || colors.info;
        el.style.background = c.bg;
        el.style.border = '1px solid ' + c.border;
        el.style.color = c.text;
        el.innerHTML = '<span style="font-weight:800;font-size:1rem;line-height:1;color:' + c.icon + '">' + icon(type) + '</span><span style="flex:1;word-break:break-word;">' + String(message).replace(/</g, '&lt;') + '</span>';
        root.appendChild(el);
        if (duration > 0) {
            setTimeout(function () {
                el.style.opacity = '0';
                el.style.transform = 'translateX(12px)';
                el.style.transition = 'opacity .2s, transform .2s';
                setTimeout(function () { el.remove(); }, 220);
            }, duration);
        }
        return el;
    }

    window.spupToast = {
        show: show,
        success: function (m, d) { return show(m, 'success', d); },
        error: function (m, d) { return show(m, 'error', d); },
        warning: function (m, d) { return show(m, 'warning', d); },
        info: function (m, d) { return show(m, 'info', d); },
    };

    var nativeAlert = window.alert;
    window.alert = function (msg) {
        var text = String(msg == null ? '' : msg).trim();
        if (!text) return;
        if (text.charAt(0) === '\u2713' || /^success/i.test(text)) {
            show(text.replace(/^\u2713\s*/, ''), 'success');
            return;
        }
        if (text.charAt(0) === '\u2717' || /^error/i.test(text)) {
            show(text.replace(/^\u2717\s*/, ''), 'error');
            return;
        }
        show(text, 'info');
    };

    if (!document.getElementById('spup-toast-keyframes')) {
        var style = document.createElement('style');
        style.id = 'spup-toast-keyframes';
        style.textContent = '@keyframes spupToastIn{from{opacity:0;transform:translateX(16px)}to{opacity:1;transform:translateX(0)}}';
        document.head.appendChild(style);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-spup-flash]').forEach(function (node, index) {
            var type = node.getAttribute('data-spup-flash-type') || 'success';
            var msg = node.getAttribute('data-spup-flash') || node.textContent.trim();
            if (msg) {
                var duration = type === 'error' ? 7000 : 4500;
                setTimeout(function () { show(msg, type, duration); }, index * 350);
            }
            node.remove();
        });
    });
})();
