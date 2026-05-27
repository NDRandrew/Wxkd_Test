<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — Abril/2026</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<style>
:root{
  --pr:#CC0A2F; --pr-dk:#8C0020; --pr-lt:#E8153A; --pr-xlt:#FF4060;
  --wh:#FFFFFF; --bg:#F4F5F7; --bg2:#ECEEF2;
  --g100:#F0F1F4; --g200:#E2E4EA; --g300:#C8CBD6; --g500:#8A8FA8;
  --g600:#5C6180; --g700:#363B56; --g800:#1A1D2E; --g900:#0D0F1A;
  --green:#00C07A; --yellow:#F5A623; --red:#E8143A; --blue:#3B6BF5;
  --cyan:#00BFCF; --violet:#7C4DFF;
  --sh:0 2px 16px rgba(0,0,0,.07); --sh2:0 8px 40px rgba(0,0,0,.14);
  --r:10px; --r-lg:18px;
  --sb:220px; --sb-col:52px;
  --font-display:'Syne',sans-serif; --font-body:'DM Sans',sans-serif;
}
[data-theme="dark"]{
  --wh:#1A1D2E; --bg:#0D0F1A; --bg2:#151726;
  --g100:#1E2135; --g200:#252840; --g300:#363B56; --g500:#6B7194;
  --g600:#8A8FA8; --g700:#C8CBD6; --g800:#E2E4EA; --g900:#F4F5F7;
  --sh:0 2px 16px rgba(0,0,0,.3); --sh2:0 8px 40px rgba(0,0,0,.5);
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;font-family:var(--font-body);background:var(--bg);color:var(--g800)}
.app{display:flex;height:100vh;overflow:hidden}

/* ─── SIDEBAR ─── */
.sb{
  position:fixed;left:0;top:0;height:100vh;width:var(--sb);
  background:linear-gradient(160deg,var(--pr-dk) 0%,var(--pr) 60%,#7A001A 100%);
  color:#fff;padding:16px 10px 20px;z-index:200;
  display:flex;flex-direction:column;gap:3px;
  border-radius:0 24px 24px 0;
  box-shadow:6px 0 30px rgba(140,0,32,.35);
  transition:width .6s cubic-bezier(.77,0,.18,1);overflow:hidden;
}
.sb.col{width:var(--sb-col)}
.sb-head{display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;margin-bottom:8px;border-bottom:1px solid rgba(255,255,255,.15);transition:all .5s ease;gap:6px;}
.sb.col .sb-head{flex-direction:column-reverse;gap:10px;align-items:center}
.sb-logo{display:flex;align-items:center;gap:9px;white-space:nowrap;overflow:hidden}
.sb-logo-icon{width:30px;height:30px;background:rgba(255,255,255,.18);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--font-display);font-weight:800;font-size:.75rem;letter-spacing:-.02em;border:1px solid rgba(255,255,255,.25)}
.sb-logo-text{font-family:var(--font-display);font-size:.78rem;font-weight:700;line-height:1.2;opacity:.92;transition:opacity .2s,width .5s;white-space:nowrap}
.sb.col .sb-logo-text{opacity:0;width:0;overflow:hidden}
.sb-toggler{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:#fff;width:28px;height:28px;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .2s,transform .6s cubic-bezier(.77,0,.18,1)}
.sb-toggler:hover{background:rgba(255,255,255,.25)}
.sb.col .sb-toggler{transform:rotate(180deg)}
.nav-section-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;opacity:.45;padding:10px 8px 4px;white-space:nowrap;overflow:hidden;transition:opacity .2s}
.sb.col .nav-section-label{opacity:0}
.nav-btn{display:flex;align-items:center;gap:9px;color:rgba(255,255,255,.72);text-decoration:none;padding:8px 9px;border-radius:9px;font-weight:500;font-size:.82rem;cursor:pointer;transition:background .2s,color .2s;border:none;background:none;width:100%;text-align:left;white-space:nowrap;overflow:hidden;font-family:var(--font-body)}
.nav-btn:hover{background:rgba(255,255,255,.13);color:#fff}
.nav-btn.act{background:rgba(255,255,255,.22);color:#fff;font-weight:700}
.nav-btn svg{flex-shrink:0;opacity:.85;min-width:15px}
.nav-btn-label{transition:opacity .2s,max-width .5s;max-width:180px;overflow:hidden}
.sb.col .nav-btn-label{opacity:0;max-width:0}
.nav-sep{height:1px;background:rgba(255,255,255,.13);margin:5px 0;flex-shrink:0}
.sb-footer{margin-top:auto;background:rgba(0,0,0,.2);border-radius:10px;padding:10px 11px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;flex-shrink:0;white-space:nowrap;overflow:hidden;transition:all .6s}
.sb.col .sb-footer{flex-direction:column;gap:8px;padding:12px 6px}
.sb-footer-label{font-size:.72rem;font-weight:600;opacity:.7;transition:opacity .2s,max-width .5s;max-width:150px;overflow:hidden}
.sb.col .sb-footer-label{opacity:0;max-width:0}
.theme-track{width:38px;height:20px;background:rgba(255,255,255,.22);border-radius:10px;position:relative;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 5px}
.theme-thumb{width:14px;height:14px;background:#fff;border-radius:50%;position:absolute;left:3px;top:3px;transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:0 2px 4px rgba(0,0,0,.2)}
[data-theme="dark"] .theme-thumb{transform:translateX(18px)}

/* ─── MAIN ─── */
.main{margin-left:var(--sb);flex:1;height:100vh;overflow:hidden;position:relative;transition:margin-left .6s cubic-bezier(.77,0,.18,1)}
.main.col{margin-left:var(--sb-col)}
.page{position:absolute;inset:0;overflow-y:auto;opacity:0;visibility:hidden;transform:translateY(20px);transition:opacity .4s ease,transform .4s ease,visibility .4s}
.page.act{opacity:1;visibility:visible;transform:translateY(0)}

/* ─── HOME PAGE ─── */
.page-home{
  background:linear-gradient(135deg,var(--pr-dk) 0%,var(--pr) 45%,#A00025 100%);
  height:100%;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;
  padding:6% 8%;position:relative;overflow:hidden;
}
.home-blob{position:absolute;border-radius:50%;pointer-events:none}
.home-blob-1{width:52vw;height:52vw;right:-10%;top:-15%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 65%)}
.home-blob-2{width:30vw;height:30vw;left:30%;bottom:-10%;background:radial-gradient(ellipse,rgba(255,100,100,.08) 0%,transparent 65%)}
.home-blob-3{width:8vw;height:8vw;right:18%;top:20%;background:rgba(255,255,255,.06);border-radius:50%}
.home-grid-lines{position:absolute;inset:0;opacity:.04;background-image:linear-gradient(rgba(255,255,255,1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,1) 1px,transparent 1px);background-size:60px 60px;pointer-events:none}
.home-inner{position:relative;z-index:2;max-width:700px}
.home-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);border-radius:50px;padding:5px 14px 5px 8px;margin-bottom:28px}
.home-badge-dot{width:7px;height:7px;background:#FF6B8A;border-radius:50%;animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(255,107,138,.5)}50%{box-shadow:0 0 0 6px rgba(255,107,138,0)}}
.home-badge-text{font-size:.7rem;font-weight:700;color:rgba(255,255,255,.88);text-transform:uppercase;letter-spacing:.09em}
.home-h1{font-family:var(--font-display);font-size:clamp(2.6rem,5.5vw,4.4rem);font-weight:800;color:#fff;line-height:1.04;margin-bottom:14px;letter-spacing:-.02em}
.home-h1 em{font-style:normal;color:rgba(255,255,255,.55)}
.home-sub{font-size:clamp(.88rem,1.4vw,1.02rem);color:rgba(255,255,255,.72);line-height:1.7;margin-bottom:36px;max-width:540px}
.home-cta-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.btn-primary{background:#fff;color:var(--pr-dk);padding:13px 28px;border-radius:10px;font-size:.9rem;font-weight:700;border:none;cursor:pointer;font-family:var(--font-body);transition:transform .2s,box-shadow .2s;box-shadow:0 4px 20px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.btn-ghost{background:rgba(255,255,255,.13);color:#fff;padding:13px 24px;border-radius:10px;font-size:.9rem;font-weight:600;border:1px solid rgba(255,255,255,.28);cursor:pointer;font-family:var(--font-body);transition:background .2s;display:flex;align-items:center;gap:8px}
.btn-ghost:hover{background:rgba(255,255,255,.22)}
.home-stats{position:absolute;right:8%;bottom:14%;display:flex;flex-direction:column;gap:12px;z-index:2}
.home-stat-card{background:rgba(255,255,255,.11);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.18);border-radius:12px;padding:14px 20px;min-width:160px}
.home-stat-val{font-family:var(--font-display);font-size:1.7rem;font-weight:800;color:#fff;line-height:1}
.home-stat-lbl{font-size:.7rem;font-weight:600;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.home-bot-line{position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),rgba(255,255,255,.15),transparent)}

/* ─── BOLETIM PAGE ─── */
.page-boletim{background:var(--bg)}
.blt-header{
  background:linear-gradient(120deg,var(--pr-dk) 0%,var(--pr) 100%);
  padding:18px 36px;display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:50;box-shadow:0 4px 24px rgba(140,0,32,.25)
}
.blt-header-left h1{font-family:var(--font-display);font-size:1.05rem;font-weight:700;color:#fff;letter-spacing:-.01em}
.blt-header-left p{font-size:.72rem;color:rgba(255,255,255,.65);margin-top:2px}
.blt-header-right{display:flex;align-items:center;gap:10px}
.header-pill{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);border-radius:20px;padding:5px 12px;font-size:.7rem;font-weight:700;color:#fff;letter-spacing:.05em;text-transform:uppercase}
.blt-body{padding:28px 36px 56px;display:flex;flex-direction:column;gap:28px}

/* ─── SECTION HEADERS ─── */
.sec-hd{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.sec-hd-bar{width:4px;height:22px;border-radius:2px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));flex-shrink:0}
.sec-hd-title{font-family:var(--font-display);font-size:1.0rem;font-weight:700;color:var(--g800);letter-spacing:-.01em}
.sec-hd-sub{font-size:.75rem;color:var(--g500);margin-top:1px}
.sec-hd-badge{margin-left:auto;background:var(--pr);color:#fff;border-radius:20px;padding:3px 11px;font-size:.68rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}

/* ─── KPI STRIP ─── */
.kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:0}
.kpi-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:20px 22px;display:flex;flex-direction:column;gap:6px;box-shadow:var(--sh);transition:transform .22s,box-shadow .22s;position:relative;overflow:hidden}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:3px 3px 0 0;background:linear-gradient(90deg,var(--pr),var(--pr-lt))}
.kpi-card.green::before{background:linear-gradient(90deg,var(--green),#00E699)}
.kpi-card.blue::before{background:linear-gradient(90deg,var(--blue),var(--cyan))}
.kpi-card.yellow::before{background:linear-gradient(90deg,var(--yellow),#FFCA28)}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.kpi-lbl{font-size:.67rem;font-weight:700;color:var(--g500);text-transform:uppercase;letter-spacing:.06em}
.kpi-val{font-family:var(--font-display);font-size:2.1rem;font-weight:800;color:var(--pr);line-height:1}
.kpi-card.green .kpi-val{color:var(--green)}
.kpi-card.blue .kpi-val{color:var(--blue)}
.kpi-card.yellow .kpi-val{color:var(--yellow)}
.kpi-delta{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px}
.kpi-delta.up{background:rgba(0,192,122,.12);color:var(--green)}
.kpi-delta.down{background:rgba(232,20,58,.1);color:var(--red)}
.kpi-delta.neutral{background:var(--g100);color:var(--g600)}
.kpi-foot{font-size:.7rem;color:var(--g500)}

/* ─── ENTREGAS PO ─── */
.po-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.po-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh);transition:box-shadow .2s}
.po-card:hover{box-shadow:var(--sh2)}
.po-card-head{padding:14px 18px;display:flex;align-items:center;gap:10px;background:var(--g100);border-bottom:1px solid var(--g200)}
.po-avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--pr),var(--pr-dk));display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:.78rem;font-weight:800;color:#fff;flex-shrink:0;letter-spacing:-.01em}
.po-name{font-size:.85rem;font-weight:700;color:var(--g800)}
.po-role{font-size:.68rem;color:var(--g500);margin-top:1px}
.po-items{padding:16px 18px;display:flex;flex-direction:column;gap:10px}
.po-item{display:flex;gap:10px}
.po-item-dot{width:6px;height:6px;border-radius:50%;background:var(--pr);flex-shrink:0;margin-top:6px}
.po-item-content{}
.po-item-tag{display:inline-block;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:2px 7px;border-radius:4px;margin-bottom:4px}
.tag-struct{background:rgba(204,10,47,.1);color:var(--pr)}
.tag-init{background:rgba(59,107,245,.1);color:var(--blue)}
.tag-ops{background:rgba(0,192,122,.1);color:var(--green)}
.tag-prod{background:rgba(124,77,255,.1);color:var(--violet)}
.po-item-title{font-size:.82rem;font-weight:600;color:var(--g800);line-height:1.35;margin-bottom:2px}
.po-item-desc{font-size:.76rem;color:var(--g600);line-height:1.55}
.po-item-sep{height:1px;background:var(--g200)}

/* ─── CHARTS ─── */
.charts-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
.charts-grid.three{grid-template-columns:2fr 1fr}
.chart-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:22px;box-shadow:var(--sh)}
.chart-card.span2{grid-column:1/-1}
.chart-title{font-size:.82rem;font-weight:700;color:var(--g800);margin-bottom:2px}
.chart-sub{font-size:.7rem;color:var(--g500);margin-bottom:16px}
.chart-wrap{position:relative}
.chart-wrap.h220{height:220px}
.chart-wrap.h260{height:260px}
.chart-wrap.h300{height:300px}
.legend-row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px}
.legend-item{display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:500;color:var(--g600)}
.legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* ─── DADOS RELEVANTES TABLE ─── */
.dados-table{width:100%;border-collapse:collapse;font-size:.8rem}
.dados-table th{background:var(--g100);color:var(--g600);font-weight:700;font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;padding:10px 14px;text-align:left;border-bottom:2px solid var(--g200)}
.dados-table td{padding:10px 14px;border-bottom:1px solid var(--g200);color:var(--g700);vertical-align:middle}
.dados-table tr:last-child td{border-bottom:none}
.dados-table tr:hover td{background:var(--g100)}
.badge-score{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;font-weight:700;padding:3px 10px;border-radius:20px}
.score-great{background:rgba(0,192,122,.12);color:var(--green)}
.score-good{background:rgba(59,107,245,.1);color:var(--blue)}
.score-warn{background:rgba(245,166,35,.12);color:var(--yellow)}
.score-bad{background:rgba(232,20,58,.1);color:var(--red)}
.trend-arrow{font-size:.85rem}

/* ─── RADAR / DIMENSOES ─── */
.dim-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.dim-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r);padding:16px 18px;box-shadow:var(--sh);display:flex;flex-direction:column;gap:8px}
.dim-name{font-size:.72rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.06em}
.dim-bar-wrap{height:6px;background:var(--g200);border-radius:3px;overflow:hidden}
.dim-bar{height:100%;border-radius:3px;transition:width 1s cubic-bezier(.77,0,.18,1)}
.dim-val{font-family:var(--font-display);font-size:1.3rem;font-weight:800;color:var(--g800)}
.dim-chamados{font-size:.7rem;color:var(--g500)}

/* ─── RELEASES TABLE ─── */
.release-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.release-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r);padding:16px;box-shadow:var(--sh);position:relative;overflow:hidden}
.release-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;border-radius:0 0 var(--r) var(--r)}
.release-card.new::after{background:var(--green)}
.release-card.update::after{background:var(--blue)}
.release-badge{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;padding:2px 8px;border-radius:4px;display:inline-block;margin-bottom:8px}
.release-card.new .release-badge{background:rgba(0,192,122,.12);color:var(--green)}
.release-card.update .release-badge{background:rgba(59,107,245,.1);color:var(--blue)}
.release-name{font-size:.85rem;font-weight:700;color:var(--g800);margin-bottom:3px;line-height:1.3}
.release-ver{font-size:.7rem;color:var(--g500)}

/* ─── SCROLL REVEAL ─── */
.reveal{opacity:0;transform:translateY(22px);transition:opacity .5s ease,transform .5s ease}
.reveal.visible{opacity:1;transform:translateY(0)}

/* ─── SCROLLBAR ─── */
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--g300);border-radius:10px}

/* ─── TABS (Dados Relevantes) ─── */
.tabs-row{display:flex;gap:4px;margin-bottom:18px;background:var(--g100);border-radius:10px;padding:4px;width:fit-content}
.tab-btn{padding:7px 18px;border-radius:7px;border:none;background:none;font-family:var(--font-body);font-size:.8rem;font-weight:600;color:var(--g600);cursor:pointer;transition:all .2s}
.tab-btn.act{background:var(--wh);color:var(--pr);box-shadow:var(--sh)}
.tab-pane{display:none}
.tab-pane.act{display:block}

/* ─── TOOLTIP ─── */
.tooltip-wrap{position:relative;display:inline-flex;align-items:center;cursor:help}
.tooltip-wrap .tt{position:absolute;bottom:130%;left:50%;transform:translateX(-50%);background:var(--g800);color:#fff;font-size:.68rem;padding:5px 10px;border-radius:7px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .2s;z-index:100}
.tooltip-wrap:hover .tt{opacity:1}

/* ─── PROGRESS RING ─── */
.ring-wrap{display:flex;align-items:center;gap:14px}
.ring{transform:rotate(-90deg)}
.ring-bg{fill:none;stroke:var(--g200);stroke-width:6}
.ring-fill{fill:none;stroke-width:6;stroke-linecap:round;transition:stroke-dashoffset 1.2s cubic-bezier(.77,0,.18,1)}
.ring-label{text-align:center}
.ring-pct{font-family:var(--font-display);font-size:1.4rem;font-weight:800;color:var(--g800);display:block}
.ring-sub{font-size:.68rem;color:var(--g500)}

@media(max-width:1100px){
  .kpi-strip{grid-template-columns:repeat(2,1fr)}
  .po-grid{grid-template-columns:1fr}
  .charts-grid{grid-template-columns:1fr}
  .charts-grid.three{grid-template-columns:1fr}
  .dim-grid{grid-template-columns:repeat(2,1fr)}
  .release-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>
<div class="app">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sb" id="sb">
  <div class="sb-head">
    <div class="sb-logo">
      <div class="sb-logo-icon">DQ</div>
      <div class="sb-logo-text">Inteligência<br>de Dados</div>
    </div>
    <button class="sb-toggler" onclick="toggleSB()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
  </div>

  <div class="nav-section-label">Navegação</div>
  <button class="nav-btn act" id="nav-home" onclick="goPage('home')">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <span class="nav-btn-label">Início</span>
  </button>
  <button class="nav-btn" id="nav-blt" onclick="goPage('blt')">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    <span class="nav-btn-label">Boletim Abril/26</span>
  </button>
  <div class="nav-sep"></div>

  <div class="sb-footer" onclick="toggleTheme()">
    <span class="sb-footer-label">Modo escuro</span>
    <div class="theme-track">
      <div class="theme-thumb"></div>
    </div>
  </div>
</aside>

<!-- ═══ MAIN ═══ -->
<div class="main" id="main">

  <!-- ── HOME ── -->
  <div class="page act page-home" id="page-home">
    <div class="home-blob home-blob-1"></div>
    <div class="home-blob home-blob-2"></div>
    <div class="home-blob home-blob-3"></div>
    <div class="home-grid-lines"></div>

    <div class="home-inner">
      <div class="home-badge">
        <span class="home-badge-dot"></span>
        <span class="home-badge-text">Qualidade de Dados · Referência Abril 2026</span>
      </div>
      <h1 class="home-h1">Boletim de<br>Qualidade<br><em>de Dados</em></h1>
      <p class="home-sub">Visão consolidada das entregas, métricas e iniciativas da área de Qualidade de Dados. Navegue pelas seções para acompanhar o desempenho do mês de <strong style="color:#fff">Abril de 2026</strong>.</p>
      <div class="home-cta-row">
        <button class="btn-primary" onclick="goPage('blt')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Ver Boletim Completo
        </button>
        <button class="btn-ghost">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Sobre a área
        </button>
      </div>
    </div>

    <div class="home-stats">
      <div class="home-stat-card">
        <div class="home-stat-val">7</div>
        <div class="home-stat-lbl">Entregas no mês</div>
      </div>
      <div class="home-stat-card">
        <div class="home-stat-val">98,4%</div>
        <div class="home-stat-lbl">Score médio geral</div>
      </div>
      <div class="home-stat-card">
        <div class="home-stat-val">10</div>
        <div class="home-stat-lbl">Produtos monitorados</div>
      </div>
    </div>

    <div class="home-bot-line"></div>
  </div>

  <!-- ── BOLETIM ── -->
  <div class="page page-boletim" id="page-blt">
    <div class="blt-header">
      <div class="blt-header-left">
        <h1>Boletim de Qualidade de Dados</h1>
        <p>Referência: Abril de 2026 · Gerado em 27/05/2026</p>
      </div>
      <div class="blt-header-right">
        <span class="header-pill">Abril 2026</span>
      </div>
    </div>

    <div class="blt-body">

      <!-- ══ 1. KPIs EXECUTIVOS ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div>
            <div class="sec-hd-title">Resumo Executivo</div>
            <div class="sec-hd-sub">Indicadores-chave do período</div>
          </div>
          <span class="sec-hd-badge">Abril 2026</span>
        </div>
        <div class="kpi-strip">
          <div class="kpi-card">
            <div class="kpi-lbl">Score Médio Geral</div>
            <div class="kpi-val">98,4%</div>
            <div class="kpi-delta up">▲ +1,2 pp vs Mar</div>
            <div class="kpi-foot">Média ponderada por volume</div>
          </div>
          <div class="kpi-card green">
            <div class="kpi-lbl">Produtos Monitorados</div>
            <div class="kpi-val">10</div>
            <div class="kpi-delta neutral">→ Estável</div>
            <div class="kpi-foot">Produtos com SLA ativo</div>
          </div>
          <div class="kpi-card blue">
            <div class="kpi-lbl">Entregas Estruturantes</div>
            <div class="kpi-val">7</div>
            <div class="kpi-delta up">▲ +2 vs Mar</div>
            <div class="kpi-foot">Realizadas no ciclo</div>
          </div>
          <div class="kpi-card yellow">
            <div class="kpi-lbl">Chamados no Período</div>
            <div class="kpi-val">94</div>
            <div class="kpi-delta down">▼ -17 vs Mar</div>
            <div class="kpi-foot">Encerrados: 94 · Em aberto: 0</div>
          </div>
        </div>
      </section>

      <!-- ══ 2. ENTREGAS DOS POs ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div>
            <div class="sec-hd-title">Entregas dos Product Owners</div>
            <div class="sec-hd-sub">Contribuições por responsável no ciclo de Abril/2026</div>
          </div>
        </div>

        <div class="po-grid">

          <!-- DIOGO HORTA COSTA -->
          <div class="po-card">
            <div class="po-card-head">
              <div class="po-avatar">DH</div>
              <div>
                <div class="po-name">Diogo Horta Costa</div>
                <div class="po-role">Product Owner · BRAI4DQ</div>
              </div>
            </div>
            <div class="po-items">
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">BRAI4DQ em Produção — 6 Dimensões Ativas</div>
                  <div class="po-item-desc">Entrega da solução BRAI4DQ no ambiente produtivo com disponibilidade, completude, consistência, integridade, unicidade e variação — habilitando Qualidade de Dados em Produtos, UCS e INGs.</div>
                </div>
              </div>
              <div class="po-item-sep"></div>
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">4 Novas Dimensões em Desenvolvimento (McKinsey)</div>
                  <div class="po-item-desc">Desenvolvimento de acurácia, validade, atualidade e tempestividade para aplicação em casos reais, com implantação produtiva prevista para Mai/26.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- ROBERTO BARBOZA LIMA -->
          <div class="po-card">
            <div class="po-card-head">
              <div class="po-avatar">RB</div>
              <div>
                <div class="po-name">Roberto Barboza Lima</div>
                <div class="po-role">Product Owner · Produtos de Dados</div>
              </div>
            </div>
            <div class="po-items">
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">Integração Databricks × SharePoint Homologada</div>
                  <div class="po-item-desc">Conexão segura e aderente entre as plataformas, reduzindo atividades manuais e garantindo rastreabilidade no fluxo de dados compartilhados.</div>
                </div>
              </div>
              <div class="po-item-sep"></div>
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-prod">Produtos</span>
                  <div class="po-item-title">7 Produtos de Dados Entregues</div>
                  <div class="po-item-desc">Autenticação de Usuários · Canais Digitais (R1) · Captação Líquida (R1) · Simulador de Métricas (R1) · Visão Clientes 360 (R2) · Carteiras Administradas (R2) · Plataforma IA Generativa (R2) · Dados Socioeconômicos (R5).</div>
                </div>
              </div>
              <div class="po-item-sep"></div>
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-init">Iniciativa</span>
                  <div class="po-item-title">Workshop de Assessment para Tribos de Negócio</div>
                  <div class="po-item-desc">Capacitação dos focais das tribos para execução padronizada do processo, elevando a maturidade em governança de dados e reduzindo riscos de interpretações divergentes.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- JOABE DA SILVA RUFINO -->
          <div class="po-card">
            <div class="po-card-head">
              <div class="po-avatar">JR</div>
              <div>
                <div class="po-name">Joabe da Silva Rufino</div>
                <div class="po-role">Product Owner · Plataforma & Infra</div>
              </div>
            </div>
            <div class="po-items">
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">Descomissionamento On-Premises Informatica/SAS</div>
                  <div class="po-item-desc">Desativação de 10 tabelas Hive (Bureaus), 8 tabelas Teradata (CRM) e 4 tabelas SAS (Contadoria), acelerando a migração para a plataforma Databricks.</div>
                </div>
              </div>
              <div class="po-item-sep"></div>
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">Control-M Jobs Databricks — AAQD Ativado</div>
                  <div class="po-item-desc">Habilitação da esteira Control-M via automação no Databricks, incluindo ativização e job de expurgo do Produto de Qualidade de Dados.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- DJAN PAULO DA SILVA -->
          <div class="po-card">
            <div class="po-card-head">
              <div class="po-avatar">DS</div>
              <div>
                <div class="po-name">Djan Paulo da Silva</div>
                <div class="po-role">Product Owner · IA & Automação</div>
              </div>
            </div>
            <div class="po-items">
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-struct">Estruturante</span>
                  <div class="po-item-title">Agente DEVA em Produção na Plataforma Bridge</div>
                  <div class="po-item-desc">Deploy do agente de IA no ambiente Bridge (plataforma corporativa de IA generativa) e no exploratório e produção do Databricks, com notebook interface compartilhado com o time.</div>
                </div>
              </div>
              <div class="po-item-sep"></div>
              <div class="po-item">
                <div class="po-item-dot"></div>
                <div class="po-item-content">
                  <span class="po-item-tag tag-ops">Eficiência</span>
                  <div class="po-item-title">Revisão do Ecossistema BRAI4DQ</div>
                  <div class="po-item-desc">Adequação de schemas, tabelas e modelo dimensional para manter conformidade com a nova solução implantada, garantindo integridade operacional contínua.</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ══ 3. DADOS RELEVANTES ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div>
            <div class="sec-hd-title">Dados Relevantes</div>
            <div class="sec-hd-sub">Métricas, indicadores e análise de qualidade por produto</div>
          </div>
        </div>

        <div class="tabs-row">
          <button class="tab-btn act" onclick="switchTab('scores')">Scores por Produto</button>
          <button class="tab-btn" onclick="switchTab('dimensoes')">Dimensões</button>
          <button class="tab-btn" onclick="switchTab('evolucao')">Evolução Mensal</button>
          <button class="tab-btn" onclick="switchTab('causas')">Causas-Raiz</button>
        </div>

        <!-- Tab: SCORES POR PRODUTO -->
        <div class="tab-pane act" id="tab-scores">
          <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start">
            <div class="chart-card">
              <div class="chart-title">Score de Qualidade por Produto — Abril/26</div>
              <div class="chart-sub">Percentual de aderência ao SLA por produto de dados monitorado</div>
              <div class="chart-wrap h300"><canvas id="cScores"></canvas></div>
            </div>
            <div style="display:flex;flex-direction:column;gap:14px">
              <div class="chart-card">
                <div class="chart-title">Distribuição por Score</div>
                <div class="chart-sub">Faixas de desempenho</div>
                <div class="chart-wrap h130"><canvas id="cDist"></canvas></div>
              </div>
              <div class="chart-card">
                <div class="chart-title">Status dos Chamados</div>
                <div class="chart-sub">Encerrados vs. em análise</div>
                <div class="chart-wrap h130"><canvas id="cStatus"></canvas></div>
              </div>
            </div>
          </div>
          <div style="margin-top:16px">
            <div class="chart-card">
              <div class="chart-title">Tabela Consolidada — Produtos Monitorados</div>
              <div class="chart-sub">Visão detalhada do desempenho por produto no período</div>
              <table class="dados-table" style="margin-top:12px">
                <thead>
                  <tr>
                    <th>Produto</th>
                    <th>Gestor</th>
                    <th>Score Abr/26</th>
                    <th>Score Mar/26</th>
                    <th>Tendência</th>
                    <th>Chamados</th>
                    <th>Dimensão Crítica</th>
                  </tr>
                </thead>
                <tbody id="scoreTbody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab: DIMENSÕES -->
        <div class="tab-pane" id="tab-dimensoes">
          <div class="charts-grid" style="grid-template-columns:1fr 1fr;gap:16px">
            <div class="chart-card">
              <div class="chart-title">Chamados por Dimensão de Qualidade</div>
              <div class="chart-sub">Volume de ocorrências registradas por dimensão no período</div>
              <div class="chart-wrap h260"><canvas id="cDimensoes"></canvas></div>
            </div>
            <div class="chart-card">
              <div class="chart-title">Radar de Dimensões — Visão Consolidada</div>
              <div class="chart-sub">Cobertura e maturidade por dimensão (escala 0-100)</div>
              <div class="chart-wrap h260"><canvas id="cRadar"></canvas></div>
            </div>
          </div>
          <div class="dim-grid" style="margin-top:16px">
            <div class="dim-card">
              <div class="dim-name">Disponibilidade</div>
              <div class="dim-val">49</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:49%;background:var(--blue)"></div></div>
              <div class="dim-chamados">49 chamados · 65 ocorrências</div>
            </div>
            <div class="dim-card">
              <div class="dim-name">Completude</div>
              <div class="dim-val">33</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:35%;background:var(--pr)"></div></div>
              <div class="dim-chamados">33 chamados · 159 ocorrências</div>
            </div>
            <div class="dim-card">
              <div class="dim-name">Consistência</div>
              <div class="dim-val">30</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:32%;background:var(--violet)"></div></div>
              <div class="dim-chamados">30 chamados · 30 ocorrências</div>
            </div>
            <div class="dim-card">
              <div class="dim-name">Integridade</div>
              <div class="dim-val">8</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:9%;background:var(--yellow)"></div></div>
              <div class="dim-chamados">8 chamados · 8 ocorrências</div>
            </div>
            <div class="dim-card">
              <div class="dim-name">Unicidade</div>
              <div class="dim-val">1</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:2%;background:var(--cyan)"></div></div>
              <div class="dim-chamados">1 chamado · 43 ocorrências</div>
            </div>
            <div class="dim-card">
              <div class="dim-name">Variação</div>
              <div class="dim-val">—</div>
              <div class="dim-bar-wrap"><div class="dim-bar" style="width:0%;background:var(--green)"></div></div>
              <div class="dim-chamados">Em implantação · BRAI4DQ</div>
            </div>
          </div>
        </div>

        <!-- Tab: EVOLUÇÃO MENSAL -->
        <div class="tab-pane" id="tab-evolucao">
          <div class="chart-card">
            <div class="chart-title">Evolução do Score por Produto — Últimos 5 Meses</div>
            <div class="chart-sub">Trajetória de qualidade dos principais produtos monitorados (dez/25 → abr/26)</div>
            <div id="legEvolucao" class="legend-row" style="margin-bottom:14px"></div>
            <div class="chart-wrap h300"><canvas id="cEvolucao"></canvas></div>
          </div>
          <div style="margin-top:16px">
            <div class="chart-card">
              <div class="chart-title">Volume de Chamados — Tendência Mensal</div>
              <div class="chart-sub">Chamados abertos e encerrados por mês nos últimos 5 ciclos</div>
              <div class="chart-wrap h220"><canvas id="cTendencia"></canvas></div>
            </div>
          </div>
        </div>

        <!-- Tab: CAUSAS-RAIZ -->
        <div class="tab-pane" id="tab-causas">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="chart-card">
              <div class="chart-title">Causas-Raiz dos Chamados</div>
              <div class="chart-sub">Distribuição por categoria de causa no período</div>
              <div class="chart-wrap h260"><canvas id="cCausas"></canvas></div>
            </div>
            <div class="chart-card">
              <div class="chart-title">Análise de Pareto — Top Causas</div>
              <div class="chart-sub">Volume acumulado e frequência relativa</div>
              <div class="chart-wrap h260"><canvas id="cPareto"></canvas></div>
            </div>
          </div>
        </div>

      </section>

      <!-- ══ 4. RELEASES DE PRODUTOS ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div>
            <div class="sec-hd-title">Produtos de Dados — Releases Abril/26</div>
            <div class="sec-hd-sub">Novos produtos e atualizações de versão entregues no período</div>
          </div>
        </div>
        <div class="release-grid">
          <div class="release-card new">
            <span class="release-badge">Novo</span>
            <div class="release-name">Autenticação de Usuários — Canais Digitais</div>
            <div class="release-ver">Release 1 · Primeiro ciclo de qualidade</div>
          </div>
          <div class="release-card new">
            <span class="release-badge">Novo</span>
            <div class="release-name">Captação Líquida</div>
            <div class="release-ver">Release 1 · Ingresso em monitoramento</div>
          </div>
          <div class="release-card new">
            <span class="release-badge">Novo</span>
            <div class="release-name">Simulador de Métricas</div>
            <div class="release-ver">Release 1 · Cobertura inicial estabelecida</div>
          </div>
          <div class="release-card update">
            <span class="release-badge">Atualização</span>
            <div class="release-name">Visão Clientes 360</div>
            <div class="release-ver">Release 2 · Expansão de dimensões</div>
          </div>
          <div class="release-card update">
            <span class="release-badge">Atualização</span>
            <div class="release-name">Plataforma IA Generativa</div>
            <div class="release-ver">Release 2 · Cobertura ampliada</div>
          </div>
          <div class="release-card update">
            <span class="release-badge">Atualização</span>
            <div class="release-name">Dados Públicos Socioeconômicos e Demográficos</div>
            <div class="release-ver">Release 5 · Maturidade consolidada</div>
          </div>
        </div>
      </section>

    </div><!-- /blt-body -->
  </div><!-- /page-blt -->

</div><!-- /main -->
</div><!-- /app -->

<script>
// ═══ SINGLETON DATA ═══
const D = {
  meses: ['Dez/25','Jan/26','Fev/26','Mar/26','Abr/26'],
  produtos: [
    {nome:'OPEN FINANCE', gestor:'Augusto Vieira', scores:[92.06,91.49,98.73,94.3,95.98], chamados:81, dim:'Consistência'},
    {nome:'QUALIDADE DE DADOS', gestor:'Augusto Vieira', scores:[null,null,null,100,100], chamados:0, dim:'—'},
    {nome:'RATING DE RISCO PLDFT', gestor:'Deise Ferreira', scores:[null,64.0,85.52,74.58,80.0], chamados:2, dim:'Disponibilidade'},
    {nome:'ATENDIMENTO AO CLIENTE', gestor:'Deise Ferreira', scores:[89.95,98.89,99.61,99.64,99.77], chamados:5, dim:'Completude'},
    {nome:'COMUNICAÇÕES E ALERTAS PLDFT', gestor:'Deise Ferreira', scores:[93.53,99.24,100,100,99.65], chamados:2, dim:'Disponibilidade'},
    {nome:'PROCESSOS JURÍDICOS', gestor:'Deise Ferreira', scores:[94.44,100,100,99.91,99.73], chamados:4, dim:'Completude'},
    {nome:'MANIFESTAÇÕES SACL', gestor:'Deise Ferreira', scores:[99.73,100,100,100,100], chamados:0, dim:'—'},
    {nome:'SRM CALENDÁRIO FERIADOS', gestor:'Deise Ferreira', scores:[99.72,99.72,100,100,100], chamados:0, dim:'—'},
    {nome:'VISÃO CLIENTES 360', gestor:'Roberto Lima', scores:[null,null,97.2,98.1,99.2], chamados:0, dim:'—'},
    {nome:'IA GENERATIVA', gestor:'Roberto Lima', scores:[null,null,null,96.5,98.4], chamados:0, dim:'—'},
  ],
  dimensoes: {labels:['Disponibilidade','Completude','Consistência','Integridade','Unicidade'], chamados:[49,33,30,8,1], ocorrencias:[65,159,30,8,43]},
  causas: {labels:['Open Finance','Alt. Estrutura','Preenchimento Incor.','Diversas','Origem Indisponível','Não Informado','Externo'], data:[29,34,19,1,3,7,1]},
  tendencia: {abertos:[0,0,0,0,0], concluidos:[31,111,55,64,30]},
  statusChamados: {encerrados:94, analise:0, andamento:0}
};

const COLORS = ['#CC0A2F','#3B6BF5','#00C07A','#F5A623','#7C4DFF','#00BFCF','#E8143A','#059669','#D97706','#2563EB'];

// ═══ NAV ═══
function goPage(id){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('act'));
  document.querySelectorAll('.nav-btn').forEach(b=>b.classList.remove('act'));
  document.getElementById('page-'+id).classList.add('act');
  document.getElementById('nav-'+id).classList.add('act');
  if(id==='blt') initCharts();
}

function toggleSB(){
  const sb=document.getElementById('sb');
  const main=document.getElementById('main');
  sb.classList.toggle('col');
  main.classList.toggle('col');
}

// ═══ THEME ═══
function toggleTheme(){
  const html=document.documentElement;
  html.dataset.theme = html.dataset.theme==='dark' ? 'light' : 'dark';
  localStorage.setItem('theme',html.dataset.theme);
}
(()=>{const t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t;})();

// ═══ TABS ═══
function switchTab(id){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('act'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('act'));
  document.getElementById('tab-'+id).classList.add('act');
  event.target.classList.add('act');
  setTimeout(()=>initCharts(),50);
}

// ═══ SCROLL REVEAL ═══
const ro = new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('visible'); });
},{threshold:0.08});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));

// ═══ CHARTS ═══
let chartsInit = false;
const chartInstances = {};

function destroyChart(id){
  if(chartInstances[id]){chartInstances[id].destroy();delete chartInstances[id];}
}

function initCharts(){
  buildScoreChart();
  buildDistChart();
  buildStatusChart();
  buildDimensoesChart();
  buildRadarChart();
  buildEvolucaoChart();
  buildTendenciaChart();
  buildCausasChart();
  buildParetoChart();
  buildScoreTable();
}

function getCtx(id){
  const el=document.getElementById(id);
  if(!el) return null;
  destroyChart(id);
  return el.getContext('2d');
}

const baseFont={family:"'DM Sans', sans-serif",size:11};
const gridColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g200').trim()||'#E2E4EA';
const textColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g600').trim()||'#5C6180';

function buildScoreChart(){
  const ctx=getCtx('cScores'); if(!ctx) return;
  const labels=D.produtos.map(p=>p.nome.length>22?p.nome.slice(0,22)+'…':p.nome);
  const scores=D.produtos.map(p=>p.scores[4]||0);
  chartInstances['cScores']=new Chart(ctx,{
    type:'bar',
    data:{labels,datasets:[{
      label:'Score Abr/26',
      data:scores,
      backgroundColor:scores.map(s=>s>=98?'rgba(0,192,122,.75)':s>=90?'rgba(59,107,245,.75)':'rgba(204,10,47,.75)'),
      borderRadius:6,borderSkipped:false,barThickness:20
    }]},
    options:{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},datalabels:{anchor:'end',align:'end',font:{size:10,weight:'700'},
        formatter:v=>v?v.toFixed(1)+'%':'—',color:textColor}},
      scales:{
        x:{min:60,max:102,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{ticks:{font:{...baseFont,size:10},color:textColor},grid:{display:false}}
      }
    }
  });
}

function buildDistChart(){
  const ctx=getCtx('cDist'); if(!ctx) return;
  const scores=D.produtos.map(p=>p.scores[4]||0).filter(s=>s>0);
  const great=scores.filter(s=>s>=98).length;
  const good=scores.filter(s=>s>=90&&s<98).length;
  const warn=scores.filter(s=>s>=80&&s<90).length;
  const bad=scores.filter(s=>s<80).length;
  chartInstances['cDist']=new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:['≥98% Excelente','90-98% Bom','80-90% Atenção','<80% Crítico'],
      datasets:[{data:[great,good,warn,bad],backgroundColor:['#00C07A','#3B6BF5','#F5A623','#CC0A2F'],borderWidth:2,borderColor:'transparent'}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'65%',
      plugins:{legend:{position:'right',labels:{font:{size:9},boxWidth:10,padding:8}},datalabels:{display:false}}
    }
  });
}

function buildStatusChart(){
  const ctx=getCtx('cStatus'); if(!ctx) return;
  const {encerrados,analise,andamento}=D.statusChamados;
  chartInstances['cStatus']=new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:['Encerrados','Em Análise','Em Andamento'],
      datasets:[{data:[encerrados,analise||0,andamento||0],backgroundColor:['#00C07A','#F5A623','#3B6BF5'],borderWidth:2,borderColor:'transparent'}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'65%',
      plugins:{legend:{position:'right',labels:{font:{size:9},boxWidth:10,padding:8}},datalabels:{display:false}}
    }
  });
}

function buildDimensoesChart(){
  const ctx=getCtx('cDimensoes'); if(!ctx) return;
  chartInstances['cDimensoes']=new Chart(ctx,{
    type:'bar',
    data:{
      labels:D.dimensoes.labels,
      datasets:[
        {label:'Chamados',data:D.dimensoes.chamados,backgroundColor:'rgba(204,10,47,.75)',borderRadius:5,borderSkipped:false},
        {label:'Ocorrências',data:D.dimensoes.ocorrencias,backgroundColor:'rgba(59,107,245,.55)',borderRadius:5,borderSkipped:false}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:baseFont,color:textColor}},datalabels:{display:false}},
      scales:{
        x:{ticks:{font:{...baseFont,size:10},color:textColor},grid:{display:false}},
        y:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()}}
      }
    }
  });
}

function buildRadarChart(){
  const ctx=getCtx('cRadar'); if(!ctx) return;
  const pcts=D.dimensoes.chamados.map((c,i)=>{
    const total=D.dimensoes.chamados.reduce((a,b)=>a+b,0);
    return parseFloat((100-(c/total*100)).toFixed(1));
  });
  chartInstances['cRadar']=new Chart(ctx,{
    type:'radar',
    data:{
      labels:D.dimensoes.labels,
      datasets:[{
        label:'Maturidade por Dimensão',
        data:pcts,
        backgroundColor:'rgba(204,10,47,.12)',
        borderColor:'rgba(204,10,47,.8)',
        pointBackgroundColor:'#CC0A2F',
        pointRadius:4,borderWidth:2
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},datalabels:{display:false}},
      scales:{r:{min:0,max:100,ticks:{stepSize:25,font:{size:9}},pointLabels:{font:{size:10},color:textColor}}}
    }
  });
}

function buildEvolucaoChart(){
  const ctx=getCtx('cEvolucao'); if(!ctx) return;
  const top=D.produtos.filter(p=>p.scores.some(s=>s!==null)).slice(0,6);
  const datasets=top.map((p,i)=>({
    label:p.nome.length>20?p.nome.slice(0,20)+'…':p.nome,
    data:p.scores,
    borderColor:COLORS[i],backgroundColor:'transparent',
    borderWidth:2,pointRadius:4,pointHoverRadius:6,
    spanGaps:true,tension:.35
  }));
  // Legend
  const leg=document.getElementById('legEvolucao');
  if(leg){leg.innerHTML=top.map((p,i)=>`<div class="legend-item"><div class="legend-dot" style="background:${COLORS[i]}"></div>${p.nome.length>20?p.nome.slice(0,20)+'…':p.nome}</div>`).join('');}
  chartInstances['cEvolucao']=new Chart(ctx,{
    type:'line',
    data:{labels:D.meses,datasets},
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},datalabels:{display:false}},
      scales:{
        x:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{min:60,max:102,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{color:gridColor()}}
      }
    }
  });
}

function buildTendenciaChart(){
  const ctx=getCtx('cTendencia'); if(!ctx) return;
  chartInstances['cTendencia']=new Chart(ctx,{
    type:'bar',
    data:{
      labels:D.meses,
      datasets:[
        {label:'Concluídos',data:D.tendencia.concluidos,backgroundColor:'rgba(0,192,122,.75)',borderRadius:5,borderSkipped:false},
        {label:'Abertos',data:D.tendencia.abertos,backgroundColor:'rgba(204,10,47,.6)',borderRadius:5,borderSkipped:false}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:baseFont,color:textColor}},datalabels:{display:false}},
      scales:{
        x:{ticks:{font:baseFont,color:textColor},grid:{display:false}},
        y:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()},stacked:false}
      }
    }
  });
}

function buildCausasChart(){
  const ctx=getCtx('cCausas'); if(!ctx) return;
  chartInstances['cCausas']=new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:D.causas.labels,
      datasets:[{data:D.causas.data,backgroundColor:COLORS,borderWidth:2,borderColor:'transparent'}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,cutout:'50%',
      plugins:{
        legend:{position:'right',labels:{font:{size:10},boxWidth:12,padding:10}},
        datalabels:{
          formatter:(v,ctx)=>{const t=ctx.dataset.data.reduce((a,b)=>a+b,0);return v/t>0.05?(v/t*100).toFixed(0)+'%':'';},
          color:'#fff',font:{weight:'700',size:10}
        }
      }
    }
  });
}

function buildParetoChart(){
  const ctx=getCtx('cPareto'); if(!ctx) return;
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i]}))].sort((a,b)=>b.v-a.v);
  const total=sorted.reduce((s,x)=>s+x.v,0);
  let acc=0;
  const cumPct=sorted.map(x=>{acc+=x.v;return parseFloat((acc/total*100).toFixed(1));});
  chartInstances['cPareto']=new Chart(ctx,{
    type:'bar',
    data:{
      labels:sorted.map(x=>x.l.length>12?x.l.slice(0,12)+'…':x.l),
      datasets:[
        {type:'bar',label:'Volume',data:sorted.map(x=>x.v),backgroundColor:'rgba(204,10,47,.72)',borderRadius:5,yAxisID:'y'},
        {type:'line',label:'% Acumulado',data:cumPct,borderColor:'#3B6BF5',borderWidth:2,pointRadius:3,fill:false,yAxisID:'y2',tension:.3}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{labels:{font:baseFont,color:textColor}},datalabels:{display:false}},
      scales:{
        x:{ticks:{font:{...baseFont,size:9},color:textColor},grid:{display:false}},
        y:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()}},
        y2:{position:'right',min:0,max:100,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{display:false}}
      }
    }
  });
}

function buildScoreTable(){
  const tbody=document.getElementById('scoreTbody');
  if(!tbody) return;
  tbody.innerHTML=D.produtos.map(p=>{
    const abr=p.scores[4], mar=p.scores[3];
    const trend=(!abr||!mar)?'—':(abr>mar?'<span class="trend-arrow" style="color:var(--green)">▲</span>':abr<mar?'<span class="trend-arrow" style="color:var(--red)">▼</span>':'<span style="color:var(--g500)">→</span>');
    const scoreClass=!abr?'':abr>=98?'score-great':abr>=90?'score-good':abr>=80?'score-warn':'score-bad';
    return `<tr>
      <td><strong>${p.nome}</strong></td>
      <td style="font-size:.75rem;color:var(--g600)">${p.gestor}</td>
      <td><span class="badge-score ${scoreClass}">${abr?abr.toFixed(2)+'%':'—'}</span></td>
      <td style="font-size:.78rem">${mar?mar.toFixed(2)+'%':'—'}</td>
      <td style="text-align:center">${trend}</td>
      <td style="font-size:.78rem">${p.chamados}</td>
      <td style="font-size:.72rem;color:var(--g500)">${p.dim}</td>
    </tr>`;
  }).join('');
}

// ═══ REGISTER DATALABELS ═══
Chart.register(ChartDataLabels);
</script>
</body>
</html>
