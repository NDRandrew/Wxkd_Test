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
  --pr:#c01137; --pr-dk:#8C0F3B; --pr-lt:#E8153A; --pr-xlt:#FF4060;
  --wh:#FFFFFF; --bg:#F4F5F7; --bg2:#ECEEF2;
  --g100:#F0F1F4; --g200:#E2E4EA; --g300:#C8CBD6; --g500:#8A8FA8;
  --g600:#5C6180; --g700:#363B56; --g800:#1A1D2E; --g900:#0D0F1A;
  --green:#00C07A; --yellow:#F5A623; --red:#E8143A; --blue:#3B6BF5;
  --cyan:#00BFCF; --violet:#7C4DFF;
  --sh:0 4px 24px rgba(0,0,0,.07); --sh2:0 8px 40px rgba(0,0,0,.12);
  --r:12px; --r-lg:20px;
  --sb:240px; --sb-col:56px;
  --font-display:'Inter',sans-serif; --font-body:'Inter',sans-serif;
}
[data-theme="dark"]{
  --wh:#1A1D2E; --bg:#0D0F1A; --bg2:#151726;
  --g100:#1E2135; --g200:#252840; --g300:#363B56; --g500:#6B7194;
  --g600:#8A8FA8; --g700:#C8CBD6; --g800:#E2E4EA; --g900:#F4F5F7;
  --sh:0 4px 24px rgba(0,0,0,.3); --sh2:0 8px 40px rgba(0,0,0,.5);
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%;font-family:var(--font-body);background:var(--bg);color:var(--g800)}
.app{display:flex;height:100vh;overflow:hidden}

/* ─── SIDEBAR ─── */
.sb{
  position:fixed;left:0;top:0;height:100vh;width:var(--sb);
  background:linear-gradient(160deg,var(--pr-dk) 0%,var(--pr) 60%,#7A001A 100%);
  color:#fff;padding:14px 10px 20px;z-index:200;
  display:flex;flex-direction:column;gap:4px;
  border-radius:0 28px 28px 0;
  box-shadow:6px 0 30px rgba(140,0,32,.35);
  transition:width .6s cubic-bezier(.77,0,.18,1);overflow:hidden;
}
.sb.col{width:var(--sb-col)}
.sb-head{
  display:flex;flex-direction:row;align-items:center;justify-content:space-between;
  margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.18);
  transition:all .7s ease;
}
.sb.col .sb-head{flex-direction:column-reverse;gap:15px;justify-content:center}
.sb-logo{display:flex;align-items:center;gap:9px;white-space:nowrap;overflow:hidden;margin-left:10px;transition:transform .3s cubic-bezier(.4,0,.2,1)}
.sb.col .sb-logo{justify-content:center;width:100%;overflow:visible}
.sb-logo-icon{width:30px;height:30px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--font-display);font-weight:800;font-size:.75rem;letter-spacing:-.02em}
.sb-logo-text{font-family:var(--font-display);font-size:.78rem;font-weight:700;line-height:1.2;opacity:.92;transition:opacity .2s,width .5s;white-space:nowrap;padding-top:3px}
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
.sb.col .nav-btn{position:relative;bottom:40px}
.nav-sep{height:1px;background:rgba(255,255,255,.13);margin:5px 0;flex-shrink:0}
.sb-footer{margin-top:auto;background:rgba(0,0,0,.2);border-radius:10px;padding:10px 11px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;flex-shrink:0;white-space:nowrap;overflow:hidden;transition:all .6s}
.sb.col .sb-footer{flex-direction:column;gap:8px;padding:12px 6px}
.sb-footer-label{font-size:.72rem;font-weight:600;opacity:.7;transition:opacity .2s,max-width .5s;max-width:150px;overflow:hidden}
.sb.col .sb-footer-label{opacity:0;max-width:0}
.theme-track{width:38px;height:20px;background:rgba(255,255,255,.22);border-radius:10px;position:relative;flex-shrink:0;display:flex;align-items:center;justify-content:space-between;padding:0 5px;transition:transform .3s cubic-bezier(.4,0,.2,1)}
.sb.col .theme-track{transform:rotate(90deg);bottom:10px}
.theme-thumb{width:14px;height:14px;background:#fff;border-radius:50%;position:absolute;left:3px;top:3px;transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:0 2px 4px rgba(0,0,0,.2)}
[data-theme="dark"] .theme-thumb{transform:translateX(18px)}

/* ─── SIDEBAR ACTION BUTTONS ─── */
.sb-action-btn{
  display:flex;align-items:center;gap:9px;
  color:rgba(255,255,255,.85);
  padding:9px 11px;border-radius:10px;font-weight:600;font-size:.8rem;
  cursor:pointer;transition:background .2s,color .2s;
  border:1px solid rgba(255,255,255,.2);
  background:rgba(255,255,255,.1);
  width:100%;text-align:left;white-space:nowrap;overflow:hidden;
  font-family:var(--font-body);margin-bottom:6px;
}
.sb-action-btn:hover{background:rgba(255,255,255,.22);color:#fff}
.sb-action-btn svg{flex-shrink:0;min-width:15px}
.sb.col .sb-action-btn{justify-content:center;padding:8px 0}
.sb.col .sb-action-btn-label{opacity:0;max-width:0;overflow:hidden;transition:opacity .2s,max-width .5s}

/* ─── MODALS ─── */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:1000;
  display:none;align-items:center;justify-content:center;padding:24px
}
.modal-overlay.open{display:flex}
.modal-box{
  background:var(--wh);border-radius:var(--r-lg);padding:32px;
  width:100%;max-width:900px;box-shadow:var(--sh2);
  height:82vh;max-height:820px;
  display:flex;flex-direction:column;
}
.modal-title{font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--g800);margin-bottom:6px}
.modal-desc{font-size:.78rem;color:var(--g500);margin-bottom:16px;line-height:1.55}
.modal-ta{
  width:100%;flex:1;min-height:0;
  border:1px solid var(--g200);border-radius:var(--r);
  padding:14px;font-size:.76rem;font-family:monospace;
  background:var(--g100);color:var(--g800);resize:none;outline:none;
}
.modal-ta:focus{border-color:var(--pr)}
.modal-row{display:flex;gap:10px;margin-top:14px;align-items:center;flex-wrap:wrap;flex-shrink:0}
.modal-err{font-size:.74rem;color:var(--red);flex:1}
.mbtn{padding:9px 20px;border-radius:9px;font-size:.82rem;font-weight:700;cursor:pointer;border:none;font-family:var(--font-body)}
.mbtn.cancel{background:var(--g100);color:var(--g600)}
.mbtn.apply{background:var(--pr);color:#fff}
.mbtn.apply:hover{background:var(--pr-dk)}
.mbtn.dl{background:var(--blue);color:#fff}
.modal-preview{flex:1;overflow-y:auto;border:1px solid var(--g200);border-radius:var(--r);min-height:0;background:#f9f9f9;margin-bottom:14px}
.modal-preview iframe{width:100%;height:100%;min-height:420px;border:none;border-radius:var(--r)}

/* ─── MAIN ─── */
.main{margin-left:var(--sb);flex:1;height:100vh;overflow:hidden;position:relative;transition:margin-left .6s cubic-bezier(.77,0,.18,1)}
.main.col{margin-left:var(--sb-col)}
.page{position:absolute;inset:0;overflow-y:auto;opacity:0;visibility:hidden;transform:translateY(20px);transition:opacity .4s ease,transform .4s ease,visibility .4s}
.page.act{opacity:1;visibility:visible;transform:translateY(0)}

/* ─── HOME PAGE ─── */
.page-home{
  background:linear-gradient(135deg,var(--pr-dk) 0%,var(--pr) 55%,#8B0621 100%);
  height:100%;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;
  padding:6% 8%;position:relative;overflow:hidden;
}
.home-bg-circle{position:absolute;right:-8%;top:-15%;width:55%;padding-top:55%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 70%);pointer-events:none}
.home-bg-circle2{position:absolute;right:12%;bottom:-20%;width:35%;padding-top:35%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.05) 0%,transparent 70%);pointer-events:none}
.home-inner{position:relative;z-index:2;max-width:680px}
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
.blt-body{padding:28px 36px 56px;display:flex;flex-direction:column;gap:28px}

/* ─── SECTION HEADERS ─── */
.sec-hd{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.sec-hd-bar{width:4px;height:22px;border-radius:2px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));flex-shrink:0}
.sec-hd-title{font-family:var(--font-display);font-size:1.0rem;font-weight:700;color:var(--g800);letter-spacing:-.01em}
.sec-hd-sub{font-size:.75rem;color:var(--g500);margin-top:1px}
.sec-hd-badge{margin-left:auto;background:var(--pr);color:#fff;border-radius:20px;padding:3px 11px;font-size:.68rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}

/* ─── KPI STRIP ─── */
.kpi-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;align-items:stretch}
.kpi-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:20px 17px;display:flex;flex-direction:column;gap:3px;box-shadow:var(--sh);transition:transform .22s,box-shadow .22s;position:relative;overflow:hidden;border-bottom-left-radius:0;border-bottom-right-radius:0}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:3px 3px 0 0;background:linear-gradient(90deg,var(--pr),var(--pr-lt))}
.kpi-card.green::before{background:linear-gradient(90deg,var(--green),#00E699)}
.kpi-card.blue::before{background:linear-gradient(90deg,var(--blue),var(--cyan))}
.kpi-card.yellow::before{background:linear-gradient(90deg,var(--yellow),#FFCA28)}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
.kpi-wrapper{display:flex;flex-direction:column;height:100%}
.kpi-card-blw{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:12px 17px 16px;display:flex;flex-direction:column;gap:3px;box-shadow:var(--sh);overflow:hidden;border-top-left-radius:0;border-top-right-radius:0;border-top:none;flex:1}
.kpi-lbl{font-size:.67rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.06em}
.kpi-val{font-family:var(--font-display);font-size:2.1rem;font-weight:800;color:var(--pr);line-height:1}
.kpi-card.green .kpi-val{color:var(--green)}
.kpi-card.blue .kpi-val{color:var(--blue)}
.kpi-card.yellow .kpi-val{color:var(--yellow)}
.kpi-delta{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px}
.kpi-delta.up{background:rgba(0,192,122,.12);color:var(--green)}
.kpi-delta.down{background:rgba(232,20,58,.1);color:var(--red)}
.kpi-delta.neutral{background:var(--g100);color:var(--g600)}
.kpi-foot{font-size:.7rem;color:var(--g500)}

/* ─── ANALISE FIELD (abaixo dos KPIs) ─── */
.analysis-box{
  background:var(--g100);border:1px solid var(--g200);border-top:none;
  border-radius:0 0 var(--r) var(--r);
  padding:10px 14px;
}
.analysis-box-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--pr);margin-bottom:3px;display:flex;align-items:center;gap:4px}
.analysis-box-text{font-size:.76rem;color:var(--g600);line-height:1.6;font-style:italic}

/* ─── CHART + SIDE ANALYSIS LAYOUT ─── */
.chart-with-analysis{display:grid;gap:0;align-items:stretch;margin-bottom:14px}
.chart-with-analysis.side{grid-template-columns:1fr 300px}
.cwa-chart{
  background:var(--wh);border:1px solid var(--g200);
  border-radius:var(--r-lg) 0 0 var(--r-lg);
  border-right:none;
  padding:22px;box-shadow:var(--sh);
}
.cwa-analysis{
  background:var(--g100);border:1px solid var(--g200);
  border-radius:0 var(--r-lg) var(--r-lg) 0;
  padding:20px 18px;
  display:flex;flex-direction:column;gap:12px;
}
.cwa-analysis-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--pr);margin-bottom:2px;display:flex;align-items:center;gap:5px}
.cwa-sep{height:1px;background:var(--g200)}
.cwa-block-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--g500);margin-bottom:3px}
.cwa-block-text{font-size:.76rem;color:var(--g600);line-height:1.6;font-style:italic}
.cwa-insight{background:var(--wh);border-radius:8px;padding:10px 12px;border-left:3px solid var(--yellow)}
.cwa-insight p{font-size:.74rem;color:var(--g700);line-height:1.55}

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
.legend-item.hidden{opacity:.35}

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

/* ─── RELEASES ─── */
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

/* ─── RESET EVOLUCAO BTN ─── */
#btnResetEvolucao{background:var(--g100);border:1px solid var(--g200);color:var(--g600);font-family:var(--font-body);font-size:.75rem;font-weight:600;padding:6px 12px;border-radius:8px;cursor:pointer;transition:all .2s ease-in-out;align-items:center;gap:3px}
#btnResetEvolucao:hover{background:rgba(192,17,55,.08);border-color:rgba(192,17,55,.25);color:var(--pr);transform:translateY(-1px)}

/* ─── SCROLL REVEAL ─── */
.reveal{opacity:0;transform:translateY(22px);transition:opacity .5s ease,transform .5s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--g300);border-radius:10px}

@media(max-width:1500px){
  .kpi-strip{grid-template-columns:repeat(2,1fr)}
  .po-grid{grid-template-columns:1fr}
  .charts-grid{grid-template-columns:1fr}
  .release-grid{grid-template-columns:repeat(2,1fr)}
  .chart-with-analysis.side{grid-template-columns:1fr}
  .cwa-chart{border-radius:var(--r-lg) var(--r-lg) 0 0;border-right:1px solid var(--g200);border-bottom:none}
  .cwa-analysis{border-radius:0 0 var(--r-lg) var(--r-lg)}
}
</style>
</head>
<body>
<div class="app">

<!-- ═══ SIDEBAR ═══ -->
<aside class="sb" id="sb">
  <div class="sb-head">
    <div class="sb-logo">
      <div class="sb-logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30" viewBox="0 0 750 750" height="30" preserveAspectRatio="xMidYMid meet" version="1.0">
          <defs><filter x="0%" y="0%" width="100%" height="100%" id="sid1"><feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 1 0" color-interpolation-filters="sRGB"/></filter><clipPath id="sid3"><path d="M 0 96 L 750 96 L 750 654 L 0 654 Z" clip-rule="nonzero"/></clipPath></defs>
          <g clip-path="url(#sid3)"><g transform="matrix(0.636, 0, 0, 0.635, 0, 96)">
            <path fill="#ffffff" d="M2,17.132c0.415-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2.1,17.574C2,17.411,1.9,17.248,2,17.132z"/>
            <path fill="#ffffff" d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path fill="#ffffff" d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g></g>
        </svg>
      </div>
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
  <button class="nav-btn" id="nav-blt" onclick="goPage('blt');toggleSB()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    <span class="nav-btn-label">Boletim Abril/26</span>
  </button>
  <div class="nav-sep"></div>

  <!-- ── ACTION BUTTONS ── -->
  <div class="nav-section-label">Ações</div>
  <button class="sb-action-btn" onclick="openJsonModal()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    <span class="sb-action-btn-label">Importar JSON</span>
    <span class="nav-btn-label">Importar JSON</span>
  </button>
  <button class="sb-action-btn" onclick="openEmailModal()">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    <span class="nav-btn-label">Gerar E-mail</span>
  </button>

  <div class="sb-footer" onclick="toggleTheme()">
    <span class="sb-footer-label">Modo escuro</span>
    <div class="theme-track"><div class="theme-thumb"></div></div>
  </div>
</aside>

<!-- ═══ JSON IMPORT MODAL ═══ -->
<div class="modal-overlay" id="jsonModal">
  <div class="modal-box">
    <div class="modal-title">Importar dados via JSON</div>
    <div class="modal-desc">Cole um objeto JSON com os dados do boletim. Campos ausentes mantêm os valores atuais. <a href="#" onclick="showJsonSchema(event)" style="color:var(--pr)">Ver schema esperado</a></div>
    <textarea class="modal-ta" id="jsonInput" placeholder='{"periodo":"Abril 2026","kpis":{"score_medio":"98,4%","score_delta":"▲ +1,2 score vs Mar"},"analises":{"score":"Texto da análise..."},...}'></textarea>
    <div class="modal-row">
      <span class="modal-err" id="jsonErr"></span>
      <button class="mbtn cancel" onclick="closeJsonModal()">Cancelar</button>
      <button class="mbtn apply" onclick="applyJson()">Aplicar</button>
    </div>
  </div>
</div>

<!-- ═══ EMAIL MODAL ═══ -->
<div class="modal-overlay" id="emailModal">
  <div class="modal-box" style="max-width:740px">
    <div class="modal-title">Gerar versão para E-mail</div>
    <div class="modal-desc">HTML table-based compatível com clientes de e-mail. Copie ou faça download.</div>
    <div class="modal-preview" id="emailPreview"></div>
    <div class="modal-row" style="justify-content:flex-end">
      <button class="mbtn cancel" onclick="closeEmailModal()">Fechar</button>
      <button class="mbtn apply" onclick="copyEmailHtml()">Copiar HTML</button>
      <button class="mbtn dl" onclick="downloadEmail()">Download .html</button>
    </div>
  </div>
</div>

<!-- ═══ MAIN ═══ -->
<div class="main" id="main">

  <!-- ── HOME ── -->
  <div class="page page-home act" id="page-home">
    <div class="home-bg-circle"></div>
    <div class="home-bg-circle2"></div>
    <div class="home-inner">
      <h1 class="home-h1">Boletim de<br>Qualidade<br><em>de Dados</em></h1>
      <p class="home-sub">Visão consolidada das entregas, métricas e iniciativas da área de Qualidade de Dados — <strong style="color:#fff">Abril de 2026</strong>.</p>
      <div class="home-cta-row">
        <button class="btn-primary" onclick="goPage('blt');toggleSB()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Ver Boletim Completo
        </button>
      </div>
    </div>
    <div class="home-stats">
      <div class="home-stat-card"><div class="home-stat-val">7</div><div class="home-stat-lbl">Entregas no mês</div></div>
      <div class="home-stat-card"><div class="home-stat-val">98,4%</div><div class="home-stat-lbl">Score médio geral</div></div>
      <div class="home-stat-card"><div class="home-stat-val">90</div><div class="home-stat-lbl">Produtos monitorados</div></div>
    </div>
  </div>

  <!-- ── BOLETIM ── -->
  <div class="page page-boletim" id="page-blt">
    <div class="blt-header">
      <div class="blt-header-left">
        <h1>Boletim de Qualidade de Dados</h1>
        <p id="bltHeaderSub">Referência: Abril de 2026 · Gerado em 27/05/2026</p>
      </div>
      <div class="blt-header-right">
        <svg xmlns="http://www.w3.org/2000/svg" width="55" height="45" viewBox="1 1 48 42" fill="none">
          <g fill="#ffffff">
            <path d="M2,17.132c0.415-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2.1,17.574C2,17.411,1.9,17.248,2,17.132z"/>
            <path d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g>
        </svg>
      </div>
    </div>

    <div class="blt-body">

      <!-- ══ 1. KPIs EXECUTIVOS ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Resumo Executivo</div></div>
          <span class="sec-hd-badge">Abril 2026</span>
        </div>
        <div class="kpi-strip">
          <!-- Score -->
          <div class="kpi-wrapper">
            <div class="kpi-card">
              <div class="kpi-lbl">Score Médio Geral</div>
              <div class="kpi-val" id="kpi-score">98,4%</div>
              <div class="kpi-delta up" id="kpi-score-delta">▲ +1,2 score vs Mar</div>
            </div>
            <div class="kpi-card-blw">
              <div class="analysis-box-label">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Análise
              </div>
              <div class="analysis-box-text" id="ana-score">Score geral 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização do Open Finance e entrada de novos produtos com alta baseline.</div>
            </div>
          </div>
          <!-- Produtos -->
          <div class="kpi-wrapper">
            <div class="kpi-card green">
              <div style="display:grid;grid-template-columns:1fr 1fr">
                <div><div class="kpi-lbl">Produtos Ativos</div><div class="kpi-val" id="kpi-prod-ativos">90</div></div>
                <div><div class="kpi-lbl">Entregues</div><div class="kpi-val" id="kpi-prod-entregues">6</div></div>
              </div>
              <div class="kpi-delta up" id="kpi-prod-delta">▲ +3 vs Mar</div>
            </div>
            <div class="kpi-card-blw">
              <div class="analysis-box-label">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Análise
              </div>
              <div class="analysis-box-text" id="ana-prod">Carteira expandida com 3 novos produtos em Release 1. Base monitorada estável sem impacto no desempenho geral.</div>
            </div>
          </div>
          <!-- Tempo -->
          <div class="kpi-wrapper">
            <div class="kpi-card blue">
              <div class="kpi-lbl">Tempo Médio de Entrega</div>
              <div style="display:flex;align-items:center;gap:2px"><div class="kpi-val" id="kpi-tempo">10</div><div class="kpi-val" style="font-size:.9rem;transform:translateY(5px)">Dias</div></div>
              <div class="kpi-delta down" id="kpi-tempo-delta">▼ +1 dia vs Mar</div>
            </div>
            <div class="kpi-card-blw">
              <div class="analysis-box-label">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Análise
              </div>
              <div class="analysis-box-text" id="ana-tempo">Aumento de 1 dia no tempo médio de entrega em relação a Março. Variação dentro da margem de normalidade operacional. Acompanhar tendência nos próximos ciclos.</div>
            </div>
          </div>
          <!-- Chamados -->
          <div class="kpi-wrapper">
            <div class="kpi-card yellow">
              <div class="kpi-lbl">Taxa de Resolução de Chamados</div>
              <div style="display:flex;align-items:center;gap:2px"><div class="kpi-val" id="kpi-chamados">95</div><div class="kpi-val" style="font-size:.9rem;transform:translateY(5px)">%</div></div>
              <div class="kpi-delta down" id="kpi-chamados-delta">▲ -17 vs Mar</div>
            </div>
            <div class="kpi-card-blw">
              <div class="analysis-box-label">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Análise
              </div>
              <div class="analysis-box-text" id="ana-chamados">Redução de 17 chamados em relação a Março. Taxa de resolução mantida em 95%, com 3 chamados ainda em análise ao final do período.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 2. DADOS RELEVANTES ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Dados Relevantes</div><div class="sec-hd-sub">Métricas, indicadores e análise de qualidade por produto</div></div>
        </div>

        <!-- Score de Qualidade + Painel de Análise -->
        <div class="chart-with-analysis side">
          <div class="cwa-chart">
            <div class="chart-title">Score de Qualidade por Produto — Abril/26</div>
            <div class="chart-sub">Percentual de aderência ao SLA por produto de dados monitorado</div>
            <div class="chart-wrap h300"><canvas id="cScores"></canvas></div>
          </div>
          <div class="cwa-analysis">
            <div class="cwa-analysis-title">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              Análise dos Scores
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Destaques</div>
              <div class="cwa-block-text" id="ana-scores-dest">7 dos 10 produtos atingiram score ≥99%, demonstrando alta maturidade. Open Finance mantém desempenho consistente acima de 95% mesmo com alta volumetria de chamados.</div>
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label" style="color:var(--red)">Atenção</div>
              <div class="cwa-block-text" id="ana-scores-atenc">Rating de Risco PLDFT segue como ponto crítico (80,0%), porém em trajetória de recuperação desde Jan/26 (64,0%). Plano de ação em andamento na dimensão de Disponibilidade.</div>
            </div>
            <div class="cwa-insight">
              <p id="ana-scores-insight">Recomendação: priorizar revisão do fluxo de Disponibilidade no PLDFT para o próximo ciclo.</p>
            </div>
          </div>
        </div>

        <!-- Evolução do Score Por Vertical + Análise -->
        <div class="chart-with-analysis side" style="margin-top:14px">
          <div class="cwa-chart">
            <div class="chart-title">Evolução do Score Por Vertical — Últimos 5 Meses</div>
            <div class="chart-sub">Clique na legenda para filtrar verticais</div>
            <div class="chart-wrap h300" style="margin-bottom:14px;margin-top:14px"><canvas id="cEvolucao"></canvas></div>
            <div id="legEvolucao" class="legend-row" style="cursor:pointer"></div>
            <button id="btnResetEvolucao" style="display:none;margin-left:3px;margin-top:5px">
              <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:4px"><path d="M1 1L9 9M9 1L1 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
              Limpar filtro
            </button>
          </div>
          <div class="cwa-analysis">
            <div class="cwa-analysis-title">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              Análise da Evolução
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Tendências</div>
              <div class="cwa-block-text" id="ana-evol-tend">Carteira apresenta trajetória ascendente consistente. AOC – Contact Center demonstra estabilidade acima de 98% nos últimos 3 meses. OPEN FINANCE oscilante, mas dentro do intervalo aceitável.</div>
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Pontos de Atenção</div>
              <div class="cwa-block-text" id="ana-evol-atenc">OPEN FINANCE atingiu 98,73% em Fev/26 e recuou para 95,98% em Abr/26. Investigar causa da regressão — possível relação com aumento de chamados de Consistência.</div>
            </div>
            <div class="cwa-insight">
              <p id="ana-evol-insight">ID apresentou crescimento de 96,5% para 98,4% no mesmo período — trajetória positiva a ser mantida.</p>
            </div>
          </div>
        </div>

        <!-- Tabela Consolidada + Análise -->
        <div class="chart-with-analysis side" style="margin-top:14px">
          <div class="cwa-chart">
            <div class="chart-title">Tabela Consolidada — Produtos Monitorados</div>
            <table class="dados-table" style="margin-top:12px">
              <thead>
                <tr>
                  <th>Criticidade</th><th>Produto</th><th>Área</th>
                  <th>Score Abr/26</th><th>Score Mar/26</th><th>Tendência</th>
                  <th>Chamados</th><th>Dimensão Crítica</th>
                </tr>
              </thead>
              <tbody id="scoreTbody"></tbody>
            </table>
          </div>
          <div class="cwa-analysis">
            <div class="cwa-analysis-title">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              Análise da Tabela
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Distribuição por Criticidade</div>
              <div class="cwa-block-text" id="ana-tabela-crit">6 produtos com criticidade baixa (verde), 2 com atenção (amarelo) e 2 com risco alto (vermelho). Foco de monitoramento em PLDFT e MANIFESTAÇÕES SACL nos próximos ciclos.</div>
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Volume de Chamados</div>
              <div class="cwa-block-text" id="ana-tabela-cham">Open Finance concentra 81 dos 94 chamados totais (86%). Demais produtos com volumes residuais — sinal de processo maduro nos outros squads.</div>
            </div>
            <div class="cwa-insight">
              <p id="ana-tabela-insight">Produtos novos (IA Generativa, Visão 360) entraram com zero chamados — qualidade de onboarding acima do esperado.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 3. ENTREGAS DOS POs ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Entregas por Squad</div></div>
        </div>
        <div class="po-grid">
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name">Data Quality</div><div class="po-role">Desenvolvimento de Produtos de Dados</div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">BRAI4DQ em Produção — 6 Dimensões Ativas</div>
                <div class="po-item-desc">Entrega da solução BRAI4DQ no ambiente produtivo com disponibilidade, completude, consistência, integridade, unicidade e variação.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">4 Novas Dimensões em Desenvolvimento (McKinsey)</div>
                <div class="po-item-desc">Acurácia, validade, atualidade e tempestividade com implantação produtiva prevista para Mai/26.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name">Migration of Data Quality</div><div class="po-role">Analistas de Produtos de Dados</div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Integração Databricks × SharePoint Homologada</div>
                <div class="po-item-desc">Conexão segura reduzindo atividades manuais e garantindo rastreabilidade no fluxo de dados.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-prod">Produtos</span>
                <div class="po-item-title">7 Produtos de Dados Entregues</div>
                <div class="po-item-desc">Canais Digitais (R1) · Captação Líquida (R1) · Simulador (R1) · Visão 360 (R2) · Carteiras (R2) · IA Generativa (R2) · Dados Socioeconômicos (R5).</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-init">Iniciativa</span>
                <div class="po-item-title">Workshop de Assessment para Tribos de Negócio</div>
                <div class="po-item-desc">Capacitação elevando maturidade em governança e reduzindo riscos de interpretações divergentes.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name">IDQ Decommissioning</div><div class="po-role">Plataforma & Infra</div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Descomissionamento On-Premises Informatica/SAS</div>
                <div class="po-item-desc">Desativação de 10 tabelas Hive, 8 Teradata e 4 SAS acelerando a migração para Databricks.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Control-M Jobs Databricks — AAQD Ativado</div>
                <div class="po-item-desc">Habilitação da esteira Control-M via automação no Databricks com job de expurgo.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name">Data Quality Tools & Optimization</div><div class="po-role">IA & Automação</div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Agente DEVA em Produção na Plataforma Bridge</div>
                <div class="po-item-desc">Deploy no Bridge (IA corporativa) e Databricks, com notebook interface compartilhado com o time.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-ops">Eficiência</span>
                <div class="po-item-title">Revisão do Ecossistema BRAI4DQ</div>
                <div class="po-item-desc">Adequação de schemas, tabelas e modelo dimensional garantindo integridade operacional contínua.</div>
              </div></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 4. RELEASES ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Produtos de Dados — Releases Abril/26</div></div>
        </div>
        <div class="release-grid">
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Autenticação de Usuários — Canais Digitais</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Captação Líquida</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Simulador de Métricas</div><div class="release-ver">Release 1</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Visão Clientes 360</div><div class="release-ver">Release 2</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Plataforma IA Generativa</div><div class="release-ver">Release 2</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Dados Públicos Socioeconômicos e Demográficos</div><div class="release-ver">Release 5</div></div>
        </div>
      </section>

    </div><!-- /blt-body -->
  </div><!-- /page-blt -->
</div><!-- /main -->
</div><!-- /app -->

<script>
Chart.register(ChartDataLabels);

// ═══════════════════════════════════════
// SINGLETON DATA — fonte única de verdade
// ═══════════════════════════════════════
let D = {
  periodo: "Abril de 2026",
  gerado: "27/05/2026",
  kpis: {
    score: "98,4%", score_delta: "▲ +1,2 score vs Mar",
    prod_ativos: 90, prod_entregues: 6, prod_delta: "▲ +3 vs Mar",
    tempo: 10, tempo_delta: "▼ +1 dia vs Mar",
    chamados: 95, chamados_delta: "▲ -17 vs Mar"
  },
  analises: {
    score: "Score geral 1,2 pp acima do mês anterior. Melhora impulsionada pela estabilização do Open Finance e entrada de novos produtos com alta baseline.",
    prod: "Carteira expandida com 3 novos produtos em Release 1. Base monitorada estável sem impacto no desempenho geral.",
    tempo: "Aumento de 1 dia no tempo médio de entrega em relação a Março. Variação dentro da margem de normalidade operacional. Acompanhar tendência nos próximos ciclos.",
    chamados: "Redução de 17 chamados em relação a Março. Taxa de resolução mantida em 95%, com 3 chamados ainda em análise ao final do período.",
    scores_dest: "7 dos 10 produtos atingiram score ≥99%, demonstrando alta maturidade. Open Finance mantém desempenho consistente acima de 95% mesmo com alta volumetria.",
    scores_atenc: "Rating de Risco PLDFT segue como ponto crítico (80,0%), porém em trajetória de recuperação desde Jan/26 (64,0%). Plano de ação em andamento.",
    scores_insight: "Recomendação: priorizar revisão do fluxo de Disponibilidade no PLDFT para o próximo ciclo.",
    evol_tend: "Carteira apresenta trajetória ascendente consistente. AOC – Contact Center demonstra estabilidade acima de 98% nos últimos 3 meses.",
    evol_atenc: "OPEN FINANCE atingiu 98,73% em Fev/26 e recuou para 95,98% em Abr/26. Investigar causa da regressão.",
    evol_insight: "ID apresentou crescimento de 96,5% para 98,4% — trajetória positiva a ser mantida.",
    tabela_crit: "6 produtos com criticidade baixa, 2 com atenção e 2 com risco alto. Foco em PLDFT e MANIFESTAÇÕES SACL.",
    tabela_cham: "Open Finance concentra 81 dos 94 chamados totais (86%). Demais produtos com volumes residuais.",
    tabela_insight: "Produtos novos (IA Generativa, Visão 360) entraram com zero chamados — qualidade de onboarding acima do esperado."
  },
  meses: ['Dez/25','Jan/26','Fev/26','Mar/26','Abr/26'],
  produtos: [
    {nome:'OPEN FINANCE',gestor:'OPEN FINANCE',scores:[92.06,91.49,98.73,94.3,95.98],chamados:81,dim:'Consistência',crit:1},
    {nome:'QUALIDADE DE DADOS',gestor:'OPEN FINANCE',scores:[null,null,null,100,94],chamados:0,dim:'—',crit:2},
    {nome:'RATING DE RISCO PLDFT',gestor:'AOC - SQUAD CONTACT CENTER',scores:[null,64.0,85.52,74.58,80.0],chamados:2,dim:'Disponibilidade',crit:3},
    {nome:'ATENDIMENTO AO CLIENTE',gestor:'AOC - SQUAD CONTACT CENTER',scores:[89.95,98.89,99.61,99.64,99.77],chamados:5,dim:'Completude',crit:1},
    {nome:'COMUNICAÇÕES E ALERTAS PLDFT',gestor:'AOC - SQUAD CONTACT CENTER',scores:[93.53,99.24,100,100,99.65],chamados:2,dim:'Disponibilidade',crit:1},
    {nome:'PROCESSOS JURÍDICOS',gestor:'AOC - SQUAD CONTACT CENTER',scores:[94.44,100,100,99.91,99.73],chamados:4,dim:'Completude',crit:2},
    {nome:'MANIFESTAÇÕES SACL',gestor:'AOC - SQUAD CONTACT CENTER',scores:[99.73,100,100,100,100],chamados:0,dim:'—',crit:3},
    {nome:'SRM CALENDÁRIO FERIADOS',gestor:'AOC - SQUAD CONTACT CENTER',scores:[99.72,99.72,100,100,100],chamados:0,dim:'—',crit:1},
    {nome:'VISÃO CLIENTES 360',gestor:'ID',scores:[null,null,97.2,98.1,99.2],chamados:0,dim:'—',crit:1},
    {nome:'IA GENERATIVA',gestor:'ID',scores:[null,null,null,96.5,98.4],chamados:0,dim:'—',crit:1}
  ],
  tendencia: {abertos:[41,11,44,2,0],concluidos:[31,111,55,64,102]},
  mediaDias: {data:[14,11,14,12,10]}
};

const COLORS=['#CC0A2F','#3B6BF5','#00C07A','#F5A623','#7C4DFF','#00BFCF','#E8143A','#059669','#D97706','#2563EB'];
const baseFont={family:"'DM Sans', sans-serif",size:11};
const gridColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g200').trim()||'#E2E4EA';
const textColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g600').trim()||'#5C6180';
const chartInstances={};
function destroyChart(id){if(chartInstances[id]){chartInstances[id].destroy();delete chartInstances[id];}}
function getCtx(id){const el=document.getElementById(id);if(!el)return null;destroyChart(id);return el.getContext('2d');}

// ─── APPLY DATA TO UI ───
function applyData(){
  // Header
  document.getElementById('bltHeaderSub').textContent=`Referência: ${D.periodo} · Gerado em ${D.gerado}`;
  // KPIs
  setEl('kpi-score',D.kpis.score);
  setEl('kpi-score-delta',D.kpis.score_delta);
  setEl('kpi-prod-ativos',D.kpis.prod_ativos);
  setEl('kpi-prod-entregues',D.kpis.prod_entregues);
  setEl('kpi-prod-delta',D.kpis.prod_delta);
  setEl('kpi-tempo',D.kpis.tempo);
  setEl('kpi-tempo-delta',D.kpis.tempo_delta);
  setEl('kpi-chamados',D.kpis.chamados);
  setEl('kpi-chamados-delta',D.kpis.chamados_delta);
  // Analises
  const anaMap={
    'ana-score':'score','ana-prod':'prod','ana-tempo':'tempo','ana-chamados':'chamados',
    'ana-scores-dest':'scores_dest','ana-scores-atenc':'scores_atenc','ana-scores-insight':'scores_insight',
    'ana-evol-tend':'evol_tend','ana-evol-atenc':'evol_atenc','ana-evol-insight':'evol_insight',
    'ana-tabela-crit':'tabela_crit','ana-tabela-cham':'tabela_cham','ana-tabela-insight':'tabela_insight'
  };
  Object.entries(anaMap).forEach(([id,key])=>setEl(id,D.analises[key]||''));
  // Table
  buildScoreTable();
  // Charts
  initCharts();
}
function setEl(id,val){const e=document.getElementById(id);if(e)e.textContent=val;}

// ─── NAV ───
function goPage(id){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('act'));
  document.querySelectorAll('.nav-btn').forEach(b=>b.classList.remove('act'));
  document.getElementById('page-'+id).classList.add('act');
  document.getElementById('nav-'+id).classList.add('act');
  if(id==='blt') initCharts();
}
function toggleSB(){
  document.getElementById('sb').classList.toggle('col');
  document.getElementById('main').classList.toggle('col');
}

// ─── THEME ───
function toggleTheme(){
  const h=document.documentElement;
  h.dataset.theme=h.dataset.theme==='dark'?'light':'dark';
  localStorage.setItem('theme',h.dataset.theme);
  setTimeout(initCharts,80);
}
(()=>{const t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t;})();

// ─── SCROLL REVEAL ───
const ro=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible');});},{threshold:0.06});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));

// ─── CHARTS ───
function initCharts(){
  buildScoreChart();
  buildEvolucaoChart();
}

function buildScoreChart(){
  const ctx=getCtx('cScores');if(!ctx)return;
  const labels=D.produtos.map(p=>p.nome.length>22?p.nome.slice(0,22)+'…':p.nome);
  const scores=D.produtos.map(p=>p.scores[4]||p.scores[3]||0);
  chartInstances['cScores']=new Chart(ctx,{
    type:'bar',
    data:{labels,datasets:[{
      label:'Score Abr/26',data:scores,
      backgroundColor:scores.map(s=>s>=95?'rgba(0,192,122,.75)':s>=85?'rgba(245,166,35,.75)':'rgba(204,10,47,.75)'),
      borderRadius:6,borderSkipped:false,barThickness:20
    }]},
    options:{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,
      layout:{padding:{right:16}},
      plugins:{
        legend:{display:false},
        datalabels:{anchor:'end',align:'end',font:{size:10,weight:'700'},
          formatter:v=>v?v.toFixed(1)+'%':'—',color:textColor,padding:{left:2}}
      },
      scales:{
        x:{min:60,max:106,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{ticks:{font:{...baseFont,size:10},color:textColor},grid:{display:false}}
      }
    }
  });
}

function aggregateByGestor(produtos){
  const map={};
  produtos.forEach(p=>{
    if(!map[p.gestor]){map[p.gestor]={gestor:p.gestor,scores:Array(p.scores.length).fill(null),counts:Array(p.scores.length).fill(0)};}
    p.scores.forEach((val,i)=>{if(val!==null){if(map[p.gestor].scores[i]===null)map[p.gestor].scores[i]=0;map[p.gestor].scores[i]+=val;map[p.gestor].counts[i]+=1;}});
  });
  return Object.values(map).map(g=>({gestor:g.gestor,scores:g.scores.map((sum,i)=>g.counts[i]>0?+(sum/g.counts[i]).toFixed(2):null)}));
}

function buildEvolucaoChart(){
  const ctx=getCtx('cEvolucao');if(!ctx)return;
  const grouped=aggregateByGestor(D.produtos).slice(0,6);
  const datasets=grouped.map((g,i)=>({
    label:g.gestor.length>20?g.gestor.slice(0,20)+'…':g.gestor,
    data:g.scores,borderColor:COLORS[i],backgroundColor:'transparent',
    borderWidth:2,pointRadius:4,pointHoverRadius:6,spanGaps:true,tension:.35
  }));
  const leg=document.getElementById('legEvolucao');
  const btnReset=document.getElementById('btnResetEvolucao');
  if(leg){
    leg.innerHTML='';
    grouped.forEach((g,i)=>{
      const name=g.gestor.length>20?g.gestor.slice(0,20)+'…':g.gestor;
      const item=document.createElement('div');
      item.className='legend-item';item.dataset.index=i;
      item.innerHTML=`<div class="legend-dot" style="background:${COLORS[i]}"></div>${name}`;
      item.onclick=function(){
        const chart=chartInstances['cEvolucao'];if(!chart)return;
        const dsIdx=parseInt(this.dataset.index);
        let anyHidden=false;
        for(let i=0;i<chart.data.datasets.length;i++){if(chart.getDatasetMeta(i).hidden){anyHidden=true;break;}}
        if(!anyHidden){
          for(let i=0;i<chart.data.datasets.length;i++){
            const meta=chart.getDatasetMeta(i);const hide=i!==dsIdx;meta.hidden=hide;
            const el=leg.querySelector(`[data-index="${i}"]`);if(el)el.classList.toggle('hidden',hide);
          }
        }else{
          const meta=chart.getDatasetMeta(dsIdx);meta.hidden=meta.hidden===null?true:!meta.hidden;
          this.classList.toggle('hidden',meta.hidden);
        }
        chart.update();
        let hasHidden=false;
        for(let i=0;i<chart.data.datasets.length;i++){if(chart.getDatasetMeta(i).hidden){hasHidden=true;break;}}
        btnReset.style.display=hasHidden?'':'none';
      };
      leg.appendChild(item);
    });
    btnReset.style.display='none';
  }
  chartInstances['cEvolucao']=new Chart(ctx,{
    type:'line',data:{labels:D.meses,datasets},
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},datalabels:{display:false}},
      scales:{
        x:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{min:80,max:100,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{color:gridColor()}}
      }
    }
  });
}
document.getElementById('btnResetEvolucao').onclick=function(){
  const chart=chartInstances['cEvolucao'];if(!chart)return;
  for(let i=0;i<chart.data.datasets.length;i++)chart.getDatasetMeta(i).hidden=null;
  chart.update();
  document.querySelectorAll('#legEvolucao .legend-item').forEach(el=>el.classList.remove('hidden'));
  document.getElementById('btnResetEvolucao').style.display='none';
};

function buildScoreTable(){
  const tbody=document.getElementById('scoreTbody');if(!tbody)return;
  tbody.innerHTML=D.produtos.map(p=>{
    const abr=p.scores[4]||p.scores[3],mar=p.scores[3];
    const trend=(!abr||!mar)?'—':(abr>mar?'<span class="trend-arrow" style="color:var(--green)">▲</span>':abr<mar?'<span class="trend-arrow" style="color:var(--red)">▼</span>':'<span style="color:var(--g500)">→</span>');
    const scoreClass=!abr?'':abr>=95?'score-great':abr>=85?'score-warn':'score-bad';
    const critDot=p.crit===3?'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--red)"></span>':p.crit===2?'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--yellow)"></span>':'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--green)"></span>';
    return `<tr><td style="text-align:center;width:20px;padding:2px">${critDot}</td><td><strong>${p.nome}</strong></td><td style="font-size:.75rem;color:var(--g600)">${p.gestor}</td><td><span class="badge-score ${scoreClass}">${abr?abr.toFixed(2)+'%':'—'}</span></td><td style="font-size:.78rem">${mar?mar.toFixed(2)+'%':'—'}</td><td style="text-align:center">${trend}</td><td style="font-size:.78rem">${p.chamados}</td><td style="font-size:.72rem;color:var(--g500)">${p.dim}</td></tr>`;
  }).join('');
}

// ═══════════════════════════════════════
// JSON IMPORT
// ═══════════════════════════════════════
function openJsonModal(){document.getElementById('jsonModal').classList.add('open');}
function closeJsonModal(){document.getElementById('jsonModal').classList.remove('open');document.getElementById('jsonErr').textContent='';}
function showJsonSchema(e){
  e.preventDefault();
  const schema={
    periodo:"string",gerado:"string",
    kpis:{score:"string",score_delta:"string",prod_ativos:0,prod_entregues:0,prod_delta:"string",tempo:0,tempo_delta:"string",chamados:0,chamados_delta:"string"},
    analises:{score:"string",prod:"string",tempo:"string",chamados:"string",scores_dest:"string",scores_atenc:"string",scores_insight:"string",evol_tend:"string",evol_atenc:"string",evol_insight:"string",tabela_crit:"string",tabela_cham:"string",tabela_insight:"string"},
    meses:["string"],
    produtos:[{nome:"string",gestor:"string",scores:["number|null"],chamados:0,dim:"string",crit:1}],
    tendencia:{abertos:[0],concluidos:[0]},
    mediaDias:{data:[0]}
  };
  document.getElementById('jsonInput').value=JSON.stringify(schema,null,2);
}
function applyJson(){
  const raw=document.getElementById('jsonInput').value.trim();
  const err=document.getElementById('jsonErr');
  try{
    const parsed=JSON.parse(raw);
    D=deepMerge(D,parsed);
    err.textContent='';
    closeJsonModal();
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

// ═══════════════════════════════════════
// EMAIL EXPORT
// ═══════════════════════════════════════
function openEmailModal(){
  const html=buildEmailHtml();
  document.getElementById('emailPreview').innerHTML=`<iframe srcdoc="${html.replace(/"/g,'&quot;')}"></iframe>`;
  document.getElementById('emailModal').classList.add('open');
}
function closeEmailModal(){document.getElementById('emailModal').classList.remove('open');}
function copyEmailHtml(){navigator.clipboard.writeText(buildEmailHtml()).then(()=>alert('HTML copiado para a área de transferência!'));}
function downloadEmail(){
  const a=document.createElement('a');
  a.href='data:text/html;charset=utf-8,'+encodeURIComponent(buildEmailHtml());
  a.download=`boletim_email_${D.periodo.replace(/\s/g,'_')}.html`;a.click();
}

function buildEmailHtml(){
  // Capture charts as base64 images
  const scoresCanvas = document.getElementById('cScores');
  const evolCanvas   = document.getElementById('cEvolucao');
  const scoresImg = scoresCanvas ? scoresCanvas.toDataURL('image/png') : '';
  const evolImg   = evolCanvas   ? evolCanvas.toDataURL('image/png')   : '';

  const prodRows=D.produtos.map((p,i)=>{
    const a=p.scores[4]||p.scores[3],m=p.scores[3];
    const t=(!a||!m)?'→':(a>m?'▲':'▼');
    const clr=!a?'#888':a>=95?'#00C07A':a>=85?'#F5A623':'#CC0A2F';
    const critClr=p.crit===3?'#E8143A':p.crit===2?'#F5A623':'#00C07A';
    return `<tr style="background:${i%2===0?'#fff':'#F4F5F7'}">
      <td style="padding:8px 10px;text-align:center;border-bottom:1px solid #E2E4EA"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${critClr}"></span></td>
      <td style="padding:8px 10px;font-size:11px;font-weight:700;color:#1A1D2E;border-bottom:1px solid #E2E4EA">${p.nome}</td>
      <td style="padding:8px 10px;font-size:10px;color:#8A8FA8;border-bottom:1px solid #E2E4EA">${p.gestor}</td>
      <td style="padding:8px 10px;text-align:center;border-bottom:1px solid #E2E4EA"><span style="background:${clr}18;color:${clr};font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px">${a?a.toFixed(2)+'%':'—'}</span></td>
      <td style="padding:8px 10px;font-size:10px;text-align:center;font-weight:700;color:${a&&m?(a>m?'#00C07A':'#E8143A'):'#888'};border-bottom:1px solid #E2E4EA">${t}</td>
      <td style="padding:8px 10px;font-size:10px;text-align:center;color:#5C6180;border-bottom:1px solid #E2E4EA">${p.chamados}</td>
    </tr>`;
  }).join('');

  // Build chart sections — only include if we got a valid data URL
  const scoresSection = scoresImg ? `
  <tr><td style="background:#fff;padding:20px 36px;border-top:1px solid #E2E4EA">
    <p style="font-size:9px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Score de Qualidade por Produto — Abril/26</p>
    <img src="${scoresImg}" width="568" style="width:100%;max-width:568px;display:block;border-radius:8px" alt="Gráfico de scores por produto">
    <div style="margin-top:12px;background:#FFF5F7;border-left:3px solid #c01137;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#c01137;margin-bottom:3px">Destaques</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.scores_dest}</div>
    </div>
    <div style="margin-top:8px;background:#FFF5F7;border-left:3px solid #E8143A;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#E8143A;margin-bottom:3px">Atenção</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.scores_atenc}</div>
    </div>
  </td></tr>` : '';

  const evolSection = evolImg ? `
  <tr><td style="background:#fff;padding:20px 36px;border-top:1px solid #E2E4EA">
    <p style="font-size:9px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Evolução do Score por Vertical — Últimos 5 Meses</p>
    <img src="${evolImg}" width="568" style="width:100%;max-width:568px;display:block;border-radius:8px" alt="Gráfico de evolução por vertical">
    <div style="margin-top:12px;background:#FFF5F7;border-left:3px solid #c01137;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#c01137;margin-bottom:3px">Tendências</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.evol_tend}</div>
    </div>
    <div style="margin-top:8px;background:#FFF8F0;border-left:3px solid #F5A623;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#F5A623;margin-bottom:3px">Pontos de Atenção</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.evol_atenc}</div>
    </div>
  </td></tr>` : '';

  return `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Boletim Qualidade — ${D.periodo}</title></head>
<body style="margin:0;padding:0;background:#F4F5F7;font-family:'Segoe UI',Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F4F5F7;padding:24px 0"><tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px">
  <tr><td style="background:linear-gradient(120deg,#8C0F3B,#c01137);border-radius:16px 16px 0 0;padding:28px 36px 24px">
    <p style="font-size:9px;font-weight:700;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.1em;margin:0 0 10px">Boletim Mensal</p>
    <h1 style="font-size:22px;font-weight:800;color:#fff;margin:0 0 6px;line-height:1.1">Boletim de Qualidade de Dados</h1>
    <p style="font-size:11px;color:rgba(255,255,255,.65);margin:0">Referência: <strong style="color:#fff">${D.periodo}</strong> · ${D.gerado}</p>
  </td></tr>
  <tr><td style="background:#fff;padding:24px 36px;border-bottom:1px solid #E2E4EA">
    <p style="font-size:9px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.08em;margin:0 0 14px">Resumo Executivo</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <td width="25%" style="padding-right:6px"><table width="100%"><tr><td style="background:#F4F5F7;border-radius:10px;padding:14px;border-top:3px solid #c01137">
        <div style="font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Score Médio</div>
        <div style="font-size:24px;font-weight:800;color:#c01137;line-height:1">${D.kpis.score}</div>
        <div style="font-size:10px;color:#00C07A;margin-top:4px">${D.kpis.score_delta}</div>
      </td></tr></table></td>
      <td width="25%" style="padding:0 3px"><table width="100%"><tr><td style="background:#F4F5F7;border-radius:10px;padding:14px;border-top:3px solid #00C07A">
        <div style="font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Produtos Ativos</div>
        <div style="font-size:24px;font-weight:800;color:#00C07A;line-height:1">${D.kpis.prod_ativos}</div>
        <div style="font-size:10px;color:#8A8FA8;margin-top:4px">${D.kpis.prod_delta}</div>
      </td></tr></table></td>
      <td width="25%" style="padding:0 3px"><table width="100%"><tr><td style="background:#F4F5F7;border-radius:10px;padding:14px;border-top:3px solid #3B6BF5">
        <div style="font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Tempo Médio</div>
        <div style="font-size:24px;font-weight:800;color:#3B6BF5;line-height:1">${D.kpis.tempo}d</div>
        <div style="font-size:10px;color:#8A8FA8;margin-top:4px">${D.kpis.tempo_delta}</div>
      </td></tr></table></td>
      <td width="25%" style="padding-left:6px"><table width="100%"><tr><td style="background:#F4F5F7;border-radius:10px;padding:14px;border-top:3px solid #F5A623">
        <div style="font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px">Taxa Resolução</div>
        <div style="font-size:24px;font-weight:800;color:#F5A623;line-height:1">${D.kpis.chamados}%</div>
        <div style="font-size:10px;color:#8A8FA8;margin-top:4px">${D.kpis.chamados_delta}</div>
      </td></tr></table></td>
    </tr></table>
    <div style="margin-top:14px;background:#FFF5F7;border-left:3px solid #c01137;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#c01137;margin-bottom:3px">Análise</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.score}</div>
    </div>
  </td></tr>
  ${scoresSection}
  <tr><td style="background:#fff;padding:20px 36px;border-top:1px solid #E2E4EA">
    <p style="font-size:9px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.08em;margin:0 0 12px">Tabela Consolidada — Produtos Monitorados</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E2E4EA;border-radius:8px;overflow:hidden">
      <tr style="background:#F0F1F4">
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA">Crit.</th>
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA">Produto</th>
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA">Área</th>
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA">Score Abr</th>
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA">Tend.</th>
        <th style="padding:8px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA">Cham.</th>
      </tr>
      ${prodRows}
    </table>
    <div style="margin-top:12px;background:#FFF5F7;border-left:3px solid #c01137;border-radius:0 8px 8px 0;padding:10px 14px">
      <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#c01137;margin-bottom:3px">Análise</div>
      <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic">${D.analises.tabela_crit}</div>
    </div>
  </td></tr>
  ${evolSection}
  <tr><td style="background:linear-gradient(120deg,#8C0F3B,#c01137);border-radius:0 0 16px 16px;padding:18px 36px">
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
