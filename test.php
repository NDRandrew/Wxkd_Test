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
    
    if ($caseData) {
        echo json_encode([
            'success' => true,
            'data' => $caseData
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Caso não encontrado'
        ]);
    }
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


-------------

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
        
        if ($result && isset($result[0]['data'])) {
            $jsonData = $result[0]['data'];
            $decoded = json_decode($jsonData, true);
            
            // Validate decoded data
            if ($decoded && is_array($decoded)) {
                return $decoded;
            }
        }
        
        return null;
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
        require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\fpdf\fpdf.php');
        
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);
        
        // Title
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, utf8_decode('Simulador de Encerramento'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, utf8_decode('Mês de Referência: ' . $data['month']), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Historical Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, utf8_decode('Histórico'), 0, 1);
        $pdf->Ln(2);
        
        // Historical table header
        $pdf->SetFillColor(172, 25, 71);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 7, utf8_decode('Período'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Real', 1, 0, 'C', true);
        $pdf->Cell(30, 7, utf8_decode('Inauguração'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Cancelamento', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Total', 1, 1, 'C', true);
        
        // Historical table body
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);
        foreach ($data['historical'] as $hist) {
            $pdf->Cell(40, 6, utf8_decode($hist['label']), 1, 0, 'L');
            $pdf->Cell(30, 6, number_format($hist['real_value'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['inauguracao'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['cancelamento'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['total'], 0, ',', '.'), 1, 1, 'R');
        }
        
        $pdf->Ln(8);
        
        // Cases Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'Casos Simulados', 0, 1);
        $pdf->Ln(2);
        
        foreach ($data['cases'] as $case) {
            // Case title
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 7, utf8_decode($case['name']), 0, 1);
            $pdf->Ln(1);
            
            // Case data table
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, '', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Ln(0);
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, 'Real Value:', 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['realValue'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, utf8_decode('Inauguração:'), 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['inauguracao'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, 'Cancelamento:', 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['cancelamento'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(100, 7, 'Total:', 1, 0, 'L', true);
            $pdf->Cell(60, 7, number_format($case['total'], 0, ',', '.'), 1, 1, 'R', true);
            
            // Breakdown
            if (!empty($case['values'])) {
                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(0, 6, 'Detalhamento:', 0, 1);
                $pdf->SetFont('Arial', '', 9);
                
                foreach ($case['values'] as $type => $value) {
                    if ($value > 0) {
                        $pdf->Cell(100, 5, utf8_decode('  ' . $type), 1, 0, 'L');
                        $pdf->Cell(60, 5, number_format($value, 0, ',', '.'), 1, 1, 'R');
                    }
                }
            }
            
            $pdf->Ln(5);
        }
        
        return $pdf->Output('S');
    }
}
?>


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
        generateInputFields(); // Regenerate inputs with current case values
        updateCaseDisplay();
        updateSimulationChart();
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
        generateInputFields(); // Regenerate inputs for current case
        updateCaseDisplay();
        updateSimulationChart();
    }

    function generateInputFields() {
        const container = document.getElementById('userInputs');
        container.innerHTML = '';

        const currentCase = activeCases[currentCaseIndex];
        
        // Get types from current case values or use defaults
        let types = [];
        if (currentCase && currentCase.values && Object.keys(currentCase.values).length > 0) {
            types = Object.keys(currentCase.values);
        } else {
            // Default types - these should come from getCancelamentoTypes query
            types = ['Tipo A', 'Tipo B', 'Tipo C', 'Tipo D'];
        }
        
        types.forEach(type => {
            const inputGroup = document.createElement('div');
            inputGroup.className = 'value-input';
            
            const currentValue = currentCase && currentCase.values && currentCase.values[type] ? currentCase.values[type] : 0;
            
            inputGroup.innerHTML = `
                <label class="form-label">${type}</label>
                <input type="number" class="form-control" data-type="${type}" value="${currentValue}" min="0">
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
            if (data.success && data.data) {
                const caseData = data.data;
                
                // Check if we can add a new case
                if (activeCases.length >= 3) {
                    showNotification('Máximo de 3 casos simultâneos. Remova um caso antes de carregar outro.', 'warning');
                    return;
                }
                
                // Create new case with loaded data
                const loadedCase = {
                    id: caseData.id || Date.now(),
                    name: caseData.name || 'Caso Carregado',
                    values: caseData.values || {},
                    realValue: caseData.realValue || 0,
                    inauguracao: caseData.inauguracao || 0,
                    cancelamento: caseData.cancelamento || 0,
                    total: caseData.total || 0
                };
                
                // Add to active cases
                activeCases.push(loadedCase);
                currentCaseIndex = activeCases.length - 1;
                
                // Update UI - generateInputFields will populate with loaded values
                renderCaseTabs();
                generateInputFields();
                updateCaseDisplay();
                updateSimulationChart();
                
                showNotification('Caso carregado: ' + loadedCase.name, 'success');
            } else {
                showNotification('Erro ao carregar caso', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar caso', 'error');
        });
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
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'warning'} alert-dismissible fade show`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }, 5000);
        
        // Manual close
        notification.querySelector('.btn-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        });
    }

})();
