<?php

// Add these methods to your EnhancedInventoryModel class

/**
 * Get inventory count by SECAO (fixed version)
 */
function getInventarioCountBySecao($secao){
    $query = "SELECT COUNT(B.SECAO) as total FROM INFRA.DBO.TB_INVENTARIO_BE A 
                RIGHT JOIN (SELECT DISTINCT
                        A.cod_func,
                        A.nome_func,
                        A.E_MAIL AS Email_Func,
                        RAMAL,
                        RAMAL_INTERNO,
                        DDD_CEL_CORPORATIVO,
                        CELULAR_CORPORATIVO,
                        A.SECAO
                    FROM MESU..FUNCIONARIOS AS A
                    WHERE DT_TransDem IS NULL
                    ) B ON B.COD_FUNC=A.COD_FUNC
                    WHERE B.SECAO = '$secao'";
    $result = $this->sqlDb->select($query);
    return isset($result[0]['total']) ? $result[0]['total'] : 0;
}

/**
 * Get all SECAO counts at once (more efficient)
 */
function getAllSecaosCounts($secaos){
    $secaoList = "'" . implode("','", $secaos) . "'";
    $query = "SELECT B.SECAO, COUNT(B.SECAO) as total FROM INFRA.DBO.TB_INVENTARIO_BE A 
                RIGHT JOIN (SELECT DISTINCT
                        A.cod_func,
                        A.nome_func,
                        A.E_MAIL AS Email_Func,
                        RAMAL,
                        RAMAL_INTERNO,
                        DDD_CEL_CORPORATIVO,
                        CELULAR_CORPORATIVO,
                        A.SECAO
                    FROM MESU..FUNCIONARIOS AS A
                    WHERE DT_TransDem IS NULL
                    ) B ON B.COD_FUNC=A.COD_FUNC
                    WHERE B.SECAO IN ($secaoList)
                    GROUP BY B.SECAO
                    ORDER BY B.SECAO";
    $result = $this->sqlDb->select($query);
    return $result;
}

/**
 * Simple output function for PHP 5.3 compatibility
 */
function outputData($data) {
    if (is_array($data)) {
        $output = "";
        foreach ($data as $key => $value) {
            $output .= $key . ":" . $value . "|";
        }
        echo rtrim($output, "|");
    } else {
        echo $data;
    }
}

?>

---------------


<?php
// File: secao_endpoint.php
// AJAX endpoint for getting SECAO data

require_once('path/to/your/EnhancedInventoryModel.php'); // Adjust path as needed

// Initialize model
$inventoryModel = new EnhancedInventoryModel();

// Set content type for plain text (PHP 5.3 compatible)
if (function_exists('http_response_code')) {
    http_response_code(200);
}

// Get action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'single_secao') {
    // Get single SECAO count
    $secao = isset($_GET['secao']) ? $_GET['secao'] : '';
    
    if ($secao !== '') {
        $count = $inventoryModel->getInventarioCountBySecao($secao);
        echo $count;
    } else {
        echo '0';
    }
    
} elseif ($action == 'all_secaos') {
    // Get all SECAOs data at once (more efficient)
    $secaos = array('008', '009', '012', '015', '018', '022', '028', '032', '042', '043', '047', 
                   '060', '066', '073', '078', '081', '092', '10', '100', '113', '12', '13', '14', 
                   '15', '16', '17', '18', '2', '23', '26', '27', '28', '30', '32', '33', '34', 
                   '35', '36', '40', '43', '45', '46', '47', '49', '54', '55', '6', '60', '67', 
                   '68', '7', '70', '73', '75', '76', '79', '8', '81', '898', '9', '910', '92');
    
    $results = $inventoryModel->getAllSecaosCounts($secaos);
    
    // Format output for easy parsing in JavaScript
    $output = "";
    foreach ($results as $row) {
        $secao = isset($row['SECAO']) ? $row['SECAO'] : (isset($row['secao']) ? $row['secao'] : '');
        $total = isset($row['total']) ? $row['total'] : 0;
        $output .= $secao . ":" . $total . "|";
    }
    
    // Remove trailing pipe
    echo rtrim($output, "|");
    
} else {
    echo 'Invalid action';
}

// Ensure output is sent
if (ob_get_level()) {
    ob_end_flush();
}
?>

--------------------

// Custom Charts for SISB Dashboard with SECAO Data
// File: assets/js/custom-charts.js

// Global variables for chart data
var secaoData = [];
var chartsInitialized = false;

// List of all SECAOs
var allSecaos = ['008', '009', '012', '015', '018', '022', '028', '032', '042', '043', '047', 
                '060', '066', '073', '078', '081', '092', '10', '100', '113', '12', '13', '14', 
                '15', '16', '17', '18', '2', '23', '26', '27', '28', '30', '32', '33', '34', 
                '35', '36', '40', '43', '45', '46', '47', '49', '54', '55', '6', '60', '67', 
                '68', '7', '70', '73', '75', '76', '79', '8', '81', '898', '9', '910', '92'];

// Function to fetch SECAO data
function fetchSecaoData(callback) {
    $.ajax({
        url: 'secao_endpoint.php?action=all_secaos', // Adjust path as needed
        method: 'GET',
        dataType: 'text',
        success: function(response) {
            console.log('Raw response:', response);
            
            // Parse the response (format: "secao1:count1|secao2:count2|...")
            var pairs = response.split('|');
            secaoData = [];
            
            for (var i = 0; i < pairs.length; i++) {
                if (pairs[i].trim() !== '') {
                    var parts = pairs[i].split(':');
                    if (parts.length === 2) {
                        var secao = parts[0].trim();
                        var count = parseInt(parts[1]) || 0;
                        
                        // Only add if count > 0 to avoid empty slices
                        if (count > 0) {
                            secaoData.push({
                                name: 'Seção ' + secao,
                                y: count,
                                secao: secao
                            });
                        }
                    }
                }
            }
            
            // Sort by count (descending)
            secaoData.sort(function(a, b) { return b.y - a.y; });
            
            console.log('Parsed SECAO data:', secaoData);
            
            if (callback && typeof callback === 'function') {
                callback();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching SECAO data:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            // Use fallback data if AJAX fails
            secaoData = [
                { name: 'Seção 012', y: 45 },
                { name: 'Seção 015', y: 32 },
                { name: 'Seção 008', y: 28 },
                { name: 'Seção 022', y: 15 },
                { name: 'Seção 032', y: 12 }
            ];
            
            if (callback && typeof callback === 'function') {
                callback();
            }
        }
    });
}

// Function to fetch single SECAO count (alternative method)
function fetchInventarioCount(secao, callback) {
    $.ajax({
        url: 'secao_endpoint.php?action=single_secao&secao=' + encodeURIComponent(secao),
        method: 'GET',
        dataType: 'text',
        success: function(count) {
            console.log('Inventário count for ' + secao + ':', count);
            if (callback && typeof callback === 'function') {
                callback(parseInt(count) || 0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching inventário count for ' + secao + ':', error);
            if (callback && typeof callback === 'function') {
                callback(0);
            }
        }
    });
}

$(document).ready(function() {
    
    // Common chart options
    var commonOptions = {
        credits: {
            enabled: false
        },
        exporting: {
            enabled: true,
            buttons: {
                contextButton: {
                    menuItems: ['downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG']
                }
            }
        }
    };

    // Pie Chart - SECAO Distribution (will be populated with real data)
    var pieChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'pie',
            backgroundColor: 'transparent'
        },
        title: {
            text: 'Distribuição por Seção'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Quantidade: <b>{point.y}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.y} ({point.percentage:.1f}%)',
                    style: {
                        fontSize: '11px'
                    }
                },
                showInLegend: true,
                colors: ['#AC193D', '#5DB2FF', '#53a93f', '#FF8F32', '#8C0095', '#03B3B2', '#cc324b', 
                        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98FB98',
                        '#F0E68C', '#FFA07A', '#20B2AA', '#87CEEB', '#DEB887', '#5F9EA0'] // More colors for more sections
            }
        },
        series: [{
            name: 'Seções',
            data: [] // Will be populated with real data
        }]
    });

    // Bar Chart - Monthly Sales (existing)
    var barChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'column',
            backgroundColor: 'transparent'
        },
        title: {
            text: 'Vendas Mensais'
        },
        xAxis: {
            categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Vendas (R$ mil)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px; ">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0; "><b>R$ {point.y:.1f}k</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    format: 'R$ {point.y}k'
                }
            }
        },
        colors: ['#AC193D', '#5DB2FF'],
        series: [{
            name: '2024',
            data: [150, 180, 220, 190, 240, 280, 320, 290, 250, 310, 340, 380]
        }, {
            name: '2023',
            data: [120, 140, 160, 150, 180, 210, 240, 220, 200, 230, 260, 290]
        }]
    });

    // Quarterly Performance Chart (existing)
    var quarterlyChartOptions = $.extend(true, {}, commonOptions, {
        chart: {
            type: 'line',
            backgroundColor: 'transparent'
        },
        title: {
            text: 'Performance Trimestral'
        },
        xAxis: {
            categories: ['Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023', 'Q1 2024', 'Q2 2024', 'Q3 2024', 'Q4 2024']
        },
        yAxis: {
            title: {
                text: 'Performance (%)'
            }
        },
        tooltip: {
            valueSuffix: '%'
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true,
                    format: '{point.y}%'
                },
                enableMouseTracking: true
            }
        },
        colors: ['#AC193D', '#5DB2FF', '#53a93f'],
        series: [{
            name: 'Meta',
            data: [85, 88, 90, 92, 85, 88, 90, 92]
        }, {
            name: 'Realizado',
            data: [82, 91, 87, 89, 88, 94, 92, 95]
        }, {
            name: 'Projeção',
            data: [80, 85, 88, 90, 85, 90, 94, 97]
        }]
    });

    // Initialize charts when page loads
    function initializeCharts() {
        try {
            // Check if containers exist before creating charts
            if ($('#pieChart').length && secaoData.length > 0) {
                // Update pie chart with real SECAO data
                pieChartOptions.series[0].data = secaoData;
                // Highlight the first (largest) slice
                if (secaoData.length > 0) {
                    secaoData[0].sliced = true;
                    secaoData[0].selected = true;
                }
                Highcharts.chart('pieChart', pieChartOptions);
            } else if ($('#pieChart').length) {
                // Show loading message
                $('#pieChart').html('<div style="text-align:center; padding:50px;">Carregando dados das seções...</div>');
            }
            
            if ($('#barChart').length) {
                Highcharts.chart('barChart', barChartOptions);
            }
            
            if ($('#quarterlyChart').length) {
                Highcharts.chart('quarterlyChart', quarterlyChartOptions);
            }
            
            console.log('Charts initialized successfully');
            chartsInitialized = true;
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    }

    // Load SECAO data and then initialize charts
    function loadDataAndInitializeCharts() {
        console.log('Loading SECAO data...');
        fetchSecaoData(function() {
            console.log('SECAO data loaded, initializing charts...');
            initializeCharts();
        });
    }

    // Start the process
    loadDataAndInitializeCharts();

    // Responsive handling
    $(window).resize(function() {
        setTimeout(function() {
            // Redraw charts on window resize
            if (window.Highcharts) {
                $.each(Highcharts.charts, function(i, chart) {
                    if (chart) {
                        chart.reflow();
                    }
                });
            }
        }, 100);
    });

    // Function to refresh SECAO data
    window.refreshSecaoData = function() {
        console.log('Refreshing SECAO data...');
        fetchSecaoData(function() {
            console.log('Data refreshed, updating pie chart...');
            updatePieChart(secaoData);
        });
    };

    // Function to update pie chart data
    window.updatePieChart = function(newData) {
        var chart = Highcharts.charts.find(function(chart) {
            return chart && chart.renderTo.id === 'pieChart';
        });
        
        if (chart) {
            chart.series[0].setData(newData || secaoData);
        }
    };

    // Function to update bar chart data (for future use with AJAX)
    window.updateBarChart = function(categories, series) {
        var chart = Highcharts.charts.find(function(chart) {
            return chart && chart.renderTo.id === 'barChart';
        });
        
        if (chart) {
            chart.xAxis[0].setCategories(categories);
            chart.series[0].setData(series[0].data);
            if (series[1]) {
                chart.series[1].setData(series[1].data);
            }
        }
    };

    // Function to refresh all charts
    window.refreshCharts = function() {
        loadDataAndInitializeCharts();
    };

});

// Utility function to generate random data for testing
function generateRandomData(count, min, max) {
    var data = [];
    for (var i = 0; i < count; i++) {
        data.push(Math.floor(Math.random() * (max - min + 1)) + min);
    }
    return data;
}

// Function to export all charts as images (optional feature)
function exportAllCharts() {
    if (window.Highcharts) {
        $.each(Highcharts.charts, function(i, chart) {
            if (chart) {
                chart.exportChart({
                    type: 'image/png',
                    filename: 'chart-' + (i + 1)
                });
            }
        });
    }
}
