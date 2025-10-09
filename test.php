-- Add ANEXO_LOCATION column to store file paths separately from message text

-- 1. Add the new column
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'MESU' 
      AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT' 
      AND COLUMN_NAME = 'ANEXO_LOCATION'
)
BEGIN
    ALTER TABLE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
    ADD ANEXO_LOCATION VARCHAR(500) NULL;
    PRINT 'ANEXO_LOCATION column added successfully';
END
ELSE
BEGIN
    PRINT 'ANEXO_LOCATION column already exists';
END;

-- 2. Migrate existing file data from MENSAGEM to ANEXO_LOCATION
-- This finds messages with [FILE:...] pattern and extracts the filename
UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT
SET ANEXO_LOCATION = SUBSTRING(
    MENSAGEM, 
    CHARINDEX('[FILE:', MENSAGEM) + 6, 
    CHARINDEX(']', MENSAGEM, CHARINDEX('[FILE:', MENSAGEM)) - CHARINDEX('[FILE:', MENSAGEM) - 6
),
MENSAGEM = LTRIM(RTRIM(REPLACE(MENSAGEM, 
    SUBSTRING(MENSAGEM, CHARINDEX('[FILE:', MENSAGEM), 
    CHARINDEX(']', MENSAGEM, CHARINDEX('[FILE:', MENSAGEM)) - CHARINDEX('[FILE:', MENSAGEM) + 1), 
    '')))
WHERE MENSAGEM LIKE '%[FILE:%]%'
  AND ANEXO_LOCATION IS NULL;

PRINT 'Migrated ' + CAST(@@ROWCOUNT AS VARCHAR) + ' existing file references';

-- 3. Verify the structure
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    CHARACTER_MAXIMUM_LENGTH
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'MESU' 
  AND TABLE_NAME = 'ENCERRAMENTO_TB_PORTAL_CHAT'
  AND COLUMN_NAME IN ('MENSAGEM', 'ANEXO', 'ANEXO_LOCATION')
ORDER BY ORDINAL_POSITION;

-- 4. Check migrated data
SELECT TOP 10
    MESSAGE_ID,
    CHAT_ID,
    REMETENTE,
    LEFT(MENSAGEM, 50) as MENSAGEM_PREVIEW,
    ANEXO,
    ANEXO_LOCATION,
    MESSAGE_DATE
FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT
WHERE ANEXO = 1
ORDER BY MESSAGE_DATE DESC;

-- 5. Create index for better performance
IF NOT EXISTS (
    SELECT 1 FROM sys.indexes 
    WHERE name = 'IDX_ANEXO' 
      AND object_id = OBJECT_ID('MESU..ENCERRAMENTO_TB_PORTAL_CHAT')
)
BEGIN
    CREATE INDEX IDX_ANEXO 
    ON MESU..ENCERRAMENTO_TB_PORTAL_CHAT(ANEXO, ANEXO_LOCATION);
    PRINT 'Index on ANEXO created';
END;

PRINT '';
PRINT '=== Summary ===';
PRINT 'Column ANEXO_LOCATION added to store file paths';
PRINT 'Messages with [FILE:...] patterns have been migrated';
PRINT 'File location now stored separately from message text';
PRINT '';
PRINT 'Table structure:';
PRINT '- MENSAGEM: Message text only (no file references)';
PRINT '- ANEXO: 1 if file attached, 0 if not';
PRINT '- ANEXO_LOCATION: Filename (e.g., "123_document.pdf")';

---------

// Send chat message to specific group - UPDATED to use ANEXO_LOCATION
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        // Check if message or file exists
        $hasFile = isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK;
        
        if (empty($mensagem) && !$hasFile) {
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
        
        // Handle file upload first if present
        $anexo_location = null;
        if ($hasFile) {
            $upload_dir = 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\view\encerramento\anexos\\';
            $user_dir = $upload_dir . $cod_usu . '\\';
            
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }
            
            // Generate temporary filename for now (will update with MESSAGE_ID after insert)
            $temp_file_name = time() . '_' . basename($_FILES['arquivo']['name']);
            $temp_file_path = $user_dir . $temp_file_name;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $temp_file_path)) {
                $anexo_location = $temp_file_name;
            } else {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode_custom(['success' => false, 'message' => 'Erro ao fazer upload do arquivo']);
                exit;
            }
        }
        
        // Determine sender and recipient groups
        $sender_group = $userGroup;
        $recipient_group = $target_group;
        
        // Send message with file info
        $anexo = $hasFile ? 1 : 0;
        $result = $model->sendChatMessageToGroup($chat_id, $mensagem, $cod_usu, $sender_group, $recipient_group, $anexo);
        
        // Update with proper filename including MESSAGE_ID
        if ($result && $hasFile && $anexo_location) {
            $message_id = $model->getLastMessageId();
            
            // Rename file to include MESSAGE_ID
            $final_file_name = $message_id . '_' . basename($_FILES['arquivo']['name']);
            $user_dir = $upload_dir . $cod_usu . '\\';
            $old_path = $user_dir . $anexo_location;
            $new_path = $user_dir . $final_file_name;
            
            if (file_exists($old_path)) {
                rename($old_path, $new_path);
            }
            
            // Update database with final filename
            $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                           SET ANEXO_LOCATION = '" . addslashes($final_file_name) . "'
                           WHERE MESSAGE_ID = " . $message_id;
            $model->update($updateQuery);
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
        
        // Parse SQL Server datetime format (YYYY-MM-DD HH:MM:SS)
        let formattedDate = '';
        if (msg.MESSAGE_DATE) {
            try {
                // SQL Server datetime comes as string: "2025-01-15 14:30:45"
                // Replace space with 'T' for proper ISO format, or parse manually
                let dateStr = msg.MESSAGE_DATE;
                
                // Handle SQL Server datetime format
                if (typeof dateStr === 'string') {
                    // Replace space with T for ISO format: "2025-01-15T14:30:45"
                    dateStr = dateStr.replace(' ', 'T');
                }
                
                const messageDate = new Date(dateStr);
                
                // Check if date is valid
                if (!isNaN(messageDate.getTime())) {
                    const day = String(messageDate.getDate()).padStart(2, '0');
                    const month = String(messageDate.getMonth() + 1).padStart(2, '0');
                    const hours = String(messageDate.getHours()).padStart(2, '0');
                    const minutes = String(messageDate.getMinutes()).padStart(2, '0');
                    formattedDate = day + '/' + month + ' ' + hours + ':' + minutes;
                } else {
                    formattedDate = dateStr; // Show raw string if parsing fails
                }
            } catch (e) {
                console.error('Date parsing error:', e, msg.MESSAGE_DATE);
                formattedDate = msg.MESSAGE_DATE || '';
            }
        }
        
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

----------

// Replace the chat methods in analise_encerramento_model.class.php

public function createChatIfNotExists($cod_solicitacao) {
    $query = "SELECT CHAT_ID FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    $result = $this->sql->select($query);
    
    if (!$result || !isset($result[0]['CHAT_ID']) || !$result[0]['CHAT_ID']) {
        // Use COD_SOLICITACAO as CHAT_ID (each solicitacao has one chat)
        $chatId = intval($cod_solicitacao);
        
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
              m.RECIPIENT_GROUP,
              m.ANEXO,
              m.ANEXO_LOCATION
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
    // Allow empty message if file is attached
    if (empty($mensagem)) {
        $mensagem = ''; // Ensure it's empty string, not NULL
    }
    
    $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
              (CHAT_ID, MENSAGEM, DESTINATARIO, REMETENTE, SENDER_GROUP, RECIPIENT_GROUP, ANEXO) 
              VALUES (" . intval($chat_id) . ", 
                     '" . addslashes($mensagem) . "', 
                     0, 
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

// Add this helper function at the top (after json_encode_custom function)

function formatMessagesForJson($messages) {
    if (!is_array($messages)) {
        return $messages;
    }
    
    foreach ($messages as &$message) {
        // Convert DateTime objects to strings
        if (isset($message['MESSAGE_DATE'])) {
            if (is_object($message['MESSAGE_DATE'])) {
                $message['MESSAGE_DATE'] = $message['MESSAGE_DATE']->format('Y-m-d H:i:s');
            }
        }
    }
    
    return $messages;
}

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
        
        // Convert DateTime objects to strings for JSON
        $messages = formatMessagesForJson($messages);
        
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

// Send chat message to specific group - UPDATED to use ANEXO_LOCATION
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        // Check if message or file exists
        $hasFile = isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK;
        
        if (empty($mensagem) && !$hasFile) {
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
        
        // Handle file upload first if present
        $anexo_location = null;
        if ($hasFile) {
            $upload_dir = 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\view\encerramento\anexos\\';
            $user_dir = $upload_dir . $cod_usu . '\\';
            
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }
            
            // Generate temporary filename for now (will update with MESSAGE_ID after insert)
            $temp_file_name = time() . '_' . basename($_FILES['arquivo']['name']);
            $temp_file_path = $user_dir . $temp_file_name;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $temp_file_path)) {
                $anexo_location = $temp_file_name;
            } else {
                ob_end_clean();
                header('Content-Type: application/json');
                echo json_encode_custom(['success' => false, 'message' => 'Erro ao fazer upload do arquivo']);
                exit;
            }
        }
        
        // Determine sender and recipient groups
        $sender_group = $userGroup;
        $recipient_group = $target_group;
        
        // Send message with file info
        $anexo = $hasFile ? 1 : 0;
        $result = $model->sendChatMessageToGroup($chat_id, $mensagem, $cod_usu, $sender_group, $recipient_group, $anexo);
        
        // Update with proper filename including MESSAGE_ID
        if ($result && $hasFile && $anexo_location) {
            $message_id = $model->getLastMessageId();
            
            // Rename file to include MESSAGE_ID
            $final_file_name = $message_id . '_' . basename($_FILES['arquivo']['name']);
            $user_dir = $upload_dir . $cod_usu . '\\';
            $old_path = $user_dir . $anexo_location;
            $new_path = $user_dir . $final_file_name;
            
            if (file_exists($old_path)) {
                rename($old_path, $new_path);
            }
            
            // Update database with final filename in ANEXO_LOCATION column
            $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                           SET ANEXO_LOCATION = '" . addslashes($final_file_name) . "'
                           WHERE MESSAGE_ID = " . $message_id;
            $model->update($updateQuery);
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

---------


