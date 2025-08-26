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
                
                const xlsxFiles = extractAccessXLSXFromXML(xmlDoc);
                
                if (xlsxFiles.length === 0) {
                    alert('Erro: Nenhum arquivo XLSX gerado');
                    return;
                }
                
                xlsxFiles.forEach((file, index) => {
                    setTimeout(() => {
                        downloadAccessXLSXFile(file.workbook, file.filename);
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

function extractAccessXLSXFromXML(xmlDoc) {
    const xlsxFiles = [];
    const timestamp = getCurrentTimestamp();
    const filter = getCurrentFilter();
    
    // Process AV/UN/PR data
    const file1Data = xmlDoc.getElementsByTagName('avUnPrData')[0];
    if (file1Data) {
        const empresas1 = file1Data.getElementsByTagName('empresa');
        const worksheetData1 = [['cod_empresa']]; // Header row
        
        for (let i = 0; i < empresas1.length; i++) {
            const empresa = empresas1[i].textContent || empresas1[i].text || '';
            if (empresa) {
                worksheetData1.push([empresa]);
            }
        }
        
        if (worksheetData1.length > 1) { // Only create file if there's data beyond header
            const workbook1 = XLSX.utils.book_new();
            const worksheet1 = XLSX.utils.aoa_to_sheet(worksheetData1);
            
            // Auto-size column
            worksheet1['!cols'] = [{ wch: 15 }];
            
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(workbook1, worksheet1, 'AV_UN_PR_Data');
            
            xlsxFiles.push({
                filename: `access_av_un_pr_${filter}_${timestamp}.xlsx`,
                workbook: workbook1
            });
        }
    }
    
    // Process OP data
    const file2Data = xmlDoc.getElementsByTagName('opData')[0];
    if (file2Data) {
        const empresas2 = file2Data.getElementsByTagName('empresa');
        const worksheetData2 = [['cod_empresa']]; // Header row
        
        for (let i = 0; i < empresas2.length; i++) {
            const empresa = empresas2[i].textContent || empresas2[i].text || '';
            if (empresa) {
                worksheetData2.push([empresa]);
            }
        }
        
        if (worksheetData2.length > 1) { // Only create file if there's data beyond header
            const workbook2 = XLSX.utils.book_new();
            const worksheet2 = XLSX.utils.aoa_to_sheet(worksheetData2);
            
            // Auto-size column
            worksheet2['!cols'] = [{ wch: 15 }];
            
            // Add worksheet to workbook
            XLSX.utils.book_append_sheet(workbook2, worksheet2, 'OP_Data');
            
            xlsxFiles.push({
                filename: `access_op_${filter}_${timestamp}.xlsx`,
                workbook: workbook2
            });
        }
    }
    
    return xlsxFiles;
}

function downloadAccessXLSXFile(workbook, filename) {
    try {
        // Generate XLSX file
        const xlsxData = XLSX.write(workbook, { 
            bookType: 'xlsx', 
            type: 'array',
            compression: true
        });
        
        // Create blob and download
        const blob = new Blob([xlsxData], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        });
        
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
            alert('Seu navegador não suporta download automático de XLSX.');
        }
    } catch (error) {
        console.error('Erro ao gerar arquivo XLSX para Access:', error);
        alert('Erro ao gerar arquivo XLSX: ' + error.message);
    }
}