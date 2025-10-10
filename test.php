// TXT Generation Functions
// Add these functions to your analise_encerramento.js file

window.gerarTXTSelection = function() {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'gerar_txt_selection');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    showNotification('Gerando arquivo TXT...', 'info');

    fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Erro na requisição');
            });
        }
        return response.blob();
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        showNotification('Arquivo TXT gerado com sucesso!', 'success');
    })
    .catch(error => {
        console.error('TXT generation error:', error);
        showNotification('Erro ao gerar arquivo TXT: ' + error.message, 'error');
    });
};

window.uploadExcelAndGenerateTXT = function() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.xlsx,.xls,.csv';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('acao', 'gerar_txt_excel');
        formData.append('excel_file', file);
        
        showNotification('Processando arquivo Excel...', 'info');
        
        fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Erro na requisição');
                });
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            showNotification('Arquivo TXT gerado com sucesso!', 'success');
        })
        .catch(error => {
            console.error('Excel processing error:', error);
            showNotification('Erro ao processar arquivo Excel: ' + error.message, 'error');
        });
    };
    
    input.click();
};