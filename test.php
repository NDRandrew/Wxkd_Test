<?php
// Add this method to your Wxkd_DashboardController class in TestAc.txt

public function exportAccess() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : (isset($_GET['ids']) ? $_GET['ids'] : '');
    
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
                $cleanId = preg_replace('/\s+/', '', $cleanId);
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
            
            error_log("exportAccess - Row with COD_EMPRESA '$codEmpresa', active types: " . implode(',', $activeTypes) . ", hasOP: " . ($hasOP ? 'yes' : 'no') . ", hasOthers: " . ($hasOthers ? 'yes' : 'no'));
        }
        
        // Remove duplicates
        $avUnPrData = array_unique($avUnPrData);
        $opData = array_unique($opData);
        
        error_log("exportAccess - AV/UN/PR data count: " . count($avUnPrData));
        error_log("exportAccess - OP data count: " . count($opData));
        
        // Create CSV content
        $timestamp = date('Y-m-d_H-i-s');
        
        // First file: AV, UN, PR data (always created)
        $csv1Content = "COD_EMPRESA\r\n";
        foreach ($avUnPrData as $codEmpresa) {
            $csv1Content .= $codEmpresa . "\r\n";
        }
        
        $filename1 = 'access_av_un_pr_' . $filter . '_' . $timestamp . '.csv';
        
        // Check if we need the second file
        $createSecondFile = !empty($opData);
        
        if ($createSecondFile) {
            // Second file: OP data
            $csv2Content = "COD_EMPRESA\r\n";
            foreach ($opData as $codEmpresa) {
                $csv2Content .= $codEmpresa . "\r\n";
            }
            $filename2 = 'access_op_' . $filter . '_' . $timestamp . '.csv';
            
            // Create ZIP file with both CSVs
            $zipFilename = 'access_export_' . $filter . '_' . $timestamp . '.zip';
            $tempZipPath = sys_get_temp_dir() . '/' . $zipFilename;
            
            $zip = new ZipArchive();
            if ($zip->open($tempZipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFromString($filename1, chr(239) . chr(187) . chr(191) . $csv1Content); // Add BOM
                $zip->addFromString($filename2, chr(239) . chr(187) . chr(191) . $csv2Content); // Add BOM
                $zip->close();
                
                // Send ZIP file
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                header('Content-Length: ' . filesize($tempZipPath));
                readfile($tempZipPath);
                unlink($tempZipPath); // Clean up
                exit;
            } else {
                throw new Exception('Could not create ZIP file');
            }
        } else {
            // Only first file needed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename1 . '"');
            echo chr(239) . chr(187) . chr(191); // BOM for UTF-8
            echo $csv1Content;
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