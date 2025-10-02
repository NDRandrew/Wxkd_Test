// Add this method to AnaliseEncerramentoController class in analise_encerramento_control.php

public function renderModals($dados) {
    $html = '';
    $length = is_array($dados) ? count($dados) : 0;
    
    if ($length > 0) {
        for ($i = 0; $i < $length; $i++) {
            $codSolicitacao = htmlspecialchars($dados[$i]['COD_SOLICITACAO']);
            
            $html .= '
            <div class="modal fade" id="AnaliseDetalhesModal' . $codSolicitacao . '" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalhes - Solicitação #' . $codSolicitacao . '</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Código Solicitação</label>
                                    <p class="form-control-plaintext">' . $codSolicitacao . '</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Agência/PACB</label>
                                    <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Chave Loja</label>
                                    <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nome Loja</label>
                                    <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['NOME_LOJA']) . '</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Data Recepção</label>
                                    <p class="form-control-plaintext">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Órgão Pagador</label>
                                    <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>';
        }
    }
    
    return $html;
}

---------


<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AjaxEncerramentoHandler {
    private $model;
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function loadData($filters = []) {
        // Base WHERE clause
        $where = "AND A.COD_TIPO_SERVICO=1";
        
        // Add search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
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
        if (isset($filters['bloqueio']) && $filters['bloqueio'] !== '') {
            if ($filters['bloqueio'] === 'bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NOT NULL";
            } else if ($filters['bloqueio'] === 'nao_bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NULL";
            }
        }
        
        // Add orgao pagador filter
        if (isset($filters['orgao_pagador']) && !empty($filters['orgao_pagador'])) {
            $orgao = $filters['orgao_pagador'];
            $where .= " AND G.ORGAO_PAGADOR LIKE '%$orgao%'";
        }
        
        // Add data range filter
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '$filters[data_inicio]'";
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '$filters[data_fim]'";
        }
        
        // Get pagination parameters
        $page = isset($filters['page']) ? max(1, intval($filters['page'])) : 1;
        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 25;
        
        // Validate per_page (only allow 50, 100, 200)
        if (!in_array($perPage, [25, 50, 100, 200])) {
            $perPage = 25;
        }
        
        // Get total count
        $totalRecords = $this->model->getTotalCount($where);
        
        // Calculate pagination
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Get paginated data
        $dados = $this->model->solicitacoes($where, $perPage, $offset);
        
        return [
            'dados' => $dados,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage,
            'startRecord' => $offset + 1,
            'endRecord' => min($offset + $perPage, $totalRecords)
        ];
    }
    
    public function renderTableRows($dados) {
        $html = '';
        $length = is_array($dados) ? count($dados) : 0;
        
        if ($length > 0) {
            for ($i = 0; $i < $length; $i++) {
                $html .= '<tr data-bs-toggle="modal" data-bs-target="#AnaliseDetalhesModal' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '" style="cursor:pointer;">';
                $html .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                        <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                            <input class="form-check-input position-static m-0" type="checkbox" onclick="event.stopPropagation();" />
                            <span class="form-check-label d-none"></span>
                        </label>
                    </th>';
                $html .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span></th>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
                
                // Data Retirada
                if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Data</span></td>';
                }
                
                // Bloqueio
                if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                    $html .= '<td><span class="text-green" style="display: block; text-align: center;">Bloqueado</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span></td>';
                
                // Motivo Bloqueio
                if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span></td>';
                }
                
                // Motivo Encerramento
                if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span></td>';
                
                // PARM
                if (!is_null($dados[$i]['PARM'])) {
                    if($dados[$i]['PARM'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['PARM']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                // TRAG
                if (!is_null($dados[$i]['TRAG'])) {
                    if($dados[$i]['TRAG'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['TRAG']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['MEDIA_CONTABEIS']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['MEDIA_NEGOCIO']) . '</span></td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
        }
        
        return $html;
    }
    
    public function renderModals($dados) {
        $html = '';
        $length = is_array($dados) ? count($dados) : 0;
        
        if ($length > 0) {
            for ($i = 0; $i < $length; $i++) {
                $codSolicitacao = htmlspecialchars($dados[$i]['COD_SOLICITACAO']);
                
                $html .= '
                <div class="modal fade" id="AnaliseDetalhesModal' . $codSolicitacao . '" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalhes - Solicitação #' . $codSolicitacao . '</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Código Solicitação</label>
                                        <p class="form-control-plaintext">' . $codSolicitacao . '</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Agência/PACB</label>
                                        <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Chave Loja</label>
                                        <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nome Loja</label>
                                        <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['NOME_LOJA']) . '</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Data Recepção</label>
                                        <p class="form-control-plaintext">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Órgão Pagador</label>
                                        <p class="form-control-plaintext">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }
        
        return $html;
    }
}

try {
    $handler = new AjaxEncerramentoHandler();
    $result = $handler->loadData($_GET);
    
    echo json_encode([
        'success' => true,
        'html' => $handler->renderTableRows($result['dados']),
        'modals' => $handler->renderModals($result['dados']),
        'totalRecords' => $result['totalRecords'],
        'totalPages' => $result['totalPages'],
        'currentPage' => $result['currentPage'],
        'perPage' => $result['perPage'],
        'startRecord' => $result['startRecord'],
        'endRecord' => $result['endRecord']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>



--------


<script>
    var currentPage = <?php echo $currentPage; ?>;
    var totalPages = <?php echo $totalPages; ?>;
    var perPage = <?php echo $recordsPerPage; ?>;
</script>

<script>
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
      const modalsContainer = document.getElementById('modalsContainer');
      
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

      function updatePaginationControls() {
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
          
          rebuildPageNumbers();
      }
      
      function attachPageNumberHandlers() {
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
</script>