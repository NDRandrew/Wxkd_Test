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
# CELULA 4 - Painel de controle HTML
#
# FLUXO DE USO:
#   1. Execute esta celula para renderizar o painel.
#   2. Preencha os campos de busca e clique em "Buscar Produto".
#      que aparecera no campo de instrucoes do painel.
#      para executar a Celula 4.5.
#   3. Execute a Celula 4.5 — ela roda as queries Python reais e re-renderiza
#      o painel ja populado com resultados e metricas.
#   4. Selecione o produto desejado, ajuste dimensoes/valores/textos e clique
#      em "Gerar PDF". Copie o JSON gerado, cole no widget json_payload
#      instrucao para executar a Celula 5.
#   5. Execute a Celula 5 para gerar e baixar o PDF.
# ==============================================================================

dbutils.widgets.removeAll()

# Parametros de busca — preenchidos pelo usuario nos widgets nativos do Databricks.
# Apos preencher, execute a Celula 4.5 para buscar produtos.
dbutils.widgets.text("busca_nome",  "", "Nome do Produto (busca parcial)")
dbutils.widgets.text("busca_email", "", "E-mail do Responsavel (opcional, so GenQuery)")
dbutils.widgets.text("dt_ini",      (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"), "Data Inicio")
dbutils.widgets.text("dt_fim",      datetime.now().strftime("%Y-%m-%d"),                        "Data Fim")

# Produto selecionado — preenchido manualmente apos ver os resultados na Celula 4.5.
# Use exatamente o valor da coluna nome_produto da tabela de resultados.
dbutils.widgets.text("nome_produto_selecionado", "", "Produto Selecionado (nome exato)")
dbutils.widgets.text("tipo_produto_selecionado", "", "Tipo do Produto Selecionado")
dbutils.widgets.dropdown("query_origem_selecionada", "GenQuery", ["GenQuery", "BI"], "Query de Origem")

_dt_fim_padrao = dbutils.widgets.get("dt_fim")
_dt_ini_padrao = dbutils.widgets.get("dt_ini")

_templates_json = json.dumps(SECTION_TEMPLATES_DEFAULT, ensure_ascii=False)

# Estado injetado pela Celula 4.5 apos execucao das queries.
# Nao editar manualmente — e sobrescrito toda vez que a Celula 4.5 roda.
_injected_state_json = "{}"


def _renderizar_painel(_injected_state_json="{}"):
    displayHTML(f"""
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Parecer Tecnico de Qualidade de Dados</title>
    <style>
      *, *::before, *::after {{ box-sizing: border-box; margin: 0; padding: 0; }}
    
      :root {{
        --bradesco-red:    #CC0000;
        --bradesco-dark:   #8B0000;
        --bradesco-light:  #FF3333;
        --neutral-900:     #1A1A1A;
        --neutral-700:     #3D3D3D;
        --neutral-500:     #6B6B6B;
        --neutral-300:     #C4C4C4;
        --neutral-100:     #F5F5F5;
        --neutral-50:      #FAFAFA;
        --white:           #FFFFFF;
        --success:         #1A7A4A;
        --success-light:   #E6F4ED;
        --warning:         #B45309;
        --warning-light:   #FEF3C7;
        --border:          #E2E2E2;
        --shadow-sm:       0 1px 3px rgba(0,0,0,0.08);
        --shadow-md:       0 4px 12px rgba(0,0,0,0.10);
        --shadow-lg:       0 8px 24px rgba(0,0,0,0.13);
        --radius:          6px;
        --radius-lg:       10px;
        --font:            'Segoe UI', system-ui, -apple-system, sans-serif;
      }}
    
      body {{
        font-family: var(--font);
        background: var(--neutral-100);
        color: var(--neutral-900);
        font-size: 14px;
        line-height: 1.5;
        min-height: 100vh;
      }}
    
      /* ---- Cabecalho ---- */
      .header {{
        background: linear-gradient(135deg, var(--bradesco-dark) 0%, var(--bradesco-red) 100%);
        color: var(--white);
        padding: 20px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow-md);
      }}
      .header-brand {{ display: flex; align-items: center; gap: 14px; }}
      .header-logo {{
        width: 42px; height: 42px;
        background: var(--white);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; font-size: 18px; color: var(--bradesco-red);
        letter-spacing: -1px; flex-shrink: 0;
      }}
      .header-title {{ font-size: 18px; font-weight: 700; letter-spacing: 0.01em; }}
      .header-sub   {{ font-size: 12px; opacity: 0.82; margin-top: 2px; }}
      .header-badge {{
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.30);
        padding: 5px 12px; border-radius: 20px;
        font-size: 12px; font-weight: 600; letter-spacing: 0.03em;
      }}
    
      /* ---- Layout principal ---- */
      .main {{ max-width: 1280px; margin: 0 auto; padding: 28px 24px; }}
    
      /* ---- Secoes ---- */
      .panel-section {{
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
      }}
      .section-header {{
        padding: 16px 24px;
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--neutral-50);
      }}
      .section-number {{
        width: 26px; height: 26px;
        background: var(--bradesco-red);
        color: var(--white);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 700; flex-shrink: 0;
      }}
      .section-title {{ font-size: 14px; font-weight: 700; color: var(--neutral-900); }}
      .section-desc  {{ font-size: 12px; color: var(--neutral-500); margin-left: auto; }}
      .section-body  {{ padding: 24px; }}
    
      /* ---- Grid de campos ---- */
      .form-grid {{ display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }}
      .form-grid-3 {{ display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }}
      .span-2 {{ grid-column: span 2; }}
    
      .field {{ display: flex; flex-direction: column; gap: 5px; }}
      .field label {{
        font-size: 12px; font-weight: 600;
        color: var(--neutral-700); letter-spacing: 0.02em; text-transform: uppercase;
      }}
      .field input, .field select, .field textarea {{
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 9px 12px;
        font-size: 14px;
        font-family: var(--font);
        color: var(--neutral-900);
        background: var(--white);
        transition: border-color 0.15s, box-shadow 0.15s;
        outline: none;
        width: 100%;
      }}
      .field input:focus, .field select:focus, .field textarea:focus {{
        border-color: var(--bradesco-red);
        box-shadow: 0 0 0 3px rgba(204,0,0,0.10);
      }}
      .field textarea {{ resize: vertical; min-height: 80px; }}
      .field-hint {{ font-size: 11px; color: var(--neutral-500); }}
    
      /* ---- Barra de busca ---- */
      .search-row {{ display: flex; gap: 8px; align-items: flex-end; }}
      .search-row .field {{ flex: 1; }}
      .btn-search {{
        height: 38px; padding: 0 18px;
        background: var(--bradesco-red); color: var(--white);
        border: none; border-radius: var(--radius);
        font-size: 13px; font-weight: 600;
        cursor: pointer; white-space: nowrap;
        transition: background 0.15s;
        flex-shrink: 0;
      }}
      .btn-search:hover {{ background: var(--bradesco-dark); }}
    
      /* ---- Tabela de resultados ---- */
      .results-wrap {{
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-top: 16px;
      }}
      .results-header {{
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 16px;
        background: var(--neutral-50);
        border-bottom: 1px solid var(--border);
        font-size: 12px; color: var(--neutral-500);
      }}
      .results-count {{ font-weight: 600; color: var(--neutral-700); }}
      .source-tag {{
        padding: 2px 8px; border-radius: 20px;
        font-size: 11px; font-weight: 600;
      }}
      .source-genquery {{ background: #E8F4FD; color: #1565C0; }}
      .source-bi       {{ background: #FFF3E0; color: #E65100; }}
    
      table {{ width: 100%; border-collapse: collapse; font-size: 13px; }}
      thead th {{
        padding: 10px 14px; text-align: left;
        background: var(--neutral-50);
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.04em;
        color: var(--neutral-500);
        border-bottom: 1px solid var(--border);
      }}
      tbody tr {{
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background 0.12s;
      }}
      tbody tr:last-child {{ border-bottom: none; }}
      tbody tr:hover {{ background: #FFF5F5; }}
      tbody tr.selected {{ background: #FFEBEB; }}
      tbody tr.selected td {{ color: var(--bradesco-dark); font-weight: 600; }}
      tbody td {{ padding: 10px 14px; color: var(--neutral-700); }}
    
      /* ---- Resumo de metricas ---- */
      .metrics-layout {{ display: flex; gap: 20px; margin-top: 16px; }}
      .metrics-table-wrap {{ flex: 1; min-width: 0; }}
      .metrics-sidebar {{
        width: 220px; flex-shrink: 0;
        display: flex; flex-direction: column; gap: 8px;
      }}
      .metric-card {{
        background: var(--neutral-50);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 12px 14px;
      }}
      .metric-card-label {{ font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--neutral-500); }}
      .metric-card-value {{ font-size: 22px; font-weight: 800; color: var(--bradesco-red); margin: 2px 0; }}
      .metric-card-sub   {{ font-size: 11px; color: var(--neutral-500); }}
      .metric-card-query {{
        margin-top: 4px; display: inline-block;
        font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 10px;
      }}
      .date-range-display {{
        background: var(--neutral-50);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 12px 14px;
      }}
      .date-range-label {{ font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--neutral-500); margin-bottom: 6px; }}
      .date-range-value {{ font-size: 13px; font-weight: 600; color: var(--neutral-700); }}
    
      /* ---- Checkboxes de dimensoes ---- */
      .dim-grid {{ display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }}
      .dim-card {{
        border: 2px solid var(--border);
        border-radius: var(--radius);
        padding: 14px 12px;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
        display: flex; flex-direction: column; gap: 6px;
        position: relative;
      }}
      .dim-card:hover {{ border-color: var(--bradesco-red); }}
      .dim-card.active {{
        border-color: var(--bradesco-red);
        background: #FFF5F5;
      }}
      .dim-card input[type=checkbox] {{
        position: absolute; top: 10px; right: 10px;
        width: 16px; height: 16px;
        accent-color: var(--bradesco-red);
        cursor: pointer;
      }}
      .dim-card-name {{ font-size: 13px; font-weight: 700; color: var(--neutral-900); padding-right: 20px; }}
      .dim-card-count {{ font-size: 20px; font-weight: 800; color: var(--bradesco-red); }}
      .dim-card-zero .dim-card-count {{ color: var(--neutral-300); }}
      .dim-card-badge {{
        font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 10px;
        display: inline-block; margin-top: 2px;
      }}
    
      /* ---- Edicao de valores ---- */
      .values-grid {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }}
      .value-field-wrap {{
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 14px;
      }}
      .value-field-wrap label {{
        font-size: 12px; font-weight: 700;
        color: var(--neutral-700); display: block; margin-bottom: 6px;
      }}
      .value-field-wrap input {{
        width: 100%; border: 1px solid var(--border);
        border-radius: var(--radius); padding: 8px 10px;
        font-size: 14px; font-weight: 600; color: var(--bradesco-red);
      }}
      .value-field-wrap input:focus {{
        outline: none; border-color: var(--bradesco-red);
        box-shadow: 0 0 0 3px rgba(204,0,0,0.10);
      }}
    
      /* ---- Editor de textos por abas ---- */
      .tabs {{ display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 0; }}
      .tab {{
        padding: 10px 18px; font-size: 13px; font-weight: 600;
        color: var(--neutral-500); cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -2px;
        transition: color 0.15s, border-color 0.15s;
        white-space: nowrap;
      }}
      .tab:hover {{ color: var(--bradesco-red); }}
      .tab.active {{ color: var(--bradesco-red); border-bottom-color: var(--bradesco-red); }}
      .tab-panel {{ display: none; padding: 20px 0 0; }}
      .tab-panel.active {{ display: block; }}
      .tab-panel textarea {{
        width: 100%; min-height: 120px;
        border: 1px solid var(--border); border-radius: var(--radius);
        padding: 12px; font-size: 13px; font-family: var(--font);
        resize: vertical; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
      }}
      .tab-panel textarea:focus {{
        border-color: var(--bradesco-red);
        box-shadow: 0 0 0 3px rgba(204,0,0,0.10);
      }}
      .template-hint {{
        margin-top: 8px; padding: 10px 12px;
        background: var(--warning-light);
        border-left: 3px solid var(--warning);
        border-radius: 0 var(--radius) var(--radius) 0;
        font-size: 12px; color: var(--warning);
      }}
      .template-vars {{
        margin-top: 8px;
        display: flex; flex-wrap: wrap; gap: 6px;
      }}
      .var-chip {{
        padding: 3px 8px; border-radius: 4px;
        background: var(--neutral-100); border: 1px solid var(--border);
        font-size: 11px; font-family: monospace; color: var(--neutral-700);
        cursor: default;
      }}
    
      /* ---- Acoes finais ---- */
      .action-bar {{
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
      }}
      .action-info {{ font-size: 12px; color: var(--neutral-500); }}
    
      .btn {{
        display: inline-flex; align-items: center; justify-content: center;
        gap: 7px; padding: 11px 24px;
        border-radius: var(--radius); font-size: 14px; font-weight: 700;
        cursor: pointer; border: none; transition: all 0.15s;
        letter-spacing: 0.01em;
      }}
      .btn-primary {{
        background: var(--bradesco-red); color: var(--white);
        box-shadow: 0 2px 8px rgba(204,0,0,0.25);
      }}
      .btn-primary:hover {{ background: var(--bradesco-dark); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(204,0,0,0.30); }}
      .btn-primary:active {{ transform: translateY(0); }}
      .btn-secondary {{
        background: var(--white); color: var(--neutral-700);
        border: 1px solid var(--border);
      }}
      .btn-secondary:hover {{ background: var(--neutral-50); border-color: var(--neutral-300); }}
      .btn-success {{
        background: var(--success); color: var(--white);
        box-shadow: 0 2px 8px rgba(26,122,74,0.25);
      }}
      .btn-success:hover {{ background: #145c38; }}
    
      /* ---- Estado vazio e alertas ---- */
      .empty-state {{
        padding: 32px; text-align: center;
        color: var(--neutral-500); font-size: 13px;
      }}
      .alert {{
        padding: 10px 14px; border-radius: var(--radius);
        font-size: 13px; margin-top: 10px;
      }}
      .alert-info    {{ background: #E8F4FD; color: #1565C0; border-left: 3px solid #1565C0; }}
      .alert-warning {{ background: var(--warning-light); color: var(--warning); border-left: 3px solid var(--warning); }}
    
      /* ---- Loader ---- */
      .loader-overlay {{
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.45); z-index: 9999;
        align-items: center; justify-content: center; flex-direction: column; gap: 16px;
      }}
      .loader-overlay.active {{ display: flex; }}
      .loader-spinner {{
        width: 44px; height: 44px;
        border: 4px solid rgba(255,255,255,0.25);
        border-top-color: var(--white);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
      }}
      .loader-text {{ color: var(--white); font-size: 14px; font-weight: 600; }}
      @keyframes spin {{ to {{ transform: rotate(360deg); }} }}
    
      /* ---- PDF preview area ---- */
      #pdf-section {{ display: none; }}
      .pdf-action-row {{
        display: flex; align-items: center; justify-content: center; gap: 12px;
        padding: 20px; flex-wrap: wrap;
      }}
    
      /* ---- Responsivo ---- */
      @media (max-width: 900px) {{
        .form-grid, .form-grid-3 {{ grid-template-columns: 1fr 1fr; }}
        .dim-grid {{ grid-template-columns: repeat(3, 1fr); }}
        .metrics-layout {{ flex-direction: column; }}
        .metrics-sidebar {{ width: 100%; flex-direction: row; flex-wrap: wrap; }}
        .metric-card {{ flex: 1; min-width: 140px; }}
      }}
      @media (max-width: 600px) {{
        .form-grid, .form-grid-3, .values-grid {{ grid-template-columns: 1fr; }}
        .dim-grid {{ grid-template-columns: 1fr 1fr; }}
        .span-2 {{ grid-column: span 1; }}
        .header {{ flex-direction: column; gap: 12px; }}
      }}
    </style>
    </head>
    <body>
    
    <div class="loader-overlay" id="loader">
      <div class="loader-spinner"></div>
      <div class="loader-text" id="loader-text">Processando...</div>
    </div>
    
    <!-- Cabecalho -->
    <div class="header">
      <div class="header-brand">
        <div class="header-logo">B</div>
        <div>
          <div class="header-title">Parecer Tecnico de Qualidade de Dados</div>
          <div class="header-sub">Data Governance - Bradesco</div>
        </div>
      </div>
      <div class="header-badge">Painel de Controle</div>
    </div>
    
    <div class="main">
    
      <!-- SECAO 1: Identificacao do documento -->
      <div class="panel-section">
        <div class="section-header">
          <div class="section-number">1</div>
          <div class="section-title">Identificacao do Documento</div>
          <div class="section-desc">Dados do responsavel pela emissao</div>
        </div>
        <div class="section-body">
          <div class="form-grid">
            <div class="field">
              <label>Nome do Autor</label>
              <input type="text" id="autor" placeholder="Nome completo">
            </div>
            <div class="field">
              <label>Perfil</label>
              <select id="ana_esp">
                <option value="Analista">Analista</option>
                <option value="Especialista">Especialista</option>
              </select>
            </div>
            <div class="field">
              <label>Nome do Produto (exibicao no documento)</label>
              <input type="text" id="nome_display" placeholder="Nome que aparecera no PDF">
              <span class="field-hint">Preenchido automaticamente ao selecionar um produto. Pode ser editado.</span>
            </div>
            <div class="field">
              <label>Tipo de Produto</label>
              <input type="text" id="tipo_produto" placeholder="Sera preenchido ao selecionar">
              <span class="field-hint">Preenchido automaticamente ao selecionar um produto.</span>
            </div>
          </div>
        </div>
      </div>
    
      <!-- SECAO 2: Busca de produto -->
      <div class="panel-section">
        <div class="section-header">
          <div class="section-number">2</div>
          <div class="section-title">Busca de Produto</div>
          <div class="section-desc">Pesquise por nome parcial - separadores: espaco ou sublinhado</div>
        </div>
        <div class="section-body">
          <div class="form-grid">
            <div class="field">
              <label>Busca por Nome</label>
              <div class="search-row">
                <div class="field" style="margin:0">
                  <input type="text" id="busca_nome" placeholder="Ex: UORG tresp und" autocomplete="off">
                </div>
                <button class="btn-search" onclick="executarBusca()">Buscar</button>
              </div>
              <span class="field-hint">Dispara ao clicar em Buscar ou ao sair do campo</span>
            </div>
            <div class="field">
              <label>Busca por E-mail do Responsavel</label>
              <div class="search-row">
                <div class="field" style="margin:0">
                  <input type="text" id="busca_email" placeholder="nome@bradesco.com.br" autocomplete="off">
                </div>
              </div>
              <span class="field-hint">Disponivel apenas na query GenQuery</span>
            </div>
          </div>
    
          <div id="resultados-busca">
            <div class="empty-state">Nenhuma busca realizada ainda.</div>
          </div>
          <div id="instrucao-wrap"></div>
        </div>
      </div>
    
      <!-- SECAO 3: Periodo de analise -->
      <div class="panel-section">
        <div class="section-header">
          <div class="section-number">3</div>
          <div class="section-title">Periodo de Analise</div>
          <div class="section-desc">Janela temporal para as queries de metricas</div>
        </div>
        <div class="section-body">
          <div class="form-grid-3">
            <div class="field">
              <label>Data de Inicio</label>
              <input type="date" id="dt_ini" value="{_dt_ini_padrao}">
            </div>
            <div class="field">
              <label>Data de Fim</label>
              <input type="date" id="dt_fim" value="{_dt_fim_padrao}">
            </div>
            <div class="field">
              <label>Atalhos de periodo</label>
              <select id="periodo_atalho" onchange="aplicarAtalho(this.value)">
                <option value="">Selecionar atalho...</option>
                <option value="7">Ultimos 7 dias</option>
                <option value="30" selected>Ultimo mes</option>
                <option value="90">Ultimos 3 meses</option>
                <option value="180">Ultimos 6 meses</option>
                <option value="365">Ultimo ano</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    
      <!-- SECAO 4: Resultados e metricas -->
      <div class="panel-section" id="secao-metricas" style="display:none">
        <div class="section-header">
          <div class="section-number">4</div>
          <div class="section-title">Metricas do Produto Selecionado</div>
          <div class="section-desc" id="produto-selecionado-label">-</div>
        </div>
        <div class="section-body">
          <div class="metrics-layout">
            <div class="metrics-table-wrap" id="tabela-detalhe">
              <div class="empty-state">Selecione um produto para carregar os detalhes.</div>
            </div>
            <div class="metrics-sidebar">
              <div class="date-range-display">
                <div class="date-range-label">Periodo analisado</div>
                <div class="date-range-value" id="label-periodo">-</div>
              </div>
              <div class="metric-card">
                <div class="metric-card-label">Completude</div>
                <div class="metric-card-value" id="mc-completude">-</div>
                <div class="metric-card-sub" id="ms-completude"></div>
                <span class="metric-card-query source-genquery" id="mq-completude"></span>
              </div>
              <div class="metric-card">
                <div class="metric-card-label">Consistencia</div>
                <div class="metric-card-value" id="mc-consistencia">-</div>
                <span class="metric-card-query source-genquery" id="mq-consistencia"></span>
              </div>
              <div class="metric-card">
                <div class="metric-card-label">Unicidade</div>
                <div class="metric-card-value" id="mc-unicidade">-</div>
                <span class="metric-card-query source-genquery" id="mq-unicidade"></span>
              </div>
              <div class="metric-card">
                <div class="metric-card-label">Variacao</div>
                <div class="metric-card-value" id="mc-variacao">-</div>
                <span class="metric-card-query source-genquery" id="mq-variacao"></span>
              </div>
              <div class="metric-card">
                <div class="metric-card-label">Disponibilidade</div>
                <div class="metric-card-value" style="font-size:13px;color:var(--neutral-500)">Texto manual</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    
      <!-- SECAO 5: Selecao de dimensoes -->
      <div class="panel-section" id="secao-dimensoes" style="display:none">
        <div class="section-header">
          <div class="section-number">5</div>
          <div class="section-title">Dimensoes a incluir no Documento</div>
          <div class="section-desc">Dimensoes com valores sao pre-selecionadas. Ajuste conforme necessario.</div>
        </div>
        <div class="section-body">
          <div class="dim-grid">
            <div class="dim-card" id="card-completude" onclick="toggleDim('completude')">
              <input type="checkbox" id="chk-completude">
              <div class="dim-card-name">Completude</div>
              <div class="dim-card-count" id="dv-completude">0</div>
              <span class="dim-card-badge" id="db-completude"></span>
            </div>
            <div class="dim-card" id="card-consistencia" onclick="toggleDim('consistencia')">
              <input type="checkbox" id="chk-consistencia">
              <div class="dim-card-name">Consistencia</div>
              <div class="dim-card-count" id="dv-consistencia">0</div>
              <span class="dim-card-badge" id="db-consistencia"></span>
            </div>
            <div class="dim-card" id="card-disponibilidade" onclick="toggleDim('disponibilidade')">
              <input type="checkbox" id="chk-disponibilidade">
              <div class="dim-card-name">Disponibilidade</div>
              <div class="dim-card-count" style="font-size:13px; color:var(--neutral-400)">Texto livre</div>
              <span class="dim-card-badge source-genquery">Manual</span>
            </div>
            <div class="dim-card" id="card-unicidade" onclick="toggleDim('unicidade')">
              <input type="checkbox" id="chk-unicidade">
              <div class="dim-card-name">Unicidade</div>
              <div class="dim-card-count" id="dv-unicidade">0</div>
              <span class="dim-card-badge" id="db-unicidade"></span>
            </div>
            <div class="dim-card" id="card-variacao" onclick="toggleDim('variacao')">
              <input type="checkbox" id="chk-variacao">
              <div class="dim-card-name">Variacao</div>
              <div class="dim-card-count" id="dv-variacao">0</div>
              <span class="dim-card-badge" id="db-variacao"></span>
            </div>
          </div>
          <div class="alert alert-info" style="margin-top:16px">
            A numeracao das secoes (3.1, 3.2...) sera calculada automaticamente com base nas dimensoes selecionadas.
          </div>
        </div>
      </div>
    
      <!-- SECAO 6: Edicao de valores -->
      <div class="panel-section" id="secao-valores" style="display:none">
        <div class="section-header">
          <div class="section-number">6</div>
          <div class="section-title">Revisao de Valores</div>
          <div class="section-desc">Edite os valores das dimensoes selecionadas se necessario</div>
        </div>
        <div class="section-body">
          <div class="values-grid" id="valores-grid"></div>
          <div id="disponibilidade-texto-wrap" style="display:none; margin-top:16px">
            <div class="field">
              <label>Texto da Disponibilidade</label>
              <textarea id="disponibilidade_texto" placeholder="Descreva a disponibilidade dos dados. Use * para bullets e ## para sub-titulos."></textarea>
            </div>
          </div>
        </div>
      </div>
    
      <!-- SECAO 7: Edicao de textos das secoes -->
      <div class="panel-section" id="secao-textos" style="display:none">
        <div class="section-header">
          <div class="section-number">7</div>
          <div class="section-title">Textos das Secoes</div>
          <div class="section-desc">Alteracoes valem apenas para esta geracao</div>
        </div>
        <div class="section-body">
          <div class="tabs" id="abas-textos"></div>
          <div id="paineis-textos"></div>
        </div>
      </div>
    
      <!-- SECAO 8: Gerar PDF -->
      <div class="panel-section">
        <div class="section-header">
          <div class="section-number">8</div>
          <div class="section-title">Gerar Documento</div>
        </div>
        <div class="section-body">
          <div class="action-bar">
            <div class="action-info">
              Revise todas as secoes antes de gerar. Apos clicar em Gerar PDF, execute a proxima celula do notebook.
              Revise todas as secoes antes de gerar. Clique em <strong>Gerar PDF</strong>,
              copie o JSON gerado, cole no widget <strong>json_payload</strong> do Databricks
              e execute a <strong>Celula 5</strong>.
            <div style="display:flex; gap:10px">
              <button class="btn btn-secondary" onclick="resetarPainel()">Limpar tudo</button>
              <button class="btn btn-primary" onclick="prepararPayload()">Gerar PDF</button>
            </div>
          </div>
        </div>
        <div style="margin-top:16px">
          <textarea
            id="payload-output"
            readonly
            style="display:none; width:100%; min-height:80px; font-family:monospace;
                   font-size:11px; border:1px solid #E2E2E2; border-radius:6px;
                   padding:10px; background:#FAFAFA; color:#3D3D3D; resize:vertical;"
            onclick="this.select()"
          ></textarea>
          <div id="payload-copy-hint" style="display:none; margin-top:6px; font-size:11px; color:#6B6B6B">
            Clique no campo acima para selecionar tudo (Ctrl+A) e copie (Ctrl+C).
            Cole no widget <strong>json_payload</strong> do Databricks e execute a Celula 5.
          </div>
        </div>
      </div>
    
      <!-- SECAO 9: Acoes do PDF (aparece apos gerar) -->
      <div class="panel-section" id="pdf-section">
        <div class="section-header">
          <div class="section-number">9</div>
          <div class="section-title">Download</div>
        </div>
        <div class="pdf-action-row">
          <a id="btn-download" href="#" download="parecer.pdf" class="btn btn-primary">Baixar PDF</a>
          <button class="btn btn-success" onclick="abrirPDF()">Visualizar em Nova Aba</button>
        </div>
      </div>
    
    </div><!-- /main -->
    
    <script>
    // ================================================================
    // Estado global do painel
    // ================================================================
    const DIMS   = ['completude','consistencia','disponibilidade','unicidade','variacao'];
    const LABELS = {{
      completude:'Completude', consistencia:'Consistencia',
      disponibilidade:'Disponibilidade', unicidade:'Unicidade', variacao:'Variacao'
    }};
    const VARS_POR_DIM = {{
      completude:    ['{{Valor_Completude}}','{{Pct_Completude}}','{{Lowest3_Completude}}'],
      consistencia:  ['{{Valor_Consistencia}}'],
      disponibilidade: ['{{Valor_Disponibilidade}}'],
      unicidade:     ['{{Valor_Unicidade}}'],
      variacao:      ['{{Valor_Variacao}}'],
    }};
    
    const TEMPLATES_DEFAULT = {_templates_json};
    
    let state = {{
      produto:    null,
      metricas:   {{}},
      dimensoes:  {{}},
      valores:    {{}},
      textos:     {{}},
      resultados: [],
      pdfB64:     null,
    }};
    
    // ================================================================
    // Busca de produto
    // Grava o pedido no widget search_payload e orienta o usuario a executar
    // a Celula 4.5, que roda as queries Python e re-renderiza o painel com
    // os resultados reais injetados em _injected_state_json.
    // ================================================================
    
    document.getElementById('busca_nome').addEventListener('blur', executarBusca);
    
    function executarBusca() {{
      const nome  = document.getElementById('busca_nome').value.trim();
      const email = document.getElementById('busca_email').value.trim();
      if (!nome) return;

      // O JS nao tem acesso ao kernel Python — nao e possivel gravar widgets via JS.
      // O fluxo correto: preencher os widgets nativos do Databricks e executar a Celula 4.5.
      exibirInstrucao(
        'Preencha os widgets nativos do Databricks acima:',
        '<strong>Nome do Produto</strong>: <code>' + nome + '</code>' +
        (email ? '<br><strong>E-mail</strong>: <code>' + email + '</code>' : '') +
        '<br><br>Em seguida execute a <strong>Celula 4.5</strong> para buscar os resultados.'
      );
    }}
    
    function renderizarResultados(rows, dtIni, dtFim) {{
      const wrap = document.getElementById('resultados-busca');
      if (!rows.length) {{
        wrap.innerHTML = '<div class="empty-state">Nenhum produto encontrado para o termo informado.</div>';
        return;
      }}
    
      const header = `
        <div class="results-wrap">
          <div class="results-header">
            <span class="results-count">${{rows.length}} resultado(s) encontrado(s)</span>
          </div>
          <table>
            <thead><tr>
              <th>Nome do Produto</th>
              <th>Tipo</th>
              <th>E-mail Responsavel</th>
              <th>Data de Criacao</th>
              <th>Query</th>
            </tr></thead>
            <tbody id="tbody-resultados"></tbody>
          </table>
        </div>`;
      wrap.innerHTML = header;
    
      const tbody = document.getElementById('tbody-resultados');
      rows.forEach((r, i) => {{
        const tr = document.createElement('tr');
        tr.setAttribute('data-idx', i);
        const qClass = r._query === 'BI' ? 'source-bi' : 'source-genquery';
        tr.innerHTML = `
          <td>${{r.nome_produto.replace(/_/g,' ')}}</td>
          <td>${{r.tipo_produto || '-'}}</td>
          <td>${{r.email_responsavel || '<span style="color:var(--neutral-300)">N/D</span>'}}</td>
          <td>${{r.data_criacao || '-'}}</td>
          <td><span class="source-tag ${{qClass}}">${{r._query}}</span></td>`;
        tr.addEventListener('click', () => selecionarProduto(i, dtIni, dtFim));
        tbody.appendChild(tr);
      }});
    }}
    
    // ================================================================
    // Selecao de produto
    // ================================================================
    
    function selecionarProduto(idx, dtIni, dtFim) {{
      document.querySelectorAll('#tbody-resultados tr').forEach(tr => tr.classList.remove('selected'));
      document.querySelector(`#tbody-resultados tr[data-idx="${{idx}}"]`).classList.add('selected');
    
      const p = state.resultados[idx];
      state.produto = p;
    
      document.getElementById('nome_display').value   = p.nome_produto.replace(/_/g,' ');
      document.getElementById('tipo_produto').value   = p.tipo_produto || '';
    
      setLoader(true, 'Carregando metricas...');
      carregarMetricas(p, dtIni, dtFim);
    }}
    
    function carregarMetricas(produto, dtIni, dtFim) {{
      // O JS nao tem acesso ao kernel Python — orienta o usuario a copiar os valores
      // para os widgets nativos do Databricks e executar a Celula 4.5.
      exibirInstrucao(
        'Produto selecionado: <strong>' + produto.nome_produto.replace(/_/g,' ') + '</strong>',
        'Preencha os widgets nativos do Databricks:<br>' +
        '&nbsp;&nbsp;<strong>Produto Selecionado</strong>: <code>' + produto.nome_produto + '</code><br>' +
        '&nbsp;&nbsp;<strong>Tipo do Produto</strong>: <code>' + (produto.tipo_produto || '') + '</code><br>' +
        '&nbsp;&nbsp;<strong>Query de Origem</strong>: <code>' + (produto._query || 'GenQuery') + '</code><br><br>' +
        'Em seguida execute a <strong>Celula 4.5</strong> para carregar as metricas.'
      );
    }}
    
    // ================================================================
    // Sidebar de metricas
    // ================================================================
    
    function atualizarMetricasSidebar(metricas, dtIni, dtFim) {{
      document.getElementById('label-periodo').textContent =
        formatarData(dtIni) + '  a  ' + formatarData(dtFim);
    
      ['completude','consistencia','unicidade','variacao'].forEach(dim => {{
        const m = metricas[dim] || {{}};
        document.getElementById('mc-' + dim).textContent = m.count ?? '-';
        const qEl = document.getElementById('mq-' + dim);
        if (qEl) {{
          qEl.textContent = m.query || '';
          qEl.className = 'metric-card-query ' + (m.query === 'BI' ? 'source-bi' : 'source-genquery');
        }}
      }});
      const ms = document.getElementById('ms-completude');
      if (ms && metricas.completude) {{
        ms.textContent = metricas.completude.pct != null ? metricas.completude.pct + '% score medio' : '';
      }}
    }}
    
    // ================================================================
    // Cards de dimensoes
    // ================================================================
    
    function atualizarCardsDimensoes(metricas) {{
      DIMS.forEach(dim => {{
        if (dim === 'disponibilidade') return;
        const m     = metricas[dim] || {{}};
        const count = m.count || 0;
        const qClass = m.query === 'BI' ? 'source-bi' : 'source-genquery';
    
        const dvEl = document.getElementById('dv-' + dim);
        const dbEl = document.getElementById('db-' + dim);
        const card = document.getElementById('card-' + dim);
    
        if (dvEl) dvEl.textContent = count;
        if (dbEl) {{ dbEl.textContent = m.query || ''; dbEl.className = 'dim-card-badge ' + qClass; }}
    
        if (count === 0) card.classList.add('dim-card-zero');
        else card.classList.remove('dim-card-zero');
    
        const checked = count > 0;
        document.getElementById('chk-' + dim).checked = checked;
        state.dimensoes[dim] = checked;
        card.classList.toggle('active', checked);
        state.valores[dim] = count;
      }});
    
      atualizarValores();
      atualizarTextosAbas();
      document.getElementById('secao-valores').style.display = '';
      document.getElementById('secao-textos').style.display  = '';
    }}
    
    function toggleDim(dim) {{
      const chk = document.getElementById('chk-' + dim);
      chk.checked = !chk.checked;
      state.dimensoes[dim] = chk.checked;
      document.getElementById('card-' + dim).classList.toggle('active', chk.checked);
      atualizarValores();
      atualizarTextosAbas();
    }}
    
    // ================================================================
    // Valores editaveis
    // ================================================================
    
    function atualizarValores() {{
      const grid  = document.getElementById('valores-grid');
      const dispW = document.getElementById('disponibilidade-texto-wrap');
      grid.innerHTML = '';
    
      DIMS.filter(d => d !== 'disponibilidade' && state.dimensoes[d]).forEach(dim => {{
        const wrap = document.createElement('div');
        wrap.className = 'value-field-wrap';
        const atual = state.valores[dim] ?? (state.metricas[dim] || {{}}).count ?? 0;
        wrap.innerHTML = `
          <label>${{LABELS[dim]}}</label>
          <input type="number" id="val-${{dim}}" value="${{atual}}"
                 onchange="state.valores['${{dim}}'] = this.value">`;
        grid.appendChild(wrap);
      }});
    
      const dispAtivo = state.dimensoes['disponibilidade'];
      dispW.style.display = dispAtivo ? '' : 'none';
    }}
    
    // ================================================================
    // Abas de textos por secao
    // ================================================================
    
    function atualizarTextosAbas() {{
      const tabsEl   = document.getElementById('abas-textos');
      const paineis  = document.getElementById('paineis-textos');
      tabsEl.innerHTML   = '';
      paineis.innerHTML  = '';
    
      const ativas = DIMS.filter(d => state.dimensoes[d]);
      if (!ativas.length) {{
        tabsEl.innerHTML = '<span style="padding:10px;color:var(--neutral-500);font-size:13px">Nenhuma dimensao selecionada.</span>';
        return;
      }}
    
      ativas.forEach((dim, i) => {{
        const tab = document.createElement('div');
        tab.className = 'tab' + (i === 0 ? ' active' : '');
        tab.textContent = LABELS[dim];
        tab.onclick = () => ativarAba(dim, ativas);
        tab.id = 'tab-' + dim;
        tabsEl.appendChild(tab);
    
        const painel = document.createElement('div');
        painel.className = 'tab-panel' + (i === 0 ? ' active' : '');
        painel.id = 'painel-' + dim;
    
        const tplAtual = state.textos[dim] || TEMPLATES_DEFAULT[dim] || '';
        const vars     = VARS_POR_DIM[dim] || [];
    
        painel.innerHTML = `
          <textarea id="tpl-${{dim}}" rows="6"
            onchange="state.textos['${{dim}}'] = this.value"
            oninput="state.textos['${{dim}}'] = this.value"
          >${{escHtml(tplAtual)}}</textarea>
          <div class="template-hint">
            Esta alteracao e valida apenas para a geracao atual. A variavel <code>{{numero}}</code>
            e preenchida automaticamente conforme as dimensoes selecionadas.
          </div>
          <div class="template-vars">
            <span style="font-size:11px;color:var(--neutral-500);align-self:center">Variaveis disponíveis:</span>
            ${{vars.map(v => `<span class="var-chip">${{v}}</span>`).join('')}}
            <span class="var-chip">{{numero}}</span>
          </div>`;
        paineis.appendChild(painel);
      }});
    }}
    
    function ativarAba(dim, ativas) {{
      ativas.forEach(d => {{
        document.getElementById('tab-' + d)?.classList.toggle('active', d === dim);
        document.getElementById('painel-' + d)?.classList.toggle('active', d === dim);
      }});
    }}
    
    // ================================================================
    // Atalhos de periodo
    // ================================================================
    
    function aplicarAtalho(dias) {{
      if (!dias) return;
      const hoje = new Date();
      const ini  = new Date(hoje);
      ini.setDate(ini.getDate() - parseInt(dias));
      document.getElementById('dt_fim').value = toISODate(hoje);
      document.getElementById('dt_ini').value = toISODate(ini);
    }}
    
    // ================================================================
    // Preparar payload para Python
    // ================================================================
    
    function prepararPayload() {{
      if (!state.produto) {{
        alert('Selecione um produto antes de gerar o PDF.');
        return;
      }}
    
      // Coleta textos editados
      DIMS.filter(d => state.dimensoes[d]).forEach(dim => {{
        const el = document.getElementById('tpl-' + dim);
        if (el) state.textos[dim] = el.value;
      }});
    
      const dispEl = document.getElementById('disponibilidade_texto');
      if (dispEl) state.valores['disponibilidade'] = dispEl.value;
    
      const payload = {{
        autor:       document.getElementById('autor').value.trim(),
        ana_esp:     document.getElementById('ana_esp').value,
        nome_display:document.getElementById('nome_display').value.trim(),
        nome_produto: state.produto.nome_produto,
        tipo_produto: document.getElementById('tipo_produto').value.trim(),
        dt_ini:      document.getElementById('dt_ini').value,
        dt_fim:      document.getElementById('dt_fim').value,
        dimensoes:   state.dimensoes,
        valores:     state.valores,
        textos:      state.textos,
        metricas:    state.metricas,
      }};
    
    
      // Exibe o JSON serializado num campo copiavel — o usuario cola no widget
      // "json_payload" do Databricks e executa a Celula 5.
      const jsonStr = JSON.stringify(payload);
      const area = document.getElementById('payload-output');
      if (area) {{
        area.value = jsonStr;
        area.style.display = 'block';
      }}

      exibirInstrucao(
        'Payload gerado.',
        'Copie o JSON do campo abaixo, cole no widget <strong>json_payload</strong> ' +
        'do Databricks e execute a <strong>Celula 5</strong>.'
      );
    }}
    
    // ================================================================
    // Utilidades
    // ================================================================
    
    function setLoader(ativo, texto) {{
      document.getElementById('loader').classList.toggle('active', ativo);
      if (texto) document.getElementById('loader-text').textContent = texto;
    }}
    
    function resetarPainel() {{
      if (!confirm('Limpar todos os campos?')) return;
      state = {{ produto: null, metricas: {{}}, dimensoes: {{}}, valores: {{}}, textos: {{}}, resultados: [], pdfB64: null }};
      document.getElementById('busca_nome').value   = '';
      document.getElementById('busca_email').value  = '';
      document.getElementById('autor').value        = '';
      document.getElementById('nome_display').value = '';
      document.getElementById('tipo_produto').value = '';
      document.getElementById('resultados-busca').innerHTML = '<div class="empty-state">Nenhuma busca realizada ainda.</div>';
      document.getElementById('secao-metricas').style.display  = 'none';
      document.getElementById('secao-dimensoes').style.display = 'none';
      document.getElementById('secao-valores').style.display   = 'none';
      document.getElementById('secao-textos').style.display    = 'none';
      document.getElementById('pdf-section').style.display     = 'none';
    }}
    
    function abrirPDF() {{
      if (!state.pdfB64) return;
      const byteCharacters = atob(state.pdfB64);
      const byteNumbers    = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) byteNumbers[i] = byteCharacters.charCodeAt(i);
      const blob = new Blob([new Uint8Array(byteNumbers)], {{type: 'application/pdf'}});
      const url  = URL.createObjectURL(blob);
      const w    = window.open(url, '_blank');
      if (!w) alert('Bloqueio de pop-up detectado. Use o botao Baixar PDF.');
    }}
    
    function escHtml(s) {{
      return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }}
    
    function toISODate(d) {{
      return d.toISOString().substring(0,10);
    }}
    
    function formatarData(iso) {{
      if (!iso) return '';
      const [y,m,d] = iso.split('-');
      return d + '/' + m + '/' + y;
    }}
    
    // ================================================================
    // Instrucao de proxima acao (substitui o loader de espera)
    // ================================================================
    
    function exibirInstrucao(titulo, detalhe) {{
      const el = document.getElementById('instrucao-wrap');
      if (!el) return;
      el.innerHTML = `
        <div style="
          background:#FFF8E1; border-left:4px solid #F59E0B;
          border-radius:6px; padding:14px 18px; margin-top:16px;
          font-size:13px; color:#78350F; line-height:1.6;
        ">
          <strong>${{titulo}}</strong><br>
          ${{detalhe}}
        </div>`;
      el.scrollIntoView({{ behavior:'smooth', block:'nearest' }});
    }}
    
    // ================================================================
    // Estado pre-populado injetado pela Celula 4.5
    // Quando _injected_state_json nao e vazio, restaura resultados e
    // metricas sem nenhuma interacao adicional do usuario.
    // ================================================================
    
    (function() {{
      let injetado;
      try {{ injetado = JSON.parse('{_injected_state_json}'); }} catch(e) {{ return; }}
      if (!injetado || !injetado.acao) return;
    
      const dtIni = document.getElementById('dt_ini').value;
      const dtFim = document.getElementById('dt_fim').value;
    
      if (injetado.acao === 'busca_resultado' && Array.isArray(injetado.resultados)) {{
        state.resultados = injetado.resultados;
        // Restaura os campos de busca
        if (injetado.busca_nome)  document.getElementById('busca_nome').value  = injetado.busca_nome;
        if (injetado.busca_email) document.getElementById('busca_email').value = injetado.busca_email;
        if (injetado.dt_ini)      document.getElementById('dt_ini').value      = injetado.dt_ini;
        if (injetado.dt_fim)      document.getElementById('dt_fim').value      = injetado.dt_fim;
        renderizarResultados(injetado.resultados, injetado.dt_ini || dtIni, injetado.dt_fim || dtFim);
      }}
    
      if (injetado.acao === 'metricas_resultado' && injetado.metricas) {{
        state.resultados = injetado.resultados || state.resultados;
        state.produto    = injetado.produto;
        state.metricas   = injetado.metricas;
    
        if (injetado.dt_ini) document.getElementById('dt_ini').value = injetado.dt_ini;
        if (injetado.dt_fim) document.getElementById('dt_fim').value = injetado.dt_fim;
    
        // Restaura campos de busca e identificacao
        if (injetado.busca_nome)  document.getElementById('busca_nome').value  = injetado.busca_nome;
        if (injetado.busca_email) document.getElementById('busca_email').value = injetado.busca_email;
        if (injetado.produto) {{
          document.getElementById('nome_display').value = (injetado.produto.nome_produto || '').replace(/_/g,' ');
          document.getElementById('tipo_produto').value  = injetado.produto.tipo_produto || '';
        }}
    
        // Re-renderiza a tabela de resultados com a linha selecionada marcada
        if (Array.isArray(injetado.resultados)) {{
          renderizarResultados(injetado.resultados, injetado.dt_ini || dtIni, injetado.dt_fim || dtFim);
          const idx = injetado.resultados.findIndex(r => r.nome_produto === injetado.produto?.nome_produto);
          if (idx >= 0) {{
            setTimeout(() => {{
              const tr = document.querySelector(`#tbody-resultados tr[data-idx="${{idx}}"]`);
              if (tr) tr.classList.add('selected');
            }}, 50);
          }}
        }}
    
        atualizarMetricasSidebar(injetado.metricas, injetado.dt_ini || dtIni, injetado.dt_fim || dtFim);
        atualizarCardsDimensoes(injetado.metricas);
        document.getElementById('secao-metricas').style.display  = '';
        document.getElementById('secao-dimensoes').style.display = '';
        document.getElementById('produto-selecionado-label').textContent =
          (injetado.produto?.nome_produto || '').replace(/_/g,' ');
      }}
    }})();
    </script>
    </body>
    </html>
    """)


_renderizar_painel()



# ==============================================================================
# ==============================================================================
# CELULA 4.5 - Busca de produto e carregamento de metricas
#
# Execute esta celula sempre que quiser:
#   a) Buscar produtos: preencha o widget "Nome do Produto (busca parcial)"
#      e opcionalmente "E-mail do Responsavel", depois execute esta celula.
#   b) Carregar metricas: apos ver os resultados no painel, copie o nome
#      exato para o widget "Produto Selecionado (nome exato)", preencha
#      "Tipo do Produto" e "Query de Origem", depois execute esta celula.
#
# Em ambos os casos o painel HTML e re-renderizado com os dados reais.
# ==============================================================================

_busca_nome  = dbutils.widgets.get("busca_nome").strip()
_busca_email = dbutils.widgets.get("busca_email").strip()
_dt_ini_p    = dbutils.widgets.get("dt_ini")
_dt_fim_p    = dbutils.widgets.get("dt_fim")
_nome_prod   = dbutils.widgets.get("nome_produto_selecionado").strip()
_tipo_prod   = dbutils.widgets.get("tipo_produto_selecionado").strip()
_query_orig  = dbutils.widgets.get("query_origem_selecionada").strip()

_m       = ModelDados(params={}, spark=spark)
_injected    = {}
_resultados  = []

if _busca_nome:
    # GenQuery primeiro; se sem resultado, tenta BI
    _resultados = _m.search_nome_produto(_busca_nome, _busca_email)
    if not _resultados:
        _resultados = _m.search_nome_produto_bi(_busca_nome)
        for r in _resultados:
            r["_query"] = "BI"
    else:
        for r in _resultados:
            r["_query"] = "GenQuery"

    print(f"Busca: {len(_resultados)} produto(s) para '{_busca_nome}'")
    for r in _resultados:
        print(f"  [{r.get('_query','?')}]  {r.get('nome_produto','')}  |  {r.get('tipo_produto','')}")

    _injected = {
        "acao":        "busca_resultado",
        "busca_nome":  _busca_nome,
        "busca_email": _busca_email,
        "dt_ini":      _dt_ini_p,
        "dt_fim":      _dt_fim_p,
        "resultados":  _resultados,
    }
else:
    print("Widget 'busca_nome' vazio — sem busca executada.")

if _nome_prod:
    # Coleta metricas para o produto selecionado
    _metricas = _m.coletar_metricas(_nome_prod, _tipo_prod, _dt_ini_p, _dt_fim_p)

    # Serializa escalares Spark/numpy para tipos Python nativos
    def _serial(obj):
        if hasattr(obj, "item"):  return obj.item()
        if isinstance(obj, dict): return {k: _serial(v) for k, v in obj.items()}
        return obj
    _metricas = _serial(_metricas)

    print(f"\nMetricas para '{_nome_prod}':") 
    for dim, val in _metricas.items():
        print(f"  {dim}: count={val.get('count', 0)}  query={val.get('query', '?')}")

    _injected = {
        "acao":        "metricas_resultado",
        "busca_nome":  _busca_nome,
        "busca_email": _busca_email,
        "dt_ini":      _dt_ini_p,
        "dt_fim":      _dt_fim_p,
        "produto":     {"nome_produto": _nome_prod, "tipo_produto": _tipo_prod, "_query": _query_orig},
        "metricas":    _metricas,
        "resultados":  _resultados,
    }
elif not _busca_nome:
    print("Nenhum widget preenchido. Preencha 'busca_nome' ou 'nome_produto_selecionado'.")

# Re-renderiza o painel com o estado injetado via f-string no HTML
_injected_state_json = json.dumps(_injected, ensure_ascii=False, default=str)
_renderizar_painel(_injected_state_json)

# ==============================================================================
# CELULA 5 - Leitura do payload e geracao do PDF
#
# Execute esta celula apos clicar em "Gerar PDF" no painel acima.
# ==============================================================================

# Widget json_payload: cole aqui o JSON copiado do campo "Gerar PDF" no painel HTML.
# Se o widget nao existir ainda, ele sera criado vazio na primeira execucao desta celula.
try:
    dbutils.widgets.text("json_payload", "", "JSON Payload (cole aqui e reexecute)")
except Exception:
    pass  # widget ja existe

_raw_payload = dbutils.widgets.get("json_payload").strip()

if not _raw_payload or _raw_payload in ("{}", ""):
    print("Widget 'json_payload' vazio.")
    print("1. No painel da Celula 4, clique em 'Gerar PDF'.")
    print("2. Copie o JSON exibido no campo que aparecer.")
    print("3. Cole aqui no widget 'json_payload' acima.")
    print("4. Execute esta celula novamente.")
else:
    payload = json.loads(_raw_payload)

    nome    = payload.get("nome_produto", "")
    tipo    = payload.get("tipo_produto", "")
    dt_ini  = payload.get("dt_ini", (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"))
    dt_fim  = payload.get("dt_fim", datetime.now().strftime("%Y-%m-%d"))

    m_dados = ModelDados(params=payload, spark=spark)

    # Executa as queries reais com o produto e periodo selecionados
    metricas_reais = m_dados.coletar_metricas(nome, tipo, dt_ini, dt_fim)

    # Mescla os valores editados pelo usuario com os valores reais (usuario tem prioridade)
    valores_usuario = payload.get("valores", {})
    for dim, val in valores_usuario.items():
        if val and dim in metricas_reais:
            try:
                metricas_reais[dim]["count"] = int(val)
            except (ValueError, TypeError):
                pass

    # Constroi o context_ns com numeracao dinamica e textos customizados
    context_ns = m_dados.build_context_ns(payload, metricas_reais)

    rendered = render_template_dotted(template_text, context_ns)

    nome_display = context_ns["prod"].get("nome_produto", "")
    ana_esp      = context_ns["prod"].get("ana_esp", "Analista")
    autor_val    = context_ns["prod"].get("autor", "")

    title   = "Parecer Tecnico de Qualidade de Dados"
    assunto = f"Avaliacao da Qualidade - {nome_display}"
    autor   = f"Data Governance {ana_esp} - {autor_val}"

    pdf_bytes = make_pdf_bytes(rendered_text=rendered, title=title, autor=autor, assunto=assunto)
    b64       = base64.b64encode(pdf_bytes).decode("utf-8")
    filename  = (
        f"Parecer_Qualidade_{sanitize_filename(nome_display)}"
        f"_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    )

    displayHTML(f"""
    <div style="background:#fff;border:1px solid #E2E2E2;border-radius:10px;padding:28px;max-width:600px;margin:20px auto;font-family:'Segoe UI',sans-serif;box-shadow:0 4px 12px rgba(0,0,0,0.08)">
      <div style="background:linear-gradient(135deg,#8B0000,#CC0000);color:#fff;border-radius:8px;padding:18px 22px;margin-bottom:22px">
        <div style="font-size:16px;font-weight:700">Documento Gerado</div>
        <div style="font-size:12px;opacity:0.85;margin-top:3px">{nome_display}</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:20px;font-size:13px">
        <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Tamanho</div>
          <div style="font-weight:600">{len(pdf_bytes)/1024:.1f} KB</div>
        </div>
        <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Formato</div>
          <div style="font-weight:600">A4 Retrato</div>
        </div>
        <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
          <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Gerado em</div>
          <div style="font-weight:600">{datetime.now().strftime("%d/%m/%Y %H:%M")}</div>
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:center">
        <a href="data:application/pdf;base64,{b64}" download="{filename}"
           style="background:#CC0000;color:#fff;padding:12px 28px;text-decoration:none;border-radius:6px;font-weight:700;font-size:14px">
          Baixar PDF
        </a>
        <button onclick="(function(){{
          var b=atob('{b64}');var n=new Array(b.length);
          for(var i=0;i<b.length;i++)n[i]=b.charCodeAt(i);
          var blob=new Blob([new Uint8Array(n)],{{type:'application/pdf'}});
          var u=URL.createObjectURL(blob);
          var w=window.open(u,'_blank');
          if(!w)alert('Bloqueio de pop-up detectado. Use o botao Baixar PDF.');
        }})()"
          style="background:#1A7A4A;color:#fff;padding:12px 28px;border:none;border-radius:6px;font-weight:700;font-size:14px;cursor:pointer">
          Visualizar em Nova Aba
        </button>
      </div>
    </div>
    """)
