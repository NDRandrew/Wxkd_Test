generateStatusHTML: function(row, activeFilter) {
    const fields = {
        'AVANCADO': 'AV',
        'ORGAO_PAGADOR': 'OP',
        'PRESENCA': 'PR',
        'UNIDADE_NEGOCIO': 'UN'
    };

    const cutoff = new Date(2025, 5, 1); // June 1, 2025
    let html = '<div class="status-container">';

    if (activeFilter === 'descadastramento') {
        const tipo = row['TIPO_CORRESPONDENTE'] ? row['TIPO_CORRESPONDENTE'].toUpperCase() : '';
        const dataConclusao = row['DATA_CONCLUSAO'] ? row['DATA_CONCLUSAO'].toString().trim() : '';
        
        console.log('DEBUG - Descadastramento:', {
            tipo: tipo,
            dataConclusao: dataConclusao,
            cutoff: cutoff
        });
        
        // Show all four indicators, but only the matching type should be active
        for (const field in fields) {
            const label = fields[field];
            let isOn = false;
            
            // Check if this field matches the tipo (handle both ORG_PAGADOR and ORGAO_PAGADOR for OP)
            const fieldMatches = (field === tipo) || 
                               (field === 'ORGAO_PAGADOR' && tipo === 'ORG_PAGADOR') ||
                               (field === 'ORG_PAGADOR' && tipo === 'ORGAO_PAGADOR');
            
            if (fieldMatches) {
                // This is the matching type, check DATA_CONCLUSAO
                const dateObj = this.parseDate(dataConclusao);
                console.log('DEBUG - Date parsing:', {
                    field: field,
                    tipo: tipo,
                    fieldMatches: fieldMatches,
                    dataConclusao: dataConclusao,
                    parsedDate: dateObj,
                    cutoff: cutoff,
                    isAfterCutoff: dateObj ? (dateObj > cutoff) : 'null'
                });
                
                isOn = dateObj !== null && dateObj > cutoff;
            }
            // All non-matching types remain gray (isOn = false)
            
            const color = isOn ? 'green' : 'gray';
            const status = isOn ? 'active' : 'inactive';
            
            const debugTitle = `Field: ${field}, Tipo: ${tipo}, Match: ${fieldMatches ? 'YES' : 'NO'}, Date: ${dataConclusao}, IsOn: ${isOn ? 'YES' : 'NO'}`;

            html += `<div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:${color};border-radius:4px;" data-field="${field}" data-status="${status}" title="${debugTitle}">${label}</div>`;
        }
    } else {
        // For other filters, use individual date fields
        for (const field in fields) {
            const label = fields[field];
            const raw = row[field] ? row[field].toString().trim() : '';
            const dateObj = this.parseDate(raw);
            const isOn = dateObj !== null && dateObj > cutoff;
            const color = isOn ? 'green' : 'gray';
            const status = isOn ? 'active' : 'inactive';

            html += `<div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:${color};border-radius:4px;" data-field="${field}" data-status="${status}">${label}</div>`;
        }
    }

    html += '</div>';
    return html;
},