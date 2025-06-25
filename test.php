// assets/Wxkd_script.js
$(document).ready(function() {
    // Inicializar módulos
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init();
    FilterModule.init();
    ExportModule.init();
});

// Módulo de Filtros
const FilterModule = {
    currentFilter: 'all',
    
    init: function() {
        // Definir filtro inicial
        this.currentFilter = window.currentFilter || 'all';
        this.updateFilterUI();
        
        // Event listeners para cards
        $('.card-filter').on('click', this.handleCardClick.bind(this));
        
        // Atualizar cards conforme filtro inicial
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
            // Se clicar no mesmo filtro, limpar
            this.clearFilter();
            return;
        }
        
        this.currentFilter = filter;
        this.setActiveCard(filter);
        this.updateFilterUI();
        this.loadTableData(filter);
        
        // Atualizar URL sem recarregar página
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('filter', filter);
        window.history.pushState({filter: filter}, '', newUrl);
    },
    
    clearFilter: function() {
        this.currentFilter = 'all';
        $('.card-filter').removeClass('active');
        $('#filterIndicator').hide();
        this.loadTableData('all');
        
        // Atualizar URL
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
            $('#filterIndicator').show();
        }
    },
    
    loadTableData: function(filter) {
        // Mostrar loading
        $('#dataTable tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        
        // Fazer requisição AJAX para XML ao invés de JSON
        $.get('?action=ajaxGetTableData&filter=' + filter)
            .done(function(xmlData) {
                try {
                    // Parse XML response
                    var $xml = $(xmlData);
                    var success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        // Extrair dados dos cards do XML
                        var cardData = {
                            cadastramento: $xml.find('cardData cadastramento').text(),
                            descadastramento: $xml.find('cardData descadastramento').text(),
                            historico: $xml.find('cardData historico').text()
                        };
                        
                        // Extrair dados da tabela do XML
                        var tableData = [];
                        $xml.find('tableData row').each(function() {
                            var row = {};
                            $(this).children().each(function() {
                                row[this.tagName] = $(this).text();
                            });
                            tableData.push(row);
                        });
                        
                        // Atualizar dados dos cards
                        FilterModule.updateCardCounts(cardData);
                        
                        // Recriar dados da tabela
                        PaginationModule.replaceTableData(tableData);
                        PaginationModule.currentPage = 1;
                        PaginationModule.updateTable();
                        
                        // Limpar seleções
                        CheckboxModule.clearSelections();
                    }
                } catch (e) {
                    $('#dataTable tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
                }
            })
            .fail(function() {
                $('#dataTable tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
            });
    },
    
    updateCardCounts: function(cardData) {
        $('#card-cadastramento .card-text').text(cardData.cadastramento);
        $('#card-descadastramento .card-text').text(cardData.descadastramento);
        $('#card-historico .card-text').text(cardData.historico);
    }
};

// Módulo de Pesquisa
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

// Módulo de Ordenação
const SortModule = {
    sortDirection: {},
    sortColumn: null,
    
    init: function() {
        $('.sortable').on('click', this.sortTable.bind(this));
    },
    
    sortTable: function(e) {
        const column = $(e.currentTarget).data('column');
        this.sortColumn = column;
        
        // Definir direção da ordenação
        this.sortDirection[column] = this.sortDirection[column] === 'asc' ? 'desc' : 'asc';
        const direction = this.sortDirection[column];
        
        // Atualizar indicadores visuais
        $('.sortable').removeClass('sort-asc sort-desc');
        $(e.currentTarget).addClass('sort-' + direction);
        
        // Reordenar dados
        PaginationModule.sortData();
        PaginationModule.currentPage = 1;
        PaginationModule.updateTable();
    }
};

// Módulo de Paginação
const PaginationModule = {
    currentPage: 1,
    itemsPerPage: 15,
    searchTerm: '',
    allData: [],
    filteredData: [],
    
    init: function() {
        // Capturar dados da tabela
        this.captureTableData();
        
        // Event listener para mudança de itens por página
        $('#itemsPerPage').on('change', this.changeItemsPerPage.bind(this));
        
        // Inicializar paginação
        this.updateTable();
    },
    
    captureTableData: function() {
        const rows = $('#dataTable tbody tr');
        this.allData = [];
        
        const self = this;
        rows.each(function() {
            const rowData = [];
            $(this).find('td').each(function() {
                rowData.push($(this).clone());
            });
            self.allData.push(rowData);
        });
    },
    
    replaceTableData: function(newData) {
        // Converter novos dados para formato da tabela
        this.allData = [];
        
        const self = this;
        newData.forEach(function(row) {
            const rowData = [];
            
            // Checkbox
            const checkboxCell = $('<td class="checkbox-column">').html(
                `<input type="checkbox" class="form-check-input row-checkbox" data-row-id="${row.id}">`
            );
            rowData.push(checkboxCell);
            
            // Demais colunas
            rowData.push($('<td>').text(row.id));
            rowData.push($('<td>').text(row.nome));
            rowData.push($('<td>').text(row.email));
            rowData.push($('<td>').text(row.telefone));
            rowData.push($('<td>').text(row.cidade));
            rowData.push($('<td>').text(row.estado));
            rowData.push($('<td>').text(row.data_cadastro));
            
            // Status com badge
            const statusClass = row.status === 'Ativo' ? 'bg-success' : 
                              row.status === 'Processado' ? 'bg-info' : 'bg-secondary';
            const statusCell = $('<td>').html(`<span class="badge ${statusClass}">${row.status}</span>`);
            rowData.push(statusCell);
            
            rowData.push($('<td>').text(row.tipo));
            rowData.push($('<td>').text(row.categoria));
            rowData.push($('<td>').text(row.observacoes));
            
            self.allData.push(rowData);
        });
    },
    
    changeItemsPerPage: function() {
        this.itemsPerPage = parseInt($('#itemsPerPage').val());
        this.currentPage = 1;
        this.updateTable();
    },
    
    filterData: function() {
        if (this.searchTerm === '') {
            this.filteredData = [...this.allData];
        } else {
            const self = this;
            this.filteredData = this.allData.filter(function(row) {
                return row.some(function(cell) {
                    return $(cell).text().toLowerCase().includes(self.searchTerm);
                });
            });
        }
    },
    
    sortData: function() {
        if (SortModule.sortColumn !== null) {
            const column = SortModule.sortColumn;
            const direction = SortModule.sortDirection[column];
            
            this.filteredData.sort(function(a, b) {
                const aValue = $(a[column]).text().trim();
                const bValue = $(b[column]).text().trim();
                
                // Tentar converter para número se possível
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
        // Filtrar dados
        this.filterData();
        
        // Ordenar dados se necessário
        this.sortData();
        
        // Calcular paginação
        const totalItems = this.filteredData.length;
        const totalPages = Math.ceil(totalItems / this.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = Math.min(startIndex + this.itemsPerPage, totalItems);
        
        // Limpar tabela
        $('#dataTable tbody').empty();
        
        // Adicionar linhas da página atual
        for (let i = startIndex; i < endIndex; i++) {
            const row = $('<tr>');
            this.filteredData[i].forEach(function(cell) {
                row.append(cell);
            });
            $('#dataTable tbody').append(row);
        }
        
        // Atualizar informações de paginação
        $('#showingStart').text(totalItems > 0 ? startIndex + 1 : 0);
        $('#showingEnd').text(endIndex);
        $('#totalItems').text(totalItems);
        
        // Atualizar controles de paginação
        this.updatePaginationControls(totalPages);
        
        // Atualizar estado dos checkboxes após atualizar tabela
        setTimeout(function() {
            CheckboxModule.updateSelectAllState();
            CheckboxModule.updateExportButton();
        }, 100);
    },
    
    updatePaginationControls: function(totalPages) {
        const pagination = $('#pagination');
        pagination.empty();
        
        const self = this;
        
        // Botão anterior
        const prevDisabled = this.currentPage <= 1 ? 'disabled' : '';
        pagination.append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}">Anterior</a>
            </li>
        `);
        
        // Páginas numeradas
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
        
        // Botão próximo
        const nextDisabled = this.currentPage >= totalPages ? 'disabled' : '';
        pagination.append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}">Próximo</a>
            </li>
        `);
        
        // Event listeners para paginação
        pagination.off('click').on('click', 'a.page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page >= 1 && page <= totalPages) {
                self.currentPage = page;
                self.updateTable();
            }
        });
    }
};

// Módulo de Checkbox
const CheckboxModule = {
    init: function() {
        // Checkbox "Selecionar Todos"
        $('#selectAll').on('change', this.toggleSelectAll.bind(this));
        
        // Checkboxes individuais
        $(document).on('change', '.row-checkbox', this.updateSelectAllState.bind(this));
        $(document).on('change', '.row-checkbox', this.updateExportButton.bind(this));
        
        // Atualizar estado inicial
        this.updateExportButton();
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
        
        // Atualizar texto do botão baseado no filtro
        let buttonText = 'Exportar TXT';
        if (FilterModule.currentFilter === 'cadastramento' || FilterModule.currentFilter === 'descadastramento') {
            buttonText = 'Converter para TXT';
        }
        
        // Atualizar o texto do botão
        const btnContent = $('#exportTxtBtn').html();
        const newContent = btnContent.replace(/^[^(]+/, buttonText + ' ');
        $('#exportTxtBtn').html(newContent);
    },
    
    clearSelections: function() {
        $('.row-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        this.updateExportButton();
    },
    
    getSelectedIds: function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).data('row-id'));
        });
        return selectedIds;
    }
};

// Módulo de Exportação
const ExportModule = {
    init: function() {
        // Módulo pronto para uso
    }
};

// Função global de exportação
function exportData(format) {
    if (format === 'xml') {
        const currentFilter = FilterModule.currentFilter;
        window.location.href = `?action=exportXML&filter=${currentFilter}`;
    } else if (format === 'txt') {
        const selectedIds = CheckboxModule.getSelectedIds();
        
        if (selectedIds.length === 0) {
            alert('Selecione pelo menos uma linha para exportar.');
            return;
        }
        
        // Confirmar ação se for cadastramento ou descadastramento
        const currentFilter = FilterModule.currentFilter;
        if (currentFilter === 'cadastramento' || currentFilter === 'descadastramento') {
            const filterName = currentFilter === 'cadastramento' ? 'Cadastramento' : 'Descadastramento';
            const confirmMessage = `Esta ação irá:\n\n` +
                `1. Converter ${selectedIds.length} registro(s) para TXT\n` +
                `2. Mover os registros para o Histórico\n` +
                `3. Remover os registros da lista de ${filterName}\n\n` +
                `Deseja continuar?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
        }
        
        // Criar formulário para enviar IDs selecionados
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?action=exportTXT';
        
        // Adicionar filtro atual
        const filterInput = document.createElement('input');
        filterInput.type = 'hidden';
        filterInput.name = 'filter';
        filterInput.value = currentFilter;
        form.appendChild(filterInput);
        
        // Adicionar IDs selecionados
        selectedIds.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selectedIds[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Se foi conversão (não apenas exportação), recarregar dados após um delay
        if (currentFilter === 'cadastramento' || currentFilter === 'descadastramento') {
            setTimeout(function() {
                FilterModule.loadTableData(currentFilter);
            }, 1000);
        }
    }
}

// Função global para limpar filtro
function clearFilter() {
    FilterModule.clearFilter();
}

// Gerenciar botão voltar do navegador
window.addEventListener('popstate', function(event) {
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