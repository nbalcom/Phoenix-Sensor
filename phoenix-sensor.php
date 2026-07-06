<?php
/**
 * Plugin Name: Phoenix Sensor
 * Version: 2.3.6 (V14 Alpha-Bridge Enabled)
 * Description: Modular AEO Intelligence Platform.
 */

if (!defined('ABSPATH')) exit;

// 1. GLOBAL BOOTSTRAP
require_once plugin_dir_path(__FILE__) . 'inc/px-core.php'; 
require_once plugin_dir_path(__FILE__) . 'inc/px-ajax.php'; 

// 2. REGISTER MENU
add_action('admin_menu', function() {
    add_menu_page('Phoenix Sensor', 'Phoenix Sensor', 'manage_options', 'phoenix-sensor', 'px_master_render', 'dashicons-visibility', 2);
});

/**
 * 3. THE ALPHA BRIDGE REDIRECT
 * Forces the plugin to use the physical V14 bridge file
 */
add_action('admin_post_px_v14_direct_export', function() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    // Redirect to the physical bridge file with a cache-buster
    wp_redirect(site_url('/data-bridge-alpha.php?v=' . time()));
    exit;
});

// 4. THE RENDER ENGINE
function px_master_render() {
    include plugin_dir_path(__FILE__) . 'inc/px-styles.php';
    
    echo '<div class="wrap px-vibe-wrap">';
        // We pass the new direct URL to the header/nav components
        $v14_url = admin_url('admin-post.php?action=px_v14_direct_export');
        
        include plugin_dir_path(__FILE__) . 'inc/px-header.php';
        include plugin_dir_path(__FILE__) . 'inc/px-sit-rep.php';
        include plugin_dir_path(__FILE__) . 'inc/px-navigation.php';
        include plugin_dir_path(__FILE__) . 'inc/px-table.php';
    echo '</div>';
}
