/**
 * Client-side search for All Requests panels (shared / teacher / leave).
 */
(function () {
    function normalize(text) {
        return String(text || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function matchesQuery(haystack, query) {
        if (!query) {
            return true;
        }
        const h = normalize(haystack);
        const tokens = normalize(query).split(' ').filter(Boolean);
        return tokens.every(function (tok) {
            return h.indexOf(tok) !== -1;
        });
    }

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
            const q = (input.value || '').trim();
            let visible = 0;
            rows.forEach(function (row) {
                const hay = row.dataset.search || row.textContent || '';
                const show = matchesQuery(hay, q);
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
