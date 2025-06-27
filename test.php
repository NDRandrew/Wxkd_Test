public function exportXLS() {
    try {
        // Receber parâmetros
        $selectedIds = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : '';
        $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
        
        error_log("exportXLS - selectedIds: " . $selectedIds);
        error_log("exportXLS - filter: " . $filter);
        
        // Processar IDs selecionados
        $idsArray = array();
        if (!empty($selectedIds)) {
            $idsArray = explode(',', $selectedIds);
            $idsArray = array_map('trim', $idsArray);
            $idsArray = array_filter($idsArray); // Remove empty values
        }
        
        // Buscar dados baseado na seleção
        if (!empty($idsArray)) {
            // Exportar apenas selecionados
            $data = $this->model->getSelectedRecords($idsArray, $filter);
            $filename = 'dashboard_selecionados_' . date('Y-m-d_H-i-s') . '.xls';
        } else {
            // Exportar todos do filtro atual
            $data = $this->model->getTableDataByFilter($filter);
            $filename = 'dashboard_' . $filter . '_' . date('Y-m-d_H-i-s') . '.xls';
        }
        
        error_log("exportXLS - Records to export: " . count($data));
        
        if (empty($data)) {
            // Se não há dados, retornar erro
            echo json_encode(array('success' => false, 'message' => 'Nenhum registro encontrado para exportação.'));
            return;
        }
        
        // Gerar conteúdo XLS
        $xlsContent = $this->generateXLSContent($data, $filter);
        
        // Headers para download
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($xlsContent));
        
        // Output do conteúdo
        echo $xlsContent;
        exit;
        
    } catch (Exception $e) {
        error_log("exportXLS - Exception: " . $e->getMessage());
        echo json_encode(array('success' => false, 'message' => 'Erro na exportação: ' . $e->getMessage()));
    }
}

private function generateXLSContent($data, $filter) {
    // Cabeçalho HTML que o Excel reconhece
    $xls = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xls .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xls .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xls .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    $xls .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    $xls .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xls .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
    
    // Estilos
    $xls .= '<Styles>' . "\n";
    $xls .= '<Style ss:ID="header">' . "\n";
    $xls .= '<Font ss:Bold="1" ss:Size="12"/>' . "\n";
    $xls .= '<Interior ss:Color="#4472C4" ss:Pattern="Solid"/>' . "\n";
    $xls .= '<Font ss:Color="#FFFFFF"/>' . "\n";
    $xls .= '</Style>' . "\n";
    $xls .= '<Style ss:ID="data">' . "\n";
    $xls .= '<Font ss:Size="10"/>' . "\n";
    $xls .= '<Borders>' . "\n";
    $xls .= '<Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>' . "\n";
    $xls .= '</Borders>' . "\n";
    $xls .= '</Style>' . "\n";
    $xls .= '</Styles>' . "\n";
    
    // Worksheet
    $xls .= '<Worksheet ss:Name="Dashboard_' . ucfirst($filter) . '">' . "\n";
    $xls .= '<Table>' . "\n";
    
    // Cabeçalho da tabela
    $xls .= '<Row>' . "\n";
    $headers = array('ID', 'Nome', 'Email', 'Telefone', 'Endereço', 'Cidade', 'Estado', 'Chave Loja', 'Data Cadastro', 'Tipo');
    
    foreach ($headers as $header) {
        $xls .= '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</Data></Cell>' . "\n";
    }
    $xls .= '</Row>' . "\n";
    
    // Dados
    foreach ($data as $row) {
        $xls .= '<Row>' . "\n";
        
        // ID
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="Number">' . 
                htmlspecialchars(isset($row['id']) ? $row['id'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Nome
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['nome']) ? $row['nome'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Email
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['email']) ? $row['email'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Telefone
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['telefone']) ? $row['telefone'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Endereço
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['endereco']) ? $row['endereco'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Cidade
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['cidade']) ? $row['cidade'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Estado
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['estado']) ? $row['estado'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Chave Loja
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['chave_loja']) ? $row['chave_loja'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Data Cadastro
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['data_cadastro']) ? $row['data_cadastro'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        // Tipo
        $xls .= '<Cell ss:StyleID="data"><Data ss:Type="String">' . 
                htmlspecialchars(isset($row['tipo']) ? $row['tipo'] : '', ENT_QUOTES, 'UTF-8') . 
                '</Data></Cell>' . "\n";
        
        $xls .= '</Row>' . "\n";
    }
    
    $xls .= '</Table>' . "\n";
    $xls .= '</Worksheet>' . "\n";
    $xls .= '</Workbook>';
    
    return $xls;
}