// Add these functions to analise_encerramento.js

function initializeCheckboxHandlers() {
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    if (headerCheckbox) {
        headerCheckbox.addEventListener('click', function(e) {
            e.stopPropagation();
            const isChecked = this.checked;
            document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkActionButtons();
        });
    }

    document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            this.checked = !this.checked;
            updateBulkActionButtons();
        });
    });

    document.querySelectorAll('tbody tr[data-bs-toggle="modal"] th:first-child').forEach(cell => {
        cell.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
    });
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
}

function getSelectedSolicitacoes() {
    const selected = [];
    document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => {
        const row = cb.closest('tr');
        if (row) {
            selected.push(row.getAttribute('name'));
        }
    });
    return selected;
}

window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    const formData = new FormData();
    formData.append('acao', 'enviar_email_bulk');
    formData.append('tipo', tipo);
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Emails enviados com sucesso!', 'success');
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => cb.checked = false);
            document.querySelector('thead input[type="checkbox"]').checked = false;
            updateBulkActionButtons();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btn.innerHTML = originalText;
        btn.disabled = false;
    })
    .catch(error => {
        showNotification('Erro ao enviar emails', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
};

// Update the initialize() function to include:
function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers(); // ADD THIS LINE
    highlightActiveFilters();
    attachPageNumberHandlers();
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}

// Update the updateUI() function to include initializeCheckboxHandlers():
function updateUI(data, params) {
    const tableBody = document.getElementById('tableBody');
    const modalsContainer = document.getElementById('modalsContainer');
    
    if (tableBody) tableBody.innerHTML = data.html;
    if (modalsContainer) {
        modalsContainer.innerHTML = data.modals;
        attachEmailHandlers();
    }
    
    updatePaginationInfo(data);
    updatePaginationControls();
    
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.pushState({}, '', newUrl);
    
    highlightActiveFilters();
    initializeCheckboxHandlers(); // ADD THIS LINE
    
    if (window.pageState && !window.pageState.autoLoadData) {
        document.getElementById('tableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    if (window.pageState) window.pageState.autoLoadData = false;
}


----------

<style>
    /* Add to existing <style> section in E.txt */
    
    /* Prevent modal trigger on checkbox column */
    tbody tr[data-bs-toggle="modal"] th:first-child,
    tbody tr[data-bs-toggle="modal"] th:first-child * {
        pointer-events: none;
    }
    
    tbody tr[data-bs-toggle="modal"] th:first-child input[type="checkbox"],
    tbody tr[data-bs-toggle="modal"] th:first-child label {
        pointer-events: auto;
    }
    
    /* Checkbox styling */
    .form-check-input {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }
    
    thead .form-check-input {
        width: 20px;
        height: 20px;
    }
    
    /* Bulk actions animation */
    #bulkActions {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>



-----------


# Complete Implementation Guide

## Files to Modify

### 1. **E.txt (analise_encerramento.php)**

#### A. Add CSS inside `<style>` section:
```css
tbody tr[data-bs-toggle="modal"] th:first-child,
tbody tr[data-bs-toggle="modal"] th:first-child * {
    pointer-events: none;
}

tbody tr[data-bs-toggle="modal"] th:first-child input[type="checkbox"],
tbody tr[data-bs-toggle="modal"] th:first-child label {
    pointer-events: auto;
}

.form-check-input {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

thead .form-check-input {
    width: 20px;
    height: 20px;
}

#bulkActions {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

#### B. Add Bulk Actions HTML after "Aplicar Filtros" card:
```html
<div class="card mb-3" id="bulkActions" style="display: none;">
    <div class="card-header">
        <h3 class="card-title">Ações em Massa</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" onclick="sendBulkEmail('orgao_pagador')">Órgão Pagador</button>
            <button class="btn btn-info" onclick="sendBulkEmail('comercial')">Comercial</button>
            <button class="btn btn-warning" onclick="sendBulkEmail('van_material')">Van-Material</button>
            <button class="btn btn-danger" onclick="sendBulkEmail('bloqueio')">Bloqueio</button>
            <button class="btn btn-red" onclick="sendBulkEmail('encerramento')">Encerramento</button>
        </div>
    </div>
</div>
```

---

### 2. **J.txt (analise_encerramento.js)**

Add these functions and modify `initialize()` and `updateUI()`:

```javascript
function initializeCheckboxHandlers() {
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    if (headerCheckbox) {
        headerCheckbox.addEventListener('click', function(e) {
            e.stopPropagation();
            const isChecked = this.checked;
            document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkActionButtons();
        });
    }

    document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            this.checked = !this.checked;
            updateBulkActionButtons();
        });
    });

    document.querySelectorAll('tbody tr[data-bs-toggle="modal"] th:first-child').forEach(cell => {
        cell.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
        });
    });
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
}

function getSelectedSolicitacoes() {
    const selected = [];
    document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => {
        const row = cb.closest('tr');
        if (row) {
            selected.push(row.getAttribute('name'));
        }
    });
    return selected;
}

window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    const formData = new FormData();
    formData.append('acao', 'enviar_email_bulk');
    formData.append('tipo', tipo);
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Emails enviados com sucesso!', 'success');
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => cb.checked = false);
            document.querySelector('thead input[type="checkbox"]').checked = false;
            updateBulkActionButtons();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btn.innerHTML = originalText;
        btn.disabled = false;
    })
    .catch(error => {
        showNotification('Erro ao enviar emails', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
};
```

**Modify `initialize()` to add:**
```javascript
initializeCheckboxHandlers(); // Add this line
```

**Modify `updateUI()` to add:**
```javascript
initializeCheckboxHandlers(); // Add this line after highlightActiveFilters()
```

---

### 3. **ED.txt (email_functions.php)**

Add these functions at the end:

```php
function sendBulkEmail($type, $cod_solicitacoes) {
    global $EMAIL_CONFIG;
    
    ob_start();
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP_MAILER_NEW\mail.php');
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    ob_end_clean();
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $model = new Analise();
    $solicitacoes = [];
    
    foreach ($cod_solicitacoes as $cod) {
        $where = "AND A.COD_SOLICITACAO = " . intval($cod);
        $dados = $model->solicitacoes($where, 1, 0);
        if (!empty($dados)) {
            $solicitacoes[] = $dados[0];
        }
    }
    
    if (empty($solicitacoes)) {
        return ['success' => false, 'message' => 'Nenhuma solicitação encontrada'];
    }
    
    $emailConfig = getBulkEmailConfig($type, $solicitacoes);
    
    $email_to = ($_SESSION['cod_usu'] == $EMAIL_CONFIG['test_user_id']) 
        ? $EMAIL_CONFIG['test_email'] 
        : $emailConfig['recipients'];
    
    ob_start();
    $result = mailer(
        false, '', 
        $email_to, '', '', 
        $emailConfig['subject'], 
        utf8_decode($emailConfig['body']), 
        '', 'I', ''
    );
    ob_end_clean();
    
    return $result 
        ? ['success' => true, 'message' => 'Emails enviados com sucesso']
        : ['success' => false, 'message' => 'Erro ao enviar emails'];
}

function getBulkEmailConfig($type, $solicitacoes) {
    global $EMAIL_CONFIG;
    $current_date = date('d/m/Y');
    
    $configs = [
        'orgao_pagador' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Cancelamento - Órgão Pagador (Múltiplos)',
            'body' => buildBulkEmailBody('Prezados,', 'Segue novas solicitações de encerramento referente ao Órgão Pagador:', $solicitacoes, 'Por gentileza providenciar as substituições.', $current_date)
        ],
        'comercial' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'CLUSTER DIAMANTE - Múltiplas Solicitações',
            'body' => buildBulkEmailBodyComercial('Prezados,', 'Recebemos múltiplas solicitações de encerramento.', $solicitacoes, 'Aguardamos retorno.', $current_date)
        ],
        'van_material' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Van/Material (Múltiplos)',
            'body' => buildBulkEmailBody('Prezados,', 'Segue base para recolhimento:', $solicitacoes, 'Atenciosamente,', $current_date)
        ],
        'bloqueio' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Solicitação de Bloqueio (Múltiplos)',
            'body' => buildBulkEmailBody('Prezados,', 'Segue para bloqueio:', $solicitacoes, 'Atenciosamente,', $current_date)
        ],
        'encerramento' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento no Bacen (Múltiplos)',
            'body' => buildBulkEmailBody('Prezados,', 'Segue solicitações:', $solicitacoes, 'Por gentileza providenciar.', $current_date)
        ]
    ];
    
    return $configs[$type] ?? $configs['orgao_pagador'];
}

function buildBulkEmailBody($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) ? $sol['DESC_MOTIVO_ENCERRAMENTO'] : 'Não informado';
        $rows .= '<tr><td>' . $sol['CHAVE_LOJA'] . '</td><td>' . $sol['NOME_LOJA'] . '</td><td>' . $motivo . '</td></tr>';
    }
    
    return '<div><p>' . $greeting . '</p><p>' . $intro . '</p>
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead><tr style="background-color:#00316E; color:#ffffff;">
                    <th><strong>Chave Loja</strong></th>
                    <th><strong>Raz&atilde;o Social</strong></th>
                    <th><strong>Motivo</strong></th>
                </tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p><p>Data: ' . $date . '</p><p>Atenciosamente</p></div>';
}

function buildBulkEmailBodyComercial($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) ? $sol['DESC_MOTIVO_ENCERRAMENTO'] : 'Não informado';
        $rows .= '<tr><td>' . $sol['COD_EMPRESA'] . '</td><td>' . $sol['CHAVE_LOJA'] . '</td><td>' . $sol['NOME_LOJA'] . '</td><td>' . $sol['CNPJ'] . '</td><td>' . $motivo . '</td></tr>';
    }
    
    return '<div><p>' . $greeting . '</p><p>' . $intro . '</p>
            <table border="1" cellspacing="0" style="width: 100%;">
                <thead><tr style="background-color:#00316E; color:#ffffff;">
                    <th><strong>Cod. Empresa</strong></th>
                    <th><strong>Chave Loja</strong></th>
                    <th><strong>Raz&atilde;o Social</strong></th>
                    <th><strong>CNPJ</strong></th>
                    <th><strong>Motivo</strong></th>
                </tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p><p>Data: ' . $date . '</p><p>Atenciosamente</p></div>';
}
```

---

### 4. **JH.txt (ajax_encerramento.php)**

Add this BEFORE the regular data loading code:

```php
if (isset($_POST['acao']) && $_POST['acao'] == 'enviar_email_bulk') {
    ob_start();
    
    try {
        require_once '../email_functions.php';
        
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $solicitacoes_json = isset($_POST['solicitacoes']) ? $_POST['solicitacoes'] : '';
        
        if (empty($solicitacoes_json)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Nenhuma solicitação selecionada']);
            exit;
        }
        
        $solicitacoes = json_decode($solicitacoes_json, true);
        
        if (!is_array($solicitacoes) || count($solicitacoes) === 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Solicitações inválidas']);
            exit;
        }
        
        if (empty($tipo)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Tipo não especificado']);
            exit;
        }
        
        $result = sendBulkEmail($tipo, $solicitacoes);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom($result);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
```

---

## Testing

1. Check header checkbox - all rows should check
2. Click individual checkbox - should NOT open modal
3. Click anywhere else in row - should open modal
4. Select checkboxes - bulk actions card should appear
5. Click bulk action button - should send ONE email with all selected records 