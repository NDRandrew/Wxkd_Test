html: `
  <div class="slide-cover">
    <div class="cover-bg-shape"></div>
    <div class="cover-bg-shape2"></div>
    <div class="cover-inner">
      <div class="cover-pill reveal">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Bradesco · Governança de Dados · BRAI4DQ v5.5.4
      </div>
      <h1 class="cover-h1 reveal delay-1">DEVA no<br><em>dia a dia</em></h1>
      <p class="cover-sub reveal delay-2">Como usar o agente para analisar dados com mais velocidade, tomar decisões com mais segurança e resolver problemas sem depender do time técnico.</p>
      <div class="cover-cta reveal delay-3">
        <button class="btn-cta solid" onclick="goSlide(1)">Iniciar</button>
        <button class="btn-cta" onclick="goSlide(SLIDES.length - 1)">Ver materiais</button>
      </div>
    </div>
    <div class="cover-bar"></div>
  </div>
`

SLIDE 0
-----------------------

navLabel: 'O que muda na prática',
title: 'O que muda na sua rotina',
html: `
  <div class="sec-h reveal">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
    Antes vs. agora
  </div>
  <table class="ref-table reveal delay-1">
    <thead><tr><th>Situação</th><th>Antes</th><th>Com o DEVA</th></tr></thead>
    <tbody>
      <tr><td>Checar se uma tabela tem nulos</td><td>Abrir SQL, escrever query, interpretar resultado</td><td><em>"A tabela está completa?"</em> — resposta em segundos</td></tr>
      <tr><td>Identificar duplicatas</td><td>Query GROUP BY, contar, analisar colunas</td><td><em>"Tem duplicatas?"</em> — resultado com detalhamento por coluna</td></tr>
      <tr><td>Entender a estrutura de uma tabela nova</td><td>Navegar no catálogo, ler documentação dispersa</td><td><em>"Faça o profiling"</em> — tipos, nulos, padrões em ~18s</td></tr>
      <tr><td>Criar regras de validação</td><td>Dias de análise manual e alinhamento com o time</td><td>34 regras sugeridas em ~25s — você revisa e aprova</td></tr>
      <tr><td>Detectar se dados mudaram entre cargas</td><td>Comparação manual de snapshots</td><td><em>"Compare com a versão de ontem"</em></td></tr>
    </tbody>
  </table>
  <div class="card-grid g3 reveal delay-3" style="margin-top:16px">
    <div class="kpi"><span class="kpi-label">Profiling completo</span><span class="kpi-val">~18s</span><span class="kpi-sub">vs. horas de análise manual</span></div>
    <div class="kpi"><span class="kpi-label">Regras geradas</span><span class="kpi-val">34</span><span class="kpi-sub">em ~25 segundos, já documentadas</span></div>
    <div class="kpi"><span class="kpi-label">Ganho</span><span class="kpi-val">7000x</span><span class="kpi-sub">vs. escrita manual de regras</span></div>
  </div>
`

SLIDE 1
--------------------
navLabel: 'Como acessar',
title: 'Acessando o DEVA',
html: `
  <div class="steps reveal">
    <div class="step">
      <div class="step-num">1</div>
      <div>
        <div class="step-title">Abra o notebook e clone para o seu workspace</div>
        <div class="step-text">Acesse pelo link do time, clique em <strong>Clone</strong> no menu superior direito e salve no seu workspace pessoal. Faça isso uma única vez.</div>
        <div class="step-code">[LINK DO NOTEBOOK — inserir após publicação]</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">2</div>
      <div>
        <div class="step-title">Run All — e aguardar o chat aparecer</div>
        <div class="step-text">Clique em <strong>Run All (▶▶)</strong>. O carregamento leva alguns minutos na primeira vez. A interface de chat aparece automaticamente abaixo das células.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num">3</div>
      <div>
        <div class="step-title">Digite em português e pressione Enviar</div>
        <div class="step-text">Não há comandos especiais para decorar. Pergunte como faria para um colega: <em>"Faça o profiling da tabela X"</em>, <em>"A tabela tem duplicatas?"</em>.</div>
      </div>
    </div>
  </div>
  <div class="card-grid g2 reveal delay-3" style="margin-top:4px">
    <div class="callout callout-ok" style="margin:0">
      <strong>Sempre use o clone.</strong> O notebook original é o template compartilhado do time — não execute nem edite diretamente.
    </div>
    <div class="callout callout-info" style="margin:0">
      <strong>Nas próximas sessões:</strong> basta abrir o clone e dar Run All. O DEVA não guarda histórico entre sessões — cada abertura começa do zero.
    </div>
  </div>
`

SLIDE 2
-----------------------


navLabel: 'Fluxo do dia a dia',
title: 'Fluxo típico de uma análise',
html: `
  <div class="sec-h reveal">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    Sequência recomendada para uma tabela nova
  </div>
  <div class="prompt-box reveal delay-1"><span class="prompt-label">Descoberta</span><span class="prompt-text">"Liste as tabelas disponíveis" · "Busque tabelas com 'fatura'"</span></div>
  <div class="prompt-box reveal delay-2"><span class="prompt-label">Raio-X</span><span class="prompt-text">"Faça o profiling da tabela catalog.schema.nome_tabela"</span></div>
  <div class="prompt-box reveal delay-3"><span class="prompt-label">Dimensões</span><span class="prompt-text">"A tabela está completa?" · "Tem duplicatas?" · "Verifique consistência" · "Analise integridade"</span></div>
  <div class="prompt-box reveal delay-4"><span class="prompt-label">Regras</span><span class="prompt-text">"Sugira regras de validação" → revisar → "Aprove as regras 1, 3 e 5" → "Exporte como YAML"</span></div>
  <div class="prompt-box reveal delay-5"><span class="prompt-label">Monitoramento</span><span class="prompt-text">"Compare com a versão de ontem" · "Descubra associações entre colunas"</span></div>

  <div class="callout callout-info reveal delay-6" style="margin-top:16px">
    <strong>Dica de velocidade:</strong> após cada resposta o DEVA exibe botões com os próximos passos sugeridos — clique diretamente neles em vez de digitar. Para análises recorrentes na mesma tabela, o contexto é mantido durante toda a sessão.
  </div>
`


SLIDE 3
----------------------

navLabel: 'O que perguntar',
title: 'Perguntas que você pode fazer agora',
html: `
  <div class="sec-h reveal">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Pronto para uso — sem configuração adicional
  </div>
  <div class="card-grid g2 reveal delay-1">
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
      <div class="card-title">Explorar e entender</div>
      <div class="card-text"><em>"Liste as tabelas disponíveis"</em><br><em>"Descreva a tabela de faturas PF"</em><br><em>"Faça o profiling da tabela X"</em></div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
      <div class="card-title">Verificar qualidade</div>
      <div class="card-text"><em>"A tabela está completa?"</em><br><em>"Tem duplicatas?"</em><br><em>"Verifique consistência e integridade"</em></div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
      <div class="card-title">Gerar e exportar regras</div>
      <div class="card-text"><em>"Sugira regras de validação"</em><br><em>"Aprove as regras 1, 3 e 5"</em><br><em>"Exporte como YAML / Python / SQL"</em></div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/><path d="M5 12H2"/></svg></div>
      <div class="card-title">Monitorar variações</div>
      <div class="card-text"><em>"Compare com a versão de ontem"</em><br><em>"Descubra associações entre colunas"</em><br><em>"Analise drift na tabela"</em></div>
    </div>
  </div>
  <div class="callout callout-warn reveal delay-3">
    <strong>Ainda em desenvolvimento:</strong> detecção de anomalias, relatório BCB e avaliação de governança BACEN estão mapeados mas com comportamento instável. Use com validação manual por enquanto.
  </div>
`

SLIDE 4
--------------------------


navLabel: 'Referência: dimensões',
title: 'Dimensões de qualidade — referência rápida',
html: `
  <div class="callout callout-info reveal">
    Use esta tabela como consulta rápida. Cada dimensão tem um prompt direto — copie e use no chat.
  </div>
  <table class="ref-table reveal delay-1">
    <thead><tr><th>Dimensão</th><th>Pergunta que responde</th><th>Prompt para usar agora</th></tr></thead>
    <tbody>
      <tr><td><strong>Completude</strong></td><td>Tem campos vazios que não deveriam estar?</td><td><em>"A tabela está completa?"</em></td></tr>
      <tr><td><strong>Unicidade</strong></td><td>Existem linhas repetidas?</td><td><em>"A tabela tem duplicatas?"</em></td></tr>
      <tr><td><strong>Consistência</strong></td><td>Os dados seguem o formato esperado?</td><td><em>"Verifique consistência dos dados"</em></td></tr>
      <tr><td><strong>Integridade</strong></td><td>As relações entre campos fazem sentido?</td><td><em>"Analise integridade da tabela"</em></td></tr>
      <tr><td><strong>Tempestividade</strong></td><td>Os dados estão atualizados?</td><td><em>"Verifique tempestividade"</em></td></tr>
      <tr><td><strong>Validade</strong></td><td>Os valores estão dentro do esperado?</td><td><em>"Verifique validade dos dados"</em></td></tr>
      <tr><td><strong>Acurácia</strong></td><td>Os dados batem com a fonte original?</td><td><em>"Verifique acurácia"</em></td></tr>
      <tr><td><strong>Disponibilidade</strong></td><td>Os dados estão acessíveis?</td><td><em>"Verifique disponibilidade"</em></td></tr>
      <tr><td><strong>Confiabilidade</strong></td><td>Os dados se mantêm consistentes ao longo do tempo?</td><td><em>"Verifique confiabilidade"</em></td></tr>
      <tr><td><strong>Interpretabilidade</strong></td><td>Os campos têm descrição no catálogo?</td><td><em>"Faça o profiling da tabela"</em></td></tr>
    </tbody>
  </table>
`


SLIDE 5

------------------------------

navLabel: 'Revisar e aprovar regras',
title: 'Revisando e aprovando regras',
html: `
  <div class="sec-h reveal">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    O DEVA sugere — você decide
  </div>
  <div class="callout callout-ok reveal delay-1">
    As regras vêm dos dados reais da tabela, não de suposições. Mesmo assim, <strong>seu julgamento de negócio é obrigatório</strong> antes de qualquer exportação — o DEVA nunca aplica nada automaticamente.
  </div>
  <div class="card-grid g2 reveal delay-2" style="margin:14px 0">
    <div class="card">
      <div class="card-title">Como revisar</div>
      <ul class="bullet-list" style="margin-top:8px">
        <li><em>"Mostre as regras geradas"</em> — lista numerada completa</li>
        <li>Para cada regra: faz sentido de negócio? Existe exceção legítima?</li>
        <li>Consulte o time se tiver dúvida sobre uma regra específica</li>
      </ul>
    </div>
    <div class="card">
      <div class="card-title">Como aprovar e exportar</div>
      <ul class="bullet-list" style="margin-top:8px">
        <li><em>"Aprove todas as regras"</em></li>
        <li><em>"Aprove as regras 1, 3 e 5"</em></li>
        <li><em>"Aprove tudo exceto a regra 2"</em></li>
        <li><em>"Exporte como YAML"</em> (ou Python, JSON, SQL)</li>
      </ul>
    </div>
  </div>
  <div class="sec-h reveal delay-3">
    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="16" y1="3" x2="16" y2="21"/></svg>
    Referência — PoC na tabela de faturas PF (20k registros)
  </div>
  <div class="card-grid g3 reveal delay-4">
    <div class="kpi"><span class="kpi-label">Regras geradas</span><span class="kpi-val">34</span><span class="kpi-sub">em ~25 segundos</span></div>
    <div class="kpi"><span class="kpi-label">Exemplos gerados</span><span class="kpi-val">8</span><span class="kpi-sub">categorias de regra diferentes</span></div>
    <div class="kpi"><span class="kpi-label">Formatos de exportação</span><span class="kpi-val">5</span><span class="kpi-sub">YAML · Python · JSON · XML · SQL</span></div>
  </div>
`


SLIDE 6
------------------------

navLabel: 'O que o DEVA não faz',
title: 'O que o DEVA não faz — limites importantes',
html: `
  <div class="card-grid g2 reveal">
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
      <div class="card-title">Nao altera dados</div>
      <div class="card-text">O DEVA é somente leitura. Ele analisa, sugere e exporta — mas não modifica, apaga nem move nenhum registro.</div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
      <div class="card-title">Nao ve valores reais</div>
      <div class="card-text">O modelo de IA recebe apenas nomes de tabelas e colunas — nunca os dados brutos. As análises estatísticas rodam localmente no cluster.</div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div>
      <div class="card-title">Nao aplica regras automaticamente</div>
      <div class="card-text">As regras exportadas são arquivos de configuração. A aplicação no pipeline depende de aprovação e integração pelo time técnico.</div>
    </div>
    <div class="card">
      <div class="card-icon"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
      <div class="card-title">Nao guarda histórico entre sessões</div>
      <div class="card-text">Cada Run All começa do zero. Salve perguntas e respostas relevantes antes de fechar o notebook caso precise consultar depois.</div>
    </div>
  </div>
  <table class="ref-table reveal delay-2" style="margin-top:16px">
    <thead><tr><th>Limite operacional</th><th>Valor</th><th>Ação se atingir</th></tr></thead>
    <tbody>
      <tr><td>Mensagens por minuto</td><td>60</td><td>Aguardar 1 minuto — não precisa reiniciar</td></tr>
      <tr><td>Tempo máximo por operação</td><td>5 minutos</td><td>Tentar com amostragem menor da tabela</td></tr>
    </tbody>
  </table>
`


SLIDE 7
----------------------------

navLabel: 'Resolvendo problemas',
title: 'Resolvendo problemas no dia a dia',
html: `
  <table class="ref-table reveal">
    <thead><tr><th>O que aconteceu</th><th>Por quê</th><th>O que fazer</th></tr></thead>
    <tbody>
      <tr><td>"Fora do escopo"</td><td>A pergunta não foi reconhecida como análise de dados</td><td>Inclua o nome da tabela e use termos como <em>coluna, completude, profiling, regra, validação</em></td></tr>
      <tr><td>"Nao identifiquei com clareza"</td><td>Pergunta genérica demais</td><td>Seja específico: <em>"Faça profiling da tabela X"</em> em vez de <em>"analisa isso"</em></td></tr>
      <tr><td>"Rate limit exceeded"</td><td>Mais de 60 mensagens em 1 minuto</td><td>Aguardar 60 segundos — o histórico da sessão é mantido</td></tr>
      <tr><td>Operação travou sem resposta</td><td>Tabela grande ou cluster sob carga</td><td>Aguardar até 5 min; se não responder, reiniciar notebook e tentar com tabela de amostragem</td></tr>
      <tr><td>Resposta parece estranha ou incorreta</td><td>Interpretação diferente do esperado</td><td>Dar feedback negativo na resposta e reformular com mais contexto. Exemplo: <em>"considerando que a coluna X é opcional, verifique novamente"</em></td></tr>
      <tr><td>Interface nao apareceu após Run All</td><td>Cluster inativo ou erro silencioso</td><td>Verificar se cluster está verde, rolar as células para ver se há erro em vermelho, e rodar Run All novamente</td></tr>
    </tbody>
  </table>
  <div class="callout callout-info reveal delay-2">
    <strong>Atalho útil:</strong> se não souber como formular uma pergunta, envie <em>"O que você pode fazer?"</em> — o DEVA lista todas as capacidades disponíveis no momento.
  </div>
`

SLIDE 8
-------------------------------


navLabel: 'DEVA não inicia',
title: 'DEVA não inicia — o que verificar',
html: `
  <div class="callout callout-warn reveal">
    <strong>Situação:</strong> Run All foi executado mas o chat não apareceu, ou apareceu mas não responde. Siga a ordem abaixo antes de acionar o suporte.
  </div>
  <div class="steps reveal delay-1">
    <div class="step">
      <div class="step-num" style="background:#B45309">1</div>
      <div>
        <div class="step-title">Cluster ligado?</div>
        <div class="step-text">Veja o indicador ao lado do nome do cluster no topo do notebook. Se estiver <strong>cinza</strong>, clique para iniciar e aguarde 3 a 8 minutos antes de tentar Run All novamente.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num" style="background:#B45309">2</div>
      <div>
        <div class="step-title">Alguma célula com fundo vermelho?</div>
        <div class="step-text">Role as células para cima. Fundo vermelho indica erro de instalação dos pacotes. Copie o texto do erro completo — você vai precisar para acionar o suporte.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num" style="background:#B45309">3</div>
      <div>
        <div class="step-title">Tente desconectar e reconectar o cluster</div>
        <div class="step-text">No menu do notebook: <strong>Cluster → Desconectar → Reconectar</strong>. Depois execute Run All novamente. Resolve a maioria dos casos de sessão expirada.</div>
      </div>
    </div>
    <div class="step">
      <div class="step-num" style="background:#B45309">4</div>
      <div>
        <div class="step-title">Ainda sem resposta? Acione o suporte</div>
        <div class="step-text">Envie: nome do cluster, mensagem de erro copiada do notebook e horário da ocorrência para o time de Governança de Dados / COE BRAI4DQ.</div>
      </div>
    </div>
  </div>
`


SLIDE 9
----------------------------------------


navLabel: 'Materiais de consulta',
title: 'Materiais de consulta disponíveis',
html: `
  <div class="callout callout-info reveal">
    Dois documentos ficam disponíveis para consulta após esta apresentação — use quando surgir uma dúvida no meio de uma análise ou quando aparecer um erro que você não reconhece.
  </div>

  <div class="reveal delay-1">
    <div class="doc-ref-row">
      <div class="doc-ref-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
      <div>
        <div class="doc-ref-title">Guia de Utilizacao Rapida (DOCX + PDF)</div>
        <div class="doc-ref-sub">Prompts prontos para cada análise, tabelas de referência de completude, unicidade e acurácia com faixas de classificação, erros comuns com solução e glossário dos termos usados pelo DEVA.</div>
      </div>
      <div class="doc-ref-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
    </div>
    <div class="doc-ref-row">
      <div class="doc-ref-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
      <div>
        <div class="doc-ref-title">Central de Dúvidas (HTML interativo)</div>
        <div class="doc-ref-sub">35 perguntas organizadas por categoria — busca por palavra-chave, modo escuro, árvore lateral de navegação. Cobre desde o fluxo básico de análise até diagnóstico de erros graves.</div>
      </div>
      <div class="doc-ref-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></div>
    </div>
  </div>

  <div class="card-grid g3 reveal delay-3" style="margin-top:20px">
    <div class="kpi"><span class="kpi-label">Prompts prontos</span><span class="kpi-val">37+</span><span class="kpi-sub">Guia de Utilização</span></div>
    <div class="kpi"><span class="kpi-label">Dúvidas respondidas</span><span class="kpi-val">35</span><span class="kpi-sub">Central de Dúvidas</span></div>
    <div class="kpi"><span class="kpi-label">Tabelas de referência</span><span class="kpi-val">3</span><span class="kpi-sub">Completude · Unicidade · Acurácia</span></div>
  </div>
`

SLIDE 10
-----------------


html: `
  <div class="slide-final">
    <div class="final-bar"></div>
    <h1 class="final-h">Boa análise<br>com o <em>DEVA</em></h1>
    <p class="final-sub">Dúvidas no dia a dia: consulte a Central de Dúvidas ou acione o time de Governança de Dados — COE BRAI4DQ.</p>
    <div class="final-docs">
      <button class="final-doc-btn" onclick="goSlide(0)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Reiniciar
      </button>
      <button class="final-doc-btn" onclick="goSlide(SLIDES.findIndex(s=>s.id==='fluxo'))">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Fluxo do dia a dia
      </button>
      <button class="final-doc-btn" onclick="goSlide(SLIDES.findIndex(s=>s.id==='erros-graves'))">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
        DEVA nao inicia
      </button>
    </div>
    <div style="font-size:.75rem;color:var(--g400);font-family:var(--font-head)">
      Bradesco · Governança de Dados · BRAI4DQ v5.5.4 · Maio 2026
    </div>
  </div>
`

SLIDE 11
---------------------------------



Dois ajustes adicionais necessários ao aplicar os snippets:

No slide 3, mude o id de 'primeiras-perguntas' para 'fluxo' (o botão final referencia s.id==='fluxo')
O slide 1 muda navGroup de 'Conceitos' para 'Rotina' — recomendo também mudar os demais slides 2, 3 para 'Rotina' para o sidebar ficar mais coeso


Tenho o conteúdo completo. Vou reescrever cada slide com foco no que o analista precisa no dia a dia — sem explicar conceitos que já conhecem, direto em como usar, o que ganham e como resolver problemas.
Aqui estão os snippets de cada slide alterado. Só o bloco html: e title: mudam (onde aplicável); tudo mais permanece igual.
