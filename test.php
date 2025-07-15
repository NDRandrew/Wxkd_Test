// JavaScript functions that create downloads using Blob - replace the previous functions:

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
    
    exportAccessData(ids.join(','), filter);
}

function exportAccessData(selectedIds, filter) {
    console.log('=== exportAccessData START ===');
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
                var parser = new DOMParser();
                var xmlDoc = parser.parseFromString(xhr.responseText, 'text/xml');
                
                var parserError = xmlDoc.getElementsByTagName('parsererror')[0];
                if (parserError) {
                    console.error('XML Parser Error:', parserError.textContent);
                    alert('Erro ao processar resposta XML');
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
                
                // Process first file (AV/UN/PR)
                var file1Element = xmlDoc.getElementsByTagName('file1')[0];
                if (file1Element) {
                    var filename1 = file1Element.getElementsByTagName('filename')[0].textContent;
                    var content1 = file1Element.getElementsByTagName('content')[0].textContent;
                    var count1 = file1Element.getElementsByTagName('count')[0].textContent;
                    
                    console.log('File 1 - Name:', filename1, 'Count:', count1);
                    
                    if (count1 > 0) {
                        downloadCSVFile(content1, filename1);
                    } else {
                        console.log('File 1 is empty, skipping download');
                    }
                }
                
                // Process second file (OP) if it exists
                var file2Element = xmlDoc.getElementsByTagName('file2')[0];
                if (file2Element) {
                    var filename2 = file2Element.getElementsByTagName('filename')[0].textContent;
                    var content2 = file2Element.getElementsByTagName('content')[0].textContent;
                    var count2 = file2Element.getElementsByTagName('count')[0].textContent;
                    
                    console.log('File 2 - Name:', filename2, 'Count:', count2);
                    
                    if (count2 > 0) {
                        // Download second file after a short delay
                        setTimeout(function() {
                            downloadCSVFile(content2, filename2);
                        }, 1000);
                    } else {
                        console.log('File 2 is empty, skipping download');
                    }
                } else {
                    console.log('No second file (OP data) found');
                }
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        }
    };
    
    xhr.send();
}

function downloadCSVFile(csvContent, filename) {
    console.log('=== downloadCSVFile START ===');
    console.log('Filename:', filename);
    console.log('Content length:', csvContent.length);
    console.log('Content preview:', csvContent.substring(0, 100));
    
    try {
        // Add BOM for UTF-8
        var csvWithBOM = '\uFEFF' + csvContent;
        
        // Create Blob
        var blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
        
        // Create download link
        var link = document.createElement('a');
        
        if (link.download !== undefined) {
            // Modern browsers
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
            
            console.log('File download triggered:', filename);
        } else {
            // Fallback for older browsers
            alert('Seu navegador não suporta download automático. Copie o conteúdo:\n\n' + csvContent.substring(0, 500) + '...');
        }
        
    } catch (e) {
        console.error('Download error:', e);
        alert('Erro ao baixar arquivo: ' + e.message);
    }
}