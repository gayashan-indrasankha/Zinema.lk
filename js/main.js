// ======================= HOME PAGE SCRIPT (FIXED) =======================
(() => {
    const grid = document.getElementById("grid");
    const searchInput = document.getElementById("searchInput");
    const playerDialog = document.getElementById("player");
    const modalTitle = document.getElementById("modalTitle");
    const videoPlayer = document.getElementById("videoPlayer"); // Changed from iframe
    const closePlayerBtn = document.getElementById("closePlayer");

    if (!grid) {
        console.error("Grid element not found!");
        return;
    }

    let allMovies = [];
    let activeGenre = null;
    let searchQuery = "";

    const createMovieCard = (movie) => {
        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <div class="thumb" style="background-image:url('${encodeURI(movie.cover_image)}')">
                <div class="play-overlay"><span>▶</span></div>
            </div>
            <div class="meta">
                <div class="title">${movie.title}</div>
                <div class="sub">${movie.release_date}</div>
            </div>`;
        
        card.addEventListener("click", () => openPlayer(movie.title, movie.video_url));
        return card;
    };

    const renderMovies = () => {
        grid.innerHTML = "";
        const filteredMovies = allMovies
            .filter(m => !activeGenre || (m.genre || []).includes(activeGenre))
            .filter(m => !searchQuery || m.title.toLowerCase().includes(searchQuery.toLowerCase()));

        if (filteredMovies.length === 0) {
            grid.innerHTML = `<p style='margin-top:15px; grid-column: 1 / -1;'>No movies found matching your criteria.</p>`;
            return;
        }
        filteredMovies.forEach(movie => grid.appendChild(createMovieCard(movie)));
    };

    const adLink = "https://www.effectivegatecpm.com/cts4pnwwvy?key=500118fcbf3c70ad9a4cb9b874f15fcf";

    const openPlayer = (title, videoUrl) => {
        if (!playerDialog || !videoPlayer || !modalTitle || !videoUrl) {
            console.error("Player elements missing or invalid video URL:", { 
                playerDialog, 
                videoPlayer, 
                modalTitle, 
                videoUrl 
            });
            alert("Cannot open video player. Please refresh the page.");
            return;
        }
    
        console.log("Opening player with:", { title, videoUrl });
        
        // 1. Open ad in new tab
        window.open(adLink, '_blank');
    
        // 2. Set video title
        modalTitle.textContent = title;
        
        // 3. Load video
        videoPlayer.src = videoUrl;
        videoPlayer.load(); // Important: Force reload
        
        // 4. Show modal
        playerDialog.showModal();
        
        // 5. Try to play (optional - some browsers block autoplay)
        videoPlayer.play().catch(err => {
            console.log("Autoplay prevented:", err);
            // This is normal - user can click play manually
        });
    };

    const closePlayer = () => {
        if (!playerDialog || !videoPlayer) return;
        
        // Stop video and clear source
        videoPlayer.pause();
        videoPlayer.src = "";
        videoPlayer.load();
        
        // Close modal
        playerDialog.close();
    };
    
    console.log("Fetching movies from API...");
    
    fetch('api/movies.php')
        .then(response => {
            console.log("Response status:", response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text();
        })
        .then(text => {
            console.log("Raw API response (first 200 chars):", text.substring(0, 200));
            
            try {
                const data = JSON.parse(text);
                console.log("Parsed movies data:", data);
                
                if (!Array.isArray(data)) {
                    throw new Error("API response is not an array");
                }
                
                allMovies = data;
                const params = new URLSearchParams(window.location.search);
                activeGenre = params.get("genre");
                renderMovies();
            } catch (e) {
                console.error("JSON parse error:", e);
                grid.innerHTML = `<p style='margin-top:15px; grid-column: 1 / -1; color: red;'>Error parsing movie data: ${e.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            grid.innerHTML = `
                <p style='margin-top:15px; grid-column: 1 / -1; color: red;'>
                    Failed to load movies. Error: ${error.message}
                    <br><small>Check browser console (F12) for details</small>
                </p>`;
        });

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener("input", e => {
            searchQuery = e.target.value;
            renderMovies();
        });
    }
    
    if (closePlayerBtn) {
        closePlayerBtn.addEventListener("click", closePlayer);
    }
    
    if (playerDialog) {
        playerDialog.addEventListener("click", (e) => {
            if (e.target === playerDialog) {
                closePlayer();
            }
        });
    }
    
    console.log("✅ Movie page initialized");
})();