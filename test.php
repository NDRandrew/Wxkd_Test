generateDateFieldsHTML: function(row) {
    const fields = {
        'AVANCADO': 'AV',
        'ORGAO_PAGADOR': 'OP', 
        'PRESENCA': 'PR',
        'UNIDADE_NEGOCIO': 'UN'
    };
    
    const cutoff = new Date(2025, 5, 1);
    const matchingDates = [];
    
    // Check if we're dealing with descadastramento filter
    if (FilterModule.currentFilter === 'descadastramento') {
        // For descadastramento, only show DATA_CONCLUSAO if it's after cutoff and matches TIPO_CORRESPONDENTE
        const tipoCorrespondente = row['TIPO_CORRESPONDENTE'] ? row['TIPO_CORRESPONDENTE'].toUpperCase() : '';
        const dataConclusao = row['DATA_CONCLUSAO'] ? row['DATA_CONCLUSAO'].toString().trim() : '';
        
        // Check if we have a valid tipo and date
        if (tipoCorrespondente && dataConclusao) {
            // Handle both ORG_PAGADOR and ORGAO_PAGADOR variants
            const validTipos = Object.keys(fields).concat(['ORG_PAGADOR']);
            const isValidTipo = validTipos.includes(tipoCorrespondente);
            
            if (isValidTipo) {
                const dateObj = this.parseDate(dataConclusao);
                if (dateObj && dateObj > cutoff) {
                    // Format the date for display
                    if (dataConclusao.match(/^\d{4}-\d{2}-\d{2}/)) {
                        const date = new Date(dataConclusao);
                        if (!isNaN(date.getTime())) {
                            const day = ('0' + date.getDate()).slice(-2);
                            const month = ('0' + (date.getMonth() + 1)).slice(-2);
                            const year = date.getFullYear();
                            matchingDates.push(`${day}/${month}/${year}`);
                        }
                    } else {
                        matchingDates.push(dataConclusao);
                    }
                }
            }
        }
    } else {
        // For other filters, use individual date fields as before
        for (const field in fields) {
            const raw = row[field] ? row[field].toString().trim() : '';
            const dateObj = this.parseDate(raw);
            
            if (dateObj && dateObj > cutoff) {
                matchingDates.push(raw);
            }
        }
    }
    
    return matchingDates.length > 0 ? matchingDates.join(' / ') : '—';
},