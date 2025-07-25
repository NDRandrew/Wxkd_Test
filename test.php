public function updateWxkdFlag($records, $fullData = array(), $chaveLote = 0, $filtro = 'cadastramento') {
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
        
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                $logs[] = "updateWxkdFlag - Record keys: " . implode(', ', array_keys($record));
                
                // Debug the DATA_CONCLUSAO field specifically
                $logs[] = "updateWxkdFlag - DATA_CONCLUSAO debug: " . (isset($record['DATA_CONCLUSAO']) ? "'{$record['DATA_CONCLUSAO']}'" : 'NOT SET');
                
                // Handle both field name formats (mixed case and uppercase)
                $chaveLoja = $this->getFieldValue($record, array('Chave_Loja', 'CHAVE_LOJA'));
                $nomeLoja = $this->getFieldValue($record, array('Nome_Loja', 'NOME_LOJA'));
                $codEmpresa = $this->getFieldValue($record, array('Cod_Empresa', 'COD_EMPRESA'));
                $codLoja = $this->getFieldValue($record, array('Cod_Loja', 'COD_LOJA'));
                
                if (empty($chaveLoja)) {
                    $logs[] = "updateWxkdFlag - Missing Chave_Loja/CHAVE_LOJA in log record #$index";
                    continue;
                }
                
                if (empty($nomeLoja)) {
                    $logs[] = "updateWxkdFlag - Missing Nome_Loja/NOME_LOJA in log record #$index";
                    continue;
                }
                
                $chaveLoja = (int)$chaveLoja;
                $nomeLoja = str_replace("'", "''", $nomeLoja);
                $codEmpresa = (int)$codEmpresa;
                $codLoja = (int)$codLoja;
                
                $tipoCorrespondente = $this->getFieldValue($record, array('TIPO_CORRESPONDENTE'));
                $tipoCorrespondente = str_replace("'", "''", $tipoCorrespondente);
                
                $tipoContrato = $this->getFieldValue($record, array('TIPO_CONTRATO'));
                $tipoContrato = str_replace("'", "''", $tipoContrato);
                
                $dataContrato = $this->getFieldValue($record, array('DATA_CONTRATO'));
                $dataContrato = !empty($dataContrato) ? "'" . str_replace("'", "''", $dataContrato) . "'" : 'NULL';
                
                // Handle DATA_CONCLUSAO with multiple possible field names
                $dataConclusao = $this->getFieldValue($record, array('DATA_CONCLUSAO'));
                if (!empty($dataConclusao) && trim($dataConclusao) !== '') {
                    $dataConclusao = "'" . str_replace("'", "''", trim($dataConclusao)) . "'";
                    $logs[] = "updateWxkdFlag - Using DATA_CONCLUSAO from record: $dataConclusao";
                } else {
                    $dataConclusao = "'" . $currentDateTime . "'";
                    $logs[] = "updateWxkdFlag - DATA_CONCLUSAO not found in record, using current timestamp: $currentDateTime";
                }
                
                // Handle DATA_SOLICITACAO with multiple possible field names
                $dataSolicitacao = $this->getFieldValue($record, array('DATA_SOLICITACAO'));
                if (!empty($dataSolicitacao) && trim($dataSolicitacao) !== '') {
                    $dataSolicitacao = "'" . str_replace("'", "''", trim($dataSolicitacao)) . "'";
                    $logs[] = "updateWxkdFlag - Using DATA_SOLICITACAO from record: $dataSolicitacao";
                } else {
                    $dataSolicitacao = $dataConclusao;
                    $logs[] = "updateWxkdFlag - DATA_SOLICITACAO not found, using DATA_CONCLUSAO as fallback";
                }
                
                // Handle monetary values - check for both field formats
                $depDinheiro = $this->getMonetaryValueForLog($record, 'DEP_DINHEIRO');
                $depCheque = $this->getMonetaryValueForLog($record, 'DEP_CHEQUE');
                $recRetirada = $this->getMonetaryValueForLog($record, 'REC_RETIRADA');
                $saqueCheque = $this->getMonetaryValueForLog($record, 'SAQUE_CHEQUE');
                
                // Handle validation fields
                $segundaVia = $this->getValidationValue($record, array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO'));
                $holeriteInss = $this->getValidationValue($record, array('HOLERITE_INSS_VALID', 'HOLERITE_INSS'));
                $consultaInss = $this->getValidationValue($record, array('CONSULTA_INSS_VALID', 'CONS_INSS'));
                $provaVida = $this->getValidationValue($record, array('PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA'));
                
                $logs[] = "updateWxkdFlag - Final values - DataConclusao: $dataConclusao, DataSolicitacao: $dataSolicitacao";
                
                $logSql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                        (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                        TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                        SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                        VALUES 
                        ($chaveLote, 
                        '$currentDateTime', 
                        $chaveLoja, 
                        '$nomeLoja', 
                        $codEmpresa, 
                        $codLoja, 
                        '$tipoCorrespondente',
                        $dataConclusao,
                        $dataSolicitacao,
                        $depDinheiro, $depCheque, $recRetirada, $saqueCheque, 
                        '$segundaVia', '$holeriteInss', '$consultaInss', '$provaVida', 
                        $dataContrato, 
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

// Helper method to get field value from multiple possible field names
private function getFieldValue($record, $fieldNames) {
    foreach ($fieldNames as $fieldName) {
        if (isset($record[$fieldName]) && !empty($record[$fieldName])) {
            return $record[$fieldName];
        }
    }
    return '';
}

// Helper method for monetary values in logs
private function getMonetaryValueForLog($record, $type) {
    // For historico data, these might already be monetary values
    $directValue = $this->getFieldValue($record, array($type));
    if (!empty($directValue) && is_numeric($directValue)) {
        return (float)$directValue;
    }
    
    // Fall back to validation-based calculation
    return $this->getMonetaryValue($record, $type);
}

// Helper method for validation values
private function getValidationValue($record, $fieldNames) {
    foreach ($fieldNames as $fieldName) {
        if (isset($record[$fieldName])) {
            $value = $record[$fieldName];
            // If it's already a string like "Apto" or "Nao Apto", return it
            if (is_string($value) && (strpos($value, 'Apto') !== false || strpos($value, 'apto') !== false)) {
                return $value;
            }
            // If it's a numeric validation flag
            if (is_numeric($value)) {
                return $value == 1 ? 'Apto' : 'Nao Apto';
            }
        }
    }
    return 'Nao Apto';
}