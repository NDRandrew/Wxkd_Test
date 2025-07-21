Update the Model (TestM.txt) - Modify the updateWxkdFlag method to include logging:public function updateWxkdFlag($records, $fullData = array(), $chaveLote = 0, $filtro = 'cadastramento') {
    $logs = array();
    
    try {
        $logs[] = "updateWxkdFlag START - Records count: " . count($records) . ", ChaveLote: $chaveLote, Filtro: $filtro";
        
        if (empty($records)) {
            $logs[] = "updateWxkdFlag - No records provided";
            $this->debugLogs = $logs;
            return false;
        }
        
        $updateCount = 0;
        $logInsertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        // First, update WXKD_FLAG as before
        foreach ($records as $index => $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                continue;
            }
            
            $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                         WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
            
            $checkResult = $this->sql->select($checkSql);
            
            if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                $logs[] = "updateWxkdFlag - No matching record found in tb_wxkd_flag for record #$index";
                continue;
            }
            
            $sql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                    SET WXKD_FLAG = 1 
                    WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
            
            $result = $this->sql->update($sql);
            
            if ($result) {
                $updateCount++;
                $logs[] = "updateWxkdFlag - Successfully updated WXKD_FLAG for record #$index";
            } else {
                $logs[] = "updateWxkdFlag - Failed to update WXKD_FLAG for record #$index";
            }
        }
        
        // Now insert into TB_WXKD_LOG if we have full data and chaveLote
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                $logs[] = "updateWxkdFlag - Record keys: " . implode(', ', array_keys($record));
                
                // Check if required fields exist
                if (!isset($record['Chave_Loja'])) {
                    $logs[] = "updateWxkdFlag - Missing Chave_Loja in log record #$index";
                    continue;
                }
                
                if (!isset($record['Nome_Loja'])) {
                    $logs[] = "updateWxkdFlag - Missing Nome_Loja in log record #$index";
                    continue;
                }
                
                $chaveLoja = (int)$record['Chave_Loja'];
                $nomeLoja = str_replace("'", "''", $record['Nome_Loja']);
                $codEmpresa = isset($record['Cod_Empresa']) ? (int)$record['Cod_Empresa'] : 0;
                $codLoja = isset($record['Cod_Loja']) ? (int)$record['Cod_Loja'] : 0;
                $tipoCorrespondente = isset($record['TIPO_CORRESPONDENTE']) ? str_replace("'", "''", $record['TIPO_CORRESPONDENTE']) : '';
                $tipoContrato = isset($record['TIPO_CONTRATO']) ? str_replace("'", "''", $record['TIPO_CONTRATO']) : '';
                
                $logs[] = "updateWxkdFlag - Log prepared values: ChaveLoja=$chaveLoja, NomeLoja=$nomeLoja, CodEmpresa=$codEmpresa, CodLoja=$codLoja";
                
                $logSql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
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
                
                $logs[] = "updateWxkdFlag - Log SQL Query: " . $logSql;
                
                try {
                    $logResult = $this->sql->insert($logSql);
                    $logs[] = "updateWxkdFlag - Log insert result for record #$index: " . ($logResult ? 'SUCCESS' : 'FAILED');
                    
                    if ($logResult) {
                        $logInsertCount++;
                        $logs[] = "updateWxkdFlag - Log insert count now: $logInsertCount";
                    }
                } catch (Exception $logEx) {
                    $logs[] = "updateWxkdFlag - Log insert exception for record #$index: " . $logEx->getMessage();
                }
            }
        }
        
        $logs[] = "updateWxkdFlag END - Flag updates: $updateCount, Log inserts: $logInsertCount";
        $this->debugLogs = $logs;
        
        return $updateCount > 0;
        
    } catch (Exception $e) {
        $logs[] = "updateWxkdFlag - MAIN Exception: " . $e->getMessage();
        $this->debugLogs = $logs;
        return false;
    }
}

public function getDebugLogs() {
    return isset($this->debugLogs) ? $this->debugLogs : array();
}Update the Controller (TestC.txt) - Modify the exportTXT method:public function exportTXT() {
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
            }
        }
        
        if (!empty($recordsToUpdate)) {
            // Call the updated method with full data for logging
            $updateResult = $this->model->updateWxkdFlag($recordsToUpdate, $data, $chaveLote, $filter);
            
            // Get debug logs from model
            $debugLogs = $this->model->getDebugLogs();
            
            $xml .= '<debugLogs>';
            foreach ($debugLogs as $log) {
                $xml .= '<log>' . addcslashes($log, '"<>&') . '</log>';
            }
            $xml .= '</debugLogs>';
            
            $xml .= '<flagUpdate>';
            $xml .= '<success>' . ($updateResult ? 'true' : 'false') . '</success>';
            $xml .= '<recordsUpdated>' . count($recordsToUpdate) . '</recordsUpdated>';
            $xml .= '</flagUpdate>';
            
            $xml .= '<logInsert>';
            $xml .= '<success>' . ($updateResult ? 'true' : 'false') . '</success>';
            $xml .= '<chaveLote>' . $chaveLote . '</chaveLote>';
            $xml .= '<recordsLogged>' . count($data) . '</recordsLogged>';
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
}