// Add this new StatusFilterModule to your existing JavaScript file

const StatusFilterModule = {
    activeStatusFilters: {},
    
    init: function() {
        console.log('StatusFilterModule: Initializing');
        this.activeStatusFilters = {};
        this.attachEventListeners();
    },
    
    attachEventListeners: function() {
        // Attach click handlers to status filter buttons
        $('.status-filter-btn').off('click.statusFilter').on('click.statusFilter', this.handleStatusFilterClick.bind(this));
        $('#clearStatusFilters').off('click.statusFilter').on('click.statusFilter', this.clearAllStatusFilters.bind(this));
    },
    
    handleStatusFilterClick: function(e) {
        const button = $(e.currentTarget);
        const fieldName = button.data('field');
        
        console.log('StatusFilterModule: Toggle filter for', fieldName);
        
        // Toggle the filter state
        if (this.activeStatusFilters[fieldName]) {
            delete this.activeStatusFilters[fieldName];
            button.removeClass('active');
        } else {
            this.activeStatusFilters[fieldName] = true;
            button.addClass('active');
        }
        
        // Update the UI and apply filters
        this.updateFilterIndicator();
        this.applyStatusFilters();
    },
    
    clearAllStatusFilters: function() {
        console.log('StatusFilterModule: Clearing all status filters');
        
        this.activeStatusFilters = {};
        $('.status-filter-btn').removeClass('active');
        this.updateFilterIndicator();
        this.applyStatusFilters();
    },
    
    updateFilterIndicator: function() {
        const activeCount = Object.keys(this.activeStatusFilters).length;
        const indicator = $('#statusFilterIndicator');
        
        if (activeCount > 0) {
            const filterNames = {
                'AVANCADO': 'Avançado',
                'ORGAO_PAGADOR': 'Órgão Pagador', 
                'PRESENCA': 'Presença',
                'UNIDADE_NEGOCIO': 'Unidade Negócio'
            };
            
            const activeFilterNames = Object.keys(this.activeStatusFilters)
                .map(field => filterNames[field])
                .join(', ');
                
            indicator.find('#activeStatusFilters').text(activeFilterNames);
            indicator.show();
        } else {
            indicator.hide();
        }
    },
    
    applyStatusFilters: function() {
        console.log('StatusFilterModule: Applying status filters', this.activeStatusFilters);
        
        // Trigger the pagination module to update with new filters
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
        
        // Update checkbox state after filtering
        setTimeout(function() {
            CheckboxModule.updateSelectAllState();
            CheckboxModule.updateExportButton();
        }, 100);
    },
    
    // This function will be called by PaginationModule to filter data
    filterRowData: function(rowData) {
        // If no status filters are active, show all rows
        if (Object.keys(this.activeStatusFilters).length === 0) {
            return true;
        }
        
        // Get the status cell (index 5 based on your table structure)
        const statusCell = rowData[5];
        if (!statusCell) return false;
        
        // Check each active filter
        for (const fieldName in this.activeStatusFilters) {
            if (!this.isFieldActive(statusCell, fieldName)) {
                return false; // Row doesn't match this filter
            }
        }
        
        return true; // Row matches all active filters
    },
    
    // Check if a specific field is active (green) in the status cell
    isFieldActive: function(statusCell, fieldName) {
        const cellHtml = statusCell.html();
        
        // Map field names to their labels
        const fieldLabels = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'PRESENCA': 'PR', 
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const label = fieldLabels[fieldName];
        if (!label) return false;
        
        // Look for the specific label with green background
        const regex = new RegExp(`background-color:\\s*green[^>]*>${label}<`, 'i');
        return regex.test(cellHtml);
    },
    
    // Get current active filters (for debugging)
    getActiveFilters: function() {
        return this.activeStatusFilters;
    }
};

// Modify the existing PaginationModule.filterData method
// Replace the existing filterData method with this enhanced version:

PaginationModule.filterData = function() {
    if (this.searchTerm === '' && Object.keys(StatusFilterModule.activeStatusFilters).length === 0) {
        this.filteredData = [...this.allData];
    } else {
        const self = this;
        this.filteredData = this.allData.filter(function(row) {
            // First apply search filter
            let matchesSearch = true;
            if (self.searchTerm !== '') {
                matchesSearch = row.some(function(cell) {
                    return $(cell).text().toLowerCase().includes(self.searchTerm);
                });
            }
            
            // Then apply status filters
            let matchesStatusFilter = StatusFilterModule.filterRowData(row);
            
            return matchesSearch && matchesStatusFilter;
        });
    }
};

// Modify the existing PaginationModule.replaceTableData method to add data attributes
// Add this code after line where squaresHtml is generated:

// Enhanced replaceTableData method - add this after the existing squaresHtml generation
PaginationModule.replaceTableDataEnhanced = function(newData) {
    this.allData = [];
    const self = this;
    newData.forEach(function(row, index) {
        const rowData = [];
        
        // Checkbox cell (same as before)
        const checkboxCell = $('<td class="checkbox-column">');
        const label = $('<label>');
        const sequentialId = index + 1;
        const checkbox = $('<input>')
            .attr({
                'type': 'checkbox',
                'class': 'form-check-input row-checkbox',
                'data-row-id': sequentialId,
                'value': sequentialId,
                'id': sequentialId
            });
        const span = $('<span class="text">');
        label.append(checkbox).append(span);
        checkboxCell.append(label);
        rowData.push(checkboxCell);
        
        // Other cells (same as before)
        rowData.push($('<td>').text(row.CHAVE_LOJA));
        rowData.push($('<td>').text(row.NOME_LOJA));
        rowData.push($('<td>').text(row.COD_EMPRESA));
        rowData.push($('<td>').text(row.COD_LOJA));
        
        // Enhanced status squares generation with data attributes
        var fields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP', 
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };

        var cutoff = new Date(2025, 5, 1);

        function parseDate(dateString) {
            if (!dateString || typeof dateString !== 'string') {
                return null;
            }
            
            var parts = dateString.trim().split('/');
            if (parts.length !== 3) {
                return null;
            }
            
            var day = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1; 
            var year = parseInt(parts[2], 10);
            
            if (isNaN(day) || isNaN(month) || isNaN(year)) {
                return null;
            }
            
            var date = new Date(year, month, day);
            
            if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
                return null;
            }
            
            return date;
        }

        var squaresHtml = '<div class="status-container">';
        for (var field in fields) {
            if (fields.hasOwnProperty(field)) {
                var label = fields[field];
                var raw = row[field] ? row[field].toString().trim() : '';
                
                var dateObj = parseDate(raw);
                var isOn = dateObj !== null && dateObj > cutoff;
                var color = isOn ? 'green' : 'gray';
                var status = isOn ? 'active' : 'inactive';
                
                squaresHtml += '<div style="display:inline-block;width:30px;height:30px;' +
                            'margin-right:5px;text-align:center;line-height:30px;' +
                            'font-size:10px;font-weight:bold;color:white;' +
                            'background-color:' + color + ';border-radius:4px;" ' +
                            'data-field="' + field + '" data-status="' + status + '">' + 
                            label + '</div>';
            }
        }
        squaresHtml += '</div>';

        rowData.push($('<td>').html(squaresHtml));
        
        // Continue with the rest of your existing code for other columns...
        // (All the other column generation code remains the same)
        
        // For brevity, I'm not repeating all the other column generation code
        // Just continue with your existing logic for the remaining columns
        
        self.allData.push(rowData);
    });
};

// HTML structure to add to your page (add this to your existing filter section):

const statusFilterHTML = `
    <!-- Status Filter Section - Add this after your existing filter buttons -->
    <div style="margin-top: 15px; margin-bottom: 10px; border-top: 1px solid #ddd; padding-top: 15px;">
        <label class="me-2 text-sm">Filtros de Status:</label>
        <button type="button" class="btn btn-sm status-filter-btn" id="filterAV" data-field="AVANCADO">
            <span class="status-indicator" style="background-color: green;">AV</span> Avançado
        </button>
        <button type="button" class="btn btn-sm status-filter-btn" id="filterOP" data-field="ORGAO_PAGADOR">
            <span class="status-indicator" style="background-color: green;">OP</span> Órgão Pagador
        </button>
        <button type="button" class="btn btn-sm status-filter-btn" id="filterPR" data-field="PRESENCA">
            <span class="status-indicator" style="background-color: green;">PR</span> Presença
        </button>
        <button type="button" class="btn btn-sm status-filter-btn" id="filterUN" data-field="UNIDADE_NEGOCIO">
            <span class="status-indicator" style="background-color: green;">UN</span> Unidade Negócio
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearStatusFilters" style="margin-left: 10px;">
            Limpar Status
        </button>
    </div>
    
    <!-- Status Filter Indicator -->
    <div id="statusFilterIndicator" style="display: none; margin-bottom: 10px; padding: 8px; background-color: #e7f3ff; border: 1px solid #b3d7ff; border-radius: 4px;">
        <small><strong>Filtros de Status Ativos:</strong> <span id="activeStatusFilters"></span></small>
    </div>
`;

// CSS for the status filter buttons - add this to your CSS:
const statusFilterCSS = `
    .status-filter-btn {
        margin-right: 8px;
        margin-bottom: 5px;
        border: 2px solid #ddd;
        background-color: #f8f9fa;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    
    .status-filter-btn.active {
        border-color: #28a745;
        background-color: #28a745;
        color: white;
    }
    
    .status-filter-btn:hover {
        border-color: #28a745;
        color: #28a745;
    }
    
    .status-filter-btn.active:hover {
        background-color: #218838;
        border-color: #218838;
        color: white;
    }
    
    .status-indicator {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 3px;
        text-align: center;
        line-height: 20px;
        font-size: 8px;
        font-weight: bold;
        color: white;
        border-radius: 3px;
        vertical-align: middle;
    }
`;

// Update your document ready section to include the new module:
$(document).ready(function() {
    console.log('Document ready - initializing modules');
    
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init(); // Add this line
    ExportModule.init();
    
    console.log('All modules initialized');
});