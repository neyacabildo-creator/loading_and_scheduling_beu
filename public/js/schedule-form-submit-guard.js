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

    var activeSubmitBtn = null;

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

    function showWarningToast(message) {
        if (window.spupToast && typeof window.spupToast.warning === 'function') {
            window.spupToast.warning(message, 6000);
            return;
        }
        if (window.spupToast) {
            window.spupToast.error(message, 6000);
        }
    }

    function runClientValidation() {
        if (typeof cfg.clientValidate === 'function') {
            return cfg.clientValidate();
        }
        return true;
    }

    function parseResponse(res) {
        return res.text().then(function (text) {
            var data = {};
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    if (!res.ok) {
                        var snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
                        throw new Error(snippet || res.statusText || 'Server error');
                    }
                }
            }
            return { ok: res.ok, data: data };
        });
    }

    function validationMessages(data) {
        if (!data || !data.errors) {
            return [];
        }
        var out = [];
        Object.keys(data.errors).forEach(function (key) {
            (data.errors[key] || []).forEach(function (msg) {
                out.push(msg);
            });
        });
        return out;
    }

    function proceedSubmit() {
        form.dataset.sfSkipGuard = '1';
        var btn = activeSubmitBtn || form.querySelector(cfg.submitSelector || 'button[type="submit"]');
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(btn || undefined);
            return;
        }
        form.submit();
    }

    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeSubmitBtn = btn;
        });
    });

    form.addEventListener('submit', function (e) {
        if (form.dataset.sfSkipGuard === '1') {
            delete form.dataset.sfSkipGuard;
            return;
        }

        if (!runClientValidation()) {
            e.preventDefault();
            return;
        }

        if (typeof cfg.skipGridCheck === 'function' && cfg.skipGridCheck()) {
            return;
        }

        e.preventDefault();

        var submitBtn = activeSubmitBtn || form.querySelector(cfg.submitSelector || 'button[type="submit"]');
        if (submitBtn) {
            if (!submitBtn.dataset.sfOrigLabel) {
                submitBtn.dataset.sfOrigLabel = submitBtn.textContent;
            }
            submitBtn.disabled = true;
        }

        var body = new FormData(form);

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
            .then(parseResponse)
            .then(function (result) {
                var conflicts = (result.data && result.data.conflicts) || [];
                var explicitOk = result.data && Object.prototype.hasOwnProperty.call(result.data, 'ok')
                    ? !!result.data.ok
                    : result.ok;

                if (result.ok && explicitOk && conflicts.length === 0) {
                    proceedSubmit();
                    return;
                }

                if (conflicts.length) {
                    showConflictToasts(conflicts);
                    return;
                }

                var validation = validationMessages(result.data);
                if (validation.length) {
                    showConflictToasts(validation);
                    return;
                }

                if (!result.ok) {
                    showConflictToasts([
                        (result.data && result.data.message) ||
                            'Could not verify the schedule. Please review your entries.',
                    ]);
                    return;
                }

                proceedSubmit();
            })
            .catch(function (err) {
                showWarningToast(
                    (err && err.message) ||
                        'Could not verify conflicts online. Saving anyway — the server will validate your entries.'
                );
                proceedSubmit();
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.dataset.sfOrigLabel) {
                        submitBtn.textContent = submitBtn.dataset.sfOrigLabel;
                    }
                }
            });
    });
})();
