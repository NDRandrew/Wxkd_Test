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
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                }
            });
        });
    });
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

-----------

// Replace renderTableRows() method in C.txt (analise_encerramento_control.php)

public function renderTableRows($dados) {
    $html = '';
    $length = is_array($dados) ? count($dados) : 0;
    
    if ($length > 0) {
        for ($i = 0; $i < $length; $i++) {
            // REMOVED: data-bs-toggle="modal" data-bs-target="#AnaliseDetalhesModal..."
            // ADDED: data-modal-target="#AnaliseDetalhesModal..."
            $html .= '<tr data-modal-target="#AnaliseDetalhesModal' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '" name="' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) .'">';
            $html .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                    <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                        <input class="form-check-input position-static m-0" type="checkbox" />
                        <span class="form-check-label d-none"></span>
                    </label>
                </th>';
            $html .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span></th>';
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span></td>';
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span></td>';
            $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
            
            // Data Retirada
            if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span></td>';
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Data</span></td>';
            }
            
            // Bloqueio
            if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                $html .= '<td><span class="text-green" style="display: block; text-align: center;">Bloqueado</span></td>';
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span></td>';
            }
            
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span></td>';
            
            // Motivo Bloqueio
            if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span></td>';
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span></td>';
            }
            
            // Motivo Encerramento
            if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span></td>';
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span></td>';
            }
            
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span></td>';
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span></td>';
            
            // PARM
            if (!is_null($dados[$i]['PARM'])) {
                if($dados[$i]['PARM'] == 'NÃO APTO'){
                    $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                } else {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['PARM']) .'</span></td>';
                }
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
            }
            
            // TRAG
            if (!is_null($dados[$i]['TRAG'])) {
                if($dados[$i]['TRAG'] == 'NÃO APTO'){
                    $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                } else {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['TRAG']) .'</span></td>';
                }
            } else {
                $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
            }

            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['MEDIA_CONTABEIS']) . '</span></td>';
            $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['MEDIA_NEGOCIO']) . '</span></td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
    }
    
    return $html;
}

-----------

// Replace renderTableRows() method in JH.txt (ajax_encerramento.php)

public function renderTableRows($dados) {
    if (empty($dados)) {
        return '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
    }
    
    $html = '';
    foreach ($dados as $row) {
        // CHANGED: data-bs-toggle="modal" data-bs-target to data-modal-target
        $html .= '<tr data-modal-target="#AnaliseDetalhesModal' . htmlspecialchars($row['COD_SOLICITACAO']) . '" name="' . htmlspecialchars($row['COD_SOLICITACAO']) . '">';
        $html .= $this->renderTableCell($row);
        $html .= '</tr>';
    }
    
    return $html;
}



-------------


<style>
    /* Add to existing <style> section in E.txt */
    
    /* Checkbox styling */
    .form-check-input {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }
    
    thead .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    /* Row hover effect */
    tbody tr[data-modal-target]:hover {
        background-color: var(--tr-highlight-modal) !important;
        transition: background-color 0.2s ease;
    }
    
    /* Make non-checkbox cells clickable */
    tbody tr[data-modal-target] td,
    tbody tr[data-modal-target] th:not(:first-child) {
        cursor: pointer;
    }
    
    /* Checkbox cell should not show pointer cursor */
    tbody tr[data-modal-target] th:first-child {
        cursor: default;
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