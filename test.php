Looking at your system, I'll help you add two new filter boxes for contract versions. Here are the changes needed:1. Update HTML (TestH.txt)Add the two new filter buttons after the existing ones (around line 185):<button type="button" class="status-filter-btn" 
        style="margin-right:5px;padding:0px;border:0px;" 
        id="filterUN" data-field="UNIDADE_NEGOCIO" onclick="toggleStatus(this)">
    <span class="status-indicator" 
            style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
        UN
    </span>
</button>
<!-- Add these two new buttons -->
<button type="button" class="status-filter-btn" 
        style="margin-right:5px;padding:0px;border:0px;" 
        id="filter10" data-field="CONTRATO_10" onclick="toggleStatus(this)">
    <span class="status-indicator" 
            style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
        10
    </span>
</button>
<button type="button" class="status-filter-btn" 
        style="margin-right:5px;padding:0px;border:0px;" 
        id="filter8" data-field="CONTRATO_8" onclick="toggleStatus(this)">
    <span class="status-indicator" 
            style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
        8
    </span>
</button>2. Update JavaScript (TestJ.txt)Update the StatusFilterModule to handle contract version filtering:const StatusFilterModule = {
    activeStatusFilters: {},
    
    init: function() {
        this.activeStatusFilters = {};
        this.attachEventListeners();
    },
    
    attachEventListeners: function() {
        $('.status-filter-btn').off('click.statusFilter').on('click.statusFilter', this.handleStatusFilterClick.bind(this));
    },
    
    handleStatusFilterClick: function(e) {
        const button = $(e.currentTarget);
        const fieldName = button.data('field');
        
        if (this.activeStatusFilters[fieldName]) {
            delete this.activeStatusFilters[fieldName];
            button.removeClass('active');
        } else {
            this.activeStatusFilters[fieldName] = true;
            button.addClass('active');
        }
        
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
                'UNIDADE_NEGOCIO': 'Unidade Negócio',
                'CONTRATO_10': 'Contrato >= 10.1',  // Add these
                'CONTRATO_8': 'Contrato >= 8.1'     // Add these
            };
            
            const activeFilterNames = Object.keys(this.activeStatusFilters)
                .map(field => filterNames[field])
                .join(', ');
                
            indicator.find('#activeStatusFilters').text(activeFilterNames);
            indicator.fadeIn();
        } else {
            indicator.fadeOut();
        }
    },
    
    applyStatusFilters: function() {
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
        
        setTimeout(() => {
            CheckboxModule.updateSelectAllState();
            CheckboxModule.updateExportButton();
        }, 100);
    },
    
    filterRowData: function(rowData) {
        if (Object.keys(this.activeStatusFilters).length === 0) {
            return true;
        }
        
        for (const fieldName in this.activeStatusFilters) {
            if (fieldName === 'CONTRATO_10' || fieldName === 'CONTRATO_8') {
                // Handle contract version filtering
                if (!this.isContractVersionActive(rowData, fieldName)) {
                    return false;
                }
            } else {
                // Handle existing status filtering
                const statusCell = rowData[6]; // Column 6 has the status indicators
                if (!statusCell) return false;
                
                if (!this.isFieldActive(statusCell, fieldName)) {
                    return false;
                }
            }
        }
        
        return true;
    },
    
    // Add this new method for contract version filtering
    isContractVersionActive: function(rowData, fieldName) {
        // TIPO_CONTRATO is in column 17 (0-based)
        const contractCell = rowData[17];
        if (!contractCell) return false;
        
        const contractText = contractCell.text();
        const version = this.extractVersionFromContract(contractText);
        
        if (version === null) return false;
        
        if (fieldName === 'CONTRATO_10') {
            return version >= 10.1;
        } else if (fieldName === 'CONTRATO_8') {
            return version >= 8.1;
        }
        
        return false;
    },
    
    // Add this helper method
    extractVersionFromContract: function(tipoContrato) {
        const match = tipoContrato.match(/(\d+\.\d+)/);
        if (match) {
            const version = parseFloat(match[1]);
            return version >= 8.1 ? version : null;
        }
        return null;
    },
    
    isFieldActive: function(statusCell, fieldName) {
        const cellHtml = statusCell.html();
        const fieldLabels = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'PRESENCA': 'PR', 
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const label = fieldLabels[fieldName];
        if (!label) return false;
        
        const regex = new RegExp(`background-color:\\s*green[^>]*>${label}<`, 'i');
        return regex.test(cellHtml);
    }
};3. Test the ImplementationAfter making these changes:The two new filter buttons ("10" and "8") will appear next to the existing onesClicking "10" will show only records where the contract version is >= 10.1Clicking "8" will show only records where the contract version is >= 8.1A record with "Termo de Adesão 10.7" will appear in both filters since 10.7 >= 10.1 and 10.7 >= 8.1The filters can be combined with existing status filtersThe system will:Extract the version number from the TIPO_CONTRATO field using regexCompare it against the minimum version for each filterShow/hide rows accordinglyUpdate the filter indicator to show which contract filters are activeThis implementation reuses the existing extractVersionFromContract logic (similar to your checkOPValidationsJS function) and integrates seamlessly with the current filtering system.