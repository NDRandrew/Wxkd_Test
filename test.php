<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AnaliseEncerramentoController {
    private $model;
    private $dados;
    
    public function __construct() {
        $this->model = new Analise();
        $this->loadData();
    }
    
    private function loadData() {
        // Base WHERE clause
        $where = "AND A.COD_TIPO_SERVICO=1";
        
        // Add search filter if exists
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $where .= " AND (
                CAST(A.COD_SOLICITACAO AS VARCHAR) LIKE '%$search%' OR
                CAST(A.COD_AG AS VARCHAR) LIKE '%$search%' OR
                CAST(A.CHAVE_LOJA AS VARCHAR) LIKE '%$search%' OR
                F.NOME_LOJA LIKE '%$search%' OR
                G.NR_PACB LIKE '%$search%' OR
                F.MOTIVO_BLOQUEIO LIKE '%$search%' OR
                F.DESC_MOTIVO_ENCERRAMENTO LIKE '%$search%'
            )";
        }
        
        // Add bloqueio filter
        if (isset($_GET['bloqueio']) && $_GET['bloqueio'] !== '') {
            if ($_GET['bloqueio'] === 'bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NOT NULL";
            } else if ($_GET['bloqueio'] === 'nao_bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NULL";
            }
        }
        
        // Add orgao pagador filter
        if (isset($_GET['orgao_pagador']) && !empty($_GET['orgao_pagador'])) {
            $orgao = $_GET['orgao_pagador'];
            $where .= " AND G.ORGAO_PAGADOR LIKE '%$orgao%'";
        }
        
        // Add data range filter
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '$_GET[data_inicio]'";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '$_GET[data_fim]'";
        }
        
        $this->dados = $this->model->solicitacoes($where);
    }
    
    public function getDados() {
        return $this->dados;
    }
    
    public function getTotalRecords() {
        return is_array($this->dados) ? count($this->dados) : 0;
    }
}

// Initialize controller
$controller = new AnaliseEncerramentoController();
$dados = $controller->getDados();
$totalRecords = $controller->getTotalRecords();
?>

---------

<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
?>

<head>
    <style>
        :root {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        :root[data-theme="light"] {
            --thead-encerramento: #ac193d;
            --thead-text-encerramento: #ffffffff
        }

        :root[data-theme="dark"] {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        .thead-encerramento {
            background: var(--thead-encerramento) !important;
            font-size: .75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            line-height: 1rem;
            color: var(--thead-text-encerramento) !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Filter and Search Section -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Filtros e Busca</h3>
        </div>
        <div class="card-body">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" name="search" 
                                   placeholder="Buscar por Solicitação, Agência, Chave Loja, PACB, Motivo..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Buscar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Bloqueio Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Status Bloqueio</label>
                        <select class="form-select" name="bloqueio" id="bloqueioFilter">
                            <option value="">Todos</option>
                            <option value="bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                            <option value="nao_bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'nao_bloqueado') ? 'selected' : ''; ?>>Não Bloqueado</option>
                        </select>
                    </div>
                    
                    <!-- Orgao Pagador Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Órgão Pagador</label>
                        <input type="text" class="form-control" name="orgao_pagador" id="orgaoPagadorFilter" 
                               placeholder="Filtrar por órgão"
                               value="<?php echo isset($_GET['orgao_pagador']) ? htmlspecialchars($_GET['orgao_pagador']) : ''; ?>">
                    </div>
                    
                    <!-- Data Inicio -->
                    <div class="col-md-3">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" name="data_inicio" id="dataInicioFilter"
                               value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                    </div>
                    
                    <!-- Data Fim -->
                    <div class="col-md-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" name="data_fim" id="dataFimFilter"
                               value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearFilters">
                            Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Section -->
    <div class="fs-base border rounded my-5 justify-content-center overflow-auto position-relative" style="height: 50rem; width:100rem; right:100px;">
        <table class="table table-vcenter table-nowrap" id="dataTable">
            <thead class="sticky-top">
                <tr>
                    <th class="thead-encerramento" style="text-align: center;">Solicitação</th>  
                    <th class="thead-encerramento" style="text-align: center;">Agência/PACB</th>
                    <th class="thead-encerramento" style="text-align: center;">Chave Loja</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Recepção</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Retirada Equip.</th>
                    <th class="thead-encerramento" style="text-align: center;">Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Última Tran.</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Encerramento</th>
                    <th class="thead-encerramento" style="text-align: center;">Órgão Pagador</th>
                    <th class="thead-encerramento" style="text-align: center;">Cluster</th>
                    <th class="thead-encerramento" style="text-align: center;">PARM</th>
                    <th class="thead-encerramento" style="text-align: center;">TRAG</th>
                    <th class="thead-encerramento" style="text-align: center;">TRAG SEM TRAG</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Contábeis</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Negócio</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $length = is_array($dados) ? count($dados) : 0;
                if ($length > 0) {
                    for ($i = 0; $i < $length; $i++) { ?>
                        <tr>
                            <th><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span>'; ?></th>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span>'; ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                                    echo '<span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Data</span>';
                                }
                            ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                                    echo '<span class="text-green" style="display: block; text-align: center;">Bloqueado</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span>';
                                }
                            ?></td> 
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span>'; ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                                    echo '<span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span>';
                                }
                            ?></td> 
                            <td><?php 
                                if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                                    echo '<span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span>';
                                }
                            ?></td> 
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span>'; ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="16" class="text-center">Nenhum registro encontrado</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row g-2 justify-content-center justify-content-sm-between">
            <div class="col-auto d-flex align-items-center">
                <p class="m-0 text-secondary">Mostrando <strong><?php echo $totalRecords; ?></strong> registros</p>
            </div>
            <div class="col-auto">
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M15 6l-6 6l6 6"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link" href="#">1</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M9 6l6 6l-6 6"></path>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\encerramento\analise_encerramento\analise_encerramento.js"></script>

    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const rawTheme = (params.get("theme") || "").trim().toLowerCase();
            const allowed = new Set(["light", "dark"]);

            const storedTheme = localStorage.getItem("theme");
            const chosen = allowed.has(rawTheme)
                ? rawTheme
                : (allowed.has(storedTheme) ? storedTheme : "light");

            document.documentElement.setAttribute("data-theme", chosen);
            localStorage.setItem("theme", chosen);
        })();
    </script>
</body>


------

// analise_encerramento.js - Client-side functionality for filters and search

(function() {
    'use strict';

    // DOM Elements
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const searchInput = document.getElementById('searchInput');
    const bloqueioFilter = document.getElementById('bloqueioFilter');
    const orgaoPagadorFilter = document.getElementById('orgaoPagadorFilter');
    const dataInicioFilter = document.getElementById('dataInicioFilter');
    const dataFimFilter = document.getElementById('dataFimFilter');

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeEventListeners();
        highlightActiveFilters();
    });

    /**
     * Initialize all event listeners
     */
    function initializeEventListeners() {
        // Clear filters button
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearAllFilters);
        }

        // Form submission
        if (filterForm) {
            filterForm.addEventListener('submit', handleFormSubmit);
        }

        // Real-time search on Enter key
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterForm.submit();
                }
            });
        }

        // Filter change handlers
        const filterElements = [bloqueioFilter, orgaoPagadorFilter, dataInicioFilter, dataFimFilter];
        filterElements.forEach(element => {
            if (element) {
                element.addEventListener('change', function() {
                    // Auto-submit on filter change (optional - remove if not desired)
                    // filterForm.submit();
                });
            }
        });
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        // Remove empty parameters before submitting
        const inputs = filterForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (!input.value || input.value === '') {
                input.disabled = true;
            }
        });

        // Allow form to submit normally
        setTimeout(() => {
            inputs.forEach(input => {
                input.disabled = false;
            });
        }, 100);
    }

    /**
     * Clear all filters and reload page
     */
    function clearAllFilters() {
        // Clear all input values
        if (searchInput) searchInput.value = '';
        if (bloqueioFilter) bloqueioFilter.value = '';
        if (orgaoPagadorFilter) orgaoPagadorFilter.value = '';
        if (dataInicioFilter) dataInicioFilter.value = '';
        if (dataFimFilter) dataFimFilter.value = '';

        // Reload page without query parameters
        window.location.href = window.location.pathname;
    }

    /**
     * Highlight active filters visually
     */
    function highlightActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Highlight search input if active
        if (searchInput && urlParams.has('search')) {
            searchInput.style.borderColor = '#206bc4';
            searchInput.style.borderWidth = '2px';
        }

        // Highlight filter selects if active
        [bloqueioFilter, orgaoPagadorFilter, dataInicioFilter, dataFimFilter].forEach(filter => {
            if (filter && filter.value) {
                filter.style.borderColor = '#206bc4';
                filter.style.borderWidth = '2px';
            }
        });

        // Update clear button state
        if (clearFiltersBtn) {
            const hasActiveFilters = urlParams.has('search') || 
                                    urlParams.has('bloqueio') || 
                                    urlParams.has('orgao_pagador') || 
                                    urlParams.has('data_inicio') || 
                                    urlParams.has('data_fim');
            
            if (hasActiveFilters) {
                clearFiltersBtn.classList.add('btn-warning');
                clearFiltersBtn.classList.remove('btn-secondary');
            }
        }
    }

    /**
     * Export table to CSV (optional feature)
     */
    function exportTableToCSV() {
        const table = document.getElementById('dataTable');
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');

            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }

            csv.push(row.join(','));
        }

        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        const downloadLink = document.createElement('a');
        downloadLink.download = 'analise_encerramento_' + new Date().getTime() + '.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

    /**
     * Show loading indicator
     */
    function showLoading() {
        const loadingHtml = `
            <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        `;
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.innerHTML = loadingHtml;
        }
    }

    /**
     * Validate date range
     */
    function validateDateRange() {
        const dataInicio = dataInicioFilter ? dataInicioFilter.value : null;
        const dataFim = dataFimFilter ? dataFimFilter.value : null;

        if (dataInicio && dataFim) {
            const inicio = new Date(dataInicio);
            const fim = new Date(dataFim);

            if (inicio > fim) {
                alert('A data de início não pode ser maior que a data fim.');
                return false;
            }
        }
        return true;
    }

    // Add date validation to form
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            if (!validateDateRange()) {
                e.preventDefault();
            }
        });
    }

    // Expose functions globally if needed
    window.AnaliseEncerramentoJS = {
        exportTableToCSV: exportTableToCSV,
        clearAllFilters: clearAllFilters,
        validateDateRange: validateDateRange
    };

})();