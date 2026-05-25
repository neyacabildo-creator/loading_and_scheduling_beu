/**
 * Download schedule card (.sched-paper) inside an iframe or document as PNG.
 */
(function (global) {
    function loadHtml2Canvas() {
        if (typeof global.html2canvas === 'function') {
            return Promise.resolve(global.html2canvas);
        }
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-schedule-card-h2c]');
            if (existing) {
                existing.addEventListener('load', function () {
                    resolve(global.html2canvas);
                });
                existing.addEventListener('error', reject);
                return;
            }
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
            script.crossOrigin = 'anonymous';
            script.setAttribute('data-schedule-card-h2c', '1');
            script.onload = function () {
                resolve(global.html2canvas);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function sanitizeFilename(name) {
        return String(name || 'schedule-card')
            .replace(/[^A-Za-z0-9._-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') || 'schedule-card';
    }

    async function downloadFromDocument(doc, filename) {
        if (!doc) {
            alert('Schedule card is not ready yet.');
            return;
        }
        var paper = doc.querySelector('.sched-paper');
        if (!paper) {
            alert('Schedule card could not be found.');
            return;
        }
        try {
            var html2canvas = await loadHtml2Canvas();
            var canvas = await html2canvas(paper, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
            });
            var link = document.createElement('a');
            link.download = sanitizeFilename(filename) + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (err) {
            console.error(err);
            alert('Could not download schedule image. Please try Print instead.');
        }
    }

    async function downloadFromIframe(iframe, filename) {
        if (!iframe) {
            return;
        }
        var doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
        if (!doc || !doc.querySelector('.sched-paper')) {
            alert('Schedule card is still loading. Please wait a moment and try again.');
            return;
        }
        await downloadFromDocument(doc, filename);
    }

    global.ScheduleCardPhotoDownload = {
        downloadFromIframe: downloadFromIframe,
        downloadFromDocument: downloadFromDocument,
    };
})(typeof window !== 'undefined' ? window : this);
