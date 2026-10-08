<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../code.jquery.com/cpd/auth.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$csrf = eca_csrf_token();
$notice = '';
$cpdUser = cpd_sso_from_member($member);
$userId = (int) ($cpdUser['id'] ?? 0);
$email = trim((string) ($member['email'] ?? $cpdUser['email'] ?? ''));
$membership = trim((string) ($member['membership'] ?? ''));

$points = 0.0;
$target = 12.0;
$applications = [];
$courses = [];
$openCourses = [];

if ($portal) {
    try {
        if ($userId > 0) {
            $stmt = $portal->prepare('SELECT COALESCE(SUM(points), 0) FROM cpd_points_ledger WHERE user_id = ?');
            $stmt->execute([$userId]);
            $points = (float) $stmt->fetchColumn();
        }
        if ($membership !== '') {
            $stmt = $portal->prepare(
                'SELECT a.id, a.course_id, a.status, a.training_status, a.created_at, a.certificate_number,
                        c.title, c.start_date, c.end_date, c.venue, c.points
                 FROM cpd_applications a
                 LEFT JOIN courses c ON c.id = a.course_id
                 WHERE a.membership_number = ?
                 ORDER BY a.id DESC LIMIT 50'
            );
            $stmt->execute([$membership]);
        } elseif ($email !== '') {
            $stmt = $portal->prepare(
                'SELECT a.id, a.course_id, a.status, a.training_status, a.created_at, a.certificate_number,
                        c.title, c.start_date, c.end_date, c.venue, c.points
                 FROM cpd_applications a
                 LEFT JOIN courses c ON c.id = a.course_id
                 WHERE a.email = ? AND a.email <> \'\'
                 ORDER BY a.id DESC LIMIT 50'
            );
            $stmt->execute([$email]);
        } else {
            $stmt = null;
        }
        $applications = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        if ($points <= 0) {
            foreach ($applications as $app) {
                if (strtoupper((string) ($app['status'] ?? '')) === 'APPROVED') {
                    $points += (float) ($app['points'] ?? 0);
                }
            }
        }
        $stmt = $portal->prepare(
            "SELECT id, title, start_date, end_date, venue, points, status
             FROM courses
             WHERE UPPER(status) IN ('OPEN', 'PUBLISHED', 'ACTIVE')
             ORDER BY start_date IS NULL, start_date ASC, id DESC
             LIMIT 12"
        );
        $stmt->execute();
        $openCourses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $notice = 'CPD records could not be loaded right now.';
    }
}

$appliedIds = [];
foreach ($applications as $app) {
    $appliedIds[(int) ($app['course_id'] ?? 0)] = true;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && eca_csrf_ok($_POST['csrf_token'] ?? null)
    && $portal
    && (int) ($_POST['course_id'] ?? 0) > 0
) {
    $courseId = (int) $_POST['course_id'];
    $already = false;
    foreach ($applications as $app) {
        if ((int) ($app['course_id'] ?? 0) === $courseId) {
            $already = true;
            break;
        }
    }
    if ($already) {
        $notice = 'You already have an application for that course.';
    } else {
        try {
            $courseStmt = $portal->prepare('SELECT id, title FROM courses WHERE id = ? LIMIT 1');
            $courseStmt->execute([$courseId]);
            $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
            if ($course) {
                $ins = $portal->prepare(
                    'INSERT INTO cpd_applications (course_id, company_name, membership_number, discipline, full_name, email, phone, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $courseId,
                    (string) ($member['registered_name'] ?: $member['name'] ?: 'ECA Member'),
                    $membership,
                    (string) ($member['classification'] ?? ''),
                    (string) ($member['name'] ?? ''),
                    $email,
                    (string) ($member['phone'] ?? ''),
                    'Pending',
                ]);
                $notice = 'Application submitted for ' . (string) $course['title'] . '.';
                header('Location: /client/cpd.php');
                exit;
            }
        } catch (Throwable $e) {
            $notice = 'The application could not be saved.';
        }
    }
}

$progress = $target > 0 ? min(100, round(($points / $target) * 100)) : 0;
eca_portal_start('CPD', 'cpd');
?>
<div class="hub-stats">
    <article class="hub-stat">
        <div class="hub-stat-label">CPD points</div>
        <div class="hub-stat-value"><?= eca_h(rtrim(rtrim(number_format($points, 1, '.', ''), '0'), '.') ?: '0') ?></div>
        <div class="hub-stat-meta">Current cycle</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Target</div>
        <div class="hub-stat-value"><?= eca_h((string) (int) $target) ?></div>
        <div class="hub-stat-meta"><?= (int) $progress ?>% complete</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Applications</div>
        <div class="hub-stat-value"><?= (int) count($applications) ?></div>
        <div class="hub-stat-meta">On your record</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Open courses</div>
        <div class="hub-stat-value"><?= (int) count($openCourses) ?></div>
        <div class="hub-stat-meta">Available now</div>
    </article>
</div>

<?php if ($notice): ?><p class="project-notice"><?= eca_h($notice) ?></p><?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="panel eca-form-panel eca-table-panel">
            <div class="eca-panel-heading"><h3>My CPD applications</h3></div>
            <div class="eca-panel-body">
                <table class="hub-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>When</th>
                            <th>Points</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?= eca_h($app['title'] ?: 'Course') ?></td>
                            <td><?= eca_h(!empty($app['start_date']) ? date('d M Y', strtotime((string) $app['start_date'])) : '') ?></td>
                            <td><?= eca_h((string) ($app['points'] ?? '0')) ?></td>
                            <td><?= eca_h($app['status'] ?: 'Pending') ?><?= !empty($app['training_status']) ? ' · ' . eca_h($app['training_status']) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$applications): ?>
                        <tr data-hub-empty-row><td colspan="4">No CPD applications yet. Apply for an open course on the right.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel eca-form-panel">
            <div class="eca-panel-heading"><h3>Open courses</h3></div>
            <div class="eca-panel-body">
                <?php if (!$openCourses): ?>
                    <p class="cpd-card-text">No open CPD courses are listed right now.</p>
                <?php endif; ?>
                <?php foreach ($openCourses as $course): ?>
                    <div class="hub-course">
                        <div>
                            <h3><?= eca_h($course['title'] ?? 'Course') ?></h3>
                            <p>
                                <?= eca_h($course['venue'] ?: 'Venue TBC') ?>
                                <?php if (!empty($course['start_date'])): ?> · <?= eca_h(date('d M Y', strtotime((string) $course['start_date']))) ?><?php endif; ?>
                                · <?= eca_h((string) ($course['points'] ?? '0')) ?> points
                            </p>
                        </div>
                        <?php if (!empty($appliedIds[(int) $course['id']])): ?>
                            <span class="hub-welcome-role">Applied</span>
                        <?php else: ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
                                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                                <button class="btn-primary" type="submit">Apply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php eca_portal_end(); ?>
