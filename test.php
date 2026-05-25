# =============================================================================
# CÉLULA 6 — RESUMO DE CHAMADOS CLOUD POR GERENTE (período do boletim)
# STATUS_TRATADO alinhado ao DAX de MORE.txt:
#   EM ANÁLISE  + datediff(hoje, CRIADO_EM) <= 5  → DENTRO DO PRAZO
#   EM ANÁLISE  + datediff(hoje, CRIADO_EM) >  5  → FORA DO PRAZO
#   EM ANDAMENTO + PRAZO DE CORREÇÃO >= hoje       → DENTRO DO PRAZO
#   EM ANDAMENTO + PRAZO DE CORREÇÃO <  hoje       → FORA DO PRAZO
#   ATUACAO QUALIDADE                              → ATENDIMENTO QUALIDADE
#   demais                                         → CONCLUÍDO (encerrados)
# =============================================================================

resumo_rows = spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE_SR,
        -- encerrados
        COUNT(CASE WHEN ch.STATUS IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA') THEN 1 END) AS QTD_ENCERRADOS,
        -- EM ANÁLISE
        COUNT(CASE WHEN ch.STATUS = 'EM ANÁLISE'
                    AND datediff(current_date(), ch.CRIADO_EM) <= 5  THEN 1 END) AS QTD_ANALISE_DENTRO,
        COUNT(CASE WHEN ch.STATUS = 'EM ANÁLISE'
                    AND datediff(current_date(), ch.CRIADO_EM) >  5  THEN 1 END) AS QTD_ANALISE_FORA,
        -- EM ANDAMENTO  (usa coluna PRAZO DE CORREÇÃO = dprz_corrc_reg_invld)
        COUNT(CASE WHEN ch.STATUS = 'EM ANDAMENTO'
                    AND ch.`PRAZO DE CORREÇÃO` >= date_format(current_date(),'dd/MM/yyyy') THEN 1 END) AS QTD_ANDAMENTO_DENTRO,
        COUNT(CASE WHEN ch.STATUS = 'EM ANDAMENTO'
                    AND (ch.`PRAZO DE CORREÇÃO` < date_format(current_date(),'dd/MM/yyyy')
                         OR ch.`PRAZO DE CORREÇÃO` IS NULL
                         OR trim(ch.`PRAZO DE CORREÇÃO`) = '') THEN 1 END) AS QTD_ANDAMENTO_FORA,
        -- ATUACAO QUALIDADE
        COUNT(CASE WHEN ch.STATUS = 'ATUACAO QUALIDADE' THEN 1 END) AS QTD_ATUACAO_QUALIDADE,
        -- vencidos (qualquer status ativo > 5 dias)
        COUNT(CASE WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
                    AND datediff(current_date(), ch.CRIADO_EM) > 5 THEN 1 END) AS QTD_ABERTOS_MAIS_5_DIAS,
        COUNT(ch.ID_ITEM) AS QTD_TOTAL
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
      AND ch.ID_ITEM IS NOT NULL
    GROUP BY sp.gs_Title
""").collect()

resumo_dict = {}
for row in resumo_rows:
    g = row["GERENTE_SR"]
    resumo_dict.setdefault(g, {
        "encerrados":0,
        "analise_dentro":0,"analise_fora":0,
        "andamento_dentro":0,"andamento_fora":0,
        "atuacao_qualidade":0,"abertos_5_dias":0,"total":0
    })
    d = resumo_dict[g]
    d["encerrados"]       += row["QTD_ENCERRADOS"]         or 0
    d["analise_dentro"]   += row["QTD_ANALISE_DENTRO"]     or 0
    d["analise_fora"]     += row["QTD_ANALISE_FORA"]       or 0
    d["andamento_dentro"] += row["QTD_ANDAMENTO_DENTRO"]   or 0
    d["andamento_fora"]   += row["QTD_ANDAMENTO_FORA"]     or 0
    d["atuacao_qualidade"]+= row["QTD_ATUACAO_QUALIDADE"]  or 0
    d["abertos_5_dias"]   += row["QTD_ABERTOS_MAIS_5_DIAS"] or 0
    d["total"]            += row["QTD_TOTAL"]               or 0

# ------------------------

# =============================================================================
# CÉLULA 7 — TENDÊNCIA MENSAL (5 meses completos) — AGREGADA POR GERENTE
# =============================================================================

tendencia_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE,
           date_format(ch.CRIADO_EM,'yyyy-MM') AS MES,
           COUNT(CASE WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA') THEN 1 END) AS ABERTOS,
           COUNT(CASE WHEN ch.STATUS IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')     THEN 1 END) AS CONCLUIDOS
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.CRIADO_EM BETWEEN date('{DATA_INI_TENDENCIA}') AND date('{DATA_FIM_TENDENCIA}')
      AND ch.ID_ITEM IS NOT NULL
    GROUP BY sp.gs_Title, date_format(ch.CRIADO_EM,'yyyy-MM')
    ORDER BY 1, 2
""").collect():
    g = row["GERENTE"]
    tendencia_dict.setdefault(g, {"meses":[], "abertos":[], "concluidos":[]})
    tendencia_dict[g]["meses"].append(row["MES"])
    tendencia_dict[g]["abertos"].append(int(row["ABERTOS"] or 0))
    tendencia_dict[g]["concluidos"].append(int(row["CONCLUIDOS"] or 0))

# ------------------------

# =============================================================================
# CÉLULA 7B — P-SCORE MENSAL POR PRODUTO (últimos 5 meses)
# Gráfico de linhas: cada produto = uma linha; eixo X = mês
# =============================================================================

_score_mensal_rows = spark.sql(f"""
    WITH sq_base AS (
        SELECT a.dini_excuc_quald,
               date_format(a.dini_excuc_quald, 'yyyy-MM') AS MES,
               h.itpo_prodt_funcl,
               f.idmsao_quald_dados,
               a.pscore_calcd
        {_JOINS_SCORE}
        WHERE h.itpo_prodt = 'PRODUTO DE DADOS'
          AND a.dini_excuc_quald BETWEEN date('{DATA_INI_TENDENCIA}') AND date('{DATA_FIM_TENDENCIA}')
          AND a.nsttus_mntrc = 1
          AND f.idmsao_quald_dados != 'VARIACAO'
    ),
    dias_count AS (
        SELECT itpo_prodt_funcl, idmsao_quald_dados, MES,
               COUNT(DISTINCT dini_excuc_quald) AS dias_execucao
        FROM sq_base GROUP BY itpo_prodt_funcl, idmsao_quald_dados, MES
    ),
    joined AS (
        SELECT sp.gs_Title AS GERENTE_SENIOR,
               sp.produto AS NOME_PRODUTO,
               sq.MES,
               sq.idmsao_quald_dados AS DIMENSAO,
               sq.pscore_calcd AS PSCORE,
               dc.dias_execucao AS DIAS_EXECUCAO
        FROM vw_sharepoint_score sp
        INNER JOIN sq_base sq   ON sp.produto = sq.itpo_prodt_funcl
        INNER JOIN dias_count dc
            ON sq.itpo_prodt_funcl    = dc.itpo_prodt_funcl
           AND sq.idmsao_quald_dados  = dc.idmsao_quald_dados
           AND sq.MES                 = dc.MES
    ),
    dim_agg AS (
        SELECT GERENTE_SENIOR, NOME_PRODUTO, MES, DIMENSAO,
               AVG(PSCORE) AS AVG_SCORE_DIM, SUM(DIAS_EXECUCAO) AS DIAS_DIM
        FROM joined GROUP BY GERENTE_SENIOR, NOME_PRODUTO, MES, DIMENSAO
    )
    SELECT GERENTE_SENIOR, NOME_PRODUTO, MES,
           ROUND(SUM(AVG_SCORE_DIM * DIAS_DIM) / NULLIF(SUM(DIAS_DIM),0), 2) AS PSCORE_MES
    FROM dim_agg
    GROUP BY GERENTE_SENIOR, NOME_PRODUTO, MES
    HAVING SUM(DIAS_DIM) > 0
    ORDER BY GERENTE_SENIOR, NOME_PRODUTO, MES
""").collect()

# Monta dicionário: {gerente: {produto: {mes: score}}}
_score_mensal_raw = {}
for row in _score_mensal_rows:
    g = row["GERENTE_SENIOR"]
    p = row["NOME_PRODUTO"]
    m = row["MES"]
    v = round(float(row["PSCORE_MES"]), 2) if row["PSCORE_MES"] is not None else None
    _score_mensal_raw.setdefault(g, {}).setdefault(p, {})[m] = v

# Normaliza para o JSON: lista de meses únicos ordenados por gerente
score_mensal_dict = {}
for g, produtos in _score_mensal_raw.items():
    all_meses = sorted({m for p_data in produtos.values() for m in p_data.keys()})
    series = []
    for prod, mes_data in produtos.items():
        series.append({
            "produto": prod,
            "scores": [mes_data.get(m) for m in all_meses]  # None = sem dado naquele mês
        })
    score_mensal_dict[g] = {"meses": all_meses, "series": series}

# ------------------------

# =============================================================================
# CÉLULA 8 — CAUSAS RAÍZES (DISTINCTCOUNT de cidtfd_rep conforme MORE.txt)
# =============================================================================

causas_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%FALHA CLUSTER%'        THEN ch.ID_ITEM END) AS FALHA_CLUSTER,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%FALSO POSITIVO%'       THEN ch.ID_ITEM END) AS FALSO_POSITIVO,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%ERRO DE EXECUCAO%'     THEN ch.ID_ITEM END) AS ERRO_EXEC,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%QUEBRA DE ARQUIVO%'    THEN ch.ID_ITEM END) AS QUEBRA_ARQ,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%ORIGEM INDISPONIVEL%'  THEN ch.ID_ITEM END) AS ORIGEM_INDISP,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%DIVERSAS%'
                             AND upper(ch.`COMENTÁRIOS`) NOT LIKE '%OPEN FINANCE%'     THEN ch.ID_ITEM END) AS DIVERSAS,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%OPEN FINANCE%'         THEN ch.ID_ITEM END) AS OPEN_FINANCE,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%JOB/MALHA COM ATRASO%' THEN ch.ID_ITEM END) AS JOB_ATRASO,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%PROBLEMAS COM AMBIE%'  THEN ch.ID_ITEM END) AS PROBLEMA_AMB,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%ALTERACAO NA ESTRUT%'  THEN ch.ID_ITEM END) AS ALTERACAO_EST,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%PREENCHIMENTO INCOR%'  THEN ch.ID_ITEM END) AS PREENCH_INCOR,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%EXTERNO%'              THEN ch.ID_ITEM END) AS EXTERNO,
        COUNT(DISTINCT CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%OUTROS%'               THEN ch.ID_ITEM END) AS OUTROS,
        COUNT(DISTINCT CASE WHEN trim(coalesce(ch.`COMENTÁRIOS`,'')) = ''              THEN ch.ID_ITEM END) AS NAO_INFORMADO
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = upper(ch.produto_de_dados)
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title
""").collect():
    g = row["GERENTE"]
    causas_dict[g] = {
        "Falha em Cluster":        int(row["FALHA_CLUSTER"]    or 0),
        "Falso Positivo":          int(row["FALSO_POSITIVO"]   or 0),
        "Erro de Execução":        int(row["ERRO_EXEC"]        or 0),
        "Quebra de Arquivo":       int(row["QUEBRA_ARQ"]       or 0),
        "Origem Indisponível":     int(row["ORIGEM_INDISP"]    or 0),
        "Diversas":                int(row["DIVERSAS"]         or 0),
        "Open Finance":            int(row["OPEN_FINANCE"]     or 0),
        "Job com Atraso":          int(row["JOB_ATRASO"]       or 0),
        "Problema com Ambiente":   int(row["PROBLEMA_AMB"]     or 0),
        "Alteração na Estrutura":  int(row["ALTERACAO_EST"]    or 0),
        "Preenchimento Incorreto": int(row["PREENCH_INCOR"]    or 0),
        "Externo":                 int(row["EXTERNO"]          or 0),
        "Outros":                  int(row["OUTROS"]           or 0),
        "Não Informado":           int(row["NAO_INFORMADO"]    or 0),
    }

# ------------------------

# =============================================================================
# CÉLULA 9 — CHAMADOS VENCIDOS > 5 DIAS
# =============================================================================

vencidos_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE_SENIOR,
           ch.ID_ITEM AS ID_CHAMADO,
           sp.produto AS NOME_PRODUTO,
           ch.`DIMENSÃO` AS DIMENSAO,
           date_format(from_utc_timestamp(ch.CRIADO_EM,'America/Sao_Paulo'),'dd/MM/yyyy') AS DATA_CRIACAO,
           datediff(current_date(), ch.CRIADO_EM) AS DIAS_ABERTO
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
      AND datediff(current_date(), ch.CRIADO_EM) > 5
    ORDER BY sp.gs_Title, datediff(current_date(), ch.CRIADO_EM) DESC
""").collect():
    g = row["GERENTE_SENIOR"]
    vencidos_dict.setdefault(g, [])
    vencidos_dict[g].append({
        "id":   str(row["ID_CHAMADO"]),
        "prod": row["NOME_PRODUTO"]  or "",
        "tipo": row["DIMENSAO"]      or "",
        "data": row["DATA_CRIACAO"]  or "",
        "dias": int(row["DIAS_ABERTO"] or 0),
    })

# ------------------------

# =============================================================================
# CÉLULA 10 — DISTRIBUIÇÃO POR DIMENSÃO
# Traz COUNT(ID_ITEM) = volume de chamados e SUM(qocor_quald) = ocorrências
# conforme MORE.txt: "VOLUME TOTAL DE CHAMADOS E OCORRÊNCIAS POR DIMENSÃO"
# =============================================================================

dimensao_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE,
           ch.`DIMENSÃO` AS DIMENSAO,
           COUNT(DISTINCT ch.ID_ITEM)   AS QTD_CHAMADOS,
           SUM(COALESCE(CAST(ch.qocor_quald AS BIGINT), 0)) AS QTD_OCORRENCIAS
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title, ch.`DIMENSÃO`
    ORDER BY sp.gs_Title, QTD_CHAMADOS DESC
""").collect():
    g = row["GERENTE"]
    dimensao_dict.setdefault(g, {"labels":[], "chamados":[], "ocorrencias":[]})
    if row["DIMENSAO"]:
        dimensao_dict[g]["labels"].append(row["DIMENSAO"])
        dimensao_dict[g]["chamados"].append(int(row["QTD_CHAMADOS"] or 0))
        dimensao_dict[g]["ocorrencias"].append(int(row["QTD_OCORRENCIAS"] or 0))

# ------------------------

# =============================================================================
# CÉLULA 11 — MONTAGEM DO JSON FINAL
# =============================================================================

lista_gerentes = sorted([
    row["gs_Title"]
    for row in df_sharepoint_score.select("gs_Title").distinct().collect()
    if row["gs_Title"]
])

dados_gerentes = {}
for gerente in lista_gerentes:
    kpis_g     = kpis_dict.get(gerente, {})
    resumo_g   = resumo_dict.get(gerente, {
        "encerrados":0,"analise_dentro":0,"analise_fora":0,
        "andamento_dentro":0,"andamento_fora":0,
        "atuacao_qualidade":0,"abertos_5_dias":0,"total":0
    })
    vencidos_g = vencidos_dict.get(gerente, [])
    qtd_vencidos_real = len(vencidos_g)

    scores_g   = sorted(score_dict.get(gerente, []), key=lambda x: x["pscore"])[:10]
    causas_g   = causas_dict.get(gerente, {})
    # Pareto: ordena decrescente, pega todos com valor > 0 (top 10)
    top_causas = sorted([(k,v) for k,v in causas_g.items() if v > 0],
                        key=lambda x: x[1], reverse=True)[:10]

    dados_gerentes[gerente] = {
        "periodo":      f"{DATA_INI} a {DATA_FIM}",
        "mesesRotulo":  MESES_ROTULO,
        "kpis": {
            "produtos":       kpis_g.get("PRODUTO DE DADOS", 0),
            "total_chamados": resumo_g["total"],
            "vencidos":       qtd_vencidos_real,
        },
        # Status dividido conforme STATUS_TRATADO do DAX
        "statusChamados": {
            "encerrados":       resumo_g["encerrados"],
            "analise_dentro":   resumo_g["analise_dentro"],
            "analise_fora":     resumo_g["analise_fora"],
            "andamento_dentro": resumo_g["andamento_dentro"],
            "andamento_fora":   resumo_g["andamento_fora"],
            "atuacao":          resumo_g["atuacao_qualidade"],
        },
        "pscore":        {"labels": [s["produto"] for s in scores_g],
                          "data":   [s["pscore"]  for s in scores_g]},
        "scoreMensal":   score_mensal_dict.get(gerente, {"meses":[],"series":[]}),
        "causasRaizes":  {"labels": [c[0] for c in top_causas],
                          "data":   [c[1] for c in top_causas]},
        "tendencia":     tendencia_dict.get(gerente, {"meses":[],"abertos":[],"concluidos":[]}),
        "dimensoes":     dimensao_dict.get(gerente, {"labels":[],"chamados":[],"ocorrencias":[]}),
        "vencidos":      vencidos_g[:15],
    }

# ========================================================================================
# CÉLULA 12 — GERAÇÃO DO HTML SINGLETON
# Mudanças focais nesta célula:
#   1. Gráfico de linhas P-Score produto×mês → PRIMEIRO na página
#   2. KPIs mantidos (3 cards)
#   3. Gráfico de Status refatorado: barras horizontais empilhadas Dentro/Fora do Prazo
#   4. Gráfico de Causas: barras horizontais com linha de Pareto acumulada e datalabels
#   5. Gráfico de Dimensões: grouped bar (Chamados + Ocorrências) com datalabels
#   6. Tendência: linha dupla com datalabels e previsão pontilhada (+1 mês)
#   7. P-Score atual: barras horizontais com datalabels de valor e faixa de cor
#   8. Chart.js DataLabels plugin carregado via CDN
# ========================================================================================

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
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js">{_SC}
<style>
:root{{
  --pr:#C01160;--pr-dk:#8C0F3B;--pr-lt:#DF1A73;
  --wh:#FFFFFF;--g50:#F9FAFB;--g100:#F3F4F6;--g200:#E5E7EB;
  --g300:#D1D5DB;--g600:#4B5563;--g700:#374151;--g800:#1F2937;--g900:#111827;
  --green:#059669;--yellow:#D97706;--red:#DC2626;
  --sh:0 4px 24px rgba(0,0,0,.07);--sh2:0 8px 40px rgba(0,0,0,.12);
  --radius:12px;--radius-lg:20px;
  --sb-w:240px;--sb-collapsed:56px;
}}
[data-theme="dark"]{{
  --wh:#111827;--g50:#1F2937;--g100:#374151;--g200:#4B5563;
  --g300:#6B7280;--g600:#D1D5DB;--g700:#E5E7EB;--g800:#F3F4F6;--g900:#F9FAFB;
}}
*{{margin:0;padding:0;box-sizing:border-box}}
html,body{{height:100%;font-family:'Inter',sans-serif;background:linear-gradient(180deg,var(--pr) 0%,var(--pr-dk) 55%,#990f48 100%);color:var(--g800)}}
.app{{display:flex;height:100vh;overflow:hidden}}

/* ── Sidebar ── */
.sidebar{{position:fixed;left:0;top:0;height:100vh;width:var(--sb-w);background:linear-gradient(180deg,var(--pr-dk) 0%,var(--pr) 100%);color:#fff;padding:14px 10px 20px;z-index:200;display:flex;flex-direction:column;gap:4px;border-radius:0 28px 28px 0;box-shadow:4px 0 24px rgba(0,0,0,.18);transition:width .7s ease,padding .7s ease;overflow:hidden}}
.sidebar.collapsed{{width:var(--sb-collapsed)}}
.sidebar-header{{display:flex;flex-direction:row;align-items:center;justify-content:space-between;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.18);transition:all .7s ease}}
.sidebar.collapsed .sidebar-header{{flex-direction:column-reverse;gap:15px;justify-content:center}}
.sb-toggle{{display:flex;align-items:center;justify-content:flex-end;flex-shrink:0;transition:all .7s ease}}
.sidebar.collapsed .sb-toggle{{justify-content:center;width:100%}}
.sb-toggle-btn{{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s,transform .7s ease;flex-shrink:0}}
.sb-toggle-btn:hover{{background:rgba(255,255,255,.28)}}
.sidebar.collapsed .sb-toggle-btn{{transform:rotate(180deg)}}
.sidebar-logo{{display:flex;align-items:center;gap:10px;transition:all .7s ease}}
.sidebar-logo svg{{flex-shrink:0;opacity:.92}}
.sidebar-logo span{{font-size:.82rem;font-weight:700;line-height:1.25;opacity:.9;transition:opacity .2s}}
.sidebar.collapsed .sidebar-logo span{{opacity:0;pointer-events:none}}
.nav-item{{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.78);text-decoration:none;padding:9px 10px;border-radius:10px;font-weight:500;font-size:.85rem;cursor:pointer;transition:background .2s,color .2s;border:none;background:none;width:100%;text-align:left;white-space:nowrap;overflow:hidden}}
.nav-item:hover{{background:rgba(255,255,255,.14);color:#fff}}
.nav-item.active{{background:rgba(255,255,255,.22);color:#fff;font-weight:700}}
.nav-item svg{{flex-shrink:0;opacity:.8;min-width:16px}}
.nav-item-label{{transition:opacity .2s,max-width .7s ease;max-width:200px;overflow:hidden}}
.sidebar.collapsed .nav-item-label{{opacity:0;max-width:0}}
.nav-divider{{height:1px;background:rgba(255,255,255,.15);margin:6px 0;flex-shrink:0}}
.nav-label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;opacity:.55;padding:4px 10px;white-space:nowrap;overflow:hidden;transition:opacity .2s}}
.sidebar.collapsed .nav-label{{opacity:0}}
.sidebar-sel-wrap{{margin-top:4px;padding:10px;background:rgba(0,0,0,.18);border-radius:10px;flex-shrink:0;overflow:hidden;transition:opacity .2s,max-height .7s ease,padding .7s ease;max-height:80px}}
.sidebar.collapsed .sidebar-sel-wrap{{opacity:0;max-height:0;padding:0;pointer-events:none}}
.sidebar-sel-wrap label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.7;display:block;margin-bottom:5px}}
.sidebar-sel{{width:100%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 8px;border-radius:8px;font-size:.8rem;font-family:inherit;outline:none;cursor:pointer}}
.sidebar-sel option{{background:#8C0F3B;color:#fff}}
.theme-row{{margin-top:auto;display:flex;align-items:center;justify-content:space-between;background:rgba(0,0,0,.18);padding:10px;border-radius:10px;cursor:pointer;flex-shrink:0;overflow:hidden;white-space:nowrap}}
.theme-row-label{{font-size:.75rem;font-weight:600;opacity:.75;transition:opacity .2s}}
.sidebar.collapsed .theme-row-label{{opacity:0;max-width:0;overflow:hidden}}
.theme-track{{width:40px;height:22px;background:rgba(255,255,255,.25);border-radius:11px;position:relative;transition:background .3s;flex-shrink:0;display:flex;align-items:center;padding:0 3px;justify-content:flex-start}}
.theme-icon{{position:absolute;transition:opacity .3s}}
.theme-icon.moon{{opacity:1;right:5px}}
.theme-icon.sun{{opacity:0;left:4px}}
[data-theme="dark"] .theme-icon.moon{{opacity:0}}
[data-theme="dark"] .theme-icon.sun{{opacity:1}}
.theme-thumb{{width:16px;height:16px;background:#fff;border-radius:50%;position:absolute;top:3px;left:3px;transition:transform .3s ease}}
[data-theme="dark"] .theme-thumb{{transform:translateX(18px)}}

/* ── Conteúdo ── */
.main-wrap{{margin-left:var(--sb-w);flex:1;height:100vh;overflow:hidden;position:relative;transition:margin-left .7s ease}}
.main-wrap.collapsed{{margin-left:var(--sb-collapsed)}}
.page{{position:absolute;inset:0;overflow-y:auto;opacity:0;visibility:hidden;transform:translateX(40px);transition:opacity .45s ease,transform .45s ease,visibility .45s}}
.page.active{{opacity:1;visibility:visible;transform:translateX(0)}}

/* ── Home ── */
.page-home{{background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 55%,#5E0827 100%);height:100%;min-height:100%;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;padding:7% 8%;position:relative;overflow:hidden}}
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

/* KPI */
.kpi-grid{{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}}
.kpi-card{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:4px;box-shadow:var(--sh);opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease,box-shadow .25s}}
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
.chart-card{{background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);padding:18px;box-shadow:var(--sh);opacity:0;transform:translateY(18px);transition:opacity .4s ease,transform .4s ease,box-shadow .25s}}
.chart-card.revealed{{opacity:1;transform:translateY(0)}}
.chart-card:hover{{box-shadow:var(--sh2)}}
.chart-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px}}
.chart-sublabel{{font-size:.68rem;color:var(--g300);margin-bottom:10px;font-style:italic}}
.chart-wrap{{position:relative;width:100%}}
/* alturas para comportar labels */
.h360{{height:360px}}.h320{{height:320px}}.h280{{height:280px}}.h260{{height:260px}}.h240{{height:240px}}.h220{{height:220px}}

/* legenda de cores inline */
.legend-row{{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:10px}}
.legend-item{{display:flex;align-items:center;gap:5px;font-size:.7rem;color:var(--g600);font-weight:500}}
.legend-dot{{width:10px;height:10px;border-radius:50%;flex-shrink:0}}

/* Tabela */
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

/* Parecer */
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
@media(max-width:768px){{.sidebar{{transform:translateX(-100%)}}.sidebar.open{{transform:translateX(0)}}.main-wrap{{margin-left:0!important}}.kpi-grid{{grid-template-columns:1fr}}.dash-body{{padding:16px}}.ajuda-inner{{padding:24px 16px}}}}
{_SS}
</head>
<body>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="36" viewBox="0 0 750 749.999995" height="36" preserveAspectRatio="xMidYMid meet" version="1.0">
        <defs>
          <filter x="0%" y="0%" width="100%" height="100%" id="sbf1"><feColorMatrix values="0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 0 0 0 1 0" color-interpolation-filters="sRGB"/></filter>
          <clipPath id="sbc1"><path d="M 0 96.292969 L 750 96.292969 L 750 653.542969 L 0 653.542969 Z" clip-rule="nonzero"/></clipPath>
          <clipPath id="sbc2"><rect x="0" y="0" width="750" height="750"/></clipPath>
          <clipPath id="sbc3"><path d="M 233.375 462.5 L 516.625 462.5 L 516.625 653.542969 L 233.375 653.542969 Z" clip-rule="nonzero"/></clipPath>
        </defs>
        <g clip-path="url(#sbc2)" filter="url(#sbf1)">
          <g clip-path="url(#sbc1)">
            <path fill="#e30045" d="M 26.667969 242.199219 C 33.054688 238.152344 39.160156 232.835938 46.433594 229.875 C 66.765625 221.035156 87.574219 213.160156 108.023438 203.710938 C 111.972656 201.996094 116.066406 197.820312 117.984375 193.800781 C 149.574219 123.535156 202.695312 75.542969 278.078125 55.386719 C 373.132812 30.082031 453.351562 57.136719 519.667969 132.335938 C 523.984375 137.171875 525.28125 142.148438 520.011719 147.324219 C 514.851562 152.390625 510.363281 149.03125 505.578125 145.289062 C 431.367188 89.832031 330.71875 83.53125 254.859375 132.085938 C 236.65625 143.511719 220.492188 159.132812 203.554688 172.703125 C 201.355469 174.652344 199.863281 177.449219 196.992188 181.757812 C 208.785156 180.078125 218.226562 178.570312 227.707031 177.328125 C 328.476562 164.554688 426.355469 174.296875 520.550781 213.085938 C 554.523438 227.171875 586.730469 246.085938 612.324219 272.210938 C 627.066406 287.398438 639.589844 304.523438 639.765625 326.410156 C 639.992188 354.410156 627.546875 376.679688 608.960938 396.496094 C 577.871094 430.289062 539.363281 450.210938 498.292969 466.425781 C 487.835938 470.503906 477.117188 473.601562 466.601562 477.25 C 464.175781 478.054688 461.5 479.191406 459.289062 478.738281 C 455.9375 478.089844 450.882812 476.757812 450.300781 474.640625 C 449.453125 471.398438 451.054688 466.953125 453.289062 463.722656 C 455.464844 460.585938 459.609375 458.984375 463.300781 457.433594 C 482.460938 449.476562 501.914062 442.261719 520.917969 433.804688 C 547.367188 422.289062 565.246094 403.339844 570.707031 374.546875 C 578.265625 335.800781 559.550781 309.421875 529.054688 288.496094 C 491.332031 263.007812 449.320312 248.683594 405.605469 238.5 C 352.421875 226.210938 298.617188 220.386719 243.925781 221.261719 C 221.332031 221.648438 198.804688 224.175781 176.28125 225.460938 C 170.960938 225.773438 167.824219 227.894531 165.636719 234.085938 C 151.960938 273.964844 148.640625 314.863281 151.542969 356.664062 C 156.230469 422.574219 178.84375 480.078125 219.414062 531.523438 C 222.433594 535.308594 224.882812 540.324219 225.074219 544.96875 C 225.421875 553.277344 217.253906 557.425781 209.398438 551.953125 C 196.03125 542.589844 182.707031 533.171875 171.292969 521.742188 C 112.476562 463.144531 87.6875 391.109375 96.398438 315.011719 C 96.484375 314.246094 96.417969 313.441406 96.417969 309.457031 C 90.214844 310.664062 84.53125 311.527344 78.980469 312.914062 C 63.929688 316.613281 48.589844 319.851562 34.101562 324.953125 C 26.117188 327.757812 20.722656 327.003906 16.007812 319.371094 C 14.523438 319.371094 13.042969 319.371094 11.558594 319.371094 Z"/>
          </g>
          <g clip-path="url(#sbc3)">
            <path fill="#e30045" d="M 350 651.796875 C 350 634.886719 350 619.203125 350 603.523438 C 350 562.113281 350.101562 520.695312 349.882812 479.289062 C 349.851562 474.054688 351.121094 470.683594 356.003906 468.113281 C 370.632812 460.417969 385.003906 452.203125 399.464844 444.132812 C 406.910156 439.980469 412.386719 443.007812 412.386719 451.636719 C 412.300781 512.167969 412.214844 572.699219 412.132812 633.230469 C 412.105469 649.859375 410.226562 651.753906 393.949219 651.769531 C 379.441406 651.800781 364.933594 651.796875 350 651.796875 Z"/>
            <path fill="#e30045" d="M 329.800781 476.914062 C 329.800781 535.527344 329.800781 593.046875 329.800781 651.074219 C 318.230469 651.074219 307.109375 651.640625 296.066406 650.660156 C 293.636719 650.449219 289.457031 645.085938 289.214844 641.726562 C 288.410156 630.664062 288.894531 619.53125 288.894531 608.421875 C 288.894531 576.46875 289.042969 544.519531 288.796875 512.566406 C 288.730469 503.328125 291.71875 496.917969 300.484375 492.574219 C 309.898438 488.007812 318.957031 482.003906 329.800781 476.914062 Z"/>
          </g>
        </g>
      </svg>
      <span>Inteligência<br>de Dados</span>
    </div>
    <div class="sb-toggle">
      <button class="sb-toggle-btn" onclick="toggleSidebar()" title="Expandir/Recolher menu">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
    </div>
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
    <div class="theme-track">
      <svg class="theme-icon moon" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
      <svg class="theme-icon sun" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
      <div class="theme-thumb"></div>
    </div>
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
            <path d="M2,17.132c0.415-0.303,0.896-0.678,1.397-0.896c1.505-0.658,3.043-1.239,4.55-1.893c0.279-0.121,0.57-0.394,0.692-0.67c2.3-5.208,6.13-8.722,11.634-10.098c6.901-1.725,12.771,0.22,17.503,5.573c0.312,0.353,0.407,0.72,0.046,1.079c-0.367,0.364-0.688,0.136-1.015-0.116c-5.234-4.027-12.561-4.507-17.989-1.069c-1.286,0.815-2.358,1.973-3.518,2.985c-0.158,0.138-0.26,0.341-0.476,0.635c0.857-0.121,1.569-0.23,2.283-0.322c7.065-0.903,13.925-0.222,20.475,2.735c2.402,1.085,4.641,2.441,6.475,4.375c1.035,1.09,1.932,2.286,1.944,3.889c0.015,1.974-0.891,3.568-2.183,4.963c-2.196,2.372-4.984,3.809-7.939,4.961c-0.736,0.287-1.493,0.52-2.243,0.769c-0.171,0.057-0.365,0.134-0.529,0.102c-0.234-0.046-0.6-0.144-0.64-0.292c-0.059-0.223,0.054-0.556,0.207-0.755c0.148-0.192,0.427-0.3,0.668-0.399c1.35-0.555,2.713-1.079,4.058-1.646c1.858-0.783,3.097-2.078,3.477-4.148c0.523-2.847-0.8-4.787-2.976-6.318c-2.618-1.843-5.589-2.851-8.666-3.575c-3.689-0.869-7.435-1.289-11.223-1.225c-1.58,0.027-3.158,0.21-4.737,0.304c-0.371,0.022-0.564,0.175-0.72,0.516c-0.969,2.115-1.217,4.341-1.018,6.629c0.327,3.748,1.86,6.921,4.567,9.528c0.21,0.202,0.383,0.547,0.395,0.832c0.024,0.581-0.564,0.872-1.103,0.495c-0.93-0.65-1.869-1.319-2.671-2.114c-4.124-4.089-5.672-9.088-5.043-14.821c0.006-0.058,0.001-0.118,0.001-0.294c-0.431,0.084-0.839,0.146-1.237,0.245c-1.062,0.262-2.138,0.484-3.172,0.833C2.719,18.067,2.345,18.015,2.1,17.574C2,17.411,1.9,17.248,2,17.132z"/>
            <path d="M24.552,44.709c0-1.196,0-2.295,0-3.394c0-2.873,0.007-5.747-0.008-8.62c-0.002-0.368,0.088-0.582,0.43-0.762c1.023-0.537,2.021-1.123,3.033-1.682c0.514-0.284,0.863-0.066,0.863,0.528c-0.006,4.218-0.013,8.436-0.019,12.654c-0.002,1.144-0.131,1.276-1.247,1.276C26.614,44.709,25.625,44.709,24.552,44.709z"/>
            <path d="M23.157,33.068c0,4.07,0,7.998,0,12.005c-0.808,0-1.596,0.039-2.373-0.031c-0.17-0.015-0.42-0.41-0.437-0.644c-0.056-0.77-0.022-1.547-0.022-2.322c0-2.221,0.011-4.443-0.007-6.664c-0.004-0.504,0.138-0.835,0.608-1.074C21.658,33.965,22.359,33.527,23.157,33.068z"/>
          </g>
        </svg>
      </div>
      <p class="home-sub">Visão consolidada dos indicadores de qualidade para os Gerentes Seniores. Selecione abaixo para acessar o painel detalhado.</p>
      <div class="home-actions">
        <button class="btn-ghost" onclick="goPage('ajuda')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" stroke="currentColor" stroke-width="1" class="bi bi-question-circle" viewBox="-1 -1 18 18" style="vertical-align:middle;margin-right:6px" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
            <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94"/>
          </svg>
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
        <p>Selecione o <strong>Gerente Sênior</strong> no dropdown — o painel abre imediatamente. O menu lateral permite trocar a qualquer momento. Use <strong>&#8249;</strong> para recolher a sidebar.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Painel de Gráficos</h2>
        <ul>
          <li><strong>Evolução do P-Score por Produto:</strong> gráfico de linhas mostrando cada produto nos últimos 5 meses. Linha pontilhada = projeção do mês seguinte.</li>
          <li><strong>KPIs:</strong> Produtos monitorados, chamados no período e vencidos (igual ao total da tabela).</li>
          <li><strong>P-Score Consolidado:</strong> 10 piores produtos no período. Vermelho &lt;70, âmbar &lt;85, verde ≥85.</li>
          <li><strong>Atendimento por Prazo:</strong> barras empilhadas com subdivisão Dentro/Fora do Prazo para cada status (EM ANÁLISE e EM ANDAMENTO), conforme regra DAX.</li>
          <li><strong>Tendência de Volume:</strong> linha dupla abertos vs concluídos com projeção do próximo mês.</li>
          <li><strong>Causas Raízes (Pareto):</strong> barras ordenadas decrescente com linha de acumulado percentual.</li>
          <li><strong>Volume por Dimensão:</strong> barras agrupadas Chamados e Ocorrências por dimensão de qualidade.</li>
        </ul>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Parecer Analítico</h2>
        <p>Suporta <strong>Markdown</strong>: <code># Título</code>, <code>## Subtítulo</code>, <code>**negrito**</code>, <code>*itálico*</code>, <code>- lista</code>, <code>&gt; citação</code>, <code>---</code>. Clique em <strong>Visualizar</strong> para ver o resultado renderizado.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>Período</h2>
        <p>Boletim: <strong>dois meses completos</strong> anteriores à execução. Tendência e linhas por produto: <strong>5 meses</strong> anteriores.</p>
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

      <!-- 1. Gráfico de linhas produto × mês (PRIMEIRO) -->
      <div class="sec">
        <div class="sec-title">Evolução do P-Score por Produto — Últimos 5 Meses</div>
        <div class="chart-card" id="ccScoreMensal">
          <div class="chart-label">P-Score ponderado mensal por produto · Vermelho &lt;70 · Âmbar &lt;85 · Verde ≥85</div>
          <div class="chart-sublabel" id="scoreMensalSub"></div>
          <div id="legendScoreMensal" class="legend-row"></div>
          <div class="chart-wrap h360"><canvas id="cScoreMensal"></canvas></div>
        </div>
      </div>

      <!-- 2. KPIs -->
      <div class="sec">
        <div class="sec-title">Carteira — Indicadores do Período</div>
        <div class="kpi-grid">
          <div class="kpi-card" id="kc1"><span class="kpi-label">Produtos de Dados</span><span class="kpi-value" id="kProd">—</span><span class="kpi-sub">Monitorados ativamente</span></div>
          <div class="kpi-card" id="kc2"><span class="kpi-label">Chamados no Período</span><span class="kpi-value" id="kTotal">—</span><span class="kpi-sub">Período do boletim</span></div>
          <div class="kpi-card kpi-alert" id="kc3"><span class="kpi-label">Vencidos (&gt; 5 dias)</span><span class="kpi-value" id="kVencidos">—</span><span class="kpi-sub">Sem encerramento · igual ao total da tabela</span></div>
        </div>
      </div>

      <!-- 3. P-Score consolidado do período -->
      <div class="sec">
        <div class="sec-title">P-Score Consolidado do Período (10 piores produtos)</div>
        <div class="chart-card" id="ccScore">
          <div class="chart-label">&#x3A3;(avg_dim &times; dias_dim) / &#x3A3;dias_dim · exclui VARIACAO · valores explícitos em cada barra</div>
          <div class="chart-wrap h320"><canvas id="cScore"></canvas></div>
        </div>
      </div>

      <!-- 4. Status por prazo + Tendência -->
      <div class="sec">
        <div class="sec-title">Atendimento por Prazo e Tendência de Volume</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccStatus">
            <div class="chart-label">Status de Atendimento — Dentro e Fora do Prazo</div>
            <div class="chart-sublabel">EM ANÁLISE: prazo = 5 dias · EM ANDAMENTO: prazo = data de correção</div>
            <div class="legend-row">
              <span class="legend-item"><span class="legend-dot" style="background:#059669"></span>Dentro do Prazo</span>
              <span class="legend-item"><span class="legend-dot" style="background:#DC2626"></span>Fora do Prazo</span>
              <span class="legend-item"><span class="legend-dot" style="background:#6B7280"></span>Encerrados</span>
              <span class="legend-item"><span class="legend-dot" style="background:#D97706"></span>Atu. Qualidade</span>
            </div>
            <div class="chart-wrap h260"><canvas id="cStatus"></canvas></div>
          </div>
          <div class="chart-card" id="ccTrend">
            <div class="chart-label">Tendência Mensal de Volume — Abertos vs Concluídos</div>
            <div class="chart-sublabel">Linha pontilhada = projeção linear do próximo mês</div>
            <div class="chart-wrap h260"><canvas id="cTrend"></canvas></div>
          </div>
        </div>
      </div>

      <!-- 5. Causas + Dimensões -->
      <div class="sec">
        <div class="sec-title">Causas Raízes e Volume por Dimensão de Qualidade</div>
        <div class="chart-grid g2">
          <div class="chart-card" id="ccCausas">
            <div class="chart-label">Causas Raízes — Análise de Pareto (DISTINCTCOUNT de chamados)</div>
            <div class="chart-sublabel">Barras: volume · Linha laranja: % acumulado</div>
            <div class="chart-wrap h280"><canvas id="cCausas"></canvas></div>
          </div>
          <div class="chart-card" id="ccDim">
            <div class="chart-label">Volume por Dimensão de Qualidade</div>
            <div class="chart-sublabel">Azul = Chamados · Rosa = Ocorrências (soma de qocor_quald)</div>
            <div class="chart-wrap h280"><canvas id="cDimensoes"></canvas></div>
          </div>
        </div>
      </div>

      <!-- 6. Tabela vencidos -->
      <div class="sec">
        <div class="sec-title">Chamados Vencidos (&gt; 5 dias sem encerramento)</div>
        <div class="table-card" id="tcVenc">
          <table><thead><tr><th>ID</th><th>Produto</th><th>Dimensão</th><th>Aberto em</th><th>Dias em aberto</th></tr></thead><tbody id="tbVencidos"></tbody></table>
        </div>
      </div>

      <!-- 7. Parecer -->
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

// Registra o plugin datalabels globalmente
Chart.register(ChartDataLabels);

// paleta de linhas para o gráfico de produtos
var _LINE_COLORS = [
  '#C01160','#2563EB','#059669','#D97706','#7C3AED',
  '#0891B2','#DC2626','#65A30D','#DB2777','#EA580C',
  '#4F46E5','#0D9488','#B45309','#7C3AED','#BE185D'
];

function toggleTheme() {{
  document.documentElement.dataset.theme =
    (document.documentElement.dataset.theme === 'dark') ? '' : 'dark';
}}

function toggleSidebar() {{
  _sbCollapsed = !_sbCollapsed;
  var sb = document.getElementById('sidebar');
  var mw = document.getElementById('mainWrap');
  if (_sbCollapsed) {{ sb.classList.add('collapsed'); mw.classList.add('collapsed'); }}
  else {{ sb.classList.remove('collapsed'); mw.classList.remove('collapsed'); }}
  setTimeout(function() {{
    Object.values(_charts).forEach(function(c) {{ try {{ c.resize(); }} catch(e) {{}} }});
  }}, 750);
}}

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

function trocaGerente(g) {{
  if (!g) return;
  _gerente = g;
  document.getElementById('homeSel').value    = g;
  document.getElementById('sidebarSel').value = g;
  renderDash(g);
  goPage('dash');
}}

function renderDash(g) {{
  var d = D[g];
  if (!d) return;
  document.getElementById('dashTitulo').textContent = 'Boletim — ' + g;
  document.getElementById('kProd').textContent      = d.kpis.produtos;
  document.getElementById('kTotal').textContent     = d.kpis.total_chamados;
  document.getElementById('kVencidos').textContent  = d.kpis.vencidos;
  _renderTabela(d.vencidos);
  _destroyCharts();
  _renderScoreMensal(d.scoreMensal);
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

function _destroyCharts() {{
  Object.values(_charts).forEach(function(c) {{ try {{ c.destroy(); }} catch(e) {{}} }});
  _charts = {{}};
}}

function _mkChart(id, cfg) {{
  var el = document.getElementById(id);
  if (!el) return;
  _charts[id] = new Chart(el, cfg);
}}

// ──────────────────────────────────────────────────────
// Projeção linear simples: extrapola 1 ponto além do último
// ──────────────────────────────────────────────────────
function _linearProject(arr) {{
  var vals = arr.filter(function(v) {{ return v !== null && v !== undefined; }});
  if (vals.length < 2) return vals.length === 1 ? vals[0] : null;
  var n = vals.length;
  var sumX=0,sumY=0,sumXY=0,sumX2=0;
  for (var i=0;i<n;i++) {{ sumX+=i;sumY+=vals[i];sumXY+=i*vals[i];sumX2+=i*i; }}
  var slope=(n*sumXY-sumX*sumY)/(n*sumX2-sumX*sumX||1);
  var intercept=(sumY-slope*sumX)/n;
  var proj = slope*(n)+intercept;
  return Math.max(0, Math.round(proj * 100) / 100);
}}

// ──────────────────────────────────────────────────────
// 1. Gráfico de linhas: P-Score produto × mês
// ──────────────────────────────────────────────────────
function _renderScoreMensal(sm) {{
  var leg = document.getElementById('legendScoreMensal');
  var sub = document.getElementById('scoreMensalSub');
  leg.innerHTML = '';
  if (!sm || !sm.meses || !sm.meses.length || !sm.series || !sm.series.length) {{
    sub.textContent = 'Sem dados disponíveis para o período.';
    return;
  }}

  // Calcula mês de projeção (+1)
  var lastMes = sm.meses[sm.meses.length - 1];
  var parts   = lastMes.split('-');
  var projDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1 + 1, 1);
  var projMes  = projDate.getFullYear() + '-' + String(projDate.getMonth()+1).padStart(2,'0');
  var labels   = sm.meses.concat([projMes + ' (proj.)']);

  sub.textContent = 'Produtos: ' + sm.series.length + ' · Último mês: ' + lastMes + ' · Linha pontilhada = projeção';

  var datasets = sm.series.map(function(s, idx) {{
    var cor = _LINE_COLORS[idx % _LINE_COLORS.length];
    // scores reais + projeção
    var realScores = s.scores.map(function(v) {{ return (v === null || v === undefined) ? null : v; }});
    var projVal    = _linearProject(realScores);
    // dataset real
    var dsReal = {{
      label:            s.produto,
      data:             realScores.concat([null]),
      borderColor:      cor,
      backgroundColor:  cor + '22',
      borderWidth:      2.5,
      pointRadius:      5,
      pointHoverRadius: 7,
      pointBackgroundColor: cor,
      tension:          0.35,
      spanGaps:         true,
      datalabels: {{
        display:    true,
        align:      'top',
        anchor:     'center',
        color:      cor,
        font:       {{size:10,weight:'700'}},
        formatter:  function(v) {{ return v === null ? '' : v.toFixed(1); }},
        backgroundColor: 'rgba(255,255,255,0.75)',
        borderRadius: 3,
        padding: {{top:2,bottom:2,left:3,right:3}},
      }}
    }};
    // dataset projeção (pontilhado, mesmo produto)
    var projData = new Array(realScores.length).fill(null);
    // ancora o último real para a linha conectar
    var lastReal = null;
    for (var i = realScores.length-1; i>=0; i--) {{
      if (realScores[i] !== null) {{ lastReal = realScores[i]; break; }}
    }}
    projData.push(projVal);
    var dsProjPrev = new Array(realScores.length).fill(null);
    if (lastReal !== null) dsProjPrev[realScores.length-1] = lastReal;
    dsProjPrev.push(projVal);

    var dsProj = {{
      label:           '',
      data:            dsProjPrev,
      borderColor:     cor,
      borderWidth:     1.8,
      borderDash:      [5,4],
      pointRadius:     [].concat(new Array(realScores.length).fill(0), [5]),
      pointHoverRadius:[].concat(new Array(realScores.length).fill(0), [7]),
      pointBackgroundColor: cor,
      pointStyle:      'triangle',
      tension:         0.25,
      spanGaps:        false,
      datalabels: {{
        display: function(ctx) {{ return ctx.dataIndex === realScores.length; }},
        align:   'right',
        anchor:  'center',
        color:   cor,
        font:    {{size:9,weight:'700',style:'italic'}},
        formatter: function(v) {{ return v === null ? '' : v.toFixed(1); }},
      }}
    }};
    // legenda HTML
    leg.innerHTML += '<span class="legend-item"><span class="legend-dot" style="background:'+cor+'"></span>'+s.produto+'</span>';
    return [dsReal, dsProj];
  }});

  // flatten datasets
  var flat = [];
  datasets.forEach(function(pair) {{ flat.push(pair[0]); flat.push(pair[1]); }});

  _mkChart('cScoreMensal', {{
    type: 'line',
    data: {{ labels: labels, datasets: flat }},
    options: {{
      responsive: true,
      maintainAspectRatio: false,
      interaction: {{ mode:'index', intersect:false }},
      scales: {{
        x: {{ grid:{{ color:'rgba(0,0,0,.04)' }}, ticks:{{ font:{{size:11}} }} }},
        y: {{
          min: 0, max: 100,
          grid:{{ color:'rgba(0,0,0,.04)' }},
          ticks:{{ font:{{size:11}}, callback:function(v){{return v+' pts';}} }},
          // Faixas de cor de fundo
        }}
      }},
      plugins: {{
        legend: {{ display:false }},
        tooltip: {{
          callbacks: {{
            label: function(ctx) {{
              if (ctx.parsed.y === null) return null;
              return ctx.dataset.label ? ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(1) : null;
            }}
          }}
        }},
        annotation: undefined,
        datalabels: {{}}
      }}
    }},
    plugins: [{{
      id:'yBands',
      beforeDraw: function(chart) {{
        var ctx2 = chart.ctx;
        var yA   = chart.scales['y'];
        var xA   = chart.scales['x'];
        var l=xA.left, r=xA.right;
        // Verde >=85
        ctx2.fillStyle='rgba(5,150,105,.06)';
        ctx2.fillRect(l, yA.getPixelForValue(100), r-l, yA.getPixelForValue(85)-yA.getPixelForValue(100));
        // Âmbar 70-85
        ctx2.fillStyle='rgba(217,119,6,.06)';
        ctx2.fillRect(l, yA.getPixelForValue(85), r-l, yA.getPixelForValue(70)-yA.getPixelForValue(85));
        // Vermelho <70
        ctx2.fillStyle='rgba(220,38,38,.06)';
        ctx2.fillRect(l, yA.getPixelForValue(70), r-l, yA.getPixelForValue(0)-yA.getPixelForValue(70));
        // linhas de threshold
        [85,70].forEach(function(thresh,i) {{
          ctx2.save();
          ctx2.strokeStyle = i===0?'rgba(5,150,105,.35)':'rgba(220,38,38,.35)';
          ctx2.setLineDash([6,4]);
          ctx2.lineWidth=1;
          ctx2.beginPath();
          var py=yA.getPixelForValue(thresh);
          ctx2.moveTo(l,py); ctx2.lineTo(r,py);
          ctx2.stroke();
          ctx2.restore();
          ctx2.fillStyle=i===0?'rgba(5,150,105,.7)':'rgba(220,38,38,.7)';
          ctx2.font='bold 9px Inter,sans-serif';
          ctx2.fillText(thresh+' pts',r+4,py+3);
        }});
      }}
    }}]
  }});
}}

// ──────────────────────────────────────────────────────
// 2. P-Score consolidado do período — barras horizontais com datalabels
// ──────────────────────────────────────────────────────
function _renderPScore(ps) {{
  if (!ps || !ps.labels.length) return;
  _mkChart('cScore', {{
    type: 'bar',
    data: {{
      labels: ps.labels,
      datasets: [{{
        label: 'P-Score',
        data:  ps.data,
        borderRadius: 6,
        borderWidth: 0,
        backgroundColor: ps.data.map(function(v) {{
          return v < 70 ? 'rgba(220,38,38,.82)' : v < 85 ? 'rgba(217,119,6,.82)' : 'rgba(5,150,105,.82)';
        }}),
        datalabels: {{
          display: true,
          anchor:  'end',
          align:   'end',
          color:   function(ctx) {{
            var v=ctx.dataset.data[ctx.dataIndex];
            return v<70?'#DC2626':v<85?'#92400E':'#065F46';
          }},
          font:       {{size:11,weight:'700'}},
          formatter:  function(v) {{ return v.toFixed(1)+' pts'; }},
        }}
      }}]
    }},
    options: {{
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: {{ padding:{{ right:60 }} }},
      scales: {{
        x: {{ min:0, max:100, grid:{{color:'rgba(0,0,0,.04)'}}, ticks:{{font:{{size:11}},callback:function(v){{return v+'pts';}}}} }},
        y: {{ ticks:{{font:{{size:11}}}} }}
      }},
      plugins: {{ legend:{{display:false}}, datalabels:{{}} }}
    }}
  }});
}}

// ──────────────────────────────────────────────────────
// 3. Status: barras horizontais empilhadas Dentro/Fora do Prazo
// ──────────────────────────────────────────────────────
function _renderStatus(sc) {{
  if (!sc) return;
  var labels = ['EM ANÁLISE','EM ANDAMENTO','Encerrados','Atu. Qualidade'];
  var dentroData  = [sc.analise_dentro,  sc.andamento_dentro, 0, 0];
  var foraData    = [sc.analise_fora,    sc.andamento_fora,   0, 0];
  var outraData   = [0, 0, sc.encerrados, sc.atuacao];

  var dlBase = {{
    display: true, anchor:'center', align:'center',
    color:'#fff', font:{{size:10,weight:'700'}},
    formatter: function(v) {{ return v > 0 ? v : ''; }},
  }};

  _mkChart('cStatus', {{
    type: 'bar',
    data: {{
      labels: labels,
      datasets: [
        {{ label:'Dentro do Prazo', data:dentroData,  backgroundColor:'rgba(5,150,105,.82)',  borderRadius:4, borderWidth:0, datalabels:dlBase }},
        {{ label:'Fora do Prazo',   data:foraData,    backgroundColor:'rgba(220,38,38,.82)',  borderRadius:4, borderWidth:0, datalabels:dlBase }},
        {{ label:'Encerrados',      data:outraData,   backgroundColor:'rgba(107,114,128,.72)',borderRadius:4, borderWidth:0, datalabels:dlBase }},
        {{ label:'Atu. Qualidade',  data:[0,0,0,sc.atuacao], backgroundColor:'rgba(217,119,6,.82)',borderRadius:4, borderWidth:0, datalabels:dlBase }},
      ]
    }},
    options: {{
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      scales: {{
        x: {{ stacked:true, beginAtZero:true, grid:{{color:'rgba(0,0,0,.04)'}}, ticks:{{font:{{size:11}}}} }},
        y: {{ stacked:true, ticks:{{font:{{size:11}}}} }}
      }},
      plugins: {{
        legend: {{ position:'bottom', labels:{{font:{{size:11}},boxWidth:12,padding:12}} }},
        datalabels: {{}}
      }}
    }}
  }});
}}

// ──────────────────────────────────────────────────────
// 4. Tendência: linha dupla + projeção pontilhada
// ──────────────────────────────────────────────────────
function _renderTendencia(t) {{
  if (!t || !t.meses.length) return;
  var projAb  = _linearProject(t.abertos);
  var projCon = _linearProject(t.concluidos);

  var lastMes = t.meses[t.meses.length-1];
  var parts   = lastMes.split('-');
  var pd      = new Date(parseInt(parts[0]), parseInt(parts[1])-1+1, 1);
  var projMes = pd.getFullYear() + '-' + String(pd.getMonth()+1).padStart(2,'0') + ' (proj.)';
  var labels  = t.meses.concat([projMes]);

  // conecta projeção ao último real
  var abProjArr  = new Array(t.abertos.length).fill(null); abProjArr.push(projAb);
  var conProjArr = new Array(t.concluidos.length).fill(null); conProjArr.push(projCon);
  var abAnchor  = t.abertos.concat([null]); abAnchor[t.abertos.length] = null;
  // anchor: inclui último ponto real
  var abFull    = t.abertos.concat([projAb]);
  var conFull   = t.concluidos.concat([projCon]);
  var abReal    = t.abertos.concat([null]);
  var conReal   = t.concluidos.concat([null]);

  var dlTend = {{
    display: true, align:'top', anchor:'center',
    color: function(ctx) {{ return ctx.dataset.borderColor; }},
    font:{{size:10,weight:'700'}},
    backgroundColor:'rgba(255,255,255,0.8)',
    borderRadius:3,
    padding:{{top:2,bottom:2,left:3,right:3}},
    formatter: function(v) {{ return v === null ? '' : v; }},
  }};

  _mkChart('cTrend', {{
    type: 'line',
    data: {{
      labels: labels,
      datasets: [
        {{
          label:'Abertos', data:abReal,
          borderColor:'#C01160', backgroundColor:'rgba(192,17,96,.1)',
          borderWidth:2.5, pointRadius:5, pointHoverRadius:7,
          pointBackgroundColor:'#C01160', tension:0.3, spanGaps:false,
          datalabels: dlTend
        }},
        {{
          label:'Concluídos', data:conReal,
          borderColor:'#059669', backgroundColor:'rgba(5,150,105,.1)',
          borderWidth:2.5, pointRadius:5, pointHoverRadius:7,
          pointBackgroundColor:'#059669', tension:0.3, spanGaps:false,
          datalabels: dlTend
        }},
        // Projeção abertos
        {{
          label:'Proj. Abertos', data:[].concat(new Array(t.abertos.length-1).fill(null), [t.abertos[t.abertos.length-1]], [projAb]),
          borderColor:'#C01160', borderWidth:1.5, borderDash:[5,4],
          pointRadius:[].concat(new Array(t.abertos.length).fill(0),[5]),
          pointBackgroundColor:'#C01160', tension:0.2, spanGaps:false,
          datalabels:{{ display:function(ctx){{return ctx.dataIndex===t.abertos.length;}}, align:'right', color:'#C01160', font:{{size:10,weight:'700',style:'italic'}}, formatter:function(v){{return v===null?'':v;}} }}
        }},
        // Projeção concluídos
        {{
          label:'Proj. Concluídos', data:[].concat(new Array(t.concluidos.length-1).fill(null), [t.concluidos[t.concluidos.length-1]], [projCon]),
          borderColor:'#059669', borderWidth:1.5, borderDash:[5,4],
          pointRadius:[].concat(new Array(t.concluidos.length).fill(0),[5]),
          pointBackgroundColor:'#059669', tension:0.2, spanGaps:false,
          datalabels:{{ display:function(ctx){{return ctx.dataIndex===t.concluidos.length;}}, align:'right', color:'#059669', font:{{size:10,weight:'700',style:'italic'}}, formatter:function(v){{return v===null?'':v;}} }}
        }}
      ]
    }},
    options: {{
      responsive:true, maintainAspectRatio:false,
      interaction:{{mode:'index',intersect:false}},
      layout:{{padding:{{right:55}}}},
      scales:{{
        x:{{grid:{{color:'rgba(0,0,0,.04)'}},ticks:{{font:{{size:11}}}}}},
        y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}},ticks:{{font:{{size:11}}}}}}
      }},
      plugins:{{
        legend:{{position:'bottom',labels:{{font:{{size:11}},boxWidth:12,padding:12}},filter:function(item){{return item.text.indexOf('Proj.')===0?false:!item.text.startsWith('Proj.');}} }},
        datalabels:{{}}
      }}
    }}
  }});
}}

// ──────────────────────────────────────────────────────
// 5. Causas: Pareto (barras + linha acumulada)
// ──────────────────────────────────────────────────────
function _renderCausas(c) {{
  if (!c || !c.labels.length) return;
  var total = c.data.reduce(function(s,v){{return s+v;}},0) || 1;
  var cum = 0;
  var cumPct = c.data.map(function(v) {{ cum+=v; return Math.round(cum/total*1000)/10; }});

  _mkChart('cCausas', {{
    type: 'bar',
    data: {{
      labels: c.labels,
      datasets: [
        {{
          type:'bar',
          label:'Chamados',
          data: c.data,
          backgroundColor:'rgba(140,15,59,.82)',
          borderRadius:5, borderWidth:0,
          yAxisID:'y',
          datalabels:{{
            display:true, anchor:'end', align:'top',
            color:'#8C0F3B', font:{{size:10,weight:'700'}},
            formatter:function(v){{return v>0?v:'';}}
          }}
        }},
        {{
          type:'line',
          label:'% Acumulado',
          data: cumPct,
          borderColor:'#D97706',
          backgroundColor:'rgba(217,119,6,.1)',
          borderWidth:2.5, pointRadius:5, pointHoverRadius:7,
          pointBackgroundColor:'#D97706',
          tension:0.35,
          yAxisID:'y2',
          datalabels:{{
            display:true, align:'top', anchor:'center',
            color:'#92400E', font:{{size:9,weight:'700'}},
            formatter:function(v){{return v+'%';}}
          }}
        }}
      ]
    }},
    options: {{
      responsive:true, maintainAspectRatio:false,
      layout:{{padding:{{top:20}}}},
      scales:{{
        x:{{grid:{{color:'rgba(0,0,0,.04)'}},ticks:{{font:{{size:10}},maxRotation:30}}}},
        y:{{
          beginAtZero:true, position:'left',
          grid:{{color:'rgba(0,0,0,.04)'}},
          ticks:{{font:{{size:11}}}}
        }},
        y2:{{
          beginAtZero:true, max:100, position:'right',
          grid:{{drawOnChartArea:false}},
          ticks:{{font:{{size:11}},callback:function(v){{return v+'%';}}}}
        }}
      }},
      plugins:{{
        legend:{{position:'bottom',labels:{{font:{{size:11}},boxWidth:12,padding:12}}}},
        datalabels:{{}}
      }}
    }}
  }});
}}

// ──────────────────────────────────────────────────────
// 6. Dimensões: grouped bar Chamados + Ocorrências com datalabels
// ──────────────────────────────────────────────────────
function _renderDimensoes(dim) {{
  if (!dim || !dim.labels.length) return;
  var dl = {{
    display:true, anchor:'end', align:'top',
    font:{{size:10,weight:'700'}},
    formatter:function(v){{return v>0?v:'';}}
  }};
  _mkChart('cDimensoes', {{
    type:'bar',
    data:{{
      labels:dim.labels,
      datasets:[
        {{
          label:'Chamados',
          data:dim.chamados,
          backgroundColor:'rgba(192,17,96,.82)',
          borderRadius:5, borderWidth:0,
          datalabels:{{...dl, color:'#8C0F3B'}}
        }},
        {{
          label:'Ocorrências',
          data:dim.ocorrencias,
          backgroundColor:'rgba(37,99,235,.72)',
          borderRadius:5, borderWidth:0,
          datalabels:{{...dl, color:'#1D4ED8'}}
        }}
      ]
    }},
    options:{{
      responsive:true, maintainAspectRatio:false,
      layout:{{padding:{{top:22}}}},
      scales:{{
        x:{{grid:{{color:'rgba(0,0,0,.04)'}},ticks:{{font:{{size:10}},maxRotation:30}}}},
        y:{{beginAtZero:true,grid:{{color:'rgba(0,0,0,.04)'}},ticks:{{font:{{size:11}}}}}}
      }},
      plugins:{{
        legend:{{position:'bottom',labels:{{font:{{size:11}},boxWidth:12,padding:12}}}},
        datalabels:{{}}
      }}
    }}
  }});
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
  <h2>HTML do Boletim gerado com sucesso</h2>
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
