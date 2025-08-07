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
        
        // Execute query (adjust based on your database connection method)
        $result = mysql_query($query); // or mysqli_query() or your preferred method
        
        if ($result) {
            while ($row = mysql_fetch_assoc($result)) {
                $secaoValue = $row['secao'];
                
                // Handle NULL values
                if ($secaoValue === null || $secaoValue === '') {
                    $secaoValue = 'NULL';
                }
                
                // Only include if it's in our predefined list
                if (array_key_exists($secaoValue, $counts)) {
                    $counts[$secaoValue] = (int)$row['count'];
                }
            }
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
