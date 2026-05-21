<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DEVA — Apresentação | Bradesco BRAI4DQ</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ──────────────────────────────────────────
   TOKENS & BRAND
   ────────────────────────────────────────── */
:root {
  --red:        #CC092F;
  --red-dk:     #9E0723;
  --red-lt:     #F5E6E9;
  --red-mid:    #E0899B;
  --white:      #FFFFFF;
  --g50:        #F8F8F8;
  --g100:       #F0F0F0;
  --g200:       #E2E2E2;
  --g400:       #9A9A9A;
  --g600:       #555555;
  --g800:       #222222;
  --g900:       #111111;
  --green:      #15803D;
  --green-bg:   #DCFCE7;
  --amber:      #B45309;
  --amber-bg:   #FEF3C7;
  --blue:       #1D4ED8;
  --blue-bg:    #DBEAFE;
  --r: var(--red);
  --font-head:  'Sora', sans-serif;
  --font-body:  'DM Sans', sans-serif;
  --sb-w:       252px;
  --sb-col:     56px;
  --ease:       cubic-bezier(.4,0,.2,1);
}
[data-theme="dark"] {
  --white:   #141414;
  --g50:     #1C1C1C;
  --g100:    #252525;
  --g200:    #303030;
  --g400:    #666666;
  --g600:    #AAAAAA;
  --g800:    #E8E8E8;
  --g900:    #F5F5F5;
  --green-bg: #052E16;
  --amber-bg: #1C1200;
  --blue-bg:  #0A1628;
}

/* ── RESET ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;overflow:hidden}
body{font-family:var(--font-body);background:var(--g50);color:var(--g800)}
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--g200);border-radius:3px}

/* ── SHELL ── */
.app{display:flex;height:100vh;overflow:hidden}

/* ── SIDEBAR ── */
.sb{
  position:fixed;left:0;top:0;height:100vh;
  width:var(--sb-w);z-index:200;
  background:linear-gradient(175deg, var(--red-dk) 0%, var(--red) 55%, #A60824 100%);
  display:flex;flex-direction:column;
  border-radius:0 24px 24px 0;
  box-shadow:6px 0 28px rgba(0,0,0,.18);
  transition:width .65s var(--ease);
  overflow:hidden;
  flex-shrink:0;
}
.sb.col{width:var(--sb-col)}

/* sb header */
.sb-head{
  padding:18px 14px 14px;
  border-bottom:1px solid rgba(255,255,255,.13);
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;
}
.sb-logo{display:flex;align-items:center;gap:10px;min-width:0}
.sb-logo svg{flex-shrink:0;transition:transform .4s var(--ease)}
.sb.col .sb-logo svg{transform:scale(1.08)}
.sb-brand{
  display:flex;flex-direction:column;gap:1px;
  overflow:hidden;white-space:nowrap;
  transition:opacity .25s,max-width .55s var(--ease);
  max-width:160px;
}
.sb.col .sb-brand{opacity:0;max-width:0;pointer-events:none}
.sb-brand-main{font-family:var(--font-head);font-size:.88rem;font-weight:700;color:#fff;letter-spacing:.01em}
.sb-brand-sub{font-size:.68rem;color:rgba(255,255,255,.65);font-weight:400}

.sb-toggle{
  flex-shrink:0;
  width:28px;height:28px;border-radius:7px;
  background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
  color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s, transform .55s var(--ease);
  margin-left:auto;
}
.sb-toggle:hover{background:rgba(255,255,255,.25)}
.sb.col .sb-toggle{transform:rotate(180deg)}

/* nav */
.sb-nav{flex:1;overflow-y:auto;padding:10px 8px;display:flex;flex-direction:column;gap:2px}
.sb-nav::-webkit-scrollbar{display:none}

.nav-group{margin-bottom:6px}
.nav-group-label{
  font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;
  color:rgba(255,255,255,.42);padding:6px 8px 3px;
  white-space:nowrap;overflow:hidden;
  transition:opacity .25s;
}
.sb.col .nav-group-label{opacity:0}

.nav-item{
  display:flex;align-items:center;gap:9px;
  padding:8px 10px;border-radius:9px;
  color:rgba(255,255,255,.72);text-decoration:none;
  font-size:.82rem;font-weight:500;cursor:pointer;
  border:none;background:none;width:100%;text-align:left;
  transition:background .15s,color .15s;
  white-space:nowrap;overflow:hidden;
  position:relative;
}
.nav-item:hover{background:rgba(255,255,255,.12);color:#fff}
.nav-item.active{background:rgba(255,255,255,.2);color:#fff;font-weight:700}
.nav-item svg{flex-shrink:0;opacity:.8;min-width:16px}
.nav-item.active svg{opacity:1}
.nav-label{transition:opacity .2s,max-width .55s var(--ease);max-width:180px;overflow:hidden}
.sb.col .nav-label{opacity:0;max-width:0}

.nav-divider{height:1px;background:rgba(255,255,255,.1);margin:4px 0}

/* sb footer */
.sb-foot{
  padding:10px 10px 14px;flex-shrink:0;
  display:flex;flex-direction:column;gap:6px;
}
.sb-foot-row{
  display:flex;align-items:center;gap:8px;
  padding:9px 10px;border-radius:10px;
  background:rgba(0,0,0,.15);cursor:pointer;
  overflow:hidden;white-space:nowrap;
  transition:background .2s;
}
.sb-foot-row:hover{background:rgba(0,0,0,.25)}
.sb-foot-label{font-size:.75rem;font-weight:600;color:rgba(255,255,255,.7);
  transition:opacity .2s,max-width .55s var(--ease);max-width:160px;overflow:hidden}
.sb.col .sb-foot-label{opacity:0;max-width:0}
.theme-track{
  width:36px;height:20px;border-radius:10px;
  background:rgba(255,255,255,.22);position:relative;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 4px;flex-shrink:0;margin-left:auto;
}
.theme-thumb{
  position:absolute;left:2px;top:2px;
  width:16px;height:16px;border-radius:50%;
  background:#fff;transition:transform .3s var(--ease);
  box-shadow:0 1px 3px rgba(0,0,0,.25);
}
[data-theme="dark"] .theme-thumb{transform:translateX(16px)}
.th-icon{font-size:8px;color:rgba(255,255,255,.55);line-height:1}
.sb.col .theme-track{margin-left:0}

/* slide counter chip */
.sb-slide-chip{
  display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.2);border-radius:8px;padding:6px 0;
  font-size:.7rem;color:rgba(255,255,255,.6);font-weight:600;
  letter-spacing:.04em;overflow:hidden;
  transition:opacity .2s,max-height .45s;
}
.sb.col .sb-slide-chip{font-size:.62rem}

/* ── MAIN ── */
.main{
  margin-left:var(--sb-w);flex:1;height:100vh;
  display:flex;flex-direction:column;
  transition:margin-left .65s var(--ease);
  overflow:hidden;position:relative;
}
.main.col{margin-left:var(--sb-col)}

/* Progress bar */
.progress-track{height:3px;background:var(--g200);flex-shrink:0}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--red-dk),var(--red));transition:width .45s var(--ease)}

/* Slide container */
.slides{flex:1;position:relative;overflow:hidden}
.slide{
  position:absolute;inset:0;overflow-y:auto;
  opacity:0;visibility:hidden;
  transform:translateX(48px);
  transition:opacity .45s var(--ease),transform .45s var(--ease),visibility 0s linear .45s;
  display:flex;flex-direction:column;
}
.slide.active{
  opacity:1;visibility:visible;transform:translateX(0);
  transition:opacity .45s var(--ease),transform .45s var(--ease),visibility 0s;
}
.slide.exit-left{transform:translateX(-48px);opacity:0;visibility:hidden}

/* ── SLIDE TYPES ── */

/* COVER */
.slide-cover{
  background:linear-gradient(135deg, var(--red-dk) 0%, var(--red) 50%, #8B0621 100%);
  justify-content:center;align-items:flex-start;
  padding:0;position:relative;overflow:hidden;
  min-height:100%;
}
.cover-bg-shape{
  position:absolute;right:-6%;top:-12%;
  width:58%;padding-top:58%;border-radius:50%;
  background:radial-gradient(ellipse at center, rgba(255,255,255,.07) 0%, transparent 70%);
  pointer-events:none;
}
.cover-bg-shape2{
  position:absolute;right:16%;bottom:-18%;
  width:34%;padding-top:34%;border-radius:50%;
  background:radial-gradient(ellipse at center, rgba(255,255,255,.04) 0%, transparent 70%);
  pointer-events:none;
}
.cover-inner{
  position:relative;z-index:2;
  display:flex;flex-direction:column;justify-content:center;
  height:100%;padding:8% 9%;max-width:660px;
}
.cover-pill{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);
  border-radius:20px;padding:5px 14px;
  font-size:.72rem;font-weight:700;color:rgba(255,255,255,.9);
  text-transform:uppercase;letter-spacing:.07em;margin-bottom:22px;
}
.cover-pill svg{opacity:.8}
.cover-h1{
  font-family:var(--font-head);
  font-size:clamp(2.4rem,4.5vw,3.8rem);
  font-weight:800;color:#fff;line-height:1.06;
  margin-bottom:14px;
}
.cover-h1 em{font-style:normal;color:rgba(255,255,255,.72)}
.cover-sub{
  font-size:clamp(.9rem,1.4vw,1.05rem);
  color:rgba(255,255,255,.72);line-height:1.65;
  margin-bottom:36px;max-width:500px;
}
.cover-cta{
  display:flex;gap:12px;flex-wrap:wrap;
  align-items:center;
}
.btn-cta{
  padding:12px 24px;border-radius:10px;
  font-size:.88rem;font-weight:700;cursor:pointer;
  font-family:var(--font-body);
  transition:all .2s;border:1px solid rgba(255,255,255,.28);
  background:rgba(255,255,255,.16);color:#fff;
}
.btn-cta:hover{background:rgba(255,255,255,.28)}
.btn-cta.solid{background:#fff;color:var(--red);border-color:#fff}
.btn-cta.solid:hover{background:rgba(255,255,255,.9)}
.cover-logo-right{
  position:absolute;right:4%;top:50%;transform:translateY(-50%);
  opacity:.12;pointer-events:none;
  width:clamp(260px,36vw,500px);
}
.cover-bar{position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,rgba(255,255,255,.35),rgba(255,255,255,.08),rgba(255,255,255,.35))}

/* CONTENT SLIDE */
.slide-content{background:var(--white)}
.slide-hdr{
  background:linear-gradient(135deg,var(--red) 0%,var(--red-dk) 100%);
  padding:13px 32px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  flex-shrink:0;
  box-shadow:0 4px 20px rgba(0,0,0,.14);
}
.slide-hdr h1{
  font-family:var(--font-head);
  font-size:1.06rem;font-weight:700;color:#fff;
}
.hdr-logo{width:38px;height:38px;opacity:.9;object-fit:contain;flex-shrink:0}
.slide-body{flex:1;overflow-y:auto;padding:22px 32px 32px}
.slide-body::-webkit-scrollbar{width:4px}
.slide-body::-webkit-scrollbar-thumb{background:var(--g200)}

/* Section title inside slide */
.sec-h{
  font-family:var(--font-head);
  font-size:1.05rem;font-weight:700;color:var(--red);
  margin-bottom:16px;padding-bottom:8px;
  border-bottom:2px solid var(--red);
  display:flex;align-items:center;gap:9px;
}
.sec-h svg{flex-shrink:0}

/* CARD GRID */
.card-grid{display:grid;gap:14px;margin-bottom:22px}
.g2{grid-template-columns:1fr 1fr}
.g3{grid-template-columns:1fr 1fr 1fr}

.card{
  background:var(--g50);
  border:1px solid var(--g200);
  border-radius:12px;padding:18px 20px;
  border-left:4px solid var(--red);
  transition:box-shadow .2s, transform .2s;
}
.card:hover{box-shadow:0 6px 20px rgba(0,0,0,.08);transform:translateY(-2px)}
.card-icon{
  width:38px;height:38px;border-radius:9px;
  background:var(--red-lt);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:12px;flex-shrink:0;
}
[data-theme="dark"] .card-icon{background:rgba(204,9,47,.2)}
.card-icon svg{color:var(--red)}
.card-title{font-family:var(--font-head);font-size:.88rem;font-weight:700;color:var(--g800);margin-bottom:5px}
.card-text{font-size:.8rem;color:var(--g600);line-height:1.65}

/* KPI card */
.kpi{
  background:var(--g50);border:1px solid var(--g200);
  border-radius:12px;padding:16px 20px;
  display:flex;flex-direction:column;gap:3px;
  transition:box-shadow .2s;
}
.kpi:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.kpi-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--g400)}
.kpi-val{font-family:var(--font-head);font-size:2rem;font-weight:800;color:var(--red);line-height:1.05}
.kpi-sub{font-size:.72rem;color:var(--g600)}

/* Bullet list */
.bullet-list{list-style:none;display:flex;flex-direction:column;gap:9px;margin-bottom:18px}
.bullet-list li{
  display:flex;align-items:flex-start;gap:10px;
  font-size:.84rem;color:var(--g600);line-height:1.6;
}
.bullet-list li::before{
  content:'';flex-shrink:0;
  width:6px;height:6px;border-radius:50%;
  background:var(--red);margin-top:7px;
}
.bullet-list li strong{color:var(--g800)}

/* Step cards */
.steps{display:flex;flex-direction:column;gap:10px;margin-bottom:18px}
.step{
  display:flex;align-items:flex-start;gap:14px;
  background:var(--g50);border:1px solid var(--g200);
  border-radius:10px;padding:13px 16px;
  transition:box-shadow .15s;
}
.step:hover{box-shadow:0 3px 12px rgba(0,0,0,.06)}
.step-num{
  flex-shrink:0;width:28px;height:28px;border-radius:50%;
  background:var(--red);color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-family:var(--font-head);font-size:.78rem;font-weight:800;
}
.step-title{font-size:.84rem;font-weight:700;color:var(--g800);margin-bottom:2px;font-family:var(--font-head)}
.step-text{font-size:.78rem;color:var(--g600);line-height:1.55}
.step-code{
  display:inline-block;background:var(--g100);border:1px solid var(--g200);
  border-radius:5px;padding:2px 8px;font-size:.75rem;
  font-family:'Courier New',monospace;color:var(--red);margin-top:4px;
}

/* Prompt box */
.prompt-box{
  background:var(--g100);border:1px solid var(--g200);
  border-left:3px solid var(--red);
  border-radius:0 8px 8px 0;padding:11px 14px;
  margin:8px 0;
  display:flex;align-items:center;gap:10px;
}
.prompt-label{
  flex-shrink:0;font-size:.65rem;font-weight:800;
  text-transform:uppercase;letter-spacing:.06em;
  color:var(--red);white-space:nowrap;
}
.prompt-text{font-size:.82rem;color:var(--g800);font-style:italic}

/* Status badge */
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700}
.badge-ok{background:var(--green-bg);color:var(--green)}
.badge-warn{background:var(--amber-bg);color:var(--amber)}
.badge-dev{background:var(--blue-bg);color:var(--blue)}
.badge-err{background:var(--red-lt);color:var(--red)}

/* Ref table */
.ref-table{width:100%;border-collapse:collapse;font-size:.8rem;margin:10px 0 18px}
.ref-table th{
  background:var(--red);color:#fff;padding:8px 12px;
  text-align:left;font-size:.7rem;font-weight:700;
  text-transform:uppercase;letter-spacing:.04em;
}
.ref-table th:first-child{border-radius:8px 0 0 0}
.ref-table th:last-child{border-radius:0 8px 0 0}
.ref-table td{padding:8px 12px;border-bottom:1px solid var(--g100);vertical-align:top;color:var(--g600)}
.ref-table tr:last-child td{border-bottom:none}
.ref-table tr:nth-child(even) td{background:var(--g50)}
.ref-table td:first-child{font-weight:600;color:var(--g800);white-space:nowrap}

/* Info/warn callout */
.callout{
  border-radius:8px;padding:12px 15px;
  margin:12px 0;font-size:.82rem;line-height:1.65;
}
.callout-info{background:var(--blue-bg);border-left:3px solid var(--blue);color:#1E3A5F}
.callout-warn{background:var(--amber-bg);border-left:3px solid #D97706;color:#92400E}
.callout-ok{background:var(--green-bg);border-left:3px solid #16A34A;color:#14532D}
[data-theme="dark"] .callout-info{color:#93C5FD}
[data-theme="dark"] .callout-warn{color:#FDE68A}
[data-theme="dark"] .callout-ok{color:#86EFAC}
.callout strong{font-weight:700}

/* Document refs section */
.doc-ref-row{
  display:flex;align-items:center;gap:12px;
  background:var(--g50);border:1px solid var(--g200);
  border-radius:10px;padding:13px 16px;margin-bottom:10px;
  transition:box-shadow .2s;cursor:pointer;
}
.doc-ref-row:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);border-color:var(--red)}
.doc-ref-icon{
  width:36px;height:36px;border-radius:8px;
  background:var(--red-lt);flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
[data-theme="dark"] .doc-ref-icon{background:rgba(204,9,47,.2)}
.doc-ref-icon svg{color:var(--red)}
.doc-ref-title{font-size:.85rem;font-weight:700;color:var(--g800);margin-bottom:2px;font-family:var(--font-head)}
.doc-ref-sub{font-size:.75rem;color:var(--g600);line-height:1.4}
.doc-ref-arrow{margin-left:auto;color:var(--g400);flex-shrink:0}

/* FINAL slide */
.slide-final{
  background:var(--white);
  display:flex;flex-direction:column;
  align-items:center;justify-content:center;
  padding:10%;text-align:center;
  min-height:100%;
}
.final-h{
  font-family:var(--font-head);
  font-size:clamp(2rem,4vw,3.2rem);
  font-weight:800;color:var(--g800);
  line-height:1.1;margin-bottom:16px;
}
.final-h em{font-style:normal;color:var(--red)}
.final-sub{font-size:.95rem;color:var(--g600);line-height:1.7;max-width:520px;margin:0 auto 32px}
.final-docs{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:36px}
.final-doc-btn{
  display:flex;align-items:center;gap:8px;
  background:var(--g50);border:1px solid var(--g200);
  border-radius:10px;padding:12px 18px;
  font-size:.82rem;font-weight:700;color:var(--g800);
  cursor:pointer;transition:all .2s;
  font-family:var(--font-body);
}
.final-doc-btn:hover{border-color:var(--red);background:var(--red-lt);color:var(--red)}
.final-doc-btn svg{color:var(--red)}
.final-bar{
  width:60px;height:3px;background:var(--red);
  border-radius:2px;margin:0 auto 24px;
}

/* ── NAV CONTROLS ── */
.nav-controls{
  position:fixed;bottom:22px;right:28px;z-index:300;
  display:flex;gap:8px;align-items:center;
}
.nav-btn{
  width:42px;height:42px;border-radius:11px;
  background:var(--white);color:var(--g800);
  border:1px solid var(--g200);cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:1rem;
  box-shadow:0 2px 12px rgba(0,0,0,.08);
  transition:all .2s;
}
.nav-btn:hover{background:var(--red);color:#fff;border-color:var(--red)}
.nav-btn:disabled{opacity:.3;pointer-events:none}
.nav-count{
  font-size:.8rem;font-weight:700;color:var(--g600);
  font-family:var(--font-head);min-width:40px;text-align:center;
}

/* Slide indicators */
.indicators{
  position:fixed;bottom:26px;left:50%;transform:translateX(-50%);
  z-index:300;display:flex;gap:7px;align-items:center;
}
.ind{
  width:8px;height:8px;border-radius:50%;
  background:var(--g200);cursor:pointer;
  transition:all .3s var(--ease);
}
.ind.active{background:var(--red);width:22px;border-radius:4px}

/* ── ANIMATIONS ── */
@keyframes reveal{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.reveal{animation:reveal .55s var(--ease) both}
.delay-1{animation-delay:.08s}
.delay-2{animation-delay:.16s}
.delay-3{animation-delay:.24s}
.delay-4{animation-delay:.32s}
.delay-5{animation-delay:.40s}
.delay-6{animation-delay:.48s}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .g2,.g3{grid-template-columns:1fr}
  .slide-body{padding:16px 18px 20px}
  .slide-hdr{padding:11px 18px}
}
@media(max-width:700px){
  .sb{transform:translateX(-100%);border-radius:0 20px 20px 0}
  .sb.mob-open{transform:translateX(0)}
  .main,.main.col{margin-left:0!important}
  .nav-controls{bottom:16px;right:16px}
}

/* ── PRINT ── */
@media print{
  .sb,.nav-controls,.indicators,.progress-track{display:none!important}
  .main,.main.col{margin-left:0!important}
  .slide{position:relative!important;opacity:1!important;visibility:visible!important;transform:none!important;page-break-after:always}
  .slide-body{overflow:visible!important}
}
</style>
</head>
<body>

<!-- ════════════════════════════════════════
     SIDEBAR
     ════════════════════════════════════════ -->
<aside class="sb" id="sb">
  <div class="sb-head">
    <div class="sb-logo">
      <!-- Bradesco tree SVG logo (inline, white) -->
      <svg width="28" height="28" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2.4 17.0c.5-.3 1-.7 1.6-.9 1.7-.7 3.4-1.4 5.1-2.1.3-.1.6-.4.8-.7C12 8.0 16.2 4.2 22.3 2.7c7.7-1.9 14.3.2 19.6 6.2.3.4.4.8.1 1.2-.4.4-.8.15-1.1-.1C35.3.7 27.4.2 21.6 3.9c-1.4.9-2.6 2.2-3.9 3.3-.2.15-.3.38-.5.7.96-.13 1.76-.26 2.56-.36C27.6 6.5 35.2 7.2 42.2 10.2c2.7 1.2 5.2 2.7 7.2 4.9 1.2 1.2 2.2 2.6 2.2 4.3 0 2.2-1 4-2.4 5.5-2.5 2.6-5.6 4.3-8.9 5.5-.8.3-1.7.6-2.5.86-.19.06-.41.15-.59.11-.26-.05-.67-.16-.72-.33-.07-.25.06-.62.23-.84.17-.22.48-.34.75-.45 1.5-.62 3-.12 4.55-1.84 2.08-.87 3.47-2.32 3.9-4.64.58-3.18-.9-5.36-3.3-7.07-2.9-2.06-6.3-3.2-9.7-4C29.6 12.7 25.6 12.2 21.5 12.3c-1.77.03-3.54.24-5.3.34-.41.02-.63.2-.8.58-1.08 2.36-1.36 4.86-1.14 7.4.37 4.19 2.08 7.74 5.11 10.65.23.23.43.61.44.93.03.65-.63.98-1.24.55-1.04-.73-2.09-1.47-2.99-2.36C11.5 25.9 9.8 20.5 10.5 14.5c.01-.07 0-.13 0-.33-.48.09-.94.16-1.39.27-1.19.29-2.39.54-3.55.93C3.1 15.9 2.7 15.87 2.4 15.4 2.2 15.2 2 15 2.4 17z" fill="white" opacity=".88"/>
        <path d="M25.5 44.9c0-1.3 0-2.6 0-3.8 0-3.2.01-6.4-.01-9.7 0-.41.1-.65.48-.85 1.15-.6 2.26-1.26 3.4-1.88.57-.32.97-.07.97.59-.01 4.7-.01 9.4-.02 14.1 0 1.28-.15 1.43-1.4 1.43-1.12 0-2.23 0-3.42 0z" fill="white" opacity=".88"/>
        <path d="M24.0 33.0c0 4.56 0 8.96 0 13.46-.9 0-1.79.04-2.66-.03-.19-.02-.47-.46-.49-.72-.06-.86-.02-1.73-.02-2.6 0-2.49.01-4.97-.01-7.46 0-.56.15-.93.68-1.2 1.14-.57 1.94-1.05 2.78-1.45z" fill="white" opacity=".88"/>
      </svg>
      <div class="sb-brand">
        <span class="sb-brand-main">DEVA</span>
        <span class="sb-brand-sub">Bradesco BRAI4DQ</span>
      </div>
    </div>
    <button class="sb-toggle" onclick="toggleSb()" title="Expandir/Recolher">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
  </div>

  <nav class="sb-nav" id="sbNav"></nav>

  <div class="sb-foot">
    <div class="sb-slide-chip" id="sbChip">slide <span id="sbChipNum">1</span> / <span id="sbChipTotal">1</span></div>
    <div class="sb-foot-row" onclick="toggleTheme()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>
      <span class="sb-foot-label">Tema escuro</span>
      <div class="theme-track">
        <div class="theme-thumb"></div>
      </div>
    </div>
    <div class="sb-foot-row" onclick="window.print()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      <span class="sb-foot-label">Imprimir</span>
    </div>
  </div>
</aside>

<!-- ════════════════════════════════════════
     MAIN
     ════════════════════════════════════════ -->
<div class="main" id="main">
  <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
  <div class="slides" id="slides"></div>
</div>

<!-- NAV -->
<div class="nav-controls">
  <button class="nav-btn" id="btnPrev" onclick="prevSlide()" disabled title="Anterior">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
  </button>
  <span class="nav-count" id="navCount">1 / 1</span>
  <button class="nav-btn" id="btnNext" onclick="nextSlide()" title="Próximo">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
  </button>
</div>
<div class="indicators" id="indicators"></div>

<!-- ════════════════════════════════════════
     BRADESCO LOGO (reused via JS)
     ════════════════════════════════════════ -->
<svg id="bradesco-logo-src" style="display:none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 750 750">
  <defs>
    <clipPath id="bc3"><path d="M 0 96 L 750 96 L 750 654 L 0 654 Z" clip-rule="nonzero"/></clipPath>
  </defs>
  <g clip-path="url(#bc3)">
    <rect width="750" height="750" fill="#CC092F"/>
    <text x="375" y="450" text-anchor="middle" font-family="Arial" font-weight="900" font-size="260" fill="white">B</text>
  </g>
</svg>

<script>
/* ============================================================
   SLIDE DATA
   Para adicionar um novo slide: adicione um objeto em SLIDES[].
   type: 'cover' | 'content' | 'final'
   navLabel: texto no sidebar
   navIcon: SVG path string
   html: conteúdo HTML do slide
   ============================================================ */

const BRADESCO_RED = '#CC092F';

// Inline Bradesco logo (small header version) — base64 placeholder replaced by inline SVG div
const LOGO_HDR = `<div style="width:38px;height:38px;border-radius:6px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
  <svg width="24" height="24" viewBox="0 0 48 48" fill="none"><path d="M2 17c.5-.3 1-.7 1.6-.9 1.7-.7 3.4-1.4 5.1-2.1.3-.1.6-.4.8-.7C12 8 16 4 22 2.7c7.7-1.9 14.3.2 19.6 6.2.3.4.4.8.1 1.2-.4.4-.8.15-1.1-.1C35.3.7 27.4.2 21.6 3.9c-1.4.9-2.6 2.2-3.9 3.3-.2.15-.3.38-.5.7.96-.13 1.76-.26 2.56-.36 7.34-.89 14.94-.19 21.94 2.81 2.7 1.2 5.2 2.7 7.2 4.9 1.2 1.2 2.2 2.6 2.2 4.3 0 2.2-1 4-2.4 5.5-2.5 2.6-5.6 4.3-8.9 5.5-.8.3-1.7.6-2.5.86-.19.06-.41.15-.59.11-.26-.05-.67-.16-.72-.33-.07-.25.06-.62.23-.84.17-.22.48-.34.75-.45 1.5-.62 3-.12 4.55-1.84 2.08-.87 3.47-2.32 3.9-4.64.58-3.18-.9-5.36-3.3-7.07-2.9-2.06-6.3-3.2-9.7-4C29.6 12.7 25.6 12.2 21.5 12.3c-1.77.03-3.54.24-5.3.34-.41.02-.63.2-.8.58-1.08 2.36-1.36 4.86-1.14 7.4.37 4.19 2.08 7.74 5.11 10.65.23.23.43.61.44.93.03.65-.63.98-1.24.55-1.04-.73-2.09-1.47-2.99-2.36C11.5 25.9 9.8 20.5 10.5 14.5c.01-.07 0-.13 0-.33-.48.09-.94.16-1.39.27-1.19.29-2.39.54-3.55.93C3.1 15.9 2.7 15.87 2.4 15.4 2.2 15.2 2 15 2 17z" fill="${BRADESCO_RED}"/><path d="M25.5 44.9c0-1.3 0-2.6 0-3.8 0-3.2.01-6.4-.01-9.7 0-.41.1-.65.48-.85 1.15-.6 2.26-1.26 3.4-1.88.57-.32.97-.07.97.59-.01 4.7-.01 9.4-.02 14.1 0 1.28-.15 1.43-1.4 1.43-1.12 0-2.23 0-3.42 0z" fill="${BRADESCO_RED}"/><path d="M24 33c0 4.56 0 8.96 0 13.46-.9 0-1.79.04-2.66-.03-.19-.02-.47-.46-.49-.72-.06-.86-.02-1.73-.02-2.6 0-2.49.01-4.97-.01-7.46 0-.56.15-.93.68-1.2 1.14-.57 1.94-1.05 2.78-1.45z" fill="${BRADESCO_RED}"/></svg>
</div>`;

// Icon SVG strings for sidebar nav
function icon(path) {
  return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
}

const SLIDES = [

  /* ── SLIDE 0: CAPA ──────────────────────────────── */
  {
    id: 'capa',
    navGroup: '',
    navLabel: 'Apresentação',
    icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    type: 'cover',
    html: `
      <div class="slide-cover">
        <div class="cover-bg-shape"></div>
        <div class="cover-bg-shape2"></div>
        <div class="cover-inner">
          <div class="cover-pill reveal">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Bradesco · Governança de Dados · BRAI4DQ v5.5.4
          </div>
          <h1 class="cover-h1 reveal delay-1">Agente<br><em>DEVA</em></h1>
          <p class="cover-sub reveal delay-2">Bem-vindo à apresentação do <strong style="color:#fff">Data Exploration &amp; Validation Agent</strong> — o assistente inteligente de qualidade de dados do Bradesco. Este material apresenta o DEVA para o time de analistas que irá utilizá-lo no dia a dia.</p>
          <div class="cover-cta reveal delay-3">
            <button class="btn-cta solid" onclick="goSlide(1)">Iniciar apresentação</button>
            <button class="btn-cta" onclick="goSlide(SLIDES.length - 1)">Ver documentos</button>
          </div>
        </div>
        <div class="cover-bar"></div>
      </div>
    `
  },

  /* ── SLIDE 1: O QUE É O DEVA ────────────────────── */
  {
    id: 'o-que-e',
    navGroup: 'Conceitos',
    navLabel: 'O que é o DEVA?',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    type: 'content',
    title: 'O que é o DEVA?',
    html: `
      <div class="sec-h reveal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Definição
      </div>
      <div class="card-grid g2 reveal delay-1">
        <div class="card" style="border-left-color:var(--red)">
          <div class="card-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
          <div class="card-title">DEVA</div>
          <div class="card-text"><strong>Data Exploration &amp; Validation Agent</strong> — assistente conversacional de qualidade de dados. Você faz perguntas em português e ele executa as análises automaticamente.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
          <div class="card-title">BRAI4DQ</div>
          <div class="card-text">O DEVA faz parte do framework <strong>Bradesco AI for Data Quality</strong> — conjunto de ferramentas corporativas para garantir a qualidade dos dados da organização.</div>
        </div>
      </div>

      <div class="sec-h reveal delay-2">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        O que muda com o DEVA
      </div>
      <div class="ref-table reveal delay-3">
        <table class="ref-table">
          <thead><tr><th>Antes (sem DEVA)</th><th>Depois (com DEVA)</th></tr></thead>
          <tbody>
            <tr><td>Engenheiro precisava escrever SQL ou Python para cada análise</td><td>Basta perguntar: <em>"A tabela está completa?"</em></td></tr>
            <tr><td>Dias para criar regras de validação manualmente</td><td>Regras geradas em segundos pelos motores AI4D</td></tr>
            <tr><td>Conhecimento concentrado em poucos especialistas técnicos</td><td>Qualquer analista consegue validar dados</td></tr>
            <tr><td>Relatórios de qualidade sob demanda, com atraso</td><td>Respostas imediatas e interativas no chat</td></tr>
          </tbody>
        </table>
      </div>

      <div class="callout callout-info reveal delay-4">
        <strong>Analogia:</strong> pense no DEVA como um analista sênior sempre disponível que conhece todas as tabelas do catálogo e responde instantaneamente — sem precisar de agendamento ou chamado técnico.
      </div>
    `
  },

  /* ── SLIDE 2: COMO ACESSAR ──────────────────────── */
  {
    id: 'como-acessar',
    navGroup: 'Conceitos',
    navLabel: 'Como acessar',
    icon: '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>',
    type: 'content',
    title: 'Como acessar o DEVA',
    html: `
      <div class="sec-h reveal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        Passo a passo de acesso
      </div>
      <div class="steps reveal delay-1">
        <div class="step">
          <div class="step-num">1</div>
          <div>
            <div class="step-title">Acesse o notebook no Databricks</div>
            <div class="step-text">Abra o link do notebook compartilhado pelo seu time. O link está disponível na seção "Como Usar" do <strong>Guia de Utilização</strong> entregue após esta apresentação.</div>
            <div class="step-code">[LINK DO NOTEBOOK — inserir após publicação]</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div>
            <div class="step-title">Clone o notebook para o seu workspace</div>
            <div class="step-text">No Databricks, clique em <strong>Clone</strong> no menu superior direito. Salve a cópia no seu workspace pessoal. Sempre trabalhe com o clone — nunca edite o original.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div>
            <div class="step-title">Clique em Run All e aguarde</div>
            <div class="step-text">Com o clone aberto, clique em <strong>Run All (▶▶)</strong> no menu superior. Os pacotes serão carregados automaticamente — esse processo leva alguns minutos na primeira execução.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div>
            <div class="step-title">A interface aparece abaixo das células</div>
            <div class="step-text">Após o carregamento, uma tela de chat aparece abaixo das células do notebook. A partir daqui, basta digitar perguntas em português e pressionar Enviar.</div>
          </div>
        </div>
      </div>
      <div class="callout callout-warn reveal delay-5">
        <strong>Importante:</strong> sempre trabalhe com o clone. O notebook original é o template oficial compartilhado e não deve ser alterado ou executado diretamente.
      </div>
    `
  },

  /* ── SLIDE 3: PRIMEIRAS PERGUNTAS ───────────────── */
  {
    id: 'primeiras-perguntas',
    navGroup: 'Conceitos',
    navLabel: 'Primeiras perguntas',
    icon: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    type: 'content',
    title: 'Primeiras perguntas — fluxo recomendado',
    html: `
      <div class="sec-h reveal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Fluxo de trabalho recomendado
      </div>
      <div class="prompt-box reveal delay-1"><span class="prompt-label">Passo 1</span><span class="prompt-text">"Liste as tabelas disponíveis"</span></div>
      <div class="prompt-box reveal delay-2"><span class="prompt-label">Passo 2</span><span class="prompt-text">"Faça o profiling da tabela catalog.schema.nome_tabela"</span></div>
      <div class="prompt-box reveal delay-3"><span class="prompt-label">Passo 3</span><span class="prompt-text">"A tabela está completa?" · "Tem duplicatas?" · "Verifique consistência"</span></div>
      <div class="prompt-box reveal delay-4"><span class="prompt-label">Passo 4</span><span class="prompt-text">"Sugira regras de validação"</span></div>
      <div class="prompt-box reveal delay-5"><span class="prompt-label">Passo 5</span><span class="prompt-text">"Aprove as regras 1, 3 e 5" · "Exporte as regras como YAML"</span></div>

      <div class="card-grid g2 reveal delay-5" style="margin-top:18px">
        <div class="card">
          <div class="card-title">Botões de sugestao</div>
          <div class="card-text">Após cada resposta, o DEVA mostra botões com os próximos passos recomendados. Clique neles em vez de digitar para agilizar o fluxo.</div>
        </div>
        <div class="card">
          <div class="card-title">Botoes de avaliacao</div>
          <div class="card-text">Cada resposta tem botões de avaliação positiva e negativa. Use sempre — o feedback melhora o agente para toda a equipe.</div>
        </div>
      </div>
    `
  },

  /* ── SLIDE 4: ANÁLISES DISPONÍVEIS ─────────────── */
  {
    id: 'analises',
    navGroup: 'Funcionalidades',
    navLabel: 'Análises disponíveis',
    icon: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    type: 'content',
    title: 'O que o DEVA analisa',
    html: `
      <div class="sec-h reveal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        6 grupos de operações · 37 no total
      </div>
      <div class="card-grid g3 reveal delay-1">
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg></div>
          <div class="card-title">Catálogo</div>
          <div class="card-text">Lista tabelas e schemas disponíveis sem precisar escrever SQL.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
          <div class="card-title">Profiling</div>
          <div class="card-text">Raio-X completo: tipos, nulos, distribuição, padrões. Em ~18 segundos.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div class="card-title">10 Dimensões DQ</div>
          <div class="card-text">Completude, unicidade, consistência, integridade e outras 6 dimensões.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
          <div class="card-title">Regras de Validação</div>
          <div class="card-text">Geração automática de regras pelos motores AI4D. Você revisa e aprova.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div>
          <div class="card-title">Exportação</div>
          <div class="card-text">Regras exportáveis em Python, YAML, JSON, XML ou SQL INSERT.</div>
        </div>
        <div class="card" style="border-left-color:var(--amber)">
          <div class="card-icon" style="background:var(--amber-bg)"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
          <div class="card-title">Análises Avançadas</div>
          <div class="card-text">Drift, associações e resolução de entidades. <span class="badge badge-dev" style="font-size:.62rem">Anomalias em desenvolvimento</span></div>
        </div>
      </div>
    `
  },

  /* ── SLIDE 5: DIMENSÕES DQ ──────────────────────── */
  {
    id: 'dimensoes',
    navGroup: 'Funcionalidades',
    navLabel: 'Dimensões de qualidade',
    icon: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>',
    type: 'content',
    title: '10 Dimensões de Qualidade de Dados',
    html: `
      <div class="callout callout-info reveal">Framework QuantumBlack/McKinsey — cada dimensão responde uma pergunta diferente sobre a saúde dos seus dados.</div>
      <table class="ref-table reveal delay-1">
        <thead><tr><th>#</th><th>Dimensão</th><th>O que verifica</th><th>Prompt de exemplo</th></tr></thead>
        <tbody>
          <tr><td>1</td><td><strong>Completude</strong></td><td>Campos obrigatórios preenchidos?</td><td><em>"A tabela está completa?"</em></td></tr>
          <tr><td>2</td><td><strong>Unicidade</strong></td><td>Registros duplicados?</td><td><em>"Tem duplicatas?"</em></td></tr>
          <tr><td>3</td><td><strong>Consistência</strong></td><td>Formato correto (ex: CPF 11 dígitos)?</td><td><em>"Verifique consistência"</em></td></tr>
          <tr><td>4</td><td><strong>Integridade</strong></td><td>Relações entre campos fazem sentido?</td><td><em>"Analise integridade"</em></td></tr>
          <tr><td>5</td><td><strong>Tempestividade</strong></td><td>Dados atualizados dentro do prazo?</td><td><em>"Verifique tempestividade"</em></td></tr>
          <tr><td>6</td><td><strong>Validade</strong></td><td>Valores dentro dos ranges esperados?</td><td><em>"Verifique validade"</em></td></tr>
          <tr><td>7</td><td><strong>Acurácia</strong></td><td>Dados correspondem à fonte original?</td><td><em>"Verifique acurácia"</em></td></tr>
          <tr><td>8</td><td><strong>Disponibilidade</strong></td><td>Dados acessíveis quando necessário?</td><td><em>"Verifique disponibilidade"</em></td></tr>
          <tr><td>9</td><td><strong>Confiabilidade</strong></td><td>Consistência ao longo do tempo?</td><td><em>"Verifique confiabilidade"</em></td></tr>
          <tr><td>10</td><td><strong>Interpretabilidade</strong></td><td>Campos bem documentados no catálogo?</td><td><em>"Faça o profiling"</em></td></tr>
        </tbody>
      </table>
    `
  },

  /* ── SLIDE 6: REGRAS DE VALIDAÇÃO ───────────────── */
  {
    id: 'regras',
    navGroup: 'Funcionalidades',
    navLabel: 'Regras de validação',
    icon: '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
    type: 'content',
    title: 'Geração e aprovação de regras',
    html: `
      <div class="sec-h reveal">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M1.05 12H7m10 0h5.95M12 1.05V7m0 10v5.95"/></svg>
        Como funciona
      </div>
      <div class="callout callout-ok reveal delay-1">
        <strong>O DEVA nao inventa regras.</strong> Os motores estatísticos AI4D analisam os dados reais da tabela e propõem as regras. A inteligência artificial apenas organiza e explica os resultados em linguagem natural.
      </div>
      <div class="card-grid g2 reveal delay-2" style="margin:14px 0">
        <div class="card">
          <div class="card-title">Tipos de regras geradas</div>
          <ul class="bullet-list" style="margin-top:8px">
            <li>Campos obrigatórios não nulos</li>
            <li>Ranges válidos para valores numéricos</li>
            <li>Formatos de string (CPF, e-mail, código)</li>
            <li>Consistência temporal entre datas</li>
            <li>Lógica condicional (SE/ENTÃO)</li>
          </ul>
        </div>
        <div class="card">
          <div class="card-title">Human-in-the-Loop (HITL)</div>
          <ul class="bullet-list" style="margin-top:8px">
            <li><strong>Revisar:</strong> "Mostre as regras geradas"</li>
            <li><strong>Aprovar tudo:</strong> "Aprove todas as regras"</li>
            <li><strong>Selecionar:</strong> "Aprove as regras 1, 3 e 5"</li>
            <li><strong>Ajustar:</strong> "Adicione regra para e-mail"</li>
            <li><strong>Exportar:</strong> "Exporte como YAML"</li>
          </ul>
        </div>
      </div>
      <div class="sec-h reveal delay-3">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="3" x2="16" y2="21"/></svg>
        Resultados reais na PoC (tabela de faturas PF — 20k registros)
      </div>
      <div class="card-grid g3 reveal delay-4">
        <div class="kpi"><span class="kpi-label">Regras geradas</span><span class="kpi-val">34</span><span class="kpi-sub">~25 segundos</span></div>
        <div class="kpi"><span class="kpi-label">Profiling completo</span><span class="kpi-val">~18s</span><span class="kpi-sub">vs. 2–4h manual</span></div>
        <div class="kpi"><span class="kpi-label">Ganho estimado</span><span class="kpi-val">7000x</span><span class="kpi-sub">vs. escrita manual</span></div>
      </div>
    `
  },

  /* ── SLIDE 7: SEGURANÇA ─────────────────────────── */
  {
    id: 'seguranca',
    navGroup: 'Segurança',
    navLabel: 'Segurança e limites',
    icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    type: 'content',
    title: 'Segurança e proteção dos dados',
    html: `
      <div class="card-grid g2 reveal">
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
          <div class="card-title">Dados ficam no ambiente</div>
          <div class="card-text">Toda a execução ocorre dentro da infraestrutura Databricks/Bradesco. Nenhum dado bruto sai do ambiente corporativo.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
          <div class="card-title">IA vê apenas metadados</div>
          <div class="card-text">O modelo de linguagem recebe apenas nomes de tabelas e listas de colunas — nunca os valores reais dos seus dados.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div class="card-title">Somente leitura</div>
          <div class="card-text">O DEVA não modifica, apaga ou move nenhum registro. É um analisador — não um editor de dados.</div>
        </div>
        <div class="card">
          <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
          <div class="card-title">Auditoria completa</div>
          <div class="card-text">Todas as interações são registradas com data e hora para fins de governança e rastreabilidade.</div>
        </div>
      </div>
      <div class="sec-h reveal delay-2">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Limites de uso
      </div>
      <table class="ref-table reveal delay-3">
        <thead><tr><th>Limite</th><th>Valor</th><th>O que fazer se atingir</th></tr></thead>
        <tbody>
          <tr><td>Mensagens por minuto</td><td>60 por minuto</td><td>Aguardar 1 minuto e continuar</td></tr>
          <tr><td>Tempo por operação</td><td>5 minutos</td><td>Tentar com tabela menor ou amostragem</td></tr>
        </tbody>
      </table>
    `
  },

  /* ── SLIDE 8: ERROS E SOLUÇÕES ──────────────────── */
  {
    id: 'erros',
    navGroup: 'Erros',
    navLabel: 'Erros comuns',
    icon: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    type: 'content',
    title: 'Erros comuns e como resolver',
    html: `
      <table class="ref-table reveal">
        <thead><tr><th>Mensagem / Situação</th><th>Causa provável</th><th>Solução</th></tr></thead>
        <tbody>
          <tr><td>"Fora do escopo"</td><td>Pergunta sem termos de qualidade de dados</td><td>Use palavras como: tabela, coluna, dados, qualidade, analise, regra</td></tr>
          <tr><td>"Nao identifiquei com clareza"</td><td>Pergunta muito vaga ou ambígua</td><td>"Faça profiling da tabela X" em vez de "analise X"</td></tr>
          <tr><td>"Rate limit exceeded"</td><td>Mais de 60 mensagens em 1 minuto</td><td>Aguardar 1 minuto e tentar novamente</td></tr>
          <tr><td>Operação travou / timeout</td><td>Tabela grande ou cluster sobrecarregado</td><td>Usar tabela de amostragem; aguardar e tentar</td></tr>
          <tr><td>Resposta parece incorreta</td><td>Interpretação diferente do esperado</td><td>Clicar em avaliação negativa e reformular com mais detalhes</td></tr>
          <tr><td>Interface nao carregou</td><td>Pacotes não instalados ou cluster reiniciado</td><td>Executar Run All novamente</td></tr>
        </tbody>
      </table>
    `
  },

  /* ── SLIDE 9: ERROS GRAVES ──────────────────────── */
  {
    id: 'erros-graves',
    navGroup: 'Erros',
    navLabel: 'DEVA não inicia',
    icon: '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>',
    type: 'content',
    title: 'O DEVA não inicia — diagnóstico',
    html: `
      <div class="callout callout-warn reveal">
        <strong>Cenário crítico:</strong> o Run All foi executado mas a interface de chat não apareceu, ou o DEVA apareceu mas não responde a nenhuma mensagem. Siga o diagnóstico abaixo em ordem.
      </div>
      <div class="steps reveal delay-1">
        <div class="step">
          <div class="step-num" style="background:#B45309">1</div>
          <div>
            <div class="step-title">Verifique se o cluster está ativo</div>
            <div class="step-text">O símbolo verde ao lado do nome do cluster indica que está em execução. Se estiver cinza, inicie o cluster e aguarde 3 a 8 minutos para a inicialização completa.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num" style="background:#B45309">2</div>
          <div>
            <div class="step-title">Verifique a versão do runtime</div>
            <div class="step-text">O DEVA requer <strong>Databricks Runtime 17.x ou superior</strong>. Versões anteriores não são compatíveis.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num" style="background:#B45309">3</div>
          <div>
            <div class="step-title">Role para cima e veja se há células com erro</div>
            <div class="step-text">Células com fundo vermelho indicam falha na instalação dos pacotes (wheels). Copie a mensagem de erro completa para acionar o suporte.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num" style="background:#B45309">4</div>
          <div>
            <div class="step-title">Reinicie o notebook e execute Run All novamente</div>
            <div class="step-text">Desconecte do cluster, reconecte e execute Run All. Se o erro persistir após reinicialização, acione o time técnico.</div>
          </div>
        </div>
      </div>
      <div class="callout callout-warn reveal delay-5">
        <strong>Ao acionar o suporte, informe:</strong> nome e versão do cluster, nome do notebook (com path), mensagem de erro completa e horário da ocorrência. Contato: time de Governança de Dados / COE BRAI4DQ.
      </div>
    `
  },

  /* ── SLIDE 10: DOCUMENTOS ENTREGUES ─────────────── */
  {
    id: 'documentos',
    navGroup: 'Materiais',
    navLabel: 'Documentos entregues',
    icon: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
    type: 'content',
    title: 'Materiais de referência entregues',
    html: `
      <div class="callout callout-info reveal">
        Após esta apresentação, dois documentos de referência serão disponibilizados para consulta no dia a dia. Eles aprofundam os tópicos apresentados aqui.
      </div>

      <div class="reveal delay-1">
        <div class="doc-ref-row">
          <div class="doc-ref-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
          <div>
            <div class="doc-ref-title">Guia de Utilizacao Rapida (DOCX + PDF)</div>
            <div class="doc-ref-sub">Passo a passo de acesso, todas as ações possíveis com prompts de exemplo, tabelas de referência de acurácia, completude e unicidade, erros comuns e glossário.</div>
          </div>
          <div class="doc-ref-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        </div>
        <div class="doc-ref-row">
          <div class="doc-ref-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
          <div>
            <div class="doc-ref-title">Central de Dúvidas (HTML interativo)</div>
            <div class="doc-ref-sub">35 perguntas e respostas organizadas por categoria — desde conceitos básicos até erros técnicos graves. Com busca, modo escuro e navegação por árvore lateral.</div>
          </div>
          <div class="doc-ref-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
        </div>
      </div>

      <div class="sec-h reveal delay-3" style="margin-top:22px">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Cobertura dos documentos
      </div>
      <div class="card-grid g3 reveal delay-4">
        <div class="kpi"><span class="kpi-label">Perguntas respondidas</span><span class="kpi-val">35</span><span class="kpi-sub">Central de Dúvidas</span></div>
        <div class="kpi"><span class="kpi-label">Seções do Guia</span><span class="kpi-val">4</span><span class="kpi-sub">Como usar, Ações, Erros, Glossário</span></div>
        <div class="kpi"><span class="kpi-label">Tabelas de referência</span><span class="kpi-val">3</span><span class="kpi-sub">Completude, Unicidade, Acurácia</span></div>
      </div>
    `
  },

  /* ── SLIDE 11: FINAL ────────────────────────────── */
  {
    id: 'final',
    navGroup: '',
    navLabel: 'Encerramento',
    icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    type: 'final',
    html: `
      <div class="slide-final">
        <div class="final-bar"></div>
        <h1 class="final-h">Pronto para<br>usar o <em>DEVA</em></h1>
        <p class="final-sub">Os documentos de referência foram entregues. Em caso de dúvidas, consulte a Central de Dúvidas ou acione o time de Governança de Dados — COE BRAI4DQ.</p>
        <div class="final-docs">
          <button class="final-doc-btn" onclick="goSlide(0)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Reiniciar apresentação
          </button>
          <button class="final-doc-btn" onclick="goSlide(1)">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            O que é o DEVA?
          </button>
          <button class="final-doc-btn" onclick="goSlide(SLIDES.findIndex(s=>s.id==='erros-graves'))">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            Erros críticos
          </button>
        </div>
        <div style="font-size:.75rem;color:var(--g400);font-family:var(--font-head)">
          Bradesco · Governança de Dados · BRAI4DQ v5.5.4 · Maio 2026
        </div>
      </div>
    `
  }
];

/* ============================================================
   APP STATE & RENDERING
   ============================================================ */
let current = 0;
let sbCollapsed = false;
const total = SLIDES.length;

function init() {
  buildSlides();
  buildSidebar();
  buildIndicators();
  updateProgress();
  updateNav();
  // restore theme
  const t = localStorage.getItem('deva-pres-theme');
  if (t === 'dark') { document.documentElement.setAttribute('data-theme','dark'); }
}

function buildSlides() {
  const container = document.getElementById('slides');
  SLIDES.forEach((s, i) => {
    const div = document.createElement('div');
    div.className = 'slide' + (i === 0 ? ' active' : '');
    div.id = 'slide-' + i;
    if (s.type === 'cover') {
      div.innerHTML = s.html;
    } else if (s.type === 'final') {
      div.innerHTML = s.html;
    } else {
      // content slide
      div.classList.add('slide-content');
      div.innerHTML = `
        <div class="slide-hdr">
          <h1>${s.title}</h1>
          ${LOGO_HDR}
        </div>
        <div class="slide-body">${s.html}</div>
      `;
    }
    container.appendChild(div);
  });
}

function buildSidebar() {
  const nav = document.getElementById('sbNav');
  document.getElementById('sbChipTotal').textContent = total;
  let lastGroup = null;
  SLIDES.forEach((s, i) => {
    if (s.navGroup && s.navGroup !== lastGroup) {
      if (lastGroup !== null) {
        const div = document.createElement('div');
        div.className = 'nav-divider'; nav.appendChild(div);
      }
      const lbl = document.createElement('div');
      lbl.className = 'nav-group-label'; lbl.textContent = s.navGroup;
      nav.appendChild(lbl); lastGroup = s.navGroup;
    }
    const btn = document.createElement('button');
    btn.className = 'nav-item' + (i === 0 ? ' active' : '');
    btn.id = 'nav-' + i;
    btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${s.icon}</svg><span class="nav-label">${s.navLabel}</span>`;
    btn.onclick = () => goSlide(i);
    nav.appendChild(btn);
  });
}

function buildIndicators() {
  const cont = document.getElementById('indicators');
  SLIDES.forEach((_, i) => {
    const d = document.createElement('div');
    d.className = 'ind' + (i === 0 ? ' active' : '');
    d.onclick = () => goSlide(i);
    d.title = SLIDES[i].navLabel;
    cont.appendChild(d);
  });
}

function goSlide(n) {
  if (n < 0 || n >= total || n === current) return;
  const prev = current;
  const slideEl = document.getElementById('slide-' + prev);
  slideEl.classList.add('exit-left');
  setTimeout(() => { slideEl.classList.remove('active','exit-left'); }, 450);

  current = n;
  const next = document.getElementById('slide-' + n);
  next.classList.add('active');
  next.scrollTop = 0;

  // trigger reveal animations
  next.querySelectorAll('.reveal').forEach(el => {
    el.style.animation = 'none';
    el.offsetHeight; // reflow
    el.style.animation = '';
  });

  updateAll();
}

function nextSlide() { goSlide(current + 1); }
function prevSlide() { goSlide(current - 1); }

function updateAll() {
  updateProgress();
  updateNav();
  // sidebar items
  document.querySelectorAll('.nav-item').forEach((el, i) => el.classList.toggle('active', i === current));
  // indicators
  document.querySelectorAll('.ind').forEach((el, i) => el.classList.toggle('active', i === current));
  // chip
  document.getElementById('sbChipNum').textContent = current + 1;
  document.getElementById('navCount').textContent = (current + 1) + ' / ' + total;
}

function updateProgress() {
  const pct = total > 1 ? (current / (total - 1)) * 100 : 0;
  document.getElementById('progressFill').style.width = pct + '%';
}

function updateNav() {
  document.getElementById('btnPrev').disabled = current === 0;
  document.getElementById('btnNext').disabled = current === total - 1;
  document.getElementById('navCount').textContent = (current + 1) + ' / ' + total;
}

function toggleSb() {
  sbCollapsed = !sbCollapsed;
  document.getElementById('sb').classList.toggle('col', sbCollapsed);
  document.getElementById('main').classList.toggle('col', sbCollapsed);
}

function toggleTheme() {
  const dark = document.documentElement.getAttribute('data-theme') === 'dark';
  document.documentElement.setAttribute('data-theme', dark ? 'light' : 'dark');
  localStorage.setItem('deva-pres-theme', dark ? 'light' : 'dark');
}

// keyboard nav
document.addEventListener('keydown', e => {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown') nextSlide();
  if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') prevSlide();
  if (e.key === 'Home') goSlide(0);
  if (e.key === 'End') goSlide(total - 1);
});

// touch swipe
let tx = null;
document.getElementById('slides').addEventListener('touchstart', e => { tx = e.touches[0].clientX; });
document.getElementById('slides').addEventListener('touchend', e => {
  if (tx === null) return;
  const dx = e.changedTouches[0].clientX - tx;
  if (Math.abs(dx) > 60) { dx < 0 ? nextSlide() : prevSlide(); }
  tx = null;
});

init();
</script>
</body>
</html>
