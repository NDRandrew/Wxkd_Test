<?php
// Add this helper function to the Wxkd_DashboardController class:

private function getActiveTypesForDescadastramento($row, $cutoff) {
    $activeTypes = array();
    
    $tipoCorrespondente = isset($row['TIPO_CORRESPONDENTE']) ? strtoupper(trim($row['TIPO_CORRESPONDENTE'])) : '';
    $dataConclusao = isset($row['DATA_CONCLUSAO']) ? trim($row['DATA_CONCLUSAO']) : '';
    
    error_log("getActiveTypesForDescadastramento - Processing: tipo={$tipoCorrespondente}, data={$dataConclusao}");
    
    if (empty($tipoCorrespondente) || empty($dataConclusao)) {
        error_log("getActiveTypesForDescadastramento - Empty tipo or data, returning empty array");
        return $activeTypes;
    }
    
    // Parse the date
    $isAfterCutoff = false;
    $timestamp = false;
    
    // Try dd/mm/yyyy format first
    $dateParts = explode('/', $dataConclusao);
    if (count($dateParts) == 3) {
        $day = (int)$dateParts[0];
        $month = (int)$dateParts[1];
        $year = (int)$dateParts[2];
        
        if (checkdate($month, $day, $year)) {
            $timestamp = mktime(0, 0, 0, $month, $day, $year);
        }
    }
    
    // If dd/mm/yyyy didn't work, try strtotime for SQL Server datetime format
    if ($timestamp === false) {
        $timestamp = strtotime($dataConclusao);
    }
    
    $isAfterCutoff = ($timestamp !== false && $timestamp > $cutoff);
    
    error_log("getActiveTypesForDescadastramento - Date parsing: timestamp={$timestamp}, cutoff={$cutoff}, isAfterCutoff=" . ($isAfterCutoff ? 'true' : 'false'));
    
    if ($isAfterCutoff) {
        $tipoMapping = array(
            'AVANCADO' => 'AV',
            'PRESENCA' => 'PR', 
            'UNIDADE_NEGOCIO' => 'UN',
            'ORGAO_PAGADOR' => 'OP',
            'ORG_PAGADOR' => 'OP'  // Handle both variants
        );
        
        // Try exact match first
        if (isset($tipoMapping[$tipoCorrespondente])) {
            $activeTypes[] = $tipoMapping[$tipoCorrespondente];
            error_log("getActiveTypesForDescadastramento - Exact match: {$tipoCorrespondente} -> {$tipoMapping[$tipoCorrespondente]}");
        } else {
            // Try partial matching
            foreach ($tipoMapping as $fullType => $shortType) {
                if (strpos($tipoCorrespondente, $fullType) !== false) {
                    $activeTypes[] = $shortType;
                    error_log("getActiveTypesForDescadastramento - Partial match: {$tipoCorrespondente} contains {$fullType} -> {$shortType}");
                    break;
                }
            }
            
            // If no match found, log for debugging
            if (empty($activeTypes)) {
                error_log("getActiveTypesForDescadastramento - No match found for tipo: {$tipoCorrespondente}");
            }
        }
    } else {
        error_log("getActiveTypesForDescadastramento - Date not after cutoff, returning empty array");
    }
    
    error_log("getActiveTypesForDescadastramento - Final result: " . implode(',', $activeTypes));
    return $activeTypes;
}

// Update the exportAccess function to use this helper:
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
            }
        }
        
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        error_log("exportAccess - Final results: avUnPrData count=" . count($avUnPrData) . ", opData count=" . count($opData));
        error_log("exportAccess - avUnPrData: " . implode(',', $avUnPrData));
        error_log("exportAccess - opData: " . implode(',', $opData));
        
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
?>