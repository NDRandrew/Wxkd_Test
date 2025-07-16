// Add these helper functions at the top of your script
function showLoading() {
    $('#modal_loading').css({'display':'flex'});
}

function hideLoading() {
    $('#modal_loading').css({'display':'none'});
}

// Update FilterModule.loadTableData method
loadTableData: function(filter) {
    showLoading(); // Show loading
    $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');

    $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
        .done(function(xmlData) {
            try {
                var $xml = $(xmlData);
                var success = $xml.find('success').text() === 'true';
                
                if (success) {
                    // ... existing success logic ...
                    
                    setTimeout(function() {
                        StatusFilterModule.reapplyAfterDataLoad();
                        hideLoading(); // Hide loading after everything is done
                    }, 400);
                }
            } catch (e) {
                console.error('Error parsing XML: ', e);
                $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
                hideLoading(); // Hide loading on error
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX failed:', textStatus, errorThrown);
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
            hideLoading(); // Hide loading on fail
        });
},

// Update export functions - add loading to start of each export function:

function exportTXTDataCorrected(selectedIds, filter) {
    showLoading(); // Add this line
    
    var url = 'wxkd.php?action=exportTXT&filter=' + filter;
    // ... existing code ...
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            hideLoading(); // Add this line
            // ... rest of existing response handling ...
        }
    };
    
    xhr.send();
}

function exportAccessDataProcessed(selectedIds, filter) {
    showLoading(); // Add this line
    
    var url = 'wxkd.php?action=exportAccess&filter=' + filter;
    // ... existing code ...
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            hideLoading(); // Add this line
            // ... rest of existing response handling ...
        }
    };
    
    xhr.send();
}

function exportCSVData(selectedIds, filter) {
    showLoading(); // Add this line
    
    var url = 'wxkd.php?action=exportCSV&filter=' + filter;
    // ... existing code ...
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            hideLoading(); // Add this line
            // ... rest of existing response handling ...
        }
    };
    
    xhr.send();
}