<?php
// views/Wxkd_dashboard.php - Main Controller + View
require_once '../models/Wxkd_DashboardModel.php';

class Wxkd_DashboardController {
    private $model;
    
    public function __construct() {
        $this->model = new Wxkd_DashboardModel();
        $this->model->Wxkd_Construct(); // Call your custom constructor
    }
    
    public function index() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        // Buscar dados dos cards
        $cardData = $this->model->getCardData();
        
        // Buscar dados da tabela conforme filtro
        $tableData = $this->model->getTableDataByFilter($filter);
        
        return ['cardData' => $cardData, 'tableData' => $tableData, 'activeFilter' => $filter];
    }
    
    public function exportXML() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $tableData = $this->model->getTableDataByFilter($filter);
        
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="dados_tabela.xml"');
        
        echo $this->model->generateXML($tableData);
        exit;
    }
    
    public function exportTXT() {
        $selectedIds = isset($_POST['selectedIds']) ? $_POST['selectedIds'] : [];
        $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
        
        if (empty($selectedIds)) {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => 'Nenhuma linha selecionada']);
            exit;
        }
        
        // Buscar dados selecionados
        $tableData = $this->model->getSelectedTableData($selectedIds, $filter);
        
        // Processar movimentação para histórico (apenas para cadastramento e descadastramento)
        if ($filter === 'cadastramento' || $filter === 'descadastramento') {
            $this->model->moveToHistory($selectedIds, $filter);
        }
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="dados_convertidos.txt"');
        
        echo $this->model->generateSpecificTXT($tableData);
        exit;
    }
    
    public function ajaxGetTableData() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $tableData = $this->model->getTableDataByFilter($filter);
        $cardData = $this->model->getCardData();
        
        // Create simple XML response without headers
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<response>';
        echo '<success>true</success>';
        
        // Card data
        echo '<cardData>';
        if (is_array($cardData)) {
            echo '<cadastramento>' . (isset($cardData['cadastramento']) ? $cardData['cadastramento'] : 0) . '</cadastramento>';
            echo '<descadastramento>' . (isset($cardData['descadastramento']) ? $cardData['descadastramento'] : 0) . '</descadastramento>';
            echo '<historico>' . (isset($cardData['historico']) ? $cardData['historico'] : 0) . '</historico>';
        } else {
            echo '<cadastramento>0</cadastramento>';
            echo '<descadastramento>0</descadastramento>';
            echo '<historico>0</historico>';
        }
        echo '</cardData>';
        
        // Table data - check if it's an array before foreach
        echo '<tableData>';
        if (is_array($tableData) && !empty($tableData)) {
            foreach ($tableData as $row) {
                if (is_array($row)) {
                    echo '<row>';
                    foreach ($row as $key => $value) {
                        $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
                        echo '<' . $cleanKey . '>' . htmlspecialchars($value) . '</' . $cleanKey . '>';
                    }
                    echo '</row>';
                }
            }
        } else {
            // If no data or not an array, add a debug message
            echo '<debug>No data found or invalid data format. TableData type: ' . gettype($tableData) . '</debug>';
        }
        echo '</tableData>';
        
        echo '</response>';
        exit;
    }
}

// Roteamento simples
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controller = new Wxkd_DashboardController();

switch($action) {
    case 'exportXML':
        $controller->exportXML();
        break;
    case 'exportTXT':
        $controller->exportTXT();
        break;
    case 'ajaxGetTableData':
        $controller->ajaxGetTableData();
        break;
    default:
        $data = $controller->index();
        $cardData = $data['cardData'];
        $tableData = $data['tableData'];
        $activeFilter = $data['activeFilter'];
        break;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/Wxkd_style.css">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Dashboard</h1>
            </div>
        </div>
        
        <!-- Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center card-filter" data-filter="cadastramento" id="card-cadastramento">
                    <div class="card-body">
                        <h5 class="card-title">Cadastramento</h5>
                        <h2 class="card-text text-primary"><?php echo $cardData['cadastramento']; ?></h2>
                        <small class="text-muted">Lojas para cadastrar</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center card-filter" data-filter="descadastramento" id="card-descadastramento">
                    <div class="card-body">
                        <h5 class="card-title">Descadastramento</h5>
                        <h2 class="card-text text-success"><?php echo $cardData['descadastramento']; ?></h2>
                        <small class="text-muted">Lojas para descadastrar</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center card-filter" data-filter="historico" id="card-historico">
                    <div class="card-body">
                        <h5 class="card-title">Histórico</h5>
                        <h2 class="card-text text-warning"><?php echo $cardData['historico']; ?></h2>
                        <small class="text-muted">Processos realizados</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Indicador de Filtro Ativo -->
        <div class="row mb-3" id="filterIndicator" style="display: none;">
            <div class="col-12">
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>
                        <strong>Filtro ativo:</strong> <span id="activeFilterName"></span>
                        <small class="ms-2" id="filterDescription"></small>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearFilter()">
                        Limpar Filtro
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Área de Controles e Tabela -->
        <div class="table-container">
            <!-- Controles Superiores -->
            <div class="row mb-3 align-items-center">
                <!-- Pesquisa e Botões -->
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2">
                        <div class="search-container">
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Pesquisar na tabela...">
                            <svg class="search-icon" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportData('xml')">
                            Exportar XML
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="exportData('txt')" id="exportTxtBtn" disabled>
                            Exportar TXT (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                </div>
                
                <!-- Seletor de Itens por Página -->
                <div class="col-md-4">
                    <div class="d-flex justify-content-end align-items-center">
                        <label for="itemsPerPage" class="me-2 text-sm">Mostrar:</label>
                        <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto;">
                            <option value="15">15</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                        </select>
                        <span class="ms-2 text-sm">itens</span>
                    </div>
                </div>
            </div>
            
            <!-- Tabela -->
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive-horizontal">
                        <table class="table table-striped table-hover" id="dataTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th class="sortable" data-column="0">ID</th>
                                    <th class="sortable" data-column="1">Nome</th>
                                    <th class="sortable" data-column="2">Email</th>
                                    <th class="sortable" data-column="3">Telefone</th>
                                    <th class="sortable" data-column="4">Cidade</th>
                                    <th class="sortable" data-column="5">Estado</th>
                                    <th class="sortable" data-column="6">Data Cadastro</th>
                                    <th class="sortable" data-column="7">Status</th>
                                    <th class="sortable" data-column="8">Tipo</th>
                                    <th class="sortable" data-column="9">Categoria</th>
                                    <th class="sortable" data-column="10">Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (is_array($tableData) && !empty($tableData)) {
                                    foreach ($tableData as $row): 
                                        if (is_array($row)) {
                                ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <input type="checkbox" class="form-check-input row-checkbox" data-row-id="<?php echo isset($row['id']) ? $row['id'] : ''; ?>">
                                    </td>
                                    <td><?php echo isset($row['id']) ? htmlspecialchars($row['id']) : ''; ?></td>
                                    <td><?php echo isset($row['nome']) ? htmlspecialchars($row['nome']) : ''; ?></td>
                                    <td><?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?></td>
                                    <td><?php echo isset($row['telefone']) ? htmlspecialchars($row['telefone']) : ''; ?></td>
                                    <td><?php echo isset($row['cidade']) ? htmlspecialchars($row['cidade']) : ''; ?></td>
                                    <td><?php echo isset($row['estado']) ? htmlspecialchars($row['estado']) : ''; ?></td>
                                    <td><?php echo isset($row['data_cadastro']) ? htmlspecialchars($row['data_cadastro']) : ''; ?></td>
                                    <td>
                                        <?php 
                                        $status = isset($row['status']) ? $row['status'] : '';
                                        $badgeClass = $status == 'Ativo' ? 'bg-success' : 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($row['tipo']) ? htmlspecialchars($row['tipo']) : ''; ?></td>
                                    <td><?php echo isset($row['categoria']) ? htmlspecialchars($row['categoria']) : ''; ?></td>
                                    <td><?php echo isset($row['observacoes']) ? htmlspecialchars($row['observacoes']) : ''; ?></td>
                                </tr>
                                <?php 
                                        }
                                    endforeach; 
                                } else {
                                    // Show debug information if no data
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
            
            <!-- Paginação -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="pagination-info text-sm text-muted">
                            Mostrando <span id="showingStart">1</span> até <span id="showingEnd">15</span> de <span id="totalItems">0</span> registros
                        </div>
                        <nav aria-label="Navegação da tabela">
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <!-- Paginação será gerada pelo JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // Variáveis globais
        window.currentFilter = '<?php echo isset($activeFilter) ? $activeFilter : "all"; ?>';
    </script>
    <script src="../assets/Wxkd_script.js"></script>
</body>
</html>