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
                console.log('Raw XML Response:', xmlData); // Debug logging
                
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        let detailsHtml = '';
                        let recordCount = 0;
                        
                        $xml.find('detailData row').each(function() {
                            const row = {};
                            const $row = $(this);
                            
                            // Debug: Log the XML structure
                            console.log('Processing XML row:', this);
                            console.log('Row children count:', $row.children().length);
                            
                            // Parse XML data more carefully
                            $row.children().each(function() {
                                const tagName = this.tagName || this.nodeName;
                                const textContent = $(this).text() || '';
                                row[tagName] = textContent;
                                console.log(`${tagName}: ${textContent}`); // Debug each field
                            });
                            
                            console.log('Parsed row object:', row); // Debug parsed object
                            
                            recordCount++;
                            const detailId = `${chaveLote}_${recordCount}`;
                            
                            // Build table row with proper error handling and defaults
                            detailsHtml += `
                                <tr>
                                    <td class="checkbox-column">
                                        <label>
                                            <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                                                   value="${detailId}" data-chave-lote="${chaveLote}">
                                            <span class="text"></span>
                                        </label>
                                    </td>
                                    <td>${self.getFieldValue(row, ['CHAVE_LOJA', 'chave_loja'])}</td>
                                    <td>${self.getFieldValue(row, ['NOME_LOJA', 'nome_loja'])}</td>
                                    <td>${self.getFieldValue(row, ['COD_EMPRESA', 'cod_empresa'])}</td>
                                    <td>${self.getFieldValue(row, ['COD_LOJA', 'cod_loja'])}</td>
                                    <td>${self.getFieldValue(row, ['TIPO_CORRESPONDENTE', 'tipo_correspondente'])}</td>
                                    <td>${self.getFieldValue(row, ['DATA_CONCLUSAO', 'data_conclusao'])}</td>
                                    <td>${self.getFieldValue(row, ['DATA_SOLICITACAO', 'data_solicitacao'])}</td>
                                    <td>${self.formatCurrency(self.getFieldValue(row, ['DEP_DINHEIRO', 'dep_dinheiro']))}</td>
                                    <td>${self.formatCurrency(self.getFieldValue(row, ['DEP_CHEQUE', 'dep_cheque']))}</td>
                                    <td>${self.formatCurrency(self.getFieldValue(row, ['REC_RETIRADA', 'rec_retirada']))}</td>
                                    <td>${self.formatCurrency(self.getFieldValue(row, ['SAQUE_CHEQUE', 'saque_cheque']))}</td>
                                    <td>${self.getFieldValue(row, ['SEGUNDA_VIA_CARTAO', 'segunda_via_cartao'])}</td>
                                    <td>${self.getFieldValue(row, ['HOLERITE_INSS', 'holerite_inss'])}</td>
                                    <td>${self.getFieldValue(row, ['CONS_INSS', 'cons_inss'])}</td>
                                    <td>${self.getFieldValue(row, ['PROVA_DE_VIDA', 'prova_de_vida'])}</td>
                                    <td>${self.formatDate(self.getFieldValue(row, ['DATA_CONTRATO', 'data_contrato']))}</td>
                                    <td>${self.getFieldValue(row, ['TIPO_CONTRATO', 'tipo_contrato'])}</td>
                                    <td>${self.formatDate(self.getFieldValue(row, ['DATA_LOG', 'data_log']))}</td>
                                    <td><span class="badge badge-info">${self.getFieldValue(row, ['FILTRO', 'filtro'])}</span></td>
                                </tr>
                            `;
                        });
                        
                        console.log('Generated HTML:', detailsHtml); // Debug generated HTML
                        
                        if (recordCount > 0) {
                            tbody.html(detailsHtml);
                        } else {
                            tbody.html('<tr><td colspan="20" class="text-center text-muted">Nenhum detalhe encontrado</td></tr>');
                        }
                        
                        tbody.append(`
                            <tr class="info">
                                <td colspan="20" class="text-center">
                                    <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
                                </td>
                            </tr>
                        `);
                        
                        HistoricoCheckboxModule.init();
                        
                    } else {
                        const errorMsg = $xml.find('e').text() || 'Erro desconhecido';
                        console.error('Server error:', errorMsg);
                        tbody.html(`<tr><td colspan="20" class="text-center text-danger">Erro ao carregar detalhes: ${errorMsg}</td></tr>`);
                    }
                    
                } catch (e) {
                    console.error('Error loading historico details: ', e);
                    tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro ao processar dados</td></tr>');
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
    
    // Helper function to get field value with fallback options
    getFieldValue: function(row, fieldNames) {
        for (let i = 0; i < fieldNames.length; i++) {
            const fieldName = fieldNames[i];
            if (row[fieldName] !== undefined && row[fieldName] !== null && row[fieldName] !== '') {
                return this.escapeHtml(row[fieldName]);
            }
        }
        return '';
    },
    
    // Helper function to escape HTML entities
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    // Helper function to format currency values
    formatCurrency: function(value) {
        if (!value || value === '' || value === '0' || value === '0.00') {
            return '0';
        }
        
        // If it's already formatted as currency, return as is
        if (value.includes('R$')) {
            return value;
        }
        
        // If it's a number, format it as currency
        const numValue = parseFloat(value);
        if (!isNaN(numValue) && numValue > 0) {
            return `R$ ${numValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        }
        
        return value;
    },
    
    onAccordionExpand: function(e) {
        const panel = $(e.target);
        const tbody = panel.find('.historico-details');
        const loadButton = tbody.find('.load-details');
        
        if (loadButton.length > 0 && !loadButton.prop('disabled')) {
            loadButton.click();
        }
    },
    
    formatDate: function(dateString) {
        if (!dateString || dateString === '') {
            return '';
        }
        
        // Handle different date formats
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

            return `${day}/${month}/${year}`;
        }
        
        // If it's already in dd/mm/yyyy format, return as is
        if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
            return dateString;
        }
        
        // Try to parse other formats
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            const day = ('0' + date.getDate()).slice(-2);
            const month = ('0' + (date.getMonth() + 1)).slice(-2);
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }
        
        return dateString;
    }
};