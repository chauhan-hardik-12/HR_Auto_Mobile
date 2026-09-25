// Navigation menu toggle and page interactions
document.addEventListener("DOMContentLoaded", function () {
    const menu = document.querySelector(".menu-icon");
    const navbar = document.querySelector(".menu");

    if (menu && navbar) {
        menu.addEventListener("click", () => {
            menu.classList.toggle("move");
            navbar.classList.toggle("open-menu");
        });

        // Close menu on page scroll
        window.addEventListener("scroll", () => {
            menu.classList.remove("move");
            navbar.classList.remove("open-menu");
        });
    }

    // Date inputs (if present on the page)
    const startDate = document.getElementById("start-date");
    const returnDate = document.getElementById("return-date");

    if (startDate) {
        startDate.value = new Date().toISOString().split("T")[0];
    }
    if (returnDate) {
        returnDate.value = new Date(Date.now() + 7 * 86400000).toISOString().split("T")[0];
    }

    // Scroll reveal animation (only if ScrollReveal library is loaded)
    if (typeof ScrollReveal !== "undefined") {
        try {
            const animate = ScrollReveal({
                origin: "top",
                distance: "60px",
                duration: 2500,
                delay: 400
            });

            animate.reveal(".menu, .heading");
            animate.reveal(".home-img img", { origin: "right" });
            animate.reveal(".input-form", { origin: "bottom" });
            animate.reveal(".trend-box, .rental-box, .team-box, .t-box, .newslatter", { interval: 100 });
        } catch (e) {
            console.warn("ScrollReveal initialization skipped:", e);
        }
    }
});
