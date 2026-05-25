/**
 * Client-side search for All Requests panels (shared / teacher / leave).
 */
(function () {
    function initSectionSearch(input) {
        const tableId = input.getAttribute('data-str-table');
        if (!tableId) {
            return;
        }
        const table = document.getElementById(tableId);
        if (!table) {
            return;
        }
        const tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const wrap = table.closest('.str-table-wrap');
        let emptyMsg = wrap && wrap.parentElement
            ? wrap.parentElement.querySelector('.str-search-no-match')
            : null;

        if (!emptyMsg && wrap) {
            emptyMsg = document.createElement('div');
            emptyMsg.className = 'str-empty-state str-search-no-match';
            emptyMsg.hidden = true;
            emptyMsg.innerHTML = '<p>No matching requests.</p>';
            wrap.insertAdjacentElement('afterend', emptyMsg);
        }

        function filter() {
            const q = (input.value || '').trim().toLowerCase();
            let visible = 0;
            rows.forEach(function (row) {
                const text = (row.textContent || '').toLowerCase();
                const show = q === '' || text.indexOf(q) !== -1;
                row.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });
            if (wrap) {
                wrap.hidden = visible === 0 && q !== '';
            }
            if (emptyMsg) {
                emptyMsg.hidden = !(q !== '' && visible === 0);
            }
        }

        input.addEventListener('input', filter);
        input.addEventListener('search', filter);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.str-section-search').forEach(initSectionSearch);
    });
})();
