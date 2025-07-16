<?php
session_start();

if($_SESSION['cod_usu']=='')
{   
    session_destroy(); ?>
    <script>
        window.location='login.php';
    </script>
<?php
    die();
}

include_once('../control/consulta_solicitacoes.controller.php');
include_once('../control/solicitacao_cadastro.controller.php');
require_once('../control/Wxkd_Config.php');
require_once('Wxkd_DashboardController.php');

$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controller = new Wxkd_DashboardController();

switch($action) {
    case 'exportCSV':
        $controller->exportCSV();
        break;
    case 'exportTXT':
        $controller->exportTXT();
        break;
    case 'exportAccess':  
        $controller->exportAccess();
        break;
    case 'ajaxGetTableData':
        $controller->ajaxGetTableData();
        break;
    default:
        $data = $controller->index();
        $cardData = $data['cardData'];
        $tableData = $data['tableData'];
        $activeFilter = $data['activeFilter'];
        $contractChavesLookup = $data['contractChavesLookup'];
        break;
}
?>

<div class="row">
    <div class="col-lg-12 col-sm-12 col-xs-12">
        <head><link rel="stylesheet" href="../view/assets/css/Wxkd_style.css"></head>
        <body>
            <div>
                <!-- Cards -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-sm-6 col-xs-12">
                        <div class="card card-filter databox databox-lg radius-bordered databox-shadowed databox-graded" data-filter="cadastramento" id="card-cadastramento" style="cursor:pointer;">
                            <div class ="databox-left bg-palegreen">
                                <span class="databox-number white" style="position:relative; top:11px; font-size:30px;"><?php echo $cardData['cadastramento']; ?></span>	
                            </div>	
                            <div class="databox-right">
                                <span class="databox-text" style="font-size:20px;  font-color:rgb(104, 104, 104)">Cadastramento</span>
                                <div class="databox-text" style="font-size:10px; font-color: #505050">Lojas para Cadastrar</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-xs-12">
                        <div class="card card-filter databox databox-lg radius-bordered databox-shadowed databox-graded" data-filter="descadastramento" id="card-descadastramento" style="cursor:pointer;">
                            <div class ="databox-left bg-sky">
                                <span class="databox-number white" style="position:relative; top:11px; font-size:30px;"><?php echo $cardData['descadastramento']; ?></span>	
                            </div>	
                            <div class="databox-right">
                                <span class="databox-text" style="font-size:20px;  font-color:rgb(104, 104, 104)">Descadastramento</span>
                                <div class="databox-text" style="font-size:10px; font-color: #505050">Lojas para Descadastrar</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-xs-12">
                        <div  class="card card-filter databox databox-lg radius-bordered databox-shadowed databox-graded" data-filter="historico" id="card-historico" style="cursor:pointer;">
                            <div class ="databox-left bg-orange" >
                                <span style="width:fit-content; position:relative; top:12px;" class="databox-number white" style="position:relative; top:11px; font-size:30px;"><?php echo $cardData['historico']; ?></span>	
                            </div>	
                            <div class="databox-right">
                                <span class="databox-text" style="font-size:20px;  font-color:rgb(104, 104, 104)">Hist&oacute;rico</span>
                                <div class="databox-text" style="font-size:10px; font-color: #505050">Processos Concluidos</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Indicators -->
                <div class="row mb-3" id="filterIndicator" style="display: none;">
                    <div class="col-12">
                        <div class="alert alert-info fade in">
                            <button class="close" style="font-color:rgb(54, 150, 198) !important; opacity:0.3; position:relative; top:5px;" onclick="clearFilter()">
                                <i class="fa-fw fa fa-times"></i>
                            </button>
                            <span>
                                <i class="fa-fw fa fa-info" style="font-size:20px !important; position:relative; top:4px;"></i>
                                <strong>Filtro ativo:</strong> <span id="activeFilterName"></span>
                                <i class="ms-2" id="filterDescription"></i>
                            </span>
                            <button type="button" class="btn btn-info tooltip-info" style="position:relative;left:10px; background-color:rgb(54, 150, 198) !important;" onclick="clearFilter()">
                                Limpar Filtro
                            </button>
                        </div>
                    </div>
                </div>
                <div id="statusFilterIndicator" style="display: none; ">
                    <div class="col-12">
                        <div class="alert alert-info ">
                            <button class="close" style="font-color:rgb(54, 150, 198) !important; opacity:0.3; position:relative; top:5px;" onclick="clearFilter()">
                            </button>
                            <span>
                                <i class="fa-fw fa fa-info" style="font-size:15px !important; position:relative; top:2px;"></i>
                                <strong>Filtro Correspondente ativo:</strong> <span id="activeStatusFilters"></span>
                                <i class="ms-2" id="filterDescription"></i>
                            </span>
                        </div>
                    </div>
                </div>
            
                <div style="position:relative; top:30px;">
                    <div class="widget-header"><span class="widget-caption">WXKD DataTable</span></div>	
                    <div class="widget-body">
                        <div class="dataTables_wrapper form-inline no-footer">
                            <div style="display:flex;">
                                <div class="col-md-8">
                                    <div class="align-items-center gap-2">
                                        <div class="dataTables_filter" style="position:relative;margin-top:10px;">
                                            <label>
                                                <input style="heigth:100px; width:250px;" type="search" class="form-control input-sm" id="searchInput" placeholder aria-controls="simpledatatable"  >
                                            </label>
                                        </div>
                                        
                                        <div style="position:flex;margin-top: 15px; margin-bottom: 10px;">
                                            <label class="me-2 text-sm" style="padding-left:20px;"></label>
                                            <button type="button" class="status-filter-btn" 
                                                    style="margin-right:5px;padding:0px;border:0px;" 
                                                    id="filterAV" data-field="AVANCADO" onclick="toggleStatus(this)">
                                                <span class="status-indicator" 
                                                        style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
                                                    AV
                                                </span>
                                            </button>
                                            <button type="button" class="status-filter-btn" 
                                                    style="margin-right:5px;padding:0px;border:0px;" 
                                                    id="filterOP" data-field="ORGAO_PAGADOR" onclick="toggleStatus(this)">
                                                <span class="status-indicator" 
                                                        style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
                                                    OP
                                                </span>
                                            </button>
                                            <button type="button" class="status-filter-btn" 
                                                    style="margin-right:5px;padding:0px;border:0px;" 
                                                    id="filterPR" data-field="PRESENCA" onclick="toggleStatus(this)">
                                                <span class="status-indicator" 
                                                        style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
                                                    PR
                                                </span>
                                            </button>
                                            <button type="button" class="status-filter-btn" 
                                                    style="margin-right:5px;padding:0px;border:0px;" 
                                                    id="filterUN" data-field="UNIDADE_NEGOCIO" onclick="toggleStatus(this)">
                                                <span class="status-indicator" 
                                                        style="display:grid;width:30px;height:30px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:gray;border-radius:4px;">
                                                    UN
                                                </span>
                                            </button>
                                        </div>
                                        
                                        <button style="margin-bottom:10px;position:relative; left:20px;" type="button" class="btn btn-success btn-sm" onclick="exportSelectedAccess()">
                                            Exportar Access
                                        </button>
                                        <button style="margin-bottom:10px;position:relative; left:20px;" type="button" class="btn btn-success btn-sm" onclick="exportAllCSV()">
                                            Exportar CSV
                                        </button>
                                        <button style="margin-bottom:10px;position:relative; left:20px;" type="button" class="btn btn-info btn-sm" onclick="exportSelectedTXT()" id="exportTxtBtn" disabled>
                                            Exportar TXT (<span id="selectedCount">0</span>)
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-4" style="position:relative; left:25%;" >
                                    <div class="d-flex justify-content-end align-items-center"  >
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
                            
                            <div>
                                <div>
                                    <div class="table-scrollable" >
                                        <table class="table table-striped table-bordered table-hover dataTable no-footer" id="dataTableAndre">
                                            <thead>
                                                <tr>
                                                    <th class="checkbox-column" >
                                                        <label>
                                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                                            <span class="text"></span>
                                                        </label>
                                                    </th>
                                                    <th class="sortable" data-column="0">Chave_Loja</th>
                                                    <th class="sortable" data-column="1">Nome_Loja</th>
                                                    <th class="sortable" data-column="2">Cod_Empresa</th>
                                                    <th class="sortable" data-column="3">Cod_Loja</th>
                                                    <th class ="sortable" data-column="4">QUANT_LOJAS</th>
                                                    <th class="sortable" data-column="5">TIPO_CORRESPONDENTE</th>
                                                    <th class="sortable" data-column="6">DATA_CONCLUSAO</th>
                                                    <th class="sortable" data-column="7">DATA_SOLICITACAO</th>
                                                    <th class="sortable" data-column="8">Dep_Dinheiro</th>
                                                    <th class="sortable" data-column="9">Dep_Cheque</th>
                                                    <th class="sortable" data-column="10">Rec_Retirada</th>
                                                    <th class="sortable" data-column="11">Saque_Cheque</th>
                                                    <th class="sortable" data-column="12">2Via_Cartao</th>
                                                    <th class="sortable" data-column="13">Holerite_INSS</th>
                                                    <th class="sortable" data-column="14">Cons_Inss</th>
                                                    <th class="sortable" data-column="15">Data_Contrato</th>
                                                    <th class="sortable" data-column="16">Tipo_Contrato</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                    if (is_array($tableData) && !empty($tableData)) {
                                                        $counter = 1;
                                                        foreach ($tableData as $row):
                                                            $chaveLoja = $row['Chave_Loja'];
                                                            $highlight = !isset($contractChavesLookup[$chaveLoja]) ? 'background-color: #f4b400;' : '';
                                                            if (is_array($row)) {
                                                ?>
                                                <tr>
                                                    <td class="checkbox-column" >
                                                        <label>
                                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?php echo $counter ?>" id="row_<?php echo $counter; ?>"/>
                                                            <span class="text"></span>
                                                        </label>
                                                        <?php
                                                        $validationError = $controller->getValidationError($row);
                                                        if ($validationError):
                                                        ?>
                                                        <i class = "fa fa-lock" style="color:#d9534f; margin-left: 5px; cursor: help;"
                                                        title="Essa Loja nao pode ser exportada como txt por: <?php echo htmlspecialchars($validationError); ?>"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo isset($row['Chave_Loja']) ? htmlspecialchars($row['Chave_Loja']) : ''; ?></td>
                                                    <td><?php echo isset($row['Nome_Loja']) ? htmlspecialchars($row['Nome_Loja']) : ''; ?></td>
                                                    <td><?php echo isset($row['Cod_Empresa']) ? htmlspecialchars($row['Cod_Empresa']) : ''; ?></td>
                                                    <td><?php echo isset($row['Cod_Loja']) ? htmlspecialchars($row['Cod_Loja']) : ''; ?></td>
                                                    <td><?php echo isset($row['QUANT_LOJAS']) ? htmlspecialchars($row['QUANT_LOJAS']) : ''; ?></td>
                                                    
                                                    <!-- Status indicators -->
                                                    <td>
                                                        <div class="status-container">
                                                            <?php
                                                            $fields = Wxkd_Config::getFieldMappings();
                                                            $cutoff = Wxkd_Config::getCutoffTimestamp();

                                                            foreach ($fields as $field => $label) {
                                                                $raw = isset($row[$field]) ? trim($row[$field]) : '';
                                                                
                                                                if (!empty($raw)) {
                                                                    $parts = explode('/', $raw);
                                                                    if (count($parts) == 3) {
                                                                        $day = (int)$parts[0];
                                                                        $month = (int)$parts[1];
                                                                        $year = (int)$parts[2];
                                                                        
                                                                        if (checkdate($month, $day, $year)) {
                                                                            $timestamp = mktime(0, 0, 0, $month, $day, $year);
                                                                        } else {
                                                                            $timestamp = false;
                                                                        }
                                                                    } else {
                                                                        $timestamp = false;
                                                                    }
                                                                } else {
                                                                    $timestamp = false;
                                                                }
                                                                
                                                                $isOn = $timestamp !== false && $timestamp > $cutoff;
                                                                $color = $isOn ? 'green' : 'gray';
                                                                $status = $isOn ? 'active' : 'inactive';
                                                                
                                                                echo '<div style="display:inline-block;width:30px;height:30px;
                                                                            margin-right:5px;text-align:center;line-height:30px;
                                                                            font-size:10px;font-weight:bold;color:white;
                                                                            background-color:' . $color . ';border-radius:4px;" 
                                                                            data-field="' . $field . '" data-status="' . $status . '">' . $label . '</div>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </td>
                                                    
                                                    <!-- Data fields with validation highlighting -->
                                                    <?php
                                                    // Generate validation highlighting for the remaining fields
                                                    $validationFields = array(
                                                        'Dep_Dinheiro' => 'DEP_DINHEIRO_VALID',
                                                        'Dep_Cheque' => 'DEP_CHEQUE_VALID', 
                                                        'Rec_Retirada' => 'REC_RETIRADA_VALID',
                                                        'Saque_Cheque' => 'SAQUE_CHEQUE_VALID',
                                                        '2Via_Cartao' => 'SEGUNDA_VIA_CARTAO_VALID',
                                                        'Holerite_INSS' => 'HOLERITE_INSS_VALID',
                                                        'Cons_INSS' => 'CONSULTA_INSS_VALID'
                                                    );
                                                    
                                                    // Helper function for field rendering
                                                    function renderValidationField($row, $fieldName, $validationField, $limits = null) {
                                                        $bgColor = empty($row[$validationField]) ? '#ffb7bb' : 'transparent';
                                                        $value = isset($row[$fieldName]) ? htmlspecialchars($row[$fieldName]) : '';
                                                        
                                                        if ($limits && isset($row[$validationField]) && isset($row['TIPO_LIMITES'])) {
                                                            $isPresencaOrOrgao = (strpos($row['TIPO_LIMITES'], 'PRESENCA') !== false || 
                                                                            strpos($row['TIPO_LIMITES'], 'ORG_PAGADOR') !== false);
                                                            $isAvancadoOrApoio = (strpos($row['TIPO_LIMITES'], 'AVANCADO') !== false || 
                                                                            strpos($row['TIPO_LIMITES'], 'UNIDADE_NEGOCIO') !== false);
                                                            
                                                            if ($row[$validationField] == 1) {
                                                                if ($isPresencaOrOrgao) {
                                                                    $value = $limits['presenca'];
                                                                } elseif ($isAvancadoOrApoio) {
                                                                    $value = $limits['avancado'];
                                                                } else {
                                                                    $value = '0';
                                                                }
                                                            } else {
                                                                if ($isPresencaOrOrgao) {
                                                                    $value = $limits['presenca'];
                                                                } elseif ($isAvancadoOrApoio) {
                                                                    $value = $limits['avancado'];
                                                                } else {
                                                                    $value = '0';
                                                                }
                                                                $bgColor = '#ffb7bb';
                                                            }
                                                        } elseif ($fieldName === '2Via_Cartao' || $fieldName === 'Holerite_INSS' || $fieldName === 'Cons_INSS') {
                                                            if (isset($row[$validationField])) {
                                                                $value = ($row[$validationField] === 1) ? 'Apto' : 'Nao Apto';
                                                            }
                                                        }
                                                        
                                                        echo '<td style="background-color: ' . $bgColor . '; text-align: center; vertical-align: middle;">' . $value . '</td>';
                                                    }
                                                    
                                                    // Render all validation fields
                                                    renderValidationField($row, 'Dep_Dinheiro', 'DEP_DINHEIRO_VALID', array('presenca' => 'R$ 3.000,00', 'avancado' => 'R$ 10.000,00'));
                                                    renderValidationField($row, 'Dep_Cheque', 'DEP_CHEQUE_VALID', array('presenca' => 'R$ 5.000,00', 'avancado' => 'R$ 10.000,00'));
                                                    renderValidationField($row, 'Rec_Retirada', 'REC_RETIRADA_VALID', array('presenca' => 'R$ 2.000,00', 'avancado' => 'R$ 3.500,00'));
                                                    renderValidationField($row, 'Saque_Cheque', 'SAQUE_CHEQUE_VALID', array('presenca' => 'R$ 2.000,00', 'avancado' => 'R$ 3.500,00'));
                                                    renderValidationField($row, '2Via_Cartao', 'SEGUNDA_VIA_CARTAO_VALID');
                                                    renderValidationField($row, 'Holerite_INSS', 'HOLERITE_INSS_VALID');
                                                    renderValidationField($row, 'Cons_INSS', 'CONSULTA_INSS_VALID');
                                                    ?>
                                                    
                                                    <!-- Contract fields with highlighting -->
                                                    <td style="<?php echo $highlight; ?> text-align: center; vertical-align: middle;">
                                                        <?php
                                                        if (empty($row['DATA_CONTRATO'])) {
                                                            echo '<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>';
                                                        } else {
                                                            $timestamp = strtotime($row['DATA_CONTRATO']);
                                                            echo $timestamp !== false ? date('d/m/Y', $timestamp) : htmlspecialchars($row['DATA_CONTRATO']);
                                                        }
                                                        ?>
                                                    </td>

                                                    <td style="<?php echo $highlight; ?> text-align: center; vertical-align: middle;">
                                                        <?php
                                                        if (empty($row['TIPO_CONTRATO'])) {
                                                            echo '<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>';
                                                        } else {
                                                            echo htmlspecialchars($row['TIPO_CONTRATO']);
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                                $counter++; 
                                                }
                                            endforeach; 
                                                } else {
                                                    echo '<tr><td colspan="12" class="text-center text-muted">';
                                                    echo 'Nenhum dado encontrado. Tipo de dados: ' . gettype($tableData);
                                                    if (is_string($tableData)) {
                                                        echo ' | Conteúdo: ' . htmlspecialchars(substr($tableData, 0, 100));
                                                    }
                                                    echo '</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center" style="position:relative; left:20px;">
                                        <div class="pagination-info text-sm text-muted">
                                            Mostrando <span id="showingStart">1</span> ate <span id="showingEnd">15</span> de <span id="totalItems">0</span> registros
                                        </div>
                                        <nav aria-label="Navegação da tabela">
                                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>			
            </div>		
                
            <script src="../view/assets/js/Wxkd_script.js"></script>
            <script>
                window.currentFilter = '<?php echo isset($activeFilter) ? $activeFilter : "all"; ?>';
            </script>
            <script>
            function toggleStatus(button) {
                const span = button.querySelector('.status-indicator');
                const isGreen = span.style.backgroundColor === 'rgb(33, 136, 56)' || span.style.backgroundColor === '#218838';

                span.style.backgroundColor = isGreen ? 'gray' : '#218838';
            }
            </script>
            <script type="text/javascript">
                var contractChaves = [<?php
                    $first = true;
                    foreach (array_keys($contractChavesLookup) as $chave) {
                        if (!$first) echo ', ';
                        echo "'" . addslashes($chave) . "'";
                        $first = false;
                    }
                ?>];
                console.log(contractChaves)
            </script>

        </body>
    </div>
</div>