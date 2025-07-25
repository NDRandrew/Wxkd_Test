Looking at the JavaScript code, I need to understand how the existing filtering works. Let me analyze the `filterRowData` function and `isFieldActive` function.

Looking at the existing code:

1. The `filterRowData` function receives `rowData` as parameter
2. It checks `statusCell = rowData[6]` for status filtering
3. The `isFieldActive` function looks for specific HTML patterns in the status cell

But for contract filtering, I need to look at a different column. Looking at the HTML structure in TestH.txt, I can see the table structure:

- Column 0: Checkbox
- Column 1: Chave_Loja  
- Column 2: Nome_Loja
- Column 3: Cod_Empresa
- Column 4: Cod_Loja
- Column 5: QUANT_LOJAS
- Column 6: TIPO_CORRESPONDENTE (status indicators)
- Column 7: DATA_CONCLUSAO
- Column 8: DATA_SOLICITACAO
- ...
- Column 16: Data_Contrato
- Column 17: Tipo_Contrato

So the contract type would be in column 17 (0-based indexing).

Looking at the existing `PaginationModule.createRowData` function, I can see how the row data is structured. The TIPO_CONTRATO data is added as the last column.

Let me check the `filterRowData` function more carefully. It receives `rowData` which seems to be an array of jQuery objects representing table cells.

The issue might be:
1. I'm looking at the wrong column index
2. The way I'm extracting the text from the cell is incorrect
3. The version extraction logic has an issue

Let me fix this by following the existing patterns more closely.

Looking at the existing code structure, I can see the issue. Let me fix the contract version filtering by following the existing patterns more closely.

Here's the corrected JavaScript code for the `StatusFilterModule`:

```javascript
const StatusFilterModule = {
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
                'CONTRATO_10': 'Contrato >= 10.1',
                'CONTRATO_8': 'Contrato >= 8.1'
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
                // Handle contract version filtering - TIPO_CONTRATO is in column 17
                if (!this.isContractVersionActive(rowData, fieldName)) {
                    return false;
                }
            } else {
                // Handle existing status filtering - status indicators are in column 6
                const statusCell = rowData[6];
                if (!statusCell) return false;
                
                if (!this.isFieldActive(statusCell, fieldName)) {
                    return false;
                }
            }
        }
        
        return true;
    },
    
    isContractVersionActive: function(rowData, fieldName) {
        // TIPO_CONTRATO is in column 17 (last column)
        const contractCell = rowData[17];
        if (!contractCell) return false;
        
        const contractText = $(contractCell).text().trim();
        console.log('Contract text:', contractText); // Debug log
        
        if (!contractText || contractText === '' || contractText.includes('NULL')) {
            return false;
        }
        
        const version = this.extractVersionFromContract(contractText);
        console.log('Extracted version:', version, 'for field:', fieldName); // Debug log
        
        if (version === null) return false;
        
        if (fieldName === 'CONTRATO_10') {
            return version >= 10.1;
        } else if (fieldName === 'CONTRATO_8') {
            return version >= 8.1;
        }
        
        return false;
    },
    
    extractVersionFromContract: function(tipoContrato) {
        if (!tipoContrato || typeof tipoContrato !== 'string') {
            return null;
        }
        
        const match = tipoContrato.match(/(\d+\.\d+)/);
        if (match) {
            const version = parseFloat(match[1]);
            console.log('Parsed version:', version, 'from:', tipoContrato); // Debug log
            return !isNaN(version) && version >= 8.1 ? version : null;
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
};
```

Also, I noticed that you need to make sure the contract version filtering works with the existing `PaginationModule`. The issue might be that the contract column index is different. 

Let me check the table structure again. Looking at the HTML, the columns are:

1. Checkbox (0)
2. Chave_Loja (1)  
3. Nome_Loja (2)
4. Cod_Empresa (3)
5. Cod_Loja (4)
6. QUANT_LOJAS (5)
7. TIPO_CORRESPONDENTE (6) - Status indicators
8. DATA_CONCLUSAO (7)
9. DATA_SOLICITACAO (8)
10. Dep_Dinheiro (9)
11. Dep_Cheque (10)
12. Rec_Retirada (11)
13. Saque_Cheque (12)
14. 2Via_Cartao (13)
15. Holerite_INSS (14)
16. Cons_Inss (15)
17. Prova_de_vida (16)
18. Data_Contrato (17)
19. Tipo_Contrato (18) - This should be the contract type

So the contract type should be in column 18, not 17. Let me update that:

```javascript
isContractVersionActive: function(rowData, fieldName) {
    // TIPO_CONTRATO is in column 18 (0-based indexing)
    const contractCell = rowData[18];
    if (!contractCell) return false;
    
    const contractText = $(contractCell).text().trim();
    console.log('Contract text:', contractText); // Debug log
    
    if (!contractText || contractText === '' || contractText.includes('NULL')) {
        return false;
    }
    
    const version = this.extractVersionFromContract(contractText);
    console.log('Extracted version:', version, 'for field:', fieldName); // Debug log
    
    if (version === null) return false;
    
    if (fieldName === 'CONTRATO_10') {
        return version >= 10.1;
    } else if (fieldName === 'CONTRATO_8') {
        return version >= 8.1;
    }
    
    return false;
},
```

Try this updated code and check the browser console for the debug logs to see what's happening with the contract text extraction.