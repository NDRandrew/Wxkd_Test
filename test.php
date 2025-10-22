// Replace the uploadExcelAndGenerateTXT function (around line 979)
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

// Update parseDateFromResponse to handle arrays (around line 895)
function parseDateFromResponse(response) {
    if (!response || response === 'Error') {
        return null;
    }
    
    // Handle array of dates: ["07/07/2005", "26/10/2006"]
    if (Array.isArray(response)) {
        const dates = response.map(dateStr => {
            const match = dateStr.match(/(\d{2})\/(\d{2})\/(\d{4})/);
            return match ? `${match[3]}-${match[2]}-${match[1]}` : null;
        }).filter(d => d !== null);
        
        return dates.length > 0 ? dates.join(',') : null;
    }
    
    // Handle single date string
    const match = response.match(/(\d{2})\/(\d{2})\/(\d{4})/);
    if (match) {
        return `${match[3]}-${match[2]}-${match[1]}`;
    }
    
    return null;
}

// Update verifySingleCNPJ to handle multiple dates (around line 900)
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
                    
                    if (verifiedDate) {
                        console.log('Updating database with dates...');
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
                    resolve({ success: false, error: data.message || 'Erro desconhecido' });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error for CNPJ', cnpjData.cnpj);
                resolve({ success: false, error: 'Erro na API: ' + error });
            }
        });
    });
}

---------


<?php
// Replace the gerarDetalhe method in EncerramentoMassa class

private function gerarDetalhe($row, $sequencial) {
    $tipo = 'D01';
    $metodo = '02';
    $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
    $cnpj = str_pad($row['CNPJ'], 8, '0', STR_PAD_LEFT);
    $cnpjSubs = str_pad(' ', 8, ' ', STR_PAD_RIGHT);
    
    // Get verified dates (can be multiple, comma-separated)
    $dataToUse = null;
    
    if (isset($row['COD_SOLICITACAO'])) {
        $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
        if ($verifiedDate) {
            $dataToUse = $verifiedDate;
        }
    }
    
    // If no verified date, use DATA_CONTRATO
    if (!$dataToUse) {
        $dataToUse = $row['DATA_CONTRATO'];
    }
    
    // Handle data formatting
    if (is_object($dataToUse)) {
        $dataContrato = $dataToUse->format('Ymd');
    } else {
        $dataContrato = date('Ymd', strtotime($dataToUse));
    }
    
    $dataEncerramento = date('Ymd');
    $linhaFiller = str_pad(' ', 135, ' ', STR_PAD_LEFT);
    $sequencialStr = str_pad($sequencial, 5, '0', STR_PAD_LEFT);
    
    $linha = $tipo . $metodo . $instituicao . $cnpj . $cnpjSubs . $dataContrato . $dataEncerramento . $linhaFiller . $sequencialStr;
    
    return substr($linha, 0, 250);
}

// Replace the generateTXT method to handle multiple dates
private function generateTXT($dados) {
    $linhas = [];
    $totalLinhas = 0;
    
    // Count total lines (including multiple contracts per solicitacao)
    foreach ($dados as $row) {
        $verifiedDate = null;
        if (isset($row['COD_SOLICITACAO'])) {
            $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
        }
        
        if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
            // Multiple dates
            $dates = explode(',', $verifiedDate);
            $totalLinhas += count($dates);
        } else {
            $totalLinhas++;
        }
    }
    
    $linhas[] = $this->gerarHeader($totalLinhas);
    
    $sequencial = 2;
    foreach ($dados as $row) {
        $verifiedDate = null;
        if (isset($row['COD_SOLICITACAO'])) {
            $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
        }
        
        if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
            // Multiple dates - create one line per date
            $dates = explode(',', $verifiedDate);
            foreach ($dates as $date) {
                $rowCopy = $row;
                $rowCopy['DATA_CONTRATO'] = trim($date);
                $linhas[] = $this->gerarDetalhe($rowCopy, $sequencial);
                $sequencial++;
            }
        } else {
            // Single date
            $linhas[] = $this->gerarDetalhe($row, $sequencial);
            $sequencial++;
        }
    }
    
    $linhas[] = $this->gerarTrailer($totalLinhas);
    
    $conteudo = implode("\r\n", $linhas);
    $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
    
    return [
        'success' => true,
        'conteudo' => $conteudo,
        'nomeArquivo' => $nomeArquivo,
        'totalRegistros' => $totalLinhas
    ];
}
?>

----------

<?php
// Replace these methods in the Analise class

public function updateDataContVerified($codSolicitacao, $dataContVerified) {
    // dataContVerified can now be a comma-separated string of dates
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
            SET DATA_CONT_VERIFIED = '" . addslashes($dataContVerified) . "' 
            WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    return $this->sql->update($query);
}

public function getDataContVerified($codSolicitacao) {
    $query = "SELECT DATA_CONT_VERIFIED 
            FROM MESU..ENCERRAMENTO_TB_PORTAL 
            WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    $result = $this->sql->select($query);
    // Returns VARCHAR (single date or comma-separated dates)
    return $result ? $result[0]['DATA_CONT_VERIFIED'] : null;
}
?>

