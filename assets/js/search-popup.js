// Resolve rewritten routes at the configured site root; render untrusted values as text.
const popupBase = document.getElementById('search-popup-overlay').dataset.baseUrl;
let popupSearchTimeout;
let popupSearchVersion = 0;
function handlePopupSearch(query) {
    clearTimeout(popupSearchTimeout);
    const version = ++popupSearchVersion;
    const results = document.getElementById('search-popup-results');
    results.replaceChildren();
    if (query.length < 2) return;
    if (query.length > 200) { results.textContent = 'Search is limited to 200 characters.'; return; }
    results.textContent = 'Searching...';
    popupSearchTimeout = setTimeout(async () => {
        const path = window.location.pathname;
        const type = /(?:tv-series|series-details|\/series\/)/.test(path) ? 'series' :
            /(?:collections|collection-details|\/collection\/)/.test(path) ? 'collections' : 'movies';
        try {
            const response = await fetch(new URL(`api/live_search.php?q=${encodeURIComponent(query)}&type=${type}`, popupBase));
            if (!response.ok) throw new Error('Search unavailable');
            const data = await response.json();
            if (version !== popupSearchVersion) return;
            results.replaceChildren();
            if (!Array.isArray(data)) throw new Error('Invalid search response');
            if (!data.length) { results.textContent = `No results found for "${query}"`; return; }
            const grid = document.createElement('div');grid.className = 'search-results-grid';
            for (const item of data) {
                const id = Number(item.id);
                if (!Number.isSafeInteger(id) || id < 1) continue;
                const prefix = type === 'series' ? 'series' : type === 'collections' ? 'collection' : 'movie';
                const card = document.createElement('a');card.className = 'search-result-card';
                card.href = new URL(`${prefix}/${id}/watch`, popupBase).href;
                card.addEventListener('click', closeSearchPopup);
                const image = document.createElement('img');image.alt = String(item.title || '');
                const source = new URL(String(item.cover_image || ''), popupBase);
                if (['http:', 'https:'].includes(source.protocol)) image.src = source.href;
                const info = document.createElement('div');info.className = 'search-result-info';
                const title = document.createElement('div');title.className = 'search-result-title';title.textContent = String(item.title || '');
                const year = document.createElement('div');year.className = 'search-result-year';
                year.textContent = /^\d{4}/.test(String(item.release_date || '')) ? String(item.release_date).slice(0,4) : 'N/A';
                info.append(title,year);card.append(image,info);grid.append(card);
            }
            results.append(grid);
        } catch {
            if (version === popupSearchVersion) results.textContent = 'Search failed. Please try again.';
        }
    },300);
}
