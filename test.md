<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — v1 (com Análises)</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<style>
:root{
  --pr:#CC0A2F;--pr-dk:#8C0020;--pr-lt:#E8153A;
  --wh:#FFFFFF;--bg:#F2F3F7;
  --g100:#ECEEF4;--g200:#DDE0EA;--g300:#BEC3D4;--g500:#7C829E;
  --g600:#4E546E;--g700:#2E3250;--g800:#181C35;
  --green:#00B87A;--yellow:#E09F1A;--red:#D9182E;--blue:#2D5FE0;
  --cyan:#009DBF;--violet:#6B3FE0;
  --sh:0 2px 12px rgba(0,0,0,.06);--sh2:0 6px 32px rgba(0,0,0,.13);
  --r:10px;--r-lg:16px;
  --ff-h:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;
}
[data-theme="dark"]{
  --wh:#1C2038;--bg:#10132A;
  --g100:#1E2240;--g200:#262B4A;--g300:#353C60;--g500:#6970A0;
  --g600:#9099C8;--g700:#C4CAEF;--g800:#E8EBFF;
  --sh:0 2px 12px rgba(0,0,0,.3);--sh2:0 6px 32px rgba(0,0,0,.5);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--ff-b);background:var(--bg);color:var(--g800);min-height:100vh}

/* ─── TOP NAV ─── */
.topnav{
  position:sticky;top:0;z-index:100;
  background:linear-gradient(135deg,var(--pr-dk) 0%,var(--pr) 100%);
  box-shadow:0 2px 20px rgba(140,0,32,.3);
  padding:0 40px;height:56px;
  display:flex;align-items:center;justify-content:space-between;gap:20px;
}
.topnav-brand{display:flex;align-items:center;gap:10px;flex-shrink:0}
.topnav-logo{width:32px;height:32px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--ff-h);font-size:.72rem;font-weight:800;color:#fff}
.topnav-title{font-family:var(--ff-h);font-size:.88rem;font-weight:700;color:#fff;line-height:1.2}
.topnav-sub{font-size:.65rem;color:rgba(255,255,255,.6)}
.topnav-pills{display:flex;align-items:center;gap:6px;flex:1;justify-content:center}
.nav-pill{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:.72rem;font-weight:600;color:rgba(255,255,255,.75);cursor:pointer;text-decoration:none;transition:all .2s;white-space:nowrap}
.nav-pill:hover,.nav-pill.act{background:rgba(255,255,255,.22);color:#fff;border-color:rgba(255,255,255,.35)}
.topnav-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
.btn-sm{padding:6px 14px;border-radius:8px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;font-family:var(--ff-b);display:flex;align-items:center;gap:6px;transition:all .2s;white-space:nowrap}
.btn-sm.ghost{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25)}
.btn-sm.ghost:hover{background:rgba(255,255,255,.22)}
.btn-sm.solid{background:#fff;color:var(--pr-dk)}
.btn-sm.solid:hover{transform:translateY(-1px);box-shadow:0 4px 16px rgba(0,0,0,.2)}

/* ─── JSON IMPORTER PANEL ─── */
.import-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}
.import-overlay.open{display:flex}
.import-panel{background:var(--wh);border-radius:var(--r-lg);padding:32px;width:100%;max-width:640px;box-shadow:var(--sh2)}
.import-panel h2{font-family:var(--ff-h);font-size:1.1rem;font-weight:700;color:var(--g800);margin-bottom:6px}
.import-panel p{font-size:.8rem;color:var(--g500);margin-bottom:16px;line-height:1.5}
.import-textarea{width:100%;height:200px;border:1px solid var(--g200);border-radius:var(--r);padding:12px;font-size:.75rem;font-family:monospace;background:var(--g100);color:var(--g800);resize:vertical;outline:none}
.import-textarea:focus{border-color:var(--pr)}
.import-row{display:flex;gap:10px;margin-top:14px;align-items:center}
.import-err{font-size:.75rem;color:var(--red);flex:1}
.btn-action{padding:9px 20px;border-radius:8px;font-size:.82rem;font-weight:700;cursor:pointer;border:none;font-family:var(--ff-b)}
.btn-cancel{background:var(--g100);color:var(--g600)}
.btn-apply{background:var(--pr);color:#fff}
.btn-apply:hover{background:var(--pr-dk)}

/* ─── EMAIL EXPORT MODAL ─── */
.export-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}
.export-overlay.open{display:flex}
.export-panel{background:var(--wh);border-radius:var(--r-lg);padding:32px;width:100%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;box-shadow:var(--sh2)}
.export-panel h2{font-family:var(--ff-h);font-size:1.1rem;font-weight:700;color:var(--g800);margin-bottom:6px}
.export-panel p{font-size:.8rem;color:var(--g500);margin-bottom:16px}
.export-preview{flex:1;overflow-y:auto;border:1px solid var(--g200);border-radius:var(--r);padding:0;background:#f9f9f9;min-height:200px}
.export-preview iframe{width:100%;min-height:400px;border:none;border-radius:var(--r)}
.export-actions{display:flex;gap:10px;margin-top:16px;justify-content:flex-end}

/* ─── MAIN LAYOUT ─── */
.main-body{max-width:1320px;margin:0 auto;padding:32px 32px 60px}

/* ─── SECTION ─── */
.section{margin-bottom:36px}
.sec-hd{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px}
.sec-hd-left{display:flex;gap:12px;align-items:flex-start}
.sec-accent{width:4px;min-height:28px;border-radius:3px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));flex-shrink:0;margin-top:2px}
.sec-title{font-family:var(--ff-h);font-size:1rem;font-weight:700;color:var(--g800);letter-spacing:-.01em}
.sec-sub{font-size:.74rem;color:var(--g500);margin-top:2px}
.sec-badge{background:var(--pr);color:#fff;border-radius:20px;padding:3px 12px;font-size:.67rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;flex-shrink:0}

/* ─── ANALYSIS FIELD ─── */
.analysis-field{background:linear-gradient(135deg,rgba(204,10,47,.04),rgba(140,0,32,.02));border:1px solid rgba(204,10,47,.15);border-left:3px solid var(--pr);border-radius:0 var(--r) var(--r) 0;padding:10px 14px;margin-top:10px}
.analysis-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--pr);margin-bottom:4px;display:flex;align-items:center;gap:5px}
.analysis-label svg{opacity:.7}
.analysis-text{font-size:.78rem;color:var(--g600);line-height:1.6;font-style:italic}

/* ─── KPI STRIP ─── */
/* Z-pattern: wide metric rows with inline analysis below each */
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.kpi-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:22px;display:flex;flex-direction:column;gap:5px;box-shadow:var(--sh);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:3px 3px 0 0}
.kpi-card.c-red::before{background:linear-gradient(90deg,var(--pr),var(--pr-lt))}
.kpi-card.c-green::before{background:linear-gradient(90deg,var(--green),#00D68A)}
.kpi-card.c-blue::before{background:linear-gradient(90deg,var(--blue),var(--cyan))}
.kpi-card.c-yellow::before{background:linear-gradient(90deg,var(--yellow),#FFD740)}
.kpi-card:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.kpi-lbl{font-size:.67rem;font-weight:700;color:var(--g500);text-transform:uppercase;letter-spacing:.07em}
.kpi-val{font-family:var(--ff-h);font-size:2.2rem;font-weight:800;line-height:1}
.kpi-card.c-red .kpi-val{color:var(--pr)}
.kpi-card.c-green .kpi-val{color:var(--green)}
.kpi-card.c-blue .kpi-val{color:var(--blue)}
.kpi-card.c-yellow .kpi-val{color:var(--yellow)}
.kpi-delta{display:inline-flex;align-items:center;gap:3px;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:12px}
.kpi-delta.up{background:rgba(0,184,122,.12);color:var(--green)}
.kpi-delta.dn{background:rgba(217,24,46,.1);color:var(--red)}
.kpi-delta.nt{background:var(--g100);color:var(--g500)}
.kpi-foot{font-size:.68rem;color:var(--g500)}

/* after 4 KPIs — analysis strip full-width */
.kpi-analysis-row{grid-column:1/-1}

/* ─── Z-PATTERN ROWS ─── */
/* Row type A: chart big + analysis panel right (3:1) */
/* Row type B: analysis panel left + chart big (1:3) — alternates for Z */
.z-row{display:grid;gap:16px;align-items:start}
.z-row.a{grid-template-columns:1fr 320px}
.z-row.b{grid-template-columns:320px 1fr}

.analysis-panel{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:22px;box-shadow:var(--sh);display:flex;flex-direction:column;gap:14px;align-self:stretch}
.ap-title{font-family:var(--ff-h);font-size:.8rem;font-weight:700;color:var(--g800);margin-bottom:2px;display:flex;align-items:center;gap:7px}
.ap-title svg{color:var(--pr);flex-shrink:0}
.ap-sep{height:1px;background:var(--g200)}
.ap-block{display:flex;flex-direction:column;gap:5px}
.ap-block-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--pr)}
.ap-block-text{font-size:.78rem;color:var(--g600);line-height:1.65}
.ap-insight{background:var(--g100);border-radius:8px;padding:10px 12px;border-left:3px solid var(--yellow)}
.ap-insight p{font-size:.76rem;color:var(--g700);line-height:1.55;font-style:italic}

/* ─── CHART CARD ─── */
.chart-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:22px;box-shadow:var(--sh)}
.cc-top{margin-bottom:14px}
.cc-title{font-size:.85rem;font-weight:700;color:var(--g800);margin-bottom:2px}
.cc-sub{font-size:.7rem;color:var(--g500)}
.cc-wrap{position:relative}
.cc-wrap.h200{height:200px}
.cc-wrap.h240{height:240px}
.cc-wrap.h280{height:280px}
.cc-wrap.h320{height:320px}
.cc-wrap.h360{height:360px}

/* legend below chart */
.chart-legend{display:flex;flex-wrap:wrap;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid var(--g200)}
.cl-item{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:500;color:var(--g600)}
.cl-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.cl-line{width:16px;height:3px;border-radius:2px;flex-shrink:0}

/* filter row for multi-line */
.filter-row{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.filter-chip{padding:4px 11px;border-radius:20px;font-size:.7rem;font-weight:600;cursor:pointer;border:1px solid var(--g200);background:var(--g100);color:var(--g600);transition:all .2s;user-select:none}
.filter-chip.act{background:var(--pr);color:#fff;border-color:var(--pr)}

/* ─── PO CARDS ─── */
.po-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.po-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh)}
.po-head{background:var(--g100);border-bottom:1px solid var(--g200);padding:14px 18px;display:flex;align-items:center;gap:11px}
.po-av{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--pr),var(--pr-dk));display:flex;align-items:center;justify-content:center;font-family:var(--ff-h);font-weight:800;font-size:.78rem;color:#fff;flex-shrink:0}
.po-name{font-size:.85rem;font-weight:700;color:var(--g800)}
.po-role{font-size:.68rem;color:var(--g500);margin-top:1px}
.po-body{padding:16px 18px;display:flex;flex-direction:column;gap:11px}
.po-item{display:flex;gap:9px}
.po-dot{width:5px;height:5px;border-radius:50%;background:var(--pr);flex-shrink:0;margin-top:7px}
.tag{display:inline-block;font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:2px 7px;border-radius:4px;margin-bottom:3px}
.tag.s{background:rgba(204,10,47,.1);color:var(--pr)}
.tag.i{background:rgba(45,95,224,.1);color:var(--blue)}
.tag.o{background:rgba(0,184,122,.1);color:var(--green)}
.tag.p{background:rgba(107,63,224,.1);color:var(--violet)}
.po-item-title{font-size:.82rem;font-weight:600;color:var(--g800);line-height:1.3;margin-bottom:2px}
.po-item-desc{font-size:.75rem;color:var(--g600);line-height:1.55}
.po-sep{height:1px;background:var(--g200)}

/* ─── SCORE TABLE ─── */
.score-table{width:100%;border-collapse:collapse;font-size:.8rem}
.score-table th{background:var(--g100);color:var(--g500);font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:10px 14px;text-align:left;border-bottom:2px solid var(--g200)}
.score-table td{padding:10px 14px;border-bottom:1px solid var(--g200);color:var(--g700);vertical-align:middle}
.score-table tr:last-child td{border-bottom:none}
.score-table tr:hover td{background:var(--g100)}
.badge{display:inline-flex;align-items:center;font-size:.74rem;font-weight:700;padding:3px 9px;border-radius:20px}
.badge.gr{background:rgba(0,184,122,.12);color:var(--green)}
.badge.bl{background:rgba(45,95,224,.1);color:var(--blue)}
.badge.ye{background:rgba(224,159,26,.12);color:var(--yellow)}
.badge.rd{background:rgba(217,24,46,.1);color:var(--red)}

/* ─── RELEASES ─── */
.releases-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.rel-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r);padding:16px;box-shadow:var(--sh);border-bottom:3px solid transparent}
.rel-card.new{border-bottom-color:var(--green)}
.rel-card.upd{border-bottom-color:var(--blue)}
.rel-badge{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:7px}
.rel-card.new .rel-badge{background:rgba(0,184,122,.12);color:var(--green)}
.rel-card.upd .rel-badge{background:rgba(45,95,224,.1);color:var(--blue)}
.rel-name{font-size:.84rem;font-weight:700;color:var(--g800);line-height:1.3;margin-bottom:3px}
.rel-ver{font-size:.7rem;color:var(--g500)}

/* ─── DIMENSOES BARS ─── */
.dim-list{display:flex;flex-direction:column;gap:10px}
.dim-row{display:flex;align-items:center;gap:12px}
.dim-name{font-size:.75rem;font-weight:600;color:var(--g600);width:110px;flex-shrink:0;white-space:nowrap}
.dim-bar-wrap{flex:1;height:8px;background:var(--g200);border-radius:4px;overflow:visible;position:relative}
.dim-bar-fill{height:100%;border-radius:4px;transition:width 1s cubic-bezier(.77,0,.18,1)}
.dim-val-label{font-size:.72rem;font-weight:700;color:var(--g700);width:80px;flex-shrink:0;text-align:right}

/* ─── SCROLL REVEAL ─── */
.reveal{opacity:0;transform:translateY(20px);transition:opacity .5s ease,transform .5s ease}
.reveal.visible{opacity:1;transform:none}

/* ─── SCROLLBAR ─── */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--g300);border-radius:10px}

/* ─── HERO ─── */
.hero{background:linear-gradient(130deg,var(--pr-dk) 0%,var(--pr) 55%,#A50022 100%);padding:40px 40px 36px;position:relative;overflow:hidden}
.hero-grid{position:absolute;inset:0;opacity:.04;background-image:linear-gradient(rgba(255,255,255,1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,1) 1px,transparent 1px);background-size:56px 56px;pointer-events:none}
.hero-blob{position:absolute;right:-6%;top:-20%;width:50%;padding-top:50%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 65%);pointer-events:none}
.hero-body{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:32px;max-width:1320px;margin:0 auto}
.hero-left{}
.hero-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:4px 13px 4px 8px;margin-bottom:14px}
.hero-dot{width:7px;height:7px;background:#FF7A9A;border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,122,154,.5)}50%{box-shadow:0 0 0 5px rgba(255,122,154,0)}}
.hero-badge-txt{font-size:.68rem;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.09em}
.hero-h1{font-family:var(--ff-h);font-size:clamp(1.7rem,3vw,2.6rem);font-weight:800;color:#fff;line-height:1.08;letter-spacing:-.02em;margin-bottom:8px}
.hero-sub{font-size:.88rem;color:rgba(255,255,255,.68);line-height:1.6}
.hero-right{display:flex;gap:14px;flex-shrink:0}
.hero-stat{background:rgba(255,255,255,.12);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:16px 22px;text-align:center;min-width:120px}
.hero-stat-val{font-family:var(--ff-h);font-size:1.8rem;font-weight:800;color:#fff;line-height:1}
.hero-stat-lbl{font-size:.66rem;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.06em;margin-top:5px}
.hero-bottom{position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent)}
</style>
</head>
<body>

<!-- ═══ TOP NAV ═══ -->
<nav class="topnav">
  <div class="topnav-brand">
    <div class="topnav-logo">DQ</div>
    <div>
      <div class="topnav-title">Boletim de Qualidade de Dados</div>
      <div class="topnav-sub">Inteligência de Dados · Bradesco</div>
    </div>
  </div>
  <div class="topnav-pills">
    <a class="nav-pill act" href="#entregas">Entregas</a>
    <a class="nav-pill" href="#dados">Dados Relevantes</a>
    <a class="nav-pill" href="#dimensoes">Dimensões</a>
    <a class="nav-pill" href="#evolucao">Evolução</a>
    <a class="nav-pill" href="#releases">Releases</a>
  </div>
  <div class="topnav-actions">
    <button class="btn-sm ghost" onclick="openImport()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Importar JSON
    </button>
    <button class="btn-sm solid" onclick="openExport()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      Gerar E-mail
    </button>
    <button class="btn-sm ghost" onclick="toggleTheme()" title="Tema" style="padding:6px 10px">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
  </div>
</nav>

<!-- ═══ IMPORT PANEL ═══ -->
<div class="import-overlay" id="importOverlay">
  <div class="import-panel">
    <h2>Importar dados via JSON</h2>
    <p>Cole um objeto JSON com as chaves do boletim. Campos não encontrados mantêm os valores padrão. <a href="#" onclick="previewSchema(event)" style="color:var(--pr)">Ver schema esperado</a>.</p>
    <textarea class="import-textarea" id="importJson" placeholder='{"periodo":"Abril 2026","kpis":{"score_medio":98.4,"produtos":10,...},...}'></textarea>
    <div class="import-row">
      <span class="import-err" id="importErr"></span>
      <button class="btn-action btn-cancel" onclick="closeImport()">Cancelar</button>
      <button class="btn-action btn-apply" onclick="applyJson()">Aplicar</button>
    </div>
  </div>
</div>

<!-- ═══ EXPORT PANEL ═══ -->
<div class="export-overlay" id="exportOverlay">
  <div class="export-panel">
    <h2>Gerar versão para E-mail</h2>
    <p>Preview do HTML compatível com clientes de e-mail. Copie o código ou faça download para envio.</p>
    <div class="export-preview" id="exportPreview"></div>
    <div class="export-actions">
      <button class="btn-action btn-cancel" onclick="closeExport()">Fechar</button>
      <button class="btn-action btn-apply" onclick="copyEmailHtml()">Copiar HTML</button>
      <button class="btn-action btn-apply" onclick="downloadEmail()">Download .html</button>
    </div>
  </div>
</div>

<!-- ═══ HERO ═══ -->
<div class="hero">
  <div class="hero-grid"></div>
  <div class="hero-blob"></div>
  <div class="hero-body">
    <div class="hero-left">
      <div class="hero-badge">
        <span class="hero-dot"></span>
        <span class="hero-badge-txt">Referência: Abril 2026</span>
      </div>
      <h1 class="hero-h1">Boletim de Qualidade<br>de Dados</h1>
      <p class="hero-sub" id="heroSub">Inteligência de Dados · Emitido em 27/05/2026</p>
    </div>
    <div class="hero-right" id="heroStats">
      <div class="hero-stat"><div class="hero-stat-val" id="hsStat1">98,4%</div><div class="hero-stat-lbl">Score Médio</div></div>
      <div class="hero-stat"><div class="hero-stat-val" id="hsStat2">10</div><div class="hero-stat-lbl">Produtos</div></div>
      <div class="hero-stat"><div class="hero-stat-val" id="hsStat3">7</div><div class="hero-stat-lbl">Entregas</div></div>
      <div class="hero-stat"><div class="hero-stat-val" id="hsStat4">94</div><div class="hero-stat-lbl">Chamados</div></div>
    </div>
  </div>
  <div class="hero-bottom"></div>
</div>

<!-- ═══ MAIN BODY ═══ -->
<div class="main-body">

  <!-- ══ KPIs ══ -->
  <section class="section reveal" id="kpis">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Resumo Executivo</div><div class="sec-sub">Indicadores-chave do período</div></div>
      </div>
      <span class="sec-badge" id="kpiBadge">Abril 2026</span>
    </div>
    <div class="kpi-grid">
      <div class="kpi-card c-red">
        <div class="kpi-lbl">Score Médio Geral</div>
        <div class="kpi-val" id="kpiScore">98,4%</div>
        <div class="kpi-delta up" id="kpiScoreDelta">▲ +1,2 pp vs Mar</div>
        <div class="kpi-foot">Média ponderada por volume</div>
        <div class="analysis-field"><div class="analysis-label"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Análise</div><div class="analysis-text" id="anaScore">Score 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização dos produtos Open Finance e pela entrada de novos produtos com alta baseline.</div></div>
      </div>
      <div class="kpi-card c-green">
        <div class="kpi-lbl">Produtos Monitorados</div>
        <div class="kpi-val" id="kpiProd">10</div>
        <div class="kpi-delta nt" id="kpiProdDelta">→ Estável</div>
        <div class="kpi-foot">Com SLA ativo</div>
        <div class="analysis-field"><div class="analysis-label"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Análise</div><div class="analysis-text" id="anaProd">Base monitorada estável. 3 novos produtos iniciaram ciclo em Release 1, ampliando cobertura sem reduzir qualidade da carteira existente.</div></div>
      </div>
      <div class="kpi-card c-blue">
        <div class="kpi-lbl">Entregas Estruturantes</div>
        <div class="kpi-val" id="kpiEnt">7</div>
        <div class="kpi-delta up" id="kpiEntDelta">▲ +2 vs Mar</div>
        <div class="kpi-foot">Realizadas no ciclo</div>
        <div class="analysis-field"><div class="analysis-label"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Análise</div><div class="analysis-text" id="anaEnt">Destaque para a entrega do BRAI4DQ em produção e o Agente DEVA na plataforma Bridge — ambas com impacto direto na maturidade técnica da área.</div></div>
      </div>
      <div class="kpi-card c-yellow">
        <div class="kpi-lbl">Chamados no Período</div>
        <div class="kpi-val" id="kpiCham">94</div>
        <div class="kpi-delta dn" id="kpiChamDelta">▼ −17 vs Mar</div>
        <div class="kpi-foot">100% encerrados</div>
        <div class="analysis-field"><div class="analysis-label"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Análise</div><div class="analysis-text" id="anaCham">Redução de 15% no volume de chamados e taxa de encerramento de 100% no período. Resultado consistente com as melhorias de processo implementadas em Mar/26.</div></div>
      </div>
    </div>
  </section>

  <!-- ══ ENTREGAS POs ══ -->
  <section class="section reveal" id="entregas">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Entregas dos Product Owners</div><div class="sec-sub">Contribuições por responsável no ciclo</div></div>
      </div>
    </div>
    <div class="po-grid" id="poGrid"></div>
  </section>

  <!-- ══ DADOS RELEVANTES — Z-ROW A: Scores (chart big left, analysis right) ══ -->
  <section class="section reveal" id="dados">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Dados Relevantes</div><div class="sec-sub">Métricas e indicadores de qualidade por produto</div></div>
      </div>
    </div>

    <!-- Z row A: chart left · analysis right -->
    <div class="z-row a" style="margin-bottom:16px">
      <div class="chart-card">
        <div class="cc-top"><div class="cc-title">Score de Qualidade por Produto — Abril/26</div><div class="cc-sub">Percentual de aderência ao SLA · cores por faixa: verde ≥98% · azul ≥90% · vermelho &lt;90%</div></div>
        <div class="cc-wrap h360"><canvas id="cScores"></canvas></div>
      </div>
      <div class="analysis-panel">
        <div class="ap-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>Análise — Scores</div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label" id="anaScoresLabel">Destaques</div><div class="ap-block-text" id="anaScoresText">7 dos 10 produtos atingiram score ≥99%, demonstrando alta maturidade da carteira. Open Finance mantém desempenho consistente acima de 95%, e os 3 novos produtos de Release 1 entraram com scores saudáveis.</div></div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label">Atenção</div><div class="ap-block-text" id="anaScoresAtencao">Rating de Risco PLDFT segue como produto crítico (80,0%), com trajetória de recuperação desde Jan/26 (64,0%). Plano de ação em andamento com foco na dimensão de Disponibilidade.</div></div>
        <div class="ap-insight"><p id="anaScoresInsight">💡 Recomendação: priorizar revisão do fluxo de Disponibilidade no PLDFT para o ciclo de Mai/26.</p></div>
      </div>
    </div>

    <!-- Tabela abaixo -->
    <div class="chart-card" style="margin-bottom:16px">
      <div class="cc-top"><div class="cc-title">Tabela Consolidada — Produtos Monitorados</div><div class="cc-sub">Visão detalhada: score atual, tendência, chamados e dimensão crítica</div></div>
      <table class="score-table" style="margin-top:8px"><thead><tr><th>Produto</th><th>Gestor</th><th>Score Abr/26</th><th>Score Mar/26</th><th>Tend.</th><th>Chamados</th><th>Dim. Crítica</th></tr></thead><tbody id="scoreTbody"></tbody></table>
    </div>

    <!-- Z row B: analysis left · status+dist charts right -->
    <div class="z-row b">
      <div class="analysis-panel">
        <div class="ap-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>Análise — Chamados</div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label">Distribuição</div><div class="ap-block-text" id="anaStatusText">94 chamados registrados no período, todos encerrados. Zero chamados em aberto ou pendentes reflete alta eficiência operacional da área e SLA 100% cumprido.</div></div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label">Por Faixa</div><div class="ap-block-text" id="anaDistText">70% dos produtos operam na faixa Excelente (≥98%). Apenas 1 produto na faixa Atenção e nenhum na faixa Crítico quando considerada a carteira toda.</div></div>
        <div class="ap-insight"><p id="anaStatusInsight">💡 Evolução favorável: 2 produtos migraram da faixa Bom para Excelente em relação ao mês anterior.</p></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="chart-card">
          <div class="cc-top"><div class="cc-title">Distribuição por Faixa de Score</div><div class="cc-sub">Quantidade de produtos por nível de desempenho</div></div>
          <div class="cc-wrap h180"><canvas id="cDist"></canvas></div>
          <div class="chart-legend" id="legDist"></div>
        </div>
        <div class="chart-card">
          <div class="cc-top"><div class="cc-title">Status dos Chamados</div><div class="cc-sub">Encerrados vs. em análise / andamento</div></div>
          <div class="cc-wrap h160"><canvas id="cStatus"></canvas></div>
          <div class="chart-legend" id="legStatus"></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ DIMENSÕES — Z-ROW A: chart left, analysis right ══ -->
  <section class="section reveal" id="dimensoes">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Dimensões de Qualidade</div><div class="sec-sub">Volume de chamados e ocorrências por dimensão monitorada</div></div>
      </div>
    </div>
    <div class="z-row a" style="margin-bottom:16px">
      <div class="chart-card">
        <div class="cc-top"><div class="cc-title">Chamados e Ocorrências por Dimensão</div><div class="cc-sub">Barras com valores explícitos — vermelho = chamados · azul = ocorrências</div></div>
        <div class="cc-wrap h280"><canvas id="cDimensoes"></canvas></div>
        <div class="chart-legend" id="legDim"></div>
      </div>
      <div class="analysis-panel">
        <div class="ap-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>Análise — Dimensões</div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label" id="anaDimLabel1">Principais vetores</div><div class="ap-block-text" id="anaDimText1">Disponibilidade lidera em chamados (49), mas Completude apresenta maior volume de ocorrências (159), indicando ocorrências recorrentes no mesmo produto. Consistência segue com 30 chamados, concentrados em Open Finance.</div></div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label" id="anaDimLabel2">Prioridade</div><div class="ap-block-text" id="anaDimText2">A relação ocorrências/chamado na Completude (4,8x) exige investigação de causa-raiz — possível problema sistêmico vs. pontuais. Integridade e Unicidade permanecem em volumes residuais.</div></div>
      </div>
    </div>

    <!-- Dimensões com barras -->
    <div class="chart-card">
      <div class="cc-top"><div class="cc-title">Mapa de Dimensões — Volumes Consolidados</div><div class="cc-sub">Comparativo de chamados e ocorrências com indicador visual de intensidade</div></div>
      <div class="dim-list" id="dimList" style="margin-top:8px"></div>
    </div>
  </section>

  <!-- ══ EVOLUÇÃO — Z-ROW B: analysis left, chart right ══ -->
  <section class="section reveal" id="evolucao">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Evolução Mensal</div><div class="sec-sub">Trajetória de score e volume de chamados nos últimos 5 meses</div></div>
      </div>
    </div>

    <!-- filtros de produto para o gráfico de evolução -->
    <div class="chart-card" style="margin-bottom:16px">
      <div class="cc-top"><div class="cc-title">Evolução do Score por Produto — Dez/25 a Abr/26</div><div class="cc-sub">Selecione os produtos para exibir · valores explícitos em cada ponto</div></div>
      <div class="filter-row" id="evolFilters"></div>
      <div class="cc-wrap h320"><canvas id="cEvolucao"></canvas></div>
      <div class="chart-legend" id="legEvolucao"></div>
    </div>

    <!-- Z row B: analysis left · tendencia right -->
    <div class="z-row b">
      <div class="analysis-panel">
        <div class="ap-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>Análise — Evolução</div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label" id="anaEvolLabel">Tendências</div><div class="ap-block-text" id="anaEvolText">Carteira apresenta trajetória ascendente consistente. Rating de Risco PLDFT em recuperação desde Jan/26. Open Finance oscilante, mas dentro do intervalo aceitável. Produtos maduros (SACL, Feriados) mantêm 100% por 3 meses consecutivos.</div></div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label">Volume</div><div class="ap-block-text" id="anaEvolVol">Chamados concluídos reduziram 53% em relação ao pico de Jan/26 (111). Tendência de normalização operacional confirmada, sem chamados em aberto nos últimos 5 meses.</div></div>
        <div class="ap-insight"><p id="anaEvolInsight">💡 Ponto de atenção: Open Finance atingiu 98,7% em Fev/26 e recuou para 95,98% em Abr/26. Investigar causa da regressão.</p></div>
      </div>
      <div class="chart-card">
        <div class="cc-top"><div class="cc-title">Volume de Chamados — Tendência Mensal</div><div class="cc-sub">Chamados concluídos e em aberto por ciclo · valores explícitos</div></div>
        <div class="cc-wrap h280"><canvas id="cTendencia"></canvas></div>
        <div class="chart-legend" id="legTend"></div>
      </div>
    </div>
  </section>

  <!-- ══ CAUSAS-RAIZ — Z-ROW A ══ -->
  <section class="section reveal" id="causas-sec">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Causas-Raiz</div><div class="sec-sub">Análise de Pareto das causas de chamados no período</div></div>
      </div>
    </div>
    <div class="z-row a">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="chart-card">
          <div class="cc-top"><div class="cc-title">Distribuição de Causas-Raiz</div><div class="cc-sub">Volume e percentual de cada causa — valores explícitos nas barras</div></div>
          <div class="cc-wrap h260"><canvas id="cCausas"></canvas></div>
          <div class="chart-legend" id="legCausas"></div>
        </div>
        <div class="chart-card">
          <div class="cc-top"><div class="cc-title">Análise de Pareto — Causas</div><div class="cc-sub">Barras = volume · linha = % acumulado · valores explícitos</div></div>
          <div class="cc-wrap h240"><canvas id="cPareto"></canvas></div>
          <div class="chart-legend" id="legPareto"></div>
        </div>
      </div>
      <div class="analysis-panel">
        <div class="ap-title"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>Análise — Causas</div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label" id="anaCausasLabel">Concentração</div><div class="ap-block-text" id="anaCausasText">As 2 principais causas (Alteração na Estrutura e Open Finance) concentram 67% dos chamados — padrão de Pareto claro. Ações preventivas nessas categorias teriam impacto direto e rápido no volume total.</div></div>
        <div class="ap-sep"></div>
        <div class="ap-block"><div class="ap-block-label">Ações Previstas</div><div class="ap-block-text" id="anaCausasAcoes">Preenchimento Incorreto (19 chamados) pode ser reduzido com capacitação direcionada dos times responsáveis. Diversas e Externo somam apenas 2 chamados — residuais sem ação imediata necessária.</div></div>
        <div class="ap-insight"><p id="anaCausasInsight">💡 Ponto crítico: 63% dos chamados de Alteração na Estrutura estão concentrados em 2 produtos. Mapear origem para mitigação sistêmica.</p></div>
      </div>
    </div>
  </section>

  <!-- ══ RELEASES ══ -->
  <section class="section reveal" id="releases">
    <div class="sec-hd">
      <div class="sec-hd-left">
        <div class="sec-accent"></div>
        <div><div class="sec-title">Produtos de Dados — Releases Abril/26</div><div class="sec-sub">Novos produtos e versões entregues no período</div></div>
      </div>
    </div>
    <div class="releases-grid" id="releasesGrid"></div>
    <div class="analysis-field" style="margin-top:14px">
      <div class="analysis-label"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Análise — Releases</div>
      <div class="analysis-text" id="anaReleases">3 novos produtos em Release 1 ampliam a cobertura da carteira monitorada. Todos iniciaram com scores acima de 96%, indicando qualidade de entrada alta. Plataforma IA Generativa (R2) e Dados Socioeconômicos (R5) consolidam maturidade em ciclos avançados.</div>
    </div>
  </section>

</div><!-- /main-body -->

<script>
Chart.register(ChartDataLabels);

// ═══════════════════════════════════════
// SINGLETON — fonte única de verdade
// ═══════════════════════════════════════
let D = {
  periodo: "Abril 2026",
  emitido: "27/05/2026",
  kpis:{
    score_medio:98.4, score_delta:"+1,2 pp vs Mar", score_delta_type:"up",
    produtos:10, produtos_delta:"Estável", produtos_delta_type:"nt",
    entregas:7, entregas_delta:"+2 vs Mar", entregas_delta_type:"up",
    chamados:94, chamados_delta:"−17 vs Mar", chamados_delta_type:"dn"
  },
  analises:{
    score:"Score 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização dos produtos Open Finance e pela entrada de novos produtos com alta baseline.",
    produtos:"Base monitorada estável. 3 novos produtos iniciaram ciclo em Release 1, ampliando cobertura sem reduzir qualidade da carteira existente.",
    entregas:"Destaque para a entrega do BRAI4DQ em produção e o Agente DEVA na plataforma Bridge — ambas com impacto direto na maturidade técnica da área.",
    chamados:"Redução de 15% no volume de chamados e taxa de encerramento de 100% no período. Resultado consistente com as melhorias de processo implementadas em Mar/26.",
    scores_destaques:"7 dos 10 produtos atingiram score ≥99%, demonstrando alta maturidade da carteira. Open Finance mantém desempenho consistente acima de 95%.",
    scores_atencao:"Rating de Risco PLDFT segue como produto crítico (80,0%), com trajetória de recuperação desde Jan/26 (64,0%). Plano de ação em andamento com foco na dimensão de Disponibilidade.",
    scores_insight:"💡 Recomendação: priorizar revisão do fluxo de Disponibilidade no PLDFT para o ciclo de Mai/26.",
    status:"94 chamados registrados no período, todos encerrados. Zero chamados em aberto ou pendentes reflete alta eficiência operacional da área e SLA 100% cumprido.",
    dist:"70% dos produtos operam na faixa Excelente (≥98%). Apenas 1 produto na faixa Atenção e nenhum na faixa Crítico quando considerada a carteira toda.",
    status_insight:"💡 Evolução favorável: 2 produtos migraram da faixa Bom para Excelente em relação ao mês anterior.",
    dim1:"Disponibilidade lidera em chamados (49), mas Completude apresenta maior volume de ocorrências (159), indicando ocorrências recorrentes no mesmo produto.",
    dim2:"A relação ocorrências/chamado na Completude (4,8x) exige investigação de causa-raiz — possível problema sistêmico vs. pontuais.",
    evol:"Carteira apresenta trajetória ascendente consistente. Rating de Risco PLDFT em recuperação desde Jan/26. Open Finance oscilante, mas dentro do intervalo aceitável.",
    evol_vol:"Chamados concluídos reduziram 53% em relação ao pico de Jan/26 (111). Tendência de normalização operacional confirmada.",
    evol_insight:"💡 Ponto de atenção: Open Finance atingiu 98,7% em Fev/26 e recuou para 95,98% em Abr/26. Investigar causa da regressão.",
    causas:"As 2 principais causas concentram 67% dos chamados — padrão de Pareto claro. Ações preventivas nessas categorias teriam impacto direto e rápido no volume total.",
    causas_acoes:"Preenchimento Incorreto (19 chamados) pode ser reduzido com capacitação direcionada. Diversas e Externo somam apenas 2 chamados — residuais.",
    causas_insight:"💡 63% dos chamados de Alteração na Estrutura estão concentrados em 2 produtos. Mapear origem para mitigação sistêmica.",
    releases:"3 novos produtos em Release 1 ampliam a cobertura da carteira. Todos iniciaram com scores acima de 96%, indicando qualidade de entrada alta."
  },
  meses:['Dez/25','Jan/26','Fev/26','Mar/26','Abr/26'],
  produtos:[
    {nome:'OPEN FINANCE',gestor:'Augusto Vieira',scores:[92.06,91.49,98.73,94.3,95.98],chamados:81,dim:'Consistência'},
    {nome:'QUALIDADE DE DADOS',gestor:'Augusto Vieira',scores:[null,null,null,100,100],chamados:0,dim:'—'},
    {nome:'RATING DE RISCO PLDFT',gestor:'Deise Ferreira',scores:[null,64.0,85.52,74.58,80.0],chamados:2,dim:'Disponibilidade'},
    {nome:'ATENDIMENTO AO CLIENTE',gestor:'Deise Ferreira',scores:[89.95,98.89,99.61,99.64,99.77],chamados:5,dim:'Completude'},
    {nome:'COMUNICAÇÕES E ALERTAS PLDFT',gestor:'Deise Ferreira',scores:[93.53,99.24,100,100,99.65],chamados:2,dim:'Disponibilidade'},
    {nome:'PROCESSOS JURÍDICOS',gestor:'Deise Ferreira',scores:[94.44,100,100,99.91,99.73],chamados:4,dim:'Completude'},
    {nome:'MANIFESTAÇÕES SACL',gestor:'Deise Ferreira',scores:[99.73,100,100,100,100],chamados:0,dim:'—'},
    {nome:'SRM CALENDÁRIO FERIADOS',gestor:'Deise Ferreira',scores:[99.72,99.72,100,100,100],chamados:0,dim:'—'},
    {nome:'VISÃO CLIENTES 360',gestor:'Roberto Lima',scores:[null,null,97.2,98.1,99.2],chamados:0,dim:'—'},
    {nome:'IA GENERATIVA',gestor:'Roberto Lima',scores:[null,null,null,96.5,98.4],chamados:0,dim:'—'}
  ],
  dimensoes:{
    labels:['Disponibilidade','Completude','Consistência','Integridade','Unicidade'],
    chamados:[49,33,30,8,1],
    ocorrencias:[65,159,30,8,43]
  },
  causas:{
    labels:['Alt. Estrutura','Open Finance','Preench. Incor.','Não Informado','Orig. Indisponível','Diversas','Externo'],
    data:[34,29,19,7,3,1,1]
  },
  tendencia:{
    abertos:[0,0,0,0,0],
    concluidos:[31,111,55,64,30]
  },
  statusChamados:{encerrados:94,analise:0,andamento:0},
  pos:[
    {id:'DH',nome:'Diogo Horta Costa',role:'Product Owner · BRAI4DQ',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'BRAI4DQ em Produção — 6 Dimensões Ativas',desc:'Entrega no ambiente produtivo com disponibilidade, completude, consistência, integridade, unicidade e variação — habilitando Qualidade de Dados em Produtos, UCS e INGs.'},
      {tag:'s',tagLabel:'Estruturante',titulo:'4 Novas Dimensões (McKinsey) — Entrega Mai/26',desc:'Desenvolvimento de acurácia, validade, atualidade e tempestividade com implantação produtiva prevista para o próximo ciclo.'}
    ]},
    {id:'RB',nome:'Roberto Barboza Lima',role:'Product Owner · Produtos de Dados',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Integração Databricks × SharePoint Homologada',desc:'Conexão segura entre plataformas, reduzindo atividades manuais e garantindo rastreabilidade no fluxo de dados compartilhados.'},
      {tag:'p',tagLabel:'Produtos',titulo:'7 Produtos de Dados Entregues',desc:'Canais Digitais (R1) · Captação Líquida (R1) · Simulador de Métricas (R1) · Visão 360 (R2) · Carteiras Administradas (R2) · IA Generativa (R2) · Dados Socioeconômicos (R5).'},
      {tag:'i',tagLabel:'Iniciativa',titulo:'Workshop de Assessment para Tribos de Negócio',desc:'Federalização da qualidade de dados nas tribos, elevando maturidade em governança e reduzindo divergências no processo.'}
    ]},
    {id:'JR',nome:'Joabe da Silva Rufino',role:'Product Owner · Plataforma & Infra',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Descomissionamento On-Premises Informatica/SAS',desc:'Desativação de 10 tabelas Hive (Bureaus), 8 Teradata (CRM) e 4 SAS (Contadoria), acelerando migração para Databricks.'},
      {tag:'s',tagLabel:'Estruturante',titulo:'Control-M Jobs Databricks — AAQD Ativado',desc:'Habilitação da esteira Control-M via automação no Databricks com job de expurgo do Produto de Qualidade de Dados.'}
    ]},
    {id:'DS',nome:'Djan Paulo da Silva',role:'Product Owner · IA & Automação',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Agente DEVA em Produção — Plataforma Bridge',desc:'Deploy no Bridge (IA corporativa) e no Databricks com notebook interface compartilhado, viabilizando acesso democratizado ao agente.'},
      {tag:'o',tagLabel:'Eficiência',titulo:'Revisão do Ecossistema BRAI4DQ',desc:'Adequação de schemas, tabelas e modelo dimensional para conformidade com a nova solução, garantindo integridade operacional contínua.'}
    ]}
  ],
  releases:[
    {tipo:'new',nome:'Autenticação de Usuários — Canais Digitais',ver:'Release 1 · Primeiro ciclo de qualidade'},
    {tipo:'new',nome:'Captação Líquida',ver:'Release 1 · Ingresso em monitoramento'},
    {tipo:'new',nome:'Simulador de Métricas',ver:'Release 1 · Cobertura inicial estabelecida'},
    {tipo:'upd',nome:'Visão Clientes 360',ver:'Release 2 · Expansão de dimensões'},
    {tipo:'upd',nome:'Plataforma IA Generativa',ver:'Release 2 · Cobertura ampliada'},
    {tipo:'upd',nome:'Dados Públicos Socioeconômicos e Demográficos',ver:'Release 5 · Maturidade consolidada'}
  ]
};

const COLORS=['#CC0A2F','#2D5FE0','#00B87A','#E09F1A','#6B3FE0','#009DBF','#B83220','#1A7AE0','#00966A','#C07A10'];
const baseFont={family:"'DM Sans',sans-serif",size:11};
const gc=()=>getComputedStyle(document.documentElement).getPropertyValue('--g200').trim()||'#DDE0EA';
const tc=()=>getComputedStyle(document.documentElement).getPropertyValue('--g600').trim()||'#4E546E';
const chartInst={};
function kc(id){if(chartInst[id]){chartInst[id].destroy();delete chartInst[id];}}
function ctx(id){const e=document.getElementById(id);if(!e)return null;kc(id);return e.getContext('2d');}

// Active evol filters
let evolActive=new Set();

// ─── APPLY DATA TO DOM ───
function applyData(){
  // hero
  document.getElementById('heroSub').textContent=`Inteligência de Dados · Emitido em ${D.emitido}`;
  document.getElementById('hsStat1').textContent=D.kpis.score_medio+'%';
  document.getElementById('hsStat2').textContent=D.kpis.produtos;
  document.getElementById('hsStat3').textContent=D.kpis.entregas;
  document.getElementById('hsStat4').textContent=D.kpis.chamados;

  // KPIs
  document.getElementById('kpiBadge').textContent=D.periodo;
  const kmap=[
    ['kpiScore',D.kpis.score_medio+'%','kpiScoreDelta','▲ '+D.kpis.score_delta,'anaScore',D.analises.score],
    ['kpiProd',D.kpis.produtos,'kpiProdDelta','→ '+D.kpis.produtos_delta,'anaProd',D.analises.produtos],
    ['kpiEnt',D.kpis.entregas,'kpiEntDelta','▲ '+D.kpis.entregas_delta,'anaEnt',D.analises.entregas],
    ['kpiCham',D.kpis.chamados,'kpiChamDelta','▼ '+D.kpis.chamados_delta,'anaCham',D.analises.chamados]
  ];
  kmap.forEach(([vi,vv,di,dv,ai,av])=>{
    const ve=document.getElementById(vi);if(ve)ve.textContent=vv;
    const de=document.getElementById(di);if(de)de.textContent=dv;
    const ae=document.getElementById(ai);if(ae)ae.textContent=av;
  });

  // Analises textos
  const ana=[
    ['anaScoresText',D.analises.scores_destaques],['anaScoresAtencao',D.analises.scores_atencao],
    ['anaScoresInsight',D.analises.scores_insight],['anaStatusText',D.analises.status],
    ['anaDistText',D.analises.dist],['anaStatusInsight',D.analises.status_insight],
    ['anaDimText1',D.analises.dim1],['anaDimText2',D.analises.dim2],
    ['anaEvolText',D.analises.evol],['anaEvolVol',D.analises.evol_vol],
    ['anaEvolInsight',D.analises.evol_insight],['anaCausasText',D.analises.causas],
    ['anaCausasAcoes',D.analises.causas_acoes],['anaCausasInsight',D.analises.causas_insight],
    ['anaReleases',D.analises.releases]
  ];
  ana.forEach(([id,txt])=>{const e=document.getElementById(id);if(e)e.textContent=txt;});

  // POs
  const pg=document.getElementById('poGrid');
  if(pg) pg.innerHTML=D.pos.map(po=>`
    <div class="po-card">
      <div class="po-head"><div class="po-av">${po.id}</div><div><div class="po-name">${po.nome}</div><div class="po-role">${po.role}</div></div></div>
      <div class="po-body">${po.itens.map((it,i)=>`
        ${i>0?'<div class="po-sep"></div>':''}
        <div class="po-item"><div class="po-dot"></div><div>
          <span class="tag ${it.tag}">${it.tagLabel}</span>
          <div class="po-item-title">${it.titulo}</div>
          <div class="po-item-desc">${it.desc}</div>
        </div></div>
      `).join('')}</div>
    </div>`).join('');

  // Releases
  const rg=document.getElementById('releasesGrid');
  if(rg) rg.innerHTML=D.releases.map(r=>`
    <div class="rel-card ${r.tipo}"><span class="rel-badge">${r.tipo==='new'?'Novo':'Atualização'}</span>
    <div class="rel-name">${r.nome}</div><div class="rel-ver">${r.ver}</div></div>`).join('');

  // Dim bars
  const dl=document.getElementById('dimList');
  if(dl){
    const maxC=Math.max(...D.dimensoes.chamados,1);
    const maxO=Math.max(...D.dimensoes.ocorrencias,1);
    dl.innerHTML=D.dimensoes.labels.map((l,i)=>`
      <div style="margin-bottom:14px">
        <div style="font-size:.72rem;font-weight:700;color:var(--g700);margin-bottom:6px">${l}</div>
        <div class="dim-row" style="margin-bottom:4px">
          <div class="dim-name" style="font-size:.68rem;color:var(--g500)">Chamados</div>
          <div class="dim-bar-wrap"><div class="dim-bar-fill" style="width:${(D.dimensoes.chamados[i]/maxC*100).toFixed(1)}%;background:var(--pr)"></div></div>
          <div class="dim-val-label" style="color:var(--pr);font-size:.72rem;font-weight:700">${D.dimensoes.chamados[i]}</div>
        </div>
        <div class="dim-row">
          <div class="dim-name" style="font-size:.68rem;color:var(--g500)">Ocorrências</div>
          <div class="dim-bar-wrap"><div class="dim-bar-fill" style="width:${(D.dimensoes.ocorrencias[i]/maxO*100).toFixed(1)}%;background:var(--blue)"></div></div>
          <div class="dim-val-label" style="color:var(--blue);font-size:.72rem;font-weight:700">${D.dimensoes.ocorrencias[i]}</div>
        </div>
      </div>`).join('');
  }

  // Score table
  buildScoreTable();

  // Build evol filters
  buildEvolFilters();

  // Charts
  buildAllCharts();
}

function buildScoreTable(){
  const tb=document.getElementById('scoreTbody');if(!tb)return;
  tb.innerHTML=D.produtos.map(p=>{
    const a=p.scores[4],m=p.scores[3];
    const t=(!a||!m)?'—':(a>m?`<span style="color:var(--green);font-weight:700">▲</span>`:a<m?`<span style="color:var(--red);font-weight:700">▼</span>`:`<span style="color:var(--g500)">→</span>`);
    const sc=!a?'':a>=98?'gr':a>=90?'bl':a>=80?'ye':'rd';
    return `<tr><td><strong>${p.nome}</strong></td><td style="font-size:.74rem;color:var(--g500)">${p.gestor}</td>
    <td><span class="badge ${sc}">${a?a.toFixed(2)+'%':'—'}</span></td>
    <td style="font-size:.78rem">${m?m.toFixed(2)+'%':'—'}</td>
    <td style="text-align:center">${t}</td>
    <td style="font-size:.78rem">${p.chamados}</td>
    <td style="font-size:.72rem;color:var(--g500)">${p.dim}</td></tr>`;
  }).join('');
}

function buildEvolFilters(){
  const c=document.getElementById('evolFilters');if(!c)return;
  evolActive=new Set(D.produtos.map((_,i)=>i));
  c.innerHTML=D.produtos.map((p,i)=>`
    <div class="filter-chip act" data-idx="${i}" onclick="toggleEvol(${i},this)">
      ${p.nome.length>18?p.nome.slice(0,18)+'…':p.nome}
    </div>`).join('');
}

function toggleEvol(idx,el){
  if(evolActive.has(idx))evolActive.delete(idx); else evolActive.add(idx);
  el.classList.toggle('act',evolActive.has(idx));
  buildEvolucaoChart();
}

// ─── CHARTS ───
function buildAllCharts(){
  buildScoreChart();buildDistChart();buildStatusChart();
  buildDimensoesChart();buildEvolucaoChart();buildTendenciaChart();
  buildCausasChart();buildParetoChart();
}

function mkLegend(id,items){
  const el=document.getElementById(id);if(!el)return;
  el.innerHTML=items.map(it=>`<div class="cl-item"><div class="${it.shape||'cl-dot'}" style="background:${it.color}"></div>${it.label}</div>`).join('');
}

function buildScoreChart(){
  const c=ctx('cScores');if(!c)return;
  const labels=D.produtos.map(p=>p.nome.length>24?p.nome.slice(0,24)+'…':p.nome);
  const scores=D.produtos.map(p=>p.scores[4]||0);
  const colors=scores.map(s=>s>=98?'rgba(0,184,122,.78)':s>=90?'rgba(45,95,224,.75)':'rgba(204,10,47,.78)');
  chartInst['cScores']=new Chart(c,{
    type:'bar',
    data:{labels,datasets:[{label:'Score',data:scores,backgroundColor:colors,borderRadius:6,borderSkipped:false,barThickness:22}]},
    options:{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,layout:{padding:{right:20}},
      plugins:{
        legend:{display:false},
        datalabels:{
          anchor:'end',align:'end',
          formatter:v=>v>0?v.toFixed(2)+'%':'—',
          font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},
          color:tc(),padding:{left:4}
        }
      },
      scales:{
        x:{min:60,max:108,ticks:{callback:v=>v+'%',font:baseFont,color:tc()},grid:{color:gc()}},
        y:{ticks:{font:{...baseFont,size:10},color:tc()},grid:{display:false}}
      }
    }
  });
}

function buildDistChart(){
  const c=ctx('cDist');if(!c)return;
  const sc=D.produtos.map(p=>p.scores[4]||0).filter(s=>s>0);
  const counts=[sc.filter(s=>s>=98).length,sc.filter(s=>s>=90&&s<98).length,sc.filter(s=>s>=80&&s<90).length,sc.filter(s=>s<80).length];
  const clrs=['#00B87A','#2D5FE0','#E09F1A','#CC0A2F'];
  const lbls=['≥98% Excelente','90–98% Bom','80–90% Atenção','<80% Crítico'];
  chartInst['cDist']=new Chart(c,{
    type:'doughnut',
    data:{labels:lbls,datasets:[{data:counts,backgroundColor:clrs,borderWidth:0,hoverOffset:4}]},
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'60%',
      plugins:{
        legend:{display:false},
        datalabels:{
          formatter:(v,ctx)=>{
            const t=ctx.dataset.data.reduce((a,b)=>a+b,0);
            return v>0?v+'\n('+((v/t)*100).toFixed(0)+'%)':'';
          },
          color:'#fff',font:{weight:'700',size:10,family:"'DM Sans',sans-serif"},
          textAlign:'center'
        }
      }
    }
  });
  mkLegend('legDist',lbls.map((l,i)=>({label:`${l}: ${counts[i]}`,color:clrs[i]})));
}

function buildStatusChart(){
  const c=ctx('cStatus');if(!c)return;
  const {encerrados,analise,andamento}=D.statusChamados;
  const vals=[encerrados,analise||0,andamento||0];
  const lbls=['Encerrados','Em Análise','Em Andamento'];
  const clrs=['#00B87A','#E09F1A','#2D5FE0'];
  chartInst['cStatus']=new Chart(c,{
    type:'doughnut',
    data:{labels:lbls,datasets:[{data:vals,backgroundColor:clrs,borderWidth:0,hoverOffset:4}]},
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'60%',
      plugins:{
        legend:{display:false},
        datalabels:{
          formatter:(v,ctx)=>{
            const t=ctx.dataset.data.reduce((a,b)=>a+b,0);
            return v>0?v+'\n('+((v/t)*100).toFixed(0)+'%)':'';
          },
          color:'#fff',font:{weight:'700',size:10,family:"'DM Sans',sans-serif"},
          textAlign:'center'
        }
      }
    }
  });
  mkLegend('legStatus',lbls.map((l,i)=>({label:`${l}: ${vals[i]}`,color:clrs[i]})));
}

function buildDimensoesChart(){
  const c=ctx('cDimensoes');if(!c)return;
  chartInst['cDimensoes']=new Chart(c,{
    type:'bar',
    data:{
      labels:D.dimensoes.labels,
      datasets:[
        {label:'Chamados',data:D.dimensoes.chamados,backgroundColor:'rgba(204,10,47,.8)',borderRadius:4,borderSkipped:false},
        {label:'Ocorrências',data:D.dimensoes.ocorrencias,backgroundColor:'rgba(45,95,224,.65)',borderRadius:4,borderSkipped:false}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        datalabels:{
          anchor:'end',align:'end',
          formatter:v=>v,
          font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},
          color:tc()
        }
      },
      scales:{
        x:{ticks:{font:{...baseFont,size:10},color:tc()},grid:{display:false}},
        y:{ticks:{font:baseFont,color:tc()},grid:{color:gc()}}
      }
    }
  });
  mkLegend('legDim',[{label:'Chamados',color:'rgba(204,10,47,.8)'},{label:'Ocorrências',color:'rgba(45,95,224,.65)'}]);
}

function buildEvolucaoChart(){
  const c=ctx('cEvolucao');if(!c)return;
  const visible=D.produtos.filter((_,i)=>evolActive.has(i));
  const datasets=visible.map((p,ii)=>{
    const globalIdx=D.produtos.indexOf(p);
    return{
      label:p.nome.length>20?p.nome.slice(0,20)+'…':p.nome,
      data:p.scores,
      borderColor:COLORS[globalIdx%COLORS.length],
      backgroundColor:'transparent',
      borderWidth:2.5,pointRadius:5,pointHoverRadius:7,
      spanGaps:true,tension:.3,
      pointBackgroundColor:COLORS[globalIdx%COLORS.length]
    };
  });
  chartInst['cEvolucao']=new Chart(c,{
    type:'line',
    data:{labels:D.meses,datasets},
    options:{
      responsive:true,maintainAspectRatio:false,layout:{padding:{right:8,top:20}},
      plugins:{
        legend:{display:false},
        datalabels:{
          display:ctx=>ctx.dataset.data[ctx.dataIndex]!==null,
          formatter:v=>v!==null?v.toFixed(1)+'%':'',
          font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},
          color:ctx=>{const idx=D.produtos.indexOf(D.produtos[evolActive.values().next().value]);return COLORS[ctx.datasetIndex%COLORS.length];},
          align:'top',anchor:'end',offset:4,
          // avoid overlap: stagger by dataset index
          textAlign:'center'
        }
      },
      scales:{
        x:{ticks:{font:baseFont,color:tc()},grid:{color:gc()}},
        y:{min:60,max:105,ticks:{callback:v=>v+'%',font:baseFont,color:tc()},grid:{color:gc()}}
      }
    }
  });
  const leg=document.getElementById('legEvolucao');
  if(leg) leg.innerHTML=visible.map((p,ii)=>{
    const gi=D.produtos.indexOf(p);
    return`<div class="cl-item"><div class="cl-line" style="background:${COLORS[gi%COLORS.length]}"></div>${p.nome.length>20?p.nome.slice(0,20)+'…':p.nome}</div>`;
  }).join('');
}

function buildTendenciaChart(){
  const c=ctx('cTendencia');if(!c)return;
  chartInst['cTendencia']=new Chart(c,{
    type:'bar',
    data:{
      labels:D.meses,
      datasets:[
        {label:'Concluídos',data:D.tendencia.concluidos,backgroundColor:'rgba(0,184,122,.78)',borderRadius:5,borderSkipped:false},
        {label:'Abertos',data:D.tendencia.abertos,backgroundColor:'rgba(204,10,47,.65)',borderRadius:5,borderSkipped:false}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{
        legend:{display:false},
        datalabels:{
          anchor:'end',align:'end',
          formatter:v=>v>0?v:'',
          font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},
          color:tc()
        }
      },
      scales:{
        x:{ticks:{font:baseFont,color:tc()},grid:{display:false}},
        y:{ticks:{font:baseFont,color:tc()},grid:{color:gc()}}
      }
    }
  });
  mkLegend('legTend',[{label:'Concluídos',color:'rgba(0,184,122,.78)'},{label:'Abertos',color:'rgba(204,10,47,.65)'}]);
}

function buildCausasChart(){
  const c=ctx('cCausas');if(!c)return;
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i]}))].sort((a,b)=>b.v-a.v);
  const clrs=sorted.map((_,i)=>COLORS[i%COLORS.length]);
  chartInst['cCausas']=new Chart(c,{
    type:'bar',
    data:{labels:sorted.map(x=>x.l),datasets:[{data:sorted.map(x=>x.v),backgroundColor:clrs,borderRadius:5,borderSkipped:false}]},
    options:{
      responsive:true,maintainAspectRatio:false,layout:{padding:{top:20}},
      plugins:{
        legend:{display:false},
        datalabels:{
          anchor:'end',align:'end',
          formatter:(v,ctx)=>{
            const t=sorted.reduce((s,x)=>s+x.v,0);
            return v+' ('+((v/t)*100).toFixed(0)+'%)';
          },
          font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},
          color:tc()
        }
      },
      scales:{
        x:{ticks:{font:{...baseFont,size:9},color:tc(),maxRotation:30},grid:{display:false}},
        y:{ticks:{font:baseFont,color:tc()},grid:{color:gc()}}
      }
    }
  });
  mkLegend('legCausas',sorted.map((x,i)=>({label:`${x.l}: ${x.v}`,color:clrs[i]})));
}

function buildParetoChart(){
  const c=ctx('cPareto');if(!c)return;
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i]}))].sort((a,b)=>b.v-a.v);
  const total=sorted.reduce((s,x)=>s+x.v,0);
  let acc=0;
  const cum=sorted.map(x=>{acc+=x.v;return parseFloat((acc/total*100).toFixed(1));});
  chartInst['cPareto']=new Chart(c,{
    type:'bar',
    data:{
      labels:sorted.map(x=>x.l.length>10?x.l.slice(0,10)+'…':x.l),
      datasets:[
        {type:'bar',label:'Volume',data:sorted.map(x=>x.v),backgroundColor:'rgba(204,10,47,.75)',borderRadius:4,yAxisID:'y',datalabels:{
          anchor:'end',align:'end',formatter:v=>v,font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:tc()
        }},
        {type:'line',label:'% Acumulado',data:cum,borderColor:'#2D5FE0',borderWidth:2,pointRadius:4,fill:false,yAxisID:'y2',tension:.3,datalabels:{
          anchor:'end',align:'top',formatter:v=>v+'%',font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:'#2D5FE0',offset:4
        }}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,layout:{padding:{top:24}},
      plugins:{legend:{display:false}},
      scales:{
        x:{ticks:{font:{...baseFont,size:9},color:tc(),maxRotation:30},grid:{display:false}},
        y:{ticks:{font:baseFont,color:tc()},grid:{color:gc()}},
        y2:{position:'right',min:0,max:100,ticks:{callback:v=>v+'%',font:baseFont,color:tc()},grid:{display:false}}
      }
    }
  });
  mkLegend('legPareto',[{label:'Volume de chamados',color:'rgba(204,10,47,.75)'},{label:'% Acumulado',color:'#2D5FE0',shape:'cl-line'}]);
}

// ─── THEME ───
function toggleTheme(){
  const h=document.documentElement;
  h.dataset.theme=h.dataset.theme==='dark'?'light':'dark';
  localStorage.setItem('blt-theme',h.dataset.theme);
  setTimeout(buildAllCharts,100);
}
(()=>{const t=localStorage.getItem('blt-theme');if(t)document.documentElement.dataset.theme=t;})();

// ─── SCROLL REVEAL ───
const ro=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible');});},{threshold:.06});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));

// ─── IMPORT JSON ───
function openImport(){document.getElementById('importOverlay').classList.add('open');}
function closeImport(){document.getElementById('importOverlay').classList.remove('open');document.getElementById('importErr').textContent='';}
function previewSchema(e){
  e.preventDefault();
  const schema={periodo:"string",emitido:"string",kpis:{score_medio:"number",score_delta:"string",produtos:"number",entregas:"number",chamados:"number"},analises:{score:"string"},meses:["string"],produtos:[{nome:"string",gestor:"string",scores:["number|null"],chamados:"number",dim:"string"}]};
  document.getElementById('importJson').value=JSON.stringify(schema,null,2);
}
function applyJson(){
  const raw=document.getElementById('importJson').value.trim();
  const err=document.getElementById('importErr');
  try{
    const parsed=JSON.parse(raw);
    // Deep merge
    D=deepMerge(D,parsed);
    err.textContent='';
    closeImport();
    applyData();
  }catch(e){err.textContent='JSON inválido: '+e.message;}
}
function deepMerge(target,source){
  const out={...target};
  for(const k in source){
    if(source[k]&&typeof source[k]==='object'&&!Array.isArray(source[k])&&typeof target[k]==='object'&&!Array.isArray(target[k])){
      out[k]=deepMerge(target[k],source[k]);
    }else{out[k]=source[k];}
  }
  return out;
}

// ─── EMAIL EXPORT ───
function openExport(){
  const html=generateEmailHtml();
  const prev=document.getElementById('exportPreview');
  prev.innerHTML=`<iframe id="emailFrame" srcdoc="${html.replace(/"/g,'&quot;')}"></iframe>`;
  document.getElementById('exportOverlay').classList.add('open');
}
function closeExport(){document.getElementById('exportOverlay').classList.remove('open');}
function copyEmailHtml(){navigator.clipboard.writeText(generateEmailHtml()).then(()=>alert('HTML copiado!'));}
function downloadEmail(){
  const a=document.createElement('a');
  a.href='data:text/html;charset=utf-8,'+encodeURIComponent(generateEmailHtml());
  a.download=`boletim_email_${D.periodo.replace(/\s/g,'_')}.html`;a.click();
}

function generateEmailHtml(){
  const scores=D.produtos;
  const prodRows=scores.map((p,i)=>{
    const a=p.scores[4],m=p.scores[3];
    const t=(!a||!m)?'→':(a>m?'▲':'▼');
    const clr=!a?'#888':a>=98?'#00A868':a>=90?'#2D5FE0':a>=80?'#C07A10':'#CC0A2F';
    return `<tr style="background:${i%2===0?'#fff':'#F7F8FA'}"><td style="padding:9px 12px;font-size:12px;font-weight:700;color:#181C35;border-bottom:1px solid #E2E4EA">${p.nome}</td><td style="padding:9px 12px;font-size:11px;color:#7C829E;border-bottom:1px solid #E2E4EA">${p.gestor}</td><td style="padding:9px 12px;text-align:center;border-bottom:1px solid #E2E4EA"><span style="background:${clr}18;color:${clr};font-size:11px;font-weight:700;padding:2px 9px;border-radius:10px">${a?a.toFixed(2)+'%':'—'}</span></td><td style="padding:9px 12px;text-align:center;font-weight:700;color:${a&&m?(a>m?'#00A868':'#CC0A2F'):'#888'};border-bottom:1px solid #E2E4EA">${t}</td><td style="padding:9px 12px;text-align:center;font-size:11px;color:#7C829E;border-bottom:1px solid #E2E4EA">${p.chamados}</td></tr>`;
  }).join('');
  const poHtml=D.pos.map(po=>`
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #DDE0EA;border-radius:10px;margin-bottom:12px;overflow:hidden">
      <tr><td style="background:#ECEEF4;padding:12px 16px;border-bottom:1px solid #DDE0EA">
        <table cellpadding="0" cellspacing="0" border="0"><tr>
          <td style="width:36px"><div style="width:34px;height:34px;background:linear-gradient(135deg,#CC0A2F,#8C0020);border-radius:8px;text-align:center;line-height:34px;font-size:11px;font-weight:800;color:#fff">${po.id}</div></td>
          <td style="padding-left:10px"><div style="font-size:13px;font-weight:700;color:#181C35">${po.nome}</div><div style="font-size:10px;color:#7C829E">${po.role}</div></td>
        </tr></table>
      </td></tr>
      <tr><td style="padding:14px 16px">
        ${po.itens.map((it,i)=>`${i>0?'<div style="height:1px;background:#DDE0EA;margin:10px 0"></div>':''}<div style="font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#CC0A2F;background:rgba(204,10,47,.08);padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:5px">${it.tagLabel}</div><div style="font-size:12px;font-weight:700;color:#181C35;margin-bottom:3px">${it.titulo}</div><div style="font-size:11px;color:#4E546E;line-height:1.55">${it.desc}</div>`).join('')}
      </td></tr>
    </table>`).join('');

  return `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Boletim de Qualidade — ${D.periodo}</title></head><body style="margin:0;padding:0;background:#F2F3F7;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F2F3F7;padding:24px 0"><tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px">
  <tr><td style="background:linear-gradient(135deg,#8C0020,#CC0A2F);border-radius:14px 14px 0 0;padding:28px 36px 24px">
    <div style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:4px 14px;display:inline-block;font-size:10px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px">Qualidade de Dados · ${D.periodo}</div>
    <h1 style="font-size:24px;font-weight:800;color:#fff;margin:0 0 6px;line-height:1.1">Boletim de Qualidade de Dados</h1>
    <p style="font-size:12px;color:rgba(255,255,255,.65);margin:0">Referência: <strong style="color:#fff">${D.periodo}</strong> · Emitido em ${D.emitido}</p>
  </td></tr>
  <tr><td style="background:#CC0A2F;height:3px"></td></tr>
  <tr><td style="background:#fff;padding:24px 36px;border-bottom:1px solid #DDE0EA">
    <p style="font-size:9px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.08em;margin:0 0 14px">Resumo Executivo</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <td width="25%" style="padding-right:8px"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#F2F3F7;border-radius:10px;padding:14px;border-top:3px solid #CC0A2F">
        <div style="font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Score Médio</div>
        <div style="font-size:24px;font-weight:800;color:#CC0A2F;line-height:1">${D.kpis.score_medio}%</div>
        <div style="font-size:10px;color:#7C829E;margin-top:4px">▲ ${D.kpis.score_delta}</div>
      </td></tr></table></td>
      <td width="25%" style="padding:0 4px"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#F2F3F7;border-radius:10px;padding:14px;border-top:3px solid #00B87A">
        <div style="font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Produtos</div>
        <div style="font-size:24px;font-weight:800;color:#00A868;line-height:1">${D.kpis.produtos}</div>
        <div style="font-size:10px;color:#7C829E;margin-top:4px">Com SLA ativo</div>
      </td></tr></table></td>
      <td width="25%" style="padding:0 4px"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#F2F3F7;border-radius:10px;padding:14px;border-top:3px solid #2D5FE0">
        <div style="font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Entregas</div>
        <div style="font-size:24px;font-weight:800;color:#2D5FE0;line-height:1">${D.kpis.entregas}</div>
        <div style="font-size:10px;color:#7C829E;margin-top:4px">▲ ${D.kpis.entregas_delta}</div>
      </td></tr></table></td>
      <td width="25%" style="padding-left:8px"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#F2F3F7;border-radius:10px;padding:14px;border-top:3px solid #E09F1A">
        <div style="font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Chamados</div>
        <div style="font-size:24px;font-weight:800;color:#C07A10;line-height:1">${D.kpis.chamados}</div>
        <div style="font-size:10px;color:#7C829E;margin-top:4px">100% encerrados</div>
      </td></tr></table></td>
    </tr></table>
  </td></tr>
  <tr><td style="background:#fff;padding:20px 36px 8px;border-top:1px solid #DDE0EA">
    <p style="font-size:9px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.08em;margin:0 0 14px">Entregas dos Product Owners</p>
    ${poHtml}
  </td></tr>
  <tr><td style="background:#fff;padding:20px 36px;border-top:1px solid #DDE0EA">
    <p style="font-size:9px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.08em;margin:0 0 14px">Scores por Produto</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #DDE0EA;border-radius:8px;overflow:hidden">
      <tr style="background:#ECEEF4"><th style="padding:9px 12px;font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #DDE0EA">Produto</th><th style="padding:9px 12px;font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #DDE0EA">Gestor</th><th style="padding:9px 12px;font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #DDE0EA">Score Abr/26</th><th style="padding:9px 12px;font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #DDE0EA">Tend.</th><th style="padding:9px 12px;font-size:8px;font-weight:700;color:#7C829E;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #DDE0EA">Chamados</th></tr>
      ${prodRows}
    </table>
    <div style="margin-top:14px;background:rgba(204,10,47,.05);border-left:3px solid #CC0A2F;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#CC0A2F;margin-bottom:3px">Análise</div>
      <div style="font-size:11px;color:#4E546E;line-height:1.6;font-style:italic">${D.analises.scores_destaques}</div>
    </div>
  </td></tr>
  <tr><td style="background:linear-gradient(135deg,#8C0020,#CC0A2F);border-radius:0 0 14px 14px;padding:18px 36px">
    <div style="font-size:12px;font-weight:700;color:#fff">Boletim de Qualidade de Dados — ${D.periodo}</div>
    <div style="font-size:10px;color:rgba(255,255,255,.55);margin-top:2px">Gerado automaticamente · Inteligência de Dados / Bradesco</div>
  </td></tr>
</table></td></tr></table></body></html>`;
}

// ─── INIT ───
applyData();
</script>
</body>
</html>
