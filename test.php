Key modifications made:
JavaScript (J.txt):
Added initializeCheckboxHandlers() for header checkbox select-all functionality
Added updateBulkActionButtons() to show/hide bulk actions
Added getSelectedSolicitacoes() to collect checked IDs
Added sendBulkEmail() for bulk email sending
Modified row click handler to prevent modal on checkbox clicks
HTML (E.txt):
Added bulk actions card with 5 email buttons (appears when checkboxes selected)
Added CSS for checkbox styling and animations
Updated table row structure to prevent modal trigger on checkbox column
PHP Email Functions (ED.txt):
Added sendBulkEmail() function for multiple records
Added getBulkEmailConfig() for bulk email templates
Added buildBulkEmailBody() and buildBulkEmailBodyComercial() for HTML tables
AJAX Handler (JH.txt):
Added handler for enviar_email_bulk action
Processes JSON array of selected solicitation IDs 


----------------


// Add to existing analise_encerramento.js after initializeEventListeners()

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

    document.getElementById('tableBody')?.addEventListener('click', function(e) {
        if (e.target.type === 'checkbox') {
            e.stopPropagation();
            updateBulkActionButtons();
            return;
        }
        
        if (e.target.closest('input[type="checkbox"]')) {
            e.stopPropagation();
            return;
        }
    });
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'flex' : 'none';
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

function sendBulkEmail(tipo) {
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
}

// Call in initialize()
function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers();
    highlightActiveFilters();
    attachPageNumberHandlers();
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}


--------------

<!-- Add this right after the "Aplicar Filtros" section, before DataTable Section -->

<!-- Bulk Actions Section -->
<div class="card mb-3" id="bulkActions" style="display: none;">
    <div class="card-header">
        <h3 class="card-title">Ações em Massa</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" onclick="sendBulkEmail('orgao_pagador')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                Órgão Pagador
            </button>
            <button class="btn btn-info" onclick="sendBulkEmail('comercial')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                Comercial
            </button>
            <button class="btn btn-warning" onclick="sendBulkEmail('van_material')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                Van-Material
            </button>
            <button class="btn btn-danger" onclick="sendBulkEmail('bloqueio')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                Bloqueio
            </button>
            <button class="btn btn-red" onclick="sendBulkEmail('encerramento')">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                </svg>
                Encerramento
            </button>
        </div>
    </div>
</div>





---------


<?php
// Add to email_functions.php

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
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue novas solicitações de encerramento referente ao Órgão Pagador:',
                $solicitacoes,
                'Por gentileza providenciar as substituições.',
                $current_date
            )
        ],
        'comercial' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'CLUSTER DIAMANTE - Múltiplas Solicitações',
            'body' => buildBulkEmailBodyComercial(
                'Prezados,',
                'Recebemos múltiplas solicitações de encerramento. Ao analisarmos os casos, identificamos parceiros com bom desempenho. Solicitamos verificar possibilidade de reversão.',
                $solicitacoes,
                'Aguardamos retorno sobre as tratativas.',
                $current_date
            )
        ],
        'van_material' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Van/Material (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue base para solicitação do recolhimento dos equipamentos:',
                $solicitacoes,
                'Atenciosamente,',
                $current_date
            )
        ],
        'bloqueio' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Solicitação de Bloqueio (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue Correspondentes em processo de encerramento para bloqueio:',
                $solicitacoes,
                'Atenciosamente,',
                $current_date
            )
        ],
        'encerramento' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento no Bacen (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue solicitações de encerramento no Bacen:',
                $solicitacoes,
                'Por gentileza providenciar os encerramentos.',
                $current_date
            )
        ]
    ];
    
    return $configs[$type] ?? $configs['orgao_pagador'];
}

function buildBulkEmailBody($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) 
            ? $sol['DESC_MOTIVO_ENCERRAMENTO'] 
            : 'Não informado';
        
        $rows .= '<tr>
            <td>' . $sol['CHAVE_LOJA'] . '</td>
            <td>' . $sol['NOME_LOJA'] . '</td>
            <td>' . $motivo . '</td>
        </tr>';
    }
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
}

function buildBulkEmailBodyComercial($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) 
            ? $sol['DESC_MOTIVO_ENCERRAMENTO'] 
            : 'Não informado';
        
        $rows .= '<tr>
            <td>' . $sol['COD_EMPRESA'] . '</td>
            <td>' . $sol['CHAVE_LOJA'] . '</td>
            <td>' . $sol['NOME_LOJA'] . '</td>
            <td>' . $sol['CNPJ'] . '</td>
            <td>' . $motivo . '</td>
        </tr>';
    }
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellspacing="0" style="width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Cod. Empresa</strong></th>
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>CNPJ</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
}
?>




-------------


<?php
// Add to ajax_encerramento.php after the regular email handling

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
            echo json_encode_custom(['success' => false, 'message' => 'Tipo de email não especificado']);
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
?>



------------------



<style>
    /* Add to existing <style> section in E.txt */
    
    /* Prevent modal trigger on checkbox column */
    tbody tr[data-bs-toggle="modal"] th:first-child {
        pointer-events: auto;
    }
    
    tbody tr[data-bs-toggle="modal"] th:first-child * {
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




---------------


// Replace the tbody click handler in initializeCheckboxHandlers()

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

    // Delegate checkbox clicks on tbody
    document.getElementById('tableBody')?.addEventListener('click', function(e) {
        // If clicking checkbox or its label
        if (e.target.type === 'checkbox' || e.target.closest('label.form-check')) {
            e.stopPropagation();
            updateBulkActionButtons();
            return;
        }
        
        // If clicking the checkbox cell
        if (e.target.closest('th:first-child')) {
            e.stopPropagation();
            const checkbox = e.target.closest('th').querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateBulkActionButtons();
            }
            return;
        }
    });
    
    // Remove data-bs-toggle from rows and handle manually
    document.querySelectorAll('tbody tr[data-bs-toggle="modal"]').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't open modal if clicking checkbox column
            if (e.target.closest('th:first-child') || e.target.type === 'checkbox') {
                return;
            }
            
            const modalId = this.getAttribute('data-bs-target');
            if (modalId) {
                const modal = new bootstrap.Modal(document.querySelector(modalId));
                modal.show();
            }
        });
    });
}