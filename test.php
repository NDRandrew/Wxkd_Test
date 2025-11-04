<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php');

#[AllowDynamicProperties]
class MotivosEncerramento {
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }
    
    public function getSql() {
        return $this->sql;
    }
    
    // Get closure reasons statistics
    public function getMotivosEncerramento($dataInicio = null, $dataFim = null) {
        $where = "1=1";
        
        if ($dataInicio && $dataFim) {
            $where .= " AND F.DATA_ENCERRAMENTO BETWEEN '$dataInicio' AND '$dataFim'";
        }
        
        $query = "
            SELECT 
                F.DESC_MOTIVO_ENCERRAMENTO,
                COUNT(*) as QTDE,
                FORMAT(F.DATA_ENCERRAMENTO, 'yyyy-MM') as MES_ANO
            FROM 
                DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
            WHERE 
                $where
                AND F.BE_INAUGURADO = 1
                AND F.DATA_ENCERRAMENTO IS NOT NULL
                AND F.DESC_MOTIVO_ENCERRAMENTO IS NOT NULL
            GROUP BY 
                F.DESC_MOTIVO_ENCERRAMENTO,
                FORMAT(F.DATA_ENCERRAMENTO, 'yyyy-MM')
            ORDER BY 
                MES_ANO DESC, QTDE DESC
        ";
        
        return $this->sql->select($query);
    }
}
?>

---------


<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento_estat\motivos_encerramento_model.class.php';

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
            
            echo json_encode([
                'success' => true,
                'data' => $processedData
            ]);
        } catch (Exception $e) {
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
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

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


------------

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
    </style>
</head>
<body>
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-2">
                    <path d="M3 3v18h18"/>
                    <path d="m19 9-5 5-4-4-3 3"/>
                </svg>
                Motivos de Encerramento
            </h3>
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

    <!-- Motivos de Encerramento Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Estatísticas por Motivo de Encerramento</h3>
        </div>
        <div class="card-body position-relative">
            <div class="loading-overlay" id="loadingOverlay">
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
                    <tbody id="tableBody">
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
                            <th class="thead-estat text-center" id="totalQtde">0</th>
                            <th class="thead-estat text-center">100%</th>
                            <!-- Monthly totals will be added dynamically -->
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script src="./js/motivos_encerramento.js"></script>
    
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

------------

(function() {
    'use strict';
    
    const AJAX_URL = './control/encerramento_estat/motivos_encerramento_control.php';
    
    // Wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
        const exportarCSVBtn = document.getElementById('exportarCSV');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        if (!aplicarFiltrosBtn || !exportarCSVBtn || !loadingOverlay) {
            console.error('Required elements not found');
            return;
        }
        
        aplicarFiltrosBtn.addEventListener('click', carregarDados);
        exportarCSVBtn.addEventListener('click', exportarCSV);
        
        // Auto-load data on page load
        setTimeout(() => {
            carregarDados();
        }, 200);
    }
    
    function carregarDados() {
        const dataInicio = document.getElementById('dataInicio')?.value;
        const dataFim = document.getElementById('dataFim')?.value;
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        if (!dataInicio || !dataFim) {
            showNotification('Por favor, selecione ambas as datas', 'warning');
            return;
        }
        
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
        
        const formData = new FormData();
        formData.append('action', 'getMotivosEncerramento');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                renderizarTabela(data.data);
                showNotification('Dados carregados com sucesso!', 'success');
            } else {
                throw new Error(data.error || 'Erro ao processar dados');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar dados: ' + error.message, 'error');
        })
        .finally(() => {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
            }
        });
    }
    
    function exportarCSV() {
        const dataInicio = document.getElementById('dataInicio')?.value;
        const dataFim = document.getElementById('dataFim')?.value;
        
        if (!dataInicio || !dataFim) {
            showNotification('Por favor, selecione ambas as datas', 'warning');
            return;
        }
        
        showNotification('Gerando arquivo CSV...', 'info');
        
        // Create a form and submit it to trigger download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = AJAX_URL;
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'exportCSV';
        form.appendChild(actionInput);
        
        const dataInicioInput = document.createElement('input');
        dataInicioInput.type = 'hidden';
        dataInicioInput.name = 'data_inicio';
        dataInicioInput.value = dataInicio;
        form.appendChild(dataInicioInput);
        
        const dataFimInput = document.createElement('input');
        dataFimInput.type = 'hidden';
        dataFimInput.name = 'data_fim';
        dataFimInput.value = dataFim;
        form.appendChild(dataFimInput);
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Show success message after a delay
        setTimeout(() => {
            showNotification('Arquivo CSV gerado com sucesso!', 'success');
        }, 500);
    }
    
    function renderizarTabela(data) {
        const thead = document.querySelector('#tabelaMotivosEncerramento thead tr');
        const tbody = document.getElementById('tableBody');
        const tfoot = document.querySelector('#tabelaMotivosEncerramento tfoot tr');
        
        if (!thead || !tbody || !tfoot) {
            console.error('Table elements not found');
            return;
        }
        
        // Clear existing monthly columns
        while (thead.children.length > 3) {
            thead.removeChild(thead.lastChild);
        }
        while (tfoot.children.length > 3) {
            tfoot.removeChild(tfoot.lastChild);
        }
        
        // Clear tbody
        tbody.innerHTML = '';
        
        const motivos = data.motivos || {};
        const meses = data.meses || [];
        const totalGeral = data.total || 0;
        
        // Add monthly columns to header
        if (meses && meses.length > 0) {
            meses.forEach(mes => {
                const th = document.createElement('th');
                th.className = 'thead-estat text-center';
                th.textContent = formatarMes(mes);
                thead.appendChild(th);
            });
        }
        
        // Add rows for each motivo
        if (Object.keys(motivos).length > 0) {
            Object.keys(motivos).forEach(motivo => {
                const tr = document.createElement('tr');
                
                // Motivo column
                const tdMotivo = document.createElement('td');
                tdMotivo.textContent = motivo;
                tr.appendChild(tdMotivo);
                
                // Calculate total for this motivo
                let totalMotivo = 0;
                if (meses) {
                    meses.forEach(mes => {
                        totalMotivo += motivos[motivo][mes] || 0;
                    });
                }
                
                // Skip if no data for this motivo
                if (totalMotivo === 0) return;
                
                // QTDE column
                const tdQtde = document.createElement('td');
                tdQtde.className = 'text-center';
                tdQtde.textContent = totalMotivo;
                tr.appendChild(tdQtde);
                
                // % column
                const tdPerc = document.createElement('td');
                tdPerc.className = 'text-center';
                tdPerc.textContent = totalGeral > 0 ? 
                    ((totalMotivo / totalGeral) * 100).toFixed(1) + '%' : '0%';
                tr.appendChild(tdPerc);
                
                // Monthly columns
                if (meses) {
                    meses.forEach(mes => {
                        const td = document.createElement('td');
                        td.className = 'text-center';
                        td.textContent = motivos[motivo][mes] || 0;
                        tr.appendChild(td);
                    });
                }
                
                tbody.appendChild(tr);
            });
        }
        
        if (tbody.children.length === 0) {
            const colspan = 3 + (meses ? meses.length : 0);
            tbody.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center py-5">Nenhum dado encontrado para o período selecionado</td></tr>';
        }
        
        // Update total footer
        const totalQtdeEl = document.getElementById('totalQtde');
        if (totalQtdeEl) {
            totalQtdeEl.textContent = totalGeral;
        }
        
        // Add monthly totals to footer
        if (meses) {
            meses.forEach(mes => {
                let totalMes = 0;
                Object.keys(motivos).forEach(motivo => {
                    totalMes += motivos[motivo][mes] || 0;
                });
                
                const td = document.createElement('td');
                td.className = 'thead-estat text-center';
                td.textContent = totalMes;
                tfoot.appendChild(td);
            });
        }
    }
    
    function formatarMes(mesAno) {
        if (!mesAno) return '';
        const parts = mesAno.split('-');
        if (parts.length !== 2) return mesAno;
        
        const ano = parts[0];
        const mes = parts[1];
        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const mesIndex = parseInt(mes) - 1;
        
        return meses[mesIndex] ? `${meses[mesIndex]}/${ano}` : mesAno;
    }
    
    function showNotification(message, type = 'info') {
        const container = document.createElement('div');
        const alertClass = type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'info' ? 'info' : 'success';
        container.className = `alert alert-${alertClass} alert-dismissible fade show`;
        
        const existingNotifications = document.querySelectorAll('.alert[style*="position: fixed"]');
        const topOffset = 20 + (existingNotifications.length * 80);
        
        container.style.cssText = `position: fixed; top: ${topOffset}px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px; transition: all 0.3s ease;`;
        container.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(container);
        
        setTimeout(() => {
            container.style.opacity = '0';
            setTimeout(() => {
                container.remove();
                repositionNotifications();
            }, 300);
        }, 5000);
        
        const closeBtn = container.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                container.style.opacity = '0';
                setTimeout(() => {
                    container.remove();
                    repositionNotifications();
                }, 300);
            });
        }
    }
    
    function repositionNotifications() {
        const notifications = document.querySelectorAll('.alert[style*="position: fixed"]');
        notifications.forEach((notif, index) => {
            notif.style.top = (20 + (index * 80)) + 'px';
        });
    }
})();