<?php
@session_start();

// Include the same database connection as your model
require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');

// Handle AJAX request for material request
if(isset($_POST['action']) && $_POST['action'] == 'pedido_material'){
    
    $sqlDb = new MSSQL("MESU");
    
    $cod_func = intval($_POST['cod_func']);
    $material_pedido = $_POST['material_pedido'];
    $motivo_pedido = $_POST['motivo_pedido'];
    
    // Get current date in DD/MM/YYYY format for insert
    $data_pedido = date('Y-m-d'); // For database insert
    $situacao = 'PENDENTE'; // Default status
    
    // Get employee name
    $nome_func = '';
    $queryFunc = "SELECT nome_func FROM MESU..FUNCIONARIOS WHERE cod_func = $cod_func AND dt_transdem IS NULL";
    $resultFunc = $sqlDb->select($queryFunc);
    
    if(!empty($resultFunc)){
        $nome_func = $resultFunc[0]['nome_func'];
    } else {
        // Try alternate table
        $queryFunc = "SELECT nomeFuncionario as nome_func FROM RH..STG_FUNCIONARIOS WHERE idFuncionario = $cod_func AND dataDemissao IS NULL";
        $resultFunc = $sqlDb->select($queryFunc);
        if(!empty($resultFunc)){
            $nome_func = $resultFunc[0]['nome_func'];
        }
    }
    
    if(empty($nome_func)){
        echo 'ERRO|Funcionário não encontrado.';
        exit;
    }
    
    // Validate required fields
    if(empty($cod_func) || empty($material_pedido) || empty($motivo_pedido)){
        echo 'ERRO|Todos os campos são obrigatórios.';
        exit;
    }
    
    // Insert into database
    $queryInsert = "INSERT INTO INFRA.DBO.TB_INVENTARIO_BE_PEDIDOS (COD_FUNC, NOME, DATA_PEDIDO, SITUACAO, MOTIVO_PEDIDO, MATERIAL_PEDIDO) 
                    VALUES ($cod_func, '$nome_func', '$data_pedido', '$situacao', '$motivo_pedido', '$material_pedido')";
    
    $result = $sqlDb->insert($queryInsert);
    
    if($result){
        echo 'SUCESSO|Pedido de material enviado com sucesso!';
    } else {
        echo 'ERRO|Erro ao enviar pedido. Tente novamente.';
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .modal-pedido .form-group {
            margin-bottom: 15px;
        }
        
        .modal-pedido .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .modal-pedido textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        .modal-pedido .btn-submit {
            background-color: #5cb85c;
            border-color: #4cae4c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .modal-pedido .btn-submit:hover {
            background-color: #449d44;
        }
        
        .modal-pedido .btn-cancel {
            background-color: #d9534f;
            border-color: #d43f3a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .modal-pedido .btn-cancel:hover {
            background-color: #c9302c;
        }
        
        .alert-pedido {
            padding: 15px;
            margin-bottom: 20px;
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
        
        #loading-pedido {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<!-- Modal for Material Request -->
<div class="modal fade" id="modalPedido" tabindex="-1" role="dialog" aria-labelledby="modalPedidoLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content modal-pedido">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalPedidoLabel">
                    <i class="fa fa-shopping-cart"></i> Pedido de Material
                </h4>
            </div>
            
            <div class="modal-body">
                <div id="alert-container-pedido"></div>
                
                <div id="loading-pedido">
                    <div class="spinner"></div>
                    <p>Enviando pedido...</p>
                </div>
                
                <form id="formPedidoMaterial">
                    <div class="form-group">
                        <label for="cod_func_pedido">
                            <strong>Código do Funcionário:</strong>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="cod_func_pedido" 
                               name="cod_func" 
                               placeholder="Digite seu código de funcionário"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="material_pedido">
                            <strong>Material Solicitado:</strong>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="material_pedido" 
                               name="material_pedido" 
                               placeholder="Ex: Notebook, Mouse, Teclado, etc."
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="motivo_pedido">
                            <strong>Motivo do Pedido:</strong>
                        </label>
                        <textarea class="form-control" 
                                  id="motivo_pedido" 
                                  name="motivo_pedido" 
                                  placeholder="Descreva o motivo da solicitação..."
                                  required></textarea>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-submit" id="btnEnviarPedido">
                    <i class="fa fa-paper-plane"></i> Enviar Pedido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Handle form submission
    document.getElementById('btnEnviarPedido').addEventListener('click', function() {
        enviarPedidoMaterial();
    });
    
    // Clear form when modal is opened
    document.getElementById('modalPedido').addEventListener('show.bs.modal', function() {
        limparFormPedido();
    });
    
    function enviarPedidoMaterial() {
        var form = document.getElementById('formPedidoMaterial');
        var codFunc = document.getElementById('cod_func_pedido').value;
        var materialPedido = document.getElementById('material_pedido').value;
        var motivoPedido = document.getElementById('motivo_pedido').value;
        
        // Validate form
        if(!codFunc || !materialPedido || !motivoPedido) {
            mostrarAlerta('Todos os campos são obrigatórios.', 'danger');
            return;
        }
        
        // Show loading
        document.getElementById('loading-pedido').style.display = 'block';
        document.getElementById('formPedidoMaterial').style.display = 'none';
        document.getElementById('btnEnviarPedido').disabled = true;
        
        // Create AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4) {
                // Hide loading
                document.getElementById('loading-pedido').style.display = 'none';
                document.getElementById('formPedidoMaterial').style.display = 'block';
                document.getElementById('btnEnviarPedido').disabled = false;
                
                if(xhr.status === 200) {
                    var response = xhr.responseText.split('|');
                    var status = response[0];
                    var message = response[1];
                    
                    if(status === 'SUCESSO') {
                        mostrarAlerta(message, 'success');
                        limparFormPedido();
                        
                        // Close modal after 2 seconds
                        setTimeout(function() {
                            var modal = document.getElementById('modalPedido');
                            var backdrop = document.querySelector('.modal-backdrop');
                            modal.style.display = 'none';
                            modal.classList.remove('in');
                            document.body.classList.remove('modal-open');
                            if(backdrop) {
                                backdrop.remove();
                            }
                        }, 2000);
                        
                    } else {
                        mostrarAlerta(message, 'danger');
                    }
                } else {
                    mostrarAlerta('Erro de conexão. Tente novamente.', 'danger');
                }
            }
        };
        
        // Send data
        var data = 'action=pedido_material&cod_func=' + encodeURIComponent(codFunc) + 
                   '&material_pedido=' + encodeURIComponent(materialPedido) + 
                   '&motivo_pedido=' + encodeURIComponent(motivoPedido);
        
        xhr.send(data);
    }
    
    function mostrarAlerta(message, type) {
        var alertContainer = document.getElementById('alert-container-pedido');
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        
        alertContainer.innerHTML = '<div class="alert-pedido ' + alertClass + '">' +
                                   '<strong>' + (type === 'success' ? 'Sucesso!' : 'Erro!') + '</strong> ' + 
                                   message + '</div>';
        
        // Auto-hide success alerts
        if(type === 'success') {
            setTimeout(function() {
                alertContainer.innerHTML = '';
            }, 3000);
        }
    }
    
    function limparFormPedido() {
        document.getElementById('formPedidoMaterial').reset();
        document.getElementById('alert-container-pedido').innerHTML = '';
    }
    
});
</script>

</body>
</html>