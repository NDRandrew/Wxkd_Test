// Adicionar ao final de assets/Wxkd_script.js

// Exportar selecionados
function exportSelectedCSV() {
    var selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    var ids = [];
    selected.forEach(function(cb) {
        ids.push(cb.value);
    });
    
    downloadCSV(ids.join(','));
}

// Exportar todos
function exportAllCSV() {
    downloadCSV('');
}

// Download CSV via GET
function downloadCSV(ids) {
    var filter = getCurrentFilter();
    var url = 'Wxkd_dashboard.php?action=exportCSV&filter=' + filter;
    if (ids) {
        url += '&ids=' + ids;
    }
    
    // Criar link temporário para download
    var link = document.createElement('a');
    link.href = url;
    link.download = 'dashboard.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Obter filtro atual
function getCurrentFilter() {
    var activeCard = document.querySelector('.card.active');
    if (activeCard) {
        if (activeCard.id === 'card-cadastramento') return 'cadastramento';
        if (activeCard.id === 'card-descadastramento') return 'descadastramento';
        if (activeCard.id === 'card-historico') return 'historico';
    }
    return 'all';
}

// Compatibilidade - redirecionar XML para CSV
function exportSelectedXML() { exportSelectedCSV(); }
function exportAllXML() { exportAllCSV(); }