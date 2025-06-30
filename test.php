// Correção para checkboxes funcionarem após filtro
// Adicionar ao final de assets/Wxkd_script.js

// Função para re-inicializar checkboxes
function initializeCheckboxes() {
    console.log('Initializing checkboxes...');
    
    // Checkbox "Selecionar Todos"
    var selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        // Remover listeners antigos
        selectAllCheckbox.removeEventListener('change', handleSelectAll);
        // Adicionar listener novo
        selectAllCheckbox.addEventListener('change', handleSelectAll);
    }
    
    // Checkboxes individuais
    var rowCheckboxes = document.querySelectorAll('.row-checkbox');
    rowCheckboxes.forEach(function(checkbox) {
        // Remover listeners antigos
        checkbox.removeEventListener('change', handleRowCheckbox);
        // Adicionar listener novo
        checkbox.addEventListener('change', handleRowCheckbox);
    });
    
    console.log('Checkboxes initialized:', rowCheckboxes.length);
}

// Handler para "Selecionar Todos"
function handleSelectAll(event) {
    var checked = event.target.checked;
    var rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    rowCheckboxes.forEach(function(checkbox) {
        checkbox.checked = checked;
    });
    
    updateSelectionCount();
}

// Handler para checkbox individual
function handleRowCheckbox() {
    updateSelectionCount();
    updateSelectAllState();
}

// Atualizar contador de selecionados
function updateSelectionCount() {
    var selectedCount = document.querySelectorAll('.row-checkbox:checked').length;
    
    // Atualizar texto do contador se existir
    var counterElement = document.getElementById('selectionCounter');
    if (counterElement) {
        counterElement.textContent = selectedCount + ' selecionado(s)';
    }
    
    // Habilitar/desabilitar botões de exportação
    var exportButtons = document.querySelectorAll('button[onclick*="Selected"]');
    exportButtons.forEach(function(button) {
        button.disabled = selectedCount === 0;
    });
}

// Atualizar estado do "Selecionar Todos"
function updateSelectAllState() {
    var selectAllCheckbox = document.getElementById('selectAll');
    if (!selectAllCheckbox) return;
    
    var rowCheckboxes = document.querySelectorAll('.row-checkbox');
    var checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
    
    if (checkedCount === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCount === rowCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
}

// IMPORTANTE: Re-inicializar checkboxes após carregar dados da tabela
// Interceptar a função que carrega dados da tabela
if (typeof loadTableData !== 'undefined') {
    // Guardar função original
    var originalLoadTableData = loadTableData;
    
    // Sobrescrever com versão que re-inicializa checkboxes
    loadTableData = function(filter) {
        console.log('Loading table data with filter:', filter);
        
        // Chamar função original
        var result = originalLoadTableData.call(this, filter);
        
        // Re-inicializar checkboxes após um pequeno delay
        setTimeout(function() {
            initializeCheckboxes();
        }, 100);
        
        return result;
    };
}

// Inicializar checkboxes quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing checkboxes...');
    setTimeout(function() {
        initializeCheckboxes();
    }, 500);
});

// Re-inicializar sempre que a tabela for atualizada
// (interceptar se existir um callback de sucesso do AJAX)
if (typeof window.onTableDataLoaded === 'undefined') {
    window.onTableDataLoaded = function() {
        console.log('Table data loaded, re-initializing checkboxes...');
        initializeCheckboxes();
    };
}