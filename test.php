// JavaScript para forçar download TXT - substituir funções existentes

/**
 * Exportar todos os registros para TXT
 */
function exportAllTXT() {
    console.log('exportAllTXT called');
    var filter = getCurrentFilter();
    
    // Mostrar loading
    showExportLoading('TXT');
    
    // Fazer requisição AJAX
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter;
    makeTextExportRequest(url, 'dashboard_' + filter + '.txt');
}

/**
 * Exportar registros selecionados para TXT
 */
function exportSelectedTXT() {
    console.log('exportSelectedTXT called');
    
    var selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Por favor, selecione pelo menos um registro para exportar.');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb) {
        ids.push(cb.value);
    });
    
    var filter = getCurrentFilter();
    
    // Mostrar loading
    showExportLoading('TXT');
    
    // Fazer requisição AJAX
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter + '&ids=' + ids.join(',');
    makeTextExportRequest(url, 'dashboard_selected_' + filter + '.txt');
}

/**
 * Fazer requisição para exportação TXT
 */
function makeTextExportRequest(url, defaultFilename) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideExportLoading();
            
            if (xhr.status === 200) {
                try {
                    // Parsear XML
                    var parser = new DOMParser();
                    var xmlDoc = parser.parseFromString(xhr.responseText, 'text/xml');
                    
                    // Verificar sucesso
                    var success = xmlDoc.getElementsByTagName('success')[0];
                    if (!success || success.textContent !== 'true') {
                        var errorNode = xmlDoc.getElementsByTagName('e')[0];
                        var errorMsg = errorNode ? errorNode.textContent : 'Erro desconhecido';
                        alert('Erro ao gerar TXT: ' + errorMsg);
                        return;
                    }
                    
                    // Extrair dados
                    var txtContentNode = xmlDoc.getElementsByTagName('txtContent')[0];
                    var filenameNode = xmlDoc.getElementsByTagName('filename')[0];
                    var lineCountNode = xmlDoc.getElementsByTagName('lineCount')[0];
                    
                    if (!txtContentNode) {
                        alert('Erro: Conteúdo TXT não encontrado');
                        return;
                    }
                    
                    var escapedContent = txtContentNode.textContent || txtContentNode.text || '';
                    var filename = filenameNode ? (filenameNode.textContent || filenameNode.text) : defaultFilename;
                    var lineCount = lineCountNode ? (lineCountNode.textContent || lineCountNode.text) : '0';
                    
                    // Reconstruir conteúdo TXT
                    var txtContent = escapedContent.replace(/\|\|NEWLINE\|\|/g, '\r\n');
                    
                    console.log('TXT content received:', lineCount, 'lines');
                    console.log('TXT preview:', txtContent.substring(0, 200) + '...');
                    
                    // Forçar download
                    forceTXTDownload(txtContent, filename);
                    
                } catch (e) {
                    console.error('Erro ao processar XML:', e);
                    alert('Erro ao processar resposta do servidor');
                }
            } else {
                alert('Erro na requisição: ' + xhr.status);
            }
        }
    };
    
    xhr.send();
}

/**
 * Forçar download do arquivo TXT
 */
function forceTXTDownload(txtContent, filename) {
    try {
        // Criar Blob
        var blob = new Blob([txtContent], { 
            type: 'text/plain;charset=utf-8' 
        });
        
        // Criar URL temporária
        var url = window.URL.createObjectURL(blob);
        
        // Criar link invisível
        var link = document.createElement('a');
        link.style.display = 'none';
        link.href = url;
        link.download = filename;
        
        // Adicionar ao DOM, clicar e remover
        document.body.appendChild(link);
        link.click();
        
        // Cleanup
        setTimeout(function() {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 100);
        
        console.log('TXT download forced:', filename);
        
    } catch (e) {
        console.error('Erro ao forçar download:', e);
        
        // Fallback - mostrar conteúdo em textarea
        var textarea = document.createElement('textarea');
        textarea.value = txtContent;
        textarea.style.width = '100%';
        textarea.style.height = '400px';
        
        var container = document.createElement('div');
        container.innerHTML = '<h3>Conteúdo TXT (copie e salve manualmente):</h3>';
        container.appendChild(textarea);
        
        // Substituir conteúdo da página temporariamente
        var originalContent = document.body.innerHTML;
        document.body.innerHTML = '';
        document.body.appendChild(container);
        
        var backButton = document.createElement('button');
        backButton.textContent = 'Voltar';
        backButton.onclick = function() {
            document.body.innerHTML = originalContent;
        };
        container.appendChild(backButton);
    }
}

/**
 * Mostrar loading durante exportação
 */
function showExportLoading(format) {
    var loading = document.createElement('div');
    loading.id = 'export-loading';
    loading.innerHTML = 
        '<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
        'background: rgba(0,0,0,0.7); z-index: 9999; display: flex; ' +
        'align-items: center; justify-content: center;">' +
            '<div style="background: white; padding: 30px; border-radius: 10px; ' +
            'text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">' +
                '<div style="font-size: 24px; margin-bottom: 10px;">📄</div>' +
                '<div style="font-weight: bold; margin-bottom: 5px;">Gerando arquivo ' + format + '...</div>' +
                '<div style="color: #666;">Aguarde, processando dados...</div>' +
                '<div style="margin-top: 15px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; ' +
                'border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; ' +
                'margin: 15px auto;"></div>' +
            '</div>' +
        '</div>' +
        '<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
    
    document.body.appendChild(loading);
}

/**
 * Esconder loading
 */
function hideExportLoading() {
    var loading = document.getElementById('export-loading');
    if (loading) {
        document.body.removeChild(loading);
    }
}

/**
 * Obter filtro atual
 */
function getCurrentFilter() {
    var activeCard = document.querySelector('.card.active');
    if (activeCard) {
        if (activeCard.id === 'card-cadastramento') return 'cadastramento';
        if (activeCard.id === 'card-descadastramento') return 'descadastramento';
        if (activeCard.id === 'card-historico') return 'historico';
    }
    return 'all';
}