formatDataSolicitacao: function(row) {
    const dataSolicitacaoRaw = row.DATA_SOLICITACAO ? row.DATA_SOLICITACAO.toString().trim() : '';
    
    if (dataSolicitacaoRaw) {
        // Parse and format DATA_SOLICITACAO as DD/MM/YYYY
        const dateObj = this.parseDate(dataSolicitacaoRaw);
        if (dateObj && !isNaN(dateObj.getTime())) {
            const day = ('0' + dateObj.getDate()).slice(-2);
            const month = ('0' + (dateObj.getMonth() + 1)).slice(-2);
            const year = dateObj.getFullYear();
            return `${day}/${month}/${year}`;
        }
        // Fallback to formatDateKeepTime for backward compatibility
        return this.formatDateKeepTime(dataSolicitacaoRaw);
    } else {
        if (FilterModule.currentFilter === 'descadastramento') {
            const dataConclusao = row.DATA_CONCLUSAO ? row.DATA_CONCLUSAO.toString().trim() : '';
            if (dataConclusao) {
                // Parse and format DATA_CONCLUSAO as DD/MM/YYYY
                const dateObj = this.parseDate(dataConclusao);
                if (dateObj && !isNaN(dateObj.getTime())) {
                    const day = ('0' + dateObj.getDate()).slice(-2);
                    const month = ('0' + (dateObj.getMonth() + 1)).slice(-2);
                    const year = dateObj.getFullYear();
                    return `${day}/${month}/${year}`;
                }
                // Fallback to formatDateKeepTime for backward compatibility
                return this.formatDateKeepTime(dataConclusao);
            }
        } else {
            return this.generateDateFieldsHTML(row);
        }
        return '—';
    }
},