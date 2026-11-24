<?php if (!defined('ABSPATH')) exit; ?>
<div class="px-filter-bar">
    <div class="px-filter-group">
        <span style="font-size: 0.8rem; font-weight: 700; color: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 4px;">
            Live Feed (30d)
        </span>
        <select id="px-filter-action" class="px-select">
            <option value="">All Intents / Actions</option>
            <option value="Live Citation Fetch">Live Citation Fetch</option>
            <option value="AI Search Indexing">AI Search Indexing</option>
            <option value="Model Training">Model Training</option>
            <option value="AI Referral Click">AI Referral Click</option>
            <option value="Search Indexing">Traditional Search</option>
            <option value="Pageview">Human Pageview</option>
        </select>

        <select id="px-filter-handshake" class="px-select">
            <option value="">All Handshakes</option>
            <option value="Verified Bot">Verified Bot</option>
            <option value="Spoofed Bot">Spoofed Bot</option>
            <option value="High Friction">High Friction</option>
            <option value="Unverified Bot">Unverified Bot</option>
            <option value="Active">Active (Human)</option>
        </select>

        <select id="px-filter-pillar" class="px-select">
            <option value="">All Pillar Nodes</option>
            <option value="Home">Home</option>
            <option value="AEO & AI Systems">AEO &amp; AI Systems</option>
            <option value="UX Architecture">UX Architecture</option>
            <option value="WordPress & Perf">WordPress &amp; Perf</option>
            <option value="Legacy Design">Legacy Design</option>
            <option value="LLMs.txt Manifest">LLMs.txt Manifest</option>
        </select>
    </div>

    <div class="px-filter-group">
        <button type="button" id="px-purge-btn" class="px-btn px-btn-danger">
            Purge Logs
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=phoenix-sensor')); ?>" class="px-btn px-btn-secondary">
            Refresh
        </a>
    </div>
</div>

<script>
(function() {
    const actionSel    = document.getElementById('px-filter-action');
    const handshakeSel = document.getElementById('px-filter-handshake');
    const pillarSel    = document.getElementById('px-filter-pillar');

    function applyTableFilters() {
        const aVal = actionSel.value;
        const hVal = handshakeSel.value;
        const pVal = pillarSel.value;

        document.querySelectorAll('#px-telemetry-table tbody tr[data-action]').forEach(row => {
            const matchA = !aVal || row.dataset.action === aVal;
            const matchH = !hVal || row.dataset.handshake === hVal;
            const matchP = !pVal || row.dataset.pillar === pVal;
            row.style.display = (matchA && matchH && matchP) ? '' : 'none';
        });
    }

    actionSel?.addEventListener('change', applyTableFilters);
    handshakeSel?.addEventListener('change', applyTableFilters);
    pillarSel?.addEventListener('change', applyTableFilters);

    document.getElementById('px-purge-btn')?.addEventListener('click', function() {
        if (!confirm('Purge all raw telemetry logs? (Daily rollup history will be preserved.)')) return;
        const data = new URLSearchParams({
            action: 'px_purge_logs',
            nonce: '<?php echo esc_js(wp_create_nonce('px_sensor_nonce')); ?>'
        });
        fetch(ajaxurl, { method: 'POST', body: data })
            .then(r => r.json())
            .then(res => { if (res.success) location.reload(); });
    });
})();
</script>