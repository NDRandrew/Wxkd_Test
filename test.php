// CORRECTED JavaScript functions for analise_encerramento.js

// Replace parseDateFromResponse function
function parseDateFromResponse(apiResponseData) {
    if (!apiResponseData || !apiResponseData.success) {
        return null;
    }
    
    // Use the 'dates' array from API response
    if (apiResponseData.dates && Array.isArray(apiResponseData.dates) && apiResponseData.dates.length > 0) {
        // Convert DD/MM/YYYY to YYYY-MM-DD for each date
        const dates = apiResponseData.dates.map(dateStr => {
            const match = dateStr.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            return match ? `${match[3]}-${match[2]}-${match[1]}` : null;
        }).filter(d => d !== null);
        
        return dates.length > 0 ? dates.join(',') : null;
    }
    
    // Fallback: try to parse from result string (backward compatibility)
    if (apiResponseData.result && typeof apiResponseData.result === 'string') {
        const match = apiResponseData.result.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        if (match) {
            return `${match[3]}-${match[2]}-${match[1]}`;
        }
    }
    
    return null;
}

// Replace verifySingleCNPJ function
async function verifySingleCNPJ(cnpjData) {
    console.log('Verifying CNPJ:', cnpjData.cnpj);
    
    return new Promise((resolve) => {
        $.ajax({
            url: BACEN_API_CONFIG.url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                user: BACEN_API_CONFIG.user,
                pwd: BACEN_API_CONFIG.pwd,
                cnpj: cnpjData.cnpj
            }),
            success: function(data) {
                console.log('API Success for CNPJ', cnpjData.cnpj, ':', data);
                
                if (data.success) {
                    // Pass entire response object to parser
                    const verifiedDate = parseDateFromResponse(data);
                    console.log('Verified date(s):', verifiedDate, 'Original date:', cnpjData.data_contrato);
                    
                    if (verifiedDate) {
                        console.log('Updating database with date(s)...');
                        updateDataContVerified(cnpjData.cod_solicitacao, verifiedDate)
                            .then(() => {
                                console.log('Database updated successfully');
                                resolve({ success: true, updated: true });
                            })
                            .catch((err) => {
                                console.error('Database update failed:', err);
                                resolve({ success: true, updated: false });
                            });
                    } else {
                        console.log('No verified date, skipping update');
                        resolve({ success: true, updated: false });
                    }
                } else {
                    console.error('API returned success:false');
                    resolve({ success: false, error: data.result || 'Erro desconhecido' });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error for CNPJ', cnpjData.cnpj);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                resolve({ success: false, error: 'Erro na API: ' + error });
            }
        });
    });
}

// uploadExcelAndGenerateTXT remains the same as before
window.uploadExcelAndGenerateTXT = function() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.xlsx,.xls,.csv';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        showNotification('Processando arquivo...', 'info');
        
        const formData = new FormData();
        formData.append('acao', 'get_cnpjs_for_verification_excel');
        formData.append('excel_file', file);
        
        fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                return;
            }
            
            const cnpjList = data.cnpjs;
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            
            verifyAllCNPJs(cnpjList, (processed, total, updated, errors) => {
                updateProgressModal(progressModal, processed, total, updated, errors);
            }).then(() => {
                setTimeout(() => progressModal.remove(), 2000);
                showNotification('Verificação concluída! Gerando TXT...', 'success');
                
                const txtFormData = new FormData();
                txtFormData.append('acao', 'gerar_txt_excel');
                txtFormData.append('excel_file', file);
                
                fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
                    method: 'POST',
                    body: txtFormData
                })
                .then(response => response.blob())
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
                    showNotification('Erro ao processar arquivo: ' + error, 'error');
                });
            });
        })
        .catch(error => {
            console.error('Get CNPJs from Excel error:', error);
            showNotification('Erro ao processar Excel: ' + error, 'error');
        });
    };
    
    input.click();
};