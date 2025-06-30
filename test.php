// Método exportTXT que retorna XML (sem headers)
public function exportTXT() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    try {
        // Buscar dados
        if (!empty($selectedIds)) {
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
            $tableData = $this->model->getTableDataByFilter($filter);
        }
        
        if (empty($tableData)) {
            echo '<response><success>false</success><error>Nenhum dado encontrado</error></response>';
            exit;
        }
        
        // Gerar conteúdo TXT usando o model
        $txtContent = $this->model->generateSpecificTXT($tableData);
        
        if (empty($txtContent)) {
            echo '<response><success>false</success><error>Erro ao gerar TXT</error></response>';
            exit;
        }
        
        // Retornar XML com o conteúdo TXT
        $xml = '<response>';
        $xml .= '<success>true</success>';
        $xml .= '<txtContent>' . addcslashes($txtContent, '"<>&') . '</txtContent>';
        $xml .= '<filename>dashboard_' . $filter . '_' . date('Y-m-d_H-i-s') . '.txt</filename>';
        $xml .= '</response>';
        
        echo $xml;
        exit;
        
    } catch (Exception $e) {
        echo '<response><success>false</success><error>' . addcslashes($e->getMessage(), '"<>&') . '</error></response>';
        exit;
    }
}