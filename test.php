<?php
// Add this to ajax_encerramento.php (after existing handlers, before the main try-catch)

// Update DATA_CONT_VERIFIED in database
if (isset($_POST['acao']) && $_POST['acao'] === 'update_data_cont_verified') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $codSolicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $dataContVerified = isset($_POST['data_cont_verified']) ? $_POST['data_cont_verified'] : '';
        
        if ($codSolicitacao <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Código de solicitação inválido']);
            exit;
        }
        
        if (empty($dataContVerified)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Data de verificação vazia']);
            exit;
        }
        
        $model = new Analise();
        
        // Check if record exists in ENCERRAMENTO_TB_PORTAL
        $status = $model->getEncerramentoStatus($codSolicitacao);
        
        if (!$status) {
            // Create record first
            $where = "AND A.COD_SOLICITACAO = " . $codSolicitacao;
            $dados = $model->solicitacoes($where, 1, 0);
            if (!empty($dados)) {
                $model->insertEncerramentoStatus($codSolicitacao, $dados[0]['CHAVE_LOJA']);
            } else {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode_custom(['success' => false, 'message' => 'Solicitação não encontrada']);
                exit;
            }
        }
        
        // Now update
        $result = $model->updateDataContVerified($codSolicitacao, $dataContVerified);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false,
            'message' => $result ? 'Atualizado com sucesso' : 'Erro ao executar UPDATE',
            'cod_solicitacao' => $codSolicitacao,
            'data_verified' => $dataContVerified
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
?>


--------

from flask import Flask, request, jsonify
import subprocess
import json
import re
import os

app = Flask(__name__)

@app.route('/portalexpresso/view/verifica_bacen', methods=['POST'])
def bacen_verify():
    try:
        data = request.get_json()
        
        user = data.get('user')
        pwd = data.get('pwd')
        cnpj = data.get('cnpj')
        
        if not all([user, pwd, cnpj]):
            return jsonify({'error': 'Missing parameters', 'result': 'Error', 'success': False}), 400
        
        cnpj_formatted = cnpj.zfill(8)
        
        python_script = r"C:\WebDrivers\bacen_verification.py"
        
        result = subprocess.run(
            ['python', python_script, cnpj_formatted],
            capture_output=True,
            text=True,
            timeout=60
        )
        
        if result.returncode != 0:
            return jsonify({'error': 'Script execution failed', 'result': 'Error', 'success': False}), 500
        
        output = result.stdout.strip()
        
        # Check if output is "Error"
        if output == "Error":
            return jsonify({
                'success': False,
                'result': 'Error',
                'cnpj': cnpj_formatted
            })
        
        # Try to parse as JSON array
        try:
            dates_array = json.loads(output)
            if isinstance(dates_array, list) and len(dates_array) > 0:
                # Return all dates formatted
                formatted_dates = [f'{date} - Verificado' for date in dates_array]
                return jsonify({
                    'success': True,
                    'result': ' | '.join(formatted_dates),  # Join with separator
                    'dates': dates_array,  # Also send raw dates array
                    'cnpj': cnpj_formatted
                })
        except json.JSONDecodeError:
            # If not JSON, treat as single date (backward compatibility)
            date_match = re.search(r'(\d{2}/\d{2}/\d{4})', output)
            if date_match:
                verified_date = date_match.group(1)
                return jsonify({
                    'success': True,
                    'result': f'{verified_date} - Verificado',
                    'dates': [verified_date],
                    'cnpj': cnpj_formatted
                })
        
        return jsonify({
            'success': False,
            'result': 'Error',
            'cnpj': cnpj_formatted
        })
            
    except subprocess.TimeoutExpired:
        return jsonify({'error': 'Timeout', 'result': 'Error', 'success': False}), 504
    except Exception as e:
        return jsonify({'error': str(e), 'result': 'Error', 'success': False}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, ssl_context='adhoc')

--------

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
import json

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
    
    # Extract ALL dates from link texts
    dates_found = []
    for link in links:
        text = (link.text or link.get_attribute("textContent") or "").strip()
        # Extract date in DD/MM/YYYY format
        date_match = re.search(r'(\d{2}/\d{2}/\d{4})', text)
        if date_match:
            dates_found.append(date_match.group(1))
    
    if dates_found:
        # Return ALL dates as JSON array
        print(json.dumps(dates_found))
    else:
        print("Error")
    
except Exception as e:
    print(f"Error")
finally:
    driver.quit()