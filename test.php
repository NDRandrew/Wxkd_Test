// COMPREHENSIVE FIX: PaginationModule.replaceTableData function
replaceTableData: function(newData) {
    // Converter novos dados para formato da tabela
    this.allData = [];
    const self = this;
    newData.forEach(function(row) {
        const rowData = [];
        
        // FIXED: Simplified checkbox creation without interfering elements
        const checkboxCell = $('<td class="checkbox-column">')
            .css({
                'padding': '8px',
                'text-align': 'center',
                'vertical-align': 'middle'
            });
        
        // Create checkbox element directly
        const checkbox = $('<input>')
            .attr({
                'type': 'checkbox',
                'class': 'row-checkbox', // Removed form-check-input class
                'data-row-id': row.CHAVE_LOJA,
                'value': row.CHAVE_LOJA,
                'id': 'checkbox_' + row.CHAVE_LOJA
            })
            .css({
                'cursor': 'pointer',
                'width': '16px',
                'height': '16px',
                'margin': '0'
            });
        
        checkboxCell.append(checkbox);
        rowData.push(checkboxCell);
        
        // Rest of your existing code for other columns...
        rowData.push($('<td>').text(row.CHAVE_LOJA));
        rowData.push($('<td>').text(row.NOME_LOJA));
        rowData.push($('<td>').text(row.COD_EMPRESA));
        rowData.push($('<td>').text(row.COD_LOJA));
        rowData.push($('<td>').text(row.DESC_ACAO));
        rowData.push($('<td>').text(row.TIPO));
        
        function formatDateKeepTime(dateStr) {
            var parts = dateStr.match(/^([A-Za-z]+) (\d{1,2}) (\d{4}) (.+)$/);
            if (!parts) return dateStr; 

            var months = {
                Jan: '01', Feb: '02', Mar: '03', Apr: '04',
                May: '05', Jun: '06', Jul: '07', Aug: '08',
                Sep: '09', Oct: '10', Nov: '11', Dec: '12'
            };

            var month = months[parts[1]];
            var day = ('0' + parts[2]).slice(-2);
            var year = parts[3];
            var time = parts[4];

            return `${day}/${month}/${year} ${time}`;
        }

        var formattedDate = formatDateKeepTime(row.DATA_APROVACAO);
        rowData.push($('<td>').text(formattedDate));

        let depDinheiroValue = '';
        if (row.TIPO === 'Presenca') {
            depDinheiroValue = 'R$ 3.000,00'; 
        } else if (row.TIPO === 'Avancado') {
            depDinheiroValue = 'R$ 10.000,000'; 
        } else {
            depDinheiroValue = '0'; 
        }
        rowData.push($('<td>').text(depDinheiroValue));
        
        let depChequeValue = '';
        if (row.TIPO === 'Presenca') {
            depChequeValue = 'R$ 5.000,00'; 
        } else if (row.TIPO === 'Avancado') {
            depChequeValue = 'R$ 10.000,000'; 
        } else {
            depChequeValue = '0'; 
        }
        rowData.push($('<td>').text(depChequeValue));

        let recRetiradaValue = '';
        if (row.TIPO === 'Presenca') {
            recRetiradaValue = 'R$ 2.000,00'; 
        } else if (row.TIPO === 'Avancado') {
            recRetiradaValue = 'R$ 3.500,000'; 
        } else {
            recRetiradaValue = '0'; 
        }
        rowData.push($('<td>').text(recRetiradaValue));

        let saqChequeValue = '';
        if (row.TIPO === 'Presenca') {
            saqChequeValue  = 'R$ 2.000,00'; 
        } else if (row.TIPO === 'Avancado') {
            saqChequeValue  = 'R$ 3.500,000'; 
        } else {
            saqChequeValue  = '0'; 
        }
        rowData.push($('<td>').text(saqChequeValue));

        let saqSegundaViaValue = '';
        if (row.SEGUNDA_VIA_CARTAO_VALID === '1') {
            saqSegundaViaValue = 'Aprovado';
        } else { 
            saqSegundaViaValue = 'Não Aprovado';
        }
        rowData.push($('<td>').text(saqSegundaViaValue));

        let saqHoleriteINSSValue = '';
        if (row.HOLERITE_INSS_VALID === '1') {
            saqHoleriteINSSValue = 'Aprovado';
        } else { 
            saqHoleriteINSSValue = 'Não Aprovado';
        }
        rowData.push($('<td>').text(saqHoleriteINSSValue));

        let saqConsINSSValue = '';
        if (row.CONSULTA_INSS_VALID === '1') {
            saqConsINSSValue = 'Aprovado';
        } else { 
            saqConsINSSValue = 'Não Aprovado';
        }
        rowData.push($('<td>').text(saqConsINSSValue));

        if (!row.DATA_CONTRATO) {
            const warningCell = $('<td style="text-align: center; vertical-align: middle;">')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
                .css({
                    'background-color': '#fb6e52',
                    'text-align': 'center',
                    'vertical-align': 'middle'
                });
            rowData.push(warningCell);
        } else {
            const formattedDate = formatDateKeepTime(row.DATA_CONTRATO);
            rowData.push($('<td>').text(formattedDate));
        }

        if (!row.TIPO_CONTRATO) {
            const warningCell = $('<td style="text-align: center; vertical-align: middle;">')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
                .css({
                    'background-color': '#fb6e52',
                    'text-align': 'center',
                    'vertical-align': 'middle'
                });
            rowData.push(warningCell);
        } else {
            rowData.push($('<td>').text(row.TIPO_CONTRATO));
        }
        
        self.allData.push(rowData);
    });
},

// COMPLETELY REWRITTEN CheckboxModule
const CheckboxModule = {
    init: function() {
        console.log('CheckboxModule.init() called');
        
        // Clear any existing event handlers
        $(document).off('change.checkboxModule');
        $('#selectAll').off('change.checkboxModule');
        
        // Checkbox "Selecionar Todos" with namespace
        $('#selectAll').on('change.checkboxModule', this.toggleSelectAll.bind(this));
        
        // Individual checkboxes with namespace and multiple event types
        $(document).on('change.checkboxModule click.checkboxModule', '.row-checkbox', this.handleRowCheckboxChange.bind(this));
        
        // Debug: Add click handler to detect if clicks are being registered
        $(document).on('click.checkboxModule', '.row-checkbox', function(e) {
            console.log('Checkbox clicked:', $(this).val(), 'Checked:', $(this).is(':checked'));
        });
        
        // Atualizar estado inicial
        this.updateExportButton();
    },
    
    handleRowCheckboxChange: function(e) {
        console.log('Row checkbox changed:', $(e.target).val(), 'Checked:', $(e.target).is(':checked'));
        
        // Force the checkbox state (in case CSS is interfering)
        var checkbox = $(e.target);
        var isChecked = checkbox.is(':checked');
        
        // Update other states
        this.updateSelectAllState();
        this.updateExportButton();
        
        // Prevent event bubbling
        e.stopPropagation();
    },
    
    toggleSelectAll: function(e) {
        console.log('Select all toggled');
        const isChecked = $('#selectAll').is(':checked');
        
        $('.row-checkbox:visible').each(function() {
            $(this).prop('checked', isChecked);
        });
        
        this.updateExportButton();
    },
    
    updateSelectAllState: function() {
        const totalCheckboxes = $('.row-checkbox:visible').length;
        const checkedCheckboxes = $('.row-checkbox:visible:checked').length;
        
        console.log('Update select all state - Total:', totalCheckboxes, 'Checked:', checkedCheckboxes);
        
        if (checkedCheckboxes === 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAll').prop('indeterminate', true).prop('checked', false);
        }
    },
    
    updateExportButton: function() {
        const checkedCount = $('.row-checkbox:checked').length;
        console.log('Update export button - Checked count:', checkedCount);
        
        $('#selectedCount').text(checkedCount);
        
        const isDisabled = checkedCount === 0;
        $('#exportTxtBtn').prop('disabled', isDisabled);
        
        // Atualizar texto do botão baseado no filtro
        let buttonText = 'Exportar TXT';
        if (FilterModule.currentFilter === 'cadastramento' || FilterModule.currentFilter === 'descadastramento') {
            buttonText = 'Converter para TXT';
        }
        
        // Atualizar o texto do botão
        const btnContent = $('#exportTxtBtn').html();
        if (btnContent) {
            const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
            $('#exportTxtBtn').html(newContent);
        }
    },
    
    clearSelections: function() {
        console.log('Clearing selections');
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        this.updateExportButton();
    },
    
    getSelectedIds: function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        console.log('Selected IDs:', selectedIds);
        return selectedIds;
    },
    
    // DEBUG: Add method to test checkbox functionality
    debugCheckboxes: function() {
        console.log('=== CHECKBOX DEBUG ===');
        console.log('Total checkboxes found:', $('.row-checkbox').length);
        console.log('Visible checkboxes:', $('.row-checkbox:visible').length);
        console.log('Checked checkboxes:', $('.row-checkbox:checked').length);
        
        $('.row-checkbox').each(function(index) {
            console.log(`Checkbox ${index}:`, {
                id: $(this).attr('id'),
                value: $(this).val(),
                checked: $(this).is(':checked'),
                visible: $(this).is(':visible'),
                disabled: $(this).is(':disabled')
            });
        });
        console.log('=== END DEBUG ===');
    }
};

// UPDATED: FilterModule.loadTableData with better debugging
loadTableData: function(filter) {
    console.log('loadTableData called with filter: ', filter);

    // Mostrar loading
    $('#dataTable tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
    
    var url = 'wxkd.php?action=ajaxGetTableData&filter=' + filter;
    console.log('AJAX URL:', url);
    
    // Fazer requisição AJAX para XML ao invés de JSON
    $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
        .done(function(xmlData) {
            try {
                // Parse XML response
                var $xml = $(xmlData);
                var success = $xml.find('success').text() === 'true';
                
                console.log('XML parsed successfully, success: ', success);
                
                if (success) {
                    var cardData = {
                        cadastramento: $xml.find('cardData cadastramento').text(),
                        descadastramento: $xml.find('cardData descadastramento').text(),
                        historico: $xml.find('cardData historico').text()
                    };
                    
                    var tableData = [];
                    $xml.find('tableData row').each(function() {
                        var row = {};
                        $(this).children().each(function() {
                            row[this.tagName] = $(this).text();
                        });
                        tableData.push(row);
                    });

                    console.log('Table data extracted: ', tableData.length, 'rows');

                    // Atualizar dados dos cards
                    FilterModule.updateCardCounts(cardData);
                    
                    // Recriar dados da tabela
                    PaginationModule.replaceTableData(tableData);
                    PaginationModule.currentPage = 1;
                    PaginationModule.updateTable();
                    
                    // Clear selections
                    CheckboxModule.clearSelections();
                    
                    // Reinitialize checkbox module after DOM update
                    setTimeout(function() {
                        console.log('Reinitializing checkbox events after filter');
                        CheckboxModule.init();
                        CheckboxModule.updateSelectAllState();
                        CheckboxModule.updateExportButton();
                        
                        // Debug checkboxes
                        CheckboxModule.debugCheckboxes();
                    }, 200);
                }
            } catch (e) {
                console.error('Error parsing XML: ', e);
                console.log('Failed XML content:', xmlData);
                $('#dataTable tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX failed:' , textStatus, errorThrown);
            console.log('Response: ', jqXHR.responseText);
            $('#dataTable tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
        });
},

// ADD THIS CSS TO FIX POTENTIAL STYLING ISSUES
// Add this to your CSS file or in a <style> tag:
/*
.checkbox-column {
    text-align: center !important;
    vertical-align: middle !important;
    padding: 8px !important;
}

.row-checkbox {
    cursor: pointer !important;
    width: 16px !important;
    height: 16px !important;
    margin: 0 !important;
    position: relative !important;
    z-index: 1 !important;
}

.row-checkbox:hover {
    transform: scale(1.1);
}
*/