<?php
@session_start();

// BULLETPROOF APPROACH: Use URL parameters instead of POST detection
// ?ajax=get_pedidos or ?ajax=update_pedido
if(isset($_GET['ajax'])){
    
    require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');
    $sqlDb = new MSSQL("MESU");
    
    if($_GET['ajax'] == 'get_pedidos'){
        $filtro = isset($_POST['filtro']) ? $_POST['filtro'] : (isset($_GET['filtro']) ? $_GET['filtro'] : '');
        
        $whereClause = "";
        if(!empty($filtro) && $filtro != 'TODOS'){
            $whereClause = "WHERE SITUACAO = '$filtro'";
        }
        
        $query = "SELECT ID, COD_FUNC, NOME, DATA_PEDIDO, SITUACAO, MOTIVO_PEDIDO, MATERIAL_PEDIDO, OBSERVACAO FROM INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS $whereClause ORDER BY DATA_PEDIDO DESC";
        
        $result = $sqlDb->select($query);
        
        if($result && count($result) > 0){
            foreach($result as $row){
                $dataPedido = date('d/m/Y', strtotime($row['DATA_PEDIDO']));
                $situacaoClass = strtolower($row['SITUACAO']);
                $observacao = isset($row['OBSERVACAO']) ? $row['OBSERVACAO'] : '';
                
                echo '<tr id="row-' . $row['ID'] . '">';
                echo '<td>' . $row['COD_FUNC'] . '</td>';
                echo '<td>' . $row['NOME'] . '</td>';
                echo '<td>' . $dataPedido . '</td>';
                echo '<td><span class="label label-' . $situacaoClass . '">' . $row['SITUACAO'] . '</span></td>';
                echo '<td>' . substr($row['MATERIAL_PEDIDO'], 0, 30) . (strlen($row['MATERIAL_PEDIDO']) > 30 ? '...' : '') . '</td>';
                echo '<td>' . substr($row['MOTIVO_PEDIDO'], 0, 40) . (strlen($row['MOTIVO_PEDIDO']) > 40 ? '...' : '') . '</td>';
                echo '<td>' . substr($observacao, 0, 30) . (strlen($observacao) > 30 ? '...' : '') . '</td>';
                echo '<td>';
                echo '<button class="btn btn-xs btn-primary" onclick="editarPedido(' . $row['ID'] . ')">Editar</button>';
                echo '</td>';
                echo '</tr>';
                
                // Hidden edit row
                echo '<tr id="edit-' . $row['ID'] . '" style="display:none;" class="edit-row">';
                echo '<td colspan="8">';
                echo '<div class="edit-form">';
                echo '<div class="row">';
                echo '<div class="col-md-4">';
                echo '<label>Situação:</label>';
                echo '<select class="form-control" id="situacao-' . $row['ID'] . '">';
                echo '<option value="PENDENTE"' . ($row['SITUACAO'] == 'PENDENTE' ? ' selected' : '') . '>PENDENTE</option>';
                echo '<option value="VERIFICACAO"' . ($row['SITUACAO'] == 'VERIFICACAO' ? ' selected' : '') . '>VERIFICAÇÃO</option>';
                echo '<option value="CONCLUIDO"' . ($row['SITUACAO'] == 'CONCLUIDO' ? ' selected' : '') . '>CONCLUÍDO</option>';
                echo '<option value="CANCELADO"' . ($row['SITUACAO'] == 'CANCELADO' ? ' selected' : '') . '>CANCELADO</option>';
                echo '</select>';
                echo '</div>';
                echo '<div class="col-md-8">';
                echo '<label>Observação:</label>';
                echo '<textarea class="form-control" id="observacao-' . $row['ID'] . '" rows="3">' . htmlspecialchars($observacao) . '</textarea>';
                echo '</div>';
                echo '</div>';
                echo '<div class="row" style="margin-top:10px;">';
                echo '<div class="col-md-12">';
                echo '<button class="btn btn-success btn-sm" onclick="salvarEdicao(' . $row['ID'] . ')">Salvar</button> ';
                echo '<button class="btn btn-default btn-sm" onclick="cancelarEdicao(' . $row['ID'] . ')">Cancelar</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="8" class="text-center">Nenhum pedido encontrado</td></tr>';
        }
        exit();
    }
    
    if($_GET['ajax'] == 'update_pedido'){
        $id = intval($_POST['id']);
        $situacao = $_POST['situacao'];
        $observacao = $_POST['observacao'];
        
        $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS 
                  SET SITUACAO = '$situacao', OBSERVACAO = '$observacao' 
                  WHERE ID = $id";
        
        $result = $sqlDb->update($query);
        
        if($result){
            echo 'SUCESSO|Pedido atualizado com sucesso!';
        } else {
            echo 'ERRO|Erro ao atualizar pedido.';
        }
        exit();
    }
    
    exit();
}

// If no ?ajax parameter, show the modal HTML
require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');
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
// BULLETPROOF JAVASCRIPT - Uses URL parameters instead of POST detection
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalPedidoControle').addEventListener('show.bs.modal', function() {
        carregarPedidos();
    });
});

function getBaseUrl() {
    var url = window.location.href;
    // Remove any existing query parameters
    if(url.indexOf('?') !== -1) {
        url = url.substring(0, url.indexOf('?'));
    }
    return url;
}

function carregarPedidos() {
    var filtro = document.getElementById('filtro-situacao').value;
    var tbody = document.getElementById('tabela-pedidos');
    
    tbody.innerHTML = '<tr><td colspan="8" class="loading-table"><div class="spinner-table"></div><p>Carregando pedidos...</p></td></tr>';
    
    var xhr = new XMLHttpRequest();
    var url = getBaseUrl() + '?ajax=get_pedidos';
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4) {
            console.log('=== LOAD RESPONSE ===');
            console.log('URL:', url);
            console.log('Status:', xhr.status);
            console.log('Length:', xhr.responseText.length);
            console.log('Starts with <tr?', xhr.responseText.trim().indexOf('<tr') === 0);
            
            if(xhr.status === 200) {
                var response = xhr.responseText.trim();
                if(response.indexOf('<tr') === 0) {
                    tbody.innerHTML = response;
                    console.log('SUCCESS: Table loaded');
                } else {
                    console.log('ERROR: Invalid response format');
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erro: Formato de resposta inválido</td></tr>';
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Erro HTTP: ' + xhr.status + '</td></tr>';
            }
        }
    };
    
    xhr.send('filtro=' + encodeURIComponent(filtro));
}

function editarPedido(id) {
    document.getElementById('row-' + id).style.display = 'none';
    document.getElementById('edit-' + id).style.display = 'table-row';
}

function cancelarEdicao(id) {
    document.getElementById('row-' + id).style.display = 'table-row';
    document.getElementById('edit-' + id).style.display = 'none';
}

function salvarEdicao(id) {
    var situacao = document.getElementById('situacao-' + id).value;
    var observacao = document.getElementById('observacao-' + id).value;
    
    var xhr = new XMLHttpRequest();
    var url = getBaseUrl() + '?ajax=update_pedido';
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4) {
            console.log('=== UPDATE RESPONSE ===');
            console.log('URL:', url);
            console.log('Status:', xhr.status);
            console.log('Response:', xhr.responseText);
            
            if(xhr.status === 200) {
                var response = xhr.responseText.trim();
                
                if(response.substring(0, 7) === 'SUCESSO') {
                    var parts = response.split('|');
                    var message = parts.length > 1 ? parts[1] : 'Sucesso';
                    mostrarAlertaControle(message, 'success');
                    carregarPedidos();
                } else if(response.substring(0, 4) === 'ERRO') {
                    var parts = response.split('|');
                    var message = parts.length > 1 ? parts[1] : 'Erro';
                    mostrarAlertaControle(message, 'danger');
                } else {
                    mostrarAlertaControle('Resposta inesperada: ' + response, 'danger');
                }
            } else {
                mostrarAlertaControle('Erro HTTP: ' + xhr.status, 'danger');
            }
        }
    };
    
    var data = 'id=' + id + 
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
    
    setTimeout(function() {
        alertContainer.innerHTML = '';
    }, 3000);
}
</script>

</body>
</html>
