// Add this new module for handling historico accordion
const HistoricoModule = {
    init: function() {
        $(document).on('click', '.load-details', this.loadDetails.bind(this));
        $(document).on('shown.bs.collapse', '.panel-collapse', this.onAccordionExpand.bind(this));
    },
    
    loadDetails: function(e) {
        e.preventDefault();
        const button = $(e.currentTarget);
        const chaveLote = button.data('chave-lote');
        const tbody = button.closest('tbody');
        
        // Prevent multiple clicks
        if (button.prop('disabled')) {
            return;
        }
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
        
        $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        let detailsHtml = '';
                        let recordCount = 0;
                        
                        $xml.find('detailData row').each(function() {
                            const row = {};
                            $(this).children().each(function() {
                                row[this.tagName] = $(this).text();
                            });
                            
                            recordCount++;
                            detailsHtml += `
                                <tr>
                                    <td>${row.CHAVE_LOJA || ''}</td>
                                    <td>${row.NOME_LOJA || ''}</td>
                                    <td>${row.COD_EMPRESA || ''}</td>
                                    <td>${row.COD_LOJA || ''}</td>
                                    <td><span class="badge badge-info">${row.TIPO_CORRESPONDENTE || ''}</span></td>
                                    <td class="text-right">R$ ${this.formatMoney(row.DEP_DINHEIRO || 0)}</td>
                                    <td class="text-right">R$ ${this.formatMoney(row.DEP_CHEQUE || 0)}</td>
                                    <td class="text-right">R$ ${this.formatMoney(row.REC_RETIRADA || 0)}</td>
                                    <td class="text-right">R$ ${this.formatMoney(row.SAQUE_CHEQUE || 0)}</td>
                                    <td><span class="badge ${this.getStatusBadgeClass(row.SEGUNDA_VIA_CARTAO)}">${row.SEGUNDA_VIA_CARTAO || ''}</span></td>
                                    <td><span class="badge ${this.getStatusBadgeClass(row.HOLERITE_INSS)}">${row.HOLERITE_INSS || ''}</span></td>
                                    <td><span class="badge ${this.getStatusBadgeClass(row.CONS_INSS)}">${row.CONS_INSS || ''}</span></td>
                                    <td><span class="badge ${this.getStatusBadgeClass(row.PROVA_DE_VIDA)}">${row.PROVA_DE_VIDA || ''}</span></td>
                                    <td>${this.formatDate(row.DATA_CONTRATO)}</td>
                                    <td><small>${row.TIPO_CONTRATO || ''}</small></td>
                                </tr>
                            `;
                        });
                        
                        if (recordCount > 0) {
                            tbody.html(detailsHtml);
                        } else {
                            tbody.html('<tr><td colspan="15" class="text-center text-muted">Nenhum detalhe encontrado</td></tr>');
                        }
                        
                        // Add summary row
                        tbody.append(`
                            <tr class="info">
                                <td colspan="15" class="text-center">
                                    <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
                                </td>
                            </tr>
                        `);
                    } else {
                        tbody.html('<tr><td colspan="15" class="text-center text-danger">Erro ao carregar detalhes</td></tr>');
                    }
                    
                } catch (e) {
                    console.error('Error loading historico details: ', e);
                    tbody.html('<tr><td colspan="15" class="text-center text-danger">Erro ao processar dados</td></tr>');
                }
            })
            .fail(() => {
                tbody.html('<tr><td colspan="15" class="text-center text-danger">Erro na requisição</td></tr>');
            })
            .always(() => {
                button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
            });
    },
    
    onAccordionExpand: function(e) {
        const panel = $(e.target);
        const tbody = panel.find('.historico-details');
        const loadButton = tbody.find('.load-details');
        
        // Auto-load details when accordion is expanded for the first time
        if (loadButton.length > 0 && !loadButton.prop('disabled')) {
            loadButton.click();
        }
    },
    
    formatMoney: function(value) {
        const num = parseFloat(value) || 0;
        return num.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    formatDate: function(dateString) {
        if (!dateString) return '—';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('pt-BR');
    },
    
    getStatusBadgeClass: function(status) {
        if (status === 'Apto') {
            return 'badge-success';
        } else if (status === 'Nao Apto') {
            return 'badge-danger';
        } else {
            return 'badge-secondary';
        }
    }
};

// Update the FilterModule to handle historico display
const FilterModuleUpdated = {
    ...FilterModule, // Keep all existing properties
    
    loadTableData: function(filter) {
        showLoading();
        
        // Show/hide appropriate containers based on filter
        if (filter === 'historico') {
            $('.table-scrollable').hide();
            $('#historicoAccordion').show();
        } else {
            $('#historicoAccordion').hide();
            $('.table-scrollable').show();
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        }

        $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        const cardData = {
                            cadastramento: $xml.find('cardData cadastramento').text(),
                            descadastramento: $xml.find('cardData descadastramento').text(),
                            historico: $xml.find('cardData historico').text()
                        };
                        
                        this.updateCardCounts(cardData);
                        
                        if (filter === 'historico') {
                            // Reload the page to get new accordion data
                            const newUrl = new URL(window.location);
                            newUrl.searchParams.set('filter', filter);
                            window.location.href = newUrl.toString();
                        } else {
                            // Handle normal table data
                            const tableData = [];
                            $xml.find('tableData row').each(function() {
                                const row = {};
                                $(this).children().each(function() {
                                    row[this.tagName] = $(this).text();
                                });
                                tableData.push(row);
                            });

                            PaginationModule.replaceTableDataEnhanced(tableData);
                            PaginationModule.currentPage = 1;
                            PaginationModule.updateTable();
                            
                            CheckboxModule.clearSelections();
                            
                            setTimeout(() => {
                                CheckboxModule.init();
                                CheckboxModule.updateSelectAllState();
                                CheckboxModule.updateExportButton();
                            }, 200);
                            
                            setTimeout(() => {
                                StatusFilterModule.reapplyAfterDataLoad();
                                hideLoading();
                            }, 400);
                        }
                    }
                } catch (e) {
                    console.error('Error parsing XML: ', e);
                    if (filter !== 'historico') {
                        $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
                    }
                    hideLoading();
                }
            })
            .fail((jqXHR, textStatus, errorThrown) => {
                console.error('AJAX failed:', textStatus, errorThrown);
                if (filter !== 'historico') {
                    $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
                }
                hideLoading();
            });
    }
};

// Replace FilterModule with the updated version
Object.assign(FilterModule, FilterModuleUpdated);

// Update the TXT export function to show success message with log info
function exportTXTData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportTXT&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const xmlContent = extractXMLFromMixedResponse(responseText);
                if (!xmlContent) {
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    const validationError = xmlDoc.getElementsByTagName('validation_error')[0];
                    if (validationError && validationError.textContent === 'true') {
                        const invalidRecords = Array.from(xmlDoc.getElementsByTagName('record')).map(record => ({
                            cod_empresa: record.getElementsByTagName('cod_empresa')[0]?.textContent || '',
                            error: record.getElementsByTagName('error_msg')[0]?.textContent || record.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido'
                        }));
                        
                        showValidationAlert(invalidRecords);
                        return;
                    }
                    
                    const errorMsg = xmlDoc.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido';
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                const txtData = extractTXTFromXML(xmlDoc);
                
                if (txtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
                const filename = `dashboard_selected_${filter}_${getCurrentTimestamp()}.txt`;
                downloadTXTFile(txtData, filename);
                
                // Show success message with log information
                const logInsert = xmlDoc.getElementsByTagName('logInsert')[0];
                if (logInsert) {
                    const logSuccess = logInsert.getElementsByTagName('success')[0]?.textContent === 'true';
                    const chaveLote = logInsert.getElementsByTagName('chaveLote')[0]?.textContent;
                    const recordsLogged = logInsert.getElementsByTagName('recordsLogged')[0]?.textContent;
                    
                    if (logSuccess) {
                        showSuccessMessage(
                            `Arquivo TXT exportado com sucesso!\n\n` +
                            `• Registros processados: ${recordsLogged}\n` +
                            `• Lote criado: #${chaveLote}\n` +
                            `• Status: Registrado no histórico\n\n` +
                            `Você pode visualizar este lote na aba "Histórico".`
                        );
                    }
                }
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição: ' + error.message);
        });
}

// Add success message function
function showSuccessMessage(message) {
    // Create a modal-like success message
    const alertHtml = `
        <div class="alert alert-success success-alert" style="
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            z-index: 9999; 
            min-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 5px;
        ">
            <button class="close" onclick="$(this).parent().remove()" style="
                color: #3c763d !important; 
                opacity: 0.7; 
                position: absolute; 
                top: 10px; 
                right: 15px;
            ">
                <i class="fa fa-times"></i>
            </button>
            <div style="padding: 20px 40px 20px 20px;">
                <i class="fa fa-check-circle" style="color: #3c763d; font-size: 20px; margin-right: 10px;"></i>
                <strong>Sucesso!</strong>
                <pre style="background: none; border: none; color: #3c763d; margin-top: 10px; white-space: pre-wrap;">${message}</pre>
            </div>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remove after 8 seconds
    setTimeout(() => {
        $('.success-alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 8000);
}

// Update the document ready function
$(document).ready(() => {
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init();
    HistoricoModule.init(); // Add this line
});