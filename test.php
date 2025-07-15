<?php
// Alternative solution - if ZIP is problematic, use this simpler approach
// This will download files one by one instead of creating a ZIP

public function exportAccess() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : (isset($_GET['ids']) ? $_GET['ids'] : '');
    $fileType = isset($_GET['fileType']) ? $_GET['fileType'] : 'first'; // 'first' or 'second'
    
    error_log("=== EXPORTAR ACCESS INITIATED ===");
    error_log("exportAccess - filter: " . $filter);
    error_log("exportAccess - selectedIds raw: " . $selectedIds);
    error_log("exportAccess - fileType: " . $fileType);
    
    try {
        if (!empty($selectedIds)) {
            $allData = $this->model->getTableDataByFilter($filter);
            $idsArray = explode(',', $selectedIds);
            $cleanIds = array();
            foreach ($idsArray as $id) {
                $cleanId = trim($id);
                $cleanId = preg_replace('/\s+/', '', $cleanId);
                if (!empty($cleanId) && is_numeric($cleanId)) {
                    $cleanIds[] = intval($cleanId); 
                }
            }
            
            $data = array();
            foreach ($cleanIds as $sequentialId) {
                $arrayIndex = $sequentialId - 1; 
                if (isset($allData[$arrayIndex])) {
                    $data[] = $allData[$arrayIndex];
                }
            }
        } else {
            $data = $this->model->getTableDataByFilter($filter);
        }
        
        // Separate data into two groups based on active tipo correspondente
        $cutoff = mktime(0, 0, 0, 6, 1, 2025); // June 1, 2025
        $avUnPrData = array(); // For AV, UN, PR types
        $opData = array();     // For OP types
        
        foreach ($data as $row) {
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
            
            $codEmpresa = isset($row['COD_EMPRESA']) ? $row['COD_EMPRESA'] : (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '');
            
            if ($hasOP) {
                $opData[] = $codEmpresa;
            }
            if ($hasOthers) {
                $avUnPrData[] = $codEmpresa;
            }
        }
        
        // Remove duplicates
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        $timestamp = date('Y-m-d_H-i-s');
        
        if ($fileType === 'first' || $fileType === 'both') {
            // Download first file: AV, UN, PR data
            $csv1Content = "COD_EMPRESA\r\n";
            foreach ($avUnPrData as $codEmpresa) {
                $csv1Content .= $codEmpresa . "\r\n";
            }
            
            $filename1 = 'access_av_un_pr_' . $filter . '_' . $timestamp . '.csv';
            
            // If there's no OP data or we're specifically asking for the first file
            if (empty($opData) || $fileType === 'first') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename1 . '"');
                echo chr(239) . chr(187) . chr(191); // BOM for UTF-8
                echo $csv1Content;
                exit;
            }
        }
        
        if ($fileType === 'second' && !empty($opData)) {
            // Download second file: OP data
            $csv2Content = "COD_EMPRESA\r\n";
            foreach ($opData as $codEmpresa) {
                $csv2Content .= $codEmpresa . "\r\n";
            }
            
            $filename2 = 'access_op_' . $filter . '_' . $timestamp . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename2 . '"');
            echo chr(239) . chr(187) . chr(191); // BOM for UTF-8
            echo $csv2Content;
            exit;
        }
        
        // If we get here, we need to show a page with download links for both files
        if (!empty($opData) && $fileType === 'both') {
            echo '<html><head><title>Download Access Files</title></head><body>';
            echo '<h3>Dois arquivos estão disponíveis para download:</h3>';
            echo '<p><a href="wxkd.php?action=exportAccess&filter=' . urlencode($filter) . '&ids=' . urlencode($selectedIds) . '&fileType=first" target="_blank">Download: access_av_un_pr_' . $filter . '_' . $timestamp . '.csv (' . count($avUnPrData) . ' registros)</a></p>';
            echo '<p><a href="wxkd.php?action=exportAccess&filter=' . urlencode($filter) . '&ids=' . urlencode($selectedIds) . '&fileType=second" target="_blank">Download: access_op_' . $filter . '_' . $timestamp . '.csv (' . count($opData) . ' registros)</a></p>';
            echo '<script>window.close();</script>';
            echo '</body></html>';
            exit;
        }
        
    } catch (Exception $e) {
        error_log("exportAccess - Exception: " . $e->getMessage());
        
        header('Content-Type: text/plain');
        echo "Erro ao exportar: " . $e->getMessage();
        exit;
    }
}
?>