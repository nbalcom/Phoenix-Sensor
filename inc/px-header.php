<?php 
if (!defined('ABSPATH')) exit; 

// Pull versioning from the main plugin file
$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/phoenix-sensor/phoenix-sensor.php');
$current_version = isset($plugin_data['Version']) ? $plugin_data['Version'] : '1.0.0';
?>

<div class="px-header-main">
    <div class="px-title-stack">
        <h1>PHOENIX SENSOR <span class="px-version-tag">v<?php echo esc_html($current_version); ?></span></h1>
        <p class="px-motto-top px-subtitle-fix">Apex Agent Report</p>
    </div>

    <div class="px-branding-right">
        <div class="px-branding-motto">
            <span class="px-motto-top">Rising From The Ashes</span>
            <span class="px-motto-bottom">Bird Brain Initiative</span>
        </div>
        <img src="../wp-content/plugins/phoenix-sensor/images/phoenix-sensor-emblem.webp" class="px-phoenix-logo" alt="Phoenix Emblem">
    </div>
</div>
