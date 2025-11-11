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
                        <h3 class="card-title">Histórico</h3>
                    </div>
                    <div class="card-body">
                        <div class="row" id="historicalCharts">
                            <!-- Charts will be generated dynamically -->
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


-----------


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

        historicalData.forEach((data, index) => {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-sm-6 mb-3';
            
            const chartDiv = document.createElement('div');
            chartDiv.id = `hist-chart-${index}`;
            chartDiv.style.height = '200px';
            
            const valuesDiv = document.createElement('div');
            valuesDiv.className = 'chart-values mt-2 text-center';
            valuesDiv.innerHTML = `
                <small class="d-block"><strong>Real:</strong> ${data.real_value.toLocaleString()}</small>
                <small class="d-block"><strong>Inauguração:</strong> ${data.inauguracao.toLocaleString()}</small>
                <small class="d-block"><strong>Cancelamento:</strong> ${data.cancelamento.toLocaleString()}</small>
            `;
            
            col.appendChild(chartDiv);
            col.appendChild(valuesDiv);
            container.appendChild(col);

            renderBarChart(chartDiv.id, data);
        });
    }

    function renderBarChart(containerId, data) {
        Highcharts.chart(containerId, {
            chart: { type: 'column' },
            title: { text: data.label, style: { fontSize: '14px' } },
            xAxis: { categories: ['Total'], labels: { enabled: false } },
            yAxis: { title: { text: '' } },
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
                data: [data.total],
                color: '#AC1947'
            }],
            credits: { enabled: false }
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
        const currentCase = activeCases[currentCaseIndex];
        currentCase.realValue = data.real_value;
        currentCase.inauguracao = data.inauguracao;
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
        const chart = Highcharts.charts.find(c => c && c.renderTo.id === 'simulationChart');
        if (!chart) return;

        const menu = document.createElement('div');
        menu.className = 'dropdown-menu show';
        menu.style.position = 'absolute';
        menu.innerHTML = `
            <button class="dropdown-item" data-type="png">PNG</button>
            <button class="dropdown-item" data-type="jpeg">JPEG</button>
            <button class="dropdown-item" data-type="pdf">PDF</button>
            <button class="dropdown-item" data-type="svg">SVG</button>
        `;

        menu.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                chart.exportChart({ type: 'image/' + btn.dataset.type });
                menu.remove();
            });
        });

        document.body.appendChild(menu);
        setTimeout(() => menu.remove(), 5000);
    }

    function showNotification(message, type) {
        console.log(`[${type}] ${message}`);
        // Implement notification UI
    }

})();
 