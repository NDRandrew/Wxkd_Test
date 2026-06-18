/*
 * js/devview.js — Dev View completa (Sessões 3+4+7+8 + correções v3)
 * ATIVAÇÃO: ?dev=1 na URL
 */
(function () {
  if (!new URLSearchParams(location.search).has('dev')) return;

  // ═══ HISTORY (undo/redo) ═══════════════════════════════════════════════
  const History = (() => {
    const MAX = 50; let stack = [], cursor = -1;
    function capture() {
      const snap = JSON.stringify(window.D);
      if (cursor < stack.length - 1) stack = stack.slice(0, cursor + 1);
      stack.push(snap);
      if (stack.length > MAX) stack.shift();
      cursor = stack.length - 1;
    }
    function apply(snap) {
      try { Object.assign(window.D, JSON.parse(snap)); } catch { return; }
      if (typeof applyData  === 'function') applyData();
      if (typeof initCharts === 'function') setTimeout(initCharts, 80);
    }
    function undo() { if (cursor <= 0) return false; cursor--; apply(stack[cursor]); return true; }
    function redo() { if (cursor >= stack.length-1) return false; cursor++; apply(stack[cursor]); return true; }
    return { capture, undo, redo };
  })();

  // ═══ LAZY LIBS ══════════════════════════════════════════════════════════
  function loadDevLibs() {
    [{ id:'sortablejs', src:'https://cdn.jsdelivr.net/npm/sortablejs@1.15.7/Sortable.min.js' },
     { id:'pickr',      src:'https://cdn.jsdelivr.net/npm/@simonwep/pickr@1.9.1/dist/pickr.es5.min.js' }
    ].forEach(({ id, src }) => {
      if (document.getElementById('devlib-' + id)) return;
      const s = document.createElement('script'); s.id = 'devlib-' + id; s.src = src;
      document.head.appendChild(s);
    });
    if (!document.getElementById('devlib-pickr-css')) {
      const l = document.createElement('link');
      l.id = 'devlib-pickr-css'; l.rel = 'stylesheet';
      l.href = 'https://cdn.jsdelivr.net/npm/@simonwep/pickr@1.9.1/dist/themes/nano.min.css';
      document.head.appendChild(l);
    }
  }

  // ═══ PAINEL PRINCIPAL ═══════════════════════════════════════════════════
  function buildDevPanel() {
    const page = document.getElementById('page-dev');
    if (!page) return;
    page.innerHTML = `
      <div class="dev-toolbar">
        <span class="dev-toolbar-brand">Dev <span>View</span></span>
        <div class="dev-toolbar-sep"></div>
        <button class="dev-btn ghost dev-tb-btn" id="devUndo" title="Ctrl+Z">↩ Desfazer</button>
        <button class="dev-btn ghost dev-tb-btn" id="devRedo" title="Ctrl+Y">↪ Refazer</button>
        <div class="dev-toolbar-sep"></div>
        <button class="dev-btn ghost dev-tb-btn" id="devExportPng">PNG</button>
        <button class="dev-btn ghost dev-tb-btn" id="devExportJson">JSON</button>
        <div style="flex:1"></div>
        <button class="dev-btn ghost dev-tb-btn" id="devViewBlt">← Ver Boletim</button>
      </div>
      <div class="dev-layout">
        <nav class="dev-nav" id="devNav">
          <button class="dev-nav-btn act" data-tab="secoes">Seções</button>
          <button class="dev-nav-btn" data-tab="kpi">KPIs</button>
          <button class="dev-nav-btn" data-tab="tabela">Tabela</button>
          <button class="dev-nav-btn" data-tab="causas">Causas</button>
          <button class="dev-nav-btn" data-tab="po">PO Cards</button>
          <button class="dev-nav-btn" data-tab="releases">Releases</button>
          <button class="dev-nav-btn" data-tab="graficos">Gráficos</button>
          <button class="dev-nav-btn" data-tab="aparencia">Aparência</button>
          <button class="dev-nav-btn" data-tab="periodo">Período</button>
          <button class="dev-nav-btn" data-tab="log">Log <span id="dev-log-badge" class="dev-badge" style="display:none">0</span></button>
          <button class="dev-nav-btn" data-tab="versoes">Versões</button>
        </nav>
        <div class="dev-pane" id="devPane"></div>
      </div>`;

    const navBtn = document.getElementById('nav-dev');
    const badge  = document.getElementById('devModeBadge');
    if (navBtn) navBtn.style.display = 'flex';
    if (badge)  badge.style.display  = 'block';

    _bindToolbar();
    _bindTabs();
    _activateTab('secoes');
    Versions.startAutoSave(5 * 60 * 1000);
    History.capture();
    DevLog.info('devview', 'Dev View pronta');
  }

  // ═══ TOOLBAR ════════════════════════════════════════════════════════════
  function _bindToolbar() {
    document.getElementById('devUndo').onclick = () => { if (History.undo()) { _activateTab(_currentTab()); } };
    document.getElementById('devRedo').onclick = () => { if (History.redo()) { _activateTab(_currentTab()); } };
    document.getElementById('devExportPng').onclick  = _exportPng;
    document.getElementById('devExportJson').onclick = _exportJson;
    document.getElementById('devViewBlt').onclick = () => { if (typeof goPage === 'function') goPage('blt'); };
    document.addEventListener('keydown', e => {
      if (!(e.ctrlKey || e.metaKey)) return;
      if (e.key === 'z' && !e.shiftKey) { e.preventDefault(); if (History.undo()) { _activateTab(_currentTab()); } }
      if (e.key === 'y' || (e.key === 'z' && e.shiftKey)) { e.preventDefault(); if (History.redo()) { _activateTab(_currentTab()); } }
    });
  }
  function _exportPng() {
    if (typeof html2canvas === 'undefined') { alert('html2canvas não carregado.'); return; }
    const periodo = (window.D.periodo || 'boletim').replace(/\s+/g,'_');

    // O .blt-body fica invisible enquanto a page-dev está ativa.
    // Solução: navegar para o boletim, capturar, voltar.
    const bltPage = document.getElementById('page-blt');
    const devPage = document.getElementById('page-dev');
    const navBlt  = document.getElementById('nav-blt');
    const navDev  = document.getElementById('nav-dev');
    if (!bltPage) return;

    // Mostrar boletim temporariamente
    bltPage.classList.add('act');
    if (devPage) devPage.classList.remove('act');
    if (navBlt)  navBlt.classList.add('act');
    if (navDev)  navDev.classList.remove('act');

    const target = bltPage.querySelector('.blt-body') || bltPage;

    // Aguardar reflow + repintura dos charts
    setTimeout(() => {
      html2canvas(target, {
        scale          : 3,
        useCORS        : true,
        allowTaint     : true,
        backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--bg').trim() || '#ECEEF3',
        scrollX        : 0,
        scrollY        : -window.scrollY
      }).then(canvas => {
        canvas.toBlob(blob => {
          const url = URL.createObjectURL(blob);
          const a   = document.createElement('a');
          a.href = url; a.download = `boletim_${periodo}.png`; a.click();
          URL.revokeObjectURL(url);
        }, 'image/png');
      }).catch(err => DevLog.capture('devview.exportPng', err))
        .finally(() => {
          // Voltar para o Dev View
          if (devPage) devPage.classList.add('act');
          bltPage.classList.remove('act');
          if (navDev) navDev.classList.add('act');
          if (navBlt) navBlt.classList.remove('act');
        });
    }, 400);
  }
  function _exportJson() {
    const periodo = (window.D.periodo || 'boletim').replace(/\s+/g,'_');
    const a = document.createElement('a');
    a.download = `dados_${periodo}.json`;
    a.href = URL.createObjectURL(new Blob([JSON.stringify(window.D, null, 2)], { type:'application/json' }));
    a.click();
  }

  // ═══ ROTEAMENTO DE ABAS ══════════════════════════════════════════════════
  function _currentTab() {
    const btn = document.querySelector('.dev-nav-btn.act');
    return btn ? btn.dataset.tab : 'secoes';
  }
  function _bindTabs() {
    document.querySelectorAll('.dev-nav-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.dev-nav-btn').forEach(b => b.classList.remove('act'));
        btn.classList.add('act');
        _activateTab(btn.dataset.tab);
      });
    });
  }
  function _activateTab(tab) {
    const pane = document.getElementById('devPane');
    if (!pane) return;
    pane.innerHTML = '';
    switch (tab) {
      case 'secoes':    renderTabSecoes(pane);           break;
      case 'kpi':       renderTabKpi(pane);              break;
      case 'periodo':   renderTabPeriodo(pane);          break;
      case 'tabela':    renderTabTabela(pane);           break;
      case 'causas':    renderTabCausas(pane);           break;
      case 'po':        renderTabPo(pane);               break;
      case 'releases':  renderTabReleases(pane);         break;
      case 'graficos':  renderTabGraficos(pane);         break;
      case 'aparencia': renderTabAparencia(pane);        break;
      case 'log':       renderTabLog(pane);              break;
      case 'versoes':   renderTabVersoes(pane);          break;
      default: pane.innerHTML = `<div class="dev-empty">Aba "${tab}" não implementada.</div>`;
    }
  }

  // ═══ HELPERS ════════════════════════════════════════════════════════════
  function mkAccordion(title, bodyHtml, extraBtns = '') {
    const id = 'acc-' + Math.random().toString(36).slice(2, 7);
    return `<div class="dev-accordion">
      <div class="dev-accordion-head" data-toggle="${id}">
        <span class="dev-accordion-title">${title}</span>
        <div style="display:flex;gap:6px;align-items:center">${extraBtns}</div>
      </div>
      <div class="dev-accordion-body" id="${id}">${bodyHtml}</div>
    </div>`;
  }
  function bindAccordions(root) {
    root.querySelectorAll('[data-toggle]').forEach(head => {
      head.addEventListener('click', e => {
        if (e.target.closest('button') && e.target.closest('button') !== head) return;
        const body = document.getElementById(head.dataset.toggle);
        if (!body) return;
        const isOpen = body.classList.contains('open');
        body.classList.toggle('open', !isOpen);
        head.classList.toggle('open', !isOpen);
      });
    });
  }
  // ─── Modal de confirmação (substitui confirm() nativo) ──────────────────
  function devConfirm(msg, onYes) {
    const ov = document.createElement('div');
    ov.className = 'dev-modal-overlay';
    ov.innerHTML = `
      <div class="dev-modal dev-confirm-modal">
        <div class="dev-modal-body" style="padding:24px 20px 16px">
          <p style="margin:0 0 20px;font-size:.85rem;color:var(--g800);line-height:1.5">${msg}</p>
          <div style="display:flex;justify-content:flex-end;gap:8px">
            <button class="dev-btn ghost" id="dcNo">Cancelar</button>
            <button class="dev-btn danger" id="dcYes">Confirmar</button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(ov);
    ov.querySelector('#dcNo').onclick  = () => ov.remove();
    ov.querySelector('#dcYes').onclick = () => { ov.remove(); onYes(); };
    ov.addEventListener('click', e => { if (e.target === ov) ov.remove(); });
  }

  function colorPicker(labelText, cssVar) {
    const root = document.documentElement;
    const cur  = root.style.getPropertyValue(cssVar) || getComputedStyle(root).getPropertyValue(cssVar).trim() || '#c01137';
    const uid  = 'cp-' + Math.random().toString(36).slice(2, 6);
    return `<div class="dev-field">
      <div class="dev-field-label">${labelText}</div>
      <div style="display:flex;gap:6px;align-items:center">
        <div id="sw-${uid}" data-uid="${uid}" data-cssvar="${cssVar}"
             style="width:28px;height:28px;border-radius:4px;border:1px solid var(--g300);background:${cur};cursor:pointer;flex-shrink:0"
             title="Clique para abrir o seletor de cor"></div>
        <input class="dev-field-input" id="inp-${uid}" data-cssvar="${cssVar}" value="${cur}" style="width:110px">
        <button class="dev-btn ghost" style="font-size:.7rem" data-apply-color="${uid}">Aplicar</button>
      </div>
    </div>`;
  }

  function bindColorPickers(root) {
    // Botão "Aplicar" — aplica o valor digitado no input
    root.querySelectorAll('[data-apply-color]').forEach(btn => {
      btn.addEventListener('click', () => {
        const uid    = btn.dataset.applyColor;
        const inp    = root.querySelector('#inp-' + uid);
        const sw     = root.querySelector('#sw-'  + uid);
        if (!inp) return;
        const cssVar = inp.dataset.cssvar;
        const val    = inp.value.trim();
        document.documentElement.style.setProperty(cssVar, val);
        if (sw) sw.style.background = val;
        DevLog.info('devview.color', `${cssVar} → ${val}`);
      });
    });

    // Swatch — abre Pickr se disponível, senão input[type=color] nativo
    root.querySelectorAll('[data-uid][data-cssvar]').forEach(sw => {
      sw.addEventListener('click', () => {
        const uid    = sw.dataset.uid;
        const cssVar = sw.dataset.cssvar;
        const inp    = root.querySelector('#inp-' + uid);
        const curVal = (inp ? inp.value.trim() : null) || '#c01137';

        if (window.Pickr) {
          if (sw._pickr) { try { sw._pickr.destroy(); } catch {} delete sw._pickr; }
          try {
            sw._pickr = Pickr.create({
              el: sw, theme: 'nano', default: curVal,
              components: { preview:true, opacity:false, hue:true,
                interaction: { hex:true, input:true, save:true } }
            });
            sw._pickr.on('save', color => {
              const hex = color.toHEXA().toString();
              document.documentElement.style.setProperty(cssVar, hex);
              sw.style.background = hex;
              if (inp) inp.value = hex;
              DevLog.info('devview.color', `${cssVar} → ${hex}`);
              sw._pickr.hide();
            });
            sw._pickr.show();
          } catch (err) {
            DevLog.capture('devview.pickr', err);
            _fallbackColorInput(sw, inp, cssVar);
          }
        } else {
          _fallbackColorInput(sw, inp, cssVar);
        }
      });
    });
  }

  function _fallbackColorInput(sw, inp, cssVar) {
    const native = document.createElement('input');
    native.type  = 'color';
    native.value = inp ? inp.value.trim() : '#c01137';
    native.style.cssText = 'position:fixed;opacity:0;pointer-events:none;top:0;left:0';
    document.body.appendChild(native);
    native.addEventListener('input', () => {
      document.documentElement.style.setProperty(cssVar, native.value);
      if (sw)  sw.style.background = native.value;
      if (inp) inp.value = native.value;
    });
    native.addEventListener('change', () => document.body.removeChild(native));
    native.click();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA SEÇÕES — gerenciar visibilidade e layout das seções do boletim
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabSecoes(pane) {
    const sections = Array.from(document.querySelectorAll('.blt-body > section'));
    const kpiStrip = document.querySelector('.kpi-strip');
    const poGrid   = document.querySelector('.po-grid');

    pane.innerHTML = `
      <div class="dev-section-row"><span class="dev-section-title">Gerenciar Seções</span></div>
      <div class="dev-sep"></div>
      <div class="dev-empty" style="font-size:.75rem;color:var(--g500);padding:0 0 12px 0;text-align:left">
        Mostre/oculte seções inteiras do boletim. Use os outros painéis para editar conteúdo.
      </div>
      <div id="secList"></div>
      <div class="dev-sep"></div>
      <div class="dev-section-row"><span class="dev-section-title">Adicionar nova seção</span></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px" id="secTemplates">
        <button class="dev-btn ghost" data-tpl="kpi-extra">+ KPI Card extra</button>
        <button class="dev-btn ghost" data-tpl="releases-grid">+ Grid de Releases</button>
        <button class="dev-btn ghost" data-tpl="po-grid-2x2">+ PO Grid 2×2</button>
        <button class="dev-btn ghost" data-tpl="po-grid-3x3">+ PO Grid 3×3</button>
        <button class="dev-btn ghost" data-tpl="texto-livre">+ Seção texto livre</button>
      </div>
      <div class="dev-sep"></div>
      <div class="dev-section-row"><span class="dev-section-title">Layout KPI Strip</span></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
        <button class="dev-btn ghost" data-kpi-cols="3">3 por linha</button>
        <button class="dev-btn ghost" data-kpi-cols="4">4 por linha</button>
        <button class="dev-btn ghost" data-kpi-cols="5">5 por linha (padrão)</button>
        <button class="dev-btn ghost" data-kpi-cols="6">6 por linha</button>
      </div>
      <div class="dev-sep"></div>
      <div class="dev-section-row"><span class="dev-section-title">Layout PO Grid</span></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
        <button class="dev-btn ghost" data-po-cols="1">1 coluna</button>
        <button class="dev-btn ghost" data-po-cols="2">2 colunas</button>
        <button class="dev-btn ghost" data-po-cols="3">3 colunas (padrão)</button>
        <button class="dev-btn ghost" data-po-cols="4">4 colunas</button>
      </div>`;

    // Lista de seções com toggle
    const list = pane.querySelector('#secList');
    list.innerHTML = sections.map((sec, i) => {
      const title = sec.querySelector('.sec-hd-title')?.textContent || `Seção ${i+1}`;
      const visible = sec.style.display !== 'none';
      return `<div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--g100)">
        <label class="dev-toggle">
          <input type="checkbox" data-sec-idx="${i}" ${visible ? 'checked' : ''}>
          <span class="dev-toggle-track"></span>
        </label>
        <span style="font-size:.78rem;color:var(--g700);flex:1">${title}</span>
        <button class="dev-btn danger" style="font-size:.65rem;padding:0 8px;height:24px" data-sec-remove="${i}">✕ Remover</button>
      </div>`;
    }).join('');

    // Toggle visibilidade
    list.querySelectorAll('[data-sec-idx]').forEach(chk => {
      chk.addEventListener('change', () => {
        sections[parseInt(chk.dataset.secIdx)].style.display = chk.checked ? '' : 'none';
      });
    });

    // Remover seção
    list.querySelectorAll('[data-sec-remove]').forEach(btn => {
      btn.addEventListener('click', () => {
        devConfirm('Remover esta seção permanentemente?', () => {
          sections[parseInt(btn.dataset.secRemove)].remove();
          _activateTab('secoes');
        });
      });
    });

    // Templates de nova seção
    pane.querySelector('#secTemplates').addEventListener('click', e => {
      const tpl = e.target.dataset.tpl; if (!tpl) return;
      const body = document.querySelector('.blt-body'); if (!body) return;
      const sec = document.createElement('section');
      sec.className = 'reveal visible';
      if (tpl === 'kpi-extra') {
        sec.innerHTML = `<div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">Novo KPI</div></div></div>
          <div class="kpi-strip">
            <div class="kpi-wrapper">
              <div class="kpi-card green"><div class="kpi-lbl">Novo Indicador</div><div class="kpi-val">—</div><div class="kpi-delta">→ vs mês anterior</div></div>
              <div class="kpi-card-blw"><div class="analysis-box-label">Análise</div><div class="analysis-box-text">Análise do indicador.</div></div>
            </div>
          </div>`;
      } else if (tpl === 'releases-grid') {
        sec.innerHTML = `<div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">Novas Releases</div></div></div>
          <div class="release-grid">
            <div class="release-card new"><span class="release-badge">Novo</span><div class="release-name">Novo Ativo</div><div class="release-ver">Release 1</div></div>
          </div>`;
      } else if (tpl === 'po-grid-2x2') {
        sec.innerHTML = `<div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">Entregas do Time</div></div></div>
          <div class="po-grid" style="grid-template-columns:repeat(2,1fr)">
            ${[1,2,3,4].map(n=>`<div class="po-card"><div class="po-card-head"><div><div class="po-name">PO ${n}</div><div class="po-role">Cargo</div></div></div>
              <div class="po-items"><div class="po-item"><div class="po-item-dot"></div><div class="po-item-content"><span class="po-item-tag tag-struct">Estruturante</span><div class="po-item-title">Título</div><div class="po-item-desc">Descrição.</div></div></div></div></div>`).join('')}
          </div>`;
      } else if (tpl === 'po-grid-3x3') {
        sec.innerHTML = `<div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">Entregas do Time</div></div></div>
          <div class="po-grid" style="grid-template-columns:repeat(3,1fr)">
            ${[1,2,3,4,5,6,7,8,9].map(n=>`<div class="po-card"><div class="po-card-head"><div><div class="po-name">PO ${n}</div><div class="po-role">Cargo</div></div></div>
              <div class="po-items"><div class="po-item"><div class="po-item-dot"></div><div class="po-item-content"><span class="po-item-tag tag-struct">Estruturante</span><div class="po-item-title">Título ${n}</div><div class="po-item-desc">Descrição.</div></div></div></div></div>`).join('')}
          </div>`;
      } else if (tpl === 'texto-livre') {
        sec.innerHTML = `<div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">Nova Seção</div></div></div>
          <div class="analysis-box-text" style="padding:16px">Clique em "PO Cards" ou "Releases" no Dev View para editar o conteúdo desta seção após ela ser criada.</div>`;
      }
      body.appendChild(sec);
      DevLog.info('devview.secoes', `Seção "${tpl}" adicionada`);
      _activateTab('secoes');
    });

    // KPI cols
    pane.querySelectorAll('[data-kpi-cols]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (kpiStrip) kpiStrip.style.gridTemplateColumns = `repeat(${btn.dataset.kpiCols}, 1fr)`;
      });
    });

    // PO cols
    pane.querySelectorAll('[data-po-cols]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (poGrid) poGrid.style.gridTemplateColumns = `repeat(${btn.dataset.poCols}, 1fr)`;
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA KPI — editar valores e adicionar KPI cards
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabKpi(pane) {
    const kpis = window.D.kpis, anas = window.D.analises;
    const fields = [
      ['score','Score'],['score_delta','Score Delta'],
      ['prod_ativos','Ativos'],['prod_entregues','Entregues'],['prod_delta','Prod Delta'],
      ['tempo','Tempo (dias)'],['tempo_delta','Tempo Delta'],
      ['chamados','Chamados %'],['chamados_delta','Chamados Delta'],
      ['chamados_aberto','Cham. Fora SLA'],['chamados_aberto_delta','Cham. F/SLA Δ'],
      ['dias_abertos_chamados','Dias Cham.'],['dias_abertos_chamados_delta','Dias Cham. Δ'],
    ];
    const anaFields = [
      ['score','Score'],['prod','Produtos'],['tempo','Tempo'],
      ['chamados','Chamados'],['chamados_aberto','Cham. Fora SLA'],
      ['dias_abertos_chamados','Dias Chamados'],['scores_atenc','Scores Atenção'],
      ['causas_conc','Causas — Conc.'],['causas_soluc','Causas — Soluc.'],
      ['tabela_crit','Tabela — Crit.'],['tabela_cham','Tabela — Cham.'],['tabela_extr','Tabela — Extr.']
    ];

    pane.innerHTML = `
      <div class="dev-section-row">
        <span class="dev-section-title">KPIs Executivos</span>
        <button class="dev-btn primary" id="addKpiCard">+ KPI Card</button>
      </div>
      <div class="dev-sep"></div>
      <div class="dev-field-label" style="margin-bottom:6px">Cores dos Cards</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        ${colorPicker('Cards verdes (--kpi-green)','--kpi-green')}
        ${colorPicker('Cards azuis (--kpi-blue)','--kpi-blue')}
      </div>
      <div class="dev-sep"></div>
      ${mkAccordion('Valores KPI', fields.map(([k,lbl]) => `
        <div class="dev-field">
          <div class="dev-field-label">${lbl}</div>
          <input class="dev-field-input" data-kkey="${k}" value="${kpis[k]||''}" style="width:100%">
        </div>`).join(''))}
      <div style="margin-top:6px"></div>
      ${mkAccordion('Análises', anaFields.map(([k,lbl]) => `
        <div class="dev-field">
          <div class="dev-field-label">${lbl}</div>
          <textarea class="dev-field-input" data-akey="${k}" rows="3" style="width:100%;resize:vertical">${anas[k]||''}</textarea>
        </div>`).join(''))}`;

    bindAccordions(pane);
    bindColorPickers(pane);

    pane.querySelectorAll('[data-kkey]').forEach(inp => {
      inp.addEventListener('change', () => {
        History.capture();
        const k = inp.dataset.kkey, v = inp.value;
        window.D.kpis[k] = isNaN(v) || v==='' ? v : parseFloat(v);
        if (typeof setEl === 'function') setEl('kpi-' + k.replace(/_/g,'-'), window.D.kpis[k]);
      });
    });
    pane.querySelectorAll('[data-akey]').forEach(ta => {
      ta.addEventListener('change', () => {
        History.capture();
        window.D.analises[ta.dataset.akey] = ta.value;
        if (typeof setEl === 'function') setEl('ana-' + ta.dataset.akey.replace(/_/g,'-'), ta.value);
      });
    });

    pane.querySelector('#addKpiCard').addEventListener('click', () => {
      const strip = document.querySelector('.kpi-strip'); if (!strip) return;
      const svgInfo = `<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
      const uid = 'kpi-custom-' + Date.now();
      const wrapper = document.createElement('div'); wrapper.className = 'kpi-wrapper';
      wrapper.innerHTML = `
        <div class="kpi-card green">
          <div class="kpi-lbl">Novo Indicador</div>
          <div class="kpi-val" id="${uid}">—</div>
          <div class="kpi-delta" id="${uid}-delta">→ vs mês anterior</div>
        </div>
        <div class="kpi-card-blw">
          <div class="analysis-box-label">${svgInfo} Análise</div>
          <div class="analysis-box-text" id="ana-${uid}">Análise do novo indicador.</div>
        </div>`;
      strip.appendChild(wrapper);
      DevLog.info('devview.kpi', 'KPI card adicionado');
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA PERÍODO
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabPeriodo(pane) {
    pane.innerHTML = `
      <div class="dev-section-row"><span class="dev-section-title">Período e Referência</span></div>
      <div class="dev-sep"></div>
      <div class="dev-field"><div class="dev-field-label">Período</div>
        <input class="dev-field-input" id="dpPeriodo" value="${window.D.periodo||''}" style="width:100%"></div>
      <div class="dev-field"><div class="dev-field-label">Data de geração</div>
        <input class="dev-field-input" id="dpGerado" value="${window.D.gerado||''}" style="width:100%"></div>
      <div class="dev-field"><div class="dev-field-label">Meses (5 rótulos, mais recente primeiro)</div>
        <textarea class="dev-field-input" id="dpMeses" rows="5" style="width:100%;resize:none">${(window.D.meses||[]).join('\n')}</textarea></div>
      <button class="dev-btn primary" id="dpSave" style="margin-top:4px">Aplicar</button>`;
    pane.querySelector('#dpSave').onclick = () => {
      History.capture();
      window.D.periodo = pane.querySelector('#dpPeriodo').value.trim();
      window.D.gerado  = pane.querySelector('#dpGerado').value.trim();
      window.D.meses   = pane.querySelector('#dpMeses').value.split('\n').map(s=>s.trim()).filter(Boolean);
      if (typeof applyData === 'function') applyData();
    };
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA TABELA — produtos com accordion funcional
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabTabela(pane) {
    function render() {
      const meses = window.D.meses || [];
      pane.innerHTML = `
        <div class="dev-section-row">
          <span class="dev-section-title">Tabela de Scores</span>
          <button class="dev-btn primary" id="dtAdd">+ Produto</button>
        </div>
        <div class="dev-sep"></div>
        <div class="dev-field-label" style="margin-bottom:8px">Cores da tabela</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          ${colorPicker('Score alto (≥95%) (--score-great-bg)','--score-great-bg')}
          ${colorPicker('Score médio (≥85%) (--score-warn-bg)','--score-warn-bg')}
          ${colorPicker('Score baixo (<85%) (--score-bad-bg)','--score-bad-bg')}
        </div>
        <div class="dev-sep"></div>
        <div id="dtList"></div>`;

      bindColorPickers(pane);
      const list = pane.querySelector('#dtList');

      if (!window.D.produtos || !window.D.produtos.length) {
        list.innerHTML = '<div class="dev-empty">Nenhum produto cadastrado.</div>';
      } else {
        list.innerHTML = window.D.produtos.map((p, i) => mkAccordion(
          `<span style="font-size:.72rem">${p.nome||'(sem nome)'}</span>`,
          `<div class="dev-field"><div class="dev-field-label">Nome</div><input class="dev-field-input" data-p="${i}" data-field="nome" value="${p.nome||''}" style="width:100%"></div>
           <div class="dev-field"><div class="dev-field-label">Gestor</div><input class="dev-field-input" data-p="${i}" data-field="gestor" value="${p.gestor||''}" style="width:100%"></div>
           <div style="display:flex;gap:8px;flex-wrap:wrap">
             <div class="dev-field"><div class="dev-field-label">Dimensão</div><input class="dev-field-input" data-p="${i}" data-field="dim" value="${p.dim||''}" style="width:160px"></div>
             <div class="dev-field"><div class="dev-field-label">Chamados</div><input class="dev-field-input" type="number" data-p="${i}" data-field="chamados" value="${p.chamados||0}" style="width:90px"></div>
             <div class="dev-field"><div class="dev-field-label">Criticidade</div>
               <select class="dev-field-select" data-p="${i}" data-field="crit" style="width:130px">
                 <option value="1" ${p.crit===1?'selected':''}>1 — Baixa</option>
                 <option value="2" ${p.crit===2?'selected':''}>2 — Média</option>
                 <option value="3" ${p.crit===3?'selected':''}>3 — Alta</option>
               </select></div>
           </div>
           <div class="dev-field"><div class="dev-field-label">Scores — ${meses.join(', ')}</div>
             <div style="display:flex;gap:6px;flex-wrap:wrap">
               ${(p.scores||[0,0,0,0,0]).map((s,si)=>`<div><div style="font-size:.6rem;color:var(--g500);text-align:center">${meses[si]||si}</div><input class="dev-field-input" type="number" step="0.01" data-p="${i}" data-score="${si}" value="${s||0}" style="width:68px"></div>`).join('')}
             </div></div>`,
          `<button class="dev-btn danger" style="font-size:.65rem;padding:0 8px;height:24px" data-delprod="${i}">✕</button>`
        )).join('');
        bindAccordions(list);

        list.querySelectorAll('[data-delprod]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation();
            devConfirm('Remover este produto?', () => { History.capture(); window.D.produtos.splice(parseInt(btn.dataset.delprod),1); if (typeof buildScoreTable==='function') buildScoreTable(); render(); });
          });
        });
        list.querySelectorAll('[data-p][data-field]').forEach(inp => {
          inp.addEventListener('change', () => { History.capture(); const i=parseInt(inp.dataset.p),f=inp.dataset.field; window.D.produtos[i][f]=f==='crit'||f==='chamados'?parseInt(inp.value):inp.value; if(typeof buildScoreTable==='function') buildScoreTable(); });
        });
        list.querySelectorAll('[data-p][data-score]').forEach(inp => {
          inp.addEventListener('change', () => { History.capture(); window.D.produtos[parseInt(inp.dataset.p)].scores[parseInt(inp.dataset.score)]=parseFloat(inp.value)||0; if(typeof buildScoreTable==='function') buildScoreTable(); });
        });
      }

      pane.querySelector('#dtAdd').onclick = () => { History.capture(); window.D.produtos.push({nome:'Novo Produto',gestor:'',scores:[0,0,0,0,0],chamados:0,dim:'Disponibilidade',crit:1}); if(typeof buildScoreTable==='function') buildScoreTable(); render(); };
    }
    render();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA CAUSAS
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabCausas(pane) {
    // Cores do gráfico de causas são os azuis do array em charts.js
    const causaColors = ['#0c2a6e','#1f4a8a','#3567a5','#4f83bf','#6a9fd6','#87b9ea','#90c0ee','#99c7f1'];
    function render() {
      const c = window.D.causas;
      pane.innerHTML = `
        <div class="dev-section-row"><span class="dev-section-title">Causas de Chamados</span>
          <button class="dev-btn primary" id="dcAdd">+ Causa</button></div>
        <div class="dev-sep"></div>
        <div class="dev-field-label" style="margin-bottom:6px">Cores das barras</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          ${causaColors.map((cor,i)=>colorPicker(`Barra ${i+1}`, `--causa-color-${i}`)).join('')}
        </div>
        <div class="dev-sep"></div>
        <table class="dev-table" style="width:100%">
          <thead><tr><th>Label</th><th style="width:80px">Qtd</th><th style="width:36px"></th></tr></thead>
          <tbody>${c.labels.map((lbl,i)=>`<tr>
            <td><input class="dev-table-input" data-causalbl="${i}" value="${lbl}"></td>
            <td><input class="dev-table-input" type="number" data-causaval="${i}" value="${c.data[i]||0}"></td>
            <td><button class="dev-btn danger" style="font-size:.6rem;padding:0 6px;height:22px" data-delcausa="${i}">✕</button></td>
          </tr>`).join('')}</tbody>
        </table>`;

      bindColorPickers(pane);
      pane.querySelectorAll('[data-causalbl]').forEach(inp => inp.addEventListener('change',()=>{ History.capture(); window.D.causas.labels[parseInt(inp.dataset.causalbl)]=inp.value; if(typeof buildCausasChart==='function') buildCausasChart(); }));
      pane.querySelectorAll('[data-causaval]').forEach(inp => inp.addEventListener('change',()=>{ History.capture(); window.D.causas.data[parseInt(inp.dataset.causaval)]=parseInt(inp.value)||0; if(typeof buildCausasChart==='function') buildCausasChart(); }));
      pane.querySelectorAll('[data-delcausa]').forEach(btn => btn.addEventListener('click',()=>{ History.capture(); const i=parseInt(btn.dataset.delcausa); window.D.causas.labels.splice(i,1); window.D.causas.data.splice(i,1); if(typeof buildCausasChart==='function') buildCausasChart(); render(); }));
      pane.querySelector('#dcAdd').onclick = () => { History.capture(); window.D.causas.labels.push('Nova Causa'); window.D.causas.data.push(0); render(); };
    }
    render();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA PO CARDS — accordion funcional com conteúdo visível
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabPo(pane) {
    function render() {
      const cards = Array.from(document.querySelectorAll('.po-card'));
      pane.innerHTML = `
        <div class="dev-section-row"><span class="dev-section-title">PO Cards</span>
          <button class="dev-btn primary" id="poAddCard">+ Card</button></div>
        <div class="dev-sep"></div>
        <div class="dev-field-label" style="margin-bottom:6px">Cores dos Cards</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          ${colorPicker('Fundo do card (--po-bg)','--po-bg')}
          ${colorPicker('Tag Estruturante (--tag-struct-bg)','--tag-struct-bg')}
          ${colorPicker('Tag Eficiência (--tag-ops-bg)','--tag-ops-bg')}
        </div>
        <div class="dev-sep"></div>
        <div id="poList"></div>`;

      bindColorPickers(pane);
      const list = pane.querySelector('#poList');

      if (!cards.length) { list.innerHTML = '<div class="dev-empty">Nenhum .po-card encontrado.</div>'; }
      else {
        list.innerHTML = cards.map((card, ci) => {
          const name  = card.querySelector('.po-name')?.textContent  || '';
          const role  = card.querySelector('.po-role')?.textContent  || '';
          const items = Array.from(card.querySelectorAll('.po-item'));
          const itemsHtml = items.map((item, ii) => {
            const t   = item.querySelector('.po-item-tag')?.textContent   || '';
            const ttl = item.querySelector('.po-item-title')?.textContent || '';
            const dsc = item.querySelector('.po-item-desc')?.innerHTML    || '';
            return `<div style="border:1px solid var(--g200);border-radius:6px;padding:10px;margin-bottom:8px">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <strong style="font-size:.72rem">Item ${ii+1}</strong>
                <button class="dev-btn danger" style="font-size:.6rem;padding:0 6px;height:22px" data-po="${ci}" data-delitem="${ii}">✕ item</button>
              </div>
              <div class="dev-field"><div class="dev-field-label">Tag</div>
                <input class="dev-field-input" data-po="${ci}" data-item="${ii}" data-ifield="tag" value="${t}" style="width:140px"></div>
              <div class="dev-field"><div class="dev-field-label">Título</div>
                <input class="dev-field-input" data-po="${ci}" data-item="${ii}" data-ifield="title" value="${ttl}" style="width:100%"></div>
              <div class="dev-field"><div class="dev-field-label">Descrição</div>
                <textarea class="dev-field-input" data-po="${ci}" data-item="${ii}" data-ifield="desc" rows="3" style="width:100%;resize:vertical">${dsc}</textarea></div>
            </div>`;
          }).join('');

          return mkAccordion(
            `<span style="font-size:.74rem">${name||`Card ${ci+1}`}</span>`,
            `<div class="dev-field"><div class="dev-field-label">Nome</div><input class="dev-field-input" data-po="${ci}" data-field="name" value="${name}" style="width:100%"></div>
             <div class="dev-field"><div class="dev-field-label">Cargo</div><input class="dev-field-input" data-po="${ci}" data-field="role" value="${role}" style="width:100%"></div>
             <div class="dev-sep"></div>
             <div class="dev-section-row" style="margin-bottom:8px"><span class="dev-field-label">ITENS</span>
               <button class="dev-btn ghost" style="font-size:.68rem;height:24px;padding:0 8px" data-po="${ci}" data-additem="1">+ Item</button></div>
             ${itemsHtml}`,
            `<button class="dev-btn danger" style="font-size:.65rem;padding:0 8px;height:24px" data-po-del="${ci}">✕ card</button>`
          );
        }).join('');

        bindAccordions(list);

        // Editar nome/role do card
        list.querySelectorAll('[data-po][data-field]').forEach(inp => {
          inp.addEventListener('change', () => {
            History.capture();
            const card = cards[parseInt(inp.dataset.po)]; if (!card) return;
            const f = inp.dataset.field;
            if (f==='name') { const el=card.querySelector('.po-name'); if(el) el.textContent=inp.value; }
            if (f==='role') { const el=card.querySelector('.po-role'); if(el) el.textContent=inp.value; }
            render();
          });
        });

        // Editar itens
        list.querySelectorAll('[data-po][data-item][data-ifield]').forEach(inp => {
          inp.addEventListener('change', () => {
            History.capture();
            const card = cards[parseInt(inp.dataset.po)];
            const item = card?.querySelectorAll('.po-item')[parseInt(inp.dataset.item)];
            if (!item) return;
            const f = inp.dataset.ifield;
            if (f==='tag')   { const el=item.querySelector('.po-item-tag');   if(el) el.textContent=inp.value; }
            if (f==='title') { const el=item.querySelector('.po-item-title'); if(el) el.textContent=inp.value; }
            if (f==='desc')  { const el=item.querySelector('.po-item-desc');  if(el) el.innerHTML=inp.value;  }
          });
        });

        // Remover item
        list.querySelectorAll('[data-po][data-delitem]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation(); History.capture();
            const card = cards[parseInt(btn.dataset.po)];
            const items2 = card?.querySelectorAll('.po-item');
            if (items2 && items2[parseInt(btn.dataset.delitem)]) { items2[parseInt(btn.dataset.delitem)].remove(); render(); }
          });
        });

        // Adicionar item
        list.querySelectorAll('[data-additem]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation(); History.capture();
            const card = cards[parseInt(btn.dataset.po)];
            const itemsWrap = card?.querySelector('.po-items'); if (!itemsWrap) return;
            const newItem = document.createElement('div'); newItem.className = 'po-item';
            newItem.innerHTML = `<div class="po-item-dot"></div><div class="po-item-content">
              <span class="po-item-tag tag-struct">Estruturante</span>
              <div class="po-item-title">Novo item</div>
              <div class="po-item-desc">Descrição do novo item.</div>
            </div>`;
            itemsWrap.appendChild(newItem); render();
          });
        });

        // Remover card
        list.querySelectorAll('[data-po-del]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation();
            devConfirm('Remover este card?', () => { History.capture(); cards[parseInt(btn.dataset.poDel)].remove(); render(); });
          });
        });
      }

      pane.querySelector('#poAddCard').onclick = () => {
        const grid = document.querySelector('.po-grid'); if (!grid) return;
        const card = document.createElement('div'); card.className = 'po-card';
        card.innerHTML = `<div class="po-card-head"><div><div class="po-name">Novo PO</div><div class="po-role">Cargo</div></div></div>
          <div class="po-items"><div class="po-item"><div class="po-item-dot"></div><div class="po-item-content">
            <span class="po-item-tag tag-struct">Estruturante</span>
            <div class="po-item-title">Novo Item</div>
            <div class="po-item-desc">Descrição.</div>
          </div></div></div>`;
        grid.appendChild(card); History.capture(); render();
      };
    }
    render();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA RELEASES
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabReleases(pane) {
    function render() {
      const grids = Array.from(document.querySelectorAll('.release-grid'));
      pane.innerHTML = `
        <div class="dev-section-row"><span class="dev-section-title">Releases</span></div>
        <div class="dev-sep"></div>
        <div class="dev-field-label" style="margin-bottom:6px">Cores dos badges</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          ${colorPicker('Novo (--release-new-bg)','--release-new-bg')}
          ${colorPicker('Atualização (--release-upd-bg)','--release-upd-bg')}
          ${colorPicker('Removido (--release-rem-bg)','--release-rem-bg')}
        </div>
        <div class="dev-sep"></div>
        ${grids.map((grid, gi) => {
          const cards = Array.from(grid.querySelectorAll('.release-card'));
          return mkAccordion(`Grid ${gi+1} — ${cards.length} itens`,
            `<div id="rgCards-${gi}">
              ${cards.map((card,ci) => {
                const name = card.querySelector('.release-name')?.textContent || '';
                const ver  = card.querySelector('.release-ver')?.textContent  || '';
                const tipo = card.classList.contains('new')?'new':card.classList.contains('update')?'update':'remove';
                return `<div style="display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap">
                  <select class="dev-field-select" data-rg="${gi}" data-rc="${ci}" data-rfield="tipo" style="width:110px">
                    <option value="new"    ${tipo==='new'?'selected':''}>Novo</option>
                    <option value="update" ${tipo==='update'?'selected':''}>Atualização</option>
                    <option value="remove" ${tipo==='remove'?'selected':''}>Removido</option>
                  </select>
                  <input class="dev-field-input" data-rg="${gi}" data-rc="${ci}" data-rfield="name" value="${name}" style="flex:1;min-width:120px">
                  <input class="dev-field-input" data-rg="${gi}" data-rc="${ci}" data-rfield="ver" value="${ver}" style="width:80px">
                  <button class="dev-btn danger" style="font-size:.6rem;padding:0 6px;height:26px" data-rg="${gi}" data-rc="${ci}" data-rfield="del">✕</button>
                </div>`;
              }).join('')}
              <button class="dev-btn ghost" data-rg-add="${gi}" style="font-size:.72rem;margin-top:4px">+ Adicionar item</button>
            </div>`,
            `<button class="dev-btn danger" style="font-size:.65rem;padding:0 8px;height:24px" data-rg-del="${gi}">✕ Grid</button>`
          );
        }).join('')}`;

      bindAccordions(pane);
      bindColorPickers(pane);

      pane.querySelectorAll('[data-rg][data-rc][data-rfield]').forEach(inp => {
        inp.addEventListener('change', () => {
          History.capture();
          const gi=parseInt(inp.dataset.rg), ci=parseInt(inp.dataset.rc);
          const card = grids[gi]?.querySelectorAll('.release-card')[ci]; if (!card) return;
          const f = inp.dataset.rfield;
          const lblMap={new:'Novo',update:'Atualização',remove:'Removido'};
          if (f==='name') { const el=card.querySelector('.release-name'); if(el) el.textContent=inp.value; }
          if (f==='ver')  { const el=card.querySelector('.release-ver');  if(el) el.textContent=inp.value; }
          if (f==='tipo') {
            card.classList.remove('new','update','remove'); card.classList.add(inp.value);
            const badge=card.querySelector('.release-badge'); if(badge) badge.textContent=lblMap[inp.value]||inp.value;
          }
          if (f==='del')  { card.remove(); render(); }
        });
      });

      pane.querySelectorAll('[data-rg-add]').forEach(btn => {
        btn.addEventListener('click', e => { e.stopPropagation(); History.capture();
          const grid = grids[parseInt(btn.dataset.rgAdd)]; if (!grid) return;
          const card = document.createElement('div'); card.className='release-card new';
          card.innerHTML='<span class="release-badge">Novo</span><div class="release-name">Novo Ativo</div><div class="release-ver">Release 1</div>';
          grid.appendChild(card); render();
        });
      });

      pane.querySelectorAll('[data-rg-del]').forEach(btn => {
        btn.addEventListener('click', e => { e.stopPropagation();
          devConfirm('Remover todo este grid de releases?', () => { History.capture(); grids[parseInt(btn.dataset.rgDel)].remove(); render(); });
        });
      });
    }
    render();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA GRÁFICOS — editor completo + legenda em bolas
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabGraficos(pane) {
    function renderList() {
      const graficos = window.D.graficos || [];
      pane.innerHTML = `
        <div class="dev-section-row"><span class="dev-section-title">Gráficos Customizados</span>
          <button class="dev-btn primary" id="gcNew">+ Novo Gráfico</button></div>
        <div class="dev-sep"></div>
        <div id="gcList"></div>`;

      const list = pane.querySelector('#gcList');
      if (!graficos.length) { list.innerHTML='<div class="dev-empty">Nenhum gráfico customizado.</div>'; }
      else {
        list.innerHTML = graficos.map((g,i) => mkAccordion(
          `<span style="font-size:.74rem">${g.titulo||'Gráfico '+(i+1)}</span>`,
          `<div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
            <button class="dev-btn ghost" style="font-size:.72rem" data-gc-edit="${i}">✏ Editar</button>
            <button class="dev-btn ghost" style="font-size:.72rem" data-gc-refresh="${i}">↻ Re-renderizar</button>
            <button class="dev-btn danger" style="font-size:.72rem" data-gc-del="${i}">✕ Remover</button>
          </div>`
        )).join('');
        bindAccordions(list);

        list.querySelectorAll('[data-gc-edit]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation(); _openChartModal(parseInt(btn.dataset.gcEdit), pane, renderList); });
        });
        list.querySelectorAll('[data-gc-refresh]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation(); _renderChartDOM(window.D.graficos[parseInt(btn.dataset.gcRefresh)], parseInt(btn.dataset.gcRefresh)); });
        });
        list.querySelectorAll('[data-gc-del]').forEach(btn => {
          btn.addEventListener('click', e => { e.stopPropagation();
            devConfirm('Remover este gráfico?', () => {
              const sec = document.getElementById('devChart-'+btn.dataset.gcDel);
              if (sec) sec.remove();
              window.D.graficos.splice(parseInt(btn.dataset.gcDel),1);
              renderList();
            });
          });
        });
      }

      pane.querySelector('#gcNew').onclick = () => _openChartModal(null, pane, renderList);
    }
    renderList();
  }

  function _openChartModal(idx, pane, onSave) {
    const isNew = idx === null;
    const cfg = isNew
      ? { titulo:'', tipo:'bar', labels:['A','B','C'], series:[{nome:'Série 1',dados:[10,20,30],cor:'#3B6BF5'}], altura:300, datalabels:true, legenda:true }
      : JSON.parse(JSON.stringify(window.D.graficos[idx]));

    const ov = document.createElement('div'); ov.className='dev-modal-overlay';
    ov.innerHTML = `
      <div class="dev-modal" style="width:560px;max-height:85vh;display:flex;flex-direction:column">
        <div class="dev-modal-head">
          <span>${isNew?'Novo Gráfico':'Editar Gráfico'}</span>
          <button class="dev-btn ghost" id="gcClose">✕</button>
        </div>
        <div style="display:flex;border-bottom:1px solid var(--g200)">
          <button class="dev-modal-tab act" data-subtab="dados">Dados</button>
          <button class="dev-modal-tab" data-subtab="series">Séries</button>
          <button class="dev-modal-tab" data-subtab="visual">Visual</button>
        </div>
        <div class="dev-modal-body" style="overflow-y:auto;flex:1">
          <div id="gcPane-dados"></div>
          <div id="gcPane-series" style="display:none"></div>
          <div id="gcPane-visual" style="display:none"></div>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--g200);display:flex;justify-content:flex-end;gap:8px">
          <button class="dev-btn ghost" id="gcCancel">Cancelar</button>
          <button class="dev-btn primary" id="gcSave">Aplicar</button>
        </div>
      </div>`;
    document.body.appendChild(ov);

    _fillDadosPane(ov.querySelector('#gcPane-dados'), cfg);
    _fillSeriesPane(ov.querySelector('#gcPane-series'), cfg);
    _fillVisualPane(ov.querySelector('#gcPane-visual'), cfg);

    ov.querySelectorAll('.dev-modal-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        ov.querySelectorAll('.dev-modal-tab').forEach(b=>b.classList.remove('act')); btn.classList.add('act');
        ov.querySelectorAll('[id^="gcPane-"]').forEach(p=>p.style.display='none');
        ov.querySelector('#gcPane-'+btn.dataset.subtab).style.display='';
      });
    });

    ov.querySelector('#gcClose').onclick = ov.querySelector('#gcCancel').onclick = () => ov.remove();
    ov.querySelector('#gcSave').onclick = () => {
      _collectChart(cfg, ov);
      if (!window.D.graficos) window.D.graficos = [];
      if (isNew) { window.D.graficos.push(cfg); _renderChartDOM(cfg, window.D.graficos.length-1); }
      else        { Object.assign(window.D.graficos[idx], cfg); _renderChartDOM(cfg, idx); }
      ov.remove(); onSave();
      DevLog.info('devcharts', `Gráfico "${cfg.titulo}" ${isNew?'criado':'atualizado'}`);
    };
  }

  function _fillDadosPane(pane, cfg) {
    pane.innerHTML = `
      <div class="dev-field"><div class="dev-field-label">Título</div>
        <input class="dev-field-input" id="gcTitulo" value="${cfg.titulo||''}" style="width:100%"></div>
      <div class="dev-field"><div class="dev-field-label">Tipo</div>
        <select class="dev-field-select" id="gcTipo" style="width:100%">
          <option value="bar"          ${cfg.tipo==='bar'?'selected':''}>Barras verticais</option>
          <option value="horizontalBar"${cfg.tipo==='horizontalBar'?'selected':''}>Barras horizontais</option>
          <option value="line"         ${cfg.tipo==='line'?'selected':''}>Linha</option>
          <option value="doughnut"     ${cfg.tipo==='doughnut'?'selected':''}>Rosca (doughnut)</option>
        </select></div>
      <div class="dev-field"><div class="dev-field-label">Labels (um por linha)</div>
        <textarea class="dev-field-input" id="gcLabels" rows="5" style="width:100%;resize:vertical">${(cfg.labels||[]).join('\n')}</textarea></div>`;
  }

  function _fillSeriesPane(pane, cfg) {
    function render() {
      pane.innerHTML = `
        <div class="dev-section-row" style="margin-bottom:8px">
          <span class="dev-field-label">SÉRIES</span>
          <button class="dev-btn ghost" id="gcAddSeries" style="font-size:.7rem">+ Série</button>
        </div>
        ${(cfg.series||[]).map((s,si)=>`
          <div style="border:1px solid var(--g200);border-radius:6px;padding:10px;margin-bottom:8px">
            <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center">
              <input class="dev-field-input" data-snome="${si}" value="${s.nome||''}" placeholder="Nome da série" style="flex:1">
              <div style="width:28px;height:28px;border-radius:4px;border:1px solid var(--g300);background:${s.cor||'#3B6BF5'};flex-shrink:0" id="sc-swatch-${si}"></div>
              <input class="dev-field-input" data-scor="${si}" value="${s.cor||'#3B6BF5'}" style="width:90px" placeholder="#hex">
              <button class="dev-btn danger" style="font-size:.65rem;padding:0 8px;height:26px" data-sdel="${si}">✕</button>
            </div>
            <div class="dev-field-label">Valores (um por linha)</div>
            <textarea class="dev-field-input" data-sdados="${si}" rows="4" style="width:100%;resize:vertical">${(s.dados||[]).join('\n')}</textarea>
          </div>`).join('')}`;

      pane.querySelectorAll('[data-scor]').forEach(inp => {
        inp.addEventListener('input', () => {
          const sw = pane.querySelector('#sc-swatch-' + inp.dataset.scor);
          if (sw) sw.style.background = inp.value;
        });
      });
      pane.querySelector('#gcAddSeries').onclick = () => { cfg.series.push({nome:'Nova Série',dados:(cfg.labels||[]).map(()=>0),cor:'#3B6BF5'}); render(); };
      pane.querySelectorAll('[data-sdel]').forEach(btn => btn.addEventListener('click',()=>{ cfg.series.splice(parseInt(btn.dataset.sdel),1); render(); }));
    }
    render();
    Object.defineProperty(pane, '_refreshSeries', { value: render, writable: true, configurable: true });
  }

  function _fillVisualPane(pane, cfg) {
    pane.innerHTML = `
      <div class="dev-field"><div class="dev-field-label">Altura (px)</div>
        <input class="dev-field-input" type="number" id="gcAltura" value="${cfg.altura||300}" style="width:110px"></div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px">
        <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer">
          <input type="checkbox" id="gcDatalabels" ${cfg.datalabels?'checked':''}> Mostrar valores nas barras
        </label>
        <label style="display:flex;align-items:center;gap:6px;font-size:.78rem;cursor:pointer">
          <input type="checkbox" id="gcLegenda" ${cfg.legenda?'checked':''}> Legenda em bolas (abaixo)
        </label>
      </div>
      <div class="dev-empty" style="font-size:.72rem;padding:8px;background:var(--g100);border-radius:6px;text-align:left">
        A legenda sempre aparece abaixo do gráfico como círculos coloridos — padrão do boletim.
      </div>`;
  }

  function _collectChart(cfg, ov) {
    cfg.titulo  = ov.querySelector('#gcTitulo').value.trim();
    cfg.tipo    = ov.querySelector('#gcTipo').value;
    cfg.labels  = ov.querySelector('#gcLabels').value.split('\n').map(s=>s.trim()).filter(Boolean);
    cfg.altura  = parseInt(ov.querySelector('#gcAltura').value)||300;
    cfg.datalabels = ov.querySelector('#gcDatalabels').checked;
    cfg.legenda    = ov.querySelector('#gcLegenda').checked;
    ov.querySelectorAll('[data-snome]').forEach(inp => { if(cfg.series[parseInt(inp.dataset.snome)]) cfg.series[parseInt(inp.dataset.snome)].nome=inp.value; });
    ov.querySelectorAll('[data-sdados]').forEach(ta  => { if(cfg.series[parseInt(ta.dataset.sdados)])  cfg.series[parseInt(ta.dataset.sdados)].dados=ta.value.split('\n').map(s=>parseFloat(s)||0); });
    ov.querySelectorAll('[data-scor]').forEach(inp  => { if(cfg.series[parseInt(inp.dataset.scor)])   cfg.series[parseInt(inp.dataset.scor)].cor=inp.value; });
  }

  function _renderChartDOM(cfg, idx) {
    const body = document.querySelector('.blt-body'); if (!body) return;
    const secId = 'devChart-' + idx, canvasId = 'cDevChart-' + idx;
    let sec = document.getElementById(secId);
    if (!sec) { sec = document.createElement('section'); sec.id=secId; sec.className='reveal visible'; body.appendChild(sec); }

    sec.innerHTML = `
      <div class="sec-hd"><div class="sec-hd-bar"></div><div><div class="sec-hd-title">${cfg.titulo||'Gráfico'}</div></div></div>
      <div class="chart-card">
        <div style="position:relative;height:${cfg.altura||300}px"><canvas id="${canvasId}"></canvas></div>
        <div id="leg-${canvasId}" class="legend-row" style="margin-top:10px;flex-wrap:wrap;gap:8px"></div>
      </div>`;

    try {
      if (window.chartInstances?.[canvasId]) { window.chartInstances[canvasId].destroy(); delete window.chartInstances[canvasId]; }
      const ctx  = document.getElementById(canvasId)?.getContext('2d'); if (!ctx) return;
      const isH  = cfg.tipo === 'horizontalBar';
      const type = (isH || cfg.tipo==='bar') ? 'bar' : cfg.tipo;
      const isDoughnut = cfg.tipo === 'doughnut';

      const datasets = (cfg.series||[]).map(s => ({
        label: s.nome||'',
        data:  s.dados||[],
        backgroundColor: isDoughnut ? (cfg.series||[]).map(x=>x.cor||'#3B6BF5') : (s.cor||'#3B6BF5'),
        borderRadius: isDoughnut ? 0 : 4
      }));

      const chartCfg = {
        type,
        data: { labels: cfg.labels||[], datasets },
        options: {
          indexAxis: isH ? 'y' : 'x',
          responsive: true, maintainAspectRatio: false,
          layout: { padding: { right: cfg.datalabels ? 48 : 8 } },
          plugins: {
            legend: { display: false }, // legenda manual em bolas abaixo
            datalabels: cfg.datalabels
              ? { anchor:'end', align:'end', font:{size:10,weight:'700',family:"'DM Sans',sans-serif"}, color: typeof textColor==='function'?textColor():'#5C6180', padding:{left:4} }
              : { display: false }
          },
          scales: isDoughnut ? {} : {
            x: { ticks:{font:typeof baseFont!=='undefined'?baseFont:{family:"'DM Sans',sans-serif",size:11}}, grid:{color:typeof gridColor==='function'?gridColor():'#E2E4EA'} },
            y: { ticks:{font:typeof baseFont!=='undefined'?baseFont:{family:"'DM Sans',sans-serif",size:11}}, grid:{display:false} }
          }
        }
      };

      const inst = new Chart(ctx, chartCfg);
      if (window.chartInstances) window.chartInstances[canvasId] = inst;

      // Legenda em bolas abaixo do gráfico
      const legEl = document.getElementById('leg-' + canvasId);
      if (legEl) {
        const legendSeries = isDoughnut
          ? (cfg.labels||[]).map((lbl,i)=>({ nome:lbl, cor:(cfg.series[0]?.dados||[])[i]?((cfg.series||[]).map(x=>x.cor))[i]||'#3B6BF5':'#3B6BF5' }))
          : (cfg.series||[]).map(s=>({ nome:s.nome, cor:s.cor||'#3B6BF5' }));
        legEl.innerHTML = legendSeries.map(s =>
          `<div class="legend-item"><div class="legend-dot" style="background:${s.cor}"></div><span>${s.nome}</span></div>`
        ).join('');
      }
    } catch(err) { DevLog.capture('devcharts.render', err); }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA APARÊNCIA
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabAparencia(pane) {
    const root = document.documentElement;
    const cs   = getComputedStyle(root);
    const get  = v => root.style.getPropertyValue(v)||cs.getPropertyValue(v).trim()||'';
    pane.innerHTML = `
      <div class="dev-section-row"><span class="dev-section-title">Aparência Global</span></div>
      <div class="dev-sep"></div>
      <div class="dev-field-label" style="margin-bottom:8px">Cores principais</div>
      ${colorPicker('Cor principal (--pr)', '--pr')}
      ${colorPicker('Cor escura (--pr-dk)', '--pr-dk')}
      <button class="dev-btn ghost" id="resetBradesco" style="font-size:.72rem;margin-bottom:12px">↩ Restaurar Bradesco (#c01137 / #8C0F3B)</button>
      <div class="dev-sep"></div>
      <div class="dev-field-label" style="margin-bottom:8px">Tipografia</div>
      <div class="dev-field"><div class="dev-field-label">Display (--font-display)</div>
        <select class="dev-field-select" id="selFD" style="width:100%">
          ${["'Syne', sans-serif","'Inter', sans-serif","'Roboto', sans-serif","'DM Sans', sans-serif"].map(f=>`<option ${get('--font-display')===f?'selected':''}>${f}</option>`).join('')}
        </select></div>
      <div class="dev-field"><div class="dev-field-label">Body (--font-body)</div>
        <select class="dev-field-select" id="selFB" style="width:100%">
          ${["'DM Sans', sans-serif","'Inter', sans-serif","'Roboto', sans-serif","'Syne', sans-serif"].map(f=>`<option ${get('--font-body')===f?'selected':''}>${f}</option>`).join('')}
        </select></div>
      <div class="dev-sep"></div>
      <div class="dev-field-label" style="margin-bottom:8px">Layout</div>
      <label style="display:flex;align-items:center;gap:8px;font-size:.78rem;cursor:pointer;margin-bottom:8px">
        <input type="checkbox" id="chkLogo" checked> Mostrar logo sidebar
      </label>
      <label style="display:flex;align-items:center;gap:8px;font-size:.78rem;cursor:pointer">
        <input type="checkbox" id="chkDark" ${root.dataset.theme==='dark'?'checked':''}> Tema escuro
      </label>`;

    bindColorPickers(pane);
    pane.querySelector('#resetBradesco').onclick = () => { root.style.setProperty('--pr','#c01137'); root.style.setProperty('--pr-dk','#8C0F3B'); DevLog.info('devview','Cores Bradesco restauradas'); };
    pane.querySelector('#selFD').onchange = e => root.style.setProperty('--font-display', e.target.value);
    pane.querySelector('#selFB').onchange = e => root.style.setProperty('--font-body', e.target.value);
    pane.querySelector('#chkLogo').onchange = e => { const l=document.querySelector('.sb-logo'); if(l) l.style.display=e.target.checked?'':'none'; };
    pane.querySelector('#chkDark').onchange  = () => { if(typeof toggleTheme==='function') toggleTheme(); };
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA LOG
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabLog(pane) {
    pane.innerHTML = `
      <div class="dev-section-row"><span class="dev-section-title">Log</span>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <select id="devLogFilter" class="dev-field-select" style="width:120px">
            <option value="all">Todos</option><option value="info">Info</option>
            <option value="warn">Aviso</option><option value="error">Erro</option><option value="resolved">Resolvido</option>
          </select>
          <input id="devLogSearch" class="dev-field-input" placeholder="Buscar…" style="width:140px">
          <button id="devLogRefresh" class="dev-btn ghost">↻</button>
          <button id="devLogClear" class="dev-btn danger">Limpar</button>
        </div></div>
      <div class="dev-sep"></div><div id="devLog-list"></div>`;
    const refresh = () => { DevLog.renderList('devLog-list', pane.querySelector('#devLogFilter').value, pane.querySelector('#devLogSearch').value.trim()); DevLog.markAllRead(); };
    pane.querySelector('#devLogFilter').onchange = refresh;
    pane.querySelector('#devLogSearch').oninput  = refresh;
    pane.querySelector('#devLogRefresh').onclick = refresh;
    pane.querySelector('#devLogClear').onclick   = () => devConfirm('Limpar todo o log permanentemente?', () => { DevLog.clear(); refresh(); });
    refresh();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // ABA VERSÕES
  // ═══════════════════════════════════════════════════════════════════════
  function renderTabVersoes(pane) {
    pane.innerHTML = `
      <div class="dev-section-row"><span class="dev-section-title">Versões Salvas</span></div>
      <div class="dev-sep"></div>
      <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px">
        <div style="flex:1"><div class="dev-field-label">Label</div>
          <input id="devVerLabel" class="dev-field-input" placeholder="Ex: Fechamento Maio 2026" style="width:100%"></div>
        <button id="devVerSave" class="dev-btn primary">Salvar</button>
      </div>
      <div class="dev-sep"></div><div id="devVersions-list"></div>`;
    pane.querySelector('#devVerSave').onclick = () => {
      const lbl = pane.querySelector('#devVerLabel').value.trim() || `Versão ${new Date().toLocaleString('pt-BR')}`;
      Versions.save(lbl, false); pane.querySelector('#devVerLabel').value=''; Versions.renderList('devVersions-list');
    };
    Versions.renderList('devVersions-list');
    const listEl = document.getElementById('devVersions-list');
    if (listEl) {
      listEl.addEventListener('click', e => {
        if (e.target.closest('[data-action="restore"]')) setTimeout(()=>{ _activateTab(_currentTab()); }, 200);
      });
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // BOOT
  // ═══════════════════════════════════════════════════════════════════════
  document.addEventListener('DOMContentLoaded', () => { loadDevLibs(); buildDevPanel(); });
})();
