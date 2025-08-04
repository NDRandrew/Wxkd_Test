<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard Charts</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        .chart-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .dashboard-widget {
            background: #fff;
            border: 1px solid #e6e6e6;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .btn-modal {
            margin: 10px;
            padding: 15px 30px;
            font-size: 16px;
        }
        .buttons-container {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="page-body">                                  
        <div class="col-lg-12 col-sm-12 col-xs-12">
            <!-- Charts Row -->
            <div class="row">
                <!-- Pie Chart -->
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="dashboard-widget">
                        <div class="chart-title">
                            <i class="fa fa-pie-chart"></i> Product Status Distribution
                        </div>
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Bar Chart -->
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <div class="dashboard-widget">
                        <div class="chart-title">
                            <i class="fa fa-bar-chart"></i> Average Products per Person
                        </div>
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Buttons Row -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="dashboard-widget">
                        <div class="buttons-container">
                            <button type="button" class="btn btn-primary btn-modal" data-toggle="modal" data-target="#reportModal">
                                <i class="fa fa-file-text-o"></i> Generate Report
                            </button>
                            <button type="button" class="btn btn-success btn-modal" data-toggle="modal" data-target="#settingsModal">
                                <i class="fa fa-cog"></i> System Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row return-ajax" id="indexDiv"></div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="reportModalLabel">
                        <i class="fa fa-file-text-o"></i> Generate Report
                    </h4>
                </div>
                <div class="modal-body">
                    <form role="form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reportType">Report Type</label>
                                    <select class="form-control" id="reportType">
                                        <option value="status">Product Status Report</option>
                                        <option value="usage">Usage Statistics</option>
                                        <option value="inventory">Inventory Report</option>
                                        <option value="maintenance">Maintenance Report</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reportFormat">Format</label>
                                    <select class="form-control" id="reportFormat">
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dateFrom">From Date</label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dateTo">To Date</label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reportNotes">Additional Notes</label>
                            <textarea class="form-control" id="reportNotes" rows="3" placeholder="Enter any additional notes for the report..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateReport()">
                        <i class="fa fa-download"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="settingsModalLabel">
                        <i class="fa fa-cog"></i> System Settings
                    </h4>
                </div>
                <div class="modal-body">
                    <form role="form">
                        <div class="form-group">
                            <label>Dashboard Refresh Rate</label>
                            <select class="form-control">
                                <option value="30">30 seconds</option>
                                <option value="60" selected>1 minute</option>
                                <option value="300">5 minutes</option>
                                <option value="600">10 minutes</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Default Chart Type</label>
                            <select class="form-control">
                                <option value="pie">Pie Chart</option>
                                <option value="bar" selected>Bar Chart</option>
                                <option value="line">Line Chart</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" checked> Enable automatic notifications
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" checked> Show detailed tooltips
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox"> Enable dark mode
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="maxRecords">Maximum records to display</label>
                            <input type="number" class="form-control" id="maxRecords" value="100" min="10" max="1000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="saveSettings()">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

    <script>
        $(document).ready(function() {
            initializeCharts();
        });

        function initializeCharts() {
            // Pie Chart - Product Status
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['Available', 'Unavailable', 'Broken', 'Fix'],
                    datasets: [{
                        data: [45, 20, 15, 20],
                        backgroundColor: [
                            '#28a745', // Green for Available
                            '#ffc107', // Yellow for Unavailable
                            '#dc3545', // Red for Broken
                            '#17a2b8'  // Blue for Fix
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Bar Chart - Average Products per Person
            const barCtx = document.getElementById('barChart').getContext('2d');
            const barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['IT Support', 'Sales', 'Marketing', 'HR', 'Finance', 'Operations'],
                    datasets: [{
                        label: 'Average Products per Person',
                        data: [3.2, 2.8, 1.9, 2.1, 2.5, 3.8],
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107',
                            '#dc3545',
                            '#6f42c1',
                            '#17a2b8'
                        ],
                        borderColor: [
                            '#0056b3',
                            '#1e7e34',
                            '#e0a800',
                            '#c82333',
                            '#5a32a3',
                            '#138496'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.parsed.y} products`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Products'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Department'
                            }
                        }
                    }
                }
            });
        }

        function generateReport() {
            const reportType = $('#reportType').val();
            const reportFormat = $('#reportFormat').val();
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            const notes = $('#reportNotes').val();

            // Simulate report generation
            alert(`Generating ${reportType} report in ${reportFormat} format...\nDate range: ${dateFrom} to ${dateTo}\nNotes: ${notes}`);
            
            // Here you would typically make an AJAX call to your backend
            // $.ajax({
            //     url: 'generate_report.php',
            //     method: 'POST',
            //     data: {
            //         type: reportType,
            //         format: reportFormat,
            //         from: dateFrom,
            //         to: dateTo,
            //         notes: notes
            //     },
            //     success: function(response) {
            //         // Handle successful report generation
            //     }
            // });

            $('#reportModal').modal('hide');
        }

        function saveSettings() {
            // Simulate saving settings
            alert('Settings saved successfully!');
            
            // Here you would typically make an AJAX call to save the settings
            // $.ajax({
            //     url: 'save_settings.php',
            //     method: 'POST',
            //     data: {
            //         // Get form values and send to backend
            //     },
            //     success: function(response) {
            //         // Handle successful save
            //     }
            // });

            $('#settingsModal').modal('hide');
        }
    </script>
</body>
</html>