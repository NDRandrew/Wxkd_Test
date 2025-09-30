// Add this script section to your view file after the pagination state variables

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
    
    const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/roteamento/ajax_encerramento.php';

    // Setup date inputs
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
    
    setupDateInput(dataInicioFilter, dataInicioDisplay);
    setupDateInput(dataFimFilter, dataFimDisplay);

    // Initialize event listeners
    initializeEventListeners();
    highlightActiveFilters();
    
    // Attach click handlers to initial page numbers (rendered by PHP)
    attachPageNumberHandlers();

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
                currentPage = 1; // Reset to page 1 on new search
                handleFormSubmit();
            });
        }
        
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1; // Reset to page 1 on new filter
                handleFormSubmit();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1; // Reset to page 1
                    handleFormSubmit();
                }
            });
        }
        
        // Per page selector
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value);
                currentPage = 1; // Reset to page 1 when changing per page
                handleFormSubmit();
            });
        }
        
        // Previous page button
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    handleFormSubmit();
                }
            });
        }
        
        // Next page button
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

    function handleFormSubmit() {
        if (!validateDateRange()) {
            return;
        }

        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        // Add form data
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        // Add pagination parameters
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
                    
                    // Update pagination info
                    totalRecordsEl.textContent = data.totalRecords;
                    startRecordEl.textContent = data.startRecord;
                    endRecordEl.textContent = data.endRecord;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    perPage = data.perPage;
                    
                    // Update pagination controls
                    updatePaginationControls();
                    
                    // Update URL without reloading
                    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                    window.history.pushState({}, '', newUrl);
                    
                    // Update filter highlights
                    highlightActiveFilters();
                    
                    // Scroll to table
                    document.getElementById('tableContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        // Clear all input values
        if (searchInput) searchInput.value = '';
        if (bloqueioFilter) bloqueioFilter.value = '';
        if (orgaoPagadorFilter) orgaoPagadorFilter.value = '';
        if (dataInicioFilter) dataInicioFilter.value = '';
        if (dataInicioDisplay) dataInicioDisplay.value = '';
        if (dataFimFilter) dataFimFilter.value = '';
        if (dataFimDisplay) dataFimDisplay.value = '';
        
        // Reset pagination
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

    function updatePaginationControls() {
        // Update prev/next button states
        if (prevPageBtn) {
            if (currentPage <= 1) {
                prevPageBtn.classList.add('disabled');
                prevPageBtn.querySelector('a').setAttribute('aria-disabled', 'true');
            } else {
                prevPageBtn.classList.remove('disabled');
                prevPageBtn.querySelector('a').setAttribute('aria-disabled', 'false');
            }
        }
        
        if (nextPageBtn) {
            if (currentPage >= totalPages) {
                nextPageBtn.classList.add('disabled');
                nextPageBtn.querySelector('a').setAttribute('aria-disabled', 'true');
            } else {
                nextPageBtn.classList.remove('disabled');
                nextPageBtn.querySelector('a').setAttribute('aria-disabled', 'false');
            }
        }
        
        // Rebuild page numbers
        rebuildPageNumbers();
    }
    
    function attachPageNumberHandlers() {
        // Attach click handlers to existing page numbers (rendered by PHP on initial load)
        const existingPageNumbers = paginationControls.querySelectorAll('.page-number');
        existingPageNumbers.forEach(link => {
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

    function rebuildPageNumbers() {
        // Remove existing page number items
        const existingNumbers = paginationControls.querySelectorAll('.page-number');
        existingNumbers.forEach(item => item.parentElement.remove());
        
        const ellipsisItems = paginationControls.querySelectorAll('.page-item:not(#prevPage):not(#nextPage)');
        ellipsisItems.forEach(item => {
            if (item.querySelector('.page-link')?.textContent === '...') {
                item.remove();
            }
        });
        
        // Calculate page range
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        const fragment = document.createDocumentFragment();
        
        // First page + ellipsis
        if (startPage > 1) {
            fragment.appendChild(createPageItem(1, false));
            if (startPage > 2) {
                fragment.appendChild(createEllipsisItem());
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            fragment.appendChild(createPageItem(i, i === currentPage));
        }
        
        // Ellipsis + last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                fragment.appendChild(createEllipsisItem());
            }
            fragment.appendChild(createPageItem(totalPages, false));
        }
        
        // Insert before next button
        nextPageBtn.parentNode.insertBefore(fragment, nextPageBtn);
        
        // Add click handlers to new page numbers
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
        
        // Reset all borders first
        [searchInput, bloqueioFilter, orgaoPagadorFilter, dataInicioDisplay, dataFimDisplay].forEach(filter => {
            if (filter) {
                filter.style.borderColor = '';
                filter.style.borderWidth = '';
            }
        });
        
        // Highlight active filters
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

// Date picker scripts for data inicio
(function () {
    const dateEl = document.getElementById('dataInicioFilter');
    const displayEl = document.getElementById('dataInicioDisplay');
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
})();

// Date picker scripts for data fim
(function () {
    const dateEl = document.getElementById('dataFimFilter');
    const displayEl = document.getElementById('dataFimDisplay');
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
})();

// Theme handler
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