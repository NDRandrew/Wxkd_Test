<?php
@session_start();

// Include the same database connection as your model
require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');

// Handle AJAX requests
if(isset($_POST['action'])){
    
    $sqlDb = new MSSQL("MESU");
    
    // Get filtered requests
    if($_POST['action'] == 'get_pedidos'){
        $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : '';
        
        $whereClause = "";
        if(!empty($filtro) && $filtro != 'TODOS'){
            $whereClause = "WHERE SITUACAO = '$filtro'";
        }
        
        // First check if table exists and has data
        $query = "SELECT TOP 1 * FROM INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS";
        $testResult = $sqlDb->select($query);
        
        if($testResult === false){
            echo '<tr><td colspan="8" class="text-center text-danger">Erro: Tabela não encontrada ou erro de conexão</td></tr>';
            exit;
        }
        
        $query = "SELECT 
                    ISNULL(ID, ROW_NUMBER() OVER (ORDER BY DATA_PEDIDO DESC)) as ID,
                    COD_FUNC, NOME, DATA_PEDIDO, SITUACAO, MOTIVO_PEDIDO, MATERIAL_PEDIDO,
                    ISNULL(OBSERVACAO, '') as OBSERVACAO
                  FROM INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS $whereClause 
                  ORDER BY DATA_PEDIDO DESC";
        $result = $sqlDb->select($query);
        
        if(!empty($result)){
            foreach($result as $row){
                $dataPedido = !empty($row['DATA_PEDIDO']) ? date('d/m/Y', strtotime($row['DATA_PEDIDO'])) : 'N/A';
                $situacaoClass = strtolower(str_replace('Ç', 'c', $row['SITUACAO']));
                $pedidoId = $row['ID'];
                
                echo '<tr id="row-' . $pedidoId . '">';
                echo '<td>' . htmlspecialchars($row['COD_FUNC']) . '</td>';
                echo '<td>' . htmlspecialchars($row['NOME']) . '</td>';
                echo '<td>' . $dataPedido . '</td>';
                echo '<td><span class="label label-' . $situacaoClass . '">' . htmlspecialchars($row['SITUACAO']) . '</span></td>';
                echo '<td>' . htmlspecialchars(substr($row['MATERIAL_PEDIDO'], 0, 30)) . (strlen($row['MATERIAL_PEDIDO']) > 30 ? '...' : '') . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['MOTIVO_PEDIDO'], 0, 40)) . (strlen($row['MOTIVO_PEDIDO']) > 40 ? '...' : '') . '</td>';
                echo '<td>' . htmlspecialchars(substr($row['OBSERVACAO'], 0, 30)) . (strlen($row['OBSERVACAO']) > 30 ? '...' : '') . '</td>';
                echo '<td>';
                echo '<button class="btn btn-xs btn-primary" onclick="editarPedido(' . $pedidoId . ')">Editar</button>';
                echo '</td>';
                echo '</tr>';
                
                // Hidden edit row
                echo '<tr id="edit-' . $pedidoId . '" style="display:none;" class="edit-row">';
                echo '<td colspan="8">';
                echo '<div class="edit-form">';
                echo '<div class="row">';
                echo '<div class="col-md-4">';
                echo '<label>Situação:</label>';
                echo '<select class="form-control" id="situacao-' . $pedidoId . '">';
                echo '<option value="PENDENTE"' . ($row['SITUACAO'] == 'PENDENTE' ? ' selected' : '') . '>PENDENTE</option>';
                echo '<option value="VERIFICACAO"' . ($row['SITUACAO'] == 'VERIFICACAO' ? ' selected' : '') . '>VERIFICAÇÃO</option>';
                echo '<option value="CONCLUIDO"' . ($row['SITUACAO'] == 'CONCLUIDO' ? ' selected' : '') . '>CONCLUÍDO</option>';
                echo '<option value="CANCELADO"' . ($row['SITUACAO'] == 'CANCELADO' ? ' selected' : '') . '>CANCELADO</option>';
                echo '</select>';
                echo '</div>';
                echo '<div class="col-md-8">';
                echo '<label>Observação:</label>';
                echo '<textarea class="form-control" id="observacao-' . $pedidoId . '" rows="3">' . htmlspecialchars($row['OBSERVACAO']) . '</textarea>';
                echo '</div>';
                echo '</div>';
                echo '<div class="row" style="margin-top:10px;">';
                echo '<div class="col-md-12">';
                echo '<button class="btn btn-success btn-sm" onclick="salvarEdicao(' . $pedidoId . ')">Salvar</button> ';
                echo '<button class="btn btn-default btn-sm" onclick="cancelarEdicao(' . $pedidoId . ')">Cancelar</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            // Check if table is empty or if there's an error
            $countQuery = "SELECT COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS";
            $countResult = $sqlDb->select($countQuery);
            
            if(!empty($countResult) && $countResult[0]['total'] > 0){
                echo '<tr><td colspan="8" class="text-center text-warning">Nenhum pedido encontrado com o filtro selecionado</td></tr>';
            } else {
                echo '<tr><td colspan="8" class="text-center text-info">Nenhum pedido cadastrado ainda</td></tr>';
            }
        }
        exit;
    }
    
    // Update request
    if($_POST['action'] == 'update_pedido'){
        $id = intval($_POST['id']);
        $situacao = $_POST['situacao'];
        $observacao = $_POST['observacao'];
        
        // First check if record exists
        $checkQuery = "SELECT COD_FUNC FROM INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS WHERE ID = $id OR ROW_NUMBER() OVER (ORDER BY DATA_PEDIDO DESC) = $id";
        $exists = $sqlDb->select($checkQuery);
        
        if(empty($exists)){
            echo 'ERRO|Registro não encontrado.';
            exit;
        }
        
        $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS 
                  SET SITUACAO = '$situacao', OBSERVACAO = '$observacao' 
                  WHERE ID = $id";
        
        $result = $sqlDb->update($query);
        
        if($result !== false){
            echo 'SUCESSO|Pedido atualizado com sucesso!';
        } else {
            echo 'ERRO|Erro ao atualizar pedido.';
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .modal-controle .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .modal-controle .table {
            margin-bottom: 0;
        }
        
        .modal-controle .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-controle .edit-form {
            background-color: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .modal-controle .form-control {
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        
        .modal-controle .btn-xs {
            padding: 2px 8px;
            font-size: 11px;
        }
        
        .label-pendente { background-color: #f0ad4e !important; }
        .label-verificacao { background-color: #5bc0de !important; }
        .label-concluido { background-color: #5cb85c !important; }
        .label-cancelado { background-color: #d9534f !important; }
        
        .filter-container {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }
        
        .loading-table {
            text-align: center;
            padding: 40px;
        }
        
        .spinner-table {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .alert-controle {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>

<!-- Modal for Material Request Control -->
<div class="modal fade" id="modalPedidoControle" tabindex="-1" role="dialog" aria-labelledby="modalControleLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-controle">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalControleLabel">
                    <i class="fa fa-cogs"></i> Controle de Pedidos de Material
                </h4>
            </div>
            
            <div class="modal-body">
                <div id="alert-container-controle"></div>
                
                <!-- Filter Section -->
                <div class="filter-container">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="filtro-situacao"><strong>Filtrar por Situação:</strong></label>
                            <select class="form-control" id="filtro-situacao" onchange="carregarPedidos()">
                                <option value="TODOS">TODOS</option>
                                <option value="PENDENTE">PENDENTE</option>
                                <option value="VERIFICACAO">VERIFICAÇÃO</option>
                                <option value="CONCLUIDO">CONCLUÍDO</option>
                                <option value="CANCELADO">CANCELADO</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>&nbsp;</label><br>
                            <button class="btn btn-info" onclick="carregarPedidos()">
                                <i class="fa fa-refresh"></i> Atualizar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Table Section -->
                <div class="table-container">
                    <table class="table table-striped table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Data</th>
                                <th>Situação</th>
                                <th>Material</th>
                                <th>Motivo</th>
                                <th>Observação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabela-pedidos">
                            <tr>
                                <td colspan="8" class="loading-table">
                                    <div class="spinner-table"></div>
                                    <p>Carregando pedidos...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Load requests when modal opens
    document.getElementById('modalPedidoControle').addEventListener('show.bs.modal', function() {
        carregarPedidos();
    });
    
});

function carregarPedidos() {
    var filtro = document.getElementById('filtro-situacao').value;
    var tbody = document.getElementById('tabela-pedidos');
    
    // Show loading
    tbody.innerHTML = '<tr><td colspan="8" class="loading-table"><div class="spinner-table"></div><p>Carregando pedidos...</p></td></tr>';
    
    // Create AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4 && xhr.status === 200) {
            tbody.innerHTML = xhr.responseText;
        }
    };
    
    xhr.send('action=get_pedidos&filtro=' + encodeURIComponent(filtro));
}

function editarPedido(id) {
    // Hide normal row
    document.getElementById('row-' + id).style.display = 'none';
    // Show edit row
    document.getElementById('edit-' + id).style.display = 'table-row';
}

function cancelarEdicao(id) {
    // Show normal row
    document.getElementById('row-' + id).style.display = 'table-row';
    // Hide edit row
    document.getElementById('edit-' + id).style.display = 'none';
}

function salvarEdicao(id) {
    var situacao = document.getElementById('situacao-' + id).value;
    var observacao = document.getElementById('observacao-' + id).value;
    
    // Create AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4 && xhr.status === 200) {
            var response = xhr.responseText.split('|');
            var status = response[0];
            var message = response[1];
            
            if(status === 'SUCESSO') {
                mostrarAlertaControle(message, 'success');
                // Reload the table
                carregarPedidos();
            } else {
                mostrarAlertaControle(message, 'danger');
            }
        }
    };
    
    var data = 'action=update_pedido&id=' + id + 
               '&situacao=' + encodeURIComponent(situacao) + 
               '&observacao=' + encodeURIComponent(observacao);
    
    xhr.send(data);
}

function mostrarAlertaControle(message, type) {
    var alertContainer = document.getElementById('alert-container-controle');
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    
    alertContainer.innerHTML = '<div class="alert-controle ' + alertClass + '">' +
                               '<strong>' + (type === 'success' ? 'Sucesso!' : 'Erro!') + '</strong> ' + 
                               message + '</div>';
    
    // Auto-hide alerts
    setTimeout(function() {
        alertContainer.innerHTML = '';
    }, 3000);
}
</script>

</body>
</html>
