<?php
$role = $_SESSION['role'] ?? null;
$is_admin = ($role === 'ADMIN');
$is_contractor = ($role === 'CONTRACTOR');
?>

<?php if($is_admin || $is_contractor): ?>
  </main>
</div>
<?php else: ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar(){
  document.body.classList.toggle('sidebar-open');
}
</script>
<script>

if ("serviceWorker" in navigator) {

  window.addEventListener("load", () => {

    navigator.serviceWorker.register("/service-worker.js")
    .then(reg => console.log("Service Worker Registered", reg))
    .catch(err => console.log("SW failed", err));

  });

}

</script>
</body>
</html>