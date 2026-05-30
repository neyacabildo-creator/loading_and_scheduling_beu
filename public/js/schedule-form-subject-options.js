/**
 * Hide subjects already picked in earlier time slots (same section column).
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

    function slotOrder(root) {
        var keys = [];
        if (!root) {
            return keys;
        }
        root.querySelectorAll('tr:not(.break-row)').forEach(function (tr) {
            var sub = tr.querySelector('select.sf-subject');
            if (!sub || !sub.name) {
                return;
            }
            var m = sub.name.match(/slots\[([^\]]+)\]/);
            if (m && keys.indexOf(m[1]) === -1) {
                keys.push(m[1]);
            }
        });
        return keys;
    }

    function parseSubjectSelect(el) {
        var m = (el.name || '').match(/slots\[([^\]]+)\]\[(\d+)\]\[subject\]/);
        if (!m) {
            return null;
        }
        return { slotKey: m[1], secIdx: parseInt(m[2], 10) };
    }

    function usedSubjectsBefore(slotKey, secIdx, root) {
        var order = slotOrder(root);
        var pos = order.indexOf(slotKey);
        var used = new Set();
        if (pos <= 0) {
            return used;
        }
        for (var i = 0; i < pos; i++) {
            var sel = root.querySelector(
                'select.sf-subject[name="slots[' + order[i] + '][' + secIdx + '][subject]"]'
            );
            if (sel) {
                var v = sel.value.trim();
                if (v) {
                    used.add(v.toUpperCase());
                }
            }
        }
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
            var used = usedSubjectsBefore(parsed.slotKey, parsed.secIdx, root);
            var current = sel.value;
            var placeholder = sel.querySelector('option[value=""]');
            var placeholderText = placeholder ? placeholder.textContent : '— Subject —';
            sel.innerHTML = '';
            var blank = document.createElement('option');
            blank.value = '';
            blank.textContent = placeholderText;
            sel.appendChild(blank);
            pool.forEach(function (s) {
                if (used.has(String(s).toUpperCase()) && s !== current) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                if (s === current) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });
            if (current && sel.value !== current) {
                sel.value = '';
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
