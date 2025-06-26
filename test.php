public function ajaxGetTableData() {
    // Debug inicial - preservar filtro
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    error_log("ajaxGetTableData received filter: " . $filter);
    error_log("GET params: " . print_r($_GET, true));
    
    // IMPORTANTE: Preservar o filtro durante todo o método
    $originalFilter = $filter;
    
    try {
        // Buscar dados
        $tableData = $this->model->getTableDataByFilter($filter);
        $cardData = $this->model->getCardData();
        
        error_log("ajaxGetTableData - filter preserved: " . $originalFilter);
        error_log("ajaxGetTableData - tableData type: " . gettype($tableData));
        error_log("ajaxGetTableData - tableData count: " . (is_array($tableData) ? count($tableData) : 'not array'));
        
        // Preparar resposta XML
        $xml = '<response>';
        $xml .= '<success>true</success>';
        
        // CORREÇÃO: Usar $originalFilter ao invés de $filter (pode ter sido alterado)
        $xml .= '<debug>';
        $xml .= '<filter>' . addcslashes($originalFilter, '"<>&') . '</filter>';
        $xml .= '<tableDataType>' . gettype($tableData) . '</tableDataType>';
        $xml .= '<tableDataCount>' . (is_array($tableData) ? count($tableData) : 0) . '</tableDataCount>';
        $xml .= '</debug>';
        
        // Card data
        if (is_array($cardData)) {
            $xml .= '<cardData>';
            foreach ($cardData as $key => $value) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $xml .= '</cardData>';
        }
        
        // Table data
        $xml .= '<tableData>';
        if (is_array($tableData) && count($tableData) > 0) {
            foreach ($tableData as $row) {
                $xml .= '<row>';
                foreach ($row as $key => $value) {
                    $xml .= '<' . $key . '>' . addcslashes($value, '"<>&') . '</' . $key . '>';
                }
                $xml .= '</row>';
            }
        } else {
            error_log("ajaxGetTableData - No table data to return");
            $xml .= '<message>No data found for filter: ' . $originalFilter . '</message>';
        }
        $xml .= '</tableData>';
        
        $xml .= '</response>';
        
        // Debug da resposta XML
        error_log("ajaxGetTableData - XML length: " . strlen($xml));
        error_log("ajaxGetTableData - XML preview: " . substr($xml, 0, 200));
        
    } catch (Exception $e) {
        error_log("ajaxGetTableData - Exception: " . $e->getMessage());
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<error>' . addcslashes($e->getMessage(), '"<>&') . '</error>';
        $xml .= '</response>';
    }
    
    // Headers
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml;
    exit;
}
