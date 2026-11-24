<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'phoenix_sensor_logs';
$entries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 200", ARRAY_A);
?>
<div class="px-table-wrap">
    <table class="px-table" id="px-telemetry-table">
        <thead>
            <tr>
                <th>Timestamp (EST)</th>
                <th>Visitor Type</th>
                <th>Target URL</th>
                <th>Pillar Node</th>
                <th>Action / Intent</th>
                <th>Status / Speed</th>
                <th>Source IP</th>
                <th>Handshake</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 32px; color: #94a3b8;">
                        No telemetry captured yet. Send a test curl request or wait for crawler hits.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($entries as $row):
                    $date = new DateTime($row['timestamp'], new DateTimeZone('UTC'));
                    $date->setTimezone(new DateTimeZone('America/New_York'));

                    $badge_class = 'px-badge-human';
                    if (strpos($row['visitor_type'], 'AI Bot') !== false) {
                        $badge_class = 'px-badge-ai';
                    } elseif (strpos($row['visitor_type'], 'Search') !== false) {
                        $badge_class = 'px-badge-search';
                    }

                    $action_style = '';
                    if ($row['action_type'] === 'Live Citation Fetch' || $row['action_type'] === 'AI Referral Click') {
                        $action_style = 'color: #38bdf8; font-weight: 600;';
                    }

                    $http_code = isset($row['http_status']) ? (int)$row['http_status'] : 200;
                    $ttfb_ms   = isset($row['ttfb_ms']) ? (int)$row['ttfb_ms'] : 0;
                    $pay_kb    = isset($row['payload_kb']) ? (float)$row['payload_kb'] : 0;
                    $status_color = $http_code >= 400 ? '#f87171' : '#4ade80';
                ?>
                <tr data-action="<?php echo esc_attr($row['action_type']); ?>"
                    data-handshake="<?php echo esc_attr($row['handshake_status']); ?>"
                    data-pillar="<?php echo esc_attr($row['pillar_node']); ?>">
                    <td><?php echo esc_html($date->format('M d, g:i:s A')); ?></td>
                    <td><span class="px-badge <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($row['visitor_type']); ?></span></td>
                    <td title="<?php echo esc_attr($row['request_url']); ?>">
                        <?php echo esc_html(parse_url($row['request_url'], PHP_URL_PATH)); ?>
                        <?php if (!empty($row['prompt_query'])): ?>
                            <div style="font-size: 0.7rem; color: #fbbf24; margin-top: 2px;" title="<?php echo esc_attr($row['prompt_query']); ?>">
                                Query: <?php echo esc_html($row['prompt_query']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo esc_html($row['pillar_node']); ?></strong></td>
                    <td style="<?php echo esc_attr($action_style); ?>"><?php echo esc_html($row['action_type']); ?></td>
                    <td>
                        <span style="color: <?php echo esc_attr($status_color); ?>; font-weight: 700;"><?php echo esc_html($http_code); ?></span>
                        <?php if ($ttfb_ms > 0): ?>
                            <span style="color: #94a3b8; font-size: 0.75rem;"> &bull; <?php echo esc_html($ttfb_ms); ?>ms</span>
                        <?php endif; ?>
                        <?php if ($pay_kb > 0): ?>
                            <span style="color: #64748b; font-size: 0.72rem;"> (<?php echo esc_html($pay_kb); ?>KB)</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?php echo esc_html($row['ip_address']); ?></code></td>
                    <td>
                        <?php if ($row['handshake_status'] === 'High Friction'): ?>
                            <span class="px-badge px-badge-friction">High Friction</span>
                        <?php elseif ($row['handshake_status'] === 'Spoofed Bot'): ?>
                            <span class="px-badge px-badge-friction">Spoofed Bot</span>
                        <?php elseif ($row['handshake_status'] === 'Verified Bot'): ?>
                            <span class="px-badge px-badge-search">Verified Bot</span>
                        <?php elseif ($row['handshake_status'] === 'Unverified Bot'): ?>
                            <span class="px-badge px-badge-warn">Unverified</span>
                        <?php else: ?>
                            <span style="color: #94a3b8;"><?php echo esc_html($row['handshake_status']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>