<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DEVA — Central de Dúvidas | Bradesco</title>

<!-- ============================================================
     DEVA FAQ — Central de Dúvidas | Bradesco BRAI4DQ
     Versão: 1.0 | Maio 2026
     
     COMO PERSONALIZAR:
     - Conteúdo (perguntas/respostas): edite o array FAQ_DATA abaixo
     - Logo: substitua o elemento #logo-placeholder pelo seu <img>
     - Cores Bradesco: edite as variáveis em :root (seção BRAND)
     - Adicionar nova seção: crie um novo objeto em FAQ_DATA com
       { id, label, icon, items: [{q, a, tags}] }
     ============================================================ -->

<style>
/* ── RESET ─────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:15px}
body{font-family:'Segoe UI',Arial,sans-serif;line-height:1.7;transition:background .25s,color .25s}

/* ── BRAND & TOKENS ────────────────────────────────── */
:root {
  --brand-red:        #CC092F;
  --brand-red-dark:   #A00722;
  --brand-red-light:  #F5E6E9;
  --brand-red-mid:    #E8B0BB;

  /* Light mode */
  --bg-page:          #F4F4F4;
  --bg-surface:       #FFFFFF;
  --bg-surface-alt:   #F8F8F8;
  --bg-sidebar:       #FFFFFF;
  --bg-header:        #FFFFFF;
  --text-primary:     #1A1A1A;
  --text-secondary:   #555555;
  --text-muted:       #888888;
  --border:           #E0E0E0;
  --border-strong:    #C8C8C8;
  --shadow:           rgba(0,0,0,.07);
  --shadow-md:        rgba(0,0,0,.12);
  --warn-bg:          #FFF8E1;
  --warn-border:      #F59E0B;
  --warn-text:        #92400E;
  --error-bg:         #FEF2F2;
  --error-border:     #CC092F;
  --error-text:       #7F1D1D;
  --success-bg:       #F0FDF4;
  --success-border:   #22C55E;
  --success-text:     #14532D;
  --info-bg:          #EFF6FF;
  --info-border:      #3B82F6;
  --info-text:        #1E3A5F;
  --tag-bg:           #EAEAEA;
  --tag-text:         #444444;
  --highlight:        #FFF3E0;
  --mark-bg:          #FFEB3B;
  --mark-text:        #000;
  --code-bg:          #F3F3F3;
  --tree-hover:       #F9ECEE;
  --tree-active:      #F5E6E9;
  --tree-active-text: #CC092F;
  --overlay:          rgba(0,0,0,.35);
  --scrollbar:        #DDDDDD;
  --scrollbar-thumb:  #BBBBBB;
}

[data-theme="dark"] {
  --bg-page:          #111111;
  --bg-surface:       #1C1C1C;
  --bg-surface-alt:   #242424;
  --bg-sidebar:       #161616;
  --bg-header:        #1A1A1A;
  --text-primary:     #F0F0F0;
  --text-secondary:   #B0B0B0;
  --text-muted:       #777777;
  --border:           #2E2E2E;
  --border-strong:    #3E3E3E;
  --shadow:           rgba(0,0,0,.35);
  --shadow-md:        rgba(0,0,0,.5);
  --warn-bg:          #2D2500;
  --warn-border:      #D97706;
  --warn-text:        #FDE68A;
  --error-bg:         #2D0A0A;
  --error-border:     #CC092F;
  --error-text:       #FCA5A5;
  --success-bg:       #052E16;
  --success-border:   #16A34A;
  --success-text:     #86EFAC;
  --info-bg:          #0C1A2E;
  --info-border:      #2563EB;
  --info-text:        #93C5FD;
  --tag-bg:           #2A2A2A;
  --tag-text:         #CCCCCC;
  --highlight:        #2A2000;
  --mark-bg:          #B45309;
  --mark-text:        #FFF;
  --code-bg:          #252525;
  --tree-hover:       #2A1218;
  --tree-active:      #31101A;
  --tree-active-text: #F08097;
  --overlay:          rgba(0,0,0,.6);
  --scrollbar:        #2A2A2A;
  --scrollbar-thumb:  #444444;
}

/* ── SCROLLBAR ──────────────────────────────────────── */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:var(--scrollbar)}
::-webkit-scrollbar-thumb{background:var(--scrollbar-thumb);border-radius:3px}

/* ── LAYOUT SHELL ───────────────────────────────────── */
body{background:var(--bg-page);color:var(--text-primary)}

#app{display:flex;flex-direction:column;min-height:100vh}

/* ── HEADER ─────────────────────────────────────────── */
#header{
  position:sticky;top:0;z-index:200;
  background:var(--bg-header);
  border-bottom:2px solid var(--brand-red);
  box-shadow:0 2px 8px var(--shadow);
  padding:0 24px;
  height:60px;
  display:flex;align-items:center;gap:16px;
}

#header-logo{display:flex;align-items:center;gap:12px;flex-shrink:0;text-decoration:none}
#logo-placeholder{
  /* SUBSTITUIR: troque este bloco por <img src="logo.svg" alt="Bradesco"> */
  display:flex;align-items:center;justify-content:center;
  width:38px;height:38px;border-radius:4px;
  background:var(--brand-red);color:#fff;
  font-weight:700;font-size:13px;letter-spacing:.5px;
  font-family:Arial,sans-serif;
}
#header-title{
  font-size:15px;font-weight:600;color:var(--text-primary);
  border-left:1px solid var(--border);padding-left:12px;
  white-space:nowrap;
}
#header-subtitle{font-size:12px;color:var(--text-muted);display:block;line-height:1.2}

#header-search-wrap{
  flex:1;max-width:520px;margin:0 auto;
  position:relative;
}
#search-input{
  width:100%;height:36px;
  background:var(--bg-surface-alt);
  border:1px solid var(--border);border-radius:18px;
  padding:0 14px 0 38px;
  color:var(--text-primary);font-size:13.5px;
  outline:none;transition:border .2s,box-shadow .2s;
}
#search-input:focus{border-color:var(--brand-red);box-shadow:0 0 0 3px var(--brand-red-light)}
#search-icon{
  position:absolute;left:12px;top:50%;transform:translateY(-50%);
  color:var(--text-muted);font-size:15px;pointer-events:none;
}
#search-clear{
  position:absolute;right:10px;top:50%;transform:translateY(-50%);
  background:none;border:none;color:var(--text-muted);cursor:pointer;
  font-size:14px;display:none;padding:2px;
  transition:color .15s;
}
#search-clear:hover{color:var(--brand-red)}

#header-actions{display:flex;align-items:center;gap:6px;flex-shrink:0}

.hbtn{
  display:flex;align-items:center;justify-content:center;gap:5px;
  background:none;border:1px solid var(--border);border-radius:6px;
  color:var(--text-secondary);cursor:pointer;font-size:12.5px;
  padding:5px 10px;transition:all .15s;white-space:nowrap;
}
.hbtn:hover{background:var(--bg-surface-alt);border-color:var(--border-strong);color:var(--text-primary)}
.hbtn.active{background:var(--brand-red-light);border-color:var(--brand-red);color:var(--brand-red)}
.hbtn svg,.hbtn i{font-size:14px}

/* ── MAIN AREA ───────────────────────────────────────── */
#main{display:flex;flex:1;overflow:hidden}

/* ── SIDEBAR ─────────────────────────────────────────── */
#sidebar{
  width:280px;flex-shrink:0;
  background:var(--bg-sidebar);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;
  overflow:hidden;
  transition:width .3s cubic-bezier(.4,0,.2,1),opacity .3s;
}
#sidebar.collapsed{width:0;opacity:0;pointer-events:none}

#sidebar-header{
  padding:14px 16px 10px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
}
#sidebar-header span{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted)}

#sidebar-scroll{flex:1;overflow-y:auto;padding:8px 0}

/* Tree */
.tree-section{margin-bottom:2px}
.tree-section-header{
  display:flex;align-items:center;gap:8px;
  padding:9px 14px;cursor:pointer;
  font-size:13px;font-weight:600;color:var(--text-secondary);
  transition:background .15s;border-radius:0;
  user-select:none;
}
.tree-section-header:hover{background:var(--tree-hover);color:var(--text-primary)}
.tree-section-header.open{color:var(--brand-red)}
.tree-arrow{
  width:14px;height:14px;flex-shrink:0;margin-left:auto;
  transition:transform .2s;color:var(--text-muted);font-size:12px;
}
.tree-section-header.open .tree-arrow{transform:rotate(90deg)}
.tree-icon{font-size:15px;flex-shrink:0;color:var(--brand-red)}

.tree-items{
  overflow:hidden;
  max-height:0;
  transition:max-height .3s cubic-bezier(.4,0,.2,1);
}
.tree-items.open{max-height:600px}

.tree-item{
  display:block;padding:7px 14px 7px 36px;
  font-size:12.5px;color:var(--text-secondary);
  cursor:pointer;text-decoration:none;
  transition:background .12s,color .12s;
  border-left:2px solid transparent;
  line-height:1.4;
}
.tree-item:hover{background:var(--tree-hover);color:var(--text-primary)}
.tree-item.active{
  background:var(--tree-active);color:var(--tree-active-text);
  border-left-color:var(--brand-red);font-weight:600;
}

/* ── CONTENT ─────────────────────────────────────────── */
#content{
  flex:1;overflow-y:auto;
  padding:32px 40px;
  max-width:900px;
}
#content-inner{max-width:820px;margin:0 auto}

/* ── SECTION HEADERS ────────────────────────────────── */
.section-block{margin-bottom:48px;scroll-margin-top:80px}
.section-title{
  font-size:20px;font-weight:700;
  color:var(--text-primary);
  border-bottom:2px solid var(--brand-red);
  padding-bottom:8px;margin-bottom:20px;
  display:flex;align-items:center;gap:10px;
}
.section-icon{font-size:20px;color:var(--brand-red)}
.section-badge{
  margin-left:auto;font-size:11px;font-weight:600;
  background:var(--brand-red-light);color:var(--brand-red);
  padding:2px 8px;border-radius:10px;
}
[data-theme="dark"] .section-badge{background:var(--tree-active);color:var(--tree-active-text)}

/* ── FAQ CARDS ──────────────────────────────────────── */
.faq-card{
  background:var(--bg-surface);
  border:1px solid var(--border);
  border-radius:10px;
  margin-bottom:10px;
  overflow:hidden;
  transition:border-color .15s,box-shadow .15s;
}
.faq-card:hover{border-color:var(--border-strong);box-shadow:0 2px 8px var(--shadow)}
.faq-card.open{border-color:var(--brand-red-mid);box-shadow:0 2px 12px var(--shadow-md)}
[data-theme="dark"] .faq-card.open{border-color:var(--brand-red)}

.faq-question{
  display:flex;align-items:flex-start;gap:12px;
  padding:16px 18px;cursor:pointer;
  font-size:14px;font-weight:600;color:var(--text-primary);
  line-height:1.5;user-select:none;
  transition:background .12s;
}
.faq-question:hover{background:var(--bg-surface-alt)}
.faq-card.open .faq-question{background:var(--brand-red-light);color:var(--brand-red)}
[data-theme="dark"] .faq-card.open .faq-question{background:var(--tree-active)}
.faq-q-icon{flex-shrink:0;width:22px;height:22px;border-radius:50%;background:var(--brand-red-light);color:var(--brand-red);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;margin-top:1px}
[data-theme="dark"] .faq-q-icon{background:var(--tree-active);color:var(--tree-active-text)}
.faq-card.open .faq-q-icon{background:var(--brand-red);color:#fff}
.faq-chevron{margin-left:auto;flex-shrink:0;font-size:14px;color:var(--text-muted);transition:transform .25s}
.faq-card.open .faq-chevron{transform:rotate(180deg);color:var(--brand-red)}

.faq-answer{
  max-height:0;overflow:hidden;
  transition:max-height .35s cubic-bezier(.4,0,.2,1),padding .25s;
  padding:0 18px;
}
.faq-card.open .faq-answer{max-height:1200px;padding:0 18px 18px}

.faq-answer-inner{
  padding-top:14px;border-top:1px solid var(--border);
  font-size:13.5px;color:var(--text-secondary);line-height:1.75;
}
.faq-card.open .faq-answer-inner{border-top-color:var(--brand-red-mid)}

.faq-answer-inner p{margin-bottom:10px}
.faq-answer-inner p:last-child{margin-bottom:0}
.faq-answer-inner strong{color:var(--text-primary);font-weight:600}
.faq-answer-inner ul,.faq-answer-inner ol{padding-left:20px;margin:8px 0}
.faq-answer-inner li{margin-bottom:4px}
.faq-answer-inner code{
  background:var(--code-bg);border:1px solid var(--border);
  border-radius:4px;padding:1px 6px;font-size:12.5px;
  font-family:'Courier New',monospace;color:var(--brand-red);
}
.faq-answer-inner .note{
  background:var(--info-bg);border-left:3px solid var(--info-border);
  border-radius:0 6px 6px 0;padding:10px 14px;margin:10px 0;
  font-size:13px;color:var(--info-text);
}
.faq-answer-inner .warn{
  background:var(--warn-bg);border-left:3px solid var(--warn-border);
  border-radius:0 6px 6px 0;padding:10px 14px;margin:10px 0;
  font-size:13px;color:var(--warn-text);
}
.faq-answer-inner .tip{
  background:var(--success-bg);border-left:3px solid var(--success-border);
  border-radius:0 6px 6px 0;padding:10px 14px;margin:10px 0;
  font-size:13px;color:var(--success-text);
}

/* Tags */
.faq-tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:12px}
.faq-tag{
  font-size:11px;background:var(--tag-bg);color:var(--tag-text);
  border-radius:10px;padding:2px 8px;font-weight:500;
}

/* Highlighted search result */
mark{background:var(--mark-bg);color:var(--mark-text);border-radius:2px;padding:0 2px}

/* ── CALLOUT BOXES ───────────────────────────────────── */
.callout{
  border-radius:8px;padding:14px 16px;margin:14px 0;
  font-size:13.5px;line-height:1.65;
}
.callout-error{background:var(--error-bg);border:1px solid var(--error-border);color:var(--error-text)}
.callout-warn{background:var(--warn-bg);border:1px solid var(--warn-border);color:var(--warn-text)}
.callout-success{background:var(--success-bg);border:1px solid var(--success-border);color:var(--success-text)}
.callout-info{background:var(--info-bg);border:1px solid var(--info-border);color:var(--info-text)}
.callout-title{font-weight:700;display:block;margin-bottom:6px;font-size:13px;text-transform:uppercase;letter-spacing:.4px}

/* ── TABLE ───────────────────────────────────────────── */
.ref-table{width:100%;border-collapse:collapse;font-size:13px;margin:10px 0}
.ref-table th{background:var(--brand-red);color:#fff;font-weight:600;padding:8px 12px;text-align:left}
.ref-table td{padding:7px 12px;border-bottom:1px solid var(--border);vertical-align:top}
.ref-table tr:last-child td{border-bottom:none}
.ref-table tr:nth-child(even) td{background:var(--bg-surface-alt)}
.ref-table td:first-child{font-weight:600;color:var(--text-primary);white-space:nowrap}

/* Status badges in tables */
.badge-ok{background:var(--success-bg);color:var(--success-text);padding:2px 8px;border-radius:10px;font-weight:600;font-size:11.5px;white-space:nowrap}
.badge-warn{background:var(--warn-bg);color:var(--warn-text);padding:2px 8px;border-radius:10px;font-weight:600;font-size:11.5px;white-space:nowrap}
.badge-err{background:var(--error-bg);color:var(--error-text);padding:2px 8px;border-radius:10px;font-weight:600;font-size:11.5px;white-space:nowrap}
.badge-dev{background:var(--info-bg);color:var(--info-text);padding:2px 8px;border-radius:10px;font-weight:600;font-size:11.5px;white-space:nowrap}

/* ── SEARCH RESULTS ─────────────────────────────────── */
#search-results-panel{display:none;padding:20px 0}
#no-results{
  text-align:center;padding:48px 24px;
  color:var(--text-muted);font-size:14px;
}

/* ── BACK TO TOP ────────────────────────────────────── */
#back-top{
  position:fixed;bottom:28px;right:28px;z-index:300;
  width:40px;height:40px;border-radius:50%;
  background:var(--brand-red);color:#fff;border:none;
  cursor:pointer;font-size:18px;
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 2px 10px rgba(204,9,47,.35);
  opacity:0;pointer-events:none;
  transition:opacity .25s,transform .2s;transform:translateY(8px);
}
#back-top.visible{opacity:1;pointer-events:auto;transform:translateY(0)}
#back-top:hover{background:var(--brand-red-dark)}

/* ── PROGRESS BAR ────────────────────────────────────── */
#progress-bar{
  position:fixed;top:0;left:0;z-index:300;
  height:2px;background:var(--brand-red);width:0;
  transition:width .1s;
}

/* ── MOBILE ──────────────────────────────────────────── */
#sidebar-overlay{
  display:none;position:fixed;inset:0;
  background:var(--overlay);z-index:150;
}

/* ── SEARCH HIGHLIGHT PULSE ─────────────────────────── */
@keyframes highlight-pulse{
  0%{box-shadow:0 0 0 0 rgba(204,9,47,.4)}
  70%{box-shadow:0 0 0 8px rgba(204,9,47,0)}
  100%{box-shadow:0 0 0 0 rgba(204,9,47,0)}
}
.pulse{animation:highlight-pulse .6s ease}

/* ── PRINT ───────────────────────────────────────────── */
@media print{
  #header,#sidebar,#back-top,#progress-bar,#header-actions{display:none!important}
  .faq-answer{max-height:none!important;padding:0 18px 18px!important}
  .faq-chevron{display:none}
}

/* ── RESPONSIVE ──────────────────────────────────────── */
@media(max-width:768px){
  #sidebar{position:fixed;top:60px;left:0;bottom:0;z-index:160;width:280px;transform:translateX(-100%);transition:transform .3s}
  #sidebar.mobile-open{transform:translateX(0)}
  #sidebar-overlay{display:block}
  #content{padding:20px 16px}
  #header-search-wrap{max-width:none;flex:1}
  .hbtn span{display:none}
}

/* ── FONT-SIZE CONTROL ───────────────────────────────── */
body.font-sm #content{font-size:13px}
body.font-lg #content{font-size:16.5px}
</style>
</head>
<body>

<div id="progress-bar"></div>

<!-- HEADER -->
<header id="header">
  <a href="#" id="header-logo">
    <!-- LOGO PLACEHOLDER: substitua o div abaixo por <img src="caminho/logo.svg" alt="Bradesco" style="height:36px"> -->
    <div id="logo-placeholder" title="Bradesco">B</div>
    <div>
      <span id="header-title">DEVA <span id="header-subtitle">Central de Dúvidas — BRAI4DQ v5.5.4</span></span>
    </div>
  </a>

  <div id="header-search-wrap">
    <i id="search-icon" class="ti ti-search"></i>
    <input id="search-input" type="search" placeholder="Buscar dúvida, funcionalidade ou erro..." autocomplete="off" aria-label="Buscar">
    <button id="search-clear" aria-label="Limpar busca"><i class="ti ti-x"></i></button>
  </div>

  <div id="header-actions">
    <button class="hbtn" id="btn-sidebar" title="Mostrar/ocultar navegação" aria-label="Navegação" onclick="toggleSidebar()">
      <i class="ti ti-layout-sidebar"></i><span>Navegação</span>
    </button>
    <button class="hbtn" id="btn-expand-all" title="Expandir todas as respostas" onclick="expandAll()">
      <i class="ti ti-unfold-more"></i><span>Expandir</span>
    </button>
    <button class="hbtn" id="btn-collapse-all" title="Recolher todas as respostas" onclick="collapseAll()">
      <i class="ti ti-fold"></i><span>Recolher</span>
    </button>
    <button class="hbtn" id="btn-print" title="Imprimir / exportar PDF" onclick="window.print()">
      <i class="ti ti-printer"></i><span>Imprimir</span>
    </button>
    <button class="hbtn" id="btn-font-size" title="Ajustar tamanho do texto" onclick="cycleFontSize()">
      <i class="ti ti-letter-case"></i>
    </button>
    <button class="hbtn" id="btn-theme" title="Alternar modo claro/escuro" onclick="toggleTheme()">
      <i class="ti ti-moon" id="theme-icon"></i>
    </button>
  </div>
</header>

<div id="app">
<div id="main">

<!-- SIDEBAR -->
<nav id="sidebar" aria-label="Navegação de categorias">
  <div id="sidebar-header">
    <span>Categorias</span>
    <button class="hbtn" onclick="toggleSidebar()" aria-label="Fechar navegação" style="padding:3px 7px">
      <i class="ti ti-x" style="font-size:13px"></i>
    </button>
  </div>
  <div id="sidebar-scroll" id="tree-root"></div>
</nav>

<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- CONTENT -->
<main id="content" role="main">
  <div id="content-inner">
    <div id="faq-main-content"></div>
    <div id="search-results-panel" aria-live="polite">
      <div id="search-results-list"></div>
      <div id="no-results" style="display:none">
        <i class="ti ti-search-off" style="font-size:36px;color:var(--text-muted);display:block;margin-bottom:12px"></i>
        Nenhum resultado encontrado para "<strong id="no-results-term"></strong>".
        <br><span style="font-size:12.5px;color:var(--text-muted)">Tente outros termos ou consulte o time técnico.</span>
      </div>
    </div>
  </div>
</main>

</div><!-- #main -->
</div><!-- #app -->

<button id="back-top" onclick="scrollToTop()" title="Voltar ao topo" aria-label="Voltar ao topo">
  <i class="ti ti-arrow-up"></i>
</button>

<!-- Tabler Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

<script>
/* ============================================================
   FAQ DATA
   Para adicionar uma nova categoria: adicione um objeto no array.
   Para adicionar uma nova pergunta: adicione em items[].
   Campos: q (pergunta), a (resposta HTML), tags (array de strings)
   Tipos de box na resposta: <div class="note">, <div class="warn">, <div class="tip">
   ============================================================ */
const FAQ_DATA = [

  /* ─── PRIMEIROS PASSOS ─────────────────────────────── */
  {
    id: "primeiros-passos",
    label: "Primeiros Passos",
    icon: "ti-rocket",
    items: [
      {
        q: "O que é o DEVA e para que serve?",
        a: `<p>O <strong>DEVA</strong> (Data Exploration &amp; Validation Agent) é um assistente virtual de qualidade de dados desenvolvido pelo Bradesco dentro do framework <strong>BRAI4DQ</strong>. Ele permite que você analise tabelas de dados, verifique problemas de qualidade e gere regras de validação — tudo por meio de perguntas em português, sem precisar escrever código SQL ou Python.</p>
            <p>Em vez de precisar de um engenheiro de dados para fazer uma análise, você pode simplesmente perguntar ao DEVA: <code>"A tabela está completa?"</code> ou <code>"Faça o profiling da tabela X"</code> e receber uma resposta em segundos.</p>
            <div class="tip"><strong>Analogia:</strong> pense no DEVA como um analista sênior sempre disponível que conhece todas as tabelas do catálogo e responde instantaneamente.</div>`,
        tags: ["introdução", "o que é"]
      },
      {
        q: "Preciso saber programar para usar o DEVA?",
        a: `<p><strong>Não.</strong> A interface principal do DEVA é um chat em português. Você digita perguntas como faria com qualquer assistente de mensagens e o DEVA retorna as análises formatadas.</p>
            <p>Para usuários que preferem, existe também a possibilidade de usar o DEVA diretamente via código Python em um notebook Databricks — mas isso é opcional e voltado ao perfil técnico.</p>`,
        tags: ["sem código", "iniciante"]
      },
      {
        q: "Como acesso o DEVA pela primeira vez?",
        a: `<p>O acesso é feito por um notebook no Databricks:</p>
            <ol>
              <li>Acesse o link do notebook compartilhado pelo seu time (disponível na seção "Como Usar" do Guia de Utilização).</li>
              <li>No Databricks, clique em <strong>Clone</strong> para criar uma cópia no seu workspace pessoal.</li>
              <li>Com o clone aberto, clique em <strong>Run All</strong> no menu superior.</li>
              <li>Aguarde o carregamento dos pacotes — a interface de chat aparecerá abaixo das células automaticamente.</li>
            </ol>
            <div class="note"><strong>Importante:</strong> sempre trabalhe com o clone. Nunca execute ou edite o notebook original, que é o template oficial compartilhado.</div>`,
        tags: ["acesso", "databricks", "clone"]
      },
      {
        q: "Qual é o fluxo de trabalho recomendado?",
        a: `<p>Para aproveitar o DEVA ao máximo, siga esta sequência:</p>
            <ol>
              <li><strong>Listar tabelas</strong> — "Liste as tabelas disponíveis"</li>
              <li><strong>Profiling</strong> — "Faça o profiling da tabela X"</li>
              <li><strong>Verificar qualidade</strong> — "A tabela está completa?" / "Tem duplicatas?"</li>
              <li><strong>Gerar regras</strong> — "Sugira regras de validação"</li>
              <li><strong>Revisar e aprovar</strong> — avaliar as regras sugeridas</li>
              <li><strong>Exportar</strong> — "Exporte as regras como YAML"</li>
            </ol>
            <div class="tip">Após cada resposta, o DEVA apresenta botões de sugestão com o próximo passo recomendado. Você pode simplesmente clicar nesses botões em vez de digitar.</div>`,
        tags: ["fluxo", "passo a passo"]
      },
      {
        q: "O DEVA pode alterar ou apagar meus dados?",
        a: `<p><strong>Não.</strong> O DEVA opera exclusivamente em modo de <strong>leitura</strong>. Ele acessa os metadados das tabelas (nomes de colunas, tipos, estatísticas), analisa a estrutura e gera sugestões — mas nunca escreve, altera ou remove registros.</p>
            <div class="tip">Suas tabelas estão protegidas. O DEVA é um analisador, não um editor.</div>`,
        tags: ["segurança", "dados", "leitura"]
      }
    ]
  },

  /* ─── USANDO O CHAT ───────────────────────────────── */
  {
    id: "usando-o-chat",
    label: "Usando o Chat",
    icon: "ti-message-circle",
    items: [
      {
        q: "Como faço perguntas ao DEVA?",
        a: `<p>Use linguagem natural em português. O DEVA foi treinado para entender perguntas diretas sobre qualidade de dados. Exemplos que funcionam bem:</p>
            <ul>
              <li><code>"Liste as tabelas disponíveis"</code></li>
              <li><code>"Faça o profiling da tabela de faturas"</code></li>
              <li><code>"A tabela está completa?"</code></li>
              <li><code>"Tem duplicatas?"</code></li>
              <li><code>"Sugira regras de validação"</code></li>
              <li><code>"Exporte as regras como YAML"</code></li>
            </ul>
            <div class="note">Quanto mais específica a pergunta, melhor a resposta. Inclua sempre o nome da tabela quando relevante.</div>`,
        tags: ["chat", "perguntas", "como usar"]
      },
      {
        q: "O DEVA consegue lembrar o contexto de perguntas anteriores?",
        a: `<p>Sim, quando iniciado com a opção <code>memory=True</code>. Com a memória ativa, você pode fazer perguntas de acompanhamento sem repetir o nome da tabela:</p>
            <ul>
              <li>Primeira pergunta: <code>"Faça o profiling da tabela de faturas"</code></li>
              <li>Pergunta seguinte: <code>"Quais colunas têm mais nulos?"</code> — o DEVA já sabe de qual tabela você fala.</li>
            </ul>
            <p>A memória funciona dentro de uma mesma sessão. Se você fechar e reabrir o notebook, o histórico é reiniciado.</p>`,
        tags: ["memória", "contexto", "sessão"]
      },
      {
        q: "Para que servem os botões de sugestão que aparecem após as respostas?",
        a: `<p>Após cada resposta, o DEVA exibe botões com os próximos passos mais relevantes. Eles funcionam como atalhos: ao clicar, a pergunta correspondente é enviada automaticamente, sem que você precise digitar.</p>
            <p>Esses botões são gerados dinamicamente com base no contexto da última análise — por isso podem variar de uma resposta para outra.</p>
            <div class="tip">São especialmente úteis para quem está explorando o DEVA pela primeira vez e ainda não sabe quais perguntas fazer.</div>`,
        tags: ["sugestões", "botões", "navegação"]
      },
      {
        q: "Para que servem os botões de avaliação (positivo/negativo) nas respostas?",
        a: `<p>Cada resposta do DEVA possui botões de avaliação. Ao clicar, o feedback é registrado com data e hora em um log estruturado que o time de governança pode consultar.</p>
            <p>Os dados de feedback alimentam métricas de qualidade do agente, como taxa de satisfação e taxa de respostas corretas — permitindo melhorias contínuas.</p>
            <div class="tip">Use sempre que possível. Quanto mais feedbacks, melhor o DEVA fica para toda a equipe.</div>`,
        tags: ["feedback", "avaliação", "qualidade"]
      },
      {
        q: "O DEVA diz que minha pergunta está 'fora do escopo'. O que significa?",
        a: `<p>O DEVA é especializado exclusivamente em <strong>qualidade de dados</strong>. Perguntas sobre outros assuntos — notícias, definições gerais, cálculos financeiros genéricos, entre outros — são bloqueadas automaticamente.</p>
            <p>Para resolver, reformule a pergunta incluindo termos relacionados a dados:</p>
            <ul>
              <li>Inclua palavras como: <em>tabela, coluna, dados, qualidade, analise, regra, profiling, completude</em></li>
              <li>Exemplo incorreto: <code>"Como está o cenário?"</code></li>
              <li>Exemplo correto: <code>"Analise a qualidade dos dados da tabela de faturas"</code></li>
            </ul>`,
        tags: ["escopo", "erro", "off-topic"]
      }
    ]
  },

  /* ─── ANÁLISES E FUNCIONALIDADES ─────────────────── */
  {
    id: "analises",
    label: "Análises e Funcionalidades",
    icon: "ti-chart-bar",
    items: [
      {
        q: "O que é profiling e o que ele me diz?",
        a: `<p>O <strong>profiling</strong> é uma análise automática completa de uma tabela. Em cerca de 18 segundos, o DEVA retorna:</p>
            <ul>
              <li>Total de registros e colunas</li>
              <li>Tipos de dados por coluna (texto, data, número, etc.)</li>
              <li>Percentual de campos nulos por coluna</li>
              <li>Distribuição de valores e padrões detectados</li>
              <li>Quais colunas possuem descrição no catálogo</li>
            </ul>
            <p>É o ponto de partida recomendado para qualquer análise. Com o profiling em mãos, você já sabe quais dimensões de qualidade merecem atenção.</p>
            <div class="note">Prompt: <code>"Faça o profiling da tabela catalog.schema.nome_tabela"</code></div>`,
        tags: ["profiling", "análise", "início"]
      },
      {
        q: "O que são as 'dimensões de qualidade' verificadas pelo DEVA?",
        a: `<p>São 10 critérios reconhecidos internacionalmente (framework QuantumBlack/McKinsey) para avaliar a saúde de um dado:</p>
            <table class="ref-table">
              <thead><tr><th>Dimensão</th><th>O que verifica</th></tr></thead>
              <tbody>
                <tr><td>Completude</td><td>Campos obrigatórios preenchidos (% não nulos)</td></tr>
                <tr><td>Unicidade</td><td>Ausência de registros duplicados</td></tr>
                <tr><td>Consistência</td><td>Formato correto (ex: CPF com 11 dígitos)</td></tr>
                <tr><td>Integridade</td><td>Relações entre campos fazem sentido de negócio</td></tr>
                <tr><td>Tempestividade</td><td>Dados atualizados dentro do prazo esperado</td></tr>
                <tr><td>Validade</td><td>Valores dentro de categorias e ranges válidos</td></tr>
                <tr><td>Acurácia</td><td>Correspondência com a fonte original</td></tr>
                <tr><td>Disponibilidade</td><td>Dados acessíveis quando necessário</td></tr>
                <tr><td>Confiabilidade</td><td>Consistência ao longo do tempo</td></tr>
                <tr><td>Interpretabilidade</td><td>Campos documentados com descrição no catálogo</td></tr>
              </tbody>
            </table>`,
        tags: ["dimensões", "qualidade", "completude", "unicidade"]
      },
      {
        q: "Como interpreto o resultado de completude?",
        a: `<p>O resultado de completude mostra o percentual de campos preenchidos por coluna. Use a tabela abaixo como referência:</p>
            <table class="ref-table">
              <thead><tr><th>Score</th><th>Classificação</th><th>Ação recomendada</th></tr></thead>
              <tbody>
                <tr><td>95% ou mais</td><td><span class="badge-ok">Excelente</span></td><td>Nenhuma ação necessária</td></tr>
                <tr><td>80% a 94%</td><td><span class="badge-warn">Atenção</span></td><td>Verificar se nulos são comportamento esperado do negócio</td></tr>
                <tr><td>Abaixo de 80%</td><td><span class="badge-err">Crítico</span></td><td>Abrir tratativa com o time responsável pela tabela</td></tr>
              </tbody>
            </table>
            <div class="warn"><strong>Atenção:</strong> campos nulos nem sempre são problema. Por exemplo, a coluna "data de pagamento" em uma tabela de faturas pode ser nula para faturas ainda não pagas — isso é comportamento esperado. O DEVA aponta o dado; o contexto de negócio é seu.</div>`,
        tags: ["completude", "nulos", "interpretação"]
      },
      {
        q: "Como interpreto o resultado de unicidade (duplicatas)?",
        a: `<p>O resultado de unicidade mostra se existem registros repetidos. Use como referência:</p>
            <table class="ref-table">
              <thead><tr><th>Resultado</th><th>Status</th><th>Ação recomendada</th></tr></thead>
              <tbody>
                <tr><td>0% de duplicatas</td><td><span class="badge-ok">OK</span></td><td>Nenhuma ação</td></tr>
                <tr><td>Menos de 1%</td><td><span class="badge-warn">Atenção</span></td><td>Verificar processo de ingestão</td></tr>
                <tr><td>1% ou mais</td><td><span class="badge-err">Crítico</span></td><td>Acionar time de engenharia de dados</td></tr>
              </tbody>
            </table>`,
        tags: ["unicidade", "duplicatas", "interpretação"]
      },
      {
        q: "Como interpreto o resultado de acurácia?",
        a: `<p>A acurácia mede se os valores da tabela correspondem à fonte de origem. Referência:</p>
            <table class="ref-table">
              <thead><tr><th>Score de acurácia</th><th>Status</th><th>Ação recomendada</th></tr></thead>
              <tbody>
                <tr><td>98% ou mais</td><td><span class="badge-ok">Excelente</span></td><td>Nenhuma ação</td></tr>
                <tr><td>90% a 97%</td><td><span class="badge-warn">Atenção</span></td><td>Revisar regras de transformação no pipeline de ingestão</td></tr>
                <tr><td>Abaixo de 90%</td><td><span class="badge-err">Crítico</span></td><td>Investigar origem e comparar com sistema-fonte</td></tr>
              </tbody>
            </table>`,
        tags: ["acurácia", "interpretação", "precisão"]
      },
      {
        q: "O que é drift e quando devo verificar?",
        a: `<p><strong>Drift</strong> é a variação estatística dos dados ao longo do tempo — comparando a distribuição atual com uma versão anterior (baseline). Por exemplo, se a proporção de clientes ativos em uma tabela era de 70% e caiu para 40% de um mês para o outro, isso é um drift significativo.</p>
            <p>Verifique drift quando:</p>
            <ul>
              <li>Suspeitar que algo mudou no processo de ingestão ou transformação dos dados</li>
              <li>Resultados de relatórios ou modelos analíticos parecerem fora do padrão</li>
              <li>Houver mudança recente no sistema de origem dos dados</li>
            </ul>
            <div class="note">Prompt: <code>"Compare a tabela com a versão de ontem"</code> ou <code>"Analise drift na tabela"</code></div>`,
        tags: ["drift", "variação", "monitoramento"]
      }
    ]
  },

  /* ─── REGRAS DE VALIDAÇÃO ─────────────────────────── */
  {
    id: "regras",
    label: "Regras de Validação",
    icon: "ti-file-check",
    items: [
      {
        q: "Como sei se as regras geradas pelo DEVA estão corretas?",
        a: `<p>As regras são geradas por motores estatísticos (AI4D) que analisam os dados reais da tabela — não são inventadas pela inteligência artificial. Ainda assim, é sua responsabilidade revisá-las antes de qualquer uso.</p>
            <p>Para validar uma regra, pergunte a si mesmo:</p>
            <ul>
              <li>Ela faz sentido para o negócio? (ex: "CPF deve ter 11 dígitos" — sim, faz sentido)</li>
              <li>Existe alguma exceção legítima que ela não considera?</li>
              <li>A regra seria aprovada pelo time responsável pela tabela?</li>
            </ul>
            <div class="tip">O processo de revisão é chamado de <strong>Human-in-the-Loop (HITL)</strong> — você revisa, aprova ou ajusta antes de qualquer exportação. O DEVA sugere; você decide.</div>`,
        tags: ["regras", "validação", "revisão", "hitl"]
      },
      {
        q: "O DEVA inventa regras de validação?",
        a: `<p><strong>Não.</strong> Este é um ponto crítico de entendimento: o modelo de inteligência artificial (LLM) utilizado pelo DEVA <strong>não gera regras</strong>. Ele apenas classifica a sua intenção e formata a resposta de forma legível.</p>
            <p>As regras vêm exclusivamente dos motores estatísticos <strong>AI4D</strong> — algoritmos que analisam a estrutura real e os valores da tabela para identificar padrões, ranges válidos, formatos e relações entre colunas.</p>
            <p>O LLM nunca recebe dados brutos — apenas metadados como nomes de tabelas e colunas.</p>`,
        tags: ["regras", "LLM", "AI4D", "confiabilidade"]
      },
      {
        q: "Posso aprovar apenas algumas das regras geradas?",
        a: `<p>Sim. O DEVA oferece controle granular sobre a aprovação:</p>
            <ul>
              <li>Aprovar todas: <code>"Aprove todas as regras"</code></li>
              <li>Aprovar apenas específicas: <code>"Aprove as regras 1, 3 e 5"</code></li>
              <li>Aprovar com exclusão: <code>"Aprove tudo exceto as regras 2 e 4"</code></li>
              <li>Pedir ajuste: <code>"Adicione uma regra para validar o formato de e-mail"</code></li>
            </ul>
            <p>Após a aprovação, você pode exportar apenas as regras aprovadas no formato desejado.</p>`,
        tags: ["aprovação", "regras", "hitl", "seleção"]
      },
      {
        q: "Em quais formatos posso exportar as regras?",
        a: `<p>O DEVA suporta 5 formatos de exportação, prontos para integração em pipelines de qualidade de dados:</p>
            <ul>
              <li><strong>Python</strong> — código usando a API Validator Fluent do framework BRAI4DQ</li>
              <li><strong>YAML</strong> — configuração declarativa, ideal para pipelines automatizados</li>
              <li><strong>JSON</strong> — para integração com sistemas e APIs</li>
              <li><strong>XML</strong> — para sistemas legados ou integrações corporativas</li>
              <li><strong>SQL INSERT</strong> — para armazenar as regras diretamente em banco de dados</li>
            </ul>
            <div class="note">Prompt: <code>"Exporte as regras como YAML"</code> (ou Python, JSON, XML, SQL)</div>`,
        tags: ["exportação", "formatos", "yaml", "python"]
      },
      {
        q: "As regras geradas são aplicadas automaticamente nas tabelas?",
        a: `<p><strong>Não.</strong> As regras exportadas são arquivos de configuração ou código que precisam ser integrados manualmente (ou via pipeline automatizado) ao processo de validação. O DEVA não aplica nada diretamente nas tabelas.</p>
            <p>Fluxo correto:</p>
            <ol>
              <li>DEVA gera as regras</li>
              <li>Você revisa e aprova</li>
              <li>DEVA exporta no formato escolhido</li>
              <li>Time técnico integra ao pipeline de qualidade</li>
            </ol>`,
        tags: ["aplicação", "pipeline", "exportação", "automático"]
      }
    ]
  },

  /* ─── SEGURANÇA E DADOS ───────────────────────────── */
  {
    id: "seguranca",
    label: "Segurança e Privacidade",
    icon: "ti-shield-lock",
    items: [
      {
        q: "Meus dados ficam seguros ao usar o DEVA?",
        a: `<p>Sim. O DEVA foi projetado com múltiplas camadas de proteção:</p>
            <ul>
              <li><strong>Dados nunca saem do ambiente:</strong> toda a execução ocorre dentro da infraestrutura Databricks/Bradesco</li>
              <li><strong>IA recebe apenas metadados:</strong> o modelo de linguagem vê apenas nomes de tabelas e listas de colunas — nunca valores reais</li>
              <li><strong>Motores estatísticos locais:</strong> as análises rodam dentro do cluster Databricks, não em servidores externos</li>
              <li><strong>Credenciais seguras:</strong> autenticação via tokens renováveis, sem senhas expostas</li>
            </ul>`,
        tags: ["segurança", "privacidade", "dados"]
      },
      {
        q: "Quem pode ver minhas conversas com o DEVA?",
        a: `<p>Todas as interações são registradas em uma tabela centralizada (<code>deva_interactions_log</code>) com data, hora e conteúdo da mensagem. O acesso a esse log segue as políticas de governança de dados do Bradesco.</p>
            <p>O objetivo é auditoria e melhoria contínua do agente — não monitoramento individual.</p>`,
        tags: ["log", "auditoria", "privacidade"]
      },
      {
        q: "O DEVA pode ser usado com qualquer tabela do Databricks?",
        a: `<p>Sim, desde que a tabela:</p>
            <ul>
              <li>Esteja registrada no <strong>Unity Catalog</strong></li>
              <li>Você tenha permissão de <strong>leitura</strong> sobre ela</li>
            </ul>
            <p>Para tabelas muito grandes, o DEVA utiliza amostragem inteligente, analisando uma amostra representativa sem comprometer a qualidade das conclusões.</p>`,
        tags: ["tabelas", "unity catalog", "permissões"]
      },
      {
        q: "Existe limite de uso do DEVA?",
        a: `<p>Sim, para garantir estabilidade para todos os usuários:</p>
            <ul>
              <li><strong>Limite de mensagens:</strong> máximo de 60 perguntas por minuto (janela deslizante)</li>
              <li><strong>Tempo por operação:</strong> máximo de 5 minutos por análise</li>
            </ul>
            <p>Se atingir o limite de mensagens, aguarde 1 minuto antes de continuar. Se uma operação ultrapassar 5 minutos, tente com uma tabela menor ou com amostragem reduzida.</p>`,
        tags: ["limite", "rate limit", "timeout"]
      }
    ]
  },

  /* ─── ERROS COMUNS ────────────────────────────────── */
  {
    id: "erros-comuns",
    label: "Erros Comuns",
    icon: "ti-alert-triangle",
    items: [
      {
        q: "O DEVA diz 'Não identifiquei com clareza' — o que fazer?",
        a: `<p>O DEVA não conseguiu entender qual análise você quer fazer. Isso acontece quando a pergunta é muito vaga ou usa termos não associados a qualidade de dados.</p>
            <p>Solução: seja mais específico e inclua o nome da tabela.</p>
            <ul>
              <li>Vago: <code>"Analise isso"</code></li>
              <li>Correto: <code>"Faça o profiling da tabela catalog.schema.faturas"</code></li>
            </ul>`,
        tags: ["erro", "pergunta", "interpretação"]
      },
      {
        q: "Recebi 'Rate limit exceeded' — o que aconteceu?",
        a: `<p>Você enviou mais de 60 mensagens em menos de 1 minuto. O limite existe para garantir estabilidade do serviço para todos os usuários.</p>
            <p><strong>Solução:</strong> aguarde 1 minuto e tente novamente. Não é necessário reiniciar o notebook.</p>`,
        tags: ["rate limit", "limite", "erro"]
      },
      {
        q: "A operação ficou travada e não respondeu — e agora?",
        a: `<p>Cada operação tem um limite de 5 minutos. Se ultrapassar esse tempo, a operação é encerrada automaticamente com um erro de timeout.</p>
            <p>Possíveis causas e soluções:</p>
            <ul>
              <li><strong>Tabela muito grande:</strong> tente com a versão de amostragem da tabela, se disponível</li>
              <li><strong>Cluster sobrecarregado:</strong> aguarde alguns minutos e tente novamente</li>
              <li><strong>Sessão expirada:</strong> reinicie o notebook e execute Run All novamente</li>
            </ul>`,
        tags: ["timeout", "travado", "erro"]
      },
      {
        q: "O que significa 'LLM indisponível'?",
        a: `<p>O modelo de inteligência artificial utilizado pelo DEVA (acessado via Bridge Bradesco ou Azure OpenAI) não estava disponível no momento da requisição. O DEVA tenta automaticamente até 3 vezes antes de exibir esse erro.</p>
            <p><strong>Solução:</strong> aguarde 2 a 5 minutos e tente novamente. Se o problema persistir por mais de 15 minutos, acione o time técnico de suporte ao BRAI4DQ.</p>`,
        tags: ["LLM", "indisponível", "erro", "suporte"]
      },
      {
        q: "A resposta parece errada ou não faz sentido — o que fazer?",
        a: `<p>As análises estatísticas (profiling, completude, duplicatas) são baseadas em dados reais e possuem alta precisão. A interpretação em linguagem natural pode eventualmente simplificar ou omitir algum detalhe.</p>
            <p>Passos recomendados:</p>
            <ol>
              <li>Clique no botão de avaliação negativa na resposta para registrar o problema</li>
              <li>Reformule a pergunta com mais detalhes e contexto</li>
              <li>Tente perguntas mais específicas por dimensão (ex: apenas completude, depois apenas unicidade)</li>
              <li>Se persistir, documente o comportamento e acione o time técnico</li>
            </ol>`,
        tags: ["resposta incorreta", "erro", "feedback"]
      }
    ]
  },

  /* ─── ERROS GRAVES ────────────────────────────────── */
  {
    id: "erros-graves",
    label: "Erros Graves — O DEVA Nao Inicia",
    icon: "ti-alert-circle",
    items: [
      {
        q: "O DEVA nao inicia apos Run All — o que fazer?",
        a: `<p>Este é o cenário mais crítico. Siga o diagnóstico em ordem:</p>
            <div class="callout callout-error"><span class="callout-title">Verificacao 1 — Cluster ativo</span>Certifique-se de que um cluster Databricks está selecionado e em execução. O símbolo verde ao lado do nome do cluster indica que está ativo. Se estiver cinza, inicie o cluster e aguarde a inicialização completa (pode levar 3 a 8 minutos).</div>
            <div class="callout callout-error"><span class="callout-title">Verificacao 2 — Versao do runtime</span>O DEVA requer <strong>Databricks Runtime 17.x ou superior</strong>. Verifique a versão do cluster nas configurações. Se for inferior, solicite a criação de um cluster na versão correta ao time de infraestrutura.</div>
            <div class="callout callout-error"><span class="callout-title">Verificacao 3 — Wheels instalados</span>Os pacotes (wheels) precisam estar instalados no cluster. Execute a célula de instalação do notebook manualmente e observe se aparecem erros. Erros do tipo <code>FileNotFoundError</code> indicam que o caminho dos wheels está incorreto ou desatualizado.</div>
            <div class="callout callout-error"><span class="callout-title">Verificacao 4 — Variaveis de ambiente</span>Verifique se as variáveis de ambiente do provedor LLM estão configuradas corretamente no cluster (Bridge Bradesco ou Azure OpenAI). Sem isso, o DEVA inicializa parcialmente mas falha ao receber a primeira mensagem.</div>
            <div class="callout callout-warn"><span class="callout-title">Se nenhuma verificacao resolver</span>Acione o time técnico de suporte ao BRAI4DQ com as seguintes informações: nome do cluster, versão do runtime, mensagem de erro completa e nome do notebook utilizado.</div>`,
        tags: ["erro grave", "não inicia", "run all", "cluster"]
      },
      {
        q: "Erro 'FileNotFoundError: Caminho de wheels nao encontrado'",
        a: `<p>Os arquivos de instalação (wheels) não foram encontrados no caminho configurado. Isso ocorre quando:</p>
            <ul>
              <li>O diretório de wheels foi atualizado para uma nova data e o notebook ainda aponta para a versão anterior</li>
              <li>Você não tem permissão de leitura no Volume do Unity Catalog que contém os wheels</li>
            </ul>
            <p>Diagnóstico:</p>
            <ol>
              <li>Verifique o path configurado na célula de instalação do notebook</li>
              <li>Confirme a data da última publicação dos wheels com o time técnico</li>
              <li>Verifique suas permissões: você precisa de <code>USE CATALOG</code>, <code>USE SCHEMA</code> e <code>READ VOLUME</code> no Unity Catalog</li>
            </ol>
            <div class="callout callout-info"><span class="callout-title">Path padrao dos wheels</span>O path segue o padrão: <code>/Volumes/dv_platfun/d4852s015_poc_mckinsey/wheels-volume/[DATA]/</code></div>`,
        tags: ["wheels", "instalação", "FileNotFoundError", "erro grave"]
      },
      {
        q: "Erro 'SparkSession nao disponivel'",
        a: `<p>O cluster Databricks não está conectado corretamente ou a sessão Spark expirou.</p>
            <p>Soluções, em ordem de prioridade:</p>
            <ol>
              <li>Verifique se o cluster ainda está ativo (não foi encerrado por inatividade)</li>
              <li>Clique em <strong>Reconectar</strong> no topo do notebook, se essa opção aparecer</li>
              <li>Execute <strong>Run All</strong> novamente para reinicializar toda a sessão</li>
              <li>Se o erro persistir, detache o notebook do cluster, reanexe e execute Run All</li>
            </ol>`,
        tags: ["spark", "sessão", "cluster", "erro grave"]
      },
      {
        q: "A interface de chat apareceu mas nao responde a nenhuma mensagem",
        a: `<p>A interface carregou, mas o backend do DEVA não está respondendo. Causas mais comuns:</p>
            <ul>
              <li><strong>Variáveis de ambiente ausentes:</strong> o provedor LLM (Bridge ou Azure OpenAI) não está configurado</li>
              <li><strong>Erro silencioso na inicialização:</strong> alguma dependência falhou sem mostrar mensagem visível</li>
              <li><strong>Timeout de conexão:</strong> o cluster está sobrecarregado</li>
            </ul>
            <p>Diagnóstico rápido:</p>
            <ol>
              <li>Role para cima no notebook e verifique se há células com fundo vermelho (erro)</li>
              <li>Execute a célula de verificação de saúde, se disponível: <code>deva.get_health()</code></li>
              <li>Se não houver erros visíveis, aguarde 2 minutos e tente enviar uma mensagem simples: <code>"Help"</code></li>
            </ol>
            <div class="callout callout-warn"><span class="callout-title">Quando escalar</span>Se o problema persistir após reinicialização completa do notebook, acione o time técnico com: print completo do log de erros, nome e versão do cluster, e a mensagem de erro exata.</div>`,
        tags: ["interface", "sem resposta", "erro grave", "LLM"]
      },
      {
        q: "Como escalar um problema tecnico ao time responsavel?",
        a: `<p>Ao acionar o suporte, inclua sempre as seguintes informações para agilizar o diagnóstico:</p>
            <ul>
              <li>Nome e versão do cluster Databricks utilizado</li>
              <li>Nome completo do notebook (com path no workspace)</li>
              <li>Mensagem de erro completa (copie o texto, não apenas uma captura de tela)</li>
              <li>Horário em que o erro ocorreu</li>
              <li>Qual ação você estava tentando realizar quando o erro apareceu</li>
            </ul>
            <div class="callout callout-info"><span class="callout-title">Canal de suporte</span>Consulte o time de Governança de Dados ou o COE (Centro de Excelência) do BRAI4DQ para o canal oficial de atendimento interno.</div>`,
        tags: ["suporte", "escalação", "contato", "COE"]
      }
    ]
  },

  /* ─── FUNCIONALIDADES EM DESENVOLVIMENTO ─────────── */
  {
    id: "em-desenvolvimento",
    label: "Em Desenvolvimento",
    icon: "ti-tools",
    items: [
      {
        q: "Quais funcionalidades ainda nao estao totalmente prontas?",
        a: `<p>As seguintes funcionalidades estão implementadas parcialmente e podem apresentar comportamento instável ou resultados incompletos:</p>
            <table class="ref-table">
              <thead><tr><th>Funcionalidade</th><th>Status</th><th>Observação</th></tr></thead>
              <tbody>
                <tr><td>Detecção de Anomalias</td><td><span class="badge-dev">Em desenvolvimento</span></td><td>Pode falhar em tabelas com alta cardinalidade</td></tr>
                <tr><td>Relatório Regulatório BCB</td><td><span class="badge-dev">Em desenvolvimento</span></td><td>Formato em validação com time de conformidade</td></tr>
                <tr><td>Avaliação de Governança BACEN</td><td><span class="badge-dev">Em desenvolvimento</span></td><td>Mapeamento de intent concluído; lógica em andamento</td></tr>
              </tbody>
            </table>
            <div class="warn">Se usar essas funcionalidades, valide os resultados manualmente antes de tomar qualquer decisão com base neles.</div>`,
        tags: ["desenvolvimento", "anomalias", "BCB", "BACEN"]
      },
      {
        q: "Posso usar a detecção de anomalias mesmo em desenvolvimento?",
        a: `<p>Sim, mas com cautela. O motor de anomalias (<code>ai4d_anomaly v1.7.3</code>) está integrado e pode funcionar em tabelas menores com baixa cardinalidade. O risco é maior em tabelas com muitas colunas numéricas distintas.</p>
            <p>Se quiser experimentar:</p>
            <ol>
              <li>Teste primeiro na tabela de amostra (20k registros), não na base completa</li>
              <li>Valide os resultados com conhecimento de negócio — um outlier detectado pode ser legítimo</li>
              <li>Registre o feedback positivo ou negativo para ajudar na evolução</li>
            </ol>
            <div class="note">Prompt: <code>"Detecte anomalias na tabela"</code></div>`,
        tags: ["anomalias", "outliers", "desenvolvimento"]
      }
    ]
  },

  /* ─── TECNICO AVANCADO ────────────────────────────── */
  {
    id: "tecnico",
    label: "Tecnico — Uso Avancado",
    icon: "ti-code",
    items: [
      {
        q: "Como o DEVA classifica a minha intenção (intent)?",
        a: `<p>O DEVA usa um processo de classificação em duas etapas chamado <strong>2-Pass Classifier</strong>:</p>
            <ol>
              <li><strong>Pass 1 — Grupo:</strong> o LLM identifica a qual grupo pertence a pergunta (catálogo, qualidade, avançado, regras, exportação ou meta). Threshold de confiança: ≥ 0,65.</li>
              <li><strong>Pass 2 — Intent específico:</strong> dentro do grupo, o LLM identifica a operação exata a executar (ex: profiling, completude, unicidade). Threshold: ≥ 0,55.</li>
            </ol>
            <p>Se a confiança ficar abaixo dos thresholds, o DEVA responde "Não identifiquei com clareza" — o que é preferível a executar a operação errada.</p>`,
        tags: ["intent", "classificação", "técnico", "2-pass"]
      },
      {
        q: "Como funciona a geração de regras por dentro?",
        a: `<p>A geração segue um pipeline em 6 etapas:</p>
            <ol>
              <li>O motor <code>ai4d.recommender</code> analisa estatísticas da tabela (mínimo, máximo, moda, distribuição)</li>
              <li>O motor <code>ai4d.association</code> identifica correlações entre colunas</li>
              <li>As regras brutas são consolidadas e deduplicadas</li>
              <li>O LLM organiza, prioriza e adiciona justificativas em linguagem natural</li>
              <li>O <code>RuleQualityProcessor</code> filtra regras com confiança abaixo de 50% e remove redundâncias</li>
              <li>O resultado é apresentado ao usuário para revisão (HITL)</li>
            </ol>
            <div class="note">O LLM nunca recebe os dados reais — apenas os metadados e as regras já geradas pelos motores estatísticos.</div>`,
        tags: ["regras", "ai4d", "pipeline", "técnico"]
      },
      {
        q: "O que são os tipos de regra (CheckType) que o DEVA gera?",
        a: `<p>Cada regra gerada tem um tipo (CheckType) que define a operação de verificação:</p>
            <table class="ref-table">
              <thead><tr><th>Categoria</th><th>Operadores</th><th>Exemplo</th></tr></thead>
              <tbody>
                <tr><td>Comparação básica</td><td>eq, ne, ge, gt, le, lt</td><td>saldo >= 0</td></tr>
                <tr><td>Range e conjunto</td><td>in_range, isin</td><td>status em {ativo, inativo}</td></tr>
                <tr><td>String</td><td>str_startswith, str_endswith, str_contains</td><td>código começa com "BR"</td></tr>
                <tr><td>Tamanho de string</td><td>str_length_min, str_length_max, str_length_range</td><td>CPF com 11 caracteres</td></tr>
                <tr><td>Padrão (regex)</td><td>matches_pattern</td><td>e-mail no formato x@y.z</td></tr>
                <tr><td>Nulabilidade</td><td>nullable</td><td>campo pode ser nulo (opcional)</td></tr>
              </tbody>
            </table>`,
        tags: ["checktype", "operadores", "técnico", "regras"]
      },
      {
        q: "Como posso usar o DEVA via código Python diretamente?",
        a: `<p>Para usuários técnicos que preferem não usar a interface visual:</p>
            <pre style="background:var(--code-bg);border:1px solid var(--border);border-radius:6px;padding:12px;font-size:12.5px;overflow-x:auto;line-height:1.6;color:var(--text-primary)"><code>from brai4dq_genai.agent.deva import DEVAAgent

# Iniciar com memória de contexto
deva = DEVAAgent(spark=spark, memory=True)

# Fazer análises
print(deva.chat("Liste as tabelas disponíveis").content)
print(deva.chat("Faça o profiling").content)

# Gerar regras diretamente
regras = deva.rules(
    table="catalog.schema.tabela",
    use_llm=True,
    use_association=True
)
deva.approve()
print(deva.code(format="yaml"))</code></pre>`,
        tags: ["python", "código", "API", "técnico"]
      },
      {
        q: "Como verificar a saúde e métricas do DEVA?",
        a: `<p>O DEVAAgent expõe métodos para monitoramento em tempo real:</p>
            <ul>
              <li><code>deva.get_health()</code> — retorna status geral: taxa de sucesso (meta: &gt; 80%), disponibilidade do LLM</li>
              <li><code>deva.get_metrics()</code> — retorna: total de requisições, tempo médio de resposta (meta: &lt; 30s), taxa de cache hit (meta: &gt; 50%), requisições off-topic bloqueadas</li>
            </ul>
            <div class="note">Os feedbacks de usuário ficam registrados na tabela <code>deva_interactions_log</code> no Unity Catalog e podem ser consultados via SQL para relatórios de governança.</div>`,
        tags: ["saúde", "métricas", "monitoramento", "técnico"]
      },
      {
        q: "Quais são as dependencias de pacotes (wheels) necessarias?",
        a: `<p>O DEVA requer 15 wheels organizados em dois grupos:</p>
            <p><strong>Grupo AI4D</strong> (motores estatísticos):</p>
            <ul>
              <li>ai4d_anomaly, ai4d_common, ai4d_entity, ai4d_lineage, ai4d_profiling</li>
              <li>ai4d_timeseries, ai4d_unstructured, ai4d_validation, ai4d_viz</li>
            </ul>
            <p><strong>Grupo BRAI4DQ</strong> (framework Bradesco):</p>
            <ul>
              <li>brai4dq_api, brai4dq_bradesco, brai4dq_core_domain, brai4dq_genai</li>
              <li>brai4dq_results_storage, brai4dq_spark_adapters</li>
            </ul>
            <div class="warn">A ordem de instalação é crítica: os wheels AI4D devem ser instalados antes dos BRAI4DQ. Consulte a seção de instalação do dossiê técnico para o script completo.</div>`,
        tags: ["wheels", "instalação", "dependências", "técnico"]
      }
    ]
  }
];

/* ============================================================
   APP STATE
   ============================================================ */
let sidebarOpen = true;
let fontSizeState = 1; // 0=sm, 1=md, 2=lg
let currentTheme = 'light';
let openCards = new Set();
let searchActive = false;

/* ============================================================
   RENDER TREE (SIDEBAR)
   ============================================================ */
function renderTree() {
  const container = document.getElementById('sidebar-scroll');
  container.innerHTML = '';
  FAQ_DATA.forEach((section, si) => {
    const sectionEl = document.createElement('div');
    sectionEl.className = 'tree-section';

    const header = document.createElement('div');
    header.className = 'tree-section-header open';
    header.innerHTML = `<i class="ti ${section.icon} tree-icon"></i>${section.label}<i class="ti ti-chevron-right tree-arrow"></i>`;
    header.onclick = () => {
      const items = sectionEl.querySelector('.tree-items');
      const isOpen = items.classList.toggle('open');
      header.classList.toggle('open', isOpen);
    };

    const items = document.createElement('div');
    items.className = 'tree-items open';

    section.items.forEach((item, ii) => {
      const a = document.createElement('a');
      a.className = 'tree-item';
      a.href = `#faq-${section.id}-${ii}`;
      a.textContent = item.q.length > 60 ? item.q.slice(0, 57) + '...' : item.q;
      a.onclick = (e) => {
        e.preventDefault();
        scrollToCard(section.id, ii);
        document.querySelectorAll('.tree-item').forEach(el => el.classList.remove('active'));
        a.classList.add('active');
        if (window.innerWidth <= 768) toggleSidebar();
      };
      items.appendChild(a);
    });

    sectionEl.appendChild(header);
    sectionEl.appendChild(items);
    container.appendChild(sectionEl);
  });
}

/* ============================================================
   RENDER MAIN CONTENT
   ============================================================ */
function renderContent() {
  const container = document.getElementById('faq-main-content');
  container.innerHTML = '';

  FAQ_DATA.forEach((section, si) => {
    const block = document.createElement('div');
    block.className = 'section-block';
    block.id = `section-${section.id}`;

    const title = document.createElement('div');
    title.className = 'section-title';
    title.innerHTML = `<i class="ti ${section.icon} section-icon"></i>${section.label}<span class="section-badge">${section.items.length} pergunta${section.items.length !== 1 ? 's' : ''}</span>`;
    block.appendChild(title);

    section.items.forEach((item, ii) => {
      const card = createCard(section.id, ii, item);
      block.appendChild(card);
    });

    container.appendChild(block);
  });
}

function createCard(sectionId, index, item, highlight = '') {
  const card = document.createElement('div');
  card.className = 'faq-card';
  card.id = `faq-${sectionId}-${index}`;

  const qText = highlight ? highlightText(item.q, highlight) : item.q;
  const question = document.createElement('div');
  question.className = 'faq-question';
  question.innerHTML = `<div class="faq-q-icon">Q</div><span style="flex:1">${qText}</span><i class="ti ti-chevron-down faq-chevron"></i>`;

  const answer = document.createElement('div');
  answer.className = 'faq-answer';

  const tags = item.tags && item.tags.length
    ? `<div class="faq-tags">${item.tags.map(t => `<span class="faq-tag">${t}</span>`).join('')}</div>` : '';

  answer.innerHTML = `<div class="faq-answer-inner">${item.a}${tags}</div>`;

  const cardId = `${sectionId}-${index}`;
  question.onclick = () => toggleCard(card, cardId);

  if (openCards.has(cardId)) {
    card.classList.add('open');
    answer.style.maxHeight = '1200px';
    answer.style.padding = '0 18px 18px';
  }

  card.appendChild(question);
  card.appendChild(answer);
  return card;
}

function toggleCard(card, cardId) {
  const isOpen = card.classList.toggle('open');
  const answer = card.querySelector('.faq-answer');
  if (isOpen) {
    answer.style.maxHeight = '1200px';
    answer.style.padding = '0 18px 18px';
    openCards.add(cardId);
  } else {
    answer.style.maxHeight = '0';
    answer.style.padding = '0 18px';
    openCards.delete(cardId);
  }
}

function scrollToCard(sectionId, index) {
  const card = document.getElementById(`faq-${sectionId}-${index}`);
  if (!card) return;
  card.scrollIntoView({ behavior: 'smooth', block: 'start' });
  setTimeout(() => {
    if (!card.classList.contains('open')) {
      const cardId = `${sectionId}-${index}`;
      toggleCard(card, cardId);
    }
    card.classList.add('pulse');
    setTimeout(() => card.classList.remove('pulse'), 700);
  }, 400);
}

/* ============================================================
   SEARCH
   ============================================================ */
function highlightText(text, query) {
  if (!query) return text;
  const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
  return text.replace(re, '<mark>$1</mark>');
}

function doSearch(query) {
  const q = query.trim().toLowerCase();
  const mainContent = document.getElementById('faq-main-content');
  const searchPanel = document.getElementById('search-results-panel');
  const resultsList = document.getElementById('search-results-list');
  const noResults = document.getElementById('no-results');
  const clearBtn = document.getElementById('search-clear');

  clearBtn.style.display = q ? 'flex' : 'none';

  if (!q) {
    searchPanel.style.display = 'none';
    mainContent.style.display = '';
    searchActive = false;
    return;
  }

  searchActive = true;
  mainContent.style.display = 'none';
  searchPanel.style.display = 'block';
  resultsList.innerHTML = '';
  noResults.style.display = 'none';

  let total = 0;
  FAQ_DATA.forEach(section => {
    section.items.forEach((item, ii) => {
      const inQ = item.q.toLowerCase().includes(q);
      const inA = item.a.toLowerCase().includes(q);
      const inT = item.tags && item.tags.some(t => t.toLowerCase().includes(q));
      if (inQ || inA || inT) {
        total++;
        const card = createCard(section.id, ii, item, q);
        card.id = `search-${section.id}-${ii}`;
        const label = document.createElement('div');
        label.style.cssText = 'font-size:11px;color:var(--text-muted);margin-bottom:4px;padding-left:4px';
        label.textContent = section.label;
        resultsList.appendChild(label);
        resultsList.appendChild(card);
      }
    });
  });

  if (total === 0) {
    document.getElementById('no-results-term').textContent = query;
    noResults.style.display = 'block';
  } else {
    const info = document.createElement('div');
    info.style.cssText = 'font-size:12.5px;color:var(--text-muted);margin-bottom:16px';
    info.textContent = `${total} resultado${total !== 1 ? 's' : ''} para "${query}"`;
    resultsList.prepend(info);
  }
}

document.getElementById('search-input').addEventListener('input', (e) => {
  clearTimeout(window._searchTimer);
  window._searchTimer = setTimeout(() => doSearch(e.target.value), 200);
});

document.getElementById('search-clear').addEventListener('click', () => {
  document.getElementById('search-input').value = '';
  doSearch('');
});

/* ============================================================
   CONTROLS
   ============================================================ */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const btn = document.getElementById('btn-sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('mobile-open');
    overlay.style.display = sidebar.classList.contains('mobile-open') ? 'block' : 'none';
  } else {
    sidebar.classList.toggle('collapsed');
    btn.classList.toggle('active', !sidebar.classList.contains('collapsed'));
    sidebarOpen = !sidebar.classList.contains('collapsed');
  }
}

function expandAll() {
  document.querySelectorAll('.faq-card:not(.open)').forEach(card => {
    const id = card.id.replace('faq-', '');
    card.classList.add('open');
    const answer = card.querySelector('.faq-answer');
    answer.style.maxHeight = '1200px';
    answer.style.padding = '0 18px 18px';
    openCards.add(id);
  });
}

function collapseAll() {
  document.querySelectorAll('.faq-card.open').forEach(card => {
    card.classList.remove('open');
    const answer = card.querySelector('.faq-answer');
    answer.style.maxHeight = '0';
    answer.style.padding = '0 18px';
  });
  openCards.clear();
}

function toggleTheme() {
  const html = document.documentElement;
  const icon = document.getElementById('theme-icon');
  if (html.getAttribute('data-theme') === 'light') {
    html.setAttribute('data-theme', 'dark');
    icon.className = 'ti ti-sun';
    currentTheme = 'dark';
  } else {
    html.setAttribute('data-theme', 'light');
    icon.className = 'ti ti-moon';
    currentTheme = 'light';
  }
  localStorage.setItem('deva-theme', currentTheme);
}

function cycleFontSize() {
  const body = document.body;
  const btn = document.getElementById('btn-font-size');
  fontSizeState = (fontSizeState + 1) % 3;
  body.classList.remove('font-sm', 'font-lg');
  const labels = ['A-', 'A', 'A+'];
  if (fontSizeState === 0) { body.classList.add('font-sm'); }
  if (fontSizeState === 2) { body.classList.add('font-lg'); }
  btn.title = `Tamanho atual: ${labels[fontSizeState]}`;
}

function scrollToTop() {
  document.getElementById('content').scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── Back to top + progress bar ── */
document.getElementById('content').addEventListener('scroll', function() {
  const btn = document.getElementById('back-top');
  const bar = document.getElementById('progress-bar');
  const el = this;
  btn.classList.toggle('visible', el.scrollTop > 400);
  const pct = (el.scrollTop / (el.scrollHeight - el.clientHeight)) * 100;
  bar.style.width = Math.min(pct, 100) + '%';
});

/* ── Restore theme ── */
const savedTheme = localStorage.getItem('deva-theme');
if (savedTheme === 'dark') {
  document.documentElement.setAttribute('data-theme', 'dark');
  document.getElementById('theme-icon').className = 'ti ti-sun';
  currentTheme = 'dark';
}

/* ── Init ── */
renderTree();
renderContent();
</script>
</body>
</html>
