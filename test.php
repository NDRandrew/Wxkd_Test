<?php
class InventarioModel {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Get inventory count by secao
     * Returns an array with secao as key and count as value
     */
    public function getInventarioCountBySecao() {
        // Define all possible secao values
        $secoes = array(
            'NULL', '008', '009', '012', '015', '018', '022', '028', '032', '042',
            '043', '047', '060', '066', '073', '078', '081', '092', '10', '100',
            '113', '12', '13', '14', '15', '16', '17', '18', '2', '23',
            '26', '27', '28', '30', '32', '33', '34', '35', '36', '40',
            '43', '45', '46', '47', '49', '54', '55', '6', '60', '67',
            '68', '7', '70', '73', '75', '76', '79', '8', '81', '898',
            '9', '910', '92'
        );
        
        $result = array();
        
        // Initialize all secoes with 0 count
        foreach ($secoes as $secao) {
            $result[$secao] = 0;
        }
        
        // Build the query to count by secao
        // Assuming your table is named 'inventario' and column is 'secao'
        // Adjust table and column names according to your database structure
        $query = "SELECT secao, COUNT(*) as count_secao FROM inventario WHERE secao IS NOT NULL GROUP BY secao";
        
        // Handle NULL values separately
        $queryNull = "SELECT COUNT(*) as count_null FROM inventario WHERE secao IS NULL";
        
        // Execute main query
        $stmt = $this->db->query($query);
        if ($stmt) {
            while ($row = $stmt->fetch_assoc()) {
                $secao = $row['secao'];
                $count = (int)$row['count_secao'];
                
                // Only include if it's in our defined secoes list
                if (in_array($secao, $secoes)) {
                    $result[$secao] = $count;
                }
            }
        }
        
        // Execute NULL query
        $stmtNull = $this->db->query($queryNull);
        if ($stmtNull) {
            $rowNull = $stmtNull->fetch_assoc();
            if ($rowNull && isset($rowNull['count_null'])) {
                $result['NULL'] = (int)$rowNull['count_null'];
            }
        }
        
        // Remove secoes with 0 count (optional - uncomment if you want to exclude empty sections)
        // $result = array_filter($result, function($count) { return $count > 0; });
        
        return $result;
    }
    
    /**
     * Helper function to manually create JSON since json_encode is not available in PHP 5.3
     */
    public function arrayToJson($array) {
        $json = '{';
        $first = true;
        
        foreach ($array as $key => $value) {
            if (!$first) {
                $json .= ',';
            }
            
            $json .= '"' . addslashes($key) . '":' . (is_numeric($value) ? $value : '"' . addslashes($value) . '"');
            $first = false;
        }
        
        $json .= '}';
        return $json;
    }
}
?>



------------------------------


// Global variables to store chart data
var inventarioData = {};
var pieChart = null;

/**
 * Pie Chart Options Configuration
 * Customize these options according to your charting library
 */
var pieChartOptions = {
    responsive: true,
    plugins: {
        title: {
            display: true,
            text: 'Inventário por Seção'
        },
        legend: {
            position: 'right',
            labels: {
                boxWidth: 12,
                padding: 10
            }
        }
    },
    animation: {
        animateScale: true,
        animateRotate: true
    }
};

/**
 * Colors for pie chart segments
 */
var pieColors = [
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
    '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
    '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB',
    '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384',
    '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56',
    '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
    '#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0',
    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384',
    '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
    '#FF6384', '#C9CBCF', '#4BC0C0'
];

/**
 * Load inventory data via AJAX
 */
function loadInventarioData() {
    // Create XMLHttpRequest object (compatible with older browsers)
    var xhr;
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else {
        xhr = new ActiveXObject("Microsoft.XMLHTTP");
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    // Parse the JSON response
                    inventarioData = JSON.parse(xhr.responseText);
                    updatePieChart();
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                    alert('Erro ao carregar dados do inventário');
                }
            } else {
                console.error('AJAX request failed:', xhr.status);
                alert('Erro na requisição dos dados');
            }
        }
    };
    
    xhr.open('GET', 'ajax_inventario.php', true);
    xhr.send();
}

/**
 * Update pie chart with loaded data
 */
function updatePieChart() {
    var labels = [];
    var data = [];
    var colors = [];
    var colorIndex = 0;
    
    // Process the inventory data
    for (var secao in inventarioData) {
        if (inventarioData.hasOwnProperty(secao) && inventarioData[secao] > 0) {
            labels.push(secao === 'NULL' ? 'Sem Seção' : 'Seção ' + secao);
            data.push(inventarioData[secao]);
            colors.push(pieColors[colorIndex % pieColors.length]);
            colorIndex++;
        }
    }
    
    // Chart.js format (adjust if using different library)
    var chartData = {
        labels: labels,
        datasets: [{
            data: data,
            backgroundColor: colors,
            borderColor: '#fff',
            borderWidth: 2
        }]
    };
    
    // If chart already exists, destroy it first
    if (pieChart) {
        pieChart.destroy();
    }
    
    // Create new chart (assuming Chart.js - adjust for your library)
    var ctx = document.getElementById('pieChart').getContext('2d');
    pieChart = new Chart(ctx, {
        type: 'pie',
        data: chartData,
        options: pieChartOptions
    });
}

/**
 * Refresh chart data
 */
function refreshChart() {
    loadInventarioData();
}

/**
 * Get chart statistics
 */
function getChartStats() {
    var total = 0;
    var sectionsCount = 0;
    
    for (var secao in inventarioData) {
        if (inventarioData.hasOwnProperty(secao) && inventarioData[secao] > 0) {
            total += inventarioData[secao];
            sectionsCount++;
        }
    }
    
    return {
        total: total,
        sections: sectionsCount,
        data: inventarioData
    };
}

/**
 * Initialize chart when page loads
 */
document.addEventListener('DOMContentLoaded', function() {
    loadInventarioData();
});

// Alternative initialization for older browsers
if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', function() {
        loadInventarioData();
    });
} else {
    window.onload = function() {
        loadInventarioData();
    };
}

-------------------------


<?php
// Prevent direct access
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    // Allow for testing purposes, but you might want to uncomment this in production
    // exit('Direct access not allowed');
}

// Include your database connection file
// require_once 'database_connection.php';

// Include the model
require_once 'testy.php';

// Set content type for JSON response (compatible with PHP 5.3)
header('Content-Type: application/json; charset=utf-8');

try {
    // Initialize database connection
    // Replace this with your actual database connection
    // Example for MySQL:
    /*
    $host = 'localhost';
    $username = 'your_username';
    $password = 'your_password';
    $database = 'your_database';
    
    $connection = new mysqli($host, $username, $password, $database);
    
    if ($connection->connect_error) {
        throw new Exception('Database connection failed: ' . $connection->connect_error);
    }
    */
    
    // For demonstration purposes, create a mock connection
    // Replace this entire block with your actual database connection
    $connection = null; // Replace with your actual connection
    
    if (!$connection) {
        // Mock data for testing - remove this when you have real database connection
        $mockData = array(
            'NULL' => 5,
            '008' => 12,
            '009' => 8,
            '012' => 15,
            '015' => 22,
            '018' => 7,
            '022' => 9,
            '028' => 11,
            '032' => 18,
            '042' => 14,
            '043' => 6,
            '047' => 13,
            '060' => 20,
            '066' => 4,
            '073' => 16,
            '078' => 10,
            '081' => 8,
            '092' => 12,
            '10' => 25,
            '100' => 3
        );
        
        // Manual JSON creation for PHP 5.3
        echo '{';
        $first = true;
        foreach ($mockData as $key => $value) {
            if (!$first) echo ',';
            echo '"' . $key . '":' . $value;
            $first = false;
        }
        echo '}';
        exit;
    }
    
    // Create model instance
    $inventarioModel = new InventarioModel($connection);
    
    // Get inventory count by secao
    $result = $inventarioModel->getInventarioCountBySecao();
    
    // Return JSON response using our custom function
    echo $inventarioModel->arrayToJson($result);
    
} catch (Exception $e) {
    // Error handling
    $error = array('error' => 'Erro ao buscar dados: ' . $e->getMessage());
    
    // Manual JSON creation for error
    echo '{"error":"' . addslashes($e->getMessage()) . '"}';
    
    // Log error if needed
    error_log('Inventario AJAX Error: ' . $e->getMessage());
}

// Close database connection if it exists
if (isset($connection) && $connection) {
    $connection->close();
}
?>
