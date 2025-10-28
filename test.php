## Fix: executeBulkEmail event.target error

**In J.txt (analise_encerramento.js)**, fix the bulk email functions:

```javascript
window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    // Store the button reference
    const btnElement = event.target.closest('button');

    const formData = new FormData();
    formData.append('acao', 'check_bulk_status');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.has_pendentes) {
            showBulkWarningModal(data.pendentes, tipo, solicitacoes, btnElement);
        } else {
            executeBulkEmail(tipo, solicitacoes, btnElement);
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        executeBulkEmail(tipo, solicitacoes, btnElement);
    });
};

function showBulkWarningModal(pendentes, tipo, solicitacoes, btnElement) {
    let message = '<div class="mb-3">As seguintes lojas possuem status pendentes:</div>';
    message += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    message += '<table class="table table-sm"><thead><tr><th>Chave Loja</th><th>Status Pendentes</th></tr></thead><tbody>';
    
    for (const [chave, status] of Object.entries(pendentes)) {
        message += `<tr><td>${chave}</td><td class="text-danger">${status.join(', ')}</td></tr>`;
    }
    
    message += '</tbody></table></div>';
    message += '<div class="mt-3">Deseja continuar mesmo assim?</div>';
    
    if (confirm(message)) {
        executeBulkEmail(tipo, solicitacoes, btnElement);
    }
}

function executeBulkEmail(tipo, solicitacoes, btnElement) {
    if (!btnElement) {
        showNotification('Erro: Elemento do botão não encontrado', 'error');
        return;
    }

    const originalText = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

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
            const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
            if (headerCheckbox) headerCheckbox.checked = false;
            updateBulkActionButtons();
            handleFormSubmit();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    })
    .catch(error => {
        showNotification('Erro ao enviar emails', 'error');
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    });
}
```

The fix:
1. Captures `event.target` as `btnElement` in `sendBulkEmail` before async operations
2. Passes `btnElement` through the chain: `sendBulkEmail` → `showBulkWarningModal` → `executeBulkEmail`
3. `executeBulkEmail` now receives the button as a parameter instead of relying on global `event`