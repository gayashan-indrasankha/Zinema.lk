// ======================= COLLECTIONS LISTING PAGE (DYNAMIC) =======================
(() => {
    // DOM Elements
    const grid = document.getElementById("grid");
    const searchInput = document.getElementById("searchInput");

    if (!grid) return;

    let allCollections = [];
    let activeGenre = null;
    let searchQuery = "";

    // Function to create a collection card
    const createCollectionCard = (c) => {
        const el = document.createElement("div");
        el.className = "card";
        el.innerHTML = `
            <div class="thumb" style="background-image:url('${encodeURI(c.cover_image)}')"></div>
            <div class="meta">
                <div class="title">${c.title}</div>
                <div class="sub">${(c.genre || []).join(", ")}</div>
                <div class="desc" style="font-size:13px;color:var(--muted);">${c.description}</div>
            </div>`;
        
        // This is correct: it navigates to the detail page
        el.addEventListener("click", () => {
            window.location.href = `collection.html?id=${c.id}`;
        });
        
        return el;
    };

    // Function to render the collections grid
    const renderCollections = () => {
        grid.innerHTML = "";
        
        const filteredCollections = allCollections
            .filter(c => !activeGenre || (c.genre || []).includes(activeGenre))
            .filter(c => !searchQuery || c.title.toLowerCase().includes(searchQuery.toLowerCase()));

        if (filteredCollections.length === 0) {
            grid.innerHTML = `<p style='margin-top:15px; grid-column: 1 / -1;'>No collections found matching your criteria.</p>`;
            return;
        }

        filteredCollections.forEach(c => grid.appendChild(createCollectionCard(c)));
    };

    // Fetch all collections from the API
    fetch('api/collections.php')
        .then(response => response.json())
        .then(data => {
            allCollections = data;
            const params = new URLSearchParams(window.location.search);
            activeGenre = params.get("genre");
            renderCollections();
        })
        .catch(error => {
            console.error('Error fetching collections:', error);
            grid.innerHTML = "<p>Failed to load collections.</p>";
        });

    // Search input listener
    if (searchInput) {
        searchInput.addEventListener("input", e => {
            searchQuery = e.target.value;
            renderCollections();
        });
    }
})();