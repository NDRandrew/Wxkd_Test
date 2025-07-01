// COMPLETE FIXED SOLUTION FOR CHECKBOX MODULE

// Fixed CheckboxModule with better event handling
const CheckboxModule = {
    init: function() {
        console.log('CheckboxModule.init() called');
        
        // Checkbox "Selecionar Todos" - direct binding
        $('#selectAll').off('change').on('change', this.toggleSelectAll.bind(this));
        
        // Use body for event delegation to ensure it always works
        $('body').off('change', '.row-checkbox');
        $('body').off('click', '.row-checkbox');
        
        // Bind both click and change events for maximum compatibility
        $('body').on('click', '.row-checkbox', this.handleRowCheckboxClick.bind(this));
        $('body').on('change', '.row-checkbox', this.handleRowCheckboxChange.bind(this));
        
        // Initial state
        this.updateExportButton();
        
        console.log('CheckboxModule initialized');
    },
    
    // Handle click events (for older browsers/jQuery versions)
    handleRowCheckboxClick: function(e) {
        console.log('Checkbox clicked:', e.target);
        
        // Ensure the checkbox state is toggled
        var checkbox = $(e.target);
        
        // Small delay to ensure the checkbox state has changed
        setTimeout(function() {
            CheckboxModule.updateSelectAllState();
            CheckboxModule.updateExportButton();
        }, 10);
    },
    
    // Handle change events
    handleRowCheckboxChange: function(e) {
        console.log('Checkbox changed:', e.target, 'checked:', e.target.checked);
        this.updateSelectAllState();
        this.updateExportButton();
    },
    
    toggleSelectAll: function() {
        console.log('toggleSelectAll called');
        const isChecked = $('#selectAll').is(':checked');
        console.log('Select all checked:', isChecked);
        
        $('.row-checkbox:visible').each(function() {
            $(this).prop('checked', isChecked);
        });
        
        this.updateExportButton();
    },
    
    updateSelectAllState: function() {
        const totalCheckboxes = $('.row-checkbox:visible').length;
        const checkedCheckboxes = $('.row-checkbox:visible:checked').length;
        
        console.log('Updating select all state - Total:', totalCheckboxes, 'Checked:', checkedCheckboxes);
        
        if (checkedCheckboxes === 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAll').prop('indeterminate', true).prop('checked', false);
        }
    },
    
    updateExportButton: function() {
        const checkedCount = $('.row-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        console.log('Updating export button - Checked count:', checkedCount);
        
        const isDisabled = checkedCount === 0;
        $('#exportTxtBtn').prop('disabled', isDisabled);
        
        // Update button text based on filter
        let buttonText = 'Exportar TXT';
        if (FilterModule.currentFilter === 'cadastramento' || FilterModule.currentFilter === 'descadastramento') {
            buttonText = 'Converter para TXT';
        }
        
        // Update button text
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
            var id = $(this).val() || $(this).data('row-id');
            if (id) {
                selectedIds.push(id);
            }
        });
        console.log('Selected IDs:', selectedIds);
        return selectedIds;
    },
    
    // New method to reinitialize after table updates
    reinitialize: function() {
        console.log('Reinitializing checkbox module');
        this.updateSelectAllState();
        this.updateExportButton();
        
        // Ensure all checkboxes are properly initialized
        $('.row-checkbox').each(function() {
            var checkbox = $(this);
            console.log('Checkbox found:', checkbox.val() || checkbox.data('row-id'), 'checked:', checkbox.is(':checked'));
        });
    }
};

// Fixed PaginationModule.replaceTableData
PaginationModule.replaceTableData = function(newData) {
    console.log('replaceTableData called with', newData.length, 'rows');
    
    // Convert new data to table format
    this.allData = [];
    const self = this;
    
    newData.forEach(function(row, index) {
        console.log('Processing row', index, ':', row.CHAVE_LOJA);
        const rowData = [];
        
        // Create checkbox with both value and data-row-id for compatibility
        const checkboxHtml = '<input type="checkbox" class="form-check-input row-checkbox" ' +
                            'value="' + row.CHAVE_LOJA + '" ' +
                            'data-row-id="' + row.CHAVE_LOJA + '">' +
                            '<span class="text"></span>';
        
        const checkboxCell = $('<td class="checkbox-column">').html(checkboxHtml);
        rowData.push(checkboxCell);
        
        // Add other columns
        rowData.push($('<td>').text(row.CHAVE_LOJA));
        rowData.push($('<td>').text(row.NOME_LOJA));
        rowData.push($('<td>').text(row.COD_EMPRESA));
        rowData.push($('<td>').text(row.COD_LOJA));
        rowData.push($('<td>').text(row.DESC_ACAO));
        rowData.push($('<td>').text(row.TIPO));
        
        // Format date
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

            return day + '/' + month + '/' + year + ' ' + time;
        }

        var formattedDate = formatDateKeepTime(row.DATA_APROVACAO);
        rowData.push($('<td>').text(formattedDate));

        // Add calculated columns
        let depDinheiroValue = row.TIPO === 'Presenca' ? 'R$ 3.000,00' : 
                              row.TIPO === 'Avancado' ? 'R$ 10.000,00' : '0';
        rowData.push($('<td>').text(depDinheiroValue));

        let depChequeValue = row.TIPO === 'Presenca' ? 'R$ 5.000,00' : 
                            row.TIPO === 'Avancado' ? 'R$ 10.000,00' : '0';
        rowData.push($('<td>').text(depChequeValue));

        let recRetiradaValue = row.TIPO === 'Presenca' ? 'R$ 2.000,00' : 
                              row.TIPO === 'Avancado' ? 'R$ 3.500,00' : '0';
        rowData.push($('<td>').text(recRetiradaValue));

        let saqChequeValue = row.TIPO === 'Presenca' ? 'R$ 2.000,00' : 
                            row.TIPO === 'Avancado' ? 'R$ 3.500,00' : '0';
        rowData.push($('<td>').text(saqChequeValue));

        let saqSegundaViaValue = row.SEGUNDA_VIA_CARTAO_VALID === '1' ? 'Aprovado' : 'Não Aprovado';
        rowData.push($('<td>').text(saqSegundaViaValue));

        let saqHoleriteINSSValue = row.HOLERITE_INSS_VALID === '1' ? 'Aprovado' : 'Não Aprovado';
        rowData.push($('<td>').text(saqHoleriteINSSValue));

        let saqConsINSSValue = row.CONSULTA_INSS_VALID === '1' ? 'Aprovado' : 'Não Aprovado';
        rowData.push($('<td>').text(saqConsINSSValue));

        // Handle contract date
        if (!row.DATA_CONTRATO) {
            const warningCell = $('<td>')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign"></span> NULL</strong></i>')
                .css({
                    'background-color': '#fb6e52',
                    'text-align': 'center',
                    'vertical-align': 'middle'
                });
            rowData.push(warningCell);
        } else {
            const formattedContractDate = formatDateKeepTime(row.DATA_CONTRATO);
            rowData.push($('<td>').text(formattedContractDate));
        }

        // Handle contract type
        if (!row.TIPO_CONTRATO) {
            const warningCell = $('<td>')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign"></span> NULL</strong></i>')
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
    
    console.log('Data replacement complete. Total rows:', this.allData.length);
};

// Fixed PaginationModule.updateTable - add checkbox reinitialization
PaginationModule.updateTable = function() {
    // Filter data
    this.filterData();
    
    // Sort data if necessary
    this.sortData();
    
    // Calculate pagination
    const totalItems = this.filteredData.length;
    const totalPages = Math.ceil(totalItems / this.itemsPerPage);
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = Math.min(startIndex + this.itemsPerPage, totalItems);
    
    // Clear table
    $('#dataTable tbody').empty();
    
    // Add rows for current page
    for (let i = startIndex; i < endIndex; i++) {
        const row = $('<tr>');
        this.filteredData[i].forEach(function(cell) {
            row.append(cell.clone(true)); // Clone with events
        });
        $('#dataTable tbody').append(row);
    }
    
    // Update pagination info
    $('#showingStart').text(totalItems > 0 ? startIndex + 1 : 0);
    $('#showingEnd').text(endIndex);
    $('#totalItems').text(totalItems);
    
    // Update pagination controls
    this.updatePaginationControls(totalPages);
    
    // IMPORTANT: Reinitialize checkboxes after table update
    setTimeout(function() {
        console.log('Reinitializing checkboxes after table update');
        CheckboxModule.reinitialize();
    }, 200);
};

// Fixed FilterModule.loadTableData - better checkbox handling
FilterModule.loadTableData = function(filter) {
    console.log('loadTableData called with filter: ', filter);

    // Show loading
    $('#dataTable tbody').html('<tr><td colspan="16" class="text-center">Carregando...</td></tr>');
    
    $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
        .done(function(xmlData) {
            try {
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

                    // Update card counts
                    FilterModule.updateCardCounts(cardData);
                    
                    // Replace table data
                    PaginationModule.replaceTableData(tableData);
                    PaginationModule.currentPage = 1;
                    PaginationModule.updateTable();
                    
                    // Clear selections and reinitialize
                    CheckboxModule.clearSelections();
                    
                    // Extra delay for older browsers
                    setTimeout(function() {
                        console.log('Final checkbox reinitialization');
                        CheckboxModule.reinitialize();
                    }, 300);
                }
            } catch (e) {
                console.error('Error parsing XML: ', e);
                $('#dataTable tbody').html('<tr><td colspan="16" class="text-center text-danger">Erro ao processar dados</td></tr>');
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX failed:', textStatus, errorThrown);
            $('#dataTable tbody').html('<tr><td colspan="16" class="text-center text-danger">Erro ao carregar dados</td></tr>');
        });
};

// Make sure to initialize properly
$(document).ready(function() {
    console.log('Document ready - initializing modules');
    
    // Initialize modules in correct order
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); // This should be after PaginationModule
    FilterModule.init();
    ExportModule.init();
    
    console.log('All modules initialized');
});