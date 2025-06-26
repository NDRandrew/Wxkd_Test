<?php
// test_wxkd_debug.php - Arquivo temporário para testar
// DELETAR após resolver o problema

require_once 'models/Wxkd_DashboardModel.php';

echo "<h3>Teste Manual - Sistema Dashboard Wxkd</h3>";
echo "<pre>";

try {
    // Inicializar model
    $model = new Wxkd_DashboardModel();
    $model->Wxkd_Construct();
    
    echo "✅ Model inicializado com sucesso\n\n";
    
    // Teste 1: getChaveCadastro
    echo "=== TESTE 1: getChaveCadastro ===\n";
    
    $filters = array('cadastramento', 'descadastramento', 'historico', 'all');
    
    foreach ($filters as $filter) {
        echo "Testando filtro: $filter\n";
        $chaves = $model->getChaveCadastro($filter);
        echo "Resultado: " . count($chaves) . " chaves\n";
        
        if (count($chaves) > 0) {
            echo "Primeiras 3 chaves: " . implode(', ', array_slice($chaves, 0, 3)) . "\n";
        } else {
            echo "❌ NENHUMA CHAVE RETORNADA!\n";
        }
        echo "---\n";
    }
    
    // Teste 2: getTableDataByFilter
    echo "\n=== TESTE 2: getTableDataByFilter ===\n";
    
    foreach ($filters as $filter) {
        echo "Testando filtro: $filter\n";
        $data = $model->getTableDataByFilter($filter);
        echo "Resultado: " . count($data) . " registros\n";
        
        if (count($data) > 0) {
            echo "Primeira linha: " . print_r($data[0], true) . "\n";
        } else {
            echo "❌ NENHUM REGISTRO RETORNADO!\n";
        }
        echo "---\n";
    }
    
    // Teste 3: getCardData
    echo "\n=== TESTE 3: getCardData ===\n";
    $cardData = $model->getCardData();
    print_r($cardData);
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

<!-- 
COMO USAR:
1. Coloque este arquivo na raiz do projeto
2. Acesse via browser: http://seusite.com/test_wxkd_debug.php  
3. Verifique se as funções retornam dados
4. Delete o arquivo após o teste
-->
