// ==================== Navbar & Genre Menu Controller ====================
(() => {
    document.addEventListener("DOMContentLoaded", () => {
        const genresBtn = document.getElementById("genresBtn");
        const genresMenu = document.getElementById("genresMenu");

        if (genresBtn && genresMenu) {
            genresBtn.addEventListener("click", (e) => {
                e.stopPropagation();
                const isOpen = genresMenu.getAttribute("data-open") === "true";
                genresMenu.setAttribute("data-open", String(!isOpen));
                genresBtn.setAttribute("aria-expanded", String(!isOpen));
                genresBtn.classList.toggle("active", !isOpen);
            });

            document.addEventListener("click", () => {
                genresMenu.setAttribute("data-open", "false");
                genresBtn.setAttribute("aria-expanded", "false");
                genresBtn.classList.remove("active");
            });

            genresMenu.querySelectorAll("button[data-genre]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const genre = encodeURIComponent(btn.dataset.genre); // Encode genre for URL
                    const file = (location.pathname.split("/").pop() || "").toLowerCase();
                    
                    if (file.includes("collections")) {
                        location.href = `collections.html?genre=${genre}`;
                    } else if (file.includes("tv-series") || file.includes("series")) {
                        location.href = `tv-series.html?genre=${genre}`;
                    } else {
                        location.href = `index.html?genre=${genre}`;
                    }
                });
            });
        }

        // Set current year in footer
        const yearSpan = document.getElementById("year");
        if (yearSpan) yearSpan.textContent = new Date().getFullYear();
    });
})();