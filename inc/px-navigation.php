<?php if (!defined('ABSPATH')) exit; ?>

<div class="px-nav-container">
    <button class="px-nav-btn active" onclick="pxVibe('all', this)">ðŸš€ All Nodes</button>
    <button class="px-nav-btn" onclick="pxVibe('ai', this)">ðŸ¤– AI Agents</button>
    <button class="px-nav-btn" onclick="pxVibe('user', this)">ðŸ§  End Users</button>
    <button class="px-nav-btn" onclick="pxVibe('ghost', this)">ðŸ‘» Ghost Hits</button>
    
    <div style="flex-grow: 1;"></div>

<a href="<?php echo site_url('/data-bridge-alpha.php?v=' . time()); ?>" class="px-csv-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    CSV DOWNLOAD REPORT | 🕙 <?php echo date('g:i A'); ?>
</a>
</div>

<form id="px-csv-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:none;">
    <input type="hidden" name="action" value="px_export_csv">
    <?php wp_nonce_field('px_csv_export_nonce', 'px_csv_nonce'); ?>
</form>

<script>
function pxVibe(vibeType, btn) {
    document.querySelectorAll('.px-nav-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    const tableBody = jQuery('.px-tbl-wrap tbody'); 
    if (tableBody.length) {
        tableBody.css('opacity', '0.4');
        jQuery.post(ajaxurl, { action: 'px_filter_table', vibe: vibeType }, function(response) {
            tableBody.html(response).css('opacity', '1');
        });
    }
}
function pxDownloadCSV() {
    document.getElementById('px-csv-form').submit();
}
</script>
