// JavaScript TXT - IGUAL ao CSV que funciona

function exportAllTXT() {
    var filter = getCurrentFilter();
    exportTXTData('', filter);
}

function exportSelectedTXT() {
    var selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb) {
        ids.push(cb.value);
    });
    
    var filter = getCurrentFilter();
    exportTXTData(ids.join(','), filter);
}

function exportTXTData(selectedIds, filter) {
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter;
    if (selectedIds) {
        url += '&ids=' + selectedIds;
    }
    
    // Fazer requisição AJAX (igual ao CSV)
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                // Parsear XML (igual ao CSV)
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xhr.responseText, 'text/xml');
                
                // Verificar se teve sucesso
                var success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    alert('Erro ao buscar dados para exportação');
                    return;
                }
                
                // Extrair dados do XML e converter para TXT
                var txtData = extractTXTFromXML(xmlDoc);
                
                // Gerar e fazer download do TXT
                downloadTXTFile(txtData, 'dashboard_' + filter + '_' + getCurrentTimestamp() + '.txt');
                
            } catch (e) {
                console.error('Erro ao processar XML:', e);
                alert('Erro ao processar dados');
            }
        }
    };
    xhr.send();
}

function extractTXTFromXML(xmlDoc) {
    var txtContent = '';
    
    // Extrair dados das linhas (igual ao CSV)
    var rows = xmlDoc.getElementsByTagName('row');
    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        
        var chave = getXMLNodeValue(row, 'chave_loja');
        var empresa = getXMLNodeValue(row, 'cod_empresa');
        var tipo = getXMLNodeValue(row, 'tipo');
        var dataContrato = getXMLNodeValue(row, 'data_contrato');
        var tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
        
        // Converter para formato TXT de 117 posições
        var txtLine = formatToTXTLine(chave, empresa, tipo, dataContrato, tipoContrato);
        txtContent += txtLine + '\r\n';
    }
    
    return txtContent;
}

function formatToTXTLine(chave, empresa, tipo, dataContrato, tipoContrato) {
    // Formatar campos para TXT (117 posições)
    
    // CHAVE_LOJA - 10 posições, numérico, zeros à esquerda
    var chaveTXT = padLeft(cleanNumeric(chave), 10, '0');
    
    // COD_EMPRESA - 8 posições, numérico, zeros à esquerda  
    var empresaTXT = padLeft(cleanNumeric(empresa), 8, '0');
    
    // TIPO - 20 posições, texto, espaços à direita
    var tipoTXT = padRight(cleanText(tipo), 20, ' ');
    
    // DATA_CONTRATO - 10 posições, formato YYYY-MM-DD
    var dataTXT = formatDate(dataContrato, 10);
    
    // TIPO_CONTRATO - 25 posições, texto, espaços à direita
    var tipoContratoTXT = padRight(cleanText(tipoContrato), 25, ' ');
    
    // STATUS - 10 posições, texto, espaços à direita
    var statusTXT = padRight('ATIVO', 10, ' ');
    
    // OBSERVACOES - 34 posições, texto, espaços à direita
    var obsTXT = padRight('CONTRATO APROVADO', 34, ' ');
    
    // Juntar tudo (total: 117 posições)
    var linha = chaveTXT + empresaTXT + tipoTXT + dataTXT + tipoContratoTXT + statusTXT + obsTXT;
    
    // Garantir exatamente 117 caracteres
    if (linha.length > 117) {
        linha = linha.substring(0, 117);
    } else if (linha.length < 117) {
        linha = padRight(linha, 117, ' ');
    }
    
    return linha;
}

// Funções auxiliares para formatação
function cleanNumeric(value) {
    return String(value).replace(/[^0-9]/g, '') || '0';
}

function cleanText(value) {
    return String(value).replace(/[^A-Za-z0-9\s]/g, '').toUpperCase().trim();
}

function formatDate(dateValue, length) {
    if (!dateValue) return padLeft('', length, '0');
    
    // Tentar extrair ano
    var year = '';
    if (dateValue.match(/(\d{4})/)) {
        year = dateValue.match(/(\d{4})/)[1];
        return year + '-01-01'; // Data padrão
    }
    
    return padLeft('', length, '0');
}

function padLeft(str, length, char) {
    str = String(str);
    while (str.length < length) {
        str = char + str;
    }
    return str.substring(0, length);
}

function padRight(str, length, char) {
    str = String(str);
    while (str.length < length) {
        str = str + char;
    }
    return str.substring(0, length);
}

function getXMLNodeValue(parentNode, tagName) {
    var node = parentNode.getElementsByTagName(tagName)[0];
    return node ? (node.textContent || node.text || '') : '';
}

function downloadTXTFile(txtContent, filename) {
    // Adicionar BOM UTF-8 para compatibilidade
    var txtWithBOM = '\uFEFF' + txtContent;
    
    // Criar Blob (igual ao CSV)
    var blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
    
    // Criar link de download (igual ao CSV)
    var link = document.createElement('a');
    if (link.download !== undefined) {
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } else {
        // Fallback para navegadores antigos
        alert('Seu navegador não suporta download automático. Copie o conteúdo:\n\n' + txtContent.substring(0, 500) + '...');
    }
}

function getCurrentFilter() {
    var activeCard = document.querySelector('.card.active');
    if (activeCard) {
        if (activeCard.id === 'card-cadastramento') return 'cadastramento';
        if (activeCard.id === 'card-descadastramento') return 'descadastramento';
        if (activeCard.id === 'card-historico') return 'historico';
    }
    return 'all';
}

function getCurrentTimestamp() {
    var now = new Date();
    return now.getFullYear() + '-' + 
           String(now.getMonth() + 1).padStart(2, '0') + '-' + 
           String(now.getDate()).padStart(2, '0') + '_' +
           String(now.getHours()).padStart(2, '0') + '-' +
           String(now.getMinutes()).padStart(2, '0') + '-' +
           String(now.getSeconds()).padStart(2, '0');
}