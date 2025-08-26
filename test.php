function exportSelectedAccess() {
    const currentFilter = getCurrentFilter();

    const selectedIds = (currentFilter === 'historico')
        ? HistoricoCheckboxModule.getSelectedIds()
        : CheckboxModule.getSelectedIds();

    if (selectedIds.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }

    exportAccessData(selectedIds.join(','), currentFilter);
}

function exportAccessData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportAccess&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const xmlContent = extractXMLFromMixedResponse(responseText);
                if (!xmlContent) {
                    alert('Erro: Nenhum XML válido encontrado na resposta');
                    return;
                }
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, 'text/xml');
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    const errorMsg = xmlDoc.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido';
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                // Check ACC_FLAG update result
                const accFlagUpdate = xmlDoc.getElementsByTagName('accFlagUpdate')[0];
                if (accFlagUpdate) {
                    const accSuccess = xmlDoc.getElementsByTagName('accFlagUpdate')[0].getElementsByTagName('success')[0]?.textContent === 'true';
                    const recordsUpdated = xmlDoc.getElementsByTagName('accFlagUpdate')[0].getElementsByTagName('recordsUpdated')[0]?.textContent || '0';
                    
                    if (accSuccess && parseInt(recordsUpdated) > 0) {
                        console.log(`ACC_FLAG updated for ${recordsUpdated} records`);
                    }
                }
                
                const csvFiles = extractAccessCSVFromXML(xmlDoc);
                
                if (csvFiles.length === 0) {
                    alert('Erro: Nenhum arquivo CSV gerado');
                    return;
                }
                
                csvFiles.forEach((file, index) => {
                    setTimeout(() => {
                        downloadAccessCSVFile(file.content, file.filename);
                    }, index * 1500);
                });
                
                // Reload the table after successful export to update lock icons
                setTimeout(() => {
                    console.log('Reloading table after Access export to update lock icons...');
                    CheckboxModule.clearSelections();
                    const currentFilter = FilterModule.currentFilter;
                    FilterModule.loadTableData(currentFilter);
                }, 2000); // Wait for downloads to start
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição');
        });
}

function extractAccessCSVFromXML(xmlDoc) {
    const csvFiles = [];
    const timestamp = getCurrentTimestamp();
    const filter = getCurrentFilter();
    
    const file1Data = xmlDoc.getElementsByTagName('avUnPrData')[0];
    if (file1Data) {
        let csvContent1 = 'cod_empresa\r\n';
        const empresas1 = file1Data.getElementsByTagName('empresa');
        
        for (let i = 0; i < empresas1.length; i++) {
            const empresa = empresas1[i].textContent || empresas1[i].text || '';
            if (empresa) {
                csvContent1 += empresa + '\r\n';
            }
        }
        
        csvFiles.push({
            filename: `access_av_un_pr_${filter}_${timestamp}.csv`,
            content: csvContent1
        });
    }
    
    const file2Data = xmlDoc.getElementsByTagName('opData')[0];
    if (file2Data) {
        let csvContent2 = 'cod_empresa\r\n';
        const empresas2 = file2Data.getElementsByTagName('empresa');
        
        for (let i = 0; i < empresas2.length; i++) {
            const empresa = empresas2[i].textContent || empresas2[i].text || '';
            if (empresa) {
                csvContent2 += empresa + '\r\n';
            }
        }
        
        csvFiles.push({
            filename: `access_op_${filter}_${timestamp}.csv`,
            content: csvContent2
        });
    }
    
    return csvFiles;
}

// Keep the original CSV download function specifically for Access exports
function downloadAccessCSVFile(csvContent, filename) {
    const csvWithBOM = '\uFEFF' + csvContent;
    const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
    
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);
    } else {
        alert('Seu navegador não suporta download automático.');
    }
}

// Keep these existing helper functions
function downloadCSVFile(csvContent, filename) {
    const csvWithBOM = '\uFEFF' + csvContent;
    const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
    downloadFile(blob, filename);
}

function downloadFile(blob, filename) {
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => {
            URL.revokeObjectURL(url);
        }, 1000);
    } else {
        alert('Seu navegador não suporta download automático.');
    }
}