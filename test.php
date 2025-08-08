<?php
@session_start();

require_once('TratamentosInv.php');

if(isset($_POST['action']) && $_POST['action'] == 'buscar_equipamento'){
    
    $consulta = new ClassDados();
    
    // Sanitize input data
    $filtros = array();
    
    if(isset($_POST['tipo']) && !empty($_POST['tipo'])){
        $filtros['tipo'] = trim($_POST['tipo']);
    }
    
    if(isset($_POST['marca']) && !empty($_POST['marca'])){
        $filtros['marca'] = trim($_POST['marca']);
    }
    
    if(isset($_POST['nome']) && !empty($_POST['nome'])){
        $filtros['nome'] = trim($_POST['nome']);
    }
    
    if(isset($_POST['cod_func']) && !empty($_POST['cod_func'])){
        $filtros['cod_func'] = trim($_POST['cod_func']);
    }
    
    if(isset($_POST['status']) && !empty($_POST['status'])){
        $filtros['status'] = trim($_POST['status']);
    }
    
    if(isset($_POST['num_serie']) && !empty($_POST['num_serie'])){
        $filtros['num_serie'] = trim($_POST['num_serie']);
    }
    
    // Get filtered equipment data
    $equipamentos = $consulta->buscarEquipamentosFiltros($filtros);
    
    if(!empty($equipamentos)){
        echo '<div class="widget">';
        echo '<div class="widget-body no-padding">';
        echo '<table class="table table-bordered table-hover table-striped" id="tbBuscaEquipamentos" style="width: 100%;">';
        echo '<thead class="bordered-darkorange">';
        echo '<tr role="row">';
        echo '<th>ID</th>';
        echo '<th>Tipo</th>';
        echo '<th>Marca</th>';
        echo '<th>Modelo</th>';
        echo '<th>Hostname</th>';
        echo '<th>Nº Série</th>';
        echo '<th>Status</th>';
        echo '<th>Usuário</th>';
        echo '<th>Ação</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach($equipamentos as $equip){
            $equipInfo = $equip['modelo'] . ' / ' . $equip['hostname'] . ' / ' . $equip['num_serie'];
            
            echo '<tr>';
            echo '<td>' . $equip['id'] . '</td>';
            echo '<td>' . $equip['tipo'] . '</td>';
            echo '<td>' . $equip['marca'] . '</td>';
            echo '<td>' . $equip['modelo'] . '</td>';
            echo '<td>' . $equip['hostname'] . '</td>';
            echo '<td>' . $equip['num_serie'] . '</td>';
            echo '<td>';
            
            // Add label colors based on status
            switch(strtoupper($equip['sts_equip'])){
                case 'DISPONIVEL':
                    echo '<span class="label label-success">Disponível</span>';
                    break;
                case 'EM USO':
                    echo '<span class="label label-info">Em Uso</span>';
                    break;
                case 'RESERVADO':
                    echo '<span class="label label-warning">Reservado</span>';
                    break;
                case 'PADRONIZAR':
                    echo '<span class="label label-danger">Padronizar</span>';
                    break;
                case 'DESCARTE':
                    echo '<span class="label label-default">Descarte</span>';
                    break;
                default:
                    echo '<span class="label label-default">' . $equip['sts_equip'] . '</span>';
            }
            echo '</td>';
            
            echo '<td>' . $equip['nome_func'] . '</td>';
            echo '<td>';
            echo '<button class="btn btn-primary btn-sm equipamento-item" ';
            echo 'data-equip-id="' . $equip['id'] . '" ';
            echo 'data-equip-info="' . htmlspecialchars($equipInfo) . '">';
            echo '<i class="fa fa-line-chart"></i> Ver Histórico';
            echo '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        // JavaScript for DataTable initialization with pagination (50 items per page)
        echo '<script>';
        echo '$(document).ready(function(){';
        echo '    $("#tbBuscaEquipamentos").DataTable({';
        echo '        "sDom": "Tflt<\'row DTTTFooter\'<\'col-sm-6\'i><\'col-sm-6\'p>>",';
        echo '        "iDisplayLength": 50,';
        echo '        "bProcessing": true,';
        echo '        "bDeferRender": true,';
        echo '        "oTableTools": {';
        echo '            "aButtons": ["copy"],';
        echo '            "sSwfPath": "assets/swf/copy_csv_xls_pdf.swf"';
        echo '        },';
        echo '        "aLengthMenu": [[25, 50, 100, -1], [25, 50, 100, "Todos"]],';
        echo '        "language": {';
        echo '            "search": "Buscar:",';
        echo '            "sLengthMenu": "Mostrar _MENU_ registros",';
        echo '            "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",';
        echo '            "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",';
        echo '            "sInfoFiltered": "(filtrado de _MAX_ registros no total)",';
        echo '            "oPaginate": {';
        echo '                "sPrevious": "Anterior",';
        echo '                "sNext": "Próximo",';
        echo '                "sFirst": "Primeiro",';
        echo '                "sLast": "Último"';
        echo '            },';
        echo '            "zeroRecords": "Nenhum resultado encontrado!"';
        echo '        },';
        echo '        "ordering": true,';
        echo '        "order": [[0, "desc"]],';
        echo '        "columnDefs": [';
        echo '            { "orderable": false, "targets": [8] }';
        echo '        ]';
        echo '    });';
        echo '    ';
        echo '    // Re-bind click events after DataTable initialization';
        echo '    $("#tbBuscaEquipamentos").on("click", ".equipamento-item", function(){';
        echo '        var equipId = $(this).data("equip-id");';
        echo '        var equipInfo = $(this).data("equip-info");';
        echo '        mostrarGraficoTransacoes(equipId, equipInfo);';
        echo '    });';
        echo '});';
        echo '</script>';
        
    } else {
        echo '<div class="alert alert-warning">';
        echo '<strong>Aviso:</strong> Nenhum equipamento encontrado com os critérios especificados.';
        echo '<br>Tente alterar os filtros de busca.';
        echo '</div>';
    }
    
} else {
    echo '<div class="alert alert-danger">';
    echo '<strong>Erro:</strong> Ação não reconhecida.';
    echo '</div>';
}
?>


---------------------


<?php
    @session_start();
    $cod_usu = $_SESSION['cod_usu'];

    require_once('TratamentosInv.php');
    $consulta = new ClassDados();
    
    // Optimized: Get all data with JOINs to avoid multiple queries
    $dados = $consulta->selectInventarioOptimized();
    $dadosTrans = $consulta->selectTransacoesOptimized();
?>

<style type="text/css">
    hr{
        margin-top: 0px;
        margin-bottom: 10px;
    }
    .transition-block {
        border: 2px solid #333;
        border-radius: 5px;
        padding: 10px;
        margin: 5px;
        background-color: #f9f9f9;
        display: inline-block;
        min-width: 150px;
        text-align: center;
    }
    .transition-arrow {
        display: inline-block;
        margin: 0 10px;
        font-size: 20px;
        color: #007bff;
    }
    .transition-timeline {
        text-align: center;
        margin: 20px 0;
    }
    .status-disponivel { background-color: #d4edda; border-color: #28a745; }
    .status-em-uso { background-color: #d1ecf1; border-color: #17a2b8; }
    .status-reservado { background-color: #fff3cd; border-color: #ffc107; }
    .status-padronizar { background-color: #f8d7da; border-color: #dc3545; }
    .status-descarte { background-color: #e2e3e5; border-color: #6c757d; }
</style>

<div hidden id="btnDeletar" data-toggle="modal" data-target="#deletar"></div>
<div class="row">
    <div class="col-lg-12 col-sm-12 col-xs-12">
        <div class="tabbable">
            <ul class="nav nav-tabs" id="myTab">
                <li class="active">
                    <a data-toggle="tab" href="#inventario">
                        INVENTÁRIO
                    </a>
                </li>
                <li class="tab-red">
                    <a data-toggle="tab" href="#hist-trans">
                        HISTÓRICO DE TRANSAÇÕES
                    </a>
                </li>
                <li class="tab-blue">
                    <a data-toggle="tab" href="#grafico-trans">
                        GRÁFICO DE TRANSAÇÕES
                    </a>
                </li>
            </ul>

            <div class="tab-content">                

                <div id="inventario" class="tab-pane in active">
                    <!-- ============================ INVENTARIO ============================ -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="col-xs-2">
                                <button type="button" class="btn btn-blue" style="width: 170px; color: white;" data-toggle="modal" data-target="#incluir"> Incluir Equipamento&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-plus"></i></button>
                            </div>
                            <div class="col-xs-2" style="cursor: pointer;">
                                <select class="form-control" style="height: 28px; " id="filtroTbInv">
                                    <option selected value="1">Todos</option>
                                    <option value="2">Disponivel</option>
                                    <option value="3">Em uso</option>
                                    <option value="4">Reservado</option>
                                    <option value="5">Padronizar</option>
                                    <option value="6">Descarte</option>
                                </select>
                            </div>                            
                            <div class="col-xs-2 pull-right">
                                <a href="gerarExcel.php?action=1" id="excel" ><img src="../img/logo_excel.png" style="width: 20%; height: 10%;"></a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-xs-2">                            
                            <div class="input-group has-info">
                                <span class="input-group-addon">&nbsp;&nbsp;ID:&nbsp;&nbsp;</span>
                                <input class="form-control" type="text" id="search-id">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="tb_inv">
                        <div class="row">
                            <div class="col-xs-12 col-md-12">
                                <div class="widget">                                                                                        
                                    <div class="widget-body no-padding">                       
                                        <table class="table table-bordered table-hover table-striped" id="tbInventario" style="width: 98%; margin-left: 15px; margin-right: 10px; margin-bottom: 10px;">
                                            <thead class="bordered-darkorange">
                                                <tr role="row">
                                                    <td>ID</td>
                                                    <td>Tipo</td>
                                                    <td>Marca</td>
                                                    <td>Modelo</td>                                
                                                    <td style="width: 200px;">HOSTNAME</td>
                                                    <td>Nº de Série</td>
                                                    <td style="width: 100px;">Status</td>
                                                    <td style="width: 100px;">Codigo Funcional</td>
                                                    <td style="width: 300px;">Nome Usuario</td>
                                                    <td>RAMAL</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                // Optimized: No more individual queries in loop
                                                for($i = 0; $i < count($dados); $i++){
                                                    if($dados[$i]['RAMAL'] != '' || $dados[$i]['RAMAL_INTERNO'] != ''){
                                                        $tel = $dados[$i]['RAMAL']." - ".$dados[$i]['RAMAL_INTERNO'];
                                                    }else if($dados[$i]['DDD_CEL_CORPORATIVO'] != '' && $dados[$i]['CELULAR_CORPORATIVO'] != ''){                                                        
                                                        $tel = $dados[$i]['DDD_CEL_CORPORATIVO']." - ".$dados[$i]['CELULAR_CORPORATIVO'];
                                                    }else{
                                                        $tel = '-';
                                                    }
                                                ?>
                                                    <tr class="openModalEdit" style="cursor: pointer;" cod='<?php echo $dados[$i]['id'];?>'  data-toggle="modal" data-target="#editar">
                                                        <td><?php echo $dados[$i]['id'];?></td>
                                                        <td><?php echo $dados[$i]['tipo'];?></td>
                                                        <td><?php echo $dados[$i]['marca'];?></td>
                                                        <td><?php echo $dados[$i]['modelo'];?></td>                                    
                                                        <td><?php echo $dados[$i]['hostname'];?></td>
                                                        <td><?php echo $dados[$i]['num_serie'];?></td>
                                                        <td><?php echo $dados[$i]['sts_equip'];?></td>
                                                        <td><?php echo $dados[$i]['cod_func'];?></td>
                                                        <td><?php echo $dados[$i]['nome_func'];?></td>
                                                        <td><?php echo $tel;?></td>
                                                    </tr>
                                                <?php }?>                      
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        $('#tbInventario').DataTable({
                           "sDom": "Tflt<'row DTTTFooter'<'col-sm-12'i><'col-sm-12'p>>",
                            "iDisplayLength": 20,
                            "bProcessing": true,
                            "bDeferRender": true,
                            "oTableTools": {
                                "aButtons": ["copy"],
                                "sSwfPath": "assets/swf/copy_csv_xls_pdf.swf"
                            },               
                            "aLengthMenu": [[20, 100, -1], [20, 100, "All"]],
                            "language": {
                                "search": "",
                                "sLengthMenu": "_MENU_",
                                "oPaginate": {
                                    "sPrevious": "Anterior",
                                    "sNext": "Proximo"
                                },
                                "zeroRecords": "Nenhum Resultado Encontrardo!"
                            }
                        }); 
                    </script>
                    <!-- ============================ INVENTARIO ============================ -->
                </div>

                <div id="hist-trans" class="tab-pane">
                    <button hidden id="attTrans"></button>
                    <!-- ============================  HISTORICO DE TRANSIÇÕES ============================ -->
                    <div class="row" style="">
                        <div class="col-xs-12 col-md-12">
                            <div class="widget">
                                <div class="widget-body">
                                    <div class="row">
                                        <div class="form-group col-xs-2">                            
                                            <div class="input-group has-info">
                                                <span class="input-group-addon">&nbsp;&nbsp;ID:&nbsp;&nbsp;</span>
                                                <input class="form-control" type="text" id="search-id-trans">
                                            </div>
                                        </div>
                                    </div>
                                    <hr>

                                <div id="tbTrans">
                                    <table class="table table-striped table-bordered table-hover" id="search">
                                        <thead>
                                            <tr>
                                                <th style="width: 10px;">ID</th>
                                                <th style="width: 10px;">Status Antigo</th>
                                                <th style="width: 20px;">Funcional</th>
                                                <th style="width: 160px;">Nome</th>
                                                <th style="width: 10px;">PARA</th>
                                                <th style="width: 20px;">Status Atual</th>
                                                <th style="width: 20px;">Funcional</th>                                                
                                                <th style="width: 160px;">Nome</th>
                                                <th style="width: 20px;">Data de Transição</th>
                                                <th style="width: 20px;">ID Equipamento</th>
                                                <th style="width: 5px">TERMOS RESP</th>
                                                <th style="width: 5px">TERMOS DEV</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                            $lastTransId = '';
                                            for($i = 0; $i < count($dadosTrans); $i++){
                                                if($dadosTrans[$i]['id_trans'] != $lastTransId){
                                                    $lastTransId = $dadosTrans[$i]['id_trans'];
                                        ?>
                                            <tr class="lineTbTrans" codTrans='<?php echo $dadosTrans[$i]['id_trans'];?>' data-toggle="modal" data-target="#transEditar">
                                                <td><?php echo $dadosTrans[$i]['id_trans'];?></td>
                                                <td><?php echo $dadosTrans[$i]['sts_ant'];?></td>
                                                <td><?php echo $dadosTrans[$i]['cod_func_antigo'];?></td>
                                                <td><?php echo $dadosTrans[$i]['nome_func_antigo'];?></td>
                                                <td style="text-align: center;">
                                                    <span class="glyphicon glyphicon-arrow-right"></span>
                                                </td>
                                                <td><?php echo $dadosTrans[$i]['sts_atual'];?></td>
                                                <td><?php echo $dadosTrans[$i]['cod_func_atual'];?></td>
                                                <td><?php echo $dadosTrans[$i]['nome_func_atual'];?></td>
                                                <td class="center "><?php echo $dadosTrans[$i]['dt_trans'];?></td>
                                                <td><?php echo $dadosTrans[$i]['id_equip'];?></td>
                                                <td><?php echo $dadosTrans[$i]['TERMO_RESP']?></td>
                                                <td><?php echo $dadosTrans[$i]['TERMO_DEV']?></td>
                                            </tr>
                                        <?php 
                                                }
                                            }
                                        ?>
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        $('#search').DataTable({
                           "sDom": "Tflt<'row DTTTFooter'<'col-sm-12'i><'col-sm-12'p>>",
                            "iDisplayLength": 20,
                            "bProcessing": true,
                            "bDeferRender": true,
                            "oTableTools": {
                                "aButtons": ["copy"],
                                "sSwfPath": "assets/swf/copy_csv_xls_pdf.swf"
                            },               
                            "aLengthMenu": [[20, 100, -1], [20, 100, "All"]],
                            "language": {
                                "search": "",
                                "sLengthMenu": "_MENU_",
                                "oPaginate": {
                                    "sPrevious": "Anterior",
                                    "sNext": "Proximo"
                                },
                                "zeroRecords": "Nenhum Resultado Encontrardo!"
                            }
                        }); 
                    </script>
                    <!-- ============================  HISTORICO DE TRANSIÇÕES ============================ -->
                </div>

                <!-- ============================ GRÁFICO DE TRANSIÇÕES ============================ -->
                <div id="grafico-trans" class="tab-pane">
                    <div class="row">
                        <div class="col-lg-4 col-sm-4 col-xs-12">
                            <div class="widget">
                                <div class="widget-header">
                                    <span class="widget-caption">Buscar Equipamento</span>
                                </div>
                                <div class="widget-body bordered-top bordered-pink">
                                    <div class="collapse in">
                                        <form role="form" id="formBuscaEquip">
                                            <div class="form-group">
                                                <label>Tipo:</label>
                                                <span class="input-icon">
                                                    <select class="form-control input-sm" id="busca_tipo">
                                                        <option value="">Todos</option>
                                                        <option>DESKTOP</option>
                                                        <option>NOTEBOOK</option>
                                                        <option>DISPOSITIVOS</option>
                                                    </select>
                                                    <i class="fa fa-desktop blue"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <label>Marca:</label>
                                                <span class="input-icon">
                                                    <input type="text" class="form-control input-sm" id="busca_marca" placeholder="Ex: Dell, HP...">
                                                    <i class="fa fa-tag darkorange"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <label>Nome Usuário:</label>
                                                <span class="input-icon">
                                                    <input type="text" class="form-control input-sm" id="busca_nome" placeholder="Nome do funcionário">
                                                    <i class="fa fa-user purple"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <label>Código Funcional:</label>
                                                <span class="input-icon">
                                                    <input type="text" class="form-control input-sm" id="busca_cod_func" placeholder="Código do funcionário">
                                                    <i class="fa fa-id-card palegreen"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <label>Status:</label>
                                                <span class="input-icon">
                                                    <select class="form-control input-sm" id="busca_status">
                                                        <option value="">Todos</option>
                                                        <option>DISPONIVEL</option>
                                                        <option>EM USO</option>
                                                        <option>RESERVADO</option>
                                                        <option>PADRONIZAR</option>
                                                        <option>DESCARTE</option>
                                                    </select>
                                                    <i class="fa fa-flag maroon"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <label>Nº de Série:</label>
                                                <span class="input-icon">
                                                    <input type="text" class="form-control input-sm" id="busca_num_serie" placeholder="Número de série">
                                                    <i class="fa fa-barcode blue"></i>
                                                </span>
                                            </div>
                                            <div class="form-group">
                                                <button type="button" class="btn btn-primary btn-block" id="btnBuscarEquip">
                                                    <i class="fa fa-search"></i> Buscar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8 col-sm-8 col-xs-12">
                            <div class="widget">
                                <div class="widget-header">
                                    <span class="widget-caption">Resultados da Busca</span>
                                </div>
                                <div class="widget-body">
                                    <div id="resultadosBusca">
                                        <p class="text-muted">Utilize os filtros à esquerda para buscar equipamentos.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="grafico-container" style="display: none;">
                        <div class="col-xs-12">
                            <div class="widget">
                                <div class="widget-header">
                                    <span class="widget-caption">Histórico de Transações - <span id="equipamento-selecionado"></span></span>
                                </div>
                                <div class="widget-body">
                                    <div id="timeline-transacoes"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================ FIM GRÁFICO DE TRANSIÇÕES ============================ -->

            </div>
        </div>
        <div class="horizontal-space"></div>
    </div>
</div>

<!-- JavaScript for new functionality -->
<script>
// Global function for showing transaction graphics (needed for pagination)
function mostrarGraficoTransacoes(equipId, equipInfo) {
    $('#equipamento-selecionado').text(equipInfo);
    
    $.ajax({
        url: 'buscar_transacoes.php',
        type: 'POST',
        data: 'equip_id=' + equipId,
        success: function(response){
            $('#timeline-transacoes').html(response);
            $('#grafico-container').show();
            
            // Scroll to graph container
            $('html, body').animate({
                scrollTop: $('#grafico-container').offset().top - 100
            }, 800);
        },
        error: function(){
            $('#timeline-transacoes').html('<p class="text-danger">Erro ao carregar histórico de transações.</p>');
            $('#grafico-container').show();
        }
    });
}

$(document).ready(function(){
    $(".valorAq").maskMoney({prefix:'R

<!-- Keep all existing modals -->
<!-- ================MODAL DE CADASTRO DE NOVOS EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="incluir">
    <div class="modal-dialog modal-sm" style="width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Novo Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Cadastrar</span>
                </div>
                <div class="widget-body">
                    <div class="row">                            
                        <div class="col-xs-12">   
                            <strong>Dados Esquipamentos</strong>
                            <hr style="margin-top: 0px;">
                            <div class="col-xs-2">
                                <label>Tipo:</label>
                                <select class="form-control dadosMaquina" name="tipo" id="tipo">
                                    <option selected disabled value="">Escolha o Tipo..</option>
                                    <option>DESKTOP</option>
                                    <option>NOTEBOOK</option>
                                    <option>DISPOSITIVOS</option>
                                </select>
                            </div>
                            <div class="col-xs-2">
                                <label>Marca:</label>
                                <input type="text" class="form-control dadosMaquina" name="marca" id="marca" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Modelo:</label>
                                <input type="text" class="form-control dadosMaquina" name="modelo" id="modelo" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Status:</label>
                                <select class="form-control dadosMaquina" name="sts_equip" id="sts_equip">
                                    <option selected disabled value="">Escolha o Tipo..</option>
                                    <option>DISPONIVEL</option>
                                    <option>EM USO</option>
                                    <option>RESERVADO</option>
                                    <option>PADRONIZAR</option>                                        
                                    <option>DESCARTE</option>
                                </select>
                            </div>                                
                        </div>
                    </div>
                    <div class="row" style="margin-top: 5px;">                            
                        <div class="col-xs-12">  
                            <div class="col-xs-2">
                                <label>Nº de Série</label>
                                <input type="text" class="form-control dadosMaquina" name="num_serie" id="num_serie" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Hostname:</label>
                                <input type="text" class="form-control dadosMaquina" name="hostname" placeholder="">
                            </div>
                            <div class="col-xs-2" hidden="" id="loc_instal_div">
                                <label>Local de Uso:</label>
                                <input type="text" class="form-control dadosMaquina" name="loc_instal" id="loc_instal" placeholder="">
                            </div>
                            <div class="col-xs-2" hidden="" id="cod_func_div">
                                <label>Codigo Funcional:</label>
                                <input type="text" class="form-control dadosMaquina" name="cod_func" id="cod_func" onkeyup="MascaraCodFunc(this,event);" placeholder="">
                            </div>
                            <div class="col-xs-4" hidden="" id="nome_func_fiv"></div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="col-xs-12">
                            <strong>Dados Hardware/SoftWare</strong>
                            <hr>
                            <div class="col-xs-2">
                                <label>Processador:</label>
                                <input type="text" class="form-control dadosMaquina" name="processador" placeholder="Intel Core I7">
                            </div>
                            <div class="col-xs-2">
                                <label>RAM:</label>
                                <input type="text" class="form-control dadosMaquina" name="RAM" placeholder="8GB">
                            </div>
                             <div class="col-xs-2">
                                <label>HD:</label>
                                <input type="text" class="form-control dadosMaquina" name="HD" placeholder="500GB">
                            </div>
                            <div class="col-xs-2">
                                <label>Sistema Operacional:</label>
                                <input type="text" class="form-control dadosMaquina" name="sistem_op" placeholder="Windows 10">
                            </div>
                            <div class="col-xs-3">
                                <label>Aplicativos:</label>
                                <input type="text" class="form-control dadosMaquina" name="apps" placeholder="Pacote Office 2010, Cisco Any Connect">
                            </div>
                        </div>
                    </div>
                    <div class="row disp_mov" style="margin-top: 15px;" hidden>
                        <div class="col-xs-12">
                            <strong>Dados Dispositivo</strong>
                            <hr>
                            <div class="col-xs-2">
                                <label>IMEI:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="IMEI" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Nº CHIP:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="NUM_CHIP" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>OPERADORA:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="OPERADORA" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>DDD:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="DDD" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Nº TEL:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="TEL" placeholder="">
                            </div>
                        </div>

                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-xs-12">
                            <b>Dados Compra</b>
                            <hr>
                            <div class="col-xs-2">
                                <label>Nº SAP:</label>
                                <input type="text" name="num_sap" class="form-control dadosMaquina">
                            </div>
                            <div class="col-xs-2">
                                <label>Data da Aquisição:</label>
                                <input type="text" name="dt_compra" id="dt_compra" class="form-control" onkeyup="MascaraData(this,event);">
                            </div>
                             <div class="col-xs-2">
                                <label>Valor da Aquisição:</label>
                                <input type="text" name="val_compra" id="val_compra" class="form-control valorAq" placeholder="R$0,00">
                            </div>
                            <div class="col-xs-2">
                                <label>Nota:</label>
                                <input type="text" name="nota" class="form-control dadosMaquina" >
                            </div>
                           
                            <div class="col-xs-2" style="margin-top: 24px; text-align: center;">                                    
                                <a class="btn btn-success" id="novoEquip">Inserir <i class="fa fa-check right"></i></a>                                    
                            </div>
                            <div class="col-xs-2" style="margin-top: 24px; text-align: right;">                
                                <a class="btn btn-danger" id="cancelCad">Cancelar</i></a>                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->
</body>

<!-- ================MODAL DE EDITAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="editar">
    <div class="modal-dialog modal-sm" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Editar / Excluir</span>
                </div>
                <div class="widget-body">
                    <div id="modalEditar"></div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL DE TRANSAÇÕES EDIT======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="transEditar">
    <div class="modal-dialog modal-sm" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Mais Informações / Excluir</span>
                </div>
                <div class="widget-body">
                    <div id="modalTransEditar"></div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL CONFIRMAÇÃO DELETAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="deletar">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Deseja mesmo Excluir ?</span>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-6">                
                            <a class="btn btn-success" id="btnConfirma" cod="<?php echo $_GET['id']?>">Sim&nbsp;&nbsp;&nbsp;<i class="fa fa-check right"></i></a>                
                        </div>
                        <div class="col-xs-6">                
                            <a class="btn btn-danger" id="btnCancela" cod="<?php echo $_GET['id']?>">Não&nbsp;&nbsp;&nbsp;<i class="fa fa-times"></i></a>                
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL CONFIRMAÇÃO DELETAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="deletarTrans">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Deseja mesmo Excluir ?</span>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-6">                
                            <a class="btn btn-success" id="btnConfirmaTrans" cod="<?php echo $_GET['id']?>">Sim&nbsp;&nbsp;&nbsp;<i class="fa fa-check right"></i></a>                
                        </div>
                        <div class="col-xs-6">                
                            <a class="btn btn-danger" id="btnCancelaTrans" cod="<?php echo $_GET['id']?>">Não&nbsp;&nbsp;&nbsp;<i class="fa fa-times"></i></a>                
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL ENVIANDO======================= -->
<button hidden class="btn-modal" data-toggle="modal" data-target="#modal-position">teste</button>

<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-position">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-12" style="text-align: center;">                
                            <b><h1 id="position" style="font-size: 12px;"></h1></b>
                        </div>                   
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim--> ,allowNegative: false, thousands:'.', decimal:','});
    
    // Search equipment functionality
    $('#btnBuscarEquip').click(function(){
        var tipo = $('#busca_tipo').val();
        var marca = $('#busca_marca').val();
        var nome = $('#busca_nome').val();
        var cod_func = $('#busca_cod_func').val();
        var status = $('#busca_status').val();
        var num_serie = $('#busca_num_serie').val();
        
        // Show loading message
        $('#resultadosBusca').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Buscando equipamentos...</div>');
        
        // Build query parameters
        var params = 'action=buscar_equipamento';
        if(tipo) params += '&tipo=' + encodeURIComponent(tipo);
        if(marca) params += '&marca=' + encodeURIComponent(marca);
        if(nome) params += '&nome=' + encodeURIComponent(nome);
        if(cod_func) params += '&cod_func=' + encodeURIComponent(cod_func);
        if(status) params += '&status=' + encodeURIComponent(status);
        if(num_serie) params += '&num_serie=' + encodeURIComponent(num_serie);
        
        $.ajax({
            url: 'buscar_equipamento.php',
            type: 'POST',
            data: params,
            success: function(response){
                $('#resultadosBusca').html(response);
                // Event binding is now handled in the AJAX response script
            },
            error: function(){
                $('#resultadosBusca').html('<div class="alert alert-danger">Erro ao buscar equipamentos. Tente novamente.</div>');
            }
        });
    });
    
    // Clear search results when changing filters
    $('#formBuscaEquip input, #formBuscaEquip select').change(function(){
        $('#grafico-container').hide();
    });
});

InitiateSimpleDataTable.init();
InitiateEditableDataTable.init();
InitiateExpandableDataTable.init();
InitiateSearchableDataTable.init();
</script>

<!-- Keep all existing modals -->
<!-- ================MODAL DE CADASTRO DE NOVOS EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="incluir">
    <div class="modal-dialog modal-sm" style="width: 70%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Novo Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Cadastrar</span>
                </div>
                <div class="widget-body">
                    <div class="row">                            
                        <div class="col-xs-12">   
                            <strong>Dados Esquipamentos</strong>
                            <hr style="margin-top: 0px;">
                            <div class="col-xs-2">
                                <label>Tipo:</label>
                                <select class="form-control dadosMaquina" name="tipo" id="tipo">
                                    <option selected disabled value="">Escolha o Tipo..</option>
                                    <option>DESKTOP</option>
                                    <option>NOTEBOOK</option>
                                    <option>DISPOSITIVOS</option>
                                </select>
                            </div>
                            <div class="col-xs-2">
                                <label>Marca:</label>
                                <input type="text" class="form-control dadosMaquina" name="marca" id="marca" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Modelo:</label>
                                <input type="text" class="form-control dadosMaquina" name="modelo" id="modelo" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Status:</label>
                                <select class="form-control dadosMaquina" name="sts_equip" id="sts_equip">
                                    <option selected disabled value="">Escolha o Tipo..</option>
                                    <option>DISPONIVEL</option>
                                    <option>EM USO</option>
                                    <option>RESERVADO</option>
                                    <option>PADRONIZAR</option>                                        
                                    <option>DESCARTE</option>
                                </select>
                            </div>                                
                        </div>
                    </div>
                    <div class="row" style="margin-top: 5px;">                            
                        <div class="col-xs-12">  
                            <div class="col-xs-2">
                                <label>Nº de Série</label>
                                <input type="text" class="form-control dadosMaquina" name="num_serie" id="num_serie" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Hostname:</label>
                                <input type="text" class="form-control dadosMaquina" name="hostname" placeholder="">
                            </div>
                            <div class="col-xs-2" hidden="" id="loc_instal_div">
                                <label>Local de Uso:</label>
                                <input type="text" class="form-control dadosMaquina" name="loc_instal" id="loc_instal" placeholder="">
                            </div>
                            <div class="col-xs-2" hidden="" id="cod_func_div">
                                <label>Codigo Funcional:</label>
                                <input type="text" class="form-control dadosMaquina" name="cod_func" id="cod_func" onkeyup="MascaraCodFunc(this,event);" placeholder="">
                            </div>
                            <div class="col-xs-4" hidden="" id="nome_func_fiv"></div>
                        </div>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="col-xs-12">
                            <strong>Dados Hardware/SoftWare</strong>
                            <hr>
                            <div class="col-xs-2">
                                <label>Processador:</label>
                                <input type="text" class="form-control dadosMaquina" name="processador" placeholder="Intel Core I7">
                            </div>
                            <div class="col-xs-2">
                                <label>RAM:</label>
                                <input type="text" class="form-control dadosMaquina" name="RAM" placeholder="8GB">
                            </div>
                             <div class="col-xs-2">
                                <label>HD:</label>
                                <input type="text" class="form-control dadosMaquina" name="HD" placeholder="500GB">
                            </div>
                            <div class="col-xs-2">
                                <label>Sistema Operacional:</label>
                                <input type="text" class="form-control dadosMaquina" name="sistem_op" placeholder="Windows 10">
                            </div>
                            <div class="col-xs-3">
                                <label>Aplicativos:</label>
                                <input type="text" class="form-control dadosMaquina" name="apps" placeholder="Pacote Office 2010, Cisco Any Connect">
                            </div>
                        </div>
                    </div>
                    <div class="row disp_mov" style="margin-top: 15px;" hidden>
                        <div class="col-xs-12">
                            <strong>Dados Dispositivo</strong>
                            <hr>
                            <div class="col-xs-2">
                                <label>IMEI:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="IMEI" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Nº CHIP:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="NUM_CHIP" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>OPERADORA:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="OPERADORA" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>DDD:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="DDD" placeholder="">
                            </div>
                            <div class="col-xs-2">
                                <label>Nº TEL:</label>
                                <input type="text" class="form-control dadosMaquina disp" name="TEL" placeholder="">
                            </div>
                        </div>

                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-xs-12">
                            <b>Dados Compra</b>
                            <hr>
                            <div class="col-xs-2">
                                <label>Nº SAP:</label>
                                <input type="text" name="num_sap" class="form-control dadosMaquina">
                            </div>
                            <div class="col-xs-2">
                                <label>Data da Aquisição:</label>
                                <input type="text" name="dt_compra" id="dt_compra" class="form-control" onkeyup="MascaraData(this,event);">
                            </div>
                             <div class="col-xs-2">
                                <label>Valor da Aquisição:</label>
                                <input type="text" name="val_compra" id="val_compra" class="form-control valorAq" placeholder="R$0,00">
                            </div>
                            <div class="col-xs-2">
                                <label>Nota:</label>
                                <input type="text" name="nota" class="form-control dadosMaquina" >
                            </div>
                           
                            <div class="col-xs-2" style="margin-top: 24px; text-align: center;">                                    
                                <a class="btn btn-success" id="novoEquip">Inserir <i class="fa fa-check right"></i></a>                                    
                            </div>
                            <div class="col-xs-2" style="margin-top: 24px; text-align: right;">                
                                <a class="btn btn-danger" id="cancelCad">Cancelar</i></a>                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->
</body>

<!-- ================MODAL DE EDITAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="editar">
    <div class="modal-dialog modal-sm" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Editar / Excluir</span>
                </div>
                <div class="widget-body">
                    <div id="modalEditar"></div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL DE TRANSAÇÕES EDIT======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="transEditar">
    <div class="modal-dialog modal-sm" style="width: 80%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Equipamento</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Mais Informações / Excluir</span>
                </div>
                <div class="widget-body">
                    <div id="modalTransEditar"></div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL CONFIRMAÇÃO DELETAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="deletar">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Deseja mesmo Excluir ?</span>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-6">                
                            <a class="btn btn-success" id="btnConfirma" cod="<?php echo $_GET['id']?>">Sim&nbsp;&nbsp;&nbsp;<i class="fa fa-check right"></i></a>                
                        </div>
                        <div class="col-xs-6">                
                            <a class="btn btn-danger" id="btnCancela" cod="<?php echo $_GET['id']?>">Não&nbsp;&nbsp;&nbsp;<i class="fa fa-times"></i></a>                
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL CONFIRMAÇÃO DELETAR EQUIPAMENTOS======================= -->
<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="deletarTrans">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-header bordered-top bordered-palegreen">
                    <span class="widget-caption">Deseja mesmo Excluir ?</span>
                </div>
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-6">                
                            <a class="btn btn-success" id="btnConfirmaTrans" cod="<?php echo $_GET['id']?>">Sim&nbsp;&nbsp;&nbsp;<i class="fa fa-check right"></i></a>                
                        </div>
                        <div class="col-xs-6">                
                            <a class="btn btn-danger" id="btnCancelaTrans" cod="<?php echo $_GET['id']?>">Não&nbsp;&nbsp;&nbsp;<i class="fa fa-times"></i></a>                
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->

<!-- ================MODAL ENVIANDO======================= -->
<button hidden class="btn-modal" data-toggle="modal" data-target="#modal-position">teste</button>

<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="modal-position">
    <div class="modal-dialog modal-sm" style="width: 13%; margin-top: 300px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
                <h4 class="modal-title" id="mySmallModalLabel">Alerta !!</h4>
            </div>
            <div class="modal-body">
                <div class="widget-body">
                    <div class="row">
                        <div class="col-xs-12" style="text-align: center;">                
                            <b><h1 id="position" style="font-size: 12px;"></h1></b>
                        </div>                   
                    </div>
                </div>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!--Fim-->
