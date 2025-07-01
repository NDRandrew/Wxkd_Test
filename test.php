// FIXED: PaginationModule.replaceTableData function
replaceTableData: function(newData) {
    // Converter novos dados para formato da tabela
    this.allData = [];
    const self = this;
    newData.forEach(function(row) {
        const rowData = [];
        
        // FIXED: Checkbox with proper value attribute
        const checkboxCell = $('<td class="checkbox-column">').html(
            `<input type="checkbox" class="form-check-input row-checkbox" 
                    data-row-id="${row.CHAVE_LOJA}" 
                    value="${row.CHAVE_LOJA}">
            <span class="text"></span>`
        );
        rowData.push(checkboxCell);
        
        // Rest of the code remains the same...
        rowData.push($('<td>').text(row.CHAVE_LOJA));
        rowData.push($('<td>').text(row.NOME_LOJA));
        // ... other columns
        
        self.allData.push(rowData);
    });
},

// FIXED: CheckboxModule with consistent event handling
const CheckboxModule = {
    init: function() {
        // Checkbox "Selecionar Todos"
        $('#selectAll').on('change', this.toggleSelectAll.bind(this));
        
        // FIXED: Use event delegation properly and ensure events are bound correctly
        $(document).off('change', '.row-checkbox'); // Remove any existing handlers
        $(document).on('change', '.row-checkbox', this.handleRowCheckboxChange.bind(this));
        
        // Atualizar estado inicial
        this.updateExportButton();
    },
    
    // NEW: Single handler for row checkbox changes
    handleRowCheckboxChange: function(e) {
        this.updateSelectAllState();
        this.updateExportButton();
    },
    
    toggleSelectAll: function() {
        const isChecked = $('#selectAll').is(':checked');
        $('.row-checkbox:visible').prop('checked', isChecked);
        this.updateExportButton();
    },
    
    updateSelectAllState: function() {
        const totalCheckboxes = $('.row-checkbox:visible').length;
        const checkedCheckboxes = $('.row-checkbox:visible:checked').length;
        
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
        const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
        $('#exportTxtBtn').html(newContent);
    },
    
    clearSelections: function() {
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        this.updateExportButton();
    },
    
    getSelectedIds: function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            // FIXED: Use consistent method to get ID
            selectedIds.push($(this).val()); // Use .val() instead of .data('row-id')
        });
        return selectedIds;
    }
};

// FIXED: Update the FilterModule.loadTableData function
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

                    // Atualizar dados dos cards
                    FilterModule.updateCardCounts(cardData);
                    
                    // Recriar dados da tabela
                    PaginationModule.replaceTableData(tableData);
                    PaginationModule.currentPage = 1;
                    PaginationModule.updateTable();
                    
                    // FIXED: Clear selections and reinitialize checkbox events
                    CheckboxModule.clearSelections();
                    
                    // FIXED: Ensure checkbox events are working after table update
                    setTimeout(function() {
                        CheckboxModule.updateSelectAllState();
                        CheckboxModule.updateExportButton();
                    }, 150); // Slightly longer timeout to ensure DOM is ready
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