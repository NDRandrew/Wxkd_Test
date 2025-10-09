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


