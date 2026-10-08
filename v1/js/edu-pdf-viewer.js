(function () {
    var stage = document.querySelector('[data-pdf-src]');
    if (!stage) {
        return;
    }

    var src = stage.getAttribute('data-pdf-src') || '';
    var frame = stage.querySelector('.edu-viewer-frame');
    var status = stage.querySelector('.edu-pdf-status');
    if (!src || !frame) {
        return;
    }

    function showError(message) {
        if (status) {
            status.hidden = false;
            status.textContent = message;
        }
        frame.hidden = true;
        frame.removeAttribute('src');
    }

    function showFrame(url) {
        if (status) {
            status.hidden = true;
        }
        frame.hidden = false;
        frame.src = url;
    }

    if (!window.fetch) {
        showFrame(src);
        return;
    }

    fetch(src, { credentials: 'same-origin', cache: 'no-store' })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('missing');
            }
            var type = (res.headers.get('content-type') || '').toLowerCase();
            return res.blob().then(function (blob) {
                return { blob: blob, type: type || (blob.type || '') };
            });
        })
        .then(function (result) {
            var type = result.type.toLowerCase();
            if (type.indexOf('pdf') === -1 && type.indexOf('octet-stream') === -1) {
                throw new Error('not-pdf');
            }
            var pdfBlob = result.blob.type === 'application/pdf'
                ? result.blob
                : new Blob([result.blob], { type: 'application/pdf' });
            showFrame(URL.createObjectURL(pdfBlob));
        })
        .catch(function () {
            showError('Preview is unavailable for this document. Use Download to save a copy.');
        });
})();
