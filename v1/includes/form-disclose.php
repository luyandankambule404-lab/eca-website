<script>
(function () {
  function setOpen(btn, panel, open) {
    btn.setAttribute("aria-expanded", open ? "true" : "false");
    if (open) panel.removeAttribute("hidden");
    else panel.setAttribute("hidden", "");
  }

  document.querySelectorAll(".eca-disclose-btn").forEach(function (btn) {
    var id = btn.getAttribute("aria-controls");
    var panel = id ? document.getElementById(id) : btn.nextElementSibling;
    if (!panel) return;
    btn.addEventListener("click", function () {
      setOpen(btn, panel, btn.getAttribute("aria-expanded") !== "true");
    });
  });

  document.querySelectorAll("details.eca-faq-card").forEach(function (details) {
    var summary = details.querySelector("summary");
    if (!summary) return;
    var sync = function () {
      summary.setAttribute("aria-expanded", details.open ? "true" : "false");
    };
    details.addEventListener("toggle", sync);
    summary.addEventListener("click", function () {
      window.setTimeout(sync, 0);
    });
    sync();
  });

  document.addEventListener("click", function (event) {
    var btn = event.target.closest(".eca-faq-card .faq-btn");
    if (!btn) return;
    var content = btn.nextElementSibling;
    if (!content || !content.classList.contains("faq-content")) return;
    var wrap = btn.closest(".faq") || document;
    var open = btn.getAttribute("aria-expanded") === "true";
    wrap.querySelectorAll(".faq-btn").forEach(function (other) {
      if (other === btn) return;
      other.setAttribute("aria-expanded", "false");
      var otherContent = other.nextElementSibling;
      if (otherContent && otherContent.classList.contains("faq-content")) {
        otherContent.style.display = "none";
      }
    });
    btn.setAttribute("aria-expanded", open ? "false" : "true");
    content.style.display = open ? "none" : "block";
  });
})();
</script>
