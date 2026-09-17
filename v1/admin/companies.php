<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
eca_admin_require();
header('Cache-Control: no-store, no-cache, must-revalidate');

$conn = eca_admin_db();
$search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$industry = eca_request_industry();
$page = max(1, (int) ($_GET['page'] ?? 1));
$types = eca_directory_industries();
$typeCounts = [];
$result = ['rows' => [], 'total' => 0];
$allCount = 0;
$chipBase = '/admin/companies.php';
if ($conn) {
    $typeCounts = eca_directory_type_counts($conn, 'members');
    $searching = $search !== '';
    $limit = ($industry !== '' || $searching) ? 1000 : 40;
    $offset = ($industry !== '' || $searching) ? 0 : (($page - 1) * $limit);
    $result = eca_fetch_directory($conn, $search, $industry, $limit, $offset, 'members');
    $allCount = ($industry === '') ? $result['total'] : eca_fetch_directory($conn, $search, '', 1, 0, 'members')['total'];
}
$totalPages = ($industry === '' && $search === '' && $result['total'] > 0) ? (int) ceil($result['total'] / 40) : 1;
$heading = $industry !== ''
    ? eca_admin_h($industry) . ' companies (' . (int) $result['total'] . ')'
    : 'All companies (' . (int) $result['total'] . ')';

eca_admin_hub_start('Companies', 'companies');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= $heading ?></h1>
    <p><?php
        if ($search !== '') {
            echo 'Results for this search are listed below.';
        } elseif ($industry === '') {
            echo 'Showing every company. Click a type to narrow the list.';
        } else {
            echo 'Showing only ' . eca_admin_h($industry) . ' companies.';
        }
    ?></p>
</div>
<?php require __DIR__ . '/../includes/type-chips.php'; ?>
<form id="company-search-form" class="hub-card" method="get" action="/admin/companies.php" role="search" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <div class="company-suggest-wrap">
        <input id="company-search" type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Search name, region, number" autocomplete="off" aria-autocomplete="list" aria-controls="company-suggest-list" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <ul id="company-suggest-list" class="company-suggest-list" role="listbox" hidden></ul>
    </div>
    <select name="industry" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All industries</option>
        <?php foreach ($types as $opt): ?>
            <option value="<?= $opt ?>"<?= strcasecmp($industry, $opt) === 0 ? ' selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?php if ($search !== ''): ?>
        <a class="hub-home-link" href="<?= eca_admin_h(eca_type_filter_url($chipBase, '', $industry)) ?>">Clear</a>
    <?php endif; ?>
</form>
<script>
(function () {
    var input = document.getElementById('company-search');
    var list = document.getElementById('company-suggest-list');
    var form = document.getElementById('company-search-form');
    var industry = form ? form.querySelector('[name="industry"]') : null;
    var summary = document.getElementById('company-results-summary');
    var tbody = document.getElementById('company-results-body');
    if (!input || !list || !form) return;

    var chips = document.querySelectorAll('.type-chips a');
    var timer = null;
    var lastQ = '';
    var items = [];
    var active = -1;
    var open = false;

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function closeList() {
        open = false;
        active = -1;
        list.hidden = true;
        list.classList.remove('is-open');
        list.innerHTML = '';
    }

    function setActive(index) {
        var buttons = list.querySelectorAll('.company-suggest-item');
        if (!buttons.length) return;
        if (index < 0) index = buttons.length - 1;
        if (index >= buttons.length) index = 0;
        active = index;
        buttons.forEach(function (btn, i) {
            btn.classList.toggle('is-active', i === active);
        });
        buttons[active].scrollIntoView({ block: 'nearest' });
    }

    function currentIndustry() {
        return industry ? industry.value : '';
    }

    function syncChips(q) {
        chips.forEach(function (a) {
            try {
                var url = new URL(a.href, window.location.origin);
                if (q) url.searchParams.set('search', q);
                else url.searchParams.delete('search');
                url.searchParams.delete('page');
                a.href = url.pathname + url.search;
            } catch (err) {}
        });
    }

    function typeLink(type, q) {
        var url = '/admin/companies.php';
        var params = [];
        if (q) params.push('search=' + encodeURIComponent(q));
        if (type) params.push('industry=' + encodeURIComponent(type));
        return params.length ? url + '?' + params.join('&') : url;
    }

    function renderTable(rows, q, total) {
        if (summary) {
            if (!q) {
                summary.textContent = (total || rows.length) + ' companies below';
            } else if (total === 1 || rows.length === 1 && total <= 1) {
                summary.innerHTML = '1 company matches <span>&ldquo;' + esc(q) + '&rdquo;</span>';
            } else {
                var shown = rows.length;
                var label = (total || shown) + ' companies match <span>&ldquo;' + esc(q) + '&rdquo;</span>';
                if (total > shown) label += ' <span>(showing ' + shown + ')</span>';
                summary.innerHTML = label;
            }
        }
        if (!tbody) return;
        var wrap = tbody.closest('.hub-card');
        var empty = document.getElementById('company-results-empty');
        if (!rows.length) {
            tbody.innerHTML = '';
            if (wrap) wrap.style.display = 'none';
            if (!empty) {
                empty = document.createElement('p');
                empty.id = 'company-results-empty';
                empty.className = 'hub-card';
                empty.textContent = 'No companies match this search.';
                var section = document.getElementById('company-search-results');
                if (section) section.appendChild(empty);
            }
            empty.style.display = '';
            return;
        }
        if (empty) empty.style.display = 'none';
        if (wrap) wrap.style.display = '';
        tbody.innerHTML = rows.map(function (row) {
            var type = row.industry || '';
            var typeCell = type
                ? '<a class="industry-link" href="' + esc(typeLink(type, q)) + '">' + esc(type) + '</a>'
                : '—';
            return '<tr>' +
                '<td>' + esc(row.id) + '</td>' +
                '<td>' + esc(row.name) + '</td>' +
                '<td>' + esc(row.number) + '</td>' +
                '<td>' + typeCell + '</td>' +
                '<td>' + esc(row.region) + '</td>' +
                '<td>' + esc(row.email) + '</td>' +
                '<td>' + esc(row.phone) + '</td>' +
                '<td>' + esc(row.status) + '</td>' +
                '</tr>';
        }).join('');
    }

    function renderList(rows, q, total) {
        items = rows || [];
        active = items.length ? 0 : -1;
        if (!q || q.length < 2) {
            closeList();
            return;
        }
        if (!items.length) {
            list.innerHTML = '<li class="company-suggest-empty">No matching companies</li>';
        } else {
            list.innerHTML = items.map(function (row, i) {
                var meta = [row.number, row.industry, row.region].filter(Boolean).join(' · ');
                return '<li role="presentation">' +
                    '<button type="button" class="company-suggest-item' + (i === 0 ? ' is-active' : '') + '" role="option" data-index="' + i + '">' +
                    '<strong>' + esc(row.name) + '</strong>' +
                    (meta ? '<small>' + esc(meta) + '</small>' : '') +
                    '</button></li>';
            }).join('');
        }
        open = true;
        list.hidden = false;
        list.classList.add('is-open');
    }

    function pick(index) {
        var row = items[index];
        if (!row) return;
        input.value = row.name;
        syncChips(row.name);
        renderTable([row], row.name, 1);
        closeList();
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('search', row.name);
            if (currentIndustry()) url.searchParams.set('industry', currentIndustry());
            else url.searchParams.delete('industry');
            url.searchParams.delete('page');
            history.replaceState({}, '', url.pathname + url.search);
        } catch (err) {}
        input.focus();
    }

    function fetchSuggest() {
        var q = input.value.trim();
        syncChips(q);
        if (q.length < 2) {
            lastQ = q;
            closeList();
            return;
        }
        if (q === lastQ) return;
        var params = new URLSearchParams();
        params.set('search', q);
        if (currentIndustry()) params.set('industry', currentIndustry());
        fetch('/admin/companies-suggest.php?' + params.toString(), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (input.value.trim() !== q) return;
                lastQ = q;
                var rows = (data && data.items) ? data.items : [];
                var total = data && data.total ? data.total : rows.length;
                renderList(rows, q, total);
                renderTable(rows, q, total);
            })
            .catch(function () {
                closeList();
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(fetchSuggest, 300);
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
        } else if (e.key === 'Enter' && active >= 0 && items.length) {
            e.preventDefault();
            pick(active);
        }
    });

    list.addEventListener('mousedown', function (e) {
        var btn = e.target.closest('.company-suggest-item');
        if (!btn) return;
        e.preventDefault();
        pick(parseInt(btn.getAttribute('data-index'), 10));
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.company-suggest-wrap')) closeList();
    });
})();
</script>
<section id="company-search-results" class="company-results">
    <p class="company-results-summary" id="company-results-summary">
        <?php if ($search !== ''): ?>
            <?= (int) $result['total'] === 1 ? '1 company matches' : (int) $result['total'] . ' companies match' ?>
            <span>&ldquo;<?= eca_admin_h($search) ?>&rdquo;</span>
            <?php if ($industry !== ''): ?>
                <span>in <?= eca_admin_h($industry) ?></span>
            <?php endif; ?>
        <?php else: ?>
            <?= (int) $result['total'] ?> companies below
            <?php if ($industry !== ''): ?>
                <span>(<?= eca_admin_h($industry) ?>)</span>
            <?php endif; ?>
        <?php endif; ?>
    </p>
<?php if (!$result['rows']): ?>
    <p class="hub-card" id="company-results-empty"><?= $conn ? 'No companies match this search.' : 'The local database is not available.' ?></p>
<?php else: ?>
<div class="hub-card" style="padding:0;overflow:auto;">
    <table class="hub-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Company</th>
                <th>Reg. no.</th>
                <th>Industry</th>
                <th>Region</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody id="company-results-body">
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= eca_admin_h($row['id'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['TradingName'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['MembershipNumber'] ?? '') ?></td>
                    <td><?php
                        $rowType = trim((string) ($row['Clasification'] ?? ''));
                        if ($rowType !== ''):
                    ?><a class="industry-link" href="<?= eca_admin_h(eca_type_filter_url($chipBase, $search, $rowType)) ?>"><?= eca_admin_h($rowType) ?></a><?php
                        else:
                            echo '—';
                        endif;
                    ?></td>
                    <td><?= eca_admin_h($row['Region'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['EmailAddress'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['Cellphone'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['Status'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
</section>
<?php if ($industry === '' && $totalPages > 1): ?>
<p class="pager" style="margin-top:16px;">
    <?php for ($i = 1; $i <= min($totalPages, 20); $i++): ?>
        <a href="<?= eca_admin_h(eca_type_filter_url($chipBase, $search, $industry, $i)) ?>"><?= $i ?></a>
    <?php endfor; ?>
</p>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
