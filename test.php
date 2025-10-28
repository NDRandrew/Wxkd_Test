## Fix: Replace alert with Bootstrap modal

**In E.txt (analise_encerramento.php)**, add this modal before closing `</body>`:

```html
<!-- Bulk Warning Modal -->
<div class="modal fade" id="bulkWarningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-warning me-2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v2m0 4v.01"/>
                        <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                    </svg>
                    Status Pendentes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bulkWarningContent">
                <!-- Content will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="bulkWarningConfirm">Continuar Mesmo Assim</button>
            </div>
        </div>
    </div>
</div>
```

**In J.txt (analise_encerramento.js)**, replace `showBulkWarningModal`:

```javascript
function showBulkWarningModal(pendentes, tipo, solicitacoes, btnElement) {
    let content = '<div class="alert alert-warning mb-3">';
    content += '<strong>Atenção!</strong> As seguintes lojas possuem status pendentes:';
    content += '</div>';
    
    content += '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
    content += '<table class="table table-bordered table-sm">';
    content += '<thead class="thead-encerramento">';
    content += '<tr><th>Chave Loja</th><th>Status Pendentes</th></tr>';
    content += '</thead><tbody>';
    
    for (const [chave, status] of Object.entries(pendentes)) {
        content += '<tr>';
        content += '<td class="text-center"><strong>' + chave + '</strong></td>';
        content += '<td><span class="text-danger">' + status.join(', ') + '</span></td>';
        content += '</tr>';
    }
    
    content += '</tbody></table></div>';
    
    content += '<div class="alert alert-info mt-3 mb-0">';
    content += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    content += 'Deseja enviar os emails mesmo com esses status pendentes?';
    content += '</div>';
    
    // Inject content
    document.getElementById('bulkWarningContent').innerHTML = content;
    
    // Show modal
    const modalElement = document.getElementById('bulkWarningModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Handle confirm button (remove old listeners first)
    const confirmBtn = document.getElementById('bulkWarningConfirm');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        executeBulkEmail(tipo, solicitacoes, btnElement);
    });
}
```

**Add CSS for better table styling in the modal (in E.txt `<style>` section):**

```css
#bulkWarningModal .table {
    margin-bottom: 0;
}

#bulkWarningModal .table thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

#bulkWarningModal .alert {
    display: flex;
    align-items: center;
}
```

Now it will show a proper Bootstrap modal instead of the browser's confirm dialog.