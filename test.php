// CLEAN SearchModule - No highlighting, just show/hide functionality
const SearchModule = {
    init: function() {
        $('#searchInput').on('keyup', this.filterTable.bind(this));
    },
    
    filterTable: function() {
        const value = $('#searchInput').val().toLowerCase();
        const currentFilter = FilterModule.currentFilter;
        
        if (currentFilter === 'historico') {
            this.filterHistorico(value);
        } else {
            // Original table filtering logic
            PaginationModule.searchTerm = value;
            PaginationModule.currentPage = 1;
            PaginationModule.updateTable();
        }
    },
    
    filterHistorico: function(searchTerm) {
        if (!searchTerm) {
            // Show all accordions when search is empty
            $('.panel').show();
            this.hideNoResultsMessage();
            return;
        }
        
        let foundAny = false;
        
        // Search through each accordion panel
        $('.panel').each((index, panel) => {
            const $panel = $(panel);
            const $title = $panel.find('.panel-title a');
            const titleText = $title.text().toLowerCase();
            const chaveLote = $panel.find('.historico-lote-checkbox').data('chave-lote');
            
            // Check if title matches
            const titleMatches = titleText.includes(searchTerm);
            
            if (titleMatches) {
                $panel.show();
                foundAny = true;
            } else {
                // Check if data inside accordion matches
                this.searchInsideAccordion($panel, chaveLote, searchTerm).then((dataMatches) => {
                    if (dataMatches) {
                        $panel.show();
                        foundAny = true;
                        // Optionally expand the accordion to show the matching data
                        this.expandAccordionIfMatches($panel, chaveLote);
                    } else {
                        $panel.hide();
                    }
                });
            }
        });
        
        // Handle case when no results found
        setTimeout(() => {
            if (!foundAny && $('.panel:visible').length === 0) {
                this.showNoResultsMessage();
            } else {
                this.hideNoResultsMessage();
            }
        }, 500);
    },
    
    searchInsideAccordion: function($panel, chaveLote, searchTerm) {
        return new Promise((resolve) => {
            const $detailsBody = $panel.find(`.historico-details[data-chave-lote="${chaveLote}"]`);
            
            // Check if data is already loaded
            const hasLoadedData = $detailsBody.find('tr').length > 1 && 
                                 !$detailsBody.find('.load-details').length;
            
            if (hasLoadedData) {
                // Search in already loaded data (no highlighting)
                const dataMatches = this.searchInTableData($detailsBody, searchTerm);
                resolve(dataMatches);
            } else {
                // Load data and then search
                this.loadAndSearchAccordionData(chaveLote, searchTerm).then(resolve);
            }
        });
    },
    
    loadAndSearchAccordionData: function(chaveLote, searchTerm) {
        return new Promise((resolve) => {
            $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
                .done((xmlData) => {
                    try {
                        const $xml = $(xmlData);
                        const success = $xml.find('success').text() === 'true';
                        
                        if (success) {
                            let foundMatch = false;
                            
                            $xml.find('detailData row').each(function() {
                                const row = {};
                                $(this).children().each(function() {
                                    row[this.tagName] = $(this).text().toLowerCase();
                                });
                                
                                // Search in all row data
                                for (const key in row) {
                                    if (row[key].includes(searchTerm)) {
                                        foundMatch = true;
                                        return false;
                                    }
                                }
                                
                                if (foundMatch) return false;
                            });
                            
                            resolve(foundMatch);
                        } else {
                            resolve(false);
                        }
                    } catch (e) {
                        console.error('Error searching accordion data:', e);
                        resolve(false);
                    }
                })
                .fail(() => {
                    resolve(false);
                });
        });
    },
    
    // Simple search without highlighting
    searchInTableData: function($tableBody, searchTerm) {
        let found = false;
        
        $tableBody.find('tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.includes(searchTerm)) {
                found = true;
                return false; // Break loop
            }
        });
        
        return found;
    },
    
    expandAccordionIfMatches: function($panel, chaveLote) {
        const $collapse = $panel.find(`#collapse${chaveLote}`);
        const $detailsBody = $panel.find(`.historico-details[data-chave-lote="${chaveLote}"]`);
        
        // Expand the accordion
        $collapse.collapse('show');
        
        // Load details if not already loaded
        const $loadButton = $detailsBody.find('.load-details');
        if ($loadButton.length > 0) {
            $loadButton.click();
        }
    },
    
    showNoResultsMessage: function() {
        if ($('.search-no-results').length === 0) {
            const noResultsHtml = `
                <div class="alert alert-info search-no-results">
                    <i class="fa fa-info-circle"></i>
                    Nenhum resultado encontrado para a pesquisa. Tente termos diferentes.
                </div>
            `;
            $('#historicoAccordion').prepend(noResultsHtml);
        }
    },
    
    hideNoResultsMessage: function() {
        $('.search-no-results').remove();
    }
};

// Standard table styling to match other tables in the system
const standardHistoricoTableCSS = `
<style id="standard-historico-table-styles">
/* Make historico tables look like standard system tables */
.historico-details table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
    margin-bottom: 20px !important;
    background-color: #fff !important;
}

.historico-details table thead th {
    background-color: #f5f5f5 !important;
    border: 1px solid #ddd !important;
    padding: 8px !important;
    font-weight: bold !important;
    text-align: center !important;
    vertical-align: middle !important;
    font-size: 12px !important;
}

.historico-details table tbody td {
    border: 1px solid #ddd !important;
    padding: 8px !important;
    vertical-align: top !important;
    font-size: 11px !important;
    line-height: 1.4 !important;
    background-color: #fff !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.historico-details table tbody tr:nth-child(even) {
    background-color: #f9f9f9 !important;
}

.historico-details table tbody tr:hover {
    background-color: #f0f8ff !important;
}

/* Column widths for readability */
.historico-details table td:nth-child(1), 
.historico-details table th:nth-child(1) { 
    width: 50px !important; 
    max-width: 50px !important; 
    text-align: center !important; 
}

.historico-details table td:nth-child(2), 
.historico-details table th:nth-child(2) { 
    width: 80px !important; 
    max-width: 80px !important; 
}

.historico-details table td:nth-child(3), 
.historico-details table th:nth-child(3) { 
    width: 180px !important; 
    max-width: 180px !important; 
    white-space: normal !important;
    word-wrap: break-word !important;
}

.historico-details table td:nth-child(4), 
.historico-details table th:nth-child(4),
.historico-details table td:nth-child(5), 
.historico-details table th:nth-child(5) { 
    width: 70px !important; 
    max-width: 70px !important; 
    text-align: center !important; 
}

.historico-details table td:nth-child(6), 
.historico-details table th:nth-child(6) { 
    width: 140px !important; 
    max-width: 140px !important; 
}

.historico-details table td:nth-child(7), 
.historico-details table th:nth-child(7),
.historico-details table td:nth-child(8), 
.historico-details table th:nth-child(8) { 
    width: 85px !important; 
    max-width: 85px !important; 
    text-align: center !important; 
}

/* Currency columns */
.historico-details table td:nth-child(9), 
.historico-details table th:nth-child(9),
.historico-details table td:nth-child(10), 
.historico-details table th:nth-child(10),
.historico-details table td:nth-child(11), 
.historico-details table th:nth-child(11),
.historico-details table td:nth-child(12), 
.historico-details table th:nth-child(12) { 
    width: 90px !important; 
    max-width: 90px !important; 
    text-align: right !important; 
    font-family: monospace !important;
}

/* Status columns */
.historico-details table td:nth-child(13), 
.historico-details table th:nth-child(13),
.historico-details table td:nth-child(14), 
.historico-details table th:nth-child(14),
.historico-details table td:nth-child(15), 
.historico-details table th:nth-child(15),
.historico-details table td:nth-child(16), 
.historico-details table th:nth-child(16) { 
    width: 70px !important; 
    max-width: 70px !important; 
    text-align: center !important; 
}

.historico-details table td:nth-child(17), 
.historico-details table th:nth-child(17) { 
    width: 90px !important; 
    max-width: 90px !important; 
    text-align: center !important; 
}

.historico-details table td:nth-child(18), 
.historico-details table th:nth-child(18) { 
    width: 120px !important; 
    max-width: 120px !important; 
}

.historico-details table td:nth-child(19), 
.historico-details table th:nth-child(19),
.historico-details table td:nth-child(20), 
.historico-details table th:nth-child(20) { 
    width: 85px !important; 
    max-width: 85px !important; 
    text-align: center !important; 
}

/* Container styling */
.historico-details .table-scrollable {
    overflow-x: auto !important;
    overflow-y: visible !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    background-color: #fff !important;
}

/* Remove any existing search highlights */
.search-highlight {
    background-color: transparent !important;
    color: inherit !important;
    font-weight: inherit !important;
    padding: 0 !important;
}

.search-highlight-row {
    background-color: inherit !important;
    border-left: none !important;
}

.search-no-results {
    margin: 15px 0 !important;
    border: 1px solid #bee5eb !important;
    border-radius: 4px !important;
}
</style>
`;

// Remove old search highlight styles and inject clean styles
$('#search-highlight-styles').remove();
$('#standard-historico-table-styles').remove();
$('head').append(standardHistoricoTableCSS);

// Clean up any existing highlights
$('.search-highlight').each(function() {
    const $this = $(this);
    $this.replaceWith($this.text());
});
$('.search-highlight-row').removeClass('search-highlight-row');
$('.search-highlight-cell').removeClass('search-highlight-cell');

// Enhanced FilterModule integration - clean version
const originalClearFilter = FilterModule.clearFilter;
FilterModule.clearFilter = function() {
    SearchModule.hideNoResultsMessage();
    $('#searchInput').val('');
    originalClearFilter.call(this);
};

const originalApplyFilter = FilterModule.applyFilter;
FilterModule.applyFilter = function(filter) {
    if (filter !== 'historico') {
        SearchModule.hideNoResultsMessage();
    }
    originalApplyFilter.call(this, filter);
};