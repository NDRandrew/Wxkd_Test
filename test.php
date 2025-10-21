// Add these functions to analise_encerramento.js

// API Configuration
const BACEN_API_CONFIG = {
    url: 'https://10.222.217.237/portalexpresso/view/verifica_bacen',
    user: 'MDUyMzc3OTcyLlNDSE5FVFpMRVI=',
    pwd: 'YnJhZDEyMzQ='
};

// Parse date from response (DD/MM/YYYY from "DD/MM/YYYY - Verificado")
function parseDateFromResponse(response) {
    if (!response || response === 'Error') {
        return null;
    }
    
    const match = response.match(/(\d{2})\/(\d{2})\/(\d{4})/);
    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    return null;
}

// Verify single CNPJ via AJAX
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
                    const verifiedDate = parseDateFromResponse(data.result);
                    console.log('Verified date:', verifiedDate, 'Original date:', cnpjData.data_contrato);
                    
                    if (verifiedDate && verifiedDate !== cnpjData.data_contrato) {
                        console.log('Dates differ! Updating database...');
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
                        console.log('Dates match or no verified date, skipping update');
                        resolve({ success: true, updated: false });
                    }
                } else {
                    console.error('API returned success:false');
                    resolve({ success: false, error: data.message || 'Erro desconhecido' });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error for CNPJ', cnpjData.cnpj);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                console.error('Status Code:', xhr.status);
                resolve({ success: false, error: 'Erro na API: ' + error });
            }
        });
    });
}

// Update DATA_CONT_VERIFIED in database
async function updateDataContVerified(codSolicitacao, dataContVerified) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: {
                acao: 'update_data_cont_verified',
                cod_solicitacao: codSolicitacao,
                data_cont_verified: dataContVerified
            },
            success: function(data) {
                resolve(data);
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                reject(error);
            }
        });
    });
}

// Verify all CNPJs sequentially
async function verifyAllCNPJs(cnpjList, progressCallback) {
    const total = cnpjList.length;
    let processed = 0;
    let updated = 0;
    let errors = 0;
    
    for (const cnpjData of cnpjList) {
        const result = await verifySingleCNPJ(cnpjData);
        processed++;
        
        if (result.success && result.updated) {
            updated++;
        } else if (!result.success) {
            errors++;
        }
        
        if (progressCallback) {
            progressCallback(processed, total, updated, errors);
        }
        
        // Small delay between requests
        await new Promise(resolve => setTimeout(resolve, 500));
    }
    
    return { total, processed, updated, errors };
}

// Replace the existing gerarTXTSelection function
window.gerarTXTSelection = async function() {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    showNotification('Iniciando verificação de CNPJs...', 'info');
    
    // Get CNPJs for verification
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        data: {
            acao: 'get_cnpjs_for_verification',
            solicitacoes: JSON.stringify(solicitacoes)
        },
        success: async function(data) {
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                return;
            }
            
            const cnpjList = data.cnpjs;
            
            // Show progress modal
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            
            // Verify all CNPJs
            await verifyAllCNPJs(cnpjList, (processed, total, updated, errors) => {
                updateProgressModal(progressModal, processed, total, updated, errors);
            });
            
            // Close progress modal
            setTimeout(() => progressModal.remove(), 2000);
            
            showNotification('Verificação concluída! Gerando TXT...', 'success');
            
            // Now generate TXT
            $.ajax({
                url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
                method: 'POST',
                data: {
                    acao: 'gerar_txt_selection',
                    solicitacoes: JSON.stringify(solicitacoes)
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    window.URL.revokeObjectURL(url);
                    showNotification('Arquivo TXT gerado com sucesso!', 'success');
                },
                error: function(xhr, status, error) {
                    console.error('TXT generation error:', error);
                    showNotification('Erro ao gerar arquivo TXT: ' + error, 'error');
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('Get CNPJs error:', error);
            showNotification('Erro ao obter lista de CNPJs: ' + error, 'error');
        }
    });
};

// Replace the existing uploadExcelAndGenerateTXT function
window.uploadExcelAndGenerateTXT = function() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.xlsx,.xls,.csv';
    
    input.onchange = async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        showNotification('Processando arquivo...', 'info');
        
        // Get CNPJs for verification
        const formData = new FormData();
        formData.append('acao', 'get_cnpjs_for_verification_excel');
        formData.append('excel_file', file);
        
        $.ajax({
            url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: async function(data) {
                if (!data.success) {
                    showNotification('Erro: ' + data.message, 'error');
                    return;
                }
                
                const cnpjList = data.cnpjs;
                
                // Show progress modal
                const progressModal = createProgressModal();
                document.body.appendChild(progressModal);
                
                // Verify all CNPJs
                await verifyAllCNPJs(cnpjList, (processed, total, updated, errors) => {
                    updateProgressModal(progressModal, processed, total, updated, errors);
                });
                
                // Close progress modal
                setTimeout(() => progressModal.remove(), 2000);
                
                showNotification('Verificação concluída! Gerando TXT...', 'success');
                
                // Now generate TXT
                const txtFormData = new FormData();
                txtFormData.append('acao', 'gerar_txt_excel');
                txtFormData.append('excel_file', file);
                
                $.ajax({
                    url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
                    method: 'POST',
                    data: txtFormData,
                    processData: false,
                    contentType: false,
                    xhrFields: {
                        responseType: 'blob'
                    },
                    success: function(blob) {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        showNotification('Arquivo TXT gerado com sucesso!', 'success');
                    },
                    error: function(xhr, status, error) {
                        console.error('Excel processing error:', error);
                        showNotification('Erro ao processar arquivo: ' + error, 'error');
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Get CNPJs from Excel error:', error);
                showNotification('Erro ao processar Excel: ' + error, 'error');
            }
        });
    };
    
    input.click();
};

// Create progress modal
function createProgressModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
    
    modal.innerHTML = `
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verificando CNPJs</h5>
                </div>
                <div class="modal-body">
                    <div class="progress mb-2">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="text-center">
                        <p class="mb-1"><span class="progress-text">0/0</span> CNPJs processados</p>
                        <small class="text-muted">
                            <span class="updated-count">0</span> atualizados | 
                            <span class="error-count">0</span> erros
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return modal;
}

// Update progress modal
function updateProgressModal(modal, processed, total, updated, errors) {
    const progressBar = modal.querySelector('.progress-bar');
    const progressText = modal.querySelector('.progress-text');
    const updatedCount = modal.querySelector('.updated-count');
    const errorCount = modal.querySelector('.error-count');
    
    const percentage = (processed / total) * 100;
    
    progressBar.style.width = percentage + '%';
    progressText.textContent = `${processed}/${total}`;
    updatedCount.textContent = updated;
    errorCount.textContent = errors;
}

---------

