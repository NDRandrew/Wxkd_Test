<?php
// Model file - enhanced getInventarioCountBySecao function
class InventarioModel {
    
    public function getInventarioCountBySecao() {
        // Define all SECAO values
        $secaoList = array(
            'NULL', '008', '009', '012', '015', '018', '022', '028', '032', '042',
            '043', '047', '060', '066', '073', '078', '081', '092', '10', '100',
            '113', '12', '13', '14', '15', '16', '17', '18', '2', '23',
            '26', '27', '28', '30', '32', '33', '34', '35', '36', '40',
            '43', '45', '46', '47', '49', '54', '55', '6', '60', '67',
            '68', '7', '70', '73', '75', '76', '79', '8', '81', '898',
            '9', '910', '92'
        );
        
        $counts = array();
        
        // Initialize all sections with 0 count
        foreach ($secaoList as $secao) {
            $counts[$secao] = 0;
        }
        
        // Your database query here - adjust table and column names as needed
        $query = "SELECT secao, COUNT(*) as count FROM inventario_table GROUP BY secao";
        
        // Option 1: Using mysqli (available since PHP 5.0)
        if (function_exists('mysqli_query')) {
            // Assuming you have a mysqli connection $mysqli_connection
            $result = mysqli_query($mysqli_connection, $query);
            
            if ($result) {
                while ($row = mysqli_fetch_row($result)) {
                    $secaoValue = $row[0]; // First column (secao)
                    $countValue = $row[1]; // Second column (count)
                    
                    // Handle NULL values
                    if ($secaoValue === null || $secaoValue === '') {
                        $secaoValue = 'NULL';
                    }
                    
                    // Only include if it's in our predefined list
                    if (array_key_exists($secaoValue, $counts)) {
                        $counts[$secaoValue] = (int)$countValue;
                    }
                }
                mysqli_free_result($result);
            }
        }
        // Option 2: Using basic mysql_query with mysql_fetch_row (most basic approach)
        else if (function_exists('mysql_query')) {
            $result = mysql_query($query);
            
            if ($result) {
                while ($row = mysql_fetch_row($result)) {
                    $secaoValue = $row[0]; // First column (secao)
                    $countValue = $row[1]; // Second column (count)
                    
                    // Handle NULL values
                    if ($secaoValue === null || $secaoValue === '') {
                        $secaoValue = 'NULL';
                    }
                    
                    // Only include if it's in our predefined list
                    if (array_key_exists($secaoValue, $counts)) {
                        $counts[$secaoValue] = (int)$countValue;
                    }
                }
                mysql_free_result($result);
            }
        }
        // Option 3: Manual approach if you know what functions ARE available
        else {
            // You'll need to tell me which MySQL functions work in your environment
            // Common alternatives might be: PDO, or custom database wrapper functions
        }
        
        return $counts;
    }
    
    // Helper function to output data for AJAX (PHP 5.3 compatible)
    public function getInventarioCountForChart() {
        $counts = $this->getInventarioCountBySecao();
        
        // Manual JSON encoding for PHP 5.3
        $jsonOutput = '{';
        $first = true;
        
        foreach ($counts as $secao => $count) {
            if (!$first) {
                $jsonOutput .= ',';
            }
            $jsonOutput .= '"' . $secao . '":' . $count;
            $first = false;
        }
        
        $jsonOutput .= '}';
        
        return $jsonOutput;
    }
}
?>
---------------
<?php
// get_secao_data.php - AJAX endpoint for retrieving SECAO data
// PHP 5.3 compatible

// Include your model file here
// require_once 'path/to/your/model.php';

// Since we can't use header() changes, we'll output plain text that JS can parse
// The calling JS will need to handle this as text, not JSON

$model = new InventarioModel();
$data = $model->getInventarioCountForChart();

// Output the data directly
echo $data;

// No exit() or die() needed, but you can add them if preferred
?>
---------------

// Enhanced pieChartOptions with AJAX data loading
var pieChartOptions = {
    chart: {
        type: 'pie',
        height: 400
    },
    title: {
        text: 'Inventário por Seção'
    },
    plotOptions: {
        pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
                enabled: true,
                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
            },
            showInLegend: true
        }
    },
    series: [{
        name: 'Seções',
        colorByPoint: true,
        data: [] // Will be populated by AJAX
    }]
};

// Function to load data via AJAX and update chart
function loadSecaoData() {
    // Create XMLHttpRequest for PHP 5.3 compatibility
    var xhr = new XMLHttpRequest();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                // Parse the response manually since we can't rely on JSON response headers
                var responseText = xhr.responseText.trim();
                var data;
                
                // Try to parse as JSON
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    // If JSON parsing fails, try to evaluate it (be careful with this in production)
                    data = eval('(' + responseText + ')');
                }
                
                // Convert data to chart format
                var chartData = [];
                var colors = generateColors(Object.keys(data).length);
                var colorIndex = 0;
                
                for (var secao in data) {
                    if (data.hasOwnProperty(secao) && data[secao] > 0) {
                        chartData.push({
                            name: 'SECAO ' + secao,
                            y: data[secao],
                            color: colors[colorIndex % colors.length]
                        });
                        colorIndex++;
                    }
                }
                
                // Update chart data
                pieChartOptions.series[0].data = chartData;
                
                // If using Highcharts, refresh the chart
                if (typeof Highcharts !== 'undefined' && window.secaoChart) {
                    window.secaoChart.series[0].setData(chartData);
                } else {
                    // Create new chart if it doesn't exist
                    createChart();
                }
                
            } catch (e) {
                console.error('Error processing SECAO data:', e);
                console.log('Response received:', xhr.responseText);
            }
        }
    };
    
    xhr.open('GET', 'get_secao_data.php', true);
    xhr.send();
}

// Function to generate colors for the pie chart
function generateColors(count) {
    var colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
        '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43',
        '#10AC84', '#EE5A24', '#0984E3', '#6C5CE7', '#A29BFE',
        '#FD79A8', '#E84393', '#2D3436', '#636E72', '#B2BEC3',
        '#DDA0DD', '#98FB98', '#F0E68C', '#FFB6C1', '#87CEEB',
        '#DEB887', '#CD853F', '#FFA07A', '#20B2AA', '#87CEFA',
        '#778899', '#B0C4DE', '#FFFFE0', '#00FF00', '#32CD32',
        '#FAF0E6', '#FF00FF', '#800000', '#66CDAA', '#0000CD',
        '#BA55D3', '#9370DB', '#3CB371', '#7B68EE', '#00FA9A',
        '#48D1CC', '#C71585', '#191970', '#F5FFFA', '#FFE4E1',
        '#006400', '#8B0000', '#008B8B', '#9ACD32', '#FF4500',
        '#DA70D6', '#EEE8AA', '#98FB98', '#F0E68C', '#DDA0DD'
    ];
    
    // If we need more colors than predefined, generate them
    while (colors.length < count) {
        colors.push('#' + Math.floor(Math.random()*16777215).toString(16));
    }
    
    return colors;
}

// Function to create the chart (assuming Highcharts)
function createChart() {
    if (typeof Highcharts !== 'undefined') {
        window.secaoChart = Highcharts.chart('container', pieChartOptions);
    } else {
        console.error('Highcharts library not loaded');
    }
}

// Function to initialize the chart system
function initSecaoChart() {
    // Load data when page is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadSecaoData();
        });
    } else {
        loadSecaoData();
    }
}

// Auto-initialize
initSecaoChart();

// Function to refresh chart data (call this when needed)
function refreshSecaoChart() {
    loadSecaoData();
}


-----------------


// Ultra-safe JavaScript for PHP 5.3 compatibility
// Supports multiple data formats in case JSON fails

var pieChartOptions = {
    chart: {
        type: 'pie',
        height: 450
    },
    title: {
        text: 'Inventário por Seção'
    },
    tooltip: {
        pointFormat: '<b>{point.name}</b><br/>Quantidade: <b>{point.y}</b><br/>Percentual: <b>{point.percentage:.1f}%</b>'
    },
    plotOptions: {
        pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            dataLabels: {
                enabled: true,
                format: '<b>{point.name}</b><br/>{point.percentage:.1f}%'
            },
            showInLegend: true
        }
    },
    series: [{
        name: 'Seções',
        colorByPoint: true,
        data: []
    }]
};

// Function to parse data from multiple possible formats
function parseSecaoData(responseText) {
    var data = {};
    
    try {
        // Method 1: Try JSON parsing
        data = JSON.parse(responseText);
        console.log('Data parsed as JSON successfully');
        return data;
    } catch (e1) {
        console.log('JSON parsing failed, trying alternative methods');
        
        try {
            // Method 2: Try eval (use with caution)
            data = eval('(' + responseText + ')');
            console.log('Data parsed with eval successfully');
            return data;
        } catch (e2) {
            console.log('Eval parsing failed, trying text format');
            
            try {
                // Method 3: Parse simple text format (secao:count|secao:count|...)
                var pairs = responseText.split('|');
                for (var i = 0; i < pairs.length; i++) {
                    if (pairs[i].length > 0) {
                        var parts = pairs[i].split(':');
                        if (parts.length === 2) {
                            data[parts[0]] = parseInt(parts[1]) || 0;
                        }
                    }
                }
                console.log('Data parsed from text format successfully');
                return data;
            } catch (e3) {
                console.error('All parsing methods failed:', e1, e2, e3);
                throw new Error('Unable to parse response data');
            }
        }
    }
}

// Function to load data with multiple fallback methods
function loadSecaoData() {
    showLoading(true);
    hideError();
    
    // Method 1: Try XMLHttpRequest
    if (typeof XMLHttpRequest !== 'undefined') {
        loadWithXHR();
    } else {
        // Method 2: Fallback to ActiveX for very old browsers
        loadWithActiveX();
    }
}

// Load data using XMLHttpRequest
function loadWithXHR() {
    var xhr = new XMLHttpRequest();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            showLoading(false);
            
            if (xhr.status === 200) {
                processResponse(xhr.responseText);
            } else {
                showError('Erro ao carregar dados. Status: ' + xhr.status);
                
                // Fallback: try with different endpoint format
                if (xhr.status === 404) {
                    tryAlternativeEndpoints();
                }
            }
        }
    };
    
    xhr.open('GET', 'get_secao_data.php?format=json&t=' + new Date().getTime(), true);
    xhr.send();
}

// Fallback for very old browsers
function loadWithActiveX() {
    try {
        var xhr = new ActiveXObject('Microsoft.XMLHTTP');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                showLoading(false);
                
                if (xhr.status === 200) {
                    processResponse(xhr.responseText);
                } else {
                    showError('Erro ao carregar dados (ActiveX). Status: ' + xhr.status);
                }
            }
        };
        
        xhr.open('GET', 'get_secao_data.php?format=text&t=' + new Date().getTime(), true);
        xhr.send();
    } catch (e) {
        showError('Navegador não suporta requisições AJAX: ' + e.message);
    }
}

// Try alternative endpoints if main one fails
function tryAlternativeEndpoints() {
    var endpoints = [
        'get_secao_data.php?format=text',
        'inventario_data.php',
        'secao_count.php'
    ];
    
    for (var i = 0; i < endpoints.length; i++) {
        tryEndpoint(endpoints[i]);
    }
}

function tryEndpoint(url) {
    var xhr = new XMLHttpRequest();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            processResponse(xhr.responseText);
        }
    };
    
    xhr.open('GET', url + '?t=' + new Date().getTime(), true);
    xhr.send();
}

// Process the response data
function processResponse(responseText) {
    try {
        var data = parseSecaoData(responseText.trim());
        
        // Convert to chart format
        var chartData = [];
        var colors = generateColors(Object.keys(data).length);
        var colorIndex = 0;
        var totalItems = 0;
        
        for (var secao in data) {
            if (data.hasOwnProperty(secao) && data[secao] > 0) {
                var displayName = secao === 'NULL' ? 'Sem Seção' : 'Seção ' + secao;
                
                chartData.push({
                    name: displayName,
                    y: data[secao],
                    color: colors[colorIndex % colors.length]
                });
                totalItems += data[secao];
                colorIndex++;
            }
        }
        
        if (chartData.length === 0) {
            showError('Nenhum dado encontrado para exibir no gráfico.');
            return;
        }
        
        // Update chart
        updateChart(chartData, totalItems);
        
    } catch (e) {
        console.error('Error processing response:', e);
        showError('Erro ao processar dados: ' + e.message);
        
        // Show raw response for debugging
        console.log('Raw response:', responseText);
    }
}

// Update or create chart
function updateChart(chartData, totalItems) {
    pieChartOptions.subtitle = {
        text: 'Total de itens: ' + totalItems
    };
    
    pieChartOptions.series[0].data = chartData;
    
    if (window.secaoChart && typeof window.secaoChart.series !== 'undefined') {
        // Update existing chart
        window.secaoChart.series[0].setData(chartData);
        if (window.secaoChart.setSubtitle) {
            window.secaoChart.setSubtitle({text: 'Total de itens: ' + totalItems});
        }
    } else {
        // Create new chart
        createChart();
    }
}

// Generate colors for chart
function generateColors(count) {
    var baseColors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
        '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43',
        '#10AC84', '#EE5A24', '#0984E3', '#6C5CE7', '#A29BFE'
    ];
    
    var colors = [];
    for (var i = 0; i < count; i++) {
        if (i < baseColors.length) {
            colors.push(baseColors[i]);
        } else {
            // Generate random colors for extra items
            var randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
            colors.push(randomColor);
        }
    }
    
    return colors;
}

// Create chart
function createChart() {
    if (typeof Highcharts !== 'undefined') {
        window.secaoChart = Highcharts.chart('chart-container', pieChartOptions);
    } else {
        showError('Biblioteca Highcharts não foi carregada');
    }
}

// Utility functions
function showLoading(show) {
    var el = document.getElementById('loading');
    if (el) el.style.display = show ? 'block' : 'none';
}

function showError(message) {
    var el = document.getElementById('error-message');
    if (el) {
        el.textContent = message;
        el.style.display = 'block';
    }
}

function hideError() {
    var el = document.getElementById('error-message');
    if (el) el.style.display = 'none';
}

function refreshSecaoChart() {
    loadSecaoData();
}

// Initialize when page loads
function initChart() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadSecaoData);
    } else {
        loadSecaoData();
    }
}

// Auto-initialize
initChart();
        -------------------------



        <?php
// Ultra-safe PHP 5.3 compatible model
class InventarioModel {
    
    public function getInventarioCountBySecao() {
        // Define all SECAO values
        $secaoList = array(
            'NULL', '008', '009', '012', '015', '018', '022', '028', '032', '042',
            '043', '047', '060', '066', '073', '078', '081', '092', '10', '100',
            '113', '12', '13', '14', '15', '16', '17', '18', '2', '23',
            '26', '27', '28', '30', '32', '33', '34', '35', '36', '40',
            '43', '45', '46', '47', '49', '54', '55', '6', '60', '67',
            '68', '7', '70', '73', '75', '76', '79', '8', '81', '898',
            '9', '910', '92'
        );
        
        $counts = array();
        
        // Initialize all sections with 0 count
        foreach ($secaoList as $secao) {
            $counts[$secao] = 0;
        }
        
        // APPROACH 1: If you have ANY working MySQL function, tell me which one
        // For now, I'll provide the safest possible approach
        
        // APPROACH 2: Manual counting (safest but slower)
        // Count each secao individually with simple queries
        foreach ($secaoList as $secao) {
            $secaoForQuery = ($secao === 'NULL') ? 'IS NULL' : "= '" . $secao . "'";
            $query = "SELECT COUNT(*) FROM inventario_table WHERE secao " . $secaoForQuery;
            
            // Try different database functions until one works
            $result = false;
            $count = 0;
            
            // Try mysqli first (most likely to work in PHP 5.3)
            if (function_exists('mysqli_query') && isset($mysqli_connection)) {
                $result = mysqli_query($mysqli_connection, $query);
                if ($result) {
                    $row = mysqli_fetch_row($result);
                    $count = $row[0];
                    mysqli_free_result($result);
                }
            }
            // Try basic mysql_query with mysql_fetch_row
            else if (function_exists('mysql_query')) {
                $result = mysql_query($query);
                if ($result) {
                    $row = mysql_fetch_row($result);
                    $count = $row[0];
                    mysql_free_result($result);
                }
            }
            
            $counts[$secao] = (int)$count;
        }
        
        return $counts;
    }
    
    // Ultra-safe JSON output for PHP 5.3
    public function getInventarioCountForChart() {
        $counts = $this->getInventarioCountBySecao();
        
        // Manual JSON encoding - absolutely no dependencies
        $output = '{';
        $items = array();
        
        foreach ($counts as $secao => $count) {
            // Escape the key safely
            $key = str_replace('"', '\\"', $secao);
            $items[] = '"' . $key . '":' . (int)$count;
        }
        
        $output .= implode(',', $items);
        $output .= '}';
        
        return $output;
    }
    
    // Alternative: Simple text output if JSON parsing fails in JavaScript
    public function getInventarioCountAsText() {
        $counts = $this->getInventarioCountBySecao();
        $output = '';
        
        foreach ($counts as $secao => $count) {
            $output .= $secao . ':' . $count . '|';
        }
        
        return rtrim($output, '|'); // Remove last pipe
    }
}

// DEBUGGING HELPER: Check what MySQL functions are available
function checkAvailableMySQLFunctions() {
    $functions = array(
        'mysql_query', 'mysql_fetch_row', 'mysql_fetch_array', 'mysql_fetch_assoc',
        'mysql_result', 'mysql_num_rows', 'mysql_free_result', 'mysql_connect',
        'mysqli_query', 'mysqli_fetch_row', 'mysqli_fetch_array', 'mysqli_fetch_assoc',
        'mysqli_num_rows', 'mysqli_free_result', 'mysqli_connect'
    );
    
    $available = array();
    foreach ($functions as $func) {
        if (function_exists($func)) {
            $available[] = $func;
        }
    }
    
    return $available;
}

// Uncomment this line to see what functions are available in your environment:
// echo "Available MySQL functions: " . implode(', ', checkAvailableMySQLFunctions());
?>
