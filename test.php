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
                
                // NEW LOGIC: Check if we have the new dual structure
                const hasInclusaoData = xmlDoc.getElementsByTagName('inclusaoTxtData').length > 0;
                const hasAlteracaoData = xmlDoc.getElementsByTagName('alteracaoTxtData').length > 0;
                
                console.log('Has inclusao data:', hasInclusaoData);
                console.log('Has alteracao data:', hasAlteracaoData);
                
                if (hasInclusaoData || hasAlteracaoData) {
                    // NEW DUAL FILE LOGIC
                    const timestamp = getCurrentTimestamp();
                    let filesDownloaded = 0;
                    const totalFiles = (hasInclusaoData ? 1 : 0) + (hasAlteracaoData ? 1 : 0);
                    
                    // Download Inclusao file
                    if (hasInclusaoData) {
                        const inclusaoTxtData = extractTXTFromXMLByType(xmlDoc, 'inclusao');
                        if (inclusaoTxtData.length > 0) {
                            const inclusaoFilename = `dashboard_inclusao_${filter}_${timestamp}.txt`;
                            console.log('Downloading inclusao file:', inclusaoFilename);
                            downloadTXTFile(inclusaoTxtData, inclusaoFilename);
                            filesDownloaded++;
                        }
                    }
                    
                    // Download Alteracao file with delay
                    if (hasAlteracaoData) {
                        setTimeout(() => {
                            const alteracaoTxtData = extractTXTFromXMLByType(xmlDoc, 'alteracao');
                            if (alteracaoTxtData.length > 0) {
                                const alteracaoFilename = `dashboard_alteracao_${filter}_${timestamp}.txt`;
                                console.log('Downloading alteracao file:', alteracaoFilename);
                                downloadTXTFile(alteracaoTxtData, alteracaoFilename);
                                filesDownloaded++;
                            }
                        }, 1500); // 1.5 second delay
                    }
                    
                    console.log(`Initiated download of ${filesDownloaded} files`);
                    
                } else {
                    // FALLBACK TO OLD LOGIC if new structure not found
                    console.log('Using fallback to old single file logic');
                    const txtData = extractTXTFromXML(xmlDoc);
                    
                    if (txtData.length === 0) {
                        alert('Erro: Conteúdo TXT vazio');
                        return;
                    }
                    
                    const filename = `dashboard_selected_${filter}_${getCurrentTimestamp()}.txt`;
                    downloadTXTFile(txtData, filename);
                }

                setTimeout(() => {
                    console.log('Reloading after TXT Export...');
                    CheckboxModule.clearSelections();
                    const currentFilter = FilterModule.currentFilter;
                    FilterModule.loadTableData(currentFilter);
                }, 3000);
                
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

    console.log(`Processing ${fileType} with ${rows.length} rows`);

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

        const tipoManutencao = fileType === 'inclusao' ? '1' : '2'; // Key difference between files

        if (actualFilter === 'descadastramento') {
            // DESCADASTRAMENTO LOGIC
            const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
            const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
            
            console.log(`Descadastramento ${fileType} - TxtType: ${descadastroTxtType}, OriginalTipo: ${descadastroOriginalTipo}`);
            
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

    console.log(`Generated ${fileType} TXT content length:`, txtContent.length);
    return txtContent;
}

// Helper function to extract version from contract
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