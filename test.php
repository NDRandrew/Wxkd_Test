// JavaScript para TXT sem headers - adicionar ao final de Wxkd_script.js

/**
 * Exportar todos os registros para TXT (sem headers)
 */
function exportAllTXT() {
    console.log('exportAllTXT called');
    var filter = getCurrentFilter();
    exportTXTData('', filter);
}

/**
 * Exportar registros selecionados para TXT (sem headers)
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
    exportTXTData(ids.join(','), filter);
}

/**
 * Função principal para exportar TXT via XML
 */
function exportTXTData(selectedIds, filter) {
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter;
    if (selectedIds) {
        url += '&ids=' + selectedIds;
    }
    
    console.log('Requesting TXT data from:', url);
    
    // Fazer requisição AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                // Parsear XML
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xhr.responseText, 'text/xml');
                
                // Verificar se teve sucesso
                var success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    var errorNode = xmlDoc.getElementsByTagName('e')[0];
                    var errorMsg = errorNode ? errorNode.textContent : 'Erro desconhecido';
                    alert('Erro ao gerar TXT: ' + errorMsg);
                    return;
                }
                
                // Extrair conteúdo TXT e nome do arquivo
                var txtContentNode = xmlDoc.getElementsByTagName('txtContent')[0];
                var filenameNode = xmlDoc.getElementsByTagName('filename')[0];
                
                if (!txtContentNode) {
                    alert('Erro: Conteúdo TXT não encontrado na resposta');
                    return;
                }
                
                var txtContent = txtContentNode.textContent || txtContentNode.text || '';
                var filename = filenameNode ? (filenameNode.textContent || filenameNode.text) : 'dashboard.txt';
                
                // Fazer download do arquivo TXT
                downloadTXTFile(txtContent, filename);
                
            } catch (e) {
                console.error('Erro ao processar XML:', e);
                alert('Erro ao processar resposta do servidor');
            }
        }
    };
    xhr.send();
}

/**
 * Fazer download do arquivo TXT
 */
function downloadTXTFile(txtContent, filename) {
    try {
        // Criar Blob com o conteúdo TXT
        var blob = new Blob([txtContent], { type: 'text/plain;charset=utf-8;' });
        
        // Criar link de download
        var link = document.createElement('a');
        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            console.log('TXT file downloaded:', filename);
        } else {
            // Fallback para navegadores antigos
            alert('Seu navegador não suporta download automático. Conteúdo TXT:\n\n' + txtContent.substring(0, 500) + '...');
        }
    } catch (e) {
        console.error('Erro ao fazer download:', e);
        alert('Erro ao fazer download do arquivo');
    }
}

/**
 * Obter filtro atual (reutilizar se já existir)
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