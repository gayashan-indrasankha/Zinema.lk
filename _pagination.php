<?php
/**
 * Pagination Component for Desktop/Tablet
 * Shows numbered page navigation
 * Only visible on screens >= 768px
 */

// Required variables:
// $current_page, $total_pages, $base_url (with existing query params)

if (!isset($current_page) || !isset($total_pages) || $total_pages <= 1) {
    return; // Don't show pagination if only 1 page
}

// Ensure variables are integers and current_page is at least 1
// Don't recalculate from $_GET, use the value passed from parent page
$current_page = max(1, (int)$current_page);
$total_pages = max(1, (int)$total_pages);

// Build query string preserving existing filters
$query_params = $_GET;
unset($query_params['page']); // Remove page param, we'll add it manually
$query_string = http_build_query($query_params);
$base_url = strtok($_SERVER["REQUEST_URI"], '?');
?>

<!-- Pagination Container (Desktop/Tablet Only) -->
<div class="pagination-wrapper">

    
    <!-- Page Info -->
    <div class="pagination-info">
        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
    </div>
    
    <!-- Pagination Controls -->
    <div class="pagination-controls">
        <?php
        // Previous Button
        if ($current_page > 1):
            $prev_page = $current_page - 1;
            if ($query_string) {
                $prev_url = $base_url . '?' . $query_string . '&page=' . $prev_page;
            } else {
                $prev_url = $base_url . '?page=' . $prev_page;
            }
        ?>
            <a href="<?php echo $prev_url; ?>" class="page-arrow">
                <i class="fas fa-chevron-left"></i>
            </a>
        <?php endif; ?>
        
        <?php
        // Calculate page range to show
        $range = 2; // Show 2 pages before and after current
        $start_page = max(1, $current_page - $range);
        $end_page = min($total_pages, $current_page + $range);
        
        // Always show first page
        if ($start_page > 1):
            if ($query_string) {
                $first_url = $base_url . '?' . $query_string . '&page=1';
            } else {
                $first_url = $base_url . '?page=1';
            }
        ?>
            <a href="<?php echo $first_url; ?>" class="page-btn">1</a>
            <?php if ($start_page > 2): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php
        // Show page numbers in range
        for ($i = $start_page; $i <= $end_page; $i++):
            // Build URL with page parameter
            if ($query_string) {
                $page_url = $base_url . '?' . $query_string . '&page=' . $i;
            } else {
                $page_url = $base_url . '?page=' . $i;
            }
            $active_class = ($i == $current_page) ? 'active' : '';
        ?>
            <a href="<?php echo $page_url; ?>" class="page-btn <?php echo $active_class; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <?php
        // Always show last page
        if ($end_page < $total_pages):
            if ($end_page < $total_pages - 1):
        ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
            <?php
            if ($query_string) {
                $last_url = $base_url . '?' . $query_string . '&page=' . $total_pages;
            } else {
                $last_url = $base_url . '?page=' . $total_pages;
            }
            ?>
            <a href="<?php echo $last_url; ?>" class="page-btn"><?php echo $total_pages; ?></a>
        <?php endif; ?>
        
        <?php
        // Next Button
        if ($current_page < $total_pages):
            $next_page = $current_page + 1;
            if ($query_string) {
                $next_url = $base_url . '?' . $query_string . '&page=' . $next_page;
            } else {
                $next_url = $base_url . '?page=' . $next_page;
            }
        ?>
            <a href="<?php echo $next_url; ?>" class="page-arrow">
                <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Scroll to top when pagination link is clicked (before navigation)
document.querySelectorAll('.page-btn, .page-arrow').forEach(btn => {
    btn.addEventListener('click', function(e) {
        // Scroll to top immediately (page will reload anyway)
        window.scrollTo(0, 0);
    });
});
</script>
