import './bootstrap';

const authHeartbeatEnabled = document.body?.dataset?.authHeartbeat === 'true';

if (authHeartbeatEnabled) {
	const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

	const sendHeartbeat = () => {
		if (!csrfToken) {
			return;
		}

		fetch('/auth/heartbeat', {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': csrfToken,
				'X-Requested-With': 'XMLHttpRequest',
			},
			credentials: 'same-origin',
			keepalive: true,
		}).catch(() => {});
	};

	sendHeartbeat();
	window.setInterval(sendHeartbeat, 10000);

	document.addEventListener('visibilitychange', () => {
		if (document.visibilityState === 'visible') {
			sendHeartbeat();
		}
	});
}
