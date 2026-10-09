(function () {
    if (window.ecaPublicNavBound) return;
    window.ecaPublicNavBound = true;
    document.documentElement.classList.add("eca-js");

    if (!document.querySelector(".eca-skip-link")) {
        var skip = document.createElement("a");
        skip.className = "eca-skip-link";
        skip.href = "#main-content";
        skip.textContent = "Skip to main content";
        document.body.insertBefore(skip, document.body.firstChild);
    }

    var mainTarget = document.querySelector("#main-content, main, .eca-landing-stage, .eca-inner-hero, .eca-page-hero, .news-hero");
    if (mainTarget && !document.getElementById("main-content")) {
        mainTarget.id = "main-content";
        mainTarget.setAttribute("tabindex", "-1");
    }

    var publicHeader = document.querySelector(".eca-public-header");
    if (publicHeader && !publicHeader.querySelector(".eca-utility")) {
        var utility = document.createElement("div");
        utility.className = "eca-utility";
        utility.innerHTML =
            '<div class="eca-utility-inner">' +
                '<span class="eca-utility-brand">Eswatini Contractors Association</span>' +
                '<div class="eca-utility-tools">' +
                    '<a class="eca-utility-contact" href="tel:+26824044987">+268 2404 4987</a>' +
                    '<a class="eca-utility-contact" href="mailto:info@eca.co.sz">info@eca.co.sz</a>' +
                '</div>' +
            '</div>';
        publicHeader.insertBefore(utility, publicHeader.firstChild);
    }

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
        var toggle = root.querySelector(".eca-landing-toggle");
        var nav = root.querySelector("#ecaLandingNav, .eca-mega-nav, .eca-landing-nav");

        if (nav && !nav.querySelector(".eca-header-cta")) {
            var cta = document.createElement("a");
            cta.className = "eca-header-cta";
            cta.href = /\.html$/i.test(window.location.pathname) ? "directory.html" : "/directory.php";
            cta.textContent = "Find a contractor";
            nav.appendChild(cta);
        }

        if (nav && !nav.querySelector('a[href*="membership-registration"]')) {
            var memberCta = document.createElement("a");
            memberCta.href = "/membership-registration.php";
            memberCta.textContent = "Membership Registration";
            var findCta = nav.querySelector(".eca-header-cta");
            if (findCta) {
                nav.insertBefore(memberCta, findCta);
            } else {
                nav.appendChild(memberCta);
            }
        }

        if (root.dataset.ecaNavReady) return;
        root.dataset.ecaNavReady = "1";

        if (toggle && nav) {
            toggle.addEventListener("click", function () {
                var open = nav.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", open ? "true" : "false");
                document.body.classList.toggle("eca-menu-open", open);
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
                if (open && event.detail === 0) {
                    var firstLink = drop.querySelector(".eca-nav-menu a");
                    if (firstLink) firstLink.focus();
                }
            });
            btn.addEventListener("keydown", function (event) {
                if (event.key !== "ArrowDown") return;
                event.preventDefault();
                drop.classList.add("is-open");
                btn.setAttribute("aria-expanded", "true");
                closeDrops(root, drop);
                var firstLink = drop.querySelector(".eca-nav-menu a");
                if (firstLink) firstLink.focus();
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

    document.querySelectorAll(".eca-faq-card .faq").forEach(function (group, groupIndex) {
        var buttons = group.querySelectorAll(".faq-btn");
        buttons.forEach(function (button, itemIndex) {
            var panel = button.nextElementSibling;
            if (!panel || !panel.classList.contains("faq-content")) return;

            var panelId = panel.id || "eca-faq-" + groupIndex + "-" + itemIndex;
            panel.id = panelId;
            button.setAttribute("aria-controls", panelId);
            button.setAttribute("aria-expanded", "false");
            panel.hidden = true;
            panel.style.display = "none";

            button.addEventListener("click", function (event) {
                event.stopPropagation();
                var shouldOpen = button.getAttribute("aria-expanded") !== "true";

                buttons.forEach(function (otherButton) {
                    var otherPanel = otherButton.nextElementSibling;
                    otherButton.setAttribute("aria-expanded", "false");
                    if (otherPanel && otherPanel.classList.contains("faq-content")) {
                        otherPanel.hidden = true;
                        otherPanel.style.display = "none";
                    }
                });

                button.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
                panel.hidden = !shouldOpen;
                panel.style.display = shouldOpen ? "block" : "none";
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
            document.body.classList.remove("eca-menu-open");
        });
    });

    if (!document.querySelector('script[src*="directory-live-search.js"]')) {
        var liveSearch = document.createElement("script");
        liveSearch.src = "/js/directory-live-search.js?v=20260919-2";
        document.head.appendChild(liveSearch);
    }
})();
