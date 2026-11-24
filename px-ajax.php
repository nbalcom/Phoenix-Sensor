<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_px_purge_logs', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    check_ajax_referer('px_sensor_nonce', 'nonce');
    global $wpdb;
    $table_name = $wpdb->prefix . 'phoenix_sensor_logs';
    $wpdb->query("TRUNCATE TABLE $table_name");
    wp_send_json_success();
});