<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('hub.access');
header('Cache-Control: no-store');

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
if (!$conn || $id < 1) {
    eca_not_found('Document not found.');
}

$adminRole = eca_normalize_role((string) ((eca_admin_user()['role'] ?? '')));
$canDocRoute = eca_can('documents.manage', $adminRole)
    || eca_can('applications.review', $adminRole)
    || eca_can('applications.manage', $adminRole)
    || eca_can('members.manage', $adminRole)
    || eca_can('payments.manage', $adminRole);
if (!$canDocRoute) {
    eca_forbid('You are not allowed to view this document.');
}

$stmt = $conn->prepare(
    'SELECT d.id, d.client_id, d.document_type, d.original_name, d.file_name, d.file_path, d.storage_key,
            d.uploaded_at, d.reviewed_at, d.reviewed_by,
            c.TradingName, c.MembershipNumber, c.application_reference
     FROM tbl_client_documents d
     LEFT JOIN tbl_client c ON c.client_id = d.client_id
     WHERE d.id = ?
     LIMIT 1'
);
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    eca_not_found('Document not found.');
}
if (!eca_can_access_client_document($doc)) {
    eca_forbid('You are not allowed to view this document.');
}

// Finance officers with payments.manage only may open payment proofs, not arbitrary docs.
if (
    eca_can('payments.manage', $adminRole)
    && !eca_can('documents.manage', $adminRole)
    && !eca_can('applications.review', $adminRole)
    && !eca_can('applications.manage', $adminRole)
    && !eca_can('members.manage', $adminRole)
    && !eca_document_is_payment_proof($doc)
) {
    eca_forbid('You are not allowed to view this document.');
}

$role = (string) ((eca_admin_user()['role'] ?? ''));
$canReview = eca_can('documents.manage', $role) || eca_can('applications.review', $role);
$returnTo = eca_admin_safe_return($_GET['return'] ?? $_POST['return'] ?? null);
$notice = '';
$noticeError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canReview) {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        if (eca_admin_wants_json()) {
            eca_admin_json(['ok' => false, 'error' => 'session'], 403);
        }
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif ((string) ($_POST['action'] ?? '') === 'review_doc' && empty($doc['reviewed_at'])) {
        $adminEmail = (string) (eca_admin_user()['email'] ?? '');
        $conn->prepare('UPDATE tbl_client_documents SET reviewed_at = NOW(), reviewed_by = ? WHERE id = ?')
            ->execute([$adminEmail, $id]);
        eca_audit('document.review', 'tbl_client_documents', (string) $id);
        $notice = 'Document marked reviewed.';
        if (eca_admin_wants_json()) {
            eca_admin_json(['ok' => true, 'id' => $id]);
        }
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC) ?: $doc;
    } elseif (eca_admin_wants_json()) {
        eca_admin_json(['ok' => false, 'error' => 'document'], 400);
    }
}

$path = eca_document_absolute_path($doc);
$kind = $path ? eca_document_preview_kind($doc, $path) : 'missing';
$typeLabel = eca_document_type_label((string) ($doc['document_type'] ?? ''));
$fileLabel = trim((string) ($doc['original_name'] ?? '')) ?: (string) ($doc['file_name'] ?? 'On file');
$embedSrc = '/document-download.php?id=' . $id . '&view=1';
$csrf = eca_admin_csrf();
$clientId = (int) ($doc['client_id'] ?? 0);
$memberLabel = trim((string) ($doc['MembershipNumber'] ?? '')) ?: trim((string) ($doc['TradingName'] ?? ''));
$navKey = eca_can('documents.manage', $role) ? 'documents' : (eca_can('applications.manage', $role) ? 'applications' : 'members');

eca_admin_hub_start('View document', $navKey);
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($typeLabel) ?></h1>
    <p><?= eca_admin_h($fileLabel) ?><?php if ($memberLabel !== ''): ?> · <?= eca_admin_h($memberLabel) ?><?php endif; ?></p>
</div>
<?php if ($notice !== ''): ?>
    <p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p>
<?php endif; ?>
<div class="hub-card hub-doc-toolbar">
    <a class="hub-doc-link hub-doc-link-ghost" href="<?= eca_admin_h($returnTo) ?>">Back</a>
    <?php if ($clientId > 0 && trim((string) ($doc['application_reference'] ?? '')) !== ''): ?>
        <a class="hub-doc-link hub-doc-link-ghost" href="/admin/application-detail.php?id=<?= $clientId ?>">Application</a>
    <?php endif; ?>
    <?php if ($clientId > 0): ?>
        <a class="hub-doc-link hub-doc-link-ghost" href="/admin/member-detail.php?id=<?= $clientId ?>">Member record</a>
    <?php endif; ?>
    <a class="hub-doc-link hub-doc-link-ghost" href="/document-download.php?id=<?= $id ?>">Download</a>
    <?php if ($canReview && empty($doc['reviewed_at'])): ?>
    <form method="post" class="hub-table-action-form hub-doc-review-form">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="review_doc">
        <input type="hidden" name="return" value="<?= eca_admin_h($returnTo) ?>">
        <button class="hub-doc-review" type="submit">Mark reviewed</button>
    </form>
    <?php endif; ?>
    <?php eca_admin_document_status_html($doc['reviewed_at'] ?? null); ?>
</div>
<div class="hub-card hub-doc-meta">
    <p><strong>Type:</strong> <?= eca_admin_h($typeLabel) ?></p>
    <p><strong>File:</strong> <?= eca_admin_h($fileLabel) ?></p>
    <p><strong>Uploaded:</strong> <?= eca_admin_h(eca_display_date((string) ($doc['uploaded_at'] ?? ''))) ?></p>
    <?php if (!empty($doc['reviewed_at'])): ?>
        <p><strong>Reviewed:</strong> <?= eca_admin_h(eca_display_date((string) $doc['reviewed_at'])) ?><?php if (!empty($doc['reviewed_by'])): ?> · <?= eca_admin_h((string) $doc['reviewed_by']) ?><?php endif; ?></p>
    <?php endif; ?>
</div>
<div class="hub-card hub-doc-stage">
    <?php if ($kind === 'pdf'): ?>
        <iframe class="hub-doc-frame" src="<?= eca_admin_h($embedSrc) ?>" title="<?= eca_admin_h($typeLabel) ?>"></iframe>
    <?php elseif ($kind === 'image'): ?>
        <img class="hub-doc-image" src="<?= eca_admin_h($embedSrc) ?>" alt="<?= eca_admin_h($typeLabel) ?>">
    <?php elseif ($kind === 'missing'): ?>
        <p class="hub-doc-empty">This document is not available in the local copy.</p>
    <?php else: ?>
        <p class="hub-doc-empty">This file cannot be previewed in the browser. Use Download to save a copy.</p>
    <?php endif; ?>
</div>
<?php eca_admin_hub_end(); ?>
