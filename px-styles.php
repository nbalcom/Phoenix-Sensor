<style>
/* FORCE #0b1421 BACKGROUND ON WORDPRESS ADMIN CANVAS */
body.toplevel_page_phoenix-sensor,
body.toplevel_page_phoenix-sensor #wpcontent,
body.toplevel_page_phoenix-sensor #wpbody,
body.toplevel_page_phoenix-sensor #wpbody-content {
    background-color: #0b1421 !important;
    color: #e2e8f0;
}
body.toplevel_page_phoenix-sensor #wpfooter {
    color: #64748b !important;
}
body.toplevel_page_phoenix-sensor .notice,
body.toplevel_page_phoenix-sensor .updated,
body.toplevel_page_phoenix-sensor .error {
    background: #111c2e !important;
    color: #e2e8f0 !important;
    border-color: #1e293b !important;
}

.px-vibe-wrap { margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #e2e8f0; }
.px-header { display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; align-items: center; background: #111c2e; padding: 20px 24px; border-radius: 12px; border: 1px solid #1e293b; margin-bottom: 20px; }
.px-header h1 { margin: 0; font-size: 1.5rem; font-weight: 700; color: #38bdf8; display: flex; align-items: center; gap: 10px; }
.px-header-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

.px-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.8125rem; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
.px-btn-primary { background: #2563eb; color: #ffffff !important; border-color: #3b82f6; }
.px-btn-primary:hover { background: #1d4ed8; color: #ffffff !important; }
.px-btn-secondary { background: #1e293b; color: #e2e8f0 !important; border-color: #334155; }
.px-btn-secondary:hover { background: #334155; color: #ffffff !important; }
.px-btn-danger { background: rgba(239, 68, 68, 0.12); color: #f87171 !important; border-color: rgba(239, 68, 68, 0.35); }
.px-btn-danger:hover { background: rgba(239, 68, 68, 0.22); color: #fecaca !important; }

.px-sitrep-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
.px-card { background: #111c2e; border: 1px solid #1e293b; padding: 18px 20px; border-radius: 12px; }
.px-card-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 6px; font-weight: 600; }
.px-card-val { font-size: 1.75rem; font-weight: 800; color: #f8fafc; line-height: 1.15; }
.px-card-sub { font-size: 0.78rem; color: #38bdf8; margin-top: 6px; }

.px-intel-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px; }
.px-panel { background: #111c2e; border: 1px solid #1e293b; border-radius: 12px; padding: 16px 20px; }
.px-panel-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #f8fafc; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
.px-gap-list { list-style: none; margin: 0; padding: 0; }
.px-gap-list li { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-top: 1px solid #1e293b; font-size: 0.8125rem; color: #cbd5e1; gap: 12px; }
.px-gap-list li:first-child { border-top: none; padding-top: 0; }

.px-filter-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; background: #111c2e; border: 1px solid #1e293b; padding: 12px 16px; border-radius: 10px; margin-bottom: 14px; }
.px-filter-group { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.px-select { background: #0b1421 !important; color: #e2e8f0 !important; border: 1px solid #334155 !important; border-radius: 6px !important; padding: 4px 10px !important; font-size: 0.8rem !important; height: 32px !important; }

.px-table-wrap { background: #111c2e; border: 1px solid #1e293b; border-radius: 12px; overflow-x: auto; }
.px-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.8125rem; color: #cbd5e1; }
.px-table th { background: #172338; padding: 12px 14px; font-weight: 600; color: #94a3b8; text-transform: uppercase; font-size: 0.68rem; letter-spacing: 0.05em; white-space: nowrap; }
.px-table td { padding: 11px 14px; border-top: 1px solid #1e293b; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #e2e8f0; }
.px-table td code { background: #0b1421; color: #93c5fd; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }
.px-table tr:hover td { background: rgba(30, 41, 59, 0.55); }

.px-badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
.px-badge-ai { background: rgba(56, 189, 248, 0.16); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
.px-badge-search { background: rgba(34, 197, 94, 0.16); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.35); }
.px-badge-human { background: rgba(148, 163, 184, 0.16); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.35); }
.px-badge-friction { background: rgba(239, 68, 68, 0.16); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }
.px-badge-warn { background: rgba(245, 158, 11, 0.16); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
</style>