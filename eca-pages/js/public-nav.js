(function () {
    if (window.ecaPublicNavBound) return;
    window.ecaPublicNavBound = true;

    function closeDrops(root, except) {
        root.querySelectorAll(".eca-nav-drop, .eca-member-split").forEach(function (other) {
            if (other === except) return;
            if (other.classList.contains("eca-login-drop")) return;
            other.classList.remove("is-open");
            var otherBtn = other.querySelector("[aria-expanded]");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
        });
    }

    document.querySelectorAll("[data-eca-landing]").forEach(function (root) {
        if (root.dataset.ecaNavReady) return;
        root.dataset.ecaNavReady = "1";

        var toggle = root.querySelector(".eca-landing-toggle");
        var nav = root.querySelector("#ecaLandingNav, .eca-mega-nav, .eca-landing-nav");

        if (toggle && nav) {
            toggle.addEventListener("click", function () {
                var open = nav.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", open ? "true" : "false");
                if (!open) closeDrops(root);
            });
        }

        root.querySelectorAll(".eca-nav-drop").forEach(function (drop) {
            if (drop.classList.contains("eca-login-drop")) return;
            var btn = drop.querySelector(".eca-nav-link");
            if (!btn) return;
            btn.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();
                var open = drop.classList.toggle("is-open");
                btn.setAttribute("aria-expanded", open ? "true" : "false");
                closeDrops(root, drop);
            });
        });

        root.querySelectorAll(".eca-member-split").forEach(function (drop) {
            var btn = drop.querySelector(".eca-member-toggle");
            if (!btn) return;
            btn.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();
                var open = drop.classList.toggle("is-open");
                btn.setAttribute("aria-expanded", open ? "true" : "false");
                closeDrops(root, drop);
            });
        });
    });

    document.addEventListener("click", function (event) {
        if (event.target.closest(".eca-nav-drop, .eca-member-split, .eca-landing-toggle")) return;
        document.querySelectorAll("[data-eca-landing]").forEach(function (root) {
            closeDrops(root);
        });
    });

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;
        document.querySelectorAll("[data-eca-landing]").forEach(function (root) {
            closeDrops(root);
            var nav = root.querySelector("#ecaLandingNav, .eca-mega-nav, .eca-landing-nav");
            var toggle = root.querySelector(".eca-landing-toggle");
            if (nav) nav.classList.remove("is-open");
            if (toggle) toggle.setAttribute("aria-expanded", "false");
        });
    });
})();
