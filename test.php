// Método exportTXT no Controller (views/Wxkd_dashboard.php)
public function exportTXT() {
    try {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
        
        error_log("exportTXT - filter: $filter, selectedIds: $selectedIds");
        
        // Buscar dados usando o método existente
        if (!empty($selectedIds)) {
            // Para IDs específicos, filtrar os dados
            $allData = $this->model->getTableDataByFilter($filter);
            $idsArray = explode(',', $selectedIds);
            $tableData = array();
            
            foreach ($allData as $row) {
                $rowId = isset($row['CHAVE_LOJA']) ? $row['CHAVE_LOJA'] : 
                        (isset($row['id']) ? $row['id'] : '');
                
                if (in_array($rowId, $idsArray)) {
                    $tableData[] = $row;
                }
            }
        } else {
            // Todos os dados do filtro
            $tableData = $this->model->getTableDataByFilter($filter);
        }
        
        error_log("exportTXT - tableData count: " . count($tableData));
        
        if (empty($tableData)) {
            echo "Nenhum dado encontrado para exportação.";
            return;
        }
        
        // Gerar conteúdo TXT específico usando o model
        $txtContent = $this->model->generateSpecificTXT($tableData);
        
        if (empty($txtContent)) {
            echo "Erro ao gerar conteúdo TXT.";
            return;
        }
        
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Headers para download TXT
        $filename = 'dashboard_' . $filter . '_' . date('Y-m-d_H-i-s') . '.txt';
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-Length: ' . strlen($txtContent));
        
        // Output do conteúdo TXT
        echo $txtContent;
        exit;
        
    } catch (Exception $e) {
        error_log("exportTXT - Exception: " . $e->getMessage());
        echo "Erro na exportação TXT: " . $e->getMessage();
        exit;
    }
}