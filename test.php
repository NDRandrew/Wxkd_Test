<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AnaliseEncerramentoController {
    private $model;
    private $dados;
    private $totalRecords;
    private $totalPages;
    private $currentPage;
    private $recordsPerPage;
    
    public function __construct() {
        $this->model = new Analise();
        
        // Set default values for initial page load (no data loaded yet)
        $this->currentPage = 1;
        $this->recordsPerPage = 25;
        $this->dados = [];
        $this->totalRecords = 0;
        $this->totalPages = 0;
    }
    
    public function getDados() {
        return $this->dados;
    }
    
    public function getTotalRecords() {
        return $this->totalRecords;
    }
    
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    public function getRecordsPerPage() {
        return $this->recordsPerPage;
    }
    
    public function getStartRecord() {
        return 0;
    }
    
    public function getEndRecord() {
        return 0;
    }
}

// Initialize controller for initial page load (no data)
$controller = new AnaliseEncerramentoController();
$dados = $controller->getDados();
$totalRecords = $controller->getTotalRecords();
$totalPages = $controller->getTotalPages();
$currentPage = $controller->getCurrentPage();
$recordsPerPage = $controller->getRecordsPerPage();
$startRecord = $controller->getStartRecord();
$endRecord = $controller->getEndRecord();
?>


-------

<!-- Replace the <tbody> section with this -->
<tbody id="tableBody">
    <tr>
        <td colspan="16" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 text-muted">Carregando registros...</p>
        </td>
    </tr>
</tbody>

<!-- At the end of the file, before </body>, replace all scripts with this minimal version -->

<!-- Only keep variable declarations inline -->
<script>
    // Global variables for pagination state
    window.pageState = {
        currentPage: <?php echo $currentPage; ?>,
        totalPages: <?php echo $totalPages; ?>,
        perPage: <?php echo $recordsPerPage; ?>,
        autoLoadData: true // Flag to trigger auto-load on page ready
    };
</script>

<!-- External JS file -->
<script src="analise_encerramento/analise_encerramento.js"></script>

<!-- Theme handler (keep this separate as it needs to run immediately) -->
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

--------

// analise_encerramento.js - Complete external JavaScript file
(function() {
    'use strict';

    // DOM Elements
    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const searchBtn = document.getElementById('searchBtn');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const searchInput = document.getElementById('searchInput');
    const bloqueioFilter = document.getElementById('bloqueioFilter');
    const orgaoPagadorFilter = document.getElementById('orgaoPagadorFilter');
    const dataInicioFilter = document.getElementById('dataInicioFilter');
    const dataInicioDisplay = document.getElementById('dataInicioDisplay');
    const dataFimFilter = document.getElementById('dataFimFilter');
    const dataFimDisplay = document.getElementById('dataFimDisplay');
    const tableBody = document.getElementById('tableBody');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const totalRecordsEl = document.getElementById('totalRecords');
    const startRecordEl = document.getElementById('startRecord');
    const endRecordEl = document.getElementById('endRecord');
    const perPageSelect = document.getElementById('perPageSelect');
    const paginationControls = document.getElementById('paginationControls');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const modalsContainer = document.getElementById('modalsContainer');
    
    const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/roteamento/ajax_encerramento.php';

    // Get state from global variable
    let currentPage = window.pageState.currentPage;
    let totalPages = window.pageState.totalPages;
    let perPage = window.pageState.perPage;

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        setupDateInputs();
        initializeEventListeners();
        highlightActiveFilters();
        attachPageNumberHandlers();
        
        // Auto-load data if flag is set
        if (window.pageState.autoLoadData) {
            // Small delay to ensure page is fully rendered
            setTimeout(() => {
                handleFormSubmit();
            }, 100);
        }
    });

    /**
     * Setup date input synchronization
     */
    function setupDateInputs() {
        setupDateInput(dataInicioFilter, dataInicioDisplay);
        setupDateInput(dataFimFilter, dataFimDisplay);
        
        // Setup date picker interactions
        setupDatePicker('dataInicioFilter', 'dataInicioDisplay');
        setupDatePicker('dataFimFilter', 'dataFimDisplay');
    }

    function setupDateInput(dateInput, displayInput) {
        if (!dateInput || !displayInput) return;
        
        dateInput.addEventListener('change', function() {
            if (this.value) {
                const parts = this.value.split('-');
                displayInput.value = parts[2] + '/' + parts[1] + '/' + parts[0];
            } else {
                displayInput.value = '';
            }
        });
    }

    function setupDatePicker(dateId, displayId) {
        const dateEl = document.getElementById(dateId);
        const displayEl = document.getElementById(displayId);
        const wrapper = dateEl?.closest('.date-input-wrapper');

        if (!dateEl || !displayEl || !wrapper) return;

        function toDisplay(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-');
            return `${d}/${m}/${y}`;
        }

        displayEl.value = toDisplay(dateEl.value);

        function openPickerFromGesture(e) {
            if (e.type === 'pointerdown' && e.button !== 0) return;
            e.preventDefault();

            if (typeof dateEl.showPicker === 'function') {
                try {
                    dateEl.showPicker();
                } catch (err) {
                    dateEl.focus({ preventScroll: true });
                }
            } else {
                dateEl.focus({ preventScroll: true });
            }
        }

        wrapper.addEventListener('pointerdown', openPickerFromGesture);
        wrapper.addEventListener('click', openPickerFromGesture);

        dateEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                if (typeof dateEl.showPicker === 'function') {
                    try { dateEl.showPicker(); } catch { /* ignore */ }
                }
            }
        });

        dateEl.addEventListener('change', function () {
            displayEl.value = toDisplay(dateEl.value);
        });
    }

    /**
     * Initialize all event listeners
     */
    function initializeEventListeners() {
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearAllFilters();
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1;
                handleFormSubmit();
            });
        }
        
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1;
                handleFormSubmit();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    handleFormSubmit();
                }
            });
        }
        
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value);
                currentPage = 1;
                handleFormSubmit();
            });
        }
        
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    handleFormSubmit();
                }
            });
        }
        
        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    handleFormSubmit();
                }
            });
        }
    }

    /**
     * Handle form submission via AJAX
     */
    function handleFormSubmit() {
        if (!validateDateRange()) {
            return;
        }

        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        params.append('page', currentPage);
        params.append('per_page', perPage);
        
        showLoading();
        
        fetch(AJAX_URL + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update table content
                    tableBody.innerHTML = data.html;
                    
                    // Update modals
                    if (modalsContainer && data.modals) {
                        modalsContainer.innerHTML = data.modals;
                    }
                    
                    // Update pagination info
                    totalRecordsEl.textContent = data.totalRecords;
                    startRecordEl.textContent = data.startRecord;
                    endRecordEl.textContent = data.endRecord;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    perPage = data.perPage;
                    
                    updatePaginationControls();
                    
                    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                    window.history.pushState({}, '', newUrl);
                    
                    highlightActiveFilters();
                    
                    // Only scroll on user actions, not initial load
                    if (!window.pageState.autoLoadData) {
                        document.getElementById('tableContainer')?.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'start' 
                        });
                    }
                    
                    // Disable auto-load after first load
                    window.pageState.autoLoadData = false;
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

    /**
     * Clear all filters and reload data
     */
    function clearAllFilters() {
        if (searchInput) searchInput.value = '';
        if (bloqueioFilter) bloqueioFilter.value = '';
        if (orgaoPagadorFilter) orgaoPagadorFilter.value = '';
        if (dataInicioFilter) dataInicioFilter.value = '';
        if (dataInicioDisplay) dataInicioDisplay.value = '';
        if (dataFimFilter) dataFimFilter.value = '';
        if (dataFimDisplay) dataFimDisplay.value = '';
        
        currentPage = 1;
        showLoading();
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('per_page', perPage);
        
        fetch(AJAX_URL + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.html;
                    
                    if (modalsContainer && data.modals) {
                        modalsContainer.innerHTML = data.modals;
                    }
                    
                    totalRecordsEl.textContent = data.totalRecords;
                    startRecordEl.textContent = data.startRecord;
                    endRecordEl.textContent = data.endRecord;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    
                    updatePaginationControls();
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

    /**
     * Update pagination controls
     */
    function updatePaginationControls() {
        if (prevPageBtn) {
            if (currentPage <= 1) {
                prevPageBtn.classList.add('disabled');
                prevPageBtn.querySelector('a')?.setAttribute('aria-disabled', 'true');
            } else {
                prevPageBtn.classList.remove('disabled');
                prevPageBtn.querySelector('a')?.setAttribute('aria-disabled', 'false');
            }
        }
        
        if (nextPageBtn) {
            if (currentPage >= totalPages) {
                nextPageBtn.classList.add('disabled');
                nextPageBtn.querySelector('a')?.setAttribute('aria-disabled', 'true');
            } else {
                nextPageBtn.classList.remove('disabled');
                nextPageBtn.querySelector('a')?.setAttribute('aria-disabled', 'false');
            }
        }
        
        rebuildPageNumbers();
    }

    /**
     * Attach click handlers to existing page numbers
     */
    function attachPageNumberHandlers() {
        const existingPageNumbers = paginationControls?.querySelectorAll('.page-number');
        existingPageNumbers?.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page && page !== currentPage) {
                    currentPage = page;
                    handleFormSubmit();
                }
            });
        });
    }

    /**
     * Rebuild page number buttons
     */
    function rebuildPageNumbers() {
        if (!paginationControls) return;
        
        const existingNumbers = paginationControls.querySelectorAll('.page-number');
        existingNumbers.forEach(item => item.parentElement.remove());
        
        const ellipsisItems = paginationControls.querySelectorAll('.page-item:not(#prevPage):not(#nextPage)');
        ellipsisItems.forEach(item => {
            if (item.querySelector('.page-link')?.textContent === '...') {
                item.remove();
            }
        });
        
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        const fragment = document.createDocumentFragment();
        
        if (startPage > 1) {
            fragment.appendChild(createPageItem(1, false));
            if (startPage > 2) {
                fragment.appendChild(createEllipsisItem());
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            fragment.appendChild(createPageItem(i, i === currentPage));
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                fragment.appendChild(createEllipsisItem());
            }
            fragment.appendChild(createPageItem(totalPages, false));
        }
        
        nextPageBtn.parentNode.insertBefore(fragment, nextPageBtn);
        
        const newPageNumbers = paginationControls.querySelectorAll('.page-number');
        newPageNumbers.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page !== currentPage) {
                    currentPage = page;
                    handleFormSubmit();
                }
            });
        });
    }

    /**
     * Create page item element
     */
    function createPageItem(pageNum, isActive) {
        const li = document.createElement('li');
        li.className = 'page-item' + (isActive ? ' active' : '');
        
        const a = document.createElement('a');
        a.className = 'page-link page-number';
        a.href = '#';
        a.setAttribute('data-page', pageNum);
        a.textContent = pageNum;
        
        li.appendChild(a);
        return li;
    }

    /**
     * Create ellipsis item element
     */
    function createEllipsisItem() {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        
        const span = document.createElement('span');
        span.className = 'page-link';
        span.textContent = '...';
        
        li.appendChild(span);
        return li;
    }

    /**
     * Highlight active filters visually
     */
    function highlightActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        [searchInput, bloqueioFilter, orgaoPagadorFilter, dataInicioDisplay, dataFimDisplay].forEach(filter => {
            if (filter) {
                filter.style.borderColor = '';
                filter.style.borderWidth = '';
            }
        });
        
        if (searchInput && urlParams.has('search')) {
            searchInput.style.borderColor = '#206bc4';
            searchInput.style.borderWidth = '2px';
        }

        if (bloqueioFilter && bloqueioFilter.value) {
            bloqueioFilter.style.borderColor = '#206bc4';
            bloqueioFilter.style.borderWidth = '2px';
        }
        
        if (orgaoPagadorFilter && orgaoPagadorFilter.value) {
            orgaoPagadorFilter.style.borderColor = '#206bc4';
            orgaoPagadorFilter.style.borderWidth = '2px';
        }
        
        if (dataInicioDisplay && dataInicioFilter && dataInicioFilter.value) {
            dataInicioDisplay.style.borderColor = '#206bc4';
            dataInicioDisplay.style.borderWidth = '2px';
        }
        
        if (dataFimDisplay && dataFimFilter && dataFimFilter.value) {
            dataFimDisplay.style.borderColor = '#206bc4';
            dataFimDisplay.style.borderWidth = '2px';
        }

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

    /**
     * Show loading indicator
     */
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
    }

    /**
     * Hide loading indicator
     */
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

})();