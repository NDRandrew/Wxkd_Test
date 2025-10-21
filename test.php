<?php
@session_start();
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948';
    private $apiUrl = 'https://10.222.217.237/portal_tds/bacen_verify';
    private $apiUser = 'your_user';
    private $apiPwd = 'your_pwd';
    private $apiToken = 'your_token';
    
    public function __construct() {
        $this->model = new Analise();
    }
    
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

<?php
// Add this to ajax_encerramento.php (after existing handlers, before the main try-catch)

// Verify single CNPJ with BACEN API
if (isset($_POST['acao']) && $_POST['acao'] === 'verify_cnpj') {
    ob_start();
    try {
        $cnpj = isset($_POST['cnpj']) ? $_POST['cnpj'] : '';
        $apiUrl = isset($_POST['api_url']) ? $_POST['api_url'] : '';
        $user = isset($_POST['user']) ? $_POST['user'] : '';
        $pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        
        if (empty($cnpj) || empty($apiUrl)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }
        
        $ch = curl_init($apiUrl);
        
        $payload = json_encode([
            'user' => $user,
            'pwd' => $pwd,
            'token' => $token,
            'cnpj' => $cnpj
        ]);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) || $httpCode !== 200) {
            $error = curl_error($ch);
            curl_close($ch);
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Erro na API: ' . $error, 'result' => 'Error']);
            exit;
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'result' => $data['result'] ?? 'Error',
            'cnpj' => $cnpj
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Update DATA_CONT_VERIFIED in database
if (isset($_POST['acao']) && $_POST['acao'] === 'update_data_cont_verified') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $codSolicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $dataContVerified = isset($_POST['data_cont_verified']) ? $_POST['data_cont_verified'] : '';
        
        if ($codSolicitacao <= 0 || empty($dataContVerified)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Parâmetros inválidos']);
            exit;
        }
        
        $model = new Analise();
        $result = $model->updateDataContVerified($codSolicitacao, $dataContVerified);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false,
            'message' => $result ? 'Atualizado com sucesso' : 'Erro ao atualizar'
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
?>


----------


// Add these functions to analise_encerramento.js

// API Configuration
const BACEN_API_CONFIG = {
    url: 'https://10.222.217.237/portal_tds/bacen_verify',
    user: 'your_user',
    pwd: 'your_pwd',
    token: 'your_token'
};

// Parse date from response (DD/MM/YYYY to YYYY-MM-DD)
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
    return new Promise((resolve) => {
        const formData = new FormData();
        formData.append('acao', 'verify_cnpj');
        formData.append('cnpj', cnpjData.cnpj);
        formData.append('api_url', BACEN_API_CONFIG.url);
        formData.append('user', BACEN_API_CONFIG.user);
        formData.append('pwd', BACEN_API_CONFIG.pwd);
        formData.append('token', BACEN_API_CONFIG.token);
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const verifiedDate = parseDateFromResponse(data.result);
                
                if (verifiedDate && verifiedDate !== cnpjData.data_contrato) {
                    // Update database with verified date
                    return updateDataContVerified(cnpjData.cod_solicitacao, verifiedDate)
                        .then(() => resolve({ success: true, updated: true }));
                }
                resolve({ success: true, updated: false });
            } else {
                resolve({ success: false, error: data.message });
            }
        })
        .catch(error => {
            console.error('Verification error:', error);
            resolve({ success: false, error: error.message });
        });
    });
}

// Update DATA_CONT_VERIFIED in database
async function updateDataContVerified(codSolicitacao, dataContVerified) {
    return new Promise((resolve) => {
        const formData = new FormData();
        formData.append('acao', 'update_data_cont_verified');
        formData.append('cod_solicitacao', codSolicitacao);
        formData.append('data_cont_verified', dataContVerified);
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => resolve(data))
        .catch(error => {
            console.error('Update error:', error);
            resolve({ success: false });
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
    const formData = new FormData();
    formData.append('acao', 'get_cnpjs_for_verification');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    try {
        const response = await fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
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
        txtFormData.append('acao', 'gerar_txt_selection');
        txtFormData.append('solicitacoes', JSON.stringify(solicitacoes));

        const txtResponse = await fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
            method: 'POST',
            body: txtFormData
        });
        
        if (!txtResponse.ok) {
            const errorData = await txtResponse.json();
            throw new Error(errorData.message || 'Erro na requisição');
        }
        
        const blob = await txtResponse.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        
        showNotification('Arquivo TXT gerado com sucesso!', 'success');
        
    } catch (error) {
        console.error('TXT generation error:', error);
        showNotification('Erro ao gerar arquivo TXT: ' + error.message, 'error');
    }
};

// Replace the existing uploadExcelAndGenerateTXT function
window.uploadExcelAndGenerateTXT = async function() {
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
        
        try {
            const response = await fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
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
            
            const txtResponse = await fetch('/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php', {
                method: 'POST',
                body: txtFormData
            });
            
            if (!txtResponse.ok) {
                const errorData = await txtResponse.json();
                throw new Error(errorData.message || 'Erro na requisição');
            }
            
            const blob = await txtResponse.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            
            showNotification('Arquivo TXT gerado com sucesso!', 'success');
            
        } catch (error) {
            console.error('Excel processing error:', error);
            showNotification('Erro ao processar arquivo: ' + error.message, 'error');
        }
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

# Quick Implementation Checklist

## ✅ Step-by-Step Implementation

### 1️⃣ Database (Run SQL Script)
```sql
ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL
ADD DATA_CONT_VERIFIED DATE NULL;

CREATE INDEX IDX_ENCERRAMENTO_DATA_CONT_VERIFIED 
ON MESU..ENCERRAMENTO_TB_PORTAL(DATA_CONT_VERIFIED);
```
**Status:** ⬜ Not Done

---

### 2️⃣ Model File: `analise_encerramento_model.class.php`

**Add these 3 methods anywhere in the class (before the closing `}`):**

```php
public function updateDataContVerified($codSolicitacao, $dataContVerified) {
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
              SET DATA_CONT_VERIFIED = '" . $dataContVerified . "' 
              WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    return $this->sql->update($query);
}

public function getDataContVerified($codSolicitacao) {
    $query = "SELECT DATA_CONT_VERIFIED 
              FROM MESU..ENCERRAMENTO_TB_PORTAL 
              WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    $result = $this->sql->select($query);
    return $result ? $result[0]['DATA_CONT_VERIFIED'] : null;
}

public function solicitacoesEncerramentoWithVerified($where, $limit = 25, $offset = 0) {
    $query = "
        ;WITH Q AS (
            SELECT
                A.COD_SOLICITACAO, A.COD_AG, A.CHAVE_LOJA,
                F.NOME_LOJA, F.CNPJ, N.DATA_CONTRATO,
                ES.DATA_CONT_VERIFIED,
                ROW_NUMBER() OVER (
                    PARTITION BY A.COD_SOLICITACAO
                    ORDER BY A.COD_SOLICITACAO DESC
                ) AS rn
            FROM TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK)
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
                    ON A.CHAVE_LOJA = F.CHAVE_LOJA 
                JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = G.COD_SOLICITACAO
                LEFT JOIN MESU..TB_EMPRESA_VERSAO_CONTRATO2 N
                    ON F.COD_EMPRESA = N.KEY_EMPRESA
                LEFT JOIN MESU..ENCERRAMENTO_TB_PORTAL ES WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = ES.COD_SOLICITACAO
            WHERE F.BE_INAUGURADO = 1 " . $where . "
        )
        SELECT * FROM Q WHERE rn = 1
        ORDER BY COD_SOLICITACAO DESC
        OFFSET " . (int)$offset . " ROWS
        FETCH NEXT " . (int)$limit . " ROWS ONLY;
    ";
    return $this->sql->select($query);
}
```
**Status:** ⬜ Not Done

---

### 3️⃣ Replace: `encerramento_massa.php`
**Action:** Delete current file and replace with artifact "encerramento_massa_updated"

**Status:** ⬜ Not Done

---

### 4️⃣ AJAX Handler: `ajax_encerramento.php`

**Add BEFORE the main `try-catch` block at bottom (around line 290):**

Copy content from artifact "ajax_cnpj_handler"

**Status:** ⬜ Not Done

---

### 5️⃣ JavaScript: `analise_encerramento.js`

**Add at the END of the file:**

Copy content from artifact "javascript_cnpj_verification"

**Then UPDATE the API credentials:**
```javascript
const BACEN_API_CONFIG = {
    url: 'https://10.222.217.237/portal_tds/bacen_verify',
    user: 'YOUR_ACTUAL_USERNAME',  // ← Change this
    pwd: 'YOUR_ACTUAL_PASSWORD',   // ← Change this
    token: 'YOUR_ACTUAL_TOKEN'     // ← Change this
};
```

**Status:** ⬜ Not Done

---

### 6️⃣ Flask API (Python)

**Save as:** `bacen_api.py`

Copy content from artifact "flask_api_endpoint"

**Update Python script path on line 22:**
```python
python_script = r"C:\actual\path\to\bacen_verification.py"
```

**Run:**
```bash
python bacen_api.py
```

**Status:** ⬜ Not Done

---

### 7️⃣ Selenium Script (Python)

**Save as:** `bacen_verification.py`

Copy content from artifact "bacen_verification_script"

**Test:**
```bash
python bacen_verification.py 12345678
```

**Status:** ⬜ Not Done

---

## 🧪 Testing Checklist

### Test 1: Single CNPJ Verification
1. Open the page
2. Select **ONE** record
3. Click "Gerar TXT BACEN"
4. **Expected:**
   - Progress modal appears
   - Shows "1/1 CNPJs processados"
   - Modal disappears
   - TXT downloads
5. **Check Database:**
   ```sql
   SELECT COD_SOLICITACAO, DATA_CONT_VERIFIED 
   FROM MESU..ENCERRAMENTO_TB_PORTAL 
   WHERE DATA_CONT_VERIFIED IS NOT NULL
   ```

**Status:** ⬜ Not Tested

---

### Test 2: Multiple CNPJs
1. Select **5** records
2. Click "Gerar TXT BACEN"
3. **Expected:**
   - Progress shows 1/5, 2/5, 3/5, 4/5, 5/5
   - Shows update count
   - TXT downloads

**Status:** ⬜ Not Tested

---

### Test 3: Excel Import
1. Create CSV with header "CHAVE_LOJA"
2. Add 3 CHAVE_LOJA values
3. Click "Importar Excel e Gerar TXT"
4. Upload CSV
5. **Expected:**
   - Progress shows 3/3
   - TXT downloads

**Status:** ⬜ Not Tested

---

### Test 4: Error Handling
1. Stop Flask API
2. Select 1 record
3. Click "Gerar TXT"
4. **Expected:**
   - Progress shows errors
   - TXT still generates
   - Uses original DATA_CONTRATO

**Status:** ⬜ Not Tested

---

## 🔧 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| PhpSpreadsheet not found | Use CSV files or install: `composer require phpoffice/phpspreadsheet` |
| AJAX 404 error | Check AJAX_URL constant in JS matches actual path |
| Flask not responding | Verify: `curl http://localhost:5000` |
| Progress modal doesn't show | Check browser console for JS errors |
| Database not updating | Check MODEL methods were added correctly |
| API returns "Error" | Check Flask logs, verify Python script works standalone |
| Selenium fails | Update Edge WebDriver, check BACEN site structure |

---

## 📋 File Locations Reference

```
tabler_portalexpresso_paginaEncerramento/
├── model/
│   └── encerramento/
│       └── analise_encerramento_model.class.php  ← ADD 3 methods
├── control/
│   └── encerramento/
│       ├── encerramento_massa.php                ← REPLACE file
│       └── roteamento/
│           └── ajax_encerramento.php             ← ADD 2 handlers
└── encerramento/
    └── analise_encerramento/
        └── analise_encerramento.js               ← ADD functions

External (Python):
C:\WebDrivers\
├── bacen_api.py           ← New file
└── bacen_verification.py  ← New file
```

---

## 🎯 Quick Verification Commands

**Check if column exists:**
```sql
SELECT TOP 1 DATA_CONT_VERIFIED 
FROM MESU..ENCERRAMENTO_TB_PORTAL
```

**Test Flask API:**
```bash
curl -X POST http://localhost:5000/portal_tds/bacen_verify \
  -H "Content-Type: application/json" \
  -d '{"user":"test","pwd":"test","token":"test","cnpj":"12345678"}'
```

**Test Python directly:**
```bash
python bacen_verification.py 12345678
```

**View database updates:**
```sql
SELECT COD_SOLICITACAO, CNPJ, DATA_CONTRATO, DATA_CONT_VERIFIED
FROM MESU..ENCERRAMENTO_TB_PORTAL EP
JOIN TB_ACIONAMENTO_FIN_SOLICITACOES A ON EP.COD_SOLICITACAO = A.COD_SOLICITACAO
JOIN DATALAKE..DL_BRADESCO_EXPRESSO F ON A.CHAVE_LOJA = F.CHAVE_LOJA
WHERE DATA_CONT_VERIFIED IS NOT NULL
ORDER BY EP.DATA_CRIACAO DESC
```

---

## 💡 Pro Tips

1. **Test with one record first** before bulk processing
2. **Check browser console** (F12) for AJAX errors
3. **Monitor Flask logs** while testing
4. **Keep Flask running** during development
5. **Use CSV files** if PhpSpreadsheet issues persist
6. **Add delays** if API rate-limited (increase from 500ms)
7. **Clear browser cache** after JS changes
8. **Check file permissions** for PhpSpreadsheet

---

## ✅ Final Checklist

- [ ] SQL script executed successfully
- [ ] Model methods added and no syntax errors
- [ ] encerramento_massa.php replaced
- [ ] AJAX handlers added to ajax_encerramento.php
- [ ] JavaScript functions added to analise_encerramento.js
- [ ] API credentials updated in JavaScript
- [ ] Flask API running
- [ ] Python script tested standalone
- [ ] Single CNPJ test passed
- [ ] Multiple CNPJ test passed
- [ ] Excel import test passed
- [ ] Error handling test passed

**When all checked:** 🎉 **Implementation Complete!**