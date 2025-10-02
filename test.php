// analise_encerramento.js - Debug version with logging
(function() {
    'use strict';

    console.log('Script loaded');

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
    
    console.log('Elements found:', {
        filterForm: !!filterForm,
        tableBody: !!tableBody,
        modalsContainer: !!modalsContainer
    });
    
    const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/roteamento/ajax_encerramento.php';

    // Get state from global variable
    let currentPage = window.pageState ? window.pageState.currentPage : 1;
    let totalPages = window.pageState ? window.pageState.totalPages : 0;
    let perPage = window.pageState ? window.pageState.perPage : 25;

    console.log('Initial state:', { currentPage, totalPages, perPage });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded');
        setupDateInputs();
        initializeEventListeners();
        highlightActiveFilters();
        attachPageNumberHandlers();
        
        // Auto-load data if flag is set
        if (window.pageState && window.pageState.autoLoadData) {
            console.log('Auto-loading data...');
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
        console.log('handleFormSubmit called');
        
        if (!validateDateRange()) {
            console.log('Date validation failed');
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
        
        const fullUrl = AJAX_URL + '?' + params.toString();
        console.log('Fetching:', fullUrl);
        
        showLoading();
        
        fetch(fullUrl)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text(); // Get as text first to see what we're getting
            })
            .then(text => {
                console.log('Raw response:', text.substring(0, 200)); // Log first 200 chars
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', {
                        success: data.success,
                        hasHtml: !!data.html,
                        hasModals: !!data.modals,
                        totalRecords: data.totalRecords
                    });
                    
                    if (data.success) {
                        // Update table content
                        if (tableBody) {
                            console.log('Updating table body');
                            tableBody.innerHTML = data.html;
                        } else {
                            console.error('tableBody element not found!');
                        }
                        
                        // Update modals
                        if (modalsContainer) {
                            if (data.modals) {
                                console.log('Updating modals');
                                modalsContainer.innerHTML = data.modals;
                            } else {
                                console.warn('No modals in response');
                            }
                        } else {
                            console.error('modalsContainer element not found!');
                        }
                        
                        // Update pagination info
                        if (totalRecordsEl) totalRecordsEl.textContent = data.totalRecords;
                        if (startRecordEl) startRecordEl.textContent = data.startRecord;
                        if (endRecordEl) endRecordEl.textContent = data.endRecord;
                        
                        currentPage = data.currentPage;
                        totalPages = data.totalPages;
                        perPage = data.perPage;
                        
                        console.log('Updated state:', { currentPage, totalPages, perPage });
                        
                        updatePaginationControls();
                        
                        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                        window.history.pushState({}, '', newUrl);
                        
                        highlightActiveFilters();
                        
                        // Only scroll on user actions, not initial load
                        if (window.pageState && !window.pageState.autoLoadData) {
                            document.getElementById('tableContainer')?.scrollIntoView({ 
                                behavior: 'smooth', 
                                block: 'start' 
                            });
                        }
                        
                        // Disable auto-load after first load
                        if (window.pageState) {
                            window.pageState.autoLoadData = false;
                        }
                        
                        console.log('Update complete');
                    } else {
                        throw new Error(data.error || 'Erro ao carregar dados');
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Erro ao carregar os dados: ' + error.message);
            })
            .finally(() => {
                console.log('Hiding loading');
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
        handleFormSubmit();
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

    function createEllipsisItem() {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        
        const span = document.createElement('span');
        span.className = 'page-link';
        span.textContent = '...';
        
        li.appendChild(span);
        return li;
    }

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