const TxtGenerationModal = {
    modalId: 'txtGenerationModal',
    currentData: [],
    selectedIds: [],
    
    init: function() {
        this.createModal();
        this.attachEventListeners();
    },
    
    createModal: function() {
        // Remove existing modal if it exists
        $(`#${this.modalId}`).remove();
        
        const modalHtml = `
            <div class="modal fade" id="${this.modalId}" tabindex="-1" role="dialog" aria-labelledby="${this.modalId}Label">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title" id="${this.modalId}Label">
                                <i class="fa fa-file-text-o"></i> Geração de TXT - Seleção de Registros
                            </h4>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="selectAllTxtModal" class="form-check-input"> 
                                            Selecionar Todos
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <span class="badge badge-info" id="selectedCountTxt">0 selecionados</span>
                                </div>
                            </div>
                            
                            <div id="txtModalLoading" class="text-center" style="padding: 40px; display: none;">
                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                <p>Carregando dados...</p>
                            </div>
                            
                            <div id="txtModalError" class="alert alert-danger" style="display: none;">
                                <i class="fa fa-exclamation-triangle"></i>
                                <span id="txtModalErrorMessage">Erro ao carregar dados</span>
                            </div>
                            
                            <div id="txtModalContent" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="50">
                                                <input type="checkbox" id="headerSelectAll" class="form-check-input">
                                            </th>
                                            <th>Código Empresa</th>
                                            <th>Código Loja</th>
                                            <th>Nome Loja</th>
                                            <th>Tipo Correspondente</th>
                                        </tr>
                                    </thead>
                                    <tbody id="txtModalTableBody">
                                        <!-- Content will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">
                                <i class="fa fa-times"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" id="generateTxtBtn" disabled>
                                <i class="fa fa-download"></i> Gerar TXT (<span id="selectedCountFooter">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    },
    
    attachEventListeners: function() {
        const self = this;
        
        // Select all functionality
        $(document).on('change', `#selectAllTxtModal, #headerSelectAll`, function() {
            const isChecked = $(this).is(':checked');
            $(`#${self.modalId} .record-checkbox`).prop('checked', isChecked);
            self.updateSelectedCount();
        });
        
        // Individual checkbox change
        $(document).on('change', `#${self.modalId} .record-checkbox`, function() {
            self.updateSelectAllState();
            self.updateSelectedCount();
        });
        
        // Generate TXT button
        $(document).on('click', '#generateTxtBtn', function() {
            self.generateTXT();
        });
        
        // Modal cleanup on hide
        $(`#${self.modalId}`).on('hidden.bs.modal', function() {
            self.cleanup();
        });
    },
    
    show: function(selectedIds, filter) {
        this.selectedIds = selectedIds;
        this.currentFilter = filter;
        
        // Show modal
        $(`#${this.modalId}`).modal('show');
        
        // Load data
        this.loadData();
    },
    
    loadData: function() {
        const self = this;
        
        // Show loading
        $('#txtModalLoading').show();
        $('#txtModalContent').hide();
        $('#txtModalError').hide();
        
        // Prepare URL with selected IDs
        const idsParam = this.selectedIds.join(',');
        const url = `wxkd.php?action=getTxtModalData&filter=${this.currentFilter}&ids=${encodeURIComponent(idsParam)}`;
        
        $.get(url)
            .done(function(xmlData) {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        self.populateModal($xml);
                    } else {
                        const errorMsg = $xml.find('e').text() || 'Erro ao carregar dados';
                        self.showError(errorMsg);
                    }
                } catch (e) {
                    console.error('Error parsing XML:', e);
                    self.showError('Erro ao processar resposta do servidor');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX failed:', status, error);
                self.showError('Erro na comunicação com o servidor');
            })
            .always(function() {
                $('#txtModalLoading').hide();
            });
    },
    
    populateModal: function($xml) {
        const self = this;
        this.currentData = [];
        let tableHtml = '';
        
        $xml.find('row').each(function(index) {
            const row = {};
            
            // Extract data from XML
            row.cod_empresa = $(this).find('cod_empresa').text() || '';
            row.cod_loja = $(this).find('cod_loja').text() || '';
            row.nome_loja = $(this).find('nome_loja').text() || '';
            row.chave_loja = $(this).find('chave_loja').text() || '';
            
            // Determine tipo correspondente based on dates (similar to original logic)
            row.tipo_correspondente = self.getTipoCorrespondenteFromXML($(this));
            
            // Store complete row data
            self.currentData.push(row);
            
            // Generate table row
            const rowId = `txtrow_${index}`;
            tableHtml += `
                <tr>
                    <td>
                        <label>
                            <input type="checkbox" class="form-check-input record-checkbox" 
                                   value="${index}" id="${rowId}" data-index="${index}">
                            <span class="text"></span>
                        </label>
                    </td>
                    <td><strong>${self.escapeHtml(row.cod_empresa)}</strong></td>
                    <td><strong>${self.escapeHtml(row.cod_loja)}</strong></td>
                    <td>${self.escapeHtml(row.nome_loja)}</td>
                    <td>
                        <span class="badge ${self.getTipoBadgeClass(row.tipo_correspondente)}">
                            ${row.tipo_correspondente}
                        </span>
                    </td>
                </tr>
            `;
        });
        
        $('#txtModalTableBody').html(tableHtml);
        $('#txtModalContent').show();
        
        // Auto-check all records initially
        $('.record-checkbox').prop('checked', true);
        $('#selectAllTxtModal, #headerSelectAll').prop('checked', true);
        this.updateSelectedCount();
    },
    
    getTipoCorrespondenteFromXML: function($row) {
        const tipoCampos = {
            'AVANCADO': 'AV',
            'PRESENCA': 'PR', 
            'UNIDADE_NEGOCIO': 'UN',
            'ORGAO_PAGADOR': 'OP'
        };
        
        const cutoff = new Date(2025, 5, 1);
        let mostRecentDate = null;
        let mostRecentType = '';
        
        // Check each field for dates after cutoff
        for (const campo in tipoCampos) {
            const raw = $row.find(campo.toLowerCase()).text();
            if (raw) {
                const date = this.parseDate(raw);
                if (date && date > cutoff) {
                    if (mostRecentDate === null || date > mostRecentDate) {
                        mostRecentDate = date;
                        mostRecentType = tipoCampos[campo];
                    }
                }
            }
        }
        
        return mostRecentType || 'N/A';
    },
    
    parseDate: function(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return null;
        }
        
        const parts = dateString.trim().split('/');
        if (parts.length !== 3) {
            return null;
        }
        
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = parseInt(parts[2], 10);
        
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            return null;
        }
        
        return new Date(year, month, day);
    },
    
    getTipoBadgeClass: function(tipo) {
        const classes = {
            'AV': 'badge-success',
            'PR': 'badge-info',
            'UN': 'badge-warning',
            'OP': 'badge-primary'
        };
        return classes[tipo] || 'badge-default';
    },
    
    updateSelectAllState: function() {
        const totalCheckboxes = $('.record-checkbox').length;
        const checkedCheckboxes = $('.record-checkbox:checked').length;
        
        const selectAllCheckboxes = $('#selectAllTxtModal, #headerSelectAll');
        
        if (checkedCheckboxes === 0) {
            selectAllCheckboxes.prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            selectAllCheckboxes.prop('indeterminate', false).prop('checked', true);
        } else {
            selectAllCheckboxes.prop('indeterminate', true).prop('checked', false);
        }
    },
    
    updateSelectedCount: function() {
        const checkedCount = $('.record-checkbox:checked').length;
        $('#selectedCountTxt').text(`${checkedCount} selecionados`);
        $('#selectedCountFooter').text(checkedCount);
        
        $('#generateTxtBtn').prop('disabled', checkedCount === 0);
    },
    
    generateTXT: function() {
        const self = this;
        const selectedIndexes = [];
        
        $('.record-checkbox:checked').each(function() {
            selectedIndexes.push(parseInt($(this).data('index')));
        });
        
        if (selectedIndexes.length === 0) {
            alert('Selecione pelo menos um registro');
            return;
        }
        
        // Generate TXT content
        let txtContent = '';
        
        selectedIndexes.forEach(index => {
            const row = this.currentData[index];
            const empresa = row.cod_empresa;
            const codigoLoja = row.cod_loja;
            const tipoCorrespondente = row.tipo_correspondente;
            
            console.log(`Processing: Empresa=${empresa}, Loja=${codigoLoja}, Tipo=${tipoCorrespondente}`);
            
            // Apply the same logic as extractTXTFromXML
            if (['AV', 'PR', 'UN'].includes(tipoCorrespondente)) {
                let limits;
                if (tipoCorrespondente === 'AV' || tipoCorrespondente === 'UN') {
                    limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                } else if (tipoCorrespondente === 'PR') {
                    limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                }

                txtContent += this.formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, 2, 0) + '\r\n';
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, 2, 0) + '\r\n';
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, 2, 0) + '\r\n';
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 2, 0) + '\r\n';
                
            } else if (tipoCorrespondente === 'OP') {
                // For OP type, we'd need contract info - simplified for now
                const limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                txtContent += this.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
            }
        });
        
        if (txtContent) {
            const filename = `custom_txt_${getCurrentTimestamp()}.txt`;
            this.downloadTXTFile(txtContent, filename);
            
            // Close modal
            $(`#${this.modalId}`).modal('hide');
            
            // Show success message
            this.showSuccessMessage(`TXT gerado com sucesso!\n${selectedIndexes.length} registros processados.`);
        } else {
            alert('Erro ao gerar conteúdo TXT');
        }
    },
    
    formatToTXTLine: function(empresa, codigoLoja, codTransacao, meioPagamento, valorMinimo, valorMaximo, situacaoMeioPagamento, valorTotalMaxDiario, tipoManutencao, quantidadeTotalMaxDiaria) {
        const empresaTXT = this.padLeft(this.cleanNumeric(empresa), 10, '0'); 
        const codigoLojaTXT = this.padLeft(this.cleanNumeric(codigoLoja), 5, '0'); 
        const fixo = this.padRight("", 10, ' '); 
        const codTransacaoTXT = this.padLeft(this.cleanNumeric(codTransacao), 5, '0'); 
        const meioPagamTXT = this.padLeft(this.cleanNumeric(meioPagamento), 2, '0'); 
        const valorMinTXT = this.padLeft(this.cleanNumeric(valorMinimo), 17, '0'); 
        const valorMaxTXT = this.padLeft(this.cleanNumeric(valorMaximo), 17, '0'); 
        const sitMeioPTXT = this.padLeft(this.cleanNumeric(situacaoMeioPagamento), 1, '0'); 
        const valorTotalMaxTXT = this.padLeft(this.cleanNumeric(valorTotalMaxDiario), 18, '0'); 
        const tipoManutTXT = this.padLeft(this.cleanNumeric(tipoManutencao), 1, '0'); 
        const quantTotalMaxTXT = this.padLeft(this.cleanNumeric(quantidadeTotalMaxDiaria), 15, '0'); 

        const linha = empresaTXT + codigoLojaTXT + fixo + codTransacaoTXT + meioPagamTXT + 
            valorMinTXT + valorMaxTXT + sitMeioPTXT + valorTotalMaxTXT + 
            tipoManutTXT + quantTotalMaxTXT;

        if (linha.length > 101) {
            return linha.substring(0, 101);
        } else if (linha.length < 101) {
            return this.padRight(linha, 101, ' ');
        }

        return linha;
    },
    
    // Helper functions
    cleanNumeric: function(value) {
        return String(value).replace(/[^0-9]/g, '') || '0';
    },
    
    padLeft: function(str, length, char) {
        str = String(str);
        while (str.length < length) {
            str = char + str;
        }
        return str.length > length ? str.slice(-length) : str;
    },
    
    padRight: function(str, length, char) {
        str = String(str);
        while (str.length < length) {
            str = str + char;
        }
        return str.substring(0, length);
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    downloadTXTFile: function(txtContent, filename) {
        const txtWithBOM = '\uFEFF' + txtContent;
        const blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
        
        const link = document.createElement('a');
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 1000);
        } else {
            alert('Seu navegador não suporta download automático.');
        }
    },
    
    showError: function(message) {
        $('#txtModalErrorMessage').text(message);
        $('#txtModalError').show();
        $('#txtModalContent').hide();
    },
    
    showSuccessMessage: function(message) {
        const alertHtml = `
            <div class="alert alert-success success-alert" style="
                position: fixed; 
                top: 50%; 
                left: 50%; 
                transform: translate(-50%, -50%); 
                z-index: 10000; 
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
        
        setTimeout(() => {
            $('.success-alert').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    },
    
    cleanup: function() {
        this.currentData = [];
        $('#txtModalTableBody').empty();
        $('#txtModalError').hide();
    }
};

// Initialize the modal when document is ready
$(document).ready(function() {
    TxtGenerationModal.init();
});

// Example usage function - call this instead of the original exportSelectedTXT
function showTxtGenerationModal() {
    const currentFilter = getCurrentFilter();
    const selectedIds = (currentFilter === 'historico')
        ? HistoricoCheckboxModule.getSelectedIds()
        : CheckboxModule.getSelectedIds();

    if (selectedIds.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }

    TxtGenerationModal.show(selectedIds, currentFilter);
}