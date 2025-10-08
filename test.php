// Update the initializeCheckboxHandlers function in analise_encerramento.js

function initializeCheckboxHandlers() {
    // ALWAYS set up modal triggers first (regardless of permissions)
    document.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td, th');
        cells.forEach((cell, index) => {
            // Skip first cell (checkbox column) only if user has bulk permissions
            if (index === 0 && window.userPermissions && window.userPermissions.canViewBulk) return;
            
            cell.addEventListener('click', function(e) {
                // Don't trigger if clicking on a checkbox
                if (e.target.type === 'checkbox') return;
                
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
    
    // Only initialize checkbox functionality if user has bulk permissions
    if (!window.userPermissions || !window.userPermissions.canViewBulk) {
        // Hide all checkboxes for non-ENC_MANAGEMENT users
        document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.style.display = 'none';
        });
        return;
    }
    
    // Header checkbox handler (only for ENC_MANAGEMENT)
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

    // Individual checkbox handlers (only for ENC_MANAGEMENT)
    document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkActionButtons();
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


------------


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
        
        // Ensure messages is always an array
        if (!$messages) {
            $messages = [];
        } else if (!is_array($messages)) {
            $messages = [$messages];
        }
        
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



-----------


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
        console.log('Chat response:', data); // Debug log
        if (data.success) {
            renderChatMessages(codSolicitacao, data.messages);
            startChatPolling(codSolicitacao);
        } else {
            console.error('Chat error:', data.message);
            const container = document.getElementById('chatMessages-' + codSolicitacao);
            if (container) {
                container.innerHTML = '<div class="text-center text-danger py-4"><p>Erro ao carregar chat: ' + (data.message || 'Erro desconhecido') + '</p></div>';
            }
        }
    })
    .catch(error => {
        console.error('Chat load error:', error);
        const container = document.getElementById('chatMessages-' + codSolicitacao);
        if (container) {
            container.innerHTML = '<div class="text-center text-danger py-4"><p>Erro ao carregar chat</p></div>';
        }
    });
};

function renderChatMessages(codSolicitacao, messages) {
    const container = document.getElementById('chatMessages-' + codSolicitacao);
    if (!container) return;
    
    // Ensure messages is an array
    if (!messages || !Array.isArray(messages)) {
        messages = [];
    }
    
    if (messages.length === 0) {
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
        let messageText = msg.MENSAGEM || '';
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


-----------


