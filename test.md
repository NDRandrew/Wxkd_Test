# Databricks notebook source
# MAGIC %md
# MAGIC # Boletim de Qualidade de Dados — Gerentes Seniores
# MAGIC Gera um HTML singleton copiável com os **dois meses completos** anteriores à data de execução.

# COMMAND ----------
# =============================================================================
# CÉLULA 1 — IMPORTS E CÁLCULO DO PERÍODO
# =============================================================================

from datetime import date, timedelta
import json
import time
import logging
import msal
import requests as http_requests
from dateutil.relativedelta import relativedelta
from pyspark.sql import functions as F
from pyspark.sql.functions import (
    col, lit, when, upper, initcap, date_format, from_utc_timestamp,
    avg as spark_avg, sum as spark_sum, count as spark_count,
    round as spark_round, first as spark_first, countDistinct,
    current_date, datediff
)

# ---------------------------------------------------------------------------
# Período: dois meses completos anteriores ao mês atual.
# Ex: execução em 20/05/2026 → março (ini) e abril (fim).
# ---------------------------------------------------------------------------
_hoje               = date.today()
_inicio_mes_atual   = _hoje.replace(day=1)

_fim_mes_2          = _inicio_mes_atual - timedelta(days=1)
_ini_mes_2          = _fim_mes_2.replace(day=1)

_fim_mes_1          = _ini_mes_2 - timedelta(days=1)
_ini_mes_1          = _fim_mes_1.replace(day=1)

DATA_INI            = str(_ini_mes_1)
DATA_FIM            = str(_fim_mes_2)

# Tendência: 5 meses completos anteriores ao mês atual
DATA_INI_TENDENCIA  = str((_inicio_mes_atual - relativedelta(months=5)).replace(day=1))
DATA_FIM_TENDENCIA  = str(_fim_mes_2)

MESES_ROTULO = [
    _ini_mes_1.strftime("%b/%y").capitalize(),
    _ini_mes_2.strftime("%b/%y").capitalize(),
]

# Rótulo legível para o cabeçalho do HTML
_meses_pt = {
    1:"Janeiro", 2:"Fevereiro", 3:"Março", 4:"Abril",
    5:"Maio", 6:"Junho", 7:"Julho", 8:"Agosto",
    9:"Setembro", 10:"Outubro", 11:"Novembro", 12:"Dezembro"
}
PERIODO_TXT = (
    f"{_meses_pt[_ini_mes_1.month]}/{_ini_mes_1.year}"
    f" – "
    f"{_meses_pt[_fim_mes_2.month]}/{_fim_mes_2.year}"
)

SCHEMA = "pr_platfun.aaqd_estrt_dados_qld_ucs"

print(f"Período do boletim : {DATA_INI} → {DATA_FIM}")
print(f"Período de tendência: {DATA_INI_TENDENCIA} → {DATA_FIM_TENDENCIA}")
print(f"Rótulos: {MESES_ROTULO}")

# COMMAND ----------
# =============================================================================
# CÉLULA 2 — AUTENTICAÇÃO MS GRAPH + LEITURA SHAREPOINT
# =============================================================================

logging.basicConfig(level=logging.INFO,
    format="%(asctime)s | %(name)s | %(levelname)s | %(message)s")
_log = logging.getLogger("BOLETIM_QLD")

SHAREPOINT_HOST = "bancobradesco.sharepoint.com"
SITE_PATH       = "/sites/EQUIPESUSTENTACAOQUALIDADEID"
LIST_AREAS      = "Sustentacao_Areas"
LIST_GERENTES   = "Sustentacao_GerentesSenior"
LIST_ANALISTAS  = "Sustentacao_Analistas"


class AccessMSGraph:
    _instance = None

    def __new__(cls, *args, **kwargs):
        if cls._instance is None:
            cls._instance = super().__new__(cls)
        return cls._instance

    def __init__(self, tenant_id: str = "ccd25372-eb59-436a-ad74-78a49d784cf3"):
        if not hasattr(self, "_initialized"):
            self.host         = "https://graph.microsoft.com/v1.0"
            self.tenant_id    = tenant_id
            self.app          = None
            self.token_cache  = None
            self.access_token = ""
            self._initialized = True

    def _get_environment(self) -> str:
        tags = json.loads(spark.conf.get("spark.databricks.clusterUsageTags.clusterAllTags"))
        return next(t for t in tags if t["key"] == "ambiente")["value"]

    def _get_secret_api_token(self) -> str:
        ambiente = self._get_environment()
        _log.info("Ambiente: %s", ambiente.upper())
        env_config = {
            "dv": ("kvazudvbraaaqd001", "scrt-qld-dv-tkn-shp", "21083960-effc-48f5-975f-d7994fbc4ad8"),
            "ho": ("kvazuhobraaaqd001", "scrt-qld-hm-tkn-shp", "d85b93fc-d0f9-4b13-8918-3e32f9d4454a"),
            "pr": ("kvazuprbraaaqd001", "scrt-qld-pr-tkn-shp", "902b6357-3083-4bb1-b0b1-3ef64c68af57"),
        }
        if ambiente not in env_config:
            raise RuntimeError(f"Ambiente '{ambiente}' não suportado.")
        scope, key, client_id = env_config[ambiente]
        self.client_id = client_id
        return dbutils.secrets.get(scope=scope, key=key)

    def _create_app_msal(self):
        secret    = self._get_secret_api_token()
        authority = f"https://login.microsoftonline.com/{self.tenant_id}"
        self.app  = msal.ConfidentialClientApplication(
            self.client_id, authority=authority, client_credential=secret)

    def _is_token_valid(self) -> bool:
        if not self.access_token: return False
        return int(self.token_cache[0]["extended_expires_on"]) > time.time()

    def get_token(self) -> str:
        if self.app is None: self._create_app_msal()
        if self._is_token_valid(): return self.access_token
        result = self.app.acquire_token_for_client(
            scopes=["https://graph.microsoft.com/.default"])
        if "access_token" in result:
            self.access_token = result["access_token"]
            self.token_cache  = self.app.token_cache.find(
                msal.TokenCache.CredentialType.ACCESS_TOKEN)
        return self.access_token


def _read_sharepoint_list(list_name: str, prefix: str = ""):
    auth    = AccessMSGraph()
    token   = auth.get_token()
    headers = {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}

    site_resp = http_requests.get(
        f"{auth.host}/sites/{SHAREPOINT_HOST}:{SITE_PATH}",
        headers=headers, timeout=60)
    site_resp.raise_for_status()
    site_id = site_resp.json()["id"]

    all_rows  = []
    items_url = (f"{auth.host}/sites/{site_id}/lists/{list_name}/items"
                 "?$expand=fields&$top=200")
    while items_url:
        resp = http_requests.get(items_url, headers=headers, timeout=60)
        resp.raise_for_status()
        data      = resp.json()
        all_rows.extend(data.get("value", []))
        items_url = data.get("@odata.nextLink")

    _log.info("'%s': %d itens", list_name, len(all_rows))
    records = [item.get("fields", {}) for item in all_rows]
    if not records:
        return spark.createDataFrame([], schema="dummy STRING")

    all_keys   = sorted({k for r in records for k in r.keys()})
    normalized = [{k: str(r.get(k, "")) for k in all_keys} for r in records]
    df = spark.createDataFrame(normalized)
    if prefix:
        for c in df.columns:
            df = df.withColumnRenamed(c, f"{prefix}{c}")
    return df


df_areas     = _read_sharepoint_list(LIST_AREAS)
df_gerentes  = _read_sharepoint_list(LIST_GERENTES, prefix="gs_")
df_analistas = _read_sharepoint_list(LIST_ANALISTAS, prefix="an_")

df_ger_an = df_gerentes.join(
    df_analistas,
    df_gerentes["gs_analistaLookupId"] == df_analistas["an_id"],
    how="left")
df_sharepoint_score = df_areas.join(
    df_ger_an,
    df_areas["gerente_seniorLookupId"] == df_ger_an["gs_id"],
    how="left")

df_sharepoint_score.createOrReplaceTempView("vw_sharepoint_score")
_log.info("vw_sharepoint_score — %d linhas", df_sharepoint_score.count())

# COMMAND ----------
# =============================================================================
# CÉLULA 3 — CARGA E NORMALIZAÇÃO DOS CHAMADOS (CLOUD / HIVE / SAS)
# =============================================================================

def _fix_after_load(df):
    string_cols = [
        "AMBIENTE","ÁREA","plataforma_afericao","DIMENSÃO","COMENTÁRIOS",
        "REGRA","ExecucaoQualidade","Categoria","produto_de_dados",
        "CAMADA","STATUS","prioridade"
    ]
    df = df.select(*[
        when(upper(col(c)).isin("N/D","ND"), "").otherwise(upper(col(c))).alias(c)
        if c in string_cols and c in df.columns else col(c)
        for c in df.columns
    ])
    if "DATA DO ENCERRAMENTO" in df.columns:
        df = (df
            .withColumn("DATA DO ENCERRAMENTO",
                date_format(col("DATA DO ENCERRAMENTO"), "dd/MM/yyyy").cast("string"))
            .withColumn("DATA DO ENCERRAMENTO",
                when(col("DATA DO ENCERRAMENTO") == "01/01/1900", "")
                .otherwise(col("DATA DO ENCERRAMENTO"))))
    if "PRAZO DE CORREÇÃO" in df.columns:
        df = df.withColumn("PRAZO DE CORREÇÃO",
            date_format(col("PRAZO DE CORREÇÃO"), "dd/MM/yyyy").cast("string"))
    if "ExecucaoQualidade" in df.columns:
        df = df.withColumn("ExecucaoQualidade",
            date_format(col("ExecucaoQualidade"), "dd/MM/yyyy").cast("string"))
    if "DETALHAMENTO DO PROBLEMA" in df.columns:
        df = df.withColumn("DETALHAMENTO DO PROBLEMA",
            when(upper(col("DETALHAMENTO DO PROBLEMA")).isin("N/D","ND"), "")
            .otherwise(col("DETALHAMENTO DO PROBLEMA")))
    if "Categoria" in df.columns:
        df = (df
            .withColumn("Categoria", initcap(col("Categoria")))
            .withColumn("Categoria",
                when(col("Categoria") == "Produto De Dados", "Produto de dados")
                .when(col("Categoria") == "Dados Brutos",    "Ingestão")
                .when(col("Categoria") == "Ativo De Dados",  "Ativo")
                .when(col("Categoria") == "Ingestao",        "Ingestão")
                .otherwise(col("Categoria"))))
    if "plataforma_afericao" in df.columns:
        df = df.withColumn("plataforma_afericao", initcap(col("plataforma_afericao")))
    if "STATUS" in df.columns:
        df = df.withColumn("STATUS",
            when(col("STATUS") == "CONCLUIDO",  "CONCLUÍDO")
            .when(col("STATUS") == "EM ANALISE","EM ANÁLISE")
            .otherwise(col("STATUS")))
    if "DIMENSÃO" in df.columns:
        df = df.withColumn("DIMENSÃO",
            when(col("DIMENSÃO") == "CONSISTENCIA","CONSISTÊNCIA")
            .otherwise(col("DIMENSÃO")))
    return df


def _load_chamados_cloud():
    raw = spark.sql(f"""
        SELECT a.*, a.cidtfd_app AS ID_DE,
            b.icatlg_anald AS catalogo_id, c.idbase_anald AS database_id,
            d.itbela_anald AS tabela_id,   a.icluna_anald AS campo,
            a.idmsao_quald AS dimensao,    e.iarea_quald,
            f.iprodt_dados,                a.rerro_quald AS descricao_erro_existente,
            a.hcriac_item_app AS created,  a.rdetlh_probl AS detalhamento_do_problema,
            a.hult_mudca_sttus AS status_chn_hr
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_catlg_dados b ON a.cidtfd_catlg_anald = b.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_dados c ON a.cidtfd_dbase_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados  e ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'cloud'
    """)
    renamed = raw.withColumnsRenamed({
        "cidtfd_app":"ID_ITEM","iambte_quald":"AMBIENTE","iarea_quald":"ÁREA",
        "iplatf_aferc":"plataforma_afericao","idmsao_quald":"DIMENSÃO",
        "eemail_resp_tecni":"responsaveis_tecnicos","eemail_resp_negoc":"responsaveis_negocio",
        "rcomen_anlse":"COMENTÁRIOS","idescr_regra_anald":"REGRA",
        "dexcuc_reg_invld":"ExecucaoQualidade","icateg_anald":"Categoria",
        "iprodt_dados":"produto_de_dados","catalogo_id":"Catalogo","database_id":"Banco",
        "tabela_id":"tabela","campo":"campo","icmada_anald":"CAMADA","rsttus_chmad":"STATUS",
        "rerro_quald":"DESCRIÇÃO DO ERRO","rdetlh_probl":"DETALHAMENTO DO PROBLEMA",
        "dencrr_reg_invld":"DATA DO ENCERRAMENTO","iprior_resol":"prioridade",
        "dprz_corrc_reg_invld":"PRAZO DE CORREÇÃO","hcriac_item_app":"CRIADO_EM",
        "hult_mudca_sttus":"ULTIMA_ALTERA_STATUS",
    })
    return _fix_after_load(renamed.select(
        "ID_ITEM","AMBIENTE","ÁREA","plataforma_afericao","DIMENSÃO",
        "responsaveis_tecnicos","responsaveis_negocio","COMENTÁRIOS","REGRA",
        "ExecucaoQualidade","Categoria","produto_de_dados","Catalogo","Banco",
        "tabela","campo","CAMADA","STATUS","DESCRIÇÃO DO ERRO","DETALHAMENTO DO PROBLEMA",
        "DATA DO ENCERRAMENTO","prioridade","PRAZO DE CORREÇÃO","CRIADO_EM","ULTIMA_ALTERA_STATUS"
    ))


def _load_chamados_hive():
    raw = spark.sql(f"""
        SELECT a.*, a.cidtfd_app AS ID_DE,
            c.idbase_anald AS database_id, d.itbela_anald AS tabela_id,
            a.icluna_anald AS campo,       a.idmsao_quald AS dimensao,
            e.iarea_quald, f.iprodt_dados,
            a.rerro_quald AS descricao_erro_existente,
            a.hcriac_item_app AS created,  a.rdetlh_probl AS detalhamento_do_problema
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_dados c ON a.cidtfd_dbase_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados  e ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'hive' OR a.iambte_quald == 'teradata'
    """)
    return _fix_after_load(
        raw.withColumnsRenamed({
            "cidtfd_app":"ID_ITEM","iambte_quald":"AMBIENTE","iarea_quald":"ÁREA",
            "idmsao_quald":"DIMENSÃO","eemail_resp_tecni":"responsaveis_tecnicos",
            "eemail_resp_negoc":"responsaveis_negocio","iplatf_aferc":"plataforma_afericao",
            "rcomen_anlse":"COMENTÁRIOS","idescr_regra_anald":"REGRA",
            "dexcuc_reg_invld":"ExecucaoQualidade","database_id":"DatabaseOnprem",
            "tabela_id":"TabelaOnprem","campo":"campo","rsafra_dbase":"SAFRA",
            "rsttus_chmad":"STATUS","rerro_quald":"DESCRIÇÃO DO ERRO",
            "rdetlh_probl":"DETALHAMENTO DO PROBLEMA","dencrr_reg_invld":"DATA DO ENCERRAMENTO",
        })
        .withColumn("SCRIPT DE CARGA", lit(""))
        .select("ID_ITEM","AMBIENTE","ÁREA","plataforma_afericao","DIMENSÃO",
                "responsaveis_tecnicos","responsaveis_negocio","COMENTÁRIOS","REGRA",
                "ExecucaoQualidade","SAFRA","DatabaseOnprem","TabelaOnprem","campo",
                "STATUS","DESCRIÇÃO DO ERRO","DETALHAMENTO DO PROBLEMA","DATA DO ENCERRAMENTO")
    )


def _load_chamados_sas():
    raw = spark.sql(f"""
        SELECT a.*, a.cidtfd_app AS ID_DE,
            c.itbela_orige_sas_anald AS libname_id, d.itbela_anald AS tabela_id,
            a.icluna_anald AS campo, a.idmsao_quald AS dimensao,
            e.iarea_quald, f.iprodt_dados,
            a.rerro_quald AS descricao_erro_existente,
            a.hcriac_item_app AS created, a.rdetlh_probl AS detalhamento_do_problema
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_tbela_sas_dados c ON a.cidtfd_sas_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados  e ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'sas'
    """)
    return _fix_after_load(
        raw.withColumnsRenamed({
            "cidtfd_app":"ID_ITEM","iambte_quald":"AMBIENTE","iarea_quald":"ÁREA",
            "idmsao_quald":"DIMENSÃO","eemail_resp_tecni":"responsaveis_tecnicos",
            "eemail_resp_negoc":"responsaveis_negocio","rcomen_anlse":"COMENTÁRIOS",
            "idescr_regra_anald":"REGRA","dexcuc_reg_invld":"ExecucaoQualidade",
            "libname_id":"LibnameOnprem","tabela_id":"TabelaOnprem","campo":"campo",
            "rsafra_dbase":"SAFRA","rsttus_chmad":"STATUS","rerro_quald":"DESCRIÇÃO DO ERRO",
            "rdetlh_probl":"DETALHAMENTO DO PROBLEMA","dencrr_reg_invld":"DATA DO ENCERRAMENTO",
        })
        .withColumn("SCRIPT DE CARGA", lit(""))
        .select("ID_ITEM","AMBIENTE","ÁREA","DIMENSÃO","responsaveis_tecnicos",
                "responsaveis_negocio","COMENTÁRIOS","REGRA","ExecucaoQualidade",
                "SAFRA","LibnameOnprem","TabelaOnprem","campo","SCRIPT DE CARGA",
                "STATUS","DATA DO ENCERRAMENTO","DETALHAMENTO DO PROBLEMA","DESCRIÇÃO DO ERRO")
    )


df_chamados_cloud = _load_chamados_cloud()
df_chamados_hive  = _load_chamados_hive()
df_chamados_sas   = _load_chamados_sas()

df_chamados_cloud.createOrReplaceTempView("vw_chamados_cloud")

# COMMAND ----------
# =============================================================================
# CÉLULA 4 — P-SCORE PONDERADO (período do boletim, exclui VARIACAO)
# Fórmula: SUM(AVG_dim × dias_dim) / SUM(dias_dim)
# =============================================================================

_JOINS_SCORE = f"""
    FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
            ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_descr_mntrc AS c
            ON a.ndescr_mntrc = c.NDESCR_MNTRC
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_ambte_quald_dados AS d
            ON a.nambte_tbela = d.nambte_tbela
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_regra_ctrl_vgcia AS e
            ON a.nregra_ctrl_vgcia = e.nregra_ctrl_vgcia
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
            ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_sttus_mntrc AS g
            ON a.nsttus_mntrc = g.nsttus_mntrc
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
            ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
        INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_data_quald_dados AS i
            ON a.dini_excuc_quald = i.dcoplt_quald
"""

_score_rows = spark.sql(f"""
    WITH sq_base AS (
        SELECT a.dini_excuc_quald, h.itpo_prodt_funcl,
               f.idmsao_quald_dados, a.pscore_calcd
        {_JOINS_SCORE}
        WHERE h.itpo_prodt = 'PRODUTO DE DADOS'
          AND a.dini_excuc_quald BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
          AND a.nsttus_mntrc = 1
          AND f.idmsao_quald_dados != 'VARIACAO'
    ),
    dias_count AS (
        SELECT itpo_prodt_funcl, idmsao_quald_dados,
               COUNT(DISTINCT dini_excuc_quald) AS dias_execucao
        FROM sq_base GROUP BY itpo_prodt_funcl, idmsao_quald_dados
    ),
    joined AS (
        SELECT sp.gs_Title AS GERENTE_SENIOR, sp.produto AS NOME_PRODUTO,
               sq.idmsao_quald_dados AS DIMENSAO, sq.pscore_calcd AS PSCORE,
               dc.dias_execucao AS DIAS_EXECUCAO
        FROM vw_sharepoint_score sp
        INNER JOIN sq_base sq   ON sp.produto = sq.itpo_prodt_funcl
        INNER JOIN dias_count dc
            ON sq.itpo_prodt_funcl = dc.itpo_prodt_funcl
           AND sq.idmsao_quald_dados = dc.idmsao_quald_dados
    ),
    dim_agg AS (
        SELECT GERENTE_SENIOR, NOME_PRODUTO, DIMENSAO,
               AVG(PSCORE) AS AVG_SCORE_DIM, SUM(DIAS_EXECUCAO) AS DIAS_DIM
        FROM joined GROUP BY GERENTE_SENIOR, NOME_PRODUTO, DIMENSAO
    )
    SELECT GERENTE_SENIOR, NOME_PRODUTO,
           ROUND(SUM(AVG_SCORE_DIM * DIAS_DIM) / NULLIF(SUM(DIAS_DIM),0), 2) AS PSCORE_PONDERADO
    FROM dim_agg
    GROUP BY GERENTE_SENIOR, NOME_PRODUTO
    HAVING SUM(DIAS_DIM) > 0
""").collect()

score_dict = {}
for row in _score_rows:
    g = row["GERENTE_SENIOR"]
    score_dict.setdefault(g, [])
    if row["PSCORE_PONDERADO"] is not None:
        score_dict[g].append({"produto": row["NOME_PRODUTO"],
                               "pscore": round(float(row["PSCORE_PONDERADO"]), 2)})

# COMMAND ----------
# =============================================================================
# CÉLULA 5 — KPIs DA CARTEIRA POR GERENTE
# =============================================================================

kpis_dict = {}
for row in (df_sharepoint_score.groupBy("gs_Title","categoria")
            .agg(countDistinct("produto").alias("qtd")).collect()):
    g   = row["gs_Title"]
    cat = (row["categoria"] or "OUTROS").upper()
    kpis_dict.setdefault(g, {})[cat] = row["qtd"]

# COMMAND ----------
# =============================================================================
# CÉLULA 6 — RESUMO DE CHAMADOS CLOUD POR GERENTE (período do boletim)
# =============================================================================

resumo_rows = spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE_SR,
        COUNT(CASE WHEN ch.STATUS = 'CONCLUÍDO'              THEN 1 END) AS QTD_CONCLUIDO,
        COUNT(CASE WHEN ch.STATUS = 'CANCELADO'              THEN 1 END) AS QTD_CANCELADO,
        COUNT(CASE WHEN ch.STATUS = 'EM ANÁLISE'             THEN 1 END) AS QTD_EM_ANALISE,
        COUNT(CASE WHEN ch.STATUS = 'EM ANDAMENTO'           THEN 1 END) AS QTD_EM_ANDAMENTO,
        COUNT(CASE WHEN ch.STATUS = 'ATUACAO QUALIDADE'      THEN 1 END) AS QTD_ATUACAO_QUALIDADE,
        COUNT(CASE WHEN ch.STATUS = 'ENCERRADO SEM RESPOSTA' THEN 1 END) AS QTD_ENCERRADO_SEM_RESPOSTA,
        COUNT(CASE
            WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
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
        "concluido":0,"cancelado":0,"em_analise":0,"em_andamento":0,
        "atuacao_qualidade":0,"encerrado_sem_resposta":0,"abertos_5_dias":0,"total":0
    })
    d = resumo_dict[g]
    d["concluido"]              += row["QTD_CONCLUIDO"]              or 0
    d["cancelado"]              += row["QTD_CANCELADO"]              or 0
    d["em_analise"]             += row["QTD_EM_ANALISE"]             or 0
    d["em_andamento"]           += row["QTD_EM_ANDAMENTO"]           or 0
    d["atuacao_qualidade"]      += row["QTD_ATUACAO_QUALIDADE"]      or 0
    d["encerrado_sem_resposta"] += row["QTD_ENCERRADO_SEM_RESPOSTA"] or 0
    d["abertos_5_dias"]         += row["QTD_ABERTOS_MAIS_5_DIAS"]    or 0
    d["total"]                  += row["QTD_TOTAL"]                  or 0

# COMMAND ----------
# =============================================================================
# CÉLULA 7 — TENDÊNCIA MENSAL (5 meses completos anteriores ao atual)
# =============================================================================

tendencia_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE,
           date_format(ch.CRIADO_EM,'yyyy-MM') AS MES,
           COUNT(CASE WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA') THEN 1 END) AS ABERTOS,
           COUNT(CASE WHEN ch.STATUS = 'CONCLUÍDO' THEN 1 END) AS CONCLUIDOS
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

# COMMAND ----------
# =============================================================================
# CÉLULA 8 — CAUSAS RAÍZES OPERACIONAIS (chamados ativos no período)
# =============================================================================

causas_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%CARGA EM ATRASO%' THEN 1 END) AS CARGA_EM_ATRASO,
        COUNT(CASE WHEN upper(ch.REGRA) LIKE '%REGRA\\_LITERAL%IS NULL%'  THEN 1 END) AS REGRA_IS_NULL,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%SEM CARGA%'        THEN 1 END) AS SEM_CARGA,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%INCONSIST%'        THEN 1 END) AS INCONSISTENCIA,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%DUPLICI%'          THEN 1 END) AS DUPLICIDADE
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title
""").collect():
    g = row["GERENTE"]
    causas_dict[g] = {
        "Carga em Atraso": int(row["CARGA_EM_ATRASO"] or 0),
        "Regra IS NULL":   int(row["REGRA_IS_NULL"]   or 0),
        "Sem Carga":       int(row["SEM_CARGA"]        or 0),
        "Inconsistência":  int(row["INCONSISTENCIA"]   or 0),
        "Duplicidade":     int(row["DUPLICIDADE"]      or 0),
    }

# COMMAND ----------
# =============================================================================
# CÉLULA 9 — CHAMADOS VENCIDOS > 5 DIAS (sem encerramento)
# O card KPI usa este mesmo conjunto — garantia de consistência.
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

# COMMAND ----------
# =============================================================================
# CÉLULA 10 — DISTRIBUIÇÃO POR DIMENSÃO (período do boletim)
# =============================================================================

dimensao_dict = {}
for row in spark.sql(f"""
    SELECT sp.gs_Title AS GERENTE, ch.`DIMENSÃO` AS DIMENSAO, COUNT(ch.ID_ITEM) AS QTD
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title, ch.`DIMENSÃO`
    ORDER BY sp.gs_Title, QTD DESC
""").collect():
    g = row["GERENTE"]
    dimensao_dict.setdefault(g, {"labels":[], "data":[]})
    if row["DIMENSAO"]:
        dimensao_dict[g]["labels"].append(row["DIMENSAO"])
        dimensao_dict[g]["data"].append(int(row["QTD"] or 0))

# COMMAND ----------
# =============================================================================
# CÉLULA 11 — MONTAGEM DO JSON FINAL
# O KPI de vencidos é derivado de vencidos_dict para garantir que o card
# e a tabela mostrem exatamente o mesmo número.
# =============================================================================

lista_gerentes = sorted([
    row["gs_Title"]
    for row in df_sharepoint_score.select("gs_Title").distinct().collect()
    if row["gs_Title"]
])

dados_gerentes = {}
for gerente in lista_gerentes:
    kpis_g   = kpis_dict.get(gerente, {})
    resumo_g = resumo_dict.get(gerente, {
        "concluido":0,"cancelado":0,"em_analise":0,"em_andamento":0,
        "atuacao_qualidade":0,"encerrado_sem_resposta":0,"abertos_5_dias":0,"total":0
    })
    vencidos_g = vencidos_dict.get(gerente, [])

    # KPI de vencidos = contagem real da lista de vencidos (consistência card ↔ tabela)
    qtd_vencidos_real = len(vencidos_g)

    scores_g   = sorted(score_dict.get(gerente, []), key=lambda x: x["pscore"])[:10]
    causas_g   = causas_dict.get(gerente, {})
    top_causas = sorted([(k,v) for k,v in causas_g.items() if v > 0],
                        key=lambda x: x[1], reverse=True)[:5]

    dados_gerentes[gerente] = {
        "periodo":      f"{DATA_INI} a {DATA_FIM}",
        "mesesRotulo":  MESES_ROTULO,
        "kpis": {
            "produtos":         kpis_g.get("PRODUTO DE DADOS", 0),
            "total_chamados":   resumo_g["total"],
            "vencidos":         qtd_vencidos_real,   # fonte única para card e tabela
        },
        "statusChamados": [
            resumo_g["concluido"] + resumo_g["cancelado"] + resumo_g["encerrado_sem_resposta"],
            resumo_g["em_andamento"] + resumo_g["atuacao_qualidade"],
            resumo_g["em_analise"],
        ],
        "pscore":      {"labels": [s["produto"] for s in scores_g],
                        "data":   [s["pscore"]  for s in scores_g]},
        "causasRaizes":{"labels": [c[0] for c in top_causas],
                        "data":   [c[1] for c in top_causas]},
        "tendencia":   tendencia_dict.get(gerente, {"meses":[],"abertos":[],"concluidos":[]}),
        "dimensoes":   dimensao_dict.get(gerente, {"labels":[],"data":[]}),
        "vencidos":    vencidos_g[:15],
    }
# COMMAND ----------
# =============================================================================
# CÉLULA 12 — GERAÇÃO DO HTML SINGLETON
# =============================================================================

_json_dados   = json.dumps(dados_gerentes, ensure_ascii=False)
_SC           = "<" + "/script>"   # evita fechar a tag prematuramente no f-string
_SS           = "<" + "/style>"
_ST           = "<" + "/textarea>"

_HTML = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Boletim de Qualidade de Dados — {PERIODO_TXT}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">{_SC}
<style>
/* ── Variáveis e reset ── */
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
html,body{{height:100%;font-family:'Inter',sans-serif;background:var(--g50);color:var(--g800)}}

/* ── Layout base (sidebar fixa + conteúdo) ── */
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
.sidebar-logo{{display:flex;align-items:center;gap:10px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,.18);}}
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

/* Seletor de gerente na sidebar */
.sidebar-sel-wrap{{margin-top:4px;padding:10px 12px;background:rgba(0,0,0,.18);border-radius:10px;}}
.sidebar-sel-wrap label{{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.7;display:block;margin-bottom:5px}}
.sidebar-sel{{width:100%;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 8px;border-radius:8px;font-size:.8rem;font-family:inherit;outline:none;cursor:pointer}}
.sidebar-sel option{{background:#8C0F3B;color:#fff}}

/* Tema toggle na base da sidebar */
.theme-row{{margin-top:auto;display:flex;align-items:center;justify-content:space-between;background:rgba(0,0,0,.18);padding:10px 12px;border-radius:10px;cursor:pointer;}}
.theme-row span{{font-size:.75rem;font-weight:600;opacity:.75}}
.theme-track{{width:40px;height:22px;background:rgba(255,255,255,.25);border-radius:11px;position:relative;transition:background .3s}}
.theme-thumb{{width:16px;height:16px;background:#fff;border-radius:50%;position:absolute;top:3px;left:3px;transition:transform .3s ease}}
[data-theme="dark"] .theme-thumb{{transform:translateX(18px)}}

/* ── Área de conteúdo ── */
.main-wrap{{margin-left:var(--sb-w);flex:1;height:100vh;overflow:hidden;position:relative}}

/* ── Telas (pages) ── */
.page{{
  position:absolute;inset:0;overflow-y:auto;
  opacity:0;visibility:hidden;
  transform:translateX(40px);
  transition:opacity .45s ease,transform .45s ease,visibility .45s;
}}
.page.active{{opacity:1;visibility:visible;transform:translateX(0)}}

/* ============================================================
   TELA HOME
   ============================================================ */
.page-home{{
  background:linear-gradient(135deg,var(--pr) 0%,var(--pr-dk) 55%,#5E0827 100%);
  display:flex;flex-direction:column;justify-content:center;align-items:flex-start;
  padding:7% 8%;position:relative;overflow:hidden;
}}
.home-bg-circle{{
  position:absolute;right:-8%;top:-15%;
  width:55%;padding-top:55%;border-radius:50%;
  background:radial-gradient(ellipse,rgba(255,255,255,.07) 0%,transparent 70%);
  pointer-events:none;
}}
.home-bg-circle2{{
  position:absolute;right:12%;bottom:-20%;
  width:35%;padding-top:35%;border-radius:50%;
  background:radial-gradient(ellipse,rgba(255,255,255,.05) 0%,transparent 70%);
  pointer-events:none;
}}
.home-content{{position:relative;z-index:2;max-width:640px}}
.home-kicker{{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
  border-radius:20px;padding:5px 14px;
  font-size:.75rem;font-weight:700;color:rgba(255,255,255,.9);
  text-transform:uppercase;letter-spacing:.07em;margin-bottom:24px;
}}
.home-h1{{
  font-size:clamp(2.4rem,5vw,4rem);font-weight:900;color:#fff;
  line-height:1.08;margin-bottom:16px;
}}
.home-sub{{
  font-size:clamp(.9rem,1.5vw,1.1rem);color:rgba(255,255,255,.75);
  line-height:1.6;margin-bottom:36px;max-width:520px;
}}
.home-actions{{display:flex;align-items:center;gap:14px;flex-wrap:wrap}}
.btn-primary{{
  background:#fff;color:var(--pr-dk);padding:12px 28px;border-radius:10px;
  font-size:.9rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;
  transition:transform .2s,box-shadow .2s;box-shadow:0 4px 16px rgba(0,0,0,.18);
}}
.btn-primary:hover{{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.22)}}
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
.home-decoration{{
  position:absolute;bottom:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,rgba(255,255,255,.35),rgba(255,255,255,.08),rgba(255,255,255,.35));
}}

/* ============================================================
   TELA AJUDA
   ============================================================ */
.page-ajuda{{background:var(--wh)}}
.ajuda-inner{{max-width:780px;margin:0 auto;padding:48px 40px}}
.ajuda-header{{
  background:linear-gradient(135deg,var(--pr),var(--pr-dk));
  border-radius:var(--radius-lg);padding:28px 32px;color:#fff;margin-bottom:32px;
}}
.ajuda-header h1{{font-size:1.5rem;font-weight:800;margin-bottom:6px}}
.ajuda-header p{{font-size:.88rem;opacity:.85;line-height:1.5}}
.ajuda-card{{
  background:var(--g50);border:1px solid var(--g200);border-radius:var(--radius);
  padding:22px 24px;margin-bottom:16px;
  border-left:4px solid var(--pr);
}}
.ajuda-card h2{{font-size:.95rem;font-weight:700;color:var(--pr-dk);margin-bottom:10px;display:flex;align-items:center;gap:8px}}
.ajuda-card p,.ajuda-card li{{font-size:.85rem;color:var(--g700);line-height:1.65}}
.ajuda-card ul{{padding-left:18px;margin-top:6px}}
.ajuda-card li{{margin-bottom:6px}}
.ajuda-back{{
  display:inline-flex;align-items:center;gap:8px;
  background:var(--pr);color:#fff;padding:10px 20px;border-radius:10px;
  font-size:.85rem;font-weight:700;border:none;cursor:pointer;
  font-family:inherit;margin-bottom:28px;transition:background .2s;
}}
.ajuda-back:hover{{background:var(--pr-dk)}}

/* ============================================================
   TELA DE GRÁFICOS (DASHBOARD)
   ============================================================ */
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
.dash-header-right{{display:flex;align-items:center;gap:10px}}
.dash-theme-btn{{
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);
  color:#fff;padding:6px 12px;border-radius:8px;font-size:.78rem;
  cursor:pointer;font-family:inherit;font-weight:600;transition:background .2s;
}}
.dash-theme-btn:hover{{background:rgba(255,255,255,.25)}}
.dash-body{{padding:24px 32px 40px;}}

/* Seções do dashboard */
.sec{{margin-bottom:28px}}
.sec-title{{
  font-size:.92rem;font-weight:700;color:var(--pr-dk);
  margin-bottom:14px;display:flex;align-items:center;gap:8px;
}}
.sec-title::before{{
  content:'';display:inline-block;width:4px;height:16px;
  background:linear-gradient(180deg,var(--pr),var(--pr-dk));
  border-radius:2px;flex-shrink:0;
}}

/* Cards KPI */
.kpi-grid{{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}}
.kpi-card{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  padding:20px;display:flex;flex-direction:column;gap:4px;
  box-shadow:var(--sh);
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease,box-shadow .25s;
}}
.kpi-card.revealed{{opacity:1;transform:translateY(0)}}
.kpi-card:hover{{box-shadow:var(--sh2);transform:translateY(-2px)}}
.kpi-card.revealed:hover{{transform:translateY(-2px)}}
.kpi-label{{font-size:.7rem;font-weight:700;color:var(--g600);text-transform:uppercase;letter-spacing:.05em}}
.kpi-value{{font-size:2.2rem;font-weight:900;color:var(--pr);line-height:1.05}}
.kpi-sub{{font-size:.72rem;color:var(--g600)}}
.kpi-card.kpi-alert .kpi-value{{color:var(--red)}}

/* Chart cards */
.chart-grid{{display:grid;gap:16px}}
.g2{{grid-template-columns:1fr 1fr}}
.g3{{grid-template-columns:1fr 1fr 1fr}}
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

/* Tabela vencidos */
.table-card{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  overflow:hidden;box-shadow:var(--sh);
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease;
}}
.table-card.revealed{{opacity:1;transform:translateY(0)}}
table{{width:100%;border-collapse:collapse;font-size:.8rem}}
thead th{{
  background:var(--g50);border-bottom:2px solid var(--g200);
  padding:10px 14px;text-align:left;font-size:.7rem;font-weight:700;
  color:var(--g600);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;
}}
tbody td{{padding:10px 14px;border-bottom:1px solid var(--g100);color:var(--g700)}}
tbody tr:last-child td{{border-bottom:none}}
tbody tr{{transition:background .15s}}
tbody tr:hover td{{background:var(--g50)}}
.badge-dias{{
  display:inline-block;padding:2px 10px;border-radius:20px;
  font-size:.7rem;font-weight:700;background:#FEE2E2;color:var(--red);
}}
.empty-msg{{text-align:center;padding:28px;color:var(--g600);font-size:.85rem}}

/* ============================================================
   EDITOR MARKDOWN (Parecer)
   ============================================================ */
.parecer-wrap{{
  background:var(--wh);border:1px solid var(--g200);border-radius:var(--radius);
  box-shadow:var(--sh);overflow:hidden;
  opacity:0;transform:translateY(18px);
  transition:opacity .4s ease,transform .4s ease;
}}
.parecer-wrap.revealed{{opacity:1;transform:translateY(0)}}
.md-toolbar{{
  display:flex;align-items:center;flex-wrap:wrap;gap:4px;
  padding:10px 14px;border-bottom:1px solid var(--g200);
  background:var(--g50);
}}
.md-btn{{
  background:var(--wh);border:1px solid var(--g200);color:var(--g700);
  padding:4px 9px;border-radius:6px;font-size:.78rem;font-weight:700;
  cursor:pointer;font-family:'Consolas','Courier New',monospace;
  transition:background .15s,color .15s,border-color .15s;line-height:1.4;
}}
.md-btn:hover{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-sep{{width:1px;height:18px;background:var(--g200);margin:0 4px;flex-shrink:0}}
.md-tabs{{display:flex;gap:0;margin-left:auto}}
.md-tab{{
  padding:4px 14px;border-radius:6px;font-size:.75rem;font-weight:600;
  cursor:pointer;border:1px solid var(--g200);background:var(--wh);color:var(--g600);
  transition:background .15s,color .15s;
}}
.md-tab.active{{background:var(--pr);color:#fff;border-color:var(--pr)}}
.md-editor{{
  display:none;width:100%;min-height:120px;padding:14px 16px;
  font-family:'Consolas','Courier New',monospace;font-size:.82rem;
  color:var(--g800);background:var(--wh);border:none;outline:none;
  resize:vertical;line-height:1.6;
}}
.md-editor.shown{{display:block}}
.md-preview{{
  display:none;min-height:80px;padding:16px 18px;
  font-size:.88rem;color:var(--g700);line-height:1.7;
}}
.md-preview.shown{{display:block}}
/* Estilos para o preview renderizado */
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

/* ── Responsivo ── */
@media(max-width:1024px){{
  .kpi-grid{{grid-template-columns:repeat(2,1fr)}}
  .g3{{grid-template-columns:1fr 1fr}}
}}
@media(max-width:768px){{
  :root{{--sb-w:0px}}
  .sidebar{{transform:translateX(-100%)}}
  .sidebar.open{{transform:translateX(0)}}
  .kpi-grid,.g2,.g3{{grid-template-columns:1fr}}
  .dash-body{{padding:16px}}
  .ajuda-inner{{padding:24px 16px}}
}}
{_SS}
</head>
<body>

<!-- ══════════════════════════════════════════
     SIDEBAR
     ══════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <!-- Logo Bradesco SVG inline -->
    <svg width="36" height="36" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
      <rect width="120" height="120" rx="16" fill="rgba(255,255,255,0.15)"/>
      <path d="M20 60C20 38.0 38.0 20 60 20C82.0 20 100 38.0 100 60C100 82.0 82.0 100 60 100C38.0 100 20 82.0 20 60Z" fill="rgba(255,255,255,0.12)"/>
      <path d="M38 44H58C65.7 44 72 50.3 72 58C72 65.7 65.7 72 58 72H46V82H38V44ZM46 64H57C60.9 64 64 60.9 64 57C64 53.1 60.9 50 57 50H46V64Z" fill="white"/>
      <path d="M74 54H82V82H74V54Z" fill="rgba(255,255,255,0.7)"/>
      <circle cx="78" cy="46" r="5" fill="rgba(255,255,255,0.7)"/>
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

<!-- ══════════════════════════════════════════
     CONTEÚDO PRINCIPAL
     ══════════════════════════════════════════ -->
<div class="main-wrap" id="mainWrap">

  <!-- ── TELA HOME ── -->
  <div class="page page-home active" id="pageHome">
    <div class="home-bg-circle"></div>
    <div class="home-bg-circle2"></div>
    <div class="home-content">
      <div class="home-kicker">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Período: {PERIODO_TXT}
      </div>
      <h1 class="home-h1">Boletim de<br>Qualidade<br>de Dados</h1>
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
        <button class="btn-primary" onclick="abrirDash()">
          Ver Boletim
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-left:6px"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
      </div>
    </div>
    <div class="home-decoration"></div>
  </div>

  <!-- ── TELA AJUDA ── -->
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
        <p>Na tela inicial selecione o <strong>Gerente Sênior</strong> no menu dropdown e clique em <strong>Ver Boletim</strong>. O menu lateral também permite trocar de gerente a qualquer momento durante a navegação.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Painel de Gráficos</h2>
        <ul>
          <li><strong>KPIs superiores:</strong> Produtos monitorados, total de chamados no período e chamados vencidos. O número de vencidos é idêntico ao total exibido na tabela abaixo.</li>
          <li><strong>P-Score Ponderado:</strong> Os 10 produtos com piores scores (média ponderada por dias de execução, excluindo a dimensão VARIACAO). Vermelho &lt; 70, âmbar &lt; 85, verde ≥ 85.</li>
          <li><strong>Status e Tendência:</strong> Distribuição de chamados por situação e evolução mensal dos últimos 5 meses.</li>
          <li><strong>Causas e Dimensões:</strong> Causas raízes identificadas em comentários e distribuição de chamados por dimensão de qualidade.</li>
          <li><strong>Tabela de Vencidos:</strong> Todos os chamados com mais de 5 dias sem encerramento.</li>
        </ul>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Parecer Analítico</h2>
        <p>Utilize o editor ao final do painel para redigir o parecer. Ele suporta <strong>Markdown</strong>:</p>
        <ul>
          <li><code># Título</code> — Título principal</li>
          <li><code>## Subtítulo</code> — Subtítulo de seção</li>
          <li><code>**negrito**</code>, <code>*itálico*</code></li>
          <li><code>- item</code> — Lista com marcadores</li>
          <li><code>&gt; texto</code> — Citação/destaque</li>
          <li><code>---</code> — Linha divisória</li>
        </ul>
        <p style="margin-top:8px">Use os botões da barra de ferramentas para inserir formatação sem precisar digitar a sintaxe. A aba <strong>Visualizar</strong> mostra o resultado renderizado.</p>
      </div>
      <div class="ajuda-card">
        <h2><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/><path d="M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>Período de referência</h2>
        <p>Este boletim cobre os <strong>dois meses completos</strong> anteriores à data em que o notebook foi executado. Dados do mês atual nunca são incluídos pois o mês ainda está em curso. A tendência mensal abrange os <strong>5 meses</strong> anteriores ao mês atual.</p>
      </div>
    </div>
  </div>

  <!-- ── TELA DASHBOARD ── -->
  <div class="page page-dash" id="pageDash">
    <div class="dash-header">
      <div>
        <h1 id="dashTitulo">Boletim — Gerente</h1>
        <p id="dashPeriodo">{PERIODO_TXT}</p>
      </div>
      <div class="dash-header-right">
        <button class="dash-theme-btn" onclick="toggleTheme()">Tema</button>
      </div>
    </div>

    <div class="dash-body" id="dashBody">

      <!-- KPIs -->
      <div class="sec" id="secKpi">
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

      <!-- P-Score -->
      <div class="sec" id="secScore">
        <div class="sec-title">P-Score Ponderado por Produto (piores)</div>
        <div class="chart-card" id="ccScore">
          <div class="chart-label">&#x3A3;(avg_dim &times; dias_dim) / &#x3A3;dias_dim — exclui VARIACAO &nbsp;|&nbsp; Vermelho &lt;70 &nbsp; Âmbar &lt;85 &nbsp; Verde ≥85</div>
          <div class="chart-wrap h280"><canvas id="cScore"></canvas></div>
        </div>
      </div>

      <!-- Status + Tendência -->
      <div class="sec" id="secStatus">
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

      <!-- Causas + Dimensões -->
      <div class="sec" id="secCausas">
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

      <!-- Tabela vencidos -->
      <div class="sec" id="secVenc">
        <div class="sec-title">Chamados Vencidos (&gt; 5 dias sem encerramento)</div>
        <div class="table-card" id="tcVenc">
          <table>
            <thead><tr><th>ID</th><th>Produto</th><th>Dimensão</th><th>Aberto em</th><th>Dias em aberto</th></tr></thead>
            <tbody id="tbVencidos"></tbody>
          </table>
        </div>
      </div>

      <!-- Parecer Analítico -->
      <div class="sec" id="secParecer">
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
            <button class="md-btn" title="Linha divisória" onclick="mdInsert('\\n---\\n','')">&#8212;</button>
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

    </div><!-- /dash-body -->
  </div><!-- /page-dash -->

</div><!-- /main-wrap -->

<script>
// ─────────────────────────────────────────────
// DADOS
// ─────────────────────────────────────────────
var D = {_json_dados};
var _charts = {{}};
var _gerente = '';

// ─────────────────────────────────────────────
// TEMA
// ─────────────────────────────────────────────
function toggleTheme() {{
  document.documentElement.dataset.theme =
    (document.documentElement.dataset.theme === 'dark') ? '' : 'dark';
}}

// ─────────────────────────────────────────────
// NAVEGAÇÃO DE TELAS
// ─────────────────────────────────────────────
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
    setTimeout(function() {{ animateReveal(); }}, 80);
  }}
}}

// ─────────────────────────────────────────────
// SELEÇÃO DE GERENTE
// ─────────────────────────────────────────────
function trocaGerente(g) {{
  if (!g) return;
  _gerente = g;
  // sincroniza todos os selects
  document.getElementById('homeSel').value    = g;
  document.getElementById('sidebarSel').value = g;
  // atualiza dashboard se já estiver visível
  if (document.getElementById('pageDash').classList.contains('active')) {{
    renderDash(g);
  }}
}}

function abrirDash() {{
  var g = document.getElementById('homeSel').value;
  if (!g) {{ alert('Selecione um Gerente Sênior antes de continuar.'); return; }}
  _gerente = g;
  document.getElementById('sidebarSel').value = g;
  renderDash(g);
  goPage('dash');
}}

// ─────────────────────────────────────────────
// RENDER DASHBOARD
// ─────────────────────────────────────────────
function renderDash(g) {{
  var d = D[g];
  if (!d) return;
  document.getElementById('dashTitulo').textContent = 'Boletim — ' + g;
  // KPIs
  document.getElementById('kProd').textContent     = d.kpis.produtos;
  document.getElementById('kTotal').textContent    = d.kpis.total_chamados;
  document.getElementById('kVencidos').textContent = d.kpis.vencidos;
  // Tabela vencidos
  _renderTabela(d.vencidos);
  // Gráficos
  _destroyCharts();
  _renderPScore(d.pscore);
  _renderStatus(d.statusChamados);
  _renderTendencia(d.tendencia);
  _renderCausas(d.causasRaizes);
  _renderDimensoes(d.dimensoes);
  // Reseta animação
  _resetReveal();
}}

// ─────────────────────────────────────────────
// ANIMAÇÃO DE APARIÇÃO (stagger top→bottom)
// ─────────────────────────────────────────────
function _resetReveal() {{
  document.querySelectorAll('.kpi-card,.chart-card,.table-card,.parecer-wrap').forEach(function(el) {{
    el.classList.remove('revealed');
  }});
}}

function animateReveal() {{
  var els = Array.from(document.querySelectorAll(
    '#pageDash .kpi-card, #pageDash .chart-card, #pageDash .table-card, #pageDash .parecer-wrap'
  ));
  els.forEach(function(el, i) {{
    setTimeout(function() {{ el.classList.add('revealed'); }}, i * 60);
  }});
}}

// ─────────────────────────────────────────────
// TABELA VENCIDOS
// ─────────────────────────────────────────────
function _renderTabela(vencidos) {{
  var tbody = document.getElementById('tbVencidos');
  if (!vencidos || !vencidos.length) {{
    tbody.innerHTML = '<tr><td colspan="5" class="empty-msg">Nenhum chamado vencido no período</td></tr>';
    return;
  }}
  tbody.innerHTML = vencidos.map(function(v) {{
    return '<tr><td>' + v.id + '</td><td>' + v.prod + '</td><td>' + v.tipo +
           '</td><td>' + v.data + '</td><td><span class="badge-dias">' +
           v.dias + ' dias</span></td></tr>';
  }}).join('');
}}

// ─────────────────────────────────────────────
// HELPERS DE GRÁFICOS
// ─────────────────────────────────────────────
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
    type: 'bar',
    data: {{
      labels: ps.labels,
      datasets: [{{
        label: 'P-Score',
        data: ps.data,
        backgroundColor: ps.data.map(function(v) {{
          return v < 70 ? 'rgba(220,38,38,.78)' : v < 85 ? 'rgba(217,119,6,.78)' : 'rgba(5,150,105,.78)';
        }}),
        borderRadius: 5, borderWidth: 0,
      }}]
    }},
    options: {{
      indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      scales: {{
        x: {{ min:0, max:100, grid:{{ color:'rgba(0,0,0,.04)' }} }},
        y: {{ ticks:{{ font:{{ size:11 }} }} }}
      }},
      plugins: {{ legend:{{ display:false }} }}
    }}
  }});
}}

function _renderStatus(sc) {{
  _mkChart('cStatus', {{
    type: 'doughnut',
    data: {{
      labels: ['Encerrados/Concluídos','Em Andamento','Em Análise'],
      datasets: [{{
        data: sc,
        backgroundColor: ['#059669','#D97706','#C01160'],
        borderWidth: 3, borderColor: '#fff',
        hoverOffset: 6,
      }}]
    }},
    options: {{
      responsive: true, maintainAspectRatio: false,
      plugins: {{ legend:{{ position:'right', labels:{{ font:{{ size:11 }}, boxWidth:13, padding:14 }} }} }}
    }}
  }});
}}

function _renderTendencia(t) {{
  if (!t || !t.meses.length) return;
  _mkChart('cTrend', {{
    type: 'bar',
    data: {{
      labels: t.meses,
      datasets: [
        {{ label:'Abertos',    data:t.abertos,    backgroundColor:'#C01160', borderRadius:4, borderWidth:0 }},
        {{ label:'Concluídos', data:t.concluidos, backgroundColor:'#059669', borderRadius:4, borderWidth:0 }}
      ]
    }},
    options: {{
      responsive: true, maintainAspectRatio: false,
      scales: {{ y:{{ beginAtZero:true, grid:{{ color:'rgba(0,0,0,.04)' }} }} }},
      plugins: {{ legend:{{ position:'top', labels:{{ font:{{ size:11 }}, boxWidth:12, padding:12 }} }} }}
    }}
  }});
}}

function _renderCausas(c) {{
  if (!c || !c.labels.length) return;
  _mkChart('cCausas', {{
    type: 'bar',
    data: {{
      labels: c.labels,
      datasets: [{{
        label:'Chamados', data:c.data,
        backgroundColor:'#8C0F3B', borderRadius:4, borderWidth:0,
      }}]
    }},
    options: {{
      indexAxis:'y', responsive:true, maintainAspectRatio:false,
      plugins:{{ legend:{{ display:false }} }},
      scales:{{ x:{{ beginAtZero:true, grid:{{ color:'rgba(0,0,0,.04)' }} }} }}
    }}
  }});
}}

function _renderDimensoes(dim) {{
  if (!dim || !dim.labels.length) return;
  _mkChart('cDimensoes', {{
    type: 'bar',
    data: {{
      labels: dim.labels,
      datasets: [{{
        label:'Chamados',
        data: dim.data,
        backgroundColor: dim.labels.map(function(_,i){{ return _palette[i % _palette.length]; }}),
        borderRadius:4, borderWidth:0,
      }}]
    }},
    options: {{
      responsive:true, maintainAspectRatio:false,
      plugins:{{ legend:{{ display:false }} }},
      scales:{{ y:{{ beginAtZero:true, grid:{{ color:'rgba(0,0,0,.04)' }} }} }}
    }}
  }});
}}

// ─────────────────────────────────────────────
// EDITOR MARKDOWN
// ─────────────────────────────────────────────
function mdTab(tab) {{
  var editor  = document.getElementById('mdEditor');
  var preview = document.getElementById('mdPreview');
  var tEdit   = document.getElementById('tabEditar');
  var tVer    = document.getElementById('tabVer');
  if (tab === 'ver') {{
    preview.innerHTML = mdRender(editor.value);
    editor.classList.remove('shown');  preview.classList.add('shown');
    tEdit.classList.remove('active'); tVer.classList.add('active');
  }} else {{
    preview.classList.remove('shown'); editor.classList.add('shown');
    tVer.classList.remove('active');  tEdit.classList.add('active');
  }}
}}

function mdInsert(before, after) {{
  var ta  = document.getElementById('mdEditor');
  var s   = ta.selectionStart;
  var e   = ta.selectionEnd;
  var sel = ta.value.substring(s, e);
  var ins = before.replace(/\\\\n/g, '\\n') + sel + after;
  ta.value = ta.value.substring(0, s) + ins + ta.value.substring(e);
  ta.focus();
  ta.selectionStart = s + before.replace(/\\\\n/g,'\\n').length;
  ta.selectionEnd   = ta.selectionStart + sel.length;
}}

function mdRender(md) {{
  if (!md) return '<span style="color:var(--g300);font-style:italic">Nenhum conteúdo para visualizar.</span>';
  var html = md
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/^### (.+)$/gm,'<h3>$1</h3>')
    .replace(/^## (.+)$/gm,'<h2>$1</h2>')
    .replace(/^# (.+)$/gm,'<h1>$1</h1>')
    .replace(/^---$/gm,'<hr>')
    .replace(/^&gt; (.+)$/gm,'<blockquote>$1</blockquote>')
    .replace(/^- (.+)$/gm,'<li>$1</li>')
    .replace(/^\\d+\\. (.+)$/gm,'<li>$1</li>')
    .replace(/(<li>.*<\\/li>\\n?)+/g, function(m){{ return '<ul>' + m + '</ul>'; }})
    .replace(/\\*\\*(.+?)\\*\\*/g,'<strong>$1</strong>')
    .replace(/\\*(.+?)\\*/g,'<em>$1</em>')
    .replace(/`(.+?)`/g,'<code>$1</code>')
    .replace(/^(?!<[hH1-6ulbci]|<hr|<blockquote)(.+)$/gm,'<p>$1</p>')
    .replace(/\\n{2,}/g,'');
  return html;
}}

// ─────────────────────────────────────────────
// INICIALIZAÇÃO
// ─────────────────────────────────────────────
(function init() {{
  var gerentes = Object.keys(D).sort();
  var selects  = ['homeSel','sidebarSel'];
  selects.forEach(function(id) {{
    var sel = document.getElementById(id);
    if (!sel) return;
    // limpa opções exceto placeholder
    while (sel.options.length > 1) sel.remove(1);
    gerentes.forEach(function(g) {{
      var opt  = document.createElement('option');
      opt.value = g; opt.text = g;
      sel.add(opt);
    }});
  }});
}})();
{_SC}
</body>
</html>"""

# COMMAND ----------
# =============================================================================
# CÉLULA 13 — OUTPUT COPIÁVEL NO DATABRICKS
# Exibe uma textarea com o HTML completo para copiar e salvar externamente.
# =============================================================================

_html_esc  = _HTML.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
_SC2 = "<" + "/script>"
_SS2 = "<" + "/style>"
_ST2 = "<" + "/textarea>"

_output_ui = f"""
<style>
#_blt_wrap{{font-family:'Segoe UI',Arial,sans-serif;max-width:980px;margin:0 auto;padding:24px}}
#_blt_wrap h2{{font-size:1rem;font-weight:700;color:#8C0F3B;margin-bottom:6px}}
#_blt_wrap p{{font-size:.82rem;color:#374151;margin-bottom:12px;line-height:1.5}}
#_blt_code{{width:100%;height:340px;font-family:'Consolas','Courier New',monospace;font-size:.74rem;
  border:1px solid #D1D5DB;border-radius:8px;padding:12px;background:#F9FAFB;color:#1F2937;
  resize:vertical;white-space:pre;overflow:auto}}
#_blt_copy{{margin-top:10px;background:#C01160;color:#fff;border:none;
  padding:9px 22px;border-radius:8px;font-size:.85rem;font-weight:700;
  cursor:pointer;font-family:inherit}}
#_blt_copy:hover{{background:#8C0F3B}}
#_blt_ok{{display:inline-block;margin-left:12px;font-size:.8rem;color:#059669;
  font-weight:600;opacity:0;transition:opacity .4s}}
{_SS2}
<div id="_blt_wrap">
  <h2>HTML do Boletim gerado com sucesso</h2>
  <p>Selecione tudo na caixa (Ctrl+A), copie (Ctrl+C) e cole em um arquivo <code>.html</code>
  fora do Databricks. O arquivo é autossuficiente e não requer servidor.</p>
  <textarea id="_blt_code" readonly spellcheck="false">{_html_esc}{_ST2}
  <br>
  <button id="_blt_copy" onclick="_copyBlt()">Copiar HTML</button>
  <span id="_blt_ok">Copiado com sucesso!</span>
</div>
<script>
function _copyBlt(){{
  var ta=document.getElementById('_blt_code');
  ta.select();
  document.execCommand('copy');
  var ok=document.getElementById('_blt_ok');
  ok.style.opacity='1';
  setTimeout(function(){{ok.style.opacity='0';}},2600);
}}
{_SC2}
"""

displayHTML(_output_ui)

# COMMAND ----------
# =============================================================================
# CÉLULA 14 — VALIDAÇÃO DE VALORES (tabela para conferência externa)
# Não faz parte do HTML; serve apenas para auditar os números embutidos.
# =============================================================================

from pyspark.sql import Row as _Row

_val_rows = []
for g in lista_gerentes:
    d  = dados_gerentes.get(g, {})
    kp = d.get("kpis", {})
    sc = d.get("statusChamados", [0,0,0])
    _val_rows.append(_Row(
        GERENTE_SENIOR       = g,
        PERIODO              = f"{DATA_INI} a {DATA_FIM}",
        QTD_PRODUTOS_DADOS   = int(kp.get("produtos", 0)),
        QTD_CHAMADOS_PERIODO = int(kp.get("total_chamados", 0)),
        QTD_VENCIDOS_CARD    = int(kp.get("vencidos", 0)),
        QTD_VENCIDOS_TABELA  = len(d.get("vencidos", [])),
        STATUS_ENCERRADOS    = int(sc[0] if len(sc)>0 else 0),
        STATUS_PENDENTES     = int(sc[1] if len(sc)>1 else 0),
        STATUS_EM_ANALISE    = int(sc[2] if len(sc)>2 else 0),
        PSCORE_TOP1_PRODUTO  = (d.get("pscore",{}).get("labels",[None]) or [None])[0],
        PSCORE_TOP1_VALOR    = float((d.get("pscore",{}).get("data",[None]) or [None])[0] or 0),
        CAUSAS_TOP1          = (d.get("causasRaizes",{}).get("labels",[None]) or [None])[0],
        CAUSAS_TOP1_QTD      = int((d.get("causasRaizes",{}).get("data",[0]) or [0])[0] or 0),
        MESES_TENDENCIA      = str(d.get("tendencia",{}).get("meses",[])),
        ABERTOS_TENDENCIA    = str(d.get("tendencia",{}).get("abertos",[])),
        CONCLUIDOS_TENDENCIA = str(d.get("tendencia",{}).get("concluidos",[])),
    ))

# QTD_VENCIDOS_CARD e QTD_VENCIDOS_TABELA devem ser sempre iguais.
# A coluna KPI usa len(vencidos_dict[gerente]) — a mesma lista da tabela.
df_validacao = spark.createDataFrame(_val_rows)
display(df_validacao.orderBy("GERENTE_SENIOR"))
