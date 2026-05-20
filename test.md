# COMMAND ----------
# =============================================================================
# CÉLULA 12 — GERAÇÃO DO HTML SINGLETON
# Correções aplicadas:
#   - Dropdown da sidebar agora navega imediatamente para o dashboard e renderiza corretamente
#   - Dropdown da home também navega imediatamente ao selecionar (sem botão)
#   - Botão de tema removido do header do dashboard
#   - Bug do sidebar (cards sumindo ao trocar gerente) corrigido:
#     trocaGerente agora sempre chama goPage('dash') + animateReveal após renderDash
# =============================================================================

_json_dados = json.dumps(dados_gerentes, ensure_ascii=False)
_SC         = "<" + "/script>"
_SS         = "<" + "/style>"

_HTML = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — {PERIODO_TXT}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">{_SC}
<style>
:root{{
  --pr:#C01160;--pr-dk:#8C0F3B;--pr-lt:#DF1A73;
  --wh:#FFFFFF;--g50:#F9FAFB;--g100:#F3F4F6;--g200:#E5E7EB;
  --g300:#D1D5DB;--g600:#4B5563;--g700:#374151;--g800:#1F2937;--g900:#111827;
  --green:#059669;--yellow:#D97706;--red:#DC2626;
  --sh:0 4px 24px rgba(0,0,0,.07);--sh2:0 8px 40px rgba(0,0,0,.12);
  --radius:12px;--radius-lg:20px;
  --sb-w:240px;
}}
[data-theme="dark"]{{
  --wh:#111827;--g50:#1F2937;--g100:#374151;--g200:#4B5563;
  --g300:#6B7280;--g600:#D1D5DB;--g700:#E5E7EB;--g800:#F3F4F6;--g900:#F9FAFB;
}}
*{{margin:0;padding:0;box-sizing:border-box}}
html,body{{height:100%;font-family:'Inter',sans-serif;background:linear-gradient(180deg,var(--pr) 0%,var(--pr-dk) 55%,#990f48 100%);color:var(--g800)}}
.app{{display:flex;height:100vh;overflow:hidden}}

/* ── Sidebar ── */
.sidebar{{
  position:fixed;left:0;top:0;height:100vh;width:var(--sb-w);
  background:linear-gradient(180deg,var(--pr-dk) 0%,var(--pr) 100%);
  color:#fff;padding:28px 20px 20px;z-index:200;
  display:flex;flex-direction:column;gap:6px;
  border-radius:0 28px 28px 0;
  box-shadow:4px 0 24px rgba(0,0,0,.18);
  transition:transform .35s ease;
}}
.sidebar-logo{{display:flex;align-items:center;gap:10px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,.18)}}
.sidebar-logo svg{{flex-shrink:0;opacity:.92}}
.sidebar-logo span{{font-size:.82rem;font-weight:700;line-height:1.25;opacity:.9}}
.nav-item{{
  display:flex;align-items:center;gap:10px;
  color:rgba(255,255,255,.78);text-decoration:none;
  padding:9px 12px;border-radius:10px;
  font-weight:500;font-size:.85rem;cursor:pointer;
  transition:background .2s,color .2s;border:none;background:none;
  width:100%;text-align:left;
}}
.nav-item:hover{{background:rgba(255,255,255,.14);color:#fff}}
.nav-item.active{{background:rgba(255,255,255,.22);color:#fff;font-weight:700}}
.nav-item svg{{flex-shrink:0;opacity:.8}}
.nav-divider{{height:1px;background:rgba(255,255,255,.15);margin:8px 0}}
.nav-label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.55;padding:4px 12px}}
.sidebar-sel-wrap{{margin-top:4px;padding:10px 12px;background:rgba(0,0,0,.18);border-radius:10px}}
.sidebar-sel-wrap label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.7;display:block;margin-bottom:5px}}
.sidebar-sel{{width:100%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 8px;border-radius:8px;font-size:.8rem;font-family:inherit;outline:none;cursor:pointer}}
.sidebar-sel option{{background:#8C0F3B;color:#fff}}
.theme-row{{margin-top:auto;display:flex;align-items:center;justify-content:space-between;background:rgba(0,0,0,.18);padding:10px 12px;border-radius:10px;cursor:pointer}}
.theme-row span{{font-size:.75rem;font-weight:600;opacity:.75}}
.theme-track{{width:40px;height:22px;background:rgba(255,255,255,.25);border-radius:11px;position:relative;transition:background .3s}}
.theme-thumb{{width:16px;height:16px;background:#fff;border-radius:50%;position:absolute;top:3px;left:3px;transition:transform .3s ease}}
[data-theme="dark"] .theme-thumb{{transform:translateX(18px)}}

/* ── Conteúdo ── */
.main-wrap{{margin-left:var(--sb-w);flex:1;height:100vh;overflow:hidden;position:relative}}
.page{{
  position:absolute;inset:0;overflow-y:auto;
  opacity:0;visibility:hidden;transform:translateX(40px);
  transition:opacity .45s ease,transform .45s ease,visibility .45s;
}}
.page.active{{opacity:1;visibility:visible;transform:translateX(0)}}

/* ── Home ── */
.page-home{{
  background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 55%,#5E0827 100%);
  height:100%;min-height:100%;
  display:flex;flex-direction:column;justify-content:center;align-items:flex-start;
  padding:7% 8%;position:relative;overflow:hidden;
}}
.home-bg-circle{{position:absolute;right:-8%;top:-15%;width:55%;padding-top:55%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 70%);pointer-events:none}}
.home-bg-circle2{{position:absolute;right:12%;bottom:-20%;width:35%;padding-top:35%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.05) 0%,transparent 70%);pointer-events:none}}
.home-content{{position:relative;z-index:2;max-width:640px}}
.home-kicker{{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
  border-radius:20px;padding:5px 14px;
  font-size:.75rem;font-weight:700;color:rgba(255,255,255,.9);
  text-transform:uppercase;letter-spacing:.07em;margin-bottom:24px;
}}
.home-h1{{font-size:clamp(2.4rem,5vw,4rem);font-weight:900;color:#fff;line-height:1.08;margin-bottom:16px}}
.home-sub{{font-size:clamp(.9rem,1.5vw,1.1rem);color:rgba(255,255,255,.75);line-height:1.6;margin-bottom:36px;max-width:520px}}
.home-actions{{display:flex;align-items:center;gap:14px;flex-wrap:wrap}}
.btn-ghost{{
  background:rgba(255,255,255,.15);color:#fff;padding:12px 24px;border-radius:10px;
  font-size:.9rem;font-weight:600;border:1px solid rgba(255,255,255,.3);
  cursor:pointer;font-family:inherit;transition:background .2s;
}}
.btn-ghost:hover{{background:rgba(255,255,255,.25)}}
.home-sel-wrap{{
  margin-top:48px;padding-top:32px;border-top:1px solid rgba(255,255,255,.18);
  display:flex;align-items:center;gap:16px;flex-wrap:wrap;
}}
.home-sel-wrap label{{font-size:.82rem;font-weight:600;color:rgba(255,255,255,.8)}}
.home-sel{{
  background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);
  color:#fff;padding:10px 14px;border-radius:10px;
  font-size:.88rem;font-family:inherit;outline:none;min-width:260px;cursor:pointer;
}}
.home-sel option{{background:#8C0F3B}}
.home-decoration{{position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,rgba(255,255,255,.35),rgba(255,255,255,.08),rgba(255,255,255,.35))}}

/* ── Ajuda ── */
.page-ajuda{{background:var(--wh)}}
.ajuda-inner{{max-width:780px;margin:0 auto;padding:48px 40px}}
.ajuda-header{{background:linear-gradient(135deg,var(--pr),var(--pr-dk));border-radius:var(--radius-lg);padding:28px 32px;color:#fff;margin-bottom:32px}}
.ajuda-header h1{{font-size:1.5rem;font-weight:800;margin-bottom:6px}}
.ajuda-header p{{font-size:.88rem;opacity:.85;line-height:1.5}}
.ajuda-card{{background:var(--g50);border:1px solid var(--g200);border-radius:var(--radius);padding:22px 24px;margin-bottom:16px;border-left:4px solid var(--pr)}}
.ajuda-card h2{{font-size:.95rem;font-weight:700;color:var(--pr-dk);margin-bottom:10px;display:flex;align-items:center;gap:8px}}
.ajuda-card p,.ajuda-card li{{font-size:.85rem;color:var(--g700);line-height:1.65}}
.ajuda-card ul{{padding-left:18px;margin-top:6px}}
.ajuda-card li{{margin-bottom:6px}}
.ajuda-back{{display:inline-flex;align-items:center;gap:8px;background:var(--pr);color:#fff;padding:10px 20px;border-radius:10px;font-size:.85rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;margin-bottom:28px;transition:background .2s}}
.ajuda-back:hover{{background:var(--pr-dk)}}

/* ── Dashboard ── */
.page-dash{{background:var(--g50)}}
.dash-header{{
  background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 100%);
  padding:16px 32px;display:flex;align-items:center;
  justify-content:space-between;gap:12px;flex-shrink:0;
  position:sticky;top:0;z-index:50;
  box-shadow:0 4px 20px rgba(0,0,0,.15);
}}
.dash-header h1{{font-size:1.05rem;font-weight:800;color:#fff}}
.dash-header p{{font-size:.75rem;color:rgba(255,255,255,.75);margin-top:2px}}
.dash-body{{padding:24px 32px 40px}}
.sec{{margin-bottom:28px}}
.sec-title{{font-size:.92rem;font-weight:700;color:var(--pr-dk);margin-bottom:14px;display:flex;align-items:center;gap:8px}}
.sec-title::before{{content:'';display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));border-radius:2px;flex-shrink:0}}

/* KPI */
.kpi-grid{{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}}
.kpi-card{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  padding:20px;display:flex;flex-direction:column;gap:4px;box-shadow:var(--sh);
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease,box-shadow .25s;
}}
.kpi-card.revealed{{opacity:1;transform:translateY(0)}}
.kpi-card:hover{{box-shadow:var(--sh2)}}
.kpi-card.revealed:hover{{transform:translateY(-2px)}}
.kpi-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em}}
.kpi-value{{font-size:2.2rem;font-weight:900;color:var(--pr);line-height:1.05}}
.kpi-sub{{font-size:.72rem;color:var(--g600)}}
.kpi-card.kpi-alert .kpi-value{{color:var(--red)}}

/* Charts */
.chart-grid{{display:grid;gap:16px}}
.g2{{grid-template-columns:1fr 1fr}}
.chart-card{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  padding:18px;box-shadow:var(--sh);
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease,box-shadow .25s;
}}
.chart-card.revealed{{opacity:1;transform:translateY(0)}}
.chart-card:hover{{box-shadow:var(--sh2)}}
.chart-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px}}
.chart-wrap{{position:relative;width:100%}}
.h280{{height:280px}}.h240{{height:240px}}.h210{{height:210px}}

/* Tabela */
.table-card{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  overflow:hidden;box-shadow:var(--sh);
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease;
}}
.table-card.revealed{{opacity:1;transform:translateY(0)}}
table{{width:100%;border-collapse:collapse;font-size:.8rem}}
thead th{{background:var(--g50);border-bottom:2px solid var(--g200);padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}}
tbody td{{padding:10px 14px;border-bottom:1px solid var(--g100);color:var(--g700)}}
tbody tr:last-child td{{border-bottom:none}}
tbody tr{{transition:background .15s}}
tbody tr:hover td{{background:var(--g50)}}
.badge-dias{{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:#FEE2E2;color:var(--red)}}
.empty-msg{{text-align:center;padding:28px;color:var(--g600);font-size:.85rem}}

/* Parecer / Editor MD */
.parecer-wrap{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  box-shadow:var(--sh);overflow:hidden;
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease;
}}
.parecer-wrap.revealed{{opacity:1;transform:translateY(0)}}
.md-toolbar{{display:flex;align-items:center;flex-wrap:wrap;gap:4px;padding:10px 14px;border-bottom:1px solid var(--g200);background:var(--g50)}}
.md-btn{{
  background:var(--wh);border:1px solid var(--g200);color:var(--g700);
  padding:4px 9px;border-radius:6px;font-size:.78rem;font-weight:700;
  cursor:pointer;font-family:'Consolas','Courier New',monospace;
  transition:background .15s,color .15s,border-color .15s;line-height:1.4;
}}
.md-btn:hover{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-sep{{width:1px;height:18px;background:var(--g200);margin:0 4px;flex-shrink:0}}
.md-tabs{{display:flex;gap:0;margin-left:auto}}
.md-tab{{padding:4px 14px;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;border:1px solid var(--g200);background:var(--wh);color:var(--g600);transition:background .15s,color .15s}}
.md-tab.active{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-editor{{display:none;width:100%;min-height:120px;padding:14px 16px;font-family:'Consolas','Courier New',monospace;font-size:.82rem;color:var(--g800);background:var(--wh);border:none;outline:none;resize:vertical;line-height:1.6}}
.md-editor.shown{{display:block}}
.md-preview{{display:none;min-height:80px;padding:16px 18px;font-size:.88rem;color:var(--g700);line-height:1.7}}
.md-preview.shown{{display:block}}
.md-preview h1{{font-size:1.35rem;font-weight:800;color:var(--pr-dk);margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid var(--g200)}}
.md-preview h2{{font-size:1.1rem;font-weight:700;color:var(--pr-dk);margin:16px 0 8px}}
.md-preview h3{{font-size:.95rem;font-weight:700;color:var(--g800);margin:12px 0 6px}}
.md-preview strong{{font-weight:700;color:var(--g900)}}
.md-preview em{{font-style:italic;color:var(--g700)}}
.md-preview ul,.md-preview ol{{padding-left:22px;margin:8px 0}}
.md-preview li{{margin-bottom:5px}}
.md-preview blockquote{{border-left:3px solid var(--pr);padding:8px 14px;background:var(--g50);border-radius:0 6px 6px 0;margin:10px 0;color:var(--g700)}}
.md-preview code{{background:var(--g100);padding:1px 6px;border-radius:4px;font-family:'Consolas',monospace;font-size:.82em}}
.md-preview hr{{border:none;border-top:2px solid var(--g200);margin:14px 0}}
.md-preview p{{margin-bottom:8px}}
.md-hint{{padding:10px 16px;font-size:.73rem;color:var(--g300);font-style:italic;border-top:1px solid var(--g100)}}

@media(max-width:1024px){{.kpi-grid{{grid-template-columns:repeat(2,1fr)}}.g2{{grid-template-columns:1fr}}}}
@media(max-width:768px){{
  :root{{--sb-w:0px}}
  .sidebar{{transform:translateX(-100%)}}
  .sidebar.open{{transform:translateX(0)}}
  .kpi-grid{{grid-template-columns:1fr}}
  .dash-body{{padding:16px}}
  .ajuda-inner{{padding:24px 16px}}
}}
{_SS}
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="36" viewBox="0 0 750 749.999995" height="36" preserveAspectRatio="xMidYMid meet" version="1.0">
      <defs>
        <filter x="0%" y="0%" width="100%" height="100%" id="sb_f1"><feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 1 0" color-interpolation-filters="sRGB"/></filter>
        <clipPath id="sb_c1"><path d="M 0 96.292969 L 750 96.292969 L 750 653.542969 L 0 653.542969 Z" clip-rule="nonzero"/></clipPath>
      </defs>
      <g clip-path="url(#sb_c1)" filter="url(#sb_f1)">
        <path fill="#e30045" d="M2,17.085c0.464-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2,17.574z" transform="scale(15.8) translate(0,-2.5)"/>
        <path fill="#e30045" d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z" transform="scale(15.8) translate(0,-2.5)"/>
        <path fill="#e30045" d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z" transform="scale(15.8) translate(0,-2.5)"/>
      </g>
    </svg>
    <span>Qualidade<br>de Dados</span>
  </div>

  <button class="nav-item active" id="navHome" onclick="goPage('home')">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    Início
  </button>

  <button class="nav-item" id="navDash" onclick="goPage('dash')" style="display:none">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Boletim
  </button>

  <button class="nav-item" id="navAjuda" onclick="goPage('ajuda')">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    Como Usar
  </button>

  <div class="nav-divider"></div>
  <div class="nav-label">Gerente Sr.</div>

  <div class="sidebar-sel-wrap">
    <label for="sidebarSel">Selecionar</label>
    <select id="sidebarSel" class="sidebar-sel" onchange="trocaGerente(this.value)">
      <option value="">— Escolha —</option>
    </select>
  </div>

  <div class="theme-row" onclick="toggleTheme()">
    <span>Tema escuro</span>
    <div class="theme-track"><div class="theme-thumb"></div></div>
  </div>
</aside>

<div class="main-wrap" id="mainWrap">

  <!-- HOME -->
  <div class="page page-home active" id="pageHome">
    <div class="home-bg-circle"></div>
    <div class="home-bg-circle2"></div>
    <div class="home-content">
      <div class="home-kicker">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Período: {PERIODO_TXT}
      </div>
      <div style="display:flex;align-items:center;gap:15px">
        <h1 class="home-h1">Boletim<br>de Resultados</h1>
      </div>
      <div style="position:absolute;left:350px;top:-180px;z-index:-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="1100" height="800" viewBox="1 1 48 42" fill="none">
          <defs>
            <linearGradient id="homeGrad" x1="1" y1="1" x2="49" y2="43" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#e61f79"/>
              <stop offset="50%" stop-color="#961250"/>
              <stop offset="100%" stop-color="#5a0824"/>
            </linearGradient>
          </defs>
          <g fill="url(#homeGrad)">
            <path d="M2,17.085c0.464-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2,17.574C2,17.411,2,17.248,2,17.085z"/>
            <path d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g>
        </svg>
      </div>
      <p class="home-sub">Visão consolidada dos indicadores de qualidade para os Gerentes Seniores. Selecione abaixo para acessar o painel detalhado.</p>
      <div class="home-actions">
        <button class="btn-ghost" onclick="goPage('ajuda')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Como Usar
        </button>
      </div>
      <div class="home-sel-wrap">
        <label for="homeSel">Gerente Sênior:</label>
        <select id="homeSel" class="home-sel" onchange="trocaGerente(this.value)">
          <option value="">— Selecione um gerente —</option>
        </select>
      </div>
    </div>
    <div class="home-decoration"></div>
  </div>

  <!-- AJUDA -->
  <div class="page page-ajuda" id="pageAjuda">
    <div class="ajuda-inner">
      <button class="ajuda-back" onclick="goPage('home')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Voltar
      </button>
      <div class="ajuda-header">
        <h1>Como usar este Boletim</h1>
        <p>Guia rápido para navegação e interpretação dos dados.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Início</h2>
        <p>Na tela inicial selecione o <strong>Gerente Sênior</strong> no menu dropdown — o painel abre imediatamente. O menu lateral também permite trocar de gerente a qualquer momento.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Painel de Gráficos</h2>
        <ul>
          <li><strong>KPIs superiores:</strong> Produtos monitorados, total de chamados no período e chamados vencidos. O número de vencidos é idêntico ao total da tabela abaixo.</li>
          <li><strong>P-Score Ponderado:</strong> Os 10 produtos com piores scores. Vermelho &lt;70, âmbar &lt;85, verde ≥85.</li>
          <li><strong>Status e Tendência:</strong> Distribuição por situação e evolução mensal dos últimos 5 meses.</li>
          <li><strong>Causas e Dimensões:</strong> Causas raízes e distribuição por dimensão de qualidade.</li>
          <li><strong>Tabela de Vencidos:</strong> Chamados com mais de 5 dias sem encerramento.</li>
        </ul>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Parecer Analítico</h2>
        <p>Editor com suporte a <strong>Markdown</strong>: <code># Título</code>, <code>## Subtítulo</code>, <code>**negrito**</code>, <code>*itálico*</code>, <code>- lista</code>, <code>&gt; citação</code>, <code>---</code> divisória. Use os botões da barra ou escreva diretamente. Clique em <strong>Visualizar</strong> para ver o resultado formatado.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>Período de referência</h2>
        <p>Cobre os <strong>dois meses completos</strong> anteriores à data de execução do notebook. O mês atual nunca é incluído. A tendência abrange os <strong>5 meses</strong> anteriores.</p>
      </div>
    </div>
  </div>

  <!-- DASHBOARD -->
  <div class="page page-dash" id="pageDash">
    <div class="dash-header">
      <div>
        <h1 id="dashTitulo">Boletim — Gerente</h1>
        <p id="dashPeriodo">{PERIODO_TXT}</p>
      </div>
    </div>
    <div class="dash-body" id="dashBody">

      <div class="sec">
        <div class="sec-title">Carteira — Indicadores do Período</div>
        <div class="kpi-grid">
          <div class="kpi-card" id="kc1">
            <span class="kpi-label">Produtos de Dados</span>
            <span class="kpi-value" id="kProd">—</span>
            <span class="kpi-sub">Monitorados ativamente</span>
          </div>
          <div class="kpi-card" id="kc2">
            <span class="kpi-label">Chamados no Período</span>
            <span class="kpi-value" id="kTotal">—</span>
            <span class="kpi-sub">Período do boletim</span>
          </div>
          <div class="kpi-card kpi-alert" id="kc3">
            <span class="kpi-label">Vencidos (&gt; 5 dias)</span>
            <span class="kpi-value" id="kVencidos">—</span>
            <span class="kpi-sub">Sem encerramento — igual ao total da tabela</span>
          </div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">P-Score Ponderado por Produto (piores)</div>
        <div class="chart-card" id="ccScore">
          <div class="chart-label">&#x3A3;(avg_dim &times; dias_dim) / &#x3A3;dias_dim — exclui VARIACAO &nbsp;|&nbsp; Vermelho &lt;70 &nbsp; Âmbar &lt;85 &nbsp; Verde &#x2265;85</div>
          <div class="chart-wrap h280"><canvas id="cScore"></canvas></div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Status e Tendência de Chamados</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccStatus">
            <div class="chart-label">Distribuição por Status</div>
            <div class="chart-wrap h240"><canvas id="cStatus"></canvas></div>
          </div>
          <div class="chart-card" id="ccTrend">
            <div class="chart-label">Tendência Mensal — Abertos vs Concluídos (últimos 5 meses)</div>
            <div class="chart-wrap h240"><canvas id="cTrend"></canvas></div>
          </div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Causas e Distribuição por Dimensão</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccCausas">
            <div class="chart-label">Causas Raízes Operacionais (chamados ativos)</div>
            <div class="chart-wrap h210"><canvas id="cCausas"></canvas></div>
          </div>
          <div class="chart-card" id="ccDim">
            <div class="chart-label">Chamados por Dimensão de Qualidade</div>
            <div class="chart-wrap h210"><canvas id="cDimensoes"></canvas></div>
          </div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Chamados Vencidos (&gt; 5 dias sem encerramento)</div>
        <div class="table-card" id="tcVenc">
          <table>
            <thead><tr><th>ID</th><th>Produto</th><th>Dimensão</th><th>Aberto em</th><th>Dias em aberto</th></tr></thead>
            <tbody id="tbVencidos"></tbody>
          </table>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Parecer Analítico</div>
        <div class="parecer-wrap" id="parecerWrap">
          <div class="md-toolbar">
            <button class="md-btn" title="Título (H1)" onclick="mdInsert('# ','')">H1</button>
            <button class="md-btn" title="Subtítulo (H2)" onclick="mdInsert('## ','')">H2</button>
            <button class="md-btn" title="Sub-subtítulo (H3)" onclick="mdInsert('### ','')">H3</button>
            <div class="md-sep"></div>
            <button class="md-btn" title="Negrito" onclick="mdInsert('**','**')"><strong>N</strong></button>
            <button class="md-btn" title="Itálico" onclick="mdInsert('*','*')"><em>I</em></button>
            <div class="md-sep"></div>
            <button class="md-btn" title="Lista" onclick="mdInsert('\\n- ','')">&#8226; Lista</button>
            <button class="md-btn" title="Lista numerada" onclick="mdInsert('\\n1. ','')">1. Lista</button>
            <button class="md-btn" title="Citação" onclick="mdInsert('\\n> ','')">&#10077;</button>
            <button class="md-btn" title="Divisória" onclick="mdInsert('\\n---\\n','')">&#8212;</button>
            <div class="md-sep"></div>
            <div class="md-tabs">
              <button class="md-tab active" id="tabEditar" onclick="mdTab('editar')">Editar</button>
              <button class="md-tab" id="tabVer" onclick="mdTab('ver')">Visualizar</button>
            </div>
          </div>
          <textarea class="md-editor shown" id="mdEditor" placeholder="Digite o parecer aqui... Suporta Markdown: # Título, **negrito**, *itálico*, - lista, > citação"></textarea>
          <div class="md-preview" id="mdPreview"></div>
          <div class="md-hint">Dica: use a barra de ferramentas ou escreva Markdown diretamente. Clique em Visualizar para ver o resultado formatado.</div>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
var D = {_json_dados};
var _charts = {{}};
var _gerente = '';

// ── Tema ──
function toggleTheme() {{
  document.documentElement.dataset.theme =
    (document.documentElement.dataset.theme === 'dark') ? '' : 'dark';
}}

// ── Navegação ──
function goPage(id) {{
  ['pageHome','pageDash','pageAjuda'].forEach(function(p) {{
    document.getElementById(p).classList.remove('active');
  }});
  ['navHome','navDash','navAjuda'].forEach(function(n) {{
    var el = document.getElementById(n);
    if (el) el.classList.remove('active');
  }});
  var pid = 'page' + id.charAt(0).toUpperCase() + id.slice(1);
  document.getElementById(pid).classList.add('active');
  var nav = document.getElementById('nav' + id.charAt(0).toUpperCase() + id.slice(1));
  if (nav) nav.classList.add('active');
  if (id === 'dash') {{
    document.getElementById('navDash').style.display = '';
    setTimeout(animateReveal, 80);
  }}
}}

// ── Seleção de gerente ──
// Ponto central: qualquer mudança de gerente sempre abre/atualiza o dashboard.
// Correção do bug: _resetReveal é chamado DENTRO de renderDash, e animateReveal
// só é disparado via goPage('dash') → setTimeout, garantindo que o DOM
// já foi atualizado antes de adicionar .revealed.
function trocaGerente(g) {{
  if (!g) return;
  _gerente = g;
  document.getElementById('homeSel').value    = g;
  document.getElementById('sidebarSel').value = g;
  renderDash(g);
  goPage('dash');
}}

// ── Dashboard ──
function renderDash(g) {{
  var d = D[g];
  if (!d) return;
  document.getElementById('dashTitulo').textContent = 'Boletim — ' + g;
  document.getElementById('kProd').textContent      = d.kpis.produtos;
  document.getElementById('kTotal').textContent     = d.kpis.total_chamados;
  document.getElementById('kVencidos').textContent  = d.kpis.vencidos;
  _renderTabela(d.vencidos);
  _destroyCharts();
  _renderPScore(d.pscore);
  _renderStatus(d.statusChamados);
  _renderTendencia(d.tendencia);
  _renderCausas(d.causasRaizes);
  _renderDimensoes(d.dimensoes);
  _resetReveal();
}}

// ── Animações ──
function _resetReveal() {{
  document.querySelectorAll('#pageDash .kpi-card,#pageDash .chart-card,#pageDash .table-card,#pageDash .parecer-wrap').forEach(function(el) {{
    el.classList.remove('revealed');
  }});
}}

function animateReveal() {{
  var els = Array.from(document.querySelectorAll(
    '#pageDash .kpi-card,#pageDash .chart-card,#pageDash .table-card,#pageDash .parecer-wrap'
  ));
  els.forEach(function(el, i) {{
    setTimeout(function() {{ el.classList.add('revealed'); }}, i * 60);
  }});
}}

// ── Tabela ──
function _renderTabela(vencidos) {{
  var tbody = document.getElementById('tbVencidos');
  if (!vencidos || !vencidos.length) {{
    tbody.innerHTML = '<tr><td colspan="5" class="empty-msg">Nenhum chamado vencido no período</td></tr>';
    return;
  }}
  tbody.innerHTML = vencidos.map(function(v) {{
    return '<tr><td>' + v.id + '</td><td>' + v.prod + '</td><td>' + v.tipo +
           '</td><td>' + v.data + '</td><td><span class="badge-dias">' + v.dias + ' dias</span></td></tr>';
  }}).join('');
}}

// ── Gráficos ──
function _destroyCharts() {{
  Object.values(_charts).forEach(function(c) {{ try {{ c.destroy(); }} catch(e) {{}} }});
  _charts = {{}};
}}

function _mkChart(id, cfg) {{
  var el = document.getElementById(id);
  if (!el) return;
  _charts[id] = new Chart(el, cfg);
}}

var _palette = ['#C01160','#8C0F3B','#DF1A73','#D97706','#059669','#2563EB','#7C3AED'];

function _renderPScore(ps) {{
  if (!ps || !ps.labels.length) return;
  _mkChart('cScore', {{
    type:'bar',
    data:{{
      labels:ps.labels,
      datasets:[{{
        label:'P-Score',data:ps.data,borderRadius:5,borderWidth:0,
        backgroundColor:ps.data.map(function(v){{
          return v<70?'rgba(220,38,38,.78)':v<85?'rgba(217,119,6,.78)':'rgba(5,150,105,.78)';
        }})
      }}]
    }},
    options:{{
      indexAxis:'y',responsive:true,maintainAspectRatio:false,
      scales:{{x:{{min:0,max:100,grid:{{color:'rgba(0,0,0,.04)'}}}},y:{{ticks:{{font:{{size:11}}}}}}}},
      plugins:{{legend:{{display:false}}}}
    }}
  }});
}}

function _renderStatus(sc) {{
  _mkChart('cStatus', {{
    type:'doughnut',
    data:{{
      labels:['Encerrados/Concluídos','Em Andamento','Em Análise'],
      datasets:[{{data:sc,backgroundColor:['#059669','#D97706','#C01160'],borderWidth:3,borderColor:'#fff',hoverOffset:6}}]
    }},
    options:{{responsive:true,maintainAspectRatio:false,plugins:{{legend:{{position:'right',labels:{{font:{{size:11}},boxWidth:13,padding:14}}}}}}}}
  }});
}}

function _renderTendencia(t) {{
  if (!t || !t.meses.length) return;
  _mkChart('cTrend', {{
    type:'bar',
    data:{{
      labels:t.meses,
      datasets:[
        {{label:'Abertos',   data:t.abertos,   backgroundColor:'#C01160',borderRadius:4,borderWidth:0}},
        {{label:'Concluídos',data:t.concluidos,backgroundColor:'#059669',borderRadius:4,borderWidth:0}}
      ]
    }},
    options:{{responsive:true,maintainAspectRatio:false,scales:{{y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}},plugins:{{legend:{{position:'top',labels:{{font:{{size:11}},boxWidth:12,padding:12}}}}}}}}
  }});
}}

function _renderCausas(c) {{
  if (!c || !c.labels.length) return;
  _mkChart('cCausas', {{
    type:'bar',
    data:{{labels:c.labels,datasets:[{{label:'Chamados',data:c.data,backgroundColor:'#8C0F3B',borderRadius:4,borderWidth:0}}]}},
    options:{{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{{legend:{{display:false}}}},scales:{{x:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}}}}
  }});
}}

function _renderDimensoes(dim) {{
  if (!dim || !dim.labels.length) return;
  _mkChart('cDimensoes', {{
    type:'bar',
    data:{{
      labels:dim.labels,
      datasets:[{{
        label:'Chamados',data:dim.data,borderRadius:4,borderWidth:0,
        backgroundColor:dim.labels.map(function(_,i){{return _palette[i%_palette.length];}})
      }}]
    }},
    options:{{responsive:true,maintainAspectRatio:false,plugins:{{legend:{{display:false}}}},scales:{{y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}}}}
  }});
}}

// ── Editor Markdown ──
function mdTab(tab) {{
  var editor=document.getElementById('mdEditor'),preview=document.getElementById('mdPreview');
  var tE=document.getElementById('tabEditar'),tV=document.getElementById('tabVer');
  if (tab==='ver') {{
    preview.innerHTML=mdRender(editor.value);
    editor.classList.remove('shown');preview.classList.add('shown');
    tE.classList.remove('active');tV.classList.add('active');
  }} else {{
    preview.classList.remove('shown');editor.classList.add('shown');
    tV.classList.remove('active');tE.classList.add('active');
  }}
}}

function mdInsert(before,after) {{
  var ta=document.getElementById('mdEditor'),s=ta.selectionStart,e=ta.selectionEnd;
  var sel=ta.value.substring(s,e);
  var ins=before.replace(/\\\\n/g,'\\n')+sel+after;
  ta.value=ta.value.substring(0,s)+ins+ta.value.substring(e);
  ta.focus();
  ta.selectionStart=s+before.replace(/\\\\n/g,'\\n').length;
  ta.selectionEnd=ta.selectionStart+sel.length;
}}

function mdRender(md) {{
  if (!md) return '<span style="color:var(--g300);font-style:italic">Nenhum conteúdo para visualizar.</span>';
  return md
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/^### (.+)$/gm,'<h3>$1</h3>')
    .replace(/^## (.+)$/gm,'<h2>$1</h2>')
    .replace(/^# (.+)$/gm,'<h1>$1</h1>')
    .replace(/^---$/gm,'<hr>')
    .replace(/^&gt; (.+)$/gm,'<blockquote>$1</blockquote>')
    .replace(/^- (.+)$/gm,'<li>$1</li>')
    .replace(/^\\d+\\. (.+)$/gm,'<li>$1</li>')
    .replace(/(<li>.*<\\/li>\\n?)+/g,function(m){{return '<ul>'+m+'</ul>';}})
    .replace(/\\*\\*(.+?)\\*\\*/g,'<strong>$1</strong>')
    .replace(/\\*(.+?)\\*/g,'<em>$1</em>')
    .replace(/`(.+?)`/g,'<code>$1</code>')
    .replace(/^(?!<[hH1-6ulbci]|<hr|<blockquote)(.+)$/gm,'<p>$1</p>')
    .replace(/\\n{{2,}}/g,'');
}}

// ── Init ──
(function() {{
  var gerentes=Object.keys(D).sort();
  ['homeSel','sidebarSel'].forEach(function(id) {{
    var sel=document.getElementById(id);
    if (!sel) return;
    while (sel.options.length>1) sel.remove(1);
    gerentes.forEach(function(g) {{
      var o=document.createElement('option');
      o.value=g;o.text=g;sel.add(o);
    }});
  }});
}})();
{_SC}
</body>
</html>"""

# COMMAND ----------
# =============================================================================
# CÉLULA 13 — OUTPUT COPIÁVEL NO DATABRICKS
# =============================================================================

_html_esc = _HTML.replace("&","&amp;").replace("<","&lt;").replace(">","&gt;")
_SC2 = "<" + "/script>"
_SS2 = "<" + "/style>"
_ST2 = "<" + "/textarea>"

_output_ui = f"""
<style>
#_blt_wrap{{font-family:'Segoe UI',Arial,sans-serif;max-width:980px;margin:0 auto;padding:24px}}
#_blt_wrap h2{{font-size:1rem;font-weight:700;color:#8C0F3B;margin-bottom:6px}}
#_blt_wrap p{{font-size:.82rem;color:#374151;margin-bottom:12px;line-height:1.5}}
#_blt_code{{width:100%;height:340px;font-family:'Consolas','Courier New',monospace;font-size:.74rem;border:1px solid #D1D5DB;border-radius:8px;padding:12px;background:#F9FAFB;color:#1F2937;resize:vertical;white-space:pre;overflow:auto}}
#_blt_copy{{margin-top:10px;background:#C01160;color:#fff;border:none;padding:9px 22px;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit}}
#_blt_copy:hover{{background:#8C0F3B}}
#_blt_ok{{display:inline-block;margin-left:12px;font-size:.8rem;color:#059669;font-weight:600;opacity:0;transition:opacity .4s}}
{_SS2}
<div id="_blt_wrap">
  <h2>HTML do Boletim gerado com sucesso</h2>
  <p>Selecione tudo na caixa (Ctrl+A), copie (Ctrl+C) e cole em um arquivo <code>.html</code> fora do Databricks. O arquivo é autossuficiente e não requer servidor.</p>
  <textarea id="_blt_code" readonly spellcheck="false">{_html_esc}{_ST2}
  <br>
  <button id="_blt_copy" onclick="_copyBlt()">Copiar HTML</button>
  <span id="_blt_ok">Copiado com sucesso!</span>
</div>
<script>
function _copyBlt(){{var ta=document.getElementById('_blt_code');ta.select();document.execCommand('copy');var ok=document.getElementById('_blt_ok');ok.style.opacity='1';setTimeout(function(){{ok.style.opacity='0';}},2600);}}
{_SC2}
"""

displayHTML(_output_ui)




_______________________________________



# COMMAND ----------
# =============================================================================
# CÉLULA 12 — GERAÇÃO DO HTML SINGLETON (versão com sidebar colapsável)
# Todas as correções da versão base, mais:
#   - Botão de toggle na sidebar colapsa/expande com animação
#   - Quando colapsada: sidebar reduz para 56px, mostrando apenas ícones
#   - main-wrap ajusta margin-left via CSS custom property
#   - Textos, labels e seletor somem na versão colapsada
#   - Botão de toggle fica sempre visível no topo da sidebar
# =============================================================================

_json_dados = json.dumps(dados_gerentes, ensure_ascii=False)
_SC         = "<" + "/script>"
_SS         = "<" + "/style>"

_HTML_SB = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — {PERIODO_TXT}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">{_SC}
<style>
:root{{
  --pr:#C01160;--pr-dk:#8C0F3B;--pr-lt:#DF1A73;
  --wh:#FFFFFF;--g50:#F9FAFB;--g100:#F3F4F6;--g200:#E5E7EB;
  --g300:#D1D5DB;--g600:#4B5563;--g700:#374151;--g800:#1F2937;--g900:#111827;
  --green:#059669;--yellow:#D97706;--red:#DC2626;
  --sh:0 4px 24px rgba(0,0,0,.07);--sh2:0 8px 40px rgba(0,0,0,.12);
  --radius:12px;--radius-lg:20px;
  --sb-w:240px;
  --sb-collapsed:56px;
}}
[data-theme="dark"]{{
  --wh:#111827;--g50:#1F2937;--g100:#374151;--g200:#4B5563;
  --g300:#6B7280;--g600:#D1D5DB;--g700:#E5E7EB;--g800:#F3F4F6;--g900:#F9FAFB;
}}
*{{margin:0;padding:0;box-sizing:border-box}}
html,body{{height:100%;font-family:'Inter',sans-serif;background:linear-gradient(180deg,var(--pr) 0%,var(--pr-dk) 55%,#990f48 100%);color:var(--g800)}}
.app{{display:flex;height:100vh;overflow:hidden}}

/* ── Sidebar ── */
.sidebar{{
  position:fixed;left:0;top:0;height:100vh;width:var(--sb-w);
  background:linear-gradient(180deg,var(--pr-dk) 0%,var(--pr) 100%);
  color:#fff;padding:14px 10px 20px;z-index:200;
  display:flex;flex-direction:column;gap:4px;
  border-radius:0 28px 28px 0;
  box-shadow:4px 0 24px rgba(0,0,0,.18);
  transition:width .35s ease,padding .35s ease;
  overflow:hidden;
}}
.sidebar.collapsed{{width:var(--sb-collapsed)}}

/* Botão de toggle */
.sb-toggle{{
  display:flex;align-items:center;justify-content:flex-end;
  margin-bottom:10px;padding:0 2px;flex-shrink:0;
}}
.sb-toggle-btn{{
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
  color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s,transform .35s ease;flex-shrink:0;
}}
.sb-toggle-btn:hover{{background:rgba(255,255,255,.28)}}
.sidebar.collapsed .sb-toggle-btn{{transform:rotate(180deg)}}

/* Logo */
.sidebar-logo{{
  display:flex;align-items:center;gap:10px;
  margin-bottom:14px;padding-bottom:14px;
  border-bottom:1px solid rgba(255,255,255,.18);
  flex-shrink:0;overflow:hidden;white-space:nowrap;
}}
.sidebar-logo svg{{flex-shrink:0;opacity:.92}}
.sidebar-logo span{{font-size:.82rem;font-weight:700;line-height:1.25;opacity:.9;transition:opacity .2s}}
.sidebar.collapsed .sidebar-logo span{{opacity:0;pointer-events:none}}

/* Nav items */
.nav-item{{
  display:flex;align-items:center;gap:10px;
  color:rgba(255,255,255,.78);text-decoration:none;
  padding:9px 10px;border-radius:10px;
  font-weight:500;font-size:.85rem;cursor:pointer;
  transition:background .2s,color .2s;border:none;background:none;
  width:100%;text-align:left;white-space:nowrap;overflow:hidden;
}}
.nav-item:hover{{background:rgba(255,255,255,.14);color:#fff}}
.nav-item.active{{background:rgba(255,255,255,.22);color:#fff;font-weight:700}}
.nav-item svg{{flex-shrink:0;opacity:.8;min-width:16px}}
.nav-item-label{{transition:opacity .2s,max-width .35s ease;max-width:200px;overflow:hidden}}
.sidebar.collapsed .nav-item-label{{opacity:0;max-width:0}}

.nav-divider{{height:1px;background:rgba(255,255,255,.15);margin:6px 0;flex-shrink:0}}
.nav-label{{
  font-size:.68rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.08em;opacity:.55;padding:4px 10px;
  white-space:nowrap;overflow:hidden;
  transition:opacity .2s;
}}
.sidebar.collapsed .nav-label{{opacity:0}}

/* Seletor de gerente */
.sidebar-sel-wrap{{
  margin-top:4px;padding:10px;background:rgba(0,0,0,.18);border-radius:10px;
  flex-shrink:0;overflow:hidden;
  transition:opacity .2s,max-height .35s ease,padding .35s ease;
  max-height:80px;
}}
.sidebar.collapsed .sidebar-sel-wrap{{
  opacity:0;max-height:0;padding:0;pointer-events:none;
}}
.sidebar-sel-wrap label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.7;display:block;margin-bottom:5px}}
.sidebar-sel{{width:100%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 8px;border-radius:8px;font-size:.8rem;font-family:inherit;outline:none;cursor:pointer}}
.sidebar-sel option{{background:#8C0F3B;color:#fff}}

/* Tema toggle */
.theme-row{{
  margin-top:auto;display:flex;align-items:center;
  justify-content:space-between;background:rgba(0,0,0,.18);
  padding:10px;border-radius:10px;cursor:pointer;
  flex-shrink:0;overflow:hidden;white-space:nowrap;
}}
.theme-row-label{{font-size:.75rem;font-weight:600;opacity:.75;transition:opacity .2s}}
.sidebar.collapsed .theme-row-label{{opacity:0;max-width:0;overflow:hidden}}
.theme-track{{width:40px;height:22px;background:rgba(255,255,255,.25);border-radius:11px;position:relative;transition:background .3s;flex-shrink:0}}
.theme-thumb{{width:16px;height:16px;background:#fff;border-radius:50%;position:absolute;top:3px;left:3px;transition:transform .3s ease}}
[data-theme="dark"] .theme-thumb{{transform:translateX(18px)}}

/* ── Conteúdo ── */
.main-wrap{{
  margin-left:var(--sb-w);flex:1;height:100vh;
  overflow:hidden;position:relative;
  transition:margin-left .35s ease;
}}
.main-wrap.collapsed{{margin-left:var(--sb-collapsed)}}

.page{{
  position:absolute;inset:0;overflow-y:auto;
  opacity:0;visibility:hidden;transform:translateX(40px);
  transition:opacity .45s ease,transform .45s ease,visibility .45s;
}}
.page.active{{opacity:1;visibility:visible;transform:translateX(0)}}

/* ── Home ── */
.page-home{{
  background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 55%,#5E0827 100%);
  height:100%;min-height:100%;
  display:flex;flex-direction:column;justify-content:center;align-items:flex-start;
  padding:7% 8%;position:relative;overflow:hidden;
}}
.home-bg-circle{{position:absolute;right:-8%;top:-15%;width:55%;padding-top:55%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 70%);pointer-events:none}}
.home-bg-circle2{{position:absolute;right:12%;bottom:-20%;width:35%;padding-top:35%;border-radius:50%;background:radial-gradient(ellipse,rgba(255,255,255,.05) 0%,transparent 70%);pointer-events:none}}
.home-content{{position:relative;z-index:2;max-width:640px}}
.home-kicker{{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:5px 14px;font-size:.75rem;font-weight:700;color:rgba(255,255,255,.9);text-transform:uppercase;letter-spacing:.07em;margin-bottom:24px}}
.home-h1{{font-size:clamp(2.4rem,5vw,4rem);font-weight:900;color:#fff;line-height:1.08;margin-bottom:16px}}
.home-sub{{font-size:clamp(.9rem,1.5vw,1.1rem);color:rgba(255,255,255,.75);line-height:1.6;margin-bottom:36px;max-width:520px}}
.home-actions{{display:flex;align-items:center;gap:14px;flex-wrap:wrap}}
.btn-ghost{{background:rgba(255,255,255,.15);color:#fff;padding:12px 24px;border-radius:10px;font-size:.9rem;font-weight:600;border:1px solid rgba(255,255,255,.3);cursor:pointer;font-family:inherit;transition:background .2s}}
.btn-ghost:hover{{background:rgba(255,255,255,.25)}}
.home-sel-wrap{{margin-top:48px;padding-top:32px;border-top:1px solid rgba(255,255,255,.18);display:flex;align-items:center;gap:16px;flex-wrap:wrap}}
.home-sel-wrap label{{font-size:.82rem;font-weight:600;color:rgba(255,255,255,.8)}}
.home-sel{{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);color:#fff;padding:10px 14px;border-radius:10px;font-size:.88rem;font-family:inherit;outline:none;min-width:260px;cursor:pointer}}
.home-sel option{{background:#8C0F3B}}
.home-decoration{{position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,rgba(255,255,255,.35),rgba(255,255,255,.08),rgba(255,255,255,.35))}}

/* ── Ajuda ── */
.page-ajuda{{background:var(--wh)}}
.ajuda-inner{{max-width:780px;margin:0 auto;padding:48px 40px}}
.ajuda-header{{background:linear-gradient(135deg,var(--pr),var(--pr-dk));border-radius:var(--radius-lg);padding:28px 32px;color:#fff;margin-bottom:32px}}
.ajuda-header h1{{font-size:1.5rem;font-weight:800;margin-bottom:6px}}
.ajuda-header p{{font-size:.88rem;opacity:.85;line-height:1.5}}
.ajuda-card{{background:var(--g50);border:1px solid var(--g200);border-radius:var(--radius);padding:22px 24px;margin-bottom:16px;border-left:4px solid var(--pr)}}
.ajuda-card h2{{font-size:.95rem;font-weight:700;color:var(--pr-dk);margin-bottom:10px;display:flex;align-items:center;gap:8px}}
.ajuda-card p,.ajuda-card li{{font-size:.85rem;color:var(--g700);line-height:1.65}}
.ajuda-card ul{{padding-left:18px;margin-top:6px}}
.ajuda-card li{{margin-bottom:6px}}
.ajuda-back{{display:inline-flex;align-items:center;gap:8px;background:var(--pr);color:#fff;padding:10px 20px;border-radius:10px;font-size:.85rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;margin-bottom:28px;transition:background .2s}}
.ajuda-back:hover{{background:var(--pr-dk)}}

/* ── Dashboard ── */
.page-dash{{background:var(--g50)}}
.dash-header{{background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 100%);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;position:sticky;top:0;z-index:50;box-shadow:0 4px 20px rgba(0,0,0,.15)}}
.dash-header h1{{font-size:1.05rem;font-weight:800;color:#fff}}
.dash-header p{{font-size:.75rem;color:rgba(255,255,255,.75);margin-top:2px}}
.dash-body{{padding:24px 32px 40px}}
.sec{{margin-bottom:28px}}
.sec-title{{font-size:.92rem;font-weight:700;color:var(--pr-dk);margin-bottom:14px;display:flex;align-items:center;gap:8px}}
.sec-title::before{{content:'';display:inline-block;width:4px;height:16px;background:linear-gradient(180deg,var(--pr),var(--pr-dk));border-radius:2px;flex-shrink:0}}
.kpi-grid{{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}}
.kpi-card{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:4px;box-shadow:var(--sh);opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease,box-shadow .25s}}
.kpi-card.revealed{{opacity:1;transform:translateY(0)}}
.kpi-card:hover{{box-shadow:var(--sh2)}}
.kpi-card.revealed:hover{{transform:translateY(-2px)}}
.kpi-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em}}
.kpi-value{{font-size:2.2rem;font-weight:900;color:var(--pr);line-height:1.05}}
.kpi-sub{{font-size:.72rem;color:var(--g600)}}
.kpi-card.kpi-alert .kpi-value{{color:var(--red)}}
.chart-grid{{display:grid;gap:16px}}
.g2{{grid-template-columns:1fr 1fr}}
.chart-card{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);padding:18px;box-shadow:var(--sh);opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease,box-shadow .25s}}
.chart-card.revealed{{opacity:1;transform:translateY(0)}}
.chart-card:hover{{box-shadow:var(--sh2)}}
.chart-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px}}
.chart-wrap{{position:relative;width:100%}}
.h280{{height:280px}}.h240{{height:240px}}.h210{{height:210px}}
.table-card{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);overflow:hidden;box-shadow:var(--sh);opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease}}
.table-card.revealed{{opacity:1;transform:translateY(0)}}
table{{width:100%;border-collapse:collapse;font-size:.8rem}}
thead th{{background:var(--g50);border-bottom:2px solid var(--g200);padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}}
tbody td{{padding:10px 14px;border-bottom:1px solid var(--g100);color:var(--g700)}}
tbody tr:last-child td{{border-bottom:none}}
tbody tr{{transition:background .15s}}
tbody tr:hover td{{background:var(--g50)}}
.badge-dias{{display:inline-block;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:#FEE2E2;color:var(--red)}}
.empty-msg{{text-align:center;padding:28px;color:var(--g600);font-size:.85rem}}
.parecer-wrap{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);box-shadow:var(--sh);overflow:hidden;opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease}}
.parecer-wrap.revealed{{opacity:1;transform:translateY(0)}}
.md-toolbar{{display:flex;align-items:center;flex-wrap:wrap;gap:4px;padding:10px 14px;border-bottom:1px solid var(--g200);background:var(--g50)}}
.md-btn{{background:var(--wh);border:1px solid var(--g200);color:var(--g700);padding:4px 9px;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:'Consolas','Courier New',monospace;transition:background .15s,color .15s,border-color .15s;line-height:1.4}}
.md-btn:hover{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-sep{{width:1px;height:18px;background:var(--g200);margin:0 4px;flex-shrink:0}}
.md-tabs{{display:flex;gap:0;margin-left:auto}}
.md-tab{{padding:4px 14px;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;border:1px solid var(--g200);background:var(--wh);color:var(--g600);transition:background .15s,color .15s}}
.md-tab.active{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-editor{{display:none;width:100%;min-height:120px;padding:14px 16px;font-family:'Consolas','Courier New',monospace;font-size:.82rem;color:var(--g800);background:var(--wh);border:none;outline:none;resize:vertical;line-height:1.6}}
.md-editor.shown{{display:block}}
.md-preview{{display:none;min-height:80px;padding:16px 18px;font-size:.88rem;color:var(--g700);line-height:1.7}}
.md-preview.shown{{display:block}}
.md-preview h1{{font-size:1.35rem;font-weight:800;color:var(--pr-dk);margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid var(--g200)}}
.md-preview h2{{font-size:1.1rem;font-weight:700;color:var(--pr-dk);margin:16px 0 8px}}
.md-preview h3{{font-size:.95rem;font-weight:700;color:var(--g800);margin:12px 0 6px}}
.md-preview strong{{font-weight:700;color:var(--g900)}}
.md-preview em{{font-style:italic;color:var(--g700)}}
.md-preview ul,.md-preview ol{{padding-left:22px;margin:8px 0}}
.md-preview li{{margin-bottom:5px}}
.md-preview blockquote{{border-left:3px solid var(--pr);padding:8px 14px;background:var(--g50);border-radius:0 6px 6px 0;margin:10px 0;color:var(--g700)}}
.md-preview code{{background:var(--g100);padding:1px 6px;border-radius:4px;font-family:'Consolas',monospace;font-size:.82em}}
.md-preview hr{{border:none;border-top:2px solid var(--g200);margin:14px 0}}
.md-preview p{{margin-bottom:8px}}
.md-hint{{padding:10px 16px;font-size:.73rem;color:var(--g300);font-style:italic;border-top:1px solid var(--g100)}}

@media(max-width:1024px){{.kpi-grid{{grid-template-columns:repeat(2,1fr)}}.g2{{grid-template-columns:1fr}}}}
@media(max-width:768px){{
  .sidebar{{transform:translateX(-100%)}}
  .sidebar.open{{transform:translateX(0)}}
  .main-wrap{{margin-left:0!important}}
  .kpi-grid{{grid-template-columns:1fr}}
  .dash-body{{padding:16px}}
  .ajuda-inner{{padding:24px 16px}}
}}
{_SS}
</head>
<body>

<aside class="sidebar" id="sidebar">
  <!-- Botão toggle sempre visível -->
  <div class="sb-toggle">
    <button class="sb-toggle-btn" onclick="toggleSidebar()" title="Expandir/Recolher menu">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
  </div>

  <div class="sidebar-logo">
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="36" viewBox="0 0 750 749.999995" height="36" preserveAspectRatio="xMidYMid meet" version="1.0">
      <defs>
        <filter x="0%" y="0%" width="100%" height="100%" id="sbf1"><feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 1 0" color-interpolation-filters="sRGB"/></filter>
        <clipPath id="sbc1"><path d="M 0 96.292969 L 750 96.292969 L 750 653.542969 L 0 653.542969 Z" clip-rule="nonzero"/></clipPath>
      </defs>
      <g clip-path="url(#sbc1)" filter="url(#sbf1)">
        <path fill="#e30045" d="M2,17.085c0.464-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2,17.574C2,17.411,2,17.248,2,17.085z" transform="scale(15.8) translate(0,-2.5)"/>
        <path fill="#e30045" d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z" transform="scale(15.8) translate(0,-2.5)"/>
        <path fill="#e30045" d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z" transform="scale(15.8) translate(0,-2.5)"/>
      </g>
    </svg>
    <span>Qualidade<br>de Dados</span>
  </div>

  <button class="nav-item active" id="navHome" onclick="goPage('home')">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
    <span class="nav-item-label">Início</span>
  </button>

  <button class="nav-item" id="navDash" onclick="goPage('dash')" style="display:none">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    <span class="nav-item-label">Boletim</span>
  </button>

  <button class="nav-item" id="navAjuda" onclick="goPage('ajuda')">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span class="nav-item-label">Como Usar</span>
  </button>

  <div class="nav-divider"></div>
  <div class="nav-label">Gerente Sr.</div>

  <div class="sidebar-sel-wrap">
    <label for="sidebarSel">Selecionar</label>
    <select id="sidebarSel" class="sidebar-sel" onchange="trocaGerente(this.value)">
      <option value="">— Escolha —</option>
    </select>
  </div>

  <div class="theme-row" onclick="toggleTheme()">
    <span class="theme-row-label">Tema escuro</span>
    <div class="theme-track"><div class="theme-thumb"></div></div>
  </div>
</aside>

<div class="main-wrap" id="mainWrap">

  <!-- HOME -->
  <div class="page page-home active" id="pageHome">
    <div class="home-bg-circle"></div>
    <div class="home-bg-circle2"></div>
    <div class="home-content">
      <div class="home-kicker">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Período: {PERIODO_TXT}
      </div>
      <div style="display:flex;align-items:center;gap:15px">
        <h1 class="home-h1">Boletim<br>de Resultados</h1>
      </div>
      <div style="position:absolute;left:350px;top:-180px;z-index:-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="1100" height="800" viewBox="1 1 48 42" fill="none">
          <defs>
            <linearGradient id="homeGrad2" x1="1" y1="1" x2="49" y2="43" gradientUnits="userSpaceOnUse">
              <stop offset="0%" stop-color="#e61f79"/>
              <stop offset="50%" stop-color="#961250"/>
              <stop offset="100%" stop-color="#5a0824"/>
            </linearGradient>
          </defs>
          <g fill="url(#homeGrad2)">
            <path d="M2,17.085c0.464-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2,17.574C2,17.411,2,17.248,2,17.085z"/>
            <path d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g>
        </svg>
      </div>
      <p class="home-sub">Visão consolidada dos indicadores de qualidade para os Gerentes Seniores. Selecione abaixo para acessar o painel detalhado.</p>
      <div class="home-actions">
        <button class="btn-ghost" onclick="goPage('ajuda')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:6px"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Como Usar
        </button>
      </div>
      <div class="home-sel-wrap">
        <label for="homeSel">Gerente Sênior:</label>
        <select id="homeSel" class="home-sel" onchange="trocaGerente(this.value)">
          <option value="">— Selecione um gerente —</option>
        </select>
      </div>
    </div>
    <div class="home-decoration"></div>
  </div>

  <!-- AJUDA -->
  <div class="page page-ajuda" id="pageAjuda">
    <div class="ajuda-inner">
      <button class="ajuda-back" onclick="goPage('home')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Voltar
      </button>
      <div class="ajuda-header">
        <h1>Como usar este Boletim</h1>
        <p>Guia rápido para navegação e interpretação dos dados.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>Início</h2>
        <p>Na tela inicial selecione o <strong>Gerente Sênior</strong> no dropdown — o painel abre imediatamente. O menu lateral permite trocar de gerente a qualquer momento. Use o botão <strong>&#8249;</strong> no topo da sidebar para recolhê-la e ampliar a área de visualização.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Painel de Gráficos</h2>
        <ul>
          <li><strong>KPIs superiores:</strong> Produtos monitorados, total de chamados e vencidos (igual ao total da tabela).</li>
          <li><strong>P-Score Ponderado:</strong> 10 piores produtos. Vermelho &lt;70, âmbar &lt;85, verde ≥85.</li>
          <li><strong>Status e Tendência:</strong> Distribuição por situação e evolução mensal dos últimos 5 meses.</li>
          <li><strong>Causas e Dimensões:</strong> Causas raízes e distribuição por dimensão.</li>
          <li><strong>Tabela de Vencidos:</strong> Chamados com mais de 5 dias sem encerramento.</li>
        </ul>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Parecer Analítico</h2>
        <p>Suporta <strong>Markdown</strong>: <code># Título</code>, <code>## Subtítulo</code>, <code>**negrito**</code>, <code>*itálico*</code>, <code>- lista</code>, <code>&gt; citação</code>, <code>---</code>. Use os botões ou escreva diretamente. Clique em <strong>Visualizar</strong> para ver o resultado renderizado.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>Período</h2>
        <p>Cobre os <strong>dois meses completos</strong> anteriores à data de execução do notebook. A tendência abrange os <strong>5 meses</strong> anteriores.</p>
      </div>
    </div>
  </div>

  <!-- DASHBOARD -->
  <div class="page page-dash" id="pageDash">
    <div class="dash-header">
      <div>
        <h1 id="dashTitulo">Boletim — Gerente</h1>
        <p id="dashPeriodo">{PERIODO_TXT}</p>
      </div>
    </div>
    <div class="dash-body">

      <div class="sec">
        <div class="sec-title">Carteira — Indicadores do Período</div>
        <div class="kpi-grid">
          <div class="kpi-card" id="kc1"><span class="kpi-label">Produtos de Dados</span><span class="kpi-value" id="kProd">—</span><span class="kpi-sub">Monitorados ativamente</span></div>
          <div class="kpi-card" id="kc2"><span class="kpi-label">Chamados no Período</span><span class="kpi-value" id="kTotal">—</span><span class="kpi-sub">Período do boletim</span></div>
          <div class="kpi-card kpi-alert" id="kc3"><span class="kpi-label">Vencidos (&gt; 5 dias)</span><span class="kpi-value" id="kVencidos">—</span><span class="kpi-sub">Sem encerramento — igual ao total da tabela</span></div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">P-Score Ponderado por Produto (piores)</div>
        <div class="chart-card" id="ccScore">
          <div class="chart-label">&#x3A3;(avg_dim &times; dias_dim) / &#x3A3;dias_dim — exclui VARIACAO &nbsp;|&nbsp; Vermelho &lt;70 &nbsp; Âmbar &lt;85 &nbsp; Verde &#x2265;85</div>
          <div class="chart-wrap h280"><canvas id="cScore"></canvas></div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Status e Tendência de Chamados</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccStatus"><div class="chart-label">Distribuição por Status</div><div class="chart-wrap h240"><canvas id="cStatus"></canvas></div></div>
          <div class="chart-card" id="ccTrend"><div class="chart-label">Tendência Mensal — Abertos vs Concluídos (últimos 5 meses)</div><div class="chart-wrap h240"><canvas id="cTrend"></canvas></div></div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Causas e Distribuição por Dimensão</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccCausas"><div class="chart-label">Causas Raízes Operacionais (chamados ativos)</div><div class="chart-wrap h210"><canvas id="cCausas"></canvas></div></div>
          <div class="chart-card" id="ccDim"><div class="chart-label">Chamados por Dimensão de Qualidade</div><div class="chart-wrap h210"><canvas id="cDimensoes"></canvas></div></div>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Chamados Vencidos (&gt; 5 dias sem encerramento)</div>
        <div class="table-card" id="tcVenc">
          <table><thead><tr><th>ID</th><th>Produto</th><th>Dimensão</th><th>Aberto em</th><th>Dias em aberto</th></tr></thead><tbody id="tbVencidos"></tbody></table>
        </div>
      </div>

      <div class="sec">
        <div class="sec-title">Parecer Analítico</div>
        <div class="parecer-wrap" id="parecerWrap">
          <div class="md-toolbar">
            <button class="md-btn" title="Título (H1)" onclick="mdInsert('# ','')">H1</button>
            <button class="md-btn" title="Subtítulo (H2)" onclick="mdInsert('## ','')">H2</button>
            <button class="md-btn" title="Sub-subtítulo (H3)" onclick="mdInsert('### ','')">H3</button>
            <div class="md-sep"></div>
            <button class="md-btn" title="Negrito" onclick="mdInsert('**','**')"><strong>N</strong></button>
            <button class="md-btn" title="Itálico" onclick="mdInsert('*','*')"><em>I</em></button>
            <div class="md-sep"></div>
            <button class="md-btn" title="Lista" onclick="mdInsert('\\n- ','')">&#8226; Lista</button>
            <button class="md-btn" title="Lista numerada" onclick="mdInsert('\\n1. ','')">1. Lista</button>
            <button class="md-btn" title="Citação" onclick="mdInsert('\\n> ','')">&#10077;</button>
            <button class="md-btn" title="Divisória" onclick="mdInsert('\\n---\\n','')">&#8212;</button>
            <div class="md-sep"></div>
            <div class="md-tabs">
              <button class="md-tab active" id="tabEditar" onclick="mdTab('editar')">Editar</button>
              <button class="md-tab" id="tabVer" onclick="mdTab('ver')">Visualizar</button>
            </div>
          </div>
          <textarea class="md-editor shown" id="mdEditor" placeholder="Digite o parecer aqui... Suporta Markdown: # Título, **negrito**, *itálico*, - lista, > citação"></textarea>
          <div class="md-preview" id="mdPreview"></div>
          <div class="md-hint">Dica: use a barra de ferramentas ou escreva Markdown diretamente. Clique em Visualizar para ver o resultado formatado.</div>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
var D = {_json_dados};
var _charts = {{}};
var _gerente = '';
var _sbCollapsed = false;

// ── Tema ──
function toggleTheme() {{
  document.documentElement.dataset.theme =
    (document.documentElement.dataset.theme === 'dark') ? '' : 'dark';
}}

// ── Toggle Sidebar ──
function toggleSidebar() {{
  _sbCollapsed = !_sbCollapsed;
  var sb = document.getElementById('sidebar');
  var mw = document.getElementById('mainWrap');
  if (_sbCollapsed) {{
    sb.classList.add('collapsed');
    mw.classList.add('collapsed');
  }} else {{
    sb.classList.remove('collapsed');
    mw.classList.remove('collapsed');
  }}
  // Aguarda a transição e força Chart.js a recalcular os tamanhos
  setTimeout(function() {{
    Object.values(_charts).forEach(function(c) {{
      try {{ c.resize(); }} catch(e) {{}}
    }});
  }}, 380);
}}

// ── Navegação ──
function goPage(id) {{
  ['pageHome','pageDash','pageAjuda'].forEach(function(p) {{
    document.getElementById(p).classList.remove('active');
  }});
  ['navHome','navDash','navAjuda'].forEach(function(n) {{
    var el = document.getElementById(n);
    if (el) el.classList.remove('active');
  }});
  document.getElementById('page' + id.charAt(0).toUpperCase() + id.slice(1)).classList.add('active');
  var nav = document.getElementById('nav' + id.charAt(0).toUpperCase() + id.slice(1));
  if (nav) nav.classList.add('active');
  if (id === 'dash') {{
    document.getElementById('navDash').style.display = '';
    setTimeout(animateReveal, 80);
  }}
}}

// ── Gerente ──
function trocaGerente(g) {{
  if (!g) return;
  _gerente = g;
  document.getElementById('homeSel').value    = g;
  document.getElementById('sidebarSel').value = g;
  renderDash(g);
  goPage('dash');
}}

// ── Dashboard ──
function renderDash(g) {{
  var d = D[g];
  if (!d) return;
  document.getElementById('dashTitulo').textContent = 'Boletim — ' + g;
  document.getElementById('kProd').textContent      = d.kpis.produtos;
  document.getElementById('kTotal').textContent     = d.kpis.total_chamados;
  document.getElementById('kVencidos').textContent  = d.kpis.vencidos;
  _renderTabela(d.vencidos);
  _destroyCharts();
  _renderPScore(d.pscore);
  _renderStatus(d.statusChamados);
  _renderTendencia(d.tendencia);
  _renderCausas(d.causasRaizes);
  _renderDimensoes(d.dimensoes);
  _resetReveal();
}}

function _resetReveal() {{
  document.querySelectorAll('#pageDash .kpi-card,#pageDash .chart-card,#pageDash .table-card,#pageDash .parecer-wrap').forEach(function(el) {{
    el.classList.remove('revealed');
  }});
}}

function animateReveal() {{
  Array.from(document.querySelectorAll(
    '#pageDash .kpi-card,#pageDash .chart-card,#pageDash .table-card,#pageDash .parecer-wrap'
  )).forEach(function(el, i) {{
    setTimeout(function() {{ el.classList.add('revealed'); }}, i * 60);
  }});
}}

// ── Tabela ──
function _renderTabela(vencidos) {{
  var tbody = document.getElementById('tbVencidos');
  if (!vencidos || !vencidos.length) {{
    tbody.innerHTML = '<tr><td colspan="5" class="empty-msg">Nenhum chamado vencido no período</td></tr>';
    return;
  }}
  tbody.innerHTML = vencidos.map(function(v) {{
    return '<tr><td>'+v.id+'</td><td>'+v.prod+'</td><td>'+v.tipo+'</td><td>'+v.data+'</td><td><span class="badge-dias">'+v.dias+' dias</span></td></tr>';
  }}).join('');
}}

// ── Gráficos ──
function _destroyCharts() {{
  Object.values(_charts).forEach(function(c) {{ try {{ c.destroy(); }} catch(e) {{}} }});
  _charts = {{}};
}}

function _mkChart(id, cfg) {{
  var el = document.getElementById(id);
  if (!el) return;
  _charts[id] = new Chart(el, cfg);
}}

var _palette = ['#C01160','#8C0F3B','#DF1A73','#D97706','#059669','#2563EB','#7C3AED'];

function _renderPScore(ps) {{
  if (!ps || !ps.labels.length) return;
  _mkChart('cScore',{{type:'bar',data:{{labels:ps.labels,datasets:[{{label:'P-Score',data:ps.data,borderRadius:5,borderWidth:0,backgroundColor:ps.data.map(function(v){{return v<70?'rgba(220,38,38,.78)':v<85?'rgba(217,119,6,.78)':'rgba(5,150,105,.78)';}})}}]}},options:{{indexAxis:'y',responsive:true,maintainAspectRatio:false,scales:{{x:{{min:0,max:100,grid:{{color:'rgba(0,0,0,.04)'}}}},y:{{ticks:{{font:{{size:11}}}}}}}},plugins:{{legend:{{display:false}}}}}}}});
}}

function _renderStatus(sc) {{
  _mkChart('cStatus',{{type:'doughnut',data:{{labels:['Encerrados/Concluídos','Em Andamento','Em Análise'],datasets:[{{data:sc,backgroundColor:['#059669','#D97706','#C01160'],borderWidth:3,borderColor:'#fff',hoverOffset:6}}]}},options:{{responsive:true,maintainAspectRatio:false,plugins:{{legend:{{position:'right',labels:{{font:{{size:11}},boxWidth:13,padding:14}}}}}}}}}});
}}

function _renderTendencia(t) {{
  if (!t || !t.meses.length) return;
  _mkChart('cTrend',{{type:'bar',data:{{labels:t.meses,datasets:[{{label:'Abertos',data:t.abertos,backgroundColor:'#C01160',borderRadius:4,borderWidth:0}},{{label:'Concluídos',data:t.concluidos,backgroundColor:'#059669',borderRadius:4,borderWidth:0}}]}},options:{{responsive:true,maintainAspectRatio:false,scales:{{y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}},plugins:{{legend:{{position:'top',labels:{{font:{{size:11}},boxWidth:12,padding:12}}}}}}}}}});
}}

function _renderCausas(c) {{
  if (!c || !c.labels.length) return;
  _mkChart('cCausas',{{type:'bar',data:{{labels:c.labels,datasets:[{{label:'Chamados',data:c.data,backgroundColor:'#8C0F3B',borderRadius:4,borderWidth:0}}]}},options:{{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{{legend:{{display:false}}}},scales:{{x:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}}}}}}});
}}

function _renderDimensoes(dim) {{
  if (!dim || !dim.labels.length) return;
  _mkChart('cDimensoes',{{type:'bar',data:{{labels:dim.labels,datasets:[{{label:'Chamados',data:dim.data,borderRadius:4,borderWidth:0,backgroundColor:dim.labels.map(function(_,i){{return _palette[i%_palette.length];}})}}]}},options:{{responsive:true,maintainAspectRatio:false,plugins:{{legend:{{display:false}}}},scales:{{y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}}}}}}}}}}});
}}

// ── Editor MD ──
function mdTab(tab) {{
  var ed=document.getElementById('mdEditor'),pr=document.getElementById('mdPreview');
  var tE=document.getElementById('tabEditar'),tV=document.getElementById('tabVer');
  if(tab==='ver'){{pr.innerHTML=mdRender(ed.value);ed.classList.remove('shown');pr.classList.add('shown');tE.classList.remove('active');tV.classList.add('active');}}
  else{{pr.classList.remove('shown');ed.classList.add('shown');tV.classList.remove('active');tE.classList.add('active');}}
}}

function mdInsert(before,after){{
  var ta=document.getElementById('mdEditor'),s=ta.selectionStart,e=ta.selectionEnd;
  var sel=ta.value.substring(s,e),ins=before.replace(/\\\\n/g,'\\n')+sel+after;
  ta.value=ta.value.substring(0,s)+ins+ta.value.substring(e);
  ta.focus();ta.selectionStart=s+before.replace(/\\\\n/g,'\\n').length;ta.selectionEnd=ta.selectionStart+sel.length;
}}

function mdRender(md){{
  if(!md) return '<span style="color:var(--g300);font-style:italic">Nenhum conteúdo para visualizar.</span>';
  return md.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/^### (.+)$/gm,'<h3>$1</h3>').replace(/^## (.+)$/gm,'<h2>$1</h2>').replace(/^# (.+)$/gm,'<h1>$1</h1>')
    .replace(/^---$/gm,'<hr>').replace(/^&gt; (.+)$/gm,'<blockquote>$1</blockquote>')
    .replace(/^- (.+)$/gm,'<li>$1</li>').replace(/^\\d+\\. (.+)$/gm,'<li>$1</li>')
    .replace(/(<li>.*<\\/li>\\n?)+/g,function(m){{return '<ul>'+m+'</ul>';}})
    .replace(/\\*\\*(.+?)\\*\\*/g,'<strong>$1</strong>').replace(/\\*(.+?)\\*/g,'<em>$1</em>')
    .replace(/`(.+?)`/g,'<code>$1</code>')
    .replace(/^(?!<[hH1-6ulbci]|<hr|<blockquote)(.+)$/gm,'<p>$1</p>').replace(/\\n{{2,}}/g,'');
}}

// ── Init ──
(function(){{
  var gerentes=Object.keys(D).sort();
  ['homeSel','sidebarSel'].forEach(function(id){{
    var sel=document.getElementById(id);if(!sel)return;
    while(sel.options.length>1)sel.remove(1);
    gerentes.forEach(function(g){{var o=document.createElement('option');o.value=g;o.text=g;sel.add(o);}});
  }});
}})();
{_SC}
</body>
</html>"""

# COMMAND ----------
# =============================================================================
# CÉLULA 13 — OUTPUT COPIÁVEL NO DATABRICKS
# =============================================================================

_html_esc = _HTML_SB.replace("&","&amp;").replace("<","&lt;").replace(">","&gt;")
_SC2 = "<" + "/script>"
_SS2 = "<" + "/style>"
_ST2 = "<" + "/textarea>"

_output_ui = f"""
<style>
#_blt_wrap{{font-family:'Segoe UI',Arial,sans-serif;max-width:980px;margin:0 auto;padding:24px}}
#_blt_wrap h2{{font-size:1rem;font-weight:700;color:#8C0F3B;margin-bottom:6px}}
#_blt_wrap p{{font-size:.82rem;color:#374151;margin-bottom:12px;line-height:1.5}}
#_blt_code{{width:100%;height:340px;font-family:'Consolas','Courier New',monospace;font-size:.74rem;border:1px solid #D1D5DB;border-radius:8px;padding:12px;background:#F9FAFB;color:#1F2937;resize:vertical;white-space:pre;overflow:auto}}
#_blt_copy{{margin-top:10px;background:#C01160;color:#fff;border:none;padding:9px 22px;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;font-family:inherit}}
#_blt_copy:hover{{background:#8C0F3B}}
#_blt_ok{{display:inline-block;margin-left:12px;font-size:.8rem;color:#059669;font-weight:600;opacity:0;transition:opacity .4s}}
{_SS2}
<div id="_blt_wrap">
  <h2>HTML do Boletim gerado com sucesso (sidebar colapsável)</h2>
  <p>Selecione tudo (Ctrl+A), copie (Ctrl+C) e cole em um arquivo <code>.html</code> externo.</p>
  <textarea id="_blt_code" readonly spellcheck="false">{_html_esc}{_ST2}
  <br>
  <button id="_blt_copy" onclick="_copyBlt()">Copiar HTML</button>
  <span id="_blt_ok">Copiado com sucesso!</span>
</div>
<script>
function _copyBlt(){{var ta=document.getElementById('_blt_code');ta.select();document.execCommand('copy');var ok=document.getElementById('_blt_ok');ok.style.opacity='1';setTimeout(function(){{ok.style.opacity='0';}},2600);}}
{_SC2}
"""

displayHTML(_output_ui)
