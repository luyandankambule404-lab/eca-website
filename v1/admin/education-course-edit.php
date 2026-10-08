<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Course');
$id = (int) ($_GET['id'] ?? 0);
$row = [
    'title' => '', 'start_date' => '', 'end_date' => '', 'venue_id' => '', 'venue_text' => '',
    'audience' => '', 'fees' => '', 'cpd_points' => '', 'cpd_info' => '', 'description' => '',
    'registration_url' => '/cpd/registration.php', 'learner_portal_url' => '', 'cpd_course_id' => '',
    'facilitator_id' => '', 'status' => 'DRAFT', 'is_featured' => 0,
];
if ($id > 0) {
    $found = eca_education_one($conn, 'SELECT * FROM education_courses WHERE id = ? LIMIT 1', [$id]);
    if ($found) {
        $row = $found;
    }
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!isset(eca_education_course_statuses()[$status])) {
        $status = 'DRAFT';
    }
    $payload = [
        $title,
        eca_education_unique_slug($conn, 'education_courses', $title !== '' ? $title : 'course', $id),
        trim((string) ($_POST['start_date'] ?? '')) ?: null,
        trim((string) ($_POST['end_date'] ?? '')) ?: null,
        (int) ($_POST['venue_id'] ?? 0) ?: null,
        trim((string) ($_POST['venue_text'] ?? '')),
        trim((string) ($_POST['audience'] ?? '')),
        trim((string) ($_POST['fees'] ?? '')),
        ($_POST['cpd_points'] ?? '') !== '' ? (float) $_POST['cpd_points'] : null,
        trim((string) ($_POST['cpd_info'] ?? '')),
        trim((string) ($_POST['description'] ?? '')),
        trim((string) ($_POST['registration_url'] ?? '')),
        trim((string) ($_POST['learner_portal_url'] ?? '')),
        (int) ($_POST['cpd_course_id'] ?? 0) ?: null,
        (int) ($_POST['facilitator_id'] ?? 0) ?: null,
        $status,
        !empty($_POST['is_featured']) ? 1 : 0,
    ];
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($id > 0) {
        $payload[] = $id;
        $conn->prepare(
            'UPDATE education_courses SET title=?, slug=?, start_date=?, end_date=?, venue_id=?, venue_text=?, audience=?, fees=?, cpd_points=?, cpd_info=?, description=?, registration_url=?, learner_portal_url=?, cpd_course_id=?, facilitator_id=?, status=?, is_featured=? WHERE id=?'
        )->execute($payload);
        eca_audit('education.course.update', 'education_courses', (string) $id);
        $notice = 'Course saved.';
        $row = eca_education_one($conn, 'SELECT * FROM education_courses WHERE id = ? LIMIT 1', [$id]) ?: $row;
    } else {
        $conn->prepare(
            'INSERT INTO education_courses (title, slug, start_date, end_date, venue_id, venue_text, audience, fees, cpd_points, cpd_info, description, registration_url, learner_portal_url, cpd_course_id, facilitator_id, status, is_featured)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute($payload);
        $id = (int) $conn->lastInsertId();
        eca_audit('education.course.create', 'education_courses', (string) $id);
        header('Location: /admin/education-course-edit.php?id=' . $id);
        exit;
    }
}
$style = eca_education_admin_field_style();
$csrf = eca_admin_csrf();
eca_admin_hub_start($id ? 'Edit course' : 'Add course', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit course' : 'Add course' ?></h1></div>
<?php eca_education_admin_nav('courses'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Course title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Start<br><input type="datetime-local" name="start_date" value="<?= eca_admin_h($row['start_date'] ? date('Y-m-d\TH:i', strtotime((string) $row['start_date'])) : '') ?>" class="form-control"></label></p>
    <p><label>End<br><input type="datetime-local" name="end_date" value="<?= eca_admin_h($row['end_date'] ? date('Y-m-d\TH:i', strtotime((string) $row['end_date'])) : '') ?>" class="form-control"></label></p>
    <p><label>Venue<br>
        <select name="venue_id" class="form-control">
            <option value="">Use venue text</option>
            <?php foreach (eca_education_venues($conn, false) as $venue): ?>
                <option value="<?= (int) $venue['id'] ?>"<?= (int) ($row['venue_id'] ?? 0) === (int) $venue['id'] ? ' selected' : '' ?>><?= eca_admin_h($venue['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>Venue text<br><input name="venue_text" value="<?= eca_admin_h($row['venue_text'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Facilitator<br>
        <select name="facilitator_id" class="form-control">
            <option value="">None</option>
            <?php foreach (eca_education_facilitators($conn, false) as $person): ?>
                <option value="<?= (int) $person['id'] ?>"<?= (int) ($row['facilitator_id'] ?? 0) === (int) $person['id'] ? ' selected' : '' ?>><?= eca_admin_h($person['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>Target audience<br><input name="audience" value="<?= eca_admin_h($row['audience'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Fees<br><input name="fees" value="<?= eca_admin_h($row['fees'] ?? '') ?>" class="form-control"></label></p>
    <p><label>CPD points<br><input name="cpd_points" value="<?= eca_admin_h((string) ($row['cpd_points'] ?? '')) ?>" class="form-control"></label></p>
    <p><label>CPD information<br><input name="cpd_info" value="<?= eca_admin_h($row['cpd_info'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Course description<br><textarea name="description" rows="6" class="form-control"><?= eca_admin_h($row['description'] ?? '') ?></textarea></label></p>
    <p><label>Registration link<br><input name="registration_url" value="<?= eca_admin_h($row['registration_url'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Learner Portal link<br><input name="learner_portal_url" value="<?= eca_admin_h($row['learner_portal_url'] ?? '') ?>" placeholder="Leave blank to use the default portal" class="form-control"></label></p>
    <p><label>Linked CPD course ID<br><input name="cpd_course_id" value="<?= eca_admin_h((string) ($row['cpd_course_id'] ?? '')) ?>" class="form-control"></label></p>
    <p><label>Training status
        <select name="status" class="form-control">
            <?php foreach (eca_education_course_statuses() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['status'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?>> Feature this course</label></p>
    <button class="hub-btn" type="submit">Save course</button>
    <a class="hub-home-link" href="/admin/education-courses.php">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
