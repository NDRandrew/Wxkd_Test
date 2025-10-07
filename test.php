// Add these functions to analise_encerramento.js

function initializeCheckboxHandlers() {
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
            if (index === 0) return; // Skip first cell (checkbox)
            
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

function openModal(modalElement) {
    // Try Bootstrap 5 approach
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
    // Try Bootstrap 4/3 with jQuery
    else if (typeof $ !== 'undefined' && $.fn.modal) {
        $(modalElement).modal('show');
    }
    // Fallback - manually toggle classes
    else {
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.setAttribute('aria-modal', 'true');
        modalElement.removeAttribute('aria-hidden');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        
        // Close modal on backdrop click
        backdrop.addEventListener('click', function() {
            closeModal(modalElement, backdrop);
        });
        
        // Close button
        const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], [data-dismiss="modal"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                closeModal(modalElement, backdrop);
            });
        });
    }
}

function closeModal(modalElement, backdrop) {
    modalElement.classList.remove('show');
    modalElement.style.display = 'none';
    modalElement.setAttribute('aria-hidden', 'true');
    modalElement.removeAttribute('aria-modal');
    
    if (backdrop) {
        backdrop.remove();
    }
    document.body.classList.remove('modal-open');
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
    
    if (headerCheckbox) {
        const allCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        headerCheckbox.checked = allCheckboxes.length > 0 && checkedBoxes.length === allCheckboxes.length;
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