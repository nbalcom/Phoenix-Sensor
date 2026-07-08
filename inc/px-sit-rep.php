<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'ao_ai_agent_logs';

/** 1. DATA CALCULATIONS **/
$total_24 = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$human_24 = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table WHERE agent_name LIKE '%End User%' AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$ai_val   = $total_24 - $human_24; 
$ratio    = ($total_24 > 0) ? round(($ai_val / $total_24) * 100) : 0;

$top_agents = $wpdb->get_results("SELECT agent_name, COUNT(*) as hits FROM $table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY agent_name ORDER BY hits DESC LIMIT 6");
?>

<div class="px-sr-grid">
    <div class="px-sr-card">
        <div class="px-sr-card-label">24H AI-TO-USER RATIO</div>
        <p class="px-ratio-desc">Percentage of site traffic originating from AI search agents and LLM scrapers versus human end-users.</p>
        <span class="px-ratio-big-num"><?php echo $ratio; ?>%</span>
        <div class="px-bar-track">
            <div class="px-bar-fill" style="width:<?php echo $ratio; ?>%;"></div>
        </div>
    </div>

    <div class="px-sr-card">
        <div class="px-sr-card-label">TOP AGENTS (<?php echo date('M j'); ?>)</div>
        <div id="px-24h-agents">
            <?php if ($top_agents): foreach ($top_agents as $row): 
                $id = get_px_agent_identity($row->agent_name);
            ?>
                <div class="px-agent-row">
                    <div> 
                        <?php echo $id['html']; ?>
                        <span style="font-weight:600; color:#fff; margin-left:12px;"><?php echo esc_html($id['name']); ?></span>
                    </div>
                    <span class="px-agent-hits"><?php echo (int)$row->hits; ?></span>
                </div>
            <?php endforeach; else: echo '<p style="color:var(--px-muted); font-size:11px;">No data recorded.</p>'; endif; ?>
        </div>
    </div>

    <div class="px-sr-card">
        <div class="px-sr-card-label">INTELLIGENCE TIMELINE</div>
        <div id="px-dynamic-agents" style="min-height:115px;"></div>
        <div style="display:flex; gap:10px; margin-top:20px; flex-wrap: wrap;">
            <button class="px-btn-ui active" onclick="pxLoadStats(2, this)">48H</button>
            <button class="px-btn-ui" onclick="pxLoadStats(7, this)">1WK</button>
            <button class="px-btn-ui" onclick="pxLoadStats(14, this)">2WK</button>
            <button class="px-btn-ui" onclick="pxLoadStats(30, this)">30DYS</button>
        </div>
    </div>
</div>

<script>
    function pxLoadStats(days, btn) {
        if(btn) { 
            const container = btn.parentElement;
            container.querySelectorAll('.px-btn-ui').forEach(b => b.classList.remove('active')); 
            btn.classList.add('active'); 
        }
        jQuery.post(ajaxurl, { action: 'px_get_stats', days: days }, function(res) { 
            jQuery('#px-dynamic-agents').html(res); 
        });
    }
    jQuery(document).ready(function(){ pxLoadStats(2, null); });
</script>
