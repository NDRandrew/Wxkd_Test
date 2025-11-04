<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php');

#[AllowDynamicProperties]
class TempoAtuacao {
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }
    
    public function getSql() {
        return $this->sql;
    }
    
    // Get active time statistics
    public function getTempoAtuacao($dataInicio = null, $dataFim = null) {
        $where = "1=1";
        
        if ($dataInicio && $dataFim) {
            $where .= " AND F.DT_ENCERRAMENTO BETWEEN '$dataInicio' AND '$dataFim'";
        }
        
        $query = "
            SELECT 
                F.DT_INAUGURACAO,
                F.DT_ENCERRAMENTO,
                DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) as MESES_ATIVO,
                CASE 
                    WHEN DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) < 1 THEN 'Menos de 1 mês'
                    WHEN DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) BETWEEN 1 AND 5 THEN 'De 1 a 6 meses'
                    WHEN DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) BETWEEN 6 AND 11 THEN 'De 6 meses a 1 ano'
                    WHEN DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) BETWEEN 12 AND 23 THEN 'De 1 a 2 anos'
                    WHEN DATEDIFF(MONTH, F.DT_INAUGURACAO, F.DT_ENCERRAMENTO) BETWEEN 24 AND 35 THEN 'De 2 a 3 anos'
                    ELSE 'Mais de 3 anos'
                END as PERIODO_ATUACAO
            FROM 
                DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
            WHERE 
                $where
                AND F.BE_INAUGURADO = 1
                AND F.DT_INAUGURACAO IS NOT NULL
                AND F.DT_ENCERRAMENTO IS NOT NULL
        ";
        
        return $this->sql->select($query);
    }
}
?>

-----------

<?php
// Prevent any output before JSON
ob_start();

try {
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento_estat\tempo_atuacao_model.class.php';
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
    
    if ($action === 'getTempoAtuacao') {
        try {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;
            
            $model = new TempoAtuacao();
            $dados = $model->getTempoAtuacao($dataInicio, $dataFim);
            
            // Process data for table display
            $processedData = processarTempoAtuacao($dados);
            
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
            
            $model = new TempoAtuacao();
            $dados = $model->getTempoAtuacao($dataInicio, $dataFim);
            
            // Process data for CSV
            $processedData = processarTempoAtuacao($dados);
            
            // Clear output buffer before sending file
            ob_end_clean();
            
            // Generate CSV
            $filename = 'tempo_atuacao_' . date('Y-m-d_His') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            $headerRow = ['Período de Atuação', 'QTDE', '%'];
            fputcsv($output, $headerRow, ';');
            
            // Data rows
            foreach ($processedData['periodos'] as $periodo => $qtde) {
                $row = [
                    $periodo,
                    $qtde,
                    number_format(($qtde / $processedData['total']) * 100, 1, ',', '.') . '%'
                ];
                fputcsv($output, $row, ';');
            }
            
            // Total row
            $totalRow = ['TOTAL', $processedData['total'], '100,0%'];
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

function processarTempoAtuacao($dados) {
    // Define the order of periods
    $periodos = [
        'Menos de 1 mês' => 0,
        'De 1 a 6 meses' => 0,
        'De 6 meses a 1 ano' => 0,
        'De 1 a 2 anos' => 0,
        'De 2 a 3 anos' => 0,
        'Mais de 3 anos' => 0
    ];
    
    $total = 0;
    
    if (!$dados || !is_array($dados)) {
        return [
            'periodos' => $periodos,
            'total' => 0
        ];
    }
    
    // Count occurrences for each period
    foreach ($dados as $row) {
        $periodo = $row['PERIODO_ATUACAO'] ?? '';
        
        if (isset($periodos[$periodo])) {
            $periodos[$periodo]++;
            $total++;
        }
    }
    
    return [
        'periodos' => $periodos,
        'total' => $total
    ];
}
?>


----------

<?php
session_start();

// Set default dates
$dataInicio = date('Y-01-01'); // First day of current year
$dataFim = date('Y-m-d'); // Today
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        :root {
            --thead-estat: #d8d8d8;
            --thead-text-estat: #262626;
        }

        :root[data-theme="light"] {
            --thead-estat: #ac193d;
            --thead-text-estat: #ffffff;
        }

        :root[data-theme="dark"] {
            --thead-estat: #d8d8d8;
            --thead-text-estat: #262626;
        }

        .thead-estat {
            background: var(--thead-estat) !important;
            color: var(--thead-text-estat) !important;
            font-size: .75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .5rem;
            white-space: nowrap;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-overlay.active {
            display: flex;
        }

        [data-theme="dark"] .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
        }

        .nav-tabs .nav-link {
            color: #626976;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
        }

        [data-theme="dark"] .nav-tabs .nav-link {
            color: #a0a0a0;
        }

        [data-theme="dark"] .nav-tabs .nav-link.active {
            color: #ffffff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Filters Card with Tabs -->
    <div class="card mb-3">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="viewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="bloqueio-tab" data-bs-toggle="tab" data-view="bloqueio" type="button" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Motivos Bloqueio
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="encerramento-tab" data-bs-toggle="tab" data-view="encerramento" type="button" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        Motivos Encerramento
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tempo-tab" data-bs-toggle="tab" data-view="tempo" type="button" role="tab">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        Tempo de Atuação
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="dataInicio" value="<?php echo $dataInicio; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="dataFim" value="<?php echo $dataFim; ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" id="aplicarFiltros">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Aplicar Filtros
                    </button>
                    <button class="btn btn-success" id="exportarCSV">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Exportar CSV
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Motivos Bloqueio Table -->
    <div class="tab-content active" id="bloqueio-content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estatísticas por Motivo de Bloqueio</h3>
            </div>
            <div class="card-body position-relative">
                <div class="loading-overlay" id="loadingOverlayBloqueio">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelaMotivosBloqueio">
                        <thead>
                            <tr>
                                <th class="thead-estat">Motivo Bloqueio</th>
                                <th class="thead-estat text-center">QTDE</th>
                                <th class="thead-estat text-center">%</th>
                                <!-- Monthly columns will be added dynamically -->
                            </tr>
                        </thead>
                        <tbody id="tableBodyBloqueio">
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner-border text-muted" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando dados...</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="thead-estat">TOTAL</th>
                                <th class="thead-estat text-center" id="totalQtdeBloqueio">0</th>
                                <th class="thead-estat text-center">100%</th>
                                <!-- Monthly totals will be added dynamically -->
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Motivos Encerramento Table -->
    <div class="tab-content" id="encerramento-content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estatísticas por Motivo de Encerramento</h3>
            </div>
            <div class="card-body position-relative">
                <div class="loading-overlay" id="loadingOverlayEncerramento">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelaMotivosEncerramento">
                        <thead>
                            <tr>
                                <th class="thead-estat">Motivo Encerramento</th>
                                <th class="thead-estat text-center">QTDE</th>
                                <th class="thead-estat text-center">%</th>
                                <!-- Monthly columns will be added dynamically -->
                            </tr>
                        </thead>
                        <tbody id="tableBodyEncerramento">
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner-border text-muted" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando dados...</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="thead-estat">TOTAL</th>
                                <th class="thead-estat text-center" id="totalQtdeEncerramento">0</th>
                                <th class="thead-estat text-center">100%</th>
                                <!-- Monthly totals will be added dynamically -->
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tempo de Atuação Table -->
    <div class="tab-content" id="tempo-content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estatísticas por Tempo de Atuação</h3>
            </div>
            <div class="card-body position-relative">
                <div class="loading-overlay" id="loadingOverlayTempo">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelaTempoAtuacao">
                        <thead>
                            <tr>
                                <th class="thead-estat">Período de Atuação</th>
                                <th class="thead-estat text-center">QTDE</th>
                                <th class="thead-estat text-center">%</th>
                            </tr>
                        </thead>
                        <tbody id="tableBodyTempo">
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <div class="spinner-border text-muted" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Carregando dados...</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="thead-estat">TOTAL</th>
                                <th class="thead-estat text-center" id="totalQtdeTempo">0</th>
                                <th class="thead-estat text-center">100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="./js/estatisticas_unified.js"></script>
    
    <!-- Theme Script -->
    <script>
        (function() {
            const storedTheme = localStorage.getItem("theme");
            const rawTheme = document.documentElement.getAttribute("data-theme");
            const allowed = new Set(["light", "dark"]);
            const chosen = allowed.has(rawTheme) ? rawTheme : (allowed.has(storedTheme) ? storedTheme : "light");
            document.documentElement.setAttribute("data-theme", chosen);
            localStorage.setItem("theme", chosen);
        })();
    </script>
</body>
</html>


----------

