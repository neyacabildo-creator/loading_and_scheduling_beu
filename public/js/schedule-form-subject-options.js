/**
 * Each section column may use a subject only once across all time slots.
 */
(function () {
    'use strict';

    function activeGridRoot(cfg) {
        if (typeof cfg.getActiveRoot === 'function') {
            return cfg.getActiveRoot();
        }
        var form = document.getElementById(cfg.formId);
        return form ? form.querySelector('table tbody') : null;
    }

    function parseSubjectSelect(el) {
        var m = (el.name || '').match(/slots\[([^\]]+)\]\[(\d+)\]\[subject\]/);
        if (!m) {
            return null;
        }
        return { slotKey: m[1], secIdx: parseInt(m[2], 10) };
    }

    /** Subjects already chosen in other time slots of the same section column. */
    function usedSubjectsInSection(secIdx, root, excludeSel) {
        var used = new Set();
        if (!root) {
            return used;
        }
        root.querySelectorAll('select.sf-subject').forEach(function (sel) {
            if (sel === excludeSel) {
                return;
            }
            var parsed = parseSubjectSelect(sel);
            if (!parsed || parsed.secIdx !== secIdx) {
                return;
            }
            var v = sel.value.trim();
            if (v) {
                used.add(v.toUpperCase());
            }
        });
        return used;
    }

    function subjectPool(cfg) {
        if (typeof cfg.getSubjects === 'function') {
            return cfg.getSubjects() || [];
        }
        return cfg.subjects || [];
    }

    function refresh(cfg) {
        var root = activeGridRoot(cfg);
        if (!root) {
            return;
        }
        var pool = subjectPool(cfg);
        root.querySelectorAll('select.sf-subject').forEach(function (sel) {
            var parsed = parseSubjectSelect(sel);
            if (!parsed) {
                return;
            }
            var used = usedSubjectsInSection(parsed.secIdx, root, sel);
            var current = sel.value.trim();
            var placeholderText = '— Subject —';
            sel.innerHTML = '';
            var blank = document.createElement('option');
            blank.value = '';
            blank.textContent = placeholderText;
            sel.appendChild(blank);
            pool.forEach(function (s) {
                if (used.has(String(s).toUpperCase())) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                if (current && current.toUpperCase() === String(s).toUpperCase()) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
            if (current && sel.value !== current) {
                sel.value = '';
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    function init(cfg) {
        if (!cfg || !cfg.formId) {
            return;
        }
        var form = document.getElementById(cfg.formId);
        if (!form) {
            return;
        }
        form.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('sf-subject')) {
                refresh(cfg);
            }
        });
        refresh(cfg);
    }

    window.ScheduleFormSubjectOptions = {
        init: init,
        refresh: refresh,
    };
})();
