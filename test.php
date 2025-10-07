The user is right - I referenced `executeBulkEmail` but it doesn't exist in the original code. The original code has the bulk email logic directly in `window.sendBulkEmail`. I need to refactor this properly to separate the validation from the execution.

Let me rewrite the JS part correctly, integrating with the existing code structure.

## 4. JavaScript (J (4).txt) - Complete rewrite:

```javascript
// Add to initialize()
function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers();
    initializeSorting(); // ADD THIS
    highlightActiveFilters();
    attachPageNumberHandlers();
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}

// ADD: Sorting functionality
function initializeSorting() {
    let currentSortColumn = null;
    let currentSortOrder = null;
    
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            
            // Toggle order
            if (currentSortColumn === column) {
                currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = column;
                currentSortOrder = 'asc';
            }
            
            // Update UI
            document.querySelectorAll('.sortable').forEach(h => {
                h.removeAttribute('data-order');
                const icon = h.querySelector('.sort-icon');
                if (icon) icon.textContent = '⇅';
            });
            
            this.setAttribute('data-order', currentSortOrder);
            const icon = this.querySelector('.sort-icon');
            if (icon) icon.textContent = currentSortOrder === 'asc' ? '↑' : '↓';
            
            // Reload data with sorting
            currentPage = 1;
            handleFormSubmit(currentSortColumn, currentSortOrder);
        });
    });
}

// MODIFY: Update handleFormSubmit to accept sort parameters
function handleFormSubmit(sortColumn, sortOrder) {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }
    
    // Add sorting parameters
    if (sortColumn) {
        params.append('sort_column', sortColumn);
        params.append('sort_order', sortOrder);
    }
    
    params.append('page', currentPage);
    params.append('per_page', perPage);
    
    showLoading();
    
    fetch(AJAX_URL + '?' + params.toString())
        .then(response => {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.text();
        })
        .then(text => {
            const data = JSON.parse(text);
            
            if (data.success) {
                updateUI(data, params);
            } else {
                throw new Error(data.error || 'Erro ao carregar dados');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showNotification('Erro ao carregar os dados: ' + error.message, 'error');
        })
        .finally(() => hideLoading());
}

// MODIFY: Update existing sendBulkEmail with validation
window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    
    // Check status before sending
    if (tipo === 'encerramento') {
        checkBulkStatusBeforeSend(solicitacoes, tipo, btn, originalText);
    } else {
        sendBulkEmailRequest(solicitacoes, tipo, btn, originalText);
    }
};

// ADD: Check bulk status
function checkBulkStatusBeforeSend(solicitacoes, tipo, btn, originalText) {
    const formData = new FormData();
    formData.append('acao', 'check_bulk_status');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.has_pending) {
            const pendingList = data.pending_list.join(', ');
            const message = 'As seguintes chaves possuem status não "Efetuado":\n\n' + 
                          pendingList + '\n\nDeseja continuar mesmo assim?';
            
            if (confirm(message)) {
                sendBulkEmailRequest(solicitacoes, tipo, btn, originalText);
            }
        } else {
            sendBulkEmailRequest(solicitacoes, tipo, btn, originalText);
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        showNotification('Erro ao verificar status', 'error');
    });
}

// ADD: Send bulk email request
function sendBulkEmailRequest(solicitacoes, tipo, btn, originalText) {
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
            
            // Clear selections
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => cb.checked = false);
            const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
            if (headerCheckbox) headerCheckbox.checked = false;
            updateBulkActionButtons();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btn.innerHTML = originalText;
        btn.disabled = false;
    })
    .catch(error => {
        console.error('Bulk email error:', error);
        showNotification('Erro ao enviar emails', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// ADD: Cancel solicitacao function
window.cancelarSolicitacao = function(codSolicitacao) {
    if (!confirm('Tem certeza que deseja cancelar esta solicitação?')) {
        return;
    }
    
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
            
            // Close modal
            const modal = document.getElementById('AnaliseDetalhesModal' + codSolicitacao);
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }
            
            // Reload table
            handleFormSubmit();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Cancel error:', error);
        showNotification('Erro ao cancelar solicitação', 'error');
    });
};

// ADD: Save motivo and data functions
window.saveMotivoData = function(codSolicitacao) {
    const motivo = document.getElementById('motivoEnc' + codSolicitacao).value;
    const data = document.getElementById('dataEnc' + codSolicitacao).value;
    
    if (!motivo && !data) {
        showNotification('Preencha ao menos um campo', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'save_motivo_data');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('motivo', motivo);
    formData.append('data', data);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Dados salvos com sucesso', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showNotification('Erro ao salvar dados', 'error');
    });
};

// MODIFY: Update initializeEventListeners to handle new filters
function initializeEventListeners() {
    const elements = {
        clearFilters: () => clearAllFilters(),
        searchBtn: () => { currentPage = 1; handleFormSubmit(); },
        applyFiltersBtn: () => { currentPage = 1; handleFormSubmit(); },
        perPageSelect: function() { perPage = parseInt(this.value); currentPage = 1; handleFormSubmit(); },
        prevPage: () => { if (currentPage > 1) { currentPage--; handleFormSubmit(); } },
        nextPage: () => { if (currentPage < totalPages) { currentPage++; handleFormSubmit(); } }
    };

    Object.entries(elements).forEach(([id, handler]) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', handler);
    });

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                currentPage = 1;
                handleFormSubmit();
            }
        });
    }
    
    // ADD: Filter change listeners
    ['statusSolicFilter', 'motivoEncFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                currentPage = 1;
                handleFormSubmit();
            });
        }
    });
}

// MODIFY: Update clearAllFilters to include new filters
function clearAllFilters() {
    ['searchInput', 'bloqueioFilter', 'orgaoPagadorFilter', 'statusSolicFilter', 'motivoEncFilter',
     'dataInicioFilter', 'dataInicioDisplay', 'dataFimFilter', 'dataFimDisplay']
        .forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    
    currentPage = 1;
    handleFormSubmit();
}

// MODIFY: Update highlightActiveFilters to include new filters
function highlightActiveFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    ['searchInput', 'bloqueioFilter', 'orgaoPagadorFilter', 'statusSolicFilter', 'motivoEncFilter',
     'dataInicioDisplay', 'dataFimDisplay'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.style.borderColor = '';
            el.style.borderWidth = '';
        }
    });
    
    const filters = {
        searchInput: 'search',
        bloqueioFilter: 'bloqueio',
        orgaoPagadorFilter: 'orgao_pagador',
        statusSolicFilter: 'status_solic',
        motivoEncFilter: 'motivo_enc'
    };
    
    Object.entries(filters).forEach(([elId, param]) => {
        const el = document.getElementById(elId);
        if (el && (urlParams.has(param) || el.value)) {
            el.style.borderColor = '#206bc4';
            el.style.borderWidth = '2px';
        }
    });
    
    ['dataInicioDisplay', 'dataFimDisplay'].forEach(displayId => {
        const displayEl = document.getElementById(displayId);
        const filterId = displayId.replace('Display', '');
        const filterEl = document.getElementById(filterId);
        
        if (displayEl && filterEl && filterEl.value) {
            displayEl.style.borderColor = '#206bc4';
            displayEl.style.borderWidth = '2px';
        }
    });

    const clearBtn = document.getElementById('clearFilters');
    if (clearBtn) {
        const hasFilters = ['search', 'bloqueio', 'orgao_pagador', 'status_solic', 'motivo_enc', 
                           'data_inicio', 'data_fim'].some(param => urlParams.has(param));
        
        clearBtn.classList.toggle('btn-warning', hasFilters);
        clearBtn.classList.toggle('btn-secondary', !hasFilters);
    }
}
```

This version:
- ✅ Removes the non-existent `executeBulkEmail` reference
- ✅ Integrates validation into the existing `window.sendBulkEmail` function
- ✅ Adds `sendBulkEmailRequest` as a separate helper function
- ✅ Adds `checkBulkStatusBeforeSend` for validation
- ✅ Adds `cancelarSolicitacao` function
- ✅ Adds `saveMotivoData` function
- ✅ Updates sorting to work with existing code structure
- ✅ Updates event listeners and filters to include new fields