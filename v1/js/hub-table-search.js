(function () {
    var SKIP_IDS = {
        applicationsTable: true,
        learnersTable: true,
        paymentsTable: true,
        studentsTable: true
    };

    function closestHost(el) {
        return el.closest('.hub-main, .hub-page, .card-body, .card-premium, .container, body') || document.body;
    }

    function bindServerSearch(input) {
        var form = input.form || input.closest('form');
        if (!form || String(form.method || 'get').toLowerCase() !== 'get') return false;
        var name = String(input.getAttribute('name') || '');
        if (name !== 'search' && name !== 'q') return false;
        if (input.getAttribute('data-dash-no-live-nav') === '1') return false;
        var scope = form.closest('.hub-main, .hub-page, .container, body') || document;
        if (!scope.querySelector('[data-dash-server-page="1"]')) return false;
        if (input.getAttribute('data-hub-search-bound') === '1') return true;
        input.setAttribute('data-hub-search-bound', '1');
        var timer = null;
        function go() {
            var action = form.getAttribute('action') || location.pathname;
            var data = new FormData(form);
            data.delete('page');
            var qs = new URLSearchParams(data).toString();
            var next = action + (qs ? '?' + qs : '');
            if (next !== (location.pathname + location.search)) {
                location.assign(next);
            }
        }
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(go, 400);
        });
        input.addEventListener('search', function () {
            clearTimeout(timer);
            go();
        });
        return true;
    }

    function bindInput(input) {
        if (input.getAttribute('data-hub-search-bound') === '1') return;
        if (bindServerSearch(input)) return;
    }

    function boot() {
        document.querySelectorAll('[data-hub-search]').forEach(bindInput);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
