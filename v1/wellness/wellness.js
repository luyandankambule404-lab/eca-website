(function () {
    var root = document.querySelector("[data-wh]");
    if (!root) return;

    var panels = root.querySelectorAll("[data-panel]");
    var navButtons = root.querySelectorAll(".wh-nav [data-wh-tab]");
    var storageKey = "eca_wellness_checkin";
    var selectedScore = null;

    var practices = {
        reset: {
            title: "A 3-minute reset",
            body: "Stand still. Inhale for 4 counts, hold for 2, exhale for 6. Repeat six times. Then name one thing you can control in the next hour — and start there."
        },
        values: {
            title: "Name what matters",
            body: "Ask yourself: What kind of leader do my people need today? Write one word (fair, calm, clear, brave). Let that word guide your next conversation."
        },
        evening: {
            title: "Protect your evening",
            body: "After work, set one hour without work chats unless you are on call. Put the phone in another room while you eat or rest. Tomorrow’s work will still be there."
        }
    };

    var statusCopy = {
        55: ["Stretched", "You’re carrying a lot. Soften the load where you can — one boundary, one honest ask for help."],
        68: ["Uneven", "Some days hold more than others. A short reset can stop the drift before it becomes exhaustion."],
        74: ["Steady", "Your energy is building. A little intentional care can help you protect it through the week."],
        86: ["Strong", "You’re in a good place. Share the calm — check on one teammate who might need it."]
    };

    function showPanel(name) {
        panels.forEach(function (panel) {
            panel.classList.toggle("is-active", panel.getAttribute("data-panel") === name);
        });
        navButtons.forEach(function (btn) {
            btn.classList.toggle("is-active", btn.getAttribute("data-wh-tab") === name);
        });
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function openModal(title, body) {
        var modal = document.getElementById("whModal");
        document.getElementById("whModalTitle").textContent = title;
        document.getElementById("whModalBody").textContent = body;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
    }

    function applyScore(score) {
        var ring = document.getElementById("whRing");
        var num = document.getElementById("whScoreNum");
        var status = document.getElementById("whScoreStatus");
        var copy = document.getElementById("whScoreCopy");
        var info = statusCopy[score] || statusCopy[74];
        ring.style.setProperty("--p", String(score));
        ring.setAttribute("aria-label", "Resilience score " + score + " of 100");
        num.textContent = String(score);
        status.textContent = info[0];
        copy.textContent = info[1];
    }

    function loadCheckin() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return;
            var data = JSON.parse(raw);
            if (!data || !data.score) return;
            applyScore(Number(data.score));
            var el = document.getElementById("whLastCheckin");
            if (el && data.at) {
                var day = new Date(data.at);
                var sameDay = new Date().toDateString() === day.toDateString();
                el.textContent = sameDay ? "Last check-in · Today" : "Last check-in · " + day.toLocaleDateString();
            }
        } catch (e) {}
    }

    root.addEventListener("click", function (event) {
        var tab = event.target.closest("[data-wh-tab]");
        if (tab) {
            event.preventDefault();
            showPanel(tab.getAttribute("data-wh-tab"));
            return;
        }

        var practice = event.target.closest("[data-wh-practice]");
        if (practice) {
            event.preventDefault();
            var key = practice.getAttribute("data-wh-practice");
            var item = practices[key];
            if (item) openModal(item.title, item.body);
        }
    });

    document.getElementById("whModalClose").addEventListener("click", function () {
        closeModal("whModal");
    });
    document.getElementById("whModal").addEventListener("click", function (event) {
        if (event.target.id === "whModal") closeModal("whModal");
    });

    document.getElementById("whCheckinOpen").addEventListener("click", function () {
        var modal = document.getElementById("whCheckin");
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
    });

    document.getElementById("whMoods").addEventListener("click", function (event) {
        var btn = event.target.closest("button[data-score]");
        if (!btn) return;
        selectedScore = Number(btn.getAttribute("data-score"));
        Array.prototype.forEach.call(document.querySelectorAll("#whMoods button"), function (b) {
            b.classList.toggle("is-on", b === btn);
        });
        document.getElementById("whCheckinSave").disabled = false;
    });

    document.getElementById("whCheckinSave").addEventListener("click", function () {
        if (!selectedScore) return;
        applyScore(selectedScore);
        try {
            localStorage.setItem(storageKey, JSON.stringify({ score: selectedScore, at: new Date().toISOString() }));
        } catch (e) {}
        document.getElementById("whLastCheckin").textContent = "Last check-in · Today";
        closeModal("whCheckin");
    });

    document.getElementById("whCheckin").addEventListener("click", function (event) {
        if (event.target.id === "whCheckin") closeModal("whCheckin");
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeModal("whModal");
            closeModal("whCheckin");
        }
    });

    var role = document.getElementById("whRole");
    try {
        var savedRole = localStorage.getItem("eca_wellness_role");
        if (savedRole) role.value = savedRole;
    } catch (e) {}
    role.addEventListener("change", function () {
        try { localStorage.setItem("eca_wellness_role", role.value); } catch (e) {}
    });

    loadCheckin();
})();
