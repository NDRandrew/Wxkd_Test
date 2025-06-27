// Adicionar este método em views/Wxkd_dashboard.php
public function exportCSV() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    // Usar método existente para buscar dados
    if (!empty($selectedIds)) {
        // Para IDs específicos, filtrar os dados existentes
        $allData = $this->model->getTableDataByFilter($filter);
        $idsArray = explode(',', $selectedIds);
        $data = array();
        foreach ($allData as $row) {
            if (in_array($row['id'], $idsArray)) {
                $data[] = $row;
            }
        }
    } else {
        // Todos os dados do filtro
        $data = $this->model->getTableDataByFilter($filter);
    }
    
    // Gerar CSV
    $filename = 'dashboard_' . $filter . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // BOM para UTF-8
    echo "\xEF\xBB\xBF";
    
    // Cabeçalho
    echo '"ID";"Nome";"Email";"Telefone";"Endereço";"Cidade";"Estado";"Chave Loja";"Data Cadastro";"Tipo"' . "\r\n";
    
    // Dados
    foreach ($data as $row) {
        $line = array(
            '"' . str_replace('"', '""', isset($row['id']) ? $row['id'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['nome']) ? $row['nome'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['email']) ? $row['email'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['telefone']) ? $row['telefone'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['endereco']) ? $row['endereco'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['cidade']) ? $row['cidade'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['estado']) ? $row['estado'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['chave_loja']) ? $row['chave_loja'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['data_cadastro']) ? $row['data_cadastro'] : '') . '"',
            '"' . str_replace('"', '""', isset($row['tipo']) ? $row['tipo'] : '') . '"'
        );
        echo implode(';', $line) . "\r\n";
    }
    exit;
}