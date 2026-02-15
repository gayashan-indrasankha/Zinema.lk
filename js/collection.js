// ======================= COLLECTION DETAIL PAGE =======================
(() => {
    const container = document.getElementById("collectionContainer");
    const playerDialog = document.getElementById("player");
    const modalTitle = document.getElementById("modalTitle");
    const videoPlayer = document.getElementById("videoPlayer");
    const closePlayerBtn = document.getElementById("closePlayer");

    if (!container) return;

    const params = new URLSearchParams(window.location.search);
    const collectionId = params.get("id");

    if (!collectionId) {
        container.innerHTML = "<p>⚠️ No collection specified.</p>";
        return;
    }

    const renderCollectionDetails = (collection) => {
        if (!collection) {
            container.innerHTML = "<p>⚠️ Collection not found.</p>";
            return;
        }

        document.title = `${collection.title} – CineDrive`;

        const header = document.createElement("div");
        header.className = "series-header";
        header.innerHTML = `
            <img src="${encodeURI(collection.cover_image)}" alt="${collection.title}">
            <div class="series-info">
                <h2>${collection.title}</h2>
                <p>${collection.description}</p>
            </div>`;
        container.appendChild(header);

        if (!collection.movies || collection.movies.length === 0) {
            container.innerHTML += "<p style='margin-top:20px;'>No movies found in this collection yet.</p>";
            return;
        }

        const grid = document.createElement("div");
        grid.className = "grid";
        
        collection.movies.forEach((m) => {
            // Try multiple possible field names for the video URL
            const videoUrl = m.driveId || m.video_url || m.videoUrl || m.drive_id || m.url;
            
            // Skip movies without valid video URLs
            if (!videoUrl || videoUrl === "null" || videoUrl === "undefined") {
                console.warn(`Skipping movie "${m.title}" - no valid video URL`);
                return;
            }

            const card = document.createElement("div");
            card.className = "card";
            card.innerHTML = `
                <div class="thumb" style="background-image:url('${encodeURI(m.cover_image)}')">
                    <div class="play-overlay"><span>▶</span></div>
                </div>
                <div class="meta">
                    <div class="title">${m.title}</div>
                    <div class="sub">${m.release_date || 'Unknown'}</div>
                </div>`;
            
            card.addEventListener("click", () => {
                openPlayer(m.title, videoUrl);
            });
            
            grid.appendChild(card);
        });
        
        container.appendChild(grid);
    };
    
    const adLink = "https://www.effectivegatecpm.com/cts4pnwwvy?key=500118fcbf3c70ad9a4cb9b874f15fcf";

    const openPlayer = (title, videoUrl) => {
        if (!playerDialog || !videoPlayer || !modalTitle) {
            console.error("❌ Player elements missing!");
            return;
        }

        if (!videoUrl || videoUrl === "undefined" || videoUrl === "null") {
            console.error("❌ Invalid video URL for:", title);
            alert(`Cannot play "${title}". Video is not available.`);
            return;
        }

        // 1. Open ad in new tab
        window.open(adLink, '_blank');

        // 2. Set video title
        modalTitle.textContent = title;

        // 3. Load video
        videoPlayer.src = videoUrl;
        videoPlayer.load();

        // 4. Show modal
        playerDialog.showModal();

        // 5. Try to play (autoplay may be blocked)
        videoPlayer.play().catch(err => {
            console.log("Autoplay prevented:", err.message);
        });
    };

    const closePlayer = () => {
        if (!playerDialog || !videoPlayer) return;
        videoPlayer.pause();
        videoPlayer.src = "";
        videoPlayer.load();
        playerDialog.close();
    };

    // Fetch collection data
    fetch(`api/collections.php?id=${collectionId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            renderCollectionDetails(data);
        })
        .catch(error => {
            console.error('❌ Error fetching collection details:', error);
            container.innerHTML = "<p>Failed to load collection details. Please try again later.</p>";
        });

    if (closePlayerBtn) closePlayerBtn.addEventListener("click", closePlayer);
    if (playerDialog) {
        playerDialog.addEventListener("click", (e) => {
            if (e.target === playerDialog) closePlayer();
        });
    }
})();