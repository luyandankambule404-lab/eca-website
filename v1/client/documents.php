<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/membership.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$life = eca_member_lifecycle($portal, $member);
$member['client_id'] = (int) ($life['client_id'] ?? $member['client_id'] ?? 0);
$rows = eca_member_document_rows($portal, $member);

eca_portal_start('Documents', 'documents');
?>
<div class="panel eca-form-panel eca-table-panel">
    <div class="eca-panel-heading"><h3>Your documents</h3></div>
    <div class="eca-panel-body">
        <p>Only documents linked to your membership record are listed. Downloads stay behind sign-in and ownership checks.</p>
        <table class="hub-table">
            <thead><tr><th>Type</th><th>Name</th><th>Review</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= eca_h($row['document_type'] ?: 'document') ?></td>
                    <td><?= eca_h($row['original_name'] ?: 'On file') ?></td>
                    <td><?= !empty($row['reviewed_at']) ? 'Reviewed' : 'Pending' ?></td>
                    <td><a href="/document-download.php?id=<?= (int) $row['id'] ?>">Download</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="4">No documents on this membership.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php eca_portal_end(); ?>
