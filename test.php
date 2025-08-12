<?php
// Add these methods to your EnhancedInventoryModel class in Testy (3).txt

/**
 * Get materials currently in use by a specific employee
 */
function getMaterialsInUseByEmployee($cod_func){
    $query = "SELECT 
                I.*,
                F.nome_func,
                F.E_MAIL as email_func
              FROM INFRA.DBO.TB_INVENTARIO_BE I
              LEFT JOIN MESU.DBO.FUNCIONARIOS F ON I.cod_func = F.COD_FUNC
              WHERE I.cod_func = $cod_func AND I.sts_equip = 'EM USO'
              ORDER BY I.tipo, I.marca, I.modelo";
    
    $dados = $this->sqlDb->select($query);
    return $dados ? $dados : array();
}

/**
 * Get all available materials that can be assigned
 */
function getAvailableMaterials(){
    $query = "SELECT 
                I.*
              FROM INFRA.DBO.TB_INVENTARIO_BE I
              WHERE I.sts_equip = 'DISPONIVEL' 
              AND I.tipo IN ('NOTEBOOK', 'DESKTOP', 'DISPOSITIVOS', 'KIT TECLADO/MOUSE')
              ORDER BY I.tipo, I.marca, I.modelo";
    
    $dados = $this->sqlDb->select($query);
    return $dados ? $dados : array();
}

/**
 * Assign material to employee
 */
function assignMaterialToEmployee($id_equip, $cod_func){
    // First get the equipment current state for transaction log
    $equipBefore = $this->selectEquip($id_equip);
    
    if(!$equipBefore || empty($equipBefore)){
        return false;
    }
    
    // Update equipment status and assign to employee
    $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE 
              SET sts_equip = 'EM USO', cod_func = $cod_func 
              WHERE id = $id_equip AND sts_equip = 'DISPONIVEL'";
    
    $result = $this->sqlDb->update($query);
    
    if($result){
        // Get equipment state after update for transaction log
        $equipAfter = $this->selectEquip($id_equip);
        
        // Log the transaction
        $this->fazerTransicao($equipBefore, $equipAfter);
        
        return true;
    }
    
    return false;
}

/**
 * Remove material from employee (set back to available)
 */
function removeMaterialFromEmployee($id_equip, $cod_func){
    // First get the equipment current state for transaction log
    $equipBefore = $this->selectEquip($id_equip);
    
    if(!$equipBefore || empty($equipBefore)){
        return false;
    }
    
    // Verify the equipment belongs to this employee
    if($equipBefore[0]['cod_func'] != $cod_func){
        return false;
    }
    
    // Update equipment status back to available
    $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE 
              SET sts_equip = 'DISPONIVEL', cod_func = NULL 
              WHERE id = $id_equip AND cod_func = $cod_func";
    
    $result = $this->sqlDb->update($query);
    
    if($result){
        // Get equipment state after update for transaction log
        $equipAfter = $this->selectEquip($id_equip);
        
        // Log the transaction
        $this->fazerTransicao($equipBefore, $equipAfter);
        
        return true;
    }
    
    return false;
}

/**
 * Search employees by name or code
 */
function searchEmployees($search = ''){
    $whereClause = "";
    if(!empty($search)){
        $whereClause = "AND (A.nome_func LIKE '%$search%' OR A.cod_func LIKE '%$search%')";
    }
    
    $query = "SELECT DISTINCT
                A.cod_func,
                A.nome_func,
                A.E_MAIL AS Email_Func,
                A.SECAO
              FROM MESU..FUNCIONARIOS AS A
              WHERE A.DT_TransDem IS NULL $whereClause
              ORDER BY A.nome_func";
    
    $dados = $this->sqlDb->select($query);
    return $dados ? $dados : array();
}
?>

------------

<?php
// Create this file as: atribuir_material_ajax.php

@session_start();
if($_SESSION['cod_usu'] == ''){   
    echo json_encode(array('success' => false, 'message' => 'Sessão expirada'));
    die();
}

require_once('Inventario/indexValueController.php');
$consulta = new EnhancedInventoryModel();

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch($action){
    case 'search_employees':
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $employees = $consulta->searchEmployees($search);
        echo json_encode(array('success' => true, 'data' => $employees));
        break;
        
    case 'get_employee_materials':
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        if($cod_func > 0){
            $materials = $consulta->getMaterialsInUseByEmployee($cod_func);
            $employee = $consulta->selectOne($cod_func);
            echo json_encode(array(
                'success' => true, 
                'materials' => $materials,
                'employee' => $employee
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Código do funcionário inválido'));
        }
        break;
        
    case 'get_available_materials':
        $materials = $consulta->getAvailableMaterials();
        echo json_encode(array('success' => true, 'data' => $materials));
        break;
        
    case 'assign_material':
        $id_equip = isset($_POST['id_equip']) ? intval($_POST['id_equip']) : 0;
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        
        if($id_equip > 0 && $cod_func > 0){
            $result = $consulta->assignMaterialToEmployee($id_equip, $cod_func);
            if($result){
                echo json_encode(array('success' => true, 'message' => 'Material atribuído com sucesso'));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Erro ao atribuir material'));
            }
        } else {
            echo json_encode(array('success' => false, 'message' => 'Dados inválidos'));
        }
        break;
        
    case 'remove_material':
        $id_equip = isset($_POST['id_equip']) ? intval($_POST['id_equip']) : 0;
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        
        if($id_equip > 0 && $cod_func > 0){
            $result = $consulta->removeMaterialFromEmployee($id_equip, $cod_func);
            if($result){
                echo json_encode(array('success' => true, 'message' => 'Material removido com sucesso'));
            } else {
                echo json_encode(array('success' => false, 'message' => 'Erro ao remover material'));
            }
        } else {
            echo json_encode(array('success' => false, 'message' => 'Dados inválidos'));
        }
        break;
        
    default:
        echo json_encode(array('success' => false, 'message' => 'Ação não reconhecida'));
        break;
}
?>

---------

<!-- Replace the AtribuirMaterialModal section in your main file with this -->
<div class="modal fade" id="AtribuirMaterialModal" tabindex="-1" role="dialog" aria-labelledby="AtribuirMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="AtribuirMaterialModalLabel">Atribuição de Material</h5>
                <button type="button" class="fa fa-times" data-dismiss="modal" aria-label="Close" style="position:relative;left:470px;border:none;color:#707070; background:none;"></button>
            </div>
            <div class="modal-body">
                <!-- Employee Search Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="employee_search">Buscar Funcionário:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="employee_search" placeholder="Digite o nome ou código do funcionário">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button" id="btn_search_employee">
                                        <i class="fa fa-search"></i> Buscar
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Results -->
                <div class="row" id="employee_results" style="display: none;">
                    <div class="col-md-12">
                        <h6>Selecione o Funcionário:</h6>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Seção</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="employee_list">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Selected Employee Materials -->
                <div class="row" id="employee_materials_section" style="display: none;">
                    <div class="col-md-12">
                        <hr>
                        <h5 id="selected_employee_name"></h5>
                        
                        <!-- Current Materials -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Materiais Atualmente em Uso:</h6>
                                <div id="current_materials" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Current materials will be loaded here -->
                                </div>
                            </div>
                            
                            <!-- Add New Material -->
                            <div class="col-md-6">
                                <h6>Adicionar Novo Material:</h6>
                                <button type="button" class="btn btn-success btn-sm" id="btn_show_available_materials">
                                    <i class="fa fa-plus"></i> Adicionar Material
                                </button>
                                
                                <!-- Available Materials List (Initially Hidden) -->
                                <div id="available_materials_section" style="display: none; margin-top: 10px;">
                                    <div class="form-group">
                                        <input type="text" class="form-control" id="material_search" placeholder="Filtrar materiais...">
                                    </div>
                                    <div id="available_materials" style="max-height: 250px; overflow-y: auto;">
                                        <!-- Available materials will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<style>
.material-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    background-color: #f9f9f9;
}

.material-item:hover {
    background-color: #f0f0f0;
}

.available-material-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    margin-bottom: 8px;
    background-color: #fff;
    cursor: pointer;
}

.available-material-item:hover {
    background-color: #e8f5e8;
    border-color: #5cb85c;
}

.employee-row {
    cursor: pointer;
}

.employee-row:hover {
    background-color: #f5f5f5;
}

.loading-spinner {
    text-align: center;
    padding: 20px;
}

.material-actions {
    margin-top: 5px;
}

.material-info {
    font-size: 12px;
    color: #666;
}
</style>


-----------

// Add this JavaScript to your existing script section or create a new script tag

$(document).ready(function() {
    var selectedEmployeeCode = null;
    
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
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayEmployees(response.data);
                } else {
                    $('#employee_list').html('<tr><td colspan="4">Erro ao buscar funcionários</td></tr>');
                }
            },
            error: function() {
                $('#employee_list').html('<tr><td colspan="4">Erro na requisição</td></tr>');
            }
        });
    }
    
    function displayEmployees(employees) {
        var html = '';
        
        if (employees.length === 0) {
            html = '<tr><td colspan="4">Nenhum funcionário encontrado</td></tr>';
        } else {
            $.each(employees, function(index, employee) {
                html += '<tr class="employee-row" data-cod-func="' + employee.cod_func + '">' +
                        '<td>' + employee.cod_func + '</td>' +
                        '<td>' + employee.nome_func + '</td>' +
                        '<td>' + (employee.SECAO || '') + '</td>' +
                        '<td><button class="btn btn-primary btn-xs btn-select-employee" data-cod-func="' + employee.cod_func + '">Selecionar</button></td>' +
                        '</tr>';
            });
        }
        
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
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#selected_employee_name').text('Funcionário: ' + response.employee[0].nome_func + ' (' + response.employee[0].cod_func + ')');
                    displayCurrentMaterials(response.materials);
                } else {
                    $('#current_materials').html('<div class="alert alert-danger">Erro ao carregar materiais</div>');
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
            $.each(materials, function(index, material) {
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
            });
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
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayAvailableMaterials(response.data);
                } else {
                    $('#available_materials').html('<div class="alert alert-danger">Erro ao carregar materiais</div>');
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
            $.each(materials, function(index, material) {
                html += '<div class="available-material-item" data-id="' + material.id + '">' +
                        '<strong>' + material.tipo + '</strong> - ' + material.marca + ' ' + material.modelo + '<br>' +
                        '<small>Host: ' + (material.hostname || 'N/A') + ' | Série: ' + (material.num_serie || 'N/A') + '</small>' +
                        '</div>';
            });
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
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Material atribuído com sucesso!');
                        // Reload the employee materials and available materials
                        loadEmployeeMaterials(selectedEmployeeCode);
                        loadAvailableMaterials();
                    } else {
                        alert('Erro: ' + response.message);
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
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Material removido com sucesso!');
                        // Reload the employee materials and available materials
                        loadEmployeeMaterials(selectedEmployeeCode);
                        if ($('#available_materials_section').is(':visible')) {
                            loadAvailableMaterials();
                        }
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro na requisição');
                }
            });
        }
    }
});