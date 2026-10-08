<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('settings.view');
header('Cache-Control: no-store');

$conn = eca_admin_db();
$actor = eca_admin_user() ?? [];
$canManage = eca_can('settings.manage') || eca_can('hub.settings');
$notice = '';
$noticeError = false;
$allowed = eca_hub_allowed_setting_keys();

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif (!$canManage) {
        eca_forbid('Only a Super Admin can change system settings.');
    } else {
        $result = eca_save_hub_settings($conn, $_POST, $actor);
        $notice = (string) ($result['message'] ?? 'Settings could not be saved.');
        $noticeError = empty($result['ok']);
    }
}

$values = [];
if ($conn) {
    try {
        $rows = $conn->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
    } catch (Throwable $e) {
        $values = [];
    }
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('System settings', 'settings');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">System settings</h1>
    <p>Local organization notes only. SMTP, database and session secrets remain in <code>.env</code> and are not stored here. Empty fee notes are not treated as SZL totals.</p>
</div>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card eca-form-panel" method="post">
    <h5 class="text-primary">Organization notes</h5>
    <?php if ($canManage): ?>
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <?php endif; ?>
    <?php foreach ($allowed as $key => $label): ?>
        <div class="mb-3">
            <label class="form-label" for="setting-<?= eca_admin_h($key) ?>"><?= eca_admin_h($label) ?></label>
            <input class="form-control" id="setting-<?= eca_admin_h($key) ?>" type="text" name="<?= eca_admin_h($key) ?>" value="<?= eca_admin_h($values[$key] ?? '') ?>" <?= $canManage ? '' : 'readonly' ?>>
        </div>
    <?php endforeach; ?>
    <?php if ($canManage): ?>
        <button class="hub-btn" type="submit">Save settings</button>
    <?php endif; ?>
</form>
<?php eca_admin_hub_end(); ?>
