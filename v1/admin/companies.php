<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/companies-intelligence.php';
require_once __DIR__ . '/../includes/portal-db.php';
eca_admin_require('companies.manage');
header('Cache-Control: no-store, no-cache, must-revalidate');

$conn = eca_admin_db();
$portal = eca_portal_pdo(false);
$search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$industry = eca_request_industry();
$status = trim((string) ($_GET['status'] ?? ''));
$hasRegistration = trim((string) ($_GET['has_registration'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'name'));
if ($hasRegistration !== '' && !in_array($hasRegistration, ['yes', 'no'], true)) {
    $hasRegistration = '';
}
$page = eca_pager_page();
$limit = eca_pager_limit();
$types = eca_directory_industries();
$typeCounts = [];
$result = ['rows' => [], 'total' => 0];
$allCount = 0;
$chipBase = '/admin/companies.php';
$intel = eca_ci_company_intelligence($conn, $portal);
$kpis = $intel['kpis'];

// Prefer direct companies table query when available so status/sort filters work on real columns.
$useDirect = $conn && eca_has_table($conn, 'companies') && eca_table_count($conn, 'companies') > 0;
if ($useDirect) {
    $filters = [
        'search' => $search,
        'industry' => $industry,
        'status' => $status,
        'has_registration' => $hasRegistration,
    ];
    $built = eca_ci_company_filters($filters);
    $order = eca_ci_company_sort($sort);
    $countSql = 'SELECT COUNT(*) FROM companies WHERE ' . $built['where'];
    $selectSql = 'SELECT id, name, registration_number, email, phone, address, industry, status, website, description
                  FROM companies WHERE ' . $built['where'] . ' ORDER BY ' . $order;
    $paged = eca_paged_query($conn, $countSql, $selectSql, $built['params'], $page, $limit);
    // Map to directory row shape used by the existing table UI.
    $mapped = [];
    foreach ($paged['rows'] as $row) {
        $mapped[] = [
            'id' => $row['id'] ?? '',
            'TradingName' => $row['name'] ?? '',
            'CompanyRegistrationName' => $row['name'] ?? '',
            'MembershipNumber' => $row['registration_number'] ?? '',
            'EmailAddress' => $row['email'] ?? '',
            'Cellphone' => $row['phone'] ?? '',
            'Region' => $row['address'] ?? '',
            'Clasification' => $row['industry'] ?? '',
            'Status' => $row['status'] ?? '',
        ];
    }
    $result = ['rows' => $mapped, 'total' => $paged['total']];
    $page = $paged['page'];
    $limit = $paged['limit'];
    $totalPages = $paged['pages'];
    $typeCounts = eca_directory_type_counts($conn, 'members');
    $allCount = ($industry === '' && $status === '' && $hasRegistration === '' && $search === '')
        ? (int) $kpis['companies_total']
        : (int) $result['total'];
    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $exportRows = eca_admin_export_rows($conn, $selectSql, $built['params']);
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['id'] ?? '',
                $row['name'] ?? '',
                $row['registration_number'] ?? '',
                $row['industry'] ?? '',
                $row['address'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['status'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-contractors', ['ID', 'Company', 'Reg. no.', 'Industry', 'Region', 'Email', 'Phone', 'Status'], $csv, 'companies');
    }
} elseif ($conn) {
    $typeCounts = eca_directory_type_counts($conn, 'members');
    $probe = eca_fetch_directory($conn, $search, $industry, 1, 0, 'members');
    $totalPagesProbe = $probe['total'] > 0 ? (int) ceil($probe['total'] / $limit) : 1;
    $page = eca_pager_redirect_if_out_of_range($page, $totalPagesProbe, (int) $probe['total']);
    $offset = ($page - 1) * $limit;
    $result = eca_fetch_directory($conn, $search, $industry, $limit, $offset, 'members');
    $allCount = ($industry === '') ? $result['total'] : eca_fetch_directory($conn, $search, '', 1, 0, 'members')['total'];
    $totalPages = $result['total'] > 0 ? (int) ceil($result['total'] / max(1, $limit)) : 1;
    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $export = eca_fetch_directory($conn, $search, $industry, 500, 0, 'members');
        $csv = [];
        foreach ($export['rows'] as $row) {
            $csv[] = [
                $row['id'] ?? '',
                $row['TradingName'] ?? '',
                $row['MembershipNumber'] ?? '',
                $row['Clasification'] ?? '',
                $row['Region'] ?? '',
                $row['EmailAddress'] ?? '',
                $row['Cellphone'] ?? '',
                $row['Status'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-contractors', ['ID', 'Company', 'Reg. no.', 'Industry', 'Region', 'Email', 'Phone', 'Status'], $csv, 'companies');
    }
}
$totalPages = $result['total'] > 0 ? (int) ceil($result['total'] / max(1, $limit)) : 1;
$listStart = $result['total'] === 0 ? 0 : (($page - 1) * $limit) + 1;
$listEnd = $result['total'] === 0 ? 0 : min((int) $result['total'], $page * $limit);
$heading = $industry !== ''
    ? eca_admin_h($industry) . ' companies (' . (int) $result['total'] . ')'
    : 'All companies (' . (int) $result['total'] . ')';

eca_admin_hub_start('Companies', 'companies');
?>
<style>
.ci-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:0 0 14px}
.ci-card{background:#fff;border:1px solid #d7dde8;border-radius:12px;padding:12px 14px}
.ci-card span{display:block;font-size:.72rem;color:#667;font-weight:700}
.ci-card strong{display:block;font-size:1.3rem;margin-top:4px}
.ci-card small{display:block;margin-top:3px;color:#6b7280;font-size:.7rem}
.ci-actions{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 12px}
</style>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= $heading ?></h1>
    <p>Directory companies from <code>eca_local.companies</code>. Membership matches are soft (registration number / email / name). Owner data lives on membership clients.</p>
</div>

<div class="ci-grid">
    <article class="ci-card"><span>Total companies</span><strong><?= (int) $kpis['companies_total'] ?></strong><small>companies COUNT(*)</small></article>
    <article class="ci-card"><span>Active</span><strong><?= (int) $kpis['companies_active'] ?></strong><small>status = active</small></article>
    <article class="ci-card"><span>Inactive</span><strong><?= (int) $kpis['companies_inactive'] ?></strong><small>inactive/disabled</small></article>
    <article class="ci-card"><span>Suspended</span><strong><?= (int) $kpis['companies_suspended'] ?></strong><small>status suspended</small></article>
    <article class="ci-card"><span>Matched to member</span><strong><?= (int) $kpis['matched_to_member'] ?></strong><small>reg = MembershipNumber</small></article>
    <article class="ci-card"><span>Active certificates</span><strong><?= (int) $kpis['with_active_certificate'] ?></strong><small>on matched members</small></article>
    <article class="ci-card"><span>Expired certificates</span><strong><?= (int) $kpis['with_expired_certificate'] ?></strong><small>on matched members</small></article>
    <article class="ci-card"><span>Owner rows</span><strong><?= (int) $kpis['owners_total'] ?></strong><small>portal owners table</small></article>
</div>
<div class="ci-actions">
    <a class="hub-btn" href="/admin/owners-report.php">Owners Report</a>
    <a class="hub-btn" href="/admin/companies.php?status=active">Active only</a>
    <a class="hub-btn" href="/admin/membership-report.php">Membership Intelligence</a>
</div>
<?php require __DIR__ . '/../includes/type-chips.php'; ?>
<form id="company-search-form" class="hub-card" method="get" action="/admin/companies.php" role="search" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <div class="company-suggest-wrap">
        <input id="company-search" type="search" name="search" data-dash-no-live-nav="1" value="<?= eca_admin_h($search) ?>" placeholder="Search name, region, number" autocomplete="off" aria-autocomplete="list" aria-controls="company-suggest-list" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <ul id="company-suggest-list" class="company-suggest-list" role="listbox" hidden></ul>
    </div>
    <select name="industry" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All industries</option>
        <?php foreach ($types as $opt): ?>
            <option value="<?= $opt ?>"<?= strcasecmp($industry, $opt) === 0 ? ' selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All statuses</option>
        <option value="active"<?= strcasecmp($status, 'active') === 0 ? ' selected' : '' ?>>Active</option>
        <option value="inactive"<?= strcasecmp($status, 'inactive') === 0 ? ' selected' : '' ?>>Inactive</option>
        <option value="suspended"<?= strcasecmp($status, 'suspended') === 0 ? ' selected' : '' ?>>Suspended</option>
    </select>
    <select name="has_registration" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">Registration #</option>
        <option value="yes"<?= $hasRegistration === 'yes' ? ' selected' : '' ?>>Has registration</option>
        <option value="no"<?= $hasRegistration === 'no' ? ' selected' : '' ?>>Missing registration</option>
    </select>
    <select name="sort" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="name"<?= $sort === 'name' ? ' selected' : '' ?>>Name A–Z</option>
        <option value="name_desc"<?= $sort === 'name_desc' ? ' selected' : '' ?>>Name Z–A</option>
        <option value="registration"<?= $sort === 'registration' ? ' selected' : '' ?>>Registration</option>
        <option value="industry"<?= $sort === 'industry' ? ' selected' : '' ?>>Industry</option>
        <option value="status"<?= $sort === 'status' ? ' selected' : '' ?>>Status</option>
        <option value="region"<?= $sort === 'region' ? ' selected' : '' ?>>Region</option>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
    <?php if ($search !== '' || $status !== '' || $hasRegistration !== ''): ?>
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
    var originalBody = tbody ? tbody.innerHTML : '';
    var originalSummary = summary ? summary.innerHTML : '';
    var originalEmpty = document.getElementById('company-results-empty');
    var originalEmptyDisplay = originalEmpty ? originalEmpty.style.display : '';

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

    function navigateWithSearch(q) {
        try {
            var url = new URL(window.location.href);
            if (q) url.searchParams.set('search', q);
            else url.searchParams.delete('search');
            url.searchParams.delete('page');
            if (currentIndustry()) url.searchParams.set('industry', currentIndustry());
            else url.searchParams.delete('industry');
            var next = url.pathname + url.search;
            if (next !== (location.pathname + location.search)) {
                location.assign(next);
            }
        } catch (err) {}
    }

    function renderList(rows, q, total) {
        items = (rows || []).slice(0, 12);
        active = items.length ? 0 : -1;
        if (!q) {
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
        closeList();
        navigateWithSearch(row.name);
        input.focus();
    }

    function fetchSuggest() {
        var q = input.value.trim();
        syncChips(q);
        if (!q) {
            lastQ = '';
            closeList();
            navigateWithSearch('');
            return;
        }
        if (q === lastQ) return;
        var params = new URLSearchParams();
        params.set('search', q);
        params.set('limit', '12');
        if (currentIndustry()) params.set('industry', currentIndustry());
        fetch('/admin/companies-suggest.php?' + params.toString(), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (input.value.trim() !== q) return;
                lastQ = q;
                var rows = (data && data.items) ? data.items : [];
                var total = data && data.total ? data.total : rows.length;
                renderList(rows, q, total);
            })
            .catch(function () {
                closeList();
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetchSuggest();
            navigateWithSearch(input.value.trim());
        }, 400);
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
            <?php if ((int) $result['total'] > $limit): ?>
                Showing <?= (int) $listStart ?>–<?= (int) $listEnd ?> of <?= (int) $result['total'] ?> companies
            <?php else: ?>
                <?= (int) $result['total'] ?> companies below
            <?php endif; ?>
            <?php if ($industry !== ''): ?>
                <span>(<?= eca_admin_h($industry) ?>)</span>
            <?php endif; ?>
        <?php endif; ?>
    </p>
<?php if (!$result['rows']): ?>
    <p class="hub-card" id="company-results-empty"><?= $conn ? 'No companies match this search.' : 'The local database is not available.' ?></p>
<?php else: ?>
<div class="hub-card eca-table-panel">
    <h5>Contractors</h5>
    <table class="hub-table" data-dash-server-page="1">
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
                <th></th>
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
                    <td>
                        <a href="/admin/company-detail.php?id=<?= (int) ($row['id'] ?? 0) ?>">Open</a>
                        ·
                        <a href="/admin/company-edit.php?id=<?= (int) ($row['id'] ?? 0) ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_pager(
    $page,
    $totalPages,
    static function (int $i) use ($chipBase, $search, $industry): string {
        return eca_type_filter_url($chipBase, $search, $industry, $i);
    },
    (int) $result['total'],
    $limit
);
?>
<?php endif; ?>
</section>
<?php
eca_admin_hub_end();
?>
