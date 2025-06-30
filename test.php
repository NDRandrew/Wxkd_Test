// JavaScript TXT - AJAX na mesma página (sem redirecionamento)

/**
 * Exportar todos para TXT (AJAX - mesma página)
 */
function exportAllTXT() {
    console.log('exportAllTXT called');
    
    var filter = getCurrentFilter();
    console.log('Current filter:', filter);
    
    // Mostrar loading
    showTXTLoading();
    
    // AJAX para buscar TXT
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter + '&ajax=1';
    makeTXTRequest(url, 'dashboard_' + filter + '.txt');
}

/**
 * Exportar selecionados para TXT (AJAX - mesma página)
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
    
    console.log('Selected IDs:', ids);
    
    var filter = getCurrentFilter();
    
    // Mostrar loading
    showTXTLoading();
    
    // AJAX para buscar TXT
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter + '&ids=' + ids.join(',') + '&ajax=1';
    makeTXTRequest(url, 'dashboard_selected_' + filter + '.txt');
}

/**
 * Fazer requisição AJAX para TXT
 */
function makeTXTRequest(url, filename) {
    console.log('Making TXT request to:', url);
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            hideTXTLoading();
            
            if (xhr.status === 200) {
                console.log('TXT response received');
                
                try {
                    // Tentar parsear como XML primeiro
                    var parser = new DOMParser();
                    var xmlDoc = parser.parseFromString(xhr.responseText, 'text/xml');
                    
                    var success = xmlDoc.getElementsByTagName('success')[0];
                    if (success && success.textContent === 'true') {
                        // É XML válido
                        var contentNode = xmlDoc.getElementsByTagName('txtContent')[0];
                        if (contentNode) {
                            var txtContent = contentNode.textContent || contentNode.text || '';
                            txtContent = txtContent.replace(/\|\|NEWLINE\|\|/g, '\r\n');
                            downloadTXTContent(txtContent, filename);
                            return;
                        }
                    }
                    
                    // Se não é XML válido, usar o texto bruto
                    var responseText = xhr.responseText;
                    
                    // Verificar se é HTML (erro) ou TXT válido
                    if (responseText.indexOf('<html>') !== -1 || responseText.indexOf('<!DOCTYPE') !== -1) {
                        alert('Erro: Resposta inválida do servidor');
                        console.error('HTML response received instead of TXT');
                        return;
                    }
                    
                    // Assumir que é conteúdo TXT válido
                    downloadTXTContent(responseText, filename);
                    
                } catch (e) {
                    console.error('Error processing TXT response:', e);
                    alert('Erro ao processar resposta do servidor');
                }
            } else {
                console.error('TXT request failed:', xhr.status);
                alert('Erro na requisição: ' + xhr.status);
            }
        }
    };
    
    // Prevenir cache
    xhr.setRequestHeader('Cache-Control', 'no-cache');
    xhr.send();
}

/**
 * Fazer download do conteúdo TXT
 */
function downloadTXTContent(txtContent, filename) {
    console.log('Downloading TXT content, length:', txtContent.length);
    console.log('Filename:', filename);
    console.log('Content preview:', txtContent.substring(0, 200) + '...');
    
    try {
        // Criar Blob
        var blob = new Blob([txtContent], { type: 'text/plain;charset=utf-8' });
        
        // Criar URL temporária
        var url = window.URL.createObjectURL(blob);
        
        // Criar link de download
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        // Adicionar ao DOM, clicar e remover
        document.body.appendChild(link);
        link.click();
        
        // Cleanup
        setTimeout(function() {
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        }, 100);
        
        console.log('TXT download completed successfully');
        
    } catch (e) {
        console.error('Error downloading TXT:', e);
        
        // Fallback - mostrar em textarea
        showTXTFallback(txtContent, filename);
    }
}

/**
 * Fallback - mostrar TXT em textarea
 */
function showTXTFallback(txtContent, filename) {
    var overlay = document.createElement('div');
    overlay.id = 'txt-fallback-overlay';
    overlay.style.cssText = 
        'position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
        'background: rgba(0,0,0,0.8); z-index: 9999; display: flex; ' +
        'align-items: center; justify-content: center;';
    
    var modal = document.createElement('div');
    modal.style.cssText = 
        'background: white; padding: 30px; border-radius: 10px; ' +
        'max-width: 90%; max-height: 90%; overflow: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);';
    
    modal.innerHTML = 
        '<h3>Conteúdo TXT - ' + filename + '</h3>' +
        '<p>O download automático falhou. Copie o conteúdo abaixo e salve como arquivo .txt:</p>' +
        '<textarea id="txt-content" style="width: 600px; height: 400px; font-family: monospace; font-size: 12px;">' + 
        txtContent + '</textarea><br><br>' +
        '<button onclick="document.getElementById(\'txt-fallback-overlay\').remove()">Fechar</button> ' +
        '<button onclick="document.getElementById(\'txt-content\').select(); document.execCommand(\'copy\'); alert(\'Conteúdo copiado!\')">Copiar Tudo</button>';
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
}

/**
 * Mostrar loading
 */
function showTXTLoading() {
    var loading = document.createElement('div');
    loading.id = 'txt-loading';
    loading.style.cssText = 
        'position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
        'background: rgba(0,0,0,0.7); z-index: 9998; display: flex; ' +
        'align-items: center; justify-content: center;';
    
    loading.innerHTML = 
        '<div style="background: white; padding: 30px; border-radius: 10px; text-align: center;">' +
            '<div style="font-size: 24px; margin-bottom: 10px;">📄</div>' +
            '<div style="font-weight: bold; margin-bottom: 5px;">Gerando arquivo TXT...</div>' +
            '<div style="color: #666;">Processando dados, aguarde...</div>' +
            '<div style="margin-top: 15px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; ' +
            'border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 15px auto;"></div>' +
        '</div>' +
        '<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
    
    document.body.appendChild(loading);
}

/**
 * Esconder loading
 */
function hideTXTLoading() {
    var loading = document.getElementById('txt-loading');
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