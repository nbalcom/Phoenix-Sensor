<?php
/**
 * PHOENIX SENSOR CORE
 * Version: 1.2 - Node Hardening & Handshake Verification
 * Logic: Handles database initialization and traffic logging.
 */

if (!defined('ABSPATH')) exit;

global $px_db_version;
$px_db_version = '1.2'; // Force schema update to include Pillar_Node

/**
 * 1. DATABASE INSTALLER
 * Creates or updates the px_sensor_logs table.
 */
function px_sensor_db_install() {
    global $wpdb;
    global $px_db_version;

    $table_name = $wpdb->prefix . 'px_sensor_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        ip varchar(100) DEFAULT '' NOT NULL,
        url varchar(255) DEFAULT '' NOT NULL,
        user_agent text NOT NULL,
        referrer varchar(255) DEFAULT '' NOT NULL,
        visitor_type varchar(50) DEFAULT '' NOT NULL,
        action varchar(50) DEFAULT '' NOT NULL,
        Pillar_Node varchar(255) DEFAULT 'Natebal_Root' NOT NULL,
        handshake varchar(50) DEFAULT 'Pending' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('px_db_version', $px_db_version);
}

// Check for updates on every admin load
add_action('plugins_loaded', function() {
    global $px_db_version;
    if (get_site_option('px_db_version') != $px_db_version) {
        px_sensor_db_install();
    }
});

/**
 * 2. LOGGING ENGINE
 * Records the visit data. Triggered by functions.php
 */
function px_sensor_log_visit() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'px_sensor_logs';

    $ua  = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    $ip  = $_SERVER['REMOTE_ADDR'];
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct';

    // Identify Visitor Type
    $visitor_type = 'End User';
    $bots = ['ChatGPT', 'Claude', 'Googlebot', 'Bingbot', 'Anthropic', 'Perplexity', 'AppleBot', 'Gemini'];
    foreach ($bots as $bot) {
        if (stripos($ua, $bot) !== false) {
            $visitor_type = 'AI Agent';
            break;
        }
    }

    // Determine Pillar Node based on URL structure
    $pillar_node = 'Natebal_Root';
    if (strpos($url, '/aeo/') !== false) { 
        $pillar_node = 'AEO_Node'; 
    } elseif (strpos($url, '/project-phoenix/') !== false) { 
        $pillar_node = 'Phoenix_Node'; 
    } elseif (strpos($url, '/ux/') !== false) { 
        $pillar_node = 'UX_Node'; 
    }

    // Insert Data into Hardened Schema
    $wpdb->insert(
        $table_name,
        array(
            'timestamp'    => current_time('mysql'),
            'ip'           => $ip,
            'url'          => $url,
            'user_agent'   => $ua,
            'referrer'     => $ref,
            'visitor_type' => $visitor_type,
            'action'       => 'discovery',
            'Pillar_Node'  => $pillar_node,
            'handshake'    => 'Pending'
        )
    );
}
