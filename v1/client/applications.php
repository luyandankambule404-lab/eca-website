<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/application-docs.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$life = eca_member_lifecycle($portal, $member);
if ((int) ($member['client_id'] ?? 0) < 1 && !empty($life['client_id'])) {
    $member['client_id'] = (int) $life['client_id'];
}
$rows = eca_member_applications($portal, $member);

eca_portal_start('Applications', 'applications');
?>
<div class="panel eca-form-panel eca-table-panel">
    <div class="eca-panel-heading"><h3>Your applications</h3></div>
    <div class="eca-panel-body">
        <p>Application status is separate from membership standing and membership type. Only applications on this membership are shown. If a document is missing or improper, open the registration number and resend the application.</p>
        <table class="hub-table">
            <thead><tr><th>Registration number</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $ref = (string) ($row['application_reference'] ?? '');
                $status = strtoupper((string) ($row['application_status'] ?? ''));
                $openReqs = $portal ? eca_application_open_doc_requests($portal, (int) ($row['client_id'] ?? 0)) : [];
                ?>
                <tr>
                    <td><?= eca_h($ref) ?></td>
                    <td><?= eca_h($row['application_status'] ?? '') ?></td>
                    <td><?= eca_h(eca_display_date((string) ($row['created_at'] ?? ''), '—')) ?></td>
                    <td>
                        <a href="/track.php?ref=<?= urlencode($ref) ?>">Track</a>
                        <?php if ($status === 'ADDITIONAL INFORMATION REQUIRED' || $openReqs): ?>
                            · <a href="/application-fix.php?ref=<?= urlencode($ref) ?>">Upload documents and resend</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="4">No applications on this membership.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php eca_portal_end(); ?>
