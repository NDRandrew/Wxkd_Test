// JavaScript corrigido para exportTXT

function exportSelectedTXT() {
    console.log('=== exportSelectedTXT CORRECTED ===');
    
    var selected = document.querySelectorAll('.row-checkbox:checked');
    console.log('Selected checkboxes found:', selected.length);
    
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb, index) {
        // CORREÇÃO: Limpar completamente os IDs
        var rawValue = cb.value || '';
        var cleanId = rawValue.toString()
                             .replace(/\s+/g, '')  // Remove todos os espaços
                             .replace(/[^\w]/g, '') // Remove caracteres especiais, mantém só letras/números
                             .trim();
        
        console.log('Checkbox', index, '- raw:', "'" + rawValue + "'", 'clean:', "'" + cleanId + "'");
        
        if (cleanId && cleanId.length > 0) {
            ids.push(cleanId);
        }
    });
    
    console.log('Clean IDs collected:', ids);
    
    if (ids.length === 0) {
        alert('Nenhum ID válido encontrado nos checkboxes selecionados');
        return;
    }
    
    var filter = getCurrentFilter();
    console.log('Current filter:', filter);
    
    // Chamar exportação com IDs limpos
    exportTXTDataCorrected(ids.join(','), filter);
}

function exportTXTDataCorrected(selectedIds, filter) {
    console.log('=== exportTXTDataCorrected START ===');
    console.log('Clean selectedIds:', selectedIds);
    console.log('Filter:', filter);
    
    // URL simples (igual ao CSV que funciona)
    var url = 'wxkd.php?action=exportTXT&filter=' + filter;
    if (selectedIds) {
        url += '&ids=' + encodeURIComponent(selectedIds);
    }
    
    console.log('Request URL:', url);
    
    // AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log('=== RESPONSE RECEIVED ===');
            console.log('Response length:', xhr.responseText.length);
            console.log('Response preview:', xhr.responseText.substring(0, 300));
            
            try {
                // CORREÇÃO: Extrair XML da resposta (mesmo que tenha HTML)
                var xmlContent = extractXMLFromMixedResponse(xhr.responseText);
                
                if (!xmlContent) {
                    console.error('No XML found in response');
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                console.log('XML extracted:', xmlContent.substring(0, 200));
                
                // Parsear XML
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                // Verificar parsing errors
                var parserError = xmlDoc.getElementsByTagName('parsererror')[0];
                if (parserError) {
                    console.error('XML Parser Error:', parserError.textContent);
                    alert('Erro ao parsear XML');
                    return;
                }
                
                // Verificar success
                var success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    var errorElement = xmlDoc.getElementsByTagName('e')[0];
                    var errorMsg = errorElement ? errorElement.textContent : 'Erro desconhecido';
                    console.error('Server error:', errorMsg);
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                console.log('XML parsed successfully');
                
                // Verificar dados
                var rows = xmlDoc.getElementsByTagName('row');
                console.log('Rows found:', rows.length);
                
                if (rows.length === 0) {
                    alert('Nenhum registro encontrado para os IDs selecionados');
                    return;
                }
                
                // Converter para TXT
                var txtData = extractTXTFromXMLCorrected(xmlDoc);
                
                console.log('TXT generated - Length:', txtData.length);
                console.log('TXT preview:', txtData.substring(0, 200));
                
                if (txtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
                // Download
                var filename = 'dashboard_selected_' + filter + '_' + getCurrentTimestamp() + '.txt';
                downloadTXTFile(txtData, filename);
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        }
    };
    
    xhr.send();
}

// CORREÇÃO: Função para extrair XML de resposta mista (HTML + XML)
function extractXMLFromMixedResponse(responseText) {
    console.log('Extracting XML from mixed response...');
    
    // Procurar pelo início da tag <response>
    var startTag = '<response>';
    var endTag = '</response>';
    
    var startIndex = responseText.indexOf(startTag);
    if (startIndex === -1) {
        console.error('Start tag <response> not found');
        return null;
    }
    
    var endIndex = responseText.indexOf(endTag, startIndex);
    if (endIndex === -1) {
        console.error('End tag </response> not found');
        return null;
    }
    
    // Extrair apenas a parte XML
    var xmlContent = responseText.substring(startIndex, endIndex + endTag.length);
    
    console.log('XML extracted successfully, length:', xmlContent.length);
    
    return xmlContent;
}

// Função para converter XML para TXT (corrigida)
function extractTXTFromXMLCorrected(xmlDoc) {
    console.log('=== Converting XML to TXT ===');
    
    var txtContent = '';
    var rows = xmlDoc.getElementsByTagName('row');
    
    console.log('Processing', rows.length, 'rows');
    
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        
        var chave = getXMLNodeValue(row, 'chave_loja');
        var empresa = getXMLNodeValue(row, 'cod_empresa');
        var tipo = getXMLNodeValue(row, 'tipo');
        var dataContrato = getXMLNodeValue(row, 'data_contrato');
        var tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
        
        // Debug primeira linha
        if (i === 0) {
            console.log('First row values:');
            console.log('  chave_loja:', chave);
            console.log('  cod_empresa:', empresa);
            console.log('  tipo:', tipo);
        }
        
        // Converter para linha TXT
        var txtLine = formatToTXTLine(chave, empresa, tipo, dataContrato, tipoContrato);
        
        if (txtLine && txtLine.length === 117) {
            txtContent += txtLine + '\r\n';
        } else {
            console.warn('Invalid TXT line generated for row', i, 'length:', txtLine ? txtLine.length : 0);
        }
    }
    
    console.log('TXT conversion completed - Total length:', txtContent.length);
    
    return txtContent;
}

// Funções auxiliares (se não existirem)
function getXMLNodeValue(parentNode, tagName) {
    var node = parentNode.getElementsByTagName(tagName)[0];
    return node ? (node.textContent || node.text || '') : '';
}

function getCurrentTimestamp() {
    var now = new Date();
    var year = now.getFullYear();
    var month = ('0' + (now.getMonth() + 1)).slice(-2);
    var day = ('0' + now.getDate()).slice(-2);
    var hours = ('0' + now.getHours()).slice(-2);
    var minutes = ('0' + now.getMinutes()).slice(-2);
    var seconds = ('0' + now.getSeconds()).slice(-2);
    
    return year + '-' + month + '-' + day + '_' + hours + '-' + minutes + '-' + seconds;
}