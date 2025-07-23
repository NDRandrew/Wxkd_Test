// Enhanced HistoricoModule with robust XML parsing and fallback handling
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
        
        if (button.prop('disabled')) {
            return;
        }
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
        
        const self = this;
        
        $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
            .done((xmlData) => {
                console.log('Raw XML Response:', xmlData);
                
                try {
                    // Clean the XML response - remove any HTML/PHP output before XML
                    const cleanedXml = this.cleanXMLResponse(xmlData);
                    console.log('Cleaned XML:', cleanedXml);
                    
                    const $xml = $(cleanedXml);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        let detailsHtml = '';
                        let recordCount = 0;
                        
                        // Try multiple parsing methods
                        const rows = this.parseXMLRows($xml);
                        console.log('Parsed rows:', rows);
                        
                        if (rows.length === 0) {
                            console.warn('No rows found in XML, trying alternative parsing...');
                            tbody.html('<tr><td colspan="20" class="text-center text-muted">Nenhum detalhe encontrado no XML</td></tr>');
                            return;
                        }
                        
                        rows.forEach((row, index) => {
                            recordCount++;
                            const detailId = `${chaveLote}_${recordCount}`;
                            
                            console.log(`Building row ${recordCount}:`, row);
                            
                            // Ensure we have proper data structure
                            const rowData = this.normalizeRowData(row);
                            console.log(`Normalized row ${recordCount}:`, rowData);
                            
                            detailsHtml += this.buildTableRow(rowData, detailId, chaveLote);
                        });
                        
                        console.log('Final HTML length:', detailsHtml.length);
                        
                        if (recordCount > 0) {
                            tbody.html(detailsHtml);
                            
                            // Add summary row
                            tbody.append(`
                                <tr class="info">
                                    <td colspan="20" class="text-center">
                                        <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
                                    </td>
                                </tr>
                            `);
                        } else {
                            tbody.html('<tr><td colspan="20" class="text-center text-muted">Nenhum detalhe encontrado</td></tr>');
                        }
                        
                        // Re-initialize checkbox module
                        setTimeout(() => {
                            HistoricoCheckboxModule.init();
                        }, 100);
                        
                    } else {
                        const errorMsg = $xml.find('e').text() || 'Erro desconhecido';
                        console.error('Server error:', errorMsg);
                        tbody.html(`<tr><td colspan="20" class="text-center text-danger">Erro: ${errorMsg}</td></tr>`);
                    }
                    
                } catch (e) {
                    console.error('Error parsing XML response:', e);
                    console.error('XML Data:', xmlData);
                    
                    // Try fallback parsing
                    this.tryFallbackParsing(xmlData, tbody, chaveLote);
                }
            })
            .fail((xhr, status, error) => {
                console.error('AJAX request failed:', status, error);
                console.error('Response text:', xhr.responseText);
                tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro na requisição</td></tr>');
            })
            .always(() => {
                button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
            });
    },
    
    cleanXMLResponse: function(xmlData) {
        // Find the actual XML content
        const xmlStart = xmlData.indexOf('<response>');
        const xmlEnd = xmlData.lastIndexOf('</response>') + 11;
        
        if (xmlStart !== -1 && xmlEnd !== -1) {
            return xmlData.substring(xmlStart, xmlEnd);
        }
        
        return xmlData;
    },
    
    parseXMLRows: function($xml) {
        const rows = [];
        
        $xml.find('detailData row').each(function() {
            const row = {};
            const $row = $(this);
            
            // Method 1: Parse by child elements
            $row.children().each(function() {
                const tagName = this.tagName || this.nodeName;
                const textContent = $(this).text() || '';
                if (tagName && tagName !== '#text') {
                    row[tagName.toUpperCase()] = textContent;
                }
            });
            
            // Method 2: If no children found, try parsing attributes or text content
            if (Object.keys(row).length === 0) {
                console.warn('No child elements found, trying alternative parsing for row:', this);
                
                // Check if data is in attributes
                const attributes = this.attributes;
                if (attributes && attributes.length > 0) {
                    for (let i = 0; i < attributes.length; i++) {
                        const attr = attributes[i];
                        row[attr.name.toUpperCase()] = attr.value;
                    }
                }
                
                // Check if it's a text node with delimited data
                const textContent = $(this).text();
                if (textContent && textContent.length > 0) {
                    row['RAW_TEXT'] = textContent;
                }
            }
            
            if (Object.keys(row).length > 0) {
                rows.push(row);
            }
        });
        
        return rows;
    },
    
    normalizeRowData: function(row) {
        // Ensure consistent field naming and provide defaults
        const normalized = {
            CHAVE_LOJA: row.CHAVE_LOJA || row.chave_loja || '',
            NOME_LOJA: row.NOME_LOJA || row.nome_loja || '',
            COD_EMPRESA: row.COD_EMPRESA || row.cod_empresa || '',
            COD_LOJA: row.COD_LOJA || row.cod_loja || '',
            TIPO_CORRESPONDENTE: row.TIPO_CORRESPONDENTE || row.tipo_correspondente || '',
            DATA_CONCLUSAO: row.DATA_CONCLUSAO || row.data_conclusao || '',
            DATA_SOLICITACAO: row.DATA_SOLICITACAO || row.data_solicitacao || '',
            DEP_DINHEIRO: row.DEP_DINHEIRO || row.dep_dinheiro || '0',
            DEP_CHEQUE: row.DEP_CHEQUE || row.dep_cheque || '0',
            REC_RETIRADA: row.REC_RETIRADA || row.rec_retirada || '0',
            SAQUE_CHEQUE: row.SAQUE_CHEQUE || row.saque_cheque || '0',
            SEGUNDA_VIA_CARTAO: row.SEGUNDA_VIA_CARTAO || row.segunda_via_cartao || '',
            HOLERITE_INSS: row.HOLERITE_INSS || row.holerite_inss || '',
            CONS_INSS: row.CONS_INSS || row.cons_inss || '',
            PROVA_DE_VIDA: row.PROVA_DE_VIDA || row.prova_de_vida || '',
            DATA_CONTRATO: row.DATA_CONTRATO || row.data_contrato || '',
            TIPO_CONTRATO: row.TIPO_CONTRATO || row.tipo_contrato || '',
            DATA_LOG: row.DATA_LOG || row.data_log || '',
            FILTRO: row.FILTRO || row.filtro || ''
        };
        
        // Handle raw text parsing if needed
        if (row.RAW_TEXT && Object.keys(row).length === 1) {
            console.warn('Attempting to parse raw text data:', row.RAW_TEXT);
            // This would need custom parsing logic based on your data format
            // For now, we'll display it as a single cell to identify the issue
            normalized.RAW_DATA = row.RAW_TEXT;
        }
        
        return normalized;
    },
    
    buildTableRow: function(rowData, detailId, chaveLote) {
        // If we have raw data that couldn't be parsed, show it for debugging
        if (rowData.RAW_DATA) {
            return `
                <tr class="debug-row">
                    <td class="checkbox-column">
                        <label>
                            <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                                   value="${detailId}" data-chave-lote="${chaveLote}">
                            <span class="text"></span>
                        </label>
                    </td>
                    <td colspan="19" style="background-color: #fff3cd; padding: 10px;">
                        <strong>Dados não estruturados (debug):</strong><br>
                        <pre style="margin-top: 5px; font-size: 11px;">${this.escapeHtml(rowData.RAW_DATA)}</pre>
                        <small><em>Contate o desenvolvedor para corrigir a estrutura dos dados.</em></small>
                    </td>
                </tr>
            `;
        }
        
        // Normal structured row
        return `
            <tr>
                <td class="checkbox-column">
                    <label>
                        <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                               value="${detailId}" data-chave-lote="${chaveLote}">
                        <span class="text"></span>
                    </label>
                </td>
                <td>${this.escapeHtml(rowData.CHAVE_LOJA)}</td>
                <td>${this.escapeHtml(rowData.NOME_LOJA)}</td>
                <td>${this.escapeHtml(rowData.COD_EMPRESA)}</td>
                <td>${this.escapeHtml(rowData.COD_LOJA)}</td>
                <td>${this.escapeHtml(rowData.TIPO_CORRESPONDENTE)}</td>
                <td>${this.formatDate(rowData.DATA_CONCLUSAO)}</td>
                <td>${this.formatDate(rowData.DATA_SOLICITACAO)}</td>
                <td>${this.formatCurrency(rowData.DEP_DINHEIRO)}</td>
                <td>${this.formatCurrency(rowData.DEP_CHEQUE)}</td>
                <td>${this.formatCurrency(rowData.REC_RETIRADA)}</td>
                <td>${this.formatCurrency(rowData.SAQUE_CHEQUE)}</td>
                <td>${this.escapeHtml(rowData.SEGUNDA_VIA_CARTAO)}</td>
                <td>${this.escapeHtml(rowData.HOLERITE_INSS)}</td>
                <td>${this.escapeHtml(rowData.CONS_INSS)}</td>
                <td>${this.escapeHtml(rowData.PROVA_DE_VIDA)}</td>
                <td>${this.formatDate(rowData.DATA_CONTRATO)}</td>
                <td>${this.escapeHtml(rowData.TIPO_CONTRATO)}</td>
                <td>${this.formatDate(rowData.DATA_LOG)}</td>
                <td><span class="badge badge-info">${this.escapeHtml(rowData.FILTRO)}</span></td>
            </tr>
        `;
    },
    
    tryFallbackParsing: function(xmlData, tbody, chaveLote) {
        console.log('Attempting fallback parsing...');
        
        // Try to extract any meaningful data from the response
        try {
            // Look for patterns in the raw data
            const dataMatches = xmlData.match(/<row>(.*?)<\/row>/g);
            
            if (dataMatches && dataMatches.length > 0) {
                let fallbackHtml = '';
                
                dataMatches.forEach((match, index) => {
                    const detailId = `${chaveLote}_${index + 1}`;
                    const content = match.replace(/<\/?row>/g, '');
                    
                    fallbackHtml += `
                        <tr class="fallback-row">
                            <td class="checkbox-column">
                                <label>
                                    <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                                           value="${detailId}" data-chave-lote="${chaveLote}">
                                    <span class="text"></span>
                                </label>
                            </td>
                            <td colspan="19" style="background-color: #f8f9fa; padding: 10px;">
                                <strong>Dados brutos (fallback parsing):</strong><br>
                                <pre style="margin-top: 5px; font-size: 11px; white-space: pre-wrap;">${this.escapeHtml(content)}</pre>
                            </td>
                        </tr>
                    `;
                });
                
                tbody.html(fallbackHtml);
            } else {
                tbody.html('<tr><td colspan="20" class="text-center text-danger">Não foi possível parsear os dados XML</td></tr>');
            }
            
        } catch (fallbackError) {
            console.error('Fallback parsing also failed:', fallbackError);
            tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro crítico no parsing dos dados</td></tr>');
        }
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    formatCurrency: function(value) {
        if (!value || value === '' || value === '0' || value === '0.00') {
            return 'R$ 0,00';
        }
        
        if (value.toString().includes('R$')) {
            return value;
        }
        
        const numValue = parseFloat(value);
        if (!isNaN(numValue)) {
            return `R$ ${numValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        }
        
        return value;
    },
    
    formatDate: function(dateString) {
        if (!dateString || dateString === '') {
            return '';
        }
        
        const parts = dateString.match(/^([A-Za-z]+) (\d{1,2}) (\d{4}) (.+)$/);
        if (parts) {
            const months = {
                Jan: '01', Feb: '02', Mar: '03', Apr: '04',
                May: '05', Jun: '06', Jul: '07', Aug: '08',
                Sep: '09', Oct: '10', Nov: '11', Dec: '12'
            };
            
            const month = months[parts[1]];
            const day = ('0' + parts[2]).slice(-2);
            const year = parts[3];
            
            return month ? `${day}/${month}/${year}` : dateString;
        }
        
        if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
            return dateString;
        }
        
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            const day = ('0' + date.getDate()).slice(-2);
            const month = ('0' + (date.getMonth() + 1)).slice(-2);
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }
        
        return dateString;
    },
    
    onAccordionExpand: function(e) {
        const panel = $(e.target);
        const tbody = panel.find('.historico-details');
        const loadButton = tbody.find('.load-details');
        
        if (loadButton.length > 0 && !loadButton.prop('disabled')) {
            loadButton.click();
        }
    }
};