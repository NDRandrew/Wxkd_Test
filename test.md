# ============================================================
# TESTE HÍBRIDO — ipywidgets nativos + HTML dinâmico
# Cole tudo isso numa única célula e rode
# ============================================================
import ipywidgets as W
from IPython.display import display, HTML

# ── Widgets de input ─────────────────────────────────────────

# Campo 1: com <style> customizado via widgets.HTML label
lbl_com_estilo = W.HTML(value="""
<style>
  .meu-label { color: #CC0000; font-weight: bold; font-size: 13px; margin-bottom: 2px; }
</style>
<div class="meu-label">Campo COM estilo (Bradesco red):</div>
""")
campo_com_estilo = W.Text(placeholder="Digite algo...", layout=W.Layout(width="300px"))

# Campo 2: sem estilo, label nativo do ipywidgets
campo_sem_estilo = W.Text(description="Sem estilo:", placeholder="Digite algo...", layout=W.Layout(width="300px"))

# Botão e área de resultado
btn = W.Button(description="Gerar HTML", button_style="primary", layout=W.Layout(margin="10px 0"))
resultado = W.HTML(value="<i style='color:gray'>Resultado aparece aqui...</i>")

# ── Callback ─────────────────────────────────────────────────

def on_gerar(b):
    v1 = campo_com_estilo.value or "(vazio)"
    v2 = campo_sem_estilo.value or "(vazio)"
    resultado.value = f"""
    <div style="border:1px solid #ccc; padding:12px; border-radius:6px; font-family:sans-serif; max-width:420px;">
      <h4 style="margin:0 0 8px; color:#CC0000;">Resultado Dinâmico</h4>
      <p style="margin:4px 0;"><b>Campo com estilo:</b> {v1}</p>
      <p style="margin:4px 0;"><b>Campo sem estilo:</b> {v2}</p>
    </div>
    """

btn.on_click(on_gerar)

# ── Layout e display ──────────────────────────────────────────

display(W.VBox([
    lbl_com_estilo,
    campo_com_estilo,
    W.Label(""),  # espaço
    campo_sem_estilo,
    btn,
    resultado,
]))
