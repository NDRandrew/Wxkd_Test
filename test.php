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
        
        # Cores fixas do gradiente
        self.cor_centro = colors.HexColor('#1a5f6f')  # Verde azulado no centro
        self.cor_lateral = colors.HexColor('#040720')  # Azul escuro nas laterais
        
        # Outras cores
        self.cor_titulo_secao = colors.HexColor('#999999')
        self.cor_fundo = colors.white
        self.cor_texto = colors.HexColor('#2c2c2c')
        self.cor_card = colors.HexColor('#f5f5f5')
        self.cor_borda_card = colors.HexColor('#cccccc')
    
    def _interpolar_cor(self, cor1, cor2, fator):
        """
        Interpola entre duas cores
        fator: 0.0 = cor1, 1.0 = cor2
        """
        r1, g1, b1 = cor1.red, cor1.green, cor1.blue
        r2, g2, b2 = cor2.red, cor2.green, cor2.blue
        
        r = r1 + (r2 - r1) * fator
        g = g1 + (g2 - g1) * fator
        b = b1 + (b2 - b1) * fator
        
        return colors.Color(r, g, b)
    
    def _desenhar_gradiente_horizontal(self, c, x, y, width, height, cor_centro, cor_lateral):
        """
        Desenha um gradiente horizontal do centro para as laterais
        """
        num_faixas = 100  # Quanto mais faixas, mais suave o gradiente
        largura_faixa = width / num_faixas
        meio = num_faixas / 2
        
        for i in range(num_faixas):
            # Calcular a distância do centro (0.0 no centro, 1.0 nas extremidades)
            distancia_centro = abs(i - meio) / meio
            
            # Interpolar cor
            cor = self._interpolar_cor(cor_centro, cor_lateral, distancia_centro)
            c.setFillColor(cor)
            
            # Desenhar faixa vertical
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
            c, 
            0, 
            header_y, 
            self.width, 
            header_height, 
            self.cor_centro, 
            self.cor_lateral
        )
        
        # Área para logo esquerda (50x50 pixels aproximadamente)
        logo_x = 30
        logo_y = self.height - 65
        logo_size = 50
        
        # Desenhar placeholder para logo
        c.setStrokeColor(colors.white)
        c.setLineWidth(1)
        c.setDash(3, 3)
        c.rect(logo_x, logo_y, logo_size, logo_size, fill=0, stroke=1)
        
        # Título do boletim
        c.setFillColor(colors.white)
        c.setFont("Helvetica-Bold", 28)
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
            
            if c.stringWidth(teste, "Helvetica", 11) < max_width:
                linha_atual = teste
            else:
                # Desenhar linha atual
                if linha_atual:
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
        # Header com gradiente
        self._desenhar_header(c, titulo_boletim, periodo)
        
        # Título da seção
        y_pos = self.height - 120
        y_pos = self._desenhar_titulo_secao(c, slide['titulo_slide'], y_pos)
        
        # Calcular grid de cards
        bullets = slide['bullets']
        num_bullets = len(bullets)
        
        # Layout: determinar quantas colunas e linhas
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

print("✅ Classe BoletimBradescoGenerator criada com gradiente!")



------------------------


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
    
    # Adicionar slides (SEM passar cor, já está fixa no código)
    print("📄 Adicionando slide 1...")
    gerador.adicionar_slide(
        slide_1_data['titulo_slide'],
        slide_1_data['conteudo']
    )
    
    print("📄 Adicionando slide 2...")
    gerador.adicionar_slide(
        slide_2_data['titulo_slide'],
        slide_2_data['conteudo']
    )
    
    print("📄 Adicionando slide 3...")
    gerador.adicionar_slide(
        slide_3_data['titulo_slide'],
        slide_3_data['conteudo']
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
    print(f"🎨 Header: Gradiente #1a5f6f → #040720")
    print("="*60)
    
except Exception as e:
    print(f"\n❌ ERRO: {e}")
    import traceback
    traceback.print_exc()


--------


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

# Metadados (SEM cor_header)
metadata = {
    "titulo_boletim": "BOLETIM Qualidade de Dados",
    "periodo": "Dez 2024"
}

print("✅ Dados mockados carregados!")
print(f"📊 Slide 1: {slide_1_data['titulo_slide']}")
print(f"📊 Slide 2: {slide_2_data['titulo_slide']}")
print(f"📊 Slide 3: {slide_3_data['titulo_slide']}")
