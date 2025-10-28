<?php
// Add these methods to analise_encerramento_model.class.php

public function getUnviewedOcorrenciasCount() {
    $query = "SELECT COUNT(DISTINCT CHAVE_LOTE) as TOTAL 
              FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
              WHERE VIEWED_FLAG IS NULL OR VIEWED_FLAG = 0";
    $dados = $this->sql->select($query);
    return $dados[0]['TOTAL'];
}

public function getOcorrenciasLotes($where = '', $limit = 25, $offset = 0) {
    $query = "SELECT 
                CHAVE_LOTE,
                MIN(DT_OCORRENCIA) as DT_OCORRENCIA,
                COUNT(*) as QTD_ERROS,
                SUM(CASE WHEN VIEWED_FLAG IS NULL OR VIEWED_FLAG = 0 THEN 1 ELSE 0 END) as QTD_NAO_VISTOS
              FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA
              WHERE 1=1 " . $where . "
              GROUP BY CHAVE_LOTE
              ORDER BY DT_OCORRENCIA DESC
              OFFSET " . $offset . " ROWS
              FETCH NEXT " . $limit . " ROWS ONLY";
    return $this->sql->select($query);
}

public function getOcorrenciasByLote($chave_lote) {
    $query = "SELECT * 
              FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
              WHERE CHAVE_LOTE = " . intval($chave_lote) . "
              ORDER BY DT_OCORRENCIA DESC";
    return $this->sql->select($query);
}

public function getTotalOcorrenciasLotes($where = '') {
    $query = "SELECT COUNT(DISTINCT CHAVE_LOTE) as TOTAL 
              FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
              WHERE 1=1 " . $where;
    $dados = $this->sql->select($query);
    return $dados[0]['TOTAL'];
}

public function markLoteAsViewed($chave_lote) {
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
              SET VIEWED_FLAG = 1 
              WHERE CHAVE_LOTE = " . intval($chave_lote);
    return $this->sql->update($query);
}

public function encerrarCorrespondente($cod_solicitacao, $chave_loja) {
    // Insert/Update logic - query to be provided by user
    // Placeholder for now
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
              SET STATUS_ENCERRAMENTO = 'EFETUADO',
                  DATA_ENC = GETDATE()
              WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    return $this->sql->update($query);
}
?>

------------


<?php
// Add these methods to analise_encerramento_control.php class

public function renderOcorrenciasAccordions($dados) {
    if (empty($dados)) {
        return '<tr><td colspan="4" class="text-center py-5">Nenhuma ocorrência encontrada</td></tr>';
    }
    
    $html = '';
    foreach ($dados as $row) {
        $chaveLote = htmlspecialchars($row['CHAVE_LOTE']);
        $dataOcorrencia = is_object($row['DT_OCORRENCIA']) 
            ? $row['DT_OCORRENCIA']->format('d/m/Y') 
            : date('d/m/Y', strtotime($row['DT_OCORRENCIA']));
        $qtdErros = htmlspecialchars($row['QTD_ERROS']);
        $qtdNaoVistos = intval($row['QTD_NAO_VISTOS']);
        
        $badgeClass = $qtdNaoVistos > 0 ? 'bg-red' : 'bg-green';
        
        $html .= '<tr class="accordion-row" data-lote="' . $chaveLote . '" style="cursor: pointer;">
                    <td class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-chevron-right">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </td>
                    <td class="text-center"><strong>Lote ' . $chaveLote . '</strong></td>
                    <td class="text-center">' . $dataOcorrencia . '</td>
                    <td class="text-center">
                        <span class="badge ' . $badgeClass . '">' . $qtdErros . ' erros</span>
                        ' . ($qtdNaoVistos > 0 ? '<span class="badge bg-red ms-2">' . $qtdNaoVistos . ' não vistos</span>' : '') . '
                    </td>
                  </tr>
                  <tr class="accordion-details" data-lote="' . $chaveLote . '" style="display: none;">
                    <td colspan="4" class="p-0">
                        <div class="loading-overlay active">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </td>
                  </tr>';
    }
    
    return $html;
}

public function renderOcorrenciasDetails($ocorrencias) {
    if (empty($ocorrencias)) {
        return '<div class="p-3 text-center">Nenhum detalhe encontrado</div>';
    }
    
    $html = '<div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr style="background-color: #d8d8d8;">
                            <th>ID</th>
                            <th>Data</th>
                            <th>CNPJs</th>
                            <th>Ocorrência</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($ocorrencias as $occ) {
        $dataOcorrencia = is_object($occ['DT_OCORRENCIA']) 
            ? $occ['DT_OCORRENCIA']->format('d/m/Y H:i:s') 
            : date('d/m/Y H:i:s', strtotime($occ['DT_OCORRENCIA']));
        
        $html .= '<tr>
                    <td>' . htmlspecialchars($occ['ID']) . '</td>
                    <td>' . $dataOcorrencia . '</td>
                    <td>' . htmlspecialchars($occ['CNPJs']) . '</td>
                    <td><small>' . htmlspecialchars($occ['OCORRENCIA']) . '</small></td>
                  </tr>';
    }
    
    $html .= '</tbody></table></div>';
    return $html;
}
?>

-------------

<?php
// Add these handlers to ajax_encerramento.php (before the regular data loading section)

// Load Ocorrências accordion view
if (isset($_POST['acao']) && $_POST['acao'] == 'load_ocorrencias') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
        
        $where = '';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $where .= " AND (CAST(CHAVE_LOTE AS VARCHAR) LIKE '%$search%' OR CNPJs LIKE '%$search%' OR OCORRENCIA LIKE '%$search%')";
        }
        
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
        $offset = ($page - 1) * $perPage;
        
        $handler = new AjaxEncerramentoHandler();
        $model = new Analise();
        
        $totalRecords = $model->getTotalOcorrenciasLotes($where);
        $totalPages = ceil($totalRecords / $perPage);
        $dados = $model->getOcorrenciasLotes($where, $perPage, $offset);
        
        $controller = new AnaliseEncerramentoController();
        $html = $controller->renderOcorrenciasAccordions($dados);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'html' => $html,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Load details for specific lote
if (isset($_POST['acao']) && $_POST['acao'] == 'load_lote_details') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
        
        $chave_lote = isset($_POST['chave_lote']) ? intval($_POST['chave_lote']) : 0;
        
        if ($chave_lote <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Lote inválido']);
            exit;
        }
        
        $model = new Analise();
        $ocorrencias = $model->getOcorrenciasByLote($chave_lote);
        
        // Mark as viewed
        $model->markLoteAsViewed($chave_lote);
        
        $controller = new AnaliseEncerramentoController();
        $html = $controller->renderOcorrenciasDetails($ocorrencias);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'html' => $html
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get unviewed count
if (isset($_POST['acao']) && $_POST['acao'] == 'get_unviewed_count') {
    ob_start();
    try {
        $model = new Analise();
        $count = $model->getUnviewedOcorrenciasCount();
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'count' => $count
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle encerramento action
if (isset($_POST['acao']) && $_POST['acao'] == 'encerrar_correspondente') {
    ob_start();
    try {
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $chave_loja = isset($_POST['chave_loja']) ? intval($_POST['chave_loja']) : 0;
        
        if ($cod_solicitacao <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Solicitação inválida']);
            exit;
        }
        
        $model = new Analise();
        $result = $model->encerrarCorrespondente($cod_solicitacao, $chave_loja);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false,
            'message' => $result ? 'Correspondente encerrado com sucesso' : 'Erro ao encerrar'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>


----------


// Add these functions and variables to analise_encerramento.js

let currentView = 'solicitacoes'; // 'solicitacoes' or 'ocorrencias'

function switchToOcorrenciasView() {
    currentView = 'ocorrencias';
    updateTableHeaders();
    loadOcorrencias();
    
    // Update header styling
    document.getElementById('headerSolicitacoes').classList.remove('active');
    document.getElementById('headerOcorrencias').classList.add('active');
}

function switchToSolicitacoesView() {
    currentView = 'solicitacoes';
    updateTableHeaders();
    handleFormSubmit();
    
    // Update header styling
    document.getElementById('headerOcorrencias').classList.remove('active');
    document.getElementById('headerSolicitacoes').classList.add('active');
}

function updateTableHeaders() {
    const thead = document.querySelector('#dataTable thead tr');
    
    if (currentView === 'ocorrencias') {
        thead.innerHTML = `
            <th class="thead-encerramento" style="text-align: center; width: 50px;"></th>
            <th class="thead-encerramento" style="text-align: center;">Lote</th>
            <th class="thead-encerramento" style="text-align: center;">Data</th>
            <th class="thead-encerramento" style="text-align: center;">Status</th>
        `;
    } else {
        // Reset to original headers
        thead.innerHTML = `
            <th class="text-center align-middle p-0" style="background-color: #d8d8d8;">
                ${window.userPermissions.canViewBulk ? '<label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0"><input class="form-check-input position-static m-0" type="checkbox" /><span class="form-check-label d-none"></span></label>' : ''}
            </th>
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
            <th class="thead-encerramento" style="text-align: center;">Média Tran. Contábeis</th>
            <th class="thead-encerramento" style="text-align: center;">Média Tran. Negócio</th>
        `;
    }
}

function loadOcorrencias() {
    const filterForm = document.getElementById('filterForm');
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
    
    const ajaxFormData = new FormData();
    ajaxFormData.append('acao', 'load_ocorrencias');
    
    fetch(AJAX_URL + '?' + params.toString(), {
        method: 'POST',
        body: ajaxFormData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tableBody = document.getElementById('tableBody');
            if (tableBody) tableBody.innerHTML = data.html;
            
            updatePaginationInfo(data);
            updatePaginationControls();
            
            attachAccordionHandlers();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showNotification('Erro ao carregar ocorrências', 'error');
    })
    .finally(() => hideLoading());
}

function attachAccordionHandlers() {
    document.querySelectorAll('.accordion-row').forEach(row => {
        row.addEventListener('click', function() {
            const lote = this.getAttribute('data-lote');
            const detailsRow = document.querySelector('.accordion-details[data-lote="' + lote + '"]');
            const icon = this.querySelector('.icon-chevron-right');
            
            if (detailsRow.style.display === 'none') {
                // Close all other accordions
                document.querySelectorAll('.accordion-details').forEach(dr => {
                    dr.style.display = 'none';
                });
                document.querySelectorAll('.accordion-row .icon-chevron-right').forEach(ic => {
                    ic.style.transform = 'rotate(0deg)';
                });
                
                // Open this one
                detailsRow.style.display = 'table-row';
                icon.style.transform = 'rotate(90deg)';
                
                loadLoteDetails(lote);
            } else {
                detailsRow.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        });
    });
}

function loadLoteDetails(lote) {
    const detailsCell = document.querySelector('.accordion-details[data-lote="' + lote + '"] td');
    
    const formData = new FormData();
    formData.append('acao', 'load_lote_details');
    formData.append('chave_lote', lote);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            detailsCell.innerHTML = data.html;
            updateUnviewedCount();
        } else {
            detailsCell.innerHTML = '<div class="p-3 text-center text-danger">Erro ao carregar detalhes</div>';
        }
    })
    .catch(error => {
        console.error('Error loading details:', error);
        detailsCell.innerHTML = '<div class="p-3 text-center text-danger">Erro ao carregar detalhes</div>';
    });
}

function updateUnviewedCount() {
    const formData = new FormData();
    formData.append('acao', 'get_unviewed_count');
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('ocorrenciasBadge');
            if (badge) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        }
    })
    .catch(error => console.error('Error updating count:', error));
}

window.confirmarEncerramento = function(codSolicitacao, chaveLoja) {
    const formData = new FormData();
    formData.append('acao', 'encerrar_correspondente');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('chave_loja', chaveLoja);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Correspondente encerrado com sucesso!', 'success');
            
            // Close modals
            const alertModal = document.getElementById('AlertaEncerramento' + codSolicitacao);
            const detailsModal = document.getElementById('AnaliseDetalhesModal' + codSolicitacao);
            
            if (alertModal) {
                const backdrop = document.querySelector('.modal-backdrop');
                closeModal(alertModal, backdrop);
            }
            if (detailsModal) {
                const backdrop = document.querySelector('.modal-backdrop');
                closeModal(detailsModal, backdrop);
            }
            
            handleFormSubmit();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Encerramento error:', error);
        showNotification('Erro ao encerrar correspondente', 'error');
    });
};

// Update initialization to load unviewed count
document.addEventListener('DOMContentLoaded', function() {
    updateUnviewedCount();
    
    // Add click handlers for view switching
    const headerSolicitacoes = document.getElementById('headerSolicitacoes');
    const headerOcorrencias = document.getElementById('headerOcorrencias');
    
    if (headerSolicitacoes) {
        headerSolicitacoes.addEventListener('click', switchToSolicitacoesView);
    }
    
    if (headerOcorrencias) {
        headerOcorrencias.addEventListener('click', switchToOcorrenciasView);
    }
});


--------------

<!-- Replace the card-header in E.txt (analise_encerramento.php) with this: -->

<div class="card-header" style="display: flex; align-items: center; cursor: default;">
    <div id="headerSolicitacoes" class="active" style="display: flex; align-items: center; cursor: pointer; padding: 5px 10px; border-radius: 4px;">
        <h3 class="card-title" style="margin: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search" style="position:relative; bottom:2px; margin-right:5px;">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                <path d="M21 21l-6 -6" />
            </svg>
            Filtros e Busca
        </h3>
    </div>
    
    <div class="vr" style="margin-left:10px; height:30px !important;"></div>
    
    <div id="headerOcorrencias" style="display: flex; align-items: center; cursor: pointer; padding: 5px 10px; border-radius: 4px; margin-left:10px;">
        <h3 class="card-title" style="margin: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-message-exclamation" style="position:relative; bottom:2px; margin-right:5px;">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M8 9h8" />
                <path d="M8 13h6" />
                <path d="M15 18h-2l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v5.5" />
                <path d="M19 16v3" />
                <path d="M19 22v.01" />
            </svg>
            Ocorrências
        </h3>
        <span id="ocorrenciasBadge" class="badge badge-sm bg-red text-red-fg" style="position:relative; bottom:6px; margin-left:2px; display: none;">0</span>
    </div>
</div>

<style>
    #headerSolicitacoes.active,
    #headerOcorrencias.active {
        background-color: rgba(32, 107, 196, 0.1);
        border: 2px solid #206bc4;
    }
    
    #headerSolicitacoes:hover,
    #headerOcorrencias:hover {
        background-color: rgba(32, 107, 196, 0.05);
    }
</style>

<!-- ========================================= -->
<!-- MODAL CHANGES -->
<!-- ========================================= -->

<!-- In JH.txt (ajax_encerramento.php), replace the buildAlertModal function: -->

<?php
private function buildAlertModal($codSolicitacao, $chaveLoja) {
    return '
        <div class="modal" id="AlertaEncerramento' . $codSolicitacao . '" tabindex="-6">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon mb-2 text-danger icon-lg" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v2m0 4v.01" />
                            <path
                                d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                        </svg>
                        <h3>Tem Certeza?</h3>
                        <div class="text-secondary">Você irá encerrar o correspondente ' . htmlspecialchars($chaveLoja) . ' no Bacen.</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Cancelar</a></div>
                                <div class="col"><button class="btn btn-danger w-100" onclick="confirmarEncerramento(' . $codSolicitacao . ', ' . $chaveLoja . ')">Encerrar</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal" id="AlertaCancelar' . $codSolicitacao . '" tabindex="-6">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <div class="modal-status bg-warning"></div>
                    <div class="modal-body text-center py-4">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon mb-2 text-warning icon-lg" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v2m0 4v.01" />
                            <path
                                d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                        </svg>
                        <h3>Cancelar Solicitação?</h3>
                        <div class="text-secondary">Esta ação irá cancelar a solicitação ' . $codSolicitacao . '.</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Não</a></div>
                                <div class="col"><button class="btn btn-warning w-100" onclick="cancelarSolicitacao(' . $codSolicitacao . ')">Sim, Cancelar</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    ';
}
?>

<!-- In JH.txt, update buildModalFooter to use the new cancel modal: -->

<?php
private function buildModalFooter($codSolicitacao) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canCancel = canCancelRequest($cod_usu);
    
    return '
        <div class="modal-footer d-flex justify-content-between">
            ' . ($canCancel ? '<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#AlertaCancelar' . $codSolicitacao . '">Cancelar Solicitação</button>' : '<div></div>') . '
            <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Fechar</a>
        </div>';
}
?>


-----------


/* Add these styles to the <style> section in E.txt (analise_encerramento.php) */

/* Header styling for view switching */
#headerSolicitacoes,
#headerOcorrencias {
    transition: all 0.2s ease;
}

#headerSolicitacoes.active,
#headerOcorrencias.active {
    background-color: rgba(32, 107, 196, 0.1);
    border: 2px solid #206bc4;
}

#headerSolicitacoes:hover,
#headerOcorrencias:hover {
    background-color: rgba(32, 107, 196, 0.05);
}

/* Accordion row styling */
.accordion-row {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.accordion-row:hover {
    background-color: var(--tr-highlight-modal) !important;
}

.accordion-row .icon-chevron-right {
    transition: transform 0.3s ease;
}

/* Details row styling */
.accordion-details {
    background-color: rgba(0, 0, 0, 0.02);
}

[data-theme="dark"] .accordion-details {
    background-color: rgba(255, 255, 255, 0.02);
}

.accordion-details td {
    border-top: 2px solid #206bc4 !important;
}

/* Loading overlay for accordion details */
.accordion-details .loading-overlay {
    position: relative;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Badge visibility */
#ocorrenciasBadge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
} 