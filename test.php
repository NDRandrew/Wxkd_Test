<?php
// Update the exportTXT function to properly handle descadastramento:

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
        
        $invalidRecords = $this->validateRecordsForTXTExport($data, $filter);
        
        if (!empty($invalidRecords)) {
            $this->outputValidationError($invalidRecords);
            return;
        }
        
        $chaveLote = $this->model->generateChaveLote();
        
        $xml = '<response><success>true</success><txtData>';
        
        $recordsToUpdate = array();
        
        foreach ($data as $row) {
            $xml .= '<row>';
            
            // Handle different data structures based on filter
            if ($filter === 'historico') {
                $xml .= '<cod_empresa>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa>';
                $xml .= '<cod_empresa_historico>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa_historico>';
                $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
                $xml .= '<cod_loja>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja>';
                $xml .= '<cod_loja_historico>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja_historico>';
            } elseif ($filter === 'descadastramento') {
                // Handle descadastramento data structure
                $xml .= '<cod_empresa>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa>';
                $xml .= '<cod_empresa_historico>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa_historico>';
                $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
                $xml .= '<cod_loja>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja>';
                $xml .= '<cod_loja_historico>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja_historico>';
            } else {
                // Handle cadastramento and other filters
                $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : (isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : ''), '"<>&') . '</cod_empresa>';
                $xml .= '<cod_empresa_historico>' . addcslashes(isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : '', '"<>&') . '</cod_empresa_historico>';
                $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
                $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : (isset($row['COD_LOJA']) ? $row['COD_LOJA'] : ''), '"<>&') . '</cod_loja>';
                $xml .= '<cod_loja_historico>' . addcslashes(isset($row['COD_LOJA']) ? $row['COD_LOJA'] : '', '"<>&') . '</cod_loja_historico>';
            }
            
            $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
            $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
            $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
            
            // Add date fields based on filter type
            if ($filter === 'descadastramento') {
                // For descadastramento, we don't have individual date fields, so set them as empty
                $xml .= '<AVANCADO></AVANCADO>';
                $xml .= '<PRESENCA></PRESENCA>';
                $xml .= '<UNIDADE_NEGOCIO></UNIDADE_NEGOCIO>';
                $xml .= '<ORGAO_PAGADOR></ORGAO_PAGADOR>';
            } else {
                // For other filters, include the individual date fields
                $xml .= '<AVANCADO>' . addcslashes(isset($row['AVANCADO']) ? $row['AVANCADO'] : '', '"<>&') . '</AVANCADO>';
                $xml .= '<PRESENCA>' . addcslashes(isset($row['PRESENCA']) ? $row['PRESENCA'] : '', '"<>&') . '</PRESENCA>';
                $xml .= '<UNIDADE_NEGOCIO>' . addcslashes(isset($row['UNIDADE_NEGOCIO']) ? $row['UNIDADE_NEGOCIO'] : '', '"<>&') . '</UNIDADE_NEGOCIO>';
                $xml .= '<ORGAO_PAGADOR>' . addcslashes(isset($row['ORGAO_PAGADOR']) ? $row['ORGAO_PAGADOR'] : '', '"<>&') . '</ORGAO_PAGADOR>';
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
            
            // Prepare records for flag update
            $codEmpresa = 0;
            $codLoja = 0;
            
            if ($filter === 'historico' || $filter === 'descadastramento') {
                $codEmpresa = (int) (isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : 0);
                $codLoja = (int) (isset($row['COD_LOJA']) ? $row['COD_LOJA'] : 0);
            } else {
                $codEmpresa = (int) (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : (isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : 0));
                $codLoja = (int) (isset($row['Cod_Loja']) ? $row['Cod_Loja'] : (isset($row['COD_LOJA']) ? $row['COD_LOJA'] : 0));
            }
            
            if ($codEmpresa > 0 && $codLoja > 0) {
                $recordsToUpdate[] = array(
                    'COD_EMPRESA' => $codEmpresa,
                    'COD_LOJA' => $codLoja
                );
            }
        }
        
        // Rest of the function remains the same...
        if (!empty($recordsToUpdate)) {
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
}

// Also update the validateRecordsForTXTExport function to handle the filter parameter:
private function validateRecordsForTXTExport($data, $filter = 'all') {
    $invalidRecords = array();
    $cutoff = Wxkd_Config::getCutoffTimestamp();
    
    foreach ($data as $row) {
        $activeTypes = array();
        
        if ($filter === 'historico' || $filter === 'descadastramento') {
            $activeTypes = $this->getActiveTypesForDescadastramento($row, $cutoff);
            $codEmpresa = $this->getEmpresaCode($row);
        } else {
            $activeTypes = $this->getActiveTypes($row, $cutoff);
            $codEmpresa = $this->getEmpresaCode($row);
        }
        
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
        
        if (!$isValid) {
            $invalidRecords[] = array(
                'cod_empresa' => $codEmpresa,
                'error' => $errorMessage
            );
        }
    }
    
    return $invalidRecords;
}
?>