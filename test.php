// Add this function to handle validation alerts
function showValidationAlert(invalidRecords) {
    // Remove any existing validation alerts
    $('.validation-alert').remove();
    
    var detailsHtml = '';
    for (var i = 0; i < invalidRecords.length; i++) {
        var record = invalidRecords[i];
        detailsHtml += '<div style="margin: 5px 0; padding: 5px; background-color: rgba(255,255,255,0.1); border-radius: 3px;">' +
            '<strong>Empresa:</strong> ' + record.cod_empresa + 
            '<br><strong>Motivo:</strong> ' + record.error +
            '</div>';
    }
    
    var alertHtml = '<div class="alert alert-warning validation-alert" style="margin: 10px 0; max-height: 400px; overflow-y: auto;">' +
        '<button class="close" onclick="$(this).parent().remove()" style="color:rgb(54, 150, 198) !important; opacity:0.3; position:relative; top:5px;">' +
        '<i class="fa fa-times"></i>' +
        '</button>' +
        '<span>' +
        '<i class="fa-fw fa fa-warning" style="font-size:15px !important; position:relative; top:2px;"></i>' +
        '<strong>Registros não podem ser exportados como TXT:</strong><br><br>' +
        detailsHtml +
        '<br><i><strong>Clique no botão "Exportar Access" para processar estes registros.</strong></i>' +
        '</span>' +
        '</div>';
    
    // Insert the alert after the status filter indicator or filter indicator
    if ($('#statusFilterIndicator').is(':visible')) {
        $('#statusFilterIndicator').after(alertHtml);
    } else if ($('#filterIndicator').is(':visible')) {
        $('#filterIndicator').after(alertHtml);
    } else {
        // Insert after the cards section
        $('.row.mb-4').after(alertHtml);
    }
    
    // Scroll to the alert
    $('html, body').animate({
        scrollTop: $('.validation-alert').offset().top - 20
    }, 500);
    
    // Auto-remove after 20 seconds (increased time due to more content)
    setTimeout(function() {
        $('.validation-alert').fadeOut();
    }, 20000);
}

// Update the exportTXTDataCorrected function to handle validation errors
function exportTXTDataCorrected(selectedIds, filter) {
    console.log('=== exportTXTDataCorrected START ===');
    console.log('Clean selectedIds:', selectedIds);
    console.log('Filter:', filter);
    var url = 'wxkd.php?action=exportTXT&filter=' + filter;
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
                    
                    // *** CHECK FOR VALIDATION ERROR ***
                    var validationError = xmlDoc.getElementsByTagName('validation_error')[0];
                    if (validationError && validationError.textContent === 'true') {
                        console.log('Validation error detected');
                        
                        var invalidRecords = xmlDoc.getElementsByTagName('record');
                        var invalidList = [];
                        
                        for (var i = 0; i < invalidRecords.length; i++) {
                            var record = invalidRecords[i];
                            var codEmpresa = record.getElementsByTagName('cod_empresa')[0];
                            var errorMsg = record.getElementsByTagName('e')[0];
                            
                            if (codEmpresa) {
                                invalidList.push({
                                    cod_empresa: codEmpresa.textContent || codEmpresa.text || '',
                                    error: errorMsg ? (errorMsg.textContent || errorMsg.text || 'Erro desconhecido') : 'Erro desconhecido'
                                });
                            }
                        }
                        
                        console.log('Invalid records with details:', invalidList);
                        showValidationAlert(invalidList);
                        return;
                    }
                    
                    // Handle other types of errors
                    var errorElement = xmlDoc.getElementsByTagName('e')[0];
                    var errorMsg = errorElement ? errorElement.textContent : 'Erro desconhecido';
                    console.error('Server error:', errorMsg);
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                console.log('XML parsed successfully - proceeding with TXT export');
                
                var rows = xmlDoc.getElementsByTagName('row');
                console.log('Rows found:', rows.length);
                
                if (rows.length === 0) {
                    alert('Nenhum registro encontrado para os IDs selecionados');
                    return;
                }
                
                var txtData = extractTXTFromXMLCorrected(xmlDoc);
                
                console.log('TXT generated - Length:', txtData.length);
                console.log('TXT preview:', txtData.substring(0, 200));
                
                if (txtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
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

// Also update the exportSelectedTXT function to clear any existing validation alerts
function exportSelectedTXT() {
    console.log('=== exportSelectedTXT CORRECTED ===');
    
    // Clear any existing validation alerts
    $('.validation-alert').remove();
    
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
    
    exportTXTDataCorrected(ids.join(','), filter);

    setTimeout(() => {
            fetch(FilterModule.loadTableData(filter)) 
            .then(response => response.text())
            .then(html => {
            document.getElementById('content').innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading content:', error);
            });
        }, 3000);
}