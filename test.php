// SOLUÇÃO DE UMA LINHA - adicionar onde os filtros são aplicados

// Encontre a função que aplica o filtro (provavelmente no FilterModule)
// e adicione esta linha no FINAL da função:

setTimeout(function() {
    document.querySelectorAll('.row-checkbox, #selectAll').forEach(function(cb) {
        cb.onclick = function() { console.log('Checkbox works:', cb.value || 'selectAll'); };
    });
}, 300);

// OU se não conseguir encontrar onde aplicar, adicione isso ao final do Wxkd_script.js:

// Auto-fix a cada 2 segundos
setInterval(function() {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    if (checkboxes.length > 0 && !checkboxes[0].onclick) {
        console.log('Auto-fixing checkboxes...');
        
        // Selecionar todos
        var selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.onclick = function() {
                checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
            };
        }
        
        // Individuais
        checkboxes.forEach(function(cb) {
            cb.onclick = function() { /* funciona */ };
        });
    }
}, 2000);