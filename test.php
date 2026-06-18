/*
 * css/devview.css — Estilos da Visão DEV
 *
 * Responsabilidade: toda a aparência do painel editor, toolbar, abas,
 * accordion, campos de formulário e componentes exclusivos da Dev View.
 * NÃO afeta o boletim — todas as classes são prefixadas com .dev-.
 *
 * Conversa com:
 *   - index.html     (IDs e classes do painel Dev View)
 *   - js/devview.js  (adiciona/remove classes .dev-* dinamicamente)
 *   - css/styles.css (herda CSS custom properties: --pr, --g*, --r, --sh)
 *
 * Pontos importantes:
 *   - Custom properties DEV-exclusivas definidas em :root abaixo.
 *     Não conflitam com styles.css pois têm prefixo --dev-.
 *   - Parte 1 (sessão 1): layout base, toolbar, abas, accordion, campos, botões, badge.
 *   - Parte 2 (sessão 2): drag & drop, guias, hover canvas, feedback visual.
 *   - Parte 3 (sessão 8): aba Log, aba Versoes, overrides Pickr, dark mode fine-tuning.
 *   - A toolbar tem fundo #1A1D2E fixo — não muda com o tema escuro/claro.
 */

/* ─── CUSTOM PROPERTIES DEV-EXCLUSIVAS ─── */
:root {
  --dev-panel-bg:      var(--g100);
  --dev-panel-width:   380px;
  --dev-toolbar-bg:    #1A1D2E;
  --dev-toolbar-h:     48px;
  --dev-border:        var(--g200);
  --dev-radius:        8px;
  --dev-field-h:       32px;
  --dev-transition:    150ms ease;
  --dev-accent:        var(--pr);
  --dev-font:          var(--font-body, 'DM Sans', sans-serif);
}

/* ─── PAGE LAYOUT ─── */
.page-dev {
  background: var(--bg);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.dev-layout {
  display: flex;
  flex: 1;
  overflow: hidden;
  height: calc(100% - var(--dev-toolbar-h));
}

/* ─── DEV TOOLBAR ─── */
.dev-toolbar {
  height: var(--dev-toolbar-h);
  background: var(--dev-toolbar-bg);
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 0 16px;
  flex-shrink: 0;
  border-bottom: 1px solid rgba(255,255,255,.08);
  z-index: 100;
}

.dev-toolbar-brand {
  font-size: .72rem;
  font-weight: 700;
  color: rgba(255,255,255,.5);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-right: 8px;
  white-space: nowrap;
}

.dev-toolbar-brand span {
  color: var(--pr);
}

.dev-toolbar-sep {
  width: 1px;
  height: 20px;
  background: rgba(255,255,255,.12);
  margin: 0 6px;
  flex-shrink: 0;
}

.dev-toolbar-spacer { flex: 1; }

.dev-tb-btn {
  height: 30px;
  padding: 0 12px;
  border-radius: 6px;
  font-size: .72rem;
  font-weight: 600;
  font-family: var(--dev-font);
  cursor: pointer;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.08);
  color: rgba(255,255,255,.75);
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
  transition: background var(--dev-transition), color var(--dev-transition);
}

.dev-tb-btn:hover:not(:disabled) {
  background: rgba(255,255,255,.18);
  color: #fff;
}

.dev-tb-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.dev-tb-btn.primary {
  background: var(--pr);
  border-color: var(--pr);
  color: #fff;
}

.dev-tb-btn.primary:hover:not(:disabled) {
  background: var(--pr-dk, #8C0F3B);
}

/* ─── PAINEL EDITOR (esquerda) ─── */
.dev-panel {
  width: var(--dev-panel-width);
  min-width: var(--dev-panel-width);
  background: var(--dev-panel-bg);
  border-right: 1px solid var(--dev-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ─── CANVAS DE PREVIEW (direita) ─── */
.dev-canvas {
  flex: 1;
  overflow-y: auto;
  position: relative;
}

/* ─── ABAS DO PAINEL ─── */
.dev-tabs {
  display: flex;
  border-bottom: 1px solid var(--dev-border);
  background: var(--wh);
  flex-shrink: 0;
}

.dev-tab {
  flex: 1;
  height: 38px;
  border: none;
  background: none;
  font-family: var(--dev-font);
  font-size: .7rem;
  font-weight: 600;
  color: var(--g500);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: color var(--dev-transition), border-color var(--dev-transition);
  position: relative;
  white-space: nowrap;
  padding: 0 4px;
}

.dev-tab:hover { color: var(--g800); }

.dev-tab.act {
  color: var(--pr);
  border-bottom-color: var(--pr);
}

.dev-tab-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 8px;
  background: var(--red, #E8143A);
  color: #fff;
  font-size: .58rem;
  font-weight: 800;
  margin-left: 3px;
  vertical-align: middle;
}

/* ─── TAB CONTENT ─── */
.dev-tab-content {
  display: none;
  flex: 1;
  overflow-y: auto;
  padding: 14px 14px 40px;
}

.dev-tab-content.act {
  display: block;
}

/* ─── ACCORDION ─── */
.dev-accordion {
  margin-bottom: 8px;
  border: 1px solid var(--dev-border);
  border-radius: var(--dev-radius);
  background: var(--wh);
  overflow: hidden;
}

.dev-accordion-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  cursor: pointer;
  user-select: none;
  background: var(--wh);
  border: none;
  width: 100%;
  text-align: left;
  font-family: var(--dev-font);
  transition: background var(--dev-transition);
}

.dev-accordion-header:hover { background: var(--g100); }

.dev-accordion-header.open { border-bottom: 1px solid var(--dev-border); }

.dev-accordion-title {
  font-size: .78rem;
  font-weight: 700;
  color: var(--g800);
  flex: 1;
}

.dev-accordion-count {
  font-size: .65rem;
  color: var(--g500);
  background: var(--g100);
  padding: 1px 7px;
  border-radius: 10px;
}

.dev-accordion-chevron {
  color: var(--g500);
  transition: transform var(--dev-transition);
  flex-shrink: 0;
}

.dev-accordion-header.open .dev-accordion-chevron {
  transform: rotate(180deg);
}

.dev-accordion-body {
  display: none;
  padding: 12px;
  border-top: 1px solid var(--dev-border);
  background: var(--wh);
}

.dev-accordion-body.open {
  display: block;
}

.dev-accordion-body-inner {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* ─── FORM FIELDS ─── */
.dev-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dev-field-row {
  display: flex;
  gap: 8px;
  align-items: flex-end;
}

.dev-field-label {
  font-size: .62rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--g500);
}

.dev-field-input {
  height: var(--dev-field-h);
  width: 100%;
  border: 1px solid var(--dev-border);
  border-radius: 6px;
  padding: 0 9px;
  font-size: .78rem;
  font-family: var(--dev-font);
  background: var(--g100);
  color: var(--g800);
  outline: none;
  transition: border-color var(--dev-transition), box-shadow var(--dev-transition);
}

.dev-field-input:focus {
  border-color: var(--pr);
  background: var(--wh);
  box-shadow: 0 0 0 2px rgba(192,17,55,.12);
}

.dev-field-textarea {
  width: 100%;
  min-height: 72px;
  border: 1px solid var(--dev-border);
  border-radius: 6px;
  padding: 8px 9px;
  font-size: .76rem;
  font-family: var(--dev-font);
  background: var(--g100);
  color: var(--g800);
  outline: none;
  resize: vertical;
  line-height: 1.5;
  transition: border-color var(--dev-transition), box-shadow var(--dev-transition);
}

.dev-field-textarea:focus {
  border-color: var(--pr);
  background: var(--wh);
  box-shadow: 0 0 0 2px rgba(192,17,55,.12);
}

.dev-field-select {
  height: var(--dev-field-h);
  width: 100%;
  border: 1px solid var(--dev-border);
  border-radius: 6px;
  padding: 0 9px;
  font-size: .78rem;
  font-family: var(--dev-font);
  background: var(--g100);
  color: var(--g800);
  outline: none;
  cursor: pointer;
  transition: border-color var(--dev-transition);
}

.dev-field-select:focus { border-color: var(--pr); }

/* ─── BUTTONS ─── */
.dev-btn {
  height: 30px;
  padding: 0 12px;
  border-radius: 6px;
  font-size: .73rem;
  font-weight: 600;
  font-family: var(--dev-font);
  cursor: pointer;
  border: 1px solid var(--dev-border);
  background: var(--g100);
  color: var(--g700);
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
  transition: background var(--dev-transition), border-color var(--dev-transition);
}

.dev-btn:hover { background: var(--g200); border-color: var(--g300); }

.dev-btn.primary {
  background: var(--pr);
  border-color: var(--pr);
  color: #fff;
}

.dev-btn.primary:hover { background: var(--pr-dk, #8C0F3B); }

.dev-btn.danger {
  background: rgba(232,20,58,.08);
  border-color: rgba(232,20,58,.2);
  color: var(--red, #E8143A);
}

.dev-btn.danger:hover {
  background: rgba(232,20,58,.15);
  border-color: rgba(232,20,58,.4);
}

.dev-btn.ghost {
  background: transparent;
  border-color: transparent;
  color: var(--g500);
}

.dev-btn.ghost:hover { background: var(--g100); color: var(--g800); }

.dev-btn.icon-only {
  width: 30px;
  padding: 0;
  justify-content: center;
}

/* ─── BADGE DEV NA SIDEBAR ─── */
.dev-mode-badge {
  display: none;
  font-size: .55rem;
  font-weight: 800;
  letter-spacing: .1em;
  color: #fff;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.25);
  border-radius: 4px;
  padding: 2px 7px;
  margin: 2px 8px 4px;
  text-align: center;
  animation: dev-badge-pulse 2.5s ease-in-out infinite;
}

@keyframes dev-badge-pulse {
  0%, 100% { opacity: 0.65; }
  50%       { opacity: 1; box-shadow: 0 0 8px rgba(255,107,138,.4); }
}

/* ─── DRAG HANDLE ─── */
.dev-drag-handle {
  color: var(--g300);
  cursor: grab;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  padding: 0 2px;
  transition: color var(--dev-transition);
}

.dev-drag-handle:hover  { color: var(--g600); }
.dev-drag-handle:active { cursor: grabbing; }

/* ─── SECTION HEADER ROW (título + botão add) ─── */
.dev-section-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.dev-section-title {
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--g600);
}

/* ─── INLINE TABLE EDITOR ─── */
.dev-table {
  width: 100%;
  border-collapse: collapse;
  font-size: .73rem;
}

.dev-table th {
  font-size: .6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--g500);
  padding: 4px 6px;
  text-align: left;
  border-bottom: 1px solid var(--dev-border);
  white-space: nowrap;
}

.dev-table td {
  padding: 3px 4px;
  border-bottom: 1px solid var(--g100);
  vertical-align: middle;
}

.dev-table tr:last-child td { border-bottom: none; }

.dev-table-input {
  width: 100%;
  height: 26px;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: 0 5px;
  font-size: .72rem;
  font-family: var(--dev-font);
  background: transparent;
  color: var(--g800);
  outline: none;
  transition: border-color var(--dev-transition), background var(--dev-transition);
}

.dev-table-input:focus {
  border-color: var(--pr);
  background: var(--wh);
}

/* ─── RADIO PILLS (layout controls) ─── */
.dev-pills {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.dev-pill {
  height: 28px;
  padding: 0 12px;
  border-radius: 14px;
  font-size: .72rem;
  font-weight: 600;
  font-family: var(--dev-font);
  cursor: pointer;
  border: 1px solid var(--dev-border);
  background: var(--g100);
  color: var(--g600);
  transition: all var(--dev-transition);
}

.dev-pill.act,
.dev-pill:hover {
  background: var(--pr);
  border-color: var(--pr);
  color: #fff;
}

/* ─── SEPARATOR ─── */
.dev-sep {
  height: 1px;
  background: var(--dev-border);
  margin: 8px 0;
}

/* ─── EMPTY STATE ─── */
.dev-empty {
  text-align: center;
  padding: 24px 12px;
  color: var(--g500);
  font-size: .78rem;
}

/* ─── INLINE CONFIRM ─── */
.dev-inline-confirm {
  background: var(--g100);
  border: 1px solid var(--dev-border);
  border-left: 3px solid var(--yellow, #F5A623);
  border-radius: 6px;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.dev-inline-confirm-text {
  font-size: .76rem;
  color: var(--g700);
  line-height: 1.4;
}

.dev-inline-confirm-btns {
  display: flex;
  gap: 6px;
}

/* ─── LOG ENTRIES ─── */
.dev-log-entry {
  padding: 8px 10px;
  border-radius: 6px;
  margin-bottom: 6px;
  border-left: 3px solid var(--g300);
  background: var(--g100);
  font-size: .75rem;
}
.dev-log-entry-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 3px;
  flex-wrap: wrap;
}
.dev-log-badge-lvl {
  font-size: .64rem;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 3px;
  text-transform: uppercase;
  letter-spacing: .04em;
  background: var(--g300);
  color: var(--g700);
}
.dev-log-ts  { font-size: .68rem; color: var(--g500); }
.dev-log-ctx { font-size: .68rem; color: var(--g600); font-family: monospace; }
.dev-log-msg { color: var(--g700); line-height: 1.4; }
.dev-log-detail { margin-top: 4px; }
.dev-log-detail summary { font-size: .68rem; color: var(--g500); cursor: pointer; }
.dev-log-detail pre { font-size: .65rem; margin: 4px 0 0; color: var(--g600); overflow-x: auto; }
.dev-log-unread { border-left-color: var(--pr); }
.dev-log-empty  { text-align: center; padding: 20px; color: var(--g500); font-size: .78rem; }

.dev-log-lvl-info     { border-left-color: #3B6BF5; }
.dev-log-lvl-info     .dev-log-badge-lvl { background: #dbe4ff; color: #1a3dbf; }
.dev-log-lvl-warn     { border-left-color: #F5A623; }
.dev-log-lvl-warn     .dev-log-badge-lvl { background: #fff3cd; color: #7c5200; }
.dev-log-lvl-error    { border-left-color: var(--red, #c01137); }
.dev-log-lvl-error    .dev-log-badge-lvl { background: #ffe4e9; color: #8c0f3b; }
.dev-log-lvl-resolved { border-left-color: #00C07A; }
.dev-log-lvl-resolved .dev-log-badge-lvl { background: #d1faec; color: #065f46; }

/* ─── VERSIONS LIST ─── */
.dev-versions-item {
  border: 1px solid var(--g200);
  border-radius: 6px;
  padding: 8px 10px;
  margin-bottom: 6px;
  background: var(--wh, #fff);
}
.dev-versions-item-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}
.dev-versions-item-actions {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}
.dev-versions-label { font-size: .78rem; font-weight: 600; color: var(--g800); flex: 1; }
.dev-versions-ts    { font-size: .68rem; color: var(--g500); }
.dev-versions-tag {
  font-size: .62rem; font-weight: 700; padding: 1px 5px;
  border-radius: 3px; text-transform: uppercase; letter-spacing: .04em;
}
.dev-versions-tag.auto   { background: #dbe4ff; color: #1a3dbf; }
.dev-versions-tag.manual { background: #d1faec; color: #065f46; }

.dev-versions-bar-wrap { margin-top: 12px; }
.dev-versions-bar-label { font-size: .68rem; color: var(--g500); margin-bottom: 4px; }
.dev-versions-bar {
  height: 6px; border-radius: 3px;
  background: var(--g200); overflow: hidden;
}
.dev-versions-bar-fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, #3B6BF5, var(--pr));
  transition: width .4s ease;
}

/* ─── MODAL overlay ─── */
.dev-modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999;
}
.dev-modal {
  background: var(--wh, #fff);
  border-radius: 10px;
  box-shadow: 0 20px 60px rgba(0,0,0,.25);
  overflow: hidden;
}
.dev-modal-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  background: var(--g100);
  border-bottom: 1px solid var(--g200);
  font-size: .82rem; font-weight: 700; color: var(--g800);
}
.dev-modal-body { padding: 16px; }

/* ─── DEV NAV (sidebar de abas) ─── */
.dev-nav {
  width: 110px;
  flex-shrink: 0;
  background: var(--g100);
  border-right: 1px solid var(--g200);
  display: flex;
  flex-direction: column;
  padding: 8px 0;
  overflow-y: auto;
}
.dev-nav-btn {
  display: block;
  width: 100%;
  padding: 9px 12px;
  text-align: left;
  font-size: .72rem;
  font-weight: 600;
  border: none;
  background: transparent;
  color: var(--g600);
  cursor: pointer;
  border-left: 3px solid transparent;
  transition: all 150ms ease;
  font-family: var(--dev-font);
}
.dev-nav-btn:hover { background: var(--g200); color: var(--g800); }
.dev-nav-btn.act   { border-left-color: var(--pr); background: var(--wh, #fff); color: var(--pr); }

/* ─── DEV PANE ─── */
.dev-pane {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: var(--wh, #fff);
}

/* ─── DEV PREVIEW ─── */
.dev-preview {
  width: 360px;
  flex-shrink: 0;
  border-left: 1px solid var(--g200);
  overflow: hidden;
  display: none; /* hidden por padrão — ativar se quiser split view */
}

/* ─── SECTION TITLE ─── */
.dev-section-title {
  font-size: .8rem;
  font-weight: 700;
  color: var(--g700);
}
.dev-section-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

/* ─── ACCORDION ─── */
.dev-accordion { margin-bottom: 6px; border: 1px solid var(--g200); border-radius: 6px; overflow: hidden; }
.dev-accordion-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 10px; cursor: pointer;
  background: var(--g100); font-size: .76rem; font-weight: 600; color: var(--g700);
}
.dev-accordion-head:hover { background: var(--g200); }
.dev-accordion-title { flex: 1; }
.dev-accordion-body  { padding: 10px; background: var(--wh,#fff); }

/* ─── FIELDS ─── */
.dev-field { margin-bottom: 10px; }
.dev-field-label {
  font-size: .68rem; font-weight: 600; color: var(--g600);
  text-transform: uppercase; letter-spacing: .04em; margin-bottom: 3px;
}
.dev-field-input {
  height: var(--dev-field-h); padding: 0 8px;
  border: 1px solid var(--dev-border); border-radius: 5px;
  font-size: .78rem; font-family: var(--dev-font); color: var(--g800);
  background: var(--wh,#fff); outline: none;
  transition: border-color var(--dev-transition);
  box-sizing: border-box;
}
.dev-field-input:focus { border-color: var(--pr); }
textarea.dev-field-input { height: auto; padding: 6px 8px; resize: vertical; }
.dev-field-select {
  height: var(--dev-field-h); padding: 0 8px;
  border: 1px solid var(--dev-border); border-radius: 5px;
  font-size: .78rem; font-family: var(--dev-font); color: var(--g800);
  background: var(--wh,#fff); outline: none;
}

/* ─── BUTTONS ─── */
.dev-btn {
  height: 30px; padding: 0 12px; border-radius: 5px;
  font-size: .76rem; font-weight: 600; font-family: var(--dev-font);
  cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 4px;
  transition: all var(--dev-transition);
}
.dev-btn.primary { background: var(--pr); color: #fff; }
.dev-btn.primary:hover { filter: brightness(1.12); }
.dev-btn.ghost   { background: transparent; border: 1px solid var(--dev-border); color: var(--g700); }
.dev-btn.ghost:hover { background: var(--g200); }
.dev-btn.danger  { background: #ffe4e9; color: #8c0f3b; border: 1px solid #fca5b8; }
.dev-btn.danger:hover { background: #fca5b8; }

/* ─── BADGE ─── */
.dev-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 16px; height: 16px; padding: 0 4px;
  border-radius: 8px; background: var(--pr); color: #fff;
  font-size: .6rem; font-weight: 700; margin-left: 4px;
}

/* ─── DARK MODE FINE-TUNING ─── */
[data-theme="dark"] .dev-modal       { background: var(--bg); }
[data-theme="dark"] .dev-field-input { background: #1a1d2e; color: #e2e8f0; border-color: #2d3552; }
[data-theme="dark"] .dev-field-select { background: #1a1d2e; color: #e2e8f0; border-color: #2d3552; }
[data-theme="dark"] .dev-versions-item { background: #1a1d2e; border-color: #2d3552; }

/* ─── TOGGLE SWITCH ─── */
.dev-toggle { position:relative; display:inline-flex; align-items:center; cursor:pointer; }
.dev-toggle input { opacity:0; width:0; height:0; position:absolute; }
.dev-toggle-track {
  width:36px; height:20px; background:var(--g300); border-radius:10px;
  transition:background .2s; position:relative; flex-shrink:0;
}
.dev-toggle-track::after {
  content:''; position:absolute; top:2px; left:2px;
  width:16px; height:16px; border-radius:50%; background:#fff;
  transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2);
}
.dev-toggle input:checked + .dev-toggle-track { background:var(--pr); }
.dev-toggle input:checked + .dev-toggle-track::after { transform:translateX(16px); }

/* ─── MODAL TABS ─── */
.dev-modal-tab {
  padding:8px 16px; border:none; background:transparent; cursor:pointer;
  font-size:.76rem; font-weight:600; color:var(--g600);
  border-bottom:2px solid transparent; transition:all .15s;
  font-family:var(--dev-font);
}
.dev-modal-tab.act { color:var(--pr); border-bottom-color:var(--pr); }
.dev-modal-tab:hover { background:var(--g100); }

/* ─── ACCORDION OPEN STATE ─── */
.dev-accordion-head.open { background:var(--g200); }

/* ─── ACCORDION BODY OPEN (display:block por padrão via JS) ─── */
.dev-accordion-body { display:block; }

/* ─── MODAL DE CONFIRMAÇÃO ─── */
.dev-confirm-modal {
  width: 360px;
  max-width: 90vw;
}
