// Add this function to help debug and verify the descadastramento logic
const DescadastramentoDebug = {
    testStatusGeneration: function(sampleRow) {
        console.log('=== TESTING DESCADASTRAMENTO STATUS GENERATION ===');
        console.log('Sample row:', sampleRow);
        
        const fields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'ORG_PAGADOR': 'OP',
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const cutoff = new Date(2025, 5, 1);
        const tipo = sampleRow['TIPO_CORRESPONDENTE'] ? sampleRow['TIPO_CORRESPONDENTE'].toUpperCase().trim() : '';
        const dataConclusao = sampleRow['DATA_CONCLUSAO'] ? sampleRow['DATA_CONCLUSAO'].toString().trim() : '';
        
        console.log('Parsed values:');
        console.log('- tipo:', tipo);
        console.log('- dataConclusao:', dataConclusao);
        console.log('- cutoff:', cutoff);
        
        // Test date parsing
        const dateObj = PaginationModule.parseDate(dataConclusao);
        console.log('- parsed date:', dateObj);
        console.log('- is after cutoff:', dateObj ? (dateObj > cutoff) : 'null');
        
        // Test field matching
        const displayFields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        console.log('\nField matching results:');
        for (const field in displayFields) {
            const label = displayFields[field];
            const fieldMatches = (field === tipo) || 
                            (field === 'ORGAO_PAGADOR' && tipo === 'ORG_PAGADOR') ||
                            (field === 'ORG_PAGADOR' && tipo === 'ORGAO_PAGADOR');
            
            const isOn = fieldMatches && dateObj !== null && dateObj > cutoff;
            const color = isOn ? 'green' : 'gray';
            
            console.log(`- ${field} (${label}): matches=${fieldMatches}, isOn=${isOn}, color=${color}`);
        }
        
        console.log('=== END TEST ===');
    },
    
    // Test with sample data from different scenarios
    runTests: function() {
        console.log('Running descadastramento tests...');
        
        // Test case 1: AVANCADO with recent date (should show AV as green)
        this.testStatusGeneration({
            'TIPO_CORRESPONDENTE': 'AVANCADO',
            'DATA_CONCLUSAO': '15/07/2025'  // After cutoff
        });
        
        // Test case 2: PRESENCA with old date (should show all gray)
        this.testStatusGeneration({
            'TIPO_CORRESPONDENTE': 'PRESENCA',
            'DATA_CONCLUSAO': '15/05/2025'  // Before cutoff
        });
        
        // Test case 3: ORG_PAGADOR variant (should show OP as green)
        this.testStatusGeneration({
            'TIPO_CORRESPONDENTE': 'ORG_PAGADOR',
            'DATA_CONCLUSAO': '15/08/2025'  // After cutoff
        });
        
        // Test case 4: ORGAO_PAGADOR variant (should show OP as green)
        this.testStatusGeneration({
            'TIPO_CORRESPONDENTE': 'ORGAO_PAGADOR',
            'DATA_CONCLUSAO': '15/08/2025'  // After cutoff
        });
    }
};

// Call this in browser console to test: DescadastramentoDebug.runTests();