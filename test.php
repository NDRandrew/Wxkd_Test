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
    console.log('[1] gerarTXTSelection called');
    
    const solicitacoes = getSelectedSolicitacoes();
    console.log('[2] Selected solicitacoes:', solicitacoes);
    
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    showNotification('Iniciando verificação de CNPJs...', 'info');
    console.log('[3] About to call encerramento_massa.php');
    
    // Get CNPJs for verification
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        dataType: 'json',
        data: {
            acao: 'get_cnpjs_for_verification',
            solicitacoes: JSON.stringify(solicitacoes)
        },
        beforeSend: function() {
            console.log('[4] AJAX beforeSend - request is being sent');
        },
        success: async function(data) {
            console.log('[5] AJAX Success! Received data:', data);
            
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                console.error('[6] Data.success is false:', data.message);
                return;
            }
            
            const cnpjList = data.cnpjs;
            console.log('[7] CNPJ List received:', cnpjList);
            
            // Show progress modal
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            console.log('[8] Progress modal created');
            
            // Verify all CNPJs
            console.log('[9] Starting CNPJ verification loop');
            await verifyAllCNPJs(cnpjList, (processed, total, updated, errors) => {
                updateProgressModal(progressModal, processed, total, updated, errors);
            });
            
            console.log('[10] CNPJ verification complete');
            
            // Close progress modal
            setTimeout(() => progressModal.remove(), 2000);
            
            showNotification('Verificação concluída! Gerando TXT...', 'success');
            console.log('[11] Starting TXT generation');
            
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
                    console.log('[12] TXT generation successful');
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
                    console.error('[13] TXT generation error');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    showNotification('Erro ao gerar arquivo TXT: ' + error, 'error');
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('[ERROR] AJAX call failed!');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('XHR Status:', xhr.status);
            console.error('Response Text:', xhr.responseText);
            console.error('Ready State:', xhr.readyState);
            showNotification('Erro ao obter lista de CNPJs: ' + error, 'error');
        },
        complete: function() {
            console.log('[COMPLETE] AJAX call completed (success or error)');
        }
    });
    
    console.log('[14] AJAX call initiated, waiting for response...');
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

-------

<?php
@session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948';
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function generateFromSelection($solicitacoes) {
        try {
            if (empty($solicitacoes) || !is_array($solicitacoes)) {
                return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
            }
            
            $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
            $dados = $this->model->solicitacoesEncerramento($where, 999999, 0);
            
            if (empty($dados)) {
                return ['success' => false, 'message' => 'Dados não encontrados'];
            }
            
            return $this->generateTXT($dados);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    public function getCNPJsForSelectionVerification($solicitacoes) {
        try {
            if (empty($solicitacoes) || !is_array($solicitacoes)) {
                return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
            }
            
            $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
            $dados = $this->model->solicitacoesEncerramento($where, 999999, 0);
            
            if (empty($dados)) {
                return ['success' => false, 'message' => 'Dados não encontrados'];
            }
            
            $cnpjList = [];
            foreach ($dados as $row) {
                $cnpj = $this->formatCNPJ($row['CNPJ']);
                $dataContrato = is_object($row['DATA_CONTRATO']) 
                    ? $row['DATA_CONTRATO']->format('Y-m-d') 
                    : date('Y-m-d', strtotime($row['DATA_CONTRATO']));
                    
                $cnpjList[] = [
                    'cod_solicitacao' => $row['COD_SOLICITACAO'],
                    'cnpj' => $cnpj,
                    'data_contrato' => $dataContrato
                ];
            }
            
            return [
                'success' => true,
                'cnpjs' => $cnpjList
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao obter CNPJs: ' . $e->getMessage()];
        }
    }
    
    public function generateFromExcel($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX'];
        }
        
        if (empty($chaveLojas)) {
            return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
        }
        
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        $dados = $this->model->solicitacoesEncerramento($where, 9999, 0);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados para as Chaves Loja fornecidas'];
        }
        
        return $this->generateTXT($dados);
    }
    
    public function getCNPJsForExcelVerification($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado'];
        }
        
        if (empty($chaveLojas)) {
            return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada'];
        }
        
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        $dados = $this->model->solicitacoesEncerramento($where, 9999, 0);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        $cnpjList = [];
        foreach ($dados as $row) {
            $cnpj = $this->formatCNPJ($row['CNPJ']);
            $dataContrato = is_object($row['DATA_CONTRATO']) 
                ? $row['DATA_CONTRATO']->format('Y-m-d') 
                : date('Y-m-d', strtotime($row['DATA_CONTRATO']));
                
            $cnpjList[] = [
                'cod_solicitacao' => $row['COD_SOLICITACAO'],
                'cnpj' => $cnpj,
                'data_contrato' => $dataContrato
            ];
        }
        
        return [
            'success' => true,
            'cnpjs' => $cnpjList
        ];
    }
    
    private function formatCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return str_pad(substr($cnpj, 0, 8), 8, '0', STR_PAD_LEFT);
    }
    
    private function parseCSV($filePath) {
        $chaveLojas = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            fclose($handle);
        }
        
        return $chaveLojas;
    }
    
    private function parseExcel($filePath) {
        $phpSpreadsheetPath = '\\\\D4920S010\\D4920_2\\Secoes\\D4920S012\\Comum_S012\\Servidor_Portal_Expresso\\Server2Go\\htdocs\\Lib\\PhpSpreadsheet\\vendor\\autoload.php';
        
        if (!file_exists($phpSpreadsheetPath)) {
            return $this->parseCSV($filePath);
        }
        
        try {
            require_once $phpSpreadsheetPath;
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $chaveLojas = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue;
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            
            return $chaveLojas;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function generateTXT($dados) {
        $linhas = [];
        
        $linhas[] = $this->gerarHeader(count($dados));
        
        $sequencial = 2;
        foreach ($dados as $row) {
            $linhas[] = $this->gerarDetalhe($row, $sequencial);
            $sequencial++;
        }
        
        $linhas[] = $this->gerarTrailer(count($dados));
        
        $conteudo = implode("\r\n", $linhas);
        $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
        
        return [
            'success' => true,
            'conteudo' => $conteudo,
            'nomeArquivo' => $nomeArquivo,
            'totalRegistros' => count($dados)
        ];
    }
    
    private function gerarHeader($totalRegistros) {
        $tipo = '#A1';
        $codigoDocumento = '5021';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $dataGeracao = date('Ymd');
        $contato = str_pad('YGOR SANTINI', 30, ' ', STR_PAD_RIGHT);
        $ddd = '00011';
        $telefone = '0036849907';
        $livreFiller = str_pad(' ', 112, ' ', STR_PAD_RIGHT);
        $sequencial = '00001';
        
        $linha = $tipo . $codigoDocumento . $instituicao . $dataGeracao . $contato . $ddd . $telefone . $livreFiller . $sequencial;
        return substr($linha, 0, 250);
    }
    
    private function gerarDetalhe($row, $sequencial) {
        $tipo = 'D01';
        $metodo = '02';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $cnpj = str_pad($row['CNPJ'], 8, '0', STR_PAD_LEFT);
        $cnpjSubs = str_pad(' ', 8, ' ', STR_PAD_RIGHT);
        
        // Check if DATA_CONT_VERIFIED exists and use it, otherwise use DATA_CONTRATO
        $dataToUse = null;
        
        // Try to get DATA_CONT_VERIFIED from database
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
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '9';
        $quantidadeRegistros = str_pad($totalRegistros, 10, '0', STR_PAD_LEFT);
        
        $linha = $tipo . $quantidadeRegistros . str_repeat(' ', 250 - strlen($tipo . $quantidadeRegistros));
        
        return substr($linha, 0, 250);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $handler = new EncerramentoMassa();
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification') {
            $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
            $result = $handler->getCNPJsForSelectionVerification($solicitacoes);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification_excel') {
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
                $result = $handler->getCNPJsForExcelVerification($_FILES['excel_file']['tmp_name']);
                
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
            }
            exit;
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
            $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
            $result = $handler->generateFromSelection($solicitacoes);
            
            if ($result['success']) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                header('Content-Length: ' . strlen($result['conteudo']));
                echo $result['conteudo'];
            } else {
                header('Content-Type: application/json');
                echo json_encode($result);
            }
            exit;
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_excel') {
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
                $result = $handler->generateFromExcel($_FILES['excel_file']['tmp_name']);
                
                if ($result['success']) {
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                    header('Content-Length: ' . strlen($result['conteudo']));
                    echo $result['conteudo'];
                } else {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
            }
            exit;
        }
        
        // If no action matched
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . ($_POST['acao'] ?? 'nenhuma')]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro fatal: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        exit;
    }
}
?>

    
    public function generateFromSelection($solicitacoes) {
        if (empty($solicitacoes) || !is_array($solicitacoes)) {
            return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
        }
        
        $dados = $this->getDadosFromSolicitacoes($solicitacoes);
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        return $this->generateTXT($dados);
    }
    
    public function getCNPJsForSelectionVerification($solicitacoes) {
        if (empty($solicitacoes) || !is_array($solicitacoes)) {
            return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
        }
        
        $dados = $this->getDadosFromSolicitacoes($solicitacoes);
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        return [
            'success' => true,
            'cnpjs' => $this->getCNPJsForVerification($dados)
        ];
    }
    
    public function generateFromExcel($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\PhpSpreadsheet\vendor\autoload.php';
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $chaveLojas = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue;
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            
            if (empty($chaveLojas)) {
                return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
            }
            
            $dados = $this->getDadosFromChaveLojas($chaveLojas);
            $this->verifyCNPJsAndUpdate($dados);
            
            return $this->generateTXT($dados);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao processar Excel: ' . $e->getMessage()];
        }
    }
    
    private function getCNPJsForVerification($dados) {
        $cnpjList = [];
        foreach ($dados as $row) {
            $cnpj = $this->formatCNPJ($row['CNPJ']);
            $cnpjList[] = [
                'cod_solicitacao' => $row['COD_SOLICITACAO'],
                'cnpj' => $cnpj,
                'data_contrato' => is_object($row['DATA_CONTRATO']) 
                    ? $row['DATA_CONTRATO']->format('Y-m-d') 
                    : date('Y-m-d', strtotime($row['DATA_CONTRATO']))
            ];
        }
        return $cnpjList;
    }
    
    private function formatCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return str_pad(substr($cnpj, 0, 8), 8, '0', STR_PAD_LEFT);
    }
    
    private function getDadosFromSolicitacoes($solicitacoes) {
        $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
        return $this->model->solicitacoesEncerramentoWithVerified($where, 999999, 0);
    }
    
    private function getDadosFromChaveLojas($chaveLojas) {
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        return $this->model->solicitacoesEncerramentoWithVerified($where, 9999, 0);
    }
    
    private function generateTXT($dados) {
        $linhas = [];
        
        $linhas[] = $this->gerarHeader(count($dados));
        
        $sequencial = 2;
        foreach ($dados as $row) {
            $linhas[] = $this->gerarDetalhe($row, $sequencial);
            $sequencial++;
        }
        
        $linhas[] = $this->gerarTrailer(count($dados));
        
        $conteudo = implode("\r\n", $linhas);
        $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
        
        return [
            'success' => true,
            'conteudo' => $conteudo,
            'nomeArquivo' => $nomeArquivo,
            'totalRegistros' => count($dados)
        ];
    }
    
    private function gerarHeader($totalRegistros) {
        $tipo = '#A1';
        $codigoDocumento = '5021';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $dataGeracao = date('Ymd');
        $contato = str_pad('YGOR SANTINI', 30, ' ', STR_PAD_RIGHT);
        $ddd = '00011';
        $telefone = '0036849907';
        $livreFiller = str_pad(' ', 112, ' ', STR_PAD_RIGHT);
        $sequencial = '00001';
        
        $linha = $tipo . $codigoDocumento . $instituicao . $dataGeracao . $contato . $ddd . $telefone . $livreFiller . $sequencial;
        return substr($linha, 0, 250);
    }
    
    private function gerarDetalhe($row, $sequencial) {
        $tipo = 'D01';
        $metodo = '02';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $cnpj = str_pad($row['CNPJ'], 8, '0', STR_PAD_LEFT);
        $cnpjSubs = str_pad(' ', 8, ' ', STR_PAD_RIGHT);
        
        $dataToUse = !empty($row['DATA_CONT_VERIFIED']) 
            ? $row['DATA_CONT_VERIFIED'] 
            : $row['DATA_CONTRATO'];
        
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
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '9';
        $quantidadeRegistros = str_pad($totalRegistros, 10, '0', STR_PAD_LEFT);
        
        $linha = $tipo . $quantidadeRegistros . str_repeat(' ', 250 - strlen($tipo . $quantidadeRegistros));
        
        return substr($linha, 0, 250);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new EncerramentoMassa();
    
    // Get CNPJs for verification (selection)
    if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification') {
        $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
        $result = $handler->getCNPJsForSelectionVerification($solicitacoes);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Get CNPJs for verification (Excel)
    if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification_excel') {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $result = $handler->getCNPJsForExcelVerification($_FILES['excel_file']['tmp_name']);
            
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
        }
        exit;
    }
    
    // Generate TXT from selection
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
        $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
        $result = $handler->generateFromSelection($solicitacoes);
        
        if ($result['success']) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
            header('Content-Length: ' . strlen($result['conteudo']));
            echo $result['conteudo'];
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        exit;
    }
    
    // Generate TXT from Excel
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_excel') {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $result = $handler->generateFromExcel($_FILES['excel_file']['tmp_name']);
            
            if ($result['success']) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                header('Content-Length: ' . strlen($result['conteudo']));
                echo $result['conteudo'];
            } else {
                header('Content-Type: application/json');
                echo json_encode($result);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
        }
        exit;
    }
}
?>


-----------


# Debug: AJAX Callbacks Not Firing

## 🔍 Current Situation

**What works:**
- Console log BEFORE $.ajax() ✅

**What doesn't work:**
- Console logs INSIDE $.ajax() (success/error/complete) ❌

**This means:** The AJAX request is failing silently before even being sent, OR there's a JavaScript error preventing it.

## 🛠️ Updated Code Features

### JavaScript (analise_encerramento.js)
- ✅ Added 14 numbered console.log statements
- ✅ Added beforeSend, success, error, complete callbacks
- ✅ Added dataType: 'json' for proper parsing

### PHP (encerramento_massa.php)
- ✅ Added error_reporting and display_errors
- ✅ Added try-catch blocks
- ✅ Added proper Content-Type headers
- ✅ Added fallback for unrecognized actions

## 📋 Debugging Steps

### Step 1: Check Console Output

After clicking "Gerar TXT BACEN", you should see:

```
[1] gerarTXTSelection called
[2] Selected solicitacoes: [123]
[3] About to call encerramento_massa.php
[4] AJAX beforeSend - request is being sent
[14] AJAX call initiated, waiting for response...
```

**Then ONE of these:**
```
[COMPLETE] AJAX call completed (success or error)
[5] AJAX Success! Received data: {...}
```
OR
```
[COMPLETE] AJAX call completed (success or error)
[ERROR] AJAX call failed!
```

### Step 2: What Console Shows vs Issue

| Console Output | Problem | Solution |
|----------------|---------|----------|
| Only `[1]` and `[2]` | `getSelectedSolicitacoes()` failing | Check function exists |
| Up to `[3]` | AJAX not starting | Check jQuery loaded |
| No `[4]` | AJAX blocked before send | JavaScript error before $.ajax |
| `[4]` but no `[14]` | Syntax error in $.ajax block | Check browser console for errors |
| `[14]` but no `[COMPLETE]` | Request never completes | PHP crash/timeout |
| `[COMPLETE]` + `[ERROR]` | PHP error | Check response text |

### Step 3: Check for JavaScript Errors

**Open Console (F12) and look for RED errors:**

Common errors:
```
Uncaught ReferenceError: getSelectedSolicitacoes is not defined
Uncaught SyntaxError: Unexpected token
Uncaught TypeError: Cannot read property 'length' of undefined
```

If you see ANY red errors, that's your problem!

### Step 4: Test PHP Endpoint Directly

**Save this as `test_endpoint.html` in your project:**

(See artifact "test_php_endpoint")

**Open in browser:**
```
http://your-server/test_endpoint.html
```

**Enter a valid COD_SOLICITACAO and click "Test Get CNPJs"**

**Expected:**
```json
{
  "success": true,
  "cnpjs": [
    {
      "cod_solicitacao": 1234,
      "cnpj": "12345678",
      "data_contrato": "2021-01-18"
    }
  ]
}
```

**If you get an error, the PHP is broken!**

### Step 5: Check PHP Errors

**Look in PHP error log** or check response in Network tab:

```
Fatal error: Call to undefined function...
Parse error: syntax error...
Warning: require_once()...
```

### Step 6: Network Tab Analysis

**Open F12 → Network tab**

1. Filter by "encerramento_massa"
2. Click "Gerar TXT BACEN"
3. Check if request appears

**If NO request appears:**
- JavaScript error preventing AJAX call
- Check console for RED errors

**If request appears:**
- Click on request
- Check "Preview" or "Response" tab
- Look for PHP errors or JSON response

### Step 7: Simplify Test

**In browser console, run this:**

```javascript
// Test 1: Is jQuery loaded?
console.log('jQuery:', typeof $);
// Should print: jQuery: function

// Test 2: Does function exist?
console.log('gerarTXTSelection:', typeof gerarTXTSelection);
// Should print: gerarTXTSelection: function

// Test 3: Test AJAX directly
$.ajax({
    url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
    method: 'POST',
    dataType: 'json',
    data: {
        acao: 'get_cnpjs_for_verification',
        solicitacoes: JSON.stringify([1234])
    },
    beforeSend: () => console.log('TEST: beforeSend'),
    success: (data) => console.log('TEST: success', data),
    error: (xhr, status, error) => console.log('TEST: error', status, error, xhr.responseText),
    complete: () => console.log('TEST: complete')
});
```

**Expected output:**
```
TEST: beforeSend
TEST: complete
TEST: success {success: true, cnpjs: [...]}
```

**If this works but the app doesn't:**
- Issue is in the app's gerarTXTSelection function
- Check if function is being replaced/overwritten

**If this also fails:**
- Issue is with PHP endpoint
- Check PHP file exists at that path
- Check PHP syntax errors

## 🎯 Quick Fixes

### Fix 1: jQuery Not Loaded

**Add to page before your script:**
```html
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
```

### Fix 2: Function Doesn't Exist

**Check if file is loaded:**
```html
<script src="./js/encerramento/analise_encerramento/analise_encerramento.js"></script>
```

### Fix 3: Path Wrong

**Try absolute path:**
```javascript
url: window.location.origin + '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
```

### Fix 4: PHP Syntax Error

**Check PHP file for:**
- Missing semicolons
- Unclosed brackets
- Typos in function names

**Run PHP lint:**
```bash
php -l encerramento_massa.php
```

### Fix 5: Model Method Missing

**Verify this method exists in model:**
```php
public function solicitacoesEncerramento($where, $limit, $offset) {
    // Should exist in analise_encerramento_model.class.php
}
```

## 🆘 Still Not Working?

**Provide these exact details:**

1. **Full console output** (copy ALL text from console)
2. **Network tab screenshot** (show request to encerramento_massa.php)
3. **Result of Test 3** (the direct AJAX test above)
4. **PHP lint result:** `php -l encerramento_massa.php`
5. **File exists check:**
   ```
   Does this file exist?
   /teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php
   ```

## 🎁 Bonus: Skip Verification Test

**If you just want to test TXT generation without verification:**

```javascript
// In console, override function:
window.gerarTXTSelection = function() {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        alert('Select something first');
        return;
    }
    
    console.log('Generating TXT for:', solicitacoes);
    
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        data: {
            acao: 'gerar_txt_selection',
            solicitacoes: JSON.stringify(solicitacoes)
        },
        xhrFields: { responseType: 'blob' },
        success: function(blob) {
            console.log('SUCCESS!');
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'TEST.txt';
            a.click();
        },
        error: function(xhr, status, error) {
            console.error('ERROR:', error, xhr.responseText);
        }
    });
};

// Then click button
```

This skips CNPJ verification and goes straight to TXT generation.