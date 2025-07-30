<?php
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
        
        foreach ($data as $row) {
            $activeTypes = array();
            
            if ($filter === 'historico') {
                $row = array_change_key_case($row, CASE_UPPER);
                
                $tipoCorrespondente = isset($row['TIPO_CORRESPONDENTE']) ? trim($row['TIPO_CORRESPONDENTE']) : '';
                $dataConlusao = isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '';
                
                $isAfterCutoff = false;
                if (!empty($dataConlusao)) {
                    $timestamp = strtotime($dataConlusao);
                    if ($timestamp !== false && $timestamp > $cutoff) {
                        $isAfterCutoff = true;
                    }
                }
                
                if ($isAfterCutoff && !empty($tipoCorrespondente)) {
                    $tipoMapping = array(
                        'AVANCADO' => 'AV',
                        'PRESENCA' => 'PR', 
                        'UNIDADE_NEGOCIO' => 'UN',
                        'ORGAO_PAGADOR' => 'OP',
                        'ORG_PAGADOR' => 'OP'  // Handle both variants
                    );
                    
                    if (isset($tipoMapping[$tipoCorrespondente])) {
                        $activeTypes[] = $tipoMapping[$tipoCorrespondente];
                    } else {
                        foreach ($tipoMapping as $fullType => $shortType) {
                            if (strpos($tipoCorrespondente, $fullType) !== false) {
                                $activeTypes[] = $shortType;
                                break;
                            }
                        }
                    }
                }
            } elseif ($filter === 'descadastramento') {
                // Special handling for descadastramento filter
                $tipoCorrespondente = isset($row['TIPO_CORRESPONDENTE']) ? strtoupper(trim($row['TIPO_CORRESPONDENTE'])) : '';
                $dataConclusao = isset($row['DATA_CONCLUSAO']) ? trim($row['DATA_CONCLUSAO']) : '';
                
                error_log("exportAccess - descadastramento row: tipo={$tipoCorrespondente}, data={$dataConclusao}");
                
                $isAfterCutoff = false;
                if (!empty($dataConclusao)) {
                    // Try different date parsing methods
                    $dateParts = explode('/', $dataConclusao);
                    if (count($dateParts) == 3) {
                        $day = (int)$dateParts[0];
                        $month = (int)$dateParts[1];
                        $year = (int)$dateParts[2];
                        
                        if (checkdate($month, $day, $year)) {
                            $timestamp = mktime(0, 0, 0, $month, $day, $year);
                            $isAfterCutoff = ($timestamp !== false && $timestamp > $cutoff);
                        }
                    } else {
                        // Try strtotime for SQL Server datetime format
                        $timestamp = strtotime($dataConclusao);
                        $isAfterCutoff = ($timestamp !== false && $timestamp > $cutoff);
                    }
                }
                
                error_log("exportAccess - descadastramento: isAfterCutoff={$isAfterCutoff}, timestamp=" . ($timestamp ?? 'null') . ", cutoff={$cutoff}");
                
                if ($isAfterCutoff && !empty($tipoCorrespondente)) {
                    $tipoMapping = array(
                        'AVANCADO' => 'AV',
                        'PRESENCA' => 'PR', 
                        'UNIDADE_NEGOCIO' => 'UN',
                        'ORGAO_PAGADOR' => 'OP',
                        'ORG_PAGADOR' => 'OP'  // Handle both variants
                    );
                    
                    if (isset($tipoMapping[$tipoCorrespondente])) {
                        $activeTypes[] = $tipoMapping[$tipoCorrespondente];
                        error_log("exportAccess - descadastramento: matched tipo {$tipoCorrespondente} -> {$tipoMapping[$tipoCorrespondente]}");
                    } else {
                        // Try partial matching
                        foreach ($tipoMapping as $fullType => $shortType) {
                            if (strpos($tipoCorrespondente, $fullType) !== false) {
                                $activeTypes[] = $shortType;
                                error_log("exportAccess - descadastramento: partial match {$tipoCorrespondente} contains {$fullType} -> {$shortType}");
                                break;
                            }
                        }
                    }
                }
            } else {
                // For other filters (cadastramento, all), use the original logic
                $activeTypes = $this->getActiveTypes($row, $cutoff);
            }

            $hasOP = in_array('OP', $activeTypes);
            $hasOthers = in_array('AV', $activeTypes) || in_array('PR', $activeTypes) || in_array('UN', $activeTypes);

            $codEmpresa = $this->getEmpresaCode($row);
            
            error_log("exportAccess - row processing: codEmpresa={$codEmpresa}, activeTypes=" . implode(',', $activeTypes) . ", hasOP={$hasOP}, hasOthers={$hasOthers}");

            if (!empty($codEmpresa) && !empty($activeTypes)) {
                if ($hasOP) {
                    $opData[] = $codEmpresa;
                    error_log("exportAccess - added to opData: {$codEmpresa}");
                }
                if ($hasOthers) {
                    $avUnPrData[] = $codEmpresa;
                    error_log("exportAccess - added to avUnPrData: {$codEmpresa}");
                }
            }
        }
        
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        error_log("exportAccess - final results: avUnPrData=" . implode(',', $avUnPrData) . ", opData=" . implode(',', $opData));
        
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