hasValidationError: function(row) {
    var cutoff = new Date(2025, 5, 1); // June 1, 2025
    var activeTypes = [];
    var fields = {
        'AVANCADO': 'AV',
        'ORGAO_PAGADOR': 'OP',
        'PRESENCA': 'PR',
        'UNIDADE_NEGOCIO': 'UN'
    };
    
    // Get active types
    for (var field in fields) {
        if (fields.hasOwnProperty(field)) {
            var raw = row[field] ? row[field].toString().trim() : '';
            if (raw) {
                var parts = raw.split('/');
                if (parts.length === 3) {
                    var day = parseInt(parts[0], 10);
                    var month = parseInt(parts[1], 10) - 1;
                    var year = parseInt(parts[2], 10);
                    var date = new Date(year, month, day);
                    if (date > cutoff) {
                        activeTypes.push(fields[field]);
                    }
                }
            }
        }
    }
    
    // Check validation for each active type
    for (var i = 0; i < activeTypes.length; i++) {
        var type = activeTypes[i];
        if (type === 'AV' || type === 'PR' || type === 'UN') {
            if (!this.checkBasicValidationsJS(row)) {
                return true;
            }
        } else if (type === 'OP') {
            if (!this.checkOPValidationsJS(row)) {
                return true;
            }
        }
    }
    
    return false;
},

getValidationErrorMessage: function(row) {
    var cutoff = new Date(2025, 5, 1);
    var activeTypes = [];
    var fields = {
        'AVANCADO': 'AV',
        'ORGAO_PAGADOR': 'OP',
        'PRESENCA': 'PR',
        'UNIDADE_NEGOCIO': 'UN'
    };
    
    // Get active types
    for (var field in fields) {
        if (fields.hasOwnProperty(field)) {
            var raw = row[field] ? row[field].toString().trim() : '';
            if (raw) {
                var parts = raw.split('/');
                if (parts.length === 3) {
                    var day = parseInt(parts[0], 10);
                    var month = parseInt(parts[1], 10) - 1;
                    var year = parseInt(parts[2], 10);
                    var date = new Date(year, month, day);
                    if (date > cutoff) {
                        activeTypes.push(fields[field]);
                    }
                }
            }
        }
    }
    
    // Check validation and return error message
    for (var i = 0; i < activeTypes.length; i++) {
        var type = activeTypes[i];
        if (type === 'AV' || type === 'PR' || type === 'UN') {
            var basicError = this.getBasicValidationErrorJS(row);
            if (basicError) {
                return 'Tipo ' + type + ' - ' + basicError;
            }
        } else if (type === 'OP') {
            var opError = this.getOPValidationErrorJS(row);
            if (opError) {
                return opError;
            }
        }
    }
    
    return 'Erro de validacao';
},

checkBasicValidationsJS: function(row) {
    var requiredFields = ['DEP_DINHEIRO_VALID', 'DEP_CHEQUE_VALID', 'REC_RETIRADA_VALID', 'SAQUE_CHEQUE_VALID'];
    for (var i = 0; i < requiredFields.length; i++) {
        if (!row[requiredFields[i]] || row[requiredFields[i]] != '1') {
            return false;
        }
    }
    return true;
},

getBasicValidationErrorJS: function(row) {
    var requiredFields = {
        'DEP_DINHEIRO_VALID': 'Deposito Dinheiro',
        'DEP_CHEQUE_VALID': 'Deposito Cheque',
        'REC_RETIRADA_VALID': 'Recarga/Retirada',
        'SAQUE_CHEQUE_VALID': 'Saque Cheque'
    };
    
    var missingFields = [];
    for (var field in requiredFields) {
        if (requiredFields.hasOwnProperty(field)) {
            if (!row[field] || row[field] != '1') {
                missingFields.push(requiredFields[field]);
            }
        }
    }
    
    return missingFields.length > 0 ? 'Validacoes pendentes: ' + missingFields.join(', ') : null;
},

checkOPValidationsJS: function(row) {
    var version = this.extractVersionFromContractJS(row.TIPO_CONTRATO || '');
    if (version === null) return false;
    
    var requiredFields = ['DEP_DINHEIRO_VALID', 'DEP_CHEQUE_VALID', 'REC_RETIRADA_VALID', 'SAQUE_CHEQUE_VALID', 'HOLERITE_INSS_VALID', 'CONSULTA_INSS_VALID'];
    if (version > 10.1) {
        requiredFields.push('SEGUNDA_VIA_CARTAO_VALID');
    }
    
    for (var i = 0; i < requiredFields.length; i++) {
        if (!row[requiredFields[i]] || row[requiredFields[i]] != '1') {
            return false;
        }
    }
    return true;
},

getOPValidationErrorJS: function(row) {
    var version = this.extractVersionFromContractJS(row.TIPO_CONTRATO || '');
    if (version === null) {
        return 'Tipo de contrato nao pode ser exportado: ' + (row.TIPO_CONTRATO || '');
    }
    
    var requiredFields = {
        'DEP_DINHEIRO_VALID': 'Deposito Dinheiro',
        'DEP_CHEQUE_VALID': 'Deposito Cheque',
        'REC_RETIRADA_VALID': 'Recarga/Retirada',
        'SAQUE_CHEQUE_VALID': 'Saque Cheque',
        'HOLERITE_INSS_VALID': 'Holerite INSS',
        'CONSULTA_INSS_VALID': 'Consulta INSS'
    };
    
    if (version > 10.1) {
        requiredFields['SEGUNDA_VIA_CARTAO_VALID'] = 'Segunda Via Cartao';
    }
    
    var missingFields = [];
    for (var field in requiredFields) {
        if (requiredFields.hasOwnProperty(field)) {
            if (!row[field] || row[field] != '1') {
                missingFields.push(requiredFields[field]);
            }
        }
    }
    
    return missingFields.length > 0 ? 'Validacoes OP pendentes (v' + version + '): ' + missingFields.join(', ') : null;
},

extractVersionFromContractJS: function(tipoContrato) {
    var match = tipoContrato.match(/(\d+\.\d+)/);
    if (match) {
        var version = parseFloat(match[1]);
        return version >= 8.1 ? version : null;
    }
    return null;
}