<?php
$role = $_SESSION['role'] ?? null;
$is_admin = ($role === 'ADMIN');
$is_contractor = ($role === 'CONTRACTOR');
$has_hub = !empty($GLOBALS['eca_hub_shell']);
if (!function_exists('eca_session_dashboard_back_ensure')) {
    require_once dirname(__DIR__, 2) . '/includes/hub.php';
}
?>

<?php if ($has_hub): ?>
      </main>
    </div>
  </div>
</div>
<?php elseif ($is_admin || $is_contractor): ?>
  </main>
</div>
<?php else: ?>
</div>
<?php endif; ?>

<?php eca_session_dashboard_back_ensure(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
  document.body.classList.toggle('sidebar-open');
}
</script>
</body>
</html>
