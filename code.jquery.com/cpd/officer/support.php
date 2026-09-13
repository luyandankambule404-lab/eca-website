<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";

$msg = "";
$msg_type = "success";

/* =========================
   UPDATE TICKET STATUS / REPLY
========================= */
if(isset($_POST['update_ticket'])){
    $id          = (int)($_POST['ticket_id'] ?? 0);
    $status      = trim($_POST['status'] ?? '');
    $admin_reply = trim($_POST['admin_reply'] ?? '');

    if($id > 0 && $status !== ''){
        $stmt = $conn->prepare("
            UPDATE support_tickets
            SET status = ?,
                admin_reply = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $status, $admin_reply, $id);

        if($stmt->execute()){
            $msg = "Support ticket updated successfully.";
            $msg_type = "success";
        }else{
            $msg = "Failed to update support ticket.";
            $msg_type = "danger";
        }
    }
}

/* =========================
   DELETE TICKET
========================= */
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM support_tickets WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $msg = "Support ticket deleted successfully.";
        $msg_type = "success";
    }else{
        $msg = "Failed to delete support ticket.";
        $msg_type = "danger";
    }
}

/* =========================
   FILTERS
========================= */
$search   = trim($_GET['search'] ?? '');
$status_f = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');
$category = trim($_GET['category'] ?? '');

$where  = " WHERE 1=1 ";
$params = [];
$types  = "";

if($search !== ''){
    $where .= " AND (ticket_no LIKE ? OR full_name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

if($status_f !== ''){
    $where .= " AND status = ? ";
    $params[] = $status_f;
    $types .= "s";
}

if($priority !== ''){
    $where .= " AND priority = ? ";
    $params[] = $priority;
    $types .= "s";
}

if($category !== ''){
    $where .= " AND category = ? ";
    $params[] = $category;
    $types .= "s";
}

/* =========================
   KPI STATS
========================= */
$total_tickets = 0;
$open_tickets  = 0;
$progress_tix  = 0;
$resolved_tix  = 0;

$q = $conn->query("SELECT COUNT(*) c FROM support_tickets");
if($q) $total_tickets = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM support_tickets WHERE status='Open'");
if($q) $open_tickets = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM support_tickets WHERE status='In Progress'");
if($q) $progress_tix = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM support_tickets WHERE status IN ('Resolved','Closed')");
if($q) $resolved_tix = (int)$q->fetch_assoc()['c'];

/* =========================
   CATEGORY STATS
========================= */
$category_stats = $conn->query("
    SELECT category, COUNT(*) total
    FROM support_tickets
    GROUP BY category
    ORDER BY total DESC, category ASC
    LIMIT 6
");

/* =========================
   TICKETS LIST
========================= */
$sql = "
    SELECT *
    FROM support_tickets
    $where
    ORDER BY created_at DESC, id DESC
";

$stmt = $conn->prepare($sql);
if($types !== ''){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once "../header.php";
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0" style="font-weight:950; letter-spacing:-.3px;">Support Management</h4>
    <div class="text-muted">Manage contractor support tickets, status updates, and replies</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-brand btn-pill" href="/cpd/admin/dashboard.php">
      <i class="fa-solid fa-house me-2"></i>Dashboard
    </a>
    <a class="btn btn-soft btn-pill" href="/cpd/admin/feedback.php">
      <i class="fa-solid fa-comments me-2"></i>Feedback
    </a>
  </div>
</div>

<?php if($msg): ?>
  <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> rounded-4 border-0 shadow-sm">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="row g-3 kpi-grid">
  <div class="col-md-3">
    <div class="kpi-card-premium kpi-lead">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Total Tickets</div>
          <div class="kpi-value"><?= $total_tickets ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-ticket"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-layer-group me-1"></i>All</span>
        <span><i class="fa-regular fa-clock me-1"></i>Live</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-red">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Open</div>
          <div class="kpi-value"><?= $open_tickets ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-life-ring"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-bell me-1"></i>Needs action</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Priority</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-soft">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">In Progress</div>
          <div class="kpi-value"><?= $progress_tix ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-spinner"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-gears me-1"></i>Work queue</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Monitor</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Resolved / Closed</div>
          <div class="kpi-value"><?= $resolved_tix ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-check-double me-1"></i>Completed</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Archived</span>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-lg-8">
    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div style="font-weight:950;">Filter Support Tickets</div>
            <div class="text-muted small">Search by ticket number, user, subject, or message</div>
          </div>
        </div>

        <form method="GET" class="row g-2">
          <div class="col-md-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-premium" placeholder="Search tickets...">
          </div>

          <div class="col-md-2">
            <select name="status" class="form-select form-control-premium">
              <option value="">All Status</option>
              <option value="Open" <?= $status_f==='Open'?'selected':'' ?>>Open</option>
              <option value="In Progress" <?= $status_f==='In Progress'?'selected':'' ?>>In Progress</option>
              <option value="Resolved" <?= $status_f==='Resolved'?'selected':'' ?>>Resolved</option>
              <option value="Closed" <?= $status_f==='Closed'?'selected':'' ?>>Closed</option>
            </select>
          </div>

          <div class="col-md-2">
            <select name="priority" class="form-select form-control-premium">
              <option value="">All Priority</option>
              <option value="High" <?= $priority==='High'?'selected':'' ?>>High</option>
              <option value="Medium" <?= $priority==='Medium'?'selected':'' ?>>Medium</option>
              <option value="Low" <?= $priority==='Low'?'selected':'' ?>>Low</option>
            </select>
          </div>

          <div class="col-md-2">
            <select name="category" class="form-select form-control-premium">
              <option value="">All Categories</option>
              <option value="Portal Access" <?= $category==='Portal Access'?'selected':'' ?>>Portal Access</option>
              <option value="Course Issue" <?= $category==='Course Issue'?'selected':'' ?>>Course Issue</option>
              <option value="Certificate Issue" <?= $category==='Certificate Issue'?'selected':'' ?>>Certificate Issue</option>
              <option value="Payments" <?= $category==='Payments'?'selected':'' ?>>Payments</option>
              <option value="Resources" <?= $category==='Resources'?'selected':'' ?>>Resources</option>
              <option value="Technical Support" <?= $category==='Technical Support'?'selected':'' ?>>Technical Support</option>
              <option value="General Inquiry" <?= $category==='General Inquiry'?'selected':'' ?>>General Inquiry</option>
            </select>
          </div>

          <div class="col-md-2 d-grid">
            <button class="btn btn-brand btn-pill">
              <i class="fa-solid fa-filter me-2"></i>Filter
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div style="font-weight:950;">Support Tickets</div>
            <div class="text-muted small">Latest support requests from contractors</div>
          </div>
        </div>

        <?php if($result && $result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-premium align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Ticket</th>
                  <th>User</th>
                  <th>Category</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Message</th>
                  <th>Date</th>
                  <th width="170">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $n = 1; while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $n++ ?></td>

                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($row['ticket_no']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($row['subject']) ?></div>
                    </td>

                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($row['email']) ?></div>
                    </td>

                    <td>
                      <span class="badge text-bg-light rounded-pill px-3 py-2">
                        <?= htmlspecialchars($row['category']) ?>
                      </span>
                    </td>

                    <td>
                      <?php
                        $p = strtolower($row['priority']);
                        $pClass = 'priority-medium';
                        if($p === 'high') $pClass = 'priority-high';
                        if($p === 'low') $pClass = 'priority-low';
                      ?>
                      <span class="ticket-badge <?= $pClass ?>">
                        <i class="fa-solid fa-flag me-1"></i><?= htmlspecialchars($row['priority']) ?>
                      </span>
                    </td>

                    <td>
                      <?php
                        $s = strtolower($row['status']);
                        $sClass = 'status-open';
                        if($s === 'in progress') $sClass = 'status-progress';
                        if($s === 'resolved' || $s === 'closed') $sClass = 'status-resolved';
                      ?>
                      <span class="ticket-badge <?= $sClass ?>">
                        <i class="fa-solid fa-circle me-1"></i><?= htmlspecialchars($row['status']) ?>
                      </span>
                    </td>

                    <td style="min-width:240px;">
                      <?= nl2br(htmlspecialchars(mb_strimwidth($row['message'], 0, 120, '...'))) ?>
                    </td>

                    <td>
                      <div><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                      <div class="text-muted small"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                    </td>

                    <td>
                      <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-sm btn-brand rounded-pill px-3"
                                data-bs-toggle="modal"
                                data-bs-target="#ticketModal<?= (int)$row['id'] ?>">
                          <i class="fa-solid fa-eye"></i>
                        </button>

                        <a href="?delete=<?= (int)$row['id'] ?>"
                           class="btn btn-sm btn-danger rounded-pill px-3"
                           onclick="return confirm('Delete this support ticket?')">
                           <i class="fa-solid fa-trash"></i>
                        </a>
                      </div>
                    </td>
                  </tr>

                  <!-- MODAL -->
                  <div class="modal fade" id="ticketModal<?= (int)$row['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0">
                          <div>
                            <h5 class="modal-title mb-1" style="font-weight:900;">
                              <?= htmlspecialchars($row['ticket_no']) ?>
                            </h5>
                            <div class="text-muted small"><?= htmlspecialchars($row['subject']) ?></div>
                          </div>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST">
                          <div class="modal-body pt-0">
                            <input type="hidden" name="ticket_id" value="<?= (int)$row['id'] ?>">

                            <div class="row g-3 mb-3">
                              <div class="col-md-6">
                                <label class="form-label fw-bold">User</label>
                                <div class="form-control form-control-premium bg-light">
                                  <?= htmlspecialchars($row['full_name']) ?> — <?= htmlspecialchars($row['email']) ?>
                                </div>
                              </div>

                              <div class="col-md-3">
                                <label class="form-label fw-bold">Category</label>
                                <div class="form-control form-control-premium bg-light">
                                  <?= htmlspecialchars($row['category']) ?>
                                </div>
                              </div>

                              <div class="col-md-3">
                                <label class="form-label fw-bold">Priority</label>
                                <div class="form-control form-control-premium bg-light">
                                  <?= htmlspecialchars($row['priority']) ?>
                                </div>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label class="form-label fw-bold">Message</label>
                              <div class="form-control form-control-premium bg-light" style="min-height:120px; white-space:pre-wrap;">
                                <?= htmlspecialchars($row['message']) ?>
                              </div>
                            </div>

                            <div class="row g-3">
                              <div class="col-md-4">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select form-control-premium" required>
                                  <option value="Open" <?= $row['status']==='Open'?'selected':'' ?>>Open</option>
                                  <option value="In Progress" <?= $row['status']==='In Progress'?'selected':'' ?>>In Progress</option>
                                  <option value="Resolved" <?= $row['status']==='Resolved'?'selected':'' ?>>Resolved</option>
                                  <option value="Closed" <?= $row['status']==='Closed'?'selected':'' ?>>Closed</option>
                                </select>
                              </div>

                              <div class="col-md-8">
                                <label class="form-label fw-bold">Admin Reply / Note</label>
                                <textarea name="admin_reply" class="form-control form-control-premium" rows="5" placeholder="Write internal note or response..."><?= htmlspecialchars($row['admin_reply'] ?? '') ?></textarea>
                              </div>
                            </div>
                          </div>

                          <div class="modal-footer border-0">
                            <button type="button" class="btn btn-soft rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="update_ticket" class="btn btn-brand rounded-pill px-4">
                              <i class="fa-solid fa-save me-2"></i>Save Changes
                            </button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-headset mb-3" style="font-size:42px;"></i>
            <div class="fw-bold">No support tickets found</div>
            <div class="small">There are no support requests matching your filter.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="quick-tile mb-3">
      <div class="t mb-1">Support Insight</div>
      <div class="s">Use ticket category and priority trends to identify recurring contractor issues and improve response time.</div>
    </div>

    <div class="card card-premium mb-3">
      <div class="card-body">
        <div style="font-weight:950;" class="mb-3">Category Breakdown</div>

        <?php if($category_stats && $category_stats->num_rows > 0): ?>
          <?php while($c = $category_stats->fetch_assoc()): ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="fw-bold"><?= htmlspecialchars($c['category']) ?></span>
                <span class="text-muted"><?= (int)$c['total'] ?></span>
              </div>
              <div class="progress progress-brand" style="height:10px;">
                <div class="progress-bar"
                     style="width: <?= $total_tickets > 0 ? round(($c['total'] / $total_tickets) * 100,1) : 0 ?>%;"></div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-muted small">No support statistics available yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="quick-tile">
      <div class="t mb-1">Suggested Improvements</div>
      <ul class="small mb-0 mt-2">
        <li>Add <b>ticket assignment</b> to admin users</li>
        <li>Add <b>SLA due date</b> and overdue alerts</li>
        <li>Send <b>email reply notifications</b> to users</li>
        <li>Add <b>attachments</b> for screenshots and files</li>
        <li>Export support tickets to <b>Excel / PDF</b></li>
      </ul>
    </div>
  </div>
</div>

<style>
.form-control-premium{
  border-radius:16px !important;
  min-height:48px;
  border:1px solid rgba(8,43,87,.08);
  box-shadow:none;
}
.form-control-premium:focus{
  border-color:#0d4f9c;
  box-shadow:0 0 0 0.2rem rgba(13,79,156,.10);
}
.table-premium thead th{
  border-bottom:0;
  color:#64748b;
  font-size:.82rem;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.table-premium tbody tr{
  vertical-align:top;
}
.table-premium tbody td{
  padding-top:1rem;
  padding-bottom:1rem;
}
.ticket-badge{
  display:inline-block;
  padding:.48rem .8rem;
  border-radius:999px;
  font-weight:800;
  font-size:.82rem;
}
.status-open{ background:#eff6ff; color:#1d4ed8; }
.status-progress{ background:#fff7ed; color:#c2410c; }
.status-resolved{ background:#ecfdf5; color:#166534; }
.priority-high{ background:#fef2f2; color:#b91c1c; }
.priority-medium{ background:#fff7ed; color:#c2410c; }
.priority-low{ background:#f0fdf4; color:#15803d; }
</style>

<?php require_once "../footer.php"; ?>