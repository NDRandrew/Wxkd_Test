<style>
    /* Add to existing <style> section in E.txt */
    
    /* Make entire checkbox cell clickable */
    tbody tr[data-bs-toggle="modal"] th:first-child {
        cursor: pointer;
        pointer-events: auto !important;
    }
    
    tbody tr[data-bs-toggle="modal"] th:first-child * {
        pointer-events: auto !important;
    }
    
    /* Checkbox styling */
    .form-check-input {
        cursor: pointer;
        width: 18px;
        height: 18px;
        margin: 0 !important;
    }
    
    thead .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    /* Make checkbox label area larger */
    tbody th:first-child .form-check {
        width: 100%;
        height: 100%;
        display: flex !important;
        justify-content: center;
        align-items: center;
        margin: 0;
        padding: 0.5rem;
    }
    
    thead th:first-child {
        cursor: pointer;
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



---------

// Add these functions to analise_encerramento.js

function initializeCheckboxHandlers() {
    // Header checkbox - select all
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    const headerCell = document.querySelector('thead th:first-child');
    
    if (headerCell) {
        headerCell.addEventListener('click', function(e) {
            e.stopPropagation();
            if (headerCheckbox) {
                headerCheckbox.checked = !headerCheckbox.checked;
                const isChecked = headerCheckbox.checked;
                document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                    cb.checked = isChecked;
                });
                updateBulkActionButtons();
            }
        });
    }

    // Individual row checkboxes - make entire cell clickable
    document.querySelectorAll('tbody tr[data-bs-toggle="modal"]').forEach(row => {
        const checkboxCell = row.querySelector('th:first-child');
        const checkbox = checkboxCell?.querySelector('input[type="checkbox"]');
        
        if (checkboxCell && checkbox) {
            checkboxCell.addEventListener('click', function(e) {
                e.stopPropagation();
                e.preventDefault();
                checkbox.checked = !checkbox.checked;
                updateBulkActionButtons();
            });
        }
    });
}

function updateBulkActionButtons() {
    const checkedBoxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
    const bulkActions = document.getElementById('bulkActions');
    const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
    
    if (bulkActions) {
        bulkActions.style.display = checkedBoxes.length > 0 ? 'block' : 'none';
    }
    
    // Update header checkbox state
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