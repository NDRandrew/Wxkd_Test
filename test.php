<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        :root {
            --chart-bg: #ffffff;
            --text-color: #262626;
            --border-color: #e6e6e6;
        }

        [data-theme="dark"] {
            --chart-bg: #1a1a1a;
            --text-color: #ffffff;
            --border-color: #333333;
        }

        .chart-container {
            min-height: 400px;
            padding: 1rem;
            background: var(--chart-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .value-input {
            margin-bottom: 0.5rem;
        }

        .case-tab {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            margin-right: 0.5rem;
            border-radius: 4px 4px 0 0;
        }

        .case-tab.active {
            background: var(--chart-bg);
            border-bottom: 1px solid var(--chart-bg);
        }

        .saved-cases {
            max-height: 300px;
            overflow-y: auto;
        }

        .chart-values {
            padding: 0.5rem;
            background: var(--chart-bg);
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 4px 4px;
        }

        .chart-values small {
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container-xl">
        <!-- Header with Month Selector and Actions -->
        <div class="card mb-3">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="card-title">Simulador de Encerramento</h3>
                    </div>
                    <div class="col-auto ms-auto">
                        <select class="form-select" id="monthSelector">
                            <option value="">Selecione o Mês</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" id="btnExport">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon me-1">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Side - Historical Charts -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Histórico de Encerramentos</h3>
                    </div>
                    <div class="card-body">
                        <div id="historicalCharts">
                            <!-- Single chart + table will be generated dynamically -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Simulation Cases -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Simulação</h3>
                            <button class="btn btn-sm btn-primary" id="btnAddCase">+ Novo Caso</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Case Tabs -->
                        <div class="d-flex mb-3" id="caseTabs"></div>

                        <!-- Case Content -->
                        <div id="caseContent">
                            <div class="chart-container mb-3" id="simulationChart"></div>
                            
                            <!-- Values Display -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-2">
                                            <small class="text-muted d-block">REAL_VALUE</small>
                                            <strong class="fs-3" id="realValue">0</strong>
                                        </div>
                                        <div class="col-6 mb-2">
                                            <small class="text-muted d-block">Inauguração</small>
                                            <strong class="fs-3 text-success" id="inauguracaoValue">0</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Cancelamento</small>
                                            <strong class="fs-3 text-danger" id="cancelamentoValue">0</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">TOTAL</small>
                                            <strong class="fs-3 text-primary" id="totalValue">0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Input Values -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Valores de Cancelamento</h4>
                                </div>
                                <div class="card-body" id="userInputs">
                                    <!-- Dynamic inputs will be generated -->
                                </div>
                            </div>

                            <!-- Save Case -->
                            <div class="mt-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="caseName" placeholder="Nome do caso">
                                    <button class="btn btn-success" id="btnSaveCase">Salvar Caso</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saved Cases -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Casos Salvos</h3>
                    </div>
                    <div class="card-body saved-cases" id="savedCasesList">
                        <!-- Saved cases will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="./js/encerramento/encerramento_simul/simulador_encerramento.js"></script>
</body>
</html>


---------

(function() {
    'use strict';

    const AJAX_URL = '/control/encerramento_simul/simulador_encerramento_control.php';
    
    let currentMonth = null;
    let activeCases = [];
    let currentCaseIndex = 0;
    let historicalData = [];

    function initialize() {
        loadMonthOptions();
        loadSavedCases();
        initializeEventListeners();
        addNewCase();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    function initializeEventListeners() {
        document.getElementById('monthSelector').addEventListener('change', handleMonthChange);
        document.getElementById('btnAddCase').addEventListener('click', handleAddCase);
        document.getElementById('btnSaveCase').addEventListener('click', handleSaveCase);
        document.getElementById('btnExport').addEventListener('click', handleExport);
    }

    function loadMonthOptions() {
        const selector = document.getElementById('monthSelector');
        const today = new Date();
        
        for (let i = 0; i < 12; i++) {
            const date = new Date(today.getFullYear(), today.getMonth() - i, 1);
            const option = document.createElement('option');
            option.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            option.textContent = date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            selector.appendChild(option);
        }
    }

    function handleMonthChange(e) {
        currentMonth = e.target.value;
        if (currentMonth) {
            loadHistoricalData();
            loadMonthData();
        }
    }

    function loadHistoricalData() {
        const formData = new FormData();
        formData.append('acao', 'get_historical_data');
        formData.append('month', currentMonth);

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                historicalData = data.data;
                renderHistoricalCharts();
            } else {
                showNotification('Erro ao carregar dados históricos', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar dados', 'error');
        });
    }

    function loadMonthData() {
        const formData = new FormData();
        formData.append('acao', 'get_month_data');
        formData.append('month', currentMonth);

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCurrentCase(data.data);
            } else {
                showNotification('Erro ao carregar dados do mês', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar dados', 'error');
        });
    }

    function renderHistoricalCharts() {
        const container = document.getElementById('historicalCharts');
        container.innerHTML = '';

        const chartDiv = document.createElement('div');
        chartDiv.id = 'historical-chart';
        chartDiv.style.height = '400px';
        chartDiv.className = 'mb-3';
        
        const valuesTable = document.createElement('div');
        valuesTable.className = 'table-responsive';
        valuesTable.innerHTML = `
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th class="text-end">Real</th>
                        <th class="text-end">Inauguração</th>
                        <th class="text-end">Cancelamento</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody id="historicalValuesTable"></tbody>
            </table>
        `;
        
        container.appendChild(chartDiv);
        container.appendChild(valuesTable);

        renderHistoricalBarChart();
        renderHistoricalValuesTable();
    }

    function renderHistoricalBarChart() {
        const categories = historicalData.map(d => d.label);
        const totals = historicalData.map(d => d.total);

        Highcharts.chart('historical-chart', {
            chart: { type: 'column' },
            title: { text: 'Histórico de Encerramentos', style: { fontSize: '16px' } },
            xAxis: { 
                categories: categories,
                labels: { 
                    rotation: -45,
                    style: { fontSize: '11px' }
                }
            },
            yAxis: { title: { text: 'Total' } },
            legend: { enabled: false },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        format: '{y}'
                    }
                }
            },
            series: [{
                name: 'Total',
                data: totals,
                color: '#AC1947'
            }],
            credits: { enabled: false }
        });
    }

    function renderHistoricalValuesTable() {
        const tbody = document.getElementById('historicalValuesTable');
        tbody.innerHTML = '';

        historicalData.forEach(data => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${data.label}</strong></td>
                <td class="text-end">${data.real_value.toLocaleString()}</td>
                <td class="text-end">${data.inauguracao.toLocaleString()}</td>
                <td class="text-end">${data.cancelamento.toLocaleString()}</td>
                <td class="text-end"><strong>${data.total.toLocaleString()}</strong></td>
            `;
            tbody.appendChild(row);
        });
    }

    function handleAddCase() {
        if (activeCases.length >= 3) {
            showNotification('Máximo de 3 casos simultâneos', 'warning');
            return;
        }
        if (!currentMonth) {
            showNotification('Selecione um mês primeiro', 'warning');
            return;
        }
        addNewCase();
        loadMonthData(); // Fetch REAL_VALUE for new case
    }

    function addNewCase() {
        const newCase = {
            id: Date.now(),
            name: `Caso ${activeCases.length + 1}`,
            values: {},
            realValue: 0,
            inauguracao: 0,
            cancelamento: 0,
            total: 0
        };
        
        activeCases.push(newCase);
        currentCaseIndex = activeCases.length - 1;
        renderCaseTabs();
        generateInputFields();
        updateSimulationChart();
    }

    function renderCaseTabs() {
        const container = document.getElementById('caseTabs');
        container.innerHTML = '';

        activeCases.forEach((caseData, index) => {
            const tab = document.createElement('div');
            tab.className = 'case-tab' + (index === currentCaseIndex ? ' active' : '');
            tab.textContent = caseData.name;
            tab.addEventListener('click', () => switchCase(index));
            
            const closeBtn = document.createElement('span');
            closeBtn.innerHTML = ' &times;';
            closeBtn.style.cursor = 'pointer';
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeCase(index);
            });
            
            tab.appendChild(closeBtn);
            container.appendChild(tab);
        });
    }

    function switchCase(index) {
        currentCaseIndex = index;
        renderCaseTabs();
        updateCaseDisplay();
    }

    function removeCase(index) {
        if (activeCases.length === 1) {
            showNotification('Deve haver pelo menos um caso', 'warning');
            return;
        }
        
        activeCases.splice(index, 1);
        if (currentCaseIndex >= activeCases.length) {
            currentCaseIndex = activeCases.length - 1;
        }
        renderCaseTabs();
        updateCaseDisplay();
    }

    function generateInputFields() {
        const container = document.getElementById('userInputs');
        container.innerHTML = '';

        // Query will return cancelamento_types
        const types = ['Tipo A', 'Tipo B', 'Tipo C', 'Tipo D'];
        
        types.forEach(type => {
            const inputGroup = document.createElement('div');
            inputGroup.className = 'value-input';
            inputGroup.innerHTML = `
                <label class="form-label">${type}</label>
                <input type="number" class="form-control" data-type="${type}" value="0" min="0">
            `;
            
            inputGroup.querySelector('input').addEventListener('input', handleInputChange);
            container.appendChild(inputGroup);
        });
    }

    function handleInputChange() {
        const currentCase = activeCases[currentCaseIndex];
        const inputs = document.querySelectorAll('#userInputs input');
        
        currentCase.cancelamento = 0;
        inputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            currentCase.values[input.dataset.type] = value;
            currentCase.cancelamento += value;
        });

        currentCase.total = currentCase.realValue - currentCase.cancelamento + currentCase.inauguracao;
        updateCaseDisplay();
        updateSimulationChart();
    }

    function updateCurrentCase(data) {
        // Update all active cases with the same month data
        activeCases.forEach(caseData => {
            caseData.realValue = data.real_value;
            caseData.inauguracao = data.inauguracao;
            // Recalculate total
            caseData.total = caseData.realValue - caseData.cancelamento + caseData.inauguracao;
        });
        
        updateCaseDisplay();
        updateSimulationChart();
    }

    function updateCaseDisplay() {
        const currentCase = activeCases[currentCaseIndex];
        document.getElementById('realValue').textContent = currentCase.realValue.toLocaleString();
        document.getElementById('inauguracaoValue').textContent = currentCase.inauguracao.toLocaleString();
        document.getElementById('cancelamentoValue').textContent = currentCase.cancelamento.toLocaleString();
        document.getElementById('totalValue').textContent = currentCase.total.toLocaleString();
    }

    function updateSimulationChart() {
        const currentCase = activeCases[currentCaseIndex];
        
        Highcharts.chart('simulationChart', {
            chart: { type: 'column', height: 250 },
            title: { text: currentCase.name, style: { fontSize: '16px' } },
            xAxis: { categories: ['Total'], labels: { enabled: false } },
            yAxis: { title: { text: '' } },
            legend: { enabled: false },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        format: '{y}',
                        style: { fontSize: '16px', fontWeight: 'bold' }
                    }
                }
            },
            series: [{
                name: 'Total',
                data: [currentCase.total],
                color: '#AC1947'
            }],
            credits: { enabled: false }
        });
    }

    function handleSaveCase() {
        const caseName = document.getElementById('caseName').value.trim();
        if (!caseName) {
            showNotification('Digite um nome para o caso', 'warning');
            return;
        }

        const currentCase = activeCases[currentCaseIndex];
        const formData = new FormData();
        formData.append('acao', 'save_case');
        formData.append('name', caseName);
        formData.append('month', currentMonth);
        formData.append('data', JSON.stringify(currentCase));

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Caso salvo com sucesso', 'success');
                document.getElementById('caseName').value = '';
                loadSavedCases();
            } else {
                showNotification('Erro ao salvar caso', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao salvar', 'error');
        });
    }

    function loadSavedCases() {
        const formData = new FormData();
        formData.append('acao', 'get_saved_cases');

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSavedCases(data.cases);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function renderSavedCases(cases) {
        const container = document.getElementById('savedCasesList');
        container.innerHTML = '';

        if (cases.length === 0) {
            container.innerHTML = '<p class="text-muted">Nenhum caso salvo</p>';
            return;
        }

        cases.forEach(caseData => {
            const item = document.createElement('div');
            item.className = 'card mb-2';
            item.innerHTML = `
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${caseData.name}</strong>
                            <small class="text-muted d-block">${caseData.month}</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-primary" onclick="window.loadCase(${caseData.id})">Carregar</button>
                            <button class="btn btn-sm btn-danger" onclick="window.deleteCase(${caseData.id})">Excluir</button>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(item);
        });
    }

    window.loadCase = function(caseId) {
        const formData = new FormData();
        formData.append('acao', 'load_case');
        formData.append('case_id', caseId);

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Load case data into current case
                showNotification('Caso carregado', 'success');
            }
        })
        .catch(error => console.error('Error:', error));
    };

    window.deleteCase = function(caseId) {
        if (!confirm('Deseja excluir este caso?')) return;

        const formData = new FormData();
        formData.append('acao', 'delete_case');
        formData.append('case_id', caseId);

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Caso excluído', 'success');
                loadSavedCases();
            }
        })
        .catch(error => console.error('Error:', error));
    };

    function handleExport() {
        if (!currentMonth) {
            showNotification('Selecione um mês primeiro', 'warning');
            return;
        }

        // Collect all data for export
        const exportData = {
            month: currentMonth,
            historical: historicalData,
            cases: activeCases.map(c => ({
                name: c.name,
                realValue: c.realValue,
                inauguracao: c.inauguracao,
                cancelamento: c.cancelamento,
                total: c.total,
                values: c.values
            }))
        };

        // Send to backend for PDF generation
        const formData = new FormData();
        formData.append('acao', 'export_pdf');
        formData.append('data', JSON.stringify(exportData));

        // Show loading state
        const btnExport = document.getElementById('btnExport');
        const originalText = btnExport.innerHTML;
        btnExport.disabled = true;
        btnExport.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando PDF...';

        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.blob())
        .then(blob => {
            // Create download link
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `simulador_encerramento_${currentMonth}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showNotification('PDF exportado com sucesso', 'success');
        })
        .catch(error => {
            console.error('Export error:', error);
            showNotification('Erro ao exportar PDF', 'error');
        })
        .finally(() => {
            btnExport.innerHTML = originalText;
            btnExport.disabled = false;
        });
    }

    function showNotification(message, type) {
        console.log(`[${type}] ${message}`);
        // Implement notification UI
    }

})();


----------

<?php
session_start();
header('Content-Type: application/json');

require_once('../../model/encerramento_simul/simulador_encerramento_model.class.php');

$model = new SimuladorEncerramento();
$acao = isset($_POST['acao']) ? $_POST['acao'] : '';

try {
    switch ($acao) {
        case 'get_historical_data':
            handleGetHistoricalData($model);
            break;
            
        case 'get_month_data':
            handleGetMonthData($model);
            break;
            
        case 'save_case':
            handleSaveCase($model);
            break;
            
        case 'get_saved_cases':
            handleGetSavedCases($model);
            break;
            
        case 'load_case':
            handleLoadCase($model);
            break;
            
        case 'delete_case':
            handleDeleteCase($model);
            break;
            
        case 'export_pdf':
            handleExportPDF($model);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGetHistoricalData($model) {
    $month = $_POST['month'];
    $data = $model->getHistoricalData($month);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

function handleGetMonthData($model) {
    $month = $_POST['month'];
    $data = $model->getMonthData($month);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}

function handleSaveCase($model) {
    $cod_func = $_SESSION['cod_func'] ?? 0;
    $name = $_POST['name'];
    $month = $_POST['month'];
    $data = $_POST['data'];
    
    $result = $model->saveCase($cod_func, $name, $month, $data);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Caso salvo com sucesso' : 'Erro ao salvar caso'
    ]);
}

function handleGetSavedCases($model) {
    $cod_func = $_SESSION['cod_func'] ?? 0;
    $cases = $model->getSavedCases($cod_func);
    
    echo json_encode([
        'success' => true,
        'cases' => $cases
    ]);
}

function handleLoadCase($model) {
    $case_id = $_POST['case_id'];
    $caseData = $model->loadCase($case_id);
    
    echo json_encode([
        'success' => true,
        'data' => $caseData
    ]);
}

function handleDeleteCase($model) {
    $case_id = $_POST['case_id'];
    $result = $model->deleteCase($case_id);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Caso excluído' : 'Erro ao excluir'
    ]);
}

function handleExportPDF($model) {
    $data = json_decode($_POST['data'], true);
    
    // Generate PDF
    $pdfContent = $model->generatePDF($data);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="simulador_encerramento_' . $data['month'] . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    
    echo $pdfContent;
    exit;
}
?>


---------

<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php');

#[AllowDynamicProperties]
class SimuladorEncerramento {
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }
    
    public function getSql() {
        return $this->sql;
    }

    /**
     * QUERY DESCRIPTIONS NEEDED:
     * 
     * 1. QUERY_REAL_VALUE: Get total count for a given period
     *    Return: COUNT of existing correspondentes
     * 
     * 2. QUERY_INAUGURACAO: Get inaugurated count for period
     *    Return: COUNT of new correspondentes
     * 
     * 3. QUERY_CANCELAMENTO: Get cancelled count for period  
     *    Return: COUNT of cancelled correspondentes
     * 
     * 4. QUERY_CANCELAMENTO_TYPES: Get cancellation categories
     *    Return: List of cancellation reason types
     */
    
    public function getHistoricalData($month) {
        list($year, $monthNum) = explode('-', $month);
        $periods = $this->calculateHistoricalPeriods($year, $monthNum);
        
        $data = [];
        foreach ($periods as $period) {
            $realValue = $this->getRealValue($period['value']);
            $inauguracao = $this->getInauguracao($period['value']);
            $cancelamento = $this->getCancelamento($period['value']);
            
            $data[] = [
                'label' => $period['label'],
                'real_value' => $realValue,
                'inauguracao' => $inauguracao,
                'cancelamento' => $cancelamento,
                'total' => $realValue - $cancelamento + $inauguracao
            ];
        }
        
        return $data;
    }

    private function calculateHistoricalPeriods($year, $month) {
        $periods = [];
        
        // Previous month
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $periods[] = [
            'label' => date('M/Y', mktime(0, 0, 0, $prevMonth, 1, $prevYear)),
            'value' => sprintf('%04d-%02d', $prevYear, $prevMonth),
            'type' => 'month'
        ];
        
        // Last 4 quarters
        $currentQuarter = ceil($month / 3);
        for ($i = 1; $i <= 4; $i++) {
            $quarter = $currentQuarter - $i;
            $qYear = $year;
            
            if ($quarter < 1) {
                $quarter += 4;
                $qYear--;
            }
            
            $qLabel = "Q{$quarter}/{$qYear}";
            $qMonth = ($quarter * 3);
            
            $periods[] = [
                'label' => $qLabel,
                'value' => sprintf('%04d-Q%d', $qYear, $quarter),
                'type' => 'quarter'
            ];
        }
        
        // Same month last year
        $periods[] = [
            'label' => date('M/Y', mktime(0, 0, 0, $month, 1, $year - 1)),
            'value' => sprintf('%04d-%02d', $year - 1, $month),
            'type' => 'month'
        ];
        
        return $periods;
    }

    private function getRealValue($period) {
        error_log("SimuladorEncerramento::getRealValue - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM DATALAKE..DL_BRADESCO_EXPRESSO 
            WHERE BE_INAUGURADO = 1 
            AND FORMAT(DATA_INAUGURACAO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    private function getInauguracao($period) {
        error_log("SimuladorEncerramento::getInauguracao - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM DATALAKE..DL_BRADESCO_EXPRESSO 
            WHERE FORMAT(DATA_INAUGURACAO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    private function getCancelamento($period) {
        error_log("SimuladorEncerramento::getCancelamento - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM MESU..TB_LOJAS 
            WHERE FORMAT(DT_ENCERRAMENTO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    public function getMonthData($month) {
        $realValue = $this->getRealValue($month);
        $inauguracao = $this->getInauguracao($month);
        
        return [
            'real_value' => $realValue,
            'inauguracao' => $inauguracao,
            'cancelamento' => 0
        ];
    }

    public function getCancelamentoTypes() {
        // TODO: Replace with actual query to get cancellation reason categories
        $query = "
            SELECT DISTINCT MOTIVO_ENCERRAMENTO 
            FROM MESU..ENCERRAMENTO_TB_PORTAL 
            WHERE MOTIVO_ENCERRAMENTO IS NOT NULL
            ORDER BY MOTIVO_ENCERRAMENTO
        ";
        
        $result = $this->sql->select($query);
        return $result;
    }

    public function saveCase($cod_func, $name, $month, $data) {
        $query = "
            INSERT INTO MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            (COD_FUNC, NOME_CASO, MES_REF, DADOS_JSON, DATA_CAD) 
            VALUES (
                {$cod_func}, 
                '" . addslashes($name) . "', 
                '{$month}', 
                '" . addslashes($data) . "', 
                GETDATE()
            )
        ";
        
        return $this->sql->insert($query);
    }

    public function getSavedCases($cod_func) {
        $query = "
            SELECT 
                ID_CASO as id,
                NOME_CASO as name,
                MES_REF as month,
                DATA_CAD as created_at
            FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            WHERE COD_FUNC = {$cod_func}
            ORDER BY DATA_CAD DESC
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result : [];
    }

    public function loadCase($case_id) {
        $query = "
            SELECT DADOS_JSON as data
            FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            WHERE ID_CASO = " . intval($case_id);
        
        $result = $this->sql->select($query);
        return $result ? json_decode($result[0]['data'], true) : null;
    }

    public function deleteCase($case_id) {
        $query = "
            DELETE FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            WHERE ID_CASO = " . intval($case_id);
        
        return $this->sql->delete($query);
    }

    public function insert($query) {
        return $this->sql->insert($query);
    }

    public function update($query) {
        return $this->sql->update($query);
    }

    public function delete($query) {
        return $this->sql->delete($query);
    }

    public function generatePDF($data) {
        // Create HTML content for PDF
        $html = $this->buildPDFHTML($data);
        
        // Use TCPDF or similar library to generate PDF
        // For now, creating a simple HTML-based approach
        require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\tcpdf\tcpdf.php');
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Portal Expresso');
        $pdf->SetAuthor('Portal Expresso');
        $pdf->SetTitle('Simulador de Encerramento - ' . $data['month']);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->AddPage();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }

    private function buildPDFHTML($data) {
        $html = '<h1>Simulador de Encerramento</h1>';
        $html .= '<h2>Mês de Referência: ' . $data['month'] . '</h2>';
        
        // Historical data section
        $html .= '<h3>Histórico</h3>';
        $html .= '<table border="1" cellpadding="5" style="width:100%">';
        $html .= '<thead>';
        $html .= '<tr style="background-color:#AC1947;color:#fff;">';
        $html .= '<th>Período</th><th>Real</th><th>Inauguração</th><th>Cancelamento</th><th>Total</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($data['historical'] as $hist) {
            $html .= '<tr>';
            $html .= '<td><strong>' . $hist['label'] . '</strong></td>';
            $html .= '<td align="right">' . number_format($hist['real_value'], 0, ',', '.') . '</td>';
            $html .= '<td align="right">' . number_format($hist['inauguracao'], 0, ',', '.') . '</td>';
            $html .= '<td align="right">' . number_format($hist['cancelamento'], 0, ',', '.') . '</td>';
            $html .= '<td align="right"><strong>' . number_format($hist['total'], 0, ',', '.') . '</strong></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        // Cases section
        $html .= '<h3>Casos Simulados</h3>';
        foreach ($data['cases'] as $case) {
            $html .= '<h4>' . $case['name'] . '</h4>';
            $html .= '<table border="1" cellpadding="5" style="width:100%;margin-bottom:20px;">';
            $html .= '<tr><td><strong>Real Value:</strong></td><td align="right">' . number_format($case['realValue'], 0, ',', '.') . '</td></tr>';
            $html .= '<tr><td><strong>Inauguração:</strong></td><td align="right">' . number_format($case['inauguracao'], 0, ',', '.') . '</td></tr>';
            $html .= '<tr><td><strong>Cancelamento:</strong></td><td align="right">' . number_format($case['cancelamento'], 0, ',', '.') . '</td></tr>';
            $html .= '<tr style="background-color:#f0f0f0;"><td><strong>Total:</strong></td><td align="right"><strong>' . number_format($case['total'], 0, ',', '.') . '</strong></td></tr>';
            
            if (!empty($case['values'])) {
                $html .= '<tr><td colspan="2"><strong>Detalhamento:</strong></td></tr>';
                foreach ($case['values'] as $type => $value) {
                    if ($value > 0) {
                        $html .= '<tr><td>' . $type . '</td><td align="right">' . number_format($value, 0, ',', '.') . '</td></tr>';
                    }
                }
            }
            $html .= '</table>';
        }
        
        return $html;
    }
}
?>
