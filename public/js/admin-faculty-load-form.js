/**
 * Shared faculty load add/edit subject rows + auto ongoing class count.
 * Configure via AdminFacultyLoadForm.init(cfg) from each admin blade.
 */
(function (global) {
    const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    function timeToMins(t) {
        if (!t) return 0;
        const [h, m] = String(t).split(':').map(Number);
        return (h || 0) * 60 + (m || 0);
    }

    function getApprovedSchedules(list) {
        return (list || []).filter(s =>
            s && s.admin_approved && ['active', 'approved'].includes(String(s.status || '').toLowerCase())
        );
    }

    function countOngoingSchedules(schedules, gradeLevel) {
        const approved = getApprovedSchedules(schedules);
        const now = new Date();
        const today = DAY_NAMES[now.getDay()];
        const nowMins = now.getHours() * 60 + now.getMinutes();
        const normalizedGrade = String(gradeLevel || '').trim().toLowerCase();
        let count = 0;
        approved.forEach(s => {
            if (String(s.day_of_week || '').toLowerCase() !== today.toLowerCase()) return;
            const start = timeToMins(s.start_time);
            const end = timeToMins(s.end_time);
            if (!(start <= nowMins && end > nowMins)) return;
            if (normalizedGrade) {
                const sg = String(s.grade_level || '').trim().toLowerCase();
                if (sg && sg !== normalizedGrade) return;
            }
            count++;
        });
        return count;
    }

    const SEL_STYLE = 'width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:0.85rem;';

    function init(cfg) {
        const rowClass = cfg.rowClass;
        const editRowClass = cfg.editRowClass;
        const listAddId = cfg.listAddId;
        const listEditId = cfg.listEditId;
        const classesAddId = cfg.classesAddId;
        const classesEditId = cfg.classesEditId;
        const gradeAddId = cfg.gradeAddId;
        const gradeEditId = cfg.gradeEditId;
        const onHoursChange = cfg.onRecalculateAddHours || function () {};
        const onEditHoursChange = cfg.onRecalculateEditHours || function () {};
        const getCache = cfg.getScheduleCache || (() => []);
        const getEditCache = cfg.getEditScheduleCache || (() => []);

        function subjectsForGrade(gradeLevel) {
            return typeof cfg.subjectsForGrade === 'function' ? cfg.subjectsForGrade(gradeLevel) : [];
        }

        function subjectOptionsHtml(gradeLevel) {
            const subjects = subjectsForGrade(gradeLevel);
            return '<option value="">-- Select Subject --</option>' +
                subjects.map(s => `<option value="${s}">${s}</option>`).join('');
        }

        function recalculateOngoingClasses(isEdit) {
            const gradeEl = document.getElementById(isEdit ? gradeEditId : gradeAddId);
            const classesEl = document.getElementById(isEdit ? classesEditId : classesAddId);
            if (!classesEl) return;
            const grade = gradeEl ? gradeEl.value : '';
            const cache = isEdit ? getEditCache() : getCache();
            classesEl.value = countOngoingSchedules(cache, grade);
        }

        function setSelectValue(sel, value) {
            const v = String(value || '').trim();
            if (!v) return;
            const exact = Array.from(sel.options).find(o => o.value === v);
            if (exact) {
                sel.value = exact.value;
                return;
            }
            const ci = Array.from(sel.options).find(
                o => o.value && o.value.toLowerCase() === v.toLowerCase()
            );
            if (ci) {
                sel.value = ci.value;
                return;
            }
            const opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v;
            sel.appendChild(opt);
            sel.value = v;
        }

        function appendSubjectRow(container, rowClassName, gradeLevel, value, idx, onChange) {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'display:flex;align-items:center;gap:0.4rem;margin-bottom:0.4rem;';
            wrap.innerHTML = `
                <span style="min-width:1.5rem;font-size:0.8rem;color:var(--text-secondary);">${idx + 1}.</span>
                <select class="${rowClassName}" style="${SEL_STYLE}" data-idx="${idx}">${subjectOptionsHtml(gradeLevel)}</select>
                <button type="button" class="fl-remove-subject" title="Remove" style="border:none;background:transparent;color:#b91c1c;cursor:pointer;font-size:1.1rem;line-height:1;">&times;</button>
            `;
            const sel = wrap.querySelector('select');
            if (value) setSelectValue(sel, value);
            sel.addEventListener('change', onChange);
            wrap.querySelector('.fl-remove-subject').addEventListener('click', function () {
                const rows = container.querySelectorAll('.' + rowClassName);
                if (rows.length <= 1) {
                    sel.value = '';
                    onChange();
                    return;
                }
                wrap.remove();
                renumberRows(container, rowClassName);
                onChange();
            });
            container.appendChild(wrap);
        }

        function renumberRows(container, rowClassName) {
            container.querySelectorAll('.' + rowClassName).forEach((sel, i) => {
                const label = sel.closest('div')?.querySelector('span');
                if (label) label.textContent = (i + 1) + '.';
                sel.dataset.idx = String(i);
            });
        }

        function renderSubjectList(containerId, rowClassName, gradeLevel, preselected, onChange) {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';
            const pending = typeof cfg.getPendingSharedSubjects === 'function'
                ? (cfg.getPendingSharedSubjects() || []).filter(s => String(s || '').trim() !== '')
                : [];
            let list = (preselected && preselected.length)
                ? preselected.filter(s => String(s || '').trim() !== '')
                : [];
            if (!list.length && pending.length) {
                list = pending.slice();
            }
            if (!list.length) {
                list = [''];
            }
            list.forEach((val, i) => appendSubjectRow(container, rowClassName, gradeLevel, val, i, onChange));
        }

        function collectSubjects(rowClassName) {
            return Array.from(document.querySelectorAll('.' + rowClassName))
                .map(s => s.value.trim())
                .filter(Boolean);
        }

        global[cfg.globalAddSubject] = function () {
            const container = document.getElementById(listAddId);
            const grade = document.getElementById(gradeAddId)?.value || '';
            if (!grade) {
                alert('Please select a grade level first.');
                return;
            }
            if (container.querySelector('p')) container.innerHTML = '';
            const n = container.querySelectorAll('.' + rowClass).length;
            appendSubjectRow(container, rowClass, grade, '', n, onHoursChange);
        };

        if (cfg.globalAddEditSubject) {
            global[cfg.globalAddEditSubject] = function () {
                const container = document.getElementById(listEditId);
                const grade = document.getElementById(gradeEditId)?.value || '';
                if (container.querySelector('p')) container.innerHTML = '';
                const n = container.querySelectorAll('.' + editRowClass).length;
                appendSubjectRow(container, editRowClass, grade, '', n, onEditHoursChange);
            };
        }

        function resolvePreselected(preselected) {
            const pending = typeof cfg.getPendingSharedSubjects === 'function'
                ? cfg.getPendingSharedSubjects()
                : [];
            if (Array.isArray(pending) && pending.length) {
                return pending;
            }
            if (Array.isArray(preselected) && preselected.length) {
                return preselected;
            }
            return [''];
        }

        global[cfg.globalRenderAdd] = function (preselected) {
            const grade = document.getElementById(gradeAddId)?.value || '';
            const list = resolvePreselected(preselected);
            if (!grade) {
                const c = document.getElementById(listAddId);
                if (c && list.length && String(list[0] || '').trim() !== '') {
                    c.innerHTML = '<p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a grade level to finalize subject options. Assigned subjects are kept below.</p>';
                    renderSubjectList(listAddId, rowClass, '', list, onHoursChange);
                } else if (c) {
                    c.innerHTML = '<p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a grade level first.</p>';
                }
                return;
            }
            renderSubjectList(listAddId, rowClass, grade, list, onHoursChange);
            recalculateOngoingClasses(false);
        };

        global[cfg.globalRenderEdit] = function (preselected) {
            const grade = document.getElementById(gradeEditId)?.value || '';
            renderSubjectList(listEditId, editRowClass, grade, preselected, onEditHoursChange);
            recalculateOngoingClasses(true);
        };

        global[cfg.globalRecalcOngoing] = function (isEdit) {
            recalculateOngoingClasses(!!isEdit);
        };

        global[cfg.globalCollectAdd] = function () {
            return collectSubjects(rowClass);
        };

        global[cfg.globalCollectEdit] = function () {
            return collectSubjects(editRowClass);
        };

        const gradeAdd = document.getElementById(gradeAddId);
        if (gradeAdd) {
            gradeAdd.addEventListener('change', function () {
                const pending = typeof cfg.getPendingSharedSubjects === 'function'
                    ? (cfg.getPendingSharedSubjects() || []).filter(s => String(s || '').trim() !== '')
                    : [];
                const fromDom = collectSubjects(rowClass).filter(s => String(s || '').trim() !== '');
                const pre = pending.length ? pending : (fromDom.length ? fromDom : ['']);
                global[cfg.globalRenderAdd](pre);
            });
        }
        const gradeEdit = document.getElementById(gradeEditId);
        if (gradeEdit) {
            gradeEdit.addEventListener('change', function () {
                global[cfg.globalRenderEdit](collectSubjects(editRowClass));
            });
        }
    }

    async function parseJsonResponse(response) {
        const text = await response.text();
        let data = {};
        try {
            data = text ? JSON.parse(text) : {};
        } catch (e) {
            if (!response.ok) {
                const snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
                throw new Error(snippet || response.statusText || 'Server error');
            }
            return data;
        }
        if (!response.ok) {
            const msg = data.errors
                ? Object.values(data.errors).flat().join(', ')
                : (data.message || response.statusText || 'Request failed');
            throw new Error(msg);
        }
        return data;
    }

    function notifyFacultyLoadError(message) {
        const text = String(message || 'Request failed').trim();
        if (window.spupToast && typeof window.spupToast.error === 'function') {
            window.spupToast.error(text);
            return;
        }
        alert('\u2717 ' + text);
    }

    function notifyFacultyLoadSuccess(message) {
        const text = String(message || 'Saved successfully').trim();
        if (window.spupToast && typeof window.spupToast.success === 'function') {
            window.spupToast.success(text);
            return;
        }
        alert('\u2713 ' + text);
    }

    global.AdminFacultyLoadForm = {
        init,
        countOngoingSchedules,
        getApprovedSchedules,
        timeToMins,
        parseJsonResponse,
        notifyFacultyLoadError,
        notifyFacultyLoadSuccess,
    };
})(window);
