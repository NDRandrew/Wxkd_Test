// Add these functions to TestAc - 2.js following the same pattern as exportTXT

function exportSelectedAccess() {
    console.log('=== exportSelectedAccess INITIATED ===');
    
    var selected = document.querySelectorAll('.row-checkbox:checked');
    console.log('Selected checkboxes found:', selected.length);
    
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb, index) {
        var rawValue = cb.value || '';
        var cleanId = rawValue.toString()
                             .replace(/\s+/g, '')  
                             .replace(/[^\w]/g, '') 
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
    
    exportAccessDataProcessed(ids.join(','), filter);
}

function exportAccessDataProcessed(selectedIds, filter) {
    console.log('=== exportAccessDataProcessed START ===');
    console.log('Clean selectedIds:', selectedIds);
    console.log('Filter:', filter);
    
    var url = 'wxkd.php?action=exportAccess&filter=' + filter;
    if (selectedIds) {
        url += '&ids=' + encodeURIComponent(selectedIds);
    }
    
    console.log('Request URL:', url);
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            console.log('=== RESPONSE RECEIVED ===');
            console.log('Response length:', xhr.responseText.length);
            console.log('Response preview:', xhr.responseText.substring(0, 300));
            
            try {
                var xmlContent = extractXMLFromMixedResponse(xhr.responseText);
                
                if (!xmlContent) {
                    console.error('No XML found in response');
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                console.log('XML extracted:', xmlContent.substring(0, 200));
                
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                var parserError = xmlDoc.getElementsByTagName('parsererror')[0];
                if (parserError) {
                    console.error('XML Parser Error:', parserError.textContent);
                    alert('Erro ao parsear XML');
                    return;
                }
                
                var success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    var errorElement = xmlDoc.getElementsByTagName('e')[0];
                    var errorMsg = errorElement ? errorElement.textContent : 'Erro desconhecido';
                    console.error('Server error:', errorMsg);
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                console.log('XML parsed successfully');
                
                // Extract and download CSV files
                var csvFiles = extractAccessCSVFromXML(xmlDoc);
                
                console.log('CSV files generated - Count:', csvFiles.length);
                
                if (csvFiles.length === 0) {
                    alert('Erro: Nenhum arquivo CSV gerado');
                    return;
                }
                
                // Download first file immediately
                if (csvFiles[0]) {
                    downloadAccessCSVFile(csvFiles[0].content, csvFiles[0].filename);
                }
                
                // Download second file after delay if it exists
                if (csvFiles[1]) {
                    setTimeout(function() {
                        downloadAccessCSVFile(csvFiles[1].content, csvFiles[1].filename);
                    }, 1500);
                }
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        }
    };
    
    xhr.send();
}

function extractAccessCSVFromXML(xmlDoc) {
    console.log('=== Converting XML to CSV files ===');
    
    var csvFiles = [];
    var timestamp = getCurrentTimestamp();
    var filter = getCurrentFilter();
    
    // Extract first file data (AV/UN/PR)
    var file1Data = xmlDoc.getElementsByTagName('avUnPrData')[0];
    if (file1Data) {
        var csvContent1 = 'COD_EMPRESA\r\n';
        var empresas1 = file1Data.getElementsByTagName('empresa');
        
        for (var i = 0; i < empresas1.length; i++) {
            var empresa = empresas1[i].textContent || empresas1[i].text || '';
            if (empresa) {
                csvContent1 += empresa + '\r\n';
            }
        }
        
        csvFiles.push({
            filename: 'access_av_un_pr_' + filter + '_' + timestamp + '.csv',
            content: csvContent1
        });
        
        console.log('First CSV file prepared - AV/UN/PR data, records:', empresas1.length);
    }
    
    // Extract second file data (OP) if it exists
    var file2Data = xmlDoc.getElementsByTagName('opData')[0];
    if (file2Data) {
        var csvContent2 = 'COD_EMPRESA\r\n';
        var empresas2 = file2Data.getElementsByTagName('empresa');
        
        for (var i = 0; i < empresas2.length; i++) {
            var empresa = empresas2[i].textContent || empresas2[i].text || '';
            if (empresa) {
                csvContent2 += empresa + '\r\n';
            }
        }
        
        csvFiles.push({
            filename: 'access_op_' + filter + '_' + timestamp + '.csv',
            content: csvContent2
        });
        
        console.log('Second CSV file prepared - OP data, records:', empresas2.length);
    }
    
    console.log('CSV conversion completed - Total files:', csvFiles.length);
    
    return csvFiles;
}

function downloadAccessCSVFile(csvContent, filename) {
    var csvWithBOM = '\uFEFF' + csvContent;
    
    var blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
    
    var link = document.createElement('a');
    if (link.download !== undefined) {
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up the URL object
        setTimeout(function() {
            URL.revokeObjectURL(url);
        }, 1000);
    } else {
        alert('Seu navegador não suporta download automático. Copie o conteúdo:\n\n' + csvContent.substring(0, 500) + '...');
    }
}