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
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        [data-theme="dark"] .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
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
            <form id="filterForm" onsubmit="return false;">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" name="search" 
                                   placeholder="Buscar por Solicitação, Agência, Chave Loja, PACB, Motivo..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="button" id="searchBtn">
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
                        <button type="button" class="btn btn-primary" id="applyFiltersBtn">
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
    <div class="fs-base border rounded my-5 justify-content-center overflow-auto position-relative" style="height: 50rem; width:100rem; right:100px;" id="tableContainer">
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
        
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
            <tbody id="tableBody">
                <?php echo $controller->renderTableRows($dados); ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row g-2 justify-content-center justify-content-sm-between">
            <div class="col-auto d-flex align-items-center">
                <p class="m-0 text-secondary">Mostrando <strong id="totalRecords"><?php echo $totalRecords; ?></strong> registros</p>
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

    <script>
        // analise_encerramento.js - AJAX functionality
        (function() {
            'use strict';

            const filterForm = document.getElementById('filterForm');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const searchBtn = document.getElementById('searchBtn');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const searchInput = document.getElementById('searchInput');
            const bloqueioFilter = document.getElementById('bloqueioFilter');
            const orgaoPagadorFilter = document.getElementById('orgaoPagadorFilter');
            const dataInicioFilter = document.getElementById('dataInicioFilter');
            const dataFimFilter = document.getElementById('dataFimFilter');
            const tableBody = document.getElementById('tableBody');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const totalRecordsEl = document.getElementById('totalRecords');
            
            // AJAX endpoint URL - adjust if your file structure is different
            const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/ajax_encerramento.php';

            // Initialize immediately
            initializeEventListeners();
            highlightActiveFilters();

            function initializeEventListeners() {
                console.log('Initializing event listeners...');
                
                if (clearFiltersBtn) {
                    clearFiltersBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        clearAllFilters();
                    });
                }

                if (searchBtn) {
                    searchBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        handleFormSubmit();
                    });
                }
                
                if (applyFiltersBtn) {
                    applyFiltersBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        handleFormSubmit();
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            handleFormSubmit();
                        }
                    });
                }
            }

            function handleFormSubmit() {
                console.log('Form submitted via AJAX');
                
                if (!validateDateRange()) {
                    return;
                }

                const formData = new FormData(filterForm);
                const params = new URLSearchParams();
                
                // Only add non-empty parameters
                for (let [key, value] of formData.entries()) {
                    if (value && value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                
                console.log('Fetching:', AJAX_URL + '?' + params.toString());
                
                // Show loading
                showLoading();
                
                // Make AJAX request to dedicated endpoint
                fetch(AJAX_URL + '?' + params.toString())
                    .then(response => {
                        console.log('Response received:', response);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Data received:', data);
                        if (data.success) {
                            tableBody.innerHTML = data.html;
                            totalRecordsEl.textContent = data.totalRecords;
                            
                            // Update URL without reloading - keep index.php in URL
                            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                            window.history.pushState({}, '', newUrl);
                            
                            highlightActiveFilters();
                        } else {
                            throw new Error(data.error || 'Erro ao carregar dados');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao carregar os dados: ' + error.message);
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            function clearAllFilters() {
                console.log('Clearing filters...');
                
                if (searchInput) searchInput.value = '';
                if (bloqueioFilter) bloqueioFilter.value = '';
                if (orgaoPagadorFilter) orgaoPagadorFilter.value = '';
                if (dataInicioFilter) dataInicioFilter.value = '';
                if (dataFimFilter) dataFimFilter.value = '';

                // Reload data without filters
                showLoading();
                
                fetch(AJAX_URL)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            tableBody.innerHTML = data.html;
                            totalRecordsEl.textContent = data.totalRecords;
                            window.history.pushState({}, '', window.location.pathname);
                            highlightActiveFilters();
                        } else {
                            throw new Error(data.error || 'Erro ao limpar filtros');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao limpar os filtros: ' + error.message);
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            function highlightActiveFilters() {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Reset all borders first
                [searchInput, bloqueioFilter, orgaoPagadorFilter, dataInicioFilter, dataFimFilter].forEach(filter => {
                    if (filter) {
                        filter.style.borderColor = '';
                        filter.style.borderWidth = '';
                    }
                });
                
                if (searchInput && urlParams.has('search')) {
                    searchInput.style.borderColor = '#206bc4';
                    searchInput.style.borderWidth = '2px';
                }

                [bloqueioFilter, orgaoPagadorFilter, dataInicioFilter, dataFimFilter].forEach(filter => {
                    if (filter && filter.value) {
                        filter.style.borderColor = '#206bc4';
                        filter.style.borderWidth = '2px';
                    }
                });

                if (clearFiltersBtn) {
                    const hasActiveFilters = urlParams.has('search') || 
                                            urlParams.has('bloqueio') || 
                                            urlParams.has('orgao_pagador') || 
                                            urlParams.has('data_inicio') || 
                                            urlParams.has('data_fim');
                    
                    if (hasActiveFilters) {
                        clearFiltersBtn.classList.add('btn-warning');
                        clearFiltersBtn.classList.remove('btn-secondary');
                    } else {
                        clearFiltersBtn.classList.add('btn-secondary');
                        clearFiltersBtn.classList.remove('btn-warning');
                    }
                }
            }

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
            
            function showLoading() {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('active');
                }
            }
            
            function hideLoading() {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('active');
                }
            }
        })();
    </script>

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