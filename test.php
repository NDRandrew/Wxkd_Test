// Add this JavaScript to your existing script section or create a new script tag

$(document).ready(function() {
    var selectedEmployeeCode = null;
    
    // Helper function to parse array data from text response
    function parseArrayData(dataString) {
        var result = [];
        if (!dataString || dataString === '') {
            return result;
        }
        
        var rows = dataString.split('|');
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].trim() !== '') {
                var obj = {};
                var pairs = rows[i].split(';');
                for (var j = 0; j < pairs.length; j++) {
                    if (pairs[j].indexOf(':') > -1) {
                        var keyValue = pairs[j].split(':');
                        if (keyValue.length >= 2) {
                            var key = keyValue[0];
                            var value = keyValue.slice(1).join(':'); // Handle values with colons
                            obj[key] = value;
                        }
                    }
                }
                if (Object.keys(obj).length > 0) {
                    result.push(obj);
                }
            }
        }
        
        // Debug: log the parsed result
        console.log('Parsed employees data:', result);
        
        return result;
    }
    
    // Reset modal when opening
    $('#AtribuirMaterialModal').on('show.bs.modal', function() {
        resetModal();
    });
    
    // Search employees
    $('#btn_search_employee, #employee_search').on('click keypress', function(e) {
        if (e.type === 'click' || e.which === 13) {
            searchEmployees();
        }
    });
    
    // Show available materials
    $('#btn_show_available_materials').on('click', function() {
        loadAvailableMaterials();
    });
    
    // Filter available materials
    $('#material_search').on('keyup', function() {
        var filter = $(this).val().toLowerCase();
        $('#available_materials .available-material-item').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(filter) > -1);
        });
    });
    
    function resetModal() {
        selectedEmployeeCode = null;
        $('#employee_search').val('');
        $('#employee_results').hide();
        $('#employee_materials_section').hide();
        $('#available_materials_section').hide();
        $('#employee_list').empty();
        $('#current_materials').empty();
        $('#available_materials').empty();
        $('#material_search').val('');
    }
    
    function searchEmployees() {
        var search = $('#employee_search').val().trim();
        
        if (search.length < 2) {
            alert('Digite pelo menos 2 caracteres para buscar');
            return;
        }
        
        $('#employee_list').html('<tr><td colspan="4" class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> Buscando...</td></tr>');
        $('#employee_results').show();
        
        $.ajax({
            url: 'atribuir_material_ajax.php',
            method: 'POST',
            data: {
                action: 'search_employees',
                search: search
            },
            success: function(response) {
                console.log('Raw response:', response);
                var parts = response.split('|');
                console.log('Split parts:', parts);
                if (parts[0] === 'SUCCESS') {
                    // Rejoin all parts except the first one (SUCCESS) to get all employee data
                    var employeeDataString = parts.slice(1).join('|');
                    console.log('Employee data string:', employeeDataString);
                    var employeesData = parseArrayData(employeeDataString);
                    console.log('Final employees data:', employeesData);
                    displayEmployees(employeesData);
                } else {
                    $('#employee_list').html('<tr><td colspan="4">' + (parts[1] || 'Erro ao buscar funcionários') + '</td></tr>');
                }
            },
            error: function() {
                $('#employee_list').html('<tr><td colspan="4">Erro na requisição</td></tr>');
            }
        });
    }
    
    function displayEmployees(employees) {
        console.log('displayEmployees called with:', employees);
        var html = '';
        
        if (employees.length === 0) {
            html = '<tr><td colspan="4">Nenhum funcionário encontrado</td></tr>';
        } else {
            for (var i = 0; i < employees.length; i++) {
                var employee = employees[i];
                console.log('Processing employee:', employee);
                html += '<tr class="employee-row" data-cod-func="' + employee.cod_func + '">' +
                        '<td>' + employee.cod_func + '</td>' +
                        '<td>' + employee.nome_func + '</td>' +
                        '<td>' + (employee.SECAO || '') + '</td>' +
                        '<td><button class="btn btn-primary btn-xs btn-select-employee" data-cod-func="' + employee.cod_func + '">Selecionar</button></td>' +
                        '</tr>';
            }
        }
        
        console.log('Generated HTML:', html);
        $('#employee_list').html(html);
        
        // Add click handlers for employee selection
        $('.btn-select-employee, .employee-row').on('click', function() {
            var codFunc = $(this).data('cod-func') || $(this).closest('tr').data('cod-func');
            selectEmployee(codFunc);
        });
    }
    
    function selectEmployee(codFunc) {
        selectedEmployeeCode = codFunc;
        loadEmployeeMaterials(codFunc);
    }
    
    function loadEmployeeMaterials(codFunc) {
        $('#current_materials').html('<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> Carregando materiais...</div>');
        $('#employee_materials_section').show();
        
        $.ajax({
            url: 'atribuir_material_ajax.php',
            method: 'POST',
            data: {
                action: 'get_employee_materials',
                cod_func: codFunc
            },
            success: function(response) {
                var parts = response.split('|');
                if (parts[0] === 'SUCCESS') {
                    // Parse the complex response: MATERIALS:data||EMPLOYEE:data
                    var dataPart = parts.slice(1).join('|'); // Rejoin in case there were pipes in the data
                    var sections = dataPart.split('||');
                    
                    var materialsData = [];
                    var employeeData = [];
                    
                    for (var i = 0; i < sections.length; i++) {
                        if (sections[i].indexOf('MATERIALS:') === 0) {
                            var materialsString = sections[i].substring(10); // Remove 'MATERIALS:'
                            materialsData = parseArrayData(materialsString);
                        } else if (sections[i].indexOf('EMPLOYEE:') === 0) {
                            var employeeString = sections[i].substring(9); // Remove 'EMPLOYEE:'
                            employeeData = parseArrayData(employeeString);
                        }
                    }
                    
                    if (employeeData.length > 0) {
                        $('#selected_employee_name').text('Funcionário: ' + employeeData[0].nome_func + ' (' + employeeData[0].cod_func + ')');
                    }
                    displayCurrentMaterials(materialsData);
                } else {
                    $('#current_materials').html('<div class="alert alert-danger">' + (parts[1] || 'Erro ao carregar materiais') + '</div>');
                }
            },
            error: function() {
                $('#current_materials').html('<div class="alert alert-danger">Erro na requisição</div>');
            }
        });
    }
    
    function displayCurrentMaterials(materials) {
        var html = '';
        
        if (materials.length === 0) {
            html = '<div class="alert alert-info">Nenhum material em uso</div>';
        } else {
            for (var i = 0; i < materials.length; i++) {
                var material = materials[i];
                html += '<div class="material-item">' +
                        '<strong>' + material.tipo + '</strong><br>' +
                        '<span class="material-info">' + material.marca + ' - ' + material.modelo + '</span><br>' +
                        '<span class="material-info">Host: ' + (material.hostname || 'N/A') + '</span><br>' +
                        '<span class="material-info">Série: ' + (material.num_serie || 'N/A') + '</span>' +
                        '<div class="material-actions">' +
                        '<button class="btn btn-danger btn-xs btn-remove-material" data-id="' + material.id + '">' +
                        '<i class="fa fa-remove"></i> Remover' +
                        '</button>' +
                        '</div>' +
                        '</div>';
            }
        }
        
        $('#current_materials').html(html);
        
        // Add click handlers for material removal
        $('.btn-remove-material').on('click', function() {
            var materialId = $(this).data('id');
            removeMaterial(materialId);
        });
    }
    
    function loadAvailableMaterials() {
        $('#available_materials').html('<div class="loading-spinner"><i class="fa fa-spinner fa-spin"></i> Carregando materiais disponíveis...</div>');
        $('#available_materials_section').show();
        
        $.ajax({
            url: 'atribuir_material_ajax.php',
            method: 'POST',
            data: {
                action: 'get_available_materials'
            },
            success: function(response) {
                var parts = response.split('|');
                if (parts[0] === 'SUCCESS') {
                    // Rejoin all parts except the first one (SUCCESS) to get all material data
                    var materialDataString = parts.slice(1).join('|');
                    var materialsData = parseArrayData(materialDataString);
                    displayAvailableMaterials(materialsData);
                } else {
                    $('#available_materials').html('<div class="alert alert-danger">' + (parts[1] || 'Erro ao carregar materiais') + '</div>');
                }
            },
            error: function() {
                $('#available_materials').html('<div class="alert alert-danger">Erro na requisição</div>');
            }
        });
    }
    
    function displayAvailableMaterials(materials) {
        var html = '';
        
        if (materials.length === 0) {
            html = '<div class="alert alert-info">Nenhum material disponível</div>';
        } else {
            for (var i = 0; i < materials.length; i++) {
                var material = materials[i];
                html += '<div class="available-material-item" data-id="' + material.id + '">' +
                        '<strong>' + material.tipo + '</strong> - ' + material.marca + ' ' + material.modelo + '<br>' +
                        '<small>Host: ' + (material.hostname || 'N/A') + ' | Série: ' + (material.num_serie || 'N/A') + '</small>' +
                        '</div>';
            }
        }
        
        $('#available_materials').html(html);
        
        // Add click handlers for material assignment
        $('.available-material-item').on('click', function() {
            var materialId = $(this).data('id');
            assignMaterial(materialId);
        });
    }
    
    function assignMaterial(materialId) {
        if (!selectedEmployeeCode) {
            alert('Nenhum funcionário selecionado');
            return;
        }
        
        if (confirm('Confirma a atribuição deste material ao funcionário?')) {
            $.ajax({
                url: 'atribuir_material_ajax.php',
                method: 'POST',
                data: {
                    action: 'assign_material',
                    id_equip: materialId,
                    cod_func: selectedEmployeeCode
                },
                success: function(response) {
                    var parts = response.split('|');
                    if (parts[0] === 'SUCCESS') {
                        alert(parts[1] || 'Material atribuído com sucesso!');
                        // Reload the employee materials and available materials
                        loadEmployeeMaterials(selectedEmployeeCode);
                        loadAvailableMaterials();
                    } else {
                        alert('Erro: ' + (parts[1] || 'Erro desconhecido'));
                    }
                },
                error: function() {
                    alert('Erro na requisição');
                }
            });
        }
    }
    
    function removeMaterial(materialId) {
        if (!selectedEmployeeCode) {
            alert('Nenhum funcionário selecionado');
            return;
        }
        
        if (confirm('Confirma a remoção deste material do funcionário?')) {
            $.ajax({
                url: 'atribuir_material_ajax.php',
                method: 'POST',
                data: {
                    action: 'remove_material',
                    id_equip: materialId,
                    cod_func: selectedEmployeeCode
                },
                success: function(response) {
                    var parts = response.split('|');
                    if (parts[0] === 'SUCCESS') {
                        alert(parts[1] || 'Material removido com sucesso!');
                        // Reload the employee materials and available materials
                        loadEmployeeMaterials(selectedEmployeeCode);
                        if ($('#available_materials_section').is(':visible')) {
                            loadAvailableMaterials();
                        }
                    } else {
                        alert('Erro: ' + (parts[1] || 'Erro desconhecido'));
                    }
                },
                error: function() {
                    alert('Erro na requisição');
                }
            });
        }
    }
});