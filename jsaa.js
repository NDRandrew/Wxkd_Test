// Módulo de Exportação Completo - XLS e CSV
var ExportModule = {
    // Exportar selecionados para XLS
    exportSelectedXLS: function() {
        console.log('exportSelectedXLS called');
        this.exportSelected('exportXLS');
    },
    
    // Exportar selecionados para CSV
    exportSelectedCSV: function() {
        console.log('exportSelectedCSV called');
        this.exportSelected('exportCSV');
    },
    
    // Exportar todos para XLS
    exportAllXLS: function() {
        console.log('exportAllXLS called');
        this.exportAll('exportXLS');
    },
    
    // Exportar todos para CSV
    exportAllCSV: function() {
        console.log('exportAllCSV called');
        this.exportAll('exportCSV');
    },
    
    // Método genérico para exportar selecionados
    exportSelected: function(action) {
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
        
        this.performExport(selectedIds, action);
    },
    
    // Método genérico para exportar todos
    exportAll: function(action) {
        console.log('exportAll called with action:', action);
        
        // Verificar se há dados na tabela
        var tableRows = document.querySelectorAll('#dataTable tbody tr');
        if (tableRows.length === 0) {
            alert('Não há dados para exportar.');
            return;
        }
        
        // Confirmar exportação de todos os registros
        var currentFilter = this.getCurrentFilter();
        var confirmation = confirm(
            'Deseja exportar TODOS os registros do filtro "' + 
            this.getFilterDisplayName(currentFilter) + '"?'
        );
        
        if (!confirmation) {
            return;
        }
        
        this.performExport([], action);
    },
    
    // Executar exportação
    performExport: function(selectedIds, action) {
        console.log('performExport called with:', selectedIds, action);
        
        try {
            // Mostrar indicador de loading
            this.showLoadingIndicator(action);
            
            // Criar formulário para envio via POST
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'Wxkd_dashboard.php?action=' + action;
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
            filterField.value = this.getCurrentFilter();
            form.appendChild(filterField);
            
            // Campo para timestamp
            var timestampField = document.createElement('input');
            timestampField.type = 'hidden';
            timestampField.name = 'timestamp';
            timestampField.value = new Date().getTime();
            form.appendChild(timestampField);
            
            // Adicionar ao body e submeter
            document.body.appendChild(form);
            
            console.log('Submitting export form...');
            form.submit();
            
            // Remover formulário e loading após submissão
            setTimeout(function() {
                document.body.removeChild(form);
                ExportModule.hideLoadingIndicator();
            }, 2000);
            
        } catch (error) {
            console.error('Error in performExport:', error);
            alert('Erro ao exportar: ' + error.message);
            this.hideLoadingIndicator();
        }
    },
    
    // Obter filtro atual
    getCurrentFilter: function() {
        // Verificar qual card está ativo
        var activeCard = document.querySelector('.card.active');
        if (activeCard) {
            var cardId = activeCard.id;
            if (cardId === 'card-cadastramento') return 'cadastramento';
            if (cardId === 'card-descadastramento') return 'descadastramento';
            if (cardId === 'card-historico') return 'historico';
        }
        return 'all'; // padrão
    },
    
    // Obter nome de exibição do filtro
    getFilterDisplayName: function(filter) {
        switch(filter) {
            case 'cadastramento': return 'Cadastramento';
            case 'descadastramento': return 'Descadastramento';
            case 'historico': return 'Histórico';
            case 'all': return 'Todos';
            default: return 'Todos';
        }
    },
    
    // Mostrar indicador de loading
    showLoadingIndicator: function(action) {
        var format = action.includes('XLS') ? 'Excel' : 'CSV';
        
        // Remover loading anterior se existir
        this.hideLoadingIndicator();
        
        // Criar loading
        var loading = document.createElement('div');
        loading.id = 'export-loading';
        loading.innerHTML = 
            '<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; ' +
            'background: rgba(0,0,0,0.5); z-index: 9999; display: flex; ' +
            'align-items: center; justify-content: center;">' +
                '<div style="background: white; padding: 30px; border-radius: 10px; ' +
                'text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">' +
                    '<div style="font-size: 24px; margin-bottom: 10px;">📊</div>' +
                    '<div style="font-weight: bold; margin-bottom: 5px;">Gerando arquivo ' + format + '...</div>' +
                    '<div style="color: #666;">Por favor, aguarde...</div>' +
                    '<div style="margin-top: 15px; border: 2px solid #f3f3f3; border-top: 2px solid #3498db; ' +
                    'border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; ' +
                    'margin: 15px auto;"></div>' +
                '</div>' +
            '</div>' +
            '<style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>';
        
        document.body.appendChild(loading);
    },
    
    // Esconder indicador de loading
    hideLoadingIndicator: function() {
        var loading = document.getElementById('export-loading');
        if (loading) {
            document.body.removeChild(loading);
        }
    }
};

// Funções globais para compatibilidade
function exportSelectedXLS() {
    ExportModule.exportSelectedXLS();
}

function exportSelectedCSV() {
    ExportModule.exportSelectedCSV();
}

function exportAllXLS() {
    ExportModule.exportAllXLS();
}

function exportAllCSV() {
    ExportModule.exportAllCSV();
}

// Compatibilidade com funções antigas (XML → XLS)
function exportSelectedXML() {
    console.log('exportSelectedXML redirecting to XLS');
    ExportModule.exportSelectedXLS();
}

function exportAllXML() {
    console.log('exportAllXML redirecting to XLS');
    ExportModule.exportAllXLS();
}

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    console.log('ExportModule (XLS/CSV) initialized');
    
    // Verificar se FilterModule existe e adicionar método se necessário
    if (typeof FilterModule !== 'undefined' && !FilterModule.getCurrentFilter) {
        FilterModule.getCurrentFilter = function() {
            return ExportModule.getCurrentFilter();
        };
    }
});