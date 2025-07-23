const SearchModule = {
    isSearchingHistorico: false,
    
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
            this.removeSearchHighlights();
            return;
        }
        
        this.isSearchingHistorico = true;
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
                this.highlightText($title, searchTerm);
                foundAny = true;
            } else {
                // Check if data inside accordion matches
                this.searchInsideAccordion($panel, chaveLote, searchTerm).then((dataMatches) => {
                    if (dataMatches) {
                        $panel.show();
                        foundAny = true;
                        // Optionally expand the accordion to show the matching data
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
                            
                            // Search through the XML data without loading it into DOM
                            $xml.find('detailData row').each(function() {
                                const row = {};
                                $(this).children().each(function() {
                                    row[this.tagName] = $(this).text().toLowerCase();
                                });
                                
                                // Search in all row data
                                for (const key in row) {
                                    if (row[key].includes(searchTerm)) {
                                        foundMatch = true;
                                        return false; // Break out of loop
                                    }
                                }
                                
                                if (foundMatch) return false; // Break out of outer loop
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
    
    searchInTableData: function($tableBody, searchTerm) {
        let found = false;
        
        $tableBody.find('tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.includes(searchTerm)) {
                found = true;
                // Highlight the matching text in this row
                SearchModule.highlightTextInElement($(this), searchTerm);
                return false; // Break loop
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
                this.highlightMatchingDataRows($detailsBody, searchTerm);
            }, 1000);
        } else {
            // Data already loaded, highlight immediately
            this.highlightMatchingDataRows($detailsBody, searchTerm);
        }
    },
    
    highlightMatchingDataRows: function($tableBody, searchTerm) {
        $tableBody.find('tr').each(function() {
            const $row = $(this);
            const rowText = $row.text().toLowerCase();
            
            if (rowText.includes(searchTerm)) {
                // Highlight the entire row
                $row.addClass('search-highlight-row');
                
                // Highlight specific text in cells
                $row.find('td').each(function() {
                    const $cell = $(this);
                    SearchModule.highlightTextInElement($cell, searchTerm);
                });
            }
        });
    },
    
    highlightText: function($element, searchTerm) {
        const originalText = $element.text();
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        const highlightedText = originalText.replace(regex, '<span class="search-highlight">$1</span>');
        
        // Preserve the original HTML structure while adding highlights
        $element.html(highlightedText);
    },
    
    highlightTextInElement: function($element, searchTerm) {
        const originalText = $element.text();
        const lowerText = originalText.toLowerCase();
        const lowerSearchTerm = searchTerm.toLowerCase();
        
        if (lowerText.includes(lowerSearchTerm)) {
            const regex = new RegExp(`(${this.escapeRegExp(searchTerm)})`, 'gi');
            const highlightedText = originalText.replace(regex, '<span class="search-highlight">$1</span>');
            $element.html(highlightedText);
        }
    },
    
    escapeRegExp: function(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    },
    
    removeSearchHighlights: function() {
        $('.search-highlight').each(function() {
            const $this = $(this);
            $this.replaceWith($this.text());
        });
        
        $('.search-highlight-row').removeClass('search-highlight-row');
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

// Add CSS styles for search highlights
const searchStyles = `
<style>
.search-highlight {
    background-color: #ffff00;
    color: #000;
    font-weight: bold;
    padding: 1px 2px;
    border-radius: 2px;
}

.search-highlight-row {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107;
}

.search-no-results {
    margin: 15px 0;
}
</style>
`;

// Inject styles into the document
if (!document.getElementById('search-highlight-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'search-highlight-styles';
    styleElement.innerHTML = searchStyles;
    document.head.appendChild(styleElement);
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