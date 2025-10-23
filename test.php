<?php
@session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948';
    private $errors = []; // Track errors during processing
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function getErrors() {
        return $this->errors;
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
            
            $result = $this->generateTXT($dados);
            
            // Add errors to result
            $result['errors'] = $this->errors;
            $result['has_errors'] = !empty($this->errors);
            
            return $result;
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
                    'data_contrato' => $dataContrato,
                    'chave_loja' => $row['CHAVE_LOJA'],
                    'nome_loja' => $row['NOME_LOJA']
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
    
    public function generateFromExcel($filePath, $originalFilename = null) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        if ($originalFilename) {
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        } else {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX. Extensão detectada: ' . $extension];
        }
        
        if (empty($chaveLojas)) {
            return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
        }
        
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        $dados = $this->model->solicitacoesEncerramento($where, 9999, 0);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados para as Chaves Loja fornecidas'];
        }
        
        $result = $this->generateTXT($dados);
        
        // Add errors to result
        $result['errors'] = $this->errors;
        $result['has_errors'] = !empty($this->errors);
        
        return $result;
    }
    
    public function getCNPJsForExcelVerification($filePath, $originalFilename = null) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        if ($originalFilename) {
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        } else {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado. Extensão detectada: ' . $extension];
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
                'data_contrato' => $dataContrato,
                'chave_loja' => $row['CHAVE_LOJA'],
                'nome_loja' => $row['NOME_LOJA']
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
        $totalLinhas = 0;
        
        // Count total lines (including multiple contracts per solicitacao)
        foreach ($dados as $row) {
            $verifiedDate = null;
            if (isset($row['COD_SOLICITACAO'])) {
                $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
            }
            
            if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
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
                    $rowCopy['DATA_CONTRATO_OVERRIDE'] = trim($date);
                    
                    $lineResult = $this->gerarDetalhe($rowCopy, $sequencial);
                    $linhas[] = $lineResult['line'];
                    
                    // Track if there was an error
                    if ($lineResult['error']) {
                        $this->errors[] = [
                            'cod_solicitacao' => $row['COD_SOLICITACAO'],
                            'chave_loja' => $row['CHAVE_LOJA'],
                            'nome_loja' => $row['NOME_LOJA'],
                            'cnpj' => $row['CNPJ'],
                            'data_contrato' => trim($date),
                            'error_type' => $lineResult['error_type'],
                            'error_message' => $lineResult['error_message'],
                            'txt_line' => $lineResult['line'],
                            'sequencial' => $sequencial
                        ];
                    }
                    
                    $sequencial++;
                }
            } else {
                // Single date
                $lineResult = $this->gerarDetalhe($row, $sequencial);
                $linhas[] = $lineResult['line'];
                
                // Track if there was an error
                if ($lineResult['error']) {
                    $this->errors[] = [
                        'cod_solicitacao' => $row['COD_SOLICITACAO'],
                        'chave_loja' => $row['CHAVE_LOJA'],
                        'nome_loja' => $row['NOME_LOJA'],
                        'cnpj' => $row['CNPJ'],
                        'data_contrato' => $verifiedDate ?: (is_object($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO']->format('Y-m-d') : $row['DATA_CONTRATO']),
                        'error_type' => $lineResult['error_type'],
                        'error_message' => $lineResult['error_message'],
                        'txt_line' => $lineResult['line'],
                        'sequencial' => $sequencial
                    ];
                }
                
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
        
        // Track error info
        $hasError = false;
        $errorType = '';
        $errorMessage = '';
        
        // Determine which date to use
        $dataToUse = null;
        
        // Check if we have an override (from multi-date splitting)
        if (isset($row['DATA_CONTRATO_OVERRIDE']) && !empty($row['DATA_CONTRATO_OVERRIDE'])) {
            $dataToUse = $row['DATA_CONTRATO_OVERRIDE'];
        } else {
            // Get verified dates
            if (isset($row['COD_SOLICITACAO'])) {
                $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
                if ($verifiedDate) {
                    $dataToUse = $verifiedDate;
                }
            }
            
            // If no verified date, use DATA_CONTRATO
            if (!$dataToUse) {
                $dataToUse = $row['DATA_CONTRATO'];
                $hasError = true;
                $errorType = 'missing_verification';
                $errorMessage = 'DATA_CONT_VERIFIED não encontrado - usando DATA_CONTRATO do banco';
            }
        }
        
        // Format the date - handle different input types
        $dataContrato = '19700101'; // Default fallback
        
        if (!empty($dataToUse)) {
            if (is_object($dataToUse)) {
                // DateTime object
                $dataContrato = $dataToUse->format('Ymd');
            } else {
                // String date - need to parse it
                $dateString = trim($dataToUse);
                
                // Check if it's already in YYYY-MM-DD format
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                    // Format: YYYY-MM-DD
                    $dataContrato = str_replace('-', '', $dateString);
                } else {
                    // Try strtotime as fallback
                    $timestamp = @strtotime($dateString);
                    if ($timestamp !== false && $timestamp > 0) {
                        $dataContrato = date('Ymd', $timestamp);
                    } else {
                        $hasError = true;
                        $errorType = 'date_parse_failed';
                        $errorMessage = 'Falha ao parsear data: "' . $dateString . '" - usando fallback 19700101';
                    }
                }
            }
        } else {
            $hasError = true;
            $errorType = 'empty_date';
            $errorMessage = 'Data vazia - usando fallback 19700101';
        }
        
        $dataEncerramento = date('Ymd');
        $linhaFiller = str_pad(' ', 135, ' ', STR_PAD_LEFT);
        $sequencialStr = str_pad($sequencial, 5, '0', STR_PAD_LEFT);
        
        $linha = $tipo . $metodo . $instituicao . $cnpj . $cnpjSubs . $dataContrato . $dataEncerramento . $linhaFiller . $sequencialStr;
        
        return [
            'line' => substr($linha, 0, 250),
            'error' => $hasError,
            'error_type' => $errorType,
            'error_message' => $errorMessage
        ];
    }
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '@10';
        $codDoc = '5021';
        $quantidadeRegistros = str_pad($totalRegistros, 5, '0', STR_PAD_LEFT);
        $filler = '000000000000000000000000000000';
        
        $linha = $tipo . $codDoc . $quantidadeRegistros . $filler . str_repeat(' ', 138) . str_pad($totalRegistros + 2, 5, '0', STR_PAD_LEFT);
        
        return substr($linha, 0, 250);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CRITICAL FIX: Suppress all output before JSON
    ob_start();
    
    try {
        $handler = new EncerramentoMassa();
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification') {
            $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
            $result = $handler->getCNPJsForSelectionVerification($solicitacoes);
            
            ob_end_clean();
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'get_cnpjs_for_verification_excel') {
            if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
                $result = $handler->getCNPJsForExcelVerification(
                    $_FILES['excel_file']['tmp_name'],
                    $_FILES['excel_file']['name']
                );
                
                ob_end_clean();
                
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                ob_end_clean();
                
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Arquivo não enviado ou erro no upload']);
            }
            exit;
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
            $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
            $result = $handler->generateFromSelection($solicitacoes);
            
            ob_end_clean();
            
            if ($result['success']) {
                // Send error email if there are errors
                if ($result['has_errors']) {
                    require_once '../email_functions.php';
                    sendTXTErrorEmail($result['errors'], $result['nomeArquivo']);
                }
                
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
                $result = $handler->generateFromExcel(
                    $_FILES['excel_file']['tmp_name'],
                    $_FILES['excel_file']['name']
                );
                
                ob_end_clean();
                
                if ($result['success']) {
                    // Send error email if there are errors
                    if ($result['has_errors']) {
                        require_once '../email_functions.php';
                        sendTXTErrorEmail($result['errors'], $result['nomeArquivo']);
                    }
                    
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                    header('Content-Length: ' . strlen($result['conteudo']));
                    echo $result['conteudo'];
                } else {
                    header('Content-Type: application/json');
                    echo json_encode($result);
                }
            } else {
                ob_end_clean();
                
                header('Content-Type: application/json');
                $errorMsg = 'Arquivo não enviado';
                if (isset($_FILES['excel_file']['error'])) {
                    $errorMsg .= ' - Erro código: ' . $_FILES['excel_file']['error'];
                }
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            }
            exit;
        }
        
        ob_end_clean();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida: ' . ($_POST['acao'] ?? 'nenhuma')]);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro fatal: ' . $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        exit;
    }
}
?>


----------

<?php
// Add this function to your existing email_functions.php file

function sendTXTErrorEmail($errors, $fileName) {
    global $EMAIL_CONFIG;
    
    if (empty($errors)) {
        return ['success' => true, 'message' => 'Nenhum erro para reportar'];
    }
    
    ob_start();
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP_MAILER_NEW\mail.php');
    ob_end_clean();
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $emailConfig = buildTXTErrorEmailConfig($errors, $fileName);
    
    $email_to = ($_SESSION['cod_usu'] == $EMAIL_CONFIG['test_user_id']) 
        ? $EMAIL_CONFIG['test_email'] 
        : $EMAIL_CONFIG['op_team'];
    
    ob_start();
    $result = mailer(
        false, '', 
        $email_to, '', '', 
        $emailConfig['subject'], 
        utf8_decode($emailConfig['body']), 
        '', 'I', ''
    );
    ob_end_clean();
    
    return $result 
        ? ['success' => true, 'message' => 'Email de erro enviado com sucesso']
        : ['success' => false, 'message' => 'Erro ao enviar email de erro'];
}

function buildTXTErrorEmailConfig($errors, $fileName) {
    $current_date = date('d/m/Y H:i:s');
    $errorCount = count($errors);
    
    return [
        'subject' => 'ATENÇÃO: Erros na Geração de TXT BACEN - ' . $fileName,
        'body' => buildTXTErrorEmailBody($errors, $fileName, $current_date, $errorCount)
    ];
}

function buildTXTErrorEmailBody($errors, $fileName, $date, $errorCount) {
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;">
        <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 5px 5px 0 0;">
            <h2 style="margin: 0;">⚠️ ALERTA: Erros na Geração de TXT BACEN</h2>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6;">
            <p><strong>Arquivo:</strong> ' . htmlspecialchars($fileName) . '</p>
            <p><strong>Data/Hora:</strong> ' . $date . '</p>
            <p><strong>Total de Erros:</strong> <span style="color: #dc3545; font-weight: bold;">' . $errorCount . '</span></p>
            <p style="margin-bottom: 0;"><strong>Descrição:</strong> Os seguintes registros apresentaram problemas durante a geração do arquivo TXT para envio ao BACEN.</p>
        </div>
        
        <div style="margin-top: 20px;">
            <h3 style="color: #dc3545;">Detalhes dos Erros:</h3>';
    
    foreach ($errors as $index => $error) {
        $errorNum = $index + 1;
        $body .= '
            <div style="background-color: white; border: 2px solid #dc3545; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #dc3545;">Erro #' . $errorNum . ' - Linha ' . $error['sequencial'] . '</h4>
                
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <tr style="background-color: #f8f9fa;">
                        <th style="text-align: left; padding: 10px; border: 1px solid #dee2e6; width: 200px;">Campo</th>
                        <th style="text-align: left; padding: 10px; border: 1px solid #dee2e6;">Valor</th>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Código Solicitação</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['cod_solicitacao']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Chave Loja</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['chave_loja']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Nome Loja</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['nome_loja']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>CNPJ</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['cnpj']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Data Contrato</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['data_contrato']) . '</td>
                    </tr>
                    <tr style="background-color: #fff3cd;">
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Tipo de Erro</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #856404; font-weight: bold;">' . formatErrorType($error['error_type']) . '</td>
                    </tr>
                    <tr style="background-color: #f8d7da;">
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Mensagem de Erro</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #721c24;">' . htmlspecialchars($error['error_message']) . '</td>
                    </tr>
                </table>
                
                <div style="background-color: #f8f9fa; padding: 10px; border-radius: 3px;">
                    <p style="margin: 0 0 5px 0;"><strong>Linha TXT Gerada:</strong></p>
                    <div style="font-family: \'Courier New\', monospace; background-color: white; padding: 10px; border: 1px solid #dee2e6; overflow-x: auto;">';
        
        // Highlight the problematic parts in red
        $txtLine = htmlspecialchars($error['txt_line']);
        
        // Find and highlight the date portion (positions 27-34 for YYYYMMDD)
        if ($error['error_type'] === 'date_parse_failed' || $error['error_type'] === 'empty_date' || $error['error_type'] === 'missing_verification') {
            // Extract parts of the line
            $part1 = substr($txtLine, 0, 27); // Before date
            $datepart = substr($txtLine, 27, 8); // Date (8 chars)
            $part2 = substr($txtLine, 35); // After date
            
            $body .= $part1 . '<span style="background-color: #dc3545; color: white; font-weight: bold; padding: 2px 4px;">' . $datepart . '</span>' . $part2;
        } else {
            $body .= $txtLine;
        }
        
        $body .= '
                    </div>
                </div>
            </div>';
    }
    
    $body .= '
        </div>
        
        <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4 style="margin-top: 0; color: #856404;">⚠️ Ação Requerida</h4>
            <p style="margin-bottom: 0;">Por favor, revise os registros com erro listados acima e:</p>
            <ol style="margin-top: 5px;">
                <li>Verifique a <strong>DATA_CONT_VERIFIED</strong> no banco de dados</li>
                <li>Confirme se as datas de contrato estão corretas</li>
                <li>Execute novamente o processo de verificação BACEN se necessário</li>
                <li>Gere novamente o arquivo TXT após as correções</li>
            </ol>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                Email automático gerado pelo sistema de Encerramento BACEN<br>
                Data: ' . $date . '
            </p>
        </div>
    </div>';
    
    return $body;
}

function formatErrorType($errorType) {
    $types = [
        'missing_verification' => '❌ Verificação BACEN Ausente',
        'date_parse_failed' => '⚠️ Erro ao Processar Data',
        'empty_date' => '🚫 Data Vazia',
        'api_error' => '🌐 Erro na API BACEN'
    ];
    
    return $types[$errorType] ?? '❓ Erro Desconhecido';
}
?>


----------

// Add this to your analise_encerramento.js file
// Replace the existing verifySingleCNPJ function

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
                    const verifiedDate = parseDateFromResponse(data);
                    console.log('Verified date(s):', verifiedDate, 'Original date:', cnpjData.data_contrato);
                    
                    if (verifiedDate) {
                        console.log('Updating database with date(s)...');
                        updateDataContVerified(cnpjData.cod_solicitacao, verifiedDate)
                            .then(() => {
                                console.log('Database updated successfully');
                                resolve({ 
                                    success: true, 
                                    updated: true,
                                    cnpjData: cnpjData,
                                    verifiedDate: verifiedDate
                                });
                            })
                            .catch((err) => {
                                console.error('Database update failed:', err);
                                resolve({ 
                                    success: false, 
                                    updated: false,
                                    error: 'Database update failed',
                                    cnpjData: cnpjData
                                });
                            });
                    } else {
                        console.log('No verified date, skipping update');
                        resolve({ 
                            success: false, 
                            updated: false,
                            error: 'No verified date returned from API',
                            cnpjData: cnpjData
                        });
                    }
                } else {
                    console.error('API returned success:false');
                    resolve({ 
                        success: false, 
                        error: data.result || 'API returned error',
                        cnpjData: cnpjData
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error for CNPJ', cnpjData.cnpj);
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                resolve({ 
                    success: false, 
                    error: 'API connection error: ' + error,
                    cnpjData: cnpjData
                });
            }
        });
    });
}

// Update verifyAllCNPJs to track API errors
function verifyAllCNPJs(cnpjList, progressCallback) {
    return new Promise(function(resolve) {
        const total = cnpjList.length;
        let processed = 0;
        let updated = 0;
        let errors = 0;
        let apiErrors = []; // Track API errors
        
        function processNext(index) {
            if (index >= cnpjList.length) {
                // All done - resolve with API errors
                resolve({ 
                    total: total, 
                    processed: processed, 
                    updated: updated, 
                    errors: errors,
                    apiErrors: apiErrors
                });
                return;
            }
            
            const cnpjData = cnpjList[index];
            
            verifySingleCNPJ(cnpjData).then(function(result) {
                processed++;
                
                if (result.success && result.updated) {
                    updated++;
                } else if (!result.success) {
                    errors++;
                    // Store API error details
                    apiErrors.push({
                        cod_solicitacao: cnpjData.cod_solicitacao,
                        chave_loja: cnpjData.chave_loja,
                        nome_loja: cnpjData.nome_loja,
                        cnpj: cnpjData.cnpj,
                        data_contrato: cnpjData.data_contrato,
                        error_type: 'api_error',
                        error_message: result.error || 'Unknown API error'
                    });
                }
                
                if (progressCallback) {
                    progressCallback(processed, total, updated, errors);
                }
                
                // Small delay between requests
                setTimeout(function() {
                    processNext(index + 1);
                }, 500);
            });
        }
        
        // Start processing
        processNext(0);
    });
}

// Update gerarTXTSelection to send API errors
window.gerarTXTSelection = function() {
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
        success: function(data) {
            console.log('[5] AJAX Success! Received data:', data);
            
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                return;
            }
            
            const cnpjList = data.cnpjs;
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            
            // Verify all CNPJs and collect API errors
            verifyAllCNPJs(cnpjList, function(processed, total, updated, errors) {
                updateProgressModal(progressModal, processed, total, updated, errors);
            }).then(function(verificationResult) {
                console.log('[10] CNPJ verification complete', verificationResult);
                
                setTimeout(function() {
                    progressModal.remove();
                }, 2000);
                
                showNotification('Verificação concluída! Gerando TXT...', 'success');
                
                // Now generate TXT - API errors will be sent automatically by PHP
                $.ajax({
                    url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
                    method: 'POST',
                    data: {
                        acao: 'gerar_txt_selection',
                        solicitacoes: JSON.stringify(solicitacoes)
                    },
                    success: function(text, status, xhr) {
                        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                        
                        if (verificationResult.errors > 0) {
                            showNotification(
                                'Arquivo gerado com ' + verificationResult.errors + ' erro(s). Email de alerta enviado.', 
                                'warning'
                            );
                        } else {
                            showNotification('Arquivo TXT gerado com sucesso!', 'success');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[13] TXT generation error');
                        showNotification('Erro ao gerar arquivo TXT: ' + error, 'error');
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            console.error('[ERROR] AJAX call failed!');
            showNotification('Erro ao obter lista de CNPJs: ' + error, 'error');
        }
    });
};