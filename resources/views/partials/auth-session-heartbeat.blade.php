{{-- Keeps auth_tab_last_seen fresh while the tab is open (RequireRecentAuthActivity). --}}
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    var intervalMs = 3 * 60 * 1000;

    function sendHeartbeat() {
        fetch('{{ route('auth.heartbeat') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            keepalive: true,
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = @json(route('login'));
            }
        }).catch(function () {});
    }

    sendHeartbeat();
    setInterval(sendHeartbeat, intervalMs);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') sendHeartbeat();
    });

    var loginUrl = @json(route('login'));
    var origFetch = window.fetch;
    if (typeof origFetch === 'function') {
        window.fetch = function (input, init) {
            return origFetch.call(this, input, init).then(function (res) {
                var url = typeof input === 'string' ? input : (input && input.url) || '';
                if (res.status === 401 && url.indexOf('auth/heartbeat') === -1) {
                    window.location.href = loginUrl;
                }
                return res;
            });
        };
    }
})();
</script>
