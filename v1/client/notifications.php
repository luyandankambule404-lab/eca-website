<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/membership.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$life = eca_member_lifecycle($portal, $member);
if ((int) ($member['client_id'] ?? 0) < 1 && !empty($life['client_id'])) {
    $member['client_id'] = (int) $life['client_id'];
}
$notice = '';
$csrf = eca_csrf_token();

if ($portal && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'mark_read') {
        $id = (int) ($_POST['id'] ?? 0);
        eca_mark_member_notifications_read($portal, $member, $id > 0 ? $id : null);
        $notice = 'Notifications updated.';
    }
}

$notes = $portal ? eca_member_notifications($portal, $member, 50) : [];
usort($notes, static function (array $a, array $b): int {
    $unread = (empty($a['is_read']) ? 0 : 1) <=> (empty($b['is_read']) ? 0 : 1);
    if ($unread !== 0) {
        return $unread;
    }
    return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
});
$unreadCount = 0;
foreach ($notes as $note) {
    if (empty($note['is_read'])) {
        $unreadCount++;
    }
}

eca_portal_start('Notifications', 'notifications', 'is-notes', 'Membership updates for this account, newest first.');
?>
<section class="hub-notes">
    <header class="hub-notes-head">
        <div>
            <h2>Your notifications</h2>
            <p><?= $notes ? eca_h((string) count($notes)) . ' on file' . ($unreadCount ? ' · ' . eca_h((string) $unreadCount) . ' unread' : '') : 'Nothing waiting right now.' ?></p>
        </div>
        <?php if ($unreadCount): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
            <input type="hidden" name="action" value="mark_read">
            <button class="hub-btn-navy" type="submit">Mark all as read</button>
        </form>
        <?php endif; ?>
    </header>
    <?php if ($notice): ?><p class="hub-notes-notice"><?= eca_h($notice) ?></p><?php endif; ?>
    <?php if (!$notes): ?>
        <div class="hub-notes-empty">
            <i class="fa-solid fa-bell" aria-hidden="true"></i>
            <strong>No membership notifications yet.</strong>
            <span>Application, payment and membership updates will appear here.</span>
        </div>
    <?php else: ?>
        <ul class="hub-notes-list">
            <?php foreach ($notes as $note):
                $unread = empty($note['is_read']);
                $kind = trim((string) ($note['type'] ?? ''));
                $kindLabel = $kind !== '' ? ucwords(strtolower(str_replace('_', ' ', $kind))) : 'Update';
                $link = trim((string) ($note['link'] ?? ''));
                $safeLink = ($link !== '' && str_starts_with($link, '/') && !str_contains($link, '//')) ? $link : '';
            ?>
            <li class="<?= $unread ? 'is-unread' : '' ?>">
                <span class="hub-notes-mark" aria-hidden="true"></span>
                <div class="hub-notes-copy">
                    <div class="hub-notes-meta">
                        <span><?= eca_h($kindLabel) ?></span>
                        <?php if ($unread): ?><span class="hub-notes-pill">Unread</span><?php endif; ?>
                        <time><?= eca_h(eca_display_date((string) ($note['created_at'] ?? ''), '')) ?></time>
                    </div>
                    <h3><?= eca_h($note['title'] ?? '') ?></h3>
                    <p><?= eca_h($note['message'] ?? '') ?></p>
                </div>
                <div class="hub-notes-actions">
                    <?php if ($safeLink !== ''): ?><a class="hub-home-link" href="<?= eca_h($safeLink) ?>">Open</a><?php endif; ?>
                    <?php if ($unread): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="id" value="<?= (int) ($note['id'] ?? 0) ?>">
                        <button type="submit">Mark read</button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php eca_portal_end(); ?>
