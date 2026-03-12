# ==============================================================================
# CELULA 1 - Imports e helpers de PDF
# ==============================================================================

import io
import re
import json
import base64
from datetime import datetime, date, timedelta
from typing import Any, Callable, Dict, List, Optional

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer

FONT_NAME = "Helvetica"

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="DocTitle", parent=styles["Heading1"], fontName=FONT_NAME,
    fontSize=16, leading=18, textColor=colors.HexColor("#1F2937"),
    alignment=TA_LEFT, spaceAfter=10,
))
styles.add(ParagraphStyle(
    name="Body", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=10.5, leading=14, textColor=colors.HexColor("#111827"),
))
styles.add(ParagraphStyle(
    name="Meta", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=9.5, leading=12, textColor=colors.HexColor("#374151"),
))
styles.add(ParagraphStyle(
    name="Separator", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=8, leading=10, textColor=colors.HexColor("#6B7280"),
))

def sanitize_filename(name: str) -> str:
    clean = re.sub(r"[^\w\-]+", "_", (name or "").strip(), flags=re.UNICODE)
    return re.sub(r"_+", "_", clean).strip("_").lower() or "arquivo"

def pct(x, ndigits=1):
    if x is None: return ""
    try:
        val = x * 100 if 0 <= x <= 1 else x
        return f"{round(val, ndigits):.{ndigits}f}"
    except Exception:
        return str(x)

def fmt_int(x):
    try:
        return f"{int(x):,}".replace(",", ".")
    except Exception:
        return str(x)

def fmt_date(d, out="%d/%m/%Y"):
    if d in (None, ""): return ""
    if isinstance(d, datetime): return d.strftime(out)
    if isinstance(d, date): return d.strftime(out)
    try:
        return datetime.fromisoformat(str(d)).strftime(out)
    except Exception:
        return str(d)

_dotted_pat = re.compile(r"\$\{([a-zA-Z_]\w*(?:\.[a-zA-Z_]\w*)*)\}")

def render_template_dotted(text: str, ctx_ns: dict) -> str:
    def _resolve(path: str):
        parts = path.split(".")
        cur = ctx_ns
        for p in parts:
            if isinstance(cur, dict) and p in cur:
                cur = cur[p]
            else:
                return None
        return cur
    def _repl(m):
        val = _resolve(m.group(1))
        return str(val) if val is not None else m.group(0)
    return _dotted_pat.sub(_repl, text)

class PageCounter:
    def __init__(self): self.count = 0
    def __call__(self, canvas, doc):
        self.count += 1
        canvas.saveState()
        canvas.setFont(FONT_NAME, 8)
        canvas.setFillColor(colors.HexColor("#6B7280"))
        canvas.drawString(30, A4[1] - 30, "Parecer Tecnico de Qualidade de Dados")
        canvas.drawRightString(A4[0] - 30, 20, f"Pagina {self.count}")
        canvas.restoreState()

def make_pdf_bytes(rendered_text: str, title: str, autor: str, assunto: str = None) -> bytes:
    buf = io.BytesIO()
    doc = SimpleDocTemplate(
        buf, pagesize=A4,
        rightMargin=36, leftMargin=36, topMargin=54, bottomMargin=36,
    )
    story = []
    story.append(Paragraph(title, styles["DocTitle"]))
    if assunto:
        story.append(Paragraph(f"<b>Assunto:</b> {assunto}", styles["Meta"]))
    story.append(Paragraph(f"<b>Elaborado por:</b> {autor}", styles["Meta"]))
    story.append(Spacer(1, 6))
    story.append(Paragraph("________________________________________", styles["Separator"]))
    story.append(Spacer(1, 8))

    def _render_raw(raw: str):
        for blk in [b.strip() for b in re.split(r"\n\s*\n", raw) if b.strip()]:
            frags = []
            for ln in blk.splitlines():
                if ln.startswith("*"):
                    frags.append(f"&nbsp;&nbsp;&bull;&nbsp;{ln[1:].strip()}")
                else:
                    frags.append(ln)
            story.append(Paragraph("<br/>".join(frags), styles["Body"]))
            story.append(Spacer(1, 8))

    for block in [b.strip() for b in re.split(r"\n\s*\n", rendered_text) if b.strip()]:
        if block.startswith("__DISPONIBILIDADE_BLOCK__"):
            disp_text = block[len("__DISPONIBILIDADE_BLOCK__"):].strip()
            if disp_text:
                _render_raw(disp_text)
        else:
            _render_raw(block)

    header = PageCounter()
    doc.build(story, onFirstPage=header, onLaterPages=header)
    out = buf.getvalue()
    buf.close()
    return out


# ==============================================================================
# CELULA 2 - Template do documento (unico ponto de edicao de conteudo)
# ==============================================================================

template_text = """
Parecer Tecnico de Qualidade de Dados
Assunto: Avaliacao da Qualidade dos Dados - ${prod.nome_produto}
Elaborado por: Data Governance ${prod.ana_esp} - ${prod.autor}
Data: ${prod.data_relatorio}

________________________________________
1. Contexto da Avaliacao
A presente analise tem como objetivo avaliar a qualidade dos dados referentes a ${prod.nome_produto}, considerando os principais pilares de qualidade: disponibilidade, completude, variacao, consistencia, unicidade e integridade.

________________________________________
2. Metodologia
A avaliacao foi conduzida por meio de:
* Validacao das regras de negocio documentadas;
* Realizacao de analise de dados quantitativa (ex.: contagens, frequencia de nulos, cruzamentos);
* Comparacao com fontes referencia, quando aplicavel;
* Entrevistas e alinhamentos com responsaveis pelo processo e donos do dado.

________________________________________
3. Resultados da Analise - Dimensoes, regras sugeridas e regras solicitadas

${sections.completude}

${sections.consistencia}

${sections.disponibilidade}

${sections.unicidade}

${sections.variacao}

________________________________________
4. Impacto no Negocio
As falhas de qualidade observadas podem impactar:
* Confiabilidade dos indicadores utilizados na tomada de decisao;
* Performance de relatorios analiticos;
* Processos regulatorios ou obrigatorios (se aplicavel).

________________________________________
5. Recomendacoes
Sugere-se as seguintes acoes para mitigacao dos problemas identificados:
1. Correcao dos registros inconsistentes e incompletos, priorizando campos criticos.
2. Revisao das regras de negocio na origem, garantindo que sistemas capturem e validem dados adequadamente.
3. Implementacao de controles automatizados, como validacoes de formato e preenchimento obrigatorio.
4. Revisao dos fluxos de ingestao e atualizacao, para minimizar atrasos.
5. Ajustes no Glossario de Dados, quando aplicavel, garantindo clareza e padronizacao.

________________________________________
6. Conclusao
Com base na analise realizada, conclui-se que o conjunto de dados apresenta nivel de qualidade classificado como ${prod.classificacao}, demandando ${prod.demanda} priorizacao de acoes corretivas. Recomenda-se acompanhamento continuo e a implementacao das acoes propostas para garantir maior confiabilidade e qualidade dos dados.
O consumidor ou gerador do dado solicitou a inclusao/exclusao de novas regras de qualidade por sua total responsabilidade.
"""

# Textos padrao para cada dimensao. As variaveis entre chaves sao substituidas
# pelos valores reais no momento da geracao. O usuario pode editar estes textos
# pelo painel HTML, mas a alteracao e valida apenas para a execucao atual.
SECTION_TEMPLATES_DEFAULT: Dict[str, str] = {
    "completude": (
        "{numero}. Completude\n"
        "Identificou-se que {Valor_Completude} atributos obrigatorios preenchidos. "
        "Os 3 atributos com menor score sao: {Lowest3_Completude}.\n"
        "Score medio de completude: {Pct_Completude}%.\n"
        "Conclusao: Orientamos a aplicabilidade de completude nos atributos conforme template."
    ),
    "consistencia": (
        "{numero}. Consistencia\n"
        "Identificou-se que {Valor_Consistencia} atributos com possibilidade de aplicacao de regras de negocio.\n"
        "Conclusao: Orientamos a aplicabilidade de Consistencia nos atributos conforme template."
    ),
    "unicidade": (
        "{numero}. Unicidade\n"
        "Identificou-se que {Valor_Unicidade} registros duplicados.\n"
        "Conclusao: Orientamos a aplicabilidade de Unicidade nos atributos conforme template."
    ),
    "variacao": (
        "{numero}. Variacao\n"
        "Identificou-se que {Valor_Variacao} registros com variacao analisada.\n"
        "Conclusao: Orientamos a aplicabilidade de Variacao nos atributos conforme template."
    ),
    "disponibilidade": (
        "{numero}. Disponibilidade\n"
        "{Valor_Disponibilidade}"
    ),
}


# ==============================================================================
# CELULA 3 - Model de dados (queries primarias e fallback)
# ==============================================================================

class ModelDados:

    def __init__(self, params: Dict[str, Any], spark):
        self.spark = spark
        self.params = dict(params or {})

    def _raw_query(self, df) -> Dict[str, Any]:
        row = df.first()
        return row.asDict() if row else {}

    def _is_empty(self, raw: Dict[str, Any], count_key: str) -> bool:
        return not raw or raw.get(count_key, 0) == 0

    def _run_with_fallback(
        self,
        primary: Callable,
        fallback: Optional[Callable],
        count_key: str,
    ) -> tuple:
        """
        Executa a query primaria (GenQuery). Se retornar vazio e houver fallback,
        executa o fallback (BI). Retorna (resultado, nome_query_usada).
        """
        result = primary()
        if self._is_empty(result, count_key) and fallback is not None:
            return fallback(), "BI"
        return result, "GenQuery"

    # ----------------------------------------------------------
    # Busca de produto por LIKE (divide o termo em tokens)
    # ----------------------------------------------------------

    def search_nome_produto(self, raw_input: str, email: str = "") -> List[Dict]:
        tokens = [t for t in re.split(r"[\s_]+", raw_input.strip()) if t]
        if not tokens:
            return []

        like_conditions = " AND ".join(
            f"b.itpo_prodt_funcl LIKE '%{t}%'" for t in tokens
        )
        email_filter = f"AND u.email LIKE '%{email}%'" if email.strip() else ""

        # PLACEHOLDER GenQuery - ajuste colunas e tabelas conforme o schema real
        df = self.spark.sql(f"""
            SELECT DISTINCT
                b.itpo_prodt_funcl  AS nome_produto,
                b.itpo_prodt        AS tipo_produto,
                u.email             AS email_responsavel,
                b.datu_criacao      AS data_criacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
            LEFT JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_responsavel AS u
                ON b.nresponsavel = u.nresponsavel
            WHERE {like_conditions}
            {email_filter}
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    def search_nome_produto_bi(self, raw_input: str) -> List[Dict]:
        """Fallback BI - nao possui campo email."""
        tokens = [t for t in re.split(r"[\s_]+", raw_input.strip()) if t]
        if not tokens:
            return []

        like_conditions = " AND ".join(
            f"itpo_prodt_funcl LIKE '%{t}%'" for t in tokens
        )

        # PLACEHOLDER BI - ajuste conforme o schema real
        df = self.spark.sql(f"""
            SELECT DISTINCT
                itpo_prodt_funcl AS nome_produto,
                itpo_prodt       AS tipo_produto,
                NULL             AS email_responsavel,
                datu_criacao     AS data_criacao
            FROM pr_platfun.bi_layer.tdmsao_tpo_prodt_dados
            WHERE {like_conditions}
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    # ----------------------------------------------------------
    # Queries de metricas por dimensao
    # ----------------------------------------------------------

    def _query_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        # PLACEHOLDER GenQuery
        df = self.spark.sql(f"""
            WITH base AS (
                SELECT a.pscore_calcd, c.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                    ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS c
                    ON a.ncatlg_base_cpo_tbela = c.ncatlg_base_cpo_tbela
                WHERE
                    a.ndmsao_quald_dados = 2
                    AND b.itpo_prodt_funcl = '{nome}'
                    AND a.nsttus_mntrc = 1
                    AND b.itpo_prodt = '{tipo}'
                    AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
            )
            SELECT
                COUNT(*) AS count_pscore,
                COUNT_IF(pscore_calcd != 100) AS count_pscore_not_100,
                100 - ROUND(
                    100.0 * COUNT_IF(pscore_calcd != 100) / NULLIF(COUNT(pscore_calcd), 0), 2
                ) AS pct_pscore_calcd,
                ARRAY_JOIN(
                    TRANSFORM(
                        SLICE(
                            ARRAY_SORT(
                                COLLECT_LIST(
                                    CASE WHEN pscore_calcd > 0
                                    THEN NAMED_STRUCT('pscore_calcd', pscore_calcd, 'icpo_tbela', icpo_tbela)
                                    END
                                )
                            ), 1, 3
                        ),
                        a -> CAST(a.icpo_tbela AS STRING)
                    ), ', '
                ) AS lowest_3
            FROM base
        """)
        return self._raw_query(df)

    def _fallback_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        # PLACEHOLDER BI
        df = self.spark.sql(f"""
            SELECT
                COUNT(*) AS count_pscore,
                0        AS count_pscore_not_100,
                100.0    AS pct_pscore_calcd,
                ''       AS lowest_3
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 2
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 3
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 3
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 4
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 4
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 5
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 5
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    # ----------------------------------------------------------
    # Coleta todas as metricas para o produto selecionado
    # ----------------------------------------------------------

    def coletar_metricas(
        self,
        nome: str,
        tipo: str,
        dt_ini: str,
        dt_fim: str,
    ) -> Dict[str, Any]:
        """
        Retorna um dict com os valores de cada dimensao e a query usada.
        Estrutura: { "completude": {..., "query": "GenQuery"|"BI"}, ... }
        """
        resultado = {}

        raw_c, q = self._run_with_fallback(
            lambda: self._query_completude(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_completude(nome, tipo, dt_ini, dt_fim),
            "count_pscore",
        )
        resultado["completude"] = {
            "count":    raw_c.get("count_pscore", 0),
            "pct":      raw_c.get("pct_pscore_calcd"),
            "lowest_3": raw_c.get("lowest_3", ""),
            "query":    q,
        }

        raw_cs, q = self._run_with_fallback(
            lambda: self._query_consistencia(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_consistencia(nome, tipo, dt_ini, dt_fim),
            "count_consistencia",
        )
        resultado["consistencia"] = {
            "count": raw_cs.get("count_consistencia", 0),
            "query": q,
        }

        raw_u, q = self._run_with_fallback(
            lambda: self._query_unicidade(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_unicidade(nome, tipo, dt_ini, dt_fim),
            "count_unicidade",
        )
        resultado["unicidade"] = {
            "count": raw_u.get("count_unicidade", 0),
            "query": q,
        }

        raw_v, q = self._run_with_fallback(
            lambda: self._query_variacao(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_variacao(nome, tipo, dt_ini, dt_fim),
            "count_variacao",
        )
        resultado["variacao"] = {
            "count": raw_v.get("count_variacao", 0),
            "query": q,
        }

        # Disponibilidade e preenchida manualmente pelo usuario no painel
        resultado["disponibilidade"] = {"count": 0, "query": "manual"}

        return resultado

    # ----------------------------------------------------------
    # Monta o context_ns a partir do payload do painel
    # ----------------------------------------------------------

    def build_context_ns(self, payload: Dict[str, Any], metricas: Dict[str, Any]) -> Dict[str, Any]:
        ana_esp = payload.get("ana_esp", "Analista").strip().title() or "Analista"
        nome_display = payload.get("nome_display", "").strip()
        if not nome_display:
            nome_display = payload.get("nome_produto", "").replace("_", " ").strip()

        prod_ns = {
            "nome_produto"   : nome_display,
            "tipo_produto"   : payload.get("tipo_produto", ""),
            "autor"          : payload.get("autor", ""),
            "ana_esp"        : ana_esp,
            "data_relatorio" : datetime.now().strftime("%d/%m/%Y"),
            "classificacao"  : payload.get("classificacao", "satisfatorio"),
            "demanda"        : payload.get("demanda", "baixa"),
        }

        # Numeracao dinamica das secoes ativas
        dimensoes_ordem = ["completude", "consistencia", "disponibilidade", "unicidade", "variacao"]
        selecionadas = [d for d in dimensoes_ordem if payload.get("dimensoes", {}).get(d, False)]
        numeracao = {dim: f"3.{i+1}" for i, dim in enumerate(selecionadas)}

        templates_customizados = payload.get("textos", {})
        valores_customizados   = payload.get("valores", {})

        sections_ns = {}
        for dim in dimensoes_ordem:
            if dim not in selecionadas:
                sections_ns[dim] = ""
                continue

            numero = numeracao[dim]
            tpl    = templates_customizados.get(dim) or SECTION_TEMPLATES_DEFAULT.get(dim, "")
            vals   = metricas.get(dim, {})

            if dim == "disponibilidade":
                texto_disp = valores_customizados.get("disponibilidade", "")
                rendered   = tpl.format(
                    numero               = numero,
                    Valor_Disponibilidade = texto_disp,
                )
                sections_ns[dim] = (
                    "__DISPONIBILIDADE_BLOCK__\n" + rendered.strip()
                )
            else:
                count_val  = valores_customizados.get(dim) or vals.get("count", 0)
                fmt_count  = fmt_int(count_val)

                try:
                    rendered = tpl.format(
                        numero             = numero,
                        Valor_Completude   = fmt_count,
                        Valor_Consistencia = fmt_count,
                        Valor_Unicidade    = fmt_count,
                        Valor_Variacao     = fmt_count,
                        Lowest3_Completude = vals.get("lowest_3", "N/A"),
                        Pct_Completude     = pct(vals.get("pct")),
                    )
                except KeyError:
                    rendered = tpl

                sections_ns[dim] = rendered.strip()

        return {"prod": prod_ns, "sections": sections_ns}




# ==============================================================================

# ==============================================================================
# CELULA 4 - Painel interativo (ipywidgets nativos + HTML para display)
#
# Execute esta celula UMA VEZ. O fluxo e em 5 passos guiados:
#   Passo 1 → Identificacao do analista
#   Passo 2 → Busca e selecao do produto
#   Passo 3 → Periodo de analise + coleta de metricas
#   Passo 4 → Dimensoes, revisao de valores e textos
#   Passo 5 → Geracao do PDF
# ==============================================================================

import ipywidgets as W
import threading as _t
from IPython.display import display as _display, HTML as _HTML

# ─────────────────────────────────────────────
# PALETA E CSS COMPARTILHADO (injetado 1x via HTML widget)
# ─────────────────────────────────────────────
_STYLES = W.HTML(value="""<style>
/* ── Reset e base ── */
.pq-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
.pq-wrap {
  font-family: 'Segoe UI', Arial, sans-serif;
  font-size: 13px;
  color: #2C2C2C;
  max-width: 900px;
  background: #F2F2F2;
  padding: 16px;
  border-radius: 4px;
}

/* ── Header ── */
.pq-header {
  background: #8B0000;
  color: #fff;
  border-radius: 4px 4px 0 0;
  padding: 20px 28px 17px;
  border-bottom: 4px solid #CC0000;
  margin-bottom: 0;
}
.pq-header h2 {
  font-size: 14px; font-weight: 700;
  letter-spacing: .5px; text-transform: uppercase;
}
.pq-header p { font-size: 11px; color: #FFCCCC; margin-top: 4px; }

/* ── Stepper ── */
.pq-stepper {
  display: flex; align-items: center;
  background: #fff;
  border: 1px solid #DCDCDC; border-top: none;
  border-radius: 0 0 4px 4px;
  padding: 13px 20px; margin-bottom: 16px;
}
.pq-step { display:flex; align-items:center; gap:7px; font-size:11px; font-weight:600; color:#ABABAB; flex:1; white-space:nowrap; }
.pq-step.active { color:#CC0000; }
.pq-step.done   { color:#1A6B3A; }
.pq-step-num {
  width:22px; height:22px; border-radius:50%;
  border:2px solid #DCDCDC;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; flex-shrink:0;
  background:#fff; color:#ABABAB;
}
.pq-step.active .pq-step-num { border-color:#CC0000; color:#CC0000; background:#FFF5F5; }
.pq-step.done   .pq-step-num { border-color:#1A6B3A; background:#1A6B3A; color:#fff; }
.pq-step-line { flex:1; height:1px; background:#DCDCDC; margin:0 8px; min-width:12px; }
.pq-step-line.done { background:#1A6B3A; }

/* ── Section header bar ── */
.pq-card-hdr {
  background:#F7F7F7; border:1px solid #DCDCDC; border-radius:4px;
  padding:10px 16px; font-weight:700; font-size:11px;
  color:#1A1A1A; text-transform:uppercase; letter-spacing:.6px;
  display:flex; align-items:center; gap:10px; margin-bottom:14px;
}
.pq-icon {
  width:20px; height:20px; border-radius:2px;
  background:#8B0000; color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; flex-shrink:0;
}

/* ── Field label ── */
.pq-lbl {
  display:block; font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:.6px; color:#6B6B6B; margin-bottom:4px;
}

/* ── Native widget overrides ── */
/* Inputs / Dropdowns / Textareas */
.pq-input input, .pq-input select, .pq-input textarea,
.pq-dropdown select, .pq-textarea textarea, .pq-datepicker input {
  border: 1px solid #DCDCDC !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 13px !important;
  color: #2C2C2C !important;
  background: #fff !important;
  padding: 6px 9px !important;
}
.pq-input input:focus, .pq-dropdown select:focus,
.pq-textarea textarea:focus, .pq-datepicker input:focus {
  border-color: #8B0000 !important;
  outline: none !important;
}

/* Primary button — dark red, white text */
.pq-btn-primary button {
  background: #8B0000 !important;
  color: #ffffff !important;
  border: 1px solid #700000 !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  letter-spacing: .2px !important;
  cursor: pointer !important;
}
.pq-btn-primary button:hover {
  background: #700000 !important;
}
.pq-btn-primary button:disabled {
  background: #C8A0A0 !important;
  border-color: #C8A0A0 !important;
  color: #fff !important;
  cursor: not-allowed !important;
}

/* Secondary button — neutral gray */
.pq-btn-secondary button {
  background: #fff !important;
  color: #3C3C3C !important;
  border: 1px solid #DCDCDC !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
}
.pq-btn-secondary button:hover { background: #F5F5F5 !important; }

/* Danger button — outlined red */
.pq-btn-danger button {
  background: #fff !important;
  color: #8B0000 !important;
  border: 1px solid #CC0000 !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
}
.pq-btn-danger button:hover { background: #FFF5F5 !important; }

/* Checkboxes */
.pq-checkbox label { font-size:13px !important; color:#2C2C2C !important; font-family:'Segoe UI',Arial,sans-serif !important; }
.pq-checkbox input[type=checkbox] { accent-color: #8B0000 !important; }

/* ── Infobox ── */
.pq-infobox {
  background:#F7F7F7; border-left:3px solid #DCDCDC;
  border-radius:0 3px 3px 0; padding:9px 13px;
  font-size:12px; color:#5C5C5C; margin-bottom:12px;
}

/* ── Produto badge ── */
.pq-produto-badge {
  background:#FFF5F5; border-left:4px solid #CC0000;
  border-radius:0 3px 3px 0; padding:10px 14px;
  font-size:13px; color:#8B0000; font-weight:600; margin-bottom:14px;
}

/* ── Metric cards ── */
.pq-mcards { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
.pq-mc {
  background:#FAFAFA; border:1px solid #DCDCDC;
  border-radius:4px; padding:14px 10px; text-align:center;
}
.pq-mc .dn { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#6B6B6B; margin-bottom:8px; }
.pq-mc .dv { font-size:24px; font-weight:700; color:#1A1A1A; line-height:1; margin-bottom:6px; }
.pq-mc .dv.zero { color:#BDBDBD; }

/* ── Table ── */
.pq-table { width:100%; border-collapse:collapse; font-size:12px; }
.pq-table th { background:#F7F7F7; padding:8px 12px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6B6B6B; border-bottom:2px solid #DCDCDC; }
.pq-table td { padding:9px 12px; border-bottom:1px solid #EBEBEB; vertical-align:middle; color:#2C2C2C; }
.pq-table tbody tr:hover { background:#FEF5F5; }
.pq-table tbody tr.pq-sel { background:#FFF0F0; border-left:3px solid #CC0000; }

/* ── Tags ── */
.pq-tag { display:inline-block; padding:2px 7px; border-radius:2px; font-size:10px; font-weight:700; letter-spacing:.3px; }
.pq-gq  { background:#E8F0FE; color:#1A56DB; }
.pq-bi  { background:#FEF3C7; color:#92400E; }
.pq-manual { background:#F0F0F0; color:#5C5C5C; }

/* ── Status ── */
.pq-status-ok  { font-size:12px; color:#1A6B3A; font-weight:600; }
.pq-status-err { font-size:12px; color:#CC0000; font-weight:600; }
.pq-status-inf { font-size:12px; color:#5C5C5C; }

/* ── Chip ── */
.pq-chip {
  display:inline-block; background:#F0F0F0; border:1px solid #DCDCDC;
  border-radius:2px; padding:2px 7px; font-family:'Courier New',monospace;
  font-size:11px; color:#2C2C2C; margin:2px 2px;
}

/* ── Empty ── */
.pq-empty { text-align:center; padding:22px; color:#ABABAB; font-size:12px; border:1px dashed #DCDCDC; border-radius:4px; }

/* ── Summary ── */
.pq-summary {
  background:#FAFAFA; border:1px solid #DCDCDC; border-radius:4px;
  padding:16px 18px; font-size:13px; line-height:2.1; margin-bottom:16px;
}
.pq-summary b { color:#1A1A1A; }
</style>""")

# ─────────────────────────────────────────────
# ESTADO DA SESSAO
# ─────────────────────────────────────────────
_state = {
    'step':        1,
    'resultados':  [],
    'produto':     None,
    'metricas':    {},
    'dimensoes':   {},
    'valores':     {},
    'textos':      {},
    'dt_ini':      (datetime.now() - timedelta(days=30)).strftime('%Y-%m-%d'),
    'dt_fim':      datetime.now().strftime('%Y-%m-%d'),
    'autor':       '',
    'ana_esp':     'Analista',
    'nome_display': '',
}

# ─────────────────────────────────────────────
# HELPERS DE HTML
# ─────────────────────────────────────────────
def _h_tag(cls, label, query=''):
    qmap = {'GenQuery': 'pq-gq', 'BI': 'pq-bi', 'manual': 'pq-manual'}
    qcls = qmap.get(query, 'pq-gq')
    tag  = f'<span class="pq-tag {qcls}">{query}</span>' if query else ''
    return f'<span class="pq-tag {qcls}">{label}</span>' if not query else tag

def _h_stepper(current):
    labels = ['Identificacao', 'Busca', 'Periodo & Metricas', 'Dimensoes & Textos', 'Gerar PDF']
    parts  = []
    for i, lbl in enumerate(labels, 1):
        cls = 'done' if i < current else ('active' if i == current else '')
        num = '&#10003;' if i < current else str(i)
        parts.append(f'<div class="pq-step {cls}"><div class="pq-step-num">{num}</div><span>{lbl}</span></div>')
        if i < len(labels):
            lcls = 'done' if i < current else ''
            parts.append(f'<div class="pq-step-line {lcls}"></div>')
    return f'<div class="pq-stepper">{"".join(parts)}</div>'

def _h_card(icon, title, body):
    return f"""<div class="pq-card">
  <div class="pq-card-hdr"><div class="pq-icon">{icon}</div>{title}</div>
  <div class="pq-card-body">{body}</div>
</div>"""

def _h_mcards(met):
    DIMS = ['completude','consistencia','disponibilidade','unicidade','variacao']
    DLBL = {'completude':'Completude','consistencia':'Consistencia',
            'disponibilidade':'Disponibilidade','unicidade':'Unicidade','variacao':'Variacao'}
    qmap = {'GenQuery':'pq-gq','BI':'pq-bi','manual':'pq-manual'}
    cards = []
    for d in DIMS:
        m   = met.get(d, {})
        v   = '—' if d == 'disponibilidade' else (m.get('count', 0))
        cls = 'dv zero' if v == 0 else 'dv'
        q   = m.get('query', '')
        qcls = qmap.get(q, 'pq-gq')
        qtag = '' if d == 'disponibilidade' else f'<span class="pq-tag {qcls}" style="margin-top:4px;display:inline-block">{q}</span>'
        cards.append(f'<div class="pq-mc"><div class="dn">{DLBL[d]}</div><div class="{cls}">{v}</div>{qtag}</div>')
    return f'<div class="pq-mcards">{"".join(cards)}</div>'

def _h_produto_badge(p):
    nome = (p.get('nome_produto') or '').replace('_', ' ')
    tipo = p.get('tipo_produto', '')
    return f'<div class="pq-produto-badge">Produto selecionado: <strong>{nome}</strong>' \
           + (f' &mdash; Tipo: {tipo}' if tipo else '') + '</div>'

def _h_search_table(rows):
    if not rows:
        return '<div class="pq-empty">Nenhum produto encontrado.</div>'
    qmap = {'GenQuery':'pq-gq','BI':'pq-bi'}
    hdr = '<table class="pq-table"><thead><tr><th>Nome</th><th>Tipo</th><th>E-mail</th><th>Query</th></tr></thead><tbody>'
    trs = []
    for i, r in enumerate(rows):
        q    = r.get('_query', 'GenQuery')
        qcls = qmap.get(q, 'pq-gq')
        nome = (r.get('nome_produto') or '').replace('_', ' ')
        email_val = r.get('email_responsavel') or '<span style="color:#BDBDBD">N/D</span>'
        tipo_val  = r.get('tipo_produto') or '-'
        trs.append(
            f'<tr id="pqr-{i}">'
            f'<td><strong>{nome}</strong></td>'
            f'<td>{tipo_val}</td>'
            f'<td>{email_val}</td>'
            f'<td><span class="pq-tag {qcls}">{q}</span></td></tr>'
        )
    return hdr + ''.join(trs) + '</tbody></table>'

def _fmt_serial(obj):
    if hasattr(obj, 'item'):  return obj.item()
    if isinstance(obj, dict): return {k: _fmt_serial(v) for k, v in obj.items()}
    if isinstance(obj, list): return [_fmt_serial(v) for v in obj]
    return obj


# ─────────────────────────────────────────────
# WIDGETS POR PASSO
# ─────────────────────────────────────────────

# ── HEADER (fixo) ──
_w_header = W.HTML(value="""<div class="pq-header">
  <h2>Parecer Tecnico de Qualidade de Dados</h2>
  <p>Data Governance &mdash; Bradesco</p>
</div>""")

# ── STEPPER (atualizado a cada passo) ──
_w_stepper = W.HTML(value=_h_stepper(1))

# ─── PASSO 1: Identificacao ────────────────────────────────────────────────────
_w_autor    = W.Text(placeholder='Nome do analista', layout=W.Layout(width='340px'))
_w_autor.add_class('pq-input')
_w_ana_esp  = W.Dropdown(options=['Analista', 'Especialista'], layout=W.Layout(width='200px'))
_w_ana_esp.add_class('pq-dropdown')
_w_p1_next  = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'))
_w_p1_next.style.button_color = '#8B0000'
_w_p1_next.add_class('pq-btn-primary')

_w_p1_status = W.HTML(value='')

_w_p1_label_autor   = W.HTML('<span class="pq-lbl">Autor do Documento</span>')
_w_p1_label_cargo   = W.HTML('<span class="pq-lbl">Cargo / Perfil</span>')

_w_passo1 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">1</div>Identificacao do Documento</div>'),
        W.HBox([
        W.VBox([_w_p1_label_autor, _w_autor], layout=W.Layout(gap='4px')),
        W.VBox([_w_p1_label_cargo, _w_ana_esp], layout=W.Layout(gap='4px')),
    ], layout=W.Layout(gap='16px', align_items='flex-start')),
    W.HTML('<br>'),
    W.HBox([_w_p1_next, _w_p1_status], layout=W.Layout(gap='12px', align_items='center')),
])

# ─── PASSO 2: Busca ────────────────────────────────────────────────────────────
_w_busca_nome  = W.Text(placeholder='Digite parte do nome do produto...', layout=W.Layout(width='380px'))
_w_busca_nome.add_class('pq-input')
_w_busca_email = W.Text(placeholder='Filtro opcional', layout=W.Layout(width='220px'))
_w_busca_email.add_class('pq-input')
_w_btn_buscar  = W.Button(description='Buscar', layout=W.Layout(width='100px'))
_w_btn_buscar.style.button_color = '#8B0000'
_w_btn_buscar.add_class('pq-btn-primary')

_w_busca_result = W.HTML(value='<div class="pq-empty">Nenhuma busca realizada ainda.</div>')
_w_busca_status = W.HTML(value='')

_w_p2_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p2_back.add_class('pq-btn-secondary')
_w_p2_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'), disabled=True)
_w_p2_next.style.button_color = '#8B0000'
_w_p2_next.add_class('pq-btn-primary')

_w_passo2 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">2</div>Busca de Produto</div>'),
        W.HTML('<span class="pq-lbl">Nome do Produto</span>'),
    W.HBox([_w_busca_nome, _w_btn_buscar], layout=W.Layout(gap='8px', align_items='center')),
    W.HTML('<span class="pq-lbl">E-mail Responsavel (so GenQuery)</span>'),
    _w_busca_email,
    W.HTML('<br>'),
    _w_busca_status,
    _w_busca_result,
    W.HTML('<br>'),
    W.HBox([_w_p2_back, _w_p2_next], layout=W.Layout(gap='8px')),
])

# ─── PASSO 3: Periodo + Metricas ───────────────────────────────────────────────
_w_dt_ini    = W.DatePicker(value=datetime.now().date() - timedelta(days=30), layout=W.Layout(width='180px'))
_w_dt_ini.add_class('pq-datepicker')
_w_dt_fim    = W.DatePicker(value=datetime.now().date(), layout=W.Layout(width='180px'))
_w_dt_fim.add_class('pq-datepicker')
_w_atalho    = W.Dropdown(
    options=[('Selecione...',''), ('7 dias','7'), ('30 dias','30'),
             ('90 dias','90'), ('180 dias','180'), ('365 dias','365')],
    layout=W.Layout(width='150px')
)
_w_atalho.add_class('pq-dropdown')
_w_btn_metricas  = W.Button(description='Coletar Metricas', layout=W.Layout(width='160px'))
_w_btn_metricas.style.button_color = '#8B0000'
_w_btn_metricas.add_class('pq-btn-primary')

_w_metricas_status = W.HTML(value='')
_w_metricas_cards  = W.HTML(value='')

_w_p3_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p3_back.add_class('pq-btn-secondary')
_w_p3_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'), disabled=True)
_w_p3_next.style.button_color = '#8B0000'
_w_p3_next.add_class('pq-btn-primary')

_w_passo3 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">3</div>Periodo de Analise e Metricas</div>'),
        W.HTML('<span class="pq-lbl">Periodo</span>'),
    W.HBox([_w_dt_ini, _w_dt_fim, _w_atalho, _w_btn_metricas], layout=W.Layout(gap='12px', align_items='center')),
    W.HTML('<br>'),
    _w_metricas_status,
    _w_metricas_cards,
    W.HTML('<br>'),
    W.HBox([_w_p3_back, _w_p3_next], layout=W.Layout(gap='8px')),
])

# ─── PASSO 4: Dimensoes + Valores + Textos ─────────────────────────────────────
_DIMS = ['completude', 'consistencia', 'disponibilidade', 'unicidade', 'variacao']
_DLBL = {'completude': 'Completude', 'consistencia': 'Consistencia',
         'disponibilidade': 'Disponibilidade', 'unicidade': 'Unicidade', 'variacao': 'Variacao'}

# Checkboxes de dimensao
_w_dim_checks = {d: W.Checkbox(value=False, description=_DLBL[d], indent=False,
                                layout=W.Layout(width='180px')) for d in _DIMS}
for _d in _DIMS: _w_dim_checks[_d].add_class('pq-checkbox')
_w_dim_status = W.HTML(value='')

# Inputs de valor por dimensao (numerico, exceto disponibilidade = textarea)
_w_val_inputs = {d: W.BoundedFloatText(value=0, min=0, max=9999999, step=1,
                                        description='', layout=W.Layout(width='160px'))
                 for d in _DIMS if d != 'disponibilidade'}
for _d in _w_val_inputs: _w_val_inputs[_d].add_class('pq-input')
_w_val_disp   = W.Textarea(placeholder='Descreva a disponibilidade do produto...',
                            layout=W.Layout(width='100%', height='80px'))
_w_val_disp.add_class('pq-textarea')

# Textareas de template por dimensao
_w_txt_areas  = {d: W.Textarea(layout=W.Layout(width='100%', height='100px'),
                                value=SECTION_TEMPLATES_DEFAULT.get(d, ''))
                 for d in _DIMS}
for _d in _DIMS: _w_txt_areas[_d].add_class('pq-textarea')
_w_txt_tabs   = W.Tab()   # tabs de textos

_w_p4_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p4_back.add_class('pq-btn-secondary')
_w_p4_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'))
_w_p4_next.style.button_color = '#8B0000'
_w_p4_next.add_class('pq-btn-primary')

# Display do produto selecionado no topo do passo 4
_w_p4_produto  = W.HTML(value='')
_w_nome_display = W.Text(placeholder='Preenchido automaticamente', layout=W.Layout(width='340px'))
_w_nome_display.add_class('pq-input')
_w_tipo_produto = W.Text(placeholder='Preenchido automaticamente', layout=W.Layout(width='220px'))
_w_tipo_produto.add_class('pq-input')

_w_p4_label_nome = W.HTML('<span class="pq-lbl">Nome para Exibicao no PDF</span>')
_w_p4_label_tipo = W.HTML('<span class="pq-lbl">Tipo do Produto</span>')

# Dropdowns de Conclusao
_w_classificacao = W.Dropdown(
    options=['satisfatorio', 'moderado', 'insatisfatorio'],
    value='satisfatorio',
    layout=W.Layout(width='200px')
)
_w_classificacao.add_class('pq-dropdown')
_w_demanda = W.Dropdown(
    options=['baixa', 'media', 'alta'],
    value='baixa',
    layout=W.Layout(width='160px')
)
_w_demanda.add_class('pq-dropdown')

def _build_p4():
    """Reconstroi o conteudo do passo 4 com base no estado atual."""
    ativas = [d for d in _DIMS if _w_dim_checks[d].value]

    # Secao valores
    val_widgets = []
    for d in ativas:
        lbl = W.HTML(f'<span class="pq-lbl">{_DLBL[d]}</span>')
        if d == 'disponibilidade':
            val_widgets.append(W.VBox([lbl, _w_val_disp], layout=W.Layout(gap='4px', width='100%')))
        else:
            val_widgets.append(W.VBox([lbl, _w_val_inputs[d]], layout=W.Layout(gap='4px')))

    val_section = W.VBox([
        W.HTML('<div class="pq-card-hdr" style="margin:12px 0 8px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">V</div>Revisao de Valores</div>'),
        W.HTML('<div class="pq-infobox">Valores pre-preenchidos pela query. Edite se necessario.</div>'),
        W.HBox(val_widgets, layout=W.Layout(flex_wrap='wrap', gap='16px')) if val_widgets
          else W.HTML('<div class="pq-empty">Selecione ao menos uma dimensao.</div>'),
        ]) if ativas else W.HTML('')

    # Secao textos (abas)
    if ativas:
        tab_children = []
        for d in ativas:
            CHIP_VARS = {
                'completude':      ['{numero}', '{Valor_Completude}', '{Pct_Completude}', '{Lowest3_Completude}'],
                'consistencia':    ['{numero}', '{Valor_Consistencia}'],
                'unicidade':       ['{numero}', '{Valor_Unicidade}'],
                'variacao':        ['{numero}', '{Valor_Variacao}'],
                'disponibilidade': ['{numero}', '{Valor_Disponibilidade}'],
            }
            chips_html = ' '.join(f'<span class="pq-chip">{v}</span>' for v in CHIP_VARS.get(d, []))
            tab_children.append(W.VBox([
                W.HTML(f'<div class="pq-infobox" style="margin-bottom:8px">Variaveis: {chips_html}</div>'),
                _w_txt_areas[d],
            ]))
        _w_txt_tabs.children = tab_children
        for i, d in enumerate(ativas):
            _w_txt_tabs.set_title(i, _DLBL[d])
        txt_section = W.VBox([
            _w_txt_tabs,
                ])
    else:
        txt_section = W.HTML('')

    return W.VBox([
        _w_p4_produto,
        W.HTML('<span class="pq-lbl">Nome para Exibicao no PDF &nbsp;&nbsp; Tipo do Produto</span>'),
        W.HBox([
            W.VBox([_w_p4_label_nome, _w_nome_display], layout=W.Layout(gap='4px')),
            W.VBox([_w_p4_label_tipo, _w_tipo_produto], layout=W.Layout(gap='4px')),
        ], layout=W.Layout(gap='16px', align_items='flex-start')),
        W.HTML('<br>'),
        W.HTML('<div class="pq-card-hdr" style="margin-bottom:8px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">6</div>Conclusao</div>'),
        W.HTML('<div class="pq-infobox">Estes valores serao inseridos automaticamente na secao de Conclusao do documento.</div>'),
        W.HBox([
            W.VBox([
                W.HTML('<span class="pq-lbl">Nivel de Qualidade</span>'),
                _w_classificacao,
            ], layout=W.Layout(gap='4px')),
            W.VBox([
                W.HTML('<span class="pq-lbl">Priorizacao de Acoes</span>'),
                _w_demanda,
            ], layout=W.Layout(gap='4px')),
        ], layout=W.Layout(gap='24px', align_items='flex-start')),
        W.HTML('<br><span class="pq-lbl">Dimensoes a incluir no parecer</span>'),
        W.HTML('<div class="pq-infobox">Marcadas automaticamente quando count &gt; 0.</div>'),
        W.HBox(list(_w_dim_checks.values()), layout=W.Layout(flex_wrap='wrap', gap='8px')),
        _w_dim_status,
        W.HTML('<br>'),
        val_section,
        W.HTML('<br>'),
        txt_section,
        W.HTML('<br>'),
        W.HBox([_w_p4_back, _w_p4_next], layout=W.Layout(gap='8px')),
        ])

_w_passo4_container = W.VBox([])  # preenchido dinamicamente

# ─── PASSO 5: Gerar PDF ────────────────────────────────────────────────────────
_w_pdf_status  = W.HTML(value='')
_w_pdf_output  = W.HTML(value='')
_w_p5_back     = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p5_back.add_class('pq-btn-secondary')
_w_btn_pdf     = W.Button(description='Gerar PDF', layout=W.Layout(width='140px'))
_w_btn_pdf.style.button_color = '#8B0000'
_w_btn_pdf.add_class('pq-btn-primary')
_w_btn_limpar  = W.Button(description='Limpar tudo', layout=W.Layout(width='120px'))
_w_btn_limpar.add_class('pq-btn-danger')

_w_passo5 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">5</div>Gerar Documento</div>'),
    W.HTML('<div class="pq-infobox">Revise os dados nos passos anteriores. Ao clicar em Gerar PDF, as metricas serao re-coletadas e o documento sera gerado.</div>'),
    W.HTML('<br>'),
    W.HBox([_w_p5_back, _w_btn_pdf, _w_btn_limpar], layout=W.Layout(gap='10px', align_items='center')),
    W.HTML('<br>'),
    _w_pdf_status,
    _w_pdf_output,
])

# ─────────────────────────────────────────────
# CONTAINER PRINCIPAL
# ─────────────────────────────────────────────
_w_passo_area = W.VBox([])   # troca de conteudo a cada passo

_main_ui = W.VBox([
    _STYLES,
    _w_header,
    _w_stepper,
    _w_passo_area,
], layout=W.Layout(max_width='920px'))


def _show_step(n):
    _state['step'] = n
    _w_stepper.value = _h_stepper(n)
    if   n == 1: _w_passo_area.children = [_w_passo1]
    elif n == 2: _w_passo_area.children = [_w_passo2]
    elif n == 3: _w_passo_area.children = [_w_passo3]
    elif n == 4:
        _w_passo4_container.children = [_build_p4()]
        _w_passo_area.children = [_w_passo4_container]
    elif n == 5: _w_passo_area.children = [_w_passo5]


# ─────────────────────────────────────────────
# CALLBACKS
# ─────────────────────────────────────────────

def _on_p1_next(b):
    nome = _w_autor.value.strip()
    if not nome:
        _w_p1_status.value = '<span class="pq-status-err">Preencha o nome do autor.</span>'
        return
    _state['autor']   = nome
    _state['ana_esp'] = _w_ana_esp.value
    _w_p1_status.value = ''
    _show_step(2)

_w_p1_next.on_click(_on_p1_next)
_w_p2_back.on_click(lambda b: _show_step(1))
_w_p3_back.on_click(lambda b: _show_step(2))
_w_p4_back.on_click(lambda b: _show_step(3))
_w_p5_back.on_click(lambda b: _show_step(4))


def _on_buscar(b):
    nome = _w_busca_nome.value.strip()
    if not nome:
        _w_busca_status.value = '<span class="pq-status-err">Digite o nome do produto.</span>'
        return
    _w_busca_status.value  = '<span class="pq-status-inf">&#x23F3; Buscando...</span>'
    _w_busca_result.value  = ''
    _w_p2_next.disabled    = True
    def _run():
        m   = ModelDados(params={}, spark=spark)
        res = m.search_nome_produto(nome, _w_busca_email.value.strip())
        if not res:
            res = m.search_nome_produto_bi(nome)
            for r in res: r['_query'] = 'BI'
        else:
            for r in res: r['_query'] = 'GenQuery'
        _state['resultados'] = _fmt_serial(res)
        _w_busca_status.value = f'<span class="pq-status-ok">&#10003; {len(res)} resultado(s) encontrado(s). Selecione abaixo.</span>' if res else ''
        _w_busca_result.value = _h_search_table(res)
    _t.Thread(target=_run, daemon=True).start()

_w_btn_buscar.on_click(_on_buscar)


def _select_produto(idx):
    """Chamado pelo JS via onclick — nao disponivel no ipywidgets puro.
    A selecao e feita via Dropdown gerado apos busca."""
    pass  # ver abaixo — usamos Dropdown de selecao

# Dropdown de selecao do produto (aparece apos busca)
_w_prod_select = W.Dropdown(options=[('— Selecione um produto —', None)],
                             layout=W.Layout(width='100%', max_width='500px'))
_w_prod_select_label = W.HTML('<span class="pq-lbl">Produto Encontrado</span>')


def _on_buscar_full(b):
    """Versao completa: busca e atualiza dropdown de selecao."""
    nome = _w_busca_nome.value.strip()
    if not nome:
        _w_busca_status.value = '<span class="pq-status-err">Digite o nome do produto.</span>'
        return
    _w_busca_status.value = '<span style="color:#6B6B6B;font-size:12px">&#x23F3; Buscando...</span>'
    _w_busca_result.value = ''
    _w_p2_next.disabled   = True
    _w_prod_select.options = [('— buscando... —', None)]

    def _run():
        m   = ModelDados(params={}, spark=spark)
        res = m.search_nome_produto(nome, _w_busca_email.value.strip())
        if not res:
            res = m.search_nome_produto_bi(nome)
            for r in res: r['_query'] = 'BI'
        else:
            for r in res: r['_query'] = 'GenQuery'
        _state['resultados'] = _fmt_serial(res)
        if res:
            opts = [('— Selecione um produto —', None)] + [
                (f"{(r.get('nome_produto') or '').replace('_',' ')} [{r.get('_query','')}]", i)
                for i, r in enumerate(res)
            ]
            _w_prod_select.options = opts
            _w_prod_select.value   = None
            _w_busca_status.value  = f'<span class="pq-status-ok">&#10003; {len(res)} resultado(s). Selecione abaixo.</span>'
        else:
            _w_prod_select.options = [('Nenhum produto encontrado', None)]
            _w_busca_status.value  = '<span class="pq-status-err">Nenhum resultado encontrado.</span>'

    _t.Thread(target=_run, daemon=True).start()

# Substitui callback do botao
_w_btn_buscar.on_click(_on_buscar_full)

def _on_prod_select(change):
    idx = change.get('new')
    if idx is None:
        _w_p2_next.disabled = True
        return
    p = _state['resultados'][idx]
    _state['produto'] = p
    nome_fmt = (p.get('nome_produto') or '').replace('_', ' ')
    _w_busca_result.value = f'<div class="pq-produto-badge">Selecionado: <strong>{nome_fmt}</strong></div>'
    _w_p2_next.disabled   = False

_w_prod_select.observe(_on_prod_select, names=['value'])

# Reconstruir passo 2 para incluir dropdown de selecao
_w_passo2.children = [
    W.HTML('<div class="pq-card"><div class="pq-card-hdr"><div class="pq-icon">2</div>Busca de Produto</div><div class="pq-card-body">'),
    W.HTML('<span class="pq-lbl">Nome do Produto</span>'),
    W.HBox([_w_busca_nome, _w_btn_buscar], layout=W.Layout(gap='8px', align_items='center')),
    W.HTML('<span class="pq-lbl">E-mail Responsavel (so GenQuery)</span>'),
    _w_busca_email,
    W.HTML('<br>'),
    _w_busca_status,
    _w_busca_result,
    W.HTML('<br>'),
    _w_prod_select_label,
    _w_prod_select,
    W.HTML('<br>'),
    W.HBox([_w_p2_back, _w_p2_next], layout=W.Layout(gap='8px')),
]

def _on_p2_next(b):
    p = _state.get('produto')
    if not p:
        return
    # Pre-preenche campos do passo 4
    _w_nome_display.value = (p.get('nome_produto') or '').replace('_', ' ')
    _w_tipo_produto.value = p.get('tipo_produto', '') or ''
    _show_step(3)

_w_p2_next.on_click(_on_p2_next)


def _on_atalho(change):
    d = change.get('new', '')
    if not d:
        return
    fim = datetime.now().date()
    ini = fim - timedelta(days=int(d))
    _w_dt_ini.value = ini
    _w_dt_fim.value = fim

_w_atalho.observe(_on_atalho, names=['value'])


def _on_coletar_metricas(b):
    p = _state.get('produto')
    if not p:
        _w_metricas_status.value = '<span class="pq-status-err">Selecione um produto primeiro (Passo 2).</span>'
        return
    nome   = p.get('nome_produto', '')
    tipo   = p.get('tipo_produto', '')
    dt_ini = _w_dt_ini.value.strftime('%Y-%m-%d') if _w_dt_ini.value else _state['dt_ini']
    dt_fim = _w_dt_fim.value.strftime('%Y-%m-%d') if _w_dt_fim.value else _state['dt_fim']
    _state['dt_ini'] = dt_ini
    _state['dt_fim'] = dt_fim
    _w_metricas_status.value = '<span class="pq-status-inf">&#x23F3; Coletando metricas...</span>'
    _w_metricas_cards.value  = ''
    _w_p3_next.disabled      = True

    def _run():
        m       = ModelDados(params={}, spark=spark)
        met     = _fmt_serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
        _state['metricas'] = met
        # Auto-marcar dimensoes com count > 0
        for d in _DIMS:
            auto = False if d == 'disponibilidade' else (met.get(d, {}).get('count', 0) > 0)
            _w_dim_checks[d].value = auto
            if d != 'disponibilidade':
                _w_val_inputs[d].value = float(met.get(d, {}).get('count', 0) or 0)
        _w_metricas_cards.value  = _h_mcards(met)
        _w_metricas_status.value = '<span class="pq-status-ok">&#10003; Metricas coletadas.</span>'
        _w_p3_next.disabled      = False

    _t.Thread(target=_run, daemon=True).start()

_w_btn_metricas.on_click(_on_coletar_metricas)


def _on_p3_next(b):
    _w_p4_produto.value = _h_produto_badge(_state.get('produto') or {})
    _show_step(4)

_w_p3_next.on_click(_on_p3_next)


def _on_dim_change(change):
    """Ao mudar qualquer checkbox de dimensao, reconstroi o passo 4."""
    if _state['step'] == 4:
        _w_passo4_container.children = [_build_p4()]

for d in _DIMS:
    _w_dim_checks[d].observe(_on_dim_change, names=['value'])


def _on_p4_next(b):
    _show_step(5)
    # Sumario no passo 5
    p         = _state.get('produto') or {}
    nome_disp = _w_nome_display.value.strip() or (p.get('nome_produto') or '').replace('_', ' ')
    ativas    = [_DLBL[d] for d in _DIMS if _w_dim_checks[d].value]
    _w_pdf_status.value = f"""
<div class="pq-card" style="margin-bottom:10px">
  <div class="pq-card-hdr"><div class="pq-icon">&#9432;</div>Resumo para Geracao</div>
  <div class="pq-card-body">
    <div class="pq-summary">
      <b>Autor:</b> {_state.get('autor','')} ({_state.get('ana_esp','')})<br>
      <b>Produto:</b> {nome_disp}<br>
      <b>Periodo:</b> {_state.get('dt_ini','')} a {_state.get('dt_fim','')}<br>
      <b>Dimensoes:</b> {', '.join(ativas) if ativas else '<em style="color:#ABABAB">nenhuma selecionada</em>'}<br>
      <b>Classificacao:</b> {_w_classificacao.value} &nbsp;&nbsp; <b>Demanda:</b> {_w_demanda.value}
    </div>
  </div>
</div>"""

_w_p4_next.on_click(_on_p4_next)


def _on_gerar_pdf(b):
    p          = _state.get('produto') or {}
    nome       = p.get('nome_produto', '')
    tipo       = p.get('tipo_produto', '')
    nome_disp  = _w_nome_display.value.strip() or nome.replace('_', ' ')
    autor_val  = _state.get('autor', '')
    ana_esp    = _state.get('ana_esp', 'Analista')
    dt_ini     = _state.get('dt_ini', '')
    dt_fim     = _state.get('dt_fim', '')

    # Leitura dos valores e textos dos widgets
    dimensoes = {d: _w_dim_checks[d].value for d in _DIMS}
    valores   = {d: int(_w_val_inputs[d].value) for d in _DIMS if d != 'disponibilidade'}
    valores['disponibilidade'] = _w_val_disp.value
    textos    = {d: _w_txt_areas[d].value for d in _DIMS}

    _w_btn_pdf.disabled    = True
    _w_pdf_output.value    = '<span class="pq-status-inf">&#x23F3; Gerando PDF, aguarde...</span>'

    def _run():
        m               = ModelDados(params={}, spark=spark)
        metricas_reais  = _fmt_serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
        for d, val in valores.items():
            if val and d in metricas_reais and d != 'disponibilidade':
                try:    metricas_reais[d]['count'] = int(val)
                except: pass

        payload = {
            'autor': autor_val, 'ana_esp': ana_esp,
            'nome_display': nome_disp, 'nome_produto': nome,
            'tipo_produto': tipo, 'dt_ini': dt_ini, 'dt_fim': dt_fim,
            'dimensoes': dimensoes, 'valores': valores, 'textos': textos,
            'classificacao': _w_classificacao.value,
            'demanda': _w_demanda.value,
        }
        ctx      = m.build_context_ns(payload, metricas_reais)
        rendered = render_template_dotted(template_text, ctx)
        pdf_b    = make_pdf_bytes(rendered_text=rendered,
                                  title='Parecer Tecnico de Qualidade de Dados',
                                  autor=f'Data Governance {ana_esp} - {autor_val}',
                                  assunto=f'Avaliacao da Qualidade - {nome_disp}')
        b64      = base64.b64encode(pdf_b).decode('utf-8')
        fname    = (f'Parecer_Qualidade_{sanitize_filename(nome_disp)}'
                    f'_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf')
        kb       = f'{len(pdf_b)/1024:.1f} KB'
        ts       = datetime.now().strftime('%d/%m/%Y %H:%M')

        _w_pdf_output.value = f"""
<div style="background:#fff;border:1px solid #E2E2E2;border-radius:10px;padding:24px;max-width:580px;font-family:'Segoe UI',sans-serif">
  <div style="background:linear-gradient(135deg,#8B0000,#CC0000);color:#fff;border-radius:8px;padding:16px 20px;margin-bottom:18px">
    <div style="font-size:15px;font-weight:700">Documento Gerado</div>
    <div style="font-size:11px;opacity:.85;margin-top:2px">{nome_disp}</div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px;font-size:13px">
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Tamanho</div>
      <div style="font-weight:600">{kb}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Produto</div>
      <div style="font-weight:600;font-size:11px">{nome_disp[:28]}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Gerado em</div>
      <div style="font-weight:600">{ts}</div>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="data:application/pdf;base64,{b64}" download="{fname}"
       style="background:#CC0000;color:#fff;padding:11px 26px;text-decoration:none;
              border-radius:6px;font-weight:700;font-size:14px">Baixar PDF</a>
  </div>
</div>"""
        _w_btn_pdf.disabled = False
        _w_pdf_status.value = ''

    _t.Thread(target=_run, daemon=True).start()

_w_btn_pdf.on_click(_on_gerar_pdf)


def _on_limpar(b):
    _state.update({
        'step': 1, 'resultados': [], 'produto': None,
        'metricas': {}, 'dimensoes': {}, 'valores': {}, 'textos': {},
        'autor': '', 'ana_esp': 'Analista', 'nome_display': '',
    })
    _w_autor.value        = ''
    _w_busca_nome.value   = ''
    _w_busca_email.value  = ''
    _w_busca_result.value = '<div class="pq-empty">Nenhuma busca realizada ainda.</div>'
    _w_busca_status.value = ''
    _w_prod_select.options = [('— Selecione um produto —', None)]
    _w_nome_display.value = ''
    _w_tipo_produto.value = ''
    _w_metricas_cards.value = ''
    _w_metricas_status.value = ''
    _w_pdf_output.value   = ''
    _w_pdf_status.value   = ''
    _w_p2_next.disabled   = True
    _w_p3_next.disabled   = True
    for d in _DIMS:
        _w_dim_checks[d].value = False
        if d != 'disponibilidade':
            _w_val_inputs[d].value = 0
        _w_txt_areas[d].value = SECTION_TEMPLATES_DEFAULT.get(d, '')
    _w_val_disp.value = ''
    _w_classificacao.value = 'satisfatorio'
    _w_demanda.value       = 'baixa'
    _show_step(1)

_w_btn_limpar.on_click(_on_limpar)


# ─── INICIALIZA NO PASSO 1 ────────────────────────────────────────────────────
_show_step(1)
_display(_main_ui)









===============================================



# ==============================================================================
# CELULA 1 - Imports e helpers de PDF
# ==============================================================================

import io
import re
import json
import base64
from datetime import datetime, date, timedelta
from typing import Any, Callable, Dict, List, Optional

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer

FONT_NAME = "Helvetica"

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="DocTitle", parent=styles["Heading1"], fontName=FONT_NAME,
    fontSize=16, leading=18, textColor=colors.HexColor("#1F2937"),
    alignment=TA_LEFT, spaceAfter=10,
))
styles.add(ParagraphStyle(
    name="Body", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=10.5, leading=14, textColor=colors.HexColor("#111827"),
))
styles.add(ParagraphStyle(
    name="Meta", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=9.5, leading=12, textColor=colors.HexColor("#374151"),
))
styles.add(ParagraphStyle(
    name="Separator", parent=styles["BodyText"], fontName=FONT_NAME,
    fontSize=8, leading=10, textColor=colors.HexColor("#6B7280"),
))

def sanitize_filename(name: str) -> str:
    clean = re.sub(r"[^\w\-]+", "_", (name or "").strip(), flags=re.UNICODE)
    return re.sub(r"_+", "_", clean).strip("_").lower() or "arquivo"

def pct(x, ndigits=1):
    if x is None: return ""
    try:
        val = x * 100 if 0 <= x <= 1 else x
        return f"{round(val, ndigits):.{ndigits}f}"
    except Exception:
        return str(x)

def fmt_int(x):
    try:
        return f"{int(x):,}".replace(",", ".")
    except Exception:
        return str(x)

def fmt_date(d, out="%d/%m/%Y"):
    if d in (None, ""): return ""
    if isinstance(d, datetime): return d.strftime(out)
    if isinstance(d, date): return d.strftime(out)
    try:
        return datetime.fromisoformat(str(d)).strftime(out)
    except Exception:
        return str(d)

_dotted_pat = re.compile(r"\$\{([a-zA-Z_]\w*(?:\.[a-zA-Z_]\w*)*)\}")

def render_template_dotted(text: str, ctx_ns: dict) -> str:
    def _resolve(path: str):
        parts = path.split(".")
        cur = ctx_ns
        for p in parts:
            if isinstance(cur, dict) and p in cur:
                cur = cur[p]
            else:
                return None
        return cur
    def _repl(m):
        val = _resolve(m.group(1))
        return str(val) if val is not None else m.group(0)
    return _dotted_pat.sub(_repl, text)

class PageCounter:
    def __init__(self): self.count = 0
    def __call__(self, canvas, doc):
        self.count += 1
        canvas.saveState()
        canvas.setFont(FONT_NAME, 8)
        canvas.setFillColor(colors.HexColor("#6B7280"))
        canvas.drawString(30, A4[1] - 30, "Parecer Tecnico de Qualidade de Dados")
        canvas.drawRightString(A4[0] - 30, 20, f"Pagina {self.count}")
        canvas.restoreState()

def make_pdf_bytes(rendered_text: str, title: str, autor: str, assunto: str = None) -> bytes:
    buf = io.BytesIO()
    doc = SimpleDocTemplate(
        buf, pagesize=A4,
        rightMargin=36, leftMargin=36, topMargin=54, bottomMargin=36,
    )
    story = []
    story.append(Paragraph(title, styles["DocTitle"]))
    if assunto:
        story.append(Paragraph(f"<b>Assunto:</b> {assunto}", styles["Meta"]))
    story.append(Paragraph(f"<b>Elaborado por:</b> {autor}", styles["Meta"]))
    story.append(Spacer(1, 6))
    story.append(Paragraph("________________________________________", styles["Separator"]))
    story.append(Spacer(1, 8))

    def _render_raw(raw: str):
        for blk in [b.strip() for b in re.split(r"\n\s*\n", raw) if b.strip()]:
            frags = []
            for ln in blk.splitlines():
                if ln.startswith("*"):
                    frags.append(f"&nbsp;&nbsp;&bull;&nbsp;{ln[1:].strip()}")
                else:
                    frags.append(ln)
            story.append(Paragraph("<br/>".join(frags), styles["Body"]))
            story.append(Spacer(1, 8))

    for block in [b.strip() for b in re.split(r"\n\s*\n", rendered_text) if b.strip()]:
        if block.startswith("__DISPONIBILIDADE_BLOCK__"):
            disp_text = block[len("__DISPONIBILIDADE_BLOCK__"):].strip()
            if disp_text:
                _render_raw(disp_text)
        else:
            _render_raw(block)

    header = PageCounter()
    doc.build(story, onFirstPage=header, onLaterPages=header)
    out = buf.getvalue()
    buf.close()
    return out


# ==============================================================================
# CELULA 2 - Template do documento (unico ponto de edicao de conteudo)
# ==============================================================================

template_text = """
Parecer Tecnico de Qualidade de Dados
Assunto: Avaliacao da Qualidade dos Dados - ${prod.nome_produto}
Elaborado por: Data Governance ${prod.ana_esp} - ${prod.autor}
Data: ${prod.data_relatorio}

________________________________________
1. Contexto da Avaliacao
A presente analise tem como objetivo avaliar a qualidade dos dados referentes a ${prod.nome_produto}, considerando os principais pilares de qualidade: disponibilidade, completude, variacao, consistencia, unicidade e integridade.

________________________________________
2. Metodologia
A avaliacao foi conduzida por meio de:
* Validacao das regras de negocio documentadas;
* Realizacao de analise de dados quantitativa (ex.: contagens, frequencia de nulos, cruzamentos);
* Comparacao com fontes referencia, quando aplicavel;
* Entrevistas e alinhamentos com responsaveis pelo processo e donos do dado.

________________________________________
3. Resultados da Analise - Dimensoes, regras sugeridas e regras solicitadas

${sections.completude}

${sections.consistencia}

${sections.disponibilidade}

${sections.unicidade}

${sections.variacao}

________________________________________
4. Impacto no Negocio
As falhas de qualidade observadas podem impactar:
* Confiabilidade dos indicadores utilizados na tomada de decisao;
* Performance de relatorios analiticos;
* Processos regulatorios ou obrigatorios (se aplicavel).

________________________________________
5. Recomendacoes
Sugere-se as seguintes acoes para mitigacao dos problemas identificados:
1. Correcao dos registros inconsistentes e incompletos, priorizando campos criticos.
2. Revisao das regras de negocio na origem, garantindo que sistemas capturem e validem dados adequadamente.
3. Implementacao de controles automatizados, como validacoes de formato e preenchimento obrigatorio.
4. Revisao dos fluxos de ingestao e atualizacao, para minimizar atrasos.
5. Ajustes no Glossario de Dados, quando aplicavel, garantindo clareza e padronizacao.

________________________________________
6. Conclusao
Com base na analise realizada, conclui-se que o conjunto de dados apresenta nivel de qualidade classificado como [ex.: moderado / insatisfatorio / satisfatorio], demandando [baixa/media/alta] priorizacao de acoes corretivas. Recomenda-se acompanhamento continuo e a implementacao das acoes propostas para garantir maior confiabilidade e qualidade dos dados.
O consumidor ou gerador do dado solicitou a inclusao/exclusao de novas regras de qualidade por sua total responsabilidade.
"""

# Textos padrao para cada dimensao. As variaveis entre chaves sao substituidas
# pelos valores reais no momento da geracao. O usuario pode editar estes textos
# pelo painel HTML, mas a alteracao e valida apenas para a execucao atual.
SECTION_TEMPLATES_DEFAULT: Dict[str, str] = {
    "completude": (
        "{numero}. Completude\n"
        "Identificou-se que {Valor_Completude} atributos obrigatorios preenchidos. "
        "Os 3 atributos com menor score sao: {Lowest3_Completude}.\n"
        "Score medio de completude: {Pct_Completude}%.\n"
        "Conclusao: Orientamos a aplicabilidade de completude nos atributos conforme template."
    ),
    "consistencia": (
        "{numero}. Consistencia\n"
        "Identificou-se que {Valor_Consistencia} atributos com possibilidade de aplicacao de regras de negocio.\n"
        "Conclusao: Orientamos a aplicabilidade de Consistencia nos atributos conforme template."
    ),
    "unicidade": (
        "{numero}. Unicidade\n"
        "Identificou-se que {Valor_Unicidade} registros duplicados.\n"
        "Conclusao: Orientamos a aplicabilidade de Unicidade nos atributos conforme template."
    ),
    "variacao": (
        "{numero}. Variacao\n"
        "Identificou-se que {Valor_Variacao} registros com variacao analisada.\n"
        "Conclusao: Orientamos a aplicabilidade de Variacao nos atributos conforme template."
    ),
    "disponibilidade": (
        "{numero}. Disponibilidade\n"
        "{Valor_Disponibilidade}"
    ),
}


# ==============================================================================
# CELULA 3 - Model de dados (queries primarias e fallback)
# ==============================================================================

class ModelDados:

    def __init__(self, params: Dict[str, Any], spark):
        self.spark = spark
        self.params = dict(params or {})

    def _raw_query(self, df) -> Dict[str, Any]:
        row = df.first()
        return row.asDict() if row else {}

    def _is_empty(self, raw: Dict[str, Any], count_key: str) -> bool:
        return not raw or raw.get(count_key, 0) == 0

    def _run_with_fallback(
        self,
        primary: Callable,
        fallback: Optional[Callable],
        count_key: str,
    ) -> tuple:
        """
        Executa a query primaria (GenQuery). Se retornar vazio e houver fallback,
        executa o fallback (BI). Retorna (resultado, nome_query_usada).
        """
        result = primary()
        if self._is_empty(result, count_key) and fallback is not None:
            return fallback(), "BI"
        return result, "GenQuery"

    # ----------------------------------------------------------
    # Busca de produto por LIKE (divide o termo em tokens)
    # ----------------------------------------------------------

    def search_nome_produto(self, raw_input: str, email: str = "") -> List[Dict]:
        tokens = [t for t in re.split(r"[\s_]+", raw_input.strip()) if t]
        if not tokens:
            return []

        like_conditions = " AND ".join(
            f"b.itpo_prodt_funcl LIKE '%{t}%'" for t in tokens
        )
        email_filter = f"AND u.email LIKE '%{email}%'" if email.strip() else ""

        # PLACEHOLDER GenQuery - ajuste colunas e tabelas conforme o schema real
        df = self.spark.sql(f"""
            SELECT DISTINCT
                b.itpo_prodt_funcl  AS nome_produto,
                b.itpo_prodt        AS tipo_produto,
                u.email             AS email_responsavel,
                b.datu_criacao      AS data_criacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
            LEFT JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_responsavel AS u
                ON b.nresponsavel = u.nresponsavel
            WHERE {like_conditions}
            {email_filter}
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    def search_nome_produto_bi(self, raw_input: str) -> List[Dict]:
        """Fallback BI - nao possui campo email."""
        tokens = [t for t in re.split(r"[\s_]+", raw_input.strip()) if t]
        if not tokens:
            return []

        like_conditions = " AND ".join(
            f"itpo_prodt_funcl LIKE '%{t}%'" for t in tokens
        )

        # PLACEHOLDER BI - ajuste conforme o schema real
        df = self.spark.sql(f"""
            SELECT DISTINCT
                itpo_prodt_funcl AS nome_produto,
                itpo_prodt       AS tipo_produto,
                NULL             AS email_responsavel,
                datu_criacao     AS data_criacao
            FROM pr_platfun.bi_layer.tdmsao_tpo_prodt_dados
            WHERE {like_conditions}
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    # ----------------------------------------------------------
    # Queries de metricas por dimensao
    # ----------------------------------------------------------

    def _query_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        # PLACEHOLDER GenQuery
        df = self.spark.sql(f"""
            WITH base AS (
                SELECT a.pscore_calcd, c.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                    ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS c
                    ON a.ncatlg_base_cpo_tbela = c.ncatlg_base_cpo_tbela
                WHERE
                    a.ndmsao_quald_dados = 2
                    AND b.itpo_prodt_funcl = '{nome}'
                    AND a.nsttus_mntrc = 1
                    AND b.itpo_prodt = '{tipo}'
                    AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
            )
            SELECT
                COUNT(*) AS count_pscore,
                COUNT_IF(pscore_calcd != 100) AS count_pscore_not_100,
                100 - ROUND(
                    100.0 * COUNT_IF(pscore_calcd != 100) / NULLIF(COUNT(pscore_calcd), 0), 2
                ) AS pct_pscore_calcd,
                ARRAY_JOIN(
                    TRANSFORM(
                        SLICE(
                            ARRAY_SORT(
                                COLLECT_LIST(
                                    CASE WHEN pscore_calcd > 0
                                    THEN NAMED_STRUCT('pscore_calcd', pscore_calcd, 'icpo_tbela', icpo_tbela)
                                    END
                                )
                            ), 1, 3
                        ),
                        a -> CAST(a.icpo_tbela AS STRING)
                    ), ', '
                ) AS lowest_3
            FROM base
        """)
        return self._raw_query(df)

    def _fallback_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        # PLACEHOLDER BI
        df = self.spark.sql(f"""
            SELECT
                COUNT(*) AS count_pscore,
                0        AS count_pscore_not_100,
                100.0    AS pct_pscore_calcd,
                ''       AS lowest_3
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 2
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 3
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 3
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 4
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 4
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _query_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 5
                AND b.itpo_prodt_funcl = '{nome}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict[str, Any]:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM pr_platfun.bi_layer.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.bi_layer.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 5
                AND b.itpo_prodt_funcl = '{nome}'
                AND b.itpo_prodt = '{tipo}'
                AND a.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    # ----------------------------------------------------------
    # Coleta todas as metricas para o produto selecionado
    # ----------------------------------------------------------

    def coletar_metricas(
        self,
        nome: str,
        tipo: str,
        dt_ini: str,
        dt_fim: str,
    ) -> Dict[str, Any]:
        """
        Retorna um dict com os valores de cada dimensao e a query usada.
        Estrutura: { "completude": {..., "query": "GenQuery"|"BI"}, ... }
        """
        resultado = {}

        raw_c, q = self._run_with_fallback(
            lambda: self._query_completude(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_completude(nome, tipo, dt_ini, dt_fim),
            "count_pscore",
        )
        resultado["completude"] = {
            "count":    raw_c.get("count_pscore", 0),
            "pct":      raw_c.get("pct_pscore_calcd"),
            "lowest_3": raw_c.get("lowest_3", ""),
            "query":    q,
        }

        raw_cs, q = self._run_with_fallback(
            lambda: self._query_consistencia(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_consistencia(nome, tipo, dt_ini, dt_fim),
            "count_consistencia",
        )
        resultado["consistencia"] = {
            "count": raw_cs.get("count_consistencia", 0),
            "query": q,
        }

        raw_u, q = self._run_with_fallback(
            lambda: self._query_unicidade(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_unicidade(nome, tipo, dt_ini, dt_fim),
            "count_unicidade",
        )
        resultado["unicidade"] = {
            "count": raw_u.get("count_unicidade", 0),
            "query": q,
        }

        raw_v, q = self._run_with_fallback(
            lambda: self._query_variacao(nome, tipo, dt_ini, dt_fim),
            lambda: self._fallback_variacao(nome, tipo, dt_ini, dt_fim),
            "count_variacao",
        )
        resultado["variacao"] = {
            "count": raw_v.get("count_variacao", 0),
            "query": q,
        }

        # Disponibilidade e preenchida manualmente pelo usuario no painel
        resultado["disponibilidade"] = {"count": 0, "query": "manual"}

        return resultado

    # ----------------------------------------------------------
    # Monta o context_ns a partir do payload do painel
    # ----------------------------------------------------------

    def build_context_ns(self, payload: Dict[str, Any], metricas: Dict[str, Any]) -> Dict[str, Any]:
        ana_esp = payload.get("ana_esp", "Analista").strip().title() or "Analista"
        nome_display = payload.get("nome_display", "").strip()
        if not nome_display:
            nome_display = payload.get("nome_produto", "").replace("_", " ").strip()

        prod_ns = {
            "nome_produto"   : nome_display,
            "tipo_produto"   : payload.get("tipo_produto", ""),
            "autor"          : payload.get("autor", ""),
            "ana_esp"        : ana_esp,
            "data_relatorio" : datetime.now().strftime("%d/%m/%Y"),
        }

        # Numeracao dinamica das secoes ativas
        dimensoes_ordem = ["completude", "consistencia", "disponibilidade", "unicidade", "variacao"]
        selecionadas = [d for d in dimensoes_ordem if payload.get("dimensoes", {}).get(d, False)]
        numeracao = {dim: f"3.{i+1}" for i, dim in enumerate(selecionadas)}

        templates_customizados = payload.get("textos", {})
        valores_customizados   = payload.get("valores", {})

        sections_ns = {}
        for dim in dimensoes_ordem:
            if dim not in selecionadas:
                sections_ns[dim] = ""
                continue

            numero = numeracao[dim]
            tpl    = templates_customizados.get(dim) or SECTION_TEMPLATES_DEFAULT.get(dim, "")
            vals   = metricas.get(dim, {})

            if dim == "disponibilidade":
                texto_disp = valores_customizados.get("disponibilidade", "")
                rendered   = tpl.format(
                    numero               = numero,
                    Valor_Disponibilidade = texto_disp,
                )
                sections_ns[dim] = (
                    "__DISPONIBILIDADE_BLOCK__\n" + rendered.strip()
                )
            else:
                count_val  = valores_customizados.get(dim) or vals.get("count", 0)
                fmt_count  = fmt_int(count_val)

                try:
                    rendered = tpl.format(
                        numero             = numero,
                        Valor_Completude   = fmt_count,
                        Valor_Consistencia = fmt_count,
                        Valor_Unicidade    = fmt_count,
                        Valor_Variacao     = fmt_count,
                        Lowest3_Completude = vals.get("lowest_3", "N/A"),
                        Pct_Completude     = pct(vals.get("pct")),
                    )
                except KeyError:
                    rendered = tpl

                sections_ns[dim] = rendered.strip()

        return {"prod": prod_ns, "sections": sections_ns}




# ==============================================================================

# ==============================================================================
# CELULA 4 - Painel interativo (ipywidgets nativos + HTML para display)
#
# Execute esta celula UMA VEZ. O fluxo e em 5 passos guiados:
#   Passo 1 → Identificacao do analista
#   Passo 2 → Busca e selecao do produto
#   Passo 3 → Periodo de analise + coleta de metricas
#   Passo 4 → Dimensoes, revisao de valores e textos
#   Passo 5 → Geracao do PDF
# ==============================================================================

import ipywidgets as W
import threading as _t
from IPython.display import display as _display, HTML as _HTML

# ─────────────────────────────────────────────
# PALETA E CSS COMPARTILHADO (injetado 1x via HTML widget)
# ─────────────────────────────────────────────
_STYLES = W.HTML(value="""<style>
/* ── Reset e base ── */
.pq-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
.pq-wrap {
  font-family: 'Segoe UI', Arial, sans-serif;
  font-size: 13px;
  color: #2C2C2C;
  max-width: 900px;
  background: #F2F2F2;
  padding: 16px;
  border-radius: 4px;
}

/* ── Header ── */
.pq-header {
  background: #8B0000;
  color: #fff;
  border-radius: 4px 4px 0 0;
  padding: 20px 28px 17px;
  border-bottom: 4px solid #CC0000;
  margin-bottom: 0;
}
.pq-header h2 {
  font-size: 14px; font-weight: 700;
  letter-spacing: .5px; text-transform: uppercase;
}
.pq-header p { font-size: 11px; color: #FFCCCC; margin-top: 4px; }

/* ── Stepper ── */
.pq-stepper {
  display: flex; align-items: center;
  background: #fff;
  border: 1px solid #DCDCDC; border-top: none;
  border-radius: 0 0 4px 4px;
  padding: 13px 20px; margin-bottom: 16px;
}
.pq-step { display:flex; align-items:center; gap:7px; font-size:11px; font-weight:600; color:#ABABAB; flex:1; white-space:nowrap; transition: color .3s; }
.pq-step.active { color:#CC0000; }
.pq-step.done   { color:#1A6B3A; }
.pq-step-num {
  width:22px; height:22px; border-radius:50%;
  border:2px solid #DCDCDC;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; flex-shrink:0;
  background:#fff; color:#ABABAB;
  transition: background .3s, border-color .3s, color .3s;
}
.pq-step.active .pq-step-num { border-color:#CC0000; color:#CC0000; background:#FFF5F5; }
.pq-step.done   .pq-step-num { border-color:#1A6B3A; background:#1A6B3A; color:#fff; }
.pq-step-line { flex:1; height:1px; background:#DCDCDC; margin:0 8px; min-width:12px; transition: background .4s; }
.pq-step-line.done { background:#1A6B3A; }

/* ── Section header bar ── */
.pq-card-hdr {
  background:#F7F7F7; border:1px solid #DCDCDC; border-radius:4px;
  padding:10px 16px; font-weight:700; font-size:11px;
  color:#1A1A1A; text-transform:uppercase; letter-spacing:.6px;
  display:flex; align-items:center; gap:10px; margin-bottom:14px;
}
.pq-icon {
  width:20px; height:20px; border-radius:2px;
  background:#8B0000; color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-size:10px; font-weight:700; flex-shrink:0;
}

/* ── Field label ── */
.pq-lbl {
  display:block; font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:.6px; color:#6B6B6B; margin-bottom:4px;
}

/* ── Native widget overrides ── */
.pq-input input, .pq-input select, .pq-input textarea,
.pq-dropdown select, .pq-textarea textarea, .pq-datepicker input {
  border: 1px solid #DCDCDC !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 13px !important;
  color: #2C2C2C !important;
  background: #fff !important;
  padding: 6px 9px !important;
  transition: border-color .2s !important;
}
.pq-input input:focus, .pq-dropdown select:focus,
.pq-textarea textarea:focus, .pq-datepicker input:focus {
  border-color: #8B0000 !important;
  outline: none !important;
}

/* Primary button */
.pq-btn-primary button {
  background: #8B0000 !important;
  color: #ffffff !important;
  border: 1px solid #700000 !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  letter-spacing: .2px !important;
  cursor: pointer !important;
  transition: background .2s, box-shadow .2s !important;
}
.pq-btn-primary button:hover {
  background: #700000 !important;
  box-shadow: 0 2px 6px rgba(139,0,0,.3) !important;
}
.pq-btn-primary button:active { box-shadow: none !important; }
.pq-btn-primary button:disabled {
  background: #C8A0A0 !important;
  border-color: #C8A0A0 !important;
  color: #fff !important;
  cursor: not-allowed !important;
  box-shadow: none !important;
}

/* Secondary button */
.pq-btn-secondary button {
  background: #fff !important;
  color: #3C3C3C !important;
  border: 1px solid #DCDCDC !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: background .2s, border-color .2s !important;
}
.pq-btn-secondary button:hover { background: #F5F5F5 !important; border-color: #BDBDBD !important; }

/* Danger button */
.pq-btn-danger button {
  background: #fff !important;
  color: #8B0000 !important;
  border: 1px solid #CC0000 !important;
  border-radius: 3px !important;
  font-family: 'Segoe UI', Arial, sans-serif !important;
  font-size: 12px !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: background .2s !important;
}
.pq-btn-danger button:hover { background: #FFF5F5 !important; }

/* Checkboxes */
.pq-checkbox label { font-size:13px !important; color:#2C2C2C !important; font-family:'Segoe UI',Arial,sans-serif !important; }
.pq-checkbox input[type=checkbox] { accent-color: #8B0000 !important; }

/* ── Infobox ── */
.pq-infobox {
  background:#F7F7F7; border-left:3px solid #DCDCDC;
  border-radius:0 3px 3px 0; padding:9px 13px;
  font-size:12px; color:#5C5C5C; margin-bottom:12px;
}

/* ── Produto badge — with left-border reveal ── */
.pq-produto-badge {
  background:#FFF5F5; border-left:4px solid #CC0000;
  border-radius:0 3px 3px 0; padding:10px 14px;
  font-size:13px; color:#8B0000; font-weight:600; margin-bottom:14px;
}

/* ── Metric cards — fade in on load ── */
.pq-mcards { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
.pq-mc {
  background:#FAFAFA; border:1px solid #DCDCDC;
  border-radius:4px; padding:14px 10px; text-align:center;
  transition: border-color .2s, box-shadow .2s;
}
.pq-mc:hover { border-color: #CC0000; box-shadow: 0 2px 6px rgba(139,0,0,.08); }
.pq-mc .dn { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#6B6B6B; margin-bottom:8px; }
.pq-mc .dv { font-size:24px; font-weight:700; color:#1A1A1A; line-height:1; margin-bottom:6px; }
.pq-mc .dv.zero { color:#BDBDBD; }

/* ── Table ── */
.pq-table { width:100%; border-collapse:collapse; font-size:12px; }
.pq-table th { background:#F7F7F7; padding:8px 12px; text-align:left; font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#6B6B6B; border-bottom:2px solid #DCDCDC; }
.pq-table td { padding:9px 12px; border-bottom:1px solid #EBEBEB; vertical-align:middle; color:#2C2C2C; transition: background .15s; }
.pq-table tbody tr:hover { background:#FEF5F5; }
.pq-table tbody tr.pq-sel { background:#FFF0F0; border-left:3px solid #CC0000; }

/* ── Tags ── */
.pq-tag { display:inline-block; padding:2px 7px; border-radius:2px; font-size:10px; font-weight:700; letter-spacing:.3px; }
.pq-gq  { background:#E8F0FE; color:#1A56DB; }
.pq-bi  { background:#FEF3C7; color:#92400E; }
.pq-manual { background:#F0F0F0; color:#5C5C5C; }

/* ── Status ── */
.pq-status-ok  { font-size:12px; color:#1A6B3A; font-weight:600; }
.pq-status-err { font-size:12px; color:#CC0000; font-weight:600; }
.pq-status-inf { font-size:12px; color:#5C5C5C; }

/* ── Chip ── */
.pq-chip {
  display:inline-block; background:#F0F0F0; border:1px solid #DCDCDC;
  border-radius:2px; padding:2px 7px; font-family:'Courier New',monospace;
  font-size:11px; color:#2C2C2C; margin:2px 2px;
  transition: background .15s, border-color .15s;
}
.pq-chip:hover { background:#FFF0F0; border-color:#CC0000; }

/* ── Empty ── */
.pq-empty { text-align:center; padding:22px; color:#ABABAB; font-size:12px; border:1px dashed #DCDCDC; border-radius:4px; }

/* ── Summary ── */
.pq-summary {
  background:#FAFAFA; border:1px solid #DCDCDC; border-radius:4px;
  padding:16px 18px; font-size:13px; line-height:2.1; margin-bottom:16px;
}
.pq-summary b { color:#1A1A1A; }

/* ── Step content fade-in ── */
@keyframes pq-fadein {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
.pq-passo-area > * { animation: pq-fadein .22s ease both; }
</style>""")

# ─────────────────────────────────────────────
# ESTADO DA SESSAO
# ─────────────────────────────────────────────
_state = {
    'step':        1,
    'resultados':  [],
    'produto':     None,
    'metricas':    {},
    'dimensoes':   {},
    'valores':     {},
    'textos':      {},
    'dt_ini':      (datetime.now() - timedelta(days=30)).strftime('%Y-%m-%d'),
    'dt_fim':      datetime.now().strftime('%Y-%m-%d'),
    'autor':       '',
    'ana_esp':     'Analista',
    'nome_display': '',
}

# ─────────────────────────────────────────────
# HELPERS DE HTML
# ─────────────────────────────────────────────
def _h_tag(cls, label, query=''):
    qmap = {'GenQuery': 'pq-gq', 'BI': 'pq-bi', 'manual': 'pq-manual'}
    qcls = qmap.get(query, 'pq-gq')
    tag  = f'<span class="pq-tag {qcls}">{query}</span>' if query else ''
    return f'<span class="pq-tag {qcls}">{label}</span>' if not query else tag

def _h_stepper(current):
    labels = ['Identificacao', 'Busca', 'Periodo & Metricas', 'Dimensoes & Textos', 'Gerar PDF']
    parts  = []
    for i, lbl in enumerate(labels, 1):
        cls = 'done' if i < current else ('active' if i == current else '')
        num = '&#10003;' if i < current else str(i)
        parts.append(f'<div class="pq-step {cls}"><div class="pq-step-num">{num}</div><span>{lbl}</span></div>')
        if i < len(labels):
            lcls = 'done' if i < current else ''
            parts.append(f'<div class="pq-step-line {lcls}"></div>')
    return f'<div class="pq-stepper">{"".join(parts)}</div>'

def _h_card(icon, title, body):
    return f"""<div class="pq-card">
  <div class="pq-card-hdr"><div class="pq-icon">{icon}</div>{title}</div>
  <div class="pq-card-body">{body}</div>
</div>"""

def _h_mcards(met):
    DIMS = ['completude','consistencia','disponibilidade','unicidade','variacao']
    DLBL = {'completude':'Completude','consistencia':'Consistencia',
            'disponibilidade':'Disponibilidade','unicidade':'Unicidade','variacao':'Variacao'}
    qmap = {'GenQuery':'pq-gq','BI':'pq-bi','manual':'pq-manual'}
    cards = []
    for d in DIMS:
        m   = met.get(d, {})
        v   = '—' if d == 'disponibilidade' else (m.get('count', 0))
        cls = 'dv zero' if v == 0 else 'dv'
        q   = m.get('query', '')
        qcls = qmap.get(q, 'pq-gq')
        qtag = '' if d == 'disponibilidade' else f'<span class="pq-tag {qcls}" style="margin-top:4px;display:inline-block">{q}</span>'
        cards.append(f'<div class="pq-mc"><div class="dn">{DLBL[d]}</div><div class="{cls}">{v}</div>{qtag}</div>')
    return f'<div class="pq-mcards">{"".join(cards)}</div>'

def _h_produto_badge(p):
    nome = (p.get('nome_produto') or '').replace('_', ' ')
    tipo = p.get('tipo_produto', '')
    return f'<div class="pq-produto-badge">Produto selecionado: <strong>{nome}</strong>' \
           + (f' &mdash; Tipo: {tipo}' if tipo else '') + '</div>'

def _h_search_table(rows):
    if not rows:
        return '<div class="pq-empty">Nenhum produto encontrado.</div>'
    qmap = {'GenQuery':'pq-gq','BI':'pq-bi'}
    hdr = '<table class="pq-table"><thead><tr><th>Nome</th><th>Tipo</th><th>E-mail</th><th>Query</th></tr></thead><tbody>'
    trs = []
    for i, r in enumerate(rows):
        q    = r.get('_query', 'GenQuery')
        qcls = qmap.get(q, 'pq-gq')
        nome = (r.get('nome_produto') or '').replace('_', ' ')
        email_val = r.get('email_responsavel') or '<span style="color:#BDBDBD">N/D</span>'
        tipo_val  = r.get('tipo_produto') or '-'
        trs.append(
            f'<tr id="pqr-{i}">'
            f'<td><strong>{nome}</strong></td>'
            f'<td>{tipo_val}</td>'
            f'<td>{email_val}</td>'
            f'<td><span class="pq-tag {qcls}">{q}</span></td></tr>'
        )
    return hdr + ''.join(trs) + '</tbody></table>'

def _fmt_serial(obj):
    if hasattr(obj, 'item'):  return obj.item()
    if isinstance(obj, dict): return {k: _fmt_serial(v) for k, v in obj.items()}
    if isinstance(obj, list): return [_fmt_serial(v) for v in obj]
    return obj


# ─────────────────────────────────────────────
# WIDGETS POR PASSO
# ─────────────────────────────────────────────

# ── HEADER (fixo) ──
_w_header = W.HTML(value="""<div class="pq-header">
  <h2>Parecer Tecnico de Qualidade de Dados</h2>
  <p>Data Governance &mdash; Bradesco</p>
</div>""")

# ── STEPPER (atualizado a cada passo) ──
_w_stepper = W.HTML(value=_h_stepper(1))

# ─── PASSO 1: Identificacao ────────────────────────────────────────────────────
_w_autor    = W.Text(placeholder='Nome do analista', layout=W.Layout(width='340px'))
_w_autor.add_class('pq-input')
_w_ana_esp  = W.Dropdown(options=['Analista', 'Especialista'], layout=W.Layout(width='200px'))
_w_ana_esp.add_class('pq-dropdown')
_w_p1_next  = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'))
_w_p1_next.style.button_color = '#8B0000'
_w_p1_next.add_class('pq-btn-primary')

_w_p1_status = W.HTML(value='')

_w_p1_label_autor   = W.HTML('<span class="pq-lbl">Autor do Documento</span>')
_w_p1_label_cargo   = W.HTML('<span class="pq-lbl">Cargo / Perfil</span>')

_w_passo1 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">1</div>Identificacao do Documento</div>'),
        W.HBox([
        W.VBox([_w_p1_label_autor, _w_autor], layout=W.Layout(gap='4px')),
        W.VBox([_w_p1_label_cargo, _w_ana_esp], layout=W.Layout(gap='4px')),
    ], layout=W.Layout(gap='16px', align_items='flex-start')),
    W.HTML('<br>'),
    W.HBox([_w_p1_next, _w_p1_status], layout=W.Layout(gap='12px', align_items='center')),
])

# ─── PASSO 2: Busca ────────────────────────────────────────────────────────────
_w_busca_nome  = W.Text(placeholder='Digite parte do nome do produto...', layout=W.Layout(width='380px'))
_w_busca_nome.add_class('pq-input')
_w_busca_email = W.Text(placeholder='Filtro opcional', layout=W.Layout(width='220px'))
_w_busca_email.add_class('pq-input')
_w_btn_buscar  = W.Button(description='Buscar', layout=W.Layout(width='100px'))
_w_btn_buscar.style.button_color = '#8B0000'
_w_btn_buscar.add_class('pq-btn-primary')

_w_busca_result = W.HTML(value='<div class="pq-empty">Nenhuma busca realizada ainda.</div>')
_w_busca_status = W.HTML(value='')

_w_p2_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p2_back.add_class('pq-btn-secondary')
_w_p2_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'), disabled=True)
_w_p2_next.style.button_color = '#8B0000'
_w_p2_next.add_class('pq-btn-primary')

_w_passo2 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">2</div>Busca de Produto</div>'),
        W.HTML('<span class="pq-lbl">Nome do Produto</span>'),
    W.HBox([_w_busca_nome, _w_btn_buscar], layout=W.Layout(gap='8px', align_items='center')),
    W.HTML('<span class="pq-lbl">E-mail Responsavel (so GenQuery)</span>'),
    _w_busca_email,
    W.HTML('<br>'),
    _w_busca_status,
    _w_busca_result,
    W.HTML('<br>'),
    W.HBox([_w_p2_back, _w_p2_next], layout=W.Layout(gap='8px')),
])

# ─── PASSO 3: Periodo + Metricas ───────────────────────────────────────────────
_w_dt_ini    = W.DatePicker(value=datetime.now().date() - timedelta(days=30), layout=W.Layout(width='180px'))
_w_dt_ini.add_class('pq-datepicker')
_w_dt_fim    = W.DatePicker(value=datetime.now().date(), layout=W.Layout(width='180px'))
_w_dt_fim.add_class('pq-datepicker')
_w_atalho    = W.Dropdown(
    options=[('Selecione...',''), ('7 dias','7'), ('30 dias','30'),
             ('90 dias','90'), ('180 dias','180'), ('365 dias','365')],
    layout=W.Layout(width='150px')
)
_w_atalho.add_class('pq-dropdown')
_w_btn_metricas  = W.Button(description='Coletar Metricas', layout=W.Layout(width='160px'))
_w_btn_metricas.style.button_color = '#8B0000'
_w_btn_metricas.add_class('pq-btn-primary')

_w_metricas_status = W.HTML(value='')
_w_metricas_cards  = W.HTML(value='')

_w_p3_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p3_back.add_class('pq-btn-secondary')
_w_p3_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'), disabled=True)
_w_p3_next.style.button_color = '#8B0000'
_w_p3_next.add_class('pq-btn-primary')

_w_passo3 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">3</div>Periodo de Analise e Metricas</div>'),
        W.HTML('<span class="pq-lbl">Periodo</span>'),
    W.HBox([_w_dt_ini, _w_dt_fim, _w_atalho, _w_btn_metricas], layout=W.Layout(gap='12px', align_items='center')),
    W.HTML('<br>'),
    _w_metricas_status,
    _w_metricas_cards,
    W.HTML('<br>'),
    W.HBox([_w_p3_back, _w_p3_next], layout=W.Layout(gap='8px')),
])

# ─── PASSO 4: Dimensoes + Valores + Textos ─────────────────────────────────────
_DIMS = ['completude', 'consistencia', 'disponibilidade', 'unicidade', 'variacao']
_DLBL = {'completude': 'Completude', 'consistencia': 'Consistencia',
         'disponibilidade': 'Disponibilidade', 'unicidade': 'Unicidade', 'variacao': 'Variacao'}

# Checkboxes de dimensao
_w_dim_checks = {d: W.Checkbox(value=False, description=_DLBL[d], indent=False,
                                layout=W.Layout(width='180px')) for d in _DIMS}
for _d in _DIMS: _w_dim_checks[_d].add_class('pq-checkbox')
_w_dim_status = W.HTML(value='')

# Inputs de valor por dimensao (numerico, exceto disponibilidade = textarea)
_w_val_inputs = {d: W.BoundedFloatText(value=0, min=0, max=9999999, step=1,
                                        description='', layout=W.Layout(width='160px'))
                 for d in _DIMS if d != 'disponibilidade'}
for _d in _w_val_inputs: _w_val_inputs[_d].add_class('pq-input')
_w_val_disp   = W.Textarea(placeholder='Descreva a disponibilidade do produto...',
                            layout=W.Layout(width='100%', height='80px'))
_w_val_disp.add_class('pq-textarea')

# Textareas de template por dimensao
_w_txt_areas  = {d: W.Textarea(layout=W.Layout(width='100%', height='100px'),
                                value=SECTION_TEMPLATES_DEFAULT.get(d, ''))
                 for d in _DIMS}
for _d in _DIMS: _w_txt_areas[_d].add_class('pq-textarea')
_w_txt_tabs   = W.Tab()   # tabs de textos

_w_p4_back = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p4_back.add_class('pq-btn-secondary')
_w_p4_next = W.Button(description='Continuar →', button_style='', layout=W.Layout(width='140px'))
_w_p4_next.style.button_color = '#8B0000'
_w_p4_next.add_class('pq-btn-primary')

# Display do produto selecionado no topo do passo 4
_w_p4_produto  = W.HTML(value='')
_w_nome_display = W.Text(placeholder='Preenchido automaticamente', layout=W.Layout(width='340px'))
_w_nome_display.add_class('pq-input')
_w_tipo_produto = W.Text(placeholder='Preenchido automaticamente', layout=W.Layout(width='220px'))
_w_tipo_produto.add_class('pq-input')

_w_p4_label_nome = W.HTML('<span class="pq-lbl">Nome para Exibicao no PDF</span>')
_w_p4_label_tipo = W.HTML('<span class="pq-lbl">Tipo do Produto</span>')

def _build_p4():
    """Reconstroi o conteudo do passo 4 com base no estado atual."""
    ativas = [d for d in _DIMS if _w_dim_checks[d].value]

    # Secao valores
    val_widgets = []
    for d in ativas:
        lbl = W.HTML(f'<span class="pq-lbl">{_DLBL[d]}</span>')
        if d == 'disponibilidade':
            val_widgets.append(W.VBox([lbl, _w_val_disp], layout=W.Layout(gap='4px', width='100%')))
        else:
            val_widgets.append(W.VBox([lbl, _w_val_inputs[d]], layout=W.Layout(gap='4px')))

    val_section = W.VBox([
        W.HTML('<div class="pq-card-hdr" style="margin:12px 0 8px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">V</div>Revisao de Valores</div>'),
        W.HTML('<div class="pq-infobox">Valores pre-preenchidos pela query. Edite se necessario.</div>'),
        W.HBox(val_widgets, layout=W.Layout(flex_wrap='wrap', gap='16px')) if val_widgets
          else W.HTML('<div class="pq-empty">Selecione ao menos uma dimensao.</div>'),
        ]) if ativas else W.HTML('')

    # Secao textos (abas)
    if ativas:
        tab_children = []
        for d in ativas:
            CHIP_VARS = {
                'completude':      ['{numero}', '{Valor_Completude}', '{Pct_Completude}', '{Lowest3_Completude}'],
                'consistencia':    ['{numero}', '{Valor_Consistencia}'],
                'unicidade':       ['{numero}', '{Valor_Unicidade}'],
                'variacao':        ['{numero}', '{Valor_Variacao}'],
                'disponibilidade': ['{numero}', '{Valor_Disponibilidade}'],
            }
            chips_html = ' '.join(f'<span class="pq-chip">{v}</span>' for v in CHIP_VARS.get(d, []))
            tab_children.append(W.VBox([
                W.HTML(f'<div class="pq-infobox" style="margin-bottom:8px">Variaveis: {chips_html}</div>'),
                _w_txt_areas[d],
            ]))
        _w_txt_tabs.children = tab_children
        for i, d in enumerate(ativas):
            _w_txt_tabs.set_title(i, _DLBL[d])
        txt_section = W.VBox([
            _w_txt_tabs,
                ])
    else:
        txt_section = W.HTML('')

    return W.VBox([
        _w_p4_produto,
        W.HTML('<span class="pq-lbl">Nome para Exibicao no PDF &nbsp;&nbsp; Tipo do Produto</span>'),
        W.HBox([
            W.VBox([_w_p4_label_nome, _w_nome_display], layout=W.Layout(gap='4px')),
            W.VBox([_w_p4_label_tipo, _w_tipo_produto], layout=W.Layout(gap='4px')),
        ], layout=W.Layout(gap='16px', align_items='flex-start')),
        W.HTML('<br><span class="pq-lbl">Dimensoes a incluir no parecer</span>'),
        W.HTML('<div class="pq-infobox">Marcadas automaticamente quando count &gt; 0.</div>'),
        W.HBox(list(_w_dim_checks.values()), layout=W.Layout(flex_wrap='wrap', gap='8px')),
        _w_dim_status,
        W.HTML('<br>'),
        val_section,
        W.HTML('<br>'),
        txt_section,
        W.HTML('<br>'),
        W.HBox([_w_p4_back, _w_p4_next], layout=W.Layout(gap='8px')),
        ])

_w_passo4_container = W.VBox([])  # preenchido dinamicamente

# ─── PASSO 5: Gerar PDF ────────────────────────────────────────────────────────
_w_pdf_status  = W.HTML(value='')
_w_pdf_output  = W.HTML(value='')
_w_p5_back     = W.Button(description='← Voltar', layout=W.Layout(width='100px'))
_w_p5_back.add_class('pq-btn-secondary')
_w_btn_pdf     = W.Button(description='Gerar PDF', layout=W.Layout(width='140px'))
_w_btn_pdf.style.button_color = '#8B0000'
_w_btn_pdf.add_class('pq-btn-primary')
_w_btn_limpar  = W.Button(description='Limpar tudo', layout=W.Layout(width='120px'))
_w_btn_limpar.add_class('pq-btn-danger')

_w_passo5 = W.VBox([
    W.HTML('<div class="pq-card-hdr" style="margin-bottom:12px;border-radius:4px;border:1px solid #DCDCDC"><div class="pq-icon">5</div>Gerar Documento</div>'),
    W.HTML('<div class="pq-infobox">Revise os dados nos passos anteriores. Ao clicar em Gerar PDF, as metricas serao re-coletadas e o documento sera gerado.</div>'),
    W.HTML('<br>'),
    W.HBox([_w_p5_back, _w_btn_pdf, _w_btn_limpar], layout=W.Layout(gap='10px', align_items='center')),
    W.HTML('<br>'),
    _w_pdf_status,
    _w_pdf_output,
])

# ─────────────────────────────────────────────
# CONTAINER PRINCIPAL
# ─────────────────────────────────────────────
_w_passo_area = W.VBox([])   # troca de conteudo a cada passo
_w_passo_area.add_class('pq-passo-area')

_main_ui = W.VBox([
    _STYLES,
    _w_header,
    _w_stepper,
    _w_passo_area,
], layout=W.Layout(max_width='920px'))


def _show_step(n):
    _state['step'] = n
    _w_stepper.value = _h_stepper(n)
    if   n == 1: _w_passo_area.children = [_w_passo1]
    elif n == 2: _w_passo_area.children = [_w_passo2]
    elif n == 3: _w_passo_area.children = [_w_passo3]
    elif n == 4:
        _w_passo4_container.children = [_build_p4()]
        _w_passo_area.children = [_w_passo4_container]
    elif n == 5: _w_passo_area.children = [_w_passo5]


# ─────────────────────────────────────────────
# CALLBACKS
# ─────────────────────────────────────────────

def _on_p1_next(b):
    nome = _w_autor.value.strip()
    if not nome:
        _w_p1_status.value = '<span class="pq-status-err">Preencha o nome do autor.</span>'
        return
    _state['autor']   = nome
    _state['ana_esp'] = _w_ana_esp.value
    _w_p1_status.value = ''
    _show_step(2)

_w_p1_next.on_click(_on_p1_next)
_w_p2_back.on_click(lambda b: _show_step(1))
_w_p3_back.on_click(lambda b: _show_step(2))
_w_p4_back.on_click(lambda b: _show_step(3))
_w_p5_back.on_click(lambda b: _show_step(4))


def _on_buscar(b):
    nome = _w_busca_nome.value.strip()
    if not nome:
        _w_busca_status.value = '<span class="pq-status-err">Digite o nome do produto.</span>'
        return
    _w_busca_status.value  = '<span class="pq-status-inf">&#x23F3; Buscando...</span>'
    _w_busca_result.value  = ''
    _w_p2_next.disabled    = True
    def _run():
        m   = ModelDados(params={}, spark=spark)
        res = m.search_nome_produto(nome, _w_busca_email.value.strip())
        if not res:
            res = m.search_nome_produto_bi(nome)
            for r in res: r['_query'] = 'BI'
        else:
            for r in res: r['_query'] = 'GenQuery'
        _state['resultados'] = _fmt_serial(res)
        _w_busca_status.value = f'<span class="pq-status-ok">&#10003; {len(res)} resultado(s) encontrado(s). Selecione abaixo.</span>' if res else ''
        _w_busca_result.value = _h_search_table(res)
    _t.Thread(target=_run, daemon=True).start()

_w_btn_buscar.on_click(_on_buscar)


def _select_produto(idx):
    """Chamado pelo JS via onclick — nao disponivel no ipywidgets puro.
    A selecao e feita via Dropdown gerado apos busca."""
    pass  # ver abaixo — usamos Dropdown de selecao

# Dropdown de selecao do produto (aparece apos busca)
_w_prod_select = W.Dropdown(options=[('— Selecione um produto —', None)],
                             layout=W.Layout(width='100%', max_width='500px'))
_w_prod_select_label = W.HTML('<span class="pq-lbl">Produto Encontrado</span>')


def _on_buscar_full(b):
    """Versao completa: busca e atualiza dropdown de selecao."""
    nome = _w_busca_nome.value.strip()
    if not nome:
        _w_busca_status.value = '<span class="pq-status-err">Digite o nome do produto.</span>'
        return
    _w_busca_status.value = '<span style="color:#6B6B6B;font-size:12px">&#x23F3; Buscando...</span>'
    _w_busca_result.value = ''
    _w_p2_next.disabled   = True
    _w_prod_select.options = [('— buscando... —', None)]

    def _run():
        m   = ModelDados(params={}, spark=spark)
        res = m.search_nome_produto(nome, _w_busca_email.value.strip())
        if not res:
            res = m.search_nome_produto_bi(nome)
            for r in res: r['_query'] = 'BI'
        else:
            for r in res: r['_query'] = 'GenQuery'
        _state['resultados'] = _fmt_serial(res)
        if res:
            opts = [('— Selecione um produto —', None)] + [
                (f"{(r.get('nome_produto') or '').replace('_',' ')} [{r.get('_query','')}]", i)
                for i, r in enumerate(res)
            ]
            _w_prod_select.options = opts
            _w_prod_select.value   = None
            _w_busca_status.value  = f'<span class="pq-status-ok">&#10003; {len(res)} resultado(s). Selecione abaixo.</span>'
        else:
            _w_prod_select.options = [('Nenhum produto encontrado', None)]
            _w_busca_status.value  = '<span class="pq-status-err">Nenhum resultado encontrado.</span>'

    _t.Thread(target=_run, daemon=True).start()

# Substitui callback do botao
_w_btn_buscar.on_click(_on_buscar_full)

def _on_prod_select(change):
    idx = change.get('new')
    if idx is None:
        _w_p2_next.disabled = True
        return
    p = _state['resultados'][idx]
    _state['produto'] = p
    nome_fmt = (p.get('nome_produto') or '').replace('_', ' ')
    _w_busca_result.value = f'<div class="pq-produto-badge">Selecionado: <strong>{nome_fmt}</strong></div>'
    _w_p2_next.disabled   = False

_w_prod_select.observe(_on_prod_select, names=['value'])

# Reconstruir passo 2 para incluir dropdown de selecao
_w_passo2.children = [
    W.HTML('<div class="pq-card"><div class="pq-card-hdr"><div class="pq-icon">2</div>Busca de Produto</div><div class="pq-card-body">'),
    W.HTML('<span class="pq-lbl">Nome do Produto</span>'),
    W.HBox([_w_busca_nome, _w_btn_buscar], layout=W.Layout(gap='8px', align_items='center')),
    W.HTML('<span class="pq-lbl">E-mail Responsavel (so GenQuery)</span>'),
    _w_busca_email,
    W.HTML('<br>'),
    _w_busca_status,
    _w_busca_result,
    W.HTML('<br>'),
    _w_prod_select_label,
    _w_prod_select,
    W.HTML('<br>'),
    W.HBox([_w_p2_back, _w_p2_next], layout=W.Layout(gap='8px')),
]

def _on_p2_next(b):
    p = _state.get('produto')
    if not p:
        return
    # Pre-preenche campos do passo 4
    _w_nome_display.value = (p.get('nome_produto') or '').replace('_', ' ')
    _w_tipo_produto.value = p.get('tipo_produto', '') or ''
    _show_step(3)

_w_p2_next.on_click(_on_p2_next)


def _on_atalho(change):
    d = change.get('new', '')
    if not d:
        return
    fim = datetime.now().date()
    ini = fim - timedelta(days=int(d))
    _w_dt_ini.value = ini
    _w_dt_fim.value = fim

_w_atalho.observe(_on_atalho, names=['value'])


def _on_coletar_metricas(b):
    p = _state.get('produto')
    if not p:
        _w_metricas_status.value = '<span class="pq-status-err">Selecione um produto primeiro (Passo 2).</span>'
        return
    nome   = p.get('nome_produto', '')
    tipo   = p.get('tipo_produto', '')
    dt_ini = _w_dt_ini.value.strftime('%Y-%m-%d') if _w_dt_ini.value else _state['dt_ini']
    dt_fim = _w_dt_fim.value.strftime('%Y-%m-%d') if _w_dt_fim.value else _state['dt_fim']
    _state['dt_ini'] = dt_ini
    _state['dt_fim'] = dt_fim
    _w_metricas_status.value = '<span class="pq-status-inf">&#x23F3; Coletando metricas...</span>'
    _w_metricas_cards.value  = ''
    _w_p3_next.disabled      = True

    def _run():
        m       = ModelDados(params={}, spark=spark)
        met     = _fmt_serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
        _state['metricas'] = met
        # Auto-marcar dimensoes com count > 0
        for d in _DIMS:
            auto = False if d == 'disponibilidade' else (met.get(d, {}).get('count', 0) > 0)
            _w_dim_checks[d].value = auto
            if d != 'disponibilidade':
                _w_val_inputs[d].value = float(met.get(d, {}).get('count', 0) or 0)
        _w_metricas_cards.value  = _h_mcards(met)
        _w_metricas_status.value = '<span class="pq-status-ok">&#10003; Metricas coletadas.</span>'
        _w_p3_next.disabled      = False

    _t.Thread(target=_run, daemon=True).start()

_w_btn_metricas.on_click(_on_coletar_metricas)


def _on_p3_next(b):
    _w_p4_produto.value = _h_produto_badge(_state.get('produto') or {})
    _show_step(4)

_w_p3_next.on_click(_on_p3_next)


def _on_dim_change(change):
    """Ao mudar qualquer checkbox de dimensao, reconstroi o passo 4."""
    if _state['step'] == 4:
        _w_passo4_container.children = [_build_p4()]

for d in _DIMS:
    _w_dim_checks[d].observe(_on_dim_change, names=['value'])


def _on_p4_next(b):
    _show_step(5)
    # Sumario no passo 5
    p         = _state.get('produto') or {}
    nome_disp = _w_nome_display.value.strip() or (p.get('nome_produto') or '').replace('_', ' ')
    ativas    = [_DLBL[d] for d in _DIMS if _w_dim_checks[d].value]
    _w_pdf_status.value = f"""
<div class="pq-card" style="margin-bottom:10px">
  <div class="pq-card-hdr"><div class="pq-icon">&#9432;</div>Resumo para Geracao</div>
  <div class="pq-card-body">
    <div class="pq-summary">
      <b>Autor:</b> {_state.get('autor','')} ({_state.get('ana_esp','')})<br>
      <b>Produto:</b> {nome_disp}<br>
      <b>Periodo:</b> {_state.get('dt_ini','')} a {_state.get('dt_fim','')}<br>
      <b>Dimensoes:</b> {', '.join(ativas) if ativas else '<em style="color:#ABABAB">nenhuma selecionada</em>'}
    </div>
  </div>
</div>"""

_w_p4_next.on_click(_on_p4_next)


def _on_gerar_pdf(b):
    p          = _state.get('produto') or {}
    nome       = p.get('nome_produto', '')
    tipo       = p.get('tipo_produto', '')
    nome_disp  = _w_nome_display.value.strip() or nome.replace('_', ' ')
    autor_val  = _state.get('autor', '')
    ana_esp    = _state.get('ana_esp', 'Analista')
    dt_ini     = _state.get('dt_ini', '')
    dt_fim     = _state.get('dt_fim', '')

    # Leitura dos valores e textos dos widgets
    dimensoes = {d: _w_dim_checks[d].value for d in _DIMS}
    valores   = {d: int(_w_val_inputs[d].value) for d in _DIMS if d != 'disponibilidade'}
    valores['disponibilidade'] = _w_val_disp.value
    textos    = {d: _w_txt_areas[d].value for d in _DIMS}

    _w_btn_pdf.disabled    = True
    _w_pdf_output.value    = '<span class="pq-status-inf">&#x23F3; Gerando PDF, aguarde...</span>'

    def _run():
        m               = ModelDados(params={}, spark=spark)
        metricas_reais  = _fmt_serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
        for d, val in valores.items():
            if val and d in metricas_reais and d != 'disponibilidade':
                try:    metricas_reais[d]['count'] = int(val)
                except: pass

        payload = {
            'autor': autor_val, 'ana_esp': ana_esp,
            'nome_display': nome_disp, 'nome_produto': nome,
            'tipo_produto': tipo, 'dt_ini': dt_ini, 'dt_fim': dt_fim,
            'dimensoes': dimensoes, 'valores': valores, 'textos': textos,
        }
        ctx      = m.build_context_ns(payload, metricas_reais)
        rendered = render_template_dotted(template_text, ctx)
        pdf_b    = make_pdf_bytes(rendered_text=rendered,
                                  title='Parecer Tecnico de Qualidade de Dados',
                                  autor=f'Data Governance {ana_esp} - {autor_val}',
                                  assunto=f'Avaliacao da Qualidade - {nome_disp}')
        b64      = base64.b64encode(pdf_b).decode('utf-8')
        fname    = (f'Parecer_Qualidade_{sanitize_filename(nome_disp)}'
                    f'_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf')
        kb       = f'{len(pdf_b)/1024:.1f} KB'
        ts       = datetime.now().strftime('%d/%m/%Y %H:%M')

        _w_pdf_output.value = f"""
<div style="background:#fff;border:1px solid #E2E2E2;border-radius:10px;padding:24px;max-width:580px;font-family:'Segoe UI',sans-serif">
  <div style="background:linear-gradient(135deg,#8B0000,#CC0000);color:#fff;border-radius:8px;padding:16px 20px;margin-bottom:18px">
    <div style="font-size:15px;font-weight:700">Documento Gerado</div>
    <div style="font-size:11px;opacity:.85;margin-top:2px">{nome_disp}</div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px;font-size:13px">
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Tamanho</div>
      <div style="font-weight:600">{kb}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Produto</div>
      <div style="font-weight:600;font-size:11px">{nome_disp[:28]}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Gerado em</div>
      <div style="font-weight:600">{ts}</div>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="data:application/pdf;base64,{b64}" download="{fname}"
       style="background:#CC0000;color:#fff;padding:11px 26px;text-decoration:none;
              border-radius:6px;font-weight:700;font-size:14px">Baixar PDF</a>
  </div>
</div>"""
        _w_btn_pdf.disabled = False
        _w_pdf_status.value = ''

    _t.Thread(target=_run, daemon=True).start()

_w_btn_pdf.on_click(_on_gerar_pdf)


def _on_limpar(b):
    _state.update({
        'step': 1, 'resultados': [], 'produto': None,
        'metricas': {}, 'dimensoes': {}, 'valores': {}, 'textos': {},
        'autor': '', 'ana_esp': 'Analista', 'nome_display': '',
    })
    _w_autor.value        = ''
    _w_busca_nome.value   = ''
    _w_busca_email.value  = ''
    _w_busca_result.value = '<div class="pq-empty">Nenhuma busca realizada ainda.</div>'
    _w_busca_status.value = ''
    _w_prod_select.options = [('— Selecione um produto —', None)]
    _w_nome_display.value = ''
    _w_tipo_produto.value = ''
    _w_metricas_cards.value = ''
    _w_metricas_status.value = ''
    _w_pdf_output.value   = ''
    _w_pdf_status.value   = ''
    _w_p2_next.disabled   = True
    _w_p3_next.disabled   = True
    for d in _DIMS:
        _w_dim_checks[d].value = False
        if d != 'disponibilidade':
            _w_val_inputs[d].value = 0
        _w_txt_areas[d].value = SECTION_TEMPLATES_DEFAULT.get(d, '')
    _w_val_disp.value = ''
    _show_step(1)

_w_btn_limpar.on_click(_on_limpar)


# ─── INICIALIZA NO PASSO 1 ────────────────────────────────────────────────────
_show_step(1)
_display(_main_ui)
