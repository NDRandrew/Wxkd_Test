public function ajaxGetTableData() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    try {
        $tableData = $this->model->getTableDataByFilter($filter);
        $cardData = $this->model->getCardData();
        
        // Get contract data for the current filter
        $contractData = $this->model->contractDateCheck($filter);
        $contractChaves = array();
        foreach ($contractData as $item) {
            $contractChaves[] = $item['Chave_Loja'];
        }
        
        $xml = '<response>';
        $xml .= '<success>true</success>';
        
        if (is_array($cardData)) {
            $xml .= '<cardData>';
            foreach ($cardData as $key => $value) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $xml .= '</cardData>';
        }
        
        // Add contract chaves to XML response
        $xml .= '<contractChaves>';
        foreach ($contractChaves as $chave) {
            $xml .= '<chave>' . addcslashes($chave, '"<>&') . '</chave>';
        }
        $xml .= '</contractChaves>';
        
        $xml .= '<tableData>';
        if (is_array($tableData) && count($tableData) > 0) {
            foreach ($tableData as $row) {
                $xml .= '<row>';
                foreach ($row as $key => $value) {
                    $xml .= '<' . $key . '>' . addcslashes($value, '"<>&') . '</' . $key . '>';
                }
                $xml .= '</row>';
            }
        }
        $xml .= '</tableData>';
        $xml .= '</response>';
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<error>' . addcslashes($e->getMessage(), '"<>&') . '</error>';
        $xml .= '</response>';
    }

    echo $xml;
    exit;
}


---------------------


loadTableData: function(filter) {
    showLoading();
    
    if (filter === 'historico') {
        $('.table-scrollable').hide();
        $('#historicoAccordion').show();
        $('.row.mt-3').hide(); 
        $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
    } else {
        $('#historicoAccordion').hide();
        $('.table-scrollable').show();
        $('.row.mt-3').show(); 
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
                    
                    // Update global contractChaves array from XML response
                    const newContractChaves = [];
                    $xml.find('contractChaves chave').each(function() {
                        const chave = $(this).text();
                        if (chave) {
                            newContractChaves.push(chave);
                        }
                    });
                    
                    // Update the global contractChaves variable
                    if (typeof window.contractChaves !== 'undefined') {
                        window.contractChaves = newContractChaves;
                    } else {
                        window.contractChaves = newContractChaves;
                    }
                    
                    console.log('Updated contractChaves for filter ' + filter + ':', window.contractChaves);
                    
                    if (filter === 'historico') {
                        this.buildHistoricoAccordion($xml);
                        setTimeout(() => {
                            HistoricoCheckboxModule.init();
                            hideLoading();
                        }, 100);
                    } else {
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
},


-------------------


createRowData: function(row, index) {
    const rowData = [];
    
    // Use the updated global contractChaves array
    const currentContractChaves = window.contractChaves || [];
    const chaveLoja = String(row.CHAVE_LOJA || row.Chave_Loja || '');
    const highlightMissing = currentContractChaves.indexOf(chaveLoja) === -1;
    const highlightStyle = highlightMissing ? '#f4b400' : 'transparent';
    
    console.log('createRowData - ChaveLoja:', chaveLoja, 'HighlightMissing:', highlightMissing, 'ContractChaves:', currentContractChaves);
    
    const checkboxCell = this.createCheckboxCell(index);
    if (this.hasValidationError(row)) {
        const lockIcon = $('<i class="fa fa-lock">')
            .css({
                'color': '#d9534f',
                'margin-left': '5px',
                'cursor': 'help'    
            })
            .attr('title', 'Essa Loja não pode ser exportada como txt por: ' + this.getValidationErrorMessage(row));
        checkboxCell.append(lockIcon);
    }
    rowData.push(checkboxCell);
    
    rowData.push($('<td>').text(row.CHAVE_LOJA || row.Chave_Loja || ''));
    rowData.push($('<td>').text(row.NOME_LOJA || row.Nome_Loja || ''));
    rowData.push($('<td>').text(row.COD_EMPRESA || row.Cod_Empresa || ''));
    rowData.push($('<td>').text(row.COD_LOJA || row.Cod_Loja || ''));
    rowData.push($('<td>').text(row.QUANT_LOJAS || ''));
    
    rowData.push($('<td>').html(this.generateStatusHTML(row, FilterModule.currentFilter)));
    
    rowData.push($('<td>').html(this.generateDateFieldsHTML(row)));
    rowData.push($('<td>').text(this.formatDataSolicitacao(row)));
    
    this.addValidationFields(rowData, row);
    
    this.addContractFields(rowData, row, highlightStyle);
    
    return rowData;
},


-------------------

addContractFields: function(rowData, row, highlightStyle) {
    // DATA_CONTRATO field
    if (!row.DATA_CONTRATO || row.DATA_CONTRATO === '' || row.DATA_CONTRATO === 'NULL') {
        const warningCell = $('<td>')
            .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
            .css({
                'background-color': '#fb6e52',  // Red warning background for NULL values
                'text-align': 'center',
                'vertical-align': 'middle'
            });
        rowData.push(warningCell);
    } else {
        const formattedDate = this.formatDateKeepTime(row.DATA_CONTRATO);
        const contractDateCell = $('<td>')
            .text(formattedDate)
            .css({
                'background-color': highlightStyle,  // Yellow highlight for missing contract validation
                'text-align': 'center',
                'vertical-align': 'middle'
            });
        rowData.push(contractDateCell);
    }
    
    // TIPO_CONTRATO field
    if (!row.TIPO_CONTRATO || row.TIPO_CONTRATO === '' || row.TIPO_CONTRATO === 'NULL') {
        const warningCell = $('<td>')
            .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
            .css({
                'background-color': '#fb6e52',  // Red warning background for NULL values
                'text-align': 'center',
                'vertical-align': 'middle'
            });
        rowData.push(warningCell);
    } else {
        const contractTypeCell = $('<td>')
            .text(row.TIPO_CONTRATO)
            .css({
                'background-color': highlightStyle,  // Yellow highlight for missing contract validation
                'text-align': 'center',
                'vertical-align': 'middle'
            });
        rowData.push(contractTypeCell);
    }
},


-----------------


$(document).ready(() => {
    // Ensure contractChaves is available globally
    if (typeof contractChaves !== 'undefined') {
        window.contractChaves = contractChaves;
    } else {
        window.contractChaves = [];
    }
    
    console.log('Initial contractChaves:', window.contractChaves);
    
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init();
    HistoricoModule.init();
    HistoricoCheckboxModule.init();
    
    $('.card-filter').on('click', function() {
        const newFilter = $(this).data('filter');
        if (newFilter !== FilterModule.currentFilter) {
            $('#searchInput').val('');
            SearchModule.hideNoResultsMessage();
        }
    });
    
    $(document).on('hidden.bs.collapse', '.panel-collapse', function() {
        const $panel = $(this).closest('.panel');
        $panel.find('.search-highlight').each(function() {
            const $this = $(this);
            $this.replaceWith($this.text());
        });
        $panel.find('.search-highlight-row').removeClass('search-highlight-row');
    });
});


---------------------

init: function() {
    this.currentFilter = window.currentFilter || 'all';
    this.updateFilterUI();
    
    $('.card-filter').on('click', this.handleCardClick.bind(this));
    
    if (this.currentFilter !== 'all') {
        this.setActiveCard(this.currentFilter);
        // Load contract data for the current filter if it's not 'all'
        // This ensures highlighting works correctly when page loads with a specific filter
        console.log('FilterModule.init - Loading data for initial filter:', this.currentFilter);
        this.loadTableData(this.currentFilter);
    }
},


---------------



// Add this debug function to TestJ - useful for troubleshooting
window.debugContractChaves = function() {
    console.log('=== CONTRACT CHAVES DEBUG ===');
    console.log('Current Filter:', FilterModule.currentFilter);
    console.log('Contract Chaves Array:', window.contractChaves);
    console.log('Contract Chaves Length:', window.contractChaves ? window.contractChaves.length : 'undefined');
    
    // Check first few visible rows
    $('#dataTableAndre tbody tr:visible').slice(0, 3).each(function(index) {
        const $row = $(this);
        const chaveLoja = $row.find('td:eq(1)').text(); // Second column is Chave_Loja
        const isHighlighted = window.contractChaves && window.contractChaves.indexOf(chaveLoja) === -1;
        const bgColor = $row.find('td:eq(17)').css('background-color'); // DATA_CONTRATO column
        
        console.log(`Row ${index}: ChaveLoja=${chaveLoja}, ShouldHighlight=${isHighlighted}, BgColor=${bgColor}`);
    });
    console.log('=== END DEBUG ===');
};

// You can call window.debugContractChaves() in browser console to debug
