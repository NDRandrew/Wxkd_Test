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