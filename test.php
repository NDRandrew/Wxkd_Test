// CRITICAL FIX 1: Pass API errors to backend when generating TXT
window.gerarTXTSelection = function() {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    showNotification('Iniciando verificação de CNPJs...', 'info');
    
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        dataType: 'json',
        data: {
            acao: 'get_cnpjs_for_verification',
            solicitacoes: JSON.stringify(solicitacoes)
        },
        success: function(data) {
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                return;
            }
            
            const cnpjList = data.cnpjs;
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            
            verifyAllCNPJs(cnpjList, function(processed, total, updated, errors) {
                updateProgressModal(progressModal, processed, total, updated, errors);
            }).then(function(verificationResult) {
                setTimeout(function() {
                    progressModal.remove();
                }, 2000);
                
                showNotification('Verificação concluída! Gerando TXT...', 'success');
                
                // FIX: Pass API errors to backend
                $.ajax({
                    url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
                    method: 'POST',
                    data: {
                        acao: 'gerar_txt_selection',
                        solicitacoes: JSON.stringify(solicitacoes),
                        api_errors: JSON.stringify(verificationResult.apiErrors || []) // ADDED
                    },
                    success: function(text, status, xhr) {
                        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                        
                        if (verificationResult.errors > 0) {
                            showNotification(
                                'TXT gerado. ' + verificationResult.errors + ' erro(s) - verifique a aba Ocorrências.', 
                                'warning'
                            );
                            updateUnviewedCount(); // Refresh ocorrencias badge
                        } else {
                            showNotification('Arquivo TXT gerado com sucesso!', 'success');
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification('Erro ao gerar TXT: ' + error, 'error');
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            showNotification('Erro ao obter CNPJs: ' + error, 'error');
        }
    });
};

// FIX 2: Add function to load per-row chat unread counts
function updateChatBadges() {
    document.querySelectorAll('[data-cod-solicitacao]').forEach(function(badge) {
        const codSolicitacao = badge.getAttribute('data-cod-solicitacao');
        
        const formData = new FormData();
        formData.append('acao', 'get_chat_unread_count');
        formData.append('cod_solicitacao', codSolicitacao);
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        })
        .catch(error => console.error('Error loading badge:', error));
    });
}

// Call after table loads
function updateUI(data, params) {
    const tableBody = document.getElementById('tableBody');
    const modalsContainer = document.getElementById('modalsContainer');
    
    if (tableBody) tableBody.innerHTML = data.html;
    if (modalsContainer) {
        modalsContainer.innerHTML = data.modals;
        attachEmailHandlers();
    }
    
    updatePaginationInfo(data);
    updatePaginationControls();
    
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.pushState({}, '', newUrl);
    
    highlightActiveFilters();
    initializeCheckboxHandlers();
    
    if (!window.userPermissions || !window.userPermissions.canViewBulk) {
        const bulkActions = document.getElementById('bulkActions');
        if (bulkActions) bulkActions.style.display = 'none';
    }
    
    if (window.pageState && !window.pageState.autoLoadData) {
        document.getElementById('tableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    if (window.pageState) window.pageState.autoLoadData = false;
    
    // FIX: Update chat badges after table renders
    setTimeout(updateChatBadges, 500);
}

// FIX 3: Mark messages as read when chat is opened
window.initializeChat = function(codSolicitacao) {
    const firstContact = document.querySelector('.chat-contact[data-solicitacao="' + codSolicitacao + '"]');
    if (firstContact) {
        const targetGroup = firstContact.getAttribute('data-group');
        loadChatForGroup(codSolicitacao, targetGroup);
        startChatPolling(codSolicitacao);
        
        // Update badge after marking as read
        setTimeout(function() {
            updateChatBadges();
        }, 1000);
    }
};


--------------

<?php
// Add to encerramento_massa.php - FIX 1: Handle API errors from frontend

if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
    $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
    $apiErrors = json_decode($_POST['api_errors'] ?? '[]', true); // ADDED
    
    $result = $handler->generateFromSelection($solicitacoes);
    
    // Merge API errors with TXT generation errors
    if (!empty($apiErrors)) {
        foreach ($apiErrors as $apiError) {
            $handler->errors[] = $apiError;
        }
    }
    
    ob_end_clean();
    
    if ($result['success']) {
        // Insert ALL errors (API + TXT) to database
        if (!empty($handler->errors)) {
            $handler->insertErrorToDatabase($handler->errors, $result['nomeArquivo']);
        }
        
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
        header('Content-Length: ' . strlen($result['conteudo']));
        echo $result['conteudo'];
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    exit;
}

// Add to ajax_encerramento.php - FIX 2: Get per-chat unread count
if (isset($_POST['acao']) && $_POST['acao'] == 'get_chat_unread_count') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        $model = new Analise();
        
        // Get chat_id for this solicitacao
        $query = "SELECT LOCAL_CHAT FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
        $result = $model->sql->select($query);
        
        if (!$result || !isset($result[0]['LOCAL_CHAT'])) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'count' => 0]);
            exit;
        }
        
        $chat_id = intval($result[0]['LOCAL_CHAT']);
        
        // Count unread messages for this chat
        $countQuery = "SELECT COUNT(*) as TOTAL 
                FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                WHERE CHAT_ID = " . intval($chat_id) . "
                AND (READ_FLAG IS NULL OR READ_FLAG = 0)
                AND REMETENTE != " . intval($cod_usu) . "
                AND RECIPIENT_GROUP = '" . addslashes($userGroup) . "'";
        
        $countResult = $model->sql->select($countQuery);
        $count = isset($countResult[0]['TOTAL']) ? intval($countResult[0]['TOTAL']) : 0;
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

------------

<?php
// Update in ajax_encerramento.php - renderTableCell method

private function renderTableCell($row) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canViewBulk = canViewBulkActions($cod_usu);
    
    $cells = '';
    
    if ($canViewBulk) {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                    <input class="form-check-input position-static m-0" type="checkbox" onclick="event.stopPropagation();" />
                    <span class="form-check-label d-none"></span>
                </label>
            </th>';
    } else {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important; width: 30px;"></th>';
    }
    
    // FIX: Add chat badge beside COD_SOLICITACAO
    $cells .= '<th>
        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span>' . htmlspecialchars($row['COD_SOLICITACAO']) . '</span>
            <span class="badge badge-sm bg-red text-red-fg" 
                  data-cod-solicitacao="' . htmlspecialchars($row['COD_SOLICITACAO']) . '"
                  style="display: none; cursor: pointer;"
                  onclick="event.stopPropagation(); openChatForSolicitacao(' . htmlspecialchars($row['COD_SOLICITACAO']) . ')">
                0
            </span>
        </div>
    </th>';
    
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_AG']) . '/' . htmlspecialchars($row['NR_PACB']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['CHAVE_LOJA']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . $row['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
    $cells .= $this->renderDateCell($row['DATA_RETIRADA_EQPTO']);
    $cells .= $this->renderBloqueioCell($row['DATA_BLOQUEIO']);
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['DATA_LAST_TRANS']) . '</span></td>';
    $cells .= $this->renderTextCell($row['MOTIVO_BLOQUEIO'], 'Sem Motivo de Bloqueio');
    $cells .= $this->renderTextCell($row['DESC_MOTIVO_ENCERRAMENTO'], 'Sem Motivo de Encerramento');
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['ORGAO_PAGADOR']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . $this->printCustomCluster($row['CLUSTER']) . '</span></td>';
    $cells .= $this->renderAptoCell($row['PARM']);
    $cells .= $this->renderAptoCell($row['TRAG']);
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_CONTABEIS']) . '</span></td>';
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_NEGOCIO']) . '</span></td>';
    
    return $cells;
}
?>

--------

// Add to analise_encerramento.js

// FIX: Open modal and switch to chat tab when badge is clicked
window.openChatForSolicitacao = function(codSolicitacao) {
    const modalElement = document.getElementById('AnaliseDetalhesModal' + codSolicitacao);
    if (modalElement) {
        // Open modal
        openModal(modalElement);
        
        // Wait for modal to open, then switch to chat tab
        setTimeout(function() {
            const chatTab = modalElement.querySelector('a[href="#tabs-chat-' + codSolicitacao + '"]');
            if (chatTab) {
                // Trigger Bootstrap tab switch
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                    const bsTab = new bootstrap.Tab(chatTab);
                    bsTab.show();
                } else if (typeof $ !== 'undefined' && $.fn.tab) {
                    $(chatTab).tab('show');
                } else {
                    // Manual tab switch
                    modalElement.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                    modalElement.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active', 'show'));
                    
                    chatTab.classList.add('active');
                    const targetPane = modalElement.querySelector('#tabs-chat-' + codSolicitacao);
                    if (targetPane) {
                        targetPane.classList.add('active', 'show');
                    }
                }
                
                // Initialize chat
                initializeChat(codSolicitacao);
            }
        }, 300);
    }
};