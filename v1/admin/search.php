<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
eca_admin_require('hub.access');
header('Cache-Control: no-store');

$q = trim((string) ($_GET['q'] ?? ''));
if (strlen($q) > 80) {
    $q = substr($q, 0, 80);
}
$role = eca_normalize_role((string) ((eca_admin_user()['role'] ?? 'admin')));
$local = eca_admin_db();
$portal = eca_portal_pdo(false);
$groups = [];

function eca_admin_search_run(?PDO $conn, string $sql, array $params): array
{
    if (!$conn) {
        return [];
    }
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

if ($q !== '' && strlen($q) >= 2) {
    $like = '%' . $q . '%';
    if ($portal && eca_can('members.manage', $role)) {
        $groups['Members'] = [
            '/admin/member-detail.php?id=',
            eca_admin_search_run(
                $portal,
                'SELECT client_id AS id, TradingName AS title, CONCAT(COALESCE(MembershipNumber,\'\'), \' · \', COALESCE(active,\'\'), \' · \', COALESCE(application_status,\'\')) AS meta
                 FROM tbl_client
                 WHERE MembershipNumber LIKE ? OR TradingName LIKE ? OR CompanyRegistrationName LIKE ? OR EmailAddress LIKE ?
                 ORDER BY TradingName ASC LIMIT 8',
                [$like, $like, $like, $like]
            ),
        ];
    }
    if ($portal && eca_can('applications.manage', $role)) {
        $groups['Applications'] = [
            '/admin/application-detail.php?id=',
            eca_admin_search_run(
                $portal,
                'SELECT client_id AS id, application_reference AS title, application_status AS meta
                 FROM tbl_client
                 WHERE application_reference IS NOT NULL AND application_reference <> ""
                   AND (application_reference LIKE ? OR TradingName LIKE ? OR MembershipNumber LIKE ?)
                 ORDER BY created_at DESC LIMIT 8',
                [$like, $like, $like]
            ),
        ];
    }
    if ($local && eca_can('companies.manage', $role)) {
        $groups['Contractors'] = [
            '/admin/company-edit.php?id=',
            eca_admin_search_run(
                $local,
                'SELECT id, name AS title, registration_number AS meta
                 FROM companies
                 WHERE name LIKE ? OR registration_number LIKE ? OR email LIKE ?
                 ORDER BY name ASC LIMIT 8',
                [$like, $like, $like]
            ),
        ];
    }
    if ($portal && eca_can('certificates.manage', $role)) {
        $certs = eca_admin_search_run(
            $portal,
            'SELECT id, certificate_number AS title, CONCAT(COALESCE(status,\'\'), \' · \', COALESCE(membership_number,\'\')) AS meta, client_id
             FROM membership_certificates
             WHERE certificate_number LIKE ? OR membership_number LIKE ?
             ORDER BY id DESC LIMIT 8',
            [$like, $like]
        );
        foreach ($certs as &$certRow) {
            $clientId = (int) ($certRow['client_id'] ?? 0);
            $certRow['_href'] = $clientId > 0
                ? '/admin/member-detail.php?id=' . $clientId
                : '/admin/certificates.php?search=' . rawurlencode((string) ($certRow['title'] ?? ''));
        }
        unset($certRow);
        $groups['Certificates'] = ['', $certs];
    }
    if ($portal && eca_can('payments.manage', $role)) {
        $groups['Payments'] = [
            '/admin/payment-detail.php?id=',
            eca_admin_search_run(
                $portal,
                'SELECT p.id, COALESCE(u.membership_number, p.payment_year) AS title, p.status AS meta
                 FROM payments p
                 LEFT JOIN userss u ON u.id = p.user_id
                 WHERE p.payment_year LIKE ? OR p.status LIKE ? OR u.membership_number LIKE ? OR u.full_name LIKE ?
                 ORDER BY p.id DESC LIMIT 8',
                [$like, $like, $like, $like]
            ),
        ];
    }
    if ($local && eca_normalize_role((string) $role) === 'super_admin' && eca_can('users.view', $role)) {
        $groups['Users'] = [
            '/admin/user-detail.php?id=',
            eca_admin_search_run(
                $local,
                'SELECT id, name AS title, email AS meta FROM users WHERE name LIKE ? OR email LIKE ? OR role LIKE ? ORDER BY name ASC LIMIT 8',
                [$like, $like, $like]
            ),
        ];
    }
    if ($portal && eca_can('cpd.view', $role)) {
        $groups['Courses'] = [
            '/admin/cpd.php',
            eca_admin_search_run(
                $portal,
                'SELECT id, title, status AS meta FROM courses WHERE title LIKE ? OR status LIKE ? ORDER BY id DESC LIMIT 8',
                [$like, $like]
            ),
        ];
    }
    if ($local && eca_can('wellness.manage', $role)) {
        $events = eca_admin_search_run($local, 'SELECT id, title, status AS meta FROM wellness_events WHERE title LIKE ? ORDER BY id DESC LIMIT 5', [$like]);
        $resources = eca_admin_search_run($local, 'SELECT id, title, status AS meta FROM wellness_resources WHERE title LIKE ? ORDER BY id DESC LIMIT 5', [$like]);
        foreach ($events as &$event) {
            $event['_href'] = '/admin/wellness/event-edit.php?id=' . (int) $event['id'];
        }
        unset($event);
        foreach ($resources as &$resource) {
            $resource['_href'] = '/admin/wellness/resource-edit.php?id=' . (int) $resource['id'];
        }
        unset($resource);
        $groups['Wellness'] = ['', array_merge($events, $resources)];
    }
    if (eca_can('content.manage', $role)) {
        if ($portal) {
            $groups['News'] = [
                '/admin/news.php?search=',
                eca_admin_search_run(
                    $portal,
                    'SELECT id, title, status AS meta FROM news WHERE title LIKE ? OR author LIKE ? ORDER BY id DESC LIMIT 6',
                    [$like, $like]
                ),
            ];
            $groups['Resources'] = [
                '/admin/resources.php?search=',
                eca_admin_search_run(
                    $portal,
                    'SELECT id, title, status AS meta FROM resources WHERE title LIKE ? OR category LIKE ? ORDER BY id DESC LIMIT 6',
                    [$like, $like]
                ),
            ];
        }
        if ($local) {
            $groups['Tenders'] = [
                '/admin/tenders.php?search=',
                eca_admin_search_run(
                    $local,
                    'SELECT id, title, status AS meta FROM tenders WHERE title LIKE ? ORDER BY id DESC LIMIT 6',
                    [$like]
                ),
            ];
            $groups['Events'] = [
                '/admin/events.php?search=',
                eca_admin_search_run(
                    $local,
                    'SELECT id, title, status AS meta FROM events WHERE title LIKE ? ORDER BY id DESC LIMIT 6',
                    [$like]
                ),
            ];
        }
    }
}

eca_admin_hub_start('Search', 'search');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Administrative search</h1>
    <p>Results respect your permissions. Private document files are not included.</p>
</div>
<form class="hub-card" method="get" action="/admin/search.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="q" value="<?= eca_admin_h($q) ?>" placeholder="Name, membership, application, email" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;min-width:240px;">
    <button class="hub-btn" type="submit">Search</button>
</form>
<?php if ($q !== '' && strlen($q) < 2): ?>
<p class="hub-card">Type at least two characters.</p>
<?php elseif ($q === ''): ?>
<p class="hub-card">Search members, contractors, applications, certificates, payments, courses and wellness items you are allowed to see.</p>
<?php elseif (!$groups): ?>
<p class="hub-card">No permitted results.</p>
<?php endif; ?>
<?php foreach ($groups as $label => $pack): ?>
    <?php [$hrefPrefix, $rows] = $pack; ?>
    <section class="hub-card">
        <div class="hub-card-head"><h2><?= eca_admin_h($label) ?></h2></div>
        <?php if (!$rows): ?><p class="hub-empty">No matches.</p><?php endif; ?>
        <ul class="hub-activity">
            <?php foreach ($rows as $row): ?>
                <?php
                    $href = (string) ($row['_href'] ?? '');
                    if ($href === '' && $hrefPrefix !== '') {
                        $href = str_contains($hrefPrefix, 'search=')
                            ? $hrefPrefix . rawurlencode((string) ($row['title'] ?? ''))
                            : ($hrefPrefix === '/admin/cpd.php' ? $hrefPrefix : $hrefPrefix . (int) ($row['id'] ?? 0));
                    }
                ?>
                <li>
                    <div>
                        <strong><?php if ($href): ?><a href="<?= eca_admin_h($href) ?>"><?= eca_admin_h($row['title'] ?? '') ?></a><?php else: ?><?= eca_admin_h($row['title'] ?? '') ?><?php endif; ?></strong>
                        <p><?= eca_admin_h($row['meta'] ?? '') ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
<?php eca_admin_hub_end(); ?>
