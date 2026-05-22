@if(auth()->check())
<script>
(function () {
    const loginUrl = @json(route('login'));

    function forceLogin() {
        window.location.replace(loginUrl);
    }

    function verifySession() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) {
            forceLogin();
            return;
        }

        fetch(@json(url('/auth/heartbeat')), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                forceLogin();
            }
        }).catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', verifySession);
    } else {
        verifySession();
    }

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            verifySession();
        }
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            verifySession();
        }
    });
})();
</script>
@endif
