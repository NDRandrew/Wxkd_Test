public function exportTXT() {
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
            
            // Insert log entries - ALWAYS try to log regardless of flag update
            if (!empty($recordsToLog)) {
                $logResult = $this->model->insertLogEntries($recordsToLog, $chaveLote);
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
}2. Fix Model (TestM.txt) - Replace/update these methods:public function insertLogEntries($records, $chaveLote) {
    try {
        if (empty($records)) {
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            $tipoCorrespondente = $this->generateTipoCorrespondente($record);
            
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, 
                     DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, SEGUNDA_VIA_CARTAO, 
                     HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO) 
                    VALUES 
                    ($chaveLote, '$currentDateTime', " . (int)$record['Chave_Loja'] . ", 
                     '" . addslashes($record['Nome_Loja']) . "', " . (int)$record['Cod_Empresa'] . ", 
                     " . (int)$record['Cod_Loja'] . ", '" . addslashes($tipoCorrespondente) . "', 
                     " . ($record['DATA_CONCLUSAO'] ? "'" . addslashes($record['DATA_CONCLUSAO']) . "'" : 'NULL') . ", 
                     " . ($record['DATA_SOLICITACAO'] ? "'" . addslashes($record['DATA_SOLICITACAO']) . "'" : 'NULL') . ", 
                     " . $this->getMonetaryValue($record, 'DEP_DINHEIRO') . ", 
                     " . $this->getMonetaryValue($record, 'DEP_CHEQUE') . ", 
                     " . $this->getMonetaryValue($record, 'REC_RETIRADA') . ", 
                     " . $this->getMonetaryValue($record, 'SAQUE_CHEQUE') . ", 
                     '" . ($record['SEGUNDA_VIA_CARTAO_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['HOLERITE_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['CONSULTA_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['PROVA_DE_VIDA_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     " . ($record['DATA_CONTRATO'] ? "'" . addslashes($record['DATA_CONTRATO']) . "'" : 'NULL') . ", 
                     " . ($record['TIPO_CONTRATO'] ? "'" . addslashes($record['TIPO_CONTRATO']) . "'" : 'NULL') . ")";
            
            $result = $this->sql->insert($sql);
            if ($result) {
                $insertCount++;
            }
        }
        
        return $insertCount > 0;
        
    } catch (Exception $e) {
        error_log("insertLogEntries - Exception: " . $e->getMessage());
        return false;
    }
}

// Update the getTableDataByFilter method - replace the historico case:
case 'historico':
    $query = "SELECT 
                CHAVE_LOTE,
                DATA_LOG,
                COUNT(*) as TOTAL_REGISTROS,
                MIN(NOME_LOJA) as PRIMEIRO_NOME_LOJA,
                STUFF((SELECT ', ' + CAST(CHAVE_LOJA AS VARCHAR) 
                       FROM PGTOCORSP.dbo.TB_WXKD_LOG t2 
                       WHERE t2.CHAVE_LOTE = t1.CHAVE_LOTE 
                       FOR XML PATH('')), 1, 2, '') as CHAVES_LOJAS
              FROM PGTOCORSP.dbo.TB_WXKD_LOG t1 
              GROUP BY CHAVE_LOTE, DATA_LOG 
              ORDER BY DATA_LOG DESC";
    break;3. Fix the Controller ajaxGetTableData() method:public function ajaxGetTableData() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    try {
        $tableData = $this->model->getTableDataByFilter($filter);
        $cardData = $this->model->getCardData();
        
        $xml = '<response>';
        $xml .= '<success>true</success>';
        
        if (is_array($cardData)) {
            $xml .= '<cardData>';
            foreach ($cardData as $key => $value) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $xml .= '</cardData>';
        }
        
        $xml .= '<tableData>';
        if (is_array($tableData) && count($tableData) > 0) {
            foreach ($tableData as $row) {
                $xml .= '<row>';
                foreach ($row as $key => $value) {
                    $xml .= '<' . $key . '>' . addcslashes($value, '"<>&') . '</' . $key . '>';
                }
                $xml .= '</row>';
            }
        }
        $xml .= '</tableData>';
        $xml .= '</response>';
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<error>' . addcslashes($e->getMessage(), '"<>&') . '</error>';
        $xml .= '</response>';
    }

    echo $xml;
    exit;
}