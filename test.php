## Fix: Ocorrências showing wrong data

**In JH.txt (ajax_encerramento.php)**, fix the load_ocorrencias handler:

```php
// Load Ocorrências accordion view
if ((isset($_POST['acao']) && $_POST['acao'] == 'load_ocorrencias') || 
    (isset($_GET['acao']) && $_GET['acao'] == 'load_ocorrencias')) {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
        
        $where = '';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = addslashes($_GET['search']);
            $where .= " AND (CAST(CHAVE_LOTE AS VARCHAR) LIKE '%$search%' OR CNPJs LIKE '%$search%' OR OCORRENCIA LIKE '%$search%')";
        }
        
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where .= " AND DT_OCORRENCIA >= '" . $_GET['data_inicio'] . "'";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where .= " AND DT_OCORRENCIA <= '" . $_GET['data_fim'] . " 23:59:59'";
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
        $offset = ($page - 1) * $perPage;
        
        $model = new Analise();
        
        $totalRecords = $model->getTotalOcorrenciasLotes($where);
        $totalPages = ceil($totalRecords / $perPage);
        $dados = $model->getOcorrenciasLotes($where, $perPage, $offset);
        
        $controller = new AnaliseEncerramentoController();
        $html = $controller->renderOcorrenciasAccordions($dados);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'html' => $html,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage,
            'startRecord' => $offset + 1,
            'endRecord' => min($offset + $perPage, $totalRecords)
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
```

**In J.txt**, update filter functions to be view-aware:

```javascript
function handleFormSubmit() {
    if (currentView === 'ocorrencias') {
        loadOcorrencias();
    } else {
        loadSolicitacoes();
    }
}

function loadSolicitacoes() {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }
    
    if (currentSort.column) {
        params.append('sort_column', currentSort.column);
        params.append('sort_direction', currentSort.direction);
    }
    
    params.append('page', currentPage);
    params.append('per_page', perPage);
    
    showLoading();
    
    fetch(AJAX_URL + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateUI(data, params);
            } else {
                throw new Error(data.error || 'Erro ao carregar dados');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showNotification('Erro ao carregar os dados: ' + error.message, 'error');
        })
        .finally(() => hideLoading());
}

function switchToOcorrenciasView() {
    currentView = 'ocorrencias';
    currentPage = 1; // Reset to first page
    updateTableHeaders();
    loadOcorrencias();
    
    // Update header styling
    document.getElementById('headerSolicitacoes').classList.remove('active');
    document.getElementById('headerOcorrencias').classList.add('active');
    
    // Hide bulk actions for ocorrencias view
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) bulkActions.style.display = 'none';
}

function switchToSolicitacoesView() {
    currentView = 'solicitacoes';
    currentPage = 1; // Reset to first page
    updateTableHeaders();
    loadSolicitacoes();
    
    // Update header styling
    document.getElementById('headerOcorrencias').classList.remove('active');
    document.getElementById('headerSolicitacoes').classList.add('active');
}
```

**Update initializeEventListeners to use view-aware submit:**

```javascript
function initializeEventListeners() {
    const elements = {
        clearFilters: () => clearAllFilters(),
        searchBtn: () => { currentPage = 1; handleFormSubmit(); },
        applyFiltersBtn: () => { currentPage = 1; handleFormSubmit(); },
        perPageSelect: function() { perPage = parseInt(this.value); currentPage = 1; handleFormSubmit(); },
        prevPage: () => { if (currentPage > 1) { currentPage--; handleFormSubmit(); } },
        nextPage: () => { if (currentPage < totalPages) { currentPage++; handleFormSubmit(); } }
    };

    Object.entries(elements).forEach(([id, handler]) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', handler);
    });

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentPage = 1;
                handleFormSubmit();
            }
        });
    }
}
```

**Update clearAllFilters:**

```javascript
function clearAllFilters() {
    ['searchInput', 'bloqueioFilter', 'orgaoPagadorFilter', 'statusSolicFilter', 'motivoEncFilter', 'clusterSelecFilter',
     'dataInicioFilter', 'dataInicioDisplay', 'dataFimFilter', 'dataFimDisplay']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    
    currentPage = 1;
    handleFormSubmit();
}
```

These changes ensure the correct data loads for each view and filters work properly in both contexts.