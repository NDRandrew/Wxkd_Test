createConditionalValidationCell: function(row, validField, orgaoPagadorEmpty) {
    let value = '';
    let style = 'text-align: center; vertical-align: middle;';
    
    if (orgaoPagadorEmpty) {
        value = ' ----- ';
        // No red background for horizontal line
    } else {
        const isValid = row[validField] === '1';
        
        // Extract version from TIPO_CONTRATO
        const tipoContrato = row.TIPO_CONTRATO || '';
        const version = this.extractVersionFromContract(tipoContrato);
        
        // Set text based on version ranges
        if (version !== null) {
            if (version < 8.1) {
                value = 'Versão < 8.1';
            } else if (version >= 8.1 && version <= 10.1) {
                value = 'Versão 8.1-10.1';
            } else if (version > 10.1) {
                value = 'Versão > 10.1';
            } else {
                value = 'Versão inválida';
            }
        } else {
            // If no valid version found, show default text
            value = 'Sem versão';
        }
        
        // Set background color to red if field value is 0 (not valid)
        if (!isValid) {
            style += ' background-color: #ffb7bb;';
        }
    }
    
    return $('<td>').attr('style', style).text(value);
},

// Helper function to extract version from contract (if not already exists)
extractVersionFromContract: function(tipoContrato) {
    if (!tipoContrato || typeof tipoContrato !== 'string') {
        return null;
    }
    
    const match = tipoContrato.match(/(\d+\.\d+)/);
    if (match) {
        const version = parseFloat(match[1]);
        return !isNaN(version) ? version : null;
    }
    return null;
}