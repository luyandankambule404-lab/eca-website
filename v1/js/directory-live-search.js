(function () {
    if (window.ecaDirectoryLiveBound) return;
    window.ecaDirectoryLiveBound = true;

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function wrapInput(input) {
        var form = input.closest('form');
        if (!form) return null;
        var wrap = form.closest('.eca-search-suggest-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'eca-search-suggest-wrap';
            form.parentNode.insertBefore(wrap, form);
            wrap.appendChild(form);
        }
        var list = wrap.querySelector('.eca-search-suggest');
        if (!list) {
            list = document.createElement('ul');
            list.className = 'eca-search-suggest';
            list.setAttribute('role', 'listbox');
            list.hidden = true;
            wrap.appendChild(list);
        }
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('autocomplete', 'off');
        return { form: form, wrap: wrap, list: list };
    }

    function industryValue(form) {
        var select = form ? form.querySelector('select[name="industry"]') : null;
        return select ? String(select.value || '') : '';
    }

    function bind(input, options) {
        if (!input || input.getAttribute('data-eca-live-search') === '1') return;
        var parts = wrapInput(input);
        if (!parts) return;
        input.setAttribute('data-eca-live-search', '1');

        var list = parts.list;
        var form = parts.form;
        var wrap = parts.wrap;
        var timer = null;
        var lastQ = '';
        var items = [];
        var active = -1;
        var open = false;
        var updateResults = options && options.updateResults;

        function placeList() {
            var rect = input.getBoundingClientRect();
            var width = Math.max(rect.width, Math.min(520, window.innerWidth - 24));
            list.style.position = 'fixed';
            list.style.top = Math.round(rect.bottom + 6) + 'px';
            list.style.left = 'auto';
            list.style.right = Math.max(12, Math.round(window.innerWidth - rect.right)) + 'px';
            list.style.width = width + 'px';
            list.style.minWidth = width + 'px';
            list.style.maxHeight = 'min(70vh, 560px)';
            list.style.zIndex = '4000';
        }

        function closeList() {
            open = false;
            active = -1;
            list.hidden = true;
            list.classList.remove('is-open');
        }

        function setActive(index) {
            var buttons = list.querySelectorAll('.eca-search-suggest-item');
            if (!buttons.length) return;
            if (index < 0) index = buttons.length - 1;
            if (index >= buttons.length) index = 0;
            active = index;
            buttons.forEach(function (btn, i) {
                btn.classList.toggle('is-active', i === active);
            });
            buttons[active].scrollIntoView({ block: 'nearest' });
        }

        function renderList(rows, q, total) {
            items = rows || [];
            active = items.length ? 0 : -1;
            if (!q) {
                closeList();
                return;
            }
            if (!items.length) {
                list.innerHTML = '<li class="eca-search-suggest-empty">No matching names</li>';
            } else {
                var extra = total > items.length
                    ? '<li class="eca-search-suggest-more">' + esc(String(total)) + ' matching names</li>'
                    : '';
                list.innerHTML = extra + items.map(function (row, i) {
                    var meta = [row.number, row.industry, row.region].filter(Boolean).join(' · ');
                    return '<li role="presentation">' +
                        '<button type="button" class="eca-search-suggest-item' + (i === 0 ? ' is-active' : '') + '" role="option" data-index="' + i + '">' +
                        '<strong>' + esc(row.name) + '</strong>' +
                        (meta ? '<small>' + esc(meta) + '</small>' : '') +
                        '</button></li>';
                }).join('');
            }
            open = true;
            list.hidden = false;
            list.classList.add('is-open');
            placeList();
        }

        function pick(index) {
            var row = items[index];
            if (!row) return;
            input.value = row.name;
            closeList();
            window.location.href = '/directory.php?search=' + encodeURIComponent(row.name);
        }

        function fetchSuggest() {
            var q = String(input.value || '').trim();
            var industry = industryValue(form);
            if (!q && !industry) {
                input._ecaLastQ = '';
                lastQ = '';
                closeList();
                if (updateResults) updateResults([], '', 0, true);
                return;
            }
            if (q && q === input._ecaLastQ) return;
            var params = new URLSearchParams();
            if (q) params.set('search', q);
            params.set('limit', updateResults ? '2000' : '600');
            if (industry) params.set('industry', industry);
            fetch('/directory-suggest.php?' + params.toString(), { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (String(input.value || '').trim() !== q) return;
                    input._ecaLastQ = q;
                    var rows = (data && data.items) ? data.items : [];
                    var total = data && data.total ? data.total : rows.length;
                    if (q) renderList(rows, q, total);
                    else closeList();
                    if (updateResults) updateResults(rows, q, total, false);
                })
                .catch(function () {
                    closeList();
                });
        }

        function scheduleFetch() {
            clearTimeout(timer);
            var q = String(input.value || '').trim();
            timer = setTimeout(fetchSuggest, q.length <= 1 ? 40 : 120);
        }

        input.addEventListener('input', scheduleFetch);
        input.addEventListener('keyup', scheduleFetch);
        input.addEventListener('search', scheduleFetch);
        input.addEventListener('focus', function () {
            lastQ = '';
            scheduleFetch();
        });
        window.addEventListener('resize', function () {
            if (open) placeList();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeList();
                return;
            }
            if (!open) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(active + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(active - 1);
            } else if (e.key === 'Enter' && active >= 0 && items.length && e.ctrlKey) {
                e.preventDefault();
                pick(active);
            }
        });

        list.addEventListener('mousedown', function (e) {
            var btn = e.target.closest('.eca-search-suggest-item');
            if (!btn) return;
            e.preventDefault();
            pick(parseInt(btn.getAttribute('data-index'), 10));
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) closeList();
        });
    }

    function cardHtml(row) {
        var name = esc(row.name || '');
        var type = esc(row.industry || '');
        var email = esc(row.email || '');
        var phone = esc(row.phone || '');
        var region = esc(row.region || '');
        var id = esc(row.id || '');
        var typeLink = type
            ? '<a href="/directory.php?industry=' + encodeURIComponent(row.industry || '') + '">' + type + '</a>'
            : '';
        return '<div class="col-md-4">' +
            '<div class="card company-card h-100 shadow-sm">' +
            '<div class="card-header"><a href="/contractor.php?id=' + id + '" style="color:inherit;text-decoration:none;">' + name + '</a></div>' +
            '<div class="card-body">' +
            '<p><i class="bi bi-diagram-3"></i> <strong>Classification:</strong> ' + typeLink + '</p>' +
            '<p><i class="bi bi-check-circle-fill"></i> <strong>Status:</strong> <span class="badge">Active</span></p>' +
            '<p><i class="bi bi-envelope-fill"></i> <strong>Email:</strong> ' + email + '</p>' +
            '<p><i class="bi bi-telephone-fill"></i> <strong>Phone:</strong> ' + phone + '</p>' +
            '<p><i class="bi bi-geo-alt-fill"></i> <strong>Region:</strong> ' + region + '</p>' +
            '</div>' +
            '<div class="card-footer">' +
            (email ? '<a href="mailto:' + email + '"><i class="bi bi-envelope"></i> Email</a>' : '') +
            (phone ? '<a href="tel:' + phone + '"><i class="bi bi-telephone"></i> Call</a>' : '') +
            '</div></div></div>';
    }

    function bindDirectoryResults(input) {
        var results = document.getElementById('directoryResults');
        if (!results) {
            bind(input);
            return;
        }
        var summary = document.getElementById('directoryFilterSummary');
        var empty = document.getElementById('directoryEmpty');
        var pager = document.getElementById('directoryPagination');
        var original = results.innerHTML;
        var originalSummary = summary ? summary.innerHTML : '';
        var originalPager = pager ? pager.style.display : '';

        bind(input, {
            updateResults: function (rows, q, total, restore) {
                if (restore) {
                    results.innerHTML = original;
                    if (summary) summary.innerHTML = originalSummary;
                    if (pager) pager.style.display = originalPager;
                    if (empty) empty.hidden = original.indexOf('No results found') === -1;
                    return;
                }
                if (pager) pager.style.display = 'none';
                if (!rows.length) {
                    results.innerHTML = '<p id="directoryEmpty">No names match this letter or search.</p>';
                    if (summary) summary.textContent = 'No matching names.';
                    return;
                }
                if (summary) {
                    if (!q) {
                        summary.textContent = (total === 1 ? '1 company' : total + ' companies') + ' below.';
                    } else {
                        summary.innerHTML = (total === 1 ? '1 name matches' : total + ' names match') +
                            ' <span>&ldquo;' + esc(q) + '&rdquo;</span>' +
                            (total > rows.length ? ' (showing ' + rows.length + ')' : '') + '.';
                    }
                }
                results.innerHTML = rows.map(cardHtml).join('');
            }
        });
    }

    function applyTypeFilter(industry) {
        var results = document.getElementById('directoryResults');
        var input = document.getElementById('directorySearch');
        var form = document.querySelector('.eca-directory-search');
        var select = form ? form.querySelector('select[name="industry"]') : null;
        var summary = document.getElementById('directoryFilterSummary');
        var pager = document.getElementById('directoryPagination');
        if (input) {
            input.value = '';
            input._ecaLastQ = '';
        }
        if (select) select.value = industry || '';
        document.querySelectorAll('.type-chips a').forEach(function (chip) {
            var href = chip.getAttribute('href') || '';
            var active = industry
                ? href.indexOf('industry=' + encodeURIComponent(industry)) !== -1
                : href.indexOf('industry=') === -1;
            chip.classList.toggle('is-active', active);
        });
        try {
            history.replaceState({}, '', industry ? '/directory.php?industry=' + encodeURIComponent(industry) : '/directory.php');
        } catch (err) {}
        if (!results) {
            window.location.href = industry ? '/directory.php?industry=' + encodeURIComponent(industry) : '/directory.php';
            return;
        }
        var params = new URLSearchParams();
        if (industry) params.set('industry', industry);
        params.set('limit', '2000');
        fetch('/directory-suggest.php?' + params.toString(), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var rows = (data && data.items) ? data.items : [];
                var total = data && data.total ? data.total : rows.length;
                if (pager) pager.style.display = 'none';
                if (!rows.length) {
                    results.innerHTML = '<p id="directoryEmpty">No companies match this type.</p>';
                    if (summary) summary.textContent = industry ? ('No ' + industry + ' companies.') : 'No companies.';
                    return;
                }
                if (summary) {
                    summary.textContent = industry
                        ? ('Showing only ' + industry + ' companies (' + total + ').')
                        : ('Showing all companies (' + total + ').');
                }
                results.innerHTML = rows.map(cardHtml).join('');
            })
            .catch(function () {
                window.location.href = industry ? '/directory.php?industry=' + encodeURIComponent(industry) : '/directory.php';
            });
    }

    function bindTypeFilters() {
        var chips = document.querySelectorAll('.type-chips a');
        var form = document.querySelector('.eca-directory-search');
        var select = form ? form.querySelector('select[name="industry"]') : null;
        if (!chips.length && !select) return;
        chips.forEach(function (chip) {
            chip.addEventListener('click', function (e) {
                e.preventDefault();
                var href = chip.getAttribute('href') || '';
                var industry = '';
                try {
                    industry = new URL(href, window.location.origin).searchParams.get('industry') || '';
                } catch (err) {}
                applyTypeFilter(industry);
            });
        });
        if (select) {
            select.addEventListener('change', function () {
                applyTypeFilter(select.value || '');
            });
        }
    }

    function boot() {
        var directoryInput = document.getElementById('directorySearch');
        if (directoryInput) bindDirectoryResults(directoryInput);
        var headerInput = document.getElementById('ecaSiteSearch');
        if (headerInput) bind(headerInput);
        var homeInput = document.getElementById('homeContractorSearch');
        if (homeInput && homeInput !== directoryInput) bind(homeInput);
        bindTypeFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
