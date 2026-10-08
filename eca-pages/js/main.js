(function () {
    if (document.querySelector('link[href*="css/theme.css"]')) {
        return;
    }
    var styleLink = document.querySelector('link[href*="css/style.css"]');
    var theme = document.createElement('link');
    theme.rel = 'stylesheet';
    theme.href = styleLink
        ? styleLink.href.replace(/style\.css(?:\?.*)?$/i, 'theme.css')
        : 'css/theme.css';
    document.head.appendChild(theme);
})();

(function ($) {
    "use strict";
    if (!$) return;

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    
    // Initiate the wowjs
    if (window.WOW && document.querySelector('.wow')) {
        new WOW().init();
    }


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top').addClass('shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('shadow-sm').css('top', '-100px');
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 300);
        return false;
    });


    // Facts counter
    if ($.fn.counterUp && $('[data-toggle="counter-up"]').length) {
        $('[data-toggle="counter-up"]').counterUp({
            delay: 10,
            time: 1200
        });
    }


    // Date and time picker
    if ($.fn.datetimepicker) {
        $('.date').datetimepicker({ format: 'L' });
        $('.time').datetimepicker({ format: 'LT' });
    }


    // Header carousel
    if ($.fn.owlCarousel && $(".header-carousel").length) {
        $(".header-carousel").owlCarousel({
        autoplay: false,
        animateOut: 'fadeOutLeft',
        items: 1,
        dots: true,
        loop: true,
        nav : true,
        navText : [
            '<i class="bi bi-chevron-left"></i>',
            '<i class="bi bi-chevron-right"></i>'
        ]
        });
    }


    // Testimonials carousel
    if ($.fn.owlCarousel && $(".testimonial-carousel").length) {
        $(".testimonial-carousel").owlCarousel({
        autoplay: false,
        smartSpeed: 1000,
        center: true,
        dots: false,
        loop: true,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0:{
                items:1
            },
            768:{
                items:2
            }
        }
        });
    }

    
})(window.jQuery);

(function () {
    var root = document.querySelector("[data-eca-landing]");
    if (!root) return;
    window.ecaModernHome = true;
    var slides = Array.prototype.slice.call(root.querySelectorAll(".eca-landing-slide"));
    var dots = Array.prototype.slice.call(root.querySelectorAll(".eca-landing-dots button"));
    var toggle = root.querySelector(".eca-landing-toggle");
    var nav = root.querySelector(".eca-landing-nav");
    var index = 0;

    function closeEcaDrops(except) {
        root.querySelectorAll(".eca-nav-drop, .eca-member-split").forEach(function (other) {
            if (other === except) return;
            other.classList.remove("is-open");
            var otherBtn = other.querySelector("[aria-expanded]");
            if (otherBtn) otherBtn.setAttribute("aria-expanded", "false");
        });
    }

    function go(next) {
        if (!slides.length) return;
        index = (next + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
            var active = i === index;
            slide.classList.toggle("is-active", active);
            slide.setAttribute("aria-hidden", active ? "false" : "true");
            slide.inert = !active;
        });
        dots.forEach(function (dot, i) {
            var active = i === index;
            dot.classList.toggle("is-active", active);
            dot.setAttribute("aria-selected", active ? "true" : "false");
            dot.setAttribute("tabindex", active ? "0" : "-1");
        });
        closeEcaDrops();
    }

    var prev = root.querySelector(".eca-landing-prev");
    var nextBtn = root.querySelector(".eca-landing-next");
    if (prev) prev.addEventListener("click", function () { go(index - 1); });
    if (nextBtn) nextBtn.addEventListener("click", function () { go(index + 1); });
    dots.forEach(function (dot, i) {
        dot.addEventListener("click", function () { go(i); });
        dot.addEventListener("keydown", function (event) {
            if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
            event.preventDefault();
            var target = i;
            if (event.key === "ArrowLeft") target = i - 1;
            if (event.key === "ArrowRight") target = i + 1;
            if (event.key === "Home") target = 0;
            if (event.key === "End") target = dots.length - 1;
            go(target);
            dots[index].focus();
        });
    });
    if (!root.dataset.ecaNavReady) {
        root.dataset.ecaNavReady = "1";
        if (toggle && nav) {
            toggle.addEventListener("click", function () {
                var open = nav.classList.toggle("is-open");
                toggle.setAttribute("aria-expanded", open ? "true" : "false");
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
                closeEcaDrops(drop);
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
                closeEcaDrops(drop);
            });
        });
        document.addEventListener("click", function (event) {
            if (event.target.closest(".eca-nav-drop, .eca-member-split")) return;
            closeEcaDrops();
        });
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") closeEcaDrops();
        });
    }
    go(index);
})();

