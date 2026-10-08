(function () {
    var PAGE_SIZE = 6;
    var SKIP_IDS = {
        applicationsTable: true,
        learnersTable: true,
        paymentsTable: true,
        studentsTable: true
    };

    function isDataTable(table) {
        if (table.closest('.dataTables_wrapper')) {
            return true;
        }
        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable && typeof jQuery.fn.dataTable.isDataTable === 'function') {
            return jQuery.fn.dataTable.isDataTable(table);
        }
        return false;
    }

    function shouldSkip(table) {
        if (!table || table.getAttribute('data-hub-paged') === '1') {
            return true;
        }
        if (SKIP_IDS[table.id]) {
            return true;
        }
        if (table.getAttribute('data-dash-server-page') === '1') {
            return true;
        }
        if (table.getAttribute('data-no-paginate') === '1') {
            return true;
        }
        if (table.getAttribute('data-no-client-page') === '1') {
            return true;
        }
        if (table.closest('nav, header, footer, .hub-topbar, .hub-sidebar, .eca-print, .certificate')) {
            return true;
        }
        if (isDataTable(table)) {
            return true;
        }
        if (!table.tHead || !table.tBodies || !table.tBodies.length) {
            return true;
        }
        return false;
    }

    function dataRows(table) {
        var rows = [];
        Array.prototype.forEach.call(table.tBodies, function (body) {
            Array.prototype.forEach.call(body.rows, function (row) {
                if (row.getAttribute('data-hub-empty-row') === '') {
                    return;
                }
                if (row.getAttribute('data-hub-empty-row') === '1') {
                    return;
                }
                if (row.classList.contains('dataTables_empty')) {
                    return;
                }
                if (row.querySelector('.dataTables_empty')) {
                    return;
                }
                var cells = row.cells;
                if (cells.length === 1 && parseInt(cells[0].getAttribute('colspan') || '1', 10) > 1) {
                    var text = (cells[0].textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                    if (text === '' || text.indexOf('no ') === 0 || text.indexOf('none') === 0) {
                        return;
                    }
                }
                rows.push(row);
            });
        });
        return rows;
    }

    function renderPager(host, page, pages, total, onPage) {
        var nav = host.querySelector(':scope > .dash-pager[data-hub-client-pager="1"]');
        if (pages < 2) {
            if (nav) {
                nav.remove();
            }
            return;
        }
        if (!nav) {
            nav = document.createElement('nav');
            nav.className = 'dash-pager';
            nav.setAttribute('data-hub-client-pager', '1');
            nav.setAttribute('aria-label', 'Table pages');
            host.appendChild(nav);
        }
        var start = ((page - 1) * PAGE_SIZE) + 1;
        var end = Math.min(total, page * PAGE_SIZE);
        var buttons = [];
        if (pages <= 7) {
            for (var n = 1; n <= pages; n += 1) {
                buttons.push(n);
            }
        } else {
            buttons.push(1);
            var from = Math.max(2, page - 1);
            var to = Math.min(pages - 1, page + 1);
            if (from > 2) {
                buttons.push('…');
            }
            for (var i = from; i <= to; i += 1) {
                buttons.push(i);
            }
            if (to < pages - 1) {
                buttons.push('…');
            }
            buttons.push(pages);
        }
        var html = '<div class="dash-pager-bar">';
        html += '<p class="dash-pager-meta">Showing ' + start + '–' + end + ' of ' + total + '</p>';
        html += '<div class="dash-pager-buttons">';
        html += page > 1
            ? '<button type="button" class="dash-pager-btn" data-hub-page="' + (page - 1) + '">Previous</button>'
            : '<span class="dash-pager-btn" aria-disabled="true">Previous</span>';
        buttons.forEach(function (item) {
            if (item === '…') {
                html += '<span class="dash-pager-ellipsis">…</span>';
            } else if (item === page) {
                html += '<span class="dash-pager-btn is-active" aria-current="page">' + item + '</span>';
            } else {
                html += '<button type="button" class="dash-pager-btn" data-hub-page="' + item + '">' + item + '</button>';
            }
        });
        html += page < pages
            ? '<button type="button" class="dash-pager-btn" data-hub-page="' + (page + 1) + '">Next</button>'
            : '<span class="dash-pager-btn" aria-disabled="true">Next</span>';
        html += '</div></div>';
        nav.innerHTML = html;
        nav.querySelectorAll('[data-hub-page]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                onPage(parseInt(btn.getAttribute('data-hub-page') || '1', 10));
            });
        });
    }

    function pageTable(table) {
        if (shouldSkip(table)) {
            return;
        }
        var rows = dataRows(table);
        if (rows.length <= PAGE_SIZE) {
            return;
        }
        table.setAttribute('data-hub-paged', '1');
        var host = table.parentElement || table;
        var page = 1;
        var pages = Math.ceil(rows.length / PAGE_SIZE);

        function draw(next) {
            page = Math.max(1, Math.min(pages, next));
            rows.forEach(function (row, index) {
                var show = index >= (page - 1) * PAGE_SIZE && index < page * PAGE_SIZE;
                row.hidden = !show;
                row.style.display = show ? '' : 'none';
            });
            renderPager(host, page, pages, rows.length, draw);
        }

        draw(1);
    }

    function boot() {
        var roots = document.querySelectorAll('main, .hub-main, .hub-page, .card-body, .container, .table-wrap');
        var seen = [];
        function visit(table) {
            if (seen.indexOf(table) !== -1) {
                return;
            }
            seen.push(table);
            pageTable(table);
        }
        if (!roots.length) {
            document.querySelectorAll('table').forEach(visit);
            return;
        }
        Array.prototype.forEach.call(roots, function (root) {
            root.querySelectorAll('table').forEach(visit);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            boot();
            setTimeout(boot, 400);
        });
    } else {
        boot();
        setTimeout(boot, 400);
    }
})();
