<?php
// Add these methods to analise_encerramento_model.class.php

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
                A.COD_SOLICITACAO AS COD_SOLICITACAO, 
                A.COD_AG AS COD_AG, 
                CASE WHEN A.COD_AG = F.COD_AG_LOJA THEN F.NOME_AG ELSE 'AGENCIA' END AS NOME_AG, 
                A.CHAVE_LOJA AS CHAVE_LOJA, 
                F.NOME_LOJA AS NOME_LOJA, 
                G.NR_PACB AS NR_PACB, 
                F.COD_EMPRESA AS COD_EMPRESA,
                A.DATA_CAD AS DATA_RECEPCAO, 
                F.RZ_SOCIAL_EMP AS NOME_EMPRESA,
                F.CNPJ AS CNPJ,
                N.DATA_CONTRATO,
                ES.DATA_CONT_VERIFIED,
                ROW_NUMBER() OVER (
                    PARTITION BY A.COD_SOLICITACAO
                    ORDER BY COALESCE(G.DATA_LAST_TRANS, A.DATA_CAD) DESC, A.COD_SOLICITACAO DESC
                ) AS rn
            FROM 
                TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK)
                JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK)
                    ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
                    ON A.CHAVE_LOJA = F.CHAVE_LOJA 
                JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = G.COD_SOLICITACAO
                LEFT JOIN (
                    SELECT KEY_EMPRESA, DATA_CONTRATO
                    FROM MESU..TB_EMPRESA_VERSAO_CONTRATO2
                ) N ON F.COD_EMPRESA = N.KEY_EMPRESA
                LEFT JOIN MESU..ENCERRAMENTO_TB_PORTAL ES WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = ES.COD_SOLICITACAO
            WHERE 1 = 1
            AND F.BE_INAUGURADO = 1
            " . $where . "
        )
        SELECT
            COD_SOLICITACAO, 
            COD_AG, 
            NOME_AG, 
            CHAVE_LOJA, 
            NOME_LOJA, 
            NR_PACB, 
            COD_EMPRESA,
            DATA_RECEPCAO, 
            NOME_EMPRESA,
            CNPJ,
            DATA_CONTRATO,
            DATA_CONT_VERIFIED
        FROM Q
        WHERE rn = 1
        ORDER BY COD_SOLICITACAO DESC
        OFFSET " . (int)$offset . " ROWS
        FETCH NEXT " . (int)$limit . " ROWS ONLY;
    ";

    $dados = $this->sql->select($query);
    return $dados;
}
?>


-----------


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
        
        $this->verifyCNPJsAndUpdate($dados);
        
        return $this->generateTXT($dados);
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
    
    private function verifyCNPJsAndUpdate(&$dados) {
        foreach ($dados as &$row) {
            $cnpj = $this->formatCNPJ($row['CNPJ']);
            $verificationResult = $this->callVerificationAPI($cnpj);
            
            if ($verificationResult['success']) {
                $verifiedDate = $this->parseDateFromResponse($verificationResult['data']);
                
                if ($verifiedDate) {
                    $dataContrato = $row['DATA_CONTRATO'];
                    $dataContratoFormatted = is_object($dataContrato) 
                        ? $dataContrato->format('Y-m-d') 
                        : date('Y-m-d', strtotime($dataContrato));
                    
                    if ($verifiedDate !== $dataContratoFormatted) {
                        $this->model->updateDataContVerified($row['COD_SOLICITACAO'], $verifiedDate);
                        $row['DATA_CONT_VERIFIED'] = $verifiedDate;
                    }
                }
            }
        }
    }
    
    private function formatCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return str_pad(substr($cnpj, 0, 8), 8, '0', STR_PAD_LEFT);
    }
    
    private function callVerificationAPI($cnpj) {
        $ch = curl_init($this->apiUrl);
        
        $payload = json_encode([
            'user' => $this->apiUser,
            'pwd' => $this->apiPwd,
            'token' => $this->apiToken,
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
            curl_close($ch);
            return ['success' => false, 'data' => 'Error'];
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        return [
            'success' => true,
            'data' => $data['result'] ?? 'Error'
        ];
    }
    
    private function parseDateFromResponse($response) {
        if ($response === 'Error' || empty($response)) {
            return null;
        }
        
        preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $response, $matches);
        
        if (count($matches) === 4) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }
        
        return null;
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

----------

-- Add DATA_CONT_VERIFIED column to ENCERRAMENTO_TB_PORTAL table
ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL
ADD DATA_CONT_VERIFIED DATE NULL;

-- Create index for better performance
CREATE INDEX IDX_ENCERRAMENTO_DATA_CONT_VERIFIED 
ON MESU..ENCERRAMENTO_TB_PORTAL(DATA_CONT_VERIFIED);



--------------

from flask import Flask, request, jsonify
import subprocess
import json
import re
import os

app = Flask(__name__)

@app.route('/portal_tds/bacen_verify', methods=['POST'])
def bacen_verify():
    try:
        data = request.get_json()
        
        user = data.get('user')
        pwd = data.get('pwd')
        token = data.get('token')
        cnpj = data.get('cnpj')
        
        if not all([user, pwd, token, cnpj]):
            return jsonify({'error': 'Missing parameters', 'result': 'Error'}), 400
        
        cnpj_formatted = cnpj.zfill(8)
        
        python_script = r"C:\path\to\bacen_verification.py"
        
        result = subprocess.run(
            ['python', python_script, cnpj_formatted],
            capture_output=True,
            text=True,
            timeout=60
        )
        
        if result.returncode != 0:
            return jsonify({'error': 'Script execution failed', 'result': 'Error'}), 500
        
        output = result.stdout
        
        date_match = re.search(r'(\d{2}/\d{2}/\d{4})', output)
        
        if date_match:
            verified_date = date_match.group(1)
            return jsonify({
                'success': True,
                'result': f'{verified_date} - Verificado',
                'cnpj': cnpj_formatted
            })
        else:
            return jsonify({
                'success': False,
                'result': 'Error',
                'cnpj': cnpj_formatted
            })
            
    except subprocess.TimeoutExpired:
        return jsonify({'error': 'Timeout', 'result': 'Error'}), 504
    except Exception as e:
        return jsonify({'error': str(e), 'result': 'Error'}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, ssl_context='adhoc')


----------

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
    
    page_text = driver.page_source
    
    date_pattern = r'Data\s*(?:do\s*)?Contrato[:\s]*(\d{2}/\d{2}/\d{4})'
    date_match = re.search(date_pattern, page_text, re.IGNORECASE)
    
    if date_match:
        contract_date = date_match.group(1)
        print(contract_date)
    else:
        print("Error: Date not found")
    
except Exception as e:
    print(f"Error: {str(e)}")
finally:
    driver.quit()


------------

<?php
// Save as: api_config.php
// Include this file in encerramento_massa.php using: require_once 'api_config.php';

define('BACEN_API_URL', 'https://10.222.217.237/portal_tds/bacen_verify');
define('BACEN_API_USER', 'your_actual_username');
define('BACEN_API_PWD', 'your_actual_password');
define('BACEN_API_TOKEN', 'your_actual_token');

// In encerramento_massa.php, replace the hardcoded values with:
// private $apiUrl = BACEN_API_URL;
// private $apiUser = BACEN_API_USER;
// private $apiPwd = BACEN_API_PWD;
// private $apiToken = BACEN_API_TOKEN;
?>

--------------

# BACEN CNPJ Verification Implementation Guide

## Overview
This implementation adds automatic CNPJ verification via API when generating TXT BACEN files. The system validates contract dates and stores discrepancies for accurate reporting.

## Architecture Flow

1. **User triggers TXT generation** (via checkbox selection or Excel import)
2. **System extracts CNPJs** from selected records
3. **For each CNPJ:**
   - Format to 8 characters (pad left with zeros)
   - Call Flask API endpoint
   - API executes Python Selenium script
   - Script scrapes BACEN website for contract date
   - Compare returned date with database DATA_CONTRATO
   - If different, store in DATA_CONT_VERIFIED column
4. **Generate TXT file** using verified dates where applicable

## Installation Steps

### 1. Database Changes
```sql
-- Execute this SQL script first
ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL
ADD DATA_CONT_VERIFIED DATE NULL;

CREATE INDEX IDX_ENCERRAMENTO_DATA_CONT_VERIFIED 
ON MESU..ENCERRAMENTO_TB_PORTAL(DATA_CONT_VERIFIED);
```

### 2. Model Updates
Add the three new methods to `analise_encerramento_model.class.php`:
- `updateDataContVerified()`
- `getDataContVerified()`
- `solicitacoesEncerramentoWithVerified()`

### 3. Replace encerramento_massa.php
Replace the entire file with the updated version that includes:
- API verification logic
- CNPJ formatting (8-char with leading zeros)
- Date comparison logic
- Error handling for failed verifications

### 4. Configure API Credentials
Create `api_config.php` with your actual credentials:
```php
define('BACEN_API_URL', 'https://10.222.217.237/portal_tds/bacen_verify');
define('BACEN_API_USER', 'your_username');
define('BACEN_API_PWD', 'your_password');
define('BACEN_API_TOKEN', 'your_token');
```

### 5. Deploy Flask API
- Install Flask: `pip install flask`
- Save the Flask API script
- Update the Python script path in the Flask code
- Run: `python flask_api.py`
- Consider using a production WSGI server (Gunicorn, uWSGI)

### 6. Update Python Selenium Script
- Replace `bacen_verification.py` with the updated version
- Ensure Edge WebDriver is installed at `C:\WebDrivers\msedgedriver.exe`
- Update credentials in the script if needed
- Test manually: `python bacen_verification.py 12345678`

## Key Features

### CNPJ Formatting
- Automatically pads CNPJs to 8 characters
- Example: "123456" becomes "00123456"
- Only first 8 digits are used if longer

### Error Handling
- Individual CNPJ failures don't stop the entire process
- Errors return "Error" status but process continues
- Failed verifications use original DATA_CONTRATO

### Date Comparison
- Compares API date (DD/MM/YYYY) with SQL datetime
- Only stores DATA_CONT_VERIFIED if dates differ
- TXT generation prioritizes DATA_CONT_VERIFIED over DATA_CONTRATO

### Sequential Processing
- Each CNPJ is verified one at a time
- Prevents API overload
- Allows for detailed error logging per CNPJ

## API Response Format

### Success Response
```json
{
  "success": true,
  "result": "15/10/2022 - Verificado",
  "cnpj": "00123456"
}
```

### Error Response
```json
{
  "success": false,
  "result": "Error",
  "cnpj": "00123456"
}
```

## TXT Generation Logic

For each record in the TXT:
1. Check if `DATA_CONT_VERIFIED` exists and is not null
2. If yes: Use `DATA_CONT_VERIFIED`
3. If no: Use `DATA_CONTRATO`
4. Format date as `Ymd` for BACEN file

## Testing Procedure

1. **Test API endpoint:**
   ```bash
   curl -X POST https://10.222.217.237/portal_tds/bacen_verify \
     -H "Content-Type: application/json" \
     -d '{"user":"test","pwd":"test","token":"test","cnpj":"12345678"}'
   ```

2. **Test single CNPJ generation:**
   - Select one record with checkbox
   - Click "Gerar TXT BACEN"
   - Verify API call in logs
   - Check DATA_CONT_VERIFIED column in database

3. **Test bulk generation:**
   - Select multiple records
   - Verify sequential processing
   - Check all records updated correctly

4. **Test Excel import:**
   - Create Excel with CHAVE_LOJA column
   - Import and generate TXT
   - Verify all CNPJs processed

## Troubleshooting

### API not responding
- Check Flask server is running
- Verify firewall allows port 5000
- Check SSL certificate configuration

### Selenium script fails
- Verify Edge WebDriver version matches Edge browser
- Check BACEN website structure hasn't changed
- Ensure credentials are correct and not expired

### Dates not matching
- Verify date format parsing in `parseDateFromResponse()`
- Check SQL datetime format conversion
- Look for timezone issues

### Performance issues
- Consider caching verified CNPJs
- Implement batch processing for large datasets
- Add timeout controls for API calls

## Security Considerations

- Store API credentials securely (use environment variables)
- Implement API rate limiting
- Add request authentication/authorization
- Log all verification attempts
- Consider encrypting sensitive data in transit
- Implement retry logic with exponential backoff

## Maintenance

- Monitor API call success rates
- Review failed verifications regularly
- Update Selenium selectors if BACEN site changes
- Keep WebDriver updated
- Regular backup of DATA_CONT_VERIFIED column