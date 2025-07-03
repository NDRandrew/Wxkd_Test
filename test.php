$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

error_log("exportTXT - filter: " . $filter);
error_log("exportTXT - selectedIds raw: " . $selectedIds);

try {
    // Buscar dados
    if (!empty($selectedIds)) {
        error_log("exportTXT - Processing selected IDs");
        
        $allData = $this->model->getTableDataByFilter($filter);
        error_log("exportTXT - All data count: " . count($allData));
        
        // CORREÇÃO: Limpar IDs e remover espaços
        $idsArray = explode(',', $selectedIds);
        $cleanIds = array();
        foreach ($idsArray as $id) {
            $cleanId = trim($id);
            $cleanId = preg_replace('/\s+/', '', $cleanId); // Remove todos os espaços
            if (!empty($cleanId) && is_numeric($cleanId)) {
                $cleanIds[] = intval($cleanId); // Convert to integer
            }
        }
        
        error_log("exportTXT - Clean IDs: " . implode('|', $cleanIds));
        
        $data = array();
        foreach ($cleanIds as $sequentialId) {
            $arrayIndex = $sequentialId - 1; // Convert 1-based to 0-based index
            
            // Check if the index exists in the data array
            if (isset($allData[$arrayIndex])) {
                $data[] = $allData[$arrayIndex];
                error_log("exportTXT - MATCH found: sequential ID '$sequentialId' -> array index '$arrayIndex'");
            } else {
                error_log("exportTXT - No data found for sequential ID '$sequentialId' (array index '$arrayIndex')");
            }
        }
        
        error_log("exportTXT - Filtered data count: " . count($data));
    } else {
        error_log("exportTXT - Getting all data");
        $data = $this->model->getTableDataByFilter($filter);
    }