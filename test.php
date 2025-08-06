// UPDATED: Helper method with special ORGAO_PAGADOR logic
private function determineDescadastroTXTType($chaveLoja, $descadastroTipo, $actualTipoData) {
    // If no actual data found, assume descadastramento tipo only
    if (!isset($actualTipoData[$chaveLoja])) {
        return 'ONLY_' . strtoupper($descadastroTipo);
    }
    
    $actualData = $actualTipoData[$chaveLoja];
    $descadastroTipoUpper = strtoupper($descadastroTipo);
    
    // Normalize descadastramento tipo variants
    if ($descadastroTipoUpper === 'ORG_PAGADOR') {
        $descadastroTipoUpper = 'ORGAO_PAGADOR';
    }
    
    // Check for ACTUAL tipos in the data (not NULL)
    $hasAvancado = !empty($actualData['AVANCADO']);
    $hasUnidadeNegocio = !empty($actualData['UNIDADE_NEGOCIO']);
    $hasOrgaoPagador = !empty($actualData['ORGAO_PAGADOR']);  
    $hasPresenca = !empty($actualData['PRESENCA']);
    
    // Collect all ACTUAL tipos that exist (ignoring what descadastramento shows)
    $actualTipos = array();
    if ($hasAvancado) $actualTipos[] = 'AVANCADO';
    if ($hasUnidadeNegocio) $actualTipos[] = 'UNIDADE_NEGOCIO';
    if ($hasOrgaoPagador) $actualTipos[] = 'ORGAO_PAGADOR';
    if ($hasPresenca) $actualTipos[] = 'PRESENCA';
    
    // Remove the descadastramento tipo from actual tipos (assume it's there as per requirement)
    $additionalTipos = array();
    foreach ($actualTipos as $tipo) {
        if ($tipo !== $descadastroTipoUpper) {
            $additionalTipos[] = $tipo;
        }
    }
    
    // If no additional tipos beyond what descadastramento shows
    if (empty($additionalTipos)) {
        return 'ONLY_' . $descadastroTipoUpper;
    }
    
    // SPECIAL LOGIC: If descadastramento shows ORGAO_PAGADOR, create combined types
    if ($descadastroTipoUpper === 'ORGAO_PAGADOR') {
        // Return combined tipo based on hierarchy: AVANCADO/UNIDADE_NEGOCIO > PRESENCA
        if (in_array('AVANCADO', $additionalTipos)) {
            return 'ORGAO_PAGADOR_ADDITIONAL_AVANCADO';
        }
        if (in_array('UNIDADE_NEGOCIO', $additionalTipos)) {
            return 'ORGAO_PAGADOR_ADDITIONAL_UNIDADE_NEGOCIO';
        }
        if (in_array('PRESENCA', $additionalTipos)) {
            return 'ORGAO_PAGADOR_ADDITIONAL_PRESENCA';
        }
    } else {
        // NORMAL LOGIC: For non-ORGAO_PAGADOR tipos, use standard priority
        if (in_array('AVANCADO', $additionalTipos)) {
            return 'ADDITIONAL_AVANCADO';
        }
        if (in_array('UNIDADE_NEGOCIO', $additionalTipos)) {
            return 'ADDITIONAL_UNIDADE_NEGOCIO';
        }
        if (in_array('ORGAO_PAGADOR', $additionalTipos)) {
            return 'ADDITIONAL_ORGAO_PAGADOR';
        }
        if (in_array('PRESENCA', $additionalTipos)) {
            return 'ADDITIONAL_PRESENCA';
        }
    }
    
    // Fallback (should not reach here)
    return 'ONLY_' . $descadastroTipoUpper;
}


-------------


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
            // DESCADASTRAMENTO LOGIC WITH ORGAO_PAGADOR SPECIAL HANDLING
            const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
            const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
            
            console.log(`Descadastramento - TxtType: ${descadastroTxtType}, OriginalTipo: ${descadastroOriginalTipo}`);
            
            // Handle ORGAO_PAGADOR combined types (NEW LOGIC)
            if (descadastroTxtType.startsWith('ORGAO_PAGADOR_ADDITIONAL_')) {
                const additionalTipo = descadastroTxtType.replace('ORGAO_PAGADOR_ADDITIONAL_', '');
                
                if (additionalTipo === 'AVANCADO') {
                    // PLACEHOLDER: Export TXT for ORGAO_PAGADOR + AVANCADO combination
                    console.log('Exporting TXT for ORGAO_PAGADOR + ADDITIONAL AVANCADO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_AVANCADO**\r\n';
                    
                } else if (additionalTipo === 'UNIDADE_NEGOCIO') {
                    // PLACEHOLDER: Export TXT for ORGAO_PAGADOR + UNIDADE_NEGOCIO combination
                    console.log('Exporting TXT for ORGAO_PAGADOR + ADDITIONAL UNIDADE_NEGOCIO');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_UNIDADE_NEGOCIO**\r\n';
                    
                } else if (additionalTipo === 'PRESENCA') {
                    // PLACEHOLDER: Export TXT for ORGAO_PAGADOR + PRESENCA combination
                    console.log('Exporting TXT for ORGAO_PAGADOR + ADDITIONAL PRESENCA');
                    txtContent += formatToTXTLine(empresa, codigoLoja, 0, 0, 0, 0, 0, 0, 0, 0) + '**ORGAO_PAGADOR_ADDITIONAL_PRESENCA**\r\n';
                }
                
            // Handle regular ADDITIONAL types (for non-ORGAO_PAGADOR originals)
            } else if (descadastroTxtType.startsWith('ADDITIONAL_')) {
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
                
            // Handle ONLY types (store has only that tipo)
            } else if (descadastroTxtType.startsWith('ONLY_')) {
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