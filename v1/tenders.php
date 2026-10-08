<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/public-page.php';
$db = new Database();
$conn = $db->getConnection(false);
$q = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$rows = [];
if ($conn) {
    try {
        $sql = "SELECT * FROM tenders WHERE status = 'PUBLISHED'";
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (title LIKE ? OR summary LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like];
        }
        $sql .= ' ORDER BY id DESC LIMIT 50';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
    }
}
eca_public_page_start('Tenders', 'Opportunities', 'Tenders', 'Published notices from the ECA office. This list is empty until a tender is published.', 'Tenders');
?>
<div class="container-xxl py-4">
    <form class="card border-0 eca-form-panel mb-4" method="get" action="/tenders.php">
        <div class="card-body">
            <h5 class="text-primary">Search tenders</h5>
            <div class="mb-3">
                <label class="form-label" for="tenderSearch">Search</label>
                <input class="form-control" id="tenderSearch" name="search" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search tenders">
            </div>
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    <div class="row g-4">
        <?php if (!$rows): ?>
            <div class="col-12"><div class="card shadow-sm"><div class="card-body"><p class="mb-0">No published tenders at the moment.</p></div></div></div>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <div class="col-md-6">
                <div class="card company-card h-100 shadow-sm">
                    <div class="card-header"><?= htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-body">
                        <p><?= htmlspecialchars((string) ($row['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <a href="/tender.php?id=<?= (int) $row['id'] ?>">View details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php eca_public_page_end(); ?>
