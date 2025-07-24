<?php
// Replace the updateWxkdFlag method in your Wxkd_DashboardModel.php with this fixed version

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
                $dataContrato = isset($record['DATA_CONTRATO']) ? "'" . str_replace("'", "''", $record['DATA_CONTRATO']) . "'" : 'NULL';
                
                // FIXED: Properly handle DATA_CONCLUSAO from the main table
                $dataConclusao = 'NULL';
                if (isset($record['DATA_CONCLUSAO']) && !empty($record['DATA_CONCLUSAO']) && trim($record['DATA_CONCLUSAO']) !== '') {
                    $dataConclusao = "'" . str_replace("'", "''", trim($record['DATA_CONCLUSAO'])) . "'";
                    $logs[] = "updateWxkdFlag - Using DATA_CONCLUSAO from record: {$record['DATA_CONCLUSAO']}";
                } else {
                    // Fallback: Generate DATA_CONCLUSAO as current timestamp if not provided
                    $dataConclusao = "'" . $currentDateTime . "'";
                    $logs[] = "updateWxkdFlag - DATA_CONCLUSAO not found in record, using current timestamp: $currentDateTime";
                }
                
                // Handle DATA_SOLICITACAO
                $dataSolicitacao = 'NULL';
                if (isset($record['DATA_SOLICITACAO']) && !empty($record['DATA_SOLICITACAO']) && trim($record['DATA_SOLICITACAO']) !== '') {
                    $dataSolicitacao = "'" . str_replace("'", "''", trim($record['DATA_SOLICITACAO'])) . "'";
                    $logs[] = "updateWxkdFlag - Using DATA_SOLICITACAO from record: {$record['DATA_SOLICITACAO']}";
                } else {
                    // Fallback: Use DATA_CONCLUSAO if DATA_SOLICITACAO is not available
                    $dataSolicitacao = $dataConclusao;
                    $logs[] = "updateWxkdFlag - DATA_SOLICITACAO not found, using DATA_CONCLUSAO as fallback";
                }
                
                $depDinheiro = $this->getMonetaryValue($record, 'DEP_DINHEIRO');
                $depCheque = $this->getMonetaryValue($record, 'DEP_CHEQUE');
                $recRetirada = $this->getMonetaryValue($record, 'REC_RETIRADA');
                $saqueCheque = $this->getMonetaryValue($record, 'SAQUE_CHEQUE');
                
                $segundaVia = isset($record['SEGUNDA_VIA_CARTAO_VALID']) && $record['SEGUNDA_VIA_CARTAO_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                $holeriteInss = isset($record['HOLERITE_INSS_VALID']) && $record['HOLERITE_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                $consultaInss = isset($record['CONSULTA_INSS_VALID']) && $record['CONSULTA_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                $provaVida = isset($record['PROVA_DE_VIDA_VALID']) && $record['PROVA_DE_VIDA_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                
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

// Helper method to check if DATA_CONCLUSAO should be populated from table data
public function populateDataConclusaoFromTable($tableData) {
    // This method can be called before updateWxkdFlag to ensure DATA_CONCLUSAO is populated
    foreach ($tableData as &$record) {
        if (!isset($record['DATA_CONCLUSAO']) || empty($record['DATA_CONCLUSAO'])) {
            // Check if we can derive DATA_CONCLUSAO from other date fields
            $fields = array('AVANCADO', 'ORGAO_PAGADOR', 'PRESENCA', 'UNIDADE_NEGOCIO');
            $cutoff = mktime(0, 0, 0, 6, 1, 2025);
            $latestDate = null;
            
            foreach ($fields as $field) {
                if (isset($record[$field]) && !empty($record[$field])) {
                    $raw = trim($record[$field]);
                    $parts = explode('/', $raw);
                    if (count($parts) == 3) {
                        $day = (int)$parts[0];
                        $month = (int)$parts[1];
                        $year = (int)$parts[2];
                        
                        if (checkdate($month, $day, $year)) {
                            $timestamp = mktime(0, 0, 0, $month, $day, $year);
                            if ($timestamp > $cutoff) {
                                if ($latestDate === null || $timestamp > $latestDate) {
                                    $latestDate = $timestamp;
                                }
                            }
                        }
                    }
                }
            }
            
            if ($latestDate !== null) {
                $record['DATA_CONCLUSAO'] = date('M j Y g:iA', $latestDate);
            } else {
                $record['DATA_CONCLUSAO'] = date('M j Y g:iA'); // Current date as fallback
            }
        }
    }
    
    return $tableData;
}
?>