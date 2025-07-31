public function getActualTipoCorrespondente($chaveLojas) {
    try {
        if (empty($chaveLojas)) {
            return array();
        }
        
        // Convert array to comma-separated string for SQL IN clause
        $chaveLojasList = implode(',', array_map('intval', $chaveLojas));
        
        $query = "SELECT 
                    A.CHAVE_LOJA
                    ,CONVERT(VARCHAR,B.DT_CADASTRO,103) AVANCADO
                    ,CONVERT(VARCHAR,C.DT_CADASTRO,103) PRESENCA
                    ,CONVERT(VARCHAR,D.DT_CADASTRO,103) UNIDADE_NEGOCIO
                    ,CONVERT(VARCHAR,E.DT_CADASTRO,103) ORGAO_PAGADOR
                    ,SUBSTRING(CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
                     CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
                     CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
                     CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,3,999) TIPO_COMPLETO
                FROM DATALAKE..DL_BRADESCO_EXPRESSO A
                    LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B ON A.CHAVE_LOJA=B.CHAVE_LOJA
                    LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA C ON C.CHAVE_LOJA=A.CHAVE_LOJA
                    LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D ON D.CHAVE_LOJA=A.CHAVE_LOJA
                    LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON A.CHAVE_LOJA=E.CHAVE_LOJA_PARA
                WHERE A.CHAVE_LOJA IN ($chaveLojasList)
                    AND (B.DT_CADASTRO IS NOT NULL OR C.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL)";
        
        $result = $this->sql->select($query);
        
        // Convert to associative array keyed by CHAVE_LOJA for faster lookup
        $actualTipos = array();
        if (!empty($result)) {
            foreach ($result as $row) {
                $actualTipos[$row['CHAVE_LOJA']] = array(
                    'AVANCADO' => $row['AVANCADO'],
                    'PRESENCA' => $row['PRESENCA'], 
                    'UNIDADE_NEGOCIO' => $row['UNIDADE_NEGOCIO'],
                    'ORGAO_PAGADOR' => $row['ORGAO_PAGADOR'],
                    'TIPO_COMPLETO' => $row['TIPO_COMPLETO']
                );
            }
        }
        
        return $actualTipos;
        
    } catch (Exception $e) {
        error_log("getActualTipoCorrespondente - Exception: " . $e->getMessage());
        return array();
    }
}


--------------------

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
        
        // NEW: Get actual TIPO_CORRESPONDENTE data for descadastramento
        $actualTipoData = array();
        if ($actualFilter === 'descadastramento') {
            $chaveLojas = array();
            foreach ($data as $row) {
                $chaveLoja = isset($row['CHAVE_LOJA']) ? $row['CHAVE_LOJA'] : '';
                if (!empty($chaveLoja)) {
                    $chaveLojas[] = $chaveLoja;
                }
            }
            
            if (!empty($chaveLojas)) {
                $actualTipoData = $this->model->getActualTipoCorrespondente($chaveLojas);
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
            
            // NEW: Add descadastramento-specific logic
            if ($actualFilter === 'descadastramento') {
                $chaveLoja = isset($row['CHAVE_LOJA']) ? $row['CHAVE_LOJA'] : '';
                $descadastroTipo = isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '';
                
                // Determine which TXT format to use
                $txtExportType = $this->determineDescadastroTXTType($chaveLoja, $descadastroTipo, $actualTipoData);
                
                $xml .= '<descadastro_txt_type>' . addcslashes($txtExportType, '"<>&') . '</descadastro_txt_type>';
                $xml .= '<descadastro_original_tipo>' . addcslashes($descadastroTipo, '"<>&') . '</descadastro_original_tipo>';
                
                // Add actual registration data if available
                if (isset($actualTipoData[$chaveLoja])) {
                    $actualData = $actualTipoData[$chaveLoja];
                    $xml .= '<actual_avancado>' . addcslashes($actualData['AVANCADO'], '"<>&') . '</actual_avancado>';
                    $xml .= '<actual_presenca>' . addcslashes($actualData['PRESENCA'], '"<>&') . '</actual_presenca>';
                    $xml .= '<actual_unidade_negocio>' . addcslashes($actualData['UNIDADE_NEGOCIO'], '"<>&') . '</actual_unidade_negocio>';
                    $xml .= '<actual_orgao_pagador>' . addcslashes($actualData['ORGAO_PAGADOR'], '"<>&') . '</actual_orgao_pagador>';
                    $xml .= '<actual_tipo_completo>' . addcslashes($actualData['TIPO_COMPLETO'], '"<>&') . '</actual_tipo_completo>';
                }
            }
            
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

// NEW: Helper method to determine TXT export type for descadastramento
private function determineDescadastroTXTType($chaveLoja, $descadastroTipo, $actualTipoData) {
    // If no actual data found, assume descadastramento tipo only
    if (!isset($actualTipoData[$chaveLoja])) {
        return 'ONLY_' . strtoupper($descadastroTipo);
    }
    
    $actualData = $actualTipoData[$chaveLoja];
    $descadastroTipoUpper = strtoupper($descadastroTipo);
    
    // Check for additional tipos based on hierarchy: AVANCADO/UNIDADE_NEGOCIO > ORGAO_PAGADOR/PRESENCA
    $hasAvancado = !empty($actualData['AVANCADO']);
    $hasUnidadeNegocio = !empty($actualData['UNIDADE_NEGOCIO']);
    $hasOrgaoPagador = !empty($actualData['ORGAO_PAGADOR']);  
    $hasPresenca = !empty($actualData['PRESENCA']);
    
    // Priority 1: AVANCADO/UNIDADE_NEGOCIO
    if ($hasAvancado && $descadastroTipoUpper !== 'AVANCADO') {
        return 'ADDITIONAL_AVANCADO';
    }
    if ($hasUnidadeNegocio && $descadastroTipoUpper !== 'UNIDADE_NEGOCIO') {
        return 'ADDITIONAL_UNIDADE_NEGOCIO';
    }
    
    // Priority 2: ORGAO_PAGADOR/PRESENCA
    if ($hasOrgaoPagador && $descadastroTipoUpper !== 'ORGAO_PAGADOR' && $descadastroTipoUpper !== 'ORG_PAGADOR') {
        return 'ADDITIONAL_ORGAO_PAGADOR';
    }
    if ($hasPresenca && $descadastroTipoUpper !== 'PRESENCA') {
        return 'ADDITIONAL_PRESENCA';
    }
    
    // No additional tipos found, return "only" type
    return 'ONLY_' . $descadastroTipoUpper;
}


-------------------

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
            // NEW DESCADASTRAMENTO LOGIC WITH COMPLEX RULES
            const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
            const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
            
            console.log(`Descadastramento - TxtType: ${descadastroTxtType}, OriginalTipo: ${descadastroOriginalTipo}`);
            
            // Handle different export types based on the hierarchy and additional tipos
            if (descadastroTxtType.startsWith('ADDITIONAL_')) {
                // Store has additional tipos - export based on the additional tipo
                const additionalTipo = descadastroTxtType.replace('ADDITIONAL_', '');
                
                if (additionalTipo === 'AVANCADO') {
                    // PLACEHOLDER: Export TXT for additional AVANCADO
                    console.log('Exporting TXT for ADDITIONAL AVANCADO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ADDITIONAL_AVANCADO**\r\n';
                    
                } else if (additionalTipo === 'UNIDADE_NEGOCIO') {
                    // PLACEHOLDER: Export TXT for additional UNIDADE_NEGOCIO
                    console.log('Exporting TXT for ADDITIONAL UNIDADE_NEGOCIO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ADDITIONAL_UNIDADE_NEGOCIO**\r\n';
                    
                } else if (additionalTipo === 'ORGAO_PAGADOR') {
                    // PLACEHOLDER: Export TXT for additional ORGAO_PAGADOR
                    console.log('Exporting TXT for ADDITIONAL ORGAO_PAGADOR');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ADDITIONAL_ORGAO_PAGADOR**\r\n';
                    
                } else if (additionalTipo === 'PRESENCA') {
                    // PLACEHOLDER: Export TXT for additional PRESENCA
                    console.log('Exporting TXT for ADDITIONAL PRESENCA');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ADDITIONAL_PRESENCA**\r\n';
                }
                
            } else if (descadastroTxtType.startsWith('ONLY_')) {
                // Store has ONLY the tipo shown in descadastramento - export specific format
                const onlyTipo = descadastroTxtType.replace('ONLY_', '');
                
                if (onlyTipo === 'AVANCADO') {
                    // PLACEHOLDER: Export TXT for ONLY AVANCADO
                    console.log('Exporting TXT for ONLY AVANCADO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_AVANCADO**\r\n';
                    
                } else if (onlyTipo === 'UNIDADE_NEGOCIO') {
                    // PLACEHOLDER: Export TXT for ONLY UNIDADE_NEGOCIO
                    console.log('Exporting TXT for ONLY UNIDADE_NEGOCIO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_UNIDADE_NEGOCIO**\r\n';
                    
                } else if (onlyTipo === 'ORGAO_PAGADOR' || onlyTipo === 'ORG_PAGADOR') {
                    // PLACEHOLDER: Export TXT for ONLY ORGAO_PAGADOR
                    console.log('Exporting TXT for ONLY ORGAO_PAGADOR');
                    
                    // Check contract version for OP-specific logic
                    const version = extractVersionFromContract(contrato);
                    if (version !== null && version >= 8.1 && version <= 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_OP_8.1_TO_10.1**\r\n';
                    } else if (version !== null && version > 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_OP_ABOVE_10.1**\r\n';
                    } else {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_OP_DEFAULT**\r\n';
                    }
                    
                } else if (onlyTipo === 'PRESENCA') {
                    // PLACEHOLDER: Export TXT for ONLY PRESENCA
                    console.log('Exporting TXT for ONLY PRESENCA');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ONLY_PRESENCA**\r\n';
                }
            }
            
        } else {
            // CADASTRAMENTO/ALL/HISTORICO FORMAT (original logic unchanged)
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


---------------------

// Add this debug function to TestJ - useful for troubleshooting descadastramento logic
window.debugDescadastroLogic = function(xmlDoc) {
    console.log('=== DESCADASTRAMENTO LOGIC DEBUG ===');
    
    const rows = xmlDoc.getElementsByTagName('row');
    for (let i = 0; i < Math.min(rows.length, 5); i++) { // Show first 5 rows
        const row = rows[i];
        
        const chaveLoja = getXMLNodeValue(row, 'cod_loja') || getXMLNodeValue(row, 'cod_loja_historico');
        const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
        const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
        const actualTipoCompleto = getXMLNodeValue(row, 'actual_tipo_completo');
        
        console.log(`Row ${i}: ChaveLoja=${chaveLoja}`);
        console.log(`  Original Tipo: ${descadastroOriginalTipo}`);
        console.log(`  TXT Export Type: ${descadastroTxtType}`);
        console.log(`  Actual Tipos: ${actualTipoCompleto}`);
        console.log(`  Actual AVANCADO: ${getXMLNodeValue(row, 'actual_avancado')}`);
        console.log(`  Actual PRESENCA: ${getXMLNodeValue(row, 'actual_presenca')}`);
        console.log(`  Actual UNIDADE_NEGOCIO: ${getXMLNodeValue(row, 'actual_unidade_negocio')}`);
        console.log(`  Actual ORGAO_PAGADOR: ${getXMLNodeValue(row, 'actual_orgao_pagador')}`);
        console.log('---');
    }
    
    console.log('=== END DESCADASTRAMENTO DEBUG ===');
};

// Usage: After exportTXTData() call, use this in console:
// window.debugDescadastroLogic(xmlDoc)
