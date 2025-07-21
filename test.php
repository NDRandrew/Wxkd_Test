public function insertLogEntries($records, $chaveLote, $filtro = 'cadastramento') {
    $logs = array();
    
    try {
        $logs[] = "insertLogEntries START - Records count: " . count($records) . ", ChaveLote: $chaveLote, Filtro: $filtro";
        
        if (empty($records)) {
            $logs[] = "insertLogEntries - No records provided";
            $this->debugLogs = $logs;
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        $logs[] = "insertLogEntries - Current DateTime: $currentDateTime";
        
        foreach ($records as $index => $record) {
            $logs[] = "insertLogEntries - Processing record #$index";
            $logs[] = "insertLogEntries - Record keys: " . implode(', ', array_keys($record));
            
            // Check if required fields exist
            if (!isset($record['Chave_Loja'])) {
                $logs[] = "insertLogEntries - Missing Chave_Loja in record #$index";
                continue;
            }
            
            if (!isset($record['Nome_Loja'])) {
                $logs[] = "insertLogEntries - Missing Nome_Loja in record #$index";
                continue;
            }
            
            $chaveLoja = (int)$record['Chave_Loja'];
            $nomeLoja = str_replace("'", "''", $record['Nome_Loja']);
            $codEmpresa = isset($record['Cod_Empresa']) ? (int)$record['Cod_Empresa'] : 0;
            $codLoja = isset($record['Cod_Loja']) ? (int)$record['Cod_Loja'] : 0;
            $tipoCorrespondente = isset($record['TIPO_CORRESPONDENTE']) ? str_replace("'", "''", $record['TIPO_CORRESPONDENTE']) : '';
            $tipoContrato = isset($record['TIPO_CONTRATO']) ? str_replace("'", "''", $record['TIPO_CONTRATO']) : '';
            
            $logs[] = "insertLogEntries - Prepared values: ChaveLoja=$chaveLoja, NomeLoja=$nomeLoja, CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                     SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                    VALUES 
                    ($chaveLote, 
                     '$currentDateTime', 
                     $chaveLoja, 
                     '$nomeLoja', 
                     $codEmpresa, 
                     $codLoja, 
                     '$tipoCorrespondente', 
                     3000.00, 5000.00, 2000.00, 2000.00, 
                     'Apto', 'Apto', 'Apto', 'Apto', 
                     GETDATE(), 
                     '$tipoContrato', 
                     '$filtro')";
            
            $logs[] = "insertLogEntries - SQL Query: " . $sql;
            
            try {
                $result = $this->sql->insert($sql);
                $logs[] = "insertLogEntries - Insert result for record #$index: " . ($result ? 'SUCCESS' : 'FAILED');
                
                if ($result) {
                    $insertCount++;
                    $logs[] = "insertLogEntries - Insert count now: $insertCount";
                } else {
                    $logs[] = "insertLogEntries - Insert failed for record #$index";
                }
            } catch (Exception $insertEx) {
                $logs[] = "insertLogEntries - Insert exception for record #$index: " . $insertEx->getMessage();
            }
        }
        
        $logs[] = "insertLogEntries END - Total inserted: $insertCount out of " . count($records) . " records";
        $this->debugLogs = $logs;
        return $insertCount > 0;
        
    } catch (Exception $e) {
        $logs[] = "insertLogEntries - MAIN Exception: " . $e->getMessage();
        $this->debugLogs = $logs;
        return false;
    }
}

public function getDebugLogs() {
    return isset($this->debugLogs) ? $this->debugLogs : array();
}Update the Controller (TestC.txt) - Add logs to XML response:public function exportTXT() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    try {
        $data = $this->getFilteredData($filter, $selectedIds);
        
        if (empty($data)) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>Nenhum dado encontrado para exportação</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
        
        $invalidRecords = $this->validateRecordsForTXTExport($data);
        
        if (!empty($invalidRecords)) {
            $this->outputValidationError($invalidRecords);
            return;
        }
        
        // Generate CHAVE_LOTE BEFORE processing
        $chaveLote = $this->model->generateChaveLote();
        
        $xml = '<response><success>true</success><txtData>';
        
        $recordsToUpdate = array();
        $recordsToLog = array();
        
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '', '"<>&') . '</cod_empresa>';
            $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
            $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
            $xml .= '<TIPO_CORRESPONDENTE>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</TIPO_CORRESPONDENTE>';
            $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
            $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
            $xml .= '</row>';
            
            $codEmpresa = (int) (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : 0);
            $codLoja = (int) (isset($row['Cod_Loja']) ? $row['Cod_Loja'] : 0);
            
            if ($codEmpresa > 0 && $codLoja > 0) {
                $recordsToUpdate[] = array(
                    'COD_EMPRESA' => $codEmpresa,
                    'COD_LOJA' => $codLoja
                );
                
                // Prepare data for logging
                $recordsToLog[] = $row;
            }
        }
        
        $updateResult = false;
        $logResult = false;
        
        if (!empty($recordsToUpdate)) {
            // Update WXKD_FLAG
            $updateResult = $this->model->updateWxkdFlag($recordsToUpdate);
            
            // Insert log entries
            if (!empty($recordsToLog)) {
                $logResult = $this->model->insertLogEntries($recordsToLog, $chaveLote, $filter);
                
                // Get debug logs from model
                $debugLogs = $this->model->getDebugLogs();
                
                $xml .= '<debugLogs>';
                foreach ($debugLogs as $log) {
                    $xml .= '<log>' . addcslashes($log, '"<>&') . '</log>';
                }
                $xml .= '</debugLogs>';
            }
            
            $xml .= '<flagUpdate>';
            $xml .= '<success>' . ($updateResult ? 'true' : 'false') . '</success>';
            $xml .= '<recordsUpdated>' . count($recordsToUpdate) . '</recordsUpdated>';
            $xml .= '</flagUpdate>';
            
            $xml .= '<logInsert>';
            $xml .= '<success>' . ($logResult ? 'true' : 'false') . '</success>';
            $xml .= '<chaveLote>' . $chaveLote . '</chaveLote>';
            $xml .= '<recordsLogged>' . count($recordsToLog) . '</recordsLogged>';
            $xml .= '</logInsert>';
        }
        
        $xml .= '</txtData></response>';
        echo $xml;
        exit;
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
        $xml .= '</response>';
        echo $xml;
        exit;
    }
}Update JavaScript (TestJ.txt) - Add console logging:function exportTXTData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportTXT&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const xmlContent = extractXMLFromMixedResponse(responseText);
                if (!xmlContent) {
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                // Output debug logs to console
                const debugLogs = xmlDoc.getElementsByTagName('debugLogs')[0];
                if (debugLogs) {
                    console.log('=== DEBUG LOGS FROM PHP ===');
                    const logs = debugLogs.getElementsByTagName('log');
                    for (let i = 0; i < logs.length; i++) {
                        console.log(logs[i].textContent);
                    }
                    console.log('=== END DEBUG LOGS ===');
                }
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    const validationError = xmlDoc.getElementsByTagName('validation_error')[0];
                    if (validationError && validationError.textContent === 'true') {
                        const invalidRecords = Array.from(xmlDoc.getElementsByTagName('record')).map(record => ({
                            cod_empresa: record.getElementsByTagName('cod_empresa')[0]?.textContent || '',
                            error: record.getElementsByTagName('error_msg')[0]?.textContent || record.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido'
                        }));
                        
                        showValidationAlert(invalidRecords);
                        return;
                    }
                    
                    const errorMsg = xmlDoc.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido';
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                const txtData = extractTXTFromXML(xmlDoc);
                
                if (txtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
                const filename = `dashboard_selected_${filter}_${getCurrentTimestamp()}.txt`;
                downloadTXTFile(txtData, filename);
                
                // Show success message with log information
                const logInsert = xmlDoc.getElementsByTagName('logInsert')[0];
                if (logInsert) {
                    const logSuccess = logInsert.getElementsByTagName('success')[0]?.textContent === 'true';
                    const chaveLote = logInsert.getElementsByTagName('chaveLote')[0]?.textContent;
                    const recordsLogged = logInsert.getElementsByTagName('recordsLogged')[0]?.textContent;
                    
                    if (logSuccess) {
                        showSuccessMessage(
                            `Arquivo TXT exportado com sucesso!\n\n` +
                            `• Registros processados: ${recordsLogged}\n` +
                            `• Lote criado: #${chaveLote}\n` +
                            `• Status: Registrado no histórico\n\n` +
                            `Você pode visualizar este lote na aba "Histórico".`
                        );
                    } else {
                        console.error('Log insert failed for chaveLote:', chaveLote);
                    }
                }
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição: ' + error.message);
        });
}