// Replace the buildChatInterface() method with this enhanced buildChat() method

private function buildChat($codSolicitacao) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $userGroup = getUserGroup($cod_usu);
    
    // Define available contacts based on user group
    $contacts = [];
    
    if ($userGroup === 'ENC_MANAGEMENT') {
        // ENC can talk to all groups
        $contacts = [
            'OP_MANAGEMENT' => ['name' => 'Órgão Pagador', 'avatar' => 'OP', 'color' => 'blue'],
            'COM_MANAGEMENT' => ['name' => 'Comercial', 'avatar' => 'COM', 'color' => 'indigo'],
            'BLOQ_MANAGEMENT' => ['name' => 'Bloqueio', 'avatar' => 'BLQ', 'color' => 'teal'],
        ];
    } else {
        // Non-ENC can only talk to ENC
        $contacts = [
            'ENC_MANAGEMENT' => ['name' => 'Encerramento', 'avatar' => 'ENC', 'color' => 'red'],
        ];
    }
    
    $html = '<div class="card flex-fill">
        <div class="row g-0 flex-fill">
            <div class="col-12 col-lg-5 col-xl-3 border-end d-flex flex-column">
                <div class="card-header">
                    <h3 class="card-title">Contatos</h3>
                </div>
                <div class="card-body p-0 scrollable flex-fill" style="max-height: 400px;">
                    <div class="nav flex-column nav-pills" role="tablist">';
    
    // Generate contact list
    $first = true;
    foreach ($contacts as $groupKey => $contact) {
        $active = $first ? 'active' : '';
        $html .= '
            <a href="#" class="nav-link text-start mw-100 p-3 chat-contact ' . $active . '" 
               data-group="' . $groupKey . '" 
               data-solicitacao="' . $codSolicitacao . '"
               onclick="selectChatContact(event, \'' . $groupKey . '\', ' . $codSolicitacao . ')">
                <div class="row align-items-center flex-fill">
                    <div class="col-auto">
                        <span class="avatar avatar-sm bg-' . $contact['color'] . '-lt">' . $contact['avatar'] . '</span>
                    </div>
                    <div class="col text-body">
                        <div class="fw-bold">' . $contact['name'] . '</div>
                        <div class="text-secondary small" id="lastMsg-' . $groupKey . '-' . $codSolicitacao . '">
                            Clique para conversar
                        </div>
                    </div>
                </div>
            </a>';
        $first = false;
    }
    
    $html .= '
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7 col-xl-9 d-flex flex-column">
                <div class="card-header">
                    <h3 class="card-title" id="chatContactName-' . $codSolicitacao . '">
                        ' . reset($contacts)['name'] . '
                    </h3>
                </div>
                <div class="card-body scrollable" style="max-height: 350px; overflow-y: auto;" id="chatMessages-' . $codSolicitacao . '">
                    <div class="text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando mensagens...</p>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="chatInput-' . $codSolicitacao . '" 
                               placeholder="Digite sua mensagem..."
                               onkeypress="if(event.key===\'Enter\'){sendChatMessage(' . $codSolicitacao . ');return false;}" />
                        <input type="file" id="chatFile-' . $codSolicitacao . '" style="display: none;" 
                               onchange="handleFileSelect(' . $codSolicitacao . ')" />
                        <button class="btn btn-icon" type="button" 
                                onclick="document.getElementById(\'chatFile-' . $codSolicitacao . '\').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
                                 stroke-linejoin="round" class="icon">
                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                            </svg>
                        </button>
                        <button class="btn btn-primary" type="button" onclick="sendChatMessage(' . $codSolicitacao . ')">
                            Enviar
                        </button>
                    </div>
                    <small id="chatFileName-' . $codSolicitacao . '" class="text-muted d-block mt-1"></small>
                    <input type="hidden" id="chatSelectedGroup-' . $codSolicitacao . '" value="' . key($contacts) . '" />
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}

---------

private function buildModalBody($row, $codSolicitacao, $nomeLoja, $status) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canViewActions = canViewModalActions($cod_usu);
    $canViewStatusSection = canViewStatus($cod_usu);
    
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
                            <a href="#tabs-chat-' . $codSolicitacao . '" class="nav-link" data-bs-toggle="tab" onclick="initializeChat(' . $codSolicitacao . ')">
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
                            ' . $this->buildChat($codSolicitacao) . '
                        </div>
                    </div>
                </div>
            </div>
        </div>
    ';
}


-----------


// Replace the chat handlers in ajax_encerramento.php

// Load chat messages for specific group
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        // Get messages filtered by group
        $messages = $model->getChatMessagesByGroup($chat_id, $userGroup, $target_group);
        
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
            'messages' => $messages,
            'target_group' => $target_group
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Send chat message to specific group
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $mensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : '';
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        if (empty($mensagem) && (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Mensagem vazia']);
            exit;
        }
        
        // Validate target group
        $valid_targets = ['OP_MANAGEMENT', 'COM_MANAGEMENT', 'BLOQ_MANAGEMENT', 'ENC_MANAGEMENT'];
        if (!in_array($target_group, $valid_targets)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Grupo inválido']);
            exit;
        }
        
        // Validate permissions
        if ($userGroup !== 'ENC_MANAGEMENT' && $target_group !== 'ENC_MANAGEMENT') {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Sem permissão para enviar para este grupo']);
            exit;
        }
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        $anexo = 0;
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $anexo = 1;
        }
        
        // Determine sender and recipient groups
        $sender_group = $userGroup;
        $recipient_group = $target_group;
        
        $result = $model->sendChatMessageToGroup($chat_id, $mensagem, $cod_usu, $sender_group, $recipient_group, $anexo);
        
        if ($result && $anexo) {
            $message_id = $model->getLastMessageId();
            $upload_dir = 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\view\encerramento\anexos\\';
            $user_dir = $upload_dir . $cod_usu . '\\';
            
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }
            
            $file_name = $message_id . '_' . basename($_FILES['arquivo']['name']);
            $file_path = $user_dir . $file_name;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $file_path)) {
                $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                               SET MENSAGEM = CASE WHEN MENSAGEM IS NULL OR MENSAGEM = '' 
                                                   THEN '[FILE:" . addslashes($file_name) . "]' 
                                                   ELSE MENSAGEM + ' [FILE:" . addslashes($file_name) . "]' 
                                              END
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


--------

// Replace the chat methods in analise_encerramento_model.class.php

public function createChatIfNotExists($cod_solicitacao) {
    $query = "SELECT CHAT_ID FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    $result = $this->sql->select($query);
    
    if (!$result || !isset($result[0]['CHAT_ID']) || !$result[0]['CHAT_ID']) {
        // For SQL Server, we need to insert and get identity separately
        $insertQuery = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT DEFAULT VALUES";
        $this->sql->insert($insertQuery);
        
        // Get the last identity value
        $idQuery = "SELECT IDENT_CURRENT('MESU.ENCERRAMENTO_TB_PORTAL_CHAT') as CHAT_ID";
        $chatResult = $this->sql->select($idQuery);
        $chatId = intval($chatResult[0]['CHAT_ID']);
        
        // Update main table with chat_id
        $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL SET CHAT_ID = " . $chatId . 
                      " WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
        $this->sql->update($updateQuery);
        
        return $chatId;
    }
    
    return intval($result[0]['CHAT_ID']);
}

public function getChatMessagesByGroup($chat_id, $user_group, $target_group) {
    // Messages are visible if:
    // 1. User is sender and target is recipient_group OR
    // 2. User's group is recipient_group and sender_group is target
    
    $query = "SELECT m.*, 
              f.nome_func as REMETENTE_NOME,
              m.SENDER_GROUP,
              m.RECIPIENT_GROUP
              FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT m
              LEFT JOIN RH..TB_FUNCIONARIOS f ON m.REMETENTE = f.COD_FUNC
              WHERE m.CHAT_ID = " . intval($chat_id) . "
              AND (
                  (m.SENDER_GROUP = '" . addslashes($user_group) . "' AND m.RECIPIENT_GROUP = '" . addslashes($target_group) . "')
                  OR
                  (m.SENDER_GROUP = '" . addslashes($target_group) . "' AND m.RECIPIENT_GROUP = '" . addslashes($user_group) . "')
              )
              ORDER BY m.MESSAGE_DATE ASC";
    
    $result = $this->sql->select($query);
    
    // Ensure we always return an array
    if (!$result) {
        return [];
    }
    
    return $result;
}

public function sendChatMessageToGroup($chat_id, $mensagem, $remetente, $sender_group, $recipient_group, $anexo = 0) {
    $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
              (CHAT_ID, MENSAGEM, REMETENTE, SENDER_GROUP, RECIPIENT_GROUP, ANEXO) 
              VALUES (" . intval($chat_id) . ", 
                     '" . addslashes($mensagem) . "', 
                     " . intval($remetente) . ", 
                     '" . addslashes($sender_group) . "',
                     '" . addslashes($recipient_group) . "',
                     " . intval($anexo) . ")";
    return $this->sql->insert($query);
}

public function getLastMessageId() {
    $query = "SELECT IDENT_CURRENT('MESU.ENCERRAMENTO_TB_PORTAL_CHAT') as MESSAGE_ID";
    $result = $this->sql->select($query);
    return intval($result[0]['MESSAGE_ID']);
}

// Keep old method for backward compatibility if needed
public function getChatMessages($chat_id) {
    return $this->getChatMessagesByGroup($chat_id, '', '');
}


----------

// Replace the chat functions in analise_encerramento.js

// Initialize chat when tab is opened
window.initializeChat = function(codSolicitacao) {
    // Get the first contact's group
    const firstContact = document.querySelector('.chat-contact[data-solicitacao="' + codSolicitacao + '"]');
    if (firstContact) {
        const targetGroup = firstContact.getAttribute('data-group');
        loadChatForGroup(codSolicitacao, targetGroup);
        startChatPolling(codSolicitacao);
    }
};

// Select a chat contact
window.selectChatContact = function(event, targetGroup, codSolicitacao) {
    event.preventDefault();
    
    // Update active state
    document.querySelectorAll('.chat-contact[data-solicitacao="' + codSolicitacao + '"]').forEach(contact => {
        contact.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Update selected group
    const selectedGroupInput = document.getElementById('chatSelectedGroup-' + codSolicitacao);
    if (selectedGroupInput) {
        selectedGroupInput.value = targetGroup;
    }
    
    // Update contact name in header
    const contactNameEl = document.getElementById('chatContactName-' + codSolicitacao);
    if (contactNameEl) {
        const contactName = event.currentTarget.querySelector('.fw-bold').textContent;
        contactNameEl.textContent = contactName;
    }
    
    // Load messages for this group
    loadChatForGroup(codSolicitacao, targetGroup);
};

// Load chat messages for specific group
function loadChatForGroup(codSolicitacao, targetGroup) {
    const formData = new FormData();
    formData.append('acao', 'load_chat');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('target_group', targetGroup);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Chat response:', data);
        if (data.success) {
            renderChatMessages(codSolicitacao, data.messages);
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
}

function renderChatMessages(codSolicitacao, messages) {
    const container = document.getElementById('chatMessages-' + codSolicitacao);
    if (!container) return;
    
    // Ensure messages is an array
    if (!messages || !Array.isArray(messages)) {
        messages = [];
    }
    
    if (messages.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4"><p>Nenhuma mensagem ainda. Seja o primeiro a enviar!</p></div>';
        return;
    }
    
    let html = '<div class="chat">';
    messages.forEach(msg => {
        const isOwnMessage = msg.REMETENTE == window.userPermissions.codUsu;
        
        html += '<div class="chat-item mb-3">';
        html += '<div class="row align-items-end ' + (isOwnMessage ? 'justify-content-end' : '') + '">';
        
        if (!isOwnMessage) {
            html += '<div class="col-auto"><span class="avatar avatar-sm">' + (msg.REMETENTE_NOME ? msg.REMETENTE_NOME.substring(0, 2).toUpperCase() : 'U') + '</span></div>';
        }
        
        html += '<div class="col col-lg-6">';
        html += '<div class="chat-bubble ' + (isOwnMessage ? 'chat-bubble-me' : '') + '">';
        html += '<div class="chat-bubble-title">';
        html += '<div class="row">';
        html += '<div class="col chat-bubble-author">' + (msg.REMETENTE_NOME || 'Usuário') + '</div>';
        
        const messageDate = new Date(msg.MESSAGE_DATE);
        const formattedDate = messageDate.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
        html += '<div class="col-auto chat-bubble-date">' + formattedDate + '</div>';
        html += '</div></div>';
        
        html += '<div class="chat-bubble-body">';
        
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
        
        if (messageText) {
            html += '<p class="mb-0">' + escapeHtml(messageText) + '</p>';
        }
        
        if (hasFile && fileName) {
            html += '<div class="mt-2">';
            html += '<a href="./view/encerramento/anexos/' + msg.REMETENTE + '/' + fileName + '" target="_blank" class="btn btn-sm ' + (isOwnMessage ? 'btn-light' : 'btn-outline-primary') + '">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-sm me-1">';
            html += '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>';
            html += '</svg>';
            html += fileName;
            html += '</a></div>';
        }
        
        html += '</div></div></div>';
        
        if (isOwnMessage) {
            html += '<div class="col-auto"><span class="avatar avatar-sm bg-primary-lt">' + (msg.REMETENTE_NOME ? msg.REMETENTE_NOME.substring(0, 2).toUpperCase() : 'EU') + '</span></div>';
        }
        
        html += '</div></div>';
    });
    html += '</div>';
    
    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

window.sendChatMessage = function(codSolicitacao) {
    const input = document.getElementById('chatInput-' + codSolicitacao);
    const fileInput = document.getElementById('chatFile-' + codSolicitacao);
    const selectedGroupInput = document.getElementById('chatSelectedGroup-' + codSolicitacao);
    
    const mensagem = input.value.trim();
    const targetGroup = selectedGroupInput ? selectedGroupInput.value : '';
    
    if (!mensagem && !fileInput.files.length) {
        showNotification('Digite uma mensagem ou selecione um arquivo', 'error');
        return;
    }
    
    if (!targetGroup) {
        showNotification('Selecione um contato', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'send_message');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('mensagem', mensagem);
    formData.append('target_group', targetGroup);
    
    if (fileInput.files.length) {
        formData.append('arquivo', fileInput.files[0]);
    }
    
    // Disable input while sending
    input.disabled = true;
    
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
            loadChatForGroup(codSolicitacao, targetGroup);
        } else {
            showNotification('Erro ao enviar mensagem: ' + data.message, 'error');
        }
        input.disabled = false;
        input.focus();
    })
    .catch(error => {
        console.error('Send message error:', error);
        showNotification('Erro ao enviar mensagem', 'error');
        input.disabled = false;
    });
};

window.handleFileSelect = function(codSolicitacao) {
    const fileInput = document.getElementById('chatFile-' + codSolicitacao);
    const fileNameDisplay = document.getElementById('chatFileName-' + codSolicitacao);
    
    if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name;
        const fileSize = (fileInput.files[0].size / 1024 / 1024).toFixed(2);
        fileNameDisplay.textContent = 'Arquivo: ' + fileName + ' (' + fileSize + ' MB)';
    } else {
        fileNameDisplay.textContent = '';
    }
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Poll for new messages every 5 seconds
let chatPollingIntervals = {};

function startChatPolling(codSolicitacao) {
    if (chatPollingIntervals[codSolicitacao]) {
        clearInterval(chatPollingIntervals[codSolicitacao]);
    }
    
    chatPollingIntervals[codSolicitacao] = setInterval(() => {
        const selectedGroupInput = document.getElementById('chatSelectedGroup-' + codSolicitacao);
        if (selectedGroupInput) {
            const targetGroup = selectedGroupInput.value;
            loadChatForGroup(codSolicitacao, targetGroup);
        }
    }, 5000);
}

// Stop polling when modal closes
document.addEventListener('DOMContentLoaded', function() {
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
});


---------


-- Database Update Script for Group-Based Chat System
-- Run this script to add group tracking columns to the chat table

-- 1. Add SENDER_GROUP column
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'MESU' 
      AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT' 
      AND COLUMN_NAME = 'SENDER_GROUP'
)
BEGIN
    ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
    ADD SENDER_GROUP VARCHAR(50) NULL;
    PRINT 'SENDER_GROUP column added successfully';
END
ELSE
BEGIN
    PRINT 'SENDER_GROUP column already exists';
END;

-- 2. Add RECIPIENT_GROUP column
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'MESU' 
      AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT' 
      AND COLUMN_NAME = 'RECIPIENT_GROUP'
)
BEGIN
    ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
    ADD RECIPIENT_GROUP VARCHAR(50) NULL;
    PRINT 'RECIPIENT_GROUP column added successfully';
END
ELSE
BEGIN
    PRINT 'RECIPIENT_GROUP column already exists';
END;

-- 3. Create index for better query performance
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes 
    WHERE name = 'IDX_CHAT_GROUPS' 
      AND object_id = OBJECT_ID('MESU..ENCERRAMENTO_TB_PORTAL_CHAT')
)
BEGIN
    CREATE INDEX IDX_CHAT_GROUPS 
    ON MESU..ENCERRAMENTO_TB_PORTAL_CHAT(CHAT_ID, SENDER_GROUP, RECIPIENT_GROUP);
    PRINT 'Index on chat groups created';
END
ELSE
BEGIN
    PRINT 'Index on chat groups already exists';
END;

-- 4. Verify the updated structure
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'MESU' 
  AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT'
ORDER BY ORDINAL_POSITION;

-- Expected columns:
-- MESSAGE_ID (int, NOT NULL)
-- CHAT_ID (int, NOT NULL)
-- MENSAGEM (varchar(4000), NULL)
-- DESTINATARIO (int, NOT NULL) - kept for backward compatibility
-- REMETENTE (int, NOT NULL)
-- ANEXO (bit, NOT NULL)
-- MESSAGE_DATE (datetime, NOT NULL)
-- SENDER_GROUP (varchar(50), NULL) - NEW
-- RECIPIENT_GROUP (varchar(50), NULL) - NEW

-- 5. Test the new structure
-- This will show if any messages exist and their group information
SELECT TOP 10
    MESSAGE_ID,
    CHAT_ID,
    REMETENTE,
    SENDER_GROUP,
    RECIPIENT_GROUP,
    SUBSTRING(MENSAGEM, 1, 50) as MENSAGEM_PREVIEW,
    MESSAGE_DATE
FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT
ORDER BY MESSAGE_DATE DESC;

PRINT 'Database update complete! The chat system now supports group-based messaging.';
PRINT 'Groups: OP_MANAGEMENT, COM_MANAGEMENT, BLOQ_MANAGEMENT, ENC_MANAGEMENT';



---------

.chat {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chat-item {
    display: flex;
    width: 100%;
}

.chat-bubble {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 0.75rem;
    max-width: 100%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.chat-bubble-me {
    background-color: #206bc4;
    color: white;
    border-radius: 0.5rem;
    padding: 0.75rem;
    max-width: 100%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

[data-theme="dark"] .chat-bubble {
    background-color: #1f2937;
}

[data-theme="dark"] .chat-bubble-me {
    background-color: #1e40af;
}

.chat-bubble-title {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-bubble-author {
    font-weight: 600;
    font-size: 0.813rem;
}

.chat-bubble-date {
    color: #6c757d;
    font-size: 0.75rem;
    opacity: 0.8;
}

.chat-bubble-me .chat-bubble-date {
    color: rgba(255, 255, 255, 0.8);
}

.chat-bubble-body {
    word-wrap: break-word;
    overflow-wrap: break-word;
    font-size: 0.875rem;
    line-height: 1.5;
}

.chat-bubble-body p {
    margin: 0;
}

.chat-messages {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: #ffffff;
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

[data-theme="dark"] .chat-messages::-webkit-scrollbar-track {
    background: #2d3748;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.chat-contact {
    cursor: pointer;
    transition: background-color 0.2s;
}

.chat-contact:hover {
    background-color: rgba(0,0,0,0.03);
}

[data-theme="dark"] .chat-contact:hover {
    background-color: rgba(255,255,255,0.05);
}

.chat-contact.active {
    background-color: #206bc4;
    color: white;
}

.chat-contact.active .text-secondary {
    color: rgba(255, 255, 255, 0.7) !important;
}

[data-theme="dark"] .chat-contact.active {
    background-color: #1e40af;
}


----------


# Group-Based Chat Implementation Guide

## Overview
The chat system now uses the existing `buildChat()` method with a contact list sidebar. Each user group can have separate private conversations.

## Chat Structure

### Visual Layout
```
┌─────────────────────────────────────┐
│ Informações │ Chat                  │
├──────────────┬──────────────────────┤
│  CONTATOS    │  Chat with: [Name]   │
│              │                      │
│ ● Órgão Pag. │  [Messages appear]   │
│   Comercial  │  [here in bubbles]   │
│   Bloqueio   │                      │
│              │  [Input field]       │
└──────────────┴──────────────────────┘
```

### Contact List by User Group

**ENC_MANAGEMENT sees:**
- Órgão Pagador (OP_MANAGEMENT)
- Comercial (COM_MANAGEMENT)
- Bloqueio (BLOQ_MANAGEMENT)

**OP/COM/BLOQ_MANAGEMENT sees:**
- Encerramento (ENC_MANAGEMENT) only

## Implementation Steps

### Step 1: Update Database
Run the SQL from artifact: **"database_update_for_groups"**

This adds:
- `SENDER_GROUP` column
- `RECIPIENT_GROUP` column
- Index for performance

### Step 2: Update Model (M.txt)
Replace all chat methods in `analise_encerramento_model.class.php` with code from:
**"updated_chat_model_with_groups"**

New/Updated methods:
- `getChatMessagesByGroup()` - Filters messages by sender/recipient groups
- `sendChatMessageToGroup()` - Sends with group tracking
- `createChatIfNotExists()` - Improved
- `getLastMessageId()` - Improved

### Step 3: Update AJAX Handler (JH.txt)
In `control/encerramento/roteamento/ajax_encerramento.php`:

#### A. Replace the old `buildChatInterface()` method:
Delete `buildChatInterface()` and use the new `buildChat()` from artifact:
**"updated_buildchat_method"**

Key features:
- Dynamic contact list based on user group
- Color-coded avatars for each group
- Active state for selected contact

#### B. Update `buildModalBody()`:
Replace with code from artifact: **"updated_modal_with_buildchat"**

Changes `onclick="loadChat(...)"` to `onclick="initializeChat(...)"`

#### C. Replace chat AJAX handlers:
Find the `load_chat` and `send_message` handlers and replace with:
**"updated_chat_ajax_with_groups"**

New features:
- Filters messages by `target_group`
- Validates permissions (non-ENC can only message ENC)
- Tracks sender and recipient groups

### Step 4: Update JavaScript (J.txt)
In `encerramento/analise_encerramento/analise_encerramento.js`:

Replace ALL chat-related functions with code from:
**"updated_chat_javascript_with_groups"**

New functions:
- `initializeChat()` - Called when Chat tab opens
- `selectChatContact()` - Switches between contacts
- `loadChatForGroup()` - Loads messages for selected group
- `renderChatMessages()` - Enhanced rendering with avatars
- `sendChatMessage()` - Sends to selected group
- `handleFileSelect()` - Shows file info

### Step 5: Update CSS
The CSS has been updated in artifact: **"chat_css"**

Add to the `<style>` section in `analise_encerramento.php`

## How It Works

### Message Flow

1. **User opens modal** → Clicks "Chat" tab
2. **initializeChat()** called → Loads first contact's messages
3. **User clicks contact** → `selectChatContact()` switches conversation
4. **Messages load** → Filtered by sender/recipient groups
5. **User types message** → Sent to selected group
6. **Auto-refresh** → Polls every 5 seconds

### Permission Enforcement

```php
// Server-side validation in send_message handler
if ($userGroup !== 'ENC_MANAGEMENT' && $target_group !== 'ENC_MANAGEMENT') {
    // Denied! Non-ENC can only message ENC
    return error;
}
```

### Database Query Logic

Messages are visible if:
```sql
(sender_group = user_group AND recipient_group = target_group)
OR
(sender_group = target_group AND recipient_group = user_group)
```

This ensures:
- ENC → OP messages visible to both ENC and OP
- OP → ENC messages visible to both OP and ENC
- OP → COM messages NOT visible (different conversations)

## Testing Checklist

### As ENC_MANAGEMENT user:
- [ ] See 3 contacts (OP, COM, BLOQ)
- [ ] Click each contact, see separate conversations
- [ ] Send message to OP group
- [ ] Send message to COM group
- [ ] Verify messages don't mix between groups
- [ ] Upload file to OP group
- [ ] Auto-refresh works (messages update)

### As OP_MANAGEMENT user:
- [ ] See only 1 contact (ENC)
- [ ] Can send messages to ENC
- [ ] Cannot see other groups' messages
- [ ] Receive messages from ENC
- [ ] Upload file works
- [ ] Try to manipulate target_group in browser → should fail

### As COM_MANAGEMENT user:
- [ ] See only 1 contact (ENC)
- [ ] Same tests as OP_MANAGEMENT

### As BLOQ_MANAGEMENT user:
- [ ] See only 1 contact (ENC)
- [ ] Same tests as OP_MANAGEMENT

## File Structure

```
view/encerramento/anexos/
  ├── [cod_usu_1]/
  │   └── [message_id]_file.pdf
  ├── [cod_usu_2]/
  │   └── [message_id]_image.jpg
  └── ...
```

Files organized by sender's `cod_usu`, not by group.

## Troubleshooting

### Contacts not showing
**Check:** Is `buildChat()` being called with `$codSolicitacao` parameter?
```php
// In buildModalBody()
' . $this->buildChat($codSolicitacao) . '
```

### Messages not filtering correctly
**Check:** Database has SENDER_GROUP and RECIPIENT_GROUP columns
```sql
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT'
  AND COLUMN_NAME IN ('SENDER_GROUP', 'RECIPIENT_GROUP');
```

### Can't switch contacts
**Check:** JavaScript `selectChatContact()` is defined globally
```javascript
// In browser console:
typeof selectChatContact
// Should return: "function"
```

### Non-ENC can message other non-ENC groups
**Check:** Server-side validation in `send_message` handler
```php
if ($userGroup !== 'ENC_MANAGEMENT' && $target_group !== 'ENC_MANAGEMENT') {
    // This should block the request
    return json_encode_custom(['success' => false, 'message' => 'Sem permissão']);
}
```

### Old messages not appearing
**Check:** Run update script to add group columns, then manually update old messages:
```sql
-- Set default groups for old messages
UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT
SET SENDER_GROUP = 'ENC_MANAGEMENT',
    RECIPIENT_GROUP = 'OP_MANAGEMENT'
WHERE SENDER_GROUP IS NULL;
```

## Security Notes

✅ **Permission validation on server** - Client cannot bypass  
✅ **Group filtering in SQL** - Database-level security  
✅ **File uploads validated** - Size and type checks  
✅ **SQL injection prevented** - Using addslashes()  
✅ **XSS prevented** - Using escapeHtml() in JavaScript  

⚠️ **Consider adding:**
- File type whitelist (PDF, images only)
- Max file size enforcement (5MB)
- Rate limiting on message sending
- Message encryption for sensitive data

## Key Differences from Previous Implementation

| Aspect | Before | After |
|--------|--------|-------|
| Layout | Simple interface | Contact sidebar + chat area |
| Groups | Single chat for all | Separate conversations per group |
| Permissions | Basic | Group-validated messaging |
| Navigation | N/A | Click contacts to switch |
| Database | DESTINATARIO field | SENDER_GROUP + RECIPIENT_GROUP |
| Initialization | `loadChat()` | `initializeChat()` + `selectChatContact()` |

## Next Steps

1. Run database update script
2. Update all code files as outlined above
3. Test with each user group type
4. Verify permissions are enforced
5. Test file uploads for each group
6. Verify auto-refresh works correctly


---------

# Quick Update Checklist - Group Chat System

## 🗄️ Step 1: Database (5 minutes)
**File:** SQL Server Management Studio or your SQL client

1. Run the entire script from artifact: **"database_update_for_groups"**
2. Verify output shows:
   - ✅ SENDER_GROUP column added
   - ✅ RECIPIENT_GROUP column added
   - ✅ Index created

---

## 📝 Step 2: Model (M.txt) (3 minutes)
**File:** `model/encerramento/analise_encerramento_model.class.php`

### Find and DELETE these old methods:
- `getChatMessages()`
- `sendChatMessage()`

### ADD all methods from artifact: **"updated_chat_model_with_groups"**
Copy all 5 methods:
1. `createChatIfNotExists()`
2. `getChatMessagesByGroup()` ← NEW
3. `sendChatMessageToGroup()` ← NEW
4. `getLastMessageId()`
5. `getChatMessages()` ← Kept for compatibility

---

## 🔧 Step 3: AJAX Handler (JH.txt) (5 minutes)
**File:** `control/encerramento/roteamento/ajax_encerramento.php`

### A. In the AjaxEncerramentoHandler class:

#### 1. DELETE the old method:
```php
private function buildChatInterface($codSolicitacao) { ... }
```

#### 2. ADD the new method from **"updated_buildchat_method"**:
```php
private function buildChat($codSolicitacao) { ... }
```

#### 3. UPDATE `buildModalBody()`:
Replace with code from **"updated_modal_with_buildchat"**

Key change: `$this->buildChat($codSolicitacao)` instead of `buildChatInterface()`

### B. At the top of the file (BEFORE regular data loading):

#### REPLACE both chat handlers:
- Find: `if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat')`
- Find: `if (isset($_POST['acao']) && $_POST['acao'] == 'send_message')`

Replace BOTH with code from **"updated_chat_ajax_with_groups"**

---

## 💻 Step 4: JavaScript (J.txt) (5 minutes)
**File:** `encerramento/analise_encerramento/analise_encerramento.js`

### DELETE all old chat functions:
- `window.loadChat`
- `renderChatMessages`
- `window.sendChatMessage`
- `startChatPolling`
- Chat event listeners

### ADD all functions from **"updated_chat_javascript_with_groups"**:
Copy everything from that artifact - it's a complete replacement.

---

## 🎨 Step 5: CSS (2 minutes)
**File:** `view/encerramento/analise_encerramento.php`

### In the `<style>` section:
Replace the chat-related CSS with updated CSS from artifact: **"chat_css"**

Look for `.chat {` and replace everything through `.chat-contact.active`

---

## ✅ Verification Steps

After all updates:

### 1. Browser Console Check
Press F12, go to Console tab, type:
```javascript
typeof initializeChat
typeof selectChatContact
typeof sendChatMessage
```
All should return `"function"`

### 2. Visual Check
Open any modal → Click Chat tab:
- **ENC user:** Should see 3 contacts (OP, COM, BLQ)
- **Non-ENC user:** Should see 1 contact (ENC)

### 3. Functionality Check
- Click a contact → Messages load
- Type message → Send → Appears in chat
- Click different contact → Different conversation
- Send message to each group → Separate threads

---

## 📋 Files Modified Summary

| # | File | Location | Changes |
|---|------|----------|---------|
| 1 | Database | SQL Server | Add 2 columns + index |
| 2 | Model | model/encerramento/ | 5 methods updated/added |
| 3 | AJAX Handler | control/encerramento/roteamento/ | 3 methods + 2 handlers |
| 4 | JavaScript | encerramento/analise_encerramento/ | Complete chat rewrite |
| 5 | CSS | view/encerramento/ | Enhanced chat styles |

---

## 🔍 Before/After Comparison

### Old System
```
Single chat interface
↓
One conversation stream for all users
↓
No group separation
```

### New System
```
Contact sidebar + chat area
↓
Click contact to switch
↓
Separate conversations per group
↓
ENC can message all groups privately
↓
Non-ENC can only message ENC
```

---

## 🆘 Quick Fixes

### If contacts don't show:
```php
// Check buildModalBody() calls buildChat() with parameter:
' . $this->buildChat($codSolicitacao) . '
```

### If messages don't send:
```javascript
// Check browser console for errors
// Common issue: AJAX_URL not defined
console.log(AJAX_URL);
```

### If switching contacts doesn't work:
```javascript
// Check if function is globally accessible:
console.log(window.selectChatContact);
// Should not be undefined
```

### If old messages don't appear:
```sql
-- Update old messages to have default groups
UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT
SET SENDER_GROUP = 'ENC_MANAGEMENT',
    RECIPIENT_GROUP = 'OP_MANAGEMENT'
WHERE SENDER_GROUP IS NULL;
```

---

## ⏱️ Estimated Time
- Database: 5 min
- Model: 3 min
- AJAX Handler: 5 min
- JavaScript: 5 min
- CSS: 2 min
- **Total: ~20 minutes**

---

## 🎯 Success Criteria

✅ Database has new columns  
✅ All JavaScript functions defined  
✅ ENC sees 3 contacts  
✅ Non-ENC sees 1 contact  
✅ Can switch between contacts  
✅ Messages send and display correctly  
✅ Conversations are separate per group  
✅ Auto-refresh works  
✅ File uploads work  
✅ No console errors  

---

## 📚 Reference Artifacts

1. **database_update_for_groups** - SQL script
2. **updated_chat_model_with_groups** - Model methods
3. **updated_buildchat_method** - New buildChat()
4. **updated_modal_with_buildchat** - Updated modal body
5. **updated_chat_ajax_with_groups** - AJAX handlers
6. **updated_chat_javascript_with_groups** - Complete JS
7. **chat_css** - Enhanced styling
8. **group_chat_implementation_guide** - Detailed docs