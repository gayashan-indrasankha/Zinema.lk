// ======================= SERIES DETAIL PAGE (FIXED - WORKING VERSION) =======================
(() => {
    const container = document.getElementById("seriesContainer");
    const playerDialog = document.getElementById("player");
    const modalTitle = document.getElementById("modalTitle");
    const videoPlayer = document.getElementById("videoPlayer"); // Changed from iframe to videoPlayer
    const closePlayerBtn = document.getElementById("closePlayer");

    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const seriesId = params.get("id");

    if (!seriesId) {
        container.innerHTML = "<p>❌ No series specified.</p>";
        return;
    }

    const renderSeriesDetails = (series) => {
        if (!series) {
            container.innerHTML = "<p>❌ Series not found.</p>";
            return;
        }

        document.title = `${series.title} — CineDrive`;

        const header = document.createElement("div");
        header.className = "series-header";
        header.innerHTML = `
            <img src="${encodeURI(series.cover_image)}" alt="${series.title}">
            <div class="series-info">
                <h2>${series.title}</h2>
                <p>${series.description}</p>
            </div>`;
        container.appendChild(header);

        if (!series.seasons || series.seasons.length === 0) {
            container.innerHTML += "<p style='margin-top:20px;'>No episodes found for this series yet.</p>";
            return;
        }

        const seasonsWrap = document.createElement("div");
        seasonsWrap.className = "seasons";
        seasonsWrap.innerHTML = `<div class="seasons-title" style="text-align:center; font-size: 1.5rem; margin-bottom: 20px;">Seasons and Episodes</div>`;
        
        series.seasons.forEach(season => {
            const seasonEl = document.createElement("div");
            seasonEl.className = "season";
            
            const episodesHTML = (season.episodes || []).map(ep => {
                return `
                <div class="episode" data-drive="${ep.driveId}" data-title="${ep.title}">
                    <img src="${encodeURI(ep.thumb)}" alt="${ep.title}">
                    <div class="ep-code">${ep.num}</div>
                    <div class="ep-title">${ep.title}</div>
                </div>`;
            }).join("");

            seasonEl.innerHTML = `
                <div class="season-head">
                    <div class="season-title">Season ${season.season}</div>
                </div>
                <div class="episodes">
                    ${episodesHTML}
                </div>`;
            seasonsWrap.appendChild(seasonEl);
        });
        container.appendChild(seasonsWrap);
    };

    const adLink = "https://www.effectivegatecpm.com/cts4pnwwvy?key=500118fcbf3c70ad9a4cb9b874f15fcf";

    // FIXED: Simple player function like main.js - using video tag
    const openPlayer = (title, videoUrl) => {
        if (!playerDialog || !videoPlayer || !modalTitle || !videoUrl) {
            console.error("Player elements missing or invalid video URL:", { playerDialog, videoPlayer, modalTitle, videoUrl });
            return;
        }

        console.log("Opening player with:", { title, videoUrl }); // Debug log

        // 1. Open ad in new tab
        window.open(adLink, '_blank');

        // 2. Open player
        modalTitle.textContent = title;
        videoPlayer.src = videoUrl; // Set video source directly
        playerDialog.showModal();
    };

    // FIXED: Simple close function
    const closePlayer = () => {
        if (!playerDialog || !videoPlayer) return;
        videoPlayer.pause();
        videoPlayer.src = "";
        playerDialog.close();
    };

    // Fetch series data
    fetch(`api/series.php?id=${seriesId}`)
        .then(response => response.json())
        .then(data => renderSeriesDetails(data))
        .catch(error => {
            console.error('Error fetching series details:', error);
            container.innerHTML = "<p>Failed to load series details.</p>";
        });

    // FIXED: Simple click listener
    container.addEventListener("click", (e) => {
        const episodeEl = e.target.closest(".episode");
        if (episodeEl && episodeEl.dataset.drive) {
            openPlayer(episodeEl.dataset.title, episodeEl.dataset.drive);
        }
    });

    // Close button listener
    if (closePlayerBtn) closePlayerBtn.addEventListener("click", closePlayer);
    
    // Close on backdrop click
    if (playerDialog) {
        playerDialog.addEventListener("click", (e) => {
            if (e.target === playerDialog) closePlayer();
        });
    }
})();