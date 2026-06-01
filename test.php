<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — v2 (Split 3:4)</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<style>
:root{
  --pr:#CC0A2F;--pr-dk:#8C0020;--pr-lt:#E8153A;
  --wh:#FFFFFF;--bg:#F0F1F6;
  --sidebar-bg:#FAFBFF;--sidebar-border:#E0E3EF;
  --g100:#ECEEF4;--g200:#D8DBE9;--g300:#B8BDCF;--g500:#7278A0;
  --g600:#454C72;--g700:#252C50;--g800:#141930;
  --green:#00B87A;--yellow:#D99B18;--red:#D41C35;--blue:#2456D8;
  --cyan:#007BB8;--violet:#5A35CC;
  --sh:0 1px 8px rgba(0,0,0,.06);--sh2:0 4px 24px rgba(0,0,0,.12);
  --r:10px;--r-lg:14px;
  --ff-h:'Syne',sans-serif;--ff-b:'DM Sans',sans-serif;
  --col-main:4fr;--col-side:3fr;
}
[data-theme="dark"]{
  --wh:#1A1E38;--bg:#0E1020;
  --sidebar-bg:#141828;--sidebar-border:#252D50;
  --g100:#1C2040;--g200:#242850;--g300:#30386A;--g500:#6068A0;
  --g600:#8890C8;--g700:#C0C8EF;--g800:#E0E8FF;
  --sh:0 1px 8px rgba(0,0,0,.3);--sh2:0 4px 24px rgba(0,0,0,.5);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--ff-b);background:var(--bg);color:var(--g800);min-height:100vh}

/* ─── TOP NAV ─── */
.topnav{
  position:sticky;top:0;z-index:200;
  background:linear-gradient(135deg,var(--pr-dk),var(--pr));
  box-shadow:0 2px 16px rgba(140,0,32,.28);
  height:52px;padding:0 36px;
  display:flex;align-items:center;justify-content:space-between;gap:16px;
}
.tn-brand{display:flex;align-items:center;gap:10px;flex-shrink:0}
.tn-logo{width:30px;height:30px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);border-radius:7px;display:flex;align-items:center;justify-content:center;font-family:var(--ff-h);font-size:.7rem;font-weight:800;color:#fff}
.tn-name{font-family:var(--ff-h);font-size:.85rem;font-weight:700;color:#fff;line-height:1.15}
.tn-sub{font-size:.62rem;color:rgba(255,255,255,.58)}
.tn-pills{display:flex;gap:5px;flex:1;justify-content:center;overflow:hidden}
.np{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:4px 12px;font-size:.7rem;font-weight:600;color:rgba(255,255,255,.72);cursor:pointer;text-decoration:none;transition:all .18s;white-space:nowrap}
.np:hover,.np.act{background:rgba(255,255,255,.22);color:#fff;border-color:rgba(255,255,255,.35)}
.tn-actions{display:flex;gap:7px;flex-shrink:0}
.tbtn{padding:5px 12px;border-radius:7px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;font-family:var(--ff-b);display:flex;align-items:center;gap:5px;transition:all .18s;white-space:nowrap}
.tbtn.g{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.22)}
.tbtn.g:hover{background:rgba(255,255,255,.22)}
.tbtn.w{background:#fff;color:var(--pr-dk)}
.tbtn.w:hover{transform:translateY(-1px);box-shadow:0 3px 12px rgba(0,0,0,.18)}

/* ─── IMPORT / EXPORT OVERLAY ─── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:500;display:none;align-items:center;justify-content:center;padding:16px}
.overlay.open{display:flex}
.ov-panel{background:var(--wh);border-radius:var(--r-lg);padding:28px;width:100%;max-width:620px;box-shadow:var(--sh2)}
.ov-panel h2{font-family:var(--ff-h);font-size:1rem;font-weight:700;color:var(--g800);margin-bottom:5px}
.ov-panel p{font-size:.78rem;color:var(--g500);margin-bottom:14px;line-height:1.5}
.ov-ta{width:100%;height:190px;border:1px solid var(--g200);border-radius:var(--r);padding:10px;font-size:.73rem;font-family:monospace;background:var(--g100);color:var(--g800);resize:vertical;outline:none}
.ov-ta:focus{border-color:var(--pr)}
.ov-row{display:flex;gap:8px;margin-top:12px;align-items:center}
.ov-err{font-size:.73rem;color:var(--red);flex:1}
.obtn{padding:8px 18px;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer;border:none;font-family:var(--ff-b)}
.obtn.c{background:var(--g100);color:var(--g600)}
.obtn.a{background:var(--pr);color:#fff}
.obtn.a:hover{background:var(--pr-dk)}
.exp-preview{border:1px solid var(--g200);border-radius:var(--r);overflow:hidden;margin-bottom:14px;height:320px}
.exp-preview iframe{width:100%;height:100%;border:none}
.exp-row{display:flex;gap:8px;justify-content:flex-end}

/* ─── HERO ─── */
.hero{
  background:linear-gradient(128deg,var(--pr-dk) 0%,var(--pr) 50%,#A50022 100%);
  padding:32px 36px 28px;position:relative;overflow:hidden;
}
.hero-noise{position:absolute;inset:0;opacity:.03;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");pointer-events:none}
.hero-circle{position:absolute;right:-5%;top:-30%;width:46%;padding-top:46%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 65%);pointer-events:none}
.hero-inner{position:relative;z-index:2;display:flex;justify-content:space-between;align-items:center;gap:24px}
.hero-left .hero-eyebrow{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.18);border-radius:16px;padding:3px 12px 3px 7px;margin-bottom:12px}
.hero-dot{width:6px;height:6px;border-radius:50%;background:#FF8FAC;animation:hpulse 2s infinite}
@keyframes hpulse{0%,100%{box-shadow:0 0 0 0 rgba(255,143,172,.5)}50%{box-shadow:0 0 0 5px rgba(255,143,172,0)}}
.hero-eyebrow-txt{font-size:.67rem;font-weight:700;color:rgba(255,255,255,.84);text-transform:uppercase;letter-spacing:.09em}
.hero-h1{font-family:var(--ff-h);font-size:clamp(1.5rem,2.8vw,2.3rem);font-weight:800;color:#fff;line-height:1.1;letter-spacing:-.02em;margin-bottom:6px}
.hero-sub{font-size:.83rem;color:rgba(255,255,255,.62)}
.hero-right{display:flex;gap:12px;flex-shrink:0}
.hstat{background:rgba(255,255,255,.11);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.18);border-radius:10px;padding:14px 20px;text-align:center;min-width:108px}
.hstat-val{font-family:var(--ff-h);font-size:1.6rem;font-weight:800;color:#fff;line-height:1}
.hstat-lbl{font-size:.63rem;font-weight:700;color:rgba(255,255,255,.58);text-transform:uppercase;letter-spacing:.06em;margin-top:4px}
.hero-line{position:absolute;bottom:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.32),transparent)}

/* ─── SPLIT LAYOUT ─── */
/* sticky side panel + scrolling main */
.split-page{display:grid;grid-template-columns:var(--col-main) var(--col-side);gap:0;min-height:calc(100vh - 52px)}
.split-main{padding:28px 28px 56px 36px;overflow-y:auto;display:flex;flex-direction:column;gap:24px}
.split-side{
  position:sticky;top:52px;height:calc(100vh - 52px);
  background:var(--sidebar-bg);
  border-left:1px solid var(--sidebar-border);
  overflow-y:auto;
  padding:24px 22px 40px;
  display:flex;flex-direction:column;gap:0;
}

/* ─── SIDE PANEL CONTENT ─── */
.side-header{margin-bottom:20px}
.side-title{font-family:var(--ff-h);font-size:.85rem;font-weight:700;color:var(--g800);margin-bottom:3px;display:flex;align-items:center;gap:8px}
.side-title-icon{width:24px;height:24px;background:linear-gradient(135deg,var(--pr),var(--pr-dk));border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.side-desc{font-size:.72rem;color:var(--g500);line-height:1.5}
.side-sep{height:1px;background:var(--sidebar-border);margin:16px 0}

/* Analysis blocks in sidebar */
.ana-block{padding:14px;background:var(--g100);border-radius:var(--r);margin-bottom:12px;border-left:3px solid var(--pr)}
.ana-block.green{border-left-color:var(--green)}
.ana-block.blue{border-left-color:var(--blue)}
.ana-block.yellow{border-left-color:var(--yellow)}
.ana-section-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--pr);margin-bottom:4px}
.ana-block.green .ana-section-label{color:var(--green)}
.ana-block.blue .ana-section-label{color:var(--blue)}
.ana-block.yellow .ana-section-label{color:var(--yellow)}
.ana-text{font-size:.77rem;color:var(--g600);line-height:1.65;font-style:italic}
.ana-insight{background:rgba(204,10,47,.06);border:1px solid rgba(204,10,47,.14);border-radius:8px;padding:10px 12px;margin-bottom:12px}
.ana-insight p{font-size:.75rem;color:var(--g700);line-height:1.55}
.side-nav{display:flex;flex-direction:column;gap:4px;margin-bottom:16px}
.side-nav-btn{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;font-size:.76rem;font-weight:500;color:var(--g500);cursor:pointer;transition:all .18s;text-decoration:none;border:none;background:none;width:100%;text-align:left;font-family:var(--ff-b)}
.side-nav-btn:hover{background:var(--g200);color:var(--g800)}
.side-nav-btn.act{background:rgba(204,10,47,.1);color:var(--pr);font-weight:700}
.side-nav-btn svg{flex-shrink:0;opacity:.7}

/* ─── SECTION ─── */
.section{display:flex;flex-direction:column;gap:14px}
.sec-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
.sec-hd-l{display:flex;gap:10px;align-items:flex-start}
.sec-bar{width:4px;min-height:24px;border-radius:2px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));flex-shrink:0;margin-top:2px}
.sec-ttl{font-family:var(--ff-h);font-size:.95rem;font-weight:700;color:var(--g800);letter-spacing:-.01em}
.sec-sub{font-size:.72rem;color:var(--g500);margin-top:1px}
.sec-badge{background:var(--pr);color:#fff;border-radius:14px;padding:2px 11px;font-size:.65rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;flex-shrink:0;align-self:flex-start}

/* ─── KPI GRID ─── */
.kpi-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.kc{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:18px 20px;box-shadow:var(--sh);position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s}
.kc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:3px 3px 0 0}
.kc.cr::before{background:linear-gradient(90deg,var(--pr),var(--pr-lt))}
.kc.cg::before{background:linear-gradient(90deg,var(--green),#00E090)}
.kc.cb::before{background:linear-gradient(90deg,var(--blue),var(--cyan))}
.kc.cy::before{background:linear-gradient(90deg,var(--yellow),#FFD060)}
.kc:hover{transform:translateY(-2px);box-shadow:var(--sh2)}
.kc-lbl{font-size:.64rem;font-weight:700;color:var(--g500);text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px}
.kc-val{font-family:var(--ff-h);font-size:2rem;font-weight:800;line-height:1;margin-bottom:5px}
.kc.cr .kc-val{color:var(--pr)}.kc.cg .kc-val{color:var(--green)}.kc.cb .kc-val{color:var(--blue)}.kc.cy .kc-val{color:var(--yellow)}
.kc-delta{display:inline-flex;align-items:center;gap:3px;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:10px}
.kc-delta.u{background:rgba(0,184,122,.12);color:var(--green)}
.kc-delta.d{background:rgba(204,10,47,.1);color:var(--red)}
.kc-delta.n{background:var(--g100);color:var(--g500)}
.kc-foot{font-size:.66rem;color:var(--g500);margin-top:3px}

/* ─── CHART CARD ─── */
.cc{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:20px;box-shadow:var(--sh)}
.cc-top{margin-bottom:12px}
.cc-ttl{font-size:.83rem;font-weight:700;color:var(--g800);margin-bottom:1px}
.cc-sub{font-size:.69rem;color:var(--g500)}
.cw{position:relative}
.cw.h200{height:200px}.cw.h240{height:240px}.cw.h280{height:280px}.cw.h320{height:320px}.cw.h360{height:360px}
.chart-legend{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;padding-top:10px;border-top:1px solid var(--g200)}
.cl-i{display:flex;align-items:center;gap:5px;font-size:.7rem;font-weight:500;color:var(--g500)}
.cl-d{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.cl-ln{width:14px;height:3px;border-radius:2px;flex-shrink:0}

/* filter chips */
.chips{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px}
.chip{padding:3px 10px;border-radius:14px;font-size:.68rem;font-weight:600;cursor:pointer;border:1px solid var(--g200);background:var(--g100);color:var(--g500);transition:all .18s;user-select:none}
.chip.act{background:var(--pr);color:#fff;border-color:var(--pr)}

/* ─── PO CARDS ─── */
.po-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
.poc{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh)}
.poc-hd{background:var(--g100);border-bottom:1px solid var(--g200);padding:12px 16px;display:flex;align-items:center;gap:10px}
.poc-av{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,var(--pr),var(--pr-dk));display:flex;align-items:center;justify-content:center;font-family:var(--ff-h);font-weight:800;font-size:.75rem;color:#fff;flex-shrink:0}
.poc-nm{font-size:.83rem;font-weight:700;color:var(--g800)}
.poc-rl{font-size:.66rem;color:var(--g500);margin-top:1px}
.poc-body{padding:14px 16px;display:flex;flex-direction:column;gap:10px}
.poc-item{display:flex;gap:8px}
.poc-dot{width:5px;height:5px;border-radius:50%;background:var(--pr);flex-shrink:0;margin-top:6px}
.tag{display:inline-block;font-size:.57rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:2px 6px;border-radius:3px;margin-bottom:3px}
.tag.s{background:rgba(204,10,47,.1);color:var(--pr)}
.tag.i{background:rgba(36,86,216,.1);color:var(--blue)}
.tag.o{background:rgba(0,184,122,.1);color:var(--green)}
.tag.p{background:rgba(90,53,204,.1);color:var(--violet)}
.poc-t{font-size:.8rem;font-weight:600;color:var(--g800);line-height:1.3;margin-bottom:2px}
.poc-d{font-size:.73rem;color:var(--g500);line-height:1.55}
.poc-sep{height:1px;background:var(--g200)}

/* ─── SCORE TABLE ─── */
.stbl{width:100%;border-collapse:collapse;font-size:.78rem}
.stbl th{background:var(--g100);color:var(--g500);font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;padding:9px 12px;text-align:left;border-bottom:2px solid var(--g200)}
.stbl td{padding:9px 12px;border-bottom:1px solid var(--g200);color:var(--g600);vertical-align:middle}
.stbl tr:last-child td{border-bottom:none}
.stbl tr:hover td{background:var(--g100)}
.sbadge{display:inline-flex;align-items:center;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:12px}
.sbadge.gr{background:rgba(0,184,122,.12);color:var(--green)}
.sbadge.bl{background:rgba(36,86,216,.1);color:var(--blue)}
.sbadge.ye{background:rgba(217,155,24,.12);color:var(--yellow)}
.sbadge.rd{background:rgba(212,28,53,.1);color:var(--red)}

/* ─── DIM BARS ─── */
.dim-list{display:flex;flex-direction:column;gap:14px}
.dim-g{display:flex;flex-direction:column;gap:5px}
.dim-lbl{font-size:.72rem;font-weight:700;color:var(--g700)}
.dim-row{display:flex;align-items:center;gap:10px}
.dim-sub{font-size:.65rem;color:var(--g500);width:72px;flex-shrink:0}
.dim-bw{flex:1;height:7px;background:var(--g200);border-radius:4px;overflow:visible;position:relative}
.dim-bf{height:100%;border-radius:4px;transition:width 1s cubic-bezier(.77,0,.18,1)}
.dim-num{font-size:.7rem;font-weight:700;width:36px;flex-shrink:0;text-align:right}

/* ─── RELEASES ─── */
.rel-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.rc{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r);padding:14px;box-shadow:var(--sh);border-bottom:3px solid transparent}
.rc.nw{border-bottom-color:var(--green)}.rc.up{border-bottom-color:var(--blue)}
.rc-badge{font-size:.58rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;padding:2px 7px;border-radius:3px;display:inline-block;margin-bottom:6px}
.rc.nw .rc-badge{background:rgba(0,184,122,.12);color:var(--green)}
.rc.up .rc-badge{background:rgba(36,86,216,.1);color:var(--blue)}
.rc-nm{font-size:.8rem;font-weight:700;color:var(--g800);line-height:1.3;margin-bottom:2px}
.rc-ver{font-size:.67rem;color:var(--g500)}

/* ─── SCROLL REVEAL ─── */
.reveal{opacity:0;transform:translateY(16px);transition:opacity .45s ease,transform .45s ease}
.reveal.visible{opacity:1;transform:none}

/* ─── SCROLLBAR ─── */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--g300);border-radius:10px}
</style>
</head>
<body>

<!-- ══ TOP NAV ══ -->
<nav class="topnav">
  <div class="tn-brand">
    <div class="tn-logo">DQ</div>
    <div><div class="tn-name">Boletim de Qualidade de Dados</div><div class="tn-sub">Inteligência de Dados · Bradesco</div></div>
  </div>
  <div class="tn-pills">
    <a class="np act" href="#kpis">Resumo</a>
    <a class="np" href="#entregas">Entregas</a>
    <a class="np" href="#dados">Scores</a>
    <a class="np" href="#dimensoes">Dimensões</a>
    <a class="np" href="#evolucao">Evolução</a>
    <a class="np" href="#causas-sec">Causas</a>
    <a class="np" href="#releases">Releases</a>
  </div>
  <div class="tn-actions">
    <button class="tbtn g" onclick="openImport()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      Importar JSON
    </button>
    <button class="tbtn w" onclick="openExport()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
      Gerar E-mail
    </button>
    <button class="tbtn g" onclick="toggleTheme()" title="Tema" style="padding:5px 8px">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
  </div>
</nav>

<!-- ══ IMPORT ══ -->
<div class="overlay" id="impOv">
  <div class="ov-panel">
    <h2>Importar dados via JSON</h2>
    <p>Cole um objeto JSON com as chaves do boletim. Campos não encontrados mantêm os valores padrão. <a href="#" onclick="previewSchema(event)" style="color:var(--pr)">Ver schema</a>.</p>
    <textarea class="ov-ta" id="impJson" placeholder='{"periodo":"Abril 2026","kpis":{"score_medio":98.4},...}'></textarea>
    <div class="ov-row">
      <span class="ov-err" id="impErr"></span>
      <button class="obtn c" onclick="closeImport()">Cancelar</button>
      <button class="obtn a" onclick="applyJson()">Aplicar</button>
    </div>
  </div>
</div>

<!-- ══ EXPORT ══ -->
<div class="overlay" id="expOv">
  <div class="ov-panel" style="max-width:720px">
    <h2>Gerar versão para E-mail</h2>
    <p>HTML compatível com clientes de e-mail. Copie ou faça download.</p>
    <div class="exp-preview" id="expPreview"></div>
    <div class="exp-row">
      <button class="obtn c" onclick="closeExport()">Fechar</button>
      <button class="obtn a" onclick="copyEmailHtml()">Copiar HTML</button>
      <button class="obtn a" onclick="downloadEmail()">Download .html</button>
    </div>
  </div>
</div>

<!-- ══ HERO ══ -->
<div class="hero">
  <div class="hero-noise"></div>
  <div class="hero-circle"></div>
  <div class="hero-inner">
    <div class="hero-left">
      <div class="hero-eyebrow"><span class="hero-dot"></span><span class="hero-eyebrow-txt">Referência: Abril 2026</span></div>
      <h1 class="hero-h1">Boletim de Qualidade de Dados</h1>
      <p class="hero-sub" id="heroSub">Inteligência de Dados · Emitido em 27/05/2026</p>
    </div>
    <div class="hero-right" id="heroRight">
      <div class="hstat"><div class="hstat-val" id="hs1">98,4%</div><div class="hstat-lbl">Score Médio</div></div>
      <div class="hstat"><div class="hstat-val" id="hs2">10</div><div class="hstat-lbl">Produtos</div></div>
      <div class="hstat"><div class="hstat-val" id="hs3">7</div><div class="hstat-lbl">Entregas</div></div>
      <div class="hstat"><div class="hstat-val" id="hs4">94</div><div class="hstat-lbl">Chamados</div></div>
    </div>
  </div>
  <div class="hero-line"></div>
</div>

<!-- ══ SPLIT LAYOUT ══ -->
<div class="split-page">

  <!-- ═══ MAIN COLUMN ═══ -->
  <div class="split-main" id="splitMain">

    <!-- KPIs -->
    <section class="section reveal" id="kpis">
      <div class="sec-hd">
        <div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Resumo Executivo</div><div class="sec-sub">Indicadores-chave do período</div></div></div>
        <span class="sec-badge" id="kpiBadge">Abril 2026</span>
      </div>
      <div class="kpi-grid">
        <div class="kc cr"><div class="kc-lbl">Score Médio Geral</div><div class="kc-val" id="kpiScore">98,4%</div><div class="kc-delta u" id="kpiScoreDelta">▲ +1,2 pp vs Mar</div><div class="kc-foot">Média ponderada por volume</div></div>
        <div class="kc cg"><div class="kc-lbl">Produtos Monitorados</div><div class="kc-val" id="kpiProd">10</div><div class="kc-delta n" id="kpiProdDelta">→ Estável</div><div class="kc-foot">Com SLA ativo</div></div>
        <div class="kc cb"><div class="kc-lbl">Entregas Estruturantes</div><div class="kc-val" id="kpiEnt">7</div><div class="kc-delta u" id="kpiEntDelta">▲ +2 vs Mar</div><div class="kc-foot">Realizadas no ciclo</div></div>
        <div class="kc cy"><div class="kc-lbl">Chamados no Período</div><div class="kc-val" id="kpiCham">94</div><div class="kc-delta d" id="kpiChamDelta">▼ −17 vs Mar</div><div class="kc-foot">100% encerrados</div></div>
      </div>
    </section>

    <!-- Entregas POs -->
    <section class="section reveal" id="entregas">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Entregas dos Product Owners</div><div class="sec-sub">Contribuições por responsável</div></div></div></div>
      <div class="po-grid" id="poGrid"></div>
    </section>

    <!-- Scores -->
    <section class="section reveal" id="dados">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Score por Produto</div><div class="sec-sub">Aderência ao SLA — Abril/26</div></div></div></div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Score de Qualidade por Produto</div><div class="cc-sub">Verde ≥98% · Azul ≥90% · Vermelho &lt;90% · valores explícitos</div></div>
        <div class="cw h360"><canvas id="cScores"></canvas></div>
      </div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Tabela Consolidada</div><div class="cc-sub">Score atual, tendência, chamados e dimensão crítica</div></div>
        <table class="stbl" style="margin-top:8px"><thead><tr><th>Produto</th><th>Gestor</th><th>Score Abr/26</th><th>Mar/26</th><th>Tend.</th><th>Chamados</th><th>Dim. Crítica</th></tr></thead><tbody id="scoreTbody"></tbody></table>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="cc">
          <div class="cc-top"><div class="cc-ttl">Distribuição por Faixa</div><div class="cc-sub">Quantidade de produtos por nível</div></div>
          <div class="cw h200"><canvas id="cDist"></canvas></div>
          <div class="chart-legend" id="legDist"></div>
        </div>
        <div class="cc">
          <div class="cc-top"><div class="cc-ttl">Status dos Chamados</div><div class="cc-sub">Encerrados vs. em análise</div></div>
          <div class="cw h200"><canvas id="cStatus"></canvas></div>
          <div class="chart-legend" id="legStatus"></div>
        </div>
      </div>
    </section>

    <!-- Dimensões -->
    <section class="section reveal" id="dimensoes">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Dimensões de Qualidade</div><div class="sec-sub">Volume de chamados e ocorrências</div></div></div></div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Chamados e Ocorrências por Dimensão</div><div class="cc-sub">Vermelho = chamados · Azul = ocorrências · valores explícitos nas barras</div></div>
        <div class="cw h260"><canvas id="cDimensoes"></canvas></div>
        <div class="chart-legend" id="legDim"></div>
      </div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Mapa de Dimensões</div><div class="cc-sub">Indicador visual de intensidade por dimensão</div></div>
        <div class="dim-list" id="dimList" style="margin-top:6px"></div>
      </div>
    </section>

    <!-- Evolução -->
    <section class="section reveal" id="evolucao">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Evolução Mensal</div><div class="sec-sub">Score e volume de chamados — últimos 5 meses</div></div></div></div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Evolução do Score por Produto — Dez/25 a Abr/26</div><div class="cc-sub">Use os filtros para selecionar produtos · valores explícitos em cada ponto</div></div>
        <div class="chips" id="evolFilters"></div>
        <div class="cw h320"><canvas id="cEvolucao"></canvas></div>
        <div class="chart-legend" id="legEvolucao"></div>
      </div>
      <div class="cc">
        <div class="cc-top"><div class="cc-ttl">Volume de Chamados — Tendência</div><div class="cc-sub">Concluídos e em aberto por ciclo · valores explícitos</div></div>
        <div class="cw h240"><canvas id="cTendencia"></canvas></div>
        <div class="chart-legend" id="legTend"></div>
      </div>
    </section>

    <!-- Causas -->
    <section class="section reveal" id="causas-sec">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Análise de Causas-Raiz</div><div class="sec-sub">Pareto das causas de chamados no período</div></div></div></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="cc">
          <div class="cc-top"><div class="cc-ttl">Distribuição de Causas</div><div class="cc-sub">Volume e % por causa — valores explícitos</div></div>
          <div class="cw h260"><canvas id="cCausas"></canvas></div>
          <div class="chart-legend" id="legCausas"></div>
        </div>
        <div class="cc">
          <div class="cc-top"><div class="cc-ttl">Análise de Pareto</div><div class="cc-sub">Volume (barras) + % acumulado (linha) · valores explícitos</div></div>
          <div class="cw h260"><canvas id="cPareto"></canvas></div>
          <div class="chart-legend" id="legPareto"></div>
        </div>
      </div>
    </section>

    <!-- Releases -->
    <section class="section reveal" id="releases">
      <div class="sec-hd"><div class="sec-hd-l"><div class="sec-bar"></div><div><div class="sec-ttl">Releases — Abril/26</div><div class="sec-sub">Novos produtos e versões entregues</div></div></div></div>
      <div class="rel-grid" id="releasesGrid"></div>
    </section>

  </div><!-- /split-main -->

  <!-- ═══ SIDE PANEL ═══ -->
  <aside class="split-side" id="splitSide">
    <div class="side-header">
      <div class="side-title">
        <div class="side-title-icon"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
        Painel de Análises
      </div>
      <p class="side-desc">Interpretações executivas de cada seção do boletim. Acompanhe as análises ao lado do conteúdo correspondente.</p>
    </div>

    <!-- Nav interna do painel -->
    <div class="side-nav">
      <button class="side-nav-btn act" onclick="scrollTo('kpis',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="6" height="6" rx="1"/><rect x="10" y="3" width="6" height="6" rx="1"/><rect x="18" y="3" width="4" height="6" rx="1"/><rect x="2" y="11" width="4" height="6" rx="1"/><rect x="8" y="11" width="10" height="6" rx="1"/></svg>Resumo Executivo</button>
      <button class="side-nav-btn" onclick="scrollTo('entregas',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Entregas POs</button>
      <button class="side-nav-btn" onclick="scrollTo('dados',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Scores</button>
      <button class="side-nav-btn" onclick="scrollTo('dimensoes',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>Dimensões</button>
      <button class="side-nav-btn" onclick="scrollTo('evolucao',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Evolução</button>
      <button class="side-nav-btn" onclick="scrollTo('causas-sec',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Causas-Raiz</button>
      <button class="side-nav-btn" onclick="scrollTo('releases',this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Releases</button>
    </div>

    <div class="side-sep"></div>

    <!-- ── Análises por seção ── -->
    <div id="ana-kpis">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">📊 Resumo Executivo</div>
      <div class="ana-block"><div class="ana-section-label">Score Médio</div><div class="ana-text" id="aKpiScore">Score 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização do Open Finance e entrada de novos produtos com alta baseline.</div></div>
      <div class="ana-block green"><div class="ana-section-label">Produtos</div><div class="ana-text" id="aKpiProd">Base monitorada estável. 3 novos produtos em Release 1 ampliaram cobertura sem reduzir qualidade da carteira existente.</div></div>
      <div class="ana-block blue"><div class="ana-section-label">Entregas</div><div class="ana-text" id="aKpiEnt">Destaque para BRAI4DQ em produção e Agente DEVA na Bridge — impacto direto na maturidade técnica da área.</div></div>
      <div class="ana-block yellow"><div class="ana-section-label">Chamados</div><div class="ana-text" id="aKpiCham">Redução de 15% no volume. Taxa de encerramento 100% — resultado consistente com as melhorias de processo de Mar/26.</div></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-entregas" style="margin-bottom:0">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">📋 Entregas dos POs</div>
      <div class="ana-block"><div class="ana-section-label">Destaques</div><div class="ana-text" id="aPos">Diogo entregou BRAI4DQ completo com 6 dimensões produtivas e McKinsey avançando com 4 novas. Roberto homologou integração crítica e entregou 7 produtos. Joabe descomissionou ambiente legado acelerando migração. Djan deployou Agente DEVA em produção.</div></div>
      <div class="ana-insight"><p id="aPosInsight">💡 A combinação de descomissionamento legado + BRAI4DQ marca uma virada estrutural na plataforma de qualidade.</p></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-dados">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">📈 Scores por Produto</div>
      <div class="ana-block"><div class="ana-section-label">Destaques</div><div class="ana-text" id="aScores">7 dos 10 produtos atingiram score ≥99%. Open Finance mantém desempenho consistente acima de 95%. Novos produtos entraram com scores saudáveis.</div></div>
      <div class="ana-block"><div class="ana-section-label" style="color:var(--red)">Atenção</div><div class="ana-text" id="aScoresAtencao">Rating de Risco PLDFT (80,0%) em recuperação desde Jan/26 (64,0%). Plano de ação em andamento com foco em Disponibilidade.</div></div>
      <div class="ana-block green"><div class="ana-section-label">Chamados</div><div class="ana-text" id="aStatus">94 chamados, todos encerrados. Zero em aberto — SLA 100% cumprido no período.</div></div>
      <div class="ana-insight"><p id="aScoresInsight">💡 Priorizar revisão do fluxo de Disponibilidade no PLDFT para o ciclo de Mai/26.</p></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-dimensoes">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">🔷 Dimensões</div>
      <div class="ana-block"><div class="ana-section-label">Vetor Principal</div><div class="ana-text" id="aDim1">Disponibilidade lidera em chamados (49). Completude tem maior volume de ocorrências (159) — sinal de problema recorrente no mesmo produto.</div></div>
      <div class="ana-block yellow"><div class="ana-section-label">Prioridade</div><div class="ana-text" id="aDim2">Relação ocorrências/chamado na Completude é 4,8x. Exige investigação de causa-raiz — pode ser problema sistêmico, não pontual.</div></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-evolucao">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">📉 Evolução</div>
      <div class="ana-block"><div class="ana-section-label">Tendências</div><div class="ana-text" id="aEvol">Carteira em trajetória ascendente. PLDFT em recuperação. SACL e Feriados em 100% por 3 meses consecutivos. Produtos maduros dominam a carteira.</div></div>
      <div class="ana-block blue"><div class="ana-section-label">Volume</div><div class="ana-text" id="aEvolVol">Chamados concluídos reduziram 53% vs. pico Jan/26 (111). Tendência de normalização confirmada.</div></div>
      <div class="ana-insight"><p id="aEvolInsight">💡 Open Finance atingiu 98,7% em Fev/26 e recuou para 95,98% em Abr/26. Investigar causa da regressão.</p></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-causas">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">🔍 Causas-Raiz</div>
      <div class="ana-block"><div class="ana-section-label">Concentração</div><div class="ana-text" id="aCausas">As 2 principais causas concentram 67% dos chamados. Ações preventivas teriam impacto rápido no volume total.</div></div>
      <div class="ana-block green"><div class="ana-section-label">Ações Previstas</div><div class="ana-text" id="aCausasAcoes">Preenchimento Incorreto (19 chamados) pode ser reduzido com capacitação direcionada. Diversas e Externo são residuais.</div></div>
      <div class="ana-insight"><p id="aCausasInsight">💡 63% dos chamados de Alteração na Estrutura estão em 2 produtos. Mapear para mitigação sistêmica.</p></div>
    </div>

    <div class="side-sep"></div>
    <div id="ana-releases">
      <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--g500);margin-bottom:10px">📦 Releases</div>
      <div class="ana-block"><div class="ana-section-label">Contexto</div><div class="ana-text" id="aReleases">3 novos produtos em Release 1 ampliam cobertura. Todos iniciaram com scores acima de 96%. IA Generativa (R2) e Dados Socioeconômicos (R5) consolidam maturidade avançada.</div></div>
    </div>

  </aside>

</div><!-- /split-page -->

<script>
Chart.register(ChartDataLabels);

// ═══ SINGLETON ═══
let D = {
  periodo:"Abril 2026",emitido:"27/05/2026",
  kpis:{score_medio:98.4,score_delta:"+1,2 pp vs Mar",score_delta_type:"u",produtos:10,produtos_delta:"Estável",produtos_delta_type:"n",entregas:7,entregas_delta:"+2 vs Mar",entregas_delta_type:"u",chamados:94,chamados_delta:"−17 vs Mar",chamados_delta_type:"d"},
  analises:{
    score:"Score 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização do Open Finance e entrada de novos produtos com alta baseline.",
    produtos:"Base monitorada estável. 3 novos produtos em Release 1 ampliaram cobertura sem reduzir qualidade da carteira existente.",
    entregas:"Destaque para BRAI4DQ em produção e Agente DEVA na Bridge — impacto direto na maturidade técnica da área.",
    chamados:"Redução de 15% no volume. Taxa de encerramento 100% — resultado consistente com as melhorias de processo de Mar/26.",
    pos:"Diogo entregou BRAI4DQ completo com 6 dimensões produtivas e McKinsey avançando com 4 novas. Roberto homologou integração crítica e entregou 7 produtos. Joabe descomissionou ambiente legado. Djan deployou Agente DEVA em produção.",
    pos_insight:"💡 A combinação de descomissionamento legado + BRAI4DQ marca uma virada estrutural na plataforma de qualidade.",
    scores:"7 dos 10 produtos atingiram score ≥99%. Open Finance mantém desempenho consistente acima de 95%.",
    scores_atencao:"Rating de Risco PLDFT (80,0%) em recuperação desde Jan/26 (64,0%). Plano de ação em andamento com foco em Disponibilidade.",
    status:"94 chamados, todos encerrados. Zero em aberto — SLA 100% cumprido no período.",
    scores_insight:"💡 Priorizar revisão do fluxo de Disponibilidade no PLDFT para o ciclo de Mai/26.",
    dim1:"Disponibilidade lidera em chamados (49). Completude tem maior volume de ocorrências (159) — sinal de problema recorrente.",
    dim2:"Relação ocorrências/chamado na Completude é 4,8x. Exige investigação de causa-raiz.",
    evol:"Carteira em trajetória ascendente. SACL e Feriados em 100% por 3 meses consecutivos.",
    evol_vol:"Chamados concluídos reduziram 53% vs. pico Jan/26 (111). Tendência de normalização confirmada.",
    evol_insight:"💡 Open Finance atingiu 98,7% em Fev/26 e recuou para 95,98% em Abr/26. Investigar regressão.",
    causas:"As 2 principais causas concentram 67% dos chamados. Ações preventivas teriam impacto rápido.",
    causas_acoes:"Preenchimento Incorreto (19 chamados) pode ser reduzido com capacitação. Diversas e Externo são residuais.",
    causas_insight:"💡 63% dos chamados de Alteração na Estrutura estão em 2 produtos. Mapear para mitigação sistêmica.",
    releases:"3 novos produtos em Release 1 com scores acima de 96%. IA Generativa (R2) e Dados Socioeconômicos (R5) em maturidade avançada."
  },
  meses:['Dez/25','Jan/26','Fev/26','Mar/26','Abr/26'],
  produtos:[
    {nome:'OPEN FINANCE',gestor:'Augusto Vieira',scores:[92.06,91.49,98.73,94.3,95.98],chamados:81,dim:'Consistência'},
    {nome:'QUALIDADE DE DADOS',gestor:'Augusto Vieira',scores:[null,null,null,100,100],chamados:0,dim:'—'},
    {nome:'RATING DE RISCO PLDFT',gestor:'Deise Ferreira',scores:[null,64.0,85.52,74.58,80.0],chamados:2,dim:'Disponibilidade'},
    {nome:'ATENDIMENTO AO CLIENTE',gestor:'Deise Ferreira',scores:[89.95,98.89,99.61,99.64,99.77],chamados:5,dim:'Completude'},
    {nome:'COMUNICAÇÕES PLDFT',gestor:'Deise Ferreira',scores:[93.53,99.24,100,100,99.65],chamados:2,dim:'Disponibilidade'},
    {nome:'PROCESSOS JURÍDICOS',gestor:'Deise Ferreira',scores:[94.44,100,100,99.91,99.73],chamados:4,dim:'Completude'},
    {nome:'MANIFESTAÇÕES SACL',gestor:'Deise Ferreira',scores:[99.73,100,100,100,100],chamados:0,dim:'—'},
    {nome:'SRM CALENDÁRIO FERIADOS',gestor:'Deise Ferreira',scores:[99.72,99.72,100,100,100],chamados:0,dim:'—'},
    {nome:'VISÃO CLIENTES 360',gestor:'Roberto Lima',scores:[null,null,97.2,98.1,99.2],chamados:0,dim:'—'},
    {nome:'IA GENERATIVA',gestor:'Roberto Lima',scores:[null,null,null,96.5,98.4],chamados:0,dim:'—'}
  ],
  dimensoes:{labels:['Disponibilidade','Completude','Consistência','Integridade','Unicidade'],chamados:[49,33,30,8,1],ocorrencias:[65,159,30,8,43]},
  causas:{labels:['Alt. Estrutura','Open Finance','Preench. Incor.','Não Informado','Orig. Indispon.','Diversas','Externo'],data:[34,29,19,7,3,1,1]},
  tendencia:{abertos:[0,0,0,0,0],concluidos:[31,111,55,64,30]},
  statusChamados:{encerrados:94,analise:0,andamento:0},
  pos:[
    {id:'DH',nome:'Diogo Horta Costa',role:'Product Owner · BRAI4DQ',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'BRAI4DQ em Produção — 6 Dimensões Ativas',desc:'Entrega no ambiente produtivo com disponibilidade, completude, consistência, integridade, unicidade e variação — habilitando Qualidade de Dados em Produtos, UCS e INGs.'},
      {tag:'s',tagLabel:'Estruturante',titulo:'4 Novas Dimensões (McKinsey) — Entrega Mai/26',desc:'Desenvolvimento de acurácia, validade, atualidade e tempestividade para implantação no próximo ciclo.'}
    ]},
    {id:'RB',nome:'Roberto Barboza Lima',role:'Product Owner · Produtos de Dados',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Integração Databricks × SharePoint Homologada',desc:'Conexão segura entre plataformas, reduzindo atividades manuais e garantindo rastreabilidade.'},
      {tag:'p',tagLabel:'Produtos',titulo:'7 Produtos de Dados Entregues',desc:'Canais Digitais (R1) · Captação Líquida (R1) · Simulador (R1) · Visão 360 (R2) · Carteiras (R2) · IA Generativa (R2) · Dados Socioeconômicos (R5).'},
      {tag:'i',tagLabel:'Iniciativa',titulo:'Workshop Assessment — Tribos de Negócio',desc:'Federalização da qualidade, elevando maturidade em governança e reduzindo divergências.'}
    ]},
    {id:'JR',nome:'Joabe da Silva Rufino',role:'Product Owner · Plataforma & Infra',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Descomissionamento Informatica/SAS On-Premises',desc:'Desativação de 10 tabelas Hive (Bureaus), 8 Teradata (CRM) e 4 SAS (Contadoria) — migração para Databricks acelerada.'},
      {tag:'s',tagLabel:'Estruturante',titulo:'Control-M Jobs Databricks — AAQD Ativado',desc:'Esteira Control-M via automação no Databricks com job de expurgo do Produto de Qualidade de Dados.'}
    ]},
    {id:'DS',nome:'Djan Paulo da Silva',role:'Product Owner · IA & Automação',itens:[
      {tag:'s',tagLabel:'Estruturante',titulo:'Agente DEVA em Produção — Plataforma Bridge',desc:'Deploy no Bridge (IA corporativa) e Databricks com notebook interface compartilhado, democratizando o acesso ao agente.'},
      {tag:'o',tagLabel:'Eficiência',titulo:'Revisão do Ecossistema BRAI4DQ',desc:'Adequação de schemas, tabelas e modelo dimensional para conformidade com a nova solução.'}
    ]}
  ],
  releases:[
    {tipo:'nw',nome:'Autenticação de Usuários — Canais Digitais',ver:'Release 1 · Primeiro ciclo de qualidade'},
    {tipo:'nw',nome:'Captação Líquida',ver:'Release 1 · Ingresso em monitoramento'},
    {tipo:'nw',nome:'Simulador de Métricas',ver:'Release 1 · Cobertura inicial estabelecida'},
    {tipo:'up',nome:'Visão Clientes 360',ver:'Release 2 · Expansão de dimensões'},
    {tipo:'up',nome:'Plataforma IA Generativa',ver:'Release 2 · Cobertura ampliada'},
    {tipo:'up',nome:'Dados Públicos Socioeconômicos e Demográficos',ver:'Release 5 · Maturidade consolidada'}
  ]
};

const COLORS=['#CC0A2F','#2456D8','#00B87A','#D99B18','#5A35CC','#007BB8','#B83020','#1A70D8','#009066','#C08010'];
const bf={family:"'DM Sans',sans-serif",size:11};
const gc=()=>getComputedStyle(document.documentElement).getPropertyValue('--g200').trim()||'#D8DBE9';
const tc=()=>getComputedStyle(document.documentElement).getPropertyValue('--g600').trim()||'#454C72';
const ci={};
function kc(id){if(ci[id]){ci[id].destroy();delete ci[id];}}
function ctx(id){const e=document.getElementById(id);if(!e)return null;kc(id);return e.getContext('2d');}

let evolActive=new Set();

function applyData(){
  // hero
  document.getElementById('heroSub').textContent=`Inteligência de Dados · Emitido em ${D.emitido}`;
  const hv=[D.kpis.score_medio+'%',D.kpis.produtos,D.kpis.entregas,D.kpis.chamados];
  ['hs1','hs2','hs3','hs4'].forEach((id,i)=>{const e=document.getElementById(id);if(e)e.textContent=hv[i];});

  // badge
  document.getElementById('kpiBadge').textContent=D.periodo;

  // KPIs
  const km=[['kpiScore',D.kpis.score_medio+'%','kpiScoreDelta',D.kpis.score_delta,D.kpis.score_delta_type],
    ['kpiProd',D.kpis.produtos,'kpiProdDelta',D.kpis.produtos_delta,D.kpis.produtos_delta_type],
    ['kpiEnt',D.kpis.entregas,'kpiEntDelta',D.kpis.entregas_delta,D.kpis.entregas_delta_type],
    ['kpiCham',D.kpis.chamados,'kpiChamDelta',D.kpis.chamados_delta,D.kpis.chamados_delta_type]];
  km.forEach(([vi,vv,di,dv,dt])=>{
    const ve=document.getElementById(vi);if(ve)ve.textContent=vv;
    const de=document.getElementById(di);if(de){de.textContent=(dt==='u'?'▲ ':dt==='d'?'▼ ':'→ ')+dv;de.className='kc-delta '+dt;}
  });

  // Análises sidebar
  const amap=[
    ['aKpiScore',D.analises.score],['aKpiProd',D.analises.produtos],
    ['aKpiEnt',D.analises.entregas],['aKpiCham',D.analises.chamados],
    ['aPos',D.analises.pos],['aPosInsight',D.analises.pos_insight],
    ['aScores',D.analises.scores],['aScoresAtencao',D.analises.scores_atencao],
    ['aStatus',D.analises.status],['aScoresInsight',D.analises.scores_insight],
    ['aDim1',D.analises.dim1],['aDim2',D.analises.dim2],
    ['aEvol',D.analises.evol],['aEvolVol',D.analises.evol_vol],['aEvolInsight',D.analises.evol_insight],
    ['aCausas',D.analises.causas],['aCausasAcoes',D.analises.causas_acoes],['aCausasInsight',D.analises.causas_insight],
    ['aReleases',D.analises.releases]
  ];
  amap.forEach(([id,txt])=>{const e=document.getElementById(id);if(e)e.textContent=txt;});

  // POs
  const pg=document.getElementById('poGrid');
  if(pg) pg.innerHTML=D.pos.map(po=>`
    <div class="poc">
      <div class="poc-hd"><div class="poc-av">${po.id}</div><div><div class="poc-nm">${po.nome}</div><div class="poc-rl">${po.role}</div></div></div>
      <div class="poc-body">${po.itens.map((it,i)=>`
        ${i>0?'<div class="poc-sep"></div>':''}
        <div class="poc-item"><div class="poc-dot"></div><div>
          <span class="tag ${it.tag}">${it.tagLabel}</span>
          <div class="poc-t">${it.titulo}</div>
          <div class="poc-d">${it.desc}</div>
        </div></div>`).join('')}</div>
    </div>`).join('');

  // Releases
  const rg=document.getElementById('releasesGrid');
  if(rg) rg.innerHTML=D.releases.map(r=>`
    <div class="rc ${r.tipo}"><span class="rc-badge">${r.tipo==='nw'?'Novo':'Atualização'}</span>
    <div class="rc-nm">${r.nome}</div><div class="rc-ver">${r.ver}</div></div>`).join('');

  // Dim bars
  const dl=document.getElementById('dimList');
  if(dl){
    const maxC=Math.max(...D.dimensoes.chamados,1),maxO=Math.max(...D.dimensoes.ocorrencias,1);
    dl.innerHTML=D.dimensoes.labels.map((l,i)=>`
      <div class="dim-g">
        <div class="dim-lbl">${l}</div>
        <div class="dim-row">
          <div class="dim-sub" style="font-size:.65rem;color:var(--g500)">Chamados</div>
          <div class="dim-bw"><div class="dim-bf" style="width:${(D.dimensoes.chamados[i]/maxC*100).toFixed(1)}%;background:var(--pr)"></div></div>
          <div class="dim-num" style="color:var(--pr)">${D.dimensoes.chamados[i]}</div>
        </div>
        <div class="dim-row">
          <div class="dim-sub" style="font-size:.65rem;color:var(--g500)">Ocorrências</div>
          <div class="dim-bw"><div class="dim-bf" style="width:${(D.dimensoes.ocorrencias[i]/maxO*100).toFixed(1)}%;background:var(--blue)"></div></div>
          <div class="dim-num" style="color:var(--blue)">${D.dimensoes.ocorrencias[i]}</div>
        </div>
      </div>`).join('');
  }

  buildScoreTable();
  buildEvolFilters();
  buildAllCharts();
}

function buildScoreTable(){
  const tb=document.getElementById('scoreTbody');if(!tb)return;
  tb.innerHTML=D.produtos.map(p=>{
    const a=p.scores[4],m=p.scores[3];
    const t=(!a||!m)?'—':(a>m?`<span style="color:var(--green);font-weight:700">▲</span>`:a<m?`<span style="color:var(--red);font-weight:700">▼</span>`:`<span style="color:var(--g500)">→</span>`);
    const sc=!a?'':a>=98?'gr':a>=90?'bl':a>=80?'ye':'rd';
    return `<tr><td><strong>${p.nome}</strong></td><td style="font-size:.72rem;color:var(--g500)">${p.gestor}</td>
    <td><span class="sbadge ${sc}">${a?a.toFixed(2)+'%':'—'}</span></td>
    <td style="font-size:.76rem">${m?m.toFixed(2)+'%':'—'}</td>
    <td style="text-align:center">${t}</td>
    <td style="font-size:.76rem">${p.chamados}</td>
    <td style="font-size:.7rem;color:var(--g500)">${p.dim}</td></tr>`;
  }).join('');
}

function buildEvolFilters(){
  const c=document.getElementById('evolFilters');if(!c)return;
  evolActive=new Set(D.produtos.map((_,i)=>i));
  c.innerHTML=D.produtos.map((p,i)=>`<div class="chip act" data-idx="${i}" onclick="toggleEvol(${i},this)">${p.nome.length>16?p.nome.slice(0,16)+'…':p.nome}</div>`).join('');
}
function toggleEvol(idx,el){
  if(evolActive.has(idx))evolActive.delete(idx);else evolActive.add(idx);
  el.classList.toggle('act',evolActive.has(idx));
  buildEvolucaoChart();
}

function mkLeg(id,items){
  const el=document.getElementById(id);if(!el)return;
  el.innerHTML=items.map(it=>`<div class="cl-i"><div class="${it.shape||'cl-d'}" style="background:${it.color}"></div>${it.label}</div>`).join('');
}

function buildAllCharts(){
  buildScoreChart();buildDistChart();buildStatusChart();
  buildDimensoesChart();buildEvolucaoChart();buildTendenciaChart();
  buildCausasChart();buildParetoChart();
}

function buildScoreChart(){
  const c=ctx('cScores');if(!c)return;
  const labels=D.produtos.map(p=>p.nome.length>24?p.nome.slice(0,24)+'…':p.nome);
  const scores=D.produtos.map(p=>p.scores[4]||0);
  const clrs=scores.map(s=>s>=98?'rgba(0,184,122,.8)':s>=90?'rgba(36,86,216,.75)':'rgba(204,10,47,.8)');
  ci['cScores']=new Chart(c,{
    type:'bar',
    data:{labels,datasets:[{data:scores,backgroundColor:clrs,borderRadius:5,borderSkipped:false,barThickness:22}]},
    options:{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,layout:{padding:{right:22}},
      plugins:{legend:{display:false},datalabels:{anchor:'end',align:'end',formatter:v=>v>0?v.toFixed(2)+'%':'—',font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},color:tc(),padding:{left:4}}},
      scales:{x:{min:60,max:110,ticks:{callback:v=>v+'%',font:bf,color:tc()},grid:{color:gc()}},y:{ticks:{font:{...bf,size:10},color:tc()},grid:{display:false}}}
    }
  });
}

function buildDistChart(){
  const c=ctx('cDist');if(!c)return;
  const sc=D.produtos.map(p=>p.scores[4]||0).filter(s=>s>0);
  const counts=[sc.filter(s=>s>=98).length,sc.filter(s=>s>=90&&s<98).length,sc.filter(s=>s>=80&&s<90).length,sc.filter(s=>s<80).length];
  const clrs=['#00B87A','#2456D8','#D99B18','#CC0A2F'];
  const lbls=['≥98% Excelente','90–98% Bom','80–90% Atenção','<80% Crítico'];
  ci['cDist']=new Chart(c,{
    type:'doughnut',
    data:{labels:lbls,datasets:[{data:counts,backgroundColor:clrs,borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{display:false},datalabels:{formatter:(v,ctx)=>{const t=ctx.dataset.data.reduce((a,b)=>a+b,0);return v>0?v+'\n('+((v/t)*100).toFixed(0)+'%)':'';},color:'#fff',font:{weight:'700',size:10,family:"'DM Sans',sans-serif"},textAlign:'center'}}}
  });
  mkLeg('legDist',lbls.map((l,i)=>({label:`${l}: ${counts[i]}`,color:clrs[i]})));
}

function buildStatusChart(){
  const c=ctx('cStatus');if(!c)return;
  const {encerrados,analise,andamento}=D.statusChamados;
  const vals=[encerrados,analise||0,andamento||0];
  const lbls=['Encerrados','Em Análise','Em Andamento'];
  const clrs=['#00B87A','#D99B18','#2456D8'];
  ci['cStatus']=new Chart(c,{
    type:'doughnut',
    data:{labels:lbls,datasets:[{data:vals,backgroundColor:clrs,borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{display:false},datalabels:{formatter:(v,ctx)=>{const t=ctx.dataset.data.reduce((a,b)=>a+b,0);return v>0?v+'\n('+((v/t)*100).toFixed(0)+'%)':'';},color:'#fff',font:{weight:'700',size:10,family:"'DM Sans',sans-serif"},textAlign:'center'}}}
  });
  mkLeg('legStatus',lbls.map((l,i)=>({label:`${l}: ${vals[i]}`,color:clrs[i]})));
}

function buildDimensoesChart(){
  const c=ctx('cDimensoes');if(!c)return;
  ci['cDimensoes']=new Chart(c,{
    type:'bar',
    data:{labels:D.dimensoes.labels,datasets:[
      {label:'Chamados',data:D.dimensoes.chamados,backgroundColor:'rgba(204,10,47,.8)',borderRadius:4,borderSkipped:false},
      {label:'Ocorrências',data:D.dimensoes.ocorrencias,backgroundColor:'rgba(36,86,216,.65)',borderRadius:4,borderSkipped:false}
    ]},
    options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:20}},
      plugins:{legend:{display:false},datalabels:{anchor:'end',align:'end',formatter:v=>v,font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},color:tc()}},
      scales:{x:{ticks:{font:{...bf,size:10},color:tc()},grid:{display:false}},y:{ticks:{font:bf,color:tc()},grid:{color:gc()}}}}
  });
  mkLeg('legDim',[{label:'Chamados',color:'rgba(204,10,47,.8)'},{label:'Ocorrências',color:'rgba(36,86,216,.65)'}]);
}

function buildEvolucaoChart(){
  const c=ctx('cEvolucao');if(!c)return;
  const visible=D.produtos.filter((_,i)=>evolActive.has(i));
  const datasets=visible.map(p=>{
    const gi=D.produtos.indexOf(p);
    return{label:p.nome.length>18?p.nome.slice(0,18)+'…':p.nome,data:p.scores,borderColor:COLORS[gi%COLORS.length],backgroundColor:'transparent',borderWidth:2.5,pointRadius:5,pointHoverRadius:7,spanGaps:true,tension:.3,pointBackgroundColor:COLORS[gi%COLORS.length]};
  });
  ci['cEvolucao']=new Chart(c,{
    type:'line',data:{labels:D.meses,datasets},
    options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:22,right:8}},
      plugins:{legend:{display:false},datalabels:{display:ctx=>ctx.dataset.data[ctx.dataIndex]!==null,formatter:v=>v!==null?v.toFixed(1)+'%':'',font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:ctx=>COLORS[ctx.datasetIndex%COLORS.length],align:'top',anchor:'end',offset:4,textAlign:'center'}},
      scales:{x:{ticks:{font:bf,color:tc()},grid:{color:gc()}},y:{min:60,max:108,ticks:{callback:v=>v+'%',font:bf,color:tc()},grid:{color:gc()}}}}
  });
  const leg=document.getElementById('legEvolucao');
  if(leg) leg.innerHTML=visible.map(p=>{const gi=D.produtos.indexOf(p);return`<div class="cl-i"><div class="cl-ln" style="background:${COLORS[gi%COLORS.length]}"></div>${p.nome.length>18?p.nome.slice(0,18)+'…':p.nome}</div>`;}).join('');
}

function buildTendenciaChart(){
  const c=ctx('cTendencia');if(!c)return;
  ci['cTendencia']=new Chart(c,{
    type:'bar',
    data:{labels:D.meses,datasets:[
      {label:'Concluídos',data:D.tendencia.concluidos,backgroundColor:'rgba(0,184,122,.78)',borderRadius:5,borderSkipped:false},
      {label:'Abertos',data:D.tendencia.abertos,backgroundColor:'rgba(204,10,47,.65)',borderRadius:5,borderSkipped:false}
    ]},
    options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:20}},
      plugins:{legend:{display:false},datalabels:{anchor:'end',align:'end',formatter:v=>v>0?v:'',font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},color:tc()}},
      scales:{x:{ticks:{font:bf,color:tc()},grid:{display:false}},y:{ticks:{font:bf,color:tc()},grid:{color:gc()}}}}
  });
  mkLeg('legTend',[{label:'Concluídos',color:'rgba(0,184,122,.78)'},{label:'Abertos',color:'rgba(204,10,47,.65)'}]);
}

function buildCausasChart(){
  const c=ctx('cCausas');if(!c)return;
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i]}))].sort((a,b)=>b.v-a.v);
  const total=sorted.reduce((s,x)=>s+x.v,0);
  const clrs=sorted.map((_,i)=>COLORS[i%COLORS.length]);
  ci['cCausas']=new Chart(c,{
    type:'bar',
    data:{labels:sorted.map(x=>x.l),datasets:[{data:sorted.map(x=>x.v),backgroundColor:clrs,borderRadius:4,borderSkipped:false}]},
    options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:22}},
      plugins:{legend:{display:false},datalabels:{anchor:'end',align:'end',formatter:(v)=>v+' ('+((v/total)*100).toFixed(0)+'%)',font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:tc()}},
      scales:{x:{ticks:{font:{...bf,size:9},color:tc(),maxRotation:30},grid:{display:false}},y:{ticks:{font:bf,color:tc()},grid:{color:gc()}}}}
  });
  mkLeg('legCausas',sorted.map((x,i)=>({label:`${x.l}: ${x.v}`,color:clrs[i]})));
}

function buildParetoChart(){
  const c=ctx('cPareto');if(!c)return;
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i]}))].sort((a,b)=>b.v-a.v);
  const total=sorted.reduce((s,x)=>s+x.v,0);
  let acc=0;
  const cum=sorted.map(x=>{acc+=x.v;return parseFloat((acc/total*100).toFixed(1));});
  ci['cPareto']=new Chart(c,{
    type:'bar',
    data:{labels:sorted.map(x=>x.l.length>10?x.l.slice(0,10)+'…':x.l),datasets:[
      {type:'bar',label:'Volume',data:sorted.map(x=>x.v),backgroundColor:'rgba(204,10,47,.75)',borderRadius:4,yAxisID:'y',datalabels:{anchor:'end',align:'end',formatter:v=>v,font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:tc()}},
      {type:'line',label:'% Acumulado',data:cum,borderColor:'#2456D8',borderWidth:2,pointRadius:4,fill:false,yAxisID:'y2',tension:.3,datalabels:{anchor:'end',align:'top',formatter:v=>v+'%',font:{size:9,weight:'700',family:"'DM Sans',sans-serif"},color:'#2456D8',offset:4}}
    ]},
    options:{responsive:true,maintainAspectRatio:false,layout:{padding:{top:24}},
      plugins:{legend:{display:false}},
      scales:{x:{ticks:{font:{...bf,size:9},color:tc(),maxRotation:30},grid:{display:false}},y:{ticks:{font:bf,color:tc()},grid:{color:gc()}},y2:{position:'right',min:0,max:100,ticks:{callback:v=>v+'%',font:bf,color:tc()},grid:{display:false}}}}
  });
  mkLeg('legPareto',[{label:'Volume',color:'rgba(204,10,47,.75)'},{label:'% Acumulado',color:'#2456D8',shape:'cl-ln'}]);
}

// ─── THEME ───
function toggleTheme(){
  const h=document.documentElement;
  h.dataset.theme=h.dataset.theme==='dark'?'light':'dark';
  localStorage.setItem('blt2-theme',h.dataset.theme);
  setTimeout(buildAllCharts,80);
}
(()=>{const t=localStorage.getItem('blt2-theme');if(t)document.documentElement.dataset.theme=t;})();

// ─── SCROLL REVEAL ───
const ro=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible');});},{threshold:.05});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));

// ─── SIDE NAV ───
function scrollTo(id,el){
  document.getElementById(id)?.scrollIntoView({behavior:'smooth',block:'start'});
  document.querySelectorAll('.side-nav-btn').forEach(b=>b.classList.remove('act'));
  el.classList.add('act');
}

// ─── IMPORT ───
function openImport(){document.getElementById('impOv').classList.add('open');}
function closeImport(){document.getElementById('impOv').classList.remove('open');document.getElementById('impErr').textContent='';}
function previewSchema(e){e.preventDefault();const s={periodo:"string",emitido:"string",kpis:{score_medio:"number"},analises:{score:"string"},meses:["string"],produtos:[{nome:"string",gestor:"string",scores:["number|null"],chamados:"number",dim:"string"}]};document.getElementById('impJson').value=JSON.stringify(s,null,2);}
function applyJson(){
  const raw=document.getElementById('impJson').value.trim();
  const err=document.getElementById('impErr');
  try{const p=JSON.parse(raw);D=deepMerge(D,p);err.textContent='';closeImport();applyData();}
  catch(e){err.textContent='JSON inválido: '+e.message;}
}
function deepMerge(t,s){const o={...t};for(const k in s){if(s[k]&&typeof s[k]==='object'&&!Array.isArray(s[k])&&typeof t[k]==='object'&&!Array.isArray(t[k])){o[k]=deepMerge(t[k],s[k]);}else{o[k]=s[k];}}return o;}

// ─── EXPORT ───
function generateEmailHtml(){
  const prodRows=D.produtos.map((p,i)=>{
    const a=p.scores[4],m=p.scores[3];
    const t=(!a||!m)?'→':(a>m?'▲':'▼');
    const clr=!a?'#888':a>=98?'#00A868':a>=90?'#2456D8':a>=80?'#C07A10':'#CC0A2F';
    return`<tr style="background:${i%2===0?'#fff':'#F7F8FA'}"><td style="padding:9px 12px;font-size:11px;font-weight:700;color:#141930;border-bottom:1px solid #D8DBE9">${p.nome}</td><td style="padding:9px 12px;font-size:10px;color:#7278A0;border-bottom:1px solid #D8DBE9">${p.gestor}</td><td style="padding:9px 12px;text-align:center;border-bottom:1px solid #D8DBE9"><span style="background:${clr}18;color:${clr};font-size:11px;font-weight:700;padding:2px 9px;border-radius:10px">${a?a.toFixed(2)+'%':'—'}</span></td><td style="padding:9px 12px;text-align:center;font-weight:700;color:${a&&m?(a>m?'#00A868':'#CC0A2F'):'#888'};border-bottom:1px solid #D8DBE9">${t}</td><td style="padding:9px 12px;text-align:center;font-size:11px;color:#7278A0;border-bottom:1px solid #D8DBE9">${p.chamados}</td></tr>`;
  }).join('');
  const poHtml=D.pos.map(po=>`<table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #D8DBE9;border-radius:10px;margin-bottom:10px;overflow:hidden"><tr><td style="background:#ECEEF4;padding:11px 14px;border-bottom:1px solid #D8DBE9"><table cellpadding="0" cellspacing="0" border="0"><tr><td style="width:34px"><div style="width:32px;height:32px;background:linear-gradient(135deg,#CC0A2F,#8C0020);border-radius:7px;text-align:center;line-height:32px;font-size:10px;font-weight:800;color:#fff">${po.id}</div></td><td style="padding-left:9px"><div style="font-size:12px;font-weight:700;color:#141930">${po.nome}</div><div style="font-size:10px;color:#7278A0">${po.role}</div></td></tr></table></td></tr><tr><td style="padding:12px 14px">${po.itens.map((it,i)=>`${i>0?'<div style="height:1px;background:#D8DBE9;margin:9px 0"></div>':''}<div style="font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#CC0A2F;background:rgba(204,10,47,.08);padding:2px 7px;border-radius:3px;display:inline-block;margin-bottom:4px">${it.tagLabel}</div><div style="font-size:11px;font-weight:700;color:#141930;margin-bottom:2px">${it.titulo}</div><div style="font-size:10px;color:#454C72;line-height:1.55">${it.desc}</div>`).join('')}</td></tr></table>`).join('');
  return`<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Boletim ${D.periodo}</title></head><body style="margin:0;padding:0;background:#F0F1F6;font-family:'Segoe UI',Arial,sans-serif"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F0F1F6;padding:20px 0"><tr><td align="center"><table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px"><tr><td style="background:linear-gradient(135deg,#8C0020,#CC0A2F);border-radius:12px 12px 0 0;padding:26px 32px 22px"><div style="background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:3px 12px;display:inline-block;font-size:9px;font-weight:700;color:rgba(255,255,255,.82);text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px">Qualidade de Dados · ${D.periodo}</div><h1 style="font-size:22px;font-weight:800;color:#fff;margin:0 0 5px;line-height:1.1">Boletim de Qualidade de Dados</h1><p style="font-size:11px;color:rgba(255,255,255,.62);margin:0">Período: <strong style="color:#fff">${D.periodo}</strong> · ${D.emitido}</p></td></tr><tr><td style="background:#CC0A2F;height:2px"></td></tr><tr><td style="background:#fff;padding:22px 32px;border-bottom:1px solid #D8DBE9"><p style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Resumo Executivo</p><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="25%" style="padding-right:7px"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background:#F0F1F6;border-radius:9px;padding:13px;border-top:3px solid #CC0A2F"><div style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Score Médio</div><div style="font-size:22px;font-weight:800;color:#CC0A2F;line-height:1">${D.kpis.score_medio}%</div><div style="font-size:9px;color:#7278A0;margin-top:3px">▲ ${D.kpis.score_delta}</div></td></tr></table></td><td width="25%" style="padding:0 3px"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background:#F0F1F6;border-radius:9px;padding:13px;border-top:3px solid #00B87A"><div style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Produtos</div><div style="font-size:22px;font-weight:800;color:#00A868;line-height:1">${D.kpis.produtos}</div><div style="font-size:9px;color:#7278A0;margin-top:3px">Com SLA ativo</div></td></tr></table></td><td width="25%" style="padding:0 3px"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background:#F0F1F6;border-radius:9px;padding:13px;border-top:3px solid #2456D8"><div style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Entregas</div><div style="font-size:22px;font-weight:800;color:#2456D8;line-height:1">${D.kpis.entregas}</div><div style="font-size:9px;color:#7278A0;margin-top:3px">▲ ${D.kpis.entregas_delta}</div></td></tr></table></td><td width="25%" style="padding-left:7px"><table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background:#F0F1F6;border-radius:9px;padding:13px;border-top:3px solid #D99B18"><div style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Chamados</div><div style="font-size:22px;font-weight:800;color:#C07A10;line-height:1">${D.kpis.chamados}</div><div style="font-size:9px;color:#7278A0;margin-top:3px">100% encerrados</div></td></tr></table></td></tr></table></td></tr><tr><td style="background:#fff;padding:18px 32px 8px;border-top:1px solid #D8DBE9"><p style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Entregas dos Product Owners</p>${poHtml}</td></tr><tr><td style="background:#fff;padding:18px 32px 22px;border-top:1px solid #D8DBE9"><p style="font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Scores por Produto</p><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #D8DBE9;border-radius:8px;overflow:hidden"><tr style="background:#ECEEF4"><th style="padding:8px 12px;font-size:8px;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #D8DBE9">Produto</th><th style="padding:8px 12px;font-size:8px;text-align:left;border-bottom:1px solid #D8DBE9;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em">Gestor</th><th style="padding:8px 12px;font-size:8px;text-align:center;border-bottom:1px solid #D8DBE9;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em">Score Abr/26</th><th style="padding:8px 12px;font-size:8px;text-align:center;border-bottom:1px solid #D8DBE9;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em">Tend.</th><th style="padding:8px 12px;font-size:8px;text-align:center;border-bottom:1px solid #D8DBE9;font-weight:700;color:#7278A0;text-transform:uppercase;letter-spacing:.06em">Chamados</th></tr>${prodRows}</table><div style="margin-top:12px;background:rgba(204,10,47,.05);border-left:3px solid #CC0A2F;border-radius:0 8px 8px 0;padding:9px 13px"><div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#CC0A2F;margin-bottom:2px">Análise</div><div style="font-size:10px;color:#454C72;line-height:1.6;font-style:italic">${D.analises.scores}</div></div></td></tr><tr><td style="background:linear-gradient(135deg,#8C0020,#CC0A2F);border-radius:0 0 12px 12px;padding:16px 32px"><div style="font-size:11px;font-weight:700;color:#fff">Boletim de Qualidade de Dados — ${D.periodo}</div><div style="font-size:9px;color:rgba(255,255,255,.52);margin-top:2px">Gerado automaticamente · Inteligência de Dados / Bradesco</div></td></tr></table></td></tr></table></body></html>`;
}

function openExport(){
  const html=generateEmailHtml();
  document.getElementById('expPreview').innerHTML=`<iframe srcdoc="${html.replace(/"/g,'&quot;')}"></iframe>`;
  document.getElementById('expOv').classList.add('open');
}
function closeExport(){document.getElementById('expOv').classList.remove('open');}
function copyEmailHtml(){navigator.clipboard.writeText(generateEmailHtml()).then(()=>alert('HTML copiado!'));}
function downloadEmail(){
  const a=document.createElement('a');
  a.href='data:text/html;charset=utf-8,'+encodeURIComponent(generateEmailHtml());
  a.download=`boletim_email_${D.periodo.replace(/\s/g,'_')}.html`;a.click();
}

// ─── INIT ───
applyData();
</script>
</body>
</html>
