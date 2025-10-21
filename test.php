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


<?php
@session_start();
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948';
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function generateFromSelection($solicitacoes) {
        if (empty($solicitacoes) || !is_array($solicitacoes)) {
            return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
        }
        
        $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
        $dados = $this->model->solicitacoesEncerramento($where, 999999, 0);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        return $this->generateTXT($dados);
    }
    
    public function getCNPJsForSelectionVerification($solicitacoes) {
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

