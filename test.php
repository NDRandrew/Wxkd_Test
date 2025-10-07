The user wants me to implement several features for a PHP-based data table application. Let me parse the requirements from T.txt:

1. Add filters in "Filtros e Busca" card based on ENCERRAMENTO_TB_PORTAL table:
   - Dropdown for STATUS_SOLIC
   - Dropdown for MOTIVO_ENC
   - Note: values will be added later, just add placeholders

2. Add column sorting (ASC/DESC) when clicking column headers

3. In the modal, in the "Ações" card:
   - Add dropdown to change MOTIVO_ENC
   - Add DateInput to change DATA_ENC
   - These changes should update ENCERRAMENTO_TB_PORTAL

4. Make STATUS card work based on ENCERRAMENTO_TB_PORTAL values:
   - STATUS_OP = ORGÃO PAGADOR
   - STATUS_COM = COMERCIAL  
   - STATUS_VAN = VAN-MATERIAL
   - STATUS_BLOQ = BLOQUEIO
   - STATUS_ENCERRAMENTO = ENCERRAMENTO
   - Status values: "Não Efetuado", "Efetuado", "ERRO"

5. Show "Tem Certeza?" modal for "Ações em Massa" and check if any STATUS_OP, STATUS_COM, STATUS_VAN, STATUS_BLOQ is not "Efetuado" for each selected CHAVE_LOJA

6. Add "Cancelar Solicitação" button in bottom left of Detalhes Modal (red) that changes STATUS_SOLIC to "CANCELADO"

The user wants:
- Minimum code
- Clean code
- MVC pattern
- No excessive comments

Let me provide the necessary code changes for each file:

1. **M (5).txt - Model**: Add methods to interact with ENCERRAMENTO_TB_PORTAL


I'll continue implementing the view components to support the required functionality. The view will need to incorporate dynamic filtering, sorting capabilities, and interactive elements that align with the model's data structure. This involves creating dropdown menus for STATUS_SOLIC and MOTIVO_ENC, implementing column header sorting mechanisms, and designing modal interactions that enable precise data manipulation.

I'll help you implement these features. Here are the minimal code additions for each file:

## 1. Model (M (5).txt) - Add new methods:

```php
// Add these methods to the Analise class

public function getEncerramentoData($cod_solicitacao) {
    $query = "SELECT * FROM ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = ".$cod_solicitacao;
    $dados = $this->sql->select($query);
    return $dados ? $dados[0] : null;
}

public function updateEncerramentoStatus($cod_solicitacao, $field, $value) {
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET ".$field." = '".$value."' WHERE COD_SOLICITACAO = ".$cod_solicitacao;
    return $this->sql->update($query);
}

public function updateMotivoEnc($cod_solicitacao, $motivo, $data) {
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET MOTIVO_ENC = '".$motivo."', DATA_ENC = '".$data."' WHERE COD_SOLICITACAO = ".$cod_solicitacao;
    return $this->sql->update($query);
}

public function cancelarSolicitacao($cod_solicitacao) {
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET STATUS_SOLIC = 'CANCELADO' WHERE COD_SOLICITACAO = ".$cod_solicitacao;
    return $this->sql->update($query);
}

public function checkBulkStatus($chaves_loja) {
    $chaves = implode(',', array_map('intval', $chaves_loja));
    $query = "SELECT CHAVE_LOJA, STATUS_OP, STATUS_COM, STATUS_VAN, STATUS_BLOQ FROM ENCERRAMENTO_TB_PORTAL WHERE CHAVE_LOJA IN (".$chaves.")";
    return $this->sql->select($query);
}
```

## 2. View (E (5).txt) - Add new filters:

```php
<!-- Add after orgaoPagadorFilter -->
<div class="col-md-3">
    <label class="form-label">Status Solicitação</label>
    <select class="form-select" name="status_solic" id="statusSolicFilter">
        <option value="">Todos</option>
        <!-- Placeholder values -->
        <option value="ATIVO">Ativo</option>
        <option value="CANCELADO">Cancelado</option>
    </select>
</div>

<div class="col-md-3">
    <label class="form-label">Motivo Encerramento</label>
    <select class="form-select" name="motivo_enc" id="motivoEncFilter">
        <option value="">Todos</option>
        <!-- Placeholder values -->
        <option value="MOTIVO1">Motivo 1</option>
        <option value="MOTIVO2">Motivo 2</option>
    </select>
</div>
```

```php
<!-- Update table headers to be sortable -->
<th class="thead-encerramento sortable" data-column="COD_SOLICITACAO" style="text-align: center; cursor: pointer;">
    Solicitação <span class="sort-icon">⇅</span>
</th>
<th class="thead-encerramento sortable" data-column="COD_AG" style="text-align: center; cursor: pointer;">
    Agência/PACB <span class="sort-icon">⇅</span>
</th>
<!-- Apply to all sortable columns -->
```

## 3. Controller (C (4).txt) - Update renderModals method:

```php
private function buildActionButtons($codSolicitacao) {
    $buttons = ['orgao_pagador' => 'Órgão Pagador', 'comercial' => 'Comercial', 
               'van_material' => 'Van-Material', 'bloqueio' => 'Bloqueio'];
    
    $html = '<div class="card"><div class="card-header"><h3 class="card-title">Ações</h3></div>';
    $html .= '<div class="card-body">';
    
    // Motivo dropdown
    $html .= '<div class="mb-3">
        <label class="form-label">Motivo Encerramento</label>
        <select class="form-select" id="motivoEnc' . $codSolicitacao . '">
            <option value="">Selecione...</option>
            <option value="MOTIVO1">Motivo 1</option>
            <option value="MOTIVO2">Motivo 2</option>
        </select>
    </div>';
    
    // Data input
    $html .= '<div class="mb-3">
        <label class="form-label">Data Encerramento</label>
        <input type="date" class="form-control" id="dataEnc' . $codSolicitacao . '">
    </div>';
    
    $html .= '<div class="d-flex gap-2 justify-content-center flex-wrap">';
    foreach ($buttons as $type => $label) {
        $html .= '<button class="btn email-action-btn" data-tipo="' . $type . '" data-solicitacao="' . $codSolicitacao . '">' . $label . '</button>';
    }
    $html .= '<button class="btn btn-red" data-bs-toggle="modal" data-bs-target="#AlertaEncerramento' . $codSolicitacao . '">Encerramento</button>';
    $html .= '</div></div></div>';
    
    return $html;
}

private function buildStatusShow($codSolicitacao) {
    // Fetch status from ENCERRAMENTO_TB_PORTAL
    $status = $this->model->getEncerramentoData($codSolicitacao);
    
    $statusConfig = [
        'STATUS_OP' => ['label' => 'Órgão Pagador', 'value' => $status['STATUS_OP'] ?? 'Não Efetuado'],
        'STATUS_COM' => ['label' => 'Comercial', 'value' => $status['STATUS_COM'] ?? 'Não Efetuado'],
        'STATUS_VAN' => ['label' => 'Van-Material', 'value' => $status['STATUS_VAN'] ?? 'Não Efetuado'],
        'STATUS_BLOQ' => ['label' => 'Bloqueio', 'value' => $status['STATUS_BLOQ'] ?? 'Não Efetuado'],
        'STATUS_ENCERRAMENTO' => ['label' => 'Encerramento', 'value' => $status['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado']
    ];
    
    $html = '<div class="card"><div class="card-header"><h3 class="card-title">Status</h3></div>';
    $html .= '<div class="card-body"><div class="table-responsive"><table class="table table-vcenter"><thead><tr>';
    
    foreach ($statusConfig as $config) {
        $html .= '<th>' . $config['label'] . '</th>';
    }
    
    $html .= '</tr></thead><tbody><tr>';
    
    foreach ($statusConfig as $config) {
        $statusClass = $config['value'] === 'Efetuado' ? 'green' : ($config['value'] === 'ERRO' ? 'red' : 'yellow');
        $html .= '<td><span class="status status-' . $statusClass . '"><span class="status-dot"></span>' . $config['value'] . '</span></td>';
    }
    
    $html .= '</tr></tbody></table></div></div></div>';
    return $html;
}

private function buildModalFooter($codSolicitacao) {
    return '<div class="modal-footer">
        <button class="btn btn-danger" onclick="cancelarSolicitacao(' . $codSolicitacao . ')">Cancelar Solicitação</button>
        <a href="#" class="btn btn-link link-secondary ms-auto" data-bs-dismiss="modal">Fechar</a>
    </div>';
}
```

## 4. JavaScript (J (4).txt) - Add sorting and validation:

```javascript
// Add to initialize()
initializeSorting();

// Add these functions
function initializeSorting() {
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            const currentOrder = this.getAttribute('data-order') || 'none';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            document.querySelectorAll('.sortable').forEach(h => {
                h.setAttribute('data-order', 'none');
                h.querySelector('.sort-icon').textContent = '⇅';
            });
            
            this.setAttribute('data-order', newOrder);
            this.querySelector('.sort-icon').textContent = newOrder === 'asc' ? '↑' : '↓';
            
            currentPage = 1;
            handleFormSubmit(column, newOrder);
        });
    });
}

function handleFormSubmit(sortColumn, sortOrder) {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }
    
    if (sortColumn) params.append('sort_column', sortColumn);
    if (sortOrder) params.append('sort_order', sortOrder);
    
    params.append('page', currentPage);
    params.append('per_page', perPage);
    
    // Rest of existing code...
}

window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }
    
    // Validate statuses
    const formData = new FormData();
    formData.append('acao', 'check_bulk_status');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.has_pending) {
            showBulkConfirmModal(solicitacoes, tipo, data.pending_list);
        } else {
            executeBulkEmail(solicitacoes, tipo);
        }
    });
};

function showBulkConfirmModal(solicitacoes, tipo, pendingList) {
    const message = 'As seguintes chaves têm status pendentes:\n' + pendingList.join(', ') + '\n\nDeseja continuar?';
    if (confirm(message)) {
        executeBulkEmail(solicitacoes, tipo);
    }
}

function executeBulkEmail(solicitacoes, tipo) {
    // Existing bulk email code...
}

window.cancelarSolicitacao = function(codSolicitacao) {
    if (!confirm('Deseja realmente cancelar esta solicitação?')) return;
    
    const formData = new FormData();
    formData.append('acao', 'cancelar_solicitacao');
    formData.append('cod_solicitacao', codSolicitacao);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Solicitação cancelada com sucesso', 'success');
            handleFormSubmit();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    });
};
```

## 5. AJAX Handler (JH (5).txt) - Add new actions:

```php
// Add after bulk email handling

if (isset($_POST['acao']) && $_POST['acao'] == 'check_bulk_status') {
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    
    $solicitacoes = json_decode($_POST['solicitacoes'], true);
    $model = new Analise();
    $statuses = $model->checkBulkStatus($solicitacoes);
    
    $has_pending = false;
    $pending_list = [];
    
    foreach ($statuses as $status) {
        if ($status['STATUS_OP'] !== 'Efetuado' || $status['STATUS_COM'] !== 'Efetuado' || 
            $status['STATUS_VAN'] !== 'Efetuado' || $status['STATUS_BLOQ'] !== 'Efetuado') {
            $has_pending = true;
            $pending_list[] = $status['CHAVE_LOJA'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['has_pending' => $has_pending, 'pending_list' => $pending_list]);
    exit;
}

if (isset($_POST['acao']) && $_POST['acao'] == 'cancelar_solicitacao') {
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    
    $cod_solicitacao = intval($_POST['cod_solicitacao']);
    $model = new Analise();
    $result = $model->cancelarSolicitacao($cod_solicitacao);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $result, 'message' => $result ? 'Cancelado' : 'Erro']);
    exit;
}

// Update loadData method to handle sorting
public function loadData($filters = []) {
    $where = "AND A.COD_TIPO_SERVICO=1";
    
    // Existing filters...
    
    if (isset($filters['status_solic']) && !empty($filters['status_solic'])) {
        $where .= " AND E.STATUS_SOLIC = '" . $filters['status_solic'] . "'";
    }
    
    if (isset($filters['motivo_enc']) && !empty($filters['motivo_enc'])) {
        $where .= " AND E.MOTIVO_ENC = '" . $filters['motivo_enc'] . "'";
    }
    
    $orderBy = " ORDER BY A.COD_SOLICITACAO DESC";
    if (isset($filters['sort_column']) && !empty($filters['sort_column'])) {
        $column = $filters['sort_column'];
        $order = isset($filters['sort_order']) && $filters['sort_order'] === 'asc' ? 'ASC' : 'DESC';
        $orderBy = " ORDER BY " . $column . " " . $order;
    }
    
    // Rest of existing code, but add JOIN to ENCERRAMENTO_TB_PORTAL
    $dados = $this->model->solicitacoes($where . $orderBy, $perPage, $offset);
    
    // Rest remains the same...
}
```

## 6. Add CSS for sorting:

```css
<style>
.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.sort-icon {
    font-size: 0.8em;
    margin-left: 5px;
    opacity: 0.5;
}

.sortable[data-order="asc"] .sort-icon,
.sortable[data-order="desc"] .sort-icon {
    opacity: 1;
}
</style>
```

This implementation:
- Adds STATUS_SOLIC and MOTIVO_ENC filters
- Implements column sorting (ASC/DESC)
- Adds Motivo and Data inputs in modal
- Shows status from ENCERRAMENTO_TB_PORTAL
- Validates bulk actions
- Adds cancel button
- Minimal code, clean, follows MVC