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
# CELULA 4 - Painel interativo (ipywidgets)
#
# Execute esta celula UMA VEZ. O painel completo aparece abaixo da celula.
# Tudo funciona em sequencia sem precisar executar outras celulas:
#   1. Preencha nome do produto e clique Buscar
#   2. Clique no produto desejado na tabela de resultados
#   3. Ajuste dimensoes, valores e textos
#   4. Preencha autor e clique Gerar PDF
#   5. Baixe o PDF diretamente no painel
# ==============================================================================

import ipywidgets as _W
import threading  as _threading
from IPython.display import display as _display, HTML as _HTML

# ── Widget de comunicacao JS -> Python ─────────────────────────────────────────
# O JS executa _cmd_widget.value = '...' via kernel.execute().
# O observe() Python dispara imediatamente.
_cmd_widget = _W.Text(value='', continuous_update=False,
                      layout=_W.Layout(display='none'))

# ── Output principal ───────────────────────────────────────────────────────────
_out = _W.Output()

# ── Estado da sessao ───────────────────────────────────────────────────────────
_state = {
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

_templates_json_str = json.dumps(SECTION_TEMPLATES_DEFAULT, ensure_ascii=False)

# ── Helpers ────────────────────────────────────────────────────────────────────
def _serial(obj):
    if hasattr(obj, 'item'):  return obj.item()
    if isinstance(obj, dict): return {k: _serial(v) for k, v in obj.items()}
    if isinstance(obj, list): return [_serial(v) for v in obj]
    return obj

def _render_panel(extra_json='{}'):
    _out.clear_output(wait=True)
    with _out:
        _display(_HTML(_build_panel_html(extra_json)))

def _banner(msg, bg='#FEF9C3', border='#F59E0B', fg='#78350F'):
    return (
        f'<div style="font-family:Segoe UI,sans-serif;font-size:13px;'
        f'padding:10px 16px;background:{bg};border-left:4px solid {border};'
        f'border-radius:6px;margin:6px 0;color:{fg}">{msg}</div>'
    )

# ── Callbacks ──────────────────────────────────────────────────────────────────
def _on_cmd(change):
    val = (change.get('new') or '').strip()
    if not val:
        return
    _cmd_widget.value = ''          # limpa imediatamente
    try:
        cmd = json.loads(val)
    except Exception:
        return
    acao = cmd.get('acao', '')
    if   acao == 'busca':        _threading.Thread(target=_handle_busca,    args=(cmd,), daemon=True).start()
    elif acao == 'metricas':     _threading.Thread(target=_handle_metricas, args=(cmd,), daemon=True).start()
    elif acao == 'gerar_pdf':    _threading.Thread(target=_handle_pdf,      args=(cmd,), daemon=True).start()
    elif acao == 'update_state': _handle_state(cmd)

_cmd_widget.observe(_on_cmd, names=['value'])


def _handle_busca(cmd):
    nome  = cmd.get('busca_nome', '').strip()
    email = cmd.get('busca_email', '').strip()
    _state['dt_ini'] = cmd.get('dt_ini', _state['dt_ini'])
    _state['dt_fim'] = cmd.get('dt_fim', _state['dt_fim'])
    with _out:
        _display(_HTML(_banner(f'Buscando "{nome}"...')))
    m = ModelDados(params={}, spark=spark)
    res = m.search_nome_produto(nome, email)
    if not res:
        res = m.search_nome_produto_bi(nome)
        for r in res: r['_query'] = 'BI'
    else:
        for r in res: r['_query'] = 'GenQuery'
    _state['resultados'] = _serial(res)
    _render_panel(json.dumps({
        'acao': 'busca_resultado',
        'busca_nome': nome, 'busca_email': email,
        'dt_ini': _state['dt_ini'], 'dt_fim': _state['dt_fim'],
        'resultados': _state['resultados'],
    }, ensure_ascii=False, default=str))


def _handle_metricas(cmd):
    p      = cmd.get('produto', {})
    nome   = p.get('nome_produto', '')
    tipo   = p.get('tipo_produto', '')
    dt_ini = cmd.get('dt_ini', _state['dt_ini'])
    dt_fim = cmd.get('dt_fim', _state['dt_fim'])
    _state['produto'] = p
    _state['dt_ini']  = dt_ini
    _state['dt_fim']  = dt_fim
    with _out:
        _display(_HTML(_banner(f'Carregando metricas para <strong>{nome}</strong>...')))
    m = ModelDados(params={}, spark=spark)
    metricas = _serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
    _state['metricas'] = metricas
    _render_panel(json.dumps({
        'acao': 'metricas_resultado',
        'dt_ini': dt_ini, 'dt_fim': dt_fim,
        'produto': p, 'metricas': metricas,
        'resultados': _state['resultados'],
        'busca_nome':  cmd.get('busca_nome', ''),
        'busca_email': cmd.get('busca_email', ''),
    }, ensure_ascii=False, default=str))


def _handle_state(cmd):
    for k in ('dimensoes', 'valores', 'textos', 'autor', 'ana_esp', 'nome_display'):
        if k in cmd: _state[k] = cmd[k]


def _handle_pdf(cmd):
    _handle_state(cmd)
    p            = _state.get('produto') or {}
    nome         = p.get('nome_produto', '')
    tipo         = p.get('tipo_produto', '')
    dt_ini       = _state['dt_ini']
    dt_fim       = _state['dt_fim']
    autor_val    = _state.get('autor', '')
    ana_esp      = _state.get('ana_esp', 'Analista')
    nome_display = _state.get('nome_display') or nome.replace('_', ' ')

    with _out:
        _display(_HTML(_banner('Gerando PDF, aguarde...')))

    m = ModelDados(params={}, spark=spark)
    metricas_reais = _serial(m.coletar_metricas(nome, tipo, dt_ini, dt_fim))
    for dim, val in _state.get('valores', {}).items():
        if val and dim in metricas_reais and dim != 'disponibilidade':
            try:    metricas_reais[dim]['count'] = int(val)
            except: pass

    payload = {
        'autor': autor_val, 'ana_esp': ana_esp,
        'nome_display': nome_display, 'nome_produto': nome,
        'tipo_produto': tipo, 'dt_ini': dt_ini, 'dt_fim': dt_fim,
        'dimensoes': _state.get('dimensoes', {}),
        'valores':   _state.get('valores', {}),
        'textos':    _state.get('textos', {}),
    }
    ctx       = m.build_context_ns(payload, metricas_reais)
    rendered  = render_template_dotted(template_text, ctx)
    title     = 'Parecer Tecnico de Qualidade de Dados'
    assunto   = f'Avaliacao da Qualidade - {nome_display}'
    autor_str = f'Data Governance {ana_esp} - {autor_val}'

    pdf_bytes = make_pdf_bytes(rendered_text=rendered, title=title,
                               autor=autor_str, assunto=assunto)
    b64       = base64.b64encode(pdf_bytes).decode('utf-8')
    filename  = (f'Parecer_Qualidade_{sanitize_filename(nome_display)}'
                 f'_{datetime.now().strftime("%Y%m%d_%H%M%S")}.pdf')
    kb        = f'{len(pdf_bytes)/1024:.1f} KB'
    ts        = datetime.now().strftime('%d/%m/%Y %H:%M')

    _out.clear_output(wait=True)
    with _out:
        _display(_HTML(f"""
<div style="background:#fff;border:1px solid #E2E2E2;border-radius:10px;padding:28px;
            max-width:640px;margin:20px auto;font-family:'Segoe UI',sans-serif;
            box-shadow:0 4px 12px rgba(0,0,0,.08)">
  <div style="background:linear-gradient(135deg,#8B0000,#CC0000);color:#fff;
              border-radius:8px;padding:18px 22px;margin-bottom:22px">
    <div style="font-size:16px;font-weight:700">Documento Gerado</div>
    <div style="font-size:12px;opacity:.85;margin-top:3px">{nome_display}</div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:20px;font-size:13px">
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Tamanho</div>
      <div style="font-weight:600">{kb}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Produto</div>
      <div style="font-weight:600;font-size:11px">{nome_display[:28]}</div>
    </div>
    <div style="background:#F5F5F5;border-radius:6px;padding:10px 12px">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:#6B6B6B;margin-bottom:2px">Gerado em</div>
      <div style="font-weight:600">{ts}</div>
    </div>
  </div>
  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <a href="data:application/pdf;base64,{b64}" download="{filename}"
       style="background:#CC0000;color:#fff;padding:12px 28px;text-decoration:none;
              border-radius:6px;font-weight:700;font-size:14px">Baixar PDF</a>
    <button onclick="(function(){{var b=atob('{b64}');var n=new Uint8Array(b.length);
      for(var i=0;i<b.length;i++)n[i]=b.charCodeAt(i);
      var blob=new Blob([n],{{type:'application/pdf'}});
      var u=URL.createObjectURL(blob);var w=window.open(u,'_blank');
      if(!w)alert('Pop-up bloqueado. Use Baixar PDF.');}})()"
      style="background:#1A7A4A;color:#fff;padding:12px 28px;border:none;
             border-radius:6px;font-weight:700;font-size:14px;cursor:pointer">
      Visualizar em Nova Aba
    </button>
  </div>
</div>"""))


# ── HTML do painel ─────────────────────────────────────────────────────────────
def _build_panel_html(injected_json='{}'):
    dt_ini   = _state['dt_ini']
    dt_fim   = _state['dt_fim']
    tpl_js   = _templates_json_str.replace('\\', '\\\\').replace('`', '\\`').replace('</script>', '<\\/script>')
    inj_safe = injected_json.replace('\\', '\\\\').replace('`', '\\`').replace('</script>', '<\\/script>')

    return f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
:root{{--red:#CC0000;--dark-red:#8B0000;--green:#1A7A4A;--border:#E2E2E2;--radius:8px;
       --n50:#FAFAFA;--n100:#F5F5F5;--n300:#BDBDBD;--n500:#6B6B6B;--n700:#3D3D3D;--n900:#1A1A1A}}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:var(--n700);background:#F0F0F0;padding:16px}}
.wrap{{max-width:900px;margin:0 auto}}
.hdr{{background:linear-gradient(135deg,var(--dark-red),var(--red));color:#fff;border-radius:var(--radius);padding:20px 28px;margin-bottom:16px}}
.hdr h1{{font-size:18px;font-weight:700}}.hdr p{{font-size:12px;opacity:.85;margin-top:2px}}
.sec{{background:#fff;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden}}
.sec-hdr{{padding:13px 20px;font-weight:700;font-size:13px;cursor:pointer;display:flex;
          justify-content:space-between;align-items:center;user-select:none;
          border-bottom:1px solid var(--border);background:var(--n50)}}
.sec-hdr:hover{{background:#EFEFEF}}
.sec-body{{padding:20px}}
.row{{display:grid;gap:12px;margin-bottom:14px}}
.c2{{grid-template-columns:1fr 1fr}}.c3{{grid-template-columns:1fr 1fr 1fr}}
.fg{{display:flex;flex-direction:column;gap:4px}}
label{{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--n500)}}
input[type=text],input[type=date],input[type=number],select,textarea{{
  border:1px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;
  font-family:inherit;color:var(--n700);background:#fff;width:100%;outline:none;transition:border-color .15s}}
input:focus,select:focus,textarea:focus{{border-color:var(--red)}}
.btn{{padding:9px 20px;border-radius:6px;border:none;font-weight:700;font-size:13px;cursor:pointer;transition:opacity .15s}}
.btn:hover{{opacity:.88}}.btn-p{{background:var(--red);color:#fff}}
.btn-s{{background:var(--n100);color:var(--n700);border:1px solid var(--border)}}
.btn-search{{background:var(--dark-red);color:#fff;padding:8px 18px;border-radius:6px;border:none;font-weight:700;font-size:13px;cursor:pointer}}
table{{width:100%;border-collapse:collapse;font-size:12px}}
th{{background:var(--n50);padding:8px 12px;text-align:left;font-weight:700;font-size:11px;
    text-transform:uppercase;letter-spacing:.3px;color:var(--n500);border-bottom:2px solid var(--border)}}
td{{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle}}
tbody tr{{cursor:pointer;transition:background .1s}}
tbody tr:hover{{background:#FEF2F2}}tbody tr.selected{{background:#FFF0F0;outline:2px solid var(--red)}}
.tag{{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700}}
.tgq{{background:#DBEAFE;color:#1E40AF}}.tbi{{background:#FEF3C7;color:#92400E}}
.mcards{{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:12px}}
.mc{{background:var(--n50);border:1px solid var(--border);border-radius:var(--radius);padding:12px;text-align:center}}
.mc .dn{{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--n500);margin-bottom:6px}}
.mc .dv{{font-size:20px;font-weight:700;color:var(--n900)}}.mc .dv.zero{{color:var(--n300)}}
.dcards{{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}}
.dcard{{border:2px solid var(--border);border-radius:var(--radius);padding:10px 16px;cursor:pointer;
        transition:all .15s;user-select:none;display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px}}
.dcard.on{{border-color:var(--red);background:#FFF0F0;color:var(--red)}}
.tabs{{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:16px;flex-wrap:wrap}}
.tab{{padding:8px 18px;cursor:pointer;font-weight:600;font-size:12px;border-bottom:2px solid transparent;
      margin-bottom:-2px;color:var(--n500);transition:all .15s}}
.tab.on{{color:var(--red);border-bottom-color:var(--red)}}
.tc{{display:none}}.tc.on{{display:block}}
.abar{{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}}
.empty{{text-align:center;padding:24px;color:var(--n300);font-size:13px}}
.chip{{display:inline-block;background:var(--n100);border:1px solid var(--border);border-radius:4px;
       padding:2px 7px;font-family:monospace;font-size:11px;color:var(--n700);margin:2px 1px;cursor:pointer}}
.chip:hover{{background:#FFF0F0;border-color:var(--red)}}
.spin{{display:none;align-items:center;gap:8px;font-size:13px;color:var(--n500);padding:8px 0}}
.spin.on{{display:flex}}
.spinner{{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--red);
          border-radius:50%;animation:rot .7s linear infinite}}
@keyframes rot{{to{{transform:rotate(360deg)}}}}
.nota{{font-size:11px;color:var(--n500);font-style:italic}}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr"><h1>Parecer Tecnico de Qualidade de Dados</h1><p>Data Governance &mdash; Bradesco</p></div>

<!-- 1. Identificacao -->
<div class="sec">
  <div class="sec-hdr" onclick="tog('s1')"><span>1. Identificacao do Documento</span><span id="a-s1">&#9660;</span></div>
  <div class="sec-body" id="s1">
    <div class="row c2">
      <div class="fg"><label>Autor</label>
        <input type="text" id="autor" placeholder="Nome do analista" oninput="syncState()"></div>
      <div class="fg"><label>Cargo</label>
        <select id="ana_esp" onchange="syncState()">
          <option value="Analista">Analista</option>
          <option value="Especialista">Especialista</option>
        </select></div>
    </div>
    <div class="row c2">
      <div class="fg"><label>Nome do Produto (exibicao)</label>
        <input type="text" id="nome_display" placeholder="Preenchido automaticamente" oninput="syncState()"></div>
      <div class="fg"><label>Tipo do Produto</label>
        <input type="text" id="tipo_produto" placeholder="Preenchido automaticamente" oninput="syncState()"></div>
    </div>
  </div>
</div>

<!-- 2. Busca -->
<div class="sec">
  <div class="sec-hdr" onclick="tog('s2')"><span>2. Busca de Produto</span><span id="a-s2">&#9660;</span></div>
  <div class="sec-body" id="s2">
    <div class="row c3">
      <div class="fg" style="grid-column:span 2"><label>Nome do Produto</label>
        <div style="display:flex;gap:8px">
          <input type="text" id="busca_nome" placeholder="Digite parte do nome..." style="flex:1"
                 onkeydown="if(event.key==='Enter')buscar()">
          <button class="btn-search" onclick="buscar()">Buscar</button>
        </div>
      </div>
      <div class="fg"><label>E-mail (so GenQuery)</label>
        <input type="text" id="busca_email" placeholder="Filtro opcional"></div>
    </div>
    <div class="spin" id="sp-busca"><div class="spinner"></div><span id="sp-txt">Aguarde...</span></div>
    <div id="resultados-busca"><div class="empty">Nenhuma busca realizada ainda.</div></div>
  </div>
</div>

<!-- 3. Periodo -->
<div class="sec">
  <div class="sec-hdr" onclick="tog('s3')"><span>3. Periodo de Analise</span><span id="a-s3">&#9660;</span></div>
  <div class="sec-body" id="s3">
    <div class="row c3">
      <div class="fg"><label>Data Inicio</label><input type="date" id="dt_ini" value="{dt_ini}"></div>
      <div class="fg"><label>Data Fim</label><input type="date" id="dt_fim" value="{dt_fim}"></div>
      <div class="fg"><label>Atalho</label>
        <select onchange="atalho(this.value)">
          <option value="">Selecione...</option>
          <option value="7">7 dias</option><option value="30">30 dias</option>
          <option value="90">90 dias</option><option value="180">180 dias</option>
          <option value="365">365 dias</option>
        </select>
      </div>
    </div>
  </div>
</div>

<!-- 4. Metricas -->
<div class="sec" id="sec-m" style="display:none">
  <div class="sec-hdr" onclick="tog('s4')">
    <span>4. Metricas &mdash; <span id="prod-label" style="color:var(--red)"></span></span>
    <span id="a-s4">&#9660;</span>
  </div>
  <div class="sec-body" id="s4"><div class="mcards" id="mcards"></div></div>
</div>

<!-- 5. Dimensoes -->
<div class="sec" id="sec-d" style="display:none">
  <div class="sec-hdr" onclick="tog('s5')"><span>5. Dimensoes a Incluir</span><span id="a-s5">&#9660;</span></div>
  <div class="sec-body" id="s5">
    <p class="nota" style="margin-bottom:12px">Marcadas automaticamente quando count &gt; 0. Ajuste livremente.</p>
    <div class="dcards" id="dcards"></div>
  </div>
</div>

<!-- 6. Valores -->
<div class="sec" id="sec-v" style="display:none">
  <div class="sec-hdr" onclick="tog('s6')"><span>6. Revisao de Valores</span><span id="a-s6">&#9660;</span></div>
  <div class="sec-body" id="s6"><div id="val-inputs"></div></div>
</div>

<!-- 7. Textos -->
<div class="sec" id="sec-t" style="display:none">
  <div class="sec-hdr" onclick="tog('s7')"><span>7. Textos das Secoes</span><span id="a-s7">&#9660;</span></div>
  <div class="sec-body" id="s7">
    <div class="tabs" id="tabs"></div>
    <div id="tconts"></div>
  </div>
</div>

<!-- 8. Gerar -->
<div class="sec">
  <div class="sec-hdr" onclick="tog('s8')"><span>8. Gerar Documento</span><span id="a-s8">&#9660;</span></div>
  <div class="sec-body" id="s8">
    <div class="abar">
      <span style="font-size:12px;color:var(--n500)">Revise as secoes e clique em Gerar PDF.</span>
      <div style="display:flex;gap:8px">
        <button class="btn btn-s" onclick="resetar()">Limpar tudo</button>
        <button class="btn btn-p" onclick="gerarPDF()">Gerar PDF</button>
      </div>
    </div>
    <div class="spin" id="sp-pdf" style="margin-top:12px"><div class="spinner"></div><span>Gerando PDF...</span></div>
  </div>
</div>
</div>

<script>
const DIMS = ['completude','consistencia','disponibilidade','unicidade','variacao'];
const DLBL = {{completude:'Completude',consistencia:'Consistencia',
              disponibilidade:'Disponibilidade',unicidade:'Unicidade',variacao:'Variacao'}};
const TPLS = JSON.parse(`{tpl_js}`);

let st = {{res:[],prod:null,met:{{}},dim:{{}},val:{{}},txt:{{}}}};

// Envia comando ao Python via kernel.execute() — altera _cmd_widget.value
function py(obj) {{
  try {{
    const code = '_cmd_widget.value = ' + JSON.stringify(JSON.stringify(obj));
    Jupyter.notebook.kernel.execute(code, {{}}, {{silent:true, store_history:false}});
  }} catch(e) {{
    console.error('py() falhou:', e);
  }}
}}

function buscar() {{
  const nome = document.getElementById('busca_nome').value.trim();
  if (!nome) {{ alert('Digite o nome do produto.'); return; }}
  spin('busca', true, 'Buscando produtos...');
  py({{acao:'busca', busca_nome:nome, busca_email:document.getElementById('busca_email').value.trim(),
      dt_ini:document.getElementById('dt_ini').value, dt_fim:document.getElementById('dt_fim').value}});
}}

function selProd(idx) {{
  document.querySelectorAll('#tbody-r tr').forEach(t=>t.classList.remove('selected'));
  const tr = document.querySelector(`#tbody-r tr[data-i="${{idx}}"]`);
  if (tr) tr.classList.add('selected');
  const p = st.res[idx];
  st.prod = p;
  document.getElementById('nome_display').value = (p.nome_produto||'').replace(/_/g,' ');
  document.getElementById('tipo_produto').value  = p.tipo_produto||'';
  spin('busca', true, 'Carregando metricas...');
  py({{acao:'metricas', produto:p,
      dt_ini:document.getElementById('dt_ini').value,
      dt_fim:document.getElementById('dt_fim').value,
      busca_nome:document.getElementById('busca_nome').value.trim(),
      busca_email:document.getElementById('busca_email').value.trim()}});
}}

function gerarPDF() {{
  if (!st.prod) {{ alert('Selecione um produto primeiro.'); return; }}
  if (!document.getElementById('autor').value.trim()) {{ alert('Preencha o campo Autor.'); return; }}
  DIMS.filter(d=>st.dim[d]).forEach(d=>{{
    const el=document.getElementById('tpl-'+d); if(el) st.txt[d]=el.value;
  }});
  const de=document.getElementById('disp-txt'); if(de) st.val['disponibilidade']=de.value;
  spin('pdf', true);
  py({{acao:'gerar_pdf',
      autor:document.getElementById('autor').value.trim(),
      ana_esp:document.getElementById('ana_esp').value,
      nome_display:document.getElementById('nome_display').value.trim(),
      dimensoes:st.dim, valores:st.val, textos:st.txt}});
}}

function syncState() {{
  py({{acao:'update_state',
      autor:document.getElementById('autor').value.trim(),
      ana_esp:document.getElementById('ana_esp').value,
      nome_display:document.getElementById('nome_display').value.trim()}});
}}

// Renderizacao da tabela de resultados
function renderRes(rows) {{
  spin('busca', false);
  const w = document.getElementById('resultados-busca');
  if (!rows||!rows.length) {{ w.innerHTML='<div class="empty">Nenhum produto encontrado.</div>'; return; }}
  w.innerHTML=`<div style="font-size:11px;color:var(--n500);margin-bottom:8px">${{rows.length}} resultado(s)</div>
    <table><thead><tr><th>Nome</th><th>Tipo</th><th>E-mail</th><th>Criado</th><th>Query</th></tr></thead>
    <tbody id="tbody-r"></tbody></table>`;
  const tb=document.getElementById('tbody-r');
  rows.forEach((r,i)=>{{
    const tr=document.createElement('tr'); tr.setAttribute('data-i',i);
    const qc=r._query==='BI'?'tbi':'tgq';
    tr.innerHTML=`<td><strong>${{(r.nome_produto||'').replace(/_/g,' ')}}</strong></td>
      <td>${{r.tipo_produto||'-'}}</td>
      <td>${{r.email_responsavel||'<span style="color:var(--n300)">N/D</span>'}}</td>
      <td>${{r.data_criacao||'-'}}</td>
      <td><span class="tag ${{qc}}">${{r._query}}</span></td>`;
    tr.addEventListener('click',()=>selProd(i));
    tb.appendChild(tr);
  }});
}}

function renderMet(met) {{
  const w=document.getElementById('mcards'); w.innerHTML='';
  DIMS.forEach(d=>{{
    const m=met[d]||{{}};
    const v=d==='disponibilidade'?'—':(m.count||0);
    const cls=(v===0||v==='0')?'dv zero':'dv';
    const qc=(m.query||'').toLowerCase()==='bi'?'tbi':'tgq';
    const qt=d==='disponibilidade'?'':
      `<span class="tag ${{qc}}" style="margin-top:4px;display:inline-block">${{m.query||''}}</span>`;
    w.innerHTML+=`<div class="mc"><div class="dn">${{DLBL[d]}}</div><div class="${{cls}}">${{v}}</div>${{qt}}</div>`;
  }});
}}

function renderDim(met) {{
  const w=document.getElementById('dcards'); w.innerHTML='';
  DIMS.forEach(d=>{{
    const m=met[d]||{{}};
    const auto=d==='disponibilidade'?false:(m.count||0)>0;
    if(st.dim[d]===undefined) st.dim[d]=auto;
    const on=st.dim[d];
    const dv=document.createElement('div');
    dv.className='dcard'+(on?' on':'');
    dv.innerHTML=`<input type="checkbox" id="ck-${{d}}" style="accent-color:var(--red);width:15px;height:15px"
      ${{on?'checked':''}}><label for="ck-${{d}}" style="cursor:pointer">${{DLBL[d]}}</label>`;
    dv.addEventListener('click',e=>{{
      if(e.target.tagName==='LABEL') return;
      st.dim[d]=!st.dim[d];
      dv.classList.toggle('on',st.dim[d]);
      document.getElementById('ck-'+d).checked=st.dim[d];
      renderVal(st.met); renderTxt(st.met);
    }});
    w.appendChild(dv);
  }});
}}

function renderVal(met) {{
  const w=document.getElementById('val-inputs'); w.innerHTML='';
  const act=DIMS.filter(d=>st.dim[d]);
  if(!act.length){{ document.getElementById('sec-v').style.display='none'; return; }}
  document.getElementById('sec-v').style.display='';
  const g=document.createElement('div');
  g.style.cssText='display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px';
  act.forEach(d=>{{
    const m=met[d]||{{}};
    const fg=document.createElement('div'); fg.className='fg';
    if(d==='disponibilidade'){{
      fg.innerHTML=`<label>Texto Disponibilidade</label>
        <textarea id="disp-txt" rows="4" style="resize:vertical">${{st.val['disponibilidade']||''}}</textarea>`;
    }}else{{
      const cur=st.val[d]!==undefined?st.val[d]:(m.count||0);
      fg.innerHTML=`<label>${{DLBL[d]}}</label>
        <input type="number" id="vi-${{d}}" value="${{cur}}" oninput="st.val['${{d}}']=this.value">`;
    }}
    g.appendChild(fg);
  }});
  w.appendChild(g);
}}

function renderTxt(met) {{
  const tw=document.getElementById('tabs'); tw.innerHTML='';
  const cw=document.getElementById('tconts'); cw.innerHTML='';
  const act=DIMS.filter(d=>st.dim[d]);
  if(!act.length){{ document.getElementById('sec-t').style.display='none'; return; }}
  document.getElementById('sec-t').style.display='';
  act.forEach((d,i)=>{{
    const tab=document.createElement('div');
    tab.className='tab'+(i===0?' on':''); tab.textContent=DLBL[d];
    tab.addEventListener('click',()=>{{
      document.querySelectorAll('.tab').forEach(t=>t.classList.remove('on'));
      document.querySelectorAll('.tc').forEach(t=>t.classList.remove('on'));
      tab.classList.add('on');
      document.getElementById('tc-'+d).classList.add('on');
    }});
    tw.appendChild(tab);
    const tpl=st.txt[d]||TPLS[d]||'';
    const vs=d==='completude'
      ?['{{numero}}','{{Valor_Completude}}','{{Pct_Completude}}','{{Lowest3_Completude}}']
      :d==='disponibilidade'?['{{numero}}','{{Valor_Disponibilidade}}']
      :['{{numero}}',`{{Valor_${{DLBL[d]}}}}`];
    const chips=vs.map(v=>`<span class="chip" onclick="ins('tpl-${{d}}','${{v}}')">${{v}}</span>`).join('');
    const dc=document.createElement('div');
    dc.className='tc'+(i===0?' on':''); dc.id='tc-'+d;
    dc.innerHTML=`<p class="nota" style="margin-bottom:8px">Variaveis (clique para inserir): ${{chips}}</p>
      <textarea id="tpl-${{d}}" rows="6"
        style="width:100%;resize:vertical;font-family:monospace;font-size:12px"
        oninput="st.txt['${{d}}']=this.value">${{tpl}}</textarea>`;
    cw.appendChild(dc);
  }});
}}

function tog(id){{
  const b=document.getElementById(id), a=document.getElementById('a-'+id);
  const h=b.style.display==='none';
  b.style.display=h?'':'none'; a.innerHTML=h?'&#9660;':'&#9654;';
}}
function spin(t,on,txt){{
  const el=document.getElementById('sp-'+t); if(!el)return;
  el.classList.toggle('on',on);
  if(txt){{const s=el.querySelector('span');if(s)s.textContent=txt;}}
}}
function atalho(d){{
  if(!d)return;
  const e=new Date(), i=new Date(); i.setDate(i.getDate()-parseInt(d));
  document.getElementById('dt_fim').value=e.toISOString().substring(0,10);
  document.getElementById('dt_ini').value=i.toISOString().substring(0,10);
}}
function ins(id,v){{
  const el=document.getElementById(id); if(!el)return;
  const s=el.selectionStart,e=el.selectionEnd;
  el.value=el.value.substring(0,s)+v+el.value.substring(e);
  el.selectionStart=el.selectionEnd=s+v.length; el.focus();
  st.txt[id.replace('tpl-','')]=el.value;
}}
function resetar(){{
  if(!confirm('Limpar tudo?'))return;
  st={{res:[],prod:null,met:{{}},dim:{{}},val:{{}},txt:{{}}}};
  ['busca_nome','busca_email','autor','nome_display','tipo_produto'].forEach(id=>{{
    const el=document.getElementById(id);if(el)el.value='';
  }});
  document.getElementById('resultados-busca').innerHTML='<div class="empty">Nenhuma busca realizada ainda.</div>';
  ['sec-m','sec-d','sec-v','sec-t'].forEach(id=>document.getElementById(id).style.display='none');
}}

// Inicializacao com estado injetado pelo Python
(function(){{
  let inj;
  try{{ inj=JSON.parse(`{inj_safe}`); }}catch(e){{return;}}
  if(!inj||!inj.acao)return;

  if(inj.dt_ini)document.getElementById('dt_ini').value=inj.dt_ini;
  if(inj.dt_fim)document.getElementById('dt_fim').value=inj.dt_fim;
  if(inj.busca_nome) document.getElementById('busca_nome').value=inj.busca_nome;
  if(inj.busca_email)document.getElementById('busca_email').value=inj.busca_email;

  if(inj.acao==='busca_resultado'&&Array.isArray(inj.resultados)){{
    st.res=inj.resultados;
    renderRes(inj.resultados);
  }}

  if(inj.acao==='metricas_resultado'){{
    st.res  =inj.resultados||st.res;
    st.prod =inj.produto;
    st.met  =inj.metricas||{{}};
    if(inj.produto){{
      document.getElementById('nome_display').value=(inj.produto.nome_produto||'').replace(/_/g,' ');
      document.getElementById('tipo_produto').value =inj.produto.tipo_produto||'';
    }}
    if(Array.isArray(inj.resultados)&&inj.resultados.length){{
      renderRes(inj.resultados);
      const idx=inj.resultados.findIndex(r=>r.nome_produto===inj.produto?.nome_produto);
      if(idx>=0)setTimeout(()=>{{
        const tr=document.querySelector(`#tbody-r tr[data-i="${{idx}}"]`);
        if(tr)tr.classList.add('selected');
      }},60);
    }}
    renderMet(inj.metricas);
    renderDim(inj.metricas);
    renderVal(inj.metricas);
    renderTxt(inj.metricas);
    document.getElementById('sec-m').style.display='';
    document.getElementById('sec-d').style.display='';
    document.getElementById('prod-label').textContent=(inj.produto?.nome_produto||'').replace(/_/g,' ');
    spin('busca',false);
  }}
}})();
</script>
</body></html>"""


# ── Exibe tudo ────────────────────────────────────────────────────────────────
_display(_cmd_widget, _out)
_render_panel()
