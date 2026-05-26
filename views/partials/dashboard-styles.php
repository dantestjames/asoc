<style>
/* ===== MEMBER DASHBOARD SHARED STYLES ===== */
.db-wrap { padding:2rem 0 5rem; min-height:70vh; }
.db-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.db-header h1 { font-size:1.8rem; font-weight:800; margin-bottom:.2rem; }
.db-header p { font-size:.9rem; color:var(--grey-text); }
.db-status-pill { padding:.35rem .9rem; border-radius:999px; font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }

/* Tabs */
.db-tabs { display:flex; gap:0; border-bottom:2px solid var(--grey-mid); margin-bottom:1.5rem; flex-wrap:wrap; }
.db-tab { padding:.65rem 1.25rem; font-size:.9rem; font-weight:600; color:var(--grey-text); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:color .15s,border-color .15s; }
.db-tab:hover { color:var(--primary); text-decoration:none; }
.db-tab--active { color:var(--primary); border-bottom-color:var(--primary); }
.db-tab--logout { margin-left:auto; color:var(--grey-text); }
.db-tab--logout:hover { color:#EF4444; }

/* Alerts */
.db-alert { padding:.875rem 1.25rem; border-radius:10px; margin-bottom:1.25rem; font-size:.9rem; }
.db-alert a { font-weight:700; text-decoration:underline; }
.db-alert--warn { background:#FEF3C7; color:#92400E; }
.db-alert--danger { background:#FEE2E2; color:#991B1B; }
.db-alert--success { background:#D1FAE5; color:#065F46; }

/* Cards */
.db-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.25rem; }
.db-card { background:#fff; border-radius:14px; padding:1.5rem; box-shadow:0 1px 4px rgba(0,0,0,.06); }
.db-card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.db-card-header h3 { font-size:1rem; font-weight:700; margin:0; }
.db-card-desc { font-size:.875rem; color:var(--grey-text); line-height:1.5; }
.db-card-actions { display:flex; gap:.75rem; margin-top:1.25rem; flex-wrap:wrap; }
.db-tier-badge { background:var(--primary); color:#fff; padding:.2rem .75rem; border-radius:999px; font-size:.75rem; font-weight:700; }

/* Definition list */
.db-dl { display:grid; grid-template-columns:auto 1fr; gap:.4rem 1.25rem; font-size:.88rem; }
.db-dl dt { color:var(--grey-text); font-weight:600; white-space:nowrap; }
.db-dl dd { color:var(--text); }

/* Benefits list */
.db-benefits { list-style:none; padding:0; margin:0; }
.db-benefits li { padding:.35rem 0 .35rem 1.4rem; position:relative; font-size:.88rem; color:var(--grey-text); }
.db-benefits li::before { content:'✓'; position:absolute; left:0; color:#10B981; font-weight:700; }

/* Toggle */
.db-toggle { display:flex; align-items:center; gap:.75rem; cursor:pointer; }
.db-toggle input { display:none; }
.db-toggle-slider { width:44px; height:24px; background:var(--grey-mid); border-radius:999px; position:relative; flex-shrink:0; transition:background .2s; }
.db-toggle-slider::after { content:''; position:absolute; top:3px; left:3px; width:18px; height:18px; background:#fff; border-radius:50%; transition:transform .2s; }
.db-toggle input:checked ~ .db-toggle-slider { background:var(--primary); }
.db-toggle input:checked ~ .db-toggle-slider::after { transform:translateX(20px); }
.db-toggle-label { font-size:.9rem; font-weight:600; }

/* Transactions */
.db-tx-list { display:flex; flex-direction:column; gap:.1rem; }
.db-tx-row { display:flex; align-items:center; gap:1rem; padding:.7rem .5rem; border-radius:8px; }
.db-tx-row:hover { background:var(--grey-light); }
.db-tx-info { flex:1; }
.db-tx-type { font-size:.88rem; font-weight:600; }
.db-tx-meta { font-size:.76rem; color:var(--grey-text); margin-top:1px; }
.db-tx-amount { font-size:.92rem; font-weight:700; flex-shrink:0; }

/* Profile form */
.db-form fieldset { border:1px solid var(--grey-mid); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
.db-form legend { padding:0 .5rem; font-weight:700; font-size:.95rem; }
.db-field { margin-bottom:1rem; }
.db-field label { display:block; font-weight:600; font-size:.85rem; margin-bottom:.3rem; }
.db-field input,.db-field select,.db-field textarea { width:100%; padding:.65rem .9rem; border:1px solid var(--grey-mid); border-radius:8px; font-size:.95rem; font-family:inherit; }
.db-field input:focus,.db-field select:focus,.db-field textarea:focus { outline:2px solid var(--secondary); border-color:var(--secondary); }
.db-field textarea { resize:vertical; }
.db-field-help { font-size:.76rem; color:var(--grey-text); margin-top:.2rem; }
.db-row { display:flex; gap:1rem; }
.db-row .db-field { flex:1; }
.db-form-actions { display:flex; gap:.75rem; margin-top:1rem; }

.btn-sm { padding:.4rem .85rem; font-size:.82rem; }
.btn-outline-muted { border:1px solid var(--grey-mid); color:var(--grey-text); background:transparent; }
.btn-outline-muted:hover { background:var(--grey-light); }
@media(max-width:768px) {
    .db-row { flex-direction:column; gap:0; }
    .db-tabs { gap:0; }
    .db-tab { padding:.5rem .9rem; font-size:.82rem; }
}
</style>
