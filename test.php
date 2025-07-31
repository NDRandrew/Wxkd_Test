case 'descadastramento':
    $query = "
        SELECT
            A.CHAVE_LOJA,
            G.NOME_LOJA,
            G.COD_EMPRESA,
            G.COD_LOJA,
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
            -- Add actual tipos data from registration tables
            CONVERT(VARCHAR, ACTUAL_TIPOS.AVANCADO_DT, 103) AS ACTUAL_AVANCADO,
            CONVERT(VARCHAR, ACTUAL_TIPOS.PRESENCA_DT, 103) AS ACTUAL_PRESENCA,
            CONVERT(VARCHAR, ACTUAL_TIPOS.UNIDADE_NEGOCIO_DT, 103) AS ACTUAL_UNIDADE_NEGOCIO,
            CONVERT(VARCHAR, ACTUAL_TIPOS.ORGAO_PAGADOR_DT, 103) AS ACTUAL_ORGAO_PAGADOR,
            ACTUAL_TIPOS.TIPOS_CONCATENATED AS ACTUAL_TIPOS_ALL
            FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
                JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
                JOIN PGTOCORSP.dbo.PGTOCORSP_TB_TIPO_SOLICITACAO C ON A.COD_TIPO_PAGAMENTO=C.COD_SOLICITACAO
                LEFT JOIN PGTOCORSP.dbo.PGTOCORSP_TB_ACAO_SOLICITACAO D ON A.COD_ACAO=D.COD_ACAO
                LEFT JOIN PGTOCORSP.dbo.TB_PGTO_ETAPA E ON E.COD_ETAPA=B.COD_ETAPA
                LEFT JOIN PGTOCORSP.dbo.TB_PGTO_STATUS F ON F.COD_STATUS=B.COD_STATUS
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO G ON A.CHAVE_LOJA=G.CHAVE_LOJA
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
                -- Join with actual tipos data
                LEFT JOIN (
                    SELECT 
                        A.CHAVE_LOJA,
                        B.DT_CADASTRO AS AVANCADO_DT,
                        C.DT_CADASTRO AS PRESENCA_DT,
                        D.DT_CADASTRO AS UNIDADE_NEGOCIO_DT,
                        E.DT_CADASTRO AS ORGAO_PAGADOR_DT,
                        SUBSTRING(
                            CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
                            CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
                            CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
                            CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,
                            3, 999
                        ) AS TIPOS_CONCATENATED
                    FROM DATALAKE..DL_BRADESCO_EXPRESSO A
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B ON A.CHAVE_LOJA=B.CHAVE_LOJA
                        LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA C ON C.CHAVE_LOJA=A.CHAVE_LOJA
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D ON D.CHAVE_LOJA=A.CHAVE_LOJA
                        LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON A.CHAVE_LOJA=E.CHAVE_LOJA_PARA
                    WHERE (B.DT_CADASTRO IS NOT NULL OR C.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL)
                ) ACTUAL_TIPOS ON A.CHAVE_LOJA = ACTUAL_TIPOS.CHAVE_LOJA
            WHERE 
                B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 AND A.DATA_PEDIDO>='20250701'
            ORDER BY A.DATA_PEDIDO";
    break;


-----------------



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

        const contrato = getXMLNodeValue(row, 'tipo_contrato');

        console.log(`Processing row ${i}: Empresa=${empresa}, Loja=${codigoLoja}, ActualFilter=${actualFilter}`);

        if (actualFilter === 'descadastramento') {
            // DESCADASTRAMENTO COMPLEX LOGIC
            const shownTipo = getXMLNodeValue(row, 'tipo_correspondente'); // What's shown in descadastramento table
            
            // Get actual tipos available for this store
            const actualAvancado = getXMLNodeValue(row, 'ACTUAL_AVANCADO');
            const actualPresenca = getXMLNodeValue(row, 'ACTUAL_PRESENCA');
            const actualUnidadeNegocio = getXMLNodeValue(row, 'ACTUAL_UNIDADE_NEGOCIO');
            const actualOrgaoPagador = getXMLNodeValue(row, 'ACTUAL_ORGAO_PAGADOR');
            
            // Build array of actual tipos available
            const actualTipos = [];
            if (actualAvancado && actualAvancado !== '') actualTipos.push('AVANCADO');
            if (actualPresenca && actualPresenca !== '') actualTipos.push('PRESENCA');
            if (actualUnidadeNegocio && actualUnidadeNegocio !== '') actualTipos.push('UNIDADE_NEGOCIO');
            if (actualOrgaoPagador && actualOrgaoPagador !== '') actualTipos.push('ORGAO_PAGADOR');
            
            console.log(`Row ${i} - Shown: ${shownTipo}, Actual: [${actualTipos.join(', ')}]`);
            
            // Find additional tipos (not shown in descadastramento)
            const additionalTipos = actualTipos.filter(tipo => tipo !== shownTipo);
            
            let exportTipo = shownTipo; // Default to shown tipo
            let isAdditionalTipo = false;
            
            if (additionalTipos.length > 0) {
                // Apply hierarchy: AVANCADO/UNIDADE_NEGOCIO > ORGAO_PAGADOR/PRESENCA
                if (additionalTipos.includes('AVANCADO')) {
                    exportTipo = 'AVANCADO';
                    isAdditionalTipo = true;
                } else if (additionalTipos.includes('UNIDADE_NEGOCIO')) {
                    exportTipo = 'UNIDADE_NEGOCIO';
                    isAdditionalTipo = true;
                } else if (additionalTipos.includes('ORGAO_PAGADOR')) {
                    exportTipo = 'ORGAO_PAGADOR';
                    isAdditionalTipo = true;
                } else if (additionalTipos.includes('PRESENCA')) {
                    exportTipo = 'PRESENCA';
                    isAdditionalTipo = true;
                }
            }
            
            console.log(`Row ${i} - Export Tipo: ${exportTipo}, IsAdditional: ${isAdditionalTipo}`);
            
            // Generate TXT based on export tipo and whether it's additional
            txtContent += generateDescadastramentoTXT(empresa, codigoLoja, exportTipo, isAdditionalTipo, contrato);
            
        } else {
            // CADASTRAMENTO/ALL/HISTORICO FORMAT (original logic)
            const tipoCorrespondente = getTipoCorrespondenteByDataConclusao(row);
            
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

// New function to generate descadastramento TXT content
function generateDescadastramentoTXT(empresa, codigoLoja, exportTipo, isAdditionalTipo, contrato) {
    let txtContent = '';
    
    console.log(`generateDescadastramentoTXT: ${empresa}-${codigoLoja}, Tipo: ${exportTipo}, Additional: ${isAdditionalTipo}`);
    
    if (isAdditionalTipo) {
        // TXT FOR ADDITIONAL TIPOS (store has more tipos than shown)
        if (exportTipo === 'AVANCADO') {
            // PLACEHOLDER: AVANCADO ADDITIONAL TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**AVANCADO_ADDITIONAL**\r\n';
            
        } else if (exportTipo === 'UNIDADE_NEGOCIO') {
            // PLACEHOLDER: UNIDADE_NEGOCIO ADDITIONAL TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**UNIDADE_NEGOCIO_ADDITIONAL**\r\n';
            
        } else if (exportTipo === 'ORGAO_PAGADOR') {
            // PLACEHOLDER: ORGAO_PAGADOR ADDITIONAL TXT FORMAT
            const version = extractVersionFromContract(contrato);
            if (version !== null && version >= 8.1 && version <= 10.1) {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_V8**\r\n';
            } else if (version !== null && version > 10.1) {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_V10**\r\n';
            } else {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_DEFAULT**\r\n';
            }
            
        } else if (exportTipo === 'PRESENCA') {
            // PLACEHOLDER: PRESENCA ADDITIONAL TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**PRESENCA_ADDITIONAL**\r\n';
        }
        
    } else {
        // TXT FOR SINGLE TIPOS (store has only the shown tipo)
        if (exportTipo === 'AVANCADO') {
            // PLACEHOLDER: AVANCADO SINGLE TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**AVANCADO_SINGLE_ONLY**\r\n';
            
        } else if (exportTipo === 'UNIDADE_NEGOCIO') {
            // PLACEHOLDER: UNIDADE_NEGOCIO SINGLE TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**UNIDADE_NEGOCIO_SINGLE_ONLY**\r\n';
            
        } else if (exportTipo === 'ORGAO_PAGADOR') {
            // PLACEHOLDER: ORGAO_PAGADOR SINGLE TXT FORMAT
            const version = extractVersionFromContract(contrato);
            if (version !== null && version >= 8.1 && version <= 10.1) {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_SINGLE_ONLY_V8**\r\n';
            } else if (version !== null && version > 10.1) {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_SINGLE_ONLY_V10**\r\n';
            } else {
                txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_SINGLE_ONLY_DEFAULT**\r\n';
            }
            
        } else if (exportTipo === 'PRESENCA') {
            // PLACEHOLDER: PRESENCA SINGLE TXT FORMAT
            txtContent += formatToTXTLine(0, 0, 0, 0, 0, 0, 0, 0, 0, 0) + '**PRESENCA_SINGLE_ONLY**\r\n';
        }
    }
    
    return txtContent;
}


--------------



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
            
            // Add actual tipos data for descadastramento
            if ($actualFilter === 'descadastramento') {
                $xml .= '<ACTUAL_AVANCADO>' . addcslashes(isset($row['ACTUAL_AVANCADO']) ? $row['ACTUAL_AVANCADO'] : '', '"<>&') . '</ACTUAL_AVANCADO>';
                $xml .= '<ACTUAL_PRESENCA>' . addcslashes(isset($row['ACTUAL_PRESENCA']) ? $row['ACTUAL_PRESENCA'] : '', '"<>&') . '</ACTUAL_PRESENCA>';
                $xml .= '<ACTUAL_UNIDADE_NEGOCIO>' . addcslashes(isset($row['ACTUAL_UNIDADE_NEGOCIO']) ? $row['ACTUAL_UNIDADE_NEGOCIO'] : '', '"<>&') . '</ACTUAL_UNIDADE_NEGOCIO>';
                $xml .= '<ACTUAL_ORGAO_PAGADOR>' . addcslashes(isset($row['ACTUAL_ORGAO_PAGADOR']) ? $row['ACTUAL_ORGAO_PAGADOR'] : '', '"<>&') . '</ACTUAL_ORGAO_PAGADOR>';
                $xml .= '<ACTUAL_TIPOS_ALL>' . addcslashes(isset($row['ACTUAL_TIPOS_ALL']) ? $row['ACTUAL_TIPOS_ALL'] : '', '"<>&') . '</ACTUAL_TIPOS_ALL>';
            }
            
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


