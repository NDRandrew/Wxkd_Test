// Funções de exportação TXT - adicionar ao final de assets/Wxkd_script.js

/**
 * Exportar todos os registros para TXT
 */
function exportAllTXT() {
    console.log('exportAllTXT called');
    
    var filter = getCurrentFilter();
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter;
    
    // Criar link de download
    var link = document.createElement('a');
    link.href = url;
    link.download = 'dashboard_' + filter + '.txt';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('TXT export initiated for filter:', filter);
}

/**
 * Exportar registros selecionados para TXT
 */
function exportSelectedTXT() {
    console.log('exportSelectedTXT called');
    
    var selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Por favor, selecione pelo menos um registro para exportar.');
        return;
    }
    
    var ids = [];
    selected.forEach(function(checkbox) {
        ids.push(checkbox.value);
    });
    
    var filter = getCurrentFilter();
    var url = 'Wxkd_dashboard.php?action=exportTXT&filter=' + filter + '&ids=' + ids.join(',');
    
    // Criar link de download
    var link = document.createElement('a');
    link.href = url;
    link.download = 'dashboard_selected_' + filter + '.txt';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log('TXT export initiated for', selected.length, 'selected records');
}

/**
 * Obter filtro atual (reutilizar função existente ou criar se não existir)
 */
function getCurrentFilter() {
    var activeCard = document.querySelector('.card.active');
    if (activeCard) {
        if (activeCard.id === 'card-cadastramento') return 'cadastramento';
        if (activeCard.id === 'card-descadastramento') return 'descadastramento';
        if (activeCard.id === 'card-historico') return 'historico';
    }
    return 'all';
}