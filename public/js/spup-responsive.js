/**
 * SPUP — mobile sidebar toggle (all layouts with .sidebar)
 */
(function () {
    'use strict';

    function init() {
        var toggle = document.getElementById('spupSidebarToggle') || document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('mainSidebar') || document.querySelector('.sidebar');
        var overlay = document.getElementById('spupSidebarOverlay') || document.getElementById('sidebarOverlay');

        if (!sidebar) return;

        function open() {
            sidebar.classList.add('open', 'spup-open');
            if (overlay) overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function close() {
            sidebar.classList.remove('open', 'spup-open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function isOpen() {
            return sidebar.classList.contains('open') || sidebar.classList.contains('spup-open');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                isOpen() ? close() : open();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', close);
        }

        document.querySelectorAll('.sidebar-nav .nav-item, .sidebar-nav a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) close();
            });
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) close();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
