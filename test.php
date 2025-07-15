<?php
// Add this method to Wxkd_DashboardController class, following the same pattern as exportTXT()

public function exportAccess() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    error_log("=== EXPORTAR ACCESS INITIATED ===");
    error_log("exportAccess - filter: " . $filter);
    error_log("exportAccess - selectedIds raw: " . $selectedIds);

    try {
        if (!empty($selectedIds)) {
            error_log("exportAccess - Processing selected IDs");
            
            $allData = $this->model->getTableDataByFilter($filter);
            error_log("exportAccess - All data count: " . count($allData));
            
            $idsArray = explode(',', $selectedIds);
            $cleanIds = array();
            foreach ($idsArray as $id) {
                $cleanId = trim($id);
                $cleanId = preg_replace('/\s+/', '', $cleanId); // Remove todos os espaços
                if (!empty($cleanId) && is_numeric($cleanId)) {
                    $cleanIds[] = intval($cleanId); 
                }
            }
            
            error_log("exportAccess - Clean IDs: " . implode('|', $cleanIds));
            
            $data = array();
            foreach ($cleanIds as $sequentialId) {
                $arrayIndex = $sequentialId - 1; 
                
                if (isset($allData[$arrayIndex])) {
                    $data[] = $allData[$arrayIndex];
                    error_log("exportAccess - MATCH found: sequential ID '$sequentialId' -> array index '$arrayIndex'");
                } else {
                    error_log("exportAccess - No data found for sequential ID '$sequentialId' (array index '$arrayIndex')");
                }
            }
            
            error_log("exportAccess - Filtered data count: " . count($data));
        } else {
            error_log("exportAccess - Getting all data");
            $data = $this->model->getTableDataByFilter($filter);
        }
        
        // Separate data into two groups based on active tipo correspondente
        $cutoff = mktime(0, 0, 0, 6, 1, 2025); // June 1, 2025
        $avUnPrData = array(); // For AV, UN, PR types
        $opData = array();     // For OP types
        
        foreach ($data as $rowIndex => $row) {
            error_log("exportAccess - Processing row $rowIndex");
            
            $activeTypes = array();
            
            // Check which types are active based on dates
            $fields = array(
                'AVANCADO' => 'AV',
                'ORGAO_PAGADOR' => 'OP',
                'PRESENCA' => 'PR',
                'UNIDADE_NEGOCIO' => 'UN'
            );
            
            foreach ($fields as $field => $label) {
                $raw = isset($row[$field]) ? trim($row[$field]) : '';
                
                if (!empty($raw)) {
                    $parts = explode('/', $raw);
                    if (count($parts) == 3) {
                        $day = (int)$parts[0];
                        $month = (int)$parts[1];
                        $year = (int)$parts[2];
                        
                        if (checkdate($month, $day, $year)) {
                            $timestamp = mktime(0, 0, 0, $month, $day, $year);
                            if ($timestamp > $cutoff) {
                                $activeTypes[] = $label;
                            }
                        }
                    }
                }
            }
            
            // Classify the record based on active types
            $hasOP = in_array('OP', $activeTypes);
            $hasOthers = in_array('AV', $activeTypes) || in_array('PR', $activeTypes) || in_array('UN', $activeTypes);
            
            // Find COD_EMPRESA field (checking multiple possible field names)
            $possibleEmpresaFields = array('COD_EMPRESA', 'cod_empresa', 'Cod_Empresa', 'CODEMPRESA', 'cod_emp');
            $codEmpresa = '';
            
            foreach ($possibleEmpresaFields as $field) {
                if (isset($row[$field]) && !empty($row[$field])) {
                    $codEmpresa = $row[$field];
                    break;
                }
            }
            
            if (!empty($codEmpresa)) {
                if ($hasOP) {
                    $opData[] = $codEmpresa;
                }
                if ($hasOthers) {
                    $avUnPrData[] = $codEmpresa;
                }
                
                error_log("exportAccess - Row with COD_EMPRESA '$codEmpresa', active types: " . implode(',', $activeTypes) . ", hasOP: " . ($hasOP ? 'yes' : 'no') . ", hasOthers: " . ($hasOthers ? 'yes' : 'no'));
            } else {
                error_log("exportAccess - No COD_EMPRESA found in row $rowIndex");
            }
        }
        
        // Remove duplicates
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        error_log("exportAccess - Final AV/UN/PR data count: " . count($avUnPrData));
        error_log("exportAccess - Final OP data count: " . count($opData));
        
        // Build XML response following the same pattern as exportTXT
        $xml = '<response>';
        $xml .= '<success>true</success>';
        $xml .= '<debug>';
        $xml .= '<totalRecords>' . count($data) . '</totalRecords>';
        $xml .= '<filter>' . addcslashes($filter, '"<>&') . '</filter>';
        $xml .= '<selectedIds>' . addcslashes($selectedIds, '"<>&') . '</selectedIds>';
        $xml .= '<avUnPrCount>' . count($avUnPrData) . '</avUnPrCount>';
        $xml .= '<opCount>' . count($opData) . '</opCount>';
        $xml .= '</debug>';
        
        // Add AV/UN/PR data
        $xml .= '<avUnPrData>';
        foreach ($avUnPrData as $codEmpresa) {
            $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
        }
        $xml .= '</avUnPrData>';
        
        // Add OP data (only if exists)
        if (!empty($opData)) {
            $xml .= '<opData>';
            foreach ($opData as $codEmpresa) {
                $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
            }
            $xml .= '</opData>';
        }
        
        $xml .= '</response>';
        
        error_log("exportAccess - XML generated, length: " . strlen($xml));
        error_log("=== EXPORTAR ACCESS COMPLETED ===");
        
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