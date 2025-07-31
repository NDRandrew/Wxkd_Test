public function contractDateCheck($filter = 'cadastramento') {
    try {
        if ($filter === 'descadastramento') {
            // For descadastramento, check based on the solicitacao records
            $query = "SELECT DISTINCT
                        G.Chave_Loja
                    FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
                        JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
                        JOIN PGTOCORSP.dbo.PGTOCORSP_TB_TIPO_SOLICITACAO C ON A.COD_TIPO_PAGAMENTO=C.COD_SOLICITACAO
                        LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO G ON A.CHAVE_LOJA=G.CHAVE_LOJA
                        LEFT JOIN (
                            SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                            FROM (
                                SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                                    ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                                FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                                LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                                WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL
                            ) SELECIONADO
                            WHERE rn = 1
                        ) I ON G.COD_EMPRESA = I.KEY_EMPRESA
                        LEFT JOIN (
                            SELECT DISTINCT COD_EMPRESA, WXKD_FLAG_DES
                            FROM PGTOCORSP.dbo.tb_wxkd_flag
                        ) H ON G.COD_EMPRESA = H.COD_EMPRESA
                    WHERE 
                        B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 
                        AND A.DATA_PEDIDO>='20250701'
                        AND H.WXKD_FLAG_DES = 0
                        AND I.TIPO IS NOT NULL";
            
            $result = $this->sql->select($query);
            return $result;
        } else {
            // Original logic for cadastramento
            $query = "SELECT
                        A.Chave_Loja
                    FROM DATALAKE..DL_BRADESCO_EXPRESSO A
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B ON A.CHAVE_LOJA=B.CHAVE_LOJA
                        LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA  C ON C.CHAVE_LOJA=A.CHAVE_LOJA
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D ON D.CHAVE_LOJA=A.CHAVE_LOJA
                        LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO  FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON A.CHAVE_LOJA=E.CHAVE_LOJA_PARA
                        LEFT JOIN (
                            SELECT  
                                B.Cod_Empresa,
                                    MAX(CASE WHEN COD_SERVICO = 'D'  THEN 1 ELSE 0 END) AS 'DEP_DINHEIRO_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_CHEQUE_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS 'REC_RETIRADA_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS 'SAQUE_CHEQUE_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS 'SEGUNDA_VIA_CARTAO_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS 'CONSULTA_INSS_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS 'HOLERITE_INSS_VALID',
                                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS 'PROVA_DE_VIDA_VALID'
                            FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                            JOIN (
                                        SELECT Cod_Empresa, COD_SERVICO 
                                        FROM MESU.DBO.EMPRESAS_SERVICOS 
                                        GROUP BY COD_EMPRESA, COD_SERVICO
                                ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                            WHERE COD_SERVICO_BRAD IN ('DP50', 'DP51', 'DP52', 'DP53', 'PD50', 'PD51', 'PD52', 'PD53', 'RR55', 'RR56', 'RR57', 'RR58', 'RE55','RR50', 'RR51', 'RR52', 'RR53','PECA', 'CPBI', 'HOBI', 'VC23') 
                            GROUP BY B.Cod_Empresa
                        ) F ON A.Cod_Empresa=F.Cod_Empresa
                        LEFT JOIN (
                            SELECT 
                                A.KEY_EMPRESA,
                                A.DATA_CONTRATO,
                                A.COD_VERSAO,
                                C.TIPO,
                                C.MODELO
                            FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                            LEFT JOIN MESU.DBO.TB_VERSAO C 
                                ON A.COD_VERSAO = C.COD_VERSAO
                            WHERE A.COD_VERSAO IS NOT NULL 
                              AND C.TIPO IS NOT NULL 
                              AND C.MODELO IS NOT NULL
                        )G ON A.COD_EMPRESA = G.KEY_EMPRESA
                        LEFT JOIN ( 
                            SELECT DISTINCT A.COD_EMPRESA, A.WXKD_FLAG
                            FROM PGTOCORSP.dbo.tb_wxkd_flag A
                        ) H ON A.COD_EMPRESA = H.COD_EMPRESA 
                        LEFT JOIN (
                            SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                            FROM DATALAKE..DL_BRADESCO_EXPRESSO
                            WHERE BE_INAUGURADO=1
                            GROUP BY COD_EMPRESA
                        ) I ON A.COD_EMPRESA = I.COD_EMPRESA
                        LEFT JOIN (
                            SELECT 
                                A.CHAVE_LOJA,
                                A.COD_SOLICITACAO,
                                B.DATA_LOG
                            FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                            INNER JOIN (
                                SELECT 
                                    A.CHAVE_LOJA,
                                    MAX(B.DATA_LOG) AS MAX_DATA_LOG
                                FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                                INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
                                    ON A.COD_SOLICITACAO = B.COD_SOLICITACAO
                                GROUP BY A.CHAVE_LOJA
                            ) MaxLog
                                ON A.CHAVE_LOJA = MaxLog.CHAVE_LOJA
                            INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
                                ON A.COD_SOLICITACAO = B.COD_SOLICITACAO AND B.DATA_LOG = MaxLog.MAX_DATA_LOG
                        ) J ON A.CHAVE_LOJA = J.CHAVE_LOJA

                    WHERE (B.DT_CADASTRO>='20250601' OR C.DT_CADASTRO>='20250601' OR D.DT_CADASTRO>='20250601' OR E.DT_CADASTRO>='20250601') AND H.WXKD_FLAG = 0 AND
                    (COD_VERSAO IN (65,68,71,79,85,91,99,100,104,22,33,36,39,44,45,46,51,56,80))";
            
            $result = $this->sql->select($query);
            return $result;
        }
    } catch (Exception $e) {
        throw new Exception("Erro ao gerar XML: " . $e->getMessage());
    }
}







------------------------








public function index() {
    
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $cardData = $this->model->getCardData();
    $tableData = $this->model->getTableDataByFilter($filter);
    $contractData = $this->model->contractDateCheck($filter); // Pass filter here
    
    $contractChaves = array();
    foreach ($contractData as $item) {
        $contractChaves[] = $item['Chave_Loja'];
    }
    $contractChavesLookup  = array_flip($contractChaves);

    return array(
        'cardData' => $cardData, 
        'tableData' => $tableData, 
        'activeFilter' => $filter, 
        'contractChavesLookup' => $contractChavesLookup
    );
}






-------------------

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
        
        // Determine which flag to update based on filter
        $flagField = ($filtro === 'descadastramento') ? 'WXKD_FLAG_DES' : 'WXKD_FLAG';
        $logs[] = "updateWxkdFlag - Using flag field: $flagField for filter: $filtro";
        
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
                $logs[] = "updateWxkdFlag - Record not found in tb_wxkd_flag, attempting to insert for record #$index";
                
                // Initialize both flags when inserting new record
                $insertSql = "INSERT INTO PGTOCORSP.dbo.tb_wxkd_flag (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES) 
                            VALUES ($codEmpresa, $codLoja, " . 
                            ($filtro === 'descadastramento' ? '0, 1' : '1, 0') . ")";
                
                $insertResult = $this->sql->insert($insertSql);
                
                if ($insertResult) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully inserted new $flagField record for #$index";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to insert $flagField record for #$index";
                }
            } else {
                $sql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                        SET $flagField = 1 
                        WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $result = $this->sql->update($sql);
                
                if ($result) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully updated $flagField for record #$index";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to update $flagField for record #$index";
                }
            }
        }
        
        // Rest of the method remains the same for logging...
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                
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
                
                $dataConclusao = $this->getFieldValue($record, array('DATA_CONCLUSAO'));
                if (!empty($dataConclusao) && trim($dataConclusao) !== '') {
                    $dataConclusao = "'" . str_replace("'", "''", trim($dataConclusao)) . "'";
                } else {
                    $dataConclusao = "'" . $currentDateTime . "'";
                }
                
                $dataSolicitacao = $this->getFieldValue($record, array('DATA_SOLICITACAO'));
                if (!empty($dataSolicitacao) && trim($dataSolicitacao) !== '') {
                    $dataSolicitacao = "'" . str_replace("'", "''", trim($dataSolicitacao)) . "'";
                } else {
                    $dataSolicitacao = $dataConclusao;
                }
                
                $depDinheiro = $this->getMonetaryValueForLog($record, 'DEP_DINHEIRO');
                $depCheque = $this->getMonetaryValueForLog($record, 'DEP_CHEQUE');
                $recRetirada = $this->getMonetaryValueForLog($record, 'REC_RETIRADA');
                $saqueCheque = $this->getMonetaryValueForLog($record, 'SAQUE_CHEQUE');
                
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
}








-----------------



public function exportTXT() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    try {
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
        
        // For historico, get the original filter from the FILTRO field
        $actualFilter = $filter;
        if ($filter === 'historico' && !empty($data)) {
            $actualFilter = isset($data[0]['FILTRO']) ? $data[0]['FILTRO'] : 'cadastramento';
            // Treat 'all' as 'cadastramento'
            if ($actualFilter === 'all') {
                $actualFilter = 'cadastramento';
            }
        }
        
        $invalidRecords = $this->validateRecordsForTXTExport($data, $actualFilter);
        
        if (!empty($invalidRecords)) {
            $this->outputValidationError($invalidRecords);
            return;
        }
        
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
            $xml .= '<filtro_original>' . addcslashes($actualFilter, '"<>&') . '</filtro_original>';
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
            if ($filter !== 'historico') {
                $data = $this->model->populateDataConclusaoFromTable($data);
            }
    
            error_log("exportTXT - Sample record structure: " . print_r(array_keys($data[0]), true));
            if (isset($data[0]['DATA_CONCLUSAO'])) {
                error_log("exportTXT - First record DATA_CONCLUSAO: " . $data[0]['DATA_CONCLUSAO']);
            }
            
            // Use the actual filter for updating flags and logging
            $updateResult = $this->model->updateWxkdFlag($recordsToUpdate, $data, $chaveLote, $actualFilter);
                        
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
}

----------------------


private function validateRecordsForTXTExport($data, $filter = 'cadastramento') {
    $invalidRecords = array();
    $cutoff = Wxkd_Config::getCutoffTimestamp();
    
    foreach ($data as $row) {
        $activeTypes = $this->getActiveTypes($row, $cutoff);
        $codEmpresa = $this->getEmpresaCode($row);
        
        if (empty($codEmpresa) || empty($activeTypes)) {
            continue;
        }
        
        $isValid = true;
        $errorMessage = '';
        
        foreach ($activeTypes as $type) {
            if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
                $basicValidation = $this->checkBasicValidations($row);
                if ($basicValidation !== true) {
                    $isValid = false;
                    $errorMessage = 'Tipo ' . $type . ' - ' . $basicValidation;
                    break;
                }
            } elseif ($type === 'OP') {
                $opValidation = $this->checkOPValidations($row);
                if ($opValidation !== true) {
                    $isValid = false;
                    $errorMessage = $opValidation;
                    break;
                }
            }
        }
        
        // For descadastramento, add additional validation if needed
        if ($filter === 'descadastramento' && $isValid) {
            // Add any descadastramento-specific validation here
            // For now, keep same validation rules
        }
        
        if (!$isValid) {
            $invalidRecords[] = array(
                'cod_empresa' => $codEmpresa,
                'error' => $errorMessage
            );
        }
    }
    
    return $invalidRecords;
}




-----------------------



function extractTXTFromXML(xmlDoc) {
    let txtContent = '';
    const rows = xmlDoc.getElementsByTagName('row');
    const currentFilter = getCurrentFilter();

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        let empresa = getXMLNodeValue(row, 'cod_empresa');
        let codigoLoja = getXMLNodeValue(row, 'cod_loja');
        
        // For historico, check the original filter
        let actualFilter = currentFilter;
        if (currentFilter === 'historico') {
            actualFilter = getXMLNodeValue(row, 'filtro_original') || 'cadastramento';
            if (actualFilter === 'all') {
                actualFilter = 'cadastramento';
            }
        }

        if (!codigoLoja) {
            codigoLoja = getXMLNodeValue(row, 'cod_loja_historico');
        }

        if (!empresa) {
            empresa = getXMLNodeValue(row, 'cod_empresa_historico');
        }

        const tipoCorrespondente = getTipoCorrespondenteByDataConclusao(row);
        const contrato = getXMLNodeValue(row, 'tipo_contrato');

        console.log(`Processing row ${i}: Empresa=${empresa}, Loja=${codigoLoja}, Tipo=${tipoCorrespondente}, Contrato=${contrato}, ActualFilter=${actualFilter}`);

        if (actualFilter === 'descadastramento') {
            // DESCADASTRAMENTO FORMAT - You can modify this section for different format
            // For now, keeping same structure but you can change the content generation
            const tipoMapping = {
                'AV': 'AVANCADO',
                'PR': 'PRESENCA', 
                'UN': 'UNIDADE_NEGOCIO',
                'OP': 'ORGAO_PAGADOR'
            };
            
            const tipoCompleto = Object.keys(tipoMapping).find(key => tipoMapping[key] === tipoCorrespondente) || tipoCorrespondente;
            console.log('Descadastramento - Tipo completo: ' + tipoCompleto);
            
            if (['AV', 'PR', 'UN'].includes(tipoCompleto) || ['AVANCADO', 'PRESENCA', 'UNIDADE_NEGOCIO'].includes(tipoCorrespondente)) {
                let limits;
                if (tipoCompleto === 'AV' || tipoCorrespondente === 'AVANCADO' || tipoCompleto === 'UN' || tipoCorrespondente === 'UNIDADE_NEGOCIO') {
                    limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                } else if (tipoCompleto === 'PR' || tipoCorrespondente === 'PRESENCA' || tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                    limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                } else {
                    limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                }

                // SAME FORMAT FOR NOW - MODIFY THIS SECTION FOR DIFFERENT DESCADASTRAMENTO FORMAT
                txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, 2, 0) + '\r\n';
                txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, 2, 0) + '\r\n';
                txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, 2, 0) + '\r\n';
                txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 2, 0) + '\r\n';
                
            } else if (tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };

                const version = extractVersionFromContract(contrato);
                if (version !== null && version >= 8.1 && version <= 10.1) {
                    // SAME FORMAT FOR NOW - MODIFY THIS SECTION FOR DIFFERENT DESCADASTRAMENTO FORMAT
                    txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
                } else if (version !== null && version > 10.1) {
                    // SAME FORMAT FOR NOW - MODIFY THIS SECTION FOR DIFFERENT DESCADASTRAMENTO FORMAT
                    txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
                }
            }
        } else {
            // CADASTRAMENTO/ALL/HISTORICO FORMAT (original logic)
            if (actualFilter === 'cadastramento' || actualFilter === 'all' || currentFilter === 'historico') {
                const tipoMapping = {
                    'AV': 'AVANCADO',
                    'PR': 'PRESENCA', 
                    'UN': 'UNIDADE_NEGOCIO',
                    'OP': 'ORGAO_PAGADOR'
                };
                
                const tipoCompleto = Object.keys(tipoMapping).find(key => tipoMapping[key] === tipoCorrespondente) || tipoCorrespondente;
                console.log('Cadastramento - Tipo completo: ' + tipoCompleto);
                
                if (['AV', 'PR', 'UN'].includes(tipoCompleto) || ['AVANCADO', 'PRESENCA', 'UNIDADE_NEGOCIO'].includes(tipoCorrespondente)) {
                    let limits;
                    if (tipoCompleto === 'AV' || tipoCorrespondente === 'AVANCADO' || tipoCompleto === 'UN' || tipoCorrespondente === 'UNIDADE_NEGOCIO') {
                        limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                    } else if (tipoCompleto === 'PR' || tipoCorrespondente === 'PRESENCA' || tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                        limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                    } else {
                        limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                    }

                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, 2, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, 2, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, 2, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 2, 0) + '\r\n';
                    
                } else if (tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                    limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };

                    const version = extractVersionFromContract(contrato);
                    if (version !== null && version >= 8.1 && version <= 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
                    } else if (version !== null && version > 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
                    }
                }
            } else {
                const txtLine = formatToTXTLine(empresa, codigoLoja);
                if (txtLine && txtLine.length === 101) {
                    txtContent += txtLine + '\r\n';
                }
            }
        }
    }

    return txtContent;
}




------------------------




public function checkContrato($filter) {
    $contractData = $this->model->contractDateCheck($filter); // Pass filter here too

    $contractChaves = array();
    foreach ($contractData as $item) {
        $contractChaves[] = $item['Chave_Loja'];
    }

    $contractChavesLookup = array_flip($contractChaves);

    $tableData = $this->model->getTableDataByFilter($filter);

    return array(
        'tableData' => $tableData,
        'contractChavesLookup' => $contractChavesLookup
    );
}
