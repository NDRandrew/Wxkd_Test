/* Add this CSS to fix the table layout issues */
<style>
/* Ensure historico tables display properly */
.historico-details table {
    table-layout: fixed;
    width: 100%;
    white-space: nowrap;
}

.historico-details table td {
    vertical-align: top;
    padding: 8px 4px;
    border: 1px solid #ddd;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.historico-details table th {
    padding: 8px 4px;
    border: 1px solid #ddd;
    background-color: #f5f5f5;
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* First column (checkbox) should be narrow */
.historico-details table td:first-child,
.historico-details table th:first-child {
    width: 50px;
    max-width: 50px;
    text-align: center;
}

/* Chave Loja column */
.historico-details table td:nth-child(2),
.historico-details table th:nth-child(2) {
    width: 80px;
    max-width: 80px;
}

/* Nome Loja column - wider */
.historico-details table td:nth-child(3),
.historico-details table th:nth-child(3) {
    width: 200px;
    max-width: 200px;
    white-space: normal;
}

/* Empresa and Loja codes */
.historico-details table td:nth-child(4),
.historico-details table th:nth-child(4),
.historico-details table td:nth-child(5),
.historico-details table th:nth-child(5) {
    width: 80px;
    max-width: 80px;
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
    width: 100px;
    max-width: 100px;
    text-align: right;
}

/* Date columns */
.historico-details table td:nth-child(7),
.historico-details table th:nth-child(7),
.historico-details table td:nth-child(8),
.historico-details table th:nth-child(8),
.historico-details table td:nth-child(17),
.historico-details table th:nth-child(17),
.historico-details table td:nth-child(19),
.historico-details table th:nth-child(19) {
    width: 90px;
    max-width: 90px;
}

/* Status columns (Apto/Não Apto) */
.historico-details table td:nth-child(13),
.historico-details table th:nth-child(13),
.historico-details table td:nth-child(14),
.historico-details table th:nth-child(14),
.historico-details table td:nth-child(15),
.historico-details table th:nth-child(15),
.historico-details table td:nth-child(16),
.historico-details table th:nth-child(16) {
    width: 80px;
    max-width: 80px;
    text-align: center;
}

/* Tipo Contrato column */
.historico-details table td:nth-child(18),
.historico-details table th:nth-child(18) {
    width: 120px;
    max-width: 120px;
}

/* Filtro column */
.historico-details table td:nth-child(20),
.historico-details table th:nth-child(20) {
    width: 100px;
    max-width: 100px;
    text-align: center;
}

/* Ensure the table scrolls horizontally if needed */
.historico-details .table-scrollable {
    overflow-x: auto;
    overflow-y: visible;
}

/* Fix for accordion content */
.panel-body {
    padding: 15px;
}

/* Debug highlighting for issues */
.debug-highlight {
    background-color: #ffcccc !important;
    border: 2px solid #ff0000 !important;
}
</style>

/* JavaScript Debug Helper */
<script>
// Add this debug function to help identify XML parsing issues
function debugHistoricoXML(xmlData, chaveLote) {
    console.group(`Debug Historico XML for Lote ${chaveLote}`);
    
    try {
        console.log('Raw XML:', xmlData);
        
        const $xml = $(xmlData);
        const success = $xml.find('success').text();
        console.log('Success status:', success);
        
        const rows = $xml.find('detailData row');
        console.log('Number of rows found:', rows.length);
        
        rows.each(function(index) {
            console.group(`Row ${index + 1}`);
            
            const $row = $(this);
            console.log('Row element:', this);
            console.log('Row children count:', $row.children().length);
            
            const rowData = {};
            $row.children().each(function() {
                const tagName = this.tagName || this.nodeName;
                const textContent = $(this).text() || '';
                rowData[tagName] = textContent;
                console.log(`${tagName}: "${textContent}"`);
            });
            
            console.log('Complete row object:', rowData);
            console.groupEnd();
        });
        
    } catch (e) {
        console.error('Error parsing XML:', e);
    }
    
    console.groupEnd();
}

// Enhanced load details with debugging
const originalLoadDetails = HistoricoModule.loadDetails;
HistoricoModule.loadDetails = function(e) {
    e.preventDefault();
    const button = $(e.currentTarget);
    const chaveLote = button.data('chave-lote');
    const tbody = button.closest('tbody');
    
    if (button.prop('disabled')) {
        return;
    }
    
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
    
    $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
        .done((xmlData) => {
            // Debug the XML response
            debugHistoricoXML(xmlData, chaveLote);
            
            // Continue with original functionality
            originalLoadDetails.call(this, e);
        })
        .fail((xhr, status, error) => {
            console.error('AJAX request failed:', status, error);
            console.error('Response text:', xhr.responseText);
            tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro na requisição</td></tr>');
            button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
        });
};
</script>