## Fix: JSON parsing error

**In JH.txt (ajax_encerramento.php)**, fix the `json_encode_custom` function:

```php
function json_encode_custom($data) {
    if (is_null($data)) return 'null';
    if ($data === true) return 'true';
    if ($data === false) return 'false';
    if (is_int($data) || is_float($data)) return (string)$data;
    
    if (is_string($data)) {
        // Properly escape special characters
        $data = str_replace(['\\', '"', "\r", "\n", "\t"], ['\\\\', '\\"', '\\r', '\\n', '\\t'], $data);
        // Remove other control characters
        $data = preg_replace('/[\x00-\x1F\x7F]/', '', $data);
        return '"' . $data . '"';
    }
    
    if (is_array($data)) {
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        $items = [];
        foreach ($data as $key => $value) {
            $encodedValue = json_encode_custom($value);
            $items[] = $isAssoc ? '"' . addslashes($key) . '":' . $encodedValue : $encodedValue;
        }
        return $isAssoc ? '{' . implode(',', $items) . '}' : '[' . implode(',', $items) . ']';
    }
    
    if (is_object($data)) {
        $items = [];
        foreach (get_object_vars($data) as $key => $value) {
            $items[] = '"' . addslashes($key) . '":' . json_encode_custom($value);
        }
        return '{' . implode(',', $items) . '}';
    }
    
    return 'null';
}
```

**In C.txt (analise_encerramento_control.php)**, fix renderOcorrenciasDetails to escape data:

```php
public function renderOcorrenciasDetails($ocorrencias) {
    if (empty($ocorrencias)) {
        return '<div class="p-3 text-center">Nenhum detalhe encontrado</div>';
    }
    
    $html = '<div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr style="background-color: #d8d8d8;">
                            <th>ID</th>
                            <th>Data</th>
                            <th>CNPJs</th>
                            <th>Ocorrência</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($ocorrencias as $occ) {
        $dataOcorrencia = is_object($occ['DT_OCORRENCIA']) 
            ? $occ['DT_OCORRENCIA']->format('d/m/Y H:i:s') 
            : date('d/m/Y H:i:s', strtotime($occ['DT_OCORRENCIA']));
        
        // Clean and escape the ocorrencia text
        $ocorrenciaText = $occ['OCORRENCIA'];
        $ocorrenciaText = str_replace(["\r\n", "\r", "\n"], ' ', $ocorrenciaText);
        $ocorrenciaText = preg_replace('/\s+/', ' ', $ocorrenciaText);
        $ocorrenciaText = htmlspecialchars($ocorrenciaText, ENT_QUOTES, 'UTF-8');
        
        $html .= '<tr>
                    <td>' . htmlspecialchars($occ['ID']) . '</td>
                    <td>' . $dataOcorrencia . '</td>
                    <td>' . htmlspecialchars($occ['CNPJs']) . '</td>
                    <td><small>' . $ocorrenciaText . '</small></td>
                  </tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}
```

**Alternative: Use native JSON encoding in ajax handler**

Replace the entire `load_ocorrencias` handler with this safer version:

```php
if ((isset($_POST['acao']) && $_POST['acao'] == 'load_ocorrencias') || 
    (isset($_GET['acao']) && $_GET['acao'] == 'load_ocorrencias')) {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
        
        $where = '';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = addslashes($_GET['search']);
            $where .= " AND (CAST(CHAVE_LOTE AS VARCHAR) LIKE '%$search%' OR CNPJs LIKE '%$search%' OR OCORRENCIA LIKE '%$search%')";
        }
        
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where .= " AND DT_OCORRENCIA >= '" . addslashes($_GET['data_inicio']) . "'";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where .= " AND DT_OCORRENCIA <= '" . addslashes($_GET['data_fim']) . " 23:59:59'";
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
        $offset = ($page - 1) * $perPage;
        
        $model = new Analise();
        
        $totalRecords = $model->getTotalOcorrenciasLotes($where);
        $totalPages = ceil($totalRecords / $perPage);
        $dados = $model->getOcorrenciasLotes($where, $perPage, $offset);
        
        $controller = new AnaliseEncerramentoController();
        $html = $controller->renderOcorrenciasAccordions($dados);
        
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        // Use native json_encode with proper flags
        echo json_encode([
            'success' => true,
            'html' => $html,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage,
            'startRecord' => $offset + 1,
            'endRecord' => min($offset + $perPage, $totalRecords)
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    exit;
}
```

Use native `json_encode` instead of the custom function - it handles special characters properly.