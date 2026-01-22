from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import inch, cm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak, Frame, PageTemplate
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.pdfgen import canvas
from datetime import datetime
import io


--------------

# ============================================
# DADOS MOCKADOS - Formato Apresentação
# ============================================

# Cada slide tem título e lista de bullets (começando com >)
slide_1 = {
    "titulo": "Marcos Importantes de 2024",
    "subtitulo": "Transformação Organizacional",
    "bullets": [
        "O ano de 2024 representou um marco importante na trajetória da organização. Foram implementadas diversas iniciativas estratégicas que transformaram significativamente a forma como conduzimos nossos processos e nos relacionamos com nossos stakeholders.",
        
        "No primeiro semestre, concentramos esforços na modernização da infraestrutura tecnológica. Esta etapa incluiu a migração de sistemas legados para plataformas em nuvem, proporcionando maior escalabilidade, segurança e eficiência operacional.",
        
        "A transição foi conduzida de forma planejada, minimizando impactos nas operações do dia a dia. A capacitação das equipes foi outro pilar fundamental do ano."
    ],
    "icone": "🚀"
}

slide_2 = {
    "titulo": "Desenvolvimento Profissional",
    "subtitulo": "Investimento em Pessoas",
    "bullets": [
        "Investimos significativamente em programas de desenvolvimento profissional, abrangendo desde habilidades técnicas específicas até competências comportamentais essenciais para o ambiente corporativo moderno.",
        
        "Os resultados foram evidentes no aumento do engajamento e da produtividade. Mais de 200 colaboradores participaram de treinamentos especializados ao longo do ano.",
        
        "A implementação de programas de mentoria aproximou lideranças e novos talentos, criando um ambiente colaborativo e propício ao crescimento profissional."
    ],
    "icone": "📚"
}

slide_3 = {
    "titulo": "Transformação Digital",
    "subtitulo": "Inovação e Automação",
    "bullets": [
        "No segundo semestre, iniciamos um ambicioso projeto de transformação digital que visa automatizar processos manuais e implementar inteligência artificial em áreas estratégicas.",
        
        "Esta iniciativa já demonstra resultados promissores, com ganhos mensuráveis em eficiência e qualidade. A automação de processos reduziu em 40% o tempo de execução de tarefas rotineiras.",
        
        "A governança corporativa foi fortalecida através da implementação de novos controles internos e processos de auditoria mais rigorosos, garantindo maior transparência e conformidade."
    ],
    "icone": "💡"
}

# Metadados
metadata = {
    "titulo_apresentacao": "Boletim Anual 2024",
    "subtitulo_apresentacao": "Relatório de Atividades e Conquistas",
    "autor": "André Silva",
    "departamento": "Gestão Estratégica",
    "data": datetime.now().strftime("%d/%m/%Y")
}

print("✅ Dados da apresentação carregados!")
print(f"📊 Total de slides: 3")
print(f"📄 Slide 1: {slide_1['titulo']}")
print(f"📄 Slide 2: {slide_2['titulo']}")
print(f"📄 Slide 3: {slide_3['titulo']}")

---------------

from reportlab.pdfgen import canvas as pdfcanvas

class ApresentacaoPDFGenerator:
    """
    Gera PDF no estilo apresentação PowerPoint
    Layout paisagem com slides coloridos e bullets
    """
    
    def __init__(self, filename):
        self.filename = filename
        self.pagesize = landscape(A4)  # Modo paisagem
        self.width, self.height = self.pagesize
        self.slides = []
        
        # Paleta de cores moderna
        self.cores = {
            'primaria': colors.HexColor('#0078d4'),      # Azul Microsoft
            'secundaria': colors.HexColor('#50e6ff'),    # Azul claro
            'destaque': colors.HexColor('#ffb900'),      # Amarelo
            'texto': colors.HexColor('#323130'),         # Cinza escuro
            'texto_claro': colors.HexColor('#605e5c'),   # Cinza médio
            'fundo_slide': colors.HexColor('#f3f2f1'),   # Cinza clarissimo
            'branco': colors.white
        }
    
    def criar_slide_capa(self, metadata):
        """Cria slide de capa da apresentação"""
        slide = {
            'tipo': 'capa',
            'dados': metadata
        }
        self.slides.append(slide)
    
    def criar_slide_conteudo(self, titulo, subtitulo, bullets, icone="📄"):
        """Cria slide com título e bullets"""
        slide = {
            'tipo': 'conteudo',
            'titulo': titulo,
            'subtitulo': subtitulo,
            'bullets': bullets,
            'icone': icone
        }
        self.slides.append(slide)
    
    def criar_slide_final(self, metadata):
        """Cria slide de encerramento"""
        slide = {
            'tipo': 'final',
            'dados': metadata
        }
        self.slides.append(slide)
    
    def _desenhar_slide_capa(self, c, dados):
        """Desenha slide de capa"""
        # Fundo com gradiente (simulado com retângulos)
        c.setFillColor(self.cores['primaria'])
        c.rect(0, 0, self.width, self.height, fill=1, stroke=0)
        
        # Barra diagonal decorativa
        c.setFillColor(self.cores['secundaria'])
        c.saveState()
        c.translate(0, 0)
        c.rotate(15)
        c.rect(-100, self.height/2 - 100, self.width + 200, 200, fill=1, stroke=0)
        c.restoreState()
        
        # Título principal
        c.setFillColor(self.cores['branco'])
        c.setFont("Helvetica-Bold", 48)
        titulo_width = c.stringWidth(dados['titulo_apresentacao'], "Helvetica-Bold", 48)
        c.drawString((self.width - titulo_width) / 2, self.height - 150, 
                     dados['titulo_apresentacao'])
        
        # Subtítulo
        c.setFont("Helvetica", 24)
        c.setFillColorRGB(0.9, 0.9, 0.9)
        subtitulo_width = c.stringWidth(dados['subtitulo_apresentacao'], "Helvetica", 24)
        c.drawString((self.width - subtitulo_width) / 2, self.height - 200, 
                     dados['subtitulo_apresentacao'])
        
        # Informações do autor
        c.setFont("Helvetica", 14)
        y_pos = 120
        info_lines = [
            f"👤 {dados['autor']}",
            f"🏢 {dados['departamento']}",
            f"📅 {dados['data']}"
        ]
        
        for line in info_lines:
            line_width = c.stringWidth(line, "Helvetica", 14)
            c.drawString((self.width - line_width) / 2, y_pos, line)
            y_pos -= 25
    
    def _desenhar_slide_conteudo(self, c, titulo, subtitulo, bullets, icone, numero_slide):
        """Desenha slide de conteúdo com bullets"""
        # Fundo claro
        c.setFillColor(self.cores['fundo_slide'])
        c.rect(0, 0, self.width, self.height, fill=1, stroke=0)
        
        # Barra superior colorida
        c.setFillColor(self.cores['primaria'])
        c.rect(0, self.height - 80, self.width, 80, fill=1, stroke=0)
        
        # Ícone no canto
        c.setFont("Helvetica", 40)
        c.setFillColor(self.cores['branco'])
        c.drawString(50, self.height - 60, icone)
        
        # Título do slide
        c.setFont("Helvetica-Bold", 32)
        c.setFillColor(self.cores['branco'])
        c.drawString(110, self.height - 55, titulo)
        
        # Subtítulo
        if subtitulo:
            c.setFont("Helvetica-Oblique", 16)
            c.setFillColorRGB(0.9, 0.9, 0.9)
            c.drawString(110, self.height - 80 + 10, subtitulo)
        
        # Número do slide
        c.setFont("Helvetica", 12)
        c.setFillColor(self.cores['texto_claro'])
        c.drawRightString(self.width - 40, 30, f"Slide {numero_slide}")
        
        # Área de conteúdo com bullets
        y_position = self.height - 140
        x_margin = 80
        bullet_spacing = 15
        line_height = 16
        
        c.setFillColor(self.cores['texto'])
        
        for i, bullet in enumerate(bullets):
            # Bullet point decorativo
            c.setFillColor(self.cores['primaria'])
            c.circle(x_margin, y_position + 5, 6, fill=1)
            
            # Texto do bullet com quebra automática
            c.setFillColor(self.cores['texto'])
            c.setFont("Helvetica", 13)
            
            # Quebrar texto em múltiplas linhas
            palavras = bullet.split()
            linha_atual = ""
            max_width = self.width - x_margin - 100
            
            for palavra in palavras:
                teste_linha = linha_atual + " " + palavra if linha_atual else palavra
                if c.stringWidth(teste_linha, "Helvetica", 13) < max_width:
                    linha_atual = teste_linha
                else:
                    # Desenhar linha atual
                    c.drawString(x_margin + 20, y_position, linha_atual)
                    y_position -= line_height
                    linha_atual = palavra
            
            # Desenhar última linha
            if linha_atual:
                c.drawString(x_margin + 20, y_position, linha_atual)
            
            y_position -= (bullet_spacing + line_height)
            
            # Verificar se ainda há espaço
            if y_position < 100:
                break
    
    def _desenhar_slide_final(self, c, dados):
        """Desenha slide de encerramento"""
        # Fundo gradiente
        c.setFillColor(self.cores['primaria'])
        c.rect(0, 0, self.width, self.height, fill=1, stroke=0)
        
        # Mensagem de agradecimento
        c.setFillColor(self.cores['branco'])
        c.setFont("Helvetica-Bold", 56)
        texto = "Obrigado!"
        texto_width = c.stringWidth(texto, "Helvetica-Bold", 56)
        c.drawString((self.width - texto_width) / 2, self.height / 2 + 50, texto)
        
        # Emoji
        c.setFont("Helvetica", 80)
        emoji = "🎯"
        emoji_width = c.stringWidth(emoji, "Helvetica", 80)
        c.drawString((self.width - emoji_width) / 2, self.height / 2 - 80, emoji)
        
        # Contato
        c.setFont("Helvetica", 16)
        c.setFillColorRGB(0.9, 0.9, 0.9)
        contato = f"📧 Dúvidas? Entre em contato: {dados['autor']}"
        contato_width = c.stringWidth(contato, "Helvetica", 16)
        c.drawString((self.width - contato_width) / 2, 80, contato)
    
    def gerar_pdf(self):
        """Gera o PDF com todos os slides"""
        c = pdfcanvas.Canvas(self.filename, pagesize=self.pagesize)
        
        numero_slide = 0
        
        for slide in self.slides:
            if slide['tipo'] == 'capa':
                self._desenhar_slide_capa(c, slide['dados'])
            
            elif slide['tipo'] == 'conteudo':
                numero_slide += 1
                self._desenhar_slide_conteudo(
                    c,
                    slide['titulo'],
                    slide['subtitulo'],
                    slide['bullets'],
                    slide['icone'],
                    numero_slide
                )
            
            elif slide['tipo'] == 'final':
                self._desenhar_slide_final(c, slide['dados'])
            
            c.showPage()  # Nova página (slide)
        
        c.save()
        print(f"✅ Apresentação PDF gerada: {self.filename}")

print("✅ Classe ApresentacaoPDFGenerator criada!")

--------------------

# ============================================
# GERAR APRESENTAÇÃO EM PDF
# ============================================

import io
import base64

try:
    print("🎨 Criando apresentação em PDF...")
    
    # Criar buffer em memória
    buffer = io.BytesIO()
    
    # Criar gerador
    gerador = ApresentacaoPDFGenerator(buffer)
    
    # Slide de capa
    print("📄 Criando slide de capa...")
    gerador.criar_slide_capa(metadata)
    
    # Slides de conteúdo
    print("📄 Criando slide 1...")
    gerador.criar_slide_conteudo(
        slide_1['titulo'],
        slide_1['subtitulo'],
        slide_1['bullets'],
        slide_1['icone']
    )
    
    print("📄 Criando slide 2...")
    gerador.criar_slide_conteudo(
        slide_2['titulo'],
        slide_2['subtitulo'],
        slide_2['bullets'],
        slide_2['icone']
    )
    
    print("📄 Criando slide 3...")
    gerador.criar_slide_conteudo(
        slide_3['titulo'],
        slide_3['subtitulo'],
        slide_3['bullets'],
        slide_3['icone']
    )
    
    # Slide final
    print("📄 Criando slide de encerramento...")
    gerador.criar_slide_final(metadata)
    
    # Gerar PDF
    print("\n🔄 Gerando arquivo PDF...")
    gerador.gerar_pdf()
    
    # Obter bytes
    buffer.seek(0)
    pdf_data = buffer.getvalue()
    
    print("\n" + "="*60)
    print("✅ APRESENTAÇÃO PDF GERADA COM SUCESSO!")
    print("="*60)
    print(f"📦 Tamanho: {len(pdf_data) / 1024:.2f} KB")
    print(f"📊 Total de slides: {len(gerador.slides)}")
    print(f"📏 Formato: A4 Paisagem (Landscape)")
    print(f"🎨 Estilo: PowerPoint")
    print("="*60)
    
except Exception as e:
    print(f"\n❌ ERRO: {e}")
    import traceback
    traceback.print_exc()

--------------------

# ============================================
# INTERFACE DE DOWNLOAD
# ============================================

from datetime import datetime

filename = f"Apresentacao_Boletim_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"

displayHTML(f'''
<div style="padding: 30px; background: linear-gradient(135deg, #0078d4 0%, #50e6ff 100%); 
            border-radius: 15px; margin: 20px 0; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
    
    <div style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 60px; margin-bottom: 10px;">🎯</div>
        <h1 style="color: white; margin: 0; font-size: 32px;">
            Apresentação Pronta!
        </h1>
        <p style="color: rgba(255,255,255,0.95); margin: 10px 0 0 0; font-size: 16px;">
            Sua apresentação em estilo PowerPoint está pronta para download
        </p>
    </div>
    
    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
            <div style="padding: 15px; background: #f3f2f1; border-radius: 8px;">
                <div style="color: #0078d4; font-size: 24px; margin-bottom: 5px;">📦</div>
                <div style="color: #323130; font-size: 13px; font-weight: bold;">Tamanho</div>
                <div style="color: #605e5c; font-size: 18px; font-weight: bold;">
                    {len(pdf_data) / 1024:.2f} KB
                </div>
            </div>
            
            <div style="padding: 15px; background: #f3f2f1; border-radius: 8px;">
                <div style="color: #0078d4; font-size: 24px; margin-bottom: 5px;">📊</div>
                <div style="color: #323130; font-size: 13px; font-weight: bold;">Total de Slides</div>
                <div style="color: #605e5c; font-size: 18px; font-weight: bold;">
                    {len(gerador.slides)} slides
                </div>
            </div>
            
            <div style="padding: 15px; background: #f3f2f1; border-radius: 8px;">
                <div style="color: #0078d4; font-size: 24px; margin-bottom: 5px;">📄</div>
                <div style="color: #323130; font-size: 13px; font-weight: bold;">Formato</div>
                <div style="color: #605e5c; font-size: 18px; font-weight: bold;">
                    A4 Paisagem
                </div>
            </div>
            
            <div style="padding: 15px; background: #f3f2f1; border-radius: 8px;">
                <div style="color: #0078d4; font-size: 24px; margin-bottom: 5px;">📅</div>
                <div style="color: #323130; font-size: 13px; font-weight: bold;">Gerado em</div>
                <div style="color: #605e5c; font-size: 18px; font-weight: bold;">
                    {datetime.now().strftime("%d/%m/%Y")}
                </div>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a id="download-link" 
               href="data:application/pdf;base64,{base64.b64encode(pdf_data).decode('utf-8')}" 
               download="{filename}"
               style="background: linear-gradient(135deg, #0078d4 0%, #005a9e 100%); 
                      color: white; padding: 18px 40px; text-decoration: none; 
                      border-radius: 8px; display: inline-block; font-weight: bold; 
                      font-size: 18px; cursor: pointer; transition: all 0.3s ease;
                      box-shadow: 0 4px 12px rgba(0,120,212,0.3); margin-right: 10px;">
                📥 Baixar Apresentação
            </a>
            
            <button onclick="visualizarPDF()" 
                    style="background: linear-gradient(135deg, #28a745 0%, #20893a 100%); 
                           color: white; padding: 18px 40px; border: none; 
                           border-radius: 8px; font-weight: bold; font-size: 18px; 
                           cursor: pointer; transition: all 0.3s ease;
                           box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
                👁️ Visualizar Slides
            </button>
        </div>
    </div>
</div>

<div id="pdf-viewer" style="margin-top: 20px; display: none; background: white; 
                             padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h3 style="color: #0078d4; margin-top: 0;">📊 Visualização da Apresentação</h3>
    <iframe id="pdf-frame" 
            style="width: 100%; height: 700px; border: 2px solid #e1dfdd; border-radius: 5px;">
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
        button.textContent = '❌ Fechar Visualização';
        viewer.scrollIntoView({{ behavior: 'smooth' }});
    }} else {{
        viewer.style.display = 'none';
        button.textContent = '👁️ Visualizar Slides';
    }}
}}

document.querySelectorAll('a, button').forEach(element => {{
    element.addEventListener('mouseenter', function() {{
        this.style.transform = 'translateY(-3px) scale(1.02)';
        this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
    }});
    
    element.addEventListener('mouseleave', function() {{
        this.style.transform = 'translateY(0) scale(1)';
        this.style.boxShadow = this.tagName === 'A' ? 
            '0 4px 12px rgba(0,120,212,0.3)' : '0 4px 12px rgba(40,167,69,0.3)';
    }});
}});
</script>
''')

print("✅ Interface de download criada!")
print(f"📄 Nome do arquivo: {filename}")
print(f"🎨 Estilo: Apresentação PowerPoint")
