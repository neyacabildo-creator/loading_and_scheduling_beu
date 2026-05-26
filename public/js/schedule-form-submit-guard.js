/**
 * Optional pre-check before create-schedule form POST. Always ends in form.submit().
 */
(function () {
    'use strict';

    var cfg = window.SCHEDULE_FORM_GUARD;
    if (!cfg || !cfg.formId) {
        return;
    }

    var form = document.getElementById(cfg.formId);
    if (!form) {
        return;
    }

    var activeSubmitBtn = null;
    var pending = false;

    function getCsrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function resolveSubmitBtn() {
        return activeSubmitBtn || document.getElementById('sfSubmitBtn') || form.querySelector('button[type="submit"]');
    }

    function setSubmitBusy(busy) {
        var btn = resolveSubmitBtn();
        if (!btn) {
            return;
        }
        if (!btn.dataset.sfOrigLabel) {
            btn.dataset.sfOrigLabel = btn.textContent.trim();
        }
        if (busy) {
            btn.setAttribute('aria-busy', 'true');
            btn.textContent = 'Saving…';
        } else {
            btn.removeAttribute('aria-busy');
            btn.textContent = btn.dataset.sfOrigLabel;
        }
        // Never disable — disabled submitters block form.submit() / requestSubmit().
        btn.disabled = false;
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

    /** Native POST — does not re-fire this submit listener. */
    function postForm() {
        pending = false;
        setSubmitBusy(false);
        var btn = resolveSubmitBtn();
        if (btn) {
            btn.disabled = false;
        }
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.reportValidity();
            return;
        }
        form.submit();
    }

    function runPreCheckThenPost() {
        if (!cfg.checkUrl) {
            postForm();
            return;
        }

        pending = true;
        setSubmitBusy(true);

        fetch(cfg.checkUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: new FormData(form),
        })
            .then(parseResponse)
            .then(function (result) {
                var conflicts = (result.data && result.data.conflicts) || [];
                var explicitOk = result.data && Object.prototype.hasOwnProperty.call(result.data, 'ok')
                    ? !!result.data.ok
                    : result.ok;

                if (conflicts.length) {
                    pending = false;
                    setSubmitBusy(false);
                    showConflictToasts(conflicts);
                    return;
                }

                var validation = validationMessages(result.data);
                if (validation.length) {
                    pending = false;
                    setSubmitBusy(false);
                    showConflictToasts(validation);
                    return;
                }

                if (!result.ok && !explicitOk) {
                    pending = false;
                    setSubmitBusy(false);
                    showConflictToasts([
                        (result.data && result.data.message) ||
                            'Could not verify the schedule. Please review your entries.',
                    ]);
                    return;
                }

                postForm();
            })
            .catch(function () {
                // Server will validate again on store.
                postForm();
            })
            .finally(function () {
                if (pending) {
                    pending = false;
                    setSubmitBusy(false);
                }
            });
    }

    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeSubmitBtn = btn;
        });
    });

    form.addEventListener('submit', function (e) {
        if (pending) {
            e.preventDefault();
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

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        runPreCheckThenPost();
    });
})();
