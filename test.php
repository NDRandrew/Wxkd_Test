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
                $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
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
        
        $xml = '<response><success>true</success>';
        
        // INCLUSAO TXT DATA
        $xml .= '<inclusaoTxtData>';
        $recordsToUpdate = array();
        
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<chave_loja>' . addcslashes(isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '', '"<>&') . '</chave_loja>';
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
            $xml .= '<file_type>inclusao</file_type>';
            
            // NEW: Add descadastramento-specific logic
            if ($actualFilter === 'descadastramento') {
                $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
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
        $xml .= '</inclusaoTxtData>';
        
        // ALTERACAO TXT DATA (same data, different processing)
        $xml .= '<alteracaoTxtData>';
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<chave_loja>' . addcslashes(isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '', '"<>&') . '</chave_loja>';
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
            $xml .= '<file_type>alteracao</file_type>';
            
            // Add same descadastramento logic for alteracao
            if ($actualFilter === 'descadastramento') {
                $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
                $descadastroTipo = isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '';
                
                $txtExportType = $this->determineDescadastroTXTType($chaveLoja, $descadastroTipo, $actualTipoData);
                
                $xml .= '<descadastro_txt_type>' . addcslashes($txtExportType, '"<>&') . '</descadastro_txt_type>';
                $xml .= '<descadastro_original_tipo>' . addcslashes($descadastroTipo, '"<>&') . '</descadastro_original_tipo>';
                
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
        }
        $xml .= '</alteracaoTxtData>';
        
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
        
        $xml .= '</response>';
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


--_-_--


function exportTXTData(selectedIds, filter) {
    showLoading();
    
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

                 if (getCurrentFilter() === 'descadastramento') {
                    window.debugDescadastroLogic(xmlDoc);
                }

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
                
                // Extract both TXT file contents
                const inclusaoTxtData = extractTXTFromXMLByType(xmlDoc, 'inclusao');
                const alteracaoTxtData = extractTXTFromXMLByType(xmlDoc, 'alteracao');
                
                if (inclusaoTxtData.length === 0 && alteracaoTxtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
                const timestamp = getCurrentTimestamp();
                
                // Download Inclusao file
                if (inclusaoTxtData.length > 0) {
                    const inclusaoFilename = `dashboard_inclusao_${filter}_${timestamp}.txt`;
                    downloadTXTFile(inclusaoTxtData, inclusaoFilename);
                }
                
                // Download Alteracao file with delay
                if (alteracaoTxtData.length > 0) {
                    setTimeout(() => {
                        const alteracaoFilename = `dashboard_alteracao_${filter}_${timestamp}.txt`;
                        downloadTXTFile(alteracaoTxtData, alteracaoFilename);
                    }, 1500); // 1.5 second delay to avoid browser download conflicts
                }

                setTimeout(() => {
                    console.log('Reloading after TXT Export...');
                    CheckboxModule.clearSelections();
                    const currentFilter = FilterModule.currentFilter;
                    FilterModule.loadTableData(currentFilter);
                }, 3000); // Increased delay to account for both downloads
                
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

function extractTXTFromXMLByType(xmlDoc, fileType) {
    let txtContent = '';
    const dataNodeName = fileType === 'inclusao' ? 'inclusaoTxtData' : 'alteracaoTxtData';
    const dataNode = xmlDoc.getElementsByTagName(dataNodeName)[0];
    
    if (!dataNode) {
        console.log(`No ${dataNodeName} found in XML`);
        return '';
    }
    
    const rows = dataNode.getElementsByTagName('row');
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

        console.log(`Processing ${fileType} row ${i}: Empresa=${empresa}, Loja=${codigoLoja}, Tipo=${tipoCorrespondente}, Contrato=${contrato}, ActualFilter=${actualFilter}`);

        if (actualFilter === 'descadastramento') {
            // DESCADASTRAMENTO LOGIC - same as before but with different tipoManutencao
            const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
            const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
            
            console.log(`Descadastramento ${fileType} - TxtType: ${descadastroTxtType}, OriginalTipo: ${descadastroOriginalTipo}`);
            
            const tipoManutencao = fileType === 'inclusao' ? '1' : '2'; // Key difference
            
            // Handle ORGAO_PAGADOR combined types 
            if (descadastroTxtType.startsWith('ORGAO_PAGADOR_ADDITIONAL_')) {
                const additionalTipo = descadastroTxtType.replace('ORGAO_PAGADOR_ADDITIONAL_', '');
                
                if (additionalTipo === 'AVANCADO') {
                    console.log(`Exporting ${fileType} TXT for ORGAO_PAGADOR + ADDITIONAL AVANCADO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (additionalTipo === 'UNIDADE_NEGOCIO') {
                    console.log(`Exporting ${fileType} TXT for ORGAO_PAGADOR + ADDITIONAL UNIDADE_NEGOCIO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (additionalTipo === 'PRESENCA') {
                    console.log(`Exporting ${fileType} TXT for ORGAO_PAGADOR + ADDITIONAL PRESENCA`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 300000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 500000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                }
                
            // Handle regular ADDITIONAL types (for non-ORGAO_PAGADOR originals)
            } else if (descadastroTxtType.startsWith('ADDITIONAL_')) {
                const additionalTipo = descadastroTxtType.replace('ADDITIONAL_', '');
                
                if (additionalTipo === 'AVANCADO') {
                    console.log(`Exporting ${fileType} TXT for ADDITIONAL AVANCADO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (additionalTipo === 'UNIDADE_NEGOCIO') {
                    console.log(`Exporting ${fileType} TXT for ADDITIONAL UNIDADE_NEGOCIO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (additionalTipo === 'ORGAO_PAGADOR') {
                    console.log(`Exporting ${fileType} TXT for ADDITIONAL ORGAO_PAGADOR`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 300000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 500000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (additionalTipo === 'PRESENCA') {
                    console.log(`Exporting ${fileType} TXT for ADDITIONAL PRESENCA`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 300000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 500000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 200000, 1, 0, tipoManutencao, 0) + '\r\n';
                }
                
            // Handle ONLY types (store has only that tipo)
            } else if (descadastroTxtType.startsWith('ONLY_')) {
                const onlyTipo = descadastroTxtType.replace('ONLY_', '');
                
                if (onlyTipo === 'AVANCADO') {
                    console.log(`Exporting ${fileType} TXT for ONLY AVANCADO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (onlyTipo === 'UNIDADE_NEGOCIO') {
                    console.log(`Exporting ${fileType} TXT for ONLY UNIDADE_NEGOCIO`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 1000000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 1000000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (onlyTipo === 'ORGAO_PAGADOR' || onlyTipo === 'ORG_PAGADOR') {
                    console.log(`Exporting ${fileType} TXT for ONLY ORGAO_PAGADOR`);
                    
                    // Check contract version for OP-specific logic
                    const version = extractVersionFromContract(contrato);
                    if (version !== null && version >= 8.1 && version <= 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    } else if (version !== null && version > 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    } else {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 2, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 350000, 2, 0, tipoManutencao, 0) + '\r\n';
                    }
                    
                } else if (onlyTipo === 'PRESENCA') {
                    console.log(`Exporting ${fileType} TXT for ONLY PRESENCA`);
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, 300000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, 500000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, 200000, 2, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, 200000, 2, 0, tipoManutencao, 0) + '\r\n';
                }
            }
            
        } else {
            // CADASTRAMENTO/ALL/HISTORICO FORMAT (original logic with tipoManutencao)
            if (actualFilter === 'cadastramento' || actualFilter === 'all' || currentFilter === 'historico') {
                const tipoMapping = {
                    'AV': 'AVANCADO',
                    'PR': 'PRESENCA', 
                    'UN': 'UNIDADE_NEGOCIO',
                    'OP': 'ORGAO_PAGADOR'
                };
                
                const tipoCompleto = Object.keys(tipoMapping).find(key => tipoMapping[key] === tipoCorrespondente) || tipoCorrespondente;
                console.log(`${fileType} - Tipo completo: ` + tipoCompleto);
                
                const tipoManutencao = fileType === 'inclusao' ? '1' : '2'; // Key difference
                
                if (['AV', 'PR', 'UN'].includes(tipoCompleto) || ['AVANCADO', 'PRESENCA', 'UNIDADE_NEGOCIO'].includes(tipoCorrespondente)) {
                    let limits;
                    if (tipoCompleto === 'AV' || tipoCorrespondente === 'AVANCADO' || tipoCompleto === 'UN' || tipoCorrespondente === 'UNIDADE_NEGOCIO') {
                        limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                    } else if (tipoCompleto === 'PR' || tipoCorrespondente === 'PRESENCA' || tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                        limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                    } else {
                        limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                    }

                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, tipoManutencao, 0) + '\r\n';
                    txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, tipoManutencao, 0) + '\r\n';
                    
                } else if (tipoCompleto === 'OP' || tipoCorrespondente === 'ORGAO_PAGADOR') {
                    limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };

                    const version = extractVersionFromContract(contrato);
                    if (version !== null && version >= 8.1 && version <= 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, tipoManutencao, 0) + '\r\n';
                    } else if (version !== null && version > 10.1) {
                        txtContent += formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 1, 0, tipoManutencao, 0) + '\r\n';
                        txtContent += formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, tipoManutencao, 0) + '\r\n';
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



------

// Replace the existing extractTXTFromXML function with this updated version
function extractTXTFromXML(xmlDoc) {
    // This function is kept for backward compatibility but now calls the new function
    return extractTXTFromXMLByType(xmlDoc, 'inclusao');
}

// Update the button text to indicate two files will be downloaded
function updateExportButtonText() {
    const checkedCount = $('.row-checkbox:checked').length;
    $('#selectedCount').text(checkedCount);
    $('#selectedCountFlags').text(checkedCount);
    
    const isDisabled = checkedCount === 0;
    $('#exportTxtBtn').prop('disabled', isDisabled);
    $('#updateFlagsBtn').prop('disabled', isDisabled);
    
    let buttonText = 'Exportar 2 TXT'; // Updated to indicate two files
    if (FilterModule.currentFilter === 'cadastramento' || FilterModule.currentFilter === 'descadastramento') {
        buttonText = 'Converter para 2 TXT'; // Updated to indicate two files
    }
    
    const btnContent = $('#exportTxtBtn').html();
    if (btnContent) {
        const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
        $('#exportTxtBtn').html(newContent);
    }
}

// Update the CheckboxModule to use the new button text function
CheckboxModule.updateExportButton = function() {
    updateExportButtonText();
};

// Add this to handle the delay between downloads better
function showDownloadProgress(fileType, current, total) {
    const progressMessages = {
        'inclusao': 'Baixando arquivo de inclusão...',
        'alteracao': 'Baixando arquivo de alteração...'
    };
    
    console.log(`Download Progress: ${progressMessages[fileType]} (${current}/${total})`);
    
    // You can add a visual progress indicator here if desired
    // For example, show a small notification or progress bar
}

// Enhanced download function with better error handling
function downloadTXTFileEnhanced(txtContent, filename, fileType) {
    try {
        console.log(`Starting download of ${fileType}: ${filename}`);
        const txtWithBOM = '\uFEFF' + txtContent;
        const blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
        downloadFile(blob, filename);
        console.log(`Successfully initiated download of ${fileType}: ${filename}`);
    } catch (error) {
        console.error(`Error downloading ${fileType} file:`, error);
        alert(`Erro ao baixar arquivo ${fileType}: ` + error.message);
    }
}

// Update the main download function to show progress
function downloadTXTFile(txtContent, filename) {
    // Determine file type from filename
    const fileType = filename.includes('inclusao') ? 'inclusao' : 
                    filename.includes('alteracao') ? 'alteracao' : 'unknown';
    
    downloadTXTFileEnhanced(txtContent, filename, fileType);
}

// Helper function to extract version from contract (if not already present)
function extractVersionFromContract(tipoContrato) {
    if (!tipoContrato || typeof tipoContrato !== 'string') {
        return null;
    }
    
    const match = tipoContrato.match(/(\d+\.\d+)/);
    if (match) {
        const version = parseFloat(match[1]);
        return !isNaN(version) ? version : null;
    }
    return null;
}

// Update the success message to mention both files
function showExportSuccessMessage() {
    const alertHtml = `
        <div class="alert alert-success success-alert" style="
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            z-index: 9999; 
            min-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 5px;
        ">
            <button class="close" onclick="$(this).parent().remove()" style="
                color: #3c763d !important; 
                opacity: 0.7; 
                position: absolute; 
                top: 10px; 
                right: 15px;
            ">
                <i class="fa fa-times"></i>
            </button>
            <div style="padding: 20px 40px 20px 20px;">
                <i class="fa fa-check-circle" style="color: #3c763d; font-size: 20px; margin-right: 10px;"></i>
                <strong>Sucesso!</strong>
                <p style="margin-top: 10px; color: #3c763d;">
                    Dois arquivos TXT foram gerados:<br>
                    • Arquivo de Inclusão (Tipo Manutenção: 1)<br>
                    • Arquivo de Alteração (Tipo Manutenção: 2)
                </p>
            </div>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(() => {
        $('.success-alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 8000); // Longer display time since there's more information
}

// Optional: Add this CSS to improve the button appearance
const dualExportButtonCSS = `
<style id="dual-export-button-styles">
#exportTxtBtn {
    background-color: #2E7D32 !important; /* Darker green to indicate enhanced functionality */
    border-color: #2E7D32 !important;
    position: relative;
}

#exportTxtBtn:before {
    content: "2×";
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #FF5722;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 10px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

#exportTxtBtn:disabled:before {
    display: none;
}

#exportTxtBtn:hover {
    background-color: #1B5E20 !important;
    border-color: #1B5E20 !important;
}
</style>
`;

// Add the CSS when the document is ready
$(document).ready(() => {
    // Remove any existing styles first
    $('#dual-export-button-styles').remove();
    $('head').append(dualExportButtonCSS);
});

// Debug function to verify the dual export is working
window.debugDualExport = function() {
    console.log('=== DUAL EXPORT DEBUG ===');
    console.log('Current Filter:', FilterModule.currentFilter);
    console.log('Button Text:', $('#exportTxtBtn').text());
    console.log('Selected Count:', $('.row-checkbox:checked').length);
    console.log('Button Disabled:', $('#exportTxtBtn').prop('disabled'));
    console.log('=== END DUAL EXPORT DEBUG ===');
};