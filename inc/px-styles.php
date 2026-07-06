<?php if (!defined('ABSPATH')) exit; ?>
<style id="px-omnipresent-styles">
    :root {
        --px-bg: #111926;
        --px-blue: #3b82f6;
        --px-card: rgba(30, 41, 59, 0.4);
        --px-border: rgba(255, 255, 255, 0.05);
        --px-text: #ffffff;
        --px-muted: #64748b;
        --px-grid-cols: 1fr;
    }

    /* 1. GLOBAL ENVIRONMENT */
    #wpwrap, #wpcontent, #wpbody, #wpbody-content, #wpfooter { background-color: var(--px-bg) !important; }
    .px-vibe-wrap { color: var(--px-text) !important; padding: 20px; font-family: 'Inter', sans-serif; min-height: 100vh; background-color: var(--px-bg) !important; box-sizing: border-box; width: 100% !important; max-width: 100vw !important; overflow-x: hidden !important; }

    /* 2. PX HEADER & TITLE STACK [Restored] */
    .px-header-main { display: flex !important; flex-wrap: wrap !important; justify-content: space-between !important; align-items: center !important; margin-bottom: 30px !important; width: 100% !important; gap: 20px !important; }
    
    .px-title-stack { display: flex !important; flex-direction: column !important; gap: 2px !important; }
    .px-title-stack h1 { font-size: 22px !important; font-weight: 900 !important; color: #ffffff !important; margin: 0 !important; letter-spacing: -0.02em !important; text-transform: uppercase !important; line-height: 1.1 !important; }
    .px-version-tag { font-size: 10px !important; color: #3b82f6 !important; background: rgba(59, 130, 246, 0.15) !important; padding: 2px 8px !important; border-radius: 4px !important; margin-left: 10px !important; font-weight: 700 !important; vertical-align: middle !important; }
    .px-subtitle-fix { margin: 0 !important; letter-spacing: 0.05em !important; font-size: 11px !important; font-weight: 800 !important; color: var(--px-blue) !important; text-transform: uppercase !important; }

    /* 3. BRANDING & ICONS [Strict Enforcement] */
    .px-branding-right { display: flex !important; align-items: center !important; gap: 15px !important; margin-left: auto !important; }
    .px-branding-motto { text-align: right !important; display: flex !important; flex-direction: column !important; gap: 2px !important; }
    .px-motto-top { font-size: 10px !important; font-weight: 800 !important; color: var(--px-blue) !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; line-height: 1 !important; }
    .px-motto-bottom { font-size: 10px !important; font-weight: 800 !important; color: #64748b !important; text-transform: uppercase !important; line-height: 1 !important; }
    .px-phoenix-logo { width: 45px !important; height: auto !important; display: block !important; }

    /* The AI Icon Hammer: Forces 20x20 regardless of context */
    .px-agent-row img, .px-agent-icon, .px-tbl-wrap img { 
        width: 20px !important; 
        height: 20px !important; 
        min-width: 20px !important; 
        max-width: 20px !important; 
        border-radius: 4px !important; 
        margin-right: 12px !important; 
        object-fit: contain !important; 
    }

    /* 4. SR MODULES & STATS */
    .px-sr-grid { display: grid !important; grid-template-columns: var(--px-grid-cols) !important; gap: 20px !important; width: 100% !important; margin: 0 0 30px 0 !important; }
    .px-sr-card { background: var(--px-card) !important; padding: 20px !important; border-radius: 12px !important; border: 1px solid var(--px-border) !important; display: flex !important; flex-direction: column !important; position: relative !important; }
    .px-sr-card-label { font-size: 11px !important; font-weight: 800 !important; color: #ffffff !important; text-transform: uppercase !important; border-bottom: 1px solid var(--px-border) !important; padding-bottom: 12px !important; margin-bottom: 15px !important; display: block !important; }
    
    .px-stat-percent { font-family: 'JetBrains Mono', monospace !important; font-size: 14px !important; font-weight: 800 !important; color: var(--px-blue) !important; margin-left: auto !important; }
    .px-ratio-big-num { font-size: 42px !important; font-weight: 900 !important; color: var(--px-blue) !important; line-height: 1 !important; margin-bottom: 10px !important; display: block !important; }

    /* 5. DATA TABLE & ZEBRA */
    .px-tbl-container { width: 100% !important; overflow-x: auto !important; border-radius: 12px !important; border: 1px solid var(--px-border) !important; background: var(--px-card) !important; }
    .px-tbl-wrap { width: 100% !important; border-collapse: collapse !important; min-width: 800px !important; }
    .px-tbl-wrap td { padding: 14px 15px !important; font-size: 12px !important; border-bottom: 1px solid var(--px-border) !important; color: #cbd5e1 !important; vertical-align: middle !important; }
    .px-tbl-wrap tr:nth-child(even) td { background: rgba(255, 255, 255, 0.02) !important; }

    /* 6. BUTTON HARDENING */
    .px-nav-container { display: flex !important; flex-wrap: wrap !important; gap: 15px !important; margin: 30px 0 20px 0 !important; justify-content: space-between !important; align-items: center !important; }
    .px-nav-btn, .px-csv-link, .px-pagination-btn, .px-timeline-btn { background: #1e293b !important; color: #ffffff !important; border: 1px solid #334155 !important; padding: 10px 18px !important; border-radius: 8px !important; cursor: pointer !important; font-weight: 700 !important; font-size: 12px !important; transition: all 0.2s ease !important; text-decoration: none !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }
    
    .px-sr-card .px-timeline-btn, .px-sr-card .px-nav-btn { margin-top: auto !important; width: 100% !important; background: rgba(59, 130, 246, 0.1) !important; border: 1px solid rgba(59, 130, 246, 0.2) !important; color: var(--px-blue) !important; padding: 12px !important; text-transform: uppercase !important; }

    /* 7. RESPONSIVE MEDIA QUERIES */
    @media (min-width: 768px) {
        :root { --px-grid-cols: repeat(2, 1fr); }
        .px-vibe-wrap { padding: 40px; }
        .px-title-stack h1 { font-size: 26px !important; }
    }
    @media (min-width: 1100px) {
        :root { --px-grid-cols: repeat(3, 1fr); }
        .px-sr-card { min-height: 420px !important; }
    }
    @media (max-width: 600px) {
        .px-header-main { gap: 10px !important; }
        .px-branding-right { width: 100% !important; justify-content: flex-start !important; margin-left: 0 !important; }
        .px-branding-motto { text-align: left !important; }
    }
</style>
