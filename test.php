generateDateFieldsHTML: function(row) {
    const fields = {
        'AVANCADO': 'AV',
        'ORGAO_PAGADOR': 'OP', 
        'ORG_PAGADOR': 'OP',  // Handle both variants
        'PRESENCA': 'PR',
        'UNIDADE_NEGOCIO': 'UN'
    };
    
    const cutoff = new Date(2025, 5, 1);
    const matchingDates = [];
    
    // Check if we're dealing with descadastramento filter
    if (FilterModule.currentFilter === 'descadastramento') {
        // For descadastramento, only show DATA_CONCLUSAO if it's after cutoff and matches TIPO_CORRESPONDENTE
        const tipoCorrespondente = row['TIPO_CORRESPONDENTE'] ? row['TIPO_CORRESPONDENTE'].toUpperCase().trim() : '';
        const dataConclusao = row['DATA_CONCLUSAO'] ? row['DATA_CONCLUSAO'].toString().trim() : '';
        
        console.log('DEBUG - generateDateFieldsHTML descadastramento:', {
            tipoCorrespondente: tipoCorrespondente,
            dataConclusao: dataConclusao
        });
        
        // Check if we have a valid tipo and date
        if (tipoCorrespondente && dataConclusao) {
            // Handle both ORG_PAGADOR and ORGAO_PAGADOR variants
            const validTipos = Object.keys(fields);
            const isValidTipo = validTipos.includes(tipoCorrespondente);
            
            if (isValidTipo) {
                const dateObj = this.parseDate(dataConclusao);
                if (dateObj && dateObj > cutoff) {
                    // Always format as DD/MM/YYYY regardless of input format
                    const day = ('0' + dateObj.getDate()).slice(-2);
                    const month = ('0' + (dateObj.getMonth() + 1)).slice(-2);
                    const year = dateObj.getFullYear();
                    const formattedDate = `${day}/${month}/${year}`;
                    
                    console.log('DEBUG - Date formatting:', {
                        original: dataConclusao,
                        parsed: dateObj,
                        formatted: formattedDate
                    });
                    
                    matchingDates.push(formattedDate);
                }
            }
        }
    } else {
        // For other filters, use individual date fields as before
        for (const field in fields) {
            if (field === 'ORG_PAGADOR') continue; // Skip duplicate
            
            const raw = row[field] ? row[field].toString().trim() : '';
            const dateObj = this.parseDate(raw);
            
            if (dateObj && dateObj > cutoff) {
                // Also format these as DD/MM/YYYY for consistency
                const day = ('0' + dateObj.getDate()).slice(-2);
                const month = ('0' + (dateObj.getMonth() + 1)).slice(-2);
                const year = dateObj.getFullYear();
                matchingDates.push(`${day}/${month}/${year}`);
            }
        }
    }
    
    return matchingDates.length > 0 ? matchingDates.join(' / ') : '—';
},