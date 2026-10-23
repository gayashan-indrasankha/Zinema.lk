// =================== Admin Utilities ===================
document.addEventListener("DOMContentLoaded", () => {

  // Genre toggle buttons
  document.querySelectorAll(".genre-buttons button").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.classList.toggle("active");
      const checkbox = document.getElementById(btn.dataset.target);
      if (checkbox) checkbox.checked = btn.classList.contains("active");
    });
  });

  // Live image preview
  const coverInput = document.querySelector('input[type="file"][name="cover"]');
  const previewImg = document.getElementById("coverPreview");
  if (coverInput && previewImg) {
    coverInput.addEventListener("change", e => {
      const file = e.target.files[0];
      if (file) previewImg.src = URL.createObjectURL(file);
    });
  }

  // Auto-hide alerts
  setTimeout(() => {
    document.querySelectorAll(".alert").forEach(a => a.remove());
  }, 4000);
});

document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector('input[name="cover"]');
  const preview = document.getElementById("coverPreview");
  if (input && preview) {
    input.addEventListener("change", e => {
      const file = e.target.files[0];
      if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
      }
    });
  }
});

// =================== Content Search Autocomplete (for Manage Shots) ===================
// Note: This uses vanilla JavaScript instead of jQuery UI for better compatibility
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("content-search");
  const linkedContentId = document.getElementById("linked-content-id");
  const searchResults = document.getElementById("search-results");
  const selectedContent = document.getElementById("selected-content");
  const selectedTitle = document.getElementById("selected-title");

  if (!searchInput) return; // Only run on pages with content search

  let searchTimeout;

  // Search as user types
  searchInput.addEventListener("input", function() {
    const query = this.value.trim();
    const contentType = document.querySelector('input[name="content_type"]:checked');
    
    if (!contentType) return;
    
    const type = contentType.value;

    // Clear previous timeout
    clearTimeout(searchTimeout);

    // Hide results if query is too short
    if (query.length < 2) {
      searchResults.style.display = "none";
      return;
    }

    // Debounce search (wait 300ms after user stops typing)
    searchTimeout = setTimeout(() => {
      // Fetch search results from API
      fetch(`../api/search_content.php?q=${encodeURIComponent(query)}&type=${type}`)
        .then(response => response.json())
        .then(data => {
          if (data.status === "success" && data.results.length > 0) {
            displaySearchResults(data.results);
          } else {
            searchResults.innerHTML = '<div style="padding: 10px; color: #999;">No results found</div>';
            searchResults.style.display = "block";
          }
        })
        .catch(error => {
          console.error("Search error:", error);
          searchResults.innerHTML = '<div style="padding: 10px; color: #f44;">Error loading results</div>';
          searchResults.style.display = "block";
        });
    }, 300);
  });

  // Display search results
  function displaySearchResults(results) {
    searchResults.innerHTML = "";
    
    results.forEach(item => {
      const div = document.createElement("div");
      div.className = "search-result-item";
      div.textContent = item.title + (item.release_year ? ` (${item.release_year})` : "");
      
      // Handle click to select item
      div.addEventListener("click", () => {
        selectContent(item.id, item.title);
      });
      
      searchResults.appendChild(div);
    });
    
    searchResults.style.display = "block";
  }

  // Select content and fill hidden input
  function selectContent(id, title) {
    linkedContentId.value = id;
    selectedTitle.textContent = title;
    selectedContent.style.display = "block";
    searchInput.value = "";
    searchResults.style.display = "none";
  }

  // Clear search when content type changes
  const radioButtons = document.querySelectorAll('input[name="content_type"]');
  radioButtons.forEach(radio => {
    radio.addEventListener("change", () => {
      searchInput.value = "";
      linkedContentId.value = "";
      selectedContent.style.display = "none";
      searchResults.style.display = "none";
    });
  });

  // Close search results when clicking outside
  document.addEventListener("click", (e) => {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = "none";
    }
  });
});

