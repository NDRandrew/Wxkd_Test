<?php
session_start();
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
                Estatísticas de Encerramento
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="dataInicio" value="<?php echo date('Y-01-01'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="dataFim" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary" id="aplicarFiltros">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Motivos de Bloqueio Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Motivos de Bloqueio</h3>
        </div>
        <div class="card-body position-relative">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-vcenter table-bordered" id="tabelaMotivosBloqueio">
                    <thead>
                        <tr>
                            <th class="thead-estat">MOTIVO DE BLOQUEIO</th>
                            <th class="thead-estat text-center">QTDE</th>
                            <th class="thead-estat text-center">%</th>
                            <!-- Monthly columns will be added dynamically -->
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                Carregue os dados usando os filtros acima
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="thead-estat">TOTAL</td>
                            <td class="thead-estat text-center" id="totalQtde">0</td>
                            <td class="thead-estat text-center">100%</td>
                            <!-- Monthly totals will be added dynamically -->
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script src="./js/encerramento/encerramento_estat/estatistica_encerramento.js"></script>
    
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const rawTheme = (params.get("theme") || "").trim().toLowerCase();
            const allowed = new Set(["light", "dark"]);
            const storedTheme = localStorage.getItem("theme");
            const chosen = allowed.has(rawTheme) ? rawTheme : (allowed.has(storedTheme) ? storedTheme : "light");
            document.documentElement.setAttribute("data-theme", chosen);
            localStorage.setItem("theme", chosen);
        })();
    </script>
</body>
</html>


------------

<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento_estat\estatistica_encerramento_model.class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'getMotivosBloqueio') {
        try {
            $dataInicio = $_POST['data_inicio'] ?? null;
            $dataFim = $_POST['data_fim'] ?? null;
            
            $model = new EstatisticaEncerramento();
            $dados = $model->getMotivosBloqueio($dataInicio, $dataFim);
            
            // Process data for table display
            $processedData = processarMotivosBloqueio($dados);
            
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
}

function processarMotivosBloqueio($dados) {
    $motivos = [
        'Em processo de Cancelamento' => [],
        'Inoperante - Retirada de Equipamento' => [],
        'Depto - Falta de prestação de contas' => [],
        'Não Liberar - Falar c/ gerente geral' => [],
        'Correspondente abaixo do ponto' => [],
        'Inadimplência' => [],
        'Falta de prestação de contas' => [],
        'Demais' => []
    ];
    
    $meses = [];
    $total = 0;
    
    if (!$dados || !is_array($dados)) {
        return [
            'motivos' => $motivos,
            'meses' => [],
            'total' => 0
        ];
    }
    
    foreach ($dados as $row) {
        $motivo = trim($row['MOTIVO_BLOQUEIO'] ?? '');
        $mes = $row['MES_ANO'] ?? '';
        $qtde = (int)($row['QTDE'] ?? 0);
        
        if (empty($mes)) continue;
        
        if (!in_array($mes, $meses)) {
            $meses[] = $mes;
        }
        
        if (!isset($motivos[$motivo])) {
            if (!isset($motivos['Demais'][$mes])) {
                $motivos['Demais'][$mes] = 0;
            }
            $motivos['Demais'][$mes] += $qtde;
        } else {
            if (!isset($motivos[$motivo][$mes])) {
                $motivos[$motivo][$mes] = 0;
            }
            $motivos[$motivo][$mes] += $qtde;
        }
        
        $total += $qtde;
    }
    
    rsort($meses);
    
    return [
        'motivos' => $motivos,
        'meses' => $meses,
        'total' => $total
    ];
}
?>


-----------

<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php');

#[AllowDynamicProperties]
class EstatisticaEncerramento {
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }
    
    public function getSql() {
        return $this->sql;
    }
    
    // Get blocking reasons statistics
    public function getMotivosBloqueio($dataInicio = null, $dataFim = null) {
        $where = "1=1";
        
        if ($dataInicio && $dataFim) {
            $where .= " AND F.DATA_BLOQUEIO BETWEEN '$dataInicio' AND '$dataFim'";
        }
        
        $query = "
            SELECT 
                F.MOTIVO_BLOQUEIO,
                COUNT(*) as QTDE,
                FORMAT(F.DATA_BLOQUEIO, 'yyyy-MM') as MES_ANO
            FROM 
                DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
            WHERE 
                $where
                AND F.BE_INAUGURADO = 1
                AND F.DATA_BLOQUEIO IS NOT NULL
            GROUP BY 
                F.MOTIVO_BLOQUEIO,
                FORMAT(F.DATA_BLOQUEIO, 'yyyy-MM')
            ORDER BY 
                MES_ANO DESC, QTDE DESC
        ";
        
        return $this->sql->select($query);
    }
}
?>


-------------

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    aplicarFiltrosBtn.addEventListener('click', carregarDados);
    
    // Auto-load data on page load
    setTimeout(() => {
        carregarDados();
    }, 200);
    
    function carregarDados() {
        const dataInicio = document.getElementById('dataInicio').value;
        const dataFim = document.getElementById('dataFim').value;
        
        if (!dataInicio || !dataFim) {
            alert('Por favor, selecione ambas as datas');
            return;
        }
        
        loadingOverlay.classList.add('active');
        
        const formData = new FormData();
        formData.append('action', 'getMotivosBloqueio');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);
        
        fetch('./control/encerramento_estat/estatistica_encerramento_control.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            console.log('Response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    renderizarTabela(data.data);
                } else {
                    alert('Erro ao carregar dados: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (e) {
                console.error('Parse error:', e);
                alert('Erro ao processar resposta do servidor');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao carregar dados: ' + error.message);
        })
        .finally(() => {
            loadingOverlay.classList.remove('active');
        });
    }
    
    function renderizarTabela(data) {
        const thead = document.querySelector('#tabelaMotivosBloqueio thead tr');
        const tbody = document.getElementById('tableBody');
        const tfoot = document.querySelector('#tabelaMotivosBloqueio tfoot tr');
        
        // Clear existing monthly columns
        while (thead.children.length > 3) {
            thead.removeChild(thead.lastChild);
        }
        while (tfoot.children.length > 3) {
            tfoot.removeChild(tfoot.lastChild);
        }
        
        // Add monthly columns to header
        data.meses.forEach(mes => {
            const th = document.createElement('th');
            th.className = 'thead-estat text-center';
            th.textContent = formatarMes(mes);
            thead.appendChild(th);
        });
        
        // Clear tbody
        tbody.innerHTML = '';
        
        // Add rows for each motivo
        const motivos = data.motivos;
        const totalGeral = data.total;
        
        Object.keys(motivos).forEach(motivo => {
            const tr = document.createElement('tr');
            
            // Motivo column
            const tdMotivo = document.createElement('td');
            tdMotivo.textContent = motivo;
            tr.appendChild(tdMotivo);
            
            // Calculate total for this motivo
            let totalMotivo = 0;
            data.meses.forEach(mes => {
                totalMotivo += motivos[motivo][mes] || 0;
            });
            
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
            tdPerc.textContent = totalGeral > 0 ? ((totalMotivo / totalGeral) * 100).toFixed(1) + '%' : '0%';
            tr.appendChild(tdPerc);
            
            // Monthly columns
            data.meses.forEach(mes => {
                const td = document.createElement('td');
                td.className = 'text-center';
                td.textContent = motivos[motivo][mes] || 0;
                tr.appendChild(td);
            });
            
            tbody.appendChild(tr);
        });
        
        if (tbody.children.length === 0) {
            tbody.innerHTML = '<tr><td colspan="' + (3 + data.meses.length) + '" class="text-center py-5">Nenhum dado encontrado para o período selecionado</td></tr>';
        }
        
        // Update total footer
        document.getElementById('totalQtde').textContent = totalGeral;
        
        // Add monthly totals to footer
        data.meses.forEach(mes => {
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
    
    function formatarMes(mesAno) {
        const [ano, mes] = mesAno.split('-');
        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        return `${meses[parseInt(mes) - 1]}/${ano}`;
    }
});
```