<?php
/**
 * Super Admin / Officer — Member Email & Notifications (Email Centre).
 * LOCAL: without SMTP, messages are logged under _private/mail-log (not delivered).
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('members.manage');
header('Cache-Control: no-store');

$portal = eca_portal_pdo(false);
$recipients = eca_member_notice_recipients($portal);
$validCount = count($recipients);
$smtpReady = eca_smtp_ready();
$flash = '';
$flashType = 'ok';
$errors = [];

$mode = 'all';
$memberId = 0;
$ccRaw = '';
$subject = 'ECA Member Notice';
$bodyHtml = '<p>Dear ECA Member,</p><p><br></p><p>Kind regards,</p>';

function eca_email_centre_sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    // Allow a small formatting subset from contenteditable.
    $allowed = '<p><br><br/><b><strong><i><em><u><ul><ol><li><div><span>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $clean) ?? $clean;
    $clean = preg_replace('/\s(style|class)\s*=\s*("|\').*?\2/iu', '', $clean) ?? $clean;
    return $clean;
}

function eca_email_centre_wrap(string $subject, string $bodyHtml, string $signatureHtml = ''): string
{
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#eef2f8;font-family:Segoe UI,Arial,sans-serif;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f8;padding:24px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #d8dee9;">'
        . '<tr><td style="background:#071a3d;color:#fff;padding:18px 22px;">'
        . '<div style="font-size:13px;opacity:.85;">Eswatini Contractors Association</div>'
        . '<div style="font-size:18px;font-weight:700;margin-top:4px;">' . $safeSubject . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:22px;color:#0b1733;font-size:15px;line-height:1.55;">' . $bodyHtml . $signatureHtml . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function eca_email_centre_store_uploads(array $filesField, string $subdir): array
{
    $saved = [];
    if (!isset($filesField['name']) || !is_array($filesField['name'])) {
        return $saved;
    }
    $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'email-uploads' . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($base)) {
        @mkdir($base, 0775, true);
    }
    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
    $count = count($filesField['name']);
    for ($i = 0; $i < $count && $i < 5; $i++) {
        $err = (int) ($filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = (string) ($filesField['tmp_name'][$i] ?? '');
        $name = basename((string) ($filesField['name'][$i] ?? 'file'));
        $size = (int) ($filesField['size'][$i] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > 8 * 1024 * 1024) {
            continue;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            continue;
        }
        $destName = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $base . DIRECTORY_SEPARATOR . $destName;
        if (@move_uploaded_file($tmp, $dest)) {
            $saved[] = $dest;
        }
    }
    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired. Refresh and try again.';
    } else {
        $mode = (string) ($_POST['mode'] ?? 'all') === 'one' ? 'one' : 'all';
        $memberId = (int) ($_POST['member_id'] ?? 0);
        $ccRaw = trim((string) ($_POST['cc'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $bodyHtml = eca_email_centre_sanitize_html((string) ($_POST['body_html'] ?? ''));
        if ($subject === '') {
            $errors[] = 'Enter a subject.';
        }
        if (trim(strip_tags($bodyHtml)) === '') {
            $errors[] = 'Enter a message.';
        }

        $ccList = [];
        if ($ccRaw !== '') {
            foreach (preg_split('/[,;]+/', $ccRaw) ?: [] as $part) {
                $addr = strtolower(trim($part));
                if ($addr === '') {
                    continue;
                }
                if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid CC address: ' . $addr;
                } else {
                    $ccList[] = $addr;
                }
            }
            $ccList = array_values(array_unique($ccList));
        }

        $batch = bin2hex(random_bytes(6));
        $attachments = eca_email_centre_store_uploads($_FILES['attachments'] ?? [], $batch);
        $signatureHtml = '';
        if (isset($_FILES['signature']) && (int) ($_FILES['signature']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $sigFake = [
                'name' => [$_FILES['signature']['name'] ?? 'signature.png'],
                'type' => [$_FILES['signature']['type'] ?? ''],
                'tmp_name' => [$_FILES['signature']['tmp_name'] ?? ''],
                'error' => [(int) ($_FILES['signature']['error'] ?? UPLOAD_ERR_NO_FILE)],
                'size' => [(int) ($_FILES['signature']['size'] ?? 0)],
            ];
            $sigFiles = eca_email_centre_store_uploads($sigFake, $batch . '-sig');
            if ($sigFiles) {
                $sigPath = $sigFiles[0];
                $attachments[] = $sigPath;
                $signatureHtml = '<p style="margin-top:24px;font-size:12px;color:#697792;">Signature image attached: '
                    . htmlspecialchars(basename($sigPath), ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }

        $targets = [];
        if ($mode === 'one') {
            foreach ($recipients as $row) {
                if ((int) $row['client_id'] === $memberId) {
                    $targets[] = $row;
                    break;
                }
            }
            if (!$targets) {
                $errors[] = 'Select a member with a valid email address.';
            }
        } else {
            $targets = $recipients;
            if (!$targets) {
                $errors[] = 'No valid member email addresses found locally.';
            }
            if (empty($_POST['confirm_bulk'])) {
                $errors[] = 'Confirm bulk send to all members.';
            }
        }

        if (!$errors) {
            $wrapped = eca_email_centre_wrap($subject, $bodyHtml, $signatureHtml);
            $sent = 0;
            $failed = 0;
            $logged = 0;
            foreach ($targets as $i => $row) {
                // Bulk: attach CC only on the first message to avoid duplicate CC floods.
                $ccForThis = ($mode === 'all' && $i > 0) ? [] : $ccList;
                $ok = eca_mail_send_notice(
                    (string) $row['email'],
                    $subject,
                    $wrapped,
                    $ccForThis,
                    $attachments,
                    [
                        'mode' => $mode,
                        'client_id' => (int) $row['client_id'],
                        'batch' => $batch,
                    ]
                );
                if ($ok) {
                    $sent++;
                } elseif (!$smtpReady) {
                    $logged++;
                } else {
                    $failed++;
                }
            }

            if (function_exists('eca_audit')) {
                eca_audit('member.email_notice', 'member_notice', $batch, [
                    'mode' => $mode,
                    'subject' => $subject,
                    'recipients' => count($targets),
                    'sent' => $sent,
                    'failed' => $failed,
                    'logged' => $logged,
                    'smtp_ready' => $smtpReady,
                    'cc_count' => count($ccList),
                    'attachments' => count($attachments),
                ]);
            }

            if ($smtpReady) {
                $flash = 'Notice processed: ' . $sent . ' sent'
                    . ($failed > 0 ? ', ' . $failed . ' failed' : '')
                    . ' of ' . count($targets) . ' recipient(s).';
                $flashType = $failed > 0 && $sent === 0 ? 'err' : 'ok';
            } else {
                $flash = 'SMTP is not configured on this local copy. '
                    . $logged . ' message(s) were written to the local mail log (_private/mail-log) and not delivered.';
                $flashType = 'warn';
            }
            // Reset compose defaults after successful queue/log.
            if ($flashType !== 'err') {
                $mode = 'all';
                $memberId = 0;
                $ccRaw = '';
                $subject = 'ECA Member Notice';
                $bodyHtml = '<p>Dear ECA Member,</p><p><br></p><p>Kind regards,</p>';
            }
        }
    }
}

$memberOptions = $recipients;
usort($memberOptions, static function ($a, $b) {
    return strcasecmp((string) $a['name'], (string) $b['name']);
});

eca_admin_hub_start('Email Centre', 'email_centre');
$csrf = eca_admin_csrf();
?>
<link rel="stylesheet" href="/css/email-centre.css?v=20261006-1">

<section class="emc-hero">
    <p class="emc-kicker">Communications</p>
    <h1>Member Email &amp; Notifications</h1>
    <p>Send notices with CC, attachments and an image signature.</p>
</section>

<?php if ($flash !== ''): ?>
    <p class="emc-flash emc-flash-<?= eca_admin_h($flashType) ?>"><?= eca_admin_h($flash) ?></p>
<?php endif; ?>
<?php if ($errors): ?>
    <ul class="emc-errors">
        <?php foreach ($errors as $err): ?>
            <li><?= eca_admin_h($err) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!$smtpReady): ?>
    <p class="emc-banner">Local SMTP is not configured. Sends are logged under <code>_private/mail-log</code> and are not delivered externally.</p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="emc-layout" id="emc-form">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">

    <section class="emc-card">
        <h2>Compose Email</h2>

        <div class="emc-mode-grid" role="radiogroup" aria-label="Recipients">
            <label class="emc-mode<?= $mode === 'one' ? ' is-active' : '' ?>">
                <input type="radio" name="mode" value="one"<?= $mode === 'one' ? ' checked' : '' ?>>
                <span class="emc-mode-title">Individual Member</span>
                <span class="emc-mode-meta">Send to one selected member.</span>
            </label>
            <label class="emc-mode<?= $mode === 'all' ? ' is-active' : '' ?>">
                <input type="radio" name="mode" value="all"<?= $mode === 'all' ? ' checked' : '' ?>>
                <span class="emc-mode-title">All Members</span>
                <span class="emc-mode-meta"><strong id="emc-count"><?= (int) $validCount ?></strong> valid email addresses detected.</span>
            </label>
        </div>

        <div class="emc-field" id="emc-member-wrap"<?= $mode === 'one' ? '' : ' hidden' ?>>
            <label for="member_id">Member</label>
            <select name="member_id" id="member_id">
                <option value="0">Select member…</option>
                <?php foreach ($memberOptions as $row): ?>
                    <option value="<?= (int) $row['client_id'] ?>"<?= $memberId === (int) $row['client_id'] ? ' selected' : '' ?>>
                        <?= eca_admin_h($row['name']) ?>
                        <?= $row['membership'] !== '' ? ' (' . eca_admin_h($row['membership']) . ')' : '' ?>
                        — <?= eca_admin_h($row['email']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="emc-field" id="emc-bulk-wrap"<?= $mode === 'all' ? '' : ' hidden' ?>>
            <label class="emc-check">
                <input type="checkbox" name="confirm_bulk" value="1">
                I confirm sending this notice to all <?= (int) $validCount ?> members with valid email addresses.
            </label>
        </div>

        <div class="emc-field">
            <label for="cc">CC recipients</label>
            <input type="text" name="cc" id="cc" value="<?= eca_admin_h($ccRaw) ?>" placeholder="accounts@example.com, manager@example.com" autocomplete="off">
            <p class="emc-hint">Separate addresses with commas. For bulk mail, CC is sent once to prevent duplicates.</p>
        </div>

        <div class="emc-field">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" value="<?= eca_admin_h($subject) ?>" required maxlength="200">
        </div>

        <div class="emc-field">
            <label for="emc-editor">Message</label>
            <div class="emc-toolbar" role="toolbar" aria-label="Formatting">
                <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
                <button type="button" data-cmd="insertUnorderedList" title="List">≡</button>
            </div>
            <div id="emc-editor" class="emc-editor" contenteditable="true" role="textbox" aria-multiline="true"><?= $bodyHtml ?></div>
            <textarea name="body_html" id="body_html" hidden><?= eca_admin_h($bodyHtml) ?></textarea>
        </div>

        <div class="emc-field-row">
            <div class="emc-field">
                <label for="attachments">Attachments</label>
                <input type="file" name="attachments[]" id="attachments" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.webp">
                <p class="emc-hint">Up to 5 files, 8&nbsp;MB each.</p>
            </div>
            <div class="emc-field">
                <label for="signature">Signature image</label>
                <input type="file" name="signature" id="signature" accept=".png,.jpg,.jpeg,.gif,.webp">
            </div>
        </div>

        <div class="emc-actions">
            <button class="hub-btn" type="submit">Send notice</button>
            <a class="hub-btn hub-btn-ghost" href="/admin/notifications.php">Admin alerts</a>
        </div>
    </section>

    <aside class="emc-card emc-preview-card">
        <h2>Live Preview</h2>
        <div class="emc-preview" id="emc-preview">
            <div class="emc-preview-head">
                <div>Eswatini Contractors Association</div>
                <strong id="emc-preview-subject"><?= eca_admin_h($subject) ?></strong>
            </div>
            <div class="emc-preview-body" id="emc-preview-body"><?= $bodyHtml ?></div>
            <div class="emc-preview-foot" id="emc-preview-foot">No attachments selected</div>
        </div>
    </aside>
</form>

<script>
(function () {
  var form = document.getElementById('emc-form');
  if (!form) return;
  var editor = document.getElementById('emc-editor');
  var hidden = document.getElementById('body_html');
  var subject = document.getElementById('subject');
  var previewSubject = document.getElementById('emc-preview-subject');
  var previewBody = document.getElementById('emc-preview-body');
  var previewFoot = document.getElementById('emc-preview-foot');
  var memberWrap = document.getElementById('emc-member-wrap');
  var bulkWrap = document.getElementById('emc-bulk-wrap');
  var attach = document.getElementById('attachments');
  var sig = document.getElementById('signature');

  function syncMode() {
    var mode = (form.querySelector('input[name="mode"]:checked') || {}).value || 'all';
    document.querySelectorAll('.emc-mode').forEach(function (el) {
      el.classList.toggle('is-active', el.querySelector('input').value === mode);
    });
    if (memberWrap) memberWrap.hidden = mode !== 'one';
    if (bulkWrap) bulkWrap.hidden = mode !== 'all';
  }

  function syncPreview() {
    if (hidden) hidden.value = editor ? editor.innerHTML : '';
    if (previewSubject && subject) previewSubject.textContent = subject.value || 'ECA Member Notice';
    if (previewBody && editor) previewBody.innerHTML = editor.innerHTML;
    var names = [];
    if (attach && attach.files) {
      Array.prototype.forEach.call(attach.files, function (f) { names.push(f.name); });
    }
    if (sig && sig.files && sig.files[0]) names.push('Signature: ' + sig.files[0].name);
    if (previewFoot) previewFoot.textContent = names.length ? names.join(', ') : 'No attachments selected';
  }

  form.querySelectorAll('input[name="mode"]').forEach(function (r) {
    r.addEventListener('change', syncMode);
  });
  document.querySelectorAll('.emc-toolbar button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.execCommand(btn.getAttribute('data-cmd'), false, null);
      editor && editor.focus();
      syncPreview();
    });
  });
  if (editor) {
    editor.addEventListener('input', syncPreview);
    editor.addEventListener('blur', syncPreview);
  }
  if (subject) subject.addEventListener('input', syncPreview);
  if (attach) attach.addEventListener('change', syncPreview);
  if (sig) sig.addEventListener('change', syncPreview);
  form.addEventListener('submit', function () { syncPreview(); });
  syncMode();
  syncPreview();
})();
</script>
<?php eca_admin_hub_end(); ?>
