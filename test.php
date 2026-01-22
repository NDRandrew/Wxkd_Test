# ============================================
# REGISTRAR FONTE CUSTOMIZADA
# ============================================

from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# PASSO 1: Upload da fonte
# Faça upload do arquivo .ttf ou .otf para o DBFS
# Você pode fazer isso via UI do Databricks ou usando o código abaixo

# PASSO 2: Registrar a fonte
# Substitua o caminho pelo local onde você fez upload da fonte

# Exemplo: Se você fez upload para /FileStore/fonts/
try:
    # Fonte Regular
    pdfmetrics.registerFont(TTFont('MinhaFonte', '/dbfs/FileStore/fonts/SuaFonte-Regular.ttf'))
    
    # Fonte Bold (se tiver)
    pdfmetrics.registerFont(TTFont('MinhaFonte-Bold', '/dbfs/FileStore/fonts/SuaFonte-Bold.ttf'))
    
    # Fonte Italic (se tiver)
    pdfmetrics.registerFont(TTFont('MinhaFonte-Italic', '/dbfs/FileStore/fonts/SuaFonte-Italic.ttf'))
    
    # Fonte Bold Italic (se tiver)
    pdfmetrics.registerFont(TTFont('MinhaFonte-BoldItalic', '/dbfs/FileStore/fonts/SuaFonte-BoldItalic.ttf'))
    
    print("✅ Fontes customizadas registradas com sucesso!")
    print("   - MinhaFonte (Regular)")
    print("   - MinhaFonte-Bold")
    print("   - MinhaFonte-Italic")
    print("   - MinhaFonte-BoldItalic")
    
except Exception as e:
    print(f"❌ Erro ao registrar fonte: {e}")
    print("\n⚠️  Usando Helvetica como fallback")


--------------------

class BoletimBradescoGenerator:
    """
    Gera PDF no estilo Boletim Bradesco
    Layout similar às imagens fornecidas
    """
    
    def __init__(self, filename, fonte_principal='MinhaFonte', fonte_bold='MinhaFonte-Bold'):
        self.filename = filename
        self.pagesize = landscape(A4)
        self.width, self.height = self.pagesize
        self.slides = []
        
        # Definir fontes (com fallback para Helvetica)
        self.fonte_regular = fonte_principal
        self.fonte_bold = fonte_bold
        
        # Verificar se as fontes estão disponíveis
        try:
            from reportlab.pdfbase import pdfmetrics
            pdfmetrics.getFont(self.fonte_regular)
            pdfmetrics.getFont(self.fonte_bold)
        except:
            print("⚠️  Fonte customizada não encontrada, usando Helvetica")
            self.fonte_regular = 'Helvetica'
            self.fonte_bold = 'Helvetica-Bold'
        
        # Cores fixas do gradiente
        self.cor_centro = colors.HexColor('#1a5f6f')
        self.cor_lateral = colors.HexColor('#040720')
        
        # Outras cores
        self.cor_titulo_secao = colors.HexColor('#999999')
        self.cor_fundo = colors.white
        self.cor_texto = colors.HexColor('#2c2c2c')
        self.cor_card = colors.HexColor('#f5f5f5')
        self.cor_borda_card = colors.HexColor('#cccccc')
    
    def _interpolar_cor(self, cor1, cor2, fator):
        """Interpola entre duas cores"""
        r1, g1, b1 = cor1.red, cor1.green, cor1.blue
        r2, g2, b2 = cor2.red, cor2.green, cor2.blue
        
        r = r1 + (r2 - r1) * fator
        g = g1 + (g2 - g1) * fator
        b = b1 + (b2 - b1) * fator
        
        return colors.Color(r, g, b)
    
    def _desenhar_gradiente_horizontal(self, c, x, y, width, height, cor_centro, cor_lateral):
        """Desenha um gradiente horizontal do centro para as laterais"""
        num_faixas = 100
        largura_faixa = width / num_faixas
        meio = num_faixas / 2
        
        for i in range(num_faixas):
            distancia_centro = abs(i - meio) / meio
            cor = self._interpolar_cor(cor_centro, cor_lateral, distancia_centro)
            c.setFillColor(cor)
            c.rect(x + (i * largura_faixa), y, largura_faixa, height, fill=1, stroke=0)
    
    def processar_bullets(self, texto):
        """Processa texto dividido por > e retorna lista de bullets"""
        bullets = []
        linhas = texto.strip().split('>')
        
        for linha in linhas:
            linha_limpa = linha.strip()
            if linha_limpa:
                bullets.append(linha_limpa)
        
        return bullets
    
    def adicionar_slide(self, titulo_slide, conteudo):
        """Adiciona um slide ao boletim"""
        bullets = self.processar_bullets(conteudo)
        
        slide = {
            'titulo_slide': titulo_slide,
            'bullets': bullets
        }
        
        self.slides.append(slide)
    
    def _desenhar_header(self, c, titulo_boletim, periodo):
        """Desenha o cabeçalho do boletim com gradiente"""
        header_height = 80
        header_y = self.height - header_height
        
        # Desenhar gradiente no fundo do header
        self._desenhar_gradiente_horizontal(
            c, 0, header_y, self.width, header_height, 
            self.cor_centro, self.cor_lateral
        )
        
        # Área para logo esquerda
        logo_x = 30
        logo_y = self.height - 65
        logo_size = 50
        
        c.setStrokeColor(colors.white)
        c.setLineWidth(1)
        c.setDash(3, 3)
        c.rect(logo_x, logo_y, logo_size, logo_size, fill=0, stroke=1)
        
        # Título do boletim - USANDO FONTE CUSTOMIZADA
        c.setFillColor(colors.white)
        c.setFont(self.fonte_bold, 28)  # ← FONTE CUSTOMIZADA BOLD
        titulo_x = logo_x + logo_size + 20
        c.drawString(titulo_x, self.height - 40, titulo_boletim)
        
        # Período - USANDO FONTE CUSTOMIZADA
        c.setFont(self.fonte_regular, 18)  # ← FONTE CUSTOMIZADA REGULAR
        c.drawString(titulo_x, self.height - 65, f"— {periodo}")
        
        # Área para logo direita
        logo_direita_x = self.width - 120
        c.rect(logo_direita_x, logo_y, 80, logo_size, fill=0, stroke=1)
        
        c.setDash(1, 0)
    
    def _desenhar_titulo_secao(self, c, titulo, y_pos):
        """Desenha o título da seção"""
        c.setFillColor(self.cor_titulo_secao)
        c.roundRect(60, y_pos - 5, 320, 35, 5, fill=1, stroke=0)
        
        # USANDO FONTE CUSTOMIZADA BOLD
        c.setFillColor(colors.white)
        c.setFont(self.fonte_bold, 14)  # ← FONTE CUSTOMIZADA BOLD
        c.drawString(75, y_pos + 5, titulo.upper())
        
        return y_pos - 50
    
    def _desenhar_card(self, c, texto, x, y, width, height):
        """Desenha um card com borda pontilhada e texto"""
        c.setFillColor(self.cor_fundo)
        c.roundRect(x, y, width, height, 8, fill=1, stroke=0)
        
        c.setStrokeColor(self.cor_borda_card)
        c.setLineWidth(1.5)
        c.setDash(4, 4)
        c.roundRect(x, y, width, height, 8, fill=0, stroke=1)
        c.setDash(1, 0)
        
        # Texto dentro do card - USANDO FONTE CUSTOMIZADA
        c.setFillColor(self.cor_texto)
        c.setFont(self.fonte_regular, 11)  # ← FONTE CUSTOMIZADA REGULAR
        
        # Quebrar texto em múltiplas linhas
        palavras = texto.split()
        linha_atual = ""
        y_texto = y + height - 25
        margem_interna = 15
        max_width = width - (2 * margem_interna)
        line_height = 14
        
        for palavra in palavras:
            teste = (linha_atual + " " + palavra) if linha_atual else palavra
            
            if c.stringWidth(teste, self.fonte_regular, 11) < max_width:
                linha_atual = teste
            else:
                if linha_atual:
                    c.drawString(x + margem_interna, y_texto, linha_atual)
                    y_texto -= line_height
                linha_atual = palavra
        
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
            
            if c.stringWidth(teste, self.fonte_regular, 11) < max_width:
                linha_atual = teste
            else:
                num_linhas += 1
                linha_atual = palavra
        
        if linha_atual:
            num_linhas += 1
        
        return 20 + (num_linhas * 14) + 15
    
    def _desenhar_slide(self, c, slide, titulo_boletim, periodo):
        """Desenha um slide completo"""
        self._desenhar_header(c, titulo_boletim, periodo)
        
        y_pos = self.height - 120
        y_pos = self._desenhar_titulo_secao(c, slide['titulo_slide'], y_pos)
        
        bullets = slide['bullets']
        num_bullets = len(bullets)
        
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
        
        margem_lateral = 60
        espacamento = 20
        area_largura = self.width - (2 * margem_lateral)
        
        if cols == 1:
            card_width = area_largura
        elif cols == 2:
            card_width = (area_largura - espacamento) / 2
        else:
            card_width = (area_largura - (2 * espacamento)) / 3
        
        x_start = margem_lateral
        y_current = y_pos
        
        for i, bullet in enumerate(bullets):
            col = i % cols
            
            if col == 0 and i > 0:
                y_current -= 150
            
            x = x_start + (col * (card_width + espacamento))
            
            card_height = self._calcular_altura_card(c, bullet, card_width)
            card_height = max(card_height, 80)
            card_height = min(card_height, 200)
            
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

print("✅ Classe BoletimBradescoGenerator criada com suporte a fonte customizada!")
