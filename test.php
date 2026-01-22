# ============================================
# DOWNLOAD DO BOLETIM - VERSÃO CORRIGIDA
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
            <strong>Dica:</strong> Os espaços para logos foram marcados com bordas pontilhadas. 
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
            
            <button onclick="abrirPDF()" 
                    style="background: #28a745; color: white; padding: 18px 40px; 
                           border: none; border-radius: 8px; font-weight: bold; 
                           font-size: 18px; cursor: pointer; transition: all 0.3s ease;
                           box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
                Visualizar em Nova Aba
            </button>
        </div>
        
        <div id="mensagem-sucesso" style="display: none; margin-top: 20px; padding: 15px; 
                                          background: #d4edda; border: 1px solid #c3e6cb; 
                                          border-radius: 5px; color: #155724;">
            PDF aberto em nova aba! Se não abrir automaticamente, verifique se o bloqueador de pop-ups está ativo.
        </div>
    </div>
</div>

<script>
function abrirPDF() {{
    // Criar um Blob a partir do base64
    const base64Data = '{base64.b64encode(pdf_data).decode('utf-8')}';
    const byteCharacters = atob(base64Data);
    const byteNumbers = new Array(byteCharacters.length);
    
    for (let i = 0; i < byteCharacters.length; i++) {{
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }}
    
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], {{type: 'application/pdf'}});
    
    // Criar URL do blob
    const url = URL.createObjectURL(blob);
    
    // Abrir em nova aba
    const newWindow = window.open(url, '_blank');
    
    if (newWindow) {{
        document.getElementById('mensagem-sucesso').style.display = 'block';
        setTimeout(() => {{
            document.getElementById('mensagem-sucesso').style.display = 'none';
        }}, 5000);
    }} else {{
        alert('Pop-up bloqueado! Por favor, permita pop-ups para este site ou use o botão "Baixar Boletim PDF".');
    }}
}}

// Adicionar efeitos hover
document.querySelectorAll('a, button').forEach(element => {{
    element.addEventListener('mouseenter', function() {{
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
    }});
    
    element.addEventListener('mouseleave', function() {{
        this.style.transform = 'translateY(0)';
        const isButton = this.tagName === 'BUTTON';
        this.style.boxShadow = isButton ? 
            '0 4px 12px rgba(40,167,69,0.3)' : '0 4px 12px rgba(26,95,111,0.3)';
    }});
}});
</script>
''')

print("✅ Interface de download criada!")
print(f"📄 Nome do arquivo: {filename}")
