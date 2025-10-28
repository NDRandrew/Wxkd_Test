## Fix: Click handlers not working

**In J.txt (analise_encerramento.js)**, replace the initialization section:

```javascript
function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers();
    initializeColumnSort();
    highlightActiveFilters();
    attachPageNumberHandlers();
    initializeViewSwitching(); // ADD THIS LINE
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}

// ADD THIS NEW FUNCTION
function initializeViewSwitching() {
    updateUnviewedCount();
    
    const headerSolicitacoes = document.getElementById('headerSolicitacoes');
    const headerOcorrencias = document.getElementById('headerOcorrencias');
    
    if (headerSolicitacoes) {
        headerSolicitacoes.addEventListener('click', function(e) {
            e.preventDefault();
            switchToSolicitacoesView();
        });
    }
    
    if (headerOcorrencias) {
        headerOcorrencias.addEventListener('click', function(e) {
            e.preventDefault();
            switchToOcorrenciasView();
        });
    }
}
```

**Fix the AJAX URL in loadOcorrencias():**

```javascript
function loadOcorrencias() {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }
    
    params.append('page', currentPage);
    params.append('per_page', perPage);
    
    showLoading();
    
    // FIX: Don't append FormData, use POST params correctly
    fetch(AJAX_URL + '?acao=load_ocorrencias&' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById('tableBody');
                if (tableBody) tableBody.innerHTML = data.html;
                
                updatePaginationInfo(data);
                updatePaginationControls();
                attachAccordionHandlers();
            } else {
                showNotification('Erro: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showNotification('Erro ao carregar ocorrências', 'error');
        })
        .finally(() => hideLoading());
}
```

**Remove the duplicate DOMContentLoaded listener at the bottom of J.txt**

Delete this entire block:
```javascript
// Delete this - it's a duplicate
document.addEventListener('DOMContentLoaded', function() {
    updateUnviewedCount();
    
    const headerSolicitacoes = document.getElementById('headerSolicitacoes');
    const headerOcorrencias = document.getElementById('headerOcorrencias');
    
    if (headerSolicitacoes) {
        headerSolicitacoes.addEventListener('click', switchToSolicitacoesView);
    }
    
    if (headerOcorrencias) {
        headerOcorrencias.addEventListener('click', switchToOcorrenciasView);
    }
});
```

That should fix the click issue. Test it and let me know what error you get in the browser console.