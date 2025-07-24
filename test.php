// FIXED SearchModule - Replace your existing SearchModule with this version

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
            // Show all accordions and remove highlights when search is empty
            $('.panel').show();
            this.removeSearchHighlights();
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
                this.highlightTextSafely($title, searchTerm);
                foundAny = true;
            } else {
                // Check if data inside accordion matches
                this.searchInsideAccordion($panel, chaveLote, searchTerm).then((dataMatches) => {
                    if (dataMatches) {
                        $panel.show();
                        foundAny = true;
                        // Expand accordion to show matching data
                        this.expandAccordionIfMatches($panel, chaveLote, searchTerm);
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
                // Search in already loaded data
                const dataMatches = this.searchInTableDataSafely($detailsBody, searchTerm);
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
    
    // FIXED: Search in table data while preserving HTML structure
    searchInTableDataSafely: function($tableBody, searchTerm) {
        let found = false;
        
        $tableBody.find('tr').each(function() {
            const $row = $(this);
            const rowText = $row.text().toLowerCase();
            
            if (rowText.includes(searchTerm)) {
                found = true;
                
                // Add highlight class to the row
                $row.addClass('search-highlight-row');
                
                // Highlight text in individual cells WITHOUT breaking table structure
                $row.find('td').each(function() {
                    const $cell = $(this);
                    SearchModule.highlightTextInCellSafely($cell, searchTerm);
                });
                
                return false; // Break loop after first match
            }
        });
        
        return found;
    },
    
    expandAccordionIfMatches: function($panel, chaveLote, searchTerm) {
        const $collapse = $panel.find(`#collapse${chaveLote}`);
        const $detailsBody = $panel.find(`.historico-details[data-chave-lote="${chaveLote}"]`);
        
        // Expand the accordion
        $collapse.collapse('show');
        
        // Load details if not already loaded
        const $loadButton = $detailsBody.find('.load-details');
        if ($loadButton.length > 0) {
            $loadButton.click();
            
            // Wait for data to load and then highlight
            setTimeout(() => {
                this.highlightMatchingDataRowsSafely($detailsBody, searchTerm);
            }, 1000);
        } else {
            // Data already loaded, highlight immediately
            this.highlightMatchingDataRowsSafely($detailsBody, searchTerm);
        }
    },
    
    // FIXED: Highlight matching rows while preserving table structure
    highlightMatchingDataRowsSafely: function($tableBody, searchTerm) {
        $tableBody.find('tr').each(function() {
            const $row = $(this);
            const rowText = $row.text().toLowerCase();
            
            if (rowText.includes(searchTerm)) {
                // Add highlight class to the entire row
                $row.addClass('search-highlight-row');
                
                // Highlight specific text in cells while preserving <td> structure
                $row.find('td').each(function() {
                    const $cell = $(this);
                    SearchModule.highlightTextInCellSafely($cell, searchTerm);
                });
            }
        });
    },
    
    // SAFE text highlighting that preserves HTML structure
    highlightTextSafely: function($element, searchTerm) {
        const originalHtml = $element.html();
        const originalText = $element.text();
        const lowerText = originalText.toLowerCase();
        const lowerSearchTerm = searchTerm.toLowerCase();
        
        if (lowerText.includes(lowerSearchTerm)) {
            // Only modify text content, preserve HTML structure
            const regex = new RegExp(`(${this.escapeRegExp(searchTerm)})`, 'gi');
            const newHtml = originalHtml.replace(regex, '<span class="search-highlight">$1</span>');
            $element.html(newHtml);
        }
    },
    
    // SAFE cell highlighting that preserves table cell structure
    highlightTextInCellSafely: function($cell, searchTerm) {
        const cellText = $cell.text().toLowerCase();
        const lowerSearchTerm = searchTerm.toLowerCase();
        
        if (cellText.includes(lowerSearchTerm)) {
            // Get the current HTML content
            let cellHtml = $cell.html();
            
            // Only highlight if the cell contains simple text (no complex HTML)
            if (!cellHtml.includes('<input') && !cellHtml.includes('<span class="badge')) {
                const regex = new RegExp(`(${this.escapeRegExp(searchTerm)})`, 'gi');
                const newHtml = cellHtml.replace(regex, '<span class="search-highlight">$1</span>');
                $cell.html(newHtml);
            }
            
            // Always add the highlight class to the cell for visual indication
            $cell.addClass('search-highlight-cell');
        }
    },
    
    escapeRegExp: function(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },
    
    removeSearchHighlights: function() {
        // Remove highlight spans
        $('.search-highlight').each(function() {
            const $this = $(this);
            $this.replaceWith($this.text());
        });
        
        // Remove highlight classes
        $('.search-highlight-row').removeClass('search-highlight-row');
        $('.search-highlight-cell').removeClass('search-highlight-cell');
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

// Enhanced CSS for search highlights that work with table structure
const searchHighlightCSS = `
<style id="search-highlight-styles">
.search-highlight {
    background-color: #ffff00 !important;
    color: #000 !important;
    font-weight: bold !important;
    padding: 1px 2px !important;
    border-radius: 2px !important;
}

.search-highlight-row {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107 !important;
}

.search-highlight-cell {
    background-color: #fff9e6 !important;
}

.search-no-results {
    margin: 15px 0 !important;
}

/* Ensure table structure is preserved during search */
.historico-details table tr.search-highlight-row td {
    display: table-cell !important;
}
</style>
`;

// Inject enhanced CSS
if (!document.getElementById('search-highlight-styles')) {
    $('head').append(searchHighlightCSS);
}

// Enhanced FilterModule integration
const originalClearFilter = FilterModule.clearFilter;
FilterModule.clearFilter = function() {
    SearchModule.removeSearchHighlights();
    SearchModule.hideNoResultsMessage();
    $('#searchInput').val('');
    originalClearFilter.call(this);
};

// Clear search when filter changes
const originalApplyFilter = FilterModule.applyFilter;
FilterModule.applyFilter = function(filter) {
    if (filter !== 'historico') {
        SearchModule.removeSearchHighlights();
        SearchModule.hideNoResultsMessage();
    }
    originalApplyFilter.call(this, filter);
};