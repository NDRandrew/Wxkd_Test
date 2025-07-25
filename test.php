Looking at your code, I can see several issues causing the updateWxkdFlag to fail. Here are the fixes:Fix 1: Controller - Remove Redundant Data FetchingIn exportTXT() method, replace the redundant data fetching:public function exportTXT() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    try {
        // Fix: Single data fetch based on filter
        if ($filter === 'historico') {
            $data = $this->getHistoricoFilteredData($selectedIds);
        } else {
            $data = $this->getFilteredData($filter, $selectedIds);
        }
        
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
            $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : (isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : ''), '"<>&') . '</cod_empresa>';
            $xml .= '<cod_empresa_historico>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa_historico>';
            $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
            $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : (isset($row['COD_LOJA']) ? $row['COD_LOJA'] : ''), '"<>&') . '</cod_loja>';
            $xml .= '<cod_loja_historico>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja_historico>';
            $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
            $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
            $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
            $xml .= '<AVANCADO>' . addcslashes(isset($row['AVANCADO']) ? $row['AVANCADO'] : '', '"<>&') . '</AVANCADO>';
            $xml .= '<PRESENCA>' . addcslashes(isset($row['PRESENCA']) ? $row['PRESENCA'] : '', '"<>&') . '</PRESENCA>';
            $xml .= '<UNIDADE_NEGOCIO>' . addcslashes(isset($row['UNIDADE_NEGOCIO']) ? $row['UNIDADE_NEGOCIO'] : '', '"<>&') . '</UNIDADE_NEGOCIO>';
            $xml .= '<ORGAO_PAGADOR>' . addcslashes(isset($row['ORGAO_PAGADOR']) ? $row['ORGAO_PAGADOR'] : '', '"<>&') . '</ORGAO_PAGADOR>';
            $xml .= '<data_conclusao>';
            $dataConclusao = isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '';
            $timeAndre = strtotime($dataConclusao);
            if ($timeAndre !== false && !empty($dataConclusao)) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $xml .= '—';
            }
            $xml .= '</data_conclusao>';
            $xml .= '</row>';
            
            $codEmpresa = (int) (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : (isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : 0));
            $codLoja = (int) (isset($row['Cod_Loja']) ? $row['Cod_Loja'] : (isset($row['COD_LOJA']) ? $row['COD_LOJA'] : 0));
            
            if ($codEmpresa > 0 && $codLoja > 0) {
                $recordsToUpdate[] = array(
                    'COD_EMPRESA' => $codEmpresa,
                    'COD_LOJA' => $codLoja
                );
            }
        }
        
        if (!empty($recordsToUpdate)) {
            // Only populate DATA_CONCLUSAO for non-historico data
            if ($filter !== 'historico') {
                $data = $this->model->populateDataConclusaoFromTable($data);
            }
    
            error_log("exportTXT - Sample record structure: " . print_r(array_keys($data[0]), true));
            if (isset($data[0]['DATA_CONCLUSAO'])) {
                error_log("exportTXT - First record DATA_CONCLUSAO: " . $data[0]['DATA_CONCLUSAO']);
            }
            
            $updateResult = $this->model->updateWxkdFlag($recordsToUpdate, $data, $chaveLote, $filter);
                        
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
}Fix 2: Model - Improved updateWxkdFlag MethodReplace the entire updateWxkdFlag method in your model:public function updateWxkdFlag($records, $fullData = array(), $chaveLote = 0, $filtro = 'cadastramento') {
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
        
        // First, update/insert WXKD_FLAG records
        foreach ($records as $index => $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                continue;
            }
            
            // Check if record exists
            $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                        WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
            
            $checkResult = $this->sql->select($checkSql);
            
            if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                $logs[] = "updateWxkdFlag - Record not found in tb_wxkd_flag, attempting to insert for record #$index";
                
                // Try to insert new record
                $insertSql = "INSERT INTO PGTOCORSP.dbo.tb_wxkd_flag (COD_EMPRESA, COD_LOJA, WXKD_FLAG) 
                             VALUES ($codEmpresa, $codLoja, 1)";
                
                $insertResult = $this->sql->insert($insertSql);
                
                if ($insertResult) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully inserted new WXKD_FLAG record for #$index";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to insert WXKD_FLAG record for #$index";
                }
            } else {
                // Update existing record
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
        }
        
        // Insert log records
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                
                // Handle both field name formats
                $chaveLoja = $this->getFieldValue($record, array('Chave_Loja', 'CHAVE_LOJA'));
                $nomeLoja = $this->getFieldValue($record, array('Nome_Loja', 'NOME_LOJA'));
                $codEmpresa = $this->getFieldValue($record, array('Cod_Empresa', 'COD_EMPRESA'));
                $codLoja = $this->getFieldValue($record, array('Cod_Loja', 'COD_LOJA'));
                
                if (empty($chaveLoja)) {
                    $logs[] = "updateWxkdFlag - Missing Chave_Loja in log record #$index";
                    continue;
                }
                
                if (empty($nomeLoja)) {
                    $logs[] = "updateWxkdFlag - Missing Nome_Loja in log record #$index";
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
                
                // Handle DATA_CONCLUSAO
                $dataConclusao = $this->getFieldValue($record, array('DATA_CONCLUSAO'));
                if (!empty($dataConclusao) && trim($dataConclusao) !== '') {
                    $dataConclusao = "'" . str_replace("'", "''", trim($dataConclusao)) . "'";
                } else {
                    $dataConclusao = "'" . $currentDateTime . "'";
                }
                
                // Handle DATA_SOLICITACAO
                $dataSolicitacao = $this->getFieldValue($record, array('DATA_SOLICITACAO'));
                if (!empty($dataSolicitacao) && trim($dataSolicitacao) !== '') {
                    $dataSolicitacao = "'" . str_replace("'", "''", trim($dataSolicitacao)) . "'";
                } else {
                    $dataSolicitacao = $dataConclusao;
                }
                
                // Handle monetary values
                $depDinheiro = $this->getMonetaryValueForLog($record, 'DEP_DINHEIRO');
                $depCheque = $this->getMonetaryValueForLog($record, 'DEP_CHEQUE');
                $recRetirada = $this->getMonetaryValueForLog($record, 'REC_RETIRADA');
                $saqueCheque = $this->getMonetaryValueForLog($record, 'SAQUE_CHEQUE');
                
                // Handle validation fields
                $segundaVia = $this->getValidationValue($record, array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO'));
                $holeriteInss = $this->getValidationValue($record, array('HOLERITE_INSS_VALID', 'HOLERITE_INSS'));
                $consultaInss = $this->getValidationValue($record, array('CONSULTA_INSS_VALID', 'CONS_INSS'));
                $provaVida = $this->getValidationValue($record, array('PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA'));
                
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
                
                try {
                    $logResult = $this->sql->insert($logSql);
                    
                    if ($logResult) {
                        $logInsertCount++;
                        $logs[] = "updateWxkdFlag - Log insert SUCCESS for record #$index";
                    } else {
                        $logs[] = "updateWxkdFlag - Log insert FAILED for record #$index";
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
}Fix 3: Add Missing Helper MethodAdd this missing helper method to your model:private function getMonetaryValueForLog($record, $type) {
    // For historico data, these might already be monetary values
    $directValue = $this->getFieldValue($record, array($type));
    if (!empty($directValue) && is_numeric($directValue)) {
        return (float)$directValue;
    }
    
    // Fall back to validation-based calculation
    return $this->getMonetaryValue($record, $type);
}These fixes address:Redundant data fetching causing data mismatchMissing records in tb_wxkd_flag by inserting new records when they don't existBetter field name compatibility for both regular and historico dataImproved error handling and debuggingThe main issue was that historico data often doesn't have corresponding records in the tb_wxkd_flag table, so the update was failing. Now it will insert new records when needed.