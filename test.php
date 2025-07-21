public function insertLogEntries($records, $chaveLote, $filtro = 'cadastramento') {
    try {
        error_log("insertLogEntries START - Records count: " . count($records) . ", ChaveLote: $chaveLote, Filtro: $filtro");
        
        if (empty($records)) {
            error_log("insertLogEntries - No records provided");
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        error_log("insertLogEntries - Current DateTime: $currentDateTime");
        
        foreach ($records as $index => $record) {
            error_log("insertLogEntries - Processing record #$index");
            error_log("insertLogEntries - Record data: " . print_r($record, true));
            
            // Check if required fields exist
            if (!isset($record['Chave_Loja'])) {
                error_log("insertLogEntries - Missing Chave_Loja in record #$index");
                continue;
            }
            
            if (!isset($record['Nome_Loja'])) {
                error_log("insertLogEntries - Missing Nome_Loja in record #$index");
                continue;
            }
            
            $chaveLoja = (int)$record['Chave_Loja'];
            $nomeLoja = str_replace("'", "''", $record['Nome_Loja']);
            $codEmpresa = isset($record['Cod_Empresa']) ? (int)$record['Cod_Empresa'] : 0;
            $codLoja = isset($record['Cod_Loja']) ? (int)$record['Cod_Loja'] : 0;
            $tipoCorrespondente = isset($record['TIPO_CORRESPONDENTE']) ? str_replace("'", "''", $record['TIPO_CORRESPONDENTE']) : '';
            $tipoContrato = isset($record['TIPO_CONTRATO']) ? str_replace("'", "''", $record['TIPO_CONTRATO']) : '';
            
            error_log("insertLogEntries - Prepared values: ChaveLoja=$chaveLoja, NomeLoja=$nomeLoja, CodEmpresa=$codEmpresa, CodLoja=$codLoja");
            
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
            
            error_log("insertLogEntries - SQL Query: " . $sql);
            
            try {
                $result = $this->sql->insert($sql);
                error_log("insertLogEntries - Insert result for record #$index: " . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result) {
                    $insertCount++;
                    error_log("insertLogEntries - Insert count now: $insertCount");
                } else {
                    error_log("insertLogEntries - Insert failed for record #$index - SQL: $sql");
                }
            } catch (Exception $insertEx) {
                error_log("insertLogEntries - Insert exception for record #$index: " . $insertEx->getMessage());
                error_log("insertLogEntries - Failed SQL: " . $sql);
            }
        }
        
        error_log("insertLogEntries END - Total inserted: $insertCount out of " . count($records) . " records");
        return $insertCount > 0;
        
    } catch (Exception $e) {
        error_log("insertLogEntries - MAIN Exception: " . $e->getMessage());
        error_log("insertLogEntries - Exception trace: " . $e->getTraceAsString());
        return false;
    }
}Also add logging to the generateChaveLote method:public function generateChaveLote() {
    try {
        error_log("generateChaveLote START");
        
        $sql = "SELECT ISNULL(MAX(CHAVE_LOTE), 0) + 1 as NEXT_LOTE FROM PGTOCORSP.dbo.TB_WXKD_LOG";
        error_log("generateChaveLote - SQL: " . $sql);
        
        $result = $this->sql->select($sql);
        error_log("generateChaveLote - Query result: " . print_r($result, true));
        
        if (!empty($result) && isset($result[0]['NEXT_LOTE'])) {
            $nextLote = (int)$result[0]['NEXT_LOTE'];
            error_log("generateChaveLote - Next lote: $nextLote");
            return $nextLote;
        }
        
        error_log("generateChaveLote - Using default value: 1");
        return 1;
        
    } catch (Exception $e) {
        error_log("generateChaveLote - Exception: " . $e->getMessage());
        return 1;
    }
}And add logging to the controller's exportTXT method where it calls the log insert:// In the exportTXT method, replace the log insert section with:
if (!empty($recordsToLog)) {
    error_log("exportTXT - About to call insertLogEntries with " . count($recordsToLog) . " records and chaveLote: $chaveLote and filter: $filter");
    error_log("exportTXT - First record data: " . print_r($recordsToLog[0], true));
    
    $logResult = $this->model->insertLogEntries($recordsToLog, $chaveLote, $filter);
    
    error_log("exportTXT - insertLogEntries returned: " . ($logResult ? 'TRUE' : 'FALSE'));
}