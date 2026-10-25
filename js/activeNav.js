// ==================== Active Navigation Highlighter (scoped) ====================
(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const currentFile = (location.pathname.split("/").pop() || "index.html").toLowerCase();

    const map = {
      "": "index.html",
      "index.html": "index.html",
      "tv-series.html": "tv-series.html",
      "series.html": "tv-series.html",
      "collections.html": "collections.html",
      "collection.html": "collections.html"
    };
    const target = map[currentFile] || "index.html";

    document.querySelectorAll(".menu a").forEach(link => {
      const href = (link.getAttribute("href") || "").toLowerCase();
      link.classList.toggle("active", href === target);
    });
  });
})();
// ==================== END Active Navigation Highlighter ====================