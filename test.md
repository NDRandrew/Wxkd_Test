# Databricks notebook source
# MAGIC %md
# MAGIC # Boletim de Qualidade de Dados — Gerentes Seniores
# MAGIC Gera um HTML singleton copiável referente aos **dois meses completos** anteriores à data de execução.

# COMMAND ----------
# =============================================================================
# CÉLULA 1 — PERÍODO E IMPORTS
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
    add_months, current_date, datediff
)

# ---------------------------------------------------------------------------
# Janela de tempo: dois meses completos anteriores ao mês atual
# Exemplo: execução em 20/05/2026 → abril (MES_1) e março (MES_2)
# ---------------------------------------------------------------------------
_hoje = date.today()
_inicio_mes_atual = _hoje.replace(day=1)

_fim_mes_2   = _inicio_mes_atual - timedelta(days=1)          # último dia do mês anterior
_ini_mes_2   = _fim_mes_2.replace(day=1)                       # 1º do mês anterior  (MES mais recente)

_fim_mes_1   = _ini_mes_2 - timedelta(days=1)                 # último dia do mês 2 meses atrás
_ini_mes_1   = _fim_mes_1.replace(day=1)                       # 1º do mês 2 meses atrás (MES mais antigo)

# Strings para SQL
DATA_INI   = str(_ini_mes_1)   # ex: 2026-03-01
DATA_FIM   = str(_fim_mes_2)   # ex: 2026-04-30

# Rótulos legíveis para o HTML
MESES_ROTULO = [
    _ini_mes_1.strftime("%b/%y").capitalize(),   # ex: Mar/26
    _ini_mes_2.strftime("%b/%y").capitalize(),   # ex: Abr/26
]

# Para tendência: últimos 5 meses anteriores ao mês atual
DATA_INI_TENDENCIA = str((_inicio_mes_atual - relativedelta(months=5)).replace(day=1))
DATA_FIM_TENDENCIA = str(_fim_mes_2)

SCHEMA = "pr_platfun.aaqd_estrt_dados_qld_ucs"

print(f"Período do boletim: {DATA_INI} até {DATA_FIM}")
print(f"Período de tendência: {DATA_INI_TENDENCIA} até {DATA_FIM_TENDENCIA}")
print(f"Rótulos dos meses: {MESES_ROTULO}")

# COMMAND ----------
# =============================================================================
# CÉLULA 2 — AUTENTICAÇÃO MS GRAPH E LEITURA SHAREPOINT
# =============================================================================

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(name)s | %(levelname)s | %(message)s",
)
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
        cluster_tags = json.loads(
            spark.conf.get("spark.databricks.clusterUsageTags.clusterAllTags")
        )
        return next(t for t in cluster_tags if t["key"] == "ambiente")["value"]

    def _get_secret_api_token(self) -> str:
        ambiente = self._get_environment()
        _log.info("Ambiente detectado: %s", ambiente.upper())
        env_config = {
            "dv": ("kvazudvbraaaqd001", "scrt-qld-dv-tkn-shp", "21083960-effc-48f5-975f-d7994fbc4ad8"),
            "ho": ("kvazuhobraaaqd001", "scrt-qld-hm-tkn-shp", "d85b93fc-d0f9-4b13-8918-3e32f9d4454a"),
            "pr": ("kvazuprbraaaqd001", "scrt-qld-pr-tkn-shp", "902b6357-3083-4bb1-b0b1-3ef64c68af57"),
        }
        if ambiente not in env_config:
            raise RuntimeError(f"Ambiente '{ambiente}' não suportado para MS Graph.")
        scope, key, client_id = env_config[ambiente]
        self.client_id = client_id
        return dbutils.secrets.get(scope=scope, key=key)

    def _create_app_msal(self):
        client_secret = self._get_secret_api_token()
        authority     = f"https://login.microsoftonline.com/{self.tenant_id}"
        self.app      = msal.ConfidentialClientApplication(
            self.client_id, authority=authority, client_credential=client_secret
        )

    def _is_token_valid(self) -> bool:
        if not self.access_token:
            return False
        return int(self.token_cache[0]["extended_expires_on"]) > time.time()

    def get_token(self) -> str:
        if self.app is None:
            self._create_app_msal()
        if self._is_token_valid():
            return self.access_token
        result = self.app.acquire_token_for_client(
            scopes=["https://graph.microsoft.com/.default"]
        )
        if "access_token" in result:
            self.access_token = result["access_token"]
            self.token_cache  = self.app.token_cache.find(
                msal.TokenCache.CredentialType.ACCESS_TOKEN
            )
        return self.access_token


def _read_sharepoint_list(list_name: str, prefix: str = ""):
    auth    = AccessMSGraph()
    token   = auth.get_token()
    headers = {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}

    site_url  = f"{auth.host}/sites/{SHAREPOINT_HOST}:{SITE_PATH}"
    site_resp = http_requests.get(site_url, headers=headers, timeout=60)
    site_resp.raise_for_status()
    site_id   = site_resp.json()["id"]

    all_rows  = []
    items_url = (
        f"{auth.host}/sites/{site_id}/lists/{list_name}/items"
        "?$expand=fields&$top=200"
    )
    while items_url:
        resp = http_requests.get(items_url, headers=headers, timeout=60)
        resp.raise_for_status()
        data     = resp.json()
        all_rows.extend(data.get("value", []))
        items_url = data.get("@odata.nextLink")

    _log.info("Total items de '%s': %d", list_name, len(all_rows))

    records = [item.get("fields", {}) for item in all_rows]
    if not records:
        _log.warning("Nenhum item em '%s'", list_name)
        return spark.createDataFrame([], schema="dummy STRING")

    all_keys   = sorted({k for r in records for k in r.keys()})
    normalized = [{k: str(r.get(k, "")) for k in all_keys} for r in records]
    df         = spark.createDataFrame(normalized)

    if prefix:
        for c in df.columns:
            df = df.withColumnRenamed(c, f"{prefix}{c}")
    return df


# Carrega listas SharePoint
df_areas     = _read_sharepoint_list(LIST_AREAS)
df_gerentes  = _read_sharepoint_list(LIST_GERENTES, prefix="gs_")
df_analistas = _read_sharepoint_list(LIST_ANALISTAS, prefix="an_")

# Join: Areas ← Gerentes ← Analistas
df_ger_an = df_gerentes.join(
    df_analistas,
    df_gerentes["gs_analistaLookupId"] == df_analistas["an_id"],
    how="left"
)
df_sharepoint_score = df_areas.join(
    df_ger_an,
    df_areas["gerente_seniorLookupId"] == df_ger_an["gs_id"],
    how="left"
)
df_sharepoint_score.createOrReplaceTempView("vw_sharepoint_score")

_log.info("vw_sharepoint_score registrada — %d linhas.", df_sharepoint_score.count())

# COMMAND ----------
# =============================================================================
# CÉLULA 3 — CARGA E NORMALIZAÇÃO DOS CHAMADOS (CLOUD / HIVE / SAS)
# =============================================================================

def _fix_after_load(df):
    string_cols = [
        "AMBIENTE", "ÁREA", "plataforma_afericao", "DIMENSÃO", "COMENTÁRIOS",
        "REGRA", "ExecucaoQualidade", "Categoria", "produto_de_dados",
        "CAMADA", "STATUS", "prioridade"
    ]
    df = df.select(
        *[
            when(upper(col(c)).isin("N/D", "ND"), "")
            .otherwise(upper(col(c))).alias(c)
            if c in string_cols and c in df.columns else col(c)
            for c in df.columns
        ]
    )
    if "DATA DO ENCERRAMENTO" in df.columns:
        df = df.withColumn(
            "DATA DO ENCERRAMENTO",
            date_format(col("DATA DO ENCERRAMENTO"), "dd/MM/yyyy").cast("string")
        )
        df = df.withColumn(
            "DATA DO ENCERRAMENTO",
            when(col("DATA DO ENCERRAMENTO") == "01/01/1900", "")
            .otherwise(col("DATA DO ENCERRAMENTO"))
        )
    if "PRAZO DE CORREÇÃO" in df.columns:
        df = df.withColumn(
            "PRAZO DE CORREÇÃO",
            date_format(col("PRAZO DE CORREÇÃO"), "dd/MM/yyyy").cast("string")
        )
    if "ExecucaoQualidade" in df.columns:
        df = df.withColumn(
            "ExecucaoQualidade",
            date_format(col("ExecucaoQualidade"), "dd/MM/yyyy").cast("string")
        )
    if "DETALHAMENTO DO PROBLEMA" in df.columns:
        df = df.withColumn(
            "DETALHAMENTO DO PROBLEMA",
            when(upper(col("DETALHAMENTO DO PROBLEMA")).isin("N/D", "ND"), "")
            .otherwise(col("DETALHAMENTO DO PROBLEMA"))
        )
    if "Categoria" in df.columns:
        df = (
            df.withColumn("Categoria", initcap(col("Categoria")))
              .withColumn("Categoria",
                when(col("Categoria") == "Produto De Dados", "Produto de dados")
                .when(col("Categoria") == "Dados Brutos",    "Ingestão")
                .when(col("Categoria") == "Ativo De Dados",  "Ativo")
                .when(col("Categoria") == "Ingestao",        "Ingestão")
                .otherwise(col("Categoria"))
              )
        )
    if "plataforma_afericao" in df.columns:
        df = df.withColumn("plataforma_afericao", initcap(col("plataforma_afericao")))
    if "STATUS" in df.columns:
        df = df.withColumn("STATUS",
            when(col("STATUS") == "CONCLUIDO",  "CONCLUÍDO")
            .when(col("STATUS") == "EM ANALISE","EM ANÁLISE")
            .otherwise(col("STATUS"))
        )
    if "DIMENSÃO" in df.columns:
        df = df.withColumn("DIMENSÃO",
            when(col("DIMENSÃO") == "CONSISTENCIA", "CONSISTÊNCIA")
            .otherwise(col("DIMENSÃO"))
        )
    return df


# --- CLOUD ---
def _load_chamados_cloud():
    raw = spark.sql(f"""
        SELECT
            a.*,
            a.cidtfd_app                        AS ID_DE,
            b.icatlg_anald                      AS catalogo_id,
            c.idbase_anald                      AS database_id,
            d.itbela_anald                      AS tabela_id,
            a.icluna_anald                      AS campo,
            a.idmsao_quald                      AS dimensao,
            e.iarea_quald,
            f.iprodt_dados,
            a.rerro_quald                       AS descricao_erro_existente,
            a.hcriac_item_app                   AS created,
            a.rdetlh_probl                      AS detalhamento_do_problema,
            a.hult_mudca_sttus                  AS status_chn_hr
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_catlg_dados b
            ON a.cidtfd_catlg_anald = b.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_dados c
            ON a.cidtfd_dbase_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d
            ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados e
            ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f
            ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'cloud'
    """)
    renamed = raw.withColumnsRenamed({
        "cidtfd_app":          "ID_ITEM",
        "iambte_quald":        "AMBIENTE",
        "iarea_quald":         "ÁREA",
        "iplatf_aferc":        "plataforma_afericao",
        "idmsao_quald":        "DIMENSÃO",
        "eemail_resp_tecni":   "responsaveis_tecnicos",
        "eemail_resp_negoc":   "responsaveis_negocio",
        "rcomen_anlse":        "COMENTÁRIOS",
        "idescr_regra_anald":  "REGRA",
        "dexcuc_reg_invld":    "ExecucaoQualidade",
        "icateg_anald":        "Categoria",
        "iprodt_dados":        "produto_de_dados",
        "catalogo_id":         "Catalogo",
        "database_id":         "Banco",
        "tabela_id":           "tabela",
        "campo":               "campo",
        "icmada_anald":        "CAMADA",
        "rsttus_chmad":        "STATUS",
        "rerro_quald":         "DESCRIÇÃO DO ERRO",
        "rdetlh_probl":        "DETALHAMENTO DO PROBLEMA",
        "dencrr_reg_invld":    "DATA DO ENCERRAMENTO",
        "iprior_resol":        "prioridade",
        "dprz_corrc_reg_invld":"PRAZO DE CORREÇÃO",
        "hcriac_item_app":     "CRIADO_EM",
        "hult_mudca_sttus":    "ULTIMA_ALTERA_STATUS",
    })
    return _fix_after_load(renamed.select(
        "ID_ITEM", "AMBIENTE", "ÁREA", "plataforma_afericao", "DIMENSÃO",
        "responsaveis_tecnicos", "responsaveis_negocio", "COMENTÁRIOS", "REGRA",
        "ExecucaoQualidade", "Categoria", "produto_de_dados", "Catalogo", "Banco",
        "tabela", "campo", "CAMADA", "STATUS", "DESCRIÇÃO DO ERRO",
        "DETALHAMENTO DO PROBLEMA", "DATA DO ENCERRAMENTO", "prioridade",
        "PRAZO DE CORREÇÃO", "CRIADO_EM", "ULTIMA_ALTERA_STATUS"
    ))


# --- HIVE/TERADATA ---
def _load_chamados_hive():
    raw = spark.sql(f"""
        SELECT
            a.*,
            a.cidtfd_app        AS ID_DE,
            c.idbase_anald      AS database_id,
            d.itbela_anald      AS tabela_id,
            a.icluna_anald      AS campo,
            a.idmsao_quald      AS dimensao,
            e.iarea_quald,
            f.iprodt_dados,
            a.rerro_quald       AS descricao_erro_existente,
            a.hcriac_item_app   AS created,
            a.rdetlh_probl      AS detalhamento_do_problema
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_dados c
            ON a.cidtfd_dbase_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d
            ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados e
            ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f
            ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'hive' OR a.iambte_quald == 'teradata'
    """)
    renamed = (
        raw.withColumnsRenamed({
            "cidtfd_app":        "ID_ITEM",
            "iambte_quald":      "AMBIENTE",
            "iarea_quald":       "ÁREA",
            "idmsao_quald":      "DIMENSÃO",
            "eemail_resp_tecni": "responsaveis_tecnicos",
            "eemail_resp_negoc": "responsaveis_negocio",
            "iplatf_aferc":      "plataforma_afericao",
            "rcomen_anlse":      "COMENTÁRIOS",
            "idescr_regra_anald":"REGRA",
            "dexcuc_reg_invld":  "ExecucaoQualidade",
            "database_id":       "DatabaseOnprem",
            "tabela_id":         "TabelaOnprem",
            "campo":             "campo",
            "rsafra_dbase":      "SAFRA",
            "rsttus_chmad":      "STATUS",
            "rerro_quald":       "DESCRIÇÃO DO ERRO",
            "rdetlh_probl":      "DETALHAMENTO DO PROBLEMA",
            "dencrr_reg_invld":  "DATA DO ENCERRAMENTO",
        })
        .withColumn("SCRIPT DE CARGA", lit(""))
    )
    return _fix_after_load(renamed.select(
        "ID_ITEM", "AMBIENTE", "ÁREA", "plataforma_afericao", "DIMENSÃO",
        "responsaveis_tecnicos", "responsaveis_negocio", "COMENTÁRIOS", "REGRA",
        "ExecucaoQualidade", "SAFRA", "DatabaseOnprem", "TabelaOnprem", "campo",
        "STATUS", "DESCRIÇÃO DO ERRO", "DETALHAMENTO DO PROBLEMA", "DATA DO ENCERRAMENTO"
    ))


# --- SAS ---
def _load_chamados_sas():
    raw = spark.sql(f"""
        SELECT
            a.*,
            a.cidtfd_app                  AS ID_DE,
            c.itbela_orige_sas_anald      AS libname_id,
            d.itbela_anald                AS tabela_id,
            a.icluna_anald                AS campo,
            a.idmsao_quald                AS dimensao,
            e.iarea_quald,
            f.iprodt_dados,
            a.rerro_quald                 AS descricao_erro_existente,
            a.hcriac_item_app             AS created,
            a.rdetlh_probl                AS detalhamento_do_problema
        FROM {SCHEMA}.tapont_quald_app_dados a
        LEFT JOIN {SCHEMA}.tapont_quald_app_dbase_tbela_sas_dados c
            ON a.cidtfd_sas_anald = c.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_tbela_dados d
            ON a.cidtfd_tbela_anald = d.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_area_dados e
            ON a.cidtfd_area = e.cidtfd_app
        LEFT JOIN {SCHEMA}.tapont_quald_app_nome_prodt_dados f
            ON a.cidtfd_prodt_dados = f.cidtfd_app
        WHERE a.iambte_quald == 'sas'
    """)
    renamed = (
        raw.withColumnsRenamed({
            "cidtfd_app":        "ID_ITEM",
            "iambte_quald":      "AMBIENTE",
            "iarea_quald":       "ÁREA",
            "idmsao_quald":      "DIMENSÃO",
            "eemail_resp_tecni": "responsaveis_tecnicos",
            "eemail_resp_negoc": "responsaveis_negocio",
            "rcomen_anlse":      "COMENTÁRIOS",
            "idescr_regra_anald":"REGRA",
            "dexcuc_reg_invld":  "ExecucaoQualidade",
            "libname_id":        "LibnameOnprem",
            "tabela_id":         "TabelaOnprem",
            "campo":             "campo",
            "rsafra_dbase":      "SAFRA",
            "rsttus_chmad":      "STATUS",
            "rerro_quald":       "DESCRIÇÃO DO ERRO",
            "rdetlh_probl":      "DETALHAMENTO DO PROBLEMA",
            "dencrr_reg_invld":  "DATA DO ENCERRAMENTO",
        })
        .withColumn("SCRIPT DE CARGA", lit(""))
    )
    return _fix_after_load(renamed.select(
        "ID_ITEM", "AMBIENTE", "ÁREA", "DIMENSÃO",
        "responsaveis_tecnicos", "responsaveis_negocio", "COMENTÁRIOS", "REGRA",
        "ExecucaoQualidade", "SAFRA", "LibnameOnprem", "TabelaOnprem", "campo",
        "SCRIPT DE CARGA", "STATUS", "DATA DO ENCERRAMENTO",
        "DETALHAMENTO DO PROBLEMA", "DESCRIÇÃO DO ERRO"
    ))


df_chamados_cloud = _load_chamados_cloud()
df_chamados_hive  = _load_chamados_hive()
df_chamados_sas   = _load_chamados_sas()

df_chamados_cloud.createOrReplaceTempView("vw_chamados_cloud")

kpi_onprem_total = df_chamados_hive.count() + df_chamados_sas.count()
_log.info("On-Premises total (hive+sas): %d", kpi_onprem_total)

# COMMAND ----------
# =============================================================================
# CÉLULA 4 — P-SCORE PONDERADO POR PRODUTO/GERENTE (período do boletim)
# Fórmula: SUM(AVG_dim * dias_dim) / SUM(dias_dim)  — exclui VARIACAO
# =============================================================================

_SQL_JOINS_SCORE = f"""
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

query_score = f"""
    WITH sq_base AS (
        SELECT
            a.dini_excuc_quald,
            h.itpo_prodt_funcl,
            f.idmsao_quald_dados,
            a.pscore_calcd,
            a.nsttus_mntrc
        {_SQL_JOINS_SCORE}
        WHERE
            h.itpo_prodt = 'PRODUTO DE DADOS'
            AND a.dini_excuc_quald BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
            AND a.nsttus_mntrc = 1
            AND f.idmsao_quald_dados != 'VARIACAO'
    ),
    dias_count AS (
        SELECT itpo_prodt_funcl, idmsao_quald_dados,
               COUNT(DISTINCT dini_excuc_quald) AS dias_execucao
        FROM sq_base
        GROUP BY itpo_prodt_funcl, idmsao_quald_dados
    ),
    joined AS (
        SELECT
            sp.gs_Title         AS GERENTE_SENIOR,
            sp.produto          AS NOME_PRODUTO,
            sq.idmsao_quald_dados AS DIMENSAO,
            sq.pscore_calcd     AS PSCORE,
            dc.dias_execucao    AS DIAS_EXECUCAO
        FROM vw_sharepoint_score sp
        INNER JOIN sq_base sq   ON sp.produto = sq.itpo_prodt_funcl
        INNER JOIN dias_count dc
            ON sq.itpo_prodt_funcl    = dc.itpo_prodt_funcl
            AND sq.idmsao_quald_dados = dc.idmsao_quald_dados
    ),
    dim_agg AS (
        SELECT
            GERENTE_SENIOR, NOME_PRODUTO, DIMENSAO,
            AVG(PSCORE)           AS AVG_SCORE_DIM,
            SUM(DIAS_EXECUCAO)    AS DIAS_EXECUCAO_DIM
        FROM joined
        GROUP BY GERENTE_SENIOR, NOME_PRODUTO, DIMENSAO
    )
    SELECT
        GERENTE_SENIOR,
        NOME_PRODUTO,
        ROUND(
            SUM(AVG_SCORE_DIM * DIAS_EXECUCAO_DIM)
            / NULLIF(SUM(DIAS_EXECUCAO_DIM), 0),
            2
        ) AS PSCORE_PONDERADO
    FROM dim_agg
    GROUP BY GERENTE_SENIOR, NOME_PRODUTO
    HAVING SUM(DIAS_EXECUCAO_DIM) > 0
"""

_score_rows = spark.sql(query_score).collect()

score_dict = {}
for row in _score_rows:
    g = row["GERENTE_SENIOR"]
    if g not in score_dict:
        score_dict[g] = []
    if row["PSCORE_PONDERADO"] is not None:
        score_dict[g].append({
            "produto": row["NOME_PRODUTO"],
            "pscore":  round(float(row["PSCORE_PONDERADO"]), 2),
        })

# COMMAND ----------
# =============================================================================
# CÉLULA 5 — KPIs DA CARTEIRA POR GERENTE
# =============================================================================

kpis_raw = (
    df_sharepoint_score
    .groupBy("gs_Title", "categoria")
    .agg(countDistinct("produto").alias("qtd"))
    .collect()
)
kpis_dict = {}
for row in kpis_raw:
    g   = row["gs_Title"]
    cat = (row["categoria"] or "OUTROS").upper()
    kpis_dict.setdefault(g, {})[cat] = row["qtd"]

# COMMAND ----------
# =============================================================================
# CÉLULA 6 — RESUMO DE CHAMADOS CLOUD POR GERENTE (período do boletim)
# =============================================================================

query_resumo = f"""
    SELECT
        sp.gs_Title AS GERENTE_SR,
        COUNT(CASE WHEN ch.STATUS = 'CONCLUÍDO'               THEN 1 END) AS QTD_CONCLUIDO,
        COUNT(CASE WHEN ch.STATUS = 'CANCELADO'               THEN 1 END) AS QTD_CANCELADO,
        COUNT(CASE WHEN ch.STATUS = 'EM ANÁLISE'              THEN 1 END) AS QTD_EM_ANALISE,
        COUNT(CASE WHEN ch.STATUS = 'EM ANDAMENTO'            THEN 1 END) AS QTD_EM_ANDAMENTO,
        COUNT(CASE WHEN ch.STATUS = 'ATUACAO QUALIDADE'       THEN 1 END) AS QTD_ATUACAO_QUALIDADE,
        COUNT(CASE WHEN ch.STATUS = 'ENCERRADO SEM RESPOSTA'  THEN 1 END) AS QTD_ENCERRADO_SEM_RESPOSTA,
        COUNT(CASE
            WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
                 AND datediff(current_date(), ch.CRIADO_EM) > 5
            THEN 1 END) AS QTD_ABERTOS_MAIS_5_DIAS,
        COUNT(ch.ID_ITEM) AS QTD_TOTAL
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
      AND ch.ID_ITEM IS NOT NULL
    GROUP BY sp.gs_Title
"""

resumo_rows = spark.sql(query_resumo).collect()

resumo_dict = {}
for row in resumo_rows:
    g = row["GERENTE_SR"]
    resumo_dict.setdefault(g, {
        "concluido": 0, "cancelado": 0, "em_analise": 0,
        "em_andamento": 0, "atuacao_qualidade": 0,
        "encerrado_sem_resposta": 0, "abertos_5_dias": 0, "total": 0
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
# CÉLULA 7 — TENDÊNCIA MENSAL (últimos 5 meses completos antes do mês atual)
# =============================================================================

query_tendencia = f"""
    SELECT
        sp.gs_Title AS GERENTE,
        date_format(ch.CRIADO_EM, 'yyyy-MM') AS MES,
        COUNT(CASE WHEN ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA') THEN 1 END) AS ABERTOS,
        COUNT(CASE WHEN ch.STATUS = 'CONCLUÍDO' THEN 1 END) AS CONCLUIDOS
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.CRIADO_EM BETWEEN date('{DATA_INI_TENDENCIA}') AND date('{DATA_FIM_TENDENCIA}')
      AND ch.ID_ITEM IS NOT NULL
    GROUP BY sp.gs_Title, date_format(ch.CRIADO_EM, 'yyyy-MM')
    ORDER BY 1, 2
"""

tendencia_rows = spark.sql(query_tendencia).collect()

tendencia_dict = {}
for row in tendencia_rows:
    g = row["GERENTE"]
    tendencia_dict.setdefault(g, {"meses": [], "abertos": [], "concluidos": []})
    tendencia_dict[g]["meses"].append(row["MES"])
    tendencia_dict[g]["abertos"].append(int(row["ABERTOS"] or 0))
    tendencia_dict[g]["concluidos"].append(int(row["CONCLUIDOS"] or 0))

# COMMAND ----------
# =============================================================================
# CÉLULA 8 — CAUSAS RAÍZES OPERACIONAIS POR GERENTE (período do boletim)
# Chamados ativos no período (excluindo encerrados)
# =============================================================================

query_causas = f"""
    SELECT
        sp.gs_Title AS GERENTE,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%CARGA EM ATRASO%' THEN 1 END) AS CARGA_EM_ATRASO,
        COUNT(CASE WHEN upper(ch.REGRA)          LIKE '%REGRA\\_LITERAL%IS NULL%' THEN 1 END) AS REGRA_IS_NULL,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%SEM CARGA%'       THEN 1 END) AS SEM_CARGA,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%INCONSIST%'       THEN 1 END) AS INCONSISTENCIA,
        COUNT(CASE WHEN upper(ch.`COMENTÁRIOS`) LIKE '%DUPLICI%'         THEN 1 END) AS DUPLICIDADE
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title
"""

causas_rows = spark.sql(query_causas).collect()

causas_dict = {}
for row in causas_rows:
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
# CÉLULA 9 — CHAMADOS VENCIDOS (> 5 DIAS, STATUS ATIVO)
# =============================================================================

query_vencidos = f"""
    SELECT
        sp.gs_Title AS GERENTE_SENIOR,
        ch.ID_ITEM  AS ID_CHAMADO,
        sp.produto  AS NOME_PRODUTO,
        ch.`DIMENSÃO`       AS DIMENSAO,
        date_format(
            from_utc_timestamp(ch.CRIADO_EM, 'America/Sao_Paulo'),
            'dd/MM/yyyy'
        ) AS DATA_CRIACAO,
        datediff(current_date(), ch.CRIADO_EM) AS DIAS_ABERTO
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.STATUS NOT IN ('CONCLUÍDO','CANCELADO','ENCERRADO SEM RESPOSTA')
      AND datediff(current_date(), ch.CRIADO_EM) > 5
    ORDER BY sp.gs_Title, datediff(current_date(), ch.CRIADO_EM) DESC
"""

vencidos_rows = spark.sql(query_vencidos).collect()

vencidos_dict = {}
for row in vencidos_rows:
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
# Gráfico extra: radar/bar de chamados por dimensão por gerente
# =============================================================================

query_dimensao = f"""
    SELECT
        sp.gs_Title          AS GERENTE,
        ch.`DIMENSÃO`        AS DIMENSAO,
        COUNT(ch.ID_ITEM)    AS QTD
    FROM vw_sharepoint_score sp
    LEFT JOIN vw_chamados_cloud ch ON sp.produto = ch.produto_de_dados
    WHERE ch.ID_ITEM IS NOT NULL
      AND ch.CRIADO_EM BETWEEN date('{DATA_INI}') AND date('{DATA_FIM}')
    GROUP BY sp.gs_Title, ch.`DIMENSÃO`
    ORDER BY sp.gs_Title, QTD DESC
"""

dimensao_rows = spark.sql(query_dimensao).collect()

dimensao_dict = {}
for row in dimensao_rows:
    g = row["GERENTE"]
    dimensao_dict.setdefault(g, {"labels": [], "data": []})
    if row["DIMENSAO"]:
        dimensao_dict[g]["labels"].append(row["DIMENSAO"])
        dimensao_dict[g]["data"].append(int(row["QTD"] or 0))

# COMMAND ----------
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
    kpis_g   = kpis_dict.get(gerente, {})
    resumo_g = resumo_dict.get(gerente, {
        "concluido": 0, "cancelado": 0, "em_analise": 0,
        "em_andamento": 0, "atuacao_qualidade": 0,
        "encerrado_sem_resposta": 0, "abertos_5_dias": 0, "total": 0
    })

    # Status agrupado: [encerrados, pendentes, análise]
    status_chamados = [
        resumo_g["concluido"] + resumo_g["cancelado"] + resumo_g["encerrado_sem_resposta"],
        resumo_g["em_andamento"] + resumo_g["atuacao_qualidade"],
        resumo_g["em_analise"],
    ]

    # P-Score: top 10 piores (menor score primeiro)
    scores_g = sorted(
        score_dict.get(gerente, []),
        key=lambda x: x["pscore"]
    )[:10]

    # Causas: top 5 com valor > 0
    causas_g = causas_dict.get(gerente, {})
    top_causas = sorted(
        [(k, v) for k, v in causas_g.items() if v > 0],
        key=lambda x: x[1], reverse=True
    )[:5]

    dados_gerentes[gerente] = {
        "periodo":         f"{DATA_INI} a {DATA_FIM}",
        "mesesRotulo":     MESES_ROTULO,
        "kpis": {
            "produtos": kpis_g.get("PRODUTO DE DADOS", 0),
            "ucs":      kpis_g.get("CASO DE USO", 0),
            "onprem":   kpi_onprem_total,
            "total_chamados": resumo_g["total"],
            "abertos_5d":     resumo_g["abertos_5_dias"],
        },
        "pscore": {
            "labels": [s["produto"] for s in scores_g],
            "data":   [s["pscore"]  for s in scores_g],
        },
        "statusChamados": status_chamados,
        "causasRaizes": {
            "labels": [c[0] for c in top_causas],
            "data":   [c[1] for c in top_causas],
        },
        "tendencia":  tendencia_dict.get(gerente, {"meses": [], "abertos": [], "concluidos": []}),
        "dimensoes":  dimensao_dict.get(gerente, {"labels": [], "data": []}),
        "vencidos":   vencidos_dict.get(gerente, [])[:15],
    }

# COMMAND ----------
# =============================================================================
# CÉLULA 12 — GERAÇÃO DO HTML SINGLETON
# =============================================================================

_json_dados   = json.dumps(dados_gerentes, ensure_ascii=False)
_periodo_txt  = f"{_ini_mes_1.strftime('%B/%Y').capitalize()} – {_fim_mes_2.strftime('%B/%Y').capitalize()}"
_CLOSE_SCRIPT = "<" + "/script>"

_HTML = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Boletim de Qualidade de Dados — {_periodo_txt}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js">{_CLOSE_SCRIPT}
<style>
:root{{
  --primary:#C01160;--primary-dark:#8C0F3B;--primary-light:#DF1A73;
  --white:#FFFFFF;--gray-50:#F9FAFB;--gray-100:#F3F4F6;
  --gray-200:#E5E7EB;--gray-300:#D1D5DB;--gray-600:#4B5563;
  --gray-700:#374151;--gray-800:#1F2937;--gray-900:#111827;
  --green:#059669;--yellow:#D97706;--shadow:0 4px 30px rgba(0,0,0,.08)
}}
[data-theme="dark"]{{
  --white:#111827;--gray-50:#1F2937;--gray-100:#374151;--gray-200:#4B5563;
  --gray-300:#6B7280;--gray-600:#D1D5DB;--gray-700:#E5E7EB;
  --gray-800:#F3F4F6;--gray-900:#F9FAFB
}}
*{{margin:0;padding:0;box-sizing:border-box}}
html,body{{height:100%}}
body{{font-family:'Inter',sans-serif;background:var(--gray-50);color:var(--gray-800);display:flex;flex-direction:column;min-height:100vh}}

/* ── Header ── */
.hdr{{
  background:linear-gradient(135deg,var(--primary) 0%,var(--primary-dark) 100%);
  color:#fff;padding:18px 32px;display:flex;align-items:center;
  justify-content:space-between;gap:16px;flex-shrink:0;
  box-shadow:0 4px 20px rgba(0,0,0,.15);position:sticky;top:0;z-index:100
}}
.hdr-left h1{{font-size:1.25rem;font-weight:800;letter-spacing:-.01em}}
.hdr-left p{{font-size:.78rem;opacity:.8;margin-top:3px}}
.hdr-right{{display:flex;align-items:center;gap:12px;flex-wrap:wrap}}
.hdr-right label{{font-size:.75rem;font-weight:600;opacity:.85}}
.sel-gerente{{
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
  color:#fff;padding:7px 12px;border-radius:8px;font-size:.85rem;
  font-family:inherit;outline:none;min-width:240px;cursor:pointer
}}
.sel-gerente option{{background:var(--primary-dark);color:#fff}}
.theme-btn{{
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
  color:#fff;padding:7px 10px;border-radius:8px;font-size:.8rem;cursor:pointer;
  font-family:inherit;transition:background .2s
}}
.theme-btn:hover{{background:rgba(255,255,255,.25)}}

/* ── Layout principal ── */
.main{{flex:1;max-width:1280px;width:100%;margin:0 auto;padding:24px 32px;display:flex;flex-direction:column;gap:24px}}

/* ── Seção ── */
.sec-title{{font-size:1rem;font-weight:700;color:var(--primary-dark);margin-bottom:12px;display:flex;align-items:center;gap:8px}}
.sec-title::before{{content:'';display:inline-block;width:4px;height:18px;background:var(--primary);border-radius:2px}}

/* ── Cards KPI ── */
.kpi-grid{{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}}
.kpi-card{{
  background:var(--white);border:1px solid var(--gray-200);
  border-radius:12px;padding:18px;display:flex;flex-direction:column;gap:4px;
  box-shadow:var(--shadow);transition:transform .2s
}}
.kpi-card:hover{{transform:translateY(-2px)}}
.kpi-label{{font-size:.72rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.04em}}
.kpi-value{{font-size:2rem;font-weight:800;color:var(--primary);line-height:1}}
.kpi-sub{{font-size:.72rem;color:var(--gray-600)}}
.kpi-card.alert .kpi-value{{color:#DC2626}}

/* ── Grid de gráficos ── */
.charts-grid{{display:grid;gap:16px}}
.g2{{grid-template-columns:1fr 1fr}}
.g3{{grid-template-columns:1fr 1fr 1fr}}
.chart-card{{
  background:var(--white);border:1px solid var(--gray-200);
  border-radius:12px;padding:18px;box-shadow:var(--shadow)
}}
.chart-card-title{{font-size:.75rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.04em;margin-bottom:12px}}
.chart-wrap{{position:relative;width:100%}}
.h280{{height:280px}}
.h240{{height:240px}}
.h200{{height:200px}}

/* ── Tabela vencidos ── */
.table-wrap{{background:var(--white);border:1px solid var(--gray-200);border-radius:12px;overflow:auto;box-shadow:var(--shadow)}}
table{{width:100%;border-collapse:collapse;font-size:.8rem}}
thead th{{background:var(--gray-50);border-bottom:2px solid var(--gray-200);padding:10px 14px;text-align:left;font-size:.72rem;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}}
tbody td{{padding:10px 14px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}}
tbody tr:last-child td{{border-bottom:none}}
tbody tr:hover td{{background:var(--gray-50)}}
.badge-dias{{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;background:#FEE2E2;color:#DC2626}}

/* ── Estado vazio ── */
.empty-msg{{text-align:center;padding:28px;color:var(--gray-600);font-size:.85rem}}

/* ── Parecer ── */
.parecer-box{{
  background:var(--white);border:1px solid var(--gray-200);border-radius:12px;
  padding:18px;min-height:90px;box-shadow:var(--shadow);
  font-size:.88rem;color:var(--gray-700);line-height:1.6;
  outline:none
}}
.parecer-box:focus{{border-color:var(--primary)}}
.parecer-hint{{font-size:.72rem;color:var(--gray-300);font-style:italic}}

/* ── Footer ── */
.footer{{
  background:var(--white);border-top:1px solid var(--gray-200);
  text-align:center;padding:14px;font-size:.75rem;color:var(--gray-600);flex-shrink:0
}}

/* ── Período badge ── */
.periodo-badge{{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);
  border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:600
}}

@media(max-width:1024px){{.kpi-grid{{grid-template-columns:repeat(3,1fr)}}.g3{{grid-template-columns:1fr 1fr}}.g2{{grid-template-columns:1fr}}}}
@media(max-width:640px){{.kpi-grid{{grid-template-columns:1fr 1fr}}.g3,.g2{{grid-template-columns:1fr}}.main{{padding:16px}}.hdr{{padding:14px 16px}}.hdr-right{{flex-direction:column;align-items:flex-start}}}}
</style>
</head>
<body>

<header class="hdr">
  <div class="hdr-left">
    <h1>Boletim de Qualidade de Dados</h1>
    <p>Período de referência: <strong>{_periodo_txt}</strong></p>
  </div>
  <div class="hdr-right">
    <div>
      <label for="selGerente">Gerente Sr.:</label><br>
      <select id="selGerente" class="sel-gerente" onchange="renderGerente()"></select>
    </div>
    <button class="theme-btn" onclick="toggleTheme()">Tema</button>
  </div>
</header>

<main class="main" id="mainContent">

  <!-- KPIs -->
  <section>
    <div class="sec-title">Carteira por Tipo de Ativo</div>
    <div class="kpi-grid">
      <div class="kpi-card">
        <span class="kpi-label">Produtos de Dados</span>
        <span class="kpi-value" id="kProd">—</span>
        <span class="kpi-sub">Monitorados ativamente</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Casos de Uso (UCS)</span>
        <span class="kpi-value" id="kUcs">—</span>
        <span class="kpi-sub">Cadastrados</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">On-Premises</span>
        <span class="kpi-value" id="kOnprem">—</span>
        <span class="kpi-sub">Chamados Hive + SAS</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Chamados no Período</span>
        <span class="kpi-value" id="kTotal">—</span>
        <span class="kpi-sub">Período do boletim</span>
      </div>
      <div class="kpi-card alert">
        <span class="kpi-label">Vencidos (&gt; 5 dias)</span>
        <span class="kpi-value" id="kVencidos">—</span>
        <span class="kpi-sub">Sem encerramento</span>
      </div>
    </div>
  </section>

  <!-- P-Score -->
  <section>
    <div class="sec-title">P-Score Ponderado por Produto (piores)</div>
    <div class="chart-card">
      <div class="chart-card-title">Média ponderada: &Sigma;(avg_dim &times; dias_dim) / &Sigma;dias_dim — exclui VARIACAO</div>
      <div class="chart-wrap h280"><canvas id="cScore"></canvas></div>
    </div>
  </section>

  <!-- Status + Tendência -->
  <section>
    <div class="sec-title">Status e Tendência</div>
    <div class="charts-grid g2">
      <div class="chart-card">
        <div class="chart-card-title">Distribuição de Chamados por Status</div>
        <div class="chart-wrap h240"><canvas id="cStatus"></canvas></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-title">Tendência Mensal — Abertos vs Concluídos</div>
        <div class="chart-wrap h240"><canvas id="cTrend"></canvas></div>
      </div>
    </div>
  </section>

  <!-- Causas + Dimensões -->
  <section>
    <div class="sec-title">Causas e Distribuição por Dimensão</div>
    <div class="charts-grid g2">
      <div class="chart-card">
        <div class="chart-card-title">Causas Raízes Operacionais (chamados ativos)</div>
        <div class="chart-wrap h200"><canvas id="cCausas"></canvas></div>
      </div>
      <div class="chart-card">
        <div class="chart-card-title">Chamados por Dimensão de Qualidade</div>
        <div class="chart-wrap h200"><canvas id="cDimensoes"></canvas></div>
      </div>
    </div>
  </section>

  <!-- Chamados vencidos -->
  <section>
    <div class="sec-title">Chamados Vencidos (&gt; 5 dias sem encerramento)</div>
    <div class="table-wrap">
      <table id="tVencidos">
        <thead>
          <tr>
            <th>ID</th>
            <th>Produto</th>
            <th>Dimensão</th>
            <th>Aberto em</th>
            <th>Dias em aberto</th>
          </tr>
        </thead>
        <tbody id="tbodyVencidos"></tbody>
      </table>
    </div>
  </section>

  <!-- Parecer analítico -->
  <section>
    <div class="sec-title">Parecer Analítico</div>
    <div class="parecer-box" contenteditable="true" id="parecer">
      <span class="parecer-hint">Clique para digitar o parecer do período...</span>
    </div>
  </section>

</main>

<footer class="footer">
  Boletim gerado via Databricks &mdash; Bradesco | Qualidade de Dados &nbsp;|&nbsp; {_periodo_txt}
</footer>

<script>
var D = {_json_dados};
var charts = {{}};

function toggleTheme() {{
  var root = document.documentElement;
  root.dataset.theme = (root.dataset.theme === 'dark') ? '' : 'dark';
}}

function _destroyCharts() {{
  Object.values(charts).forEach(function(c) {{ try {{ c.destroy(); }} catch(e) {{}} }});
  charts = {{}};
}}

function _makeChart(id, cfg) {{
  var el = document.getElementById(id);
  if (!el) return;
  charts[id] = new Chart(el, cfg);
}}

function renderGerente() {{
  var gerente = document.getElementById('selGerente').value;
  var d = D[gerente];
  if (!d) return;

  document.getElementById('kProd').textContent    = d.kpis.produtos;
  document.getElementById('kUcs').textContent     = d.kpis.ucs;
  document.getElementById('kOnprem').textContent  = d.kpis.onprem;
  document.getElementById('kTotal').textContent   = d.kpis.total_chamados;
  document.getElementById('kVencidos').textContent = d.kpis.abertos_5d;

  _renderVencidos(d.vencidos);
  _destroyCharts();
  _renderPScore(d.pscore);
  _renderStatus(d.statusChamados);
  _renderTendencia(d.tendencia);
  _renderCausas(d.causasRaizes);
  _renderDimensoes(d.dimensoes);
}}

function _renderVencidos(vencidos) {{
  var tbody = document.getElementById('tbodyVencidos');
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

function _renderPScore(pscore) {{
  if (!pscore || !pscore.labels.length) return;
  _makeChart('cScore', {{
    type: 'bar',
    data: {{
      labels: pscore.labels,
      datasets: [{{
        label: 'P-Score Ponderado',
        data: pscore.data,
        backgroundColor: pscore.data.map(function(v) {{
          return v < 70 ? 'rgba(220,38,38,.75)' : v < 85 ? 'rgba(217,119,6,.75)' : 'rgba(5,150,105,.75)';
        }}),
        borderWidth: 0,
        borderRadius: 4,
      }}]
    }},
    options: {{
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      scales: {{
        x: {{ min: 0, max: 100, grid: {{ color: 'rgba(0,0,0,.05)' }} }},
        y: {{ ticks: {{ font: {{ size: 11 }} }} }}
      }},
      plugins: {{ legend: {{ display: false }} }}
    }}
  }});
}}

function _renderStatus(statusChamados) {{
  _makeChart('cStatus', {{
    type: 'doughnut',
    data: {{
      labels: ['Encerrados/Concluídos', 'Em Andamento', 'Em Análise'],
      datasets: [{{
        data: statusChamados,
        backgroundColor: ['#059669', '#D97706', '#C01160'],
        borderWidth: 2,
        borderColor: '#ffffff',
      }}]
    }},
    options: {{
      responsive: true,
      maintainAspectRatio: false,
      plugins: {{
        legend: {{ position: 'right', labels: {{ font: {{ size: 11 }}, boxWidth: 14 }} }}
      }}
    }}
  }});
}}

function _renderTendencia(tendencia) {{
  if (!tendencia || !tendencia.meses.length) return;
  _makeChart('cTrend', {{
    type: 'bar',
    data: {{
      labels: tendencia.meses,
      datasets: [
        {{ label: 'Abertos',    data: tendencia.abertos,    backgroundColor: '#C01160', borderRadius: 3 }},
        {{ label: 'Concluídos', data: tendencia.concluidos, backgroundColor: '#059669', borderRadius: 3 }}
      ]
    }},
    options: {{
      responsive: true,
      maintainAspectRatio: false,
      scales: {{ y: {{ beginAtZero: true, grid: {{ color: 'rgba(0,0,0,.05)' }} }} }},
      plugins: {{ legend: {{ position: 'top', labels: {{ font: {{ size: 11 }}, boxWidth: 12 }} }} }}
    }}
  }});
}}

function _renderCausas(causas) {{
  if (!causas || !causas.labels.length) return;
  _makeChart('cCausas', {{
    type: 'bar',
    data: {{
      labels: causas.labels,
      datasets: [{{
        label: 'Qtd Chamados',
        data: causas.data,
        backgroundColor: '#8C0F3B',
        borderRadius: 4,
        borderWidth: 0,
      }}]
    }},
    options: {{
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {{ legend: {{ display: false }} }},
      scales: {{ x: {{ beginAtZero: true, grid: {{ color: 'rgba(0,0,0,.05)' }} }} }}
    }}
  }});
}}

function _renderDimensoes(dimensoes) {{
  if (!dimensoes || !dimensoes.labels.length) return;
  var palette = ['#C01160','#8C0F3B','#DF1A73','#D97706','#059669','#2563EB','#7C3AED'];
  _makeChart('cDimensoes', {{
    type: 'bar',
    data: {{
      labels: dimensoes.labels,
      datasets: [{{
        label: 'Chamados',
        data: dimensoes.data,
        backgroundColor: dimensoes.labels.map(function(_, i) {{ return palette[i % palette.length]; }}),
        borderRadius: 4,
        borderWidth: 0,
      }}]
    }},
    options: {{
      responsive: true,
      maintainAspectRatio: false,
      plugins: {{ legend: {{ display: false }} }},
      scales: {{ y: {{ beginAtZero: true, grid: {{ color: 'rgba(0,0,0,.05)' }} }} }}
    }}
  }});
}}

// Inicialização
(function init() {{
  var sel = document.getElementById('selGerente');
  Object.keys(D).sort().forEach(function(g) {{
    var opt = document.createElement('option');
    opt.value = g;
    opt.text  = g;
    sel.add(opt);
  }});
  renderGerente();
}})();
{_CLOSE_SCRIPT}
</body>
</html>"""

# COMMAND ----------
# =============================================================================
# CÉLULA 13 — OUTPUT COPIÁVEL
# Exibe o HTML em uma textarea de fácil seleção dentro do output do notebook.
# O usuário copia e cola em um arquivo .html externo.
# =============================================================================

_CLOSE_TEXTAREA = "<" + "/textarea>"
_CLOSE_STYLE    = "<" + "/style>"
_CLOSE_SCRIPT2  = "<" + "/script>"

_html_escaped = _HTML.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")

_output_ui = f"""
<style>
  #boletim-output-wrap {{
    font-family: 'Segoe UI', Arial, sans-serif;
    max-width: 960px;
    margin: 0 auto;
    padding: 24px;
  }}
  #boletim-output-wrap h2 {{
    font-size: 1rem;
    color: #8C0F3B;
    margin-bottom: 8px;
    font-weight: 700;
  }}
  #boletim-output-wrap p {{
    font-size: .82rem;
    color: #374151;
    margin-bottom: 12px;
  }}
  #boletim-html-code {{
    width: 100%;
    height: 320px;
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: .75rem;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    padding: 12px;
    background: #F9FAFB;
    color: #1F2937;
    resize: vertical;
    white-space: pre;
    overflow: auto;
  }}
  #btn-copiar {{
    margin-top: 10px;
    background: #C01160;
    color: #fff;
    border: none;
    padding: 9px 20px;
    border-radius: 8px;
    font-size: .85rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
  }}
  #btn-copiar:hover {{ background: #8C0F3B; }}
  #copy-status {{
    display: inline-block;
    margin-left: 12px;
    font-size: .8rem;
    color: #059669;
    font-weight: 600;
    opacity: 0;
    transition: opacity .4s;
  }}
{_CLOSE_STYLE}

<div id="boletim-output-wrap">
  <h2>HTML do Boletim gerado</h2>
  <p>
    Selecione tudo na caixa abaixo (Ctrl+A), copie (Ctrl+C) e cole em um arquivo
    <code>.html</code> externo. O arquivo funcionará de forma standalone, sem dependências
    de servidor.
  </p>
  <textarea id="boletim-html-code" readonly spellcheck="false">{_html_escaped}{_CLOSE_TEXTAREA}
  <br>
  <button id="btn-copiar" onclick="copiarHTML()">Copiar HTML</button>
  <span id="copy-status">Copiado!</span>
</div>

<script>
function copiarHTML() {{
  var ta = document.getElementById('boletim-html-code');
  ta.select();
  document.execCommand('copy');
  var st = document.getElementById('copy-status');
  st.style.opacity = '1';
  setTimeout(function() {{ st.style.opacity = '0'; }}, 2500);
}}
{_CLOSE_SCRIPT2}
"""

displayHTML(_output_ui)

# COMMAND ----------
# =============================================================================
# CÉLULA 14 (VALIDAÇÃO) — TABELA DE VALORES CALCULADOS
# Todos os cálculos em formato tabular para conferência externa.
# Esta célula NÃO faz parte do notebook do boletim;
# serve apenas para auditar os valores embutidos no HTML.
# =============================================================================

from pyspark.sql import Row

_validacao_rows = []

for gerente in lista_gerentes:
    d  = dados_gerentes.get(gerente, {{}})
    kp = d.get("kpis", {{}})
    sc = d.get("statusChamados", [0, 0, 0])
    _validacao_rows.append(Row(
        GERENTE_SENIOR       = gerente,
        PERIODO              = f"{{DATA_INI}} a {{DATA_FIM}}",
        QTD_PRODUTOS_DADOS   = int(kp.get("produtos", 0)),
        QTD_UCS              = int(kp.get("ucs", 0)),
        QTD_ONPREM           = int(kp.get("onprem", 0)),
        QTD_CHAMADOS_PERIODO = int(kp.get("total_chamados", 0)),
        QTD_VENCIDOS_5D      = int(kp.get("abertos_5d", 0)),
        STATUS_ENCERRADOS    = int(sc[0] if len(sc) > 0 else 0),
        STATUS_PENDENTES     = int(sc[1] if len(sc) > 1 else 0),
        STATUS_EM_ANALISE    = int(sc[2] if len(sc) > 2 else 0),
        PSCORE_TOP1_PRODUTO  = (d.get("pscore", {{}}).get("labels", [None]) or [None])[0],
        PSCORE_TOP1_VALOR    = float((d.get("pscore", {{}}).get("data", [None])  or [None])[0] or 0),
        CAUSAS_TOP1          = (d.get("causasRaizes", {{}}).get("labels", [None]) or [None])[0],
        CAUSAS_TOP1_QTD      = int((d.get("causasRaizes", {{}}).get("data", [0]) or [0])[0] or 0),
        MESES_TENDENCIA      = str(d.get("tendencia", {{}}).get("meses", [])),
        ABERTOS_TENDENCIA    = str(d.get("tendencia", {{}}).get("abertos", [])),
        CONCLUIDOS_TENDENCIA = str(d.get("tendencia", {{}}).get("concluidos", [])),
        QTD_VENCIDOS_LISTA   = len(d.get("vencidos", [])),
    ))

df_validacao = spark.createDataFrame(_validacao_rows)
display(df_validacao.orderBy("GERENTE_SENIOR"))
