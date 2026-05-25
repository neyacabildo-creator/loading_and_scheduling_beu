/**
 * Create-schedule teacher dropdowns: only teachers with faculty load for grade + subject.
 */
(function (global) {
    function resolveTeacherIds(grade, subject, byGradeSubject, aliases) {
        if (!grade || !subject) {
            return [];
        }

        var gradeMap = byGradeSubject[grade] || {};
        var keys = [String(subject).toUpperCase()];
        if (aliases && aliases[subject]) {
            keys = keys.concat(aliases[subject].map(function (k) {
                return String(k).toUpperCase();
            }));
        }

        var seen = {};
        var out = [];
        keys.forEach(function (key) {
            var ids = gradeMap[key] || [];
            ids.forEach(function (id) {
                var s = String(id);
                if (!seen[s]) {
                    seen[s] = true;
                    out.push(s);
                }
            });
        });

        return out;
    }

    function rebuild(select, options) {
        if (!select) {
            return;
        }

        var grade = options.getGrade ? options.getGrade() : '';
        var row = select.closest('.sf-subject-row') || select.closest('.sf-cell');
        var subjectEl = row ? row.querySelector('.sf-subject') : null;
        var subject = subjectEl ? String(subjectEl.value || '').trim().toUpperCase() : '';

        select.innerHTML = '<option value="">' + (options.placeholder || '-- Teacher --') + '</option>';

        if (!grade || !subject) {
            select.value = '';
            delete select.dataset.lastTeacher;
            return;
        }

        var allowed = resolveTeacherIds(
            grade,
            subject,
            options.byGradeSubject || {},
            options.aliases || null
        );

        var unavailable = options.unavailable || {};
        var allTeachers = options.allTeachers || [];

        allowed.forEach(function (id) {
            if (unavailable[id]) {
                return;
            }
            var t = allTeachers.find(function (u) {
                return String(u.id) === id;
            });
            if (!t) {
                return;
            }
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            select.appendChild(opt);
        });
    }

    global.ScheduleFormTeacherFilter = {
        rebuild: rebuild,
        resolveTeacherIds: resolveTeacherIds,
    };
})(typeof window !== 'undefined' ? window : this);
