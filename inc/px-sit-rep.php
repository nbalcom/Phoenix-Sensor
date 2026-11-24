<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'phoenix_sensor_logs';

$total_hits     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$ai_hits        = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE visitor_type LIKE 'AI Bot%'");
$live_citations = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type = 'Live Citation Fetch'");
$training_hits  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type = 'Model Training'");
$ai_referrals   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE action_type = 'AI Referral Click'");
$verified_bots  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE handshake_status = 'Verified Bot'");
$spoofed_bots   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE handshake_status = 'Spoofed Bot'");
$error_bots     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE visitor_type LIKE 'AI Bot%' AND http_status >= 400");
$avg_bot_ttfb   = (int) $wpdb->get_var("SELECT ROUND(AVG(NULLIF(ttfb_ms, 0))) FROM $table_name WHERE visitor_type LIKE 'AI Bot%'");

$ai_pct = $total_hits > 0 ? round(($ai_hits / $total_hits) * 100, 1) : 0;

// Citation Gap: Top URLs fetched live by AI agents with ZERO human referral clicks back
$citation_gaps = $wpdb->get_results(
    "SELECT
        request_url,
        SUM(CASE WHEN action_type = 'Live Citation Fetch' THEN 1 ELSE 0 END) AS fetches,
        SUM(CASE WHEN action_type = 'AI Referral Click' THEN 1 ELSE 0 END) AS clicks
     FROM $table_name
     GROUP BY request_url
     HAVING fetches > 0 AND clicks = 0
     ORDER BY fetches DESC
     LIMIT 4",
    ARRAY_A
);

$top_pillar = $wpdb->get_var(
    "SELECT pillar_node FROM $table_name WHERE visitor_type LIKE 'AI Bot%' GROUP BY pillar_node ORDER BY COUNT(*) DESC LIMIT 1"
) ?: 'None yet';

$access_health = function_exists('px_check_ai_access_health') ? px_check_ai_access_health() : ['blocked_bots' => [], 'llms_txt_ok' => false];

$sitrep_summary = sprintf(
    "Phoenix Sensor 30-Day Telemetry (%s):\n• Live AI Citation Fetches: %d\n• AI Model Training Hits: %d\n• AI Share of Traffic: %s%% (%d total hits)\n• Verified AI Crawlers: %d (%d spoofed blocked/flagged)\n• Human Referral Clicks from LLMs: %d\n• Avg AI Bot TTFB: %dms\n• Top Cited Topic Pillar: %s",
    wp_parse_url(home_url(), PHP_URL_HOST),
    $live_citations,
    $training_hits,
    $ai_pct,
    $total_hits,
    $verified_bots,
    $spoofed_bots,
    $ai_referrals,
    $avg_bot_ttfb,
    $top_pillar
);
?>
<div class="px-sitrep-grid">
    <div class="px-card">
        <div class="px-card-label">1. Live Citation Fetches</div>
        <div class="px-card-val" style="color: #38bdf8;"><?php echo esc_html($live_citations); ?></div>
        <div class="px-card-sub"><?php echo esc_html($training_hits); ?> Training | <?php echo esc_html($ai_pct); ?>% AI Share</div>
    </div>
    <div class="px-card">
        <div class="px-card-label">2. Handshake Verification</div>
        <div class="px-card-val" style="color: #4ade80;"><?php echo esc_html($verified_bots); ?></div>
        <div class="px-card-sub" style="<?php echo $spoofed_bots > 0 ? 'color: #f87171;' : ''; ?>">
            <?php echo esc_html($spoofed_bots); ?> Spoofed Impostors
        </div>
    </div>
    <div class="px-card">
        <div class="px-card-label">3. AI Referral Clicks</div>
        <div class="px-card-val"><?php echo esc_html($ai_referrals); ?></div>
        <div class="px-card-sub">Top Pillar: <?php echo esc_html($top_pillar); ?></div>
    </div>
    <div class="px-card">
        <div class="px-card-label">4. Bot Speed &amp; Errors</div>
        <div class="px-card-val" style="<?php echo $error_bots > 0 ? 'color: #f87171;' : ''; ?>">
            <?php echo esc_html($avg_bot_ttfb); ?><span style="font-size: 1rem; font-weight: 600; color: #94a3b8;">ms</span>
        </div>
        <div class="px-card-sub" style="<?php echo $error_bots > 0 ? 'color: #f87171;' : ''; ?>">
            <?php echo esc_html($error_bots); ?> Bot HTTP Errors (4xx/5xx)
        </div>
    </div>
</div>

<div class="px-intel-row">
    <div class="px-panel">
        <div class="px-panel-title">
            <span>Citation Gap Alerts (High Fetch, Zero Clicks)</span>
            <span class="px-badge px-badge-warn">Add CTA / Hook</span>
        </div>
        <?php if (empty($citation_gaps)): ?>
            <div style="font-size: 0.8125rem; color: #94a3b8;">No zero-click citation gaps detected yet in the current 30-day window.</div>
        <?php else: ?>
            <ul class="px-gap-list">
                <?php foreach ($citation_gaps as $gap): ?>
                    <li>
                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($gap['request_url']); ?>">
                            <?php echo esc_html(parse_url($gap['request_url'], PHP_URL_PATH)); ?>
                        </span>
                        <span class="px-badge px-badge-ai"><?php echo esc_html($gap['fetches']); ?> fetches &rarr; 0 clicks</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="px-panel">
        <div class="px-panel-title">
            <span>AI Crawler Access &amp; Manifest Health</span>
            <?php if (empty($access_health['blocked_bots'])): ?>
                <span class="px-badge px-badge-search">Open Access</span>
            <?php else: ?>
                <span class="px-badge px-badge-friction">Blocked Bots</span>
            <?php endif; ?>
        </div>
        <ul class="px-gap-list">
            <li>
                <span><code>robots.txt</code> AI Directives</span>
                <?php if (empty($access_health['blocked_bots'])): ?>
                    <span style="color: #4ade80; font-weight: 600;">All Major AI Bots Allowed</span>
                <?php else: ?>
                    <span style="color: #f87171; font-weight: 600;">Blocked: <?php echo esc_html(implode(', ', $access_health['blocked_bots'])); ?></span>
                <?php endif; ?>
            </li>
            <li>
                <span><code>/llms.txt</code> AI Index Manifest</span>
                <?php if (!empty($access_health['llms_txt_ok'])): ?>
                    <span style="color: #4ade80; font-weight: 600;">200 OK (Active)</span>
                <?php else: ?>
                    <span style="color: #fbbf24; font-weight: 600;">Not Found (Optional)</span>
                <?php endif; ?>
            </li>
        </ul>
    </div>
</div>

<textarea id="px-sitrep-clipboard-text" style="position: absolute; left: -9999px;"><?php echo esc_textarea($sitrep_summary); ?></textarea>
<script>
document.getElementById('px-copy-sitrep-btn')?.addEventListener('click', function() {
    const text = document.getElementById('px-sitrep-clipboard-text').value;
    navigator.clipboard.writeText(text).then(() => {
        const original = this.textContent;
        this.textContent = 'Copied to Clipboard!';
        setTimeout(() => { this.textContent = original; }, 2000);
    });
});
</script>