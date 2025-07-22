function showLoading() {
    $('#modal_loading').css({'display':'flex'});
}

function hideLoading() {
    $('#modal_loading').css({'display':'none'});
}

const StatusFilterModule = {
    activeStatusFilters: {},
    
    init: function() {
        this.activeStatusFilters = {};
        this.attachEventListeners();
    },
    
    attachEventListeners: function() {
        $('.status-filter-btn').off('click.statusFilter').on('click.statusFilter', this.handleStatusFilterClick.bind(this));
    },
    
    handleStatusFilterClick: function(e) {
        const button = $(e.currentTarget);
        const fieldName = button.data('field');
        
        if (this.activeStatusFilters[fieldName]) {
            delete this.activeStatusFilters[fieldName];
            button.removeClass('active');
        } else {
            this.activeStatusFilters[fieldName] = true;
            button.addClass('active');
        }
        
        this.updateFilterIndicator();
        this.applyStatusFilters();
    },
    
    updateFilterIndicator: function() {
        const activeCount = Object.keys(this.activeStatusFilters).length;
        const indicator = $('#statusFilterIndicator');
        
        if (activeCount > 0) {
            const filterNames = {
                'AVANCADO': 'Avançado',
                'ORGAO_PAGADOR': 'Órgão Pagador', 
                'PRESENCA': 'Presença',
                'UNIDADE_NEGOCIO': 'Unidade Negócio'
            };
            
            const activeFilterNames = Object.keys(this.activeStatusFilters)
                .map(field => filterNames[field])
                .join(', ');
                
            indicator.find('#activeStatusFilters').text(activeFilterNames);
            indicator.fadeIn();
        } else {
            indicator.fadeOut();
        }
    },
    
    applyStatusFilters: function() {
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
        
        setTimeout(() => {
            CheckboxModule.updateSelectAllState();
            CheckboxModule.updateExportButton();
        }, 100);
    },
    
    filterRowData: function(rowData) {
        if (Object.keys(this.activeStatusFilters).length === 0) {
            return true;
        }
        
        const statusCell = rowData[6];
        if (!statusCell) return false;
        
        for (const fieldName in this.activeStatusFilters) {
            if (!this.isFieldActive(statusCell, fieldName)) {
                return false;
            }
        }
        
        return true;
    },
    
    isFieldActive: function(statusCell, fieldName) {
        const cellHtml = statusCell.html();
        const fieldLabels = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'PRESENCA': 'PR', 
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const label = fieldLabels[fieldName];
        if (!label) return false;
        
        const regex = new RegExp(`background-color:\\s*green[^>]*>${label}<`, 'i');
        return regex.test(cellHtml);
    }
};

const FilterModule = {
    currentFilter: 'all',
    
    init: function() {
        this.currentFilter = window.currentFilter || 'all';
        this.updateFilterUI();
        
        $('.card-filter').on('click', this.handleCardClick.bind(this));
        
        if (this.currentFilter !== 'all') {
            this.setActiveCard(this.currentFilter);
        }
    },
    
    handleCardClick: function(e) {
        const filter = $(e.currentTarget).data('filter');
        this.applyFilter(filter);
    },
    
    applyFilter: function(filter) {
        if (this.currentFilter === filter && filter !== 'all') {
            this.clearFilter();
            return;
        }
        
        this.currentFilter = filter;
        this.setActiveCard(filter);
        this.updateFilterUI();
        this.loadTableData(filter);
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('filter', filter);
        window.history.pushState({filter: filter}, '', newUrl);
    },
    
    clearFilter: function() {
        this.currentFilter = 'all';
        $('.card-filter').removeClass('active');
        $('#filterIndicator').fadeOut();
        
        // Show regular table and hide historico accordion
        $('.table-scrollable').show();
        $('#historicoAccordion').hide();
        $('.row.mt-3').show(); // Show pagination
        
        this.loadTableData('all');
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('filter');
        window.history.pushState({filter: 'all'}, '', newUrl);
    },
    
    setActiveCard: function(filter) {
        $('.card-filter').removeClass('active');
        $(`#card-${filter}`).addClass('active');
    },
    
    updateFilterUI: function() {
        if (this.currentFilter === 'all') {
            $('#filterIndicator').hide();
        } else {
            const filterNames = {
                'cadastramento': 'Cadastramento',
                'descadastramento': 'Descadastramento',
                'historico': 'Histórico'
            };
            
            const filterDescriptions = {
                'cadastramento': 'Mostrando apenas lojas para cadastramento',
                'descadastramento': 'Mostrando apenas lojas para descadastramento',
                'historico': 'Mostrando histórico de processos realizados'
            };
            
            $('#activeFilterName').text(filterNames[this.currentFilter]);
            $('#filterDescription').text(filterDescriptions[this.currentFilter]);
            $('#filterIndicator').fadeIn();
        }
    },
    
    loadTableData: function(filter) {
        showLoading();
        
        if (filter === 'historico') {
            $('.table-scrollable').hide();
            $('#historicoAccordion').show();
            $('.row.mt-3').hide(); // Hide pagination for historico
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        } else {
            $('#historicoAccordion').hide();
            $('.table-scrollable').show();
            $('.row.mt-3').show(); // Show pagination for regular table
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
                            this.buildHistoricoAccordion($xml);
                            // Initialize checkbox functionality for historico
                            setTimeout(() => {
                                HistoricoCheckboxModule.init();
                                hideLoading();
                            }, 100);
                        } else {
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
    
    updateCardCounts: function(cardData) {
        $('#card-cadastramento .databox-number').text(cardData.cadastramento);
        $('#card-descadastramento .databox-number').text(cardData.descadastramento);
        $('#card-historico .databox-number').text(cardData.historico);
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
                const filtro = row.FILTRO || row['3'];
                
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
                                <label style="margin-right: 10px;">
                                    <input type="checkbox" class="form-check-input historico-lote-checkbox" 
                                           value="${chaveLote}" data-chave-lote="${chaveLote}">
                                    <span class="text"></span>
                                </label>
                                <a class="accordion-toggle collapsed" data-toggle="collapse" 
                                   data-parent="#accordions" href="#collapse${chaveLote}" 
                                   aria-expanded="false">
                                    <i class="fa-fw fa fa-history"></i> 
                                    Lote #${chaveLote} - ${formattedDate} - 
                                    ${totalRegistros} registro(s) - Filtro: ${filtro}
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
                                                <th>
                                                    <label>
                                                        <input type="checkbox" class="form-check-input select-all-details" 
                                                               data-chave-lote="${chaveLote}">
                                                        <span class="text"></span>
                                                    </label>
                                                </th>
                                                <th>Chave Loja</th>
                                                <th>Nome Loja</th>
                                                <th>Cod Empresa</th>
                                                <th>Cod Loja</th>
                                                <th>Tipo Correspondente</th>
                                                <th>Dep Dinheiro</th>
                                                <th>Dep Cheque</th>
                                                <th>Rec Retirada</th>
                                                <th>Saque Cheque</th>
                                                <th>Segunda Via Cartão</th>
                                                <th>Holerite INSS</th>
                                                <th>Cons INSS</th>
                                                <th>Prova de Vida</th>
                                                <th>Data Contrato</th>
                                                <th>Tipo Contrato</th>
                                                <th>Data Log</th>
                                                <th>Filtro</th>
                                            </tr>
                                        </thead>
                                        <tbody class="historico-details" data-chave-lote="${chaveLote}">
                                            <tr>
                                                <td colspan="18" class="text-center">
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
        $('#historicoAccordion').html(accordionHtml);
        console.log('Accordion HTML created');
    }
};

const SearchModule = {
    init: function() {
        $('#searchInput').on('keyup', this.filterTable.bind(this));
    },
    
    filterTable: function() {
        const value = $('#searchInput').val().toLowerCase();
        PaginationModule.searchTerm = value;
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
    }
};

const SortModule = {
    sortDirection: {},
    sortColumn: null,
    
    init: function() {
        $('.sortable').on('click', this.sortTable.bind(this));
    },
    
    sortTable: function(e) {
        const column = $(e.currentTarget).data('column');
        this.sortColumn = column;
        
        this.sortDirection[column] = this.sortDirection[column] === 'asc' ? 'desc' : 'asc';
        const direction = this.sortDirection[column];
        
        $('.sortable').removeClass('sort-asc sort-desc');
        $(e.currentTarget).addClass('sort-' + direction);
        
        PaginationModule.sortData();
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
    }
};

const PaginationModule = {
    currentPage: 1,
    itemsPerPage: 15,
    searchTerm: '',
    allData: [],
    filteredData: [],
    
    init: function() {
        this.captureTableData();
        $('#itemsPerPage').on('change', this.changeItemsPerPage.bind(this));
        this.updateTable();
    },
    
    captureTableData: function() {
        const rows = $('#dataTableAndre tbody tr');
        this.allData = [];
        
        rows.each((index, row) => {
            const rowData = [];
            $(row).find('td').each(function() {
                rowData.push($(this).clone());
            });
            this.allData.push(rowData);
        });
    },
    
    replaceTableDataEnhanced: function(newData) {
        this.allData = [];
        
        newData.forEach((row, index) => {
            const rowData = this.createRowData(row, index);
            this.allData.push(rowData);
        });
    },
    
    createRowData: function(row, index) {
        const rowData = [];
        const highlightMissing = contractChaves.indexOf(String(row.CHAVE_LOJA)) === -1;
        const highlightStyle = highlightMissing ? '#f4b400' : 'transparent';
        
        const checkboxCell = this.createCheckboxCell(index);
        if (this.hasValidationError(row)) {
            const lockIcon = $('<i class="fa fa-lock">')
                .css({
                    'color': '#d9534f',
                    'margin-left': '5px',
                    'cursor': 'help'    
                })
                .attr('title', 'Essa Loja não pode ser exportada como txt por: ' + this.getValidationErrorMessage(row));
            checkboxCell.append(lockIcon);
        }
        rowData.push(checkboxCell);
        
        rowData.push($('<td>').text(row.CHAVE_LOJA));
        rowData.push($('<td>').text(row.NOME_LOJA));
        rowData.push($('<td>').text(row.COD_EMPRESA));
        rowData.push($('<td>').text(row.COD_LOJA));
        rowData.push($('<td>').text(row.QUANT_LOJAS));
        
        rowData.push($('<td>').html(this.generateStatusHTML(row)));
        
        rowData.push($('<td>').html(this.generateDateFieldsHTML(row)));
        rowData.push($('<td>').text(this.formatDataSolicitacao(row)));
        
        this.addValidationFields(rowData, row);
        
        this.addContractFields(rowData, row, highlightStyle);
        
        return rowData;
    },
    
    createCheckboxCell: function(index) {
        const checkboxCell = $('<td class="checkbox-column">');
        const label = $('<label>');
        const sequentialId = index + 1;
        const checkbox = $('<input>')
            .attr({
                'type': 'checkbox',
                'class': 'form-check-input row-checkbox',
                'data-row-id': sequentialId,
                'value': sequentialId,
                'id': sequentialId
            });
        const span = $('<span class="text">');
        
        label.append(checkbox).append(span);
        checkboxCell.append(label);
        
        return checkboxCell;
    },
    
    generateStatusHTML: function(row) {
        const fields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP', 
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const cutoff = new Date(2025, 5, 1);
        let html = '<div class="status-container">';
        
        for (const field in fields) {
            const label = fields[field];
            const raw = row[field] ? row[field].toString().trim() : '';
            const dateObj = this.parseDate(raw);
            const isOn = dateObj !== null && dateObj > cutoff;
            const color = isOn ? 'green' : 'gray';
            const status = isOn ? 'active' : 'inactive';
            
            html += `<div style="display:inline-block;width:30px;height:30px;margin-right:5px;text-align:center;line-height:30px;font-size:10px;font-weight:bold;color:white;background-color:${color};border-radius:4px;" data-field="${field}" data-status="${status}">${label}</div>`;
        }
        
        html += '</div>';
        return html;
    },
    
    generateDateFieldsHTML: function(row) {
        const fields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP', 
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const cutoff = new Date(2025, 5, 1);
        const matchingDates = [];
        
        for (const field in fields) {
            const raw = row[field] ? row[field].toString().trim() : '';
            const dateObj = this.parseDate(raw);
            
            if (dateObj && dateObj > cutoff) {
                matchingDates.push(raw);
            }
        }
        
        return matchingDates.length > 0 ? matchingDates.join(' / ') : '—';
    },
    
    formatDataSolicitacao: function(row) {
        const dataSolicitacaoRaw = row.DATA_SOLICITACAO ? row.DATA_SOLICITACAO.toString().trim() : '';
        
        if (dataSolicitacaoRaw) {
            return this.formatDateKeepTime(dataSolicitacaoRaw);
        } else {
            return this.generateDateFieldsHTML(row);
        }
    },
    
    addValidationFields: function(rowData, row) {
        const validationConfigs = [
            { field: 'dep_dinheiro', limits: { presenca: 'R$ 3.000,00', avancado: 'R$ 10.000,00' }, validField: 'DEP_DINHEIRO_VALID' },
            { field: 'dep_cheque', limits: { presenca: 'R$ 5.000,00', avancado: 'R$ 10.000,00' }, validField: 'DEP_CHEQUE_VALID' },
            { field: 'rec_retirada', limits: { presenca: 'R$ 2.000,00', avancado: 'R$ 3.500,00' }, validField: 'REC_RETIRADA_VALID' },
            { field: 'saque_cheque', limits: { presenca: 'R$ 2.000,00', avancado: 'R$ 3.500,00' }, validField: 'SAQUE_CHEQUE_VALID' }
        ];
        
        validationConfigs.forEach(config => {
            rowData.push(this.createValidationCell(row, config));
        });
        
        rowData.push(this.createSimpleValidationCell(row, 'SEGUNDA_VIA_CARTAO_VALID'));
        rowData.push(this.createSimpleValidationCell(row, 'HOLERITE_INSS_VALID'));
        rowData.push(this.createSimpleValidationCell(row, 'CONSULTA_INSS_VALID'));
        rowData.push(this.createSimpleValidationCell(row, 'PROVA_DE_VIDA_VALID'));
    },
    
    createValidationCell: function(row, config) {
        const isValid = row[config.validField] === '1';
        const isPresencaOrOrgao = row.TIPO_LIMITES.includes('PRESENCA') || row.TIPO_LIMITES.includes('ORG_PAGADOR');
        const isAvancadoOrApoio = row.TIPO_LIMITES.includes('AVANCADO') || row.TIPO_LIMITES.includes('UNIDADE_NEGOCIO');
        
        let value = '';
        if (isPresencaOrOrgao) {
            value = config.limits.presenca;
        } else if (isAvancadoOrApoio) {
            value = config.limits.avancado;
        }
        
        const style = isValid ? 'text-align: center; vertical-align: middle;' : 'text-align: center; vertical-align: middle; background-color: #ffb7bb;';
        
        return $('<td>').attr('style', style).text(value);
    },
    
    createSimpleValidationCell: function(row, validField) {
        const isValid = row[validField] === '1';
        const value = isValid ? 'Apto' : 'Não Apto';
        const style = isValid ? 'text-align: center; vertical-align: middle;' : 'text-align: center; vertical-align: middle; background-color: #ffb7bb;';
        
        return $('<td>').attr('style', style).text(value);
    },
    
    addContractFields: function(rowData, row, highlightStyle) {
        if (!row.DATA_CONTRATO) {
            const warningCell = $('<td>')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
                .css({
                    'background-color': '#fb6e52',
                    'text-align': 'center',
                    'vertical-align': 'middle'
                });
            rowData.push(warningCell);
        } else {
            const formattedDate = this.formatDateKeepTime(row.DATA_CONTRATO);
            rowData.push($('<td>')
                .text(formattedDate)
                .css({
                    'background-color': highlightStyle,
                    'text-align': 'center',
                    'vertical-align': 'middle'
                }));
        }
        
        if (!row.TIPO_CONTRATO) {
            const warningCell = $('<td>')
                .html('<i><strong><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> NULL</strong></i>')
                .css({
                    'background-color': '#fb6e52',
                    'text-align': 'center',
                    'vertical-align': 'middle'
                });
            rowData.push(warningCell);
        } else {
            rowData.push($('<td>')
                .text(row.TIPO_CONTRATO)
                .css({
                    'background-color': highlightStyle,
                    'text-align': 'center',
                    'vertical-align': 'middle'
                }));
        }
    },
    
    hasValidationError: function(row) {
        const cutoff = new Date(2025, 5, 1);
        const activeTypes = this.getActiveTypes(row, cutoff);
        
        for (let i = 0; i < activeTypes.length; i++) {
            const type = activeTypes[i];
            if (['AV', 'PR', 'UN'].includes(type)) {
                if (!this.checkBasicValidationsJS(row)) {
                    return true;
                }
            } else if (type === 'OP') {
                if (!this.checkOPValidationsJS(row)) {
                    return true;
                }
            }
        }
        
        return false;
    },
    
    getValidationErrorMessage: function(row) {
        const cutoff = new Date(2025, 5, 1);
        const activeTypes = this.getActiveTypes(row, cutoff);
        
        for (let i = 0; i < activeTypes.length; i++) {
            const type = activeTypes[i];
            if (['AV', 'PR', 'UN'].includes(type)) {
                const basicError = this.getBasicValidationErrorJS(row);
                if (basicError) {
                    return `Tipo ${type} - ${basicError}`;
                }
            } else if (type === 'OP') {
                const opError = this.getOPValidationErrorJS(row);
                if (opError) {
                    return opError;
                }
            }
        }
        
        return 'Erro de validacao';
    },
    
    getActiveTypes: function(row, cutoff) {
        const fields = {
            'AVANCADO': 'AV',
            'ORGAO_PAGADOR': 'OP',
            'PRESENCA': 'PR',
            'UNIDADE_NEGOCIO': 'UN'
        };
        
        const activeTypes = [];
        
        for (const field in fields) {
            const raw = row[field] ? row[field].toString().trim() : '';
            if (raw) {
                const dateObj = this.parseDate(raw);
                if (dateObj && dateObj > cutoff) {
                    activeTypes.push(fields[field]);
                }
            }
        }
        
        return activeTypes;
    },
    
    checkBasicValidationsJS: function(row) {
        const requiredFields = ['DEP_DINHEIRO_VALID', 'DEP_CHEQUE_VALID', 'REC_RETIRADA_VALID', 'SAQUE_CHEQUE_VALID'];
        return requiredFields.every(field => row[field] && row[field] === '1');
    },
    
    getBasicValidationErrorJS: function(row) {
        const requiredFields = {
            'DEP_DINHEIRO_VALID': 'Deposito Dinheiro',
            'DEP_CHEQUE_VALID': 'Deposito Cheque',
            'REC_RETIRADA_VALID': 'Recarga/Retirada',
            'SAQUE_CHEQUE_VALID': 'Saque Cheque'
        };
        
        const missingFields = [];
        for (const field in requiredFields) {
            if (!row[field] || row[field] !== '1') {
                missingFields.push(requiredFields[field]);
            }
        }
        
        return missingFields.length > 0 ? `Validacoes pendentes: ${missingFields.join(', ')}` : null;
    },
    
    checkOPValidationsJS: function(row) {
        const version = this.extractVersionFromContractJS(row.TIPO_CONTRATO || '');
        if (version === null) return false;
        
        const requiredFields = ['DEP_DINHEIRO_VALID', 'DEP_CHEQUE_VALID', 'REC_RETIRADA_VALID', 'SAQUE_CHEQUE_VALID', 'HOLERITE_INSS_VALID', 'CONSULTA_INSS_VALID'];
        if (version > 10.1) {
            requiredFields.push('SEGUNDA_VIA_CARTAO_VALID');
        }
        
        return requiredFields.every(field => row[field] && row[field] === '1');
    },
    
    getOPValidationErrorJS: function(row) {
        const version = this.extractVersionFromContractJS(row.TIPO_CONTRATO || '');
        if (version === null) {
            return `Tipo de contrato nao pode ser exportado: ${row.TIPO_CONTRATO || ''}`;
        }
        
        const requiredFields = {
            'DEP_DINHEIRO_VALID': 'Deposito Dinheiro',
            'DEP_CHEQUE_VALID': 'Deposito Cheque',
            'REC_RETIRADA_VALID': 'Recarga/Retirada',
            'SAQUE_CHEQUE_VALID': 'Saque Cheque',
            'HOLERITE_INSS_VALID': 'Holerite INSS',
            'CONSULTA_INSS_VALID': 'Consulta INSS'
        };
        
        if (version > 10.1) {
            requiredFields['SEGUNDA_VIA_CARTAO_VALID'] = 'Segunda Via Cartao';
        }
        
        const missingFields = [];
        for (const field in requiredFields) {
            if (!row[field] || row[field] !== '1') {
                missingFields.push(requiredFields[field]);
            }
        }
        
        return missingFields.length > 0 ? `Validacoes OP pendentes (v${version}): ${missingFields.join(', ')}` : null;
    },
    
    extractVersionFromContractJS: function(tipoContrato) {
        const match = tipoContrato.match(/(\d+\.\d+)/);
        if (match) {
            const version = parseFloat(match[1]);
            return version >= 8.1 ? version : null;
        }
        return null;
    },
    
    parseDate: function(dateString) {
        if (!dateString || typeof dateString !== 'string') {
            return null;
        }
        
        const parts = dateString.trim().split('/');
        if (parts.length !== 3) {
            return null;
        }
        
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const year = parseInt(parts[2], 10);
        
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            return null;
        }
        
        const date = new Date(year, month, day);
        
        if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
            return null;
        }
        
        return date;
    },
    
    formatDateKeepTime: function(dateStr) {
        const parts = dateStr.match(/^([A-Za-z]+) (\d{1,2}) (\d{4}) (.+)$/);
        if (!parts) return dateStr;

        const months = {
            Jan: '01', Feb: '02', Mar: '03', Apr: '04',
            May: '05', Jun: '06', Jul: '07', Aug: '08',
            Sep: '09', Oct: '10', Nov: '11', Dec: '12'
        };

        const month = months[parts[1]];
        const day = ('0' + parts[2]).slice(-2);
        const year = parts[3];

        return `${day}/${month}/${year}`;
    },
    
    changeItemsPerPage: function() {
        this.itemsPerPage = parseInt($('#itemsPerPage').val());
        this.currentPage = 1;
        this.updateTable();
    },
    
    filterData: function() {
        if (this.searchTerm === '' && Object.keys(StatusFilterModule.activeStatusFilters).length === 0) {
            this.filteredData = [...this.allData];
        } else {
            this.filteredData = this.allData.filter(row => {
                let matchesSearch = true;
                if (this.searchTerm !== '') {
                    matchesSearch = row.some(cell => $(cell).text().toLowerCase().includes(this.searchTerm));
                }
                
                let matchesStatusFilter = StatusFilterModule.filterRowData(row);
                
                return matchesSearch && matchesStatusFilter;
            });
        }
    },
    
    sortData: function() {
        if (SortModule.sortColumn !== null) {
            const column = SortModule.sortColumn;
            const direction = SortModule.sortDirection[column];
            
            this.filteredData.sort((a, b) => {
                const aValue = $(a[column]).text().trim();
                const bValue = $(b[column]).text().trim();
                
                const aNum = parseFloat(aValue);
                const bNum = parseFloat(bValue);
                
                let comparison;
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    comparison = aNum - bNum;
                } else {
                    comparison = aValue.localeCompare(bValue, 'pt-BR');
                }
                
                return direction === 'asc' ? comparison : -comparison;
            });
        }
    },
    
    updateTable: function() {
        this.filterData();
        this.sortData();
        
        const totalItems = this.filteredData.length;
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = Math.min(startIndex + this.itemsPerPage, totalItems);
        
        $('#dataTableAndre tbody').empty();
        
        for (let i = startIndex; i < endIndex; i++) {
            const row = $('<tr>');
            this.filteredData[i].forEach(cell => {
                row.append(cell.clone(true)); 
            });
            $('#dataTableAndre tbody').append(row);
        }
        
        $('#showingStart').text(totalItems > 0 ? startIndex + 1 : 0);
        $('#showingEnd').text(endIndex);
        $('#totalItems').text(totalItems);
        
        this.updatePaginationControls(totalPages);
    },
    
    updatePaginationControls: function(totalPages) {
        const pagination = $('#pagination');
        pagination.empty();
        
        const prevDisabled = this.currentPage <= 1 ? 'disabled' : '';
        pagination.append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}">Anterior</a>
            </li>
        `);
        
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, this.currentPage + 2);
        
        if (endPage - startPage < 4 && totalPages > 5) {
            if (startPage === 1) {
                endPage = Math.min(totalPages, 5);
            } else if (endPage === totalPages) {
                startPage = Math.max(1, totalPages - 4);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const active = i === this.currentPage ? 'active' : '';
            pagination.append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }
        
        const nextDisabled = this.currentPage >= totalPages ? 'disabled' : '';
        pagination.append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}">Próximo</a>
            </li>
        `);
        
        pagination.off('click').on('click', 'a.page-link', (e) => {
            e.preventDefault();
            const page = parseInt($(e.target).data('page'));
            if (page >= 1 && page <= totalPages) {
                this.currentPage = page;
                this.updateTable();
            }
        });
    }
};

const CheckboxModule = {
    init: function() {
        $(document).off('change.checkboxModule');
        $('#selectAll').off('change.checkboxModule');
        
        $('#selectAll').on('change.checkboxModule', this.toggleSelectAll.bind(this));
        $(document).on('change.checkboxModule click.checkboxModule', '.row-checkbox', this.handleRowCheckboxChange.bind(this));
        
        this.updateExportButton();
    },
    
    handleRowCheckboxChange: function(e) {
        this.updateSelectAllState();
        this.updateExportButton();
        e.stopPropagation();
    },
    
    toggleSelectAll: function() {
        const isChecked = $('#selectAll').is(':checked');
        $('.row-checkbox:visible').prop('checked', isChecked);
        this.updateExportButton();
    },
    
    updateSelectAllState: function() {
        const totalCheckboxes = $('.row-checkbox:visible').length;
        const checkedCheckboxes = $('.row-checkbox:visible:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#selectAll').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAll').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAll').prop('indeterminate', true).prop('checked', false);
        }
    },
    
    updateExportButton: function() {
        const checkedCount = $('.row-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        const isDisabled = checkedCount === 0;
        $('#exportTxtBtn').prop('disabled', isDisabled);
        
        let buttonText = 'Exportar TXT';
        if (FilterModule.currentFilter === 'cadastramento' || FilterModule.currentFilter === 'descadastramento') {
            buttonText = 'Converter para TXT';
        }
        
        const btnContent = $('#exportTxtBtn').html();
        if (btnContent) {
            const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
            $('#exportTxtBtn').html(newContent);
        }
    },
    
    clearSelections: function() {
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        this.updateExportButton();
    },
    
    getSelectedIds: function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        return selectedIds;
    }
};

// New module for handling historico checkboxes
const HistoricoCheckboxModule = {
    init: function() {
        $(document).off('change.historicoCheckbox');
        
        // Handle lote-level checkboxes
        $(document).on('change.historicoCheckbox', '.historico-lote-checkbox', this.handleLoteCheckboxChange.bind(this));
        
        // Handle detail-level checkboxes (select all within a lote)
        $(document).on('change.historicoCheckbox', '.select-all-details', this.handleSelectAllDetails.bind(this));
        
        // Handle individual detail checkboxes
        $(document).on('change.historicoCheckbox', '.historico-detail-checkbox', this.handleDetailCheckboxChange.bind(this));
        
        this.updateExportButton();
    },
    
    handleLoteCheckboxChange: function(e) {
        const checkbox = $(e.currentTarget);
        const chaveLote = checkbox.data('chave-lote');
        const isChecked = checkbox.is(':checked');
        
        // Toggle all detail checkboxes in this lote
        $(`.historico-detail-checkbox[data-chave-lote="${chaveLote}"]`).prop('checked', isChecked);
        $(`.select-all-details[data-chave-lote="${chaveLote}"]`).prop('checked', isChecked);
        
        this.updateExportButton();
    },
    
    handleSelectAllDetails: function(e) {
        const checkbox = $(e.currentTarget);
        const chaveLote = checkbox.data('chave-lote');
        const isChecked = checkbox.is(':checked');
        
        // Toggle all detail checkboxes in this lote
        $(`.historico-detail-checkbox[data-chave-lote="${chaveLote}"]`).prop('checked', isChecked);
        
        // Update the lote checkbox
        $(`.historico-lote-checkbox[data-chave-lote="${chaveLote}"]`).prop('checked', isChecked);
        
        this.updateExportButton();
    },
    
    handleDetailCheckboxChange: function(e) {
        const checkbox = $(e.currentTarget);
        const chaveLote = checkbox.data('chave-lote');
        
        const totalDetails = $(`.historico-detail-checkbox[data-chave-lote="${chaveLote}"]`).length;
        const checkedDetails = $(`.historico-detail-checkbox[data-chave-lote="${chaveLote}"]:checked`).length;
        
        // Update select all checkbox for this lote
        const selectAllCheckbox = $(`.select-all-details[data-chave-lote="${chaveLote}"]`);
        if (checkedDetails === 0) {
            selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
        } else if (checkedDetails === totalDetails) {
            selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
        } else {
            selectAllCheckbox.prop('indeterminate', true).prop('checked', false);
        }
        
        // Update lote checkbox
        const loteCheckbox = $(`.historico-lote-checkbox[data-chave-lote="${chaveLote}"]`);
        if (checkedDetails === 0) {
            loteCheckbox.prop('checked', false);
        } else if (checkedDetails === totalDetails) {
            loteCheckbox.prop('checked', true);
        } else {
            loteCheckbox.prop('checked', false);
        }
        
        this.updateExportButton();
    },
    
    updateExportButton: function() {
        // For historico, we count individual detail records, not lotes
        const checkedCount = $('.historico-detail-checkbox:checked').length + $('.historico-lote-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        const isDisabled = checkedCount === 0;
        $('#exportTxtBtn').prop('disabled', isDisabled);
    },
    
    getSelectedIds: function() {
        const selectedIds = [];
        
        // Get selected lote IDs (will export entire lotes)
        $('.historico-lote-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        // Get individual detail IDs (for partial lote exports)
        $('.historico-detail-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        return selectedIds;
    }
};

const HistoricoModule = {
    init: function() {
        $(document).on('click', '.load-details', this.loadDetails.bind(this));
        $(document).on('shown.bs.collapse', '.panel-collapse', this.onAccordionExpand.bind(this));
    },
    
    loadDetails: function(e) {
        e.preventDefault();
        const button = $(e.currentTarget);
        const chaveLote = button.data('chave-lote');
        const tbody = button.closest('tbody');
        
        // Prevent multiple clicks
        if (button.prop('disabled')) {
            return;
        }
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
        
        // Store reference to this module for use in callbacks
        const self = this;
        
        $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        let detailsHtml = '';
                        let recordCount = 0;
                        
                        $xml.find('detailData row').each(function() {
                            const row = {};
                            $(this).children().each(function() {
                                row[this.tagName] = $(this).text();
                            });
                            
                            recordCount++;
                            const detailId = `${chaveLote}_${recordCount}`;
                            
                            detailsHtml += `
                                <tr>
                                    <td>
                                        <label>
                                            <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                                                   value="${detailId}" data-chave-lote="${chaveLote}">
                                            <span class="text"></span>
                                        </label>
                                    </td>
                                    <td>${row.CHAVE_LOJA || ''}</td>
                                    <td>${row.NOME_LOJA || ''}</td>
                                    <td>${row.COD_EMPRESA || ''}</td>
                                    <td>${row.COD_LOJA || ''}</td>
                                    <td>${row.TIPO_CORRESPONDENTE || ''}</td>
                                    <td>${row.DEP_DINHEIRO || ''}</td>
                                    <td>${row.DEP_CHEQUE || ''}</td>
                                    <td>${row.REC_RETIRADA || ''}</td>
                                    <td>${row.SAQUE_CHEQUE || ''}</td>
                                    <td>${row.SEGUNDA_VIA_CARTAO || ''}</td>
                                    <td>${row.HOLERITE_INSS || ''}</td>
                                    <td>${row.CONS_INSS || ''}</td>
                                    <td>${row.PROVA_DE_VIDA || ''}</td>
                                    <td>${row.DATA_CONTRATO || ''}</td>
                                    <td>${row.TIPO_CONTRATO || ''}</td>
                                    <td>${self.formatDate(row.DATA_LOG)}</td>
                                    <td><span class="badge badge-info">${row.FILTRO || ''}</span></td>
                                </tr>
                            `;
                        });
                        
                        if (recordCount > 0) {
                            tbody.html(detailsHtml);
                        } else {
                            tbody.html('<tr><td colspan="18" class="text-center text-muted">Nenhum detalhe encontrado</td></tr>');
                        }
                        
                        // Add summary row
                        tbody.append(`
                            <tr class="info">
                                <td colspan="18" class="text-center">
                                    <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
                                </td>
                            </tr>
                        `);
                        
                        // Initialize checkbox functionality for the newly loaded details
                        HistoricoCheckboxModule.init();
                        
                    } else {
                        tbody.html('<tr><td colspan="18" class="text-center text-danger">Erro ao carregar detalhes</td></tr>');
                    }
                    
                } catch (e) {
                    console.error('Error loading historico details: ', e);
                    tbody.html('<tr><td colspan="18" class="text-center text-danger">Erro ao processar dados</td></tr>');
                }
            })
            .fail(() => {
                tbody.html('<tr><td colspan="18" class="text-center text-danger">Erro na requisição</td></tr>');
            })
            .always(() => {
                button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
            });
    },
    
    onAccordionExpand: function(e) {
        const panel = $(e.target);
        const tbody = panel.find('.historico-details');
        const loadButton = tbody.find('.load-details');
        
        // Auto-load details when accordion is expanded for the first time
        if (loadButton.length > 0 && !loadButton.prop('disabled')) {
            loadButton.click();
        }
    },
    
    formatDate: function(dateString) {
        if (!dateString) return '—';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
    }
};

function exportSelectedTXT() {
    $('.validation-alert').remove();
    
    const currentFilter = getCurrentFilter();
    
    if (currentFilter === 'historico') {
        alert('Exportação TXT não disponível para histórico. Use Exportar CSV.');
        return;
    }
    
    const selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    const ids = [];
    selected.forEach(cb => {
        const cleanId = cb.value.toString().replace(/\s+/g, '').replace(/[^\w]/g, '').trim();
        if (cleanId && cleanId.length > 0) {
            ids.push(cleanId);
        }
    });
    
    if (ids.length === 0) {
        alert('Nenhum ID válido encontrado nos checkboxes selecionados');
        return;
    }
    
    const filter = getCurrentFilter();
    exportTXTData(ids.join(','), filter);
}

function exportTXTData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportTXT&filter=${filter}&ids=${encodeURIComponent(selectedIds)}`;
    
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

                const debugLogs = xmlDoc.getElementsByTagName('debugLogs')[0];
                if (debugLogs) {
                    console.log('=== DEBUG LOGS FROM PHP ===');
                    const logs = debugLogs.getElementsByTagName('log');
                    for (let i = 0; i < logs.length; i++) {
                        console.log(logs[i].textContent);
                    }
                    console.log('=== END DEBUG LOGS ===');
                }
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    const validationError = xmlDoc.getElementsByTagName('validation_error')[0];
                    if (validationError && validationError.textContent === 'true') {
                        const invalidRecords = Array.from(xmlDoc.getElementsByTagName('record')).map(record => ({
                            cod_empresa: record.getElementsByTagName('cod_empresa')[0]?.textContent || '',
                            error: record.getElementsByTagName('error_msg')[0]?.textContent || record.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido'
                        }));
                        
                        showValidationAlert(invalidRecords);
                        return;
                    }
                    
                    const errorMsg = xmlDoc.getElementsByTagName('e')[0]?.textContent || 'Erro desconhecido';
                    alert('Erro do servidor: ' + errorMsg);
                    return;
                }
                
                const txtData = extractTXTFromXML(xmlDoc);
                
                if (txtData.length === 0) {
                    alert('Erro: Conteúdo TXT vazio');
                    return;
                }
                
                const filename = `dashboard_selected_${filter}_${getCurrentTimestamp()}.txt`;
                downloadTXTFile(txtData, filename);

                // REMOVED: Success message about entering historico table
                // Just show a simple success message
                showSuccessMessage(`Arquivo TXT exportado com sucesso!`);
                
            } catch (e) {
                console.error('Processing error:', e);
                alert('Erro ao processar resposta: ' + e.message);
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição: ' + error.message);
        });
}

function extractXMLFromMixedResponse(responseText) {
    const startTag = '<response>';
    const endTag = '</response>';
    
    const startIndex = responseText.indexOf(startTag);
    if (startIndex === -1) return null;
    
    const endIndex = responseText.indexOf(endTag, startIndex);
    if (endIndex === -1) return null;
    
    return responseText.substring(startIndex, endIndex + endTag.length);
}

function extractTXTFromXML(xmlDoc) {
    let txtContent = '';
    const rows = xmlDoc.getElementsByTagName('row');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        
        const empresa = getXMLNodeValue(row, 'cod_empresa');
        const codigoLoja = getXMLNodeValue(row, 'cod_loja');
        
        const txtLine = formatToTXTLine(empresa, codigoLoja);
        
        if (txtLine && txtLine.length === 101) {
            txtContent += txtLine + '\r\n';
        }
    }
    
    return txtContent;
}

function formatToTXTLine(empresa, codigoLoja, codTransacao = "", meioPagamento = "", valorMinimo = "", valorMaximo = "", situacaoMeioPagamento = "", valorTotalMaxDiario = "", tipoManutencao = "", quantidadeTotalMaxDiaria = "") {

const empresaTXT = padLeft(cleanNumeric(empresa), 10, '0'); 
const codigoLojaTXT = padLeft(cleanNumeric(codigoLoja), 5, '0'); 
const fixo = padRight("", 10, ' '); 
const codTransacaoTXT = padLeft(cleanNumeric(codTransacao), 5, '0'); 
const meioPagamTXT = padLeft(cleanNumeric(meioPagamento), 2, '0'); 
const valorMinTXT = padLeft(cleanNumeric(valorMinimo),17,'0'); 
const valorMaxTXT = padLeft(cleanNumeric(valorMaximo),17,'0'); 
const sitMeioPTXT = padLeft(cleanNumeric(situacaoMeioPagamento), 1, '0'); 
const valorTotalMaxTXT = padLeft(cleanNumeric(valorTotalMaxDiario),18,'0'); 
const tipoManutTXT = padLeft(cleanNumeric(tipoManutencao), 1, '0'); 
const quantTotalMaxTXT = padLeft(cleanNumeric(quantidadeTotalMaxDiaria),15,'0'); 

const linha = empresaTXT + codigoLojaTXT + fixo + codTransacaoTXT + meioPagamTXT + 
valorMinTXT + valorMaxTXT + sitMeioPTXT + valorTotalMaxTXT + 
tipoManutTXT + quantTotalMaxTXT;

if (linha.length > 101) {
return linha.substring(0, 101);
} else if (linha.length < 101) {
return padRight(linha, 101, ' ');
}

return linha;
}

const MEIO_PAGAMENTO = {
DINHEIRO: "01",
CHEQUE: "02", 
CARTAO: "03",
NAO_SE_APLICA: "04"
};

const SITUACAO_MEIO_PAGAMENTO = {
ATIVO: "1",
INATIVO: "2"
};

const TIPO_MANUTENCAO = {
INCLUSAO: "1",
ALTERACAO: "2", 
EXCLUSAO: "3"
};

function exportAllCSV() {
    const filter = getCurrentFilter();
    exportCSVData('', filter);
}

function exportCSVData(selectedIds, filter) {
    showLoading();
    
    const url = `wxkd.php?action=exportCSV&filter=${filter}${selectedIds ? '&ids=' + selectedIds : ''}`;
    
    fetch(url)
        .then(response => response.text())
        .then(responseText => {
            hideLoading();
            
            try {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(responseText, 'text/xml');
                
                const success = xmlDoc.getElementsByTagName('success')[0];
                if (!success || success.textContent !== 'true') {
                    alert('Erro ao buscar dados para exportação');
                    return;
                }
                
                const csvData = extractCSVFromXML(xmlDoc);
                const filename = `dashboard_${filter}_${getCurrentTimestamp()}.csv`;
                downloadCSVFile(csvData, filename);
                
            } catch (e) {
                console.error('Erro ao processar XML:', e);
                alert('Erro ao processar dados');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Fetch error:', error);
            alert('Erro na requisição');
        });
}

function extractCSVFromXML(xmlDoc) {
    const currentFilter = getCurrentFilter();
    
    if (currentFilter === 'historico') {
        // Different CSV structure for historico
        let csvContent = 'CHAVE_LOTE;DATA_LOG;CHAVE_LOJA;NOME_LOJA;COD_EMPRESA;COD_LOJA;TIPO_CORRESPONDENTE;DEP_DINHEIRO;DEP_CHEQUE;REC_RETIRADA;SAQUE_CHEQUE;SEGUNDA_VIA_CARTAO;HOLERITE_INSS;CONS_INSS;PROVA_DE_VIDA;DATA_CONTRATO;TIPO_CONTRATO;FILTRO\r\n';
        
        const rows = xmlDoc.getElementsByTagName('row');
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            
            const fields = [
                'chave_lote', 'data_log', 'chave_loja', 'nome_loja', 'cod_empresa', 'cod_loja', 
                'tipo_correspondente', 'dep_dinheiro', 'dep_cheque', 'rec_retirada', 'saque_cheque',
                'segunda_via_cartao', 'holerite_inss', 'cons_inss', 'prova_de_vida',
                'data_contrato', 'tipo_contrato', 'filtro'
            ];
            
            const rowData = fields.map(field => escapeCSVField(getXMLNodeValue(row, field)));
            csvContent += rowData.join(';') + '\r\n';
        }
        
        return csvContent;
    } else {
        // Regular CSV structure for cadastramento/descadastramento
        let csvContent = 'CHAVE_LOJA;NOME_LOJA;COD_EMPRESA;COD_LOJA;QUANT_LOJAS;TIPO_CORRESPONDENTE;DATA_CONCLUSAO;DATA_SOLICITACAO;DEP_DINHEIRO;DEP_CHEQUE;REC_RETIRADA;SAQUE_CHEQUE;2VIA_CARTAO;HOLERITE_INSS;CONS_INSS;PROVA_DE_VIDA;DATA_CONTRATO;TIPO_CONTRATO\r\n';
        
        const rows = xmlDoc.getElementsByTagName('row');
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            
            const fields = [
                'chave_loja', 'nome_loja', 'cod_empresa', 'cod_loja', 'quant_lojas',
                'tipo_correspondente', 'date', 'data_solicitacao', 'dep_dinheiro',
                'dep_cheque', 'rec_retirada', 'saque_cheque', 'segunda_via_cartao',
                'holerite_inss', 'cons_inss', 'prova_de_vida' , 'data_contrato', 'tipo_contrato'
            ];
            
            const rowData = fields.map(field => escapeCSVField(getXMLNodeValue(row, field)));
            csvContent += rowData.join(';') + '\r\n';
        }
        
        return csvContent;
    }
}

function exportSelectedAccess() {
    const currentFilter = getCurrentFilter();
    
    if (currentFilter === 'historico') {
        alert('Exportação Access não disponível para histórico. Use Exportar CSV.');
        return;
    }
    
    const selected = document.querySelectorAll('.row-checkbox:checked');
    if (selected.length === 0) {
        alert('Selecione pelo menos um registro');
        return;
    }
    
    const ids = [];
    selected.forEach(cb => {
        const cleanId = cb.value.toString().replace(/\s+/g, '').replace(/[^\w]/g, '').trim();
        if (cleanId && cleanId.length > 0) {
            ids.push(cleanId);
        }
    });
    
    if (ids.length === 0) {
        alert('Nenhum ID válido encontrado nos checkboxes selecionados');
        return;
    }
    
    const filter = getCurrentFilter();
    exportAccessData(ids.join(','), filter);
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
        let csvContent2 = 'COD_EMPRESA\r\n';
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

function getXMLNodeValue(parentNode, tagName) {
    const node = parentNode.getElementsByTagName(tagName)[0];
    return node ? (node.textContent || node.text || '') : '';
}

function cleanNumeric(value) {
    return String(value).replace(/[^0-9]/g, '') || '0';
}

function cleanText(value) {
    return String(value).replace(/[^A-Za-z0-9\s]/g, '').toUpperCase().trim();
}

function formatDate(dateValue, length) {
    if (!dateValue) return padLeft('', length, '0');
    
    const yearMatch = dateValue.match(/(\d{4})/);
    if (yearMatch) {
        return yearMatch[1] + '-01-01';
    }
    
    return padLeft('', length, '0');
}

function padLeft(str, length, char) {
    str = String(str);
    while (str.length < length) {
        str = char + str;
    }
    return str.length > length ? str.slice(-length) : str;
}

function padRight(str, length, char) {
    str = String(str);
    while (str.length < length) {
        str = str + char;
    }
    return str.substring(0, length);
}

function escapeCSVField(field) {
    field = String(field).replace(/"/g, '""');
    if (field.indexOf(';') !== -1 || field.indexOf('"') !== -1 || field.indexOf('\r') !== -1 || field.indexOf('\n') !== -1) {
        field = '"' + field + '"';
    }
    return field;
}

function downloadTXTFile(txtContent, filename) {
    const txtWithBOM = '\uFEFF' + txtContent;
    const blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
    downloadFile(blob, filename);
}

function downloadCSVFile(csvContent, filename) {
    const csvWithBOM = '\uFEFF' + csvContent;
    const blob = new Blob([csvWithBOM], { type: 'text/csv;charset=utf-8;' });
    downloadFile(blob, filename);
}

function downloadAccessCSVFile(csvContent, filename) {
    downloadCSVFile(csvContent, filename);
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

function getCurrentFilter() {
    const activeCard = document.querySelector('.card.active');
    if (activeCard) {
        if (activeCard.id === 'card-cadastramento') return 'cadastramento';
        if (activeCard.id === 'card-descadastramento') return 'descadastramento';
        if (activeCard.id === 'card-historico') return 'historico';
    }
    return 'all';
}

function getCurrentTimestamp() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    return `${year}-${month}-${day}_${hours}-${minutes}-${seconds}`;
}

function clearFilter() {
    FilterModule.clearFilter();
}

function toggleStatus(button) {
    const span = button.querySelector('.status-indicator');
    const isGreen = span.style.backgroundColor === 'rgb(33, 136, 56)' || span.style.backgroundColor === '#218838';
    span.style.backgroundColor = isGreen ? 'gray' : '#218838';
}

function showValidationAlert(invalidRecords) {
    $('.validation-alert').remove();
    
    let detailsHtml = '';
    for (let i = 0; i < invalidRecords.length; i++) {
        const record = invalidRecords[i];
        detailsHtml += `<div style="margin: 5px 0; padding: 5px; background-color: rgba(255,255,255,0.1); border-radius: 3px;">
            <strong>Empresa:</strong> ${record.cod_empresa}
            <br><strong>Motivo:</strong> ${record.error}
        </div>`;
    }
    
    const alertHtml = `<div class="alert alert-warning validation-alert" style="margin: 10px 0; max-height: 400px; overflow-y: auto;">
        <button class="close" onclick="$(this).parent().remove()" style="color:rgb(54, 150, 198) !important; opacity:0.3; position:relative; top:5px;">
            <i class="fa fa-times"></i>
        </button>
        <span>
            <i class="fa-fw fa fa-warning" style="font-size:15px !important; position:relative; top:2px;"></i>
            <strong>Registros não podem ser exportados como TXT:</strong><br><br>
            ${detailsHtml}
            <br><i><strong>Clique no botão "Exportar Access" para processar estes registros.</strong></i>
        </span>
    </div>`;
    
    if ($('#statusFilterIndicator').is(':visible')) {
        $('#statusFilterIndicator').after(alertHtml);
    } else if ($('#filterIndicator').is(':visible')) {
        $('#filterIndicator').after(alertHtml);
    } else {
        $('.row.mb-4').after(alertHtml);
    }
    
    $('html, body').animate({
        scrollTop: $('.validation-alert').offset().top - 20
    }, 500);
    
    setTimeout(() => {
        $('.validation-alert').fadeOut();
    }, 10000);
}

function showSuccessMessage(message) {
    const alertHtml = `
        <div class="alert alert-success success-alert" style="
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            z-index: 9999; 
            min-width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 5px;
        ">
            <button class="close" onclick="$(this).parent().remove()" style="
                color: #3c763d !important; 
                opacity: 0.7; 
                position: absolute; 
                top: 10px; 
                right: 15px;
            ">
                <i class="fa fa-times"></i>
            </button>
            <div style="padding: 20px 40px 20px 20px;">
                <i class="fa fa-check-circle" style="color: #3c763d; font-size: 20px; margin-right: 10px;"></i>
                <strong>Sucesso!</strong>
                <pre style="background: none; border: none; color: #3c763d; margin-top: 10px; white-space: pre-wrap;">${message}</pre>
            </div>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        $('.success-alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}

window.addEventListener('popstate', (event) => {
    const filter = event.state?.filter || 'all';
    FilterModule.currentFilter = filter;
    FilterModule.updateFilterUI();
    FilterModule.loadTableData(filter);
    
    if (filter === 'all') {
        $('.card-filter').removeClass('active');
    } else {
        FilterModule.setActiveCard(filter);
    }
});

StatusFilterModule.reapplyAfterDataLoad = function() {
    if (Object.keys(this.activeStatusFilters).length > 0) {
        setTimeout(() => {
            this.applyStatusFilters();
        }, 300);
    }
};

$(document).ready(() => {
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init();
    HistoricoModule.init();
    HistoricoCheckboxModule.init();
});