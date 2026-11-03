<?php
// Shared poster card for movies and series (styled by css/media-card.css).
// Every card is the same 2:3 poster frame with the title overlaid at the bottom, so a row of
// cards always lines up no matter the poster's original size or the title's length.

// Poster URL from a stored cover path: legacy full URLs on the old domain become local uploads,
// relative paths are made absolute so they work on clean URLs like /movie/90/title.
function media_card_poster($cover) {
    $cover = trim((string)$cover);
    if ($cover === '') return '';
    if (strpos($cover, 'sinhalamovies.web.lk/uploads/') !== false) {
        $cover = 'uploads/' . basename($cover);
    }
    return preg_match('~^https?://~', $cover) ? $cover : BASE_URL . '/' . ltrim($cover, '/');
}

// $card: href, title, cover, rating (optional). The title sits inside the poster at the bottom.
function render_media_card(array $card) {
    $title = htmlspecialchars($card['title'] ?? '');
    $poster = media_card_poster($card['cover'] ?? '');
    $rating = isset($card['rating']) && (float)$card['rating'] > 0 ? number_format((float)$card['rating'], 1) : '';
    ?>
    <a href="<?php echo htmlspecialchars($card['href']); ?>" class="media-card" title="<?php echo $title; ?>">
        <div class="media-card__poster">
            <?php if ($poster !== ''): ?>
                <img src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo $title; ?>" loading="lazy" decoding="async"
                     onerror="this.onerror=null; this.remove();">
            <?php endif; ?>
            <span class="media-card__fallback" aria-hidden="true"><i class="fas fa-film"></i></span>
            <?php if ($rating !== ''): ?>
                <span class="media-card__rating"><i class="fas fa-star"></i> <?php echo $rating; ?></span>
            <?php endif; ?>
            <span class="media-card__play" aria-hidden="true"><i class="fas fa-play"></i></span>
            <h3 class="media-card__title"><?php echo $title; ?></h3>
        </div>
    </a>
    <?php
}
