// Módulo de Exportação - Atualizado para XLS
var ExportModule = {
    // Exportar selecionados para XLS
    exportSelectedXLS: function() {
        console.log('exportSelectedXLS called');
        
        var selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
        
        if (selectedCheckboxes.length === 0) {
            alert('Por favor, selecione pelo menos um registro para exportar.');
            return;
        }
        
        var selectedIds = [];
        selectedCheckboxes.forEach(function(checkbox) {
            selectedIds.push(checkbox.value);
        });
        
        console.log('Selected IDs for export:', selectedIds);
        
        this.performXLSExport(selectedIds, 'selecionados');
    },
    
    // Exportar todos do filtro atual para XLS
    exportAllXLS: function() {
        console.log('exportAllXLS called');
        
        var currentFilter = FilterModule.getCurrentFilter();
        console.log('Current filter for export:', currentFilter);
        
        this.performXLSExport([], currentFilter);
    },
    
    // Executar exportação XLS
    performXLSExport: function(selectedIds, exportType) {
        console.log('performXLSExport called with:', selectedIds, exportType);
        
        try {
            // Criar formulário para envio via POST
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'Wxkd_dashboard.php?action=exportXLS';
            form.style.display = 'none';
            
            // Campo para IDs selecionados
            var selectedIdsField = document.createElement('input');
            selectedIdsField.type = 'hidden';
            selectedIdsField.name = 'selectedIds';
            selectedIdsField.value = selectedIds.join(',');
            form.appendChild(selectedIdsField);
            
            // Campo para filtro atual
            var filterField = document.createElement('input');
            filterField.type = 'hidden';
            filterField.name = 'filter';
            filterField.value = FilterModule.getCurrentFilter();
            form.appendChild(filterField);
            
            // Campo para tipo de exportação
            var typeField = document.createElement('input');
            typeField.type = 'hidden';
            typeField.name = 'exportType';
            typeField.value = exportType;
            form.appendChild(typeField);
            
            // Adicionar ao body e submeter
            document.body.appendChild(form);
            
            console.log('Submitting XLS export form...');
            form.submit();
            
            // Remover formulário após submissão
            setTimeout(function() {
                document.body.removeChild(form);
            }, 1000);
            
        } catch (error) {
            console.error('Error in performXLSExport:', error);
            alert('Erro ao exportar: ' + error.message);
        }
    }
};

// Módulo de Filtros - Adicionar método getCurrentFilter se não existir
if (typeof FilterModule !== 'undefined') {
    // Adicionar método para obter filtro atual
    if (!FilterModule.getCurrentFilter) {
        FilterModule.getCurrentFilter = function() {
            // Verificar qual card está ativo
            var activeCard = document.querySelector('.card.active');
            if (activeCard) {
                var cardId = activeCard.id;
                if (cardId === 'card-cadastramento') return 'cadastramento';
                if (cardId === 'card-descadastramento') return 'descadastramento';
                if (cardId === 'card-historico') return 'historico';
            }
            return 'all'; // padrão
        };
    }
}

// Função global para exportar selecionados (chamada pelos botões)
function exportSelectedXLS() {
    ExportModule.exportSelectedXLS();
}

// Função global para exportar todos (chamada pelos botões)
function exportAllXLS() {
    ExportModule.exportAllXLS();
}

// Compatibilidade - manter funções antigas mas redirecionando para XLS
function exportSelectedXML() {
    console.log('exportSelectedXML redirecting to XLS');
    ExportModule.exportSelectedXLS();
}

function exportAllXML() {
    console.log('exportAllXML redirecting to XLS');
    ExportModule.exportAllXLS();
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('ExportModule XLS initialized');
    
    // Atualizar textos dos botões de XML para XLS
    var exportButtons = document.querySelectorAll('button[onclick*="XML"]');
    exportButtons.forEach(function(button) {
        if (button.textContent.includes('XML')) {
            button.textContent = button.textContent.replace('XML', 'XLS');
        }
        if (button.innerHTML.includes('XML')) {
            button.innerHTML = button.innerHTML.replace('XML', 'XLS');
        }
    });
    
    // Atualizar onclick dos botões
    var selectedBtn = document.querySelector('button[onclick="exportSelectedXML()"]');
    if (selectedBtn) {
        selectedBtn.setAttribute('onclick', 'exportSelectedXLS()');
    }
    
    var allBtn = document.querySelector('button[onclick="exportAllXML()"]');
    if (allBtn) {
        allBtn.setAttribute('onclick', 'exportAllXLS()');
    }
});