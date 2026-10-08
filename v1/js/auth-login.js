(function () {
    document.querySelectorAll('[data-auth-toggle-password]').forEach(function (btn) {
        var targetId = btn.getAttribute('data-auth-toggle-password');
        var input = targetId ? document.getElementById(targetId) : null;
        if (!input) {
            return;
        }
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.remove(show ? 'fa-eye' : 'fa-eye-slash');
                icon.classList.add(show ? 'fa-eye-slash' : 'fa-eye');
            }
        });
    });

    document.querySelectorAll('form[data-auth-submit]').forEach(function (form) {
        var btn = form.querySelector('[type="submit"]');
        if (!btn) {
            return;
        }
        var busy = btn.getAttribute('data-auth-busy') || 'Signing in…';
        form.addEventListener('submit', function () {
            if (btn.name) {
                var keep = document.createElement('input');
                keep.type = 'hidden';
                keep.name = btn.name;
                keep.value = btn.value || '1';
                form.appendChild(keep);
            }
            btn.disabled = true;
            var inner = btn.querySelector('[data-auth-submit-label]');
            if (inner) {
                inner.textContent = busy;
            } else {
                btn.textContent = busy;
            }
        });
    });
})();
