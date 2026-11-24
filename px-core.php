<?php
if (!defined('ABSPATH')) exit;

define('PX_DB_VERSION', '2.5.0');
define('PX_START_TIME', microtime(true));

// 1. DATABASE SCHEMA INITIALIZATION (RAW LOGS + 30-DAY DAILY ROLLUPS)
function px_init_sensor_db() {
    global $wpdb;
    $installed_ver = get_option('px_sensor_db_version');

    if ($installed_ver !== PX_DB_VERSION) {
        $logs_table  = $wpdb->prefix . 'phoenix_sensor_logs';
        $daily_table = $wpdb->prefix . 'phoenix_sensor_daily';
        $charset_collate = $wpdb->get_charset_collate();

        $sql_logs = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip_address varchar(100) NOT NULL,
            request_url text NOT NULL,
            user_agent text NOT NULL,
            referrer text,
            visitor_type varchar(60) DEFAULT 'Unknown',
            action_type varchar(60) DEFAULT 'Pageview',
            pillar_node varchar(100) DEFAULT 'General',
            handshake_status varchar(50) DEFAULT 'Active',
            http_status smallint(4) DEFAULT 200,
            ttfb_ms int(11) DEFAULT 0,
            payload_kb decimal(10,2) DEFAULT 0.00,
            prompt_query text,
            PRIMARY KEY  (id),
            KEY idx_timestamp (timestamp),
            KEY idx_ip (ip_address),
            KEY idx_action (action_type),
            KEY idx_handshake (handshake_status)
        ) $charset_collate;";

        $sql_daily = "CREATE TABLE $daily_table (
            log_date date NOT NULL,
            total_hits int(11) DEFAULT 0,
            live_citations int(11) DEFAULT 0,
            ai_indexing int(11) DEFAULT 0,
            model_training int(11) DEFAULT 0,
            ai_referrals int(11) DEFAULT 0,
            human_hits int(11) DEFAULT 0,
            verified_bots int(11) DEFAULT 0,
            spoofed_bots int(11) DEFAULT 0,
            avg_ttfb_ms int(11) DEFAULT 0,
            top_cited_url text,
            PRIMARY KEY  (log_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_logs);
        dbDelta($sql_daily);

        update_option('px_sensor_db_version', PX_DB_VERSION);
    }

    if (!wp_next_scheduled('px_daily_maintenance_event')) {
        wp_schedule_event(time() + 300, 'daily', 'px_daily_maintenance_event');
    }
}
add_action('init', 'px_init_sensor_db');

// 2. DAILY ROLLUP & 30-DAY AUTO-PRUNING CRON
function px_run_daily_maintenance() {
    global $wpdb;
    $logs_table  = $wpdb->prefix . 'phoenix_sensor_logs';
    $daily_table = $wpdb->prefix . 'phoenix_sensor_daily';

    // Aggregate yesterday's UTC data into the long-term rollup table
    $target_date = gmdate('Y-m-d', strtotime('-1 day'));
    $start_dt    = $target_date . ' 00:00:00';
    $end_dt      = $target_date . ' 23:59:59';

    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COUNT(*) AS total_hits,
            SUM(CASE WHEN action_type = 'Live Citation Fetch' THEN 1 ELSE 0 END) AS live_citations,
            SUM(CASE WHEN action_type = 'AI Search Indexing' THEN 1 ELSE 0 END) AS ai_indexing,
            SUM(CASE WHEN action_type = 'Model Training' THEN 1 ELSE 0 END) AS model_training,
            SUM(CASE WHEN action_type = 'AI Referral Click' THEN 1 ELSE 0 END) AS ai_referrals,
            SUM(CASE WHEN visitor_type = 'Human Visitor' THEN 1 ELSE 0 END) AS human_hits,
            SUM(CASE WHEN handshake_status = 'Verified Bot' THEN 1 ELSE 0 END) AS verified_bots,
            SUM(CASE WHEN handshake_status = 'Spoofed Bot' THEN 1 ELSE 0 END) AS spoofed_bots,
            ROUND(AVG(NULLIF(ttfb_ms, 0))) AS avg_ttfb_ms
         FROM $logs_table
         WHERE timestamp BETWEEN %s AND %s",
        $start_dt,
        $end_dt
    ), ARRAY_A);

    if (!empty($summary) && (int)$summary['total_hits'] > 0) {
        $top_url = $wpdb->get_var($wpdb->prepare(
            "SELECT request_url FROM $logs_table
             WHERE timestamp BETWEEN %s AND %s AND action_type = 'Live Citation Fetch'
             GROUP BY request_url ORDER BY COUNT(*) DESC LIMIT 1",
            $start_dt,
            $end_dt
        ));

        $wpdb->replace(
            $daily_table,
            [
                'log_date'       => $target_date,
                'total_hits'     => (int)$summary['total_hits'],
                'live_citations' => (int)$summary['live_citations'],
                'ai_indexing'    => (int)$summary['ai_indexing'],
                'model_training' => (int)$summary['model_training'],
                'ai_referrals'   => (int)$summary['ai_referrals'],
                'human_hits'     => (int)$summary['human_hits'],
                'verified_bots'  => (int)$summary['verified_bots'],
                'spoofed_bots'   => (int)$summary['spoofed_bots'],
                'avg_ttfb_ms'    => (int)($summary['avg_ttfb_ms'] ?? 0),
                'top_cited_url'  => $top_url ?: ''
            ],
            ['%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
        );
    }

    // Prune raw hit rows older than 30 days to keep queries instantaneous
    $cutoff = gmdate('Y-m-d H:i:s', strtotime('-30 days'));
    $wpdb->query($wpdb->prepare("DELETE FROM $logs_table WHERE timestamp < %s", $cutoff));
}
add_action('px_daily_maintenance_event', 'px_run_daily_maintenance');

// 3. IP CIDR HELPER FOR BOT HANDSHAKE VERIFICATION
function px_ip_in_cidrs($ip, $cidrs) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }
    $ip_long = ip2long($ip);
    foreach ($cidrs as $cidr) {
        if (strpos($cidr, '/') === false) continue;
        list($subnet, $mask) = explode('/', $cidr);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);
        if (($ip_long & $mask_long) === ($subnet_long & $mask_long)) {
            return true;
        }
    }
    return false;
}

// 4. FETCH & CACHE OFFICIAL BOT IP RANGES (24-HOUR TRANSIENT)
function px_get_verified_bot_cidrs($provider) {
    $cache_key = 'px_bot_cidrs_' . sanitize_key($provider);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }

    $urls = [
        'openai' => [
            'https://openai.com/chatgpt-user.json',
            'https://openai.com/searchbot.json',
            'https://openai.com/gptbot.json'
        ],
        'google' => [
            'https://developers.google.com/static/search/apis/ipranges/googlebot.json'
        ]
    ];

    $cidrs = [];
    if (!empty($urls[$provider])) {
        foreach ($urls[$provider] as $url) {
            $res = wp_remote_get($url, ['timeout' => 3]);
            if (!is_wp_error($res) && wp_remote_retrieve_response_code($res) === 200) {
                $data = json_decode(wp_remote_retrieve_body($res), true);
                if (!empty($data['prefixes']) && is_array($data['prefixes'])) {
                    foreach ($data['prefixes'] as $prefix) {
                        if (!empty($prefix['ipv4Prefix'])) {
                            $cidrs[] = $prefix['ipv4Prefix'];
                        }
                    }
                }
            }
        }
    }

    if (empty($cidrs) && $provider === 'openai') {
        $cidrs = ['20.194.0.0/16', '52.255.0.0/16', '9.160.0.0/16', '20.15.240.0/20', '20.171.206.0/24', '20.171.207.0/24'];
    }

    set_transient($cache_key, $cidrs, DAY_IN_SECONDS);
    return $cidrs;
}

// 5. VERIFY BOT HANDSHAKE (CIDR + REVERSE DNS)
function px_verify_bot_handshake($ip, $ua_lower) {
    if (strpos($ua_lower, 'gptbot') !== false || strpos($ua_lower, 'chatgpt-user') !== false || strpos($ua_lower, 'oai-searchbot') !== false) {
        $openai_cidrs = px_get_verified_bot_cidrs('openai');
        return px_ip_in_cidrs($ip, $openai_cidrs) ? 'Verified Bot' : 'Spoofed Bot';
    }

    if (strpos($ua_lower, 'googlebot') !== false || strpos($ua_lower, 'google-extended') !== false) {
        $google_cidrs = px_get_verified_bot_cidrs('google');
        if (px_ip_in_cidrs($ip, $google_cidrs)) {
            return 'Verified Bot';
        }
        $host = @gethostbyaddr($ip);
        return ($host && (str_ends_with($host, '.googlebot.com') || str_ends_with($host, '.google.com'))) ? 'Verified Bot' : 'Spoofed Bot';
    }

    if (strpos($ua_lower, 'claude') !== false || strpos($ua_lower, 'anthropic') !== false) {
        if (strpos($ip, '216.73.216.') === 0 || strpos($ip, '216.73.217.') === 0 || strpos($ip, '160.79.104.') === 0) {
            return 'Verified Bot';
        }
        $host = @gethostbyaddr($ip);
        return ($host && str_ends_with($host, '.anthropic.com')) ? 'Verified Bot' : 'Unverified Bot';
    }

    return 'Unverified Bot';
}

// 6. ROUTE URL INTO CLEAN CONTENT PILLAR NODES
function px_classify_pillar_node($path) {
    $clean = trim(strtolower($path), '/');
    if ($clean === '') {
        return 'Home';
    }
    if ($clean === 'llms.txt') {
        return 'LLMs.txt Manifest';
    }
    if (preg_match('/(aeo|schema|llm|ai-search|jailbird|freebird|phoenix)/', $clean)) {
        return 'AEO & AI Systems';
    }
    if (preg_match('/(ux|vibe-coding|inference-friction|design|interface)/', $clean)) {
        return 'UX Architecture';
    }
    if (preg_match('/(photoshop|export-layers)/', $clean)) {
        return 'Legacy Design';
    }
    if (preg_match('/(wp-|wordpress|plugin|wpos|pagespeed|performance)/', $clean)) {
        return 'WordPress & Perf';
    }
    if (in_array($clean, ['about-me', 'blog', 'contact'], true)) {
        return 'Core Nav (' . ucfirst($clean) . ')';
    }
    $segments = explode('/', $clean);
    return ucfirst(str_replace('-', ' ', $segments[0]));
}

// 7. PROTOCOL #3: PROMPT & SEARCH QUERY TRACING
function px_extract_prompt_clue($request_url, $referrer) {
    $clues = [];
    $url_query = parse_url($request_url, PHP_URL_QUERY);
    if ($url_query) {
        parse_str($url_query, $params);
        foreach (['q', 'query', 'prompt', 's', 'search', 'utm_source', 'utm_term'] as $key) {
            if (!empty($params[$key]) && is_string($params[$key])) {
                $clues[] = $key . '=' . sanitize_text_field($params[$key]);
            }
        }
    }

    if ($referrer && $referrer !== 'Direct') {
        $ref_query = parse_url($referrer, PHP_URL_QUERY);
        if ($ref_query) {
            parse_str($ref_query, $rparams);
            foreach (['q', 'query', 'prompt', 'text'] as $key) {
                if (!empty($rparams[$key]) && is_string($rparams[$key])) {
                    $clues[] = 'ref_' . $key . '=' . sanitize_text_field($rparams[$key]);
                }
            }
        }
    }

    return !empty($clues) ? substr(implode(' | ', $clues), 0, 250) : '';
}

// 8. BOT ACCESS & ROBOTS.TXT / LLMS.TXT HEALTH CHECK (CACHED 1 HOUR)
function px_check_ai_access_health() {
    $cached = get_transient('px_ai_access_health');
    if ($cached !== false) {
        return $cached;
    }

    $bots_to_check = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot'];
    $blocked_bots = [];

    $robots_res = wp_remote_get(home_url('/robots.txt'), ['timeout' => 3]);
    if (!is_wp_error($robots_res) && wp_remote_retrieve_response_code($robots_res) === 200) {
        $body = wp_remote_retrieve_body($robots_res);
        $lines = preg_split('/\r\n|\r|\n/', $body);
        $active_agents = [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if ($line === '') continue;

            if (stripos($line, 'User-agent:') === 0) {
                $agent = trim(substr($line, 11));
                $active_agents[] = $agent;
            } elseif (stripos($line, 'Disallow:') === 0) {
                $rule = trim(substr($line, 9));
                if ($rule === '/') {
                    foreach ($active_agents as $ag) {
                        if ($ag === '*') {
                            $blocked_bots = $bots_to_check;
                        } else {
                            foreach ($bots_to_check as $target) {
                                if (strcasecmp($ag, $target) === 0 && !in_array($target, $blocked_bots, true)) {
                                    $blocked_bots[] = $target;
                                }
                            }
                        }
                    }
                }
            } else {
                $active_agents = [];
            }
        }
    }

    $llms_res = wp_remote_head(home_url('/llms.txt'), ['timeout' => 3]);
    $llms_ok = (!is_wp_error($llms_res) && wp_remote_retrieve_response_code($llms_res) === 200);

    $health = [
        'blocked_bots' => $blocked_bots,
        'llms_txt_ok'  => $llms_ok
    ];

    set_transient('px_ai_access_health', $health, HOUR_IN_SECONDS);
    return $health;
}

// 9. CAPTURE HTTP STATUS, TTFB & PAYLOAD SIZE AT SHUTDOWN
$GLOBALS['px_telemetry_pending'] = null;

add_action('template_redirect', function() {
    if (is_admin() || is_user_logged_in() || is_feed() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $raw_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = parse_url($raw_uri, PHP_URL_PATH) ?: '';

    // Filter out static assets (except llms.txt) and REST API noise
    if ($path !== '/llms.txt' && (preg_match('/\.(ico|css|js|map|png|jpg|jpeg|gif|svg|webp|woff2?|ttf|eot|xml|txt)$/i', $path) || strpos($path, '/wp-json/') === 0)) {
        return;
    }

    // Start output buffer to measure HTML payload size cleanly
    ob_start();

    $user_agent  = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'Unknown';
    $ip_address  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    $request_url = esc_url_raw((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $raw_uri);
    $referrer    = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])) : 'Direct';

    $ua_lower     = strtolower($user_agent);
    $visitor_type = 'Human Visitor';
    $action_type  = 'Pageview';
    $is_bot       = false;

    $ai_bots = [
        'chatgpt-user'      => ['label' => 'OpenAI ChatGPT-User',  'action' => 'Live Citation Fetch'],
        'oai-searchbot'     => ['label' => 'OpenAI SearchBot',     'action' => 'AI Search Indexing'],
        'gptbot'            => ['label' => 'OpenAI GPTBot',        'action' => 'Model Training'],
        'claude-user'       => ['label' => 'Anthropic Claude-User', 'action' => 'Live Citation Fetch'],
        'claude-searchbot'  => ['label' => 'Anthropic SearchBot',  'action' => 'AI Search Indexing'],
        'claudebot'         => ['label' => 'Anthropic ClaudeBot',  'action' => 'Model Training'],
        'anthropic-ai'      => ['label' => 'Anthropic AI',         'action' => 'Model Training'],
        'perplexity-user'   => ['label' => 'Perplexity User',      'action' => 'Live Citation Fetch'],
        'perplexitybot'     => ['label' => 'PerplexityBot',        'action' => 'AI Search Indexing'],
        'applebot-extended' => ['label' => 'Apple Intelligence',   'action' => 'Model Training'],
        'google-extended'   => ['label' => 'Google Gemini Bot',    'action' => 'Model Training'],
        'bytespider'        => ['label' => 'ByteDance Spider',     'action' => 'Model Training'],
        'cohere-ai'         => ['label' => 'Cohere AI',            'action' => 'Model Training'],
        'diffbot'           => ['label' => 'Diffbot',              'action' => 'Model Training']
    ];

    $search_bots = [
        'googlebot'   => 'Googlebot',
        'bingbot'     => 'Bingbot',
        'duckduckbot' => 'DuckDuckGo',
        'yandexbot'   => 'Yandex',
        'baiduspider' => 'Baidu'
    ];

    foreach ($ai_bots as $token => $meta) {
        if (strpos($ua_lower, $token) !== false) {
            $visitor_type = 'AI Bot (' . $meta['label'] . ')';
            $action_type  = $meta['action'];
            $is_bot       = true;
            break;
        }
    }

    if (!$is_bot) {
        foreach ($search_bots as $token => $label) {
            if (strpos($ua_lower, $token) !== false) {
                $visitor_type = 'Search Crawler (' . $label . ')';
                $action_type  = 'Search Indexing';
                $is_bot       = true;
                break;
            }
        }
    }

    if (!$is_bot && $referrer !== 'Direct') {
        $ref_lower = strtolower($referrer);
        if (strpos($ref_lower, 'chatgpt.com') !== false || strpos($ref_lower, 'claude.ai') !== false || strpos($ref_lower, 'perplexity.ai') !== false || strpos($ref_lower, 'gemini.google.com') !== false) {
            $action_type = 'AI Referral Click';
        }
    }

    $GLOBALS['px_telemetry_pending'] = [
        'ip_address'   => $ip_address,
        'request_url'  => $request_url,
        'user_agent'   => substr($user_agent, 0, 500),
        'ua_lower'     => $ua_lower,
        'referrer'     => $referrer,
        'visitor_type' => $visitor_type,
        'action_type'  => $action_type,
        'pillar_node'  => px_classify_pillar_node($path),
        'prompt_query' => px_extract_prompt_clue($request_url, $referrer),
        'is_bot'       => $is_bot
    ];
}, 1);

add_action('shutdown', function() {
    if (empty($GLOBALS['px_telemetry_pending'])) {
        return;
    }

    $data = $GLOBALS['px_telemetry_pending'];
    $payload_bytes = ob_get_level() > 0 ? ob_get_length() : 0;
    if (ob_get_level() > 0) {
        ob_end_flush();
    }

    $http_status = http_response_code();
    if (!$http_status) {
        $http_status = is_404() ? 404 : 200;
    }

    $ttfb_ms    = (int) round((microtime(true) - PX_START_TIME) * 1000);
    $payload_kb = round($payload_bytes / 1024, 2);

    global $wpdb;
    $table_name = $wpdb->prefix . 'phoenix_sensor_logs';

    $recent_hits = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE ip_address = %s AND request_url = %s",
        $data['ip_address'],
        $data['request_url']
    ));

    if ($recent_hits >= 5) {
        $handshake_status = 'High Friction';
    } elseif ($data['is_bot']) {
        $handshake_status = px_verify_bot_handshake($data['ip_address'], $data['ua_lower']);
    } else {
        $handshake_status = 'Active';
    }

    $wpdb->insert(
        $table_name,
        [
            'timestamp'        => current_time('mysql', 1),
            'ip_address'       => $data['ip_address'],
            'request_url'      => $data['request_url'],
            'user_agent'       => $data['user_agent'],
            'referrer'         => $data['referrer'],
            'visitor_type'     => $data['visitor_type'],
            'action_type'      => $data['action_type'],
            'pillar_node'      => $data['pillar_node'],
            'handshake_status' => $handshake_status,
            'http_status'      => $http_status,
            'ttfb_ms'          => $ttfb_ms,
            'payload_kb'       => $payload_kb,
            'prompt_query'     => $data['prompt_query']
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s']
    );
}, 999);