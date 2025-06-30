// Versão SUPER SIMPLES - adicionar ao final de Wxkd_script.js

// Função simples para re-ativar checkboxes
function reactivateCheckboxes() {
    console.log('Reactivating checkboxes...');
    
    // Selecionar todos
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.onclick = function() {
            var checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        };
    }
    
    // Checkboxes individuais
    var checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(function(cb) {
        cb.onclick = function() {
            // Apenas para garantir que funciona
            console.log('Checkbox clicked:', cb.value, cb.checked);
        };
    });
    
    console.log('Found', checkboxes.length, 'checkboxes');
}

// Re-ativar checkboxes sempre que aplicar filtro
// Adicionar esta linha no final da função que aplica o filtro
// Se você tiver uma função como FilterModule.applyFilter, adicione lá:

// INTERCEPTAR a aplicação de filtros
if (typeof FilterModule !== 'undefined' && FilterModule.applyFilter) {
    var originalApplyFilter = FilterModule.applyFilter;
    FilterModule.applyFilter = function(filter) {
        console.log('Applying filter:', filter);
        
        // Chamar função original
        var result = originalApplyFilter.call(this, filter);
        
        // Re-ativar checkboxes após pequeno delay
        setTimeout(reactivateCheckboxes, 200);
        
        return result;
    };
}

// Ativar na inicialização
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(reactivateCheckboxes, 1000);
});

// SOLUÇÃO DE EMERGÊNCIA: Re-ativar a cada 3 segundos
setInterval(function() {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    if (checkboxes.length > 0) {
        // Verificar se algum não tem onclick
        var needsReactivation = false;
        checkboxes.forEach(function(cb) {
            if (!cb.onclick) {
                needsReactivation = true;
            }
        });
        
        if (needsReactivation) {
            console.log('Re-activating checkboxes automatically...');
            reactivateCheckboxes();
        }
    }
}, 3000);