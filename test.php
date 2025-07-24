// DEBUG VERSION: Add this to see what's overriding the table styles

tbody.html(detailsHtml);
console.log('CELLS PER ROW:', tbody.find('tr:first td').length, 'Expected: 20');

// DEBUG: Check what styles are being applied
const table = tbody.closest('table');
const firstRow = tbody.find('tr:first');
const firstCell = tbody.find('td:first');

console.log('🔍 CSS DEBUGGING:');
console.log('Table classes:', table[0].className);
console.log('Table computed styles:', {
    display: getComputedStyle(table[0]).display,
    tableLayout: getComputedStyle(table[0]).tableLayout,
    width: getComputedStyle(table[0]).width
});

console.log('Row classes:', firstRow[0].className);
console.log('Row computed styles:', {
    display: getComputedStyle(firstRow[0]).display,
    width: getComputedStyle(firstRow[0]).width
});

console.log('Cell classes:', firstCell[0].className);
console.log('Cell computed styles:', {
    display: getComputedStyle(firstCell[0]).display,
    width: getComputedStyle(firstCell[0]).width,
    float: getComputedStyle(firstCell[0]).float,
    position: getComputedStyle(firstCell[0]).position
});

// Check if there's a conflicting stylesheet
const allStyleSheets = Array.from(document.styleSheets);
console.log('Active stylesheets:', allStyleSheets.map(sheet => sheet.href || 'inline'));

// TRY NUCLEAR OPTION: Remove all classes and force inline styles
table.removeClass().attr('class', '');
firstRow.removeClass().attr('class', '');

tbody.find('td').each(function(index) {
    $(this).removeClass().attr('class', '');
    
    // Force inline styles that can't be overridden
    this.style.cssText = `
        display: table-cell !important;
        border: 2px solid red !important;
        padding: 5px !important;
        min-width: 60px !important;
        max-width: 150px !important;
        vertical-align: top !important;
        white-space: nowrap !important;
        overflow: hidden !important;
    `;
});

// Force table structure
table[0].style.cssText = `
    display: table !important;
    table-layout: fixed !important;
    width: 100% !important;
    border-collapse: separate !important;
`;

firstRow[0].style.cssText = `
    display: table-row !important;
    width: 100% !important;
`;

console.log('✅ Nuclear CSS override applied');

// Final check after 500ms
setTimeout(() => {
    console.log('🔍 FINAL CHECK:');
    tbody.find('td').slice(0, 3).each(function(i) {
        console.log(`Cell ${i + 1}:`, {
            display: getComputedStyle(this).display,
            width: getComputedStyle(this).width,
            content: $(this).text().substring(0, 20)
        });
    });
}, 500);

tbody.append(`
    <tr class="info">
        <td colspan="20" class="text-center">
            <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
        </td>
    </tr>
`);