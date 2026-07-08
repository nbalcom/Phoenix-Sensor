<?php
if (!defined('ABSPATH')) exit;

/**
 * 1. THE MESSENGER (SR3 Timeline Data Puller)
 */
add_action('wp_ajax_px_get_stats', 'px_get_stats_handler');
function px_get_stats_handler() {
    global $wpdb;
    $days = isset($_POST['days']) ? intval($_POST['days']) : 2;
    $table = $wpdb->prefix . 'ao_ai_agent_logs';

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT agent_name, COUNT(*) as hits FROM $table 
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) 
        GROUP BY agent_name ORDER BY hits DESC LIMIT 6
    ", $days));

    if (!empty($results)) {
        foreach ($results as $row) {
            $id = get_px_agent_identity($row->agent_name);
            echo '<div class="px-agent-row">';
                echo '<div>' . $id['html'];
                echo '<span style="font-weight:600; color:#fff; margin-left:12px;">' . esc_html($id['name']) . '</span></div>';
                echo '<span class="px-agent-hits">' . (int)$row->hits . '</span>';
            echo '</div>';
        }
    } else {
        echo '<p style="color:#64748b; font-size:11px; padding:10px;">No intel recorded.</p>';
    }
    wp_die(); 
}

/**
 * 2. TABLE FILTER HANDLER
 */
add_action('wp_ajax_px_filter_table', 'px_filter_table_handler');
function px_filter_table_handler() {
    global $wpdb;
    $vibe = isset($_POST['vibe']) ? sanitize_text_field($_POST['vibe']) : 'all';
    $table = $wpdb->prefix . 'ao_ai_agent_logs';
    
    if ($vibe === 'ai') {
        $query = "SELECT * FROM $table WHERE agent_name NOT LIKE '%Ghost%' AND agent_name NOT LIKE '%End User%'";
    } elseif ($vibe === 'user') {
        $query = "SELECT * FROM $table WHERE agent_name LIKE '%End User%'";
    } elseif ($vibe === 'ghost') {
        $query = "SELECT * FROM $table WHERE agent_name LIKE '%Ghost%' OR agent_name LIKE '%Human/Other%'";
    } else {
        $query = "SELECT * FROM $table";
    }
    
    $results = $wpdb->get_results($query . " ORDER BY timestamp DESC LIMIT 50");

    if ($results) {
        foreach ($results as $row) {
            $id = get_px_agent_identity($row->agent_name);
            echo '<tr>';
            echo '<td>' . $id['html'] . ' ' . esc_html($id['name']) . '</td>';
            echo '<td>' . esc_html($row->ip_address) . '</td>';
            echo '<td>' . esc_html($row->timestamp) . '</td>';
            echo '</tr>';
        }
    }
    wp_die();
}

/**
 * 3. CSV EXPORT HANDLER
 */
add_action('admin_post_px_export_csv', 'px_export_csv_handler');
function px_export_csv_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('px_csv_export_nonce', 'px_csv_nonce');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=phoenix-intel-report.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Agent Name', 'IP Address', 'Timestamp'));
    global $wpdb; $table = $wpdb->prefix . 'ao_ai_agent_logs';
    $rows = $wpdb->get_results("SELECT agent_name, ip_address, timestamp FROM $table ORDER BY timestamp DESC LIMIT 500", ARRAY_A);
    foreach ($rows as $row) fputcsv($output, $row);
    fclose($output); exit;
}
