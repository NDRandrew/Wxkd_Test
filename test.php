// Method 1: JavaScript CSS Injection (Add this to your existing JavaScript file)
function injectHistoricoCSS() {
    // Check if styles are already injected
    if (document.getElementById('historico-table-styles')) {
        return;
    }
    
    const css = `
        /* Fix for historico table layout */
        .historico-details table {
            table-layout: fixed !important;
            width: 100% !important;
            white-space: nowrap !important;
            border-collapse: collapse !important;
        }

        .historico-details table td {
            vertical-align: top !important;
            padding: 8px 4px !important;
            border: 1px solid #ddd !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 120px !important;
            word-wrap: break-word !important;
        }

        .historico-details table th {
            padding: 8px 4px !important;
            border: 1px solid #ddd !important;
            background-color: #f5f5f5 !important;
            font-weight: bold !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        /* Specific column widths */
        .historico-details table td:first-child,
        .historico-details table th:first-child {
            width: 50px !important;
            max-width: 50px !important;
            text-align: center !important;
        }

        .historico-details table td:nth-child(2),
        .historico-details table th:nth-child(2) {
            width: 80px !important;
            max-width: 80px !important;
        }

        .historico-details table td:nth-child(3),
        .historico-details table th:nth-child(3) {
            width: 200px !important;
            max-width: 200px !important;
            white-space: normal !important;
        }

        .historico-details table td:nth-child(4),
        .historico-details table th:nth-child(4),
        .historico-details table td:nth-child(5),
        .historico-details table th:nth-child(5) {
            width: 80px !important;
            max-width: 80px !important;
        }

        /* Currency columns */
        .historico-details table td:nth-child(9),
        .historico-details table th:nth-child(9),
        .historico-details table td:nth-child(10),
        .historico-details table th:nth-child(10),
        .historico-details table td:nth-child(11),
        .historico-details table th:nth-child(11),
        .historico-details table td:nth-child(12),
        .historico-details table th:nth-child(12) {
            width: 100px !important;
            max-width: 100px !important;
            text-align: right !important;
        }

        /* Date columns */
        .historico-details table td:nth-child(7),
        .historico-details table th:nth-child(7),
        .historico-details table td:nth-child(8),
        .historico-details table th:nth-child(8),
        .historico-details table td:nth-child(17),
        .historico-details table th:nth-child(17),
        .historico-details table td:nth-child(19),
        .historico-details table th:nth-child(19) {
            width: 90px !important;
            max-width: 90px !important;
        }

        /* Status columns */
        .historico-details table td:nth-child(13),
        .historico-details table th:nth-child(13),
        .historico-details table td:nth-child(14),
        .historico-details table th:nth-child(14),
        .historico-details table td:nth-child(15),
        .historico-details table th:nth-child(15),
        .historico-details table td:nth-child(16),
        .historico-details table th:nth-child(16) {
            width: 80px !important;
            max-width: 80px !important;
            text-align: center !important;
        }

        /* Contract type column */
        .historico-details table td:nth-child(18),
        .historico-details table th:nth-child(18) {
            width: 120px !important;
            max-width: 120px !important;
        }

        /* Filter column */
        .historico-details table td:nth-child(20),
        .historico-details table th:nth-child(20) {
            width: 100px !important;
            max-width: 100px !important;
            text-align: center !important;
        }

        /* Table container */
        .historico-details .table-scrollable {
            overflow-x: auto !important;
            overflow-y: visible !important;
        }

        /* Debug rows */
        .debug-row td {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
        }

        .fallback-row td {
            background-color: #f8f9fa !important;
            border-left: 4px solid #6c757d !important;
        }

        /* Search highlights for historico */
        .search-highlight {
            background-color: #ffff00 !important;
            color: #000 !important;
            font-weight: bold !important;
            padding: 1px 2px !important;
            border-radius: 2px !important;
        }

        .search-highlight-row {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
        }

        .search-no-results {
            margin: 15px 0 !important;
        }
    `;
    
    // Create style element
    const styleElement = document.createElement('style');
    styleElement.type = 'text/css';
    styleElement.id = 'historico-table-styles';
    
    if (styleElement.styleSheet) {
        // IE support
        styleElement.styleSheet.cssText = css;
    } else {
        styleElement.appendChild(document.createTextNode(css));
    }
    
    // Append to head
    document.head.appendChild(styleElement);
    
    console.log('Historico CSS styles injected successfully');
}

// Method 2: jQuery CSS Injection (if you prefer jQuery)
function injectHistoricoCSSjQuery() {
    if ($('#historico-table-styles').length > 0) {
        return;
    }
    
    const css = `
        <style id="historico-table-styles" type="text/css">
            .historico-details table { table-layout: fixed !important; width: 100% !important; }
            .historico-details table td { padding: 8px 4px !important; border: 1px solid #ddd !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 120px !important; }
            .historico-details table th { padding: 8px 4px !important; border: 1px solid #ddd !important; background-color: #f5f5f5 !important; font-weight: bold !important; }
            .historico-details table td:first-child, .historico-details table th:first-child { width: 50px !important; text-align: center !important; }
            .historico-details table td:nth-child(3), .historico-details table th:nth-child(3) { width: 200px !important; white-space: normal !important; }
        </style>
    `;
    
    $('head').append(css);
    console.log('Historico CSS styles injected with jQuery');
}

// Method 3: Direct Style Application (applies styles directly to elements)
function applyHistoricoStylesDirectly() {
    // Apply styles directly to existing tables
    $('.historico-details table').css({
        'table-layout': 'fixed',
        'width': '100%',
        'border-collapse': 'collapse'
    });
    
    $('.historico-details table td').css({
        'padding': '8px 4px',
        'border': '1px solid #ddd',
        'overflow': 'hidden',
        'text-overflow': 'ellipsis',
        'max-width': '120px',
        'vertical-align': 'top'
    });
    
    $('.historico-details table th').css({
        'padding': '8px 4px',
        'border': '1px solid #ddd',
        'background-color': '#f5f5f5',
        'font-weight': 'bold'
    });
    
    // Specific column widths
    $('.historico-details table td:first-child, .historico-details table th:first-child').css({
        'width': '50px',
        'max-width': '50px',
        'text-align': 'center'
    });
    
    $('.historico-details table td:nth-child(3), .historico-details table th:nth-child(3)').css({
        'width': '200px',
        'max-width': '200px',
        'white-space': 'normal'
    });
    
    console.log('Direct styles applied to historico tables');
}

// Method 4: Enhanced HistoricoModule with Built-in Styling
const HistoricoModuleWithStyles = {
    init: function() {
        // Inject CSS when module initializes
        this.injectStyles();
        
        $(document).on('click', '.load-details', this.loadDetails.bind(this));
        $(document).on('shown.bs.collapse', '.panel-collapse', this.onAccordionExpand.bind(this));
    },
    
    injectStyles: function() {
        injectHistoricoCSS(); // Use Method 1
    },
    
    loadDetails: function(e) {
        e.preventDefault();
        const button = $(e.currentTarget);
        const chaveLote = button.data('chave-lote');
        const tbody = button.closest('tbody');
        
        if (button.prop('disabled')) {
            return;
        }
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
        
        const self = this;
        
        $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
            .done((xmlData) => {
                // ... your existing loadDetails code here ...
                
                // After loading data, apply styles to the new elements
                setTimeout(() => {
                    self.applyStylesToNewElements();
                }, 100);
            })
            .fail((xhr, status, error) => {
                console.error('AJAX request failed:', status, error);
                tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro na requisição</td></tr>');
            })
            .always(() => {
                button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
            });
    },
    
    applyStylesToNewElements: function() {
        // Apply styles to newly created table elements
        applyHistoricoStylesDirectly();
    }
    
    // ... rest of your HistoricoModule methods ...
};

// Integration with your existing code
$(document).ready(function() {
    // Method 1: Inject CSS immediately when document is ready
    injectHistoricoCSS();
    
    // Method 2: Also apply when historico accordion is opened
    $(document).on('shown.bs.collapse', '.panel-collapse', function() {
        setTimeout(() => {
            injectHistoricoCSS();
            applyHistoricoStylesDirectly();
        }, 200);
    });
    
    // Method 3: Apply styles whenever new data is loaded
    $(document).on('DOMNodeInserted', '.historico-details', function() {
        setTimeout(() => {
            applyHistoricoStylesDirectly();
        }, 100);
    });
    
    // Initialize other modules
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init();
    HistoricoModule.init(); // or HistoricoModuleWithStyles.init();
    HistoricoCheckboxModule.init();
});