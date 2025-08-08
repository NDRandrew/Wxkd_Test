<?php
// Replace the initializeBaseQuery method in Wxkd_DashboardModel class

private function initializeBaseQuery() {
    $this->baseSelectFields = "
    DISTINCT
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
        COALESCE(H.WXKD_FLAG_DES, 0) AS WXKD_FLAG_DES,
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
                    WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL
                ) SELECIONADO
                WHERE rn = 1
            ) G ON A.COD_EMPRESA = G.KEY_EMPRESA
            LEFT JOIN (
                SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES
                FROM (
                    SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG, 
                           COALESCE(WXKD_FLAG_DES, 0) AS WXKD_FLAG_DES,
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

// Replace the updateWxkdFlag method in Wxkd_DashboardModel class
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
        $flagToUpdate = ($filtro === 'descadastramento') ? 'WXKD_FLAG_DES' : 'WXKD_FLAG';
        $logs[] = "updateWxkdFlag - Will update flag: $flagToUpdate for filter: $filtro";
        
        foreach ($records as $index => $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                continue;
            }
            
            // Use MERGE statement to avoid race conditions and duplicates
            $mergeSql = "
                MERGE PGTOCORSP.dbo.tb_wxkd_flag AS target
                USING (SELECT $codEmpresa AS COD_EMPRESA, $codLoja AS COD_LOJA) AS source
                ON (target.COD_EMPRESA = source.COD_EMPRESA AND target.COD_LOJA = source.COD_LOJA)
                WHEN MATCHED THEN
                    UPDATE SET $flagToUpdate = 1
                WHEN NOT MATCHED THEN
                    INSERT (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES)
                    VALUES (source.COD_EMPRESA, source.COD_LOJA, 
                           " . ($flagToUpdate === 'WXKD_FLAG' ? '1' : '0') . ", 
                           " . ($flagToUpdate === 'WXKD_FLAG_DES' ? '1' : '0') . ");
            ";
            
            try {
                $result = $this->sql->update($mergeSql);
                
                if ($result) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully processed $flagToUpdate for record #$index (MERGE)";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to process $flagToUpdate for record #$index (MERGE)";
                }
            } catch (Exception $mergeEx) {
                $logs[] = "updateWxkdFlag - MERGE exception for record #$index: " . $mergeEx->getMessage();
                
                // Fallback to the old method if MERGE fails
                $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                            WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $checkResult = $this->sql->select($checkSql);
                
                if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                    $insertSql = "INSERT INTO PGTOCORSP.dbo.tb_wxkd_flag (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES) 
                                VALUES ($codEmpresa, $codLoja, 
                                       " . ($flagToUpdate === 'WXKD_FLAG' ? '1' : '0') . ", 
                                       " . ($flagToUpdate === 'WXKD_FLAG_DES' ? '1' : '0') . ")";
                    
                    $insertResult = $this->sql->insert($insertSql);
                    
                    if ($insertResult) {
                        $updateCount++;
                        $logs[] = "updateWxkdFlag - Successfully inserted new $flagToUpdate record for #$index (fallback)";
                    } else {
                        $logs[] = "updateWxkdFlag - Failed to insert $flagToUpdate record for #$index (fallback)";
                    }
                } else {
                    $updateSql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                                SET $flagToUpdate = 1 
                                WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                    
                    $updateResult = $this->sql->update($updateSql);
                    
                    if ($updateResult) {
                        $updateCount++;
                        $logs[] = "updateWxkdFlag - Successfully updated $flagToUpdate for record #$index (fallback)";
                    } else {
                        $logs[] = "updateWxkdFlag - Failed to update $flagToUpdate for record #$index (fallback)";
                    }
                }
            }
        }
        
        // Log insertion logic remains the same but with filter information
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records for filter: $filtro";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                
                $chaveLoja = $this->getFieldValue($record, array('Chave_Loja', 'CHAVE_LOJA'));
                $nomeLoja = $this->getFieldValue($record, array('Nome_Loja', 'NOME_LOJA'));
                $codEmpresa = $this->getFieldValue($record, array('Cod_Empresa', 'COD_EMPRESA'));
                $codLoja = $this->getFieldValue($record, array('Cod_Loja', 'COD_LOJA'));
                
                if (empty($chaveLoja) || empty($nomeLoja)) {
                    $logs[] = "updateWxkdFlag - Missing required fields in log record #$index";
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
                
                // Check if ORGAO_PAGADOR is empty for this record
                $orgaoPagadorEmpty = empty($record['ORGAO_PAGADOR']) || trim($record['ORGAO_PAGADOR']) === '';
                
                $segundaVia = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO'));
                $holeriteInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('HOLERITE_INSS_VALID', 'HOLERITE_INSS'));
                $consultaInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('CONSULTA_INSS_VALID', 'CONS_INSS'));
                $provaVida = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA'));
                
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
        
        $logs[] = "updateWxkdFlag END - Flag ($flagToUpdate) updates: $updateCount, Log inserts: $logInsertCount";
        $this->debugLogs = $logs;
        
        return $updateCount > 0;
        
    } catch (Exception $e) {
        $logs[] = "updateWxkdFlag - MAIN Exception: " . $e->getMessage();
        $this->debugLogs = $logs;
        return false;
    }
}
?>


----------------------



// Add this to CheckboxModule in TestJ - Update the updateExportButton method
updateExportButton: function() {
    const checkedCount = $('.row-checkbox:checked').length;
    $('#selectedCount').text(checkedCount);
    
    const isDisabled = checkedCount === 0;
    $('#exportTxtBtn').prop('disabled', isDisabled);
    
    let buttonText = 'Exportar TXT';
    const currentFilter = FilterModule.currentFilter;
    
    if (currentFilter === 'cadastramento') {
        buttonText = 'Converter para TXT (WXKD_FLAG)';
    } else if (currentFilter === 'descadastramento') {
        buttonText = 'Converter para TXT (WXKD_FLAG_DES)';
    } else if (currentFilter === 'historico') {
        buttonText = 'Exportar TXT';
    }
    
    const btnContent = $('#exportTxtBtn').html();
    if (btnContent) {
        const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
        $('#exportTxtBtn').html(newContent);
    }
},

// Add this method to PaginationModule - to handle the flag status display
getFlagStatusForDisplay: function(row) {
    const currentFilter = FilterModule.currentFilter;
    
    if (currentFilter === 'descadastramento') {
        // For descadastramento, check WXKD_FLAG_DES
        return row.WXKD_FLAG_DES || row.wxkd_flag_des || '0';
    } else {
        // For cadastramento and others, check WXKD_FLAG
        return row.WXKD_FLAG || row.wxkd_flag || '0';
    }
},

// Update the createRowData method in PaginationModule to include flag info
createRowData: function(row, index) {
    const rowData = [];
    const highlightMissing = contractChaves.indexOf(String(row.CHAVE_LOJA || row.Chave_Loja)) === -1;
    const highlightStyle = highlightMissing ? '#f4b400' : 'transparent';
    
    const checkboxCell = this.createCheckboxCell(index);
    
    // Add flag status indicator based on current filter
    const currentFilter = FilterModule.currentFilter;
    const flagStatus = this.getFlagStatusForDisplay(row);
    
    if (this.hasValidationError(row)) {
        const lockIcon = $('<i class="fa fa-lock">')
            .css({
                'color': '#d9534f',
                'margin-left': '5px',
                'cursor': 'help'    
            })
            .attr('title', 'Essa Loja não pode ser exportada como txt por: ' + this.getValidationErrorMessage(row));
        checkboxCell.append(lockIcon);
    }
    
    // Add flag status indicator
    if (currentFilter !== 'historico') {
        const flagIndicator = $('<i class="fa fa-circle">')
            .css({
                'color': flagStatus === '1' ? '#5cb85c' : '#d9534f',
                'margin-left': '3px',
                'font-size': '8px'
            })
            .attr('title', `${currentFilter === 'descadastramento' ? 'WXKD_FLAG_DES' : 'WXKD_FLAG'}: ${flagStatus}`);
        checkboxCell.append(flagIndicator);
    }
    
    rowData.push(checkboxCell);
    
    // Continue with the rest of the row creation...
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
    
    return rowData;
},

// Update the FilterModule to show appropriate filter information
// Add this method to FilterModule
updateFilterUI: function() {
    if (this.currentFilter === 'all') {
        $('#filterIndicator').hide();
    } else {
        const filterNames = {
            'cadastramento': 'Cadastramento',
            'descadastramento': 'Descadastramento', 
            'historico': 'Histórico'
        };
        
        const filterDescriptions = {
            'cadastramento': 'Mostrando apenas lojas para cadastramento (WXKD_FLAG = 0)',
            'descadastramento': 'Mostrando apenas lojas para descadastramento (WXKD_FLAG_DES = 0)',
            'historico': 'Mostrando histórico de processos realizados'
        };
        
        $('#activeFilterName').text(filterNames[this.currentFilter]);
        $('#filterDescription').text(filterDescriptions[this.currentFilter]);
        $('#filterIndicator').fadeIn();
    }
},

// Update the exportSelectedTXT function
function exportSelectedTXT() {
    $('.validation-alert').remove();
    const currentFilter = getCurrentFilter();

    const selectedIds = (currentFilter === 'historico')
        ? HistoricoCheckboxModule.getSelectedIds()
        : CheckboxModule.getSelectedIds();

    if (selectedIds.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }

    // Show confirmation for descadastramento
    if (currentFilter === 'descadastramento') {
        const confirmMsg = `Você está exportando ${selectedIds.length} registro(s) do filtro DESCADASTRAMENTO.\n\n` +
                          `Isso irá atualizar o WXKD_FLAG_DES para 1.\n\n` +
                          `Deseja continuar?`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
    }

    exportTXTData(selectedIds.join(','), currentFilter);
}

// Add logging for flag updates - Update the exportTXTData function
function exportTXTData(selectedIds, filter) {
    showLoading();
    
    console.log(`Exporting TXT for filter: ${filter}, will update: ${filter === 'descadastramento' ? 'WXKD_FLAG_DES' : 'WXKD_FLAG'}`);
    
    const url = `wxkd.php?action=exportTXT&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const xmlContent = extractXMLFromMixedResponse(responseText);
                console.log('Conteudo: '+xmlContent);
                if (!xmlContent) {
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');

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
                
                // Show success message with flag information
                const flagUpdated = filter === 'descadastramento' ? 'WXKD_FLAG_DES' : 'WXKD_FLAG';
                const successMsg = `TXT exportado com sucesso!\n\nFlag atualizada: ${flagUpdated} = 1\nFiltro: ${filter}\nRegistros: ${selectedIds.split(',').length}`;
                
                setTimeout(() => {
                    alert(successMsg);
                    console.log('Reloading after TXT Export...');

                    CheckboxModule.clearSelections();

                    const currentFilter = FilterModule.currentFilter;
                    FilterModule.loadTableData(currentFilter);

                }, 1000);

                
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





-------------------




