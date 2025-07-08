<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Buttons Example</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .filter-btn {
            margin-right: 8px;
            margin-bottom: 5px;
            border: 2px solid #ddd;
            background-color: #f8f9fa;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active {
            border-color: #28a745;
            background-color: #28a745;
            color: white;
        }
        
        .filter-btn:hover {
            border-color: #28a745;
            color: #28a745;
        }
        
        .filter-btn.active:hover {
            background-color: #218838;
            border-color: #218838;
            color: white;
        }
        
        .status-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 3px;
            text-align: center;
            line-height: 20px;
            font-size: 8px;
            font-weight: bold;
            color: white;
            border-radius: 3px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <div style="display:flex;">
            <div class="col-md-8">
                <div class="align-items-center gap-2">
                    <div class="dataTables_filter" style="position:relative;margin-top:10px;">
                        <label>
                            <input style="height:100px; width:250px;" type="search" class="form-control input-sm" id="searchInput" placeholder aria-controls="simpledatatable" >
                        </label>
                    </div>
                    
                    <!-- Filter Buttons Section -->
                    <div style="margin-top: 15px; margin-bottom: 10px;">
                        <label class="me-2 text-sm">Filtros:</label>
                        <button type="button" class="btn btn-sm filter-btn" id="filterAV" onclick="toggleFilter('AVANCADO', this)">
                            <span class="status-indicator" style="background-color: green;">AV</span> Avançado
                        </button>
                        <button type="button" class="btn btn-sm filter-btn" id="filterOP" onclick="toggleFilter('ORGAO_PAGADOR', this)">
                            <span class="status-indicator" style="background-color: green;">OP</span> Órgão Pagador
                        </button>
                        <button type="button" class="btn btn-sm filter-btn" id="filterPR" onclick="toggleFilter('PRESENCA', this)">
                            <span class="status-indicator" style="background-color: green;">PR</span> Presença
                        </button>
                        <button type="button" class="btn btn-sm filter-btn" id="filterUN" onclick="toggleFilter('UNIDADE_NEGOCIO', this)">
                            <span class="status-indicator" style="background-color: green;">UN</span> Unidade Negócio
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters" onclick="clearAllFilters()" style="margin-left: 10px;">
                            Limpar Filtros
                        </button>
                    </div>
                    
                    <button style="position:relative; left:20px;" type="button" class="btn btn-success btn-sm" onclick="exportAllCSV()" style="position:relative; margin-top:10px;margin-bottom:10px;">
                        Exportar CSV
                    </button>
                    <button style="position:relative; left:20px;" type="button" class="btn btn-info btn-sm" onclick="exportSelectedTXT()" id="exportTxtBtn" disabled style="position:relative; margin-top:10px;margin-bottom:10px;">
                        Exportar TXT (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>

            <div class="col-md-4" style="position:relative; left:25%;" >
                <div class="d-flex justify-content-end align-items-center" >
                    <label for="itemsPerPage" class="me-2 text-sm" ></label>
                    <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto; cursor:pointer;">
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                    </select>
                    <span class="ms-2 text-sm"></span>
                </div>
            </div>
        </div>
        
        <!-- Example table to demonstrate the filtering -->
        <table class="table table-striped" id="dataTable" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>João Silva</td>
                    <td>
                        <div class="status-container">
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="AVANCADO" data-status="active">AV</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;" data-field="ORGAO_PAGADOR" data-status="inactive">OP</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="PRESENCA" data-status="active">PR</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;" data-field="UNIDADE_NEGOCIO" data-status="inactive">UN</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Maria Santos</td>
                    <td>
                        <div class="status-container">
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;" data-field="AVANCADO" data-status="inactive">AV</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="ORGAO_PAGADOR" data-status="active">OP</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="PRESENCA" data-status="active">PR</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="UNIDADE_NEGOCIO" data-status="active">UN</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Pedro Oliveira</td>
                    <td>
                        <div class="status-container">
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="AVANCADO" data-status="active">AV</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="ORGAO_PAGADOR" data-status="active">OP</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;" data-field="PRESENCA" data-status="inactive">PR</div>
                            <div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:green;border-radius:4px;" data-field="UNIDADE_NEGOCIO" data-status="active">UN</div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        // Global object to track active filters
        var activeFilters = {};
        
        function toggleFilter(fieldName, buttonElement) {
            // Toggle the filter state
            if (activeFilters[fieldName]) {
                delete activeFilters[fieldName];
                buttonElement.classList.remove('active');
            } else {
                activeFilters[fieldName] = true;
                buttonElement.classList.add('active');
            }
            
            // Apply the filtering
            applyFilters();
        }
        
        function clearAllFilters() {
            // Clear all active filters
            activeFilters = {};
            
            // Remove active class from all filter buttons
            var filterButtons = document.querySelectorAll('.filter-btn');
            for (var i = 0; i < filterButtons.length; i++) {
                filterButtons[i].classList.remove('active');
            }
            
            // Show all rows
            applyFilters();
        }
        
        function applyFilters() {
            var table = document.getElementById('dataTable');
            var rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            // If no filters are active, show all rows
            if (Object.keys(activeFilters).length === 0) {
                for (var i = 0; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
                return;
            }
            
            // Check each row
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var shouldShow = true;
                
                // Check each active filter
                for (var fieldName in activeFilters) {
                    var statusDiv = row.querySelector('[data-field="' + fieldName + '"]');
                    
                    if (statusDiv) {
                        var isActive = statusDiv.style.backgroundColor === 'green' || 
                                      statusDiv.getAttribute('data-status') === 'active';
                        
                        if (!isActive) {
                            shouldShow = false;
                            break;
                        }
                    } else {
                        // If the field doesn't exist, don't show the row when this filter is active
                        shouldShow = false;
                        break;
                    }
                }
                
                // Show or hide the row
                row.style.display = shouldShow ? '' : 'none';
            }
        }
        
        // Placeholder functions for existing functionality
        function exportAllCSV() {
            alert('Export CSV function would be called here');
        }
        
        function exportSelectedTXT() {
            alert('Export TXT function would be called here');
        }
    </script>
</body>
</html>