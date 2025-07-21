The user doesn't want to add CSS, so I need to remove the CSS parts from my response and just focus on the JavaScript fix that will build the accordion from the XML data they showed me.

Looking at the XML data they provided:
```xml
<tableData>
    <row>
        <0>1</0>
        <CHAVE_LOTE>1</CHAVE_LOTE>
        <1>Jul 21 2025  4:14PM</1>
        <DATA_LOG>Jul 21 2025  4:14PM</DATA_LOG>
        <2>1</2>
        <TOTAL_REGISTROS>1</TOTAL_REGISTROS>
        <3>MULTIDROGAS</3>
        <PRIMEIRO_NOME_LOJA>MULTIDROGAS</PRIMEIRO_NOME_LOJA>
        <4>Cadastramento</4>
        <FILTRO>Cadastramento</FILTRO>
    </row>
</tableData>
```

The data is there, so the JavaScript just needs to build the accordion from this data. I'll provide just the JavaScript update without any CSS.

**Update only the JavaScript (TestJ.txt) - Fix the FilterModule.loadTableData method:**

```javascript
const FilterModuleUpdated = {
    ...FilterModule,
    
    loadTableData: function(filter) {
        showLoading();
        
        // Show/hide appropriate containers based on filter
        if (filter === 'historico') {
            $('.table-scrollable').hide();
            $('#historicoAccordion').show();
        } else {
            $('#historicoAccordion').hide();
            $('.table-scrollable').show();
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        }

        $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        const cardData = {
                            cadastramento: $xml.find('cardData cadastramento').text(),
                            descadastramento: $xml.find('cardData descadastramento').text(),
                            historico: $xml.find('cardData historico').text()
                        };
                        
                        this.updateCardCounts(cardData);
                        
                        if (filter === 'historico') {
                            // Build accordion HTML from XML data
                            this.buildHistoricoAccordion($xml);
                            hideLoading();
                        } else {
                            // Handle normal table data
                            const tableData = [];
                            $xml.find('tableData row').each(function() {
                                const row = {};
                                $(this).children().each(function() {
                                    row[this.tagName] = $(this).text();
                                });
                                tableData.push(row);
                            });

                            PaginationModule.replaceTableDataEnhanced(tableData);
                            PaginationModule.currentPage = 1;
                            PaginationModule.updateTable();
                            
                            CheckboxModule.clearSelections();
                            
                            setTimeout(() => {
                                CheckboxModule.init();
                                CheckboxModule.updateSelectAllState();
                                CheckboxModule.updateExportButton();
                            }, 200);
                            
                            setTimeout(() => {
                                StatusFilterModule.reapplyAfterDataLoad();
                                hideLoading();
                            }, 400);
                        }
                    }
                } catch (e) {
                    console.error('Error parsing XML: ', e);
                    if (filter !== 'historico') {
                        $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
                    }
                    hideLoading();
                }
            })
            .fail((jqXHR, textStatus, errorThrown) => {
                console.error('AJAX failed:', textStatus, errorThrown);
                if (filter !== 'historico') {
                    $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
                }
                hideLoading();
            });
    },
    
    buildHistoricoAccordion: function($xml) {
        let accordionHtml = '';
        const rows = $xml.find('tableData row');
        
        console.log('Building accordion with', rows.length, 'rows');
        
        if (rows.length > 0) {
            accordionHtml = '<div class="panel-group accordion" id="accordions">';
            
            rows.each(function() {
                const row = {};
                $(this).children().each(function() {
                    row[this.tagName] = $(this).text();
                });
                
                console.log('Processing row:', row);
                
                const chaveLote = row.CHAVE_LOTE || row['0'];
                const dataLog = row.DATA_LOG || row['1'];
                const totalRegistros = row.TOTAL_REGISTROS || row['2'];
                const primeiroNomeLoja = row.PRIMEIRO_NOME_LOJA || row['3'];
                const filtro = row.FILTRO || row['4'];
                
                // Format date
                let formattedDate = dataLog;
                try {
                    const date = new Date(dataLog);
                    if (!isNaN(date.getTime())) {
                        formattedDate = date.toLocaleDateString('pt-BR') + ' ' + 
                                      date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                    }
                } catch (e) {
                    console.log('Date formatting error:', e);
                }
                
                accordionHtml += `
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a class="accordion-toggle collapsed" data-toggle="collapse" 
                                   data-parent="#accordions" href="#collapse${chaveLote}" 
                                   aria-expanded="false">
                                    <i class="fa-fw fa fa-history"></i> 
                                    Lote #${chaveLote} - ${formattedDate} - 
                                    ${totalRegistros} registro(s) - 
                                    ${primeiroNomeLoja}
                                </a>
                            </h4>
                        </div>
                        <div id="collapse${chaveLote}" class="panel-collapse collapse" 
                             aria-expanded="false" style="height: 0px;">
                            <div class="panel-body border-red">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Chave Loja</th>
                                                <th>Nome Loja</th>
                                                <th>Cod Empresa</th>
                                                <th>Cod Loja</th>
                                                <th>Data Log</th>
                                                <th>Filtro</th>
                                            </tr>
                                        </thead>
                                        <tbody class="historico-details" data-chave-lote="${chaveLote}">
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <button class="btn btn-sm btn-info load-details" 
                                                            data-chave-lote="${chaveLote}">
                                                        <i class="fa fa-download"></i> Carregar Detalhes
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            accordionHtml += '</div>';
        } else {
            accordionHtml = `
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    Nenhum histórico encontrado. Os registros aparecerão aqui após a exportação de arquivos TXT.
                </div>
            `;
        }
        
        // Create or update the accordion container
        if ($('#historicoAccordion').length === 0) {
            $('.table-scrollable').after('<div id="historicoAccordion"></div>');
        }
        
        $('#historicoAccordion').html(accordionHtml);
        console.log('Accordion HTML created');
    }
};

// Replace FilterModule with the updated version
Object.assign(FilterModule, FilterModuleUpdated);
```

**And add the container div in your HTML (TestH.txt) right after the table-scrollable div:**

```php
                    </div>
                </div>
            </div>
            
            <!-- Add this accordion container -->
            <div id="historicoAccordion" style="display: none;"></div>
            
            <div class="row mt-3">
```

This will build the accordion dynamically from the XML data without requiring any additional CSS.