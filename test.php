// ADD THESE DEBUG FUNCTIONS TO YOUR TestJ FILE:

// Debug function for descadastramento logic
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

// Debug function for contract highlighting
window.debugContractChaves = function() {
    console.log('=== CONTRACT CHAVES DEBUG ===');
    console.log('Current Filter:', FilterModule.currentFilter);
    console.log('Contract Chaves Array:', window.contractChaves);
    console.log('Contract Chaves Length:', window.contractChaves ? window.contractChaves.length : 'undefined');
    
    // Check first few visible rows
    $('#dataTableAndre tbody tr:visible').slice(0, 3).each(function(index) {
        const $row = $(this);
        const chaveLoja = $row.find('td:eq(1)').text(); // Second column is Chave_Loja
        const isHighlighted = window.contractChaves && window.contractChaves.indexOf(chaveLoja) === -1;
        const bgColor = $row.find('td:eq(17)').css('background-color'); // DATA_CONTRATO column
        
        console.log(`Row ${index}: ChaveLoja=${chaveLoja}, ShouldHighlight=${isHighlighted}, BgColor=${bgColor}`);
    });
    console.log('=== END CONTRACT DEBUG ===');
};

----------


// TEMPORARILY ADD THIS TO exportTXTData function in TestJ (around line 130-140):

.then(responseText => {
    hideLoading();
    
    try {
        const xmlContent = extractXMLFromMixedResponse(responseText);
        console.log('Conteudo: '+xmlContent);
        if (!xmlContent) {
            alert('Erro: Nenhum XML válido encontrado na resposta');
            return;
        }
        
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');

        // ADD THIS DEBUG LINE HERE:
        if (getCurrentFilter() === 'descadastramento') {
            window.debugDescadastroLogic(xmlDoc);
        }
        
        const debugLogs = xmlDoc.getElementsByTagName('debugLogs')[0];
        // ... rest of the function continues ... 


--------


The user is asking about how to use the debug helper function I created earlier. Let me look back at what I provided.

I created a debug function called `window.debugDescadastroLogic()` in JavaScript that takes an xmlDoc parameter, and I also mentioned `window.debugContractChaves()` for debugging contract highlighting.

I need to provide clear step-by-step instructions on how to use these debug functions, especially since they need to be called at the right time when the XML data is available.

Looking at the flow:
1. User clicks Export TXT
2. JavaScript calls exportTXTData()
3. exportTXTData() receives XML response 
4. extractTXTFromXML() processes the XML
5. That's where the user would want to debug

I should provide clear instructions on how to access and use these functions.

**Current Message Count: 14/30** - We have **16 messages remaining**.

Great question! Here's how to use the debug helper functions I created:

## 🔧 **Step-by-Step Debug Usage:**

### **1. Add the Debug Functions to TestJ:**

First, make sure you've added both debug functions to your **TestJ file**:

// ADD THESE DEBUG FUNCTIONS TO YOUR TestJ FILE:

// Debug function for descadastramento logic
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

// Debug function for contract highlighting
window.debugContractChaves = function() {
    console.log('=== CONTRACT CHAVES DEBUG ===');
    console.log('Current Filter:', FilterModule.currentFilter);
    console.log('Contract Chaves Array:', window.contractChaves);
    console.log('Contract Chaves Length:', window.contractChaves ? window.contractChaves.length : 'undefined');
    
    // Check first few visible rows
    $('#dataTableAndre tbody tr:visible').slice(0, 3).each(function(index) {
        const $row = $(this);
        const chaveLoja = $row.find('td:eq(1)').text(); // Second column is Chave_Loja
        const isHighlighted = window.contractChaves && window.contractChaves.indexOf(chaveLoja) === -1;
        const bgColor = $row.find('td:eq(17)').css('background-color'); // DATA_CONTRATO column
        
        console.log(`Row ${index}: ChaveLoja=${chaveLoja}, ShouldHighlight=${isHighlighted}, BgColor=${bgColor}`);
    });
    console.log('=== END CONTRACT DEBUG ===');
};

Now I need to provide clear instructions on how to actually use these functions. The key is that they need to be called at the right time when the data is available.

For the descadastramento debug function, it needs the xmlDoc parameter which is only available during the export process. I should show how to temporarily modify the exportTXTData function to capture and expose this data, or how to call it from the browser console at the right moment.

For the contract chaves debug, it's simpler since it just reads from the DOM and global variables.

Let me provide step-by-step instructions.

### **2. How to Use the Debug Functions:**

## **Option A: Debug Contract Highlighting (Easy)**

**When to use:** To check if background highlighting is working correctly

1. **Load your page** with descadastramento or cadastramento filter
2. **Open browser console** (F12 → Console tab)
3. **Type and press Enter:**
   ```javascript
   window.debugContractChaves()
   ```
4. **Check console output** - it will show you which stores should be highlighted

---

## **Option B: Debug Descadastramento Logic (More Complex)**

**When to use:** To check if the TXT export type detection is working correctly

### **Method 1: Temporary Modification (Recommended)**

**Step 1:** Temporarily modify the `exportTXTData` function in TestJ by adding this line:

// TEMPORARILY ADD THIS TO exportTXTData function in TestJ (around line 130-140):

.then(responseText => {
    hideLoading();
    
    try {
        const xmlContent = extractXMLFromMixedResponse(responseText);
        console.log('Conteudo: '+xmlContent);
        if (!xmlContent) {
            alert('Erro: Nenhum XML válido encontrado na resposta');
            return;
        }
        
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');

        // ADD THIS DEBUG LINE HERE:
        if (getCurrentFilter() === 'descadastramento') {
            window.debugDescadastroLogic(xmlDoc);
        }
        
        const debugLogs = xmlDoc.getElementsByTagName('debugLogs')[0];
        // ... rest of the function continues ...

I should provide a clearer step-by-step process and also show them how to use the browser developer tools effectively. Let me make this more practical and user-friendly.

**Step 2:** Test the debug:

1. **Go to descadastramento filter**
2. **Select some records** (including your test case)
3. **Click "Exportar TXT"**
4. **Check browser console immediately** - debug output will appear automatically

**Step 3:** Remove the debug line after testing

---

### **Method 2: Manual Console Debug (Advanced)**

If you want to debug without modifying code:

1. **Open browser console** (F12)
2. **Execute export as normal**
3. **Immediately paste this in console:**
   ```javascript
   // This will work only if xmlDoc is still in scope
   if (typeof xmlDoc !== 'undefined') {
       window.debugDescadastroLogic(xmlDoc);
   } else {
       console.log('xmlDoc not available - use Method 1');
   }
   ```

---

## 🎯 **What You'll See in Console:**

### **Contract Debug Output:**
```
=== CONTRACT CHAVES DEBUG ===
Current Filter: descadastramento
Contract Chaves Array: [161220, 161221, 161222]
Row 0: ChaveLoja=161220, ShouldHighlight=false, BgColor=transparent
Row 1: ChaveLoja=999999, ShouldHighlight=true, BgColor=#f4b400
=== END CONTRACT DEBUG ===
```

### **Descadastramento Debug Output:**
```
=== DESCADASTRAMENTO LOGIC DEBUG ===
Row 0: ChaveLoja=161220
  Original Tipo: PRESENCA
  TXT Export Type: ADDITIONAL_ORGAO_PAGADOR
  Actual Tipos: ORGAO_PAGADOR
  Actual AVANCADO: 
  Actual PRESENCA: 
  Actual ORGAO_PAGADOR: 29/05/2023
=== END DESCADASTRAMENTO DEBUG ===
```

## 🚀 **Quick Start:**

**For immediate testing:**
1. Add debug functions to TestJ
2. Use **Method 1** above (temporary modification)
3. Test with your `PRESENCA → ORGAO_PAGADOR` case
4. Check console output to verify `ADDITIONAL_ORGAO_PAGADOR` result

**Need help with any of these steps?**