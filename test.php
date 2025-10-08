<?php
// Define user groups and their permissions
define('USER_GROUPS', [
    'OP_MANAGEMENT' => [11111, 22222], // cod_usu for OP users
    'COM_MANAGEMENT' => [33333, 44444], // cod_usu for COM users
    'BLOQ_MANAGEMENT' => [55555, 66666], // cod_usu for BLOQ users
    'ENC_MANAGEMENT' => [77777, 88888] // cod_usu for ENC users (full access)
]);

function getUserGroup($cod_usu) {
    foreach (USER_GROUPS as $group => $users) {
        if (in_array($cod_usu, $users)) {
            return $group;
        }
    }
    return null;
}

function canViewBulkActions($cod_usu) {
    $group = getUserGroup($cod_usu);
    return $group === 'ENC_MANAGEMENT';
}

function canViewModalActions($cod_usu) {
    $group = getUserGroup($cod_usu);
    return $group === 'ENC_MANAGEMENT';
}

function canViewStatus($cod_usu) {
    $group = getUserGroup($cod_usu);
    return $group === 'ENC_MANAGEMENT';
}

function canCancelRequest($cod_usu) {
    $group = getUserGroup($cod_usu);
    return $group === 'ENC_MANAGEMENT';
}

function getChatRecipient($cod_usu) {
    $group = getUserGroup($cod_usu);
    if ($group === 'ENC_MANAGEMENT') {
        return null; // Can chat with any group
    }
    // Other groups can only chat with ENC_MANAGEMENT
    return 'ENC_MANAGEMENT';
}
?>

-------------

// Add these methods to the Analise class in analise_encerramento_model.class.php

public function createChatIfNotExists($cod_solicitacao) {
    $query = "SELECT CHAT_ID FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    $result = $this->sql->select($query);
    
    if (!$result || !$result[0]['CHAT_ID']) {
        $insertQuery = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT DEFAULT VALUES; SELECT SCOPE_IDENTITY() as CHAT_ID";
        $chatResult = $this->sql->select($insertQuery);
        $chatId = $chatResult[0]['CHAT_ID'];
        
        $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL SET CHAT_ID = " . $chatId . 
                      " WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
        $this->sql->update($updateQuery);
        
        return $chatId;
    }
    
    return $result[0]['CHAT_ID'];
}

public function getChatMessages($chat_id) {
    $query = "SELECT m.*, 
              f.nome_func as REMETENTE_NOME 
              FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT m
              LEFT JOIN RH..TB_FUNCIONARIOS f ON m.REMETENTE = f.COD_FUNC
              WHERE m.CHAT_ID = " . intval($chat_id) . "
              ORDER BY m.MESSAGE_DATE ASC";
    return $this->sql->select($query);
}

public function sendChatMessage($chat_id, $mensagem, $destinatario, $remetente, $anexo = 0) {
    $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
              (CHAT_ID, MENSAGEM, DESTINATARIO, REMETENTE, ANEXO) 
              VALUES (" . intval($chat_id) . ", 
                     '" . addslashes($mensagem) . "', 
                     " . intval($destinatario) . ", 
                     " . intval($remetente) . ", 
                     " . intval($anexo) . ")";
    return $this->sql->insert($query);
}

public function getLastMessageId() {
    $query = "SELECT SCOPE_IDENTITY() as MESSAGE_ID";
    $result = $this->sql->select($query);
    return $result[0]['MESSAGE_ID'];
}


----------


// Add these handlers to ajax_encerramento.php (before the regular data loading section)

// Load chat messages
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        $messages = $model->getChatMessages($chat_id);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'chat_id' => $chat_id,
            'messages' => $messages
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Send chat message
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $mensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : '';
        $destinatario = isset($_POST['destinatario']) ? intval($_POST['destinatario']) : 0;
        $remetente = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        $anexo = 0;
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $anexo = 1;
        }
        
        $result = $model->sendChatMessage($chat_id, $mensagem, $destinatario, $remetente, $anexo);
        
        if ($result && $anexo) {
            $message_id = $model->getLastMessageId();
            $upload_dir = 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\view\encerramento\anexos\\';
            $user_dir = $upload_dir . $remetente . '\\';
            
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }
            
            $file_name = $message_id . '_' . basename($_FILES['arquivo']['name']);
            $file_path = $user_dir . $file_name;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $file_path)) {
                $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                               SET MENSAGEM = MENSAGEM + ' [FILE:' + '" . $file_name . "' + ']' 
                               WHERE MESSAGE_ID = " . $message_id;
                $model->update($updateQuery);
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'message' => 'Mensagem enviada'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


-------------

<?php
// Add at the top of analise_encerramento.php after require_once statements

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\permissions_config.php';

$cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
$userGroup = getUserGroup($cod_usu);
$canViewBulk = canViewBulkActions($cod_usu);
$canViewActions = canViewModalActions($cod_usu);
$canViewStatusSection = canViewStatus($cod_usu);
$canCancel = canCancelRequest($cod_usu);

// Store in JavaScript for client-side use
echo '<script>';
echo 'window.userPermissions = {';
echo '  canViewBulk: ' . ($canViewBulk ? 'true' : 'false') . ',';
echo '  canViewActions: ' . ($canViewActions ? 'true' : 'false') . ',';
echo '  canViewStatus: ' . ($canViewStatusSection ? 'true' : 'false') . ',';
echo '  canCancel: ' . ($canCancel ? 'true' : 'false') . ',';
echo '  userGroup: "' . $userGroup . '",';
echo '  codUsu: ' . $cod_usu;
echo '};';
echo '</script>';
?>

<!-- Then wrap the bulk actions section with permission check -->
<?php if ($canViewBulk): ?>
<div class="card mb-3" id="bulkActions" style="display: none;">
    <!-- existing bulk actions content -->
</div>
<?php endif; ?>

<!-- Update the checkbox column header to only show for ENC_MANAGEMENT -->
<th class="text-center align-middle p-0" style="background-color: #d8d8d8;">
    <?php if ($canViewBulk): ?>
    <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
        <input class="form-check-input position-static m-0" type="checkbox" />
        <span class="form-check-label d-none"></span>
    </label>
    <?php endif; ?>
</th>


-------------


private function buildModalBody($row, $codSolicitacao, $nomeLoja, $status) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canViewActions = canViewModalActions($cod_usu);
    $canViewStatusSection = canViewStatus($cod_usu);
    $canCancel = canCancelRequest($cod_usu);
    
    return '
        <div class="modal-body">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-home-' . $codSolicitacao . '" class="nav-link active" data-bs-toggle="tab">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <polyline points="5 12 3 12 12 3 21 12 19 12" />
                                    <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                    <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                                </svg>
                                Informações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-chat-' . $codSolicitacao . '" class="nav-link" data-bs-toggle="tab" onclick="loadChat(' . $codSolicitacao . ')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M3 20l1.3 -3.9a9 9 0 1 1 3.4 2.9l-4.7 1" />
                                    <line x1="12" y1="12" x2="12" y2="12.01" />
                                    <line x1="8" y1="12" x2="8" y2="12.01" />
                                    <line x1="16" y1="12" x2="16" y2="12.01" />
                                </svg>
                                Chat
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active show" id="tabs-home-' . $codSolicitacao . '">
                            ' . $this->buildInfoCards($row, $nomeLoja) . '
                            ' . ($canViewActions ? $this->buildActionButtons($codSolicitacao) : '') . '
                            ' . ($canViewStatusSection ? $this->buildStatusShow($status) : '') . '
                        </div>
                        <div class="tab-pane" id="tabs-chat-' . $codSolicitacao . '">
                            ' . $this->buildChatInterface($codSolicitacao) . '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    ';
}

private function buildChatInterface($codSolicitacao) {
    return '
        <div class="card flex-fill">
            <div class="card-body">
                <div id="chatMessages-' . $codSolicitacao . '" class="chat-messages" style="height: 400px; overflow-y: auto; margin-bottom: 1rem;">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando mensagens...</p>
                    </div>
                </div>
                <div class="input-group">
                    <input type="text" class="form-control" id="chatInput-' . $codSolicitacao . '" placeholder="Digite sua mensagem..." />
                    <input type="file" id="chatFile-' . $codSolicitacao . '" style="display: none;" />
                    <button class="btn btn-icon" onclick="document.getElementById(\'chatFile-' . $codSolicitacao . '\').click()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                        </svg>
                    </button>
                    <button class="btn btn-primary" onclick="sendChatMessage(' . $codSolicitacao . ')">Enviar</button>
                </div>
                <small id="chatFileName-' . $codSolicitacao . '" class="text-muted"></small>
            </div>
        </div>
    ';
}

private function buildModalFooter($codSolicitacao) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canCancel = canCancelRequest($cod_usu);
    
    return '
        <div class="modal-footer d-flex justify-content-between">
            ' . ($canCancel ? '<button class="btn btn-danger" onclick="cancelarSolicitacao(' . $codSolicitacao . ')">Cancelar Solicitação</button>' : '<div></div>') . '
            <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Fechar</a>
        </div>';
}


-------------


// Add these functions to analise_encerramento.js

window.loadChat = function(codSolicitacao) {
    const formData = new FormData();
    formData.append('acao', 'load_chat');
    formData.append('cod_solicitacao', codSolicitacao);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderChatMessages(codSolicitacao, data.messages);
            startChatPolling(codSolicitacao);
        } else {
            showNotification('Erro ao carregar chat', 'error');
        }
    })
    .catch(error => {
        console.error('Chat load error:', error);
        showNotification('Erro ao carregar chat', 'error');
    });
};

function renderChatMessages(codSolicitacao, messages) {
    const container = document.getElementById('chatMessages-' + codSolicitacao);
    if (!container) return;
    
    if (!messages || messages.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4"><p>Nenhuma mensagem ainda</p></div>';
        return;
    }
    
    let html = '<div class="chat">';
    messages.forEach(msg => {
        const isOwnMessage = msg.REMETENTE == window.userPermissions.codUsu;
        const messageClass = isOwnMessage ? 'chat-item-end' : 'chat-item-start';
        const bubbleClass = isOwnMessage ? 'chat-bubble-me' : 'chat-bubble';
        
        const messageDate = new Date(msg.MESSAGE_DATE);
        const formattedDate = messageDate.toLocaleString('pt-BR');
        
        const hasFile = msg.MENSAGEM && msg.MENSAGEM.includes('[FILE:');
        let messageText = msg.MENSAGEM;
        let fileName = '';
        
        if (hasFile) {
            const fileMatch = msg.MENSAGEM.match(/\[FILE:(.*?)\]/);
            if (fileMatch) {
                fileName = fileMatch[1];
                messageText = msg.MENSAGEM.replace(/\[FILE:.*?\]/, '').trim();
            }
        }
        
        html += '<div class="' + messageClass + ' mb-3">';
        html += '<div class="' + bubbleClass + '">';
        html += '<div class="chat-bubble-title">';
        html += '<div class="row">';
        html += '<div class="col chat-bubble-author">' + (msg.REMETENTE_NOME || 'Usuário') + '</div>';
        html += '<div class="col-auto chat-bubble-date">' + formattedDate + '</div>';
        html += '</div></div>';
        html += '<div class="chat-bubble-body">';
        if (messageText) html += '<p>' + messageText + '</p>';
        if (hasFile) {
            html += '<div class="mt-2"><a href="./anexos/' + msg.REMETENTE + '/' + fileName + '" target="_blank" class="btn btn-sm btn-outline-primary">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4.406 1.342A5.53 5.53 0 0 1 8 0c2.69 0 4.923 2 5.166 4.579C14.758 4.804 16 6.137 16 7.773 16 9.569 14.502 11 12.687 11H10a.5.5 0 0 1 0-1h2.688C13.979 10 15 8.988 15 7.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 2.825 10.328 1 8 1a4.53 4.53 0 0 0-2.941 1.1c-.757.652-1.153 1.438-1.153 2.055v.448l-.445.049C2.064 4.805 1 5.952 1 7.318 1 8.785 2.23 10 3.781 10H6a.5.5 0 0 1 0 1H3.781C1.708 11 0 9.366 0 7.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383z"/><path d="M7.646 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V5.5a.5.5 0 0 0-1 0v8.793l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z"/></svg> ';
            html += fileName + '</a></div>';
        }
        html += '</div></div></div>';
    });
    html += '</div>';
    
    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

window.sendChatMessage = function(codSolicitacao) {
    const input = document.getElementById('chatInput-' + codSolicitacao);
    const fileInput = document.getElementById('chatFile-' + codSolicitacao);
    const mensagem = input.value.trim();
    
    if (!mensagem && !fileInput.files.length) {
        showNotification('Digite uma mensagem ou selecione um arquivo', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'send_message');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('mensagem', mensagem);
    formData.append('remetente', window.userPermissions.codUsu);
    
    // Determine recipient based on user group
    let destinatario = 0; // Will be determined on server based on group
    formData.append('destinatario', destinatario);
    
    if (fileInput.files.length) {
        formData.append('arquivo', fileInput.files[0]);
    }
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            fileInput.value = '';
            document.getElementById('chatFileName-' + codSolicitacao).textContent = '';
            loadChat(codSolicitacao);
        } else {
            showNotification('Erro ao enviar mensagem', 'error');
        }
    })
    .catch(error => {
        console.error('Send message error:', error);
        showNotification('Erro ao enviar mensagem', 'error');
    });
};

// File selection handler
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="chatFile-"]').forEach(input => {
        input.addEventListener('change', function() {
            const codSolicitacao = this.id.replace('chatFile-', '');
            const fileName = this.files.length ? this.files[0].name : '';
            document.getElementById('chatFileName-' + codSolicitacao).textContent = fileName ? 'Arquivo: ' + fileName : '';
        });
    });
});

// Poll for new messages every 5 seconds
let chatPollingIntervals = {};

function startChatPolling(codSolicitacao) {
    if (chatPollingIntervals[codSolicitacao]) {
        clearInterval(chatPollingIntervals[codSolicitacao]);
    }
    
    chatPollingIntervals[codSolicitacao] = setInterval(() => {
        loadChat(codSolicitacao);
    }, 5000);
}

// Stop polling when modal closes
document.addEventListener('hidden.bs.modal', function(e) {
    const modalId = e.target.id;
    const match = modalId.match(/AnaliseDetalhesModal(\d+)/);
    if (match) {
        const codSolicitacao = match[1];
        if (chatPollingIntervals[codSolicitacao]) {
            clearInterval(chatPollingIntervals[codSolicitacao]);
            delete chatPollingIntervals[codSolicitacao];
        }
    }
});



------------

// Update the initializeCheckboxHandlers function in analise_encerramento.js

function initializeCheckboxHandlers() {
    // Only initialize if user has bulk permissions
    if (!window.userPermissions || !window.userPermissions.canViewBulk) {
        // Hide all checkboxes for non-ENC_MANAGEMENT users
        document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.style.display = 'none';
        });
        return;
    }
    
    // Header checkbox handler
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    if (headerCheckbox) {
        headerCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                cb.checked = isChecked;
            });
            updateBulkActionButtons();
        });
    }

    // Individual checkbox handlers
    document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkActionButtons();
        });
    });

    // Modal trigger for rows (excluding checkbox cell)
    document.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td, th');
        cells.forEach((cell, index) => {
            if (index === 0 && window.userPermissions.canViewBulk) return; // Skip first cell if checkbox visible
            
            cell.addEventListener('click', function() {
                const modalId = row.getAttribute('data-modal-target');
                if (modalId) {
                    const modalElement = document.querySelector(modalId);
                    if (modalElement) {
                        openModal(modalElement);
                    }
                }
            });
        });
    });
}

// Update the renderTableRows to conditionally include checkbox
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
    initializeCheckboxHandlers();
    
    // Hide bulk actions if no permissions
    if (!window.userPermissions || !window.userPermissions.canViewBulk) {
        const bulkActions = document.getElementById('bulkActions');
        if (bulkActions) bulkActions.style.display = 'none';
    }
    
    if (window.pageState && !window.pageState.autoLoadData) {
        document.getElementById('tableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    if (window.pageState) window.pageState.autoLoadData = false;
}

--------------

// Update the renderTableCell method in ajax_encerramento.php

private function renderTableCell($row) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canViewBulk = canViewBulkActions($cod_usu);
    
    $cells = '';
    
    if ($canViewBulk) {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                    <input class="form-check-input position-static m-0" type="checkbox" onclick="event.stopPropagation();" />
                    <span class="form-check-label d-none"></span>
                </label>
            </th>';
    } else {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important; width: 30px;"></th>';
    }
    
    $cells .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_SOLICITACAO']) . '</span></th>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_AG']) . htmlspecialchars($row['NR_PACB']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['CHAVE_LOJA']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . $row['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
    $cells .= $this->renderDateCell($row['DATA_RETIRADA_EQPTO']);
    $cells .= $this->renderBloqueioCell($row['DATA_BLOQUEIO']);
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['DATA_LAST_TRANS']) . '</span></td>';
    $cells .= $this->renderTextCell($row['MOTIVO_BLOQUEIO'], 'Sem Motivo de Bloqueio');
    $cells .= $this->renderTextCell($row['DESC_MOTIVO_ENCERRAMENTO'], 'Sem Motivo de Encerramento');
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['ORGAO_PAGADOR']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['CLUSTER']) . '</span></td>';
    $cells .= $this->renderAptoCell($row['PARM']);
    $cells .= $this->renderAptoCell($row['TRAG']);
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_CONTABEIS']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_NEGOCIO']) . '</span></td>';
    
    return $cells;
}


-------------

.chat {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.chat-item-start {
    display: flex;
    justify-content: flex-start;
}

.chat-item-end {
    display: flex;
    justify-content: flex-end;
}

.chat-bubble {
    max-width: 75%;
    background-color: #f1f3f5;
    border-radius: 0.5rem;
    padding: 0.75rem;
}

.chat-bubble-me {
    max-width: 75%;
    background-color: #206bc4;
    color: white;
    border-radius: 0.5rem;
    padding: 0.75rem;
}

[data-theme="dark"] .chat-bubble {
    background-color: #1f2937;
}

.chat-bubble-title {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.chat-bubble-author {
    font-weight: 600;
}

.chat-bubble-date {
    color: #6c757d;
    font-size: 0.75rem;
}

.chat-bubble-me .chat-bubble-date {
    color: rgba(255, 255, 255, 0.7);
}

.chat-bubble-body {
    word-wrap: break-word;
}

.chat-messages {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

[data-theme="dark"] .chat-messages {
    background-color: #1a1d26;
    border-color: #2d3748;
}

.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}


-------------



# Code Placement Quick Reference

## File: permissions_config.php (NEW FILE)
**Location:** `control/encerramento/permissions_config.php`
- Use entire artifact: "permissions_config.php"
- **ACTION:** Replace placeholder COD_USU values (11111, 22222, etc.) with actual user IDs

---

## File: analise_encerramento_model.class.php (M.txt)
**Location:** `model/encerramento/analise_encerramento_model.class.php`

### Add these methods to the `Analise` class:
```php
// From artifact: "Chat Methods for Model"
public function createChatIfNotExists($cod_solicitacao) { ... }
public function getChatMessages($chat_id) { ... }
public function sendChatMessage(...) { ... }
public function getLastMessageId() { ... }
```
**ACTION:** Copy all 4 methods into the class

---

## File: ajax_encerramento.php (JH.txt)
**Location:** `control/encerramento/roteamento/ajax_encerramento.php`

### 1. Add at the top (after session_start):
```php
require_once '../permissions_config.php';
```

### 2. Add BEFORE regular data loading (before "try { $handler = new..."):
```php
// From artifact: "Chat AJAX Handlers"
// Handler for 'load_chat'
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') { ... }

// Handler for 'send_message'  
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') { ... }
```

### 3. In AjaxEncerramentoHandler class:

#### Replace `renderTableCell()`:
```php
// From artifact: "Table Row with Permission Check"
private function renderTableCell($row) { ... }
```

#### Replace `buildModalBody()`:
```php
// From artifact: "Updated Modal Rendering"
private function buildModalBody($row, $codSolicitacao, $nomeLoja, $status) { ... }
```

#### Replace `buildModalFooter()`:
```php
// From artifact: "Updated Modal Rendering"
private function buildModalFooter($codSolicitacao) { ... }
```

#### Add new method:
```php
// From artifact: "Updated Modal Rendering"
private function buildChatInterface($codSolicitacao) { ... }
```

---

## File: analise_encerramento.php (E.txt)
**Location:** `view/encerramento/analise_encerramento.php`

### 1. After existing require_once statements (top of file):
```php
// From artifact: "View Modifications"
require_once 'permissions_config.php';
$cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
// ... rest of permission checks
```

### 2. In the <head> section (before </head>):
```html
<!-- From artifact: "Chat CSS Styling" -->
<style>
.chat { ... }
.chat-item-start { ... }
/* ... rest of CSS */
</style>
```

### 3. Wrap bulk actions div:
```php
<?php if ($canViewBulk): ?>
<div class="card mb-3" id="bulkActions" style="display: none;">
    <!-- existing content -->
</div>
<?php endif; ?>
```

### 4. Update table header checkbox cell:
```html
<th class="text-center align-middle p-0" style="background-color: #d8d8d8;">
    <?php if ($canViewBulk): ?>
    <label class="form-check d-inline-flex...">
        <input class="form-check-input..." type="checkbox" />
        ...
    </label>
    <?php endif; ?>
</th>
```

---

## File: analise_encerramento.js (J.txt)
**Location:** `encerramento/analise_encerramento/analise_encerramento.js`

### 1. Add these functions anywhere in the file:
```javascript
// From artifact: "Chat JavaScript Functions"
window.loadChat = function(codSolicitacao) { ... }
function renderChatMessages(...) { ... }
window.sendChatMessage = function(codSolicitacao) { ... }
function startChatPolling(codSolicitacao) { ... }
// ... plus event listeners
```

### 2. Replace `initializeCheckboxHandlers()`:
```javascript
// From artifact: "JavaScript Permission Updates"
function initializeCheckboxHandlers() {
    // Only initialize if user has bulk permissions
    if (!window.userPermissions || !window.userPermissions.canViewBulk) { ... }
    // ... rest of function
}
```

### 3. In `updateUI()` function, add after `initializeCheckboxHandlers()`:
```javascript
// From artifact: "JavaScript Permission Updates"
// Hide bulk actions if no permissions
if (!window.userPermissions || !window.userPermissions.canViewBulk) {
    const bulkActions = document.getElementById('bulkActions');
    if (bulkActions) bulkActions.style.display = 'none';
}
```

---

## Summary Checklist

- [ ] Create `permissions_config.php` with actual COD_USU values
- [ ] Add 4 chat methods to Model (M.txt)
- [ ] Update AJAX handler (JH.txt): 2 new handlers + 4 method updates
- [ ] Update View (E.txt): permissions + CSS + conditional rendering
- [ ] Update JavaScript (J.txt): chat functions + permission checks
- [ ] Create `anexos` directory with write permissions
- [ ] Test with each user group type