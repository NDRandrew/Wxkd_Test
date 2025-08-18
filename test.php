// ADD this new module for handling confirmed records
// Button appears in all filters for easy access to restore functionality
const ConfirmedRecordsModule = {
    init: function() {
        this.updateConfirmedCount();
        this.bindEvents();
    },
    
    bindEvents: function() {
        // Update count when filter changes
        const originalApplyFilter = FilterModule.applyFilter;
        FilterModule.applyFilter = function(filter) {
            originalApplyFilter.call(this, filter);
            setTimeout(() => {
                ConfirmedRecordsModule.updateConfirmedCount();
            }, 1500);
        };
        
        const originalLoadTableData = FilterModule.loadTableData;
        FilterModule.loadTableData = function(filter) {
            originalLoadTableData.call(this, filter);
            // Update confirmed count after loading any filter data
            setTimeout(() => {
                ConfirmedRecordsModule.updateConfirmedCount();
            }, 1500);
        };
    },
    
    updateConfirmedCount: function() {
        // MODIFIED: Show button in any filter, not just historico
        $.get('wxkd.php?action=ajaxGetConfirmedCount')
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        const count = parseInt($xml.find('count').text()) || 0;
                        $('#confirmedCount').text(count);
                        
                        if (count > 0) {
                            $('#restoreConfirmedBtn').show();
                        } else {
                            $('#restoreConfirmedBtn').hide();
                        }
                    }
                } catch (e) {
                    console.error('Error updating confirmed count:', e);
                }
            })
            .fail(() => {
                console.error('Failed to get confirmed count');
            });
    },
    
    restoreConfirmedRecords: function() {
        // Works from any filter but affects historico data
        if (!confirm('Tem certeza que deseja restaurar todos os registros confirmados? Esta acao nao pode ser desfeita.')) {
            return;
        }
        
        showLoading();
        $('#restoreConfirmedBtn').prop('disabled', true);
        
        $.get('wxkd.php?action=ajaxRestoreConfirmedRecords')
            .done((xmlData) => {
                hideLoading();
                $('#restoreConfirmedBtn').prop('disabled', false);
                
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    const message = $xml.find('message').text();
                    
                    if (success) {
                        const restoredCount = $xml.find('restoredCount').text();
                        const restoredLote = $xml.find('restoredLote').text();
                        
                        alert(`Sucesso! ${restoredCount} registro(s) restaurado(s) para o Lote ${restoredLote}`);
                        
                        // Reload current filter data to reflect changes
                        const currentFilter = FilterModule.currentFilter || 'all';
                        FilterModule.loadTableData(currentFilter);
                        
                        // Update confirmed count
                        setTimeout(() => {
                            this.updateConfirmedCount();
                        }, 1000);
                        
                    } else {
                        alert('Erro: ' + message);
                    }
                    
                } catch (e) {
                    console.error('Error processing restore response:', e);
                    alert('Erro ao processar resposta do servidor');
                }
            })
            .fail(() => {
                hideLoading();
                $('#restoreConfirmedBtn').prop('disabled', false);
                alert('Erro na comunicacao com o servidor');
            });
    }
};

// MODIFIED: Update the FilterModule to handle confirmed count updates
const FilterModule = {
    currentFilter: 'all',
    
    init: function() {
        this.currentFilter = window.currentFilter || 'all';
        this.updateFilterUI();
        
        $('.card-filter').on('click', this.handleCardClick.bind(this));
        
        if (this.currentFilter !== 'all') {
            this.setActiveCard(this.currentFilter);
            console.log('FilterModule.init - Loading data for initial filter:', this.currentFilter);
            this.loadTableData(this.currentFilter);
        }
    },
    
    handleCardClick: function(e) {
        const filter = $(e.currentTarget).data('filter');
        this.applyFilter(filter);
    },
    
    applyFilter: function(filter) {
        if (this.currentFilter === filter && filter !== 'all') {
            this.clearFilter();
            return;
        }
        
        this.currentFilter = filter;
        this.setActiveCard(filter);
        this.updateFilterUI();
        this.loadTableData(filter);
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('filter', filter);
        window.history.pushState({filter: filter}, '', newUrl);
    },

    clearFilter: function() {
        this.currentFilter = 'all';
        $('.card-filter').removeClass('active');
        $('#filterIndicator').fadeOut();
        
        $('.table-scrollable').show();
        $('#historicoAccordion').hide();
        $('.row.mt-3').show(); 
        
        this.loadTableData('all');
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('filter');
        window.history.pushState({filter: 'all'}, '', newUrl);
    },
    
    setActiveCard: function(filter) {
        $('.card-filter').removeClass('active');
        $(`#card-${filter}`).addClass('active');
    },
    
    updateFilterUI: function() {
        if (this.currentFilter === 'all') {
            $('#filterIndicator').hide();
        } else {
            const filterNames = {
                'cadastramento': 'Cadastramento',
                'descadastramento': 'Descadastramento',
                'historico': 'Histórico'
            };
            
            const filterDescriptions = {
                'cadastramento': 'Mostrando apenas lojas para cadastramento',
                'descadastramento': 'Mostrando apenas lojas para descadastramento',
                'historico': 'Mostrando histórico de processos realizados (apenas não confirmados)'
            };
            
            $('#activeFilterName').text(filterNames[this.currentFilter]);
            $('#filterDescription').text(filterDescriptions[this.currentFilter]);
            $('#filterIndicator').fadeIn();
        }
    },
    
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
                        
                        const newContractChaves = [];
                        $xml.find('contractChaves chave').each(function() {
                            const chave = $(this).text();
                            if (chave) {
                                newContractChaves.push(chave);
                            }
                        });
                        
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
                                // Update confirmed count after loading any data
                                ConfirmedRecordsModule.updateConfirmedCount();
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
                                // Update confirmed count after loading any data
                                ConfirmedRecordsModule.updateConfirmedCount();
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
    
    // ... rest of the existing FilterModule methods remain the same ...
    
    updateCardCounts: function(cardData) {
        $('#card-cadastramento .databox-number').text(cardData.cadastramento);
        $('#card-descadastramento .databox-number').text(cardData.descadastramento);
        $('#card-historico .databox-number').text(cardData.historico);
    },
    
    buildHistoricoAccordion: function($xml) {
        // ... existing buildHistoricoAccordion implementation ...
        // No changes needed here as the PHP query already filters by CONFIRMADO_FLAG = 0
    }
};

// Add the global function for the button onclick
function restoreConfirmedRecords() {
    ConfirmedRecordsModule.restoreConfirmedRecords();
}

// MODIFIED: Update the document ready function
$(document).ready(() => {
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
    ConfirmedRecordsModule.init(); // ADDED: Initialize confirmed records module
    
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


-----------------


<?php
// MODIFIED: Update the switch case to handle new actions
switch($action) {
    case 'exportCSV':
        $controller->exportCSV();
        break;
    case 'exportTXT':
        $controller->exportTXT();
        break;
    case 'exportAccess':  
        $controller->exportAccess();
        break;
    case 'ajaxGetTableData':
        $controller->ajaxGetTableData();
        break;
    case 'ajaxGetHistoricoDetails':  
        $controller->ajaxGetHistoricoDetails();
        break;
    case 'ajaxRestoreConfirmedRecords':  // NEW ACTION
        $controller->ajaxRestoreConfirmedRecords();
        break;
    case 'ajaxGetConfirmedCount':  // NEW ACTION
        $controller->ajaxGetConfirmedCount();
        break;
    default:
        $data = $controller->index();
        $cardData = $data['cardData'];
        $tableData = $data['tableData'];
        $activeFilter = $data['activeFilter'];
        $contractChavesLookup = $data['contractChavesLookup'];
        break;
}

// ... existing HTML structure until the dropdown section ...

?>

<!-- MODIFIED: Add the restore button below the dropdown -->
<div class="col-md-4" style="position:relative; left:25%;" >
    <div class="d-flex justify-content-end align-items-center"  >
        <label for="itemsPerPage" class="me-2 text-sm" ></label>
        <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto; cursor:pointer;">
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="50">50</option>
        </select>
        <span class="ms-2 text-sm"></span>
        
        <!-- NEW: Restore Confirmed Records Button - Available in all filters -->
        <div style="margin-left: 15px;" id="restoreButtonContainer">
            <button type="button" class="btn btn-warning btn-sm" id="restoreConfirmedBtn" 
                    onclick="restoreConfirmedRecords()" 
                    style="display: none; white-space: nowrap;"
                    title="Restaurar registros confirmados do histórico">
                <i class="fa fa-undo"></i> Restaurar (<span id="confirmedCount">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Add this JavaScript section before closing body tag -->
<script type="text/javascript">
// Check and update confirmed count on page load and filter changes
// This button works in all filters for easy access to restore functionality
function updateConfirmedCount() {
    // MODIFIED: Check confirmed count regardless of current filter
    $.get('wxkd.php?action=ajaxGetConfirmedCount')
        .done(function(xmlData) {
            try {
                var $xml = $(xmlData);
                var success = $xml.find('success').text() === 'true';
                
                if (success) {
                    var count = parseInt($xml.find('count').text()) || 0;
                    $('#confirmedCount').text(count);
                    
                    if (count > 0) {
                        $('#restoreConfirmedBtn').show();
                    } else {
                        $('#restoreConfirmedBtn').hide();
                    }
                }
            } catch (e) {
                console.error('Error updating confirmed count:', e);
            }
        })
        .fail(function() {
            console.error('Failed to get confirmed count');
        });
}

// Restore confirmed records function
// Works from any filter but affects historico data
function restoreConfirmedRecords() {
    if (!confirm('Tem certeza que deseja restaurar todos os registros confirmados? Esta acao nao pode ser desfeita.')) {
        return;
    }
    
    showLoading();
    $('#restoreConfirmedBtn').prop('disabled', true);
    
    $.get('wxkd.php?action=ajaxRestoreConfirmedRecords')
        .done(function(xmlData) {
            hideLoading();
            $('#restoreConfirmedBtn').prop('disabled', false);
            
            try {
                var $xml = $(xmlData);
                var success = $xml.find('success').text() === 'true';
                var message = $xml.find('message').text();
                
                if (success) {
                    var restoredCount = $xml.find('restoredCount').text();
                    var restoredLote = $xml.find('restoredLote').text();
                    
                    alert('Sucesso! ' + restoredCount + ' registro(s) restaurado(s) para o Lote ' + restoredLote);
                    
                    // Reload the historico data
                    FilterModule.loadTableData('historico');
                    
                    // Update confirmed count
                    setTimeout(function() {
                        updateConfirmedCount();
                    }, 1000);
                    
                } else {
                    alert('Erro: ' + message);
                }
                
            } catch (e) {
                console.error('Error processing restore response:', e);
                alert('Erro ao processar resposta do servidor');
            }
        })
        .fail(function() {
            hideLoading();
            $('#restoreConfirmedBtn').prop('disabled', false);
            alert('Erro na comunicacao com o servidor');
        });
}

// Update confirmed count when filter changes
$(document).ready(function() {
    // Initial check
    setTimeout(function() {
        updateConfirmedCount();
    }, 1000);
    
    // Monitor filter changes
    var originalApplyFilter = FilterModule.applyFilter;
    FilterModule.applyFilter = function(filter) {
        originalApplyFilter.call(this, filter);
        setTimeout(function() {
            updateConfirmedCount();
        }, 1500);
    };
    
    var originalLoadTableData = FilterModule.loadTableData;
    FilterModule.loadTableData = function(filter) {
        originalLoadTableData.call(this, filter);
        // Update confirmed count after loading any filter data
        setTimeout(function() {
            updateConfirmedCount();
        }, 1500);
    };
});
</script>

<?php
// ... rest of existing HTML structure ...
?>
