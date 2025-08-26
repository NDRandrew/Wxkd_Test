public function exportXLSX() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    if ($filter === 'historico') {
        $this->exportHistoricoXLSX($selectedIds);
        return;
    }
    
    $data = $this->getFilteredData($filter, $selectedIds);
    
    if (empty($data)) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<e>Nenhum dado encontrado para exportação</e>';
        $xml .= '</response>';
        echo $xml;
        exit;
    }
    
    $xml = '<response><success>true</success><xlsxData>';
    
    foreach ($data as $row) {
        $xml .= '<row>';
        $xml .= '<chave_loja>' . addcslashes(isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '', '"<>&') . '</chave_loja>';
        $nome_loja = isset($row['Nome_Loja']) ? $row['Nome_Loja'] : '';
        $xml .= '<nome_loja><![CDATA[' . $nome_loja . ']]></nome_loja>';
        $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '', '"<>&') . '</cod_empresa>';
        $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
        $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
        $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
        $xml .= '<data_conclusao>' . addcslashes(isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '', '"<>&') . '</data_conclusao>';
        
        $this->addDateFieldsToXML($xml, $row);
        
        $xml .= '<data_solicitacao>';
        $timeAndre = isset($row['DATA_SOLICITACAO']) ? strtotime($row['DATA_SOLICITACAO']) : false;
        if ($timeAndre !== false && !empty($row['DATA_SOLICITACAO'])) {
            $xml .= date('d/m/Y', $timeAndre);
        } else {
            $fields = Wxkd_Config::getFieldMappings();
            $cutoff = Wxkd_Config::getCutoffTimestamp();
            $matchingDates = array();

            foreach ($fields as $field => $label) {
                $raw = isset($row[$field]) ? trim($row[$field]) : '';

                if (!empty($raw)) {
                    $parts = explode('/', $raw);
                    if (count($parts) === 3) {
                        $day = (int)$parts[0];
                        $month = (int)$parts[1];
                        $year = (int)$parts[2];

                        if (checkdate($month, $day, $year)) {
                            $timestamp = mktime(0, 0, 0, $month, $day, $year);
                            if ($timestamp > $cutoff) {
                                $matchingDates[] = $raw;
                            }
                        }
                    }
                }
            }

            $xml .= !empty($matchingDates) ? implode(' / ', $matchingDates) : '—';
        }
        $xml .= '</data_solicitacao>';
        
        $this->addValidationFieldsToXMLForXLSX($xml, $row);
        
        $xml .= '</row>';
    }
    
    $xml .= '</xlsxData></response>';
    echo $xml;
    exit;
}

private function exportHistoricoXLSX($selectedIds) {
    try {
        $data = $this->getHistoricoFilteredData($selectedIds);
        
        if (empty($data)) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>Nenhum dado encontrado para exportação</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
        
        $xml = '<response><success>true</success><xlsxData>';
        
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<chave_lote>' . addcslashes(isset($row['CHAVE_LOTE']) ? $row['CHAVE_LOTE'] : '', '"<>&') . '</chave_lote>';
            $xml .= '<data_log>';
            $timeAndre = strtotime($row['DATA_LOG']);
            if ($timeAndre !== false && !empty($row['DATA_LOG'])) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $xml .= '—';
            }
            $xml .= '</data_log>';
            $xml .= '<chave_loja>' . addcslashes(isset($row['CHAVE_LOJA']) ? $row['CHAVE_LOJA'] : '', '"<>&') . '</chave_loja>';
            $nome_loja = isset($row['NOME_LOJA']) ? $row['NOME_LOJA'] : '';
            $xml .= '<nome_loja><![CDATA[' . $nome_loja . ']]></nome_loja>';
            $xml .= '<cod_empresa>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa>';
            $xml .= '<cod_loja>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja>';
            $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
            $xml .= '<data_conclusao>';
            $timeAndre = strtotime($row['DATA_CONCLUSAO']);
            if ($timeAndre !== false && !empty($row['DATA_CONCLUSAO'])) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $xml .= '—';
            }
            $xml .= '</data_conclusao>';
            $xml .= '<data_solicitacao>';
            $timeAndre = strtotime($row['DATA_SOLICITACAO']);
            if ($timeAndre !== false && !empty($row['DATA_SOLICITACAO'])) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $xml .= '—';
            }
            $xml .= '</data_solicitacao>';
            $xml .= '<dep_dinheiro>' . addcslashes(isset($row['DEP_DINHEIRO']) ? $row['DEP_DINHEIRO'] : '', '"<>&') . '</dep_dinheiro>';
            $xml .= '<dep_cheque>' . addcslashes(isset($row['DEP_CHEQUE']) ? $row['DEP_CHEQUE'] : '', '"<>&') . '</dep_cheque>';
            $xml .= '<rec_retirada>' . addcslashes(isset($row['REC_RETIRADA']) ? $row['REC_RETIRADA'] : '', '"<>&') . '</rec_retirada>';
            $xml .= '<saque_cheque>' . addcslashes(isset($row['SAQUE_CHEQUE']) ? $row['SAQUE_CHEQUE'] : '', '"<>&') . '</saque_cheque>';
            $xml .= '<segunda_via_cartao>' . addcslashes(isset($row['SEGUNDA_VIA_CARTAO']) ? $row['SEGUNDA_VIA_CARTAO'] : '', '"<>&') . '</segunda_via_cartao>';
            $xml .= '<holerite_inss>' . addcslashes(isset($row['HOLERITE_INSS']) ? $row['HOLERITE_INSS'] : '', '"<>&') . '</holerite_inss>';
            $xml .= '<cons_inss>' . addcslashes(isset($row['CONS_INSS']) ? $row['CONS_INSS'] : '', '"<>&') . '</cons_inss>';
            $xml .= '<prova_de_vida>' . addcslashes(isset($row['PROVA_DE_VIDA']) ? $row['PROVA_DE_VIDA'] : '', '"<>&') . '</prova_de_vida>';
            $xml .= '<data_contrato>';
            $timeAndre = strtotime($row['DATA_CONTRATO']);
            if ($timeAndre !== false && !empty($row['DATA_CONTRATO'])) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $xml .= '—';
            }
            $xml .= '</data_contrato>';
            $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
            $xml .= '<filtro>' . addcslashes(isset($row['FILTRO']) ? $row['FILTRO'] : '', '"<>&') . '</filtro>';
            $xml .= '</row>';
        }
        
        $xml .= '</xlsxData></response>';
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

private function addValidationFieldsToXMLForXLSX(&$xml, $row) {
    $validationFields = array(
        'dep_dinheiro' => 'DEP_DINHEIRO_VALID',
        'dep_cheque' => 'DEP_CHEQUE_VALID',
        'rec_retirada' => 'REC_RETIRADA_VALID',
        'saque_cheque' => 'SAQUE_CHEQUE_VALID'
    );
    
    foreach ($validationFields as $xmlField => $validField) {
        $xml .= '<' . $xmlField . '>';
        if (isset($row[$validField]) && isset($row['TIPO_LIMITES'])) {
            $isPresencaOrOrgao = (strpos($row['TIPO_LIMITES'], 'PRESENCA') !== false || 
                            strpos($row['TIPO_LIMITES'], 'ORG_PAGADOR') !== false);
            $isAvancadoOrApoio = (strpos($row['TIPO_LIMITES'], 'AVANCADO') !== false || 
                            strpos($row['TIPO_LIMITES'], 'UNIDADE_NEGOCIO') !== false);
            
            $limits = $this->getLimitsForField($xmlField, $isPresencaOrOrgao, $isAvancadoOrApoio);
            
            if ($row[$validField] == 1) {
                $xml .= $limits['valid'];
            } else {
                $xml .= $limits['invalid'];
            }
        } else {
            $xml .= 'Tipo não definido';
        }
        $xml .= '</' . $xmlField . '>';
    }
    
    // Check if ORGAO_PAGADOR is empty for this row
    $orgaoPagadorEmpty = empty($row['ORGAO_PAGADOR']) || trim($row['ORGAO_PAGADOR']) === '';
    
    // Add validation status flags for JavaScript processing
    $xml .= '<orgao_pagador_empty>' . ($orgaoPagadorEmpty ? 'true' : 'false') . '</orgao_pagador_empty>';
    $xml .= '<segunda_via_cartao_valid>' . (isset($row['SEGUNDA_VIA_CARTAO_VALID']) ? $row['SEGUNDA_VIA_CARTAO_VALID'] : '0') . '</segunda_via_cartao_valid>';
    $xml .= '<holerite_inss_valid>' . (isset($row['HOLERITE_INSS_VALID']) ? $row['HOLERITE_INSS_VALID'] : '0') . '</holerite_inss_valid>';
    $xml .= '<consulta_inss_valid>' . (isset($row['CONSULTA_INSS_VALID']) ? $row['CONSULTA_INSS_VALID'] : '0') . '</consulta_inss_valid>';
    $xml .= '<prova_de_vida_valid>' . (isset($row['PROVA_DE_VIDA_VALID']) ? $row['PROVA_DE_VIDA_VALID'] : '0') . '</prova_de_vida_valid>';
    
    $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
    $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
}


--------


// Replace the existing exportCSVData function
function exportXLSXData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportXLSX&filter=${filter}${selectedIds ? '&ids=' + selectedIds : ''}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(responseText, 'text/xml');
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    alert('Erro ao buscar dados para exportação');
                    return;
                }
                
                const xlsxData = extractXLSXDataFromXML(xmlDoc, filter);
                const filename = `dashboard_${filter}_${getCurrentTimestamp()}.xlsx`;
                downloadXLSXFile(xlsxData, filename);
                
            } catch (e) {
                console.error('Erro ao processar XML:', e);
                alert('Erro ao processar dados');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição');
        });
}

// Replace the existing exportAllCSV function
function exportAllXLSX() {
    const filter = getCurrentFilter();
    exportXLSXData('', filter);
}

function extractXLSXDataFromXML(xmlDoc, filter) {
    if (filter === 'historico') {
        return extractHistoricoXLSXFromXML(xmlDoc);
    } else {
        return extractStandardXLSXFromXML(xmlDoc);
    }
}

function extractStandardXLSXFromXML(xmlDoc) {
    const workbook = XLSX.utils.book_new();
    
    // Create headers
    const headers = [
        'CHAVE_LOJA', 'NOME_LOJA', 'COD_EMPRESA', 'COD_LOJA', 'QUANT_LOJAS',
        'TIPO_CORRESPONDENTE', 'DATA_CONCLUSAO', 'DATA_SOLICITACAO',
        'DEP_DINHEIRO', 'DEP_CHEQUE', 'REC_RETIRADA', 'SAQUE_CHEQUE',
        '2VIA_CARTAO', 'HOLERITE_INSS', 'CONS_INSS', 'PROVA_DE_VIDA',
        'DATA_CONTRATO', 'TIPO_CONTRATO'
    ];
    
    // Create worksheet data array
    const worksheetData = [headers];
    
    const rows = xmlDoc.getElementsByTagName('row');
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        
        const rowData = [
            getXMLNodeValue(row, 'chave_loja'),
            getXMLNodeValue(row, 'nome_loja'),
            getXMLNodeValue(row, 'cod_empresa'),
            getXMLNodeValue(row, 'cod_loja'),
            getXMLNodeValue(row, 'quant_lojas'),
            getXMLNodeValue(row, 'tipo_correspondente'),
            getXMLNodeValue(row, 'data_conclusao'),
            getXMLNodeValue(row, 'data_solicitacao'),
            getXMLNodeValue(row, 'dep_dinheiro'),
            getXMLNodeValue(row, 'dep_cheque'),
            getXMLNodeValue(row, 'rec_retirada'),
            getXMLNodeValue(row, 'saque_cheque'),
            getVersionSpecificTextForSegundaVia(row),
            getVersionSpecificTextForHolerite(row),
            getVersionSpecificTextForConsulta(row),
            getVersionSpecificTextForProvaVida(row),
            getXMLNodeValue(row, 'data_contrato'),
            getXMLNodeValue(row, 'tipo_contrato')
        ];
        
        worksheetData.push(rowData);
    }
    
    // Create worksheet
    const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
    
    // Auto-size columns
    const colWidths = [];
    for (let i = 0; i < headers.length; i++) {
        let maxWidth = headers[i].length;
        for (let j = 1; j < worksheetData.length; j++) {
            const cellValue = worksheetData[j][i] ? worksheetData[j][i].toString() : '';
            maxWidth = Math.max(maxWidth, cellValue.length);
        }
        colWidths.push({ wch: Math.min(maxWidth + 2, 50) });
    }
    worksheet['!cols'] = colWidths;
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Dashboard Data');
    
    return workbook;
}

function extractHistoricoXLSXFromXML(xmlDoc) {
    const workbook = XLSX.utils.book_new();
    
    // Create headers for historico
    const headers = [
        'CHAVE_LOTE', 'DATA_LOG', 'CHAVE_LOJA', 'NOME_LOJA', 'COD_EMPRESA', 'COD_LOJA',
        'TIPO_CORRESPONDENTE', 'DATA_CONCLUSAO', 'DATA_SOLICITACAO',
        'DEP_DINHEIRO', 'DEP_CHEQUE', 'REC_RETIRADA', 'SAQUE_CHEQUE',
        'SEGUNDA_VIA_CARTAO', 'HOLERITE_INSS', 'CONS_INSS', 'PROVA_DE_VIDA',
        'DATA_CONTRATO', 'TIPO_CONTRATO', 'FILTRO'
    ];
    
    // Create worksheet data array
    const worksheetData = [headers];
    
    const rows = xmlDoc.getElementsByTagName('row');
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        
        const rowData = [
            getXMLNodeValue(row, 'chave_lote'),
            getXMLNodeValue(row, 'data_log'),
            getXMLNodeValue(row, 'chave_loja'),
            getXMLNodeValue(row, 'nome_loja'),
            getXMLNodeValue(row, 'cod_empresa'),
            getXMLNodeValue(row, 'cod_loja'),
            getXMLNodeValue(row, 'tipo_correspondente'),
            getXMLNodeValue(row, 'data_conclusao'),
            getXMLNodeValue(row, 'data_solicitacao'),
            getXMLNodeValue(row, 'dep_dinheiro'),
            getXMLNodeValue(row, 'dep_cheque'),
            getXMLNodeValue(row, 'rec_retirada'),
            getXMLNodeValue(row, 'saque_cheque'),
            getXMLNodeValue(row, 'segunda_via_cartao'),
            getXMLNodeValue(row, 'holerite_inss'),
            getXMLNodeValue(row, 'cons_inss'),
            getXMLNodeValue(row, 'prova_de_vida'),
            getXMLNodeValue(row, 'data_contrato'),
            getXMLNodeValue(row, 'tipo_contrato'),
            getXMLNodeValue(row, 'filtro')
        ];
        
        worksheetData.push(rowData);
    }
    
    // Create worksheet
    const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);
    
    // Auto-size columns
    const colWidths = [];
    for (let i = 0; i < headers.length; i++) {
        let maxWidth = headers[i].length;
        for (let j = 1; j < worksheetData.length; j++) {
            const cellValue = worksheetData[j][i] ? worksheetData[j][i].toString() : '';
            maxWidth = Math.max(maxWidth, cellValue.length);
        }
        colWidths.push({ wch: Math.min(maxWidth + 2, 50) });
    }
    worksheet['!cols'] = colWidths;
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Histórico Data');
    
    return workbook;
}

// Version-specific text functions (matching your JavaScript logic)
function getVersionSpecificTextForSegundaVia(row) {
    const orgaoPagadorEmpty = getXMLNodeValue(row, 'orgao_pagador_empty') === 'true';
    
    if (orgaoPagadorEmpty) {
        return ' ----- ';
    }
    
    const isValid = getXMLNodeValue(row, 'segunda_via_cartao_valid') === '1';
    const tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
    const version = extractVersionFromContract(tipoContrato);
    
    if (version === null) {
        return 'Sem Contrato';
    }
    
    if (version < 8.1) {
        return 'Nao Apto';
    } else if (version >= 8.1 && version < 10.1) {
        return 'Nao Apto';
    } else if (version >= 10.1) {
        return 'Apto';
    } else {
        return 'Nao Apto';
    }
}

function getVersionSpecificTextForHolerite(row) {
    const orgaoPagadorEmpty = getXMLNodeValue(row, 'orgao_pagador_empty') === 'true';
    
    if (orgaoPagadorEmpty) {
        return ' ----- ';
    }
    
    const isValid = getXMLNodeValue(row, 'holerite_inss_valid') === '1';
    const tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
    const version = extractVersionFromContract(tipoContrato);
    
    if (version === null) {
        return 'Sem Contrato';
    }
    
    // For HOLERITE_INSS, all versions show "Apto"
    return 'Apto';
}

function getVersionSpecificTextForConsulta(row) {
    const orgaoPagadorEmpty = getXMLNodeValue(row, 'orgao_pagador_empty') === 'true';
    
    if (orgaoPagadorEmpty) {
        return ' ----- ';
    }
    
    const isValid = getXMLNodeValue(row, 'consulta_inss_valid') === '1';
    const tipoContrato = getXMLNodeValue(row, 'tipo_contrato');
    const version = extractVersionFromContract(tipoContrato);
    
    if (version === null) {
        return 'Sem Contrato';
    }
    
    if (version < 8.1) {
        return 'Nao Apto';
    } else if (version >= 8.1 && version < 10.1) {
        return 'Apto';
    } else if (version >= 10.1) {
        return 'Apto';
    } else {
        return 'Nao Apto';
    }
}

function getVersionSpecificTextForProvaVida(row) {
    const orgaoPagadorEmpty = getXMLNodeValue(row, 'orgao_pagador_empty') === 'true';
    
    if (orgaoPagadorEmpty) {
        return ' ----- ';
    }
    
    const isValid = getXMLNodeValue(row, 'prova_de_vida_valid') === '1';
    return isValid ? 'Apurado' : 'Não Apurado';
}

// Helper function to extract version from contract (same as existing)
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

function downloadXLSXFile(workbook, filename) {
    try {
        // Generate XLSX file
        const xlsxData = XLSX.write(workbook, { 
            bookType: 'xlsx', 
            type: 'array',
            compression: true
        });
        
        // Create blob and download
        const blob = new Blob([xlsxData], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        
        const link = document.createElement('a');
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => {
                URL.revokeObjectURL(url);
            }, 1000);
        } else {
            alert('Seu navegador não suporta download automático de XLSX.');
        }
    } catch (error) {
        console.error('Erro ao gerar arquivo XLSX:', error);
        alert('Erro ao gerar arquivo XLSX: ' + error.message);
    }
}

// Update the existing export functions to use XLSX instead of CSV
function exportAllXLSX() {
    const filter = getCurrentFilter();
    exportXLSXData('', filter);
}

function exportSelectedXLSX() {
    const currentFilter = getCurrentFilter();
    const selectedIds = (currentFilter === 'historico')
        ? HistoricoCheckboxModule.getSelectedIds()
        : CheckboxModule.getSelectedIds();

    if (selectedIds.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }

    exportXLSXData(selectedIds.join(','), currentFilter);
}