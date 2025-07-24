// AGGRESSIVE FIX: Replace the CSS fix section with this more forceful approach

tbody.html(detailsHtml);
console.log('CELLS PER ROW:', tbody.find('tr:first td').length, 'Expected: 20');

// STEP 1: Remove any conflicting CSS classes and styles
const table = tbody.closest('table');

// Remove problematic classes that might be causing issues
table.removeClass().addClass('table table-striped table-bordered table-hover');
tbody.find('tr').removeClass();
tbody.find('td').removeClass();

// STEP 2: Force table display with !important styles via CSS injection
const forceTableCSS = `
    <style id="force-historico-table" type="text/css">
        .historico-details table {
            display: table !important;
            table-layout: fixed !important;
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .historico-details table tbody {
            display: table-row-group !important;
        }
        
        .historico-details table tr {
            display: table-row !important;
            width: 100% !important;
            height: auto !important;
        }
        
        .historico-details table td {
            display: table-cell !important;
            vertical-align: top !important;
            padding: 4px 6px !important;
            border: 1px solid #ddd !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            float: none !important;
            clear: none !important;
            position: static !important;
        }
        
        /* Specific column widths */
        .historico-details table td:nth-child(1) { width: 50px !important; min-width: 50px !important; max-width: 50px !important; }
        .historico-details table td:nth-child(2) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
        .historico-details table td:nth-child(3) { width: 200px !important; min-width: 200px !important; max-width: 200px !important; white-space: normal !important; }
        .historico-details table td:nth-child(4) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
        .historico-details table td:nth-child(5) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; }
        .historico-details table td:nth-child(6) { width: 150px !important; min-width: 150px !important; max-width: 150px !important; }
        .historico-details table td:nth-child(7) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }
        .historico-details table td:nth-child(8) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }
        .historico-details table td:nth-child(9) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: right !important; }
        .historico-details table td:nth-child(10) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: right !important; }
        .historico-details table td:nth-child(11) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: right !important; }
        .historico-details table td:nth-child(12) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: right !important; }
        .historico-details table td:nth-child(13) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; text-align: center !important; }
        .historico-details table td:nth-child(14) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; text-align: center !important; }
        .historico-details table td:nth-child(15) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; text-align: center !important; }
        .historico-details table td:nth-child(16) { width: 80px !important; min-width: 80px !important; max-width: 80px !important; text-align: center !important; }
        .historico-details table td:nth-child(17) { width: 120px !important; min-width: 120px !important; max-width: 120px !important; }
        .historico-details table td:nth-child(18) { width: 150px !important; min-width: 150px !important; max-width: 150px !important; }
        .historico-details table td:nth-child(19) { width: 90px !important; min-width: 90px !important; max-width: 90px !important; }
        .historico-details table td:nth-child(20) { width: 100px !important; min-width: 100px !important; max-width: 100px !important; text-align: center !important; }
        
        /* Force horizontal scrolling if needed */
        .historico-details .table-scrollable {
            overflow-x: auto !important;
            width: 100% !important;
        }
    </style>
`;

// Remove any existing force styles and add new ones
$('#force-historico-table').remove();
$('head').append(forceTableCSS);

// STEP 3: Debug current table structure
console.log('🔍 Table debugging:');
console.log('Table display:', table.css('display'));
console.log('Table table-layout:', table.css('table-layout'));
console.log('First row display:', tbody.find('tr:first').css('display'));
console.log('First cell display:', tbody.find('td:first').css('display'));

// STEP 4: Force styles with JavaScript as backup
setTimeout(() => {
    // Ensure table is actually a table
    table[0].style.setProperty('display', 'table', 'important');
    table[0].style.setProperty('table-layout', 'fixed', 'important');
    table[0].style.setProperty('width', '100%', 'important');
    
    // Force each row to be a table-row
    tbody.find('tr').each(function() {
        this.style.setProperty('display', 'table-row', 'important');
    });
    
    // Force each cell to be a table-cell
    tbody.find('td').each(function(index) {
        this.style.setProperty('display', 'table-cell', 'important');
        this.style.setProperty('vertical-align', 'top', 'important');
        this.style.setProperty('border', '1px solid #ddd', 'important');
        this.style.setProperty('padding', '4px 6px', 'important');
    });
    
    console.log('✅ JavaScript force-styling applied');
    
    // Final debug check
    setTimeout(() => {
        const finalCheck = tbody.find('tr:first td').map(function() {
            return {
                display: this.style.display || getComputedStyle(this).display,
                width: getComputedStyle(this).width,
                content: $(this).text().substring(0, 10)
            };
        }).get();
        
        console.log('🔍 Final cell check:', finalCheck);
        
        // If cells are still not table-cell, try nuclear option
        if (finalCheck[0].display !== 'table-cell') {
            console.warn('⚠️  Cells still not displaying as table-cell, trying nuclear option...');
            
            // Create a completely new table structure
            const currentHTML = tbody.html();
            const newTableHTML = `
                <div style="display: table; width: 100%; table-layout: fixed; border-collapse: collapse;">
                    <div style="display: table-row-group;">
                        ${currentHTML.replace(/<tr/g, '<div style="display: table-row;"').replace(/<\/tr>/g, '</div>').replace(/<td/g, '<div style="display: table-cell; border: 1px solid #ddd; padding: 4px 6px; vertical-align: top;"').replace(/<\/td>/g, '</div>')}
                    </div>
                </div>
            `;
            
            tbody.parent().html(newTableHTML);
            console.log('🚀 Nuclear option applied - converted to div-based table');
        }
    }, 200);
}, 100);

console.log('✅ Aggressive table fix applied');

tbody.append(`
    <tr class="info">
        <td colspan="20" class="text-center">
            <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
        </td>
    </tr>
`);