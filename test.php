function getTipoCorrespondenteByDataConclusao(row) {
    const tipoCampos = {
        'AVANCADO': 'AV',
        'PRESENCA': 'PR', 
        'UNIDADE_NEGOCIO': 'UN',
        'ORGAO_PAGADOR': 'OP',
        'ORG_PAGADOR': 'OP'  // Support both variants
    };

    const cutoff = new Date(2025, 5, 1);
    let mostRecentDate = null;
    let mostRecentType = '';
    
    const currentFilter = getCurrentFilter();

    function parseDate(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return null;
        }
        
        const parts = dateString.trim().split('/');
        if (parts.length !== 3) {
            // Try parsing as YYYY-MM-DD format
            if (dateString.match(/^\d{4}-\d{2}-\d{2}/)) {
                const date = new Date(dateString);
                if (!isNaN(date.getTime())) {
                    return date;
                }
            }
            return null;
        }
        
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1; 
        const year = parseInt(parts[2], 10);
        
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            return null;
        }
        
        const date = new Date(year, month, day);
        
        if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
            return null;
        }
        
        return date;
    }

    console.log('Cutoff date:', cutoff);
    console.log('Current filter:', currentFilter);
    
    if (currentFilter === 'historico') {
        const raw = getXMLNodeValue(row, 'data_conclusao');
        
        if (raw) {
            const date = parseDate(raw);
            console.log(`Parsed date for historico:`, date);
            
            if (date && date > cutoff) {
                console.log(`Date ${raw} is after cutoff`);
                mostRecentType = getXMLNodeValue(row, 'tipo_correspondente');
                console.log(`Historico type: ${mostRecentType}`);
            }
        }
    } else if (currentFilter === 'descadastramento') {
        const tipoCorrespondente = getXMLNodeValue(row, 'tipo_correspondente');
        const dataConclusao = getXMLNodeValue(row, 'data_conclusao') || getXMLNodeValue(row, 'DATA_CONCLUSAO');
        
        console.log(`Descadastramento - Tipo: ${tipoCorrespondente}, Data: ${dataConclusao}`);
        
        if (tipoCorrespondente && dataConclusao) {
            const date = parseDate(dataConclusao);
            console.log(`Parsed date for descadastramento:`, date);
            
            if (date && date > cutoff) {
                console.log(`Date ${dataConclusao} is after cutoff`);
                const upperTipo = tipoCorrespondente.toUpperCase();
                if (tipoCampos[upperTipo]) {
                    mostRecentType = tipoCampos[upperTipo];
                } else {
                    // Try to match partial strings
                    for (const campo in tipoCampos) {
                        if (upperTipo.includes(campo)) {
                            mostRecentType = tipoCampos[campo];
                            break;
                        }
                    }
                }
                console.log(`Descadastramento mapped type: ${mostRecentType}`);
            }
        }
    } else {
        // For other filters (cadastramento, all), use individual date fields
        for (const campo in tipoCampos) {
            // Skip the duplicate ORG_PAGADOR entry for other filters
            if (campo === 'ORG_PAGADOR') continue;
            
            const raw = getXMLNodeValue(row, campo).trim();
            console.log(`Checking ${campo}: raw value = "${raw}"`);
            
            if (raw) {
                const date = parseDate(raw);
                console.log(`Parsed date for ${campo}:`, date);
                console.log(`Cutoff comparison for ${campo}: ${date} > ${cutoff} = ${date > cutoff}`);
                
                if (date && date > cutoff) {
                    console.log(`Date ${raw} for ${campo} is after cutoff`);
                    if (mostRecentDate === null || date > mostRecentDate) {
                        mostRecentDate = date;
                        mostRecentType = tipoCampos[campo];
                        console.log(`New most recent: ${campo} = ${tipoCampos[campo]}, Date: ${date}`);
                    }
                } else if (date) {
                    console.log(`Date ${raw} for ${campo} is NOT after cutoff (${date} <= ${cutoff})`);
                }
            }
        }
    }

    console.log('Final result - Most recent type found:', mostRecentType, 'Date:', mostRecentDate);
    return mostRecentType;
}