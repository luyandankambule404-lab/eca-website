<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/certificates.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/admin-ops.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/application-docs.php';
eca_admin_require('applications.manage');
header('Cache-Control: no-store, no-cache, must-revalidate');

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
if (!$conn || $id < 1) {
    eca_not_found('Application not found.');
}

$load = static function (PDO $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT * FROM tbl_client WHERE client_id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
};

$app = $load($conn, $id);
if (!$app || trim((string) ($app['application_reference'] ?? '')) === '') {
    eca_not_found('Application not found.');
}

$statuses = eca_application_statuses();
$notice = '';
$noticeError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        if (eca_admin_wants_json()) {
            eca_admin_json(['ok' => false, 'error' => 'session'], 403);
        }
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $admin = eca_admin_user() ?? [];
        $adminEmail = (string) ($admin['email'] ?? '');
        $role = (string) ($admin['role'] ?? '');
        if ($action === 'note') {
            $note = trim((string) ($_POST['note'] ?? ''));
            if ($note !== '') {
                $stmt = $conn->prepare(
                    'INSERT INTO membership_application_notes (client_id, admin_email, note) VALUES (?,?,?)'
                );
                $stmt->execute([$id, $adminEmail, $note]);
                eca_audit('application.note', 'tbl_client', (string) $id, ['ref' => $app['application_reference'] ?? '']);
                $notice = 'Internal note saved.';
            }
        } elseif ($action === 'status') {
            $next = strtoupper(trim((string) ($_POST['application_status'] ?? '')));
            if (!in_array($next, $statuses, true)) {
                $notice = 'That application status is not valid.';
                $noticeError = true;
            } elseif (!eca_can(eca_application_status_permission($next), $role)) {
                eca_forbid();
            } else {
                $stmt = $conn->prepare('UPDATE tbl_client SET application_status = ? WHERE client_id = ?');
                $stmt->execute([$next, $id]);
                eca_audit(eca_application_status_audit_action($next), 'tbl_client', (string) $id, [
                    'status' => $next,
                    'ref' => $app['application_reference'] ?? '',
                ]);
                $app = $load($conn, $id) ?? $app;
                $temporaryPassword = null;
                $hub = ['membership' => '', 'password' => null, 'created' => false];
                if ($next === 'APPROVED') {
                    $hub = eca_provision_member_hub_login($conn, $app);
                    $temporaryPassword = $hub['password'] ?? null;
                    $app = $load($conn, $id) ?? $app;
                    if (!empty($hub['created'])) {
                        eca_audit('member.hub_login_created', 'tbl_client', (string) $id, [
                            'membership' => $hub['membership'] ?? '',
                            'ref' => $app['application_reference'] ?? '',
                        ]);
                    }
                    eca_issue_certificate($conn, $app, eca_latest_membership_year($conn, $app));
                    eca_audit('certificate.issued', 'tbl_client', (string) $id);
                }
                require_once __DIR__ . '/../includes/notify.php';
                require_once __DIR__ . '/../includes/mailer.php';
                $ref = (string) ($app['application_reference'] ?? '');
                $statusMessage = 'Application registration number ' . $ref . ' is now ' . $next . '.';
                $statusLink = '/track.php?ref=' . rawurlencode($ref);
                if ($next === 'ADDITIONAL INFORMATION REQUIRED') {
                    $statusMessage = 'Application registration number ' . $ref
                        . ' was returned because a document is missing or improper. Upload the file and resend the application.';
                    $statusLink = '/application-fix.php?ref=' . rawurlencode($ref);
                }
                eca_notify([
                    'client_id' => $id,
                    'membership_number' => (string) ($app['MembershipNumber'] ?? ''),
                    'title' => 'Application ' . $ref,
                    'message' => $statusMessage,
                    'type' => 'APPLICATION',
                    'link' => $statusLink,
                ]);
                $applicantEmail = (string) ($app['EmailAddress'] ?? '');
                $applicantName = (string) ($app['TradingName'] ?? $app['CompanyRegistrationName'] ?? 'Applicant');
                if ($next === 'APPROVED') {
                    $membership = trim((string) ($hub['membership'] ?? $app['MembershipNumber'] ?? ''));
                    $mailed = $membership !== ''
                        ? eca_mail_membership_approved($applicantEmail, $applicantName, $ref, $membership, $temporaryPassword)
                        : false;
                    if ($mailed) {
                        $notice = 'Application approved. Login details and the welcome package were emailed to the applicant.';
                    } elseif (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
                        $notice = 'Application approved. No welcome email was sent because the application email is missing or invalid.';
                    } elseif (!eca_smtp_ready()) {
                        $notice = 'Application approved. Welcome email was logged locally because SMTP is not configured.';
                    } else {
                        $notice = 'Application approved. The welcome email could not be delivered; it was logged.';
                    }
                } else {
                    eca_mail_status_update($applicantEmail, $applicantName, $ref, $next);
                    $notice = 'Application ' . strtolower($next) . '.';
                }
            }
        } elseif ($action === 'request_docs') {
            if (!eca_can('applications.review', $role) && !eca_can('documents.manage', $role)) {
                eca_forbid();
            }
            $note = trim((string) ($_POST['doc_message'] ?? ''));
            $items = [];
            foreach ((array) ($_POST['missing_types'] ?? []) as $type) {
                $items[] = ['document_type' => $type, 'reason' => 'missing'];
            }
            foreach ((array) ($_POST['improper_docs'] ?? []) as $docId) {
                $docId = (int) $docId;
                if ($docId < 1) {
                    continue;
                }
                $docStmt = $conn->prepare('SELECT id, document_type FROM tbl_client_documents WHERE id = ? AND client_id = ? LIMIT 1');
                $docStmt->execute([$docId, $id]);
                $doc = $docStmt->fetch(PDO::FETCH_ASSOC);
                if ($doc) {
                    $items[] = [
                        'document_type' => (string) ($doc['document_type'] ?? 'document'),
                        'reason' => 'improper',
                        'source_document_id' => (int) $doc['id'],
                    ];
                }
            }
            if ($items === []) {
                $notice = 'Select a missing document type or mark an uploaded file as improper.';
                $noticeError = true;
            } else {
                $created = eca_application_request_documents($conn, $app + ['client_id' => $id], $items, $note, $adminEmail);
                $notice = $created
                    ? 'A message was sent using application registration number ' . ($app['application_reference'] ?? '') . '.'
                    : 'The document request could not be saved.';
                $noticeError = $created === [];
            }
        } elseif ($action === 'generate') {
            if (!eca_can('certificates.manage', $role)) {
                eca_forbid();
            }
            eca_issue_certificate($conn, $app, eca_latest_membership_year($conn, $app));
            eca_audit('certificate.issued', 'tbl_client', (string) $id);
            $notice = 'Certificate generated.';
        } elseif ($action === 'review_doc') {
            if (!eca_can('documents.manage', $role) && !eca_can('applications.review', $role)) {
                if (eca_admin_wants_json()) {
                    eca_admin_json(['ok' => false, 'error' => 'forbidden'], 403);
                }
                eca_forbid();
            }
            $docId = (int) ($_POST['doc_id'] ?? 0);
            if ($docId > 0) {
                $conn->prepare('UPDATE tbl_client_documents SET reviewed_at = NOW(), reviewed_by = ? WHERE id = ? AND client_id = ?')
                    ->execute([$adminEmail, $docId, $id]);
                eca_audit('document.review', 'tbl_client_documents', (string) $docId);
                $notice = 'Document marked reviewed.';
                if (eca_admin_wants_json()) {
                    eca_admin_json(['ok' => true, 'id' => $docId]);
                }
            } elseif (eca_admin_wants_json()) {
                eca_admin_json(['ok' => false, 'error' => 'document'], 400);
            }
        } elseif ($action === 'revoke') {
            if (!eca_can('certificates.manage', $role)) {
                eca_forbid();
            }
            $conn->prepare('UPDATE membership_certificates SET status = ? WHERE client_id = ? AND status = ?')
                ->execute(['REVOKED', $id, 'ACTIVE']);
            eca_audit('certificate.revoke', 'tbl_client', (string) $id);
            $notice = 'Certificate revoked.';
        }
        $app = $load($conn, $id) ?? $app;
        if ($notice === 'Document marked reviewed.') {
            header('Location: /admin/application-detail.php?id=' . $id . '#documents', true, 303);
            exit;
        }
    }
}

$docs = [];
$notes = [];
$docRequests = eca_application_doc_requests($conn, $id);
$cert = eca_active_certificate($conn, $id);
$docStmt = $conn->prepare('SELECT id, document_type, original_name, file_name, uploaded_at, reviewed_at FROM tbl_client_documents WHERE client_id = ? ORDER BY id DESC LIMIT 100');
$docStmt->execute([$id]);
$docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);
$noteStmt = $conn->prepare('SELECT * FROM membership_application_notes WHERE client_id = ? ORDER BY id DESC');
$noteStmt->execute([$id]);
$notes = $noteStmt->fetchAll(PDO::FETCH_ASSOC);
$history = [];
if (eca_can('audit.view')) {
    $history = eca_hub_audit_for('tbl_client', (string) $id, 20);
}
$role = (string) ((eca_admin_user()['role'] ?? ''));
$canApprove = eca_can('applications.approve', $role);
$canReject = eca_can('applications.reject', $role);
$canReview = eca_can('applications.review', $role);
$canCert = eca_can('certificates.manage', $role);
$csrf = eca_admin_csrf();

eca_admin_hub_start('Application', 'applications');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($app['application_reference'] ?? '') ?></h1>
    <p><?= eca_admin_h($app['TradingName'] ?? $app['CompanyRegistrationName'] ?? '') ?></p>
</div>
<?php if ($notice !== ''): ?>
    <p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p>
<?php endif; ?>
<div class="hub-card eca-form-panel">
    <div class="eca-apply-head">
        <h1>Application</h1>
        <a href="/admin/applications.php">Back</a>
    </div>
    <h5>Company</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label">Registered name</label>
            <div class="eca-field-value"><?= eca_admin_h($app['CompanyRegistrationName'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Trading name</label>
            <div class="eca-field-value"><?= eca_admin_h($app['TradingName'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Email</label>
            <div class="eca-field-value"><?= eca_admin_h($app['EmailAddress'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Cell</label>
            <div class="eca-field-value"><?= eca_admin_h($app['Cellphone'] ?: ($app['telephone'] ?? '')) ?></div>
        </div>
        <div>
            <label class="form-label">Classification</label>
            <div class="eca-field-value"><?= eca_admin_h($app['Clasification'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Region</label>
            <div class="eca-field-value"><?= eca_admin_h($app['Region'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Membership</label>
            <div class="eca-field-value"><?= eca_admin_h($app['MembershipNumber'] ?: '—') ?></div>
        </div>
        <div>
            <label class="form-label">Application status</label>
            <div class="eca-field-value"><?= eca_admin_h($app['application_status'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Standing (active)</label>
            <div class="eca-field-value"><?= eca_admin_h($app['active'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Membership type (Status)</label>
            <div class="eca-field-value"><?= eca_admin_h($app['Status'] ?? '') ?></div>
        </div>
        <div class="eca-field-full">
            <label class="form-label">Submitted</label>
            <div class="eca-field-value"><?= eca_admin_h($app['created_at'] ?? '') ?></div>
        </div>
    </div>
    <div class="eca-apply-nav">
        <a class="eca-btn-back" href="/admin/applications.php">Back</a>
        <a class="eca-btn-next" href="/admin/member-detail.php?id=<?= $id ?>">Member record</a>
    </div>
    <p class="form-text" style="margin-top:10px;"><a href="/track.php?ref=<?= urlencode((string) ($app['application_reference'] ?? '')) ?>">Public track</a></p>
</div>
<div class="hub-card">
    <h2>Review</h2>
    <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="status">
        <?php if ($canReview): ?>
            <button class="hub-btn" name="application_status" value="UNDER REVIEW" type="submit">Mark under review</button>
            <button class="hub-btn" name="application_status" value="ADDITIONAL INFORMATION REQUIRED" type="submit" onclick="return confirm('Return this application for more information?');">Return for information</button>
        <?php endif; ?>
        <?php if ($canApprove): ?>
            <button class="hub-btn" name="application_status" value="APPROVED" type="submit" onclick="return confirm('Approve this application? Login details and the welcome package will be emailed to the applicant, and an active certificate may be issued.');">Approve</button>
        <?php endif; ?>
        <?php if ($canReject): ?>
            <button class="hub-btn" name="application_status" value="REJECTED" type="submit" onclick="return confirm('Reject this application?');">Reject</button>
        <?php endif; ?>
    </form>
    <p>Application status is separate from membership standing (<code>active</code>) and membership type (<code>Status</code>). Approval emails login details to the applicant and may issue a certificate using the existing certificate rules. Use the member record to approve standing if the applicant should become an active member.</p>
    <?php if ($canReview || $canApprove || $canReject): ?>
    <form method="post" style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="status">
        <select class="form-select" name="application_status">
            <?php foreach ($statuses as $opt): ?>
                <option value="<?= eca_admin_h($opt) ?>"<?= strtoupper((string) ($app['application_status'] ?? '')) === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="hub-btn" type="submit">Update status</button>
    </form>
    <?php endif; ?>
    <?php if ($canCert): ?>
    <form method="post" style="margin-top:12px;">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="generate">
        <button class="hub-btn" type="submit"><?= $cert ? 'Regenerate certificate' : 'Generate certificate' ?></button>
    </form>
    <?php endif; ?>
    <?php if ($cert): ?>
        <p style="margin-top:12px;">
            Certificate <?= eca_admin_h($cert['certificate_number'] ?? '') ?> · <?= eca_admin_h($cert['status'] ?? '') ?>
            · <a href="/certificate-download.php?id=<?= (int) $cert['id'] ?>">Download</a>
            · <a href="/verify.php?cert=<?= urlencode((string) ($cert['certificate_number'] ?? '')) ?>">Verify</a>
        </p>
        <?php if ($canCert): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
            <input type="hidden" name="action" value="revoke">
            <button class="hub-btn" type="submit" onclick="return confirm('Revoke the active certificate?');">Revoke certificate</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
<div class="hub-card eca-table-panel" id="documents">
    <h2 class="hub-doc-heading">Documents</h2>
    <?php if (!$docs): ?>
        <p class="hub-doc-empty">No documents on this application.</p>
    <?php else: ?>
    <table class="hub-table">
            <thead><tr><th>Document</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php
            $appReturn = '/admin/application-detail.php?id=' . $id;
            $canReviewDoc = eca_can('documents.manage', $role) || $canReview;
            foreach ($docs as $doc):
                $fileLabel = trim((string) ($doc['original_name'] ?? '')) ?: (string) ($doc['file_name'] ?? 'On file');
            ?>
                <tr>
                    <td>
                        <span class="hub-doc-type"><?= eca_admin_h(eca_document_type_label((string) ($doc['document_type'] ?? ''))) ?></span>
                        <span class="hub-doc-file"><?= eca_admin_h($fileLabel) ?></span>
                        <span class="hub-doc-file"><?= eca_admin_h(eca_display_date((string) ($doc['uploaded_at'] ?? ''))) ?></span>
                    </td>
                    <td><?php eca_admin_document_status_html($doc['reviewed_at'] ?? null); ?></td>
                <td><?php eca_admin_document_actions($doc, $csrf, [
                    'can_review' => $canReviewDoc,
                    'action' => 'review_doc',
                    'id_field' => 'doc_id',
                    'return' => $appReturn,
                ]); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php if ($canReview || eca_can('documents.manage', $role)): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="request_docs">
        <h3 class="hub-doc-heading" style="padding:0 0 10px;">Missing or improper documents</h3>
        <p>This sends a message using application registration number <strong><?= eca_admin_h($app['application_reference'] ?? '') ?></strong>. The applicant uploads the file and resends the application.</p>
        <p><strong>Missing types</strong></p>
        <div style="display:flex;flex-wrap:wrap;gap:10px 16px;margin:8px 0 14px;">
            <?php foreach (eca_application_document_types() as $type => $label): ?>
                <label><input type="checkbox" name="missing_types[]" value="<?= eca_admin_h($type) ?>"> <?= eca_admin_h($label) ?></label>
            <?php endforeach; ?>
        </div>
        <?php if ($docs): ?>
            <p><strong>Mark uploaded files as improper</strong></p>
            <div style="display:flex;flex-wrap:wrap;gap:10px 16px;margin:8px 0 14px;">
                <?php foreach ($docs as $doc): ?>
                    <label>
                        <input type="checkbox" name="improper_docs[]" value="<?= (int) $doc['id'] ?>">
                        <?= eca_admin_h(eca_document_type_label((string) ($doc['document_type'] ?? ''))) ?>
                        (<?= eca_admin_h(trim((string) ($doc['original_name'] ?? '')) ?: 'on file') ?>)
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <label class="form-label" for="doc_message">Message to the applicant</label>
        <textarea class="form-control" id="doc_message" name="doc_message" rows="3" placeholder="Tell them exactly what is missing or why the file was rejected."></textarea>
        <button class="hub-btn" type="submit">Send document request</button>
    </form>
    <?php endif; ?>
    <?php if ($docRequests): ?>
        <div>
            <p><strong>Document requests</strong></p>
            <ul>
                <?php foreach ($docRequests as $req): ?>
                    <li>
                        <?= eca_admin_h(eca_document_type_label((string) ($req['document_type'] ?? ''))) ?>
                        · <?= eca_admin_h((string) ($req['reason'] ?? '')) ?>
                        · <?= eca_admin_h((string) ($req['status'] ?? '')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
<?php if ($history): ?>
<div class="hub-card">
    <h2>Application history</h2>
    <ul class="hub-activity">
        <?php foreach ($history as $item): ?>
            <li>
                <div>
                    <strong><?= eca_admin_h(eca_admin_audit_label((string) ($item['action'] ?? ''))) ?></strong>
                    <p><?= eca_admin_h($item['actor_email'] ?: 'system') ?></p>
                </div>
                <time><?= eca_admin_h($item['created_at'] ?? '') ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<div class="hub-card">
    <h2>Internal notes</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="note">
        <label class="form-label" for="application-note">Internal note</label>
        <textarea class="form-control" id="application-note" name="note" rows="3"></textarea>
        <button class="hub-btn" type="submit" style="margin-top:10px;">Add note</button>
    </form>
    <?php foreach ($notes as $note): ?>
        <p><strong><?= eca_admin_h($note['admin_email'] ?? '') ?></strong> · <?= eca_admin_h($note['created_at'] ?? '') ?><br><?= eca_admin_h($note['note'] ?? '') ?></p>
    <?php endforeach; ?>
</div>
<?php eca_admin_hub_end(); ?>
