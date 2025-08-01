// Add this debug function to TestJ - useful for troubleshooting descadastramento logic
window.debugDescadastroLogic = function(xmlDoc) {
    console.log('=== DESCADASTRAMENTO LOGIC DEBUG ===');
    
    const rows = xmlDoc.getElementsByTagName('row');
    for (let i = 0; i < Math.min(rows.length, 5); i++) { // Show first 5 rows
        const row = rows[i];
        
        const chaveLoja = getXMLNodeValue(row, 'cod_loja') || getXMLNodeValue(row, 'cod_loja_historico');
        const descadastroTxtType = getXMLNodeValue(row, 'descadastro_txt_type');
        const descadastroOriginalTipo = getXMLNodeValue(row, 'descadastro_original_tipo');
        const actualTipoCompleto = getXMLNodeValue(row, 'actual_tipo_completo');
        
        console.log(`Row ${i}: ChaveLoja=${chaveLoja}`);
        console.log(`  Original Tipo: ${descadastroOriginalTipo}`);
        console.log(`  TXT Export Type: ${descadastroTxtType}`);
        console.log(`  Actual Tipos: ${actualTipoCompleto}`);
        console.log(`  Actual AVANCADO: ${getXMLNodeValue(row, 'actual_avancado')}`);
        console.log(`  Actual PRESENCA: ${getXMLNodeValue(row, 'actual_presenca')}`);
        console.log(`  Actual UNIDADE_NEGOCIO: ${getXMLNodeValue(row, 'actual_unidade_negocio')}`);
        console.log(`  Actual ORGAO_PAGADOR: ${getXMLNodeValue(row, 'actual_orgao_pagador')}`);
        console.log('---');
    }
    
    console.log('=== END DESCADASTRAMENTO DEBUG ===');
};

// Usage: After exportTXTData() call, use this in console:
// window.debugDescadastroLogic(xmlDoc)