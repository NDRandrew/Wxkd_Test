I'll help you remove email sending from TXT generation and add chat message notifications. Here are the necessary code changes:

## 1. Database Schema Change

<artifact identifier="db-schema-chat" type="application/vnd.ant.code" language="sql" title="Add READ_FLAG to Chat Table">
-- Add READ_FLAG column to track unread messages
ALTER TABLE ENCERRAMENTO_TB_PORTAL_CHAT 
ADD READ_FLAG BIT NULL DEFAULT 0;

-- Add index for performance
CREATE INDEX IX_CHAT_READ_FLAG ON ENCERRAMENTO_TB_PORTAL_CHAT(DESTINATARIO, READ_FLAG, REMETENTE);
</artifact>

## 2. Update ajax_encerramento.php (JH.txt)

<artifact identifier="ajax-handler-updates" type="application/vnd.ant.code" language="php" title="Remove Email Sending & Add Chat Count">
// REMOVE these sections from both 'gerar_txt_selection' and 'gerar_txt_excel' actions:
// DELETE THIS CODE:
/*
if ($result['has_errors']) {
    require_once 'X:\...\email_functions.php';
    sendTXTErrorEmail($result['errors'], $result['nomeArquivo']);
}
*/

// ADD: New action to get unread chat message count
if (isset($_POST['acao']) && $_POST['acao'] == 'get_unread_chat_count') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        $model = new Analise();
        $count = $model->getUnreadChatCount($cod_usu, $userGroup);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'count' => $count
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// UPDATE: Mark messages as read when loading chat
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
        
        // Mark messages as read
        $model->markChatMessagesAsRead($chat_id, $cod_usu, $userGroup, $target_group);
        
        $messages = $model->getChatMessagesByGroup($chat_id, $userGroup, $target_group);
        
        if (!$messages) {
            $messages = [];
        } else if (!is_array($messages)) {
            $messages = [$messages];
        }
        
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
</artifact>

## 3. Update Model (M.txt)

<artifact identifier="model-updates" type="application/vnd.ant.code" language="php" title="Add Chat Notification Methods">
// ADD these methods to the Analise class:

public function getUnreadChatCount($cod_usu, $userGroup) {
    $query = "SELECT COUNT(*) as TOTAL 
            FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
            WHERE (READ_FLAG IS NULL OR READ_FLAG = 0)
            AND REMETENTE != " . intval($cod_usu) . "
            AND (
                (RECIPIENT_GROUP = '" . addslashes($userGroup) . "')
                OR 
                (SENDER_GROUP = '" . addslashes($userGroup) . "' AND RECIPIENT_GROUP = 'ENC_MANAGEMENT')
            )";
    $dados = $this->sql->select($query);
    return $dados[0]['TOTAL'];
}

public function markChatMessagesAsRead($chat_id, $cod_usu, $userGroup, $target_group) {
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
            SET READ_FLAG = 1 
            WHERE CHAT_ID = " . intval($chat_id) . "
            AND REMETENTE != " . intval($cod_usu) . "
            AND (
                (SENDER_GROUP = '" . addslashes($target_group) . "' AND RECIPIENT_GROUP = '" . addslashes($userGroup) . "')
            )
            AND (READ_FLAG IS NULL OR READ_FLAG = 0)";
    return $this->sql->update($query);
}
</artifact>

## 4. Update JavaScript (J.txt)

<artifact identifier="js-updates" type="application/vnd.ant.code" language="javascript" title="Add Chat Badge Updates">
// ADD: Function to update unread chat count
function updateUnreadChatCount() {
    const formData = new FormData();
    formData.append('acao', 'get_unread_chat_count');
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('chatBadge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        }
    })
    .catch(error => console.error('Error updating chat count:', error));
}

// MODIFY: loadChatForGroup to mark as read and update badge
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
            updateUnreadChatCount(); // Update badge after loading
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

// MODIFY: initialize function to include chat badge update
function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers();
    initializeColumnSort();
    highlightActiveFilters();
    attachPageNumberHandlers();
    initializeViewSwitching();
    updateUnreadChatCount(); // Add this line
    
    // Poll for new messages every 30 seconds
    setInterval(updateUnreadChatCount, 30000);
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}

// MODIFY: sendChatMessage to update badge after sending
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
            updateUnreadChatCount(); // Update badge after sending
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
</artifact>

## 5. Update View (E.txt)

<artifact identifier="view-chat-badge" type="application/vnd.ant.code" language="php" title="Add Chat Badge to Header">
<!-- MODIFY: Add chat badge to the tab header -->
<!-- Find the chat tab in buildModalBody and add badge: -->

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
        <span id="chatBadge" class="badge badge-sm bg-red text-red-fg" style="position:relative; bottom:6px; margin-left:2px; display: none;">0</span>
    </a>
</li>
</artifact>

## Summary of Changes:

1. **Removed email sending** from TXT generation in `ajax_encerramento.php`
2. **Added `READ_FLAG`** column to track unread messages
3. **Added methods** to count unread messages and mark as read
4. **Added badge** to display unread message count
5. **Added auto-update** every 30 seconds for the badge
6. **Errors are stored** in `ENCERRAMENTO_TB_PORTAL_OCORRENCIA` table (already implemented)

The system now stores TXT errors in the database instead of sending emails, and displays a notification badge for unread chat messages similar to the Ocorrências feature.