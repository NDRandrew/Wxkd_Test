<?php
// Add ACC_FLAG to the base SELECT fields (line ~20 in initializeBaseQuery)
// Modify this section in TestM:

private function initializeBaseQuery() {
    $this->baseSelectFields = "
    
        A.Chave_Loja,
        A.Nome_Loja,
        A.Cod_Loja,
        A.Cod_Empresa,
        CONVERT(VARCHAR, B.DT_CADASTRO, 103) AS AVANCADO,
        CONVERT(VARCHAR, C.DT_CADASTRO, 103) AS PRESENCA,
        CONVERT(VARCHAR, D.DT_CADASTRO, 103) AS UNIDADE_NEGOCIO,
        CONVERT(VARCHAR, E.DT_CADASTRO, 103) AS ORGAO_PAGADOR,
        SUBSTRING(
            CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
            CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
            CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
            CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,
            3, 999
        ) AS TIPO_CORRESPONDENTE,
        CASE 
            WHEN B.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL THEN 'AVANCADO/UNIDADE_NEGOCIO'
            WHEN C.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL THEN 'ORG_PAGADOR/PRESENCA'
            ELSE 'N/I'
        END AS TIPO_LIMITES,
        F.DEP_DINHEIRO_VALID,
        F.DEP_CHEQUE_VALID,
        F.REC_RETIRADA_VALID,
        F.SAQUE_CHEQUE_VALID,
        F.SEGUNDA_VIA_CARTAO_VALID,
        F.CONSULTA_INSS_VALID,
        F.HOLERITE_INSS_VALID,
        F.PROVA_DE_VIDA_VALID,
        G.DATA_CONTRATO,
        G.TIPO AS TIPO_CONTRATO,
        COALESCE(H.WXKD_FLAG, 0) AS WXKD_FLAG,
        COALESCE(H.ACC_FLAG, 0) AS ACC_FLAG,
        I.qtd_repeticoes AS QUANT_LOJAS,
        J.data_log AS DATA_SOLICITACAO,
        CASE 
            WHEN B.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, B.DT_CADASTRO, 120)
            WHEN C.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, C.DT_CADASTRO, 120)
            WHEN D.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, D.DT_CADASTRO, 120)
            WHEN E.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, E.DT_CADASTRO, 120)
            ELSE CONVERT(VARCHAR, GETDATE(), 120)
        END AS DATA_CONCLUSAO,
        A.Nome_Loja AS Dep_Dinheiro,
        A.Nome_Loja AS Dep_Cheque,
        A.Nome_Loja AS Rec_Retirada,
        A.Nome_Loja AS Saque_Cheque,
        A.Nome_Loja AS '2Via_Cartao',
        A.Nome_Loja AS Holerite_INSS,
        A.Nome_Loja AS Cons_INSS";
        
    // Rest of the baseJoins remains the same, but update the H join:
    $this->baseJoins = "
        
        FROM DATALAKE..DL_BRADESCO_EXPRESSO A
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_AVANCADO
                GROUP BY CHAVE_LOJA
            ) B ON A.CHAVE_LOJA = B.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_PRESENCA
                GROUP BY CHAVE_LOJA
            ) C ON A.CHAVE_LOJA = C.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_UNIDADE_NEGOCIO
                GROUP BY CHAVE_LOJA
            ) D ON A.CHAVE_LOJA = D.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA_PARA, MAX(DATA_ATT) AS DT_CADASTRO
                FROM PBEN..TB_OP_PBEN_INDICACAO
                WHERE APROVACAO = 1
                GROUP BY CHAVE_LOJA_PARA
            ) E ON A.CHAVE_LOJA = E.CHAVE_LOJA_PARA
            LEFT JOIN (
                SELECT B.Cod_Empresa,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_DINHEIRO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS REC_RETIRADA_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS SAQUE_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS SEGUNDA_VIA_CARTAO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS CONSULTA_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS HOLERITE_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS PROVA_DE_VIDA_VALID
                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                JOIN (
                    SELECT Cod_Empresa, COD_SERVICO
                    FROM MESU.DBO.EMPRESAS_SERVICOS
                    GROUP BY COD_EMPRESA, COD_SERVICO
                ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                WHERE COD_SERVICO_BRAD IN (" . Wxkd_Config::getServiceCodesSQL() . ")
                GROUP BY B.Cod_Empresa
            ) F ON A.Cod_Empresa = F.Cod_Empresa
            LEFT JOIN (
                SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                FROM (
                    SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                        ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                    FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                    LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                    WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL AND (TIPO LIKE '%VERS%' OR TIPO LIKE '%TERMO DE ADESAO%') AND NOT TIPO LIKE '%BRADESCARD%'
                ) SELECIONADO
                WHERE rn = 1
            ) G ON A.COD_EMPRESA = G.KEY_EMPRESA
            LEFT JOIN (
                SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG, ACC_FLAG
                FROM (
                    SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG, ISNULL(ACC_FLAG, 0) AS ACC_FLAG,
                        ROW_NUMBER() OVER (PARTITION BY COD_EMPRESA, COD_LOJA ORDER BY WXKD_FLAG DESC) AS rn
                    FROM PGTOCORSP.dbo.tb_wxkd_flag
                ) ranked_flags
                WHERE rn = 1
            ) H ON A.COD_EMPRESA = H.COD_EMPRESA AND A.COD_LOJA = H.COD_LOJA
            LEFT JOIN (
                SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                FROM DATALAKE..DL_BRADESCO_EXPRESSO
                WHERE BE_INAUGURADO = 1
                GROUP BY COD_EMPRESA
            ) I ON A.COD_EMPRESA = I.COD_EMPRESA
            LEFT JOIN (
                SELECT A.CHAVE_LOJA, B.DATA_LOG
                FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                INNER JOIN (
                    SELECT A.CHAVE_LOJA, MAX(B.DATA_LOG) AS MAX_DATA_LOG
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                    INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B ON A.COD_SOLICITACAO = B.COD_SOLICITACAO
                    GROUP BY A.CHAVE_LOJA
                ) MaxLog ON A.CHAVE_LOJA = MaxLog.CHAVE_LOJA
                INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
                    ON A.COD_SOLICITACAO = B.COD_SOLICITACAO AND B.DATA_LOG = MaxLog.MAX_DATA_LOG
            ) J ON A.CHAVE_LOJA = J.CHAVE_LOJA";
}

// Add method to update ACC_FLAG
public function updateAccFlag($records) {
    try {
        if (empty($records)) {
            return false;
        }
        
        $updateCount = 0;
        
        foreach ($records as $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                continue;
            }
            
            // Use MERGE statement to avoid race conditions and duplicates
            $mergeSql = "
                MERGE PGTOCORSP.dbo.tb_wxkd_flag AS target
                USING (SELECT $codEmpresa AS COD_EMPRESA, $codLoja AS COD_LOJA) AS source
                ON (target.COD_EMPRESA = source.COD_EMPRESA AND target.COD_LOJA = source.COD_LOJA)
                WHEN MATCHED THEN
                    UPDATE SET ACC_FLAG = 1
                WHEN NOT MATCHED THEN
                    INSERT (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES, ACC_FLAG)
                    VALUES (source.COD_EMPRESA, source.COD_LOJA, 0, 0, 1);
            ";
            
            try {
                $result = $this->sql->update($mergeSql);
                if ($result) {
                    $updateCount++;
                }
            } catch (Exception $mergeEx) {
                // Fallback to the old method if MERGE fails
                $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                            WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $checkResult = $this->sql->select($checkSql);
                
                if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                    $insertSql = "INSERT INTO PGTOCORSP.dbo.tb_wxkd_flag (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES, ACC_FLAG) 
                                VALUES ($codEmpresa, $codLoja, 0, 0, 1)";
                    
                    $insertResult = $this->sql->insert($insertSql);
                    if ($insertResult) {
                        $updateCount++;
                    }
                } else {
                    $updateSql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                                SET ACC_FLAG = 1 
                                WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                    
                    $updateResult = $this->sql->update($updateSql);
                    if ($updateResult) {
                        $updateCount++;
                    }
                }
            }
        }
        
        return $updateCount > 0;
        
    } catch (Exception $e) {
        error_log("updateAccFlag - Exception: " . $e->getMessage());
        return false;
    }
}
?>

--------


<?php
// Modify the getValidationError method to check ACC_FLAG first
public function getValidationError($row) {
    // Check ACC_FLAG first - if it's 1, bypass all validation
    if (isset($row['ACC_FLAG']) && $row['ACC_FLAG'] == '1') {
        return null; // No validation error - bypass the lock
    }
    
    $cutoff = Wxkd_Config::getCutoffTimestamp();
    $activeTypes = $this->getActiveTypes($row, $cutoff);
    
    foreach ($activeTypes as $type) {
        if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
            $basicValidation = $this->checkBasicValidations($row);
            if ($basicValidation !== true) {
                return 'Tipo ' . $type . ' - ' . str_replace(array('ç', 'õ', 'ã'), array('c', 'o', 'a'), $basicValidation);
            }
        } elseif ($type === 'OP') {
            // For OP type, we need to check if ORGAO_PAGADOR has a value
            $orgaoPagadorEmpty = empty($row['ORGAO_PAGADOR']) || trim($row['ORGAO_PAGADOR']) === '';
            
            if ($orgaoPagadorEmpty) {
                return 'Tipo OP - ORGAO_PAGADOR sem data valida';
            }
            
            $opValidation = $this->checkOPValidations($row);
            if ($opValidation !== true) {
                return str_replace(array('ç', 'õ', 'ã'), array('c', 'o', 'a'), $opValidation);
            }
        }
    }
    
    return null; 
}

// Modify the exportAccess method to update ACC_FLAG after processing
public function exportAccess() {
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
        
        $cutoff = Wxkd_Config::getCutoffTimestamp();
        
        $avUnPrData = array();
        $opData = array();
        
        // Collect records for ACC_FLAG update
        $recordsToUpdateAccFlag = array();
        
        error_log("exportAccess - Starting processing with filter: {$filter}, records: " . count($data));
        
        foreach ($data as $index => $row) {
            $activeTypes = array();
            
            if ($filter === 'historico') {
                $row = array_change_key_case($row, CASE_UPPER);
                $activeTypes = $this->getActiveTypesForDescadastramento($row, $cutoff);
            } elseif ($filter === 'descadastramento') {
                $activeTypes = $this->getActiveTypesForDescadastramento($row, $cutoff);
            } else {
                // For other filters (cadastramento, all), use the original logic
                $activeTypes = $this->getActiveTypes($row, $cutoff);
            }

            $hasOP = in_array('OP', $activeTypes);
            $hasOthers = in_array('AV', $activeTypes) || in_array('PR', $activeTypes) || in_array('UN', $activeTypes);

            $codEmpresa = $this->getEmpresaCode($row);
            
            error_log("exportAccess - Row {$index}: codEmpresa={$codEmpresa}, activeTypes=" . implode(',', $activeTypes) . ", hasOP=" . ($hasOP ? 'true' : 'false') . ", hasOthers=" . ($hasOthers ? 'true' : 'false'));

            if (!empty($codEmpresa) && !empty($activeTypes)) {
                if ($hasOP) {
                    $opData[] = $codEmpresa;
                }
                if ($hasOthers) {
                    $avUnPrData[] = $codEmpresa;
                }
                
                // Add to ACC_FLAG update list
                $codLoja = $this->getLojaCode($row);
                if (!empty($codLoja)) {
                    $recordsToUpdateAccFlag[] = array(
                        'COD_EMPRESA' => $codEmpresa,
                        'COD_LOJA' => $codLoja
                    );
                }
            }
        }
        
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        error_log("exportAccess - Final results: avUnPrData count=" . count($avUnPrData) . ", opData count=" . count($opData));
        error_log("exportAccess - avUnPrData: " . implode(',', $avUnPrData));
        error_log("exportAccess - opData: " . implode(',', $opData));
        
        // Update ACC_FLAG for processed records
        $accFlagUpdated = false;
        if (!empty($recordsToUpdateAccFlag)) {
            $accFlagUpdated = $this->model->updateAccFlag($recordsToUpdateAccFlag);
            error_log("exportAccess - ACC_FLAG update result: " . ($accFlagUpdated ? 'success' : 'failed') . " for " . count($recordsToUpdateAccFlag) . " records");
        }
        
        $xml = '<response><success>true</success>';
        
        $xml .= '<avUnPrData>';
        foreach ($avUnPrData as $codEmpresa) {
            $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
        }
        $xml .= '</avUnPrData>';
        
        if (!empty($opData)) {
            $xml .= '<opData>';
            foreach ($opData as $codEmpresa) {
                $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
            }
            $xml .= '</opData>';
        }
        
        // Add ACC_FLAG update info to response
        $xml .= '<accFlagUpdate>';
        $xml .= '<success>' . ($accFlagUpdated ? 'true' : 'false') . '</success>';
        $xml .= '<recordsUpdated>' . count($recordsToUpdateAccFlag) . '</recordsUpdated>';
        $xml .= '</accFlagUpdate>';
        
        $xml .= '</response>';
        echo $xml;
        exit;
        
    } catch (Exception $e) {
        error_log("exportAccess - Exception: " . $e->getMessage());
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
        $xml .= '</response>';
        echo $xml;
        exit;
    }
}

// Add helper method to get loja code
private function getLojaCode($row) {
    $possibleLojaFields = array('Cod_Loja', 'cod_loja', 'COD_LOJA', 'CODLOJA', 'cod_loj');
    foreach ($possibleLojaFields as $field) {
        if (isset($row[$field]) && !empty($row[$field])) {
            return $row[$field];
        }
    }
    
    return '';
}
?>


------


// Modify the hasValidationError function to check ACC_FLAG first
hasValidationError: function(row) {
    // Check ACC_FLAG first - if it's 1, bypass all validation
    if (row[19] && $(row[19]).text() === '1') { // Assuming ACC_FLAG is in column 19
        return false; // No validation error - bypass the lock
    }
    
    const cutoff = new Date(2025, 5, 1);
    const activeTypes = this.getActiveTypes(row, cutoff);
    
    for (let i = 0; i < activeTypes.length; i++) {
        const type = activeTypes[i];
        if (['AV', 'PR', 'UN'].includes(type)) {
            if (!this.checkBasicValidationsJS(row)) {
                return true;
            }
        } else if (type === 'OP') {
            if (!this.checkOPValidationsJS(row)) {
                return true;
            }
        }
    }
    
    return false;
},

// Modify the createRowData function to include ACC_FLAG
createRowData: function(row, index) {
    const rowData = [];
    
    // Use ContractChaves for highlighting
    const currentContractChaves = window.contractChaves || [];
    const chaveLoja = String(row.CHAVE_LOJA || row.Chave_Loja || '');
    const highlightMissing = currentContractChaves.indexOf(chaveLoja) === -1;
    const highlightStyle = highlightMissing ? '#f4b400' : 'transparent';
    
    console.log('createRowData - ChaveLoja:', chaveLoja, 'HighlightMissing:', highlightMissing, 'ContractChaves:', currentContractChaves);
    
    const checkboxCell = this.createCheckboxCell(index);
    
    // Check ACC_FLAG first for validation error
    const accFlag = row.ACC_FLAG || '0';
    const hasValidationError = accFlag === '1' ? false : this.hasValidationError(row);
    
    if (hasValidationError) {
        const lockIcon = $('<i class="fa fa-lock">')
            .css({
                'color': '#d9534f',
                'margin-left': '5px',
                'cursor': 'help'    
            })
            .attr('title', 'Essa Loja não pode ser exportada como txt por: ' + this.getValidationErrorMessage(row));
        checkboxCell.append(lockIcon);
    } else if (accFlag === '1') {
        // Add a green check icon to show it was processed by Access export
        const checkIcon = $('<i class="fa fa-check-circle">')
            .css({
                'color': '#5cb85c',
                'margin-left': '5px',
                'cursor': 'help'    
            })
            .attr('title', 'Esta loja foi processada pelo Export Access e pode ser exportada como TXT');
        checkboxCell.append(checkIcon);
    }
    
    rowData.push(checkboxCell);
    
    rowData.push($('<td>').text(row.CHAVE_LOJA || row.Chave_Loja || ''));
    rowData.push($('<td>').text(row.NOME_LOJA || row.Nome_Loja || ''));
    rowData.push($('<td>').text(row.COD_EMPRESA || row.Cod_Empresa || ''));
    rowData.push($('<td>').text(row.COD_LOJA || row.Cod_Loja || ''));
    rowData.push($('<td>').text(row.QUANT_LOJAS || ''));
    
    rowData.push($('<td>').html(this.generateStatusHTML(row, FilterModule.currentFilter)));
    
    rowData.push($('<td>').html(this.generateDateFieldsHTML(row)));
    rowData.push($('<td>').text(this.formatDataSolicitacao(row)));
    
    this.addValidationFields(rowData, row);
    
    this.addContractFields(rowData, row, highlightStyle);
    
    // Add ACC_FLAG as hidden column (can be used for debugging)
    rowData.push($('<td style="display:none;">').text(accFlag));
    
    return rowData;
},

// Update exportAccessData to reload table after success
function exportAccessData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportAccess&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
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
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    const errorMsg = xmlDoc.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido';
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                // Check ACC_FLAG update result
                const accFlagUpdate = xmlDoc.getElementsByTagName('accFlagUpdate')[0];
                if (accFlagUpdate) {
                    const accSuccess = xmlDoc.getElementsByTagName('accFlagUpdate')[0].getElementsByTagName('success')[0]?.textContent === 'true';
                    const recordsUpdated = xmlDoc.getElementsByTagName('accFlagUpdate')[0].getElementsByTagName('recordsUpdated')[0]?.textContent || '0';
                    
                    if (accSuccess && parseInt(recordsUpdated) > 0) {
                        console.log(`ACC_FLAG updated for ${recordsUpdated} records`);
                    }
                }
                
                const csvFiles = extractAccessCSVFromXML(xmlDoc);
                
                if (csvFiles.length === 0) {
                    alert('Erro: Nenhum arquivo CSV gerado');
                    return;
                }
                
                csvFiles.forEach((file, index) => {
                    setTimeout(() => {
                        downloadAccessCSVFile(file.content, file.filename);
                    }, index * 1500);
                });
                
                // Reload the table after successful export to update lock icons
                setTimeout(() => {
                    console.log('Reloading table after Access export to update lock icons...');
                    CheckboxModule.clearSelections();
                    const currentFilter = FilterModule.currentFilter;
                    FilterModule.loadTableData(currentFilter);
                }, 2000); // Wait for downloads to start
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição');
        });
}

// Update getValidationErrorMessage to check ACC_FLAG
getValidationErrorMessage: function(row) {
    // Check ACC_FLAG first
    const accFlag = row.ACC_FLAG || (row[19] && $(row[19]).text()) || '0';
    if (accFlag === '1') {
        return null; // No error message needed
    }
    
    const cutoff = new Date(2025, 5, 1);
    const activeTypes = this.getActiveTypes(row, cutoff);
    
    for (let i = 0; i < activeTypes.length; i++) {
        const type = activeTypes[i];
        if (['AV', 'PR', 'UN'].includes(type)) {
            const basicError = this.getBasicValidationErrorJS(row);
            if (basicError) {
                return `Tipo ${type} - ${basicError}`;
            }
        } else if (type === 'OP') {
            const opError = this.getOPValidationErrorJS(row);
            if (opError) {
                return opError;
            }
        }
    }
    
    return 'Erro de validacao';
}


---------


<?php
// The lock icon section in TestH should be updated to show different icons based on ACC_FLAG
// Find this section around line 220-230 and replace it:

<td class="checkbox-column" >
    <label>
        <!-- Valores de checkbox definidos por esse PHP counter -->
        <input type="checkbox" class="form-check-input row-checkbox" value="<?php echo $counter ?>" id="row_<?php echo $counter; ?>"/>
        <span class="text"></span>
    </label>
    <?php
    // Check ACC_FLAG first
    $accFlag = isset($row['ACC_FLAG']) ? $row['ACC_FLAG'] : '0';
    
    if ($accFlag == '1') {
        // Show green check icon for ACC_FLAG = 1 (processed by Access export)
        echo '<i class="fa fa-check-circle" style="color:#5cb85c; margin-left: 5px; cursor: help;" 
              title="Esta loja foi processada pelo Export Access e pode ser exportada como TXT"></i>';
    } else {
        // Check validation error only if ACC_FLAG is not 1
        $validationError = $controller->getValidationError($row);
        if ($validationError):
        ?>
        <i class = "fa fa-lock" style="color:#d9534f; margin-left: 5px; cursor: help;"
        title="Essa Loja nao pode ser exportada como txt por: <?php echo htmlspecialchars($validationError); ?>"></i>
        <?php 
        endif; 
    }
    ?>
</td>

<?php
// Also, make sure ACC_FLAG is available in the JavaScript by adding it to the contractChaves section
// Add this after the existing contractChaves script (around line 580):
?>

<script type="text/javascript">
    // Make ACC_FLAG data available to JavaScript for each row
    var accFlagData = {
        <?php
        if (is_array($tableData) && !empty($tableData)) {
            $first = true;
            foreach ($tableData as $index => $row) {
                if (!$first) echo ', ';
                $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
                $accFlag = isset($row['ACC_FLAG']) ? $row['ACC_FLAG'] : '0';
                echo "'" . addslashes($chaveLoja) . "': '" . addslashes($accFlag) . "'";
                $first = false;
            }
        }
        ?>
    };
    
    // Make it globally available
    window.accFlagData = accFlagData;
    console.log('ACC_FLAG data loaded:', accFlagData);
</script>

<?php
// If you want to add a debug function to check ACC_FLAG status, add this script:
?>

<script type="text/javascript">
    // Debug function to check ACC_FLAG status
    window.debugAccFlag = function() {
        console.log('=== ACC_FLAG DEBUG ===');
        console.log('Current Filter:', FilterModule.currentFilter);
        console.log('ACC_FLAG Data:', window.accFlagData);
        
        // Check first few visible rows
        $('#dataTableAndre tbody tr:visible').slice(0, 3).each(function(index) {
            const $row = $(this);
            const chaveLoja = $row.find('td:eq(1)').text(); // Second column is Chave_Loja
            const hasLock = $row.find('.fa-lock').length > 0;
            const hasCheck = $row.find('.fa-check-circle').length > 0;
            const accFlag = window.accFlagData ? window.accFlagData[chaveLoja] : 'unknown';
            
            console.log(`Row ${index}: ChaveLoja=${chaveLoja}, ACC_FLAG=${accFlag}, HasLock=${hasLock}, HasCheck=${hasCheck}`);
        });
        console.log('=== END ACC_FLAG DEBUG ===');
    };
</script>
?>


---------


<?php
// In the getTableDataByFilter method, update the descadastramento query to include ACC_FLAG
// Find the descadastramento case (around line 280) and update the LEFT JOIN sections:

case 'descadastramento':
    $query = "
        SELECT
    A.Chave_Loja,
    G.Nome_Loja,
    G.Cod_Empresa,
    G.Cod_Loja,
    A.COD_SOLICITACAO,
    DATA_PEDIDO,
    DATA_PEDIDO AS DATA_SOLICITACAO,
    DATA_PEDIDO AS DATA_CONCLUSAO,
    CASE 
        WHEN C.DESC_SOLICITACAO = 'Incentivo Producao' THEN 'UNIDADE_NEGOCIO'
        WHEN C.DESC_SOLICITACAO = 'Presenca' THEN 'PRESENCA'
        WHEN C.DESC_SOLICITACAO = 'Avancado' THEN 'AVANCADO'
        WHEN C.DESC_SOLICITACAO = 'Orgao Pagador' THEN 'ORGAO_PAGADOR'
        WHEN C.DESC_SOLICITACAO = 'ORG_PAGADOR' THEN 'ORGAO_PAGADOR'
        ELSE C.DESC_SOLICITACAO
    END AS TIPO_CORRESPONDENTE,
    CASE 
        WHEN C.DESC_SOLICITACAO = 'Avancado' OR C.DESC_SOLICITACAO = 'Incentivo Producao' THEN 'AVANCADO/UNIDADE_NEGOCIO'
        WHEN C.DESC_SOLICITACAO = 'Presenca' THEN 'ORGAO_PAGADOR/PRESENCA'
        ELSE 'N/I'
    END AS TIPO_LIMITES,
    H.DEP_DINHEIRO_VALID,
    H.DEP_CHEQUE_VALID,
    H.REC_RETIRADA_VALID,
    H.SAQUE_CHEQUE_VALID,
    H.SEGUNDA_VIA_CARTAO_VALID,
    H.CONSULTA_INSS_VALID,
    H.HOLERITE_INSS_VALID,
    H.PROVA_DE_VIDA_VALID,
    I.DATA_CONTRATO,
    I.TIPO AS TIPO_CONTRATO,
    A.COD_ACAO,
    D.DESC_ACAO,
    B.COD_ETAPA,
    E.DESC_ETAPA,
    B.COD_STATUS,
    F.DESC_STATUS,
    J.QTD_REPETICOES AS QUANT_LOJAS,
    ISNULL(K.ACC_FLAG, 0) AS ACC_FLAG
                FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
                    JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
                    JOIN 
                    --select * from 
                        PGTOCORSP.dbo.PGTOCORSP_TB_TIPO_SOLICITACAO C ON A.COD_TIPO_PAGAMENTO=C.COD_SOLICITACAO
                    LEFT JOIN
                        PGTOCORSP.dbo.PGTOCORSP_TB_ACAO_SOLICITACAO D ON A.COD_ACAO=D.COD_ACAO
                    LEFT JOIN 
                        PGTOCORSP.dbo.TB_PGTO_ETAPA E ON E.COD_ETAPA=B.COD_ETAPA
                    LEFT JOIN 
                        PGTOCORSP.dbo.TB_PGTO_STATUS F ON F.COD_STATUS=B.COD_STATUS
                    LEFT JOIN
                        DATALAKE..DL_BRADESCO_EXPRESSO G ON A.CHAVE_LOJA=G.CHAVE_LOJA
                    LEFT JOIN (
                                    SELECT B.Cod_Empresa,
                                        MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_DINHEIRO_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_CHEQUE_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS REC_RETIRADA_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS SAQUE_CHEQUE_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS SEGUNDA_VIA_CARTAO_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS CONSULTA_INSS_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS HOLERITE_INSS_VALID,
                                        MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS PROVA_DE_VIDA_VALID
                                    FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                                    JOIN (
                                        SELECT Cod_Empresa, COD_SERVICO
                                        FROM MESU.DBO.EMPRESAS_SERVICOS
                                        GROUP BY COD_EMPRESA, COD_SERVICO
                                    ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                                    WHERE COD_SERVICO_BRAD IN  (" . Wxkd_Config::getServiceCodesSQL() . ") GROUP BY B.Cod_Empresa
                                                ) H ON G.COD_EMPRESA = H.COD_EMPRESA
                    LEFT JOIN (
                                    SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                                        FROM (
                                            SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                                                ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                                            FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                                            LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                                            WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL AND C.TIPO LIKE 'TERMO DE ADESAO%' OR C.TIPO LIKE 'VERSAO%'
                                        ) SELECIONADO
                                        WHERE rn = 1
                                ) I ON G.COD_EMPRESA = I.KEY_EMPRESA
                    LEFT JOIN (
                                SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                                    FROM DATALAKE..DL_BRADESCO_EXPRESSO
                                    WHERE BE_INAUGURADO = 1
                                    GROUP BY COD_EMPRESA
                                ) J ON G.COD_EMPRESA = J.COD_EMPRESA
                    LEFT JOIN (
                                SELECT COD_EMPRESA, COD_LOJA, ISNULL(ACC_FLAG, 0) AS ACC_FLAG
                                FROM PGTOCORSP.dbo.tb_wxkd_flag
                            ) K ON G.COD_EMPRESA = K.COD_EMPRESA AND G.COD_LOJA = K.COD_LOJA
                WHERE 
                    B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 AND A.DATA_PEDIDO>='" . Wxkd_Config::CUTOFF_DATE . "'

            UNION ALL

                    SELECT
                G.Chave_Loja,
                G.Nome_Loja,
                G.Cod_Empresa,
                G.Cod_Loja,
                G.Cod_Empresa as COD_SOLICITACAO,
                A.DATA_ATT AS DATA_PEDIDO,
                A.DATA_ATT AS DATA_SOLICITACAO,
                A.DATA_ATT AS DATA_CONCLUSAO,
                CASE 
                    WHEN A.COD_EMPRESA IS NOT NULL THEN 'ORGAO_PAGADOR'
                    ELSE 'N/I'
                END AS TIPO_CORRESPONDENTE,
                CASE 
                    WHEN A.COD_EMPRESA IS NOT NULL THEN 'ORGAO_PAGADOR/PRESENCA'
                    ELSE 'N/I'
                END AS TIPO_LIMITES,
                H.DEP_DINHEIRO_VALID,
                H.DEP_CHEQUE_VALID,
                H.REC_RETIRADA_VALID,
                H.SAQUE_CHEQUE_VALID,
                H.SEGUNDA_VIA_CARTAO_VALID,
                H.CONSULTA_INSS_VALID,
                H.HOLERITE_INSS_VALID,
                H.PROVA_DE_VIDA_VALID,
                I.DATA_CONTRATO,
                I.TIPO AS TIPO_CONTRATO,
                A.COD_EMPRESA AS COD_ACAO,
                G.NOME_LOJA AS DESC_ACAO,
                A.COD_EMPRESA AS COD_ETAPA,
                G.NOME_LOJA AS DESC_ETAPA,
                A.COD_EMPRESA AS COD_STATUS,
                G.NOME_LOJA AS DESC_STATUS,
                J.QTD_REPETICOES AS QUANT_LOJAS,
                ISNULL(K.ACC_FLAG, 0) AS ACC_FLAG
            FROM PBEN..TB_OP_PBEN_HIST A
            LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO G ON A.COD_EMPRESA = G.COD_EMPRESA
            LEFT JOIN (
                                SELECT B.Cod_Empresa,
                                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_DINHEIRO_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_CHEQUE_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS REC_RETIRADA_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS SAQUE_CHEQUE_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS SEGUNDA_VIA_CARTAO_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS CONSULTA_INSS_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS HOLERITE_INSS_VALID,
                                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS PROVA_DE_VIDA_VALID
                                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                                JOIN (
                                    SELECT Cod_Empresa, COD_SERVICO
                                    FROM MESU.DBO.EMPRESAS_SERVICOS
                                    GROUP BY COD_EMPRESA, COD_SERVICO
                                ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                                WHERE COD_SERVICO_BRAD IN  (" . Wxkd_Config::getServiceCodesSQL() . ") GROUP BY B.Cod_Empresa
                            ) H ON G.COD_EMPRESA = H.COD_EMPRESA
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
                                    SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                                        FROM DATALAKE..DL_BRADESCO_EXPRESSO
                                        WHERE BE_INAUGURADO = 1
                                        GROUP BY COD_EMPRESA
                                    ) J ON G.COD_EMPRESA = J.COD_EMPRESA
                LEFT JOIN (
                            SELECT COD_EMPRESA, COD_LOJA, ISNULL(ACC_FLAG, 0) AS ACC_FLAG
                            FROM PGTOCORSP.dbo.tb_wxkd_flag
                        ) K ON A.COD_EMPRESA = K.COD_EMPRESA
            WHERE 
                A.DATA_ATT >= '" . Wxkd_Config::CUTOFF_DATE . "'
                AND CONVERT(DATE, A.DATA_ATT) = CONVERT(DATE, DATEADD(DAY, -30, GETDATE()))
                AND NOT EXISTS (
                    SELECT 1
                    FROM PBEN..TB_OP_PBEN atual
                    WHERE A.COD_EMPRESA = atual.COD_EMPRESA)";
    break;
?>



-------------


