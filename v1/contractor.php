<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/company-data.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/public-page.php';

$id = (int) ($_GET['id'] ?? 0);
$mode = (($_GET['mode'] ?? '') === 'balingani') ? 'balingani' : 'members';
$db = new Database();
$conn = $db->getConnection(false);
$row = null;
if ($conn && $id > 0) {
    $found = eca_fetch_directory($conn, (string) $id, '', 1, 0, $mode);
    foreach ($found['rows'] as $candidate) {
        if ((int) ($candidate['id'] ?? 0) === $id) {
            $row = $candidate;
            break;
        }
    }
    if (!$row) {
        $base = eca_directory_base_sql($conn, $mode);
        if ($base['sql'] !== '') {
            try {
                $stmt = $conn->prepare('SELECT * FROM (' . $base['sql'] . ') AS directory_rows WHERE id = :id LIMIT 1');
                $params = $base['params'];
                $params[':id'] = $id;
                $stmt->execute($params);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) {
                $row = null;
            }
        }
    }
}

if (!$row) {
    eca_not_found('No public contractor profile matches that listing.');
}

$name = (string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? 'Contractor');
eca_public_page_start(
    $name,
    'Directory',
    $name,
    'Public contractor listing from the ECA directory.',
    'Contractor'
);
?>
<div class="container-xxl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card company-card shadow-sm">
                <div class="card-header text-center" style="background:#192754;color:#fff;font-weight:700;padding:12px;">
                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> <?= htmlspecialchars((string) ($row['Status'] ?? 'Active'), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Classification:</strong> <?= htmlspecialchars((string) ($row['Clasification'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Region / address:</strong> <?= htmlspecialchars(trim((string) ($row['Region'] ?? '') . ' ' . (string) ($row['address'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars((string) ($row['Cellphone'] ?? $row['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars((string) ($row['EmailAddress'] ?? $row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php
                    $website = trim((string) ($row['website'] ?? ''));
                    if ($website !== ''):
                    ?>
                    <p><strong>Website:</strong> <?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php
                    $desc = trim((string) ($row['description'] ?? ''));
                    if ($desc !== ''):
                    ?>
                    <p><strong>Description:</strong> <?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?= $mode === 'balingani' ? 'balingani-directory.html' : 'directory.html' ?>">Back to directory</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php eca_public_page_end(); ?>
