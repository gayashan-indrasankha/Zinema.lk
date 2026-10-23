// ======================= TV SERIES LISTING PAGE (FIXED - WORKING VERSION) =======================
(() => {
    const grid = document.getElementById("grid");
    const searchInput = document.getElementById("searchInput");
    
    if (!grid) return;
    
    let allSeries = [];
    let activeGenre = null;
    let searchQuery = "";

    const createSeriesCard = (series) => {
        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <div class="thumb" style="background-image:url('${encodeURI(series.cover_image)}')"></div>
            <div class="meta">
                <div class="title">${series.title}</div>
                <div class="sub">${series.release_date}</div>
            </div>`;
        
        card.addEventListener("click", () => {
            window.location.href = `series.html?id=${series.id}`;
        });
        
        return card;
    };

    const renderSeries = () => {
        grid.innerHTML = "";
        const filteredSeries = allSeries
            .filter(s => !activeGenre || (s.genre || []).includes(activeGenre))
            .filter(s => !searchQuery || s.title.toLowerCase().includes(searchQuery.toLowerCase()));

        if (filteredSeries.length === 0) {
            grid.innerHTML = `<p style='margin-top:15px; grid-column: 1 / -1;'>No TV series found matching your criteria.</p>`;
            return;
        }
        
        filteredSeries.forEach(series => grid.appendChild(createSeriesCard(series)));
    };

    fetch('api/series.php')
        .then(response => response.json())
        .then(data => {
            allSeries = data;
            const params = new URLSearchParams(window.location.search);
            activeGenre = params.get("genre");
            renderSeries();
        })
        .catch(error => {
            console.error('Error fetching series:', error);
            grid.innerHTML = "<p style='margin-top:15px;'>Failed to load TV series.</p>";
        });

    if (searchInput) {
        searchInput.addEventListener("input", e => {
            searchQuery = e.target.value.toLowerCase();
            renderSeries();
        });
    }
})();