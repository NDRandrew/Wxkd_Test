import os
from selenium import webdriver
from selenium.webdriver.edge.options import Options
from selenium.webdriver.edge.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import sys
import base64
import time
import re

os.environ.pop('HTTP_PROXY', None)
os.environ.pop('HTTPS_PROXY', None)
os.environ.pop('http_proxy', None)
os.environ.pop('https_proxy', None)
os.environ['NO_PROXY'] = 'localhost,127.0.0.1,::1'

options = Options()
options.add_argument("--start-maximized")
options.add_argument("--headless")
options.add_experimental_option("excludeSwitches", ["enable-logging"])
options.add_argument("--log-level=3")
options.add_argument("--disable-logging")
options.set_capability('proxy', {'proxyType': 'system'})

service = Service(r"C:\WebDrivers\msedgedriver.exe", log_output=os.devnull)
driver = webdriver.Edge(service=service, options=options)
wait = WebDriverWait(driver, 20)

try:
    driver.get("https://www.bcb.gov.br/estabilidadefinanceira/unicadentidadesinteressebanco")
    time.sleep(5)
    
    button = wait.until(EC.element_to_be_clickable(
        (By.XPATH, '/html/body/app-root/app-root/div/div/main/dynamic-comp/div/div/div[2]/div[1]/a[1]')
    ))
    button.click()
    time.sleep(2)
    
    email = wait.until(EC.presence_of_element_located((By.XPATH, '//*[@id="userNameInput"]')))
    base64_email = 'MDUyMzc3OTcyLlNDSE5FVFpMRVI='
    decode_string = base64.b64decode(base64_email).decode('utf-8')
    email.send_keys(decode_string)
    
    senha = driver.find_element(By.XPATH, '//*[@id="passwordInput"]')
    base64_senha = 'YnJhZDEyMzQ='
    decode_string_s = base64.b64decode(base64_senha).decode('utf-8')
    senha.send_keys(decode_string_s)
    
    driver.find_element(By.XPATH, '//*[@id="submitButton"]').click()
    time.sleep(5)
    
    driver.find_element(By.XPATH, '/html/body/div[1]/ul/li[8]/a').click()
    driver.find_element(By.XPATH, '/html/body/div[1]/ul/li[8]/ul/li[1]/a').click()
    time.sleep(2)
    
    iframes_inicial = driver.find_elements(By.TAG_NAME, "iframe")
    driver.switch_to.frame(iframes_inicial[0])
    
    select_li = wait.until(EC.element_to_be_clickable(
        (By.XPATH, "/html/body/form/center/table/tbody/tr/td/table[2]/tbody/tr[4]/td[2]/select")
    ))
    select = Select(select_li)
    select.select_by_index(1)
    
    cnpj_input = driver.find_element(By.XPATH, '/html/body/form/center/table/tbody/tr/td/table[2]/tbody/tr[4]/td[2]/input')
    cnpj_input.send_keys(sys.argv[1])
    
    driver.find_element(By.XPATH, '/html/body/form/center/table/tbody/tr/td/table[2]/tbody/tr[4]/td[2]/img').click()
    time.sleep(2)
    
    driver.find_element(By.XPATH, '/html/body/form/center/center/table/tbody/tr/td[1]/input').click()
    driver.switch_to.default_content()
    time.sleep(2)
    
    iframes_secundario = driver.find_elements(By.TAG_NAME, "iframe")
    driver.switch_to.frame(iframes_secundario[0])
    
    driver.find_element(By.XPATH, '/html/body/form/table/tbody/tr/td/font/a[9]').click()
    time.sleep(2)
    
    # Find all links matching the pattern
    links = wait.until(EC.presence_of_all_elements_located((
        By.CSS_SELECTOR,
        "a[href^='redireciona.jsp?tipoOperacao=c'][href*='idVinculo']"
    )))
    
    # Extract dates from link texts
    dates_found = []
    for link in links:
        text = (link.text or link.get_attribute("textContent") or "").strip()
        # Extract date in DD/MM/YYYY format
        date_match = re.search(r'(\d{2}/\d{2}/\d{4})', text)
        if date_match:
            dates_found.append(date_match.group(1))
    
    if dates_found:
        # Print the first date found (usually the active contract)
        print(dates_found[0])
    else:
        print("Error: Date not found")
    
except Exception as e:
    print(f"Error: {str(e)}")
finally:
    driver.quit()


------------

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