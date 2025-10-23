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
        
        return $this->generateTXT($dados);
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
                    // Set as trimmed string date (not object)
                    $rowCopy['DATA_CONTRATO_OVERRIDE'] = trim($date);
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
                    }
                }
            }
        }
        
        $dataEncerramento = date('Ymd');
        $linhaFiller = str_pad(' ', 135, ' ', STR_PAD_LEFT);
        $sequencialStr = str_pad($sequencial, 5, '0', STR_PAD_LEFT);
        
        $linha = $tipo . $metodo . $instituicao . $cnpj . $cnpjSubs . $dataContrato . $dataEncerramento . $linhaFiller . $sequencialStr;
        
        return substr($linha, 0, 250);
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
            
            // Clear any warnings/errors before JSON output
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
