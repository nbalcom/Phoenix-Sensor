<?php
/**
 * PHOENIX SENSOR: INTELLIGENCE TABLE
 * Version: 2.4.1 - Pillar Node & Agent Mapping
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'px_sensor_logs';

// Fetch the latest 50 logs
$logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");

// Define Asset Path for Logos
$img_path = plugin_dir_url(dirname(__FILE__)) . 'images/';
?>

<div class="px-table-container">
    <div class="px-table-header">
        <h3><span class="dashicons dashicons-database"></span> INFERENCE INTELLIGENCE</h3>
        <p>Real-time traffic monitoring & machine-handshake verification.</p>
    </div>

    <table class="wp-list-table widefat fixed striped px-intel-table">
        <thead>
            <tr>
                <th class="px-col-time">TIMESTAMP</th>
                <th class="px-col-agent">AGENT / VISITOR</th>
                <th class="px-col-node">PILLAR NODE</th>
                <th class="px-col-url">TARGET URL</th>
                <th class="px-col-status">HANDSHAKE</th>
                <th class="px-col-action">ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs) : foreach ($logs as $log) : 
                // Determine Logo based on User Agent
                $logo = 'phoenix-sensor-emblem.webp';
                $ua = $log->user_agent;

                if (strpos($ua, 'ChatGPT') !== false) { $logo = 'chatgpt_logo.webp'; }
                elseif (strpos($ua, 'Claude') !== false || strpos($ua, 'Anthropic') !== false) { $logo = 'claude_logo.webp'; }
                elseif (strpos($ua, 'Googlebot') !== false || strpos($ua, 'Gemini') !== false) { $logo = 'gemini_logo.webp'; }
                elseif (strpos($ua, 'Perplexity') !== false) { $logo = 'perplexity_logo.webp'; }
                elseif (strpos($ua, 'AppleBot') !== false) { $logo = 'apple-intelligence_logo.webp'; }
                elseif (strpos($ua, 'Meta') !== false) { $logo = 'meta_logo.webp'; }
            ?>
                <tr>
                    <td class="px-col-time"><?php echo date('m.d.y H:i:s', strtotime($log->timestamp)); ?></td>
                    <td class="px-col-agent">
                        <div class="px-agent-identity">
                            <img src="<?php echo esc_url($img_path . $logo); ?>" class="px-agent-icon" title="<?php echo esc_attr($ua); ?>">
                            <span><?php echo esc_html($log->visitor_type); ?></span>
                        </div>
                    </td>
                    <td class="px-col-node">
                        <span class="px-badge-node"><?php echo esc_html($log->Pillar_Node); ?></span>
                    </td>
                    <td class="px-col-url">
                        <a href="<?php echo esc_url($log->url); ?>" target="_blank" class="px-link-target">
                            <?php echo esc_html(parse_url($log->url, PHP_URL_PATH)); ?>
                        </a>
                    </td>
                    <td class="px-col-status">
                        <span class="px-status-<?php echo strtolower($log->handshake); ?>">
                            <?php echo esc_html($log->handshake); ?>
                        </span>
                    </td>
                    <td class="px-col-action">
                        <button class="px-btn-purge" data-id="<?php echo $log->id; ?>"><span class="dashicons dashicons-trash"></span></button>
                    </td>
                </tr>
            <?php endforeach; else : ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:40px;">[ NO INTEL TRAPPED - SENSOR ACTIVE ]</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .px-agent-icon { width: 24px; height: 24px; border-radius: 4px; margin-right: 10px; vertical-align: middle; }
    .px-badge-node { background: rgba(40, 102, 231, 0.1); color: #2866e7; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 11px; border: 1px solid rgba(40, 102, 231, 0.2); }
    .px-status-pending { color: #f39c12; font-weight: bold; }
    .px-status-verified { color: #27ae60; font-weight: bold; }
    .px-agent-identity { display: flex; align-items: center; }
</style>
