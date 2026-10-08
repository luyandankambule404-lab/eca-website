<?php
if (!function_exists('cpd_datetime_local_value')) {
    function cpd_datetime_local_value($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d\TH:i', $ts) : '';
    }
}

$courseRows = [];
if (isset($courses) && $courses instanceof mysqli_result) {
    while ($row = $courses->fetch_assoc()) {
        $courseRows[] = $row;
    }
}
$total_courses = (int) ($total_courses ?? count($courseRows));
$total_apps = (int) ($total_apps ?? 0);
$total_learners = (int) ($total_learners ?? 0);
$msg = (string) ($msg ?? '');
$defaultBanner = '/cpd/images/banner.png';
?>
<style>
.cpd-course-stage .page-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:16px;
  margin-bottom:20px;
}
.cpd-course-stage .analytics-card{
  padding:22px;
  border-radius:14px;
  color:#fff;
  text-align:center;
  box-shadow:0 10px 24px rgba(6,37,74,.12);
}
.cpd-course-stage .analytics-card h3{
  margin:0;
  font-size:2rem;
  font-weight:800;
}
.cpd-course-stage .analytics-card span{
  display:block;
  margin-top:6px;
  font-size:.9rem;
  letter-spacing:.04em;
  text-transform:uppercase;
  opacity:.9;
}
.cpd-course-stage .course-card{
  background:#fff;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 10px 25px rgba(0,0,0,.08);
  height:100%;
  display:flex;
  flex-direction:column;
}
.cpd-course-stage .course-banner img{
  width:100%;
  height:180px;
  object-fit:cover;
}
.cpd-course-stage .course-content{
  padding:20px;
  display:flex;
  flex-direction:column;
  gap:8px;
  flex:1;
}
.cpd-course-stage .course-meta,
.cpd-course-stage .course-stats{
  font-size:13px;
  color:#667085;
}
.cpd-course-stage .course-stats{
  display:flex;
  justify-content:space-between;
  gap:8px;
}
.cpd-course-stage .course-actions{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:auto;
  padding-top:12px;
}
.cpd-course-stage .countdown{
  font-weight:700;
  color:#06254a;
}
</style>

<div class="cpd-course-stage">
  <div class="page-header">
    <div>
      <h4 class="mb-1">CPD Courses Dashboard</h4>
      <p class="text-muted mb-0">Create, publish and manage training courses</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Add Course</button>
  </div>

  <?php if ($msg !== ''): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="analytics-card" style="background:linear-gradient(135deg,#06254a,#0e5aa7);">
        <h3><?= $total_courses ?></h3>
        <span>Courses</span>
      </div>
    </div>
    <div class="col-md-4">
      <div class="analytics-card" style="background:linear-gradient(135deg,#134f62,#25809b);">
        <h3><?= $total_apps ?></h3>
        <span>Applications</span>
      </div>
    </div>
    <div class="col-md-4">
      <div class="analytics-card" style="background:linear-gradient(135deg,#1a7f5a,#2f9e6f);">
        <h3><?= $total_learners ?></h3>
        <span>Approved learners</span>
      </div>
    </div>
  </div>

  <input type="text" id="courseSearch" class="form-control mb-4" placeholder="Search courses">

  <div class="row g-4">
    <?php if (!$courseRows): ?>
      <div class="col-12">
        <div class="alert alert-light border">No courses yet. Use Add Course to create the first one.</div>
      </div>
    <?php endif; ?>
    <?php foreach ($courseRows as $c): ?>
      <?php
      $banner = trim((string) ($c['banner'] ?? ''));
      $bannerSrc = $banner !== ''
          ? '/cpd/' . ltrim(str_replace('\\', '/', $banner), '/')
          : $defaultBanner;
      $status = strtoupper((string) ($c['status'] ?? 'DRAFT'));
      ?>
      <div class="col-md-6 course-item">
        <div class="course-card">
          <div class="course-banner">
            <img src="<?= e($bannerSrc) ?>" alt="" onerror="this.src='<?= e($defaultBanner) ?>'">
          </div>
          <div class="course-content">
            <h5><?= e($c['title'] ?? '') ?></h5>
            <div class="course-meta">
              <i class="fa fa-calendar"></i> <?= e($c['start_date'] ?? '') ?><br>
              <i class="fa fa-map-marker-alt"></i> <?= e($c['venue'] ?? '') ?>
            </div>
            <div class="course-stats">
              <div><i class="fa fa-users"></i> <?= (int) ($c['learners'] ?? 0) ?></div>
              <div><i class="fa fa-file"></i> <?= (int) ($c['applications'] ?? 0) ?></div>
              <div><i class="fa fa-star"></i> <?= e($c['points'] ?? '0') ?></div>
            </div>
            <div class="countdown" data-date="<?= e($c['start_date'] ?? '') ?>"></div>
            <div class="course-actions">
              <a href="course_students.php?course_id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-success">
                <i class="fa fa-user-graduate"></i> Students
              </a>
              <button
                class="btn btn-sm btn-warning editCourse"
                type="button"
                data-id="<?= (int) $c['id'] ?>"
                data-title="<?= e($c['title'] ?? '') ?>"
                data-desc="<?= e($c['description'] ?? '') ?>"
                data-start="<?= e(cpd_datetime_local_value($c['start_date'] ?? '')) ?>"
                data-end="<?= e(cpd_datetime_local_value($c['end_date'] ?? '')) ?>"
                data-venue="<?= e($c['venue'] ?? '') ?>"
                data-cap="<?= (int) ($c['capacity'] ?? 0) ?>"
                data-points="<?= e($c['points'] ?? '') ?>"
                data-status="<?= e($status) ?>"
              >Edit</button>
              <button
                class="btn btn-sm btn-info statusCourse"
                type="button"
                data-id="<?= (int) $c['id'] ?>"
                data-status="<?= e($status) ?>"
              ><?= e($status) ?></button>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this course?')">
                <?= cpd_csrf_input() ?>
                <button type="submit" name="delete" value="<?= (int) $c['id'] ?>" class="btn btn-sm btn-danger">Delete</button>
              </form>
              <a
                href="send_course_email.php?course_id=<?= (int) $c['id'] ?>"
                class="btn btn-sm"
                onclick="return confirm('Send this course details email to all members?');"
                style="background:linear-gradient(135deg,#134f62,#25809b);color:#fff;border:none;border-radius:10px;padding:8px 14px;font-weight:600;"
              >
                <i class="fa-solid fa-paper-plane"></i> Email All Members
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <?= cpd_csrf_input() ?>
        <input type="hidden" name="action" value="add">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add Course</h5>
          <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Course Title</label>
              <input class="form-control" name="title" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">CPD Points</label>
              <input class="form-control" name="points" type="number" step="0.01">
            </div>
            <div class="col-md-3">
              <label class="form-label">Capacity</label>
              <input class="form-control" name="capacity" type="number" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="datetime-local" class="form-control" name="start_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="datetime-local" class="form-control" name="end_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">Venue</label>
              <input class="form-control" name="venue">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-control" name="status">
                <option>OPEN</option>
                <option>CLOSED</option>
                <option>DRAFT</option>
                <option>COMPLETED</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Course Banner</label>
              <input type="file" class="form-control" name="banner" accept="image/jpeg,image/png,image/webp">
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="4"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <?= cpd_csrf_input() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="course_id" id="course_id">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title">Edit Course</h5>
          <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Course Title</label>
              <input class="form-control" name="title" id="title">
            </div>
            <div class="col-md-3">
              <label class="form-label">Points</label>
              <input class="form-control" name="points" id="points" type="number" step="0.01">
            </div>
            <div class="col-md-3">
              <label class="form-label">Capacity</label>
              <input class="form-control" name="capacity" id="capacity" type="number" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Date</label>
              <input type="datetime-local" class="form-control" name="start_date" id="start">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Date</label>
              <input type="datetime-local" class="form-control" name="end_date" id="end">
            </div>
            <div class="col-md-6">
              <label class="form-label">Venue</label>
              <input class="form-control" name="venue" id="venue">
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select class="form-control" name="status" id="status">
                <option>OPEN</option>
                <option>CLOSED</option>
                <option>DRAFT</option>
                <option>COMPLETED</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="description" rows="4"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Course Banner</label>
              <input type="file" class="form-control" name="banner" id="banner" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success" type="submit">Update Course</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <?= cpd_csrf_input() ?>
        <input type="hidden" name="course_id" id="status_course_id">
        <div class="modal-header">
          <h5 class="modal-title">Update course status</h5>
          <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Status</label>
          <select class="form-control" name="status" id="status_value">
            <option>OPEN</option>
            <option>CLOSED</option>
            <option>DRAFT</option>
            <option>COMPLETED</option>
          </select>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit" name="update_status" value="1">Save status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var search = document.getElementById('courseSearch');
  if (search) {
    search.addEventListener('keyup', function () {
      var value = this.value.toLowerCase();
      document.querySelectorAll('.course-item').forEach(function (item) {
        item.style.display = item.textContent.toLowerCase().indexOf(value) > -1 ? '' : 'none';
      });
    });
  }

  document.querySelectorAll('.editCourse').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('course_id').value = this.dataset.id || '';
      document.getElementById('title').value = this.dataset.title || '';
      document.getElementById('description').value = this.dataset.desc || '';
      document.getElementById('start').value = this.dataset.start || '';
      document.getElementById('end').value = this.dataset.end || '';
      document.getElementById('venue').value = this.dataset.venue || '';
      document.getElementById('capacity').value = this.dataset.cap || '';
      document.getElementById('points').value = this.dataset.points || '';
      document.getElementById('status').value = this.dataset.status || 'DRAFT';
      var modal = new bootstrap.Modal(document.getElementById('editModal'));
      modal.show();
    });
  });

  document.querySelectorAll('.statusCourse').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('status_course_id').value = this.dataset.id || '';
      document.getElementById('status_value').value = this.dataset.status || 'DRAFT';
      var modal = new bootstrap.Modal(document.getElementById('statusModal'));
      modal.show();
    });
  });

  document.querySelectorAll('.countdown').forEach(function (el) {
    var date = new Date(el.dataset.date).getTime();
    function tick() {
      var distance = date - Date.now();
      if (!(date > 0) || isNaN(date)) {
        el.textContent = '';
        return;
      }
      if (distance > 0) {
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        el.textContent = 'Starts in ' + days + 'd ' + hours + 'h';
      } else {
        el.textContent = 'Course Started';
      }
    }
    tick();
    setInterval(tick, 60000);
  });
})();
</script>
