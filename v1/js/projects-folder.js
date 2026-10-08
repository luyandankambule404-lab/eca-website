(function () {
    var toggle = document.querySelector('.pf-toggle');
    var overlay = document.querySelector('.pf-overlay');

    function setOpen(open) {
        document.body.classList.toggle('pf-nav-open', open);
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            setOpen(!document.body.classList.contains('pf-nav-open'));
        });
    }
    if (overlay) {
        overlay.addEventListener('click', function () {
            setOpen(false);
        });
    }
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    document.querySelectorAll('[data-pf-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById(button.getAttribute('data-pf-toggle'));
            if (!form) {
                return;
            }
            form.hidden = !form.hidden;
        });
    });

    document.querySelectorAll('form[data-pf-autosave]').forEach(function (form) {
        form.querySelectorAll('input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', function () {
                form.requestSubmit();
            });
        });
    });
})();
