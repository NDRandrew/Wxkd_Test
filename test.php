function exportSelectedTXT() {
    console.log('=== exportSelectedTXT FIXED VERSION ===');
    
    var selected = document.querySelectorAll('.row-checkbox:checked');
    console.log('Selected checkboxes found:', selected.length);
    
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb, index) {
        // CORREÇÃO: Limpar espaços extras do valor
        var cleanId = cb.value.toString().trim().replace(/\s+/g, '');
        console.log('Checkbox', index, '- raw value:', "'" + cb.value + "'", 'cleaned:', "'" + cleanId + "'");
        
        if (cleanId && cleanId !== '') {
            ids.push(cleanId);
        }
    });
    
    console.log('IDs collected (cleaned):', ids);
    console.log('IDs joined:', ids.join(','));
    
    if (ids.length === 0) {
        alert('Nenhum ID válido encontrado nos checkboxes selecionados');
        return;
    }
    
    var filter = getCurrentFilter();
    console.log('Current filter:', filter);
    
    // Chamar função de exportação
    exportTXTDataFixed(ids.join(','), filter);
}

function exportTXTDataFixed(selectedIds, filter) {
    console.log('=== exportTXTDataFixed START ===');
    console.log('selectedIds (clean):', selectedIds);
    console.log('filter:', filter);
    
    // CORREÇÃO: Adicionar parâmetro para forçar apenas XML
    var url = 'wxkd.php?action=exportTXT&ajax=1&filter=' + filter;
    if (selectedIds) {
        url += '&ids=' + encodeURIComponent(selectedIds);
    }
    
    console.log('Final URL:', url);
    
    // Fazer requisição AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        console.log('XHR readyState:', xhr.readyState, 'status:', xhr.status);
        
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log('=== XHR RESPONSE RECEIVED ===');
                console.log('Response length:', xhr.responseText.length);
                console.log('Response preview (first 200 chars):', xhr.responseText.substring(0, 200));
                
                // CORREÇÃO: Extrair apenas a parte XML
                var xmlContent = extractXMLFromResponse(xhr.responseText);
                
                if (!xmlContent) {
                    console.error('No valid XML found in response');
                    alert('Erro: resposta do servidor não contém XML válido');
                    return;
                }
                
                try {
                    // Parsear XML limpo
                    var parser = new DOMParser();
                    var xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                    
                    console.log('XML parsed successfully');
                    
                    // Verificar se há erros de parsing
                    var parserError = xmlDoc.getElementsByTagName('parsererror')[0];
                    if (parserError) {
                        console.error('XML Parser Error:', parserError.textContent);
                        alert('Erro ao parsear XML: ' + parserError.textContent);
                        return;
                    }
                    
                    // Verificar success
                    var success = xmlDoc.getElementsByTagName('success')[0];
                    console.log('Success element found:', !!success);
                    if (success) {
                        console.log('Success value:', success.textContent);
                    }
                    
                    if (!success || success.textContent !== 'true') {
                        console.error('Server returned success = false');
                        var errorElement = xmlDoc.getElementsByTagName('e')[0];
                        var errorMsg = errorElement ? errorElement.textContent : 'Erro desconhecido';
                        console.error('Error message:', errorMsg);
                        alert('Erro do servidor: ' + errorMsg);
                        return;
                    }
                    
                    // Debug: verificar dados
                    var rows = xmlDoc.getElementsByTagName('row');
                    console.log('Rows found in XML:', rows.length);
                    
                    if (rows.length === 0) {
                        console.warn('No rows found in XML response');
                        alert('Nenhum dado foi retornado pelo servidor para os IDs selecionados');
                        return;
                    }
                    
                    // Extrair dados do XML e converter para TXT
                    console.log('Converting XML to TXT...');
                    var txtData = extractTXTFromXML(xmlDoc);
                    
                    console.log('TXT data generated:');
                    console.log('  Length:', txtData.length);
                    console.log('  Lines:', txtData.split('\r\n').length);
                    console.log('  First line:', txtData.split('\r\n')[0]);
                    
                    if (txtData.length === 0) {
                        console.error('Generated TXT data is empty!');
                        alert('Erro: dados TXT gerados estão vazios');
                        return;
                    }
                    
                    // Download do arquivo
                    var filename = 'dashboard_selected_' + filter + '_' + getCurrentTimestamp() + '.txt';
                    console.log('Downloading file:', filename);
                    downloadTXTFile(txtData, filename);
                    
                } catch (e) {
                    console.error('Exception during processing:', e);
                    alert('Erro ao processar dados: ' + e.message);
                }
            } else {
                console.error('XHR failed with status:', xhr.status);
                alert('Erro na requisição: ' + xhr.status);
            }
        }
    };
    
    console.log('Sending XHR request...');
    xhr.send();
}

// CORREÇÃO: Função para extrair XML limpo da resposta
function extractXMLFromResponse(responseText) {
    console.log('Extracting XML from response...');
    
    // Procurar pelo início do XML
    var xmlStart = responseText.indexOf('<response>');
    if (xmlStart === -1) {
        console.error('No <response> tag found in response');
        return null;
    }
    
    // Procurar pelo fim do XML
    var xmlEnd = responseText.indexOf('</response>');
    if (xmlEnd === -1) {
        console.error('No </response> tag found in response');
        return null;
    }
    
    // Extrair apenas a parte XML
    var xmlContent = responseText.substring(xmlStart, xmlEnd + 11); // +11 para incluir </response>
    
    console.log('Extracted XML content:', xmlContent.substring(0, 200) + '...');
    
    return xmlContent;
}