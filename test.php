from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch, cm
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_JUSTIFY
from reportlab.pdfgen import canvas as pdfcanvas
from datetime import datetime
import io

----------

# ============================================
# DADOS MOCKADOS - Formato Boletim
# ============================================

# Slide 1
slide_1_data = {
    "titulo_slide": "PRINCIPAIS RESULTADOS",
    "conteudo": """
> Implementação de qualidade de dados em 100% dos produtos de dados lançados em 2025, totalizando 57 produtos dados e 46 novas releases entregues.

> 84% dos produtos de dados com mais de duas dimensões aplicadas, garantindo mais segurança e confiabilidade na informação.

> Fechamos 2025 com um score médio de qualidade de 97%, com 46 produtos de dados acima de 95% e 6 Produtos com pontuação máxima.

> 6 workshops sobre conceitos de qualidade de dados, disseminando a cultura Data Driven e a importância de garantir a qualidade de dados nas tomadas decisões.
"""
}

# Slide 2
slide_2_data = {
    "titulo_slide": "INICIATIVAS ESTRATÉGICAS",
    "conteudo": """
> Estruturação do modelo dimensional do Produto de Dados Qualidade, com inclusão de nova dimensão, carga dos dados de origem e validação para garantir integridade.

> Definição e validação das regras de qualidade no Produto de Dados, assegurando confiabilidade das informações para tomada de decisão baseada em dados.

> Aprovado no Chapter o produto de Qualidade de Dados, voltado a garantir a confiabilidade das informações por meio de práticas de definição, correção, monitoramento e melhoria contínua.

> Realização da POC IA4DQ (Framework e agent) para otimizar a monitoração e avaliação das métricas de qualidade de dados, buscando garantir dados confiáveis para análises e tomadas de decisão, promovendo eficiência e confiabilidade nos processos de negócios.
"""
}

# Slide 3
slide_3_data = {
    "titulo_slide": "EFICIÊNCIA OPERACIONAL",
    "conteudo": """
> Descomissionamento do ambiente on-premises, mapeando 100% dos JOBs, automações para desativação e eliminação de 20% dos JOBs sem impacto operacional.

> Iniciamos o piloto com o GenQuality (IA) que visa otimizar o processo de avaliação de métricas de qualidade de dados em bases produtivas no ambiente Cloud.

> Experimento de encerramento de chamados na Bridge, com 98% dos chamados encerrados com acurácia.

> Documentação do passo a passo para resolução de Incidentes no ambiente Informática, buscando encontrar melhorias no processo de encerramento de incidentes e possíveis automações.

> Formalização dos processos e esteiras da Qualidade, com fluxo para implementação, alteração e desativação de JOBs no Control-M, garantindo padronização e governança.
"""
}

# Metadados
metadata = {
    "titulo_boletim": "BOLETIM Qualidade de Dados",
    "periodo": "Dez 2024",
    "cor_header": "#1a5f6f"  # Verde azulado do Bradesco
}

print("✅ Dados mockados carregados!")
print(f"📊 Slide 1: {slide_1_data['titulo_slide']}")
print(f"📊 Slide 2: {slide_2_data['titulo_slide']}")
print(f"📊 Slide 3: {slide_3_data['titulo_slide']}")


-------------------

class BoletimBradescoGenerator:
    """
    Gera PDF no estilo Boletim Bradesco
    Layout similar às imagens fornecidas
    """
    
    def __init__(self, filename):
        self.filename = filename
        self.pagesize = landscape(A4)
        self.width, self.height = self.pagesize
        self.slides = []
        
        # Cores padrão (podem ser customizadas)
        self.cor_header = colors.HexColor('#1a5f6f')  # Verde azulado
        self.cor_titulo_secao = colors.HexColor('#999999')  # Cinza
        self.cor_fundo = colors.white
        self.cor_texto = colors.HexColor('#2c2c2c')
        self.cor_card = colors.HexColor('#f5f5f5')
        self.cor_borda_card = colors.HexColor('#cccccc')
    
    def processar_bullets(self, texto):
        """Processa texto dividido por > e retorna lista de bullets"""
        bullets = []
        linhas = texto.strip().split('>')
        
        for linha in linhas:
            linha_limpa = linha.strip()
            if linha_limpa:
                bullets.append(linha_limpa)
        
        return bullets
    
    def adicionar_slide(self, titulo_slide, conteudo, cor_header=None):
        """Adiciona um slide ao boletim"""
        if cor_header:
            self.cor_header = colors.HexColor(cor_header)
        
        bullets = self.processar_bullets(conteudo)
        
        slide = {
            'titulo_slide': titulo_slide,
            'bullets': bullets
        }
        
        self.slides.append(slide)
    
    def _desenhar_header(self, c, titulo_boletim, periodo):
        """Desenha o cabeçalho do boletim"""
        # Fundo do header
        c.setFillColor(self.cor_header)
        c.rect(0, self.height - 80, self.width, 80, fill=1, stroke=0)
        
        # Área para logo esquerda (50x50 pixels aproximadamente)
        # Deixar espaço vazio para o usuário adicionar depois
        logo_x = 30
        logo_y = self.height - 65
        logo_size = 50
        
        # Desenhar placeholder para logo (opcional - pode remover)
        c.setStrokeColor(colors.white)
        c.setLineWidth(1)
        c.setDash(3, 3)
        c.rect(logo_x, logo_y, logo_size, logo_size, fill=0, stroke=1)
        
        # Título do boletim
        c.setFillColor(colors.white)
        c.setFont("Helvetica", 28)
        titulo_x = logo_x + logo_size + 20
        c.drawString(titulo_x, self.height - 40, titulo_boletim)
        
        # Período
        c.setFont("Helvetica", 18)
        c.drawString(titulo_x, self.height - 65, f"— {periodo}")
        
        # Área para logo direita
        logo_direita_x = self.width - 120
        c.rect(logo_direita_x, logo_y, 80, logo_size, fill=0, stroke=1)
        
        c.setDash(1, 0)  # Reset dash
    
    def _desenhar_titulo_secao(self, c, titulo, y_pos):
        """Desenha o título da seção (ex: PRINCIPAIS RESULTADOS)"""
        # Fundo cinza arredondado
        c.setFillColor(self.cor_titulo_secao)
        c.roundRect(60, y_pos - 5, 320, 35, 5, fill=1, stroke=0)
        
        # Texto
        c.setFillColor(colors.white)
        c.setFont("Helvetica-Bold", 14)
        c.drawString(75, y_pos + 5, titulo.upper())
        
        return y_pos - 50  # Retorna nova posição Y
    
    def _desenhar_card(self, c, texto, x, y, width, height):
        """Desenha um card com borda pontilhada e texto"""
        # Fundo branco
        c.setFillColor(self.cor_fundo)
        c.roundRect(x, y, width, height, 8, fill=1, stroke=0)
        
        # Borda pontilhada
        c.setStrokeColor(self.cor_borda_card)
        c.setLineWidth(1.5)
        c.setDash(4, 4)
        c.roundRect(x, y, width, height, 8, fill=0, stroke=1)
        c.setDash(1, 0)  # Reset
        
        # Texto dentro do card
        c.setFillColor(self.cor_texto)
        c.setFont("Helvetica", 11)
        
        # Quebrar texto em múltiplas linhas
        palavras = texto.split()
        linha_atual = ""
        y_texto = y + height - 25
        margem_interna = 15
        max_width = width - (2 * margem_interna)
        line_height = 14
        
        for palavra in palavras:
            teste = (linha_atual + " " + palavra) if linha_atual else palavra
            
            # Destacar texto em negrito (entre **)
            if '**' in palavra:
                palavra = palavra.replace('**', '')
                teste = (linha_atual + " " + palavra) if linha_atual else palavra
            
            if c.stringWidth(teste, "Helvetica", 11) < max_width:
                linha_atual = teste
            else:
                # Desenhar linha atual
                if linha_atual:
                    # Verificar se tem texto em negrito
                    if any(p.startswith('**') or p.endswith('**') for p in linha_atual.split()):
                        c.setFont("Helvetica-Bold", 11)
                        linha_limpa = linha_atual.replace('**', '')
                        c.drawString(x + margem_interna, y_texto, linha_limpa)
                        c.setFont("Helvetica", 11)
                    else:
                        c.drawString(x + margem_interna, y_texto, linha_atual)
                    
                    y_texto -= line_height
                linha_atual = palavra
        
        # Última linha
        if linha_atual:
            c.drawString(x + margem_interna, y_texto, linha_atual)
    
    def _calcular_altura_card(self, c, texto, width):
        """Calcula a altura necessária para um card baseado no texto"""
        palavras = texto.split()
        linha_atual = ""
        num_linhas = 0
        margem_interna = 15
        max_width = width - (2 * margem_interna)
        
        for palavra in palavras:
            teste = (linha_atual + " " + palavra) if linha_atual else palavra
            
            if c.stringWidth(teste, "Helvetica", 11) < max_width:
                linha_atual = teste
            else:
                num_linhas += 1
                linha_atual = palavra
        
        if linha_atual:
            num_linhas += 1
        
        # Altura = margem superior + (linhas * altura_linha) + margem inferior
        return 20 + (num_linhas * 14) + 15
    
    def _desenhar_slide(self, c, slide, titulo_boletim, periodo):
        """Desenha um slide completo"""
        # Header
        self._desenhar_header(c, titulo_boletim, periodo)
        
        # Título da seção
        y_pos = self.height - 120
        y_pos = self._desenhar_titulo_secao(c, slide['titulo_slide'], y_pos)
        
        # Calcular grid de cards
        bullets = slide['bullets']
        num_bullets = len(bullets)
        
        # Layout: determinar quantas colunas e linhas
        # Para 4 bullets: 2x2, para 3 bullets: 3x1, para 5+: adaptar
        if num_bullets <= 2:
            cols = 1
            rows = num_bullets
        elif num_bullets == 3:
            cols = 3
            rows = 1
        elif num_bullets == 4:
            cols = 2
            rows = 2
        else:
            cols = 2
            rows = (num_bullets + 1) // 2
        
        # Dimensões dos cards
        margem_lateral = 60
        espacamento = 20
        area_largura = self.width - (2 * margem_lateral)
        
        if cols == 1:
            card_width = area_largura
        elif cols == 2:
            card_width = (area_largura - espacamento) / 2
        else:  # 3 colunas
            card_width = (area_largura - (2 * espacamento)) / 3
        
        # Desenhar cards
        x_start = margem_lateral
        y_current = y_pos
        
        for i, bullet in enumerate(bullets):
            # Calcular posição
            col = i % cols
            
            if col == 0 and i > 0:
                # Nova linha
                y_current -= 150  # Espaçamento entre linhas
            
            x = x_start + (col * (card_width + espacamento))
            
            # Calcular altura do card
            card_height = self._calcular_altura_card(c, bullet, card_width)
            card_height = max(card_height, 80)  # Altura mínima
            card_height = min(card_height, 200)  # Altura máxima
            
            # Desenhar card
            self._desenhar_card(c, bullet, x, y_current - card_height, 
                               card_width, card_height)
    
    def gerar_pdf(self, titulo_boletim, periodo):
        """Gera o PDF completo"""
        c = pdfcanvas.Canvas(self.filename, pagesize=self.pagesize)
        
        for slide in self.slides:
            self._desenhar_slide(c, slide, titulo_boletim, periodo)
            c.showPage()
        
        c.save()
        print(f"✅ Boletim PDF gerado: {self.filename}")

print("✅ Classe BoletimBradescoGenerator criada!")




--------------------

# ============================================
# GERAR BOLETIM EM PDF
# ============================================

import io
import base64

try:
    print("🎨 Criando boletim em PDF...")
    
    # Criar buffer
    buffer = io.BytesIO()
    
    # Criar gerador
    gerador = BoletimBradescoGenerator(buffer)
    
    # Adicionar slides
    print("📄 Adicionando slide 1...")
    gerador.adicionar_slide(
        slide_1_data['titulo_slide'],
        slide_1_data['conteudo'],
        metadata['cor_header']
    )
    
    print("📄 Adicionando slide 2...")
    gerador.adicionar_slide(
        slide_2_data['titulo_slide'],
        slide_2_data['conteudo'],
        metadata['cor_header']
    )
    
    print("📄 Adicionando slide 3...")
    gerador.adicionar_slide(
        slide_3_data['titulo_slide'],
        slide_3_data['conteudo'],
        metadata['cor_header']
    )
    
    # Gerar PDF
    print("\n🔄 Gerando arquivo PDF...")
    gerador.gerar_pdf(
        metadata['titulo_boletim'],
        metadata['periodo']
    )
    
    # Obter bytes
    buffer.seek(0)
    pdf_data = buffer.getvalue()
    
    print("\n" + "="*60)
    print("✅ BOLETIM PDF GERADO COM SUCESSO!")
    print("="*60)
    print(f"📦 Tamanho: {len(pdf_data) / 1024:.2f} KB")
    print(f"📊 Total de páginas: {len(gerador.slides)}")
    print(f"📏 Formato: A4 Paisagem")
    print(f"🎨 Estilo: Boletim Bradesco")
    print("="*60)
    
except Exception as e:
    print(f"\n❌ ERRO: {e}")
    import traceback
    traceback.print_exc()

----------------------


# ============================================
# DOWNLOAD DO BOLETIM
# ============================================

from datetime import datetime

filename = f"Boletim_QualidadeDados_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"

displayHTML(f'''
<div style="padding: 30px; background: linear-gradient(135deg, #1a5f6f 0%, #2d8a9e 100%); 
            border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
    
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0; font-size: 32px;">
            Boletim Pronto!
        </h1>
        <p style="color: rgba(255,255,255,0.95); margin: 10px 0 0 0; font-size: 16px;">
            Seu boletim no estilo Bradesco está pronto para download
        </p>
    </div>
    
    <div style="background: white; padding: 30px; border-radius: 10px;">
        
        <div style="margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0; color: #1a5f6f;">Informações do Documento</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div><strong>Tamanho:</strong> {len(pdf_data) / 1024:.2f} KB</div>
                <div><strong>Páginas:</strong> {len(gerador.slides)}</div>
                <div><strong>Formato:</strong> A4 Paisagem</div>
                <div><strong>Gerado:</strong> {datetime.now().strftime("%d/%m/%Y %H:%M")}</div>
            </div>
        </div>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 20px;">
            <strong>Atenção:</strong> Os espaços para logos foram marcados com bordas pontilhadas. 
            Você pode adicionar suas imagens editando o PDF posteriormente.
        </div>
        
        <div style="text-align: center;">
            <a id="download-link" 
               href="data:application/pdf;base64,{base64.b64encode(pdf_data).decode('utf-8')}" 
               download="{filename}"
               style="background: #1a5f6f; color: white; padding: 18px 40px; 
                      text-decoration: none; border-radius: 8px; display: inline-block;
                      font-weight: bold; font-size: 18px; margin-right: 10px;
                      transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(26,95,111,0.3);">
                Baixar Boletim PDF
            </a>
            
            <button onclick="visualizarPDF()" 
                    style="background: #28a745; color: white; padding: 18px 40px; 
                           border: none; border-radius: 8px; font-weight: bold; 
                           font-size: 18px; cursor: pointer; transition: all 0.3s ease;
                           box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
                Visualizar PDF
            </button>
        </div>
    </div>
</div>

<div id="pdf-viewer" style="margin-top: 20px; display: none; background: white; 
                             padding: 20px; border-radius: 10px;">
    <h3 style="color: #1a5f6f;">Visualização do Boletim</h3>
    <iframe id="pdf-frame" style="width: 100%; height: 700px; border: 2px solid #ddd; border-radius: 5px;">
    </iframe>
</div>

<script>
function visualizarPDF() {{
    const viewer = document.getElementById('pdf-viewer');
    const frame = document.getElementById('pdf-frame');
    const button = event.target;
    
    if (viewer.style.display === 'none') {{
        viewer.style.display = 'block';
        frame.src = 'data:application/pdf;base64,{base64.b64encode(pdf_data).decode('utf-8')}';
        button.textContent = 'Fechar Visualização';
        viewer.scrollIntoView({{ behavior: 'smooth' }});
    }} else {{
        viewer.style.display = 'none';
        button.textContent = 'Visualizar PDF';
    }}
}}
</script>
''')

print("✅ Interface de download criada!")
print(f"📄 Nome do arquivo: {filename}")
