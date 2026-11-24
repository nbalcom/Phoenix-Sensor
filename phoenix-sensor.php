<?php
/**
 * Plugin Name: Phoenix Sensor
 * Version: 2.5.0 (V15 Telemetry & Actionable AEO Suite)
 * Description: Modular AEO Intelligence Platform with 30-Day Rollups, HTTP/TTFB Telemetry, Citation Gap Alerts, and Handshake Verification.
 */

if (!defined('ABSPATH')) exit;

// 1. GLOBAL BOOTSTRAP
require_once plugin_dir_path(__FILE__) . 'inc/px-core.php';
require_once plugin_dir_path(__FILE__) . 'inc/px-ajax.php';

// 2. CLEAN UP CRON ON DEACTIVATION
register_deactivation_hook(__FILE__, function() {
    $timestamp = wp_next_scheduled('px_daily_maintenance_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'px_daily_maintenance_event');
    }
});

// 3. REGISTER MENU
add_action('admin_menu', function() {
    add_menu_page('Phoenix Sensor', 'Phoenix Sensor', 'manage_options', 'phoenix-sensor', 'px_master_render', 'dashicons-visibility', 2);
});

/**
 * 4. THE ALPHA BRIDGE REDIRECT
 * Forces the plugin to use the physical V15 bridge file
 */
add_action('admin_post_px_v14_direct_export', function() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    wp_redirect(site_url('/data-bridge-alpha.php?v=' . time()));
    exit;
});

// 5. THE RENDER ENGINE
function px_master_render() {
    include plugin_dir_path(__FILE__) . 'inc/px-styles.php';

    echo '<div class="wrap px-vibe-wrap">';
        $v14_url = admin_url('admin-post.php?action=px_v14_direct_export');

        include plugin_dir_path(__FILE__) . 'inc/px-header.php';
        include plugin_dir_path(__FILE__) . 'inc/px-sit-rep.php';
        include plugin_dir_path(__FILE__) . 'inc/px-navigation.php';
        include plugin_dir_path(__FILE__) . 'inc/px-table.php';
    echo '</div>';
}