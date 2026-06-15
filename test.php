<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — Maio/2026</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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
html,body{height:100%;font-family:var(--font-body);background:linear-gradient(180deg,var(--pr-dk) 0%,var(--pr) 55%,#A00025 100%);color:var(--g800)}
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
.sb.col .sb-logo-icon{overflow: visible;}
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
.sb.col .sb-action-btn{justify-content:center;padding:8px 0;position: relative;}
.sb.col .sb-action-btn svg{position: relative;}
.sb.col .sb-action-btn-label{opacity:0;max-width:0;overflow:hidden;transition:opacity .2s,max-width .5s}
.sb.col .sb-action-btn:nth-of-type(odd) svg{left: 9px}
.sb.col .sb-action-btn:nth-of-type(even) svg{left: 5px}




/* ─── MODALS ─── */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:1000;
  display:none;align-items:center;justify-content:center;padding:24px
}
.modal-overlay.open{display:flex}
.modal-box{
  background:var(--wh);border-radius:var(--r-lg);padding:32px;
  width:100%;max-width:1020px;box-shadow:var(--sh2);
  height:88vh;max-height:920px;
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
.modal-preview{flex:1;overflow-y:auto;border:1px solid var(--g200);border-radius:var(--r);min-height:0;background:#ECEEF3;margin-bottom:14px}
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
.kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;align-items:stretch}
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
.kpi-delta{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:20px;transform: translateY(5px);}
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
.chart-card{background:var(--wh);border:1px solid var(--g200);border-radius:var(--r-lg);padding:22px;box-shadow:var(--sh)}
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
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30" zoomAndPan="magnify" viewBox="0 0 750 749.999995" height="30" preserveAspectRatio="xMidYMid meet" version="1.0">
            <defs>
                <filter x="0%" y="0%" width="100%" height="100%" id="id1">
                    <feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 1 0" color-interpolation-filters="sRGB"/>
                </filter>
                <filter x="0%" y="0%" width="100%" height="100%" id="id2">
                    <feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0.2126 0.7152 0.0722 0 0" color-interpolation-filters="sRGB"/>
                </filter>
                <clipPath id="id3">
                    <path d="M 0 96.292969 L 750 96.292969 L 750 653.542969 L 0 653.542969 Z M 0 96.292969 " clip-rule="nonzero"/>
                </clipPath>
                <image x="0" y="0" width="1180" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABJwAAANtCAAAAADwLD4yAAAAAmJLR0QA/4ePzL8AACAASURBVHic7d3tVRvJvujhumfNdzgRoB0B2hHQOwI4EaCJwEwEliMwE4HlCAZH4CaCERG4iWCLCOZ+4MWAhdBLteqln2etu2wYwP9zzlq/W1WUWv+vDbWYzVJPEMX4MvUElWqf/jZfhBBC1yUahDX9dpJ6gmja1APEcVjP/0XysvR/r3fzEEIbwmL+2Cyy8VvqASChg5PwPFu3Xei60HW1/P90ZRMneHJ09FSq2y60Yb6w+UtHnGCZp07dzRfzxXwxTzzPAIkTrHRwEk5DCOG267r5ok08zZCIE6zl6OgkhBBuu/mitdnbB3GCTRwdnYSPIVx3nUT1TJxgCycPiZrP3UDoizjB1k5OzkO4nc/n8y71KBUSJ9jN0dFpCHfzVqEiEyeI4ODkJIS7dt7a5UUjThDLwenpx3Azb+cuRcUgThDV8fF5uGvnbZt6kOL9T+oBoD4Hpx+//9NOm9RzlE2coB8nH7//M79sUo9RLts66M/x8Ydw3dribcXKCfp18vH7P1cX49RjlMfKCfp3ehpu26vWNYNNWDnBXhyd//Xf1gJqA1ZOsDcnJ+G2vbpKPUYhrJxgn47O//rnajJKPUYJxAn27fTLj/nFKPUU2RMnSOD484/5pQOolcQJ0jj+8HenTyuIEyRzpE8riBOkdPTh7+5ylHqKLIkTJHb0wfn4MuIE6R1//tFODlNPkRlxgiycfPnv7Cz1EFkRJ8jF+V+On54RJ8jH0Ycfc9u7B+IEWTn+8t9Zk3qILIgT5Ob8e3dh+SROkKGjz5ZP4gR5Ov/eDfz0SZwgU0dfutko9RAJiRNk6+D8RztJPUQy4gQ5O/ky2MNxcYK8HX0e6O5OnCB3B+c/rprUQ+yfOEEBTr/PJ6ln2DdxgiIcD+7wSZygEEefu+mQ8iROUIyDj0M6GxcnKMjB+Y/B5EmcoCyDyZM4QWkGkidxgvIMIk/iBCUaQJ7ECcpUfZ7ECUpVeZ7ECcp1/mNW77VMcYKSndd7a1ycoGgHH7uL1DP0Q5ygcAefu0nqGfogTlC8oy/zJvUM8YkTVOD4eztKPUNs4gRVOPlxWdnJuDhBJT5UdjIuTlCLg89dk3qGiMQJ6nFU09GTOEFNTn5UcylTnKAuH2u59SROUJmDL+049QwxiBNU5+TvGq4ViBNU6MP8LPUIOxMnqNHRX8X/3k6coE4n82nqEXYjTlCpg49lvxxYnKBax99LPhgXJ6jYh4IXT+IENTv6flXq4kmcoG6nXaG3CsQJKnfwV5mLJ3GC6pW5eBInqF+RiydxgiEocPEkTjAIB3+VdudJnGAgSrvzJE4wFEffp6lH2IQ4wXB8nI9Sj7A+cYIBOZ5PUo+wNnGCITn4UsylAnGCYTkt5VxcnGBgSjkXFycYnI9tCVs7cYLhOSnhfcvFCQbooICtnTjBIOW/tRMnGKaTLvP3BRYnGKiDvy9Sj7CSOMFgfc76QqY4wXCdthlv7cQJBuy4zfcZdOIEQ3bw1zT1CG8RJxi2j7kePIkTDFyuB0/iBEN33DapR1hGnGDwDr5PUo+whDgB4css9QS/EicghPP8XmonTkAI4SS7Y3FxAkII4Ti3OokTEEII4eDvSeoRXhAn4MGXrB5TIE7Ao8+z1BM8I07Ak/OMXssiTsBPp/lcKRAn4Jl8fmknTsBz2dRJnIAXDjJ5HbA4AS9l8jpgcQJe+zJJPUEQJ2CJHOokTsCvMniGijgBS5zPUk8gTsAyyeskTsBSqeskTsByieskTsAb0j67V5yAt5ykrJM4AW86TlgncQLelrBO4gSskK5O4gSskqxO4gSslKpO4gSsdjxL8s+KE/CO01mKf1WcgPckuSsuTsC7UtRJnID3JaiTOAFrOL/c978oTsA6Pkz2/A+KE7CWfT9XXJyA9ey5TuIErOlyr+8FLE7Amg72+k7l4gSs66Ad7e8fEydgbQdX+3sRsDgB69vjIwrECdjA8d4uY4oTsIm9vZBFnICNnE/28++IE7CZPV3GFCdgQ/u5jClOwIYO9vIrO3ECNrWXOokTsLF9vOeBOAGbO+3/upM4AVvo/9lz4gRs40vfv7ITJ2ArfR+KixOwlb5/ZSdOwHZ6fg2wOAFbOr/o86eLE7Ctz02PP1ycgK31+WBMcQK2dtD297PFCdhej69jESdgB/09ek6cgF309nAncQJ2cTDr6VBcnICd9HUXU5yA3fR0F1OcgB197uXYSZyAXfVyF1OcgF0d9XHsJE7Azvq47SROwO56uO0kTsDuDmbRf6Q4ARHEv+0kTkAMH5rIP1CcgChi3ycQJyCK2MdO4gTEcRr3ZSziBEQyHcX8aeIERBJ3YydOQCwn04g/TJyAaD5GvCj+W7wfBc/dzVNP8GR8kHqC4ZjFq5M40ZN5k3qCV0ajEEITwmgUDo8Tz1Kv4+k01o8SJ4ai60II7eNHo1EYH45GMhXbx6tYS2ZxYpi67iFUh+PD8eF4dJR2nIpE29iJEwO3aMNVCCGMR+PR6CT1NBWItrETJwghhDCfX4UQRuPxeGwVtZNYGztxgme67iqE0IzHY4dRW4u0sRMn+EXbhnA4bsaNOwjbiLSxEydYatG2IYzHTWOTt7GPsy7CTxEneNt8PgujRqA2NWsi/BAvX4HVutlk9K8/vt2lnqMkJzEeniJO8L7u8uzwP3/epB6jHNMIT8UUJ1hPezH+1x/fUk9RiBgPTxEnWFt3efa/v+vTOk6bnX+EOMEmFjN9Wsts542dOMGG9GkdRzufiYsTbG4xO/vfP5yPr/RxtOMPECfYyuJy/O8/3S9YYbbj94sTbGt+cfh/tndvOjnb7fvFCXZwdfavT5ZPb7jc7UxcnGAn3fTw9+vUQ+RpxzNxcYJdzZp/f009Q5Z2OxMXJ9jdfGJ3t8xsl28WJ4ihm47+uE09RHZ2OhMXJ4hjcTn6XZ5eudzhe8UJopnJ0ytH0+2/V5wgInl65WK09beKE0QlTy8cTLf+VnGCyOTpufOt34pFnCC62djFgidbn4mLE8S3mI7+TD1DLra+TiBO0IfFxb+8qOXetksncYJ+dM1/HD2FsP1L7MQJ+tKOHD2FsPVbsYgT9Gc69rynEA62WzqJE/SoO7O32/YmpjhBr9qx39ttdxNTnKBfi4t/D/6tEM5HW3yTOEHf5uNPqUdIbbrF94gT9G869MXTebP594gT7MHgF0/Tzb9FnGAvpsP+td1Js/G3iBPsRzse9LsgTDf+DnGCPVlM/m/AF8Y3XzqJE+zN1XjA5+LTTb9BnGB/ugHfyNx46SROsE8Xw93aTTf8enGCvbpqhrq123TpJE6wX/NmqL+1m2725eIEe7aY/JF6hDQ2XDqJE+zd5UAPnqYbfbU4wf4N9OBps6WTOEEC82HWabrJF4sTpLAY5ItZNlo6iROkMRnicwqmG3ytOEEi099TT7B/Jxu8Obk4QSqz/wzvl3YbvBGLOEEybTO4Om3wNHFxgnTmzeCeQDdd+yvFCRKaD+4hKudrv/2vOEFKi8FdeFr71EmcIKnB1eli3aWTOEFaQ6vTwdmaXyhOkNjQ6jRd8+vECVIbWJ2O1lw6iRMkN7A6rXkkLk6Q3rDqtOZrWMQJMjCsOq23dBInyMGg6rTeRUxxgiwshvQ6u7WWTuIEeRhSnSbrfJE4QSbmw6nT0WSNLxInyMV83bvT5Zus8TXiBNloB/NszJPR+18jTpCP2WDebnP6/peIE2TkcijvyXL2/m0CcYKcTK5TT7AfazybQJwgK2cDuYz5/lUncYKsLCbDuFBw/O4L7MQJ8jKUCwXvLp3ECTIzkAsF7x6JixPkZjaIX9m9eyQuTpCdySAOxd/b14kT5GcQr7J770hcnCA/iyb1BPswWf2fxQkyNB/C61gmq/+zOEGOLr+lnqB/7xyJixNkaXKbeoL+TVb+V3GCLC0GcBfzdOVVJ3GCPA3h2Gmy6j+KE2RqAMdOk1X/UZwgV/W/BPh4tOI/ihPkagDHTqtuiYsTZKv9M/UEfVuVX3GCfE1rf5Hd0YqXsIgT5GsxST1B31bs68QJMjb/lHqCnq3Y14kT5Kz2jd2Kl7CIE2RtknqAnokTFKr2jZ04Qakq39i9va8TJ8jcJPUA/RInKNW87quY4gTFmlb9aKc393XiBLlbvP/W3SUTJyjWVdUPTxEnKNdFzQ9PeWtfJ06Qv+4y9QR9EicoV9Vn4uIEBav5TPxg+XNTxAlKcHWdeoIeTZZ+VpygCJPUA/Ro+b5OnKAI3dfUE/TnaLTss+IEZaj5OsHSpZM4QRkWFV8nmCz7pDhBIS7rvU5wvOx9ycUJCrGYpp6gP8v2deIEpZjVu3RqlnxOnKAY09QD9MbKCYo2q/Ym5rJL4uIE5ZimHqA3S5ZO4gTlaKtdOokTlG2aeoC+LLlMIE5QkCEtncQJSjJNPUBfml8+I05QkrbWt9hsfvmMOEFRan2F3dEvlwnECYpS7TXx5vUnxAnKMk09QE+a158QJyhLrUun5vUnxAkKM0s9QD8OmlefECcozGWlj8RsXn0sTlCYxVXqCfrRvPpYnKA009QD9OPk1cfiBKXpvqWeoB/Nyw/FCYpT6UXM5uWH4gTFaeu8TdC8/FCcoDzT1AP04tWhkzhBea7qvE3QvPhInKA8ld4mePnaX3GCAtV5JN68+EicoEDzKh/r1Lz4SJygRFUunV6+QZQ4QYnqPBIXJyhenUfizfMPxAmKNEs9QB+snKB8Vd4Sf/HudeIEZZqlHqAPz5dO4gRlmqUeoA/Ns7+LE5Spq/Gqk5UTVGCWeoAeNM/+Lk5QqBovExyMfv5dnKBQVT4Q89m+TpygVDUuncQJKlBjnJqffxUnKNWiwn2dlRPUoMKl07MTcXGCYlUYp2dLJ3GCYi0qvIcpTlCDWeoB4mue/iZOUK4K93VWTlCDCl9fd/D01BRxgoK1qQeI72npJE5QsAr3dc3jX8QJCtbW9z4Ho8e/iBOUrL6lk20dVKFNPUB0x49/EScoWZt6gPgel07iBCWr8DLB6OFPcYKitakHiM7KCarQph4gOnGCKtT367rRw5/iBGW7Tj1AbI+/rhMnKFubeoDoHvZ14gRla1MPEN3o/g9xgrK1qQeIzsoJ6lDdodPo/g9xgsK1qQeIbXT/hzhB4drUA8R2cv+HOEHh5qkHiO7+YZjiBIWr7z1Y7k/ExQlK16YeILZRCEGcoHzV7etGIQRxgvK1qQeIzbYO6tDV9iBxB+JQidr2dVZOUIk29QCRHYQQxAkqUNvK6f6968QJilddnEII4gQVqO5EvAlBnKAGVS6dxAnK16YeILImBHGCGlg5AVnqUg8Q2UkI4gQ1sHIC8lTbU1PGQZygCrUtnQ6DOEEVutQDRDYK4gRVaFMPENkoiBNUoUs9QGS2dVCJLvUAkTkQh1rU9uu6IE5Qhy71AHFZOUEtKrtLcBDECerQpR4gskNxgjp0qQeIbCxOUIfKtnUhiBPUYZF6gMhG4gSVqOwuwUicoBK1LZ3ECSrRph4gLgfiQJZcJYBatKkHiE6cgPzY1kEtKrvodCBOUAm/rQPydJt6gLgOxQkq0aUeIK6xOAF5EieoQ5t6gNjECciQbR3Uoks9QFwOxKEWXeoBYhMnIEviBHWo7Ir4SJygEpVdERcnIFPiBJWo7EG94gS1qGxfJ05Ajk7ECWph5QRkqbK7BOIE5EmcgCyJE1SisjOnQ3GCSlR25jQWJyBL4gRkSZyALIkTkCVxgkq0qQeITJyALIkTkKOROAE5EicgT+IEZEmcgCyJE9TiNvUAcYkT1KJLPUBc4gRkSZyALIkTkCVxArIkTkCWxAnIkjgBWRInIEviBGRJnIAsiROQJXECsiROQJbECciSOAFZEicgS+IEZEmcgCyJE9RinHqAuMQJanGQeoC4xAnIkjgBWRInIEcLcQJyNBcnIEviBJU4TD1AZOIElajsmpM4AXkSJyBL4gRkSZygEpUdiHfiBJWo7EBcnIA8iROQJXECsiROUIkm9QCRiROQoRtxAnK0ECeoxSj1AJGJE1TiKPUAkYkTkCHbOqhFZa9emYsTVKKyV6/Y1gGZEieowyj1AHE5c4JajFIPEJczJyBT4gR1aFIPEJs4ARnqxAkqMUo9QFziBLWo7dUr4gR1qOyC+J04QSUquyA+FycgU+IEVWhSDxDXQpyAHNnWQS2a1ANEJ05AfmzroBYnqQeIy7YOKlHZNacQxAnqUNk1JysnqMUo9QCROXOCSoxSDxCfOEENKtvWXQdxgjo4EAeyVN9NAnGCGoxSDxDZIogTVGGUeoDIxAkqUdl5uG0d1GKUeoDIrJygElZOQJYq+2VdCEGcoAaj1ANEdh2COEENRqkH6IM4Qfma1ANE1oYgTlCD2s7DQwjiBDWoLU5tCOIENajurchDECeoQJN6gNjaEMQJKlDbru4uhCBOUIHa4jQPIYgTVKBJPUBkixCCOEH5Dms7D7dygjrUtqsLXQhBnKB8TeoBYutCCOIE5WtSDxCbbR3UobZt3Z0DcajC+CD1BJHdL5zECUpX28Lp4chJnKB0TeoBYuvu/xAnKFyTeoDYbOugCqParmBaOUEdmtQDRGflBFVoUg8Q283Dn+IEZWtSDxBb9/CnOEHR6jtyetjViROUrUk9QHTiBFU4Sz1AdN3Dn+IERWtSDxCdlRPUoLoX1t2/FXkI4gRlq3dXJ05QNHECcnR4nHqC6NrHv4gTFKy+hdPTebg4Qcma1ANEd7t4/Js4QcHqWzl1T38TJyhXU91Fgp9HTuIEBatv4fTzyEmcoGDiBORoXN0TCcJd9/RXcYJiTVIPEN/PhZM4Qbkq3NW1P/8qTlCqCnd1Vk5Qg0nqAXogTlCBCnd1t93Pv4sTFKryXZ04QakmqQfogThBBSrc1T3/ZZ04QaGaCnd14gQVmKQeoAc3zz8QJyjSYe27OnGCMp3V97SUl+fh4gRlmqQeoA/t8w/ECUo0Okk9QQ+eX8EUJyjTJPUAfXixqxMnKNIk9QB9aF98JE5QoLMaLzmJE5RvknqAPtzZ1kHpRqepJ+hD+/JDcYLyTFIP0Iv25YfiBOW5SD1AL9qXH4oTFGdS4+3w10dO4gTlGcTCSZygOM1x6gl60b76WJygNJPUA/SjffWxOEFhRuepJ+jF6yMncYLS1Hni9MvCSZygMIeT1BP04+r1J8QJynJR5T0CKyco3WGlu7qXz3IKQZygMFU+njcs2dWJE5RlmnqAnrS/fEacoCSTKh/kFMQJSjdNPUBPrhe/fEqcoCDVLpx+PXISJyjJNPUAfWl//ZQ4QTmqXTjdvn7tShAnKMk09QB9aZd8TpygGBe1LpyWHTmJExTjcJp6gt6IE5Ss1lfVhfBt2SfFCQpR66vqwvKFkzhBKabVLpyWnoeLExRi9CH1BL256ZZ9VpygDLPUA/RntvSz4gRFaE5ST9CfdulnxQmKcJl6gP4sux4exAnKcFHne9WFEN74XZ04QREqvn/55mmaOEEBLuu9RvDWrk6coABNne+jee+NXZ04QQEqPg1/+46EOEH2aj4Nf3NXJ06QvdE09QR9emtXJ06QvZpPw1fcfBcnyNzZaeoJ+vTmrk6cIHOHs9QT9OrNXZ04Qebq3tSteD2zOEHWqr7itGpXJ06QtcO3tz1VWPE/njhBzmZ1b+pWXS8VJ8hY3b+pe+sZmPfECfJV+W/qVj/eU5wgX1eVb+pWHTmJE+TrouJH84YQQvjWrfiP4gS5Gn9OPUHfVv4qUpwgU9UfOIU7cYISXdb8oJQQQghXi1X/VZwgT5O6r4aH8N5b8YkTZGlc9dMvQwgh3LYr/7M4QY4Oa78aHt59+LA4QY7qP3B653d14gRZuqj/wGn1JacgTpCjpvobTuG943BxggyNKn9OSgjhvUtOQZwgP4fVv6QuhPcXTuIE2RnCYfgabxQqTpCZ6QAOw0O47t77CnGCvEw+pp5gL96/YypOkJUB3AwPIYTb98/8xQlyMmqHcBi+xnG4OEFWhvGLuiBOUJjDdhC/qAvha/f+14gT5GMYlwjCWgsncYJ8zAZxiSC8+7CUe+IEuRjGBacQQpiu80XiBJkYyAWnsMbL6kII4gS5mHxJPcHezFY+O/yROEEWBtSmNW6HhyBOkIchtWmdewRBnCALQ2rTWvcIgjhBDgbVput2va8TJ0huUG1ad+EkTpDcsNp0O1vzC8UJEhtWm9a7gBmCOEFqA2vTehcwQxAnSGxgbQqXa13ADEGcIK3pwNp0t/6DPn/rcQzgHYN5DsGjq7UXTlZOkNDg2rT+cbiVE6QzmOde/rTmK1dCCFZOkMxoeG3aZOFk5QSJjAfyPivPbbJwsnKCNCYDbNOaz0p5IE6QwsWXAbbper7JV9vWwf4dXg7u13QhbHbiJE6QwOhqeEfhYf1npTywrYN9a+aDbNOGCydxgn27+D7A46aw8cLJtg7263B2mnqERKYbfr04wT6Nr45Sj5DIpgsn2zrYp4u/h9qmjRdOVk6wP8Pd0oXwrd30O6ycYF+a+XDbFC42/g5xgj2Zfh/slm7DV9Xds62DvRjPhnm56cF082+xcoJ9uPh70G3aYuFk5QR7MJqdpB4hqbvpFt9k5QS9m86H3aZw2W3xTVZO0LOBnzaFjd5y5RkrJ+jV4eWwT5tC2OS96p6zcoI+nV0O+P7Ag9vpVt8mTtCfoR+E35tu9222ddCXw+kPbQrherbd91k5QU8mUzu6ELZeOIkT9KOZWjWFELZ5xe8DcYIejC4H/BrflzZ/xe8DcYLoBvrmKkt96rb9TnGCyA4vLob5kPBltrt/GUIQJ4hMml642Or+ZQhBnCAqaXrpZrb994oTRCNNr219Gh7ECaIZXUyk6aWtrxGEIE4QyWjqN3Sv3e2ycBIniMGVy2W2eozTE3GCXR2eeaHKMls+jeCROMFuRtMzR01LTXb7dnGCXUwm9nNv2Ok0PIgT7GA88fu5N+12Gh7ECbZ1OJkM/vm7q+x2Gh7ECbYzOfPYgZVuprv+BHGCjZ2dOQN/z66bOnGCTSnTOv5sd/4R4gTrOzxrlGkdW73F7yviBGsanTXOmdY02f5JKU/ECdZx1py5Bb6266sIP0Sc4D3j5sxNy03cTWL8FHGCVcZN0zhl2tC0i/FTxAnecDhuGiumLVxv/9zw58QJlhiPm7H731va/YpTCEGc4LVmNB5bMO3g0zzOzxEneDQejccj66Ud7f66lQfiBOFwPBo1h7IUxSTWDxInBuxwHJqgSlHF2tSJEwM0Pgzjw9CEkVuV8UXb1IkTlWue/9mEEBx192sS70eJEz05+Sf1BOxfvE1dCP8T70cBA3c9jfjDxAmIJM5r6h6JExBJnNfUPRInII5vcV5T90icgCjiburECYgkxtMvnxMnIIY/Yzz98jlxAiKIeDX8gTgBEcTe1IkTEMMfEa+GPxAnYGeRbxGEEMQJ2N3tpIcfKk7AruIfOAVxAnb2qe3jp4oTsJuozyL4SZyAndyd9fNzxQnYyVkfB05BnIDd/NH29IPFCdhBHzec7okTsL2bSW8/WpyArd31csPpnjgBW7uI/5K6J+IEbOvPWY8/XJyALV1f9PnTxQnYzm1Pty8fiBOwlbu+bl8+ECdgK30ehocgTsB2ej0MD0GcgK186/UwPARxArbR483wR+IEbKzPm+GPxAnYWNPzYXgI4gRs7vc9tEmcgE31/ou6EII4AZv62vsv6kII4gRs6GY/bRInYCM3Tf+/qAshiBOwkX1cIrgnTsD67vZxieCeOAHr6/vVvs+IE7C232f7+7fECVjXfi44PRAnYE17uuD0QJyA9Xyb7PWfEydgLXt4SsoL4gSsY2+XLx+JE7CGvbdJnIA19P1WK0uIE/Cuu6bb+78pTsB79viilZ/ECXhHkjaJE/CONG0SJ+AdadokTsBqe3k3gyXECVhlnw8ieEGcgBWStUmcgBXStUmcgLclbJM4AW9K2SZxAt6StE3ht5T/OJCvu7M26b8vTsAyie6F/2RbByyRvE3iBCyRvk3iBPwqgzaJE/CLmwza5EAceG3/zwtfxsoJeCmPNokT8NK3PNokTsALX/f/PivLiRPwzNdJ6gkeiRPw0++T1BM8ESfgSdqX+r7kKgHwIIerlz9ZOQH3brNqk5UTcC+T601PrJyAEPK53vREnICQ0fWmJ+IEZHWF4JEzJyD1E3mXsnKCwbtp2tQjLGHlBEN3nd1xUwjBygkG72tuv6Z7IE4wbBkehd+zrYMhy+sVKy9YOcGA3YyybZM4wYB9Hed53BRCECcYsGyPm0IIzpxgsG7P8t3ShWDlBEN1Pc67TeIEw/Qp09tNP9nWwQBl+WK6V6ycYHiuR23qEd4nTjA4+W/pQrCtg8EpYUsXgpUTDE0RW7oQxAkGpowtXQi2dTAouV+8fM7KCYbja+4XL5+zcoKhuJtcpR5hE1ZOMBDX46LaJE4wEJ+aLvUIm7GtgyG4mRR02nTPygkG4FNJJ+EPrJygereTNvUIW7Bygtr9OW5Tj7ANKyeoW5nLpmDlBJUrdNkUrJygasUum4KVE9TsUylPIFjGyglqVeDdpuesnKBOd38UeLfpOSsnqNK3iy71CDsSJ6jQ7UVZL/JdxrYO6vNnYQ8gWMrKCWpzfVH2YdMDcYK63F3MUo8Qh20dVOXP0Sz1CJFYOUFFKtnRhRCsnKAit7839bTJygmq8emylLekW4s4QR3Kv3X5ijhBDW4u2tQjxObMCcp3+3uxT216m5UTlO7usq7DpgfiBIX7elFjmsQJClfdOfgTZ05QsOv/nHWpZ+iLlRMUq8Jf0T1j5QSFqvJXdM9YOUGRbqez1CP0TJygQPWnSZygQENIkzhBcYaRJnGCwgwlTeIERRlOmsQJCjKkNIkTFON62qYeYa/ECYrw7bJNPcKeiRMU4Ou0Sz3C3okT5K7S5zW9R5wgb7fTqyGmSZwgb0M7BX9GnCBbd1cDPGp6OLXC0QAAAYBJREFUIk6QqdvL2TD3cw/ECbI0vKsDr4kT5Od2NutSz5CcOEFuvs2uUo+QA3GCrFg0PRInyIhF00/iBLm4mQ3713OviBNk4W42m6eeIS/iBBn4emU795o4QWrfrgb66rnVxAmSuplddalnyJM4QTrKtII4QSLKtJo4QQrK9C5xgr27vlKm94kT7NVd63dz6xEn2J/bq9Z9pnWJE+zJ9VXrDvgGxAn24La9am3mNiNO0LdvrSXTFsQJ+nRz1bapZyiUOEFfbtrWXm574gR9EKadiRPEdt3aykUgThDR7dzhdyziBJFct3M7uYjECXZ3PZ/PLZgiEyfYxd1cl3oiTrClm24+n3epp6iXOMHGbru269rUU9ROnGAD14v5fNGmnmIYxAnWcb2YL+Zdl3qMIREnWOVmMV/MF3NXBPZPnGCJm0XXLeahTT3HkIkTPLpZhPkitGHhakAOxIkBuw4hdN39/2vTjsIvfrtOPUE0XeoB4ljU83+RXLxcCLVLPkeO/j9ra9XXmGnHxgAAAABJRU5ErkJggg==" id="id4" height="877" preserveAspectRatio="xMidYMid meet"/>
                <mask id="id5">
                    <g filter="url(#id1)">
                        <g filter="url(#id2)" transform="matrix(0.635593, 0, 0, 0.635405, 0.00000265, 96.292407)">
                            <image x="0" y="0" width="1180" xlink:href="data:image/png;base64,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" height="877" preserveAspectRatio="xMidYMid meet"/>
                        </g>
                    </g>
                </mask>
                <image x="0" y="0" width="1180" xlink:href="data:image/png;base64,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" id="id6" height="877" preserveAspectRatio="xMidYMid meet"/>
            </defs>
            <g clip-path="url(#id3)">
                <g mask="url(#id5)">
                    <g transform="matrix(0.635593, 0, 0, 0.635405, 0.00000265, 96.292407)">
                        <image x="0" y="0" width="1180" xlink:href="data:image/png;base64,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" height="877" preserveAspectRatio="xMidYMid meet"/>
                    </g>
                </g>
            </g>
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
    <span class="nav-btn-label">Boletim Maio/26</span>
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
    <textarea class="modal-ta" id="jsonInput" placeholder='{"periodo":"Maio 2026","kpis":{"score_medio":"98,4%","score_delta":"▲ +1,2 score vs Mar"},"analises":{"score":"Texto da análise..."},...}'></textarea>
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
    <div class="modal-row" style="justify-content:flex-end;align-items:center">
      <span id="pngStatus" style="font-size:.75rem;color:var(--g500);flex:1;display:none"></span>
      <button class="mbtn cancel" onclick="closeEmailModal()">Fechar</button>
      <button class="mbtn apply" onclick="copyEmailHtml()">Copiar HTML</button>
      <button class="mbtn dl" onclick="downloadEmail()">Download .html</button>
      <button class="mbtn dl" onclick="downloadEmailPng()" id="btnPng" style="background:#7C4DFF">Download PNG</button>
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
      <div style="position:absolute;left:400px;top:-180px;z-index:-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="1100" height="800" viewBox="1 1 48 42" fill="none">
          <defs>
            <linearGradient id="homeGrad2" x1="1" y1="1" x2="49" y2="43" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#a10b2c"/>
              <stop offset="50%" stop-color="#7d0630"/>
              <stop offset="100%" stop-color="#d43f67"/>
            </linearGradient>
          </defs>
          <g fill="url(#homeGrad2)">
            <path d="M2,17.132c0.415-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2.1,17.574C2,17.411,1.9,17.248,2,17.132z"/>
            <path d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g>
        </svg>
      </div>
      <p class="home-sub">Visão consolidada das entregas, métricas e iniciativas da área de Qualidade de Dados — <strong style="color:#fff">Maio de 2026</strong>.</p>
      <div class="home-cta-row">
        <button class="btn-primary" onclick="goPage('blt');toggleSB()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Ver Boletim Completo
        </button>
      </div>
    </div>
  </div>

  <!-- ── BOLETIM ── -->
  <div class="page page-boletim" id="page-blt">
    <div class="blt-header">
      <div class="blt-header-left">
        <h1>Boletim de Qualidade de Dados</h1>
        <p id="bltHeaderSub">Referência: Maio de 2026</p>
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
          <span class="sec-hd-badge">Maio 2026</span>
        </div>
        <div class="kpi-strip">
          <!-- Score -->
          <div class="kpi-wrapper">
            <div class="kpi-card green">
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
                <div><div class="kpi-lbl">Ativos de Dados</div><div class="kpi-val" id="kpi-prod-ativos">90</div></div>
                <div><div class="kpi-lbl">Novos</div><div class="kpi-val" id="kpi-prod-entregues">6</div></div>
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
              <div class="kpi-lbl">Taxa de Resolução de Chamados Histórica</div>
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
          <!-- Chamados em Aberto Fora Prazo -->
          <div class="kpi-wrapper">
            <div class="kpi-card">
              <div class="kpi-lbl">Chamados Fora do Prazo</div>
              <div class="kpi-val" id="kpi-chamados-aberto">0</div>
              <div class="kpi-delta down" id="kpi-chamados-aberto-delta">→ vs mês anterior</div>
            </div>
            <div class="kpi-card-blw">
              <div class="analysis-box-label">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Análise
              </div>
              <div class="analysis-box-text" id="ana-chamados-aberto">Sem chamados em aberto ao final do período.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 2. DADOS RELEVANTES ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Dados Relevantes</div></div>
        </div>

        

        <!-- Tabela Consolidada + Análise abaixo -->
        <div class="chart-card" style="margin-top:14px">
          <div class="chart-title">Produtos com Score abaixo de 95%</div>
          <table class="dados-table" style="margin-top:12px">
            <thead>
              <tr>
                <th>Criticidade</th><th>Produto</th><th>Área</th>
                <th>Score Abr/26</th><th>Score Mai/26</th><th>Tendência</th>
                <th>Chamados</th><th>Dimensão Crítica</th>
              </tr>
            </thead>
            <tbody id="scoreTbody"></tbody>
          </table>
          <div style="margin-top:14px;display:flex;flex-direction:column;gap:10px">
            <div class="cwa-analysis" style="border-radius:var(--r);flex-direction:column;gap:12px">
              <div class="cwa-analysis-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Analítico
              </div>
              <div class="cwa-sep"></div>
              <div>
                <div class="cwa-block-label">Análise de Desempenho e Impactos</div>
                <div class="cwa-block-text" id="ana-tabela-cham">Open Finance concentra 81 dos 94 chamados totais (86%). Demais produtos com volumes residuais — sinal de processo maduro nos outros squads.</div>
              </div>
              <div class="cwa-sep"></div>
              <div>
                <div class="cwa-block-label">Ações Estratégicas</div>
                <div class="cwa-block-text" id="ana-tabela-extr">Placeholder</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Causas-Raiz / Chamados + Análise -->
        <div class="chart-with-analysis side" style="margin-top:14px">
          <div class="cwa-chart">
            <div class="chart-title">Distribuição de Causas-Raiz — Chamados Mai/26</div>
            <div class="chart-wrap h300" style="margin-top:14px"><canvas id="cCausas"></canvas></div>
            <div id="legCausas" class="legend-row" style="margin-top:14px"></div>
          </div>
          <div class="cwa-analysis">
            <div class="cwa-analysis-title">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              Análise das Causas
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Destaque</div>
              <div class="cwa-block-text" id="ana-causas-conc">As 2 principais causas (Alteração na Estrutura e Open Finance) concentram 67% dos chamados — padrão de Pareto claro. Ações preventivas nessas categorias teriam impacto direto no volume total.</div>
            </div>
            <div class="cwa-sep"></div>
            <div>
              <div class="cwa-block-label">Volumetria de Causas Mitigáveis</div>
              <div class="cwa-block-text" id="ana-causas-resol">Sobre as causas identificadas </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 3. ENTREGAS DOS POs ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Principais Entregas do Mês</div></div>
        </div>
        <div class="po-grid">
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name"></div><div class="po-role"></div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content"> <!-- ══ DATA QUALITY ══ -->
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">BRAI4DQ — 10 Dimensões Ativas</div>
                <div class="po-item-desc">Evolução do BRAI4DQ com a implantação de 4 novas dimensões (Acurácia, Validade, Atualidade e Tempestividade). Com a entrega, a ferramenta atinge o marco de 10 dimensões ativas em ambiente produtivo, ampliando a capacidade de governança e elevando o padrão de qualidade dos Produtos de Dados.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Dimensão de Variação — Junto a Área de Crédito</div>
                <div class="po-item-desc">Desenvolvimento da nova dimensão VDI (Variable Distribution Index) em parceria com a área de Crédito. A iniciativa otimiza processos operacionais e expande nossa esteira de monitoramento, viabilizando a cobertura de novos casos de uso e necessidades dos Produtos de Dados.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Evolução e Integração do Modelo Dimensional ao BRAIA4QD</div>
                <div class="po-item-desc">Evolução do modelo dimensional para integração com os dados do BRAIA4QD. A atualização viabiliza o atendimento às novas demandas do ecossistema Braia e otimiza diretamente a performance e eficiência dos casos de uso já vigentes.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name"></div><div class="po-role"></div></div>
            </div>
            <div class="po-items">
              
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Definição e Documentação da Matriz de Criticidade de Ativos de Dados</div>
                <div class="po-item-desc">Definição da classificação de criticidade para os ativos de dados (Produtos, Casos de Uso, Modelos e Delta Sharing). A entrega consiste em uma matriz multicritério que avalia dimensões de risco, impacto e dependência técnica. Esse novo modelo estabelece um padrão unificado para apoiar a tomada de decisão executiva, garantindo que ativos de alta sensibilidade institucional recebam a devida priorização em ações de governança, monitoramento e mitigação de incidentes.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-ops">Eficiência</span>
                <div class="po-item-title">Mapeamento de Origens e Rastreabilidade de Ativos</div>
                <div class="po-item-desc">Desenvolvimento de nova dimensão focada no mapeamento estrutural entre bases, catálogos, tabelas e campos. Essa entrega estabelece a linhagem de dados completa, garantindo governança, total rastreabilidade e transparência sobre a origem das informações consumidas pelo portfólio de Produtos de Dados.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name"></div><div class="po-role"></div></div>
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Avanços — Agente DEVA Web</div>
                <div class="po-item-desc">Implantação de infraestrutura no ambiente ARO (plataforma LEAP) para hospedagem da interface web do Agente DEVA. A entrega estabelece a base tecnológica necessária para a evolução do agente, viabilizando uma experiência de usuário (UX) superior e estruturada.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content"> <!-- ══ IDQ DECOMISSIONING ══ -->
                <span class="po-item-tag tag-ops">Eficiência</span>
                <div class="po-item-title">Descomissionamento On-Premises</div>
                <div class="po-item-desc">Como parte da migração para o Databricks, realizamos a desativação de 12 tabelas (4 CRM — Teradata, 6 Marketing — Hive, 2 PLD — Hive). O plano continua em junho, com o descomissionamento projetado de 254 tabelas (119 Vila — Hive, 114 CRM — Teradata, 16 BRAIN — Hive e 5 PLD — Hive). A iniciativa faz a transição arquitetural e promove a utilização do ambiente Databricks como já previsto.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-ops">Eficiência</span>
                <div class="po-item-title">Feature de Alertas ao Produto de Dados de Qualidade</div>
                <div class="po-item-desc">Implementação de rotinas automatizadas de notificação para ocorrências de erro em lote e sublote. Por meio da integração do Databricks com o Teams, a entrega cria uma esteira de observabilidade em tempo real, conferindo visibilidade imediata às falhas e otimizando a mitigação de impactos na operação.</div>
              </div></div>
            </div>
          </div>
          <div class="po-card">
            <div class="po-card-head">
              <div><div class="po-name"></div><div class="po-role"></div></div> <!-- ══ DTTOOLS ══ -->
            </div>
            <div class="po-items">
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Modelo Trabalhista</div>
                <div class="po-item-desc">Implantação de modelos de Machine Learning no ADAC/Databricks (Squad People Analytics) para otimizar processos trabalhistas. A solução disponibiliza painéis que guiam o Jurídico e o RH na priorização preventiva e estratégica de casos (pré e pós-judicialização), destravando ganho de eficiência operacional e redução de passivos.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Modelo de Propensão BACEN — Ouvidoria</div>
                <div class="po-item-desc">Implantação de modelo preditivo de propensão (H2O/Databricks) pela Squad Ouvidoria para antecipar o escalonamento de queixas ao BACEN. Com atualização diária e painel de monitoramento, a ferramenta viabiliza ações proativas em casos de alto risco, reduzindo incidentes regulatórios, mapeando ofensores estruturais e protegendo o ranking da instituição.</div>
              </div></div>
              <div class="po-item-sep"></div>
              <div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
                <span class="po-item-tag tag-struct">Estruturante</span>
                <div class="po-item-title">Modelo de Procedência BACEN — Ouvidoria</div>
                <div class="po-item-desc">Desenvolvimento de modelo preditivo (Squad Ouvidoria) para estimar o risco de procedência de reclamações no BACEN. A inteligência fornece previsibilidade para atuação preventiva em casos com tendência de decisão desfavorável à instituição, mitigando perdas financeiras, otimizando o ranking regulatório e direcionando melhorias nos processos internos.</div>
              </div></div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══ 4. RELEASES ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Ativos de Dados — Releases Maio/26</div></div>
        </div>
        <div class="release-grid">
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Retorno de Mídias</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Bradesco Global Solution</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Caso de Uso — Modelo Trabalhista</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Caso de Uso — Modelos Ouvidoria</div><div class="release-ver">Release 1</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Encarteiramento Bradesco</div><div class="release-ver">Release 3</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Renegociação de Dívidas</div><div class="release-ver">Release 5</div></div>

        </div>
      </section>


      <!-- ══ 5. RELEASES PREVISTAS ══ -->
      <section class="reveal">
        <div class="sec-hd">
          <div class="sec-hd-bar"></div>
          <div><div class="sec-hd-title">Ativos de Dados — Releases Previstas Junho/26</div></div>
        </div>
        <div class="release-grid">
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Capital Regulatório</div><div class="release-ver">Release 1</div></div>
          <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Fundos</div><div class="release-ver">Release 1</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Empréstimos e Financiamentos</div><div class="release-ver">Release 4</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Informação Agro</div><div class="release-ver">Release 2</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Simulador de Métricas de Risco de Contraparte de Derivativos - CVA</div><div class="release-ver">Release 2</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Open Finance</div><div class="release-ver">Release 7</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Carteiras Administradas de Terceiros</div><div class="release-ver">Release 3</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Captação Líquida</div><div class="release-ver">Release 2</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Empréstimo Rural</div><div class="release-ver">Release 3</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Folha de Pagamento</div><div class="release-ver">Release 3</div></div>
          <div class="release-card update"><span class="release-badge">Atualização</span><div class="release-name">Interações BIA</div><div class="release-ver">Release 6</div></div>
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
  periodo: "Maio de 2026",
  gerado: "31/05/2026",
  kpis: {
    score: "97.06%", score_delta: "▲ 0.75% vs Abril/2026",
    prod_ativos: 87, prod_entregues: 4, prod_delta: "▲ 2 Produtos e 2 Modelos",
    tempo: 9, tempo_delta: "▼ 2 Dias vs Abril/2026",
    chamados: 92.83, chamados_delta: "▲ 1771 Chamados vs Abril/2026",
    chamados_aberto: 416, chamados_aberto_delta: "▲ 406 vs Abril/2026"
  },
  analises: {
    score: "Score em 97,06% com alta de 0,75 pp vs Abril. Tend\u00eancia positiva sustentada pelo crescimento estável de ativos já consolidados podendo dar destaque a <i>Rentabilidade de Investimento BVP</i>, <i> Bradesco Global Solution</i> e <i>Clientes Apostadores</i>, sendo os 3 produtos com maior crescimento.",
    prod: "87 ativos monitorados, 4 entregas no per\u00edodo (2 produtos e 2 modelos). Capacidade de absor\u00e7\u00e3o mantida sem impacto no desempenho.",
    tempo: "Tempo m\u00e9dio de 9 dias, redu\u00e7\u00e3o de 2 dias vs Abril. Ganho de efici\u00eancia operacional no ciclo de entregas impulsionado pelo uso do DEVA e BRAIA4DQ.",
    chamados: "Taxa de Resolu\u00e7\u00e3o de Chamados Histórica em 92,83% com 1.771 chamados à mais. Desempenho no período abaixo do esperado com Taxa de Resolução Mensal em 79,40%.",
    chamados_aberto: "416 chamados fora do prazo (aumento de 406 vs anterior), volume esperado pelo crecimento de chamados no período. Backlog requer prioriza\u00e7\u00e3o imediata.",
    scores_atenc: "Rating PLDTF em 71,43% (\u221215,5 pp vs Abril) \u2014 principal detrator. Pesquisa de Satisfa\u00e7\u00e3o em forte recupera\u00e7\u00e3o (93,3% ap\u00f3s 29,0% em Abril).",
    causas_conc: "Alta concentração de chamados em <strong>Rentabilidade de Investimento</strong>, totalizando 653 chamados gerados no período. O produto representou 54,25% dos casos Job/Malha com Atraso e 48,45% dos casos de Job/Malha com Erro de Execução, além de 15,34% dos casos de Base Origem Indisponível. O cenário é reflexo das alterações e revisões de regras atualmente em implementação.",
    causas_soluc: "Identificado que 21% (Preenchimento Incorreto e Alteração na Estrutura da Tabela ou Rotina) dos casos registrados possuem oportunidade de mitigação",
    tabela_crit: "6 ativos criticidade Baixa, 2 com criticidade Média e 2 com criticidade Alta. PLDTF (crit. Alta) com 71,43% \u2014 abaixo do limiar aceit\u00e1vel de 85%.",
    tabela_cham: "<br><strong>Dados Públicos Socioeconômicos e Demográficos</strong> \u2014 O score do produto segue abaixo do esperado, mesmo com alta de 5,83% no mês. O desvio ocorreu devido a 31 tabelas descalibradas, cujas correções já foram aplicadas e refletem no avanço recente." + "<br><br>" + "<strong>Operação e Repasse FINAME BNDES</strong> \u2014 Impacto negativo de 21,21% gerado por indisponibilidade na camada bronze. Incidente em tratativa com a equipe de Ingestão, no momento aguardando parecer definitivo." + "<br><br>" + "<strong>Rating de Riscos PLDTF</strong> \u2014 Mesmo com evolução de 4,52%, o score permanece abaixo do ideal devido à relevância de Alta Criticidade do produto. O impacto temporário deve-se à remoção de um schema em descontinuação, ação que já está sendo tratada pelo time técnico." + "<br><br>" + "<strong>Rentabilidade de Investimento</strong> \u2014 Com crescimento de 0,67%, o indicador atingiu 88,33%, mantendo-se em aceitável, sendo abaixo do esperado para a média criticidade. O patamar atual reflete a revisão em andamento das regras aplicadas para assegurar o alinhamento ao cenário real das tabelas, alta taxa de chamados atingindo 653 sendo concentrado em Base Indisponível e Job/Malha com Erro de Execução." + "<br><br>" + "<strong>Restritivo</strong> \u2014 Retração de 6,04% em produto de média criticidade devido à instabilidade nos pipelines, que gerou indisponibilidade temporária de dados para consumo. Os tech leads atuaram prontamente na correção e um comunicado oficial foi enviado sobre a manutenção dos processos, já colhendo resultados, estando atualmente 97,03%.",
    tabela_extr: "<br><strong>Kunumi</strong> \u2014 No contexto do Projeto Kunumi, a modernização do monitoramento do Comportamental PJ evolui com mapeamento concluído e ampliação de escopo por meio de IA aplicada à Qualidade de Dados. A curadoria técnica está em fase final, com conclusão prevista para 12/06. Na sequência, a implementação das novas métricas deverá elevar o score do produto. O projeto inclui ainda o expurgo de tabelas obsoletas, conduzido em alinhamento contínuo com os responsáveis pelos dados, reforçando a governança. A entrega completa está estimada para jun/2026." + "<br><br>" + "<strong>CMR - Comprometimento Máximo de Renda</strong> \u2014 Em tratativa via sala de guerra junto à superintendência, identificou-se que a redução de aproximadamente 2 mil clientes elegíveis originou-se de um desvio na variável de book. A análise de Qualidade de Dados aponta um gap, visto que o processamento ocorre em uma camada de trabalho (WKR) sem monitoramento ativo, limitando visibilidade e governança — hoje restrito às fases iniciais. Como plano de ação, será avaliada a evolução da arquitetura para camadas estruturadas (Silver/Gold) ou sua formalização como Produto de Dados, garantindo monitoramento contínuo.",
  },
  meses: ['Mai/26','Abr/26','Mar/26','Fev/26','Jan/26'],
  produtos: [
    {nome:'Dados Publicos Socioeconomicos e Demograficos',gestor:'Controle Integrado de Riscos',scores:[80.43,72.11,68.09,68.30,74.13],chamados:45,dim:'Disponibilidade',crit:1},
    {nome:'Operacao e Repasse FINAME BNDES',gestor:'Controladoria',scores:[99.46,87.66,100.00,100.00,78.79],chamados:3,dim:'Disponibilidade',crit:1},
    {nome:'Rating de Risco PLDTF',gestor:'Segurança Corporativa',scores:[71.43,86.96,80.00,83.33,87.85],chamados:2,dim:'Unicidade',crit:3},
    {nome:'Rentabilidade de Investimento',gestor:'Investimentos',scores:[87.81,88.38,88.18,87.66,88.33],chamados:653,dim:'Consistencia',crit:2},
    {nome:'Autentificacao de Usuarios - Canais Digitais',gestor:'Bradesco Experience',scores:[110.00,110.00,110.00,90.19,92.40],chamados:6,dim:'Completude',crit:1},
    {nome:'Restritivo',gestor:'Credito',scores:[99.82,99.57,100.00,98.98,92.94],chamados:9,dim:'Disponibilidade',crit:2},
    {nome:'Simulador de Metricas de Risco de Contraparte de Derivativos CVA',gestor:'Controle Integrado de Riscos',scores:[110.00,110.00,99.71,92.96,94.89],chamados:42,dim:'Disponibilidade',crit:1}, 
  ],
  tendencia: {abertos:[1],concluidos:[1]},
  mediaDias: {data:[1]},
  causas: {
    labels:['Base Origem Indisponivel ou Disponibilizada com Atraso','Job/Malha com Erro de Execucao ou Abend','Job/Malha com Atraso','Alteracao na Estrutura da Tabela ou Rotina','Preenchimento Incorreto na Entrada do Dado','Open Finance Diversas','Externo','Problemas com Ambiente (Lentidao/Indisponibilidade/Etc.)','Falha Cluster - subnet_exhausted'],
    data:[202,97,94,86,42,36,28,7,3]
  }
};

const COLORS = [
  '#CC0A2F', '#3B6BF5', '#00C07A', '#F5A623', '#7C4DFF', '#00BFCF',
  '#E8143A', '#059669', '#D97706', '#2563EB', '#5A32C8', '#008C9E',
  '#EC4899', '#9CA3AF', '#BE185D', '#6B7280', '#06B6D4', '#8B5CF6',
  '#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#0891B2', '#7C3AED',
  '#047857', '#B45309'
];
const baseFont={family:"'DM Sans', sans-serif",size:11};
const gridColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g200').trim()||'#E2E4EA';
const textColor=()=>getComputedStyle(document.documentElement).getPropertyValue('--g600').trim()||'#5C6180';
const chartInstances={};
function destroyChart(id){if(chartInstances[id]){chartInstances[id].destroy();delete chartInstances[id];}}
function getCtx(id){const el=document.getElementById(id);if(!el)return null;destroyChart(id);return el.getContext('2d');}

// ─── APPLY DATA TO UI ───
function applyData(){
  // Header
  document.getElementById('bltHeaderSub').textContent=`Referência: ${D.periodo}`;
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
  setEl('kpi-chamados-aberto',D.kpis.chamados_aberto);
  setEl('kpi-chamados-aberto-delta',D.kpis.chamados_aberto_delta);
  // Analises
  const anaMap={
    'ana-score':'score','ana-prod':'prod','ana-tempo':'tempo','ana-chamados':'chamados','ana-chamados-aberto':'chamados_aberto',
    'ana-scores-atenc':'scores_atenc',
    'ana-causas-conc':'causas_conc','ana-causas-resol':'causas_soluc',
    'ana-tabela-crit':'tabela_crit','ana-tabela-cham':'tabela_cham', 'ana-tabela-extr':'tabela_extr'
  };
  Object.entries(anaMap).forEach(([id,key])=>setEl(id,D.analises[key]||''));
  // Table
  buildScoreTable();
  // Charts
  initCharts();
}
function setEl(id,val){const e=document.getElementById(id);if(e)e.innerHTML=val;}

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
  buildCausasChart();
}

function buildCausasChart(){
  const ctx=getCtx('cCausas');if(!ctx)return;
  const total=D.causas.data.reduce((a,b)=>a+b,0);
  const clrs=['#0c2a6e','#1f4a8a','#3567a5','#4f83bf','#6a9fd6','#87b9ea','#90c0ee','#99c7f1','#a2cef4','#abd5f7'];

  // Sort descending so biggest bar is on top
  const sorted=[...D.causas.labels.map((l,i)=>({l,v:D.causas.data[i],c:clrs[i%clrs.length]}))].sort((a,b)=>b.v-a.v);

  chartInstances['cCausas']=new Chart(ctx,{
    type:'bar',
    data:{
      labels:sorted.map(x=>x.l),
      datasets:[{
        data:sorted.map(x=>x.v),
        backgroundColor:sorted.map(x=>x.c),
        borderRadius:6,
        borderSkipped:false,
        barThickness:20
      }]
    },
    options:{
      indexAxis:'y',
      responsive:true,
      maintainAspectRatio:false,
      layout:{padding:{right:56}},
      plugins:{
        legend:{display:false},
        datalabels:{
          anchor:'end',
          align:'end',
          formatter:(v)=>{
            const pct=((v/total)*100).toFixed(0);
            return v+' ('+pct+'%)';
          },
          font:{size:10,weight:'700',family:"'DM Sans',sans-serif"},
          color:textColor,
          padding:{left:4}
        }
      },
      scales:{
        x:{ticks:{font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{ticks:{font:{...baseFont,size:10},color:textColor},grid:{display:false}}
      }
    }
  });

  // Legend: balls below chart
  const leg=document.getElementById('legCausas');
  if(leg){
    leg.innerHTML=sorted.map(x=>{
      const pct=((x.v/total)*100).toFixed(0);
      return `<div class="legend-item"><div class="legend-dot" style="background:${x.c}"></div>${x.l}: <strong style="margin-left:3px">${x.v}</strong>&nbsp;<span style="color:var(--g500);font-size:.66rem">(${pct}%)</span></div>`;
    }).join('');
  }
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
      layout:{padding:{right:42}},
      plugins:{
        legend:{display:false},
        datalabels:{anchor:'end',align:'end',font:{size:10,weight:'700'},
          formatter:v=>v?v.toFixed(1)+'%':'—',color:textColor,padding:{left:2}}
      },
      scales:{
        x:{min:60,max:100,ticks:{callback:v=>v+'%',font:baseFont,color:textColor},grid:{color:gridColor()}},
        y:{ticks:{font:{...baseFont,size:10},color:textColor},grid:{display:false}}
      }
    }
  });
}

function buildScoreTable(){
  const tbody=document.getElementById('scoreTbody');if(!tbody)return;
  tbody.innerHTML=D.produtos.map(p=>{
    const abr=p.scores[4]||p.scores[3],mar=p.scores[3];
    const trend=(!abr||!mar)?'—':(abr>mar?'<span class="trend-arrow" style="color:var(--green)">▲</span>':abr<mar?'<span class="trend-arrow" style="color:var(--red)">▼</span>':'<span style="color:var(--g500)">→</span>');
    const scoreClass=!mar?'':mar>=95?'score-great':mar>=85?'score-warn':'score-bad';
    const scoreClassMAI=!abr?'':abr>=95?'score-great':abr>=85?'score-warn':'score-bad';
    const critDot=p.crit===3?'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--red)"></span>':p.crit===2?'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--yellow)"></span>':'<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:var(--green)"></span>';
    const critLbl=p.crit===3?'<span style="display:inline-block;color:var(--red)">Alta</span>':p.crit===2?'<span style="display:inline-block;color:var(--yellow)">Média</span>':'<span style="display:inline-block;color:var(--green)">Baixa</span>';
    return `<tr><td style="text-align:center;width:20px;padding:2px">${critDot}&nbsp;${critLbl}</td><td><strong>${p.nome}</strong></td><td style="font-size:.75rem;color:var(--g600)">${p.gestor}</td><td><span class="badge-score ${scoreClass}">${mar?mar.toFixed(2)+'%':'—'}</span></td><td style="font-size:.78rem"><span class="badge-score ${scoreClassMAI}">${abr?abr.toFixed(2)+'%':'—'}</span></td><td style="text-align:center">${trend}</td><td style="font-size:.78rem">${p.chamados}</td><td style="font-size:.72rem;color:var(--g500)">${p.dim}</td></tr>`;
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
    kpis:{score:"string",score_delta:"string",prod_ativos:0,prod_entregues:0,prod_delta:"string",tempo:0,tempo_delta:"string",chamados:0,chamados_delta:"string",chamados_aberto:0,chamados_aberto_delta:"string"},
    analises:{score:"string",prod:"string",tempo:"string",chamados:"string",chamados_aberto:"string",scores_atenc:"string",causas_conc:"string",causas_soluc:"string",tabela_crit:"string",tabela_cham:"string",tabela_extr:"string"},
    meses:["string"],
    produtos:[{nome:"string",gestor:"string",scores:["number|null"],chamados:0,dim:"string",crit:1}],
    tendencia:{abertos:[0],concluidos:[0]},
    mediaDias:{data:[0]},
    causas:{labels:["string"],data:[0]}
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

function downloadEmailPng(){
  const btn    = document.getElementById('btnPng');
  const status = document.getElementById('pngStatus');

  // ── 1. Write email HTML into a hidden, off-screen iframe ──────────────────
  const html = buildEmailHtml();
  const iframe = document.createElement('iframe');
  // position off-screen so it doesn't flash
  iframe.style.cssText = 'position:fixed;left:-9999px;top:0;border:none;';
  // email is 900px wide — set exactly that so layout is correct
  iframe.width  = '900';
  iframe.height = '1';
  document.body.appendChild(iframe);

  const doc = iframe.contentDocument || iframe.contentWindow.document;
  doc.open(); doc.write(html); doc.close();

  // ── 2. Wait for iframe content to fully render (images + layout) ──────────
  btn.disabled  = true;
  btn.textContent = 'Gerando…';
  status.style.display = 'inline';
  status.textContent   = 'Renderizando — pode levar alguns segundos…';

  // Use requestAnimationFrame + small timeout to let the iframe paint
  setTimeout(() => {
    const iframeDoc  = iframe.contentDocument || iframe.contentWindow.document;
    const fullHeight = iframeDoc.body.scrollHeight || iframeDoc.documentElement.scrollHeight;

    // Resize iframe to full height so nothing is clipped
    iframe.height = fullHeight;

    // Small extra delay to ensure reflow after resize
    setTimeout(() => {
      html2canvas(iframeDoc.body, {
        scale       : 3,          // 3× = ~2700px wide, crisp on any screen
        useCORS     : true,
        allowTaint  : true,
        width       : 900,
        height      : fullHeight,
        windowWidth : 900,
        scrollX     : 0,
        scrollY     : 0,
        backgroundColor: '#ECEEF3'
      }).then(canvas => {
        // Download as PNG
        canvas.toBlob(blob => {
          const url = URL.createObjectURL(blob);
          const a   = document.createElement('a');
          a.href    = url;
          a.download = `boletim_email_${D.periodo.replace(/\s/g,'_')}.png`;
          a.click();
          URL.revokeObjectURL(url);

          // Clean up
          document.body.removeChild(iframe);
          btn.disabled    = false;
          btn.textContent = 'Download PNG';
          status.style.display = 'none';
        }, 'image/png');
      }).catch(err => {
        console.error('html2canvas error:', err);
        document.body.removeChild(iframe);
        btn.disabled    = false;
        btn.textContent = 'Download PNG';
        status.textContent = 'Erro ao gerar imagem. Tente novamente.';
      });
    }, 300);
  }, 600);
}

function buildEmailHtml(){
  // ── Capture charts (only causas — scores chart removed) ─────────────────────
  const causasCanvas = document.getElementById('cCausas');
  const causasImg = causasCanvas ? causasCanvas.toDataURL('image/png') : '';

  // ── Constants ────────────────────────────────────────────────────────────────
  const W   = 900;
  const pad = 40;
  const inner = W - pad * 2; // 820px

  // ── Helpers ──────────────────────────────────────────────────────────────────

  // Section heading — solid left bar, no gradient
  const secHd = (title) =>
    `<tr><td style="padding:0 ${pad}px;background:#ffffff">
       <table width="100%" cellpadding="0" cellspacing="0" border="0">
         <tr><td style="border-top:1px solid #E2E4EA;padding-top:24px;padding-bottom:4px">
           <table cellpadding="0" cellspacing="0" border="0"><tr>
             <td style="width:4px;background:#8C0F3B;font-size:1px;line-height:1px">&nbsp;</td>
             <td style="padding-left:10px;font-size:13px;font-weight:700;color:#1A1D2E;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${title}</td>
           </tr></table>
         </td></tr>
       </table>
     </td></tr>`;

  // Analysis strip — solid bg color, no gradient
  const ana = (color, bgcolor, label, text) =>
    `<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:0"><tr>
      <td style="background:${bgcolor}; border-left:3px solid ${color}; padding:9px 13px">
        <div style="font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:${color};margin-bottom:3px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${label}</div>
        <div style="font-size:11px;color:#5C6180;line-height:1.6;font-style:italic;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${text}</div>
      </td>
    </tr></table>`;



  // Spacer between ana strips
  const anaSpacer = `<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:6px;font-size:1px;line-height:1px">&nbsp;</td></tr></table>`;

  // KPI card — solid border-top, no gradient
  const kpiCard = (color, label, value, delta, deltaColor='#8A8FA8') =>
    `<td style="padding:3px;vertical-align:top">
       <table width="100%" cellpadding="0" cellspacing="0" border="0">
         <tr><td style="background:#F4F5F7;padding:0">
           <table width="100%" cellpadding="0" cellspacing="0" border="0">
             <tr><td style="background:${color};height:3px;font-size:1px;line-height:1px">&nbsp;</td></tr>
             <tr><td style="padding:14px 12px">
               <div style="font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${label}</div>
               <div style="font-size:24px;font-weight:800;color:${color};line-height:1;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${value}</div>
               <div style="font-size:10px;color:${deltaColor};margin-top:4px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${delta}</div>
             </td></tr>
           </table>
         </td></tr>
       </table>
     </td>`;

  // ── Table rows ────────────────────────────────────────────────────────────────
  const prodRows = D.produtos.map((p,i) => {
    const a = p.scores[4]||p.scores[3], m = p.scores[3];
    const t    = (!a||!m)?'→':(a>m?'▲':'▼');
    const tClr = (!a||!m)?'#888':(a>m?'#00C07A':'#E8143A');
    const sClr = !a?'#888':a>=95?'#00C07A':a>=85?'#F5A623':'#CC0A2F';
    const mClr = !m?'#888':m>=95?'#00C07A':m>=85?'#F5A623':'#CC0A2F';
    const cClr = p.crit===3?'#E8143A':p.crit===2?'#F5A623':'#00C07A';
    const cLbl = p.crit===3?'Alta':p.crit===2?'Média':'Baixa';
    const nome = p.nome.length>30?p.nome.slice(0,30)+'…':p.nome;
    const bg   = i%2===0?'#ffffff':'#F8F9FB';
    return `<tr style="background:${bg}">
      <td style="padding:8px 10px;border-bottom:1px solid #E2E4EA;white-space:nowrap;font-family:'Segoe UI',Helvetica,Arial,sans-serif">
        <table cellpadding="0" cellspacing="0" border="0"><tr>
          <td><div style="width:8px;height:8px;border-radius:50%;flex-shrink:0;background:${cClr}"></div></td>
          <td style="padding-left:5px;font-size:10px;font-weight:700;color:${cClr}">${cLbl}</td>
        </tr></table>
      </td>
      <td style="padding:8px 10px;font-size:11px;font-weight:700;color:#1A1D2E;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${nome}</td>
      <td style="padding:8px 10px;font-size:10px;color:#8A8FA8;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${p.gestor}</td>
      <td style="padding:8px 10px;text-align:center;border-bottom:1px solid #E2E4EA">
        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto"><tr>
          <td style="background:${mClr}20;padding:2px 8px;font-size:10px;font-weight:700;color:${mClr};font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">${m?m.toFixed(2)+'%':'—'}</td>
        </tr></table>
      </td>
      <td style="padding:8px 10px;text-align:center;border-bottom:1px solid #E2E4EA">
        <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto"><tr>
          <td style="background:${sClr}20;padding:2px 8px;font-size:10px;font-weight:700;color:${sClr};font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">${a?a.toFixed(2)+'%':'—'}</td>
        </tr></table>
      </td>
      <td style="padding:8px 10px;font-size:13px;font-weight:800;text-align:center;color:${tClr};border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${t}</td>
      <td style="padding:8px 10px;font-size:11px;text-align:center;color:#5C6180;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${p.chamados}</td>
      <td style="padding:8px 10px;font-size:10px;color:#8A8FA8;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${p.dim}</td>
    </tr>`;
  }).join('');

  // ── Releases ─────────────────────────────────────────────────────────────────
  const buildRelGrid = (section) => {
    const cards = [...section.querySelectorAll('.release-card')].map(rc => {
      const isNew = rc.classList.contains('new');
      const color = isNew ? '#00C07A' : '#3B6BF5';
      const badge = rc.querySelector('.release-badge')?.textContent || '';
      const name  = rc.querySelector('.release-name')?.textContent  || '';
      const ver   = rc.querySelector('.release-ver')?.textContent   || '';
      return {color, badge, name, ver};
    });
    const cols = 3;
    const cw   = Math.floor(inner/cols);
    const rows = [];
    for(let i=0;i<cards.length;i+=cols){
      const chunk = cards.slice(i,i+cols);
      let cells = chunk.map(r =>
        `<td width="${cw}" style="padding:4px;vertical-align:top">
           <table width="100%" cellpadding="0" cellspacing="0" border="0">
             <tr><td style="background:#ffffff;border:1px solid #E2E4EA;padding:0">
               <table width="100%" cellpadding="0" cellspacing="0" border="0">
                 <tr><td style="background:${r.color};height:3px;font-size:1px;line-height:1px">&nbsp;</td></tr>
                 <tr><td style="padding:12px 14px">
                   <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:7px"><tr>
                     <td style="background:${r.color}22;padding:2px 7px;font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:${r.color};font-family:'Segoe UI',Helvetica,Arial,sans-serif">${r.badge}</td>
                   </tr></table>
                   <div style="font-size:11px;font-weight:700;color:#1A1D2E;line-height:1.35;margin-bottom:4px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${r.name}</div>
                   <div style="font-size:10px;color:#8A8FA8;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${r.ver}</div>
                 </td></tr>
               </table>
             </td></tr>
           </table>
         </td>`
      ).join('');
      for(let j=chunk.length;j<cols;j++) cells += `<td width="${cw}" style="padding:4px"></td>`;
      rows.push(`<tr>${cells}</tr>`);
    }
    return rows.join('');
  };

  const relSections = document.querySelectorAll('section.reveal');
  let relGrid1=null,relGrid2=null,relTitle1='',relTitle2='';
  relSections.forEach(sec=>{
    const title = sec.querySelector('.sec-hd-title')?.textContent?.trim()||'';
    const grid  = sec.querySelector('.release-grid');
    if(!grid) return;
    if(title.toLowerCase().includes('prevista')){relGrid2=grid;relTitle2=title;}
    else{relGrid1=grid;relTitle1=title;}
  });
  const releaseRows1 = relGrid1?buildRelGrid(relGrid1):'';
  const releaseRows2 = relGrid2?buildRelGrid(relGrid2):'';

  // ── PO cards ─────────────────────────────────────────────────────────────────
  const poCards = [...document.querySelectorAll('.po-card')].map(pc=>{
    const name  = pc.querySelector('.po-name')?.textContent||'';
    const role  = pc.querySelector('.po-role')?.textContent||'';
    const items = [...pc.querySelectorAll('.po-item-content')].map(it=>{
      const tag  = it.querySelector('.po-item-tag')?.textContent||'';
      const ttl  = it.querySelector('.po-item-title')?.textContent||'';
      const desc = it.querySelector('.po-item-desc')?.textContent||'';
      const tagClr = it.querySelector('.po-item-tag')?.classList.contains('tag-struct')?'#c01137':
                     it.querySelector('.po-item-tag')?.classList.contains('tag-init')?'#3B6BF5':
                     it.querySelector('.po-item-tag')?.classList.contains('tag-ops')?'#00C07A':'#7C4DFF';
      return `<tr>
        <td style="width:6px;padding-top:20px;vertical-align:top">
          
        </td>
        <td style="padding-left:8px;padding-bottom:10px;padding-top:10px">
          
          <div style="font-size:11px;font-weight:700;color:#1A1D2E;line-height:1.35;margin-bottom:3px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${ttl}</div>
          <div style="font-size:10px;color:#5C6180;line-height:1.5;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${desc}</div>
        </td>
      </tr>`;
    }).join(`<tr><td colspan="2" style="padding:0 0 0 14px"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:1px;background:#E2E4EA;font-size:1px;line-height:1px">&nbsp;</td></tr></table></td></tr>`);
    return {name,role,items};
  });

  const poRows = (()=>{
    const cols=2, cw=Math.floor(inner/cols)-4;
    const rows=[];
    for(let i=0;i<poCards.length;i+=cols){
      const chunk=poCards.slice(i,i+cols);
      const cells=chunk.map(po=>
        `<td width="${cw}" style="padding:4px;vertical-align:top">
           <table width="100%" cellpadding="0" cellspacing="0" border="0">
             <tr><td style="border:1px solid #E2E4EA">
               <table width="100%" cellpadding="0" cellspacing="0" border="0">
                 <tr><td style="background:#F4F5F7;border-bottom:1px solid #E2E4EA;padding:11px 14px">
                   <div style="font-size:12px;font-weight:700;color:#1A1D2E;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${po.name}</div>
                   <div style="font-size:10px;color:#8A8FA8;margin-top:2px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">${po.role}</div>
                 </td></tr>
                 <tr><td style="padding:12px 14px">
                   <table width="100%" cellpadding="0" cellspacing="0" border="0">
                     ${po.items}
                   </table>
                 </td></tr>
               </table>
             </td></tr>
           </table>
         </td>`
      ).join('');
      rows.push(`<tr>${cells}</tr>`);
    }
    return rows.join('');
  })();

  // ── Assemble ──────────────────────────────────────────────────────────────────
  return `<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8">
<title>Boletim Qualidade — ${D.periodo}</title>
</head>
<body style="margin:0;padding:0;background:#ECEEF3;font-family:'Segoe UI',Helvetica,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ECEEF3;padding:28px 0">
<tr><td align="center">
<table width="${W}" cellpadding="0" cellspacing="0" border="0" style="max-width:${W}px;background:#ffffff">

  <!-- HEADER — solid color, no gradient -->
  <tr><td style="background:#8C0F3B;padding:30px ${pad}px 26px">
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>
      <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom:11px"><tr>
        <td style="border:1px solid rgba(255,255,255,.25);padding:3px 12px;font-size:9px;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.1em;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Boletim Mensal · Inteligência de Dados</td>
      </tr></table>
      <div style="font-size:24px;font-weight:800;color:#ffffff;line-height:1.05;margin-bottom:8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Boletim de Qualidade de Dados</div>
      <div style="font-size:12px;color:rgba(255,255,255,.65);font-family:'Segoe UI',Helvetica,Arial,sans-serif">Referência: <strong style="color:#ffffff">${D.periodo}</strong> &nbsp;</div>
    </td></tr></table>
  </td></tr>

  <!-- KPIs -->
  ${secHd('Resumo Executivo')}
  <tr><td style="padding:14px ${pad}px 0;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      ${kpiCard('#00C07A','Score Médio',D.kpis.score,D.kpis.score_delta,'#00C07A')}
      ${kpiCard('#00C07A','Ativos de Dados',D.kpis.prod_ativos,D.kpis.prod_delta,'#00C07A')}
      ${kpiCard('#3B6BF5','Tempo médio de Entrega',D.kpis.tempo,D.kpis.tempo_delta,'#00C07A')}
      ${kpiCard('#F5A623','Taxa de Resolução Histórica',D.kpis.chamados+'%',D.kpis.chamados_delta,'#FF2C2C')}
      ${kpiCard('#c01137','Chamados Fora do Prazo',D.kpis.chamados_aberto,D.kpis.chamados_aberto_delta,'#FF2C2C')}
    </tr></table>
  </td></tr>

  <!-- KPI analyses — two columns -->
  <tr><td style="padding:10px ${pad}px 24px;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
      <td width="${Math.floor(inner/2)-6}" style="vertical-align:top;padding-right:6px">
        ${ana('#00C07A','#e7f9f2','Score Médio Geral',D.analises.score)}
        ${anaSpacer}
        ${ana('#3B6BF5','#edf1fe','Tempo de Entrega',D.analises.tempo)}
        ${anaSpacer}
        ${ana('#c01137','#f9e9ec','Chamados Fora do Prazo',D.analises.chamados_aberto)}
      </td>
      <td width="${Math.floor(inner/2)-6}" style="vertical-align:top;padding-left:6px">
        ${ana('#00C07A','#e7f9f2','Ativos de Dados',D.analises.prod)}
        ${anaSpacer}
        ${ana('#F5A623','#fef7ea','Taxa de Resolução',D.analises.chamados)}
      </td>
    </tr></table>
  </td></tr>

  <!-- TABELA CONSOLIDADA -->
  ${secHd('Produtos com Score abaixo de 95%')}
  <tr><td style="padding:14px ${pad}px 8px;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E2E4EA">
      <tr style="background:#F4F5F7">
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">Crit.</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Produto</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Área</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">Score Abr</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">Score Mai</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Tend.</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:center;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">Cham.</th>
        <th style="padding:9px 10px;font-size:8px;font-weight:700;color:#8A8FA8;text-transform:uppercase;letter-spacing:.06em;text-align:left;border-bottom:1px solid #E2E4EA;font-family:'Segoe UI',Helvetica,Arial,sans-serif;white-space:nowrap">Dim. Crítica</th>
      </tr>
      ${prodRows}
    </table>
  </td></tr>
  <!-- Table analyses below -->
  <tr><td style="padding:0 ${pad}px 24px;background:#ffffff">
    ${ana('#c01137','#f9e9ec','Distribuição por Criticidade',D.analises.tabela_crit)}
    ${anaSpacer}
    ${ana('#F5A623','#fef7ea','Análise de Desempenho e Impactos',D.analises.tabela_cham)}
    ${anaSpacer}
    ${ana('#c01137','#f9e9ec','Ações Estratégicas',D.analises.tabela_extr)}
    ${anaSpacer}
  </td></tr>

  <!-- CAUSAS — full width image, analyses below -->
  ${secHd('Causas-Raiz — Chamados '+D.periodo)}
  ${causasImg ? `
  <tr><td style="padding:14px ${pad}px 8px;background:#ffffff">
    <img src="${causasImg}" width="${inner}" style="width:100%;max-width:${inner}px;display:block;border:1px solid #E2E4EA" alt="Causas-Raiz">
  </td></tr>
  <tr><td style="padding:0 ${pad}px 24px;background:#ffffff">
    ${ana('#c01137','#f9e9ec','Destaque',D.analises.causas_conc)}
    ${anaSpacer}
    ${ana('#F5A623','#fef7ea','Solução',D.analises.causas_soluc)}
    ${anaSpacer}
  </td></tr>` : ''}

  <!-- ENTREGAS POR SQUAD -->
  ${secHd('Principais Entregas do Mês')}
  <tr><td style="padding:14px ${pad}px 24px;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      ${poRows}
    </table>
  </td></tr>

  <!-- RELEASES -->
  ${relTitle1 ? secHd(relTitle1) : secHd('Ativos de Dados — Releases '+D.periodo)}
  <tr><td style="padding:14px ${pad}px 24px;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      ${releaseRows1}
    </table>
  </td></tr>

  ${releaseRows2 ? `
  ${relTitle2 ? secHd(relTitle2) : secHd('Ativos de Dados — Releases Previstas')}
  <tr><td style="padding:14px ${pad}px 24px;background:#ffffff">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      ${releaseRows2}
    </table>
  </td></tr>` : ''}

  <!-- FOOTER — solid color, no gradient -->
  <tr><td style="background:#8C0F3B;padding:18px ${pad}px">
    <div style="font-size:13px;font-weight:700;color:#ffffff;font-family:'Segoe UI',Helvetica,Arial,sans-serif">Boletim de Qualidade de Dados — ${D.periodo}</div>
    <div style="font-size:10px;color:rgba(255,255,255,.55);margin-top:3px;font-family:'Segoe UI',Helvetica,Arial,sans-serif">· Inteligência de Dados / Bradesco</div>
  </td></tr>

</table>
</td></tr></table>
</body></html>`;
}
// ─── INIT ───
applyData();
</script>
</body>
</html>
