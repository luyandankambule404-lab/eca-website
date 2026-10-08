(function () {
    function markRowReviewed(form) {
        var host = form.closest('tr') || form.closest('.hub-doc-toolbar') || form.parentElement;
        var status = host ? host.querySelector('.hub-doc-status') : null;
        if (status) {
            status.classList.remove('is-pending');
            status.classList.add('is-reviewed');
            status.textContent = 'Reviewed';
        }
        var btn = form.querySelector('.hub-doc-review');
        var slot = document.createElement('span');
        slot.className = 'hub-doc-review-slot';
        slot.setAttribute('aria-hidden', 'true');
        if (btn) {
            slot.style.width = btn.offsetWidth + 'px';
            slot.style.height = btn.offsetHeight + 'px';
        }
        form.replaceWith(slot);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('hub-doc-review-form')) {
            return;
        }
        event.preventDefault();
        if (form.getAttribute('data-hub-reviewing') === '1') {
            return;
        }
        form.setAttribute('data-hub-reviewing', '1');
        var btn = form.querySelector('.hub-doc-review');
        if (btn) {
            btn.disabled = true;
        }
        var body = new FormData(form);
        body.set('ajax', '1');
        fetch(form.getAttribute('action') || location.href, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
            }
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok && data && data.ok, data: data };
            }, function () {
                return { ok: false };
            });
        }).then(function (result) {
            if (!result.ok) {
                form.removeAttribute('data-hub-reviewing');
                if (btn) {
                    btn.disabled = false;
                }
                return;
            }
            markRowReviewed(form);
        }).catch(function () {
            form.removeAttribute('data-hub-reviewing');
            if (btn) {
                btn.disabled = false;
            }
        });
    });
})();
