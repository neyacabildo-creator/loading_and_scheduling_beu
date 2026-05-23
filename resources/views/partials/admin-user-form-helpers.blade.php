<script>
(function() {
    window.capitalizePersonName = function(value) {
        return String(value || '').trim().replace(/\S+/g, function(word) {
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        });
    };

    window.bindAdminNameCapitalize = function(inputId) {
        const el = document.getElementById(inputId);
        if (!el || el.dataset.nameCapitalizeBound === '1') return;
        el.dataset.nameCapitalizeBound = '1';
        el.addEventListener('blur', function() {
            if (el.value.trim()) el.value = capitalizePersonName(el.value);
        });
    };

    window.resetAdminPasswordAutofill = function(passwordIds) {
        (passwordIds || []).forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.value = '';
            el.setAttribute('autocomplete', 'new-password');
            el.setAttribute('readonly', 'readonly');
            el.addEventListener('focus', function unlock() {
                el.removeAttribute('readonly');
                el.removeEventListener('focus', unlock);
            });
        });
        [0, 150, 400].forEach(function(ms) {
            setTimeout(function() {
                (passwordIds || []).forEach(function(id) {
                    const el = document.getElementById(id);
                    if (!el || document.activeElement === el) return;
                    if (!el.dataset.userTypedPassword) el.value = '';
                });
            }, ms);
        });
    };

    window.markAdminPasswordTyped = function(passwordIds) {
        (passwordIds || []).forEach(function(id) {
            const el = document.getElementById(id);
            if (!el || el.dataset.pwdTypedBound === '1') return;
            el.dataset.pwdTypedBound = '1';
            el.addEventListener('input', function() {
                if (el.value) el.dataset.userTypedPassword = '1';
            });
        });
    };

    window.clearAdminPasswordTypedFlags = function(passwordIds) {
        (passwordIds || []).forEach(function(id) {
            const el = document.getElementById(id);
            if (el) delete el.dataset.userTypedPassword;
        });
    };
})();
</script>
