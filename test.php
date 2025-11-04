<?php
// Prevent any output before JSON
ob_start();

try {
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento_estat\motivos_encerramento_model.class.php';
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao carregar model: ' . $e->getMessage()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'getMotivosEncerramento') {
        try {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;
            
            $model = new MotivosEncerramento();
            $dados = $model->getMotivosEncerramento($dataInicio, $dataFim);
            
            // Process data for table display
            $processedData = processarMotivosEncerramento($dados);
            
            // Clear any output buffer and send JSON
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $processedData
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'exportCSV') {
        try {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;
            
            $model = new MotivosEncerramento();
            $dados = $model->getMotivosEncerramento($dataInicio, $dataFim);
            
            // Process data for CSV
            $processedData = processarMotivosEncerramento($dados);
            
            // Clear output buffer before sending file
            ob_end_clean();
            
            // Generate CSV
            $filename = 'motivos_encerramento_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            $headerRow = ['Motivo Encerramento', 'QTDE', '%'];
            foreach ($processedData['meses'] as $mes) {
                $headerRow[] = formatarMesCSV($mes);
            }
            fputcsv($output, $headerRow, ';');
            
            // Data rows
            foreach ($processedData['motivos'] as $motivo => $mesesData) {
                $totalMotivo = 0;
                foreach ($processedData['meses'] as $mes) {
                    $totalMotivo += $mesesData[$mes] ?? 0;
                }
                
                // Skip rows with no data
                if ($totalMotivo === 0) continue;
                
                $row = [
                    $motivo,
                    $totalMotivo,
                    number_format(($totalMotivo / $processedData['total']) * 100, 1, ',', '.') . '%'
                ];
                
                foreach ($processedData['meses'] as $mes) {
                    $row[] = $mesesData[$mes] ?? 0;
                }
                
                fputcsv($output, $row, ';');
            }
            
            // Total row
            $totalRow = ['TOTAL', $processedData['total'], '100,0%'];
            foreach ($processedData['meses'] as $mes) {
                $totalMes = 0;
                foreach ($processedData['motivos'] as $mesesData) {
                    $totalMes += $mesesData[$mes] ?? 0;
                }
                $totalRow[] = $totalMes;
            }
            fputcsv($output, $totalRow, ';');
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            ob_end_clean();
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// If no action or invalid request
ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'error' => 'Ação inválida ou método não permitido'
]);
exit;

function processarMotivosEncerramento($dados) {
    $motivos = [];
    $meses = [];
    $total = 0;
    
    if (!$dados || !is_array($dados)) {
        return [
            'motivos' => [],
            'meses' => [],
            'total' => 0
        ];
    }
    
    // First pass: collect all unique motivos and meses
    foreach ($dados as $row) {
        $motivo = trim($row['DESC_MOTIVO_ENCERRAMENTO'] ?? '');
        $mes = $row['MES_ANO'] ?? '';
        
        if (empty($motivo) || empty($mes)) continue;
        
        if (!isset($motivos[$motivo])) {
            $motivos[$motivo] = [];
        }
        
        if (!in_array($mes, $meses)) {
            $meses[] = $mes;
        }
    }
    
    // Second pass: populate data
    foreach ($dados as $row) {
        $motivo = trim($row['DESC_MOTIVO_ENCERRAMENTO'] ?? '');
        $mes = $row['MES_ANO'] ?? '';
        $qtde = (int)($row['QTDE'] ?? 0);
        
        if (empty($motivo) || empty($mes)) continue;
        
        if (!isset($motivos[$motivo][$mes])) {
            $motivos[$motivo][$mes] = 0;
        }
        $motivos[$motivo][$mes] += $qtde;
        $total += $qtde;
    }
    
    // Sort months in descending order (newest first)
    rsort($meses);
    
    // Sort motivos by total count
    uksort($motivos, function($a, $b) use ($motivos, $meses) {
        $totalA = 0;
        $totalB = 0;
        foreach ($meses as $mes) {
            $totalA += $motivos[$a][$mes] ?? 0;
            $totalB += $motivos[$b][$mes] ?? 0;
        }
        return $totalB - $totalA;
    });
    
    return [
        'motivos' => $motivos,
        'meses' => $meses,
        'total' => $total
    ];
}

function formatarMesCSV($mesAno) {
    if (!$mesAno) return '';
    $parts = explode('-', $mesAno);
    if (count($parts) !== 2) return $mesAno;
    
    $ano = $parts[0];
    $mes = $parts[1];
    $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $mesIndex = (int)$mes - 1;
    
    return isset($meses[$mesIndex]) ? $meses[$mesIndex] . '/' . $ano : $mesAno;
}
?>
