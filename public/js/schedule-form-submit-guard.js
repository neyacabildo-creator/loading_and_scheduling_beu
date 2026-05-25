/**
 * Intercepts create-schedule form submit, checks grid conflicts via API, shows right-side toasts.
 */
(function () {
    'use strict';

    var cfg = window.SCHEDULE_FORM_GUARD;
    if (!cfg || !cfg.checkUrl || !cfg.formId) {
        return;
    }

    var form = document.getElementById(cfg.formId);
    if (!form) {
        return;
    }

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showConflictToasts(messages) {
        var list = Array.isArray(messages) ? messages : [String(messages)];
        function emit() {
            if (!window.spupToast) {
                setTimeout(emit, 40);
                return;
            }
            list.forEach(function (msg, i) {
                if (!msg) {
                    return;
                }
                setTimeout(function () {
                    window.spupToast.error(msg, 8000);
                }, i * 400);
            });
        }
        emit();
    }

    function runClientValidation() {
        if (typeof cfg.clientValidate === 'function') {
            return cfg.clientValidate();
        }
        return true;
    }

    form.addEventListener('submit', function (e) {
        if (form.dataset.sfSkipGuard === '1') {
            delete form.dataset.sfSkipGuard;
            return;
        }

        if (!runClientValidation()) {
            e.preventDefault();
            return;
        }

        e.preventDefault();

        var body = new FormData(form);
        var submitBtn = form.querySelector(cfg.submitSelector || 'button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        fetch(cfg.checkUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: body,
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                var conflicts = (result.data && result.data.conflicts) || [];
                if (!result.ok || (conflicts.length > 0 && !result.data.ok)) {
                    if (conflicts.length) {
                        showConflictToasts(conflicts);
                    } else {
                        showConflictToasts([
                            (result.data && result.data.message) ||
                                'Could not save: schedule conflict or validation error.',
                        ]);
                    }
                    return;
                }
                form.dataset.sfSkipGuard = '1';
                form.submit();
            })
            .catch(function () {
                showConflictToasts(['Could not verify the schedule. Please try again.']);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (cfg.submitLabel && submitBtn.dataset.sfOrigLabel) {
                        submitBtn.textContent = submitBtn.dataset.sfOrigLabel;
                    }
                }
            });
    });

})();
