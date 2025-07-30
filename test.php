// In StatusFilterModule, update the isFieldActive function:
isFieldActive: function(statusCell, fieldName) {
    const cellHtml = statusCell.html();
    
    // Handle the ORG_PAGADOR/ORGAO_PAGADOR mapping for OP field
    let fieldsToCheck = [fieldName];
    if (fieldName === 'ORGAO_PAGADOR') {
        fieldsToCheck = ['ORGAO_PAGADOR', 'ORG_PAGADOR'];
    } else if (fieldName === 'ORG_PAGADOR') {
        fieldsToCheck = ['ORGAO_PAGADOR', 'ORG_PAGADOR'];
    }
    
    // Check if any of the field variants are active
    for (const field of fieldsToCheck) {
        const regex = new RegExp(`data-field="${field}"[^>]*data-status="active"`, 'i');
        if (regex.test(cellHtml)) {
            return true;
        }
    }
    
    return false;
},