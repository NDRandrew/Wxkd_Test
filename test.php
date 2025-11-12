(function() {
    'use strict';

    const AJAX_URL = '/control/encerramento_simul/simulador_encerramento_control.php';
    
    let currentMonth = null;
    let activeCases = [];
    let currentCaseIndex = 0;
    let historicalData = [];
    let currentTheme = 'light';

    function initialize() {
        loadMonthOptions();
        loadSavedCases();
        initializeEventListeners();
        initializeSidebarToggle();
        detectTheme();
        addNewCase();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    function initializeSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('savedCasesSidebar');
        const mainContent = document.getElementById('mainContent');
        
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            toggleBtn.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }

    function detectTheme() {
        // Detect current theme
        const theme = document.documentElement.getAttribute('data-theme') || 'light';
        currentTheme = theme;
        
        // Watch for theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'data-theme') {
                    currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
                    // Redraw all charts with new theme
                    if (historicalData.length > 0) {
                        renderHistoricalBarChart();
                    }
                    if (activeCases.length > 0) {
                        updateSimulationChart();
                    }
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    function getChartTheme() {
        const isDark = currentTheme === 'dark';
        return {
            backgroundColor: isDark ? '#1a1a1a' : '#ffffff',
            textColor: isDark ? '#ffffff' : '#262626',
            gridColor: isDark ? '#333333' : '#e6e6e6'
        };
    }

    function showHistoricalLoading() {
        const loading = document.getElementById('historicalLoading');
        const charts = document.getElementById('historicalCharts');
        if (loading) loading.style.display = 'block';
        if (charts) charts.style.opacity = '0';
    }

    function hideHistoricalLoading() {
        const loading = document.getElementById('historicalLoading');
        const charts = document.getElementById('historicalCharts');
        if (loading) loading.style.display = 'none';
        if (charts) charts.style.opacity = '1';
    }

    function showSimulationLoading() {
        const loading = document.getElementById('simulationLoading');
        const chart = document.getElementById('simulationChart');
        if (loading) loading.style.display = 'block';
        if (chart) chart.style.opacity = '0';
    }

    function hideSimulationLoading() {
        const loading = document.getElementById('simulationLoading');
        const chart = document.getElementById('simulationChart');
        if (loading) loading.style.display = 'none';
        if (chart) chart.style.opacity = '1';
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
        showHistoricalLoading();
        
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
                hideHistoricalLoading();
            } else {
                showNotification('Erro ao carregar dados históricos', 'error');
                hideHistoricalLoading();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar dados', 'error');
            hideHistoricalLoading();
        });
    }

    function loadMonthData() {
        showSimulationLoading();
        
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
                hideSimulationLoading();
            } else {
                showNotification('Erro ao carregar dados do mês', 'error');
                hideSimulationLoading();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao carregar dados', 'error');
            hideSimulationLoading();
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
        const theme = getChartTheme();

        Highcharts.chart('historical-chart', {
            chart: { 
                type: 'column',
                backgroundColor: theme.backgroundColor
            },
            title: { 
                text: 'Histórico de Encerramentos', 
                style: { fontSize: '16px', color: theme.textColor }
            },
            xAxis: { 
                categories: categories,
                labels: { 
                    rotation: -45,
                    style: { fontSize: '11px', color: theme.textColor }
                },
                gridLineColor: theme.gridColor
            },
            yAxis: { 
                title: { text: 'Total', style: { color: theme.textColor } },
                labels: { style: { color: theme.textColor } },
                gridLineColor: theme.gridColor
            },
            legend: { 
                enabled: false,
                itemStyle: { color: theme.textColor }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        format: '{y}',
                        style: { color: theme.textColor }
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
        
        showSimulationLoading();
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
        const theme = getChartTheme();
        
        Highcharts.chart('simulationChart', {
            chart: { 
                type: 'column', 
                height: 250,
                backgroundColor: theme.backgroundColor
            },
            title: { 
                text: currentCase.name, 
                style: { fontSize: '16px', color: theme.textColor }
            },
            xAxis: { 
                categories: ['Total'], 
                labels: { enabled: false },
                gridLineColor: theme.gridColor
            },
            yAxis: { 
                title: { text: '', style: { color: theme.textColor } },
                labels: { style: { color: theme.textColor } },
                gridLineColor: theme.gridColor
            },
            legend: { 
                enabled: false,
                itemStyle: { color: theme.textColor }
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        format: '{y}',
                        style: { fontSize: '16px', fontWeight: 'bold', color: theme.textColor }
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
        
        hideSimulationLoading();
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
        showSimulationLoading();
        
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
                    hideSimulationLoading();
                    showNotification('Máximo de 3 casos simultâneos. Remova um caso antes de carregar outro.', 'warning');
                    return;
                }
                
                // Get the month from database (from saved case metadata)
                const caseMonth = data.month; // MES_REF from database
                
                // Update month selector to match case month
                if (caseMonth) {
                    document.getElementById('monthSelector').value = caseMonth;
                    currentMonth = caseMonth;
                    
                    // Reload historical data for this month
                    loadHistoricalData();
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
                
                // Show loading before rendering
                showSimulationLoading();
                
                // Update UI - generateInputFields will populate with loaded values
                renderCaseTabs();
                generateInputFields();
                updateCaseDisplay();
                
                showSimulationLoading();
                setTimeout(() => {
                    updateSimulationChart();
                }, 100);
                
                showNotification('Caso carregado: ' + loadedCase.name, 'success');
            } else {
                hideSimulationLoading();
                showNotification('Erro ao carregar caso', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideSimulationLoading();
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

        const btnExport = document.getElementById('btnExport');
        const originalText = btnExport.innerHTML;
        btnExport.disabled = true;
        btnExport.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gerando PDF...';

        // Export charts as images
        const historicalChart = Highcharts.charts.find(c => c && c.renderTo.id === 'historical-chart');
        const simulationChart = Highcharts.charts.find(c => c && c.renderTo.id === 'simulationChart');

        Promise.all([
            historicalChart ? getChartImage(historicalChart) : Promise.resolve(null),
            simulationChart ? getChartImage(simulationChart) : Promise.resolve(null)
        ]).then(([historicalImg, simulationImg]) => {
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
                })),
                charts: {
                    historical: historicalImg,
                    simulation: simulationImg
                }
            };

            // Send to backend for PDF generation
            const formData = new FormData();
            formData.append('acao', 'export_pdf');
            formData.append('data', JSON.stringify(exportData));

            fetch(AJAX_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
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
        });
    }

    function getChartImage(chart) {
        return new Promise((resolve) => {
            try {
                // Use Highcharts built-in export
                const svg = chart.getSVG();
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Set canvas size (2x for quality)
                canvas.width = chart.chartWidth * 2;
                canvas.height = chart.chartHeight * 2;
                
                // Create image from SVG
                const img = new Image();
                const blob = new Blob([svg], { type: 'image/svg+xml' });
                const url = URL.createObjectURL(blob);
                
                img.onload = function() {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    const dataUrl = canvas.toDataURL('image/png');
                    URL.revokeObjectURL(url);
                    resolve(dataUrl);
                };
                
                img.onerror = function() {
                    URL.revokeObjectURL(url);
                    resolve(null);
                };
                
                img.src = url;
            } catch (e) {
                console.error('Chart export error:', e);
                resolve(null);
            }
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
