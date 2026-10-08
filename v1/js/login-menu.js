(function () {
    if (window.ecaLoginMenuBound) return;
    window.ecaLoginMenuBound = true;

    function loginDrops() {
        return document.querySelectorAll("[data-eca-login-drop]");
    }

    function menuButton(drop) {
        return drop.querySelector(".eca-nav-login, [aria-haspopup]");
    }

    function closeLoginDrops(except) {
        loginDrops().forEach(function (drop) {
            if (drop === except) return;
            drop.classList.remove("is-open");
            var btn = menuButton(drop);
            if (btn) btn.setAttribute("aria-expanded", "false");
        });
    }

    function closeOtherNavDrops() {
        document.querySelectorAll(".eca-nav-drop, .eca-member-split").forEach(function (other) {
            if (other.hasAttribute("data-eca-login-drop")) return;
            other.classList.remove("is-open");
            var otherBtn = other.querySelector("[aria-expanded]");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
        });
    }

    document.addEventListener("click", function (event) {
        var drop = event.target.closest("[data-eca-login-drop]");
        var btn = event.target.closest("[data-eca-login-drop] > .eca-nav-link, [data-eca-login-drop] > .eca-nav-login");
        if (btn && drop) {
            event.preventDefault();
            event.stopPropagation();
            var open = !drop.classList.contains("is-open");
            closeLoginDrops(open ? drop : null);
            drop.classList.toggle("is-open", open);
            btn.setAttribute("aria-expanded", open ? "true" : "false");
            if (open) closeOtherNavDrops();
            return;
        }
        if (!drop) closeLoginDrops();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeLoginDrops();
    });
})();
