# ==============================================================================
# CÉLULA 1 — Imports e helpers de PDF
# pip install reportlab openpyxl
# ==============================================================================

import io
import re
import base64
from datetime import datetime, date, timedelta
from typing import Any, Dict, List, Optional
from xml.sax.saxutils import escape

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer

# ──────────────────────────────────────────────────────────────────────────────
# Constantes de estilo PDF
# ──────────────────────────────────────────────────────────────────────────────
FONT_NAME = "Helvetica"

_styles = getSampleStyleSheet()
_styles.add(ParagraphStyle(
    name="DocTitle", parent=_styles["Heading1"], fontName=FONT_NAME,
    fontSize=16, leading=18, textColor=colors.HexColor("#1F2937"),
    alignment=TA_LEFT, spaceAfter=10,
))
_styles.add(ParagraphStyle(
    name="Body", parent=_styles["BodyText"], fontName=FONT_NAME,
    fontSize=10.5, leading=14, textColor=colors.HexColor("#111827"),
))
_styles.add(ParagraphStyle(
    name="Meta", parent=_styles["BodyText"], fontName=FONT_NAME,
    fontSize=9.5, leading=12, textColor=colors.HexColor("#374151"),
))
_styles.add(ParagraphStyle(
    name="Separator", parent=_styles["BodyText"], fontName=FONT_NAME,
    fontSize=8, leading=10, textColor=colors.HexColor("#6B7280"),
))

# ──────────────────────────────────────────────────────────────────────────────
# Utilitários gerais
# ──────────────────────────────────────────────────────────────────────────────

def sanitize_filename(name: str) -> str:
    clean = re.sub(r"[^\w\-]+", "_", (name or "").strip(), flags=re.UNICODE)
    return re.sub(r"_+", "_", clean).strip("_").lower() or "arquivo"

def pct(x, ndigits: int = 1) -> str:
    if x is None:
        return ""
    try:
        val = x * 100 if 0 <= x <= 1 else x
        return f"{round(val, ndigits):.{ndigits}f}"
    except Exception:
        return str(x)

def fmt_int(x) -> str:
    try:
        return f"{int(x):,}".replace(",", ".")
    except Exception:
        return str(x)

def fmt_date(d, out: str = "%d/%m/%Y") -> str:
    if d in (None, ""):
        return ""
    if isinstance(d, datetime):
        return d.strftime(out)
    if isinstance(d, date):
        return d.strftime(out)
    try:
        return datetime.fromisoformat(str(d)).strftime(out)
    except Exception:
        return str(d)

# ──────────────────────────────────────────────────────────────────────────────
# Motor de template  ${namespace.chave}
# ──────────────────────────────────────────────────────────────────────────────

_DOTTED_PAT = re.compile(r"\$\{([a-zA-Z_]\w*(?:\.[a-zA-Z_]\w*)*)\}")

def render_template_dotted(text: str, ctx_ns: dict) -> str:
    """Substitui ${prod.x} e ${sections.y} pelo valor no dicionário ctx_ns."""
    def _resolve(path: str):
        cur = ctx_ns
        for p in path.split("."):
            if isinstance(cur, dict) and p in cur:
                cur = cur[p]
            else:
                return None
        return cur

    def _repl(m):
        val = _resolve(m.group(1))
        return str(val) if val is not None else m.group(0)

    return _DOTTED_PAT.sub(_repl, text)

# ──────────────────────────────────────────────────────────────────────────────
# Gerador de PDF
# ──────────────────────────────────────────────────────────────────────────────

class _PageCounter:
    """Callback do ReportLab: adiciona cabeçalho e número de página."""

    def __init__(self) -> None:
        self.count = 0

    def __call__(self, canvas, doc) -> None:
        self.count += 1
        canvas.saveState()
        canvas.setFont(FONT_NAME, 8)
        canvas.setFillColor(colors.HexColor("#6B7280"))
        canvas.drawString(30, A4[1] - 30, "Parecer Técnico de Qualidade de Dados")
        canvas.drawRightString(A4[0] - 30, 20, f"Página {self.count}")
        canvas.restoreState()


def _render_block(raw: str, story: list) -> None:
    """Converte um bloco de texto (com bullets */-/•) em elementos ReportLab."""
    for blk in [b for b in re.split(r"\n\s*\n", raw) if b.strip()]:
        frags = []
        for ln in blk.splitlines():
            if not ln.strip():
                continue
            leading_ws = len(ln) - len(ln.lstrip(" \t"))
            text = ln.lstrip(" \t")
            bullet = None
            for marker in ("* ", "- ", "• "):
                if text.startswith(marker):
                    bullet = marker
                    text = text[len(marker):].strip()
                    break
            if bullet:
                level  = max(0, leading_ws // 2)
                indent = "&nbsp;" * (level * 2)
                frags.append(f"{indent}&bull;&nbsp;{escape(text)}")
            else:
                frags.append(escape(ln.strip()))
        story.append(Paragraph("<br/>".join(frags), _styles["Body"]))
        story.append(Spacer(1, 8))


def make_pdf_bytes(rendered_text: str, title: str, autor: str,
                   assunto: Optional[str] = None) -> bytes:
    """Recebe o texto já renderizado e devolve os bytes do PDF."""
    buf = io.BytesIO()
    doc = SimpleDocTemplate(
        buf, pagesize=A4,
        rightMargin=36, leftMargin=36, topMargin=54, bottomMargin=36,
    )
    story: list = []
    story.append(Paragraph(title, _styles["DocTitle"]))
    if assunto:
        story.append(Paragraph(f"<b>Assunto:</b> {assunto}", _styles["Meta"]))
    story.append(Paragraph(f"<b>Elaborado por:</b> {autor}", _styles["Meta"]))
    story.append(Spacer(1, 6))
    story.append(Paragraph("________________________________________", _styles["Separator"]))
    story.append(Spacer(1, 8))

    for block in [b.strip() for b in re.split(r"\n\s*\n", rendered_text) if b.strip()]:
        if block.startswith("__DISPONIBILIDADE_BLOCK__"):
            disp = block[len("__DISPONIBILIDADE_BLOCK__"):].strip()
            if disp:
                _render_block(disp, story)
        else:
            _render_block(block, story)

    counter = _PageCounter()
    doc.build(story, onFirstPage=counter, onLaterPages=counter)
    result = buf.getvalue()
    buf.close()
    return result


# ==============================================================================
# CÉLULA 2 — Templates do documento e defaults de conteúdo
# ==============================================================================

# ──────────────────────────────────────────────────────────────────────────────
# Template principal  (único ponto de edição de estrutura do documento)
# ──────────────────────────────────────────────────────────────────────────────

TEMPLATE_PARECER = """
Parecer Técnico de Qualidade de Dados
Assunto: Avaliação da Qualidade dos Dados - ${prod.nome_produto}
Elaborado por: ${prod.ana_esp} de Governança de Dados - ${prod.autor}
Data: ${prod.data_relatorio}

________________________________________
1. Contexto da Avaliação
A presente análise tem como objetivo avaliar a qualidade dos dados referentes ao Produto de Dados ${prod.nome_produto}, considerando os principais pilares de qualidade: disponibilidade, completude, variação, consistência e unicidade.

________________________________________
2. Metodologia
A avaliação foi conduzida por meio de:
* Validação das regras de negócio documentadas;
* Realização de análise de dados quantitativa (ex.: contagens, frequência de nulos, cruzamentos);
* Comparação com fontes de referência, quando aplicável;
* Entrevistas e alinhamentos com responsáveis pelo processo e donos do dado.

________________________________________
3. Resultados da Análise - Dimensões, regras sugeridas e regras solicitadas

${sections.completude}

${sections.consistencia}

${sections.disponibilidade}

${sections.unicidade}

${sections.variacao}

________________________________________
4. Impacto no Negócio
As falhas de qualidade observadas podem impactar:
* A confiabilidade dos indicadores utilizados na tomada de decisão;
* A performance de relatórios analíticos;
* Os processos regulatórios ou obrigatórios (quando aplicável).

________________________________________
5. Recomendações
Sugere-se as seguintes ações para mitigação dos problemas identificados:
${prod.recomendacoes}

________________________________________
6. Conclusão
Com base na análise realizada, conclui-se que o conjunto de dados apresenta nível de qualidade classificado como ${prod.classificacao}, demandando ${prod.demanda} priorização de ações corretivas. Recomenda-se o acompanhamento contínuo e a implementação das ações propostas para garantir maior confiabilidade e qualidade dos dados.
O consumidor ou gerador do dado solicitou a inclusão/exclusão de novas regras de qualidade por sua total responsabilidade.
"""

# ──────────────────────────────────────────────────────────────────────────────
# Textos-padrão por dimensão
# Serão substituídos pelo conteúdo gerado pela IA (recurso futuro).
# As variáveis {numero}, {Valor_X} são substituídas em build_context_ns().
# ──────────────────────────────────────────────────────────────────────────────

SECTION_TEMPLATES_DEFAULT: Dict[str, str] = {
    "completude": (
        "{numero}. Completude\n"
        "Identificou-se que {Valor_Completude} regras foram recomendadas pela ferramenta GenQuality.\n"
        "Dentre essas, (QTD_ALTA_IMPORTANCIA) foram classificadas como de alta importância, conforme avaliação do analista responsável, "
        "e (QTD_ACEITAS) foram formalmente aceitas pelo cliente.\n"
        "As regras que, embora recomendadas pelo analista, não foram aceitas pelo cliente, estão descritas a seguir:\n \n"
        "* [Dimensão – Regra / Coluna X]: Avaliada como relevante pelo analista [nome/área], porém não implementada por decisão de [cliente/área responsável].\n"
        "* [Dimensão – Regra / Coluna Y]: Considerada de alta importância sob a perspectiva de [critério ou impacto], entretanto não aprovada para implementação pelo cliente.\n"
        "* [Dimensão – Regra / Coluna Z]: Recomendada em função de [justificativa técnica ou de negócio], mas não aceita pelo cliente no contexto atual."
    ),
    "consistencia": (
        "{numero}. Consistência\n"
        "Identificou-se que {Valor_Consistencia} regras foram recomendadas pela ferramenta GenQuality.\n"
        "Dentre essas, (QTD_ALTA_IMPORTANCIA) foram classificadas como de alta importância, conforme avaliação do analista responsável, "
        "e (QTD_ACEITAS) foram formalmente aceitas pelo cliente.\n"
        "As regras que, embora recomendadas pelo analista, não foram aceitas pelo cliente, estão descritas a seguir:\n \n"
        "* [Dimensão – Regra / Coluna X]: Avaliada como relevante pelo analista [nome/área], porém não implementada por decisão de [cliente/área responsável].\n"
        "* [Dimensão – Regra / Coluna Y]: Considerada de alta importância sob a perspectiva de [critério ou impacto], entretanto não aprovada para implementação pelo cliente.\n"
        "* [Dimensão – Regra / Coluna Z]: Recomendada em função de [justificativa técnica ou de negócio], mas não aceita pelo cliente no contexto atual."
    ),
    "unicidade": (
        "{numero}. Unicidade\n"
        "Identificou-se que {Valor_Unicidade} regras foram recomendadas pela ferramenta GenQuality.\n"
        "Dentre essas, (QTD_ALTA_IMPORTANCIA) foram classificadas como de alta importância, conforme avaliação do analista responsável, "
        "e (QTD_ACEITAS) foram formalmente aceitas pelo cliente.\n"
        "As regras que, embora recomendadas pelo analista, não foram aceitas pelo cliente, estão descritas a seguir:\n \n"
        "* [Dimensão – Regra / Coluna X]: Avaliada como relevante pelo analista [nome/área], porém não implementada por decisão de [cliente/área responsável].\n"
        "* [Dimensão – Regra / Coluna Y]: Considerada de alta importância sob a perspectiva de [critério ou impacto], entretanto não aprovada para implementação pelo cliente.\n"
        "* [Dimensão – Regra / Coluna Z]: Recomendada em função de [justificativa técnica ou de negócio], mas não aceita pelo cliente no contexto atual."
    ),
    "variacao": (
        "{numero}. Variação\n"
        "Identificou-se que {Valor_Variacao} regras foram recomendadas pela ferramenta GenQuality.\n"
        "Dentre essas, (QTD_ALTA_IMPORTANCIA) foram classificadas como de alta importância, conforme avaliação do analista responsável, "
        "e (QTD_ACEITAS) foram formalmente aceitas pelo cliente.\n"
        "As regras que, embora recomendadas pelo analista, não foram aceitas pelo cliente, estão descritas a seguir:\n \n"
        "* [Dimensão – Regra / Coluna X]: Avaliada como relevante pelo analista [nome/área], porém não implementada por decisão de [cliente/área responsável].\n"
        "* [Dimensão – Regra / Coluna Y]: Considerada de alta importância sob a perspectiva de [critério ou impacto], entretanto não aprovada para implementação pelo cliente.\n"
        "* [Dimensão – Regra / Coluna Z]: Recomendada em função de [justificativa técnica ou de negócio], mas não aceita pelo cliente no contexto atual."
    ),
    "disponibilidade": (
        "{numero}. Disponibilidade\n"
        "Identificou-se que {Valor_Disponibilidade} regras foram recomendadas pela ferramenta GenQuality.\n"
        "Dentre essas, (QTD_ALTA_IMPORTANCIA) foram classificadas como de alta importância, conforme avaliação do analista responsável, "
        "e (QTD_ACEITAS) foram formalmente aceitas pelo cliente.\n"
        "As regras que, embora recomendadas pelo analista, não foram aceitas pelo cliente, estão descritas a seguir:\n \n"
        "* [Dimensão – Regra / Coluna X]: Avaliada como relevante pelo analista [nome/área], porém não implementada por decisão de [cliente/área responsável].\n"
        "* [Dimensão – Regra / Coluna Y]: Considerada de alta importância sob a perspectiva de [critério ou impacto], entretanto não aprovada para implementação pelo cliente.\n"
        "* [Dimensão – Regra / Coluna Z]: Recomendada em função de [justificativa técnica ou de negócio], mas não aceita pelo cliente no contexto atual."
    ),
}

# Recomendações-padrão — substituídas pelo conteúdo da IA (recurso futuro).
RECOMENDACOES_DEFAULT: List[str] = [
    "* Correção dos registros inconsistentes e incompletos, priorizando campos/colunas críticas:",
    "  - Coluna X - Dimensão Y.",
    "  - Coluna Z - Dimensão B.",
    "* Revisão das regras de negócio na origem, garantindo que os sistemas capturem e validem os dados adequadamente:",
    "  - Dimensão X - Regra Y.",
    "  - Dimensão Z - Regra B.",
    "* Inclusão de obrigatoriedade de preenchimento de campos-chave:",
    "  - Dimensão X - Coluna Y.",
    "  - Dimensão Z - Coluna B.",
]


# ==============================================================================
# CÉLULA 3 — Leitura de contexto + Model de dados
# ==============================================================================

import openpyxl
from pathlib import Path

# ──────────────────────────────────────────────────────────────────────────────
# Dataclasses de configuração
# ──────────────────────────────────────────────────────────────────────────────

class RunConfig:
    """
    Configuração de execução lida do arquivo de contexto do analista.

    Campos esperados na planilha (aba 'config', coluna A = chave, coluna B = valor):
        nome_analista       Nome completo do analista responsável
        perfil_analista     Analista | Especialista
        nome_produto        Nome exato do produto de dados
        tipo_produto        Tipo do produto (ex.: PRODUTO DE DADOS)
        dt_ini              Data início da análise  (YYYY-MM-DD)
        dt_fim              Data fim da análise     (YYYY-MM-DD)
        classificacao       satisfatório | moderado | insatisfatório
        demanda             baixa | média | alta

    Valores não encontrados recebem os defaults abaixo.
    """

    # Defaults — evitam falha se o arquivo omitir alguma chave
    _DEFAULTS: Dict[str, str] = {
        "nome_analista"   : "Analista Responsável",
        "perfil_analista" : "Analista",
        "nome_produto"    : "",
        "tipo_produto"    : "PRODUTO DE DADOS",
        "dt_ini"          : (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d"),
        "dt_fim"          : datetime.now().strftime("%Y-%m-%d"),
        "classificacao"   : "satisfatório",
        "demanda"         : "baixa",
    }

    def __init__(self, path: str) -> None:
        raw = self._read_xlsx(path)
        merged = {**self._DEFAULTS, **raw}
        self.nome_analista   : str = merged["nome_analista"]
        self.perfil_analista : str = merged["perfil_analista"]
        self.nome_produto    : str = merged["nome_produto"]
        self.tipo_produto    : str = merged["tipo_produto"]
        self.dt_ini          : str = merged["dt_ini"]
        self.dt_fim          : str = merged["dt_fim"]
        self.classificacao   : str = merged["classificacao"]
        self.demanda         : str = merged["demanda"]

    @staticmethod
    def _read_xlsx(path: str) -> Dict[str, str]:
        wb  = openpyxl.load_workbook(path, read_only=True, data_only=True)
        # Aceita aba chamada 'config' ou a primeira aba disponível
        ws  = wb["config"] if "config" in wb.sheetnames else wb.worksheets[0]
        out: Dict[str, str] = {}
        for row in ws.iter_rows(min_col=1, max_col=2, values_only=True):
            key, val = row
            if key and val is not None:
                out[str(key).strip().lower().replace(" ", "_")] = str(val).strip()
        wb.close()
        return out


class ProductTemplateContext:
    """
    Lê o template de produto de dados (xlsx) que descreve, por dimensão,
    quais regras foram aceitas/rejeitadas pelo cliente.

    Estrutura esperada da planilha (aba 'template' ou primeira aba):
        Coluna A  dimensao        completude | consistencia | unicidade | variacao | disponibilidade
        Coluna B  coluna          Nome da coluna avaliada
        Coluna C  regra           Descrição da regra
        Coluna D  status          aceita | rejeitada
        Coluna E  justificativa   Texto livre (opcional)

    O resultado fica em self.rules: Dict[str, List[Dict]] indexado por dimensão.
    Será consumido futuramente pela IA para gerar os textos das seções.
    """

    DIMENSIONS = ("completude", "consistencia", "unicidade", "variacao", "disponibilidade")

    def __init__(self, path: str) -> None:
        self.rules: Dict[str, List[Dict[str, str]]] = {d: [] for d in self.DIMENSIONS}
        self._read_xlsx(path)

    def _read_xlsx(self, path: str) -> None:
        wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
        ws = wb["template"] if "template" in wb.sheetnames else wb.worksheets[0]
        for row in ws.iter_rows(min_row=2, values_only=True):  # pula cabeçalho
            if not row or not row[0]:
                continue
            dim, coluna, regra, status, justificativa = (
                (str(row[i]).strip().lower() if row[i] is not None else "")
                for i in range(5)
            )
            if dim not in self.rules:
                continue
            self.rules[dim].append({
                "coluna"        : coluna,
                "regra"         : regra,
                "status"        : status,        # "aceita" ou "rejeitada"
                "justificativa" : justificativa,
            })
        wb.close()

    def accepted(self, dimension: str) -> List[Dict[str, str]]:
        return [r for r in self.rules.get(dimension, []) if r["status"] == "aceita"]

    def rejected(self, dimension: str) -> List[Dict[str, str]]:
        return [r for r in self.rules.get(dimension, []) if r["status"] == "rejeitada"]

    def active_dimensions(self) -> List[str]:
        """Retorna apenas as dimensões que possuem ao menos uma regra."""
        return [d for d in self.DIMENSIONS if self.rules[d]]


# ──────────────────────────────────────────────────────────────────────────────
# Model de dados (queries Spark)
# ──────────────────────────────────────────────────────────────────────────────

class ModelDados:
    """Responsável exclusivamente pelas queries Spark de métricas."""

    def __init__(self, spark) -> None:
        self.spark = spark

    # ── helpers internos ──────────────────────────────────────────────────────

    def _raw_query(self, df) -> Dict[str, Any]:
        row = df.first()
        return row.asDict() if row else {}

    def _is_empty(self, raw: Dict[str, Any], count_key: str) -> bool:
        return not raw or raw.get(count_key, 0) == 0

    def _run_with_fallback(
        self,
        primary,
        fallback: Optional[Any],
        count_key: str,
    ) -> tuple:
        result = primary()
        if self._is_empty(result, count_key) and fallback is not None:
            return fallback(), "BI"
        return result, "GenQuality"

    # ── busca de produto ──────────────────────────────────────────────────────

    def search_produto(self, raw_input: str, email: str = "") -> List[Dict]:
        tokens = [t for t in re.split(r"[\s_\-]+", raw_input.strip()) if t]
        if not tokens:
            return []
        like_conds   = " AND ".join(f"projeto ILIKE '%{t}%'" for t in tokens)
        email_filter = f"AND usuario ILIKE '%{email}%'" if email.strip() else ""
        df = self.spark.sql(f"""
            SELECT DISTINCT
                projeto                  AS nome_produto,
                usuario                  AS email_responsavel,
                "PRODUTO DE DADOS"       AS tipo_produto,
                FIRST(timestamp_selecao) AS data_criacao
            FROM pr_explo.d4852s015_quald_dados.genquality_interface_user
            WHERE {like_conds} {email_filter}
            GROUP BY nome_produto, email_responsavel
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    def search_produto_bi(self, raw_input: str) -> List[Dict]:
        tokens = [t for t in re.split(r"[\s_]+", raw_input.strip()) if t]
        if not tokens:
            return []
        like_conds = " AND ".join(f"itpo_prodt_funcl ILIKE '%{t}%'" for t in tokens)
        df = self.spark.sql(f"""
            SELECT
                itpo_prodt_funcl         AS nome_produto,
                FIRST(itpo_prodt)        AS tipo_produto,
                NULL                     AS email_responsavel,
                FIRST(A.dini_excuc_quald) AS data_criacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
                ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
                ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
                ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
            WHERE {like_conds} AND A.nsttus_mntrc = 1
            GROUP BY itpo_prodt_funcl
            ORDER BY nome_produto
            LIMIT 30
        """)
        return [r.asDict() for r in df.collect()]

    # ── queries de métricas por dimensão ─────────────────────────────────────

    def _query_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(DISTINCT nome_catlg, nome_base, nome_tbela, nome_cluna) AS count_completude
            FROM pr_explo.d4852s015_quald_dados.estrutura_genquality
            WHERE dmsao = 'Completude' AND nome_proj = '{nome}'
              AND date_excuc BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_completude(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_completude
            FROM (
                SELECT DISTINCT b.itbela_quald, b.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
                    ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
                    ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
                    ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
                WHERE idmsao_quald_dados = 'COMPLETUDE'
                  AND H.itpo_prodt_funcl = '{nome}' AND itpo_prodt = '{tipo}'
                  AND A.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
                  AND A.nsttus_mntrc = 1
            )
        """)
        return self._raw_query(df)

    def _query_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(DISTINCT nome_catlg, nome_base, nome_tbela, nome_cluna) AS count_consistencia
            FROM pr_explo.d4852s015_quald_dados.estrutura_genquality
            WHERE dmsao = 'Consistência' AND nome_proj = '{nome}'
              AND date_excuc BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_consistencia(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM (
                SELECT DISTINCT b.itbela_quald, b.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
                    ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
                    ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
                    ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
                WHERE idmsao_quald_dados = 'CONSISTENCIA'
                  AND H.itpo_prodt_funcl = '{nome}' AND itpo_prodt = '{tipo}'
                  AND A.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
                  AND A.nsttus_mntrc = 1
            )
        """)
        return self._raw_query(df)

    def _query_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(DISTINCT nome_catlg, nome_base, nome_tbela, nome_cluna) AS count_unicidade
            FROM pr_explo.d4852s015_quald_dados.estrutura_genquality
            WHERE dmsao = 'Unicidade' AND nome_proj = '{nome}'
              AND date_excuc BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_unicidade(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM (
                SELECT DISTINCT b.itbela_quald, b.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
                    ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
                    ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
                    ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
                WHERE idmsao_quald_dados = 'UNICIDADE'
                  AND H.itpo_prodt_funcl = '{nome}' AND itpo_prodt = '{tipo}'
                  AND A.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
                  AND A.nsttus_mntrc = 1
            )
        """)
        return self._raw_query(df)

    def _query_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(DISTINCT nome_catlg, nome_base, nome_tbela, nome_cluna) AS count_variacao
            FROM pr_explo.d4852s015_quald_dados.estrutura_genquality
            WHERE dmsao = 'Variação' AND nome_proj = '{nome}'
              AND date_excuc BETWEEN '{dt_ini}' AND '{dt_fim}'
        """)
        return self._raw_query(df)

    def _fallback_variacao(self, nome: str, tipo: str, dt_ini: str, dt_fim: str) -> Dict:
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM (
                SELECT DISTINCT b.itbela_quald, b.icpo_tbela
                FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_catlg_base_cpo_tbela AS b
                    ON a.ncatlg_base_cpo_tbela = b.ncatlg_base_cpo_tbela
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_quald_dados AS f
                    ON a.ndmsao_quald_dados = f.NDMSAO_QUALD_DADOS
                INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS h
                    ON a.ntpo_prodt_dados = h.ntpo_prodt_dados
                WHERE idmsao_quald_dados = 'VARIACAO'
                  AND H.itpo_prodt_funcl = '{nome}' AND itpo_prodt = '{tipo}'
                  AND A.dini_excuc_quald BETWEEN '{dt_ini}' AND '{dt_fim}'
                  AND A.nsttus_mntrc = 1
            )
        """)
        return self._raw_query(df)

    # ── coleta completa ───────────────────────────────────────────────────────

    def coletar_metricas(
        self, nome: str, tipo: str, dt_ini: str, dt_fim: str
    ) -> Dict[str, Any]:
        """
        Retorna { dimensao: { count, query } } para todas as dimensões.
        disponibilidade não tem query — o valor virá do contexto / IA.
        """
        resultado: Dict[str, Any] = {}

        for dim, q_fn, fb_fn, key in [
            ("completude",  self._query_completude,  self._fallback_completude,  "count_completude"),
            ("consistencia",self._query_consistencia,self._fallback_consistencia,"count_consistencia"),
            ("unicidade",   self._query_unicidade,   self._fallback_unicidade,   "count_unicidade"),
            ("variacao",    self._query_variacao,    self._fallback_variacao,    "count_variacao"),
        ]:
            raw, q = self._run_with_fallback(
                lambda fn=q_fn:  fn(nome, tipo, dt_ini, dt_fim),
                lambda fn=fb_fn: fn(nome, tipo, dt_ini, dt_fim),
                key,
            )
            resultado[dim] = {"count": raw.get(key, 0), "query": q}

        resultado["disponibilidade"] = {"count": 0, "query": "contexto"}
        return resultado


# ──────────────────────────────────────────────────────────────────────────────
# Construção do contexto de template
# ──────────────────────────────────────────────────────────────────────────────

class DocumentContextBuilder:
    """
    Transforma RunConfig + ProductTemplateContext + métricas no dicionário
    ctx_ns consumido por render_template_dotted().

    Responsabilidade única: montar o contexto — sem IO, sem Spark.
    """

    DIMENSIONS_ORDER = ["completude", "consistencia", "disponibilidade", "unicidade", "variacao"]

    def build(
        self,
        config: RunConfig,
        product_ctx: ProductTemplateContext,
        metricas: Dict[str, Any],
        # ── PLACEHOLDER IA ────────────────────────────────────────────────────
        # Quando o recurso de IA estiver disponível, receber os textos gerados
        # como parâmetro aqui e usá-los em vez dos SECTION_TEMPLATES_DEFAULT.
        # Sugestão de assinatura futura:
        #   ai_sections: Optional[Dict[str, str]] = None
        #   ai_recomendacoes: Optional[List[str]] = None
        # ─────────────────────────────────────────────────────────────────────
    ) -> Dict[str, Any]:

        nome_display = config.nome_produto.replace("_", " ").strip()

        # ── Recomendações ─────────────────────────────────────────────────────
        # PLACEHOLDER IA — substituir RECOMENDACOES_DEFAULT pelo retorno da IA
        recomendacoes_block = "\n".join(RECOMENDACOES_DEFAULT)

        prod_ns = {
            "nome_produto"  : nome_display,
            "tipo_produto"  : config.tipo_produto,
            "autor"         : config.nome_analista,
            "ana_esp"       : config.perfil_analista,
            "data_relatorio": datetime.now().strftime("%d/%m/%Y"),
            "classificacao" : config.classificacao,
            "demanda"       : config.demanda,
            "recomendacoes" : recomendacoes_block,
        }

        # ── Dimensões ativas e numeração dinâmica ──────────────────────────────
        active_dims  = product_ctx.active_dimensions()
        # Garante a ordem canônica e filtra apenas as presentes no template
        ordered_dims = [d for d in self.DIMENSIONS_ORDER if d in active_dims]
        numeracao    = {dim: f"3.{i + 1}" for i, dim in enumerate(ordered_dims)}

        sections_ns: Dict[str, str] = {}
        for dim in self.DIMENSIONS_ORDER:
            if dim not in ordered_dims:
                sections_ns[dim] = ""
                continue

            numero   = numeracao[dim]
            # PLACEHOLDER IA — quando ai_sections disponível:
            #   tpl = (ai_sections or {}).get(dim) or SECTION_TEMPLATES_DEFAULT.get(dim, "")
            tpl      = SECTION_TEMPLATES_DEFAULT.get(dim, "")
            met      = metricas.get(dim, {})
            count    = fmt_int(met.get("count", 0))

            if dim == "disponibilidade":
                # PLACEHOLDER IA — texto de disponibilidade virá da IA
                disp_texto = "(texto de disponibilidade a ser gerado pela IA)"
                rendered   = tpl.format(
                    numero=numero,
                    Valor_Disponibilidade=disp_texto,
                )
                sections_ns[dim] = "__DISPONIBILIDADE_BLOCK__\n" + rendered.strip()
            else:
                try:
                    rendered = tpl.format(
                        numero             = numero,
                        Valor_Completude   = count,
                        Valor_Consistencia = count,
                        Valor_Unicidade    = count,
                        Valor_Variacao     = count,
                    )
                except KeyError:
                    rendered = tpl
                sections_ns[dim] = rendered.strip()

        return {"prod": prod_ns, "sections": sections_ns}


# ==============================================================================
# CÉLULA 4 — Orquestrador: lê contexto → coleta métricas → gera PDF
# ==============================================================================

# ──────────────────────────────────────────────────────────────────────────────
# Caminhos dos arquivos de contexto
# Altere apenas aqui para apontar para os arquivos corretos no DBFS / Volume.
# ──────────────────────────────────────────────────────────────────────────────

RUN_CONFIG_PATH      = "/dbfs/FileStore/parecer_qualidade/config_analista.xlsx"
PRODUCT_CONTEXT_PATH = "/dbfs/FileStore/parecer_qualidade/template_produto.xlsx"
OUTPUT_DIR           = "/dbfs/FileStore/parecer_qualidade/output/"

# ──────────────────────────────────────────────────────────────────────────────
# Orquestrador principal
# ──────────────────────────────────────────────────────────────────────────────

def gerar_parecer(
    run_config_path: str      = RUN_CONFIG_PATH,
    product_ctx_path: str     = PRODUCT_CONTEXT_PATH,
    output_dir: str           = OUTPUT_DIR,
    # ── PLACEHOLDER Graph ─────────────────────────────────────────────────────
    # Quando o recurso Microsoft Graph estiver disponível, receber aqui o
    # client autenticado e usar para buscar os arquivos de contexto diretamente
    # do SharePoint / OneDrive em vez de caminhos locais no DBFS.
    # Sugestão de assinatura futura:
    #   graph_client = None
    # ─────────────────────────────────────────────────────────────────────────
    # ── PLACEHOLDER IA ────────────────────────────────────────────────────────
    # Quando o recurso de IA estiver disponível, receber aqui o cliente/função
    # e delegar a ele a geração dos textos de cada seção.
    # Sugestão de assinatura futura:
    #   ai_client = None
    # ─────────────────────────────────────────────────────────────────────────
) -> str:
    """
    Fluxo completo:
        1. Lê RunConfig       (config_analista.xlsx)
        2. Lê ProductTemplate (template_produto.xlsx)
        3. Coleta métricas    (Spark / GenQuality + BI fallback)
        4. Monta contexto     (DocumentContextBuilder)
        5. Renderiza template (render_template_dotted)
        6. Gera PDF           (make_pdf_bytes)
        7. Salva no output_dir e exibe link para download

    Retorna o caminho do arquivo gerado.
    """
    print("[1/6] Lendo arquivos de contexto...")
    config      = RunConfig(run_config_path)
    product_ctx = ProductTemplateContext(product_ctx_path)

    print(f"      Produto  : {config.nome_produto}")
    print(f"      Analista : {config.nome_analista} ({config.perfil_analista})")
    print(f"      Período  : {config.dt_ini} → {config.dt_fim}")
    print(f"      Dimensões ativas no template: {product_ctx.active_dimensions()}")

    print("[2/6] Coletando métricas no Spark...")
    model    = ModelDados(spark)  # `spark` disponível no escopo do notebook Databricks
    metricas = model.coletar_metricas(
        config.nome_produto, config.tipo_produto,
        config.dt_ini, config.dt_fim,
    )
    for dim, m in metricas.items():
        print(f"      {dim:<18} count={m['count']:>6}  via={m['query']}")

    print("[3/6] Montando contexto do documento...")
    builder = DocumentContextBuilder()
    ctx     = builder.build(config, product_ctx, metricas)

    print("[4/6] Renderizando template...")
    rendered = render_template_dotted(TEMPLATE_PARECER, ctx)

    print("[5/6] Gerando PDF...")
    pdf_bytes = make_pdf_bytes(
        rendered_text = rendered,
        title         = "Parecer Técnico de Qualidade de Dados",
        autor         = f"{config.perfil_analista} de Governança de Dados - {config.nome_analista}",
        assunto       = f"Avaliação da Qualidade - {config.nome_produto}",
    )

    print("[6/6] Salvando arquivo...")
    Path(output_dir).mkdir(parents=True, exist_ok=True)
    ts       = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"Parecer_Qualidade_{sanitize_filename(config.nome_produto)}_{ts}.pdf"
    out_path = str(Path(output_dir) / filename)

    with open(out_path, "wb") as fh:
        fh.write(pdf_bytes)

    kb = len(pdf_bytes) / 1024
    print(f"\n✔  Documento gerado: {out_path}  ({kb:.1f} KB)")

    # Link de download inline no notebook Databricks
    b64   = base64.b64encode(pdf_bytes).decode("utf-8")
    from IPython.display import display, HTML
    display(HTML(f"""
        <div style="font-family:'Segoe UI',sans-serif;margin-top:12px">
          <b>Download:</b>&nbsp;
          <a href="data:application/pdf;base64,{b64}" download="{filename}"
             style="background:#8B0000;color:#fff;padding:8px 20px;
                    text-decoration:none;border-radius:3px;font-weight:700">
            Baixar PDF
          </a>
        </div>
    """))

    return out_path


# ──────────────────────────────────────────────────────────────────────────────
# Ponto de entrada — execute esta célula para gerar o documento
# ──────────────────────────────────────────────────────────────────────────────

gerar_parecer()
