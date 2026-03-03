# ==============================================================================
# CELL 0 - Databricks Widgets (user-facing parameters)
# Run this cell first. Values are read by all subsequent cells.
# ==============================================================================

dbutils.widgets.removeAll()

dbutils.widgets.text("tipo_produto_param",  "", "Tipo de Produto")
dbutils.widgets.text("nome_produto_param",  "", "Nome do Produto")
dbutils.widgets.text("autor_param",         "", "Autor")
dbutils.widgets.text("ana_esp",             "Analista", "Perfil (Analista / Especialista)")

# Disponibilidade is written directly in Cell 0.5 below - no widget needed.


# ==============================================================================
# CELL 0.5 - Disponibilidade (optional section - edit freely here)
#
# INSTRUCTIONS:
#   - To INCLUDE the section: set disponibilidade_ativo = True and write your
#     content in disponibilidade_texto using the conventions below.
#   - To EXCLUDE the section: set disponibilidade_ativo = False.
#     The content is ignored and the section will not appear in the PDF.
#
# TEXT CONVENTIONS (plain Python string, no special tools needed):
#   ## Texto        -> renders as a sub-header inside the section in the PDF
#   * Texto         -> renders as a bullet point
#   Any other line  -> renders as normal body text
#
# EXAMPLE:
#   disponibilidade_texto = """
#   Os dados encontram-se atualizados ate 15/07/2025.
#
#   ## Sistemas impactados
#   Ha indicio de atraso no carregamento da camada trusted do sistema XYZ,
#   impactando a tomada de decisao dos indicadores de risco.
#
#   * Pipeline batch com falha desde 10/07/2025;
#   * Equipe de engenharia notificada, correcao prevista para 20/07/2025.
#   """
# ==============================================================================

disponibilidade_ativo = False   # Change to True to include this section in the PDF

disponibilidade_texto = """

"""
# Write your content above.
# The header "3.3 Disponibilidade" is added automatically - do not repeat it.


# ==============================================================================
# CELL 1 - Setup & Helpers
# NOTE: pip installs are assumed to be done separately.
# ==============================================================================

import io
import re
import base64
from datetime import datetime, date
from string import Template

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
    name="SectionTitle", parent=styles["Heading2"], fontName=FONT_NAME,
    fontSize=13, leading=16, textColor=colors.HexColor("#111827"),
    spaceBefore=10, spaceAfter=6,
))
styles.add(ParagraphStyle(
    name="SubSectionTitle", parent=styles["Heading3"], fontName=FONT_NAME,
    fontSize=11, leading=14, textColor=colors.HexColor("#1F2937"),
    spaceBefore=6, spaceAfter=4,
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

# ---------- Utils ----------

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

def join_list(items, sep=", "):
    if items is None: return ""
    if isinstance(items, (list, tuple, set)): return sep.join(map(str, items))
    return str(items)

def merge_left_to_right(*dicts):
    """Shallow merge: later dicts override earlier keys."""
    out = {}
    for d in dicts:
        if d: out.update(d)
    return out

# ---------- Namespaced placeholder renderer ----------
# Supports ${ns.key} and ${ns.sub.key}; leaves unknown placeholders intact.

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

# ---------- Disponibilidade text parser ----------
# Converts the free-form string from Cell 0.5 into ReportLab story elements.
# ## line  -> SubSectionTitle style
# * line   -> bulleted Body
# other    -> Body

def parse_disponibilidade(raw: str) -> list:
    """
    Parses disponibilidade_texto into a list of ReportLab Paragraph objects.
    Returns an empty list if the input is blank.
    """
    elements = []
    for line in raw.splitlines():
        line = line.rstrip()
        if not line:
            elements.append(Spacer(1, 6))
        elif line.startswith("##"):
            elements.append(Paragraph(line[2:].strip(), styles["SubSectionTitle"]))
        elif line.startswith("*"):
            elements.append(Paragraph(
                f"&nbsp;&nbsp;&bull;&nbsp;{line[1:].strip()}", styles["Body"]
            ))
        else:
            elements.append(Paragraph(line, styles["Body"]))
    return elements

# ---------- PDF builder ----------

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

def make_pdf_bytes(
    rendered_text: str,
    title: str,
    autor: str,
    assunto: str = None,
    disponibilidade_elements: list = None,
) -> bytes:
    buf = io.BytesIO()
    doc = SimpleDocTemplate(
        buf, pagesize=A4,
        rightMargin=36, leftMargin=36, topMargin=54, bottomMargin=36,
    )
    story = []

    # Header block
    story.append(Paragraph(title, styles["DocTitle"]))
    if assunto:
        story.append(Paragraph(f"<b>Assunto:</b> {assunto}", styles["Meta"]))
    story.append(Paragraph(f"<b>Elaborado por:</b> {autor}", styles["Meta"]))
    story.append(Spacer(1, 6))
    story.append(Paragraph("________________________________________", styles["Separator"]))
    story.append(Spacer(1, 8))

    # Body blocks - split on blank lines; inject Disponibilidade when sentinel found
    blocks = [b.strip() for b in re.split(r"\n\s*\n", rendered_text) if b.strip()]
    for block in blocks:
        if block == "__DISPONIBILIDADE_BLOCK__":
            # Inject the pre-parsed rich elements for Disponibilidade
            if disponibilidade_elements:
                story.append(Paragraph("3.3 Disponibilidade", styles["SectionTitle"]))
                story.extend(disponibilidade_elements)
                story.append(Spacer(1, 8))
        elif re.match(r"^\d+(\.\d+)*\s", block):
            story.append(Paragraph(block, styles["SectionTitle"]))
            story.append(Spacer(1, 8))
        else:
            lines = block.splitlines()
            frags = []
            for ln in lines:
                if ln.startswith("*"):
                    frags.append(f"&nbsp;&nbsp;&bull;&nbsp;{ln[1:].strip()}")
                else:
                    frags.append(ln)
            story.append(Paragraph("<br/>".join(frags), styles["Body"]))
            story.append(Spacer(1, 8))

    header = PageCounter()
    doc.build(story, onFirstPage=header, onLaterPages=header)
    out = buf.getvalue()
    buf.close()
    return out


# ==============================================================================
# CELL 2 - Template text (ONLY cell the user should edit for content/design)
#
# To add or remove a variable: add/remove a ${namespace.key} placeholder.
# To add or remove a section: add/remove the section block here.
# Completude, Consistencia, Unicidade and Variacao appear automatically when
# their queries return rows (handled in Cell 3).
# Disponibilidade appears when disponibilidade_ativo = True in Cell 0.5.
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


# ==============================================================================
# CELL 3 - Data Model (queries + context assembly)
#
# To add a new metric: add a query method following the existing pattern,
# then register it in _build_sections() and to_context_ns().
# To remove a metric: delete its method and remove it from both places.
# ==============================================================================

from typing import Any, Dict

# ---- Section text templates ----
# To change what is written for a dimension, edit its string here.
# To add a new dimension, add a new entry and handle it in _build_sections().

SECTION_TEMPLATES: Dict[str, str] = {
    "completude": (
        "3.1 Completude\n"
        "Identificou-se que {completude_qnt} atributos obrigatorios preenchidos. "
        "Os 3 atributos com menor score sao: {completude_lowest_3}.\n"
        "Score medio de completude: {completude_pct}%.\n"
        "Conclusao: Orientamos a aplicabilidade de completude nos atributos conforme template."
    ),
    "consistencia": (
        "3.2 Consistencia\n"
        "Identificou-se que {consistencia_qnt} atributos com possibilidade de aplicacao de regras de negocio.\n"
        "Conclusao: Orientamos a aplicabilidade de Consistencia nos atributos conforme template."
    ),
    "unicidade": (
        "3.5 Unicidade\n"
        "Identificou-se que {unicidade_qnt} registros duplicados.\n"
        "Conclusao: Orientamos a aplicabilidade de Unicidade nos atributos conforme template."
    ),
    "variacao": (
        "3.6 Variacao\n"
        "Identificou-se que {variacao_qnt} registros com variacao analisada.\n"
        "Conclusao: Orientamos a aplicabilidade de Variacao nos atributos conforme template."
    ),
}


class model:

    def __init__(self, params: Dict[str, Any], spark):
        self.spark = spark
        self.params = dict(params or {})
        self._cache: Dict[str, Any] = {}

    # ====================================================
    # Internal helpers
    # ====================================================

    def _raw_query(self, df) -> Dict[str, Any]:
        row = df.first()
        return row.asDict() if row else {}

    # ====================================================
    # Queries - one method per dimension
    # ====================================================

    def _query_completude(self) -> Dict[str, Any]:
        nome_produto = self.params["nome_produto_param"]
        tipo_produto = self.params["tipo_produto_param"]
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
                    AND b.itpo_prodt_funcl = '{nome_produto}'
                    AND a.nsttus_mntrc = 1
                    AND b.itpo_prodt = '{tipo_produto}'
                    AND a.dini_excuc_quald >= current_date() - 0
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

    # ---- Consistencia ----
    def _query_consistencia(self) -> Dict[str, Any]:
        nome_produto = self.params["nome_produto_param"]
        tipo_produto = self.params["tipo_produto_param"]
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_consistencia
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 3
                AND b.itpo_prodt_funcl = '{nome_produto}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo_produto}'
                AND a.dini_excuc_quald >= current_date() - 0
        """)
        return self._raw_query(df)

    # ---- Unicidade ----
    def _query_unicidade(self) -> Dict[str, Any]:
        nome_produto = self.params["nome_produto_param"]
        tipo_produto = self.params["tipo_produto_param"]
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_unicidade
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 4
                AND b.itpo_prodt_funcl = '{nome_produto}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo_produto}'
                AND a.dini_excuc_quald >= current_date() - 0
        """)
        return self._raw_query(df)

    # ---- Variacao ----
    def _query_variacao(self) -> Dict[str, Any]:
        nome_produto = self.params["nome_produto_param"]
        tipo_produto = self.params["tipo_produto_param"]
        df = self.spark.sql(f"""
            SELECT COUNT(*) AS count_variacao
            FROM pr_platfun.aaqd_estrt_dados_qld_ucs.tfato_anlse_quald_dados AS a
            INNER JOIN pr_platfun.aaqd_estrt_dados_qld_ucs.tdmsao_tpo_prodt_dados AS b
                ON a.ntpo_prodt_dados = b.ntpo_prodt_dados
            WHERE
                a.ndmsao_quald_dados = 5
                AND b.itpo_prodt_funcl = '{nome_produto}'
                AND a.nsttus_mntrc = 1
                AND b.itpo_prodt = '{tipo_produto}'
                AND a.dini_excuc_quald >= current_date() - 0
        """)
        return self._raw_query(df)

    # ====================================================
    # Section builder
    # ====================================================

    def _build_sections(self, disponibilidade_ativo: bool) -> Dict[str, str]:
        sections: Dict[str, str] = {}

        # --- Completude ---
        raw_c = self._query_completude()
        if raw_c.get("count_pscore", 0) > 0:
            sections["completude"] = SECTION_TEMPLATES["completude"].format(
                completude_qnt      = fmt_int(raw_c.get("count_pscore")),
                completude_lowest_3 = raw_c.get("lowest_3", "N/A"),
                completude_pct      = pct(raw_c.get("pct_pscore_calcd")),
            )
        else:
            sections["completude"] = ""

        # --- Consistencia ---
        raw_cs = self._query_consistencia()
        if raw_cs.get("count_consistencia", 0) > 0:
            sections["consistencia"] = SECTION_TEMPLATES["consistencia"].format(
                consistencia_qnt = fmt_int(raw_cs.get("count_consistencia")),
            )
        else:
            sections["consistencia"] = ""

        # --- Unicidade ---
        raw_u = self._query_unicidade()
        if raw_u.get("count_unicidade", 0) > 0:
            sections["unicidade"] = SECTION_TEMPLATES["unicidade"].format(
                unicidade_qnt = fmt_int(raw_u.get("count_unicidade")),
            )
        else:
            sections["unicidade"] = ""

        # --- Variacao ---
        raw_v = self._query_variacao()
        if raw_v.get("count_variacao", 0) > 0:
            sections["variacao"] = SECTION_TEMPLATES["variacao"].format(
                variacao_qnt = fmt_int(raw_v.get("count_variacao")),
            )
        else:
            sections["variacao"] = ""

        # --- Disponibilidade ---
        # Sentinel string: the PDF builder replaces this with the parsed rich elements.
        # If inactive, empty string causes the block to be skipped entirely.
        sections["disponibilidade"] = "__DISPONIBILIDADE_BLOCK__" if disponibilidade_ativo else ""

        return sections

    # ====================================================
    # Public: assemble full context namespace
    # ====================================================

    def to_context_ns(self, disponibilidade_ativo: bool = False) -> Dict[str, Any]:
        """
        Returns the full context_ns dict ready for render_template_dotted().
        Sections with no data render as empty strings and are skipped by the
        PDF builder.
        """
        ana_esp_raw = self.params.get("ana_esp", "Analista").strip()
        ana_esp = ana_esp_raw.title() if ana_esp_raw else "Analista"

        prod_ns = {
            "nome_produto"   : self.params.get("nome_produto_param", ""),
            "tipo_produto"   : self.params.get("tipo_produto_param", ""),
            "autor"          : self.params.get("autor_param", ""),
            "ana_esp"        : ana_esp,
            "data_relatorio" : datetime.now().strftime("%d/%m/%Y"),
        }

        sections_ns = self._build_sections(disponibilidade_ativo)

        return {
            "prod"     : prod_ns,
            "sections" : sections_ns,
        }


# ==============================================================================
# CELL 3.5 - Context assembly
# Reads widgets + Cell 0.5 variables, instantiates model, builds context_ns.
# ==============================================================================

# Read widget values
params = {
    "tipo_produto_param" : dbutils.widgets.get("tipo_produto_param"),
    "nome_produto_param" : dbutils.widgets.get("nome_produto_param"),
    "autor_param"        : dbutils.widgets.get("autor_param"),
    "ana_esp"            : dbutils.widgets.get("ana_esp"),
}

# disponibilidade_ativo and disponibilidade_texto come from Cell 0.5 (already in scope)

# Build context namespace
m = model(params=params, spark=spark)
context_ns = m.to_context_ns(disponibilidade_ativo=disponibilidade_ativo)

# Parse the rich Disponibilidade content once here so Cell 4 can use it
disponibilidade_elements = (
    parse_disponibilidade(disponibilidade_texto)
    if disponibilidade_ativo and disponibilidade_texto.strip()
    else []
)

print("context_ns assembled:")
for ns, val in context_ns.items():
    print(f"  [{ns}]", val if not isinstance(val, dict) else "")
    if isinstance(val, dict):
        for k, v in val.items():
            snippet = str(v)[:80].replace("\n", " ")
            print(f"    {k}: {snippet}")

print(f"\n  [disponibilidade_ativo] {disponibilidade_ativo}")
print(f"  [disponibilidade_elements] {len(disponibilidade_elements)} element(s) parsed")


# ==============================================================================
# CELL 4 - Render template + generate PDF + display download button
# ==============================================================================

rendered = render_template_dotted(template_text, context_ns)

title   = "Parecer Tecnico de Qualidade de Dados"
assunto = f"Avaliacao da Qualidade - {context_ns['prod'].get('nome_produto', '')}"
autor   = (
    f"Data Governance {context_ns['prod'].get('ana_esp', 'Analista')} - "
    f"{context_ns['prod'].get('autor', '')}"
)

pdf_bytes = make_pdf_bytes(
    rendered_text            = rendered,
    title                    = title,
    autor                    = autor,
    assunto                  = assunto,
    disponibilidade_elements = disponibilidade_elements,
)
b64      = base64.b64encode(pdf_bytes).decode("utf-8")
filename = (
    f"Parecer_Qualidade_{sanitize_filename(context_ns['prod'].get('nome_produto'))}"
    f"_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
)

displayHTML(f'''
<div style="padding: 30px; background: linear-gradient(135deg, #1a5f6f 0%, #2d8a9e 100%);
            border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">

  <div style="text-align: center; margin-bottom: 20px;">
    <h1 style="color: white; margin: 0; font-size: 32px;">Boletim Pronto!</h1>
    <p style="color: rgba(255,255,255,0.95); margin: 10px 0 0 0; font-size: 16px;">
      Seu boletim esta pronto para download
    </p>
  </div>

  <div style="background: white; padding: 30px; border-radius: 10px;">
    <div style="margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
      <h3 style="margin: 0 0 15px 0; color: #1a5f6f;">Informacoes do Documento</h3>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <div><strong>Tamanho:</strong> {(len(pdf_bytes) / 1024):.2f} KB</div>
        <div><strong>Formato:</strong> A4 Retrato</div>
        <div><strong>Gerado:</strong> {datetime.now().strftime("%d/%m/%Y %H:%M")}</div>
      </div>
    </div>

    <div style="text-align: center;">
      <a id="download-link"
         href="data:application/pdf;base64,{b64}"
         download="{filename}"
         style="background: #1a5f6f; color: white; padding: 18px 40px;
                text-decoration: none; border-radius: 8px; display: inline-block;
                font-weight: bold; font-size: 18px; margin-right: 10px;">
        Baixar PDF
      </a>

      <button onclick="abrirPDF()"
              style="background: #28a745; color: white; padding: 18px 40px;
                     border: none; border-radius: 8px; font-weight: bold;
                     font-size: 18px; cursor: pointer;">
        Visualizar em Nova Aba
      </button>
    </div>

    <div id="mensagem-sucesso" style="display:none; margin-top:15px; color: #28a745; text-align:center;">
      PDF aberto com sucesso em nova aba.
    </div>
  </div>
</div>

<script>
function abrirPDF() {{
    const base64Data = '{b64}';
    const byteCharacters = atob(base64Data);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {{
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }}
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], {{type: 'application/pdf'}});
    const url = URL.createObjectURL(blob);
    const newWindow = window.open(url, '_blank');
    if (newWindow) {{
        document.getElementById('mensagem-sucesso').style.display = 'block';
        setTimeout(() => {{
            document.getElementById('mensagem-sucesso').style.display = 'none';
        }}, 5000);
    }} else {{
        alert('Pop-up bloqueado. Por favor, permita pop-ups ou use o botao Baixar PDF.');
    }}
}}
</script>
''')
